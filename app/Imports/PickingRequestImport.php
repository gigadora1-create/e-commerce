<?php

namespace App\Imports;

use App\Models\Inventory;
use App\Models\PickingOrder;
use App\Models\PickingDetail;
use App\Models\PickingReservation;
use App\Services\InventoryAvailabilityService;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PickingRequestImport implements ToCollection, WithHeadingRow
{
    protected $items = [];
    protected $errors = [];
    protected $pickingOrder = null;
    protected $inventoryCache = [];
    protected $availabilityCache = [];
    protected $allSkusInSystem = [];
    protected $consolidatedErrors = [];
    
    protected $defaultCustomer;
    protected $allWarehousesInExcel = [];

    public function __construct(?string $defaultCustomer = null)
    {
        $this->defaultCustomer = $defaultCustomer;
        set_time_limit(300);
        ini_set('memory_limit', '512M');
        
        // Inicializar servicio
        $this->availabilityService = new InventoryAvailabilityService();
    }

    public function collection(Collection $rows)
    {
        $rowNumber = 1;
        $uniqueSkus = $rows->pluck('sku')->filter()->unique()->map(fn($sku) => trim($sku))->values()->all();
        $uniqueWarehouses = $rows->pluck('bodega')->filter()->unique()->map(fn($w) => trim($w))->values()->all();
        $uniqueCustomers = $rows->pluck('cliente')->filter()->unique()->map(fn($c) => trim($c))->values()->all();
        
        // Si no hay clientes en el Excel, usar el cliente por defecto
        if (empty($uniqueCustomers) && $this->defaultCustomer) {
            $uniqueCustomers = [$this->defaultCustomer];
        }
        
        if (empty($uniqueSkus)) {
            throw new \Exception("El archivo no contiene SKUs válidos");
        }
        
        // ⚡ OPTIMIZACIÓN: Precarga SIN usar vistas SQL
        $this->preloadInventoryDataOptimized($uniqueSkus, $uniqueWarehouses, $uniqueCustomers);

        foreach ($rows as $row) {
            $rowNumber++;
            $this->processRow($row, $rowNumber);
        }

        if (!empty($this->errors)) {
            $this->throwValidationErrors();
        }

        $this->createPickingOrder();
        $this->processAndReserveItems();
    }

    /**
     * ⚡ NUEVA FUNCIÓN: Precarga usando SOLO tablas base (sin vistas)
     */
    protected function preloadInventoryDataOptimized($skus, $warehouses, $customers)
    {
        if (empty($skus)) return;

        Log::info("=== PRECARGANDO INVENTARIO (OPTIMIZADO SIN VISTAS) ===");
        Log::info("SKUs: " . count($skus));
        Log::info("Bodegas: " . implode(', ', $warehouses));
        Log::info("Clientes: " . implode(', ', $customers));

        // Usar el servicio para cargar toda la disponibilidad
        $result = $this->availabilityService->preloadAvailability($skus, $warehouses, $customers);
        
        $this->allSkusInSystem = $result['all_skus'];
        $this->inventoryCache = $result['inventory_cache'];
        $this->availabilityCache = $result['availability_cache'];

        Log::info("SKUs encontrados en sistema: " . count($this->allSkusInSystem));
        Log::info("Cache de inventarios: " . count($this->inventoryCache));
        Log::info("Cache de disponibilidad: " . count($this->availabilityCache) . " combinaciones");
        Log::info("Total registros pickables: " . collect($this->availabilityCache)->sum(fn($c) => $c->count()));
    }
    
    protected function processRow($row, $rowNumber)
    {
        $rowErrors = [];
        $sku = trim($row['sku'] ?? '');
        $productoExcel = trim($row['producto'] ?? '');
        $cantidad = intval($row['cantidad'] ?? 0);
        $bodega = trim($row['bodega'] ?? '');
        $customer = trim($row['cliente'] ?? $this->defaultCustomer ?? '');
        $orderNumber = isset($row['pedido']) ? trim($row['pedido']) : null;

        if (empty($sku) || empty($productoExcel) || empty($bodega) || 
            empty($cantidad) || empty($customer)) {
            $rowErrors[] = "❌ FALTAN CAMPOS OBLIGATORIOS: SKU, Producto, Cantidad, Bodega o Cliente";
        }
        
        if ($cantidad <= 0) {
            $rowErrors[] = "❌ CANTIDAD INVÁLIDA - SKU '{$sku}': La cantidad debe ser mayor a 0";
        }

        if (!isset($this->allSkusInSystem[$sku])) {
            $rowErrors[] = "❌ SKU NO EXISTE - '{$sku}': Este SKU no está registrado en el sistema";
        }

        $cacheKey = $this->getCacheKey($sku, $bodega, $customer);
        $inventoryMatch = $this->inventoryCache[$cacheKey] ?? null;
        
        if (!isset($this->inventoryCache[$cacheKey])) {
            $validCombinations = collect($this->allSkusInSystem[$sku] ?? [])
                ->map(fn($item) => "🏪 '{$item['warehouse']}' | 👤 '{$item['customer']}'")
                ->unique()
                ->implode(' // ');

            $rowErrors[] = "❌ COMBINACIÓN INVÁLIDA - SKU '{$sku}'\n" .
                           "   No existe con Bodega '{$bodega}' y Cliente '{$customer}'\n" .
                           "   📋 Combinaciones válidas: {$validCombinations}";
        }

        if ($inventoryMatch && strcasecmp($productoExcel, $inventoryMatch->item_description) !== 0) {
            $rowErrors[] = "⚠️ NOMBRE DE PRODUCTO INCORRECTO\n" .
                            "   📦 SKU: '{$sku}'\n" .
                            "   📝 En Excel: '{$productoExcel}'\n" .
                            "   ✅ Correcto: '{$inventoryMatch->item_description}'\n" .
                            "   El nombre NO coincide con el sistema";
        }

        $nombreProducto = $inventoryMatch ? $inventoryMatch->item_description : $productoExcel;
        
        $availableInventories = $this->availabilityCache[$cacheKey] ?? collect();

        if ($availableInventories->isEmpty()) {
            $diagnostics = $this->diagnosticInventory($sku, $bodega, $customer);
            $rowErrors[] = "❌ SIN STOCK DISPONIBLE - PRODUCTO: '{$nombreProducto}'\n" .
                           "   📦 SKU: '{$sku}'\n" .
                           "   🏪 Bodega: '{$bodega}' | 👤 Cliente: '{$customer}'\n" .
                           "   📋 Pedido: '{$orderNumber}'\n" .
                           "   📋 {$diagnostics}";
        } else {
            $totalNetDisponible = $availableInventories->sum('quantity_net_available');

            if ($totalNetDisponible < $cantidad) {
                $ubicacionesInfo = $availableInventories->take(5)->map(function($inv) {
                    $ubicacionCompleta = $inv->localizacion ?? 'N/A';
                    $reservedInfo = $inv->quantity_reserved > 0 ? 
                        " [Reservado: {$inv->quantity_reserved}]" : "";
                    return "{$ubicacionCompleta}: {$inv->quantity_net_available} unds{$reservedInfo}";
                })->toArray();

                $ubicacionesDetalle = implode("\n   • ", $ubicacionesInfo);
                if ($availableInventories->count() > 5) {
                    $ubicacionesDetalle .= "\n   • ... y " . ($availableInventories->count() - 5) . " ubicaciones más";
                }
                
                $rowErrors[] = "🚨 STOCK INSUFICIENTE - PRODUCTO: '{$nombreProducto}'\n" .
                               "   📦 SKU: '{$sku}'\n" .
                               "   🏪 Bodega: '{$bodega}' | 👤 Cliente: '{$customer}'\n" .
                               "   📋 Pedido: '{$orderNumber}'\n" .
                               "   📊 Solicitado: {$cantidad} unidades\n" .
                               "   ✅ Disponible TOTAL: {$totalNetDisponible} unidades\n" .
                               "   📍 Ubicaciones disponibles:\n   • {$ubicacionesDetalle}";
            }

            $invalidLocations = $availableInventories->filter(fn($inv) => strtoupper($inv->localizacion) === 'ALMACENAMIENTO');

            if ($invalidLocations->isNotEmpty()) {
                $ubicacionesAlmacenamiento = $invalidLocations->pluck('localizacion')->unique()->take(3)->implode(', ');
                $rowErrors[] = "⚠️ UBICACIÓN NO PICKABLE - PRODUCTO: '{$nombreProducto}'\n" .
                               "   📦 SKU: '{$sku}'\n" .
                               "   🏪 Bodega: '{$bodega}' | 👤 Cliente: '{$customer}'\n" .
                               "   📋 Pedido: '{$orderNumber}'\n" .
                               "   📍 Stock solo en ALMACENAMIENTO (ubicaciones: {$ubicacionesAlmacenamiento})\n" .
                               "   💡 Mueva el stock a zona de picking primero";
            }

            $sinFechaVencimiento = $availableInventories->filter(fn($inv) => empty($inv->expiry_date));

            if ($sinFechaVencimiento->isNotEmpty()) {
                $ubicacionesSinFecha = $sinFechaVencimiento->pluck('localizacion')->unique()->take(3)->implode(', ');
                $rowErrors[] = "⚠️ FECHA VENCIMIENTO FALTANTE - PRODUCTO: '{$nombreProducto}'\n" .
                               "   📦 SKU: '{$sku}'\n" .
                               "   📋 Pedido: '{$orderNumber}'\n" .
                               "   📍 Registros sin fecha (ubicaciones: {$ubicacionesSinFecha})\n" .
                               "   💡 Complete las fechas de vencimiento";
            }
        }

        if (!empty($rowErrors)) {
            $this->errors[] = "📁 FILA {$rowNumber}: " . implode("\n   ", $rowErrors);
        } else {
            $this->items[] = [
                'sku' => $sku,
                'item_name' => $inventoryMatch->item_description,
                'quantity' => $cantidad,
                'warehouse' => $bodega,
                'customer' => $customer,
                'order_number' => $orderNumber
            ];
        }
    }

    protected function createPickingOrder()
    {
        if (empty($this->items)) {
            throw new \Exception("No hay items válidos para procesar");
        }

        $warehouse = $this->items[0]['warehouse'];
        $customer = $this->items[0]['customer'];
        $orderNumber = $this->items[0]['order_number'] ?? null;

        $pickingCode = PickingOrder::generatePickingCode();

        $this->pickingOrder = PickingOrder::create([
            'picking_code' => $pickingCode,
            'warehouse' => $warehouse,
            'customer' => $customer,
            'order_number' => $orderNumber,
            'status' => 'pending',
            'total_items' => count($this->items),
            'total_quantity' => array_sum(array_column($this->items, 'quantity')),
            'user_id' => auth()->id()
        ]);

        Log::info("PickingOrder creado: ID={$this->pickingOrder->id}, Code={$pickingCode}");
    }

    protected function processAndReserveItems()
    {
        $detailsToInsert = [];
        $reservationsToInsert = [];
        $allInventoryIds = [];
        
        // Recolectar todos los IDs de inventario que necesitamos bloquear
        foreach ($this->items as $item) {
            $cacheKey = $this->getCacheKey($item['sku'], $item['warehouse'], $item['customer']);
            $availableInventories = $this->availabilityCache[$cacheKey] ?? collect();
            $allInventoryIds = array_merge($allInventoryIds, $availableInventories->pluck('id')->toArray());
        }

        $allInventoryIds = array_unique($allInventoryIds);
        if (!empty($allInventoryIds)) {
            Inventory::lockForUpdate()->whereIn('id', $allInventoryIds)->get();
        }

        // Consolidar información por producto
        foreach ($this->items as $item) {
            $sku = $item['sku'];
            $warehouse = $item['warehouse'];
            $customer = $item['customer'];
            $orderNumber = $item['order_number'] ?? 'SIN PEDIDO';
            $itemName = $item['item_name'];
            $quantityNeeded = $item['quantity'];

            $cacheKey = $this->getCacheKey($sku, $warehouse, $customer);
            
            if (!isset($this->consolidatedErrors[$cacheKey])) {
                $availableInventories = $this->availabilityCache[$cacheKey] ?? collect();
                $totalAvailable = $availableInventories->sum('quantity_net_available');
                
                $this->consolidatedErrors[$cacheKey] = [
                    'sku' => $sku,
                    'item_name' => $itemName,
                    'warehouse' => $warehouse,
                    'customer' => $customer,
                    'total_requested' => 0,
                    'total_available' => $totalAvailable,
                    'orders' => [],
                    'locations' => $availableInventories
                ];
            }
            
            $this->consolidatedErrors[$cacheKey]['total_requested'] += $quantityNeeded;
            $this->consolidatedErrors[$cacheKey]['orders'][] = [
                'order_number' => $orderNumber,
                'quantity' => $quantityNeeded
            ];
        }

        // Detectar faltantes consolidados
        $errorsFound = [];
        
        foreach ($this->consolidatedErrors as $cacheKey => $productInfo) {
            $totalRequested = $productInfo['total_requested'];
            $totalAvailable = $productInfo['total_available'];
            $faltante = $totalRequested - $totalAvailable;
            
            if ($faltante > 0) {
                $ordersDetail = collect($productInfo['orders'])
                    ->map(fn($o) => "      - Pedido {$o['order_number']}: {$o['quantity']} unds")
                    ->implode("\n");
                
                $ubicacionesInfo = $productInfo['locations']->take(5)->map(function($inv) {
                    $ubicacionCompleta = $inv->localizacion ?? 'N/A';
                    $reservedInfo = $inv->quantity_reserved > 0 ? 
                        " [Reservado: {$inv->quantity_reserved}]" : "";
                    return "{$ubicacionCompleta}: {$inv->quantity_net_available} unds{$reservedInfo}";
                })->toArray();

                $ubicacionesDetalle = implode("\n      • ", $ubicacionesInfo);
                if ($productInfo['locations']->count() > 5) {
                    $ubicacionesDetalle .= "\n      • ... y " . ($productInfo['locations']->count() - 5) . " ubicaciones más";
                }
                
                $errorsFound[] = 
                    "╔════════════════════════════════════════════════════════════════════\n" .
                    "║ 🚨 STOCK INSUFICIENTE\n" .
                    "╠════════════════════════════════════════════════════════════════════\n" .
                    "║ 📦 PRODUCTO: '{$productInfo['item_name']}'\n" .
                    "║ 🔖 SKU: {$productInfo['sku']}\n" .
                    "║ 🏪 Bodega: {$productInfo['warehouse']} | 👤 Cliente: {$productInfo['customer']}\n" .
                    "╠════════════════════════════════════════════════════════════════════\n" .
                    "║ 📊 RESUMEN DE STOCK:\n" .
                    "║    ➤ Total solicitado:  {$totalRequested} unidades\n" .
                    "║    ➤ Total disponible:  {$totalAvailable} unidades\n" .
                    "║    ➤ FALTANTE:          {$faltante} unidades ❌\n" .
                    "╠════════════════════════════════════════════════════════════════════\n" .
                    "║ 📋 PEDIDOS AFECTADOS (" . count($productInfo['orders']) . "):\n" .
                    "{$ordersDetail}\n" .
                    "╠════════════════════════════════════════════════════════════════════\n" .
                    "║ 📍 UBICACIONES DISPONIBLES:\n" .
                    "      • {$ubicacionesDetalle}\n" .
                    "╚════════════════════════════════════════════════════════════════════";
            }
        }

        if (!empty($errorsFound)) {
            $message = "\n\n" .
                "╔══════════════════════════════════════════════════════════════════════════════╗\n" .
                "║                     🚨 ERRORES DE STOCK DETECTADOS                          ║\n" .
                "╚══════════════════════════════════════════════════════════════════════════════╝\n\n" .
                implode("\n\n", $errorsFound) . "\n\n" .
                "╔══════════════════════════════════════════════════════════════════════════════╗\n" .
                "║ 🔧 CORRIJA EL STOCK INSUFICIENTE Y VUELVA A INTENTAR LA IMPORTACIÓN         ║\n" .
                "║ 💡 Complete el stock faltante o ajuste las cantidades solicitadas           ║\n" .
                "╚══════════════════════════════════════════════════════════════════════════════╝\n";
            
            throw new \Exception($message);
        }

        // Si no hay errores, proceder con la reserva
        foreach ($this->items as $item) {
            $quantityNeeded = $item['quantity'];
            $sku = $item['sku'];
            $warehouse = $item['warehouse'];
            $customer = $item['customer'];
            $orderNumber = $item['order_number'] ?? null;

            $cacheKey = $this->getCacheKey($sku, $warehouse, $customer);
            $availableInventories = $this->availabilityCache[$cacheKey] ?? collect();

            $selectedInventories = $this->selectOptimalInventories($availableInventories, $quantityNeeded);

            $remainingQuantity = $quantityNeeded;

            foreach ($selectedInventories as $inv) {
                if ($remainingQuantity <= 0) break;

                $quantityToTake = min($inv->quantity_net_available, $remainingQuantity);

                $detailsToInsert[] = [
                    'picking_order_id' => $this->pickingOrder->id,
                    'inventory_id' => $inv->id,
                    'sku' => $inv->sku,
                    'item_description' => $inv->item_description,
                    'location_code' => $inv->localizacion,
                    'location_name' => $inv->localizacion,
                    'warehouse' => $inv->warehouse,
                    'customer' => $inv->customer,
                    'order_number' => $orderNumber,
                    'batch' => $inv->batch,
                    'expiry_date' => $inv->expiry_date,
                    'quantity_requested' => $quantityNeeded,
                    'quantity_picked' => $quantityToTake,
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                $reservationsToInsert[] = [
                    'picking_order_id' => $this->pickingOrder->id,
                    'inventory_id' => $inv->id,
                    'quantity_reserved' => $quantityToTake,
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                $remainingQuantity -= $quantityToTake;
                
                // Actualizar cache
                $cacheKey = $this->getCacheKey($inv->sku, $inv->warehouse, $inv->customer);
                if (isset($this->availabilityCache[$cacheKey])) {
                    $this->availabilityCache[$cacheKey] = $this->availabilityCache[$cacheKey]->map(function($item) use ($inv, $quantityToTake) {
                        if ($item->id === $inv->id) {
                            $item->quantity_net_available -= $quantityToTake;
                        }
                        return $item;
                    })->filter(function($item) {
                        return $item->quantity_net_available > 0;
                    });
                }
            }
        }

        // Insertar detalles en lotes de 500
        if (!empty($detailsToInsert)) {
            foreach (array_chunk($detailsToInsert, 500) as $chunk) {
                PickingDetail::insert($chunk);
            }
        }
        
        // Insertar reservas en lotes de 500
        if (!empty($reservationsToInsert)) {
            foreach (array_chunk($reservationsToInsert, 500) as $chunk) {
                PickingReservation::insert($chunk);
            }
        }

        $this->pickingOrder->update(['status' => 'in_progress']);
        Log::info("=== RESERVAS COMPLETADAS ===");
    }

    protected function selectOptimalInventories($inventories, $quantityNeeded)
    {
        $selected = collect();
        $remainingQty = $quantityNeeded;
        $byExpiryDate = $inventories->groupBy('expiry_date');
        
        foreach ($byExpiryDate as $lotsWithSameExpiry) {
            if ($remainingQty <= 0) break;
            
            $byLocation = $lotsWithSameExpiry->groupBy('localizacion');
            $sortedLocations = $byLocation->sortByDesc(fn($lots) => $lots->sum('quantity_net_available'));
            
            foreach ($sortedLocations as $lotsInLocation) {
                if ($remainingQty <= 0) break;
                
                $locationTotal = $lotsInLocation->sum('quantity_net_available');
                
                if ($locationTotal >= $remainingQty) {
                    foreach ($lotsInLocation->sortBy('entry_date') as $lot) {
                        if ($remainingQty <= 0) break;
                        $selected->push($lot);
                        $remainingQty -= $lot->quantity_net_available;
                    }
                    break;
                } else {
                    foreach ($lotsInLocation->sortBy('entry_date') as $lot) {
                        $selected->push($lot);
                        $remainingQty -= $lot->quantity_net_available;
                    }
                }
            }
        }
        
        return $selected;
    }

    protected function diagnosticInventory($sku, $warehouse, $customer)
    {
        // 1. Obtener TODO el stock físico sin filtros (excepto filtros de ubicación/cliente)
        $inventoryQuery = DB::table('inventories')
            ->where('sku', $sku)
            ->where('warehouse', $warehouse)
            ->where('customer', $customer);
            
        $allInventory = $inventoryQuery->get();

        if ($allInventory->isEmpty()) {
            return "❌ No existe stock físico registrado para este producto en el sistema.";
        }

        $inventoryIds = $allInventory->pluck('id')->toArray();
        
        // 2. Obtener resúmenes de procesos (Salidas y Reservas activas)
        $outputsSummary = $this->availabilityService->getOutputsSummary($inventoryIds);
        $reservationsSummary = $this->availabilityService->getReservationsSummary($inventoryIds);
        
        $totalPhysicalQty = 0;
        $totalInPickingZone = 0;
        $totalInStorageZone = 0;
        $totalReservedUnits = 0;
        $totalBlockedUnits = 0; // Salidas o Retenciones

        foreach ($allInventory as $inv) {
            $salidas = $outputsSummary[$inv->id]['salidas'] ?? 0;
            $devoluciones = $outputsSummary[$inv->id]['devoluciones'] ?? 0;
            $currentQty = max(0, $inv->quantity - $salidas + $devoluciones);
            $reservedQty = $reservationsSummary[$inv->id] ?? 0;
            
            $totalPhysicalQty += $inv->quantity;
            $totalReservedUnits += $reservedQty;
            
            // Si el estado es SALIDA o RETENCION, se considera bloqueado
            if (in_array(strtoupper($inv->status), ['SALIDA', 'RETENCION'])) {
                $totalBlockedUnits += $currentQty;
                continue;
            }

            $normalizedLocation = strtoupper(trim($inv->localizacion ?? ''));
            
            if ($normalizedLocation === 'ALMACENAMIENTO') {
                $totalInStorageZone += $currentQty;
            } elseif (!empty($normalizedLocation)) {
                $totalInPickingZone += $currentQty;
            }
        }

        $availableToPick = max(0, $totalInPickingZone - $totalReservedUnits);

        $reasons = [
            "📊 RESUMEN DE UNIDADES REALES:",
            "   • STOCK TOTAL FÍSICO ACTUAL: " . number_format($totalInPickingZone + $totalInStorageZone + $totalBlockedUnits) . " unds",
            "   • 📍 EN ZONA DE PICKING:      " . number_format($totalInPickingZone) . " unds",
            "   • 📦 EN ALMACENAMIENTO:      " . number_format($totalInStorageZone) . " unds (No utilizable en picking)",
            "   • 🔒 RESERVADO/RETENIDO:      " . number_format($totalReservedUnits + ($totalBlockedUnits - 0)) . " unds", 
            "   -------------------------------------------",
            "   ✅ DISPONIBLE PARA PICKING:  " . number_format($availableToPick) . " unds"
        ];

        if ($totalInStorageZone > 0 && $availableToPick <= 0) {
            $reasons[] = "   💡 TIP: Mueva stock de ALMACENAMIENTO a una ubicación de picking.";
        }
        
        return implode("\n", $reasons);
    }

    protected function throwValidationErrors()
    {
        $errorCount = count($this->errors);
        $maxShow = 30;
        $errorsToShow = array_slice($this->errors, 0, $maxShow);
        
        $message = "🚨 SE DETECTARON {$errorCount} ERROR(ES) DE VALIDACIÓN:\n" .
                   "═══════════════════════════════════════════════════════\n\n" .
                   implode("\n" . str_repeat("═", 80) . "\n", $errorsToShow);
        
        if ($errorCount > $maxShow) {
            $message .= "\n" . str_repeat("═", 80) . "\n" .
                        "⚠️  ... y " . ($errorCount - $maxShow) . " errores adicionales\n";
        }
        
        $message .= str_repeat("═", 80) . "\n" .
                    "🔧 CORRIJA LOS ERRORES Y VUELVA A INTENTAR LA IMPORTACIÓN\n" .
                    "💡 Revise especialmente productos con stock insuficiente o ubicaciones incorrectas";
        
        throw new \Exception($message);
    }

    protected function getCacheKey($sku, $warehouse, $customer)
    {
        return trim($sku) . '|' . trim($warehouse) . '|' . trim($customer);
    }

    public function getPickingOrder()
    {
        return $this->pickingOrder;
    }

    public function getItems()
    {
        return $this->items;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}