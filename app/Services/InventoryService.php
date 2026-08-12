<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryOutput;
use App\Models\ItemLocation;
use App\Models\Location;
use App\Helpers\ItemLocationStockHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class InventoryService
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Registers a new stock entry or devolution.
     */
    public function registerEntry(array $data): Inventory
    {
        return DB::transaction(function () use ($data) {
            // 1. Create the inventory record
            $inventory = Inventory::create($data);

            // 2. Audit
            $this->logMovement($inventory->item_id, null, $inventory->location_id, $inventory->quantity, 'ENTRY');

            // 3. Sync ItemLocation
            ItemLocationStockHelper::syncStock($inventory->item_id, $inventory->localizacion, $inventory->warehouse, $inventory->customer);

            return $inventory;
        });
    }

    /**
     * Registers a stock exit from a specific location (handles FIFO automatically).
     */
    public function registerExitByLocation(int $itemId, int $locationId, int $quantity, array $meta): void
    {
        DB::transaction(function () use ($itemId, $locationId, $quantity, $meta) {
            $location = Location::findOrFail($locationId);
            
            // 1. Validate total available in location
            $available = $this->stockService->getAvailable($itemId, $location->code, $location->warehouse, $meta['customer'] ?? null);
            if ($available < $quantity) {
                throw new \Exception("Stock insuficiente en {$location->code}. Disponible: {$available}");
            }

            // 2. Find eligible records (FIFO)
            $records = Inventory::where('item_id', $itemId)
                ->where('location_id', $locationId)
                ->where('status', 'INGRESO')
                ->where('quantity', '>', 0)
                ->orderBy('created_at', 'asc')
                ->get();

            $remainingToExit = $quantity;
            foreach ($records as $record) {
                if ($remainingToExit <= 0) break;

                // Physical stock check is better:
                $physInRecord = $record->quantity - DB::table('inventory_outputs')->where('inventory_id', $record->id)->where('status', 'completado')->sum('quantity');
                
                $canTake = min($physInRecord, $remainingToExit);
                if ($canTake <= 0) continue;

                $this->registerExit($record->id, $canTake, $meta);
                $remainingToExit -= $canTake;
            }

            if ($remainingToExit > 0) {
                throw new \Exception("Inconsistencia: No se pudieron encontrar suficientes registros físicos para completar la salida de {$quantity} unidades.");
            }
        });
    }

    /**
     * Registers a stock exit from any location in a warehouse (FIFO across locations).
     */
    public function registerExitGlobal(int $itemId, string $warehouse, string $customer, int $quantity, array $meta): void
    {
        DB::transaction(function () use ($itemId, $warehouse, $customer, $quantity, $meta) {
            // 1. Validate total available in warehouse
            $available = $this->stockService->getAvailable($itemId, null, $warehouse, $customer);
            if ($available < $quantity) {
                throw new \Exception("Stock global insuficiente en {$warehouse}. Disponible: {$available}");
            }
            
            $remainingToExit = $quantity;

            // We find all ingress records for this item/warehouse/customer
            $records = Inventory::where('item_id', $itemId)
                ->where('warehouse', $warehouse)
                ->where('customer', $customer)
                ->where('status', 'INGRESO')
                ->where('quantity', '>', 0)
                ->whereNotNull('location_id')
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($records as $record) {
                if ($remainingToExit <= 0) break;

                $physInRecord = $record->quantity - DB::table('inventory_outputs')->where('inventory_id', $record->id)->where('status', 'completado')->sum('quantity');
                $canTake = min($physInRecord, $remainingToExit);
                if ($canTake <= 0) continue;

                $this->registerExit($record->id, $canTake, $meta);
                $remainingToExit -= $canTake;
            }

            if ($remainingToExit > 0) {
                throw new \Exception("Inconsistencia: No se pudieron encontrar suficientes registros físicos en la bodega para completar la salida de {$quantity} unidades.");
            }
        });
    }

    /**
     * Registers a stock exit (Output/Picking) for a specific inventory record.
     */
    public function registerExit(int $inventoryId, int $quantity, array $meta): InventoryOutput
    {
        return DB::transaction(function () use ($inventoryId, $quantity, $meta) {
            $inventory = Inventory::lockForUpdate()->findOrFail($inventoryId);
            
            // 1. Validate available stock
            $available = $this->stockService->getAvailable($inventory->item_id, $inventory->localizacion, $inventory->warehouse, $inventory->customer);
            if (isset($meta['picking_order_id'])) {
                $reservedForPicking = (int) DB::table('picking_details')
                    ->where('picking_order_id', $meta['picking_order_id'])
                    ->where('inventory_id', $inventory->id)
                    ->sum('quantity_picked');
                $available += $reservedForPicking;
            }
            if ($available < $quantity) {
                throw new \Exception("Stock insuficiente para SKU {$inventory->sku} en {$inventory->localizacion}. Disponible: {$available}");
            }

            // 2. Create the Output record
            $output = InventoryOutput::create(array_merge($meta, [
                'inventory_id' => $inventory->id,
                'item_id' => $inventory->item_id,
                'item_name' => $inventory->item_description,
                'batch' => $inventory->batch,
                'expiry_date' => $inventory->expiry_date,
                'localizacion' => $inventory->localizacion,
                'warehouse' => $inventory->warehouse,
                'customer' => $inventory->customer,
                'quantity' => $quantity,
                'output_date' => now(),
                'status' => 'completado',
                'location_id' => $inventory->location_id,
                'user_id' => Auth::id()
            ]));

            // 3. Update Inventory status if exhausted
            $currentPhysical = $this->stockService->getPhysical($inventory->item_id, $inventory->localizacion, $inventory->warehouse, $inventory->customer);
            if ($currentPhysical <= 0) {
                $inventory->status = 'SALIDA';
                $inventory->save();
            }

            // 4. Audit
            $this->logMovement($inventory->item_id, $inventory->location_id, null, $quantity, 'EXIT');

            // 5. Sync ItemLocation
            ItemLocationStockHelper::syncStock($inventory->item_id, $inventory->localizacion, $inventory->warehouse, $inventory->customer);

            return $output;
        });
    }

    /**
     * Reverses an exit (Devolution).
     */
    public function cancelExit(int $outputId, int $quantity): void
    {
        DB::transaction(function () use ($outputId, $quantity) {
            $output = InventoryOutput::lockForUpdate()->findOrFail($outputId);
            $inventory = Inventory::findOrFail($output->inventory_id);

            // 1. Create a negative output record (Devolution)
            InventoryOutput::create(array_merge($output->toArray(), [
                'guide' => 'DEV-' . $output->guide,
                'quantity' => -$quantity,
                'status' => 'devolucion',
                'output_date' => now(),
                'user_id' => Auth::id()
            ]));

            // 2. Reactivate inventory if it was closed
            if ($inventory->status === 'SALIDA') {
                $inventory->status = 'INGRESO';
                $inventory->save();
            }

            // 3. Audit
            $this->logMovement($inventory->item_id, null, $inventory->location_id, $quantity, 'RETURN');
        });
    }

    /**
     * Moves items between locations safely.
     * @return array [success message and new stocks]
     */
    public function moveBetweenLocations(int $itemId, int $fromLocId, int $toLocId, int $quantity, string $customer): array
    {
        return DB::transaction(function () use ($itemId, $fromLocId, $toLocId, $quantity, $customer) {
            $fromLoc = Location::findOrFail($fromLocId);
            $toLoc = Location::findOrFail($toLocId);

            $targetWarehouse = $fromLoc->warehouse;
            if (in_array($fromLoc->code, ['ALMACENAMIENTO', 'PENDIENTES']) && !in_array($toLoc->code, ['ALMACENAMIENTO', 'PENDIENTES'])) {
                $targetWarehouse = $toLoc->warehouse;
            } elseif (!in_array($fromLoc->code, ['ALMACENAMIENTO', 'PENDIENTES']) && in_array($toLoc->code, ['ALMACENAMIENTO', 'PENDIENTES'])) {
                $targetWarehouse = $fromLoc->warehouse;
            }

            // 1. Validate 'Available' stock in source
            $available = $this->stockService->getAvailable($itemId, $fromLoc->code, $targetWarehouse, $customer);
            if ($available < $quantity) {
                throw new \Exception("Stock insuficiente en {$fromLoc->code} ({$targetWarehouse}). Disponible: {$available}");
            }

            // 2. Target capacity check (Skip for Storage)
            if ($toLoc->code !== 'ALMACENAMIENTO' && !$toLoc->is_storage) {
                $toItemLoc = ItemLocation::where('item_id', $itemId)->where('location_id', $toLocId)->first();
                if (!$toItemLoc) {
                    throw new \Exception("El producto no está asignado a la ubicación destino {$toLoc->code}");
                }
                $currentInTo = $this->stockService->getPhysical($itemId, $toLoc->code, $toLoc->warehouse, $customer);
                if ($currentInTo + $quantity > $toItemLoc->max_capacity) {
                    throw new \Exception("Capacidad excedida en {$toLoc->code}. Libres: " . ($toItemLoc->max_capacity - $currentInTo));
                }
            }

            // 3. Move records (FIFO)
            $this->distributeInventoryRecords($itemId, $fromLoc->code, $toLoc->code, $toLocId, $quantity, $customer, $targetWarehouse);

            // 4. Audit
            $this->logMovement($itemId, $fromLocId, $toLocId, $quantity, 'MOVE');

            // 5. Sync ItemLocations
            ItemLocationStockHelper::syncStock($itemId, $fromLoc->code, $fromLoc->warehouse, $customer);
            ItemLocationStockHelper::syncStock($itemId, $toLoc->code, $toLoc->warehouse, $customer);

            $newStockTo = $this->stockService->getAvailable($itemId, $toLoc->code, $toLoc->warehouse, $customer);

            return [
                'message' => "Se movieron {$quantity} unidades de {$fromLoc->code} a {$toLoc->code} correctamente.",
                'new_stock' => $newStockTo
            ];
        });
    }

    /**
     * Moves items from a picker/floor location to "ALMACENAMIENTO".
     */
    public function moveToStorage(int $itemId, int $fromLocId, int $quantity, string $customer): array
    {
        $storage = Location::where('code', 'ALMACENAMIENTO')
            ->where('customer', $customer)
            ->firstOrFail();
            
        return $this->moveBetweenLocations($itemId, $fromLocId, $storage->location_id, $quantity, $customer);
    }

    /**
     * Moves items from "ALMACENAMIENTO" to a picker location.
     */
    public function moveFromStorage(int $itemId, int $toLocId, int $quantity, string $customer): array
    {
        $storage = Location::where('code', 'ALMACENAMIENTO')
            ->where('customer', $customer)
            ->firstOrFail();
            
        return $this->moveBetweenLocations($itemId, $storage->location_id, $toLocId, $quantity, $customer);
    }

    /**
     * Internal: Distributes physical inventory records between locations.
     */
    protected function distributeInventoryRecords(int $itemId, string $fromCode, string $toCode, ?int $toLocId, int $quantity, string $customer, ?string $warehouse = null)
    {
        $moved = 0;
        $query = Inventory::where('item_id', $itemId)
            ->where('localizacion', $fromCode)
            ->where('customer', $customer)
            ->where('quantity', '>', 0);
            
        if ($warehouse) {
            $query->where('warehouse', $warehouse);
        }

        $records = $query->orderBy('created_at', 'asc')->get();

        foreach ($records as $record) {
            if ($moved >= $quantity) break;

            $outputs = DB::table('inventory_outputs')
                ->where('inventory_id', $record->id)
                ->whereIn('status', ['completado', 'SALIDA'])
                ->sum('quantity');
            $returns = DB::table('inventory_outputs')
                ->where('inventory_id', $record->id)
                ->where('status', 'devolucion')
                ->sum('quantity');
            
            $physInRecord = ($record->quantity - $outputs) + $returns;
            $canTake = min($physInRecord, $quantity - $moved);
            if ($canTake <= 0) continue;

            if ($canTake == $record->quantity && DB::table('inventory_outputs')->where('inventory_id', $record->id)->count() == 0) {
                // If the whole record is moved and has no outputs, just update it
                $record->update([
                    'localizacion' => $toCode,
                    'location_id' => $toLocId,
                    'status' => 'INGRESO', // Asegurar estado activo al mover
                    'updated_at' => now()
                ]);
            } else {
                // Split the record
                $record->decrement('quantity', $canTake);
                
                $new = $record->replicate();
                $new->quantity = $canTake;
                $new->localizacion = $toCode;
                $new->location_id = $toLocId;
                $new->status = 'INGRESO'; // Asegurar estado activo en el nuevo registro
                $new->created_at = now();
                $new->save();
            }
            $moved += $canTake;
        }
    }

    /**
     * Logs a movement in the inventory_movements table if it exists.
     */
    protected function logMovement(int $itemId, ?int $fromId, ?int $toId, int $quantity, string $type)
    {
        try {
            if (Schema::hasTable('inventory_movements')) {
                DB::table('inventory_movements')->insert([
                    'item_id' => $itemId,
                    'from_location_id' => $fromId,
                    'to_location_id' => $toId,
                    'quantity' => $quantity,
                    'user_id' => Auth::id(),
                    'type' => $type,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Stock movement audit failed: ' . $e->getMessage());
        }
    }
}
