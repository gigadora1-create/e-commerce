<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class InventoryAvailabilityService
{
    /**
     * Precarga datos de disponibilidad para múltiples SKUs
     * 
     * @param array $skus
     * @param array $warehouses
     * @param array $customers
     * @return array ['cache_key' => ['inventories' => [], 'available' => []]]
     */
    public function preloadAvailability(array $skus, array $warehouses, array $customers): array
    {
        Log::info("=== PRECARGANDO DISPONIBILIDAD (LÓGICA EXACTA DE VISTA) ===");
        Log::info("SKUs: " . count($skus) . ", Bodegas: " . count($warehouses) . ", Clientes: " . count($customers));
        
        $result = [
            'inventory_cache' => [],
            'availability_cache' => [],
            'all_skus' => []
        ];
        
    
        $allSkusInSystem = DB::table('inventories')
            ->whereIn('sku', $skus)
            ->select('sku', 'warehouse', 'customer', 'item_description')
            ->distinct()
            ->get();
        
        foreach ($allSkusInSystem as $inv) {
            $result['all_skus'][$inv->sku][] = [
                'warehouse' => $inv->warehouse,
                'customer' => $inv->customer,
                'item_description' => $inv->item_description
            ];
        }
        
        
        $inventories = DB::table('inventories')
            ->whereIn('sku', $skus)
            ->whereIn('warehouse', $warehouses)
            ->whereIn('customer', $customers)
            ->where('status', '!=', 'RETENCION')
            ->select(
                'id', 'item_id', 'sku', 'item_description', 'warehouse', 'customer',
                'batch', 'expiry_date', 'localizacion', 'location_id',
                'quantity', 'status', 'entry_date', 'value'
            )
            ->orderBy('id') 
            ->get();
        
        Log::info("Inventarios base encontrados: " . $inventories->count());
        
        foreach ($inventories as $inv) {
            $key = $this->getCacheKey($inv->sku, $inv->warehouse, $inv->customer);
            if (!isset($result['inventory_cache'][$key])) {
                $result['inventory_cache'][$key] = $inv;
            }
        }
        
        $inventoryIds = $inventories->pluck('id')->toArray();
        $outputsSummary = $this->getOutputsSummary($inventoryIds);
        $reservationsSummary = $this->getReservationsSummary($inventoryIds);
        $locationCaps = $this->getLocationCaps($inventories, $customers, $warehouses);
        $reservedByLocation = $this->getReservedByLocation($inventories, $reservationsSummary);
        $remainingByLocation = $this->getRemainingByLocation($locationCaps, $reservedByLocation);
        
        $retentionsSummary = $this->getRetentionsSummary($skus, $warehouses, $customers);
        

        $retentionAssignments = $this->assignRetentionsToInventories(
            $inventories,
            $outputsSummary,
            $reservationsSummary,
            $retentionsSummary
        );
        
        // 1. Calcular totales físicos reales por ubicación para prevenir picks en ubicaciones "en deuda"
        $locationTotals = [];
        $idsByLocation = [];
        foreach ($inventories as $inv) {
            $locKey = $this->getLocationKey($inv->item_id, $inv->localizacion, $inv->warehouse, $inv->customer);
            $idsByLocation[$locKey][] = $inv->id;
        }

        foreach ($idsByLocation as $locKey => $ids) {
            $totalEntries = $inventories->whereIn('id', $ids)->sum('quantity');
            $totalOuts = 0;
            $totalDevs = 0;
            foreach ($ids as $id) {
                $totalOuts += $outputsSummary[$id]['salidas'] ?? 0;
                $totalDevs += $outputsSummary[$id]['devoluciones'] ?? 0;
            }
            $locationTotals[$locKey] = max(0, $totalEntries - $totalOuts + $totalDevs);
        }

        // 2. Inicializar acumuladores por ubicación para no sobrepasar el total físico
        $locationAccumulators = [];

        // 2. Procesar cada registro de inventario
        foreach ($inventories as $inv) {
            $key = $this->getCacheKey($inv->sku, $inv->warehouse, $inv->customer);
            $locKey = $this->getLocationKey($inv->item_id, $inv->localizacion, $inv->warehouse, $inv->customer);
            
            $totalSalidas = $outputsSummary[$inv->id]['salidas'] ?? 0;
            $totalDevoluciones = $outputsSummary[$inv->id]['devoluciones'] ?? 0;
            
            // FÓRMULA CORREGIDA: Cantidad - Salidas + Devoluciones
            $quantityCurrent = max(0, floor(($inv->quantity - $totalSalidas) + $totalDevoluciones));
            
            $locTotal = $locationTotals[$locKey] ?? 0;
            
            if ($locTotal <= 0) {
                $quantityCurrent = 0;
            } else {
                // Control acumulativo para no exceder el total físico de la ubicación con la suma de registros
                if (!isset($locationAccumulators[$locKey])) {
                    $locationAccumulators[$locKey] = 0;
                }
                
                $remainingInLocation = max(0, $locTotal - $locationAccumulators[$locKey]);
                $quantityCurrent = min($quantityCurrent, $remainingInLocation);
                
                // Actualizar lo que ya "consumimos" de la disponibilidad física de esta ubicación
                $locationAccumulators[$locKey] += $quantityCurrent;
            }
            
            $quantityReserved = $reservationsSummary[$inv->id] ?? 0;
            $totalRetentionForThisRow = $retentionAssignments[$inv->id] ?? 0;
            
            $quantityNetAvailable = max(0, floor(
                $quantityCurrent - $quantityReserved - $totalRetentionForThisRow
            ));
            
            $pickingStatus = $this->calculatePickingStatus(
                $inv->status,
                $inv->localizacion,
                $inv->expiry_date,
                $quantityCurrent
            );
            
            $isPickable = $this->isPickable(
                $inv->status,
                $inv->localizacion,
                $inv->expiry_date,
                $quantityNetAvailable
            );
            
            if ($isPickable && $quantityNetAvailable > 0) {
                if (!isset($result['availability_cache'][$key])) {
                    $result['availability_cache'][$key] = collect();
                }
                
                $result['availability_cache'][$key]->push((object)[
                    'id' => $inv->id,
                    'sku' => $inv->sku,
                    'item_description' => $inv->item_description,
                    'warehouse' => $inv->warehouse,
                    'customer' => $inv->customer,
                    'batch' => $inv->batch,
                    'expiry_date' => $inv->expiry_date,
                    'entry_date' => $inv->entry_date,
                    'localizacion' => $inv->localizacion,
                    'location_id' => $inv->location_id,
                    'quantity_original' => $inv->quantity,
                    'quantity_current' => $quantityCurrent,
                    'quantity_reserved' => $quantityReserved,
                    'total_retencion' => $totalRetentionForThisRow,
                    'quantity_net_available' => $quantityNetAvailable,
                    'total_salidas' => $totalSalidas,
                    'total_devoluciones' => $totalDevoluciones,
                    'picking_status' => $pickingStatus,
                    'inventory_status' => $inv->status,
                    'is_pickable' => $isPickable,
                    'value' => $inv->value
                ]);
            }
        }
        
        Log::info("Cache de disponibilidad creado con " . count($result['availability_cache']) . " combinaciones");
        
        return $result;
    }
    
    protected function assignRetentionsToInventories(
        $inventories,
        array $outputsSummary,
        array $reservationsSummary,
        array $retentionsSummary
    ): array {
        $assignments = [];
        

        $groupedInventories = [];
        foreach ($inventories as $inv) {
            $groupKey = $this->getRetentionGroupKey($inv->sku, $inv->batch, $inv->expiry_date);
            
            if (!isset($groupedInventories[$groupKey])) {
                $groupedInventories[$groupKey] = [];
            }
            
            $groupedInventories[$groupKey][] = $inv;
        }
        
        foreach ($groupedInventories as $groupKey => $groupInventories) {
            $totalRetention = $retentionsSummary[$groupKey] ?? 0;
            
            if ($totalRetention <= 0) {
                continue;
            }
            

            usort($groupInventories, fn($a, $b) => $a->id <=> $b->id);
            

            foreach ($groupInventories as $inv) {
                $totalSalidas = $outputsSummary[$inv->id]['salidas'] ?? 0;
                $totalDevoluciones = $outputsSummary[$inv->id]['devoluciones'] ?? 0;
                $quantityCurrent = max(0, ($inv->quantity - $totalSalidas) + $totalDevoluciones);
                $quantityReserved = $reservationsSummary[$inv->id] ?? 0;
                $quantityNetAvailableBeforeRetention = $quantityCurrent - $quantityReserved;
        
                if ($quantityNetAvailableBeforeRetention >= $totalRetention) {
                    $assignments[$inv->id] = $totalRetention;
                    
                    Log::debug("Retención asignada: ID={$inv->id}, SKU={$inv->sku}, Batch={$inv->batch}, Retención={$totalRetention}");
                    break; 
                }
            }
            
            if (!isset($assignments[$inv->id])) {
                Log::warning("Sin capacidad para retenciones en grupo: {$groupKey}, Retención={$totalRetention}");
            }
        }
        
        return $assignments;
    }
    

    public function getOutputsSummary(array $inventoryIds): array
    {
        if (empty($inventoryIds)) return [];
        
        $outputs = DB::table('inventory_outputs as io')
            ->join('inventories as i', 'io.inventory_id', '=', 'i.id')
            ->whereIn('i.id', $inventoryIds)
            ->whereIn('io.status', ['completado', 'SALIDA', 'devolucion'])
            ->select(
                'i.id as inventory_id',
                DB::raw('SUM(CASE WHEN io.status IN ("completado", "SALIDA") THEN io.quantity ELSE 0 END) as total_salidas'),
                DB::raw('SUM(CASE WHEN io.status = "devolucion" THEN io.quantity ELSE 0 END) as total_devoluciones')
            )
            ->groupBy('i.id')
            ->get();
        
        $summary = [];
        foreach ($outputs as $output) {
            $summary[$output->inventory_id] = [
                'salidas' => $output->total_salidas ?? 0,
                'devoluciones' => $output->total_devoluciones ?? 0
            ];
        }
        
        return $summary;
    }
    

    public function getReservationsSummary(array $inventoryIds): array
    {
        if (empty($inventoryIds)) return [];
        
        $reservations = DB::table('picking_details as pd')
            ->join('picking_orders as po', 'pd.picking_order_id', '=', 'po.id')
            ->whereIn('pd.inventory_id', $inventoryIds)
            ->whereIn(DB::raw('LOWER(po.status)'), [
                'pending',
                'in_progress',
                'pendiente',
                'en progreso',
                'en_progreso'
            ])
            ->select('pd.inventory_id', DB::raw('SUM(pd.quantity_picked) as quantity_reserved'))
            ->groupBy('pd.inventory_id')
            ->get();
        
        $summary = [];
        foreach ($reservations as $res) {
            $summary[$res->inventory_id] = $res->quantity_reserved ?? 0;
        }
        
        return $summary;
    }

    protected function getRetentionsSummary(array $skus, array $warehouses, array $customers): array
    {
        $query = DB::table('inventories')
            ->whereIn('sku', $skus)
            ->whereIn('warehouse', $warehouses)
            ->whereIn('customer', $customers)
            ->where('status', 'RETENCION');

        $retentions = $query->select(
                'sku', 
                'batch', 
                'expiry_date',
                DB::raw('SUM(quantity) as total_retention_group')
            )
            ->groupBy('sku', 'batch', 'expiry_date')
            ->get();
        
        $summary = [];
        foreach ($retentions as $ret) {
            $key = $this->getRetentionGroupKey($ret->sku, $ret->batch, $ret->expiry_date);
            $summary[$key] = $ret->total_retention_group ?? 0;
        }
        
        return $summary;
    }

    protected function calculatePickingStatus($status, $localizacion, $expiryDate, $quantityCurrent): string
    {
        if (strtoupper((string) $status) === 'SALIDA') {
            return 'YA_EN_SALIDA';
        }
        
        $normalizedLocation = strtoupper(trim($localizacion ?? ''));
        
        if (empty($normalizedLocation)) {
            return 'SIN_UBICACION';
        }
        
        if ($normalizedLocation === 'ALMACENAMIENTO') {
            return 'EN_ALMACENAMIENTO';
        }
        
        if (empty($expiryDate) || $expiryDate < '1900-01-01') {
            return 'SIN_FECHA_VENCIMIENTO';
        }
        
        if ($quantityCurrent <= 0) {
            return 'SIN_STOCK';
        }
        
        return 'DISPONIBLE';
    }

    protected function isPickable($status, $localizacion, $expiryDate, $quantityNetAvailable): bool
    {
        // Normalize location for robust comparison
        $normalizedLocation = strtoupper(trim($localizacion ?? ''));
        
        $isPickable = strtoupper((string) $status) !== 'SALIDA'
            && !empty($normalizedLocation)
            && $normalizedLocation !== 'ALMACENAMIENTO'
            && !empty($expiryDate)
            && $expiryDate >= '1900-01-01'
            && $quantityNetAvailable > 0;
            
        // Log if ALMACENAMIENTO somehow passes (should never happen)
        if ($normalizedLocation === 'ALMACENAMIENTO' && $isPickable) {
            Log::error("!!! CRITICAL: ALMACENAMIENTO PASSED isPickable CHECK !!!", [
                'status' => $status,
                'localizacion_raw' => $localizacion,
                'localizacion_normalized' => $normalizedLocation,
                'expiry' => $expiryDate,
                'net' => $quantityNetAvailable
            ]);
        }
        
        return $isPickable;
    }

    protected function getCacheKey($sku, $warehouse, $customer): string
    {
        return trim($sku) . '|' . trim($warehouse) . '|' . trim($customer);
    }
    

    protected function getRetentionGroupKey($sku, $batch, $expiryDate): string
    {
        return trim($sku) . '|' 
            . trim($batch ?? '') . '|' 
            . trim($expiryDate ?? '');
    }

    protected function getLocationCaps($inventories, array $customers, array $warehouses): array
    {
        $itemIds = $inventories->pluck('item_id')->unique()->filter()->values()->all();
        $locationCodes = $inventories->pluck('localizacion')->unique()->filter()->values()->all();

        if (empty($itemIds) || empty($locationCodes)) {
            return [];
        }

        $rows = DB::table('item_locations as il')
            ->join('locations as l', 'il.location_id', '=', 'l.location_id')
            ->whereIn('il.item_id', $itemIds)
            ->whereIn('l.code', $locationCodes)
            ->whereIn('l.customer', $customers)
            ->whereIn('l.warehouse', $warehouses)
            ->select('il.item_id', 'l.code as location_code', 'l.warehouse', 'l.customer', 'il.current_quantity')
            ->get();

        $caps = [];
        foreach ($rows as $row) {
            $key = $this->getLocationKey($row->item_id, $row->location_code, $row->warehouse, $row->customer);
            $caps[$key] = (int) ($row->current_quantity ?? 0);
        }

        return $caps;
    }

    protected function getReservedByLocation($inventories, array $reservationsSummary): array
    {
        $reservedByLocation = [];

        foreach ($inventories as $inv) {
            $reserved = (int) ($reservationsSummary[$inv->id] ?? 0);
            if ($reserved <= 0) {
                continue;
            }
            $key = $this->getLocationKey($inv->item_id, $inv->localizacion, $inv->warehouse, $inv->customer);
            if (!isset($reservedByLocation[$key])) {
                $reservedByLocation[$key] = 0;
            }
            $reservedByLocation[$key] += $reserved;
        }

        return $reservedByLocation;
    }

    protected function getRemainingByLocation(array $locationCaps, array $reservedByLocation): array
    {
        $remaining = [];
        foreach ($locationCaps as $key => $cap) {
            $reserved = (int) ($reservedByLocation[$key] ?? 0);
            $remaining[$key] = max(0, $cap - $reserved);
        }
        return $remaining;
    }

    protected function getLocationKey($itemId, $locationCode, $warehouse, $customer): string
    {
        return trim((string) $itemId) . '|' . trim((string) $locationCode) . '|' . trim((string) $warehouse) . '|' . trim((string) $customer);
    }
}
