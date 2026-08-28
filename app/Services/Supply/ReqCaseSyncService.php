<?php

namespace App\Services\Supply;

use App\Models\SupplyReqCaseSync;
use App\Models\SupplyRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

class ReqCaseSyncService
{
    public function syncCreatedPurchaseRequest(SupplyRequest $supplyRequest): SupplyReqCaseSync
    {
        $supplyRequest->loadMissing([
            'items.product:id,catalog_number,name',
            'requestedBy:id,name,email',
            'client:id,name,contact_name,contact_phone',
        ]);

        $sync = SupplyReqCaseSync::firstOrCreate([
            'supply_request_id' => $supplyRequest->id,
        ]);

        if ($sync->status === SupplyReqCaseSync::STATUS_SYNCED) {
            return $sync;
        }

        $payload = $this->buildPayload($supplyRequest);
        $sync->fill([
            'status' => SupplyReqCaseSync::STATUS_PENDING,
            'attempts' => $sync->attempts + 1,
            'last_attempt_at' => now(),
            'request_payload' => $payload,
            'last_error' => null,
        ])->save();

        $endpoint = rtrim((string) config('services.req_supply.url'), '/');
        $token = (string) config('services.req_supply.token');

        if ($endpoint === '' || $token === '') {
            $sync->update([
                'status' => SupplyReqCaseSync::STATUS_NOT_CONFIGURED,
                'last_error' => 'La integracion con req no esta configurada.',
            ]);

            return $sync->refresh();
        }

        try {
            $response = Http::acceptJson()
                ->withToken($token)
                ->timeout((int) config('services.req_supply.timeout', 10))
                ->retry(2, 300, throw: false)
                ->post($endpoint . '/api/v1/proveeduria/cases', $payload);

            $responsePayload = $response->json() ?? [];
            $externalCaseId = data_get($responsePayload, 'data.case_id');

            if (!$response->successful() || !is_numeric($externalCaseId)) {
                $sync->update([
                    'status' => SupplyReqCaseSync::STATUS_FAILED,
                    'response_payload' => $responsePayload,
                    'last_error' => data_get($responsePayload, 'message')
                        ?: 'req respondio HTTP ' . $response->status() . '.',
                ]);

                return $sync->refresh();
            }

            $sync->update([
                'status' => SupplyReqCaseSync::STATUS_SYNCED,
                'external_case_id' => (int) $externalCaseId,
                'response_payload' => $responsePayload,
                'last_error' => null,
                'synced_at' => now(),
            ]);
        } catch (ConnectionException|RequestException $exception) {
            $sync->update([
                'status' => SupplyReqCaseSync::STATUS_FAILED,
                'last_error' => $exception->getMessage(),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            $sync->update([
                'status' => SupplyReqCaseSync::STATUS_FAILED,
                'last_error' => 'No fue posible sincronizar el caso con req.',
            ]);
        }

        return $sync->refresh();
    }

    private function buildPayload(SupplyRequest $supplyRequest): array
    {
        return [
            'source' => 'e-commerce',
            'source_request_id' => $supplyRequest->id,
            'source_request_number' => $supplyRequest->request_number,
            'requested_at' => optional($supplyRequest->requested_at)->toIso8601String(),
            'requester' => [
                'name' => $supplyRequest->requestedBy?->name,
                'email' => $supplyRequest->requestedBy?->email,
            ],
            'client' => [
                'name' => $supplyRequest->client?->name,
                'contact_name' => $supplyRequest->client?->contact_name,
                'contact_phone' => $supplyRequest->client?->contact_phone,
            ],
            'notes' => $supplyRequest->request_notes,
            'items' => $supplyRequest->items->map(fn ($item) => [
                'source_item_id' => $item->id,
                'catalog_number' => (string) $item->product->catalog_number,
                'description' => $item->product->name,
                'requested_quantity' => (int) $item->requested_quantity,
            ])->values()->all(),
        ];
    }
}
