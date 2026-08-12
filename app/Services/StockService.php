<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Inventory;
use App\Models\PickingReservation;
use App\Models\InventoryOutput;

class StockService
{
    /**
     * Calculates the available stock for a product in a specific location or warehouse.
     * Available = Physical - Reserved - Retained
     */
    public function getAvailable(int $itemId, ?string $locationCode = null, ?string $warehouse = null, ?string $customer = null): int
    {
        $physical = $this->getPhysical($itemId, $locationCode, $warehouse, $customer);
        $reserved = $this->getReserved($itemId, $locationCode, $warehouse, $customer);
        $retained = $this->getRetained($itemId, $locationCode, $warehouse, $customer);
        
        return max(0, $physical - $reserved - $retained);
    }

    /**
     * Calculates current physical stock (Entry Quantity - Exit Quantity).
     * Excludes records in 'RETENCION' status.
     */
    public function getPhysical(int $itemId, ?string $locationCode = null, ?string $warehouse = null, ?string $customer = null): int
    {
        $query = DB::table('inventories as i')
            ->where('i.item_id', $itemId);

        if ($locationCode) {
            $query->where('i.localizacion', $locationCode);
        }
        if ($warehouse) {
            $query->where('i.warehouse', $warehouse);
        }
        if ($customer) {
            $query->where('i.customer', $customer);
        }

        $totalEntry = (int) $query->sum('i.quantity');

        // Subtract outputs (completado, SALIDA)
        $outputQuery = DB::table('inventory_outputs as io')
            ->where('io.item_id', $itemId)
            ->whereIn('io.status', ['completado', 'SALIDA']);

        if ($locationCode) {
            $outputQuery->where('io.localizacion', $locationCode);
        }
        if ($warehouse) {
            $outputQuery->where('io.warehouse', $warehouse);
        }
        if ($customer) {
            $outputQuery->where('io.customer', $customer);
        }

        $totalExit = (int) $outputQuery->sum('io.quantity');

        // Subtract/Add returns
        $returnQuery = DB::table('inventory_outputs as io')
            ->where('io.item_id', $itemId)
            ->where('io.status', 'devolucion');

        if ($locationCode) {
            $returnQuery->where('io.localizacion', $locationCode);
        }
        if ($warehouse) {
            $returnQuery->where('io.warehouse', $warehouse);
        }
        if ($customer) {
            $returnQuery->where('io.customer', $customer);
        }

        $totalReturns = (int) $returnQuery->sum('io.quantity');

        // Physical = Entry - Exit + Returns 
        return max(0, ($totalEntry - $totalExit) + $totalReturns);
    }

    /**
     * Calculates reserved stock from active picking orders.
     */
    public function getReserved(int $itemId, ?string $locationCode = null, ?string $warehouse = null, ?string $customer = null): int
    {
        $query = DB::table('picking_details as pd')
            ->join('picking_orders as po', 'pd.picking_order_id', '=', 'po.id')
            ->join('inventories as i', 'pd.inventory_id', '=', 'i.id')
            ->where('i.item_id', $itemId)
            ->whereIn(DB::raw('LOWER(po.status)'), [
                'pending',
                'in_progress',
                'pendiente',
                'en progreso',
                'en_progreso'
            ]);

        if ($locationCode) {
            $query->where('i.localizacion', $locationCode);
        }
        if ($warehouse) {
            $query->where('i.warehouse', $warehouse);
        }
        if ($customer) {
            $query->where('i.customer', $customer);
        }

        return (int) $query->sum('pd.quantity_picked');
    }

    /**
     * Calculates stock currently in 'RETENCION' status.
     */
    public function getRetained(int $itemId, ?string $locationCode = null, ?string $warehouse = null, ?string $customer = null): int
    {
        $query = DB::table('inventories as i')
            ->where('i.item_id', $itemId)
            ->where('i.status', 'RETENCION');

        if ($locationCode) {
            $query->where('i.localizacion', $locationCode);
        }
        if ($warehouse) {
            $query->where('i.warehouse', $warehouse);
        }
        if ($customer) {
            $query->where('i.customer', $customer);
        }

        return (int) $query->sum('i.quantity');
    }
}
