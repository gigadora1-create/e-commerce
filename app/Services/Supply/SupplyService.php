<?php

namespace App\Services\Supply;

use App\Models\SupplyRequest;
use App\Models\SupplyStockMovement;
use App\Traits\NormalizableItems;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplyService
{
    use NormalizableItems;

    public function createRequest(Request $request): SupplyRequest
    {
        $items = $this->normalizeItems(
            $request->input('product_id', []),
            $request->input('requested_quantity', [])
        );

        if (empty($items)) {
            throw new \Exception('Debe agregar al menos un producto con cantidad válida.');
        }

        return DB::transaction(function () use ($request, $items) {
            $supplyRequest = SupplyRequest::create([
                'requested_by_user_id' => $request->user()?->id,
                'supply_client_id' => $request->filled('supply_client_id')
                    ? (int) $request->input('supply_client_id')
                    : null,
                'status' => SupplyRequest::STATUS_REQUESTED,
                'request_notes' => $request->input('request_notes'),
                'requested_at' => now(),
            ]);

            $supplyRequest->update([
                'request_number' => $this->generateRequestNumber($supplyRequest->id),
            ]);

            foreach ($items as $productId => $quantity) {
                $supplyRequest->items()->create([
                    'supply_product_id' => $productId,
                    'requested_quantity' => $quantity,
                    'received_quantity' => 0,
                    'missing_quantity' => $quantity,
                ]);
            }

            return $supplyRequest;
        });
    }

    public function auditRequest(Request $request, SupplyRequest $supplyRequest): SupplyRequest
    {
        if (!in_array($supplyRequest->status, [
            SupplyRequest::STATUS_REQUESTED,
            SupplyRequest::STATUS_PARTIAL,
        ], true)) {
            throw new \Exception('Solo se pueden registrar recepciones para solicitudes pendientes o con faltantes.');
        }

        return DB::transaction(function () use ($request, $supplyRequest) {
            $allComplete = true;

            foreach ($supplyRequest->items as $item) {
                $product = $item->product()->lockForUpdate()->firstOrFail();
                $previousReceived = (int) $item->received_quantity;
                $receivedNow = (int) ($request->input("received_quantity.{$item->id}") ?? 0);
                $received = $previousReceived + $receivedNow;
                $missing = max($item->requested_quantity - $received, 0);

                if ($missing > 0) {
                    $allComplete = false;
                }

                $item->update([
                    'received_quantity' => $received,
                    'missing_quantity' => $missing,
                    'observation' => $request->input("observation.{$item->id}"),
                ]);

                if ($receivedNow > 0) {
                    $this->updateProductStock($product, $receivedNow, $request->user()?->id, $supplyRequest);
                }
            }

            $supplyRequest->update([
                'audited_by_user_id' => $request->user()?->id,
                'status' => $allComplete
                    ? SupplyRequest::STATUS_COMPLETE
                    : SupplyRequest::STATUS_PARTIAL,
                'audit_notes' => $request->input('audit_notes'),
                'audited_at' => now(),
                'received_by_name' => trim((string) $request->input('received_by_name')),
                'received_by_signature' => null,
                'delivered_by_name' => trim((string) $request->input('delivered_by_name')),
                'delivered_by_signature' => null,
            ]);

            return $supplyRequest;
        });
    }

    private function updateProductStock($product, int $delta, $userId, SupplyRequest $supplyRequest): void
    {
        $product->stock_on_hand = max(((int) $product->stock_on_hand) + $delta, 0);
        $product->save();

        SupplyStockMovement::create([
            'supply_product_id' => $product->id,
            'user_id' => $userId,
            'movement_type' => 'receipt_audit',
            'quantity' => $delta,
            'stock_on_hand_after' => (int) $product->stock_on_hand,
            'reserved_stock_after' => (int) $product->reserved_stock,
            'reference_type' => SupplyRequest::class,
            'reference_id' => $supplyRequest->id,
            'notes' => 'Ingreso por auditoría de proveeduría ' . $supplyRequest->request_number,
        ]);
    }

    private function generateRequestNumber(int $id): string
    {
        return 'PRV-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }
}
