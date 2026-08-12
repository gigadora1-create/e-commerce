<?php

namespace App\Services\Supply;

use App\Models\SupplyIssueRequest;
use App\Models\SupplyProduct;
use App\Models\SupplyStockMovement;
use App\Traits\NormalizableItems;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplyIssueService
{
    use NormalizableItems;

    public function createRequest(Request $request): SupplyIssueRequest
    {
        $items = $this->normalizeItems(
            $request->input('product_id', []),
            $request->input('requested_quantity', [])
        );

        if (empty($items)) {
            throw new \Exception('Debe agregar al menos un producto con stock disponible y cantidad válida.');
        }

        return DB::transaction(function () use ($request, $items) {
            $products = SupplyProduct::query()
                ->whereIn('id', array_keys($items))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $validatedItems = [];
            $stockErrors = [];

            foreach ($items as $productId => $quantity) {
                $product = $products->get($productId);

                if (!$product || $quantity <= 0) {
                    continue;
                }

                $available = $product->available_stock;

                if ($available < $quantity) {
                    $stockErrors["requested_quantity.{$productId}"] =
                        "No hay stock suficiente para {$product->name}. Disponible: {$available}.";
                    continue;
                }

                $validatedItems[$productId] = [
                    'quantity' => $quantity,
                    'available' => $available,
                ];
            }

            if (!empty($stockErrors)) {
                throw ValidationException::withMessages($stockErrors);
            }

            if (empty($validatedItems)) {
                throw ValidationException::withMessages([
                    'product_id' => 'Debe agregar al menos un producto con stock disponible y cantidad válida.',
                ]);
            }

            $issueRequest = SupplyIssueRequest::create([
                'request_number' => $this->generateIssueNumber(),
                'requested_by_user_id' => $request->user()->id,
                'supply_client_id' => (int) $request->input('supply_client_id'),
                'status' => SupplyIssueRequest::STATUS_PREPARING,
                'request_notes' => $request->input('request_notes'),
                'requested_at' => now(),
            ]);

            foreach ($validatedItems as $productId => $data) {
                $product = $products->get($productId);
                $product->reserved_stock += $data['quantity'];
                $product->save();

                $issueRequest->items()->create([
                    'supply_product_id' => $productId,
                    'requested_quantity' => $data['quantity'],
                    'reserved_quantity' => $data['quantity'],
                    'delivered_quantity' => 0,
                    'available_quantity_at_request' => $data['available'],
                ]);

                SupplyStockMovement::create([
                    'supply_product_id' => $productId,
                    'user_id' => $request->user()->id,
                    'movement_type' => 'reserve_issue_request',
                    'quantity' => $data['quantity'],
                    'stock_on_hand_after' => (int) $product->stock_on_hand,
                    'reserved_stock_after' => (int) $product->reserved_stock,
                    'reference_type' => SupplyIssueRequest::class,
                    'reference_id' => $issueRequest->id,
                    'notes' => 'Reserva de stock para solicitud ' . $issueRequest->request_number,
                ]);
            }

            return $issueRequest;
        });
    }

    public function markReady(Request $request, SupplyIssueRequest $issueRequest): SupplyIssueRequest
    {
        if ($issueRequest->status !== SupplyIssueRequest::STATUS_PREPARING) {
            throw new \Exception('Solo se pueden marcar como listas las solicitudes en alistamiento.');
        }

        $issueRequest->update([
            'status' => SupplyIssueRequest::STATUS_READY,
            'prepared_by_user_id' => $request->user()?->id,
            'ready_at' => now(),
            'admin_notes' => $request->input('admin_notes'),
        ]);

        return $issueRequest;
    }

    public function close(Request $request, SupplyIssueRequest $issueRequest): SupplyIssueRequest
    {
        if ($issueRequest->status === SupplyIssueRequest::STATUS_CLOSED) {
            throw new \Exception('La solicitud ya fue cerrada.');
        }

        return DB::transaction(function () use ($request, $issueRequest) {
            $items = $issueRequest->items()->with('product')->lockForUpdate()->get();

            foreach ($items as $item) {
                $product = SupplyProduct::query()->lockForUpdate()->findOrFail($item->supply_product_id);
                $quantity = (int) $item->reserved_quantity;

                $product->reserved_stock = max(((int) $product->reserved_stock) - $quantity, 0);
                $product->stock_on_hand = max(((int) $product->stock_on_hand) - $quantity, 0);
                $product->save();

                $item->update([
                    'delivered_quantity' => $quantity,
                ]);

                SupplyStockMovement::create([
                    'supply_product_id' => $product->id,
                    'user_id' => $request->user()?->id,
                    'movement_type' => 'close_issue_request',
                    'quantity' => -$quantity,
                    'stock_on_hand_after' => (int) $product->stock_on_hand,
                    'reserved_stock_after' => (int) $product->reserved_stock,
                    'reference_type' => SupplyIssueRequest::class,
                    'reference_id' => $issueRequest->id,
                    'notes' => 'Salida definitiva por cierre de solicitud ' . $issueRequest->request_number,
                ]);
            }

            $issueRequest->update([
                'status' => SupplyIssueRequest::STATUS_CLOSED,
                'closed_by_user_id' => $request->user()?->id,
                'closed_at' => now(),
                'admin_notes' => $request->input('admin_notes'),
            ]);

            return $issueRequest;
        });
    }

    public function reject(Request $request, SupplyIssueRequest $issueRequest): SupplyIssueRequest
    {
        if ($issueRequest->status === SupplyIssueRequest::STATUS_REJECTED) {
            throw new \Exception('La solicitud ya fue rechazada.');
        }

        if ($issueRequest->status === SupplyIssueRequest::STATUS_CLOSED) {
            throw new \Exception('No se puede rechazar una solicitud cerrada.');
        }

        return DB::transaction(function () use ($request, $issueRequest) {
            $items = $issueRequest->items()->with('product')->lockForUpdate()->get();

            foreach ($items as $item) {
                $product = SupplyProduct::query()->lockForUpdate()->findOrFail($item->supply_product_id);
                $quantity = (int) $item->reserved_quantity;

                $product->reserved_stock = max(((int) $product->reserved_stock) - $quantity, 0);
                $product->save();

                $item->update([
                    'reserved_quantity' => 0,
                    'delivered_quantity' => 0,
                ]);

                SupplyStockMovement::create([
                    'supply_product_id' => $product->id,
                    'user_id' => $request->user()?->id,
                    'movement_type' => 'reject_issue_request',
                    'quantity' => -$quantity,
                    'stock_on_hand_after' => (int) $product->stock_on_hand,
                    'reserved_stock_after' => (int) $product->reserved_stock,
                    'reference_type' => SupplyIssueRequest::class,
                    'reference_id' => $issueRequest->id,
                    'notes' => 'Liberacion de reserva por rechazo de solicitud ' . $issueRequest->request_number,
                ]);
            }

            $issueRequest->update([
                'status' => SupplyIssueRequest::STATUS_REJECTED,
                'closed_by_user_id' => $request->user()?->id,
                'closed_at' => now(),
                'admin_notes' => $request->input('admin_notes'),
            ]);

            return $issueRequest;
        });
    }

    private function generateIssueNumber(): string
    {
        $lastNumber = (int) SupplyIssueRequest::max('id');
        return 'SAL-' . str_pad((string) ($lastNumber + 1), 6, '0', STR_PAD_LEFT);
    }
}
