<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use App\Models\ItemLocation;
use App\Models\Location;

class ItemLocationStockHelper
{
    /**
     * Sincroniza la tabla item_locations para un item y ubicación específicos
     */
    public static function syncStock(int $itemId, string $locationCode, string $warehouse, string $customer): ?array
    {
        $stock = self::calculateCurrentStock($itemId, $locationCode, $warehouse, $customer);
        $actualPhysical = $stock['available'];

        $location = Location::where('code', $locationCode)
            ->where('warehouse', $warehouse)
            ->where('customer', $customer)
            ->first();

        if ($location) {
            $itemLoc = ItemLocation::where('item_id', $itemId)
                ->where('location_id', $location->location_id)
                ->first();

            if ($itemLoc) {
                $oldQuantity = (int) $itemLoc->current_quantity;
                if ($oldQuantity !== (int) $actualPhysical) {
                    $itemLoc->current_quantity = $actualPhysical;
                    $itemLoc->save();
                    return [
                        'sku' => $itemLoc->item->sku ?? 'N/A',
                        'location' => $locationCode,
                        'old_stock' => $oldQuantity,
                        'new_stock' => (int) $actualPhysical
                    ];
                }
            } else if ($actualPhysical > 0) {
                $itemLoc = ItemLocation::create([
                    'item_id' => $itemId,
                    'location_id' => $location->location_id,
                    'location_code' => $location->code,
                    'current_quantity' => $actualPhysical,
                    'max_capacity' => 1000
                ]);
                return [
                    'sku' => $itemLoc->item->sku ?? 'N/A',
                    'location' => $locationCode,
                    'old_stock' => 0,
                    'new_stock' => (int) $actualPhysical
                ];
            }
        }
        return null;
    }

    /**
     * Versión simplificada para compatibilidad con comandos antiguos
     */
    public static function sync(int $itemId, int $locationId): ?array
    {
        $location = Location::find($locationId);
        if ($location) {
            return self::syncStock($itemId, $location->code, $location->warehouse, $location->customer);
        }
        return null;
    }

    /**
     * Calcula el stock actual (disponible y retenido) para un item y ubicación.
     */
    public static function calculateCurrentStock(int $itemId, string $locationCode, ?string $warehouse = null, ?string $customer = null): array
    {
        $query = DB::table('inventories')
            ->where('item_id', $itemId)
            ->where('localizacion', $locationCode);
            
        if ($warehouse) $query->where('warehouse', $warehouse);
        if ($customer) $query->where('customer', $customer);

        $entries = (int) $query->sum('quantity');

        $outputQuery = DB::table('inventory_outputs')
            ->where('item_id', $itemId)
            ->where('localizacion', $locationCode)
            ->whereIn('status', ['completado', 'SALIDA']);

        if ($warehouse) $outputQuery->where('warehouse', $warehouse);
        if ($customer) $outputQuery->where('customer', $customer);
            
        $outputs = (int) $outputQuery->sum('quantity');

        $returnQuery = DB::table('inventory_outputs')
            ->where('item_id', $itemId)
            ->where('localizacion', $locationCode)
            ->where('status', 'devolucion');

        if ($warehouse) $returnQuery->where('warehouse', $warehouse);
        if ($customer) $returnQuery->where('customer', $customer);

        $returns = (int) $returnQuery->sum('quantity');

        $retainedQuery = DB::table('inventories')
            ->where('item_id', $itemId)
            ->where('localizacion', $locationCode)
            ->where('status', 'RETENCION');

        if ($warehouse) $retainedQuery->where('warehouse', $warehouse);
        if ($customer) $retainedQuery->where('customer', $customer);

        $retained = (int) $retainedQuery->sum('quantity');

        $physical = max(0, $entries - $outputs + $returns);
        
        return [
            'available' => $physical - $retained,
            'retained' => $retained,
            'physical' => $physical
        ];
    }

    /**
     * Obtiene datos de stock para múltiples ubicaciones de forma eficiente (Batch).
     */
    public static function getBatchStockData($itemLocations, $customer)
    {
        if ($itemLocations->isEmpty()) return [];

        $itemIds = $itemLocations->pluck('item_id')->unique()->toArray();
        $locationCodes = $itemLocations->pluck('location_code')->unique()->toArray();

        // 1. Entradas totales y Retenciones
        $entriesData = DB::table('inventories')
            ->select(
                'item_id',
                'localizacion',
                DB::raw('SUM(quantity) as total_entries'),
                DB::raw('SUM(CASE WHEN status = "RETENCION" THEN quantity ELSE 0 END) as total_retained')
            )
            ->whereIn('item_id', $itemIds)
            ->whereIn('localizacion', $locationCodes)
            ->where('customer', $customer)
            ->groupBy('item_id', 'localizacion')
            ->get()
            ->keyBy(fn($item) => $item->item_id . '|' . $item->localizacion);

        // 2. Salidas y Devoluciones
        $outputsData = DB::table('inventory_outputs')
            ->select(
                'item_id',
                'localizacion',
                DB::raw('SUM(CASE WHEN status IN ("completado", "SALIDA") THEN quantity ELSE 0 END) as total_outputs'),
                DB::raw('SUM(CASE WHEN status = "devolucion" THEN quantity ELSE 0 END) as total_returns')
            )
            ->whereIn('item_id', $itemIds)
            ->whereIn('localizacion', $locationCodes)
            ->where('customer', $customer)
            ->groupBy('item_id', 'localizacion')
            ->get()
            ->keyBy(fn($item) => $item->item_id . '|' . $item->localizacion);

        // 3. Reservas de Picking
        $reservationsData = DB::table('picking_details as pd')
            ->join('picking_orders as po', 'pd.picking_order_id', '=', 'po.id')
            ->join('inventories as i', 'pd.inventory_id', '=', 'i.id')
            ->select(
                'i.item_id',
                'i.localizacion',
                DB::raw('SUM(pd.quantity_picked) as total_reserved')
            )
            ->whereIn('i.item_id', $itemIds)
            ->whereIn('i.localizacion', $locationCodes)
            ->whereIn(DB::raw('LOWER(po.status)'), ['pending', 'in_progress', 'pendiente', 'en progreso', 'en_progreso'])
            ->groupBy('i.item_id', 'i.localizacion')
            ->get()
            ->keyBy(fn($item) => $item->item_id . '|' . $item->localizacion);

        $result = [];
        foreach ($itemLocations as $il) {
            $key = $il->item_id . '|' . $il->location_code;
            
            $e = $entriesData[$key] ?? null;
            $o = $outputsData[$key] ?? null;
            $r = $reservationsData[$key] ?? null;

            $totalEntries = $e->total_entries ?? 0;
            $totalRetained = $e->total_retained ?? 0;
            $totalOutputs = $o->total_outputs ?? 0;
            $totalReturns = $o->total_returns ?? 0;
            $totalReserved = $r->total_reserved ?? 0;

            $physical = max(0, $totalEntries - $totalOutputs + $totalReturns);
            $available = max(0, $physical - $totalRetained);

            $result[$key] = [
                'available_stock' => $available,
                'total_retention' => $totalRetained,
                'quantity_reserved' => $totalReserved
            ];
        }

        return $result;
    }
}
