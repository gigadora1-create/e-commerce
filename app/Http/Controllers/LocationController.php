<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Item;
use App\Models\ItemLocation;
use App\Models\Inventory;
use App\Models\InventoryOutput;
use App\Models\City;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Helpers\ItemLocationStockHelper;
use App\Services\InventoryService;


class LocationController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->middleware('auth');
        $this->inventoryService = $inventoryService;
    }

    private function getCommonData($customer = null)
    {
        try {
            $selectedCustomers = $this->getSelectedLocationCustomers();
            $customer = $customer ?? (!empty($selectedCustomers) ? $selectedCustomers[0] : 'SKYONE');
            $warehouses = City::orderBy('city_store')
                ->pluck('city_store')
                ->unique()
                ->values();
            Log::info('Warehouses obtenidas desde cities: ', ['warehouses' => $warehouses]);

            return [
                'items' => Item::select('item_id', 'name', 'sku', 'ruta')->get(),
                'locations' => Location::whereIn('customer', $selectedCustomers)
                    ->where('is_active', true)
                    ->orderBy('code')
                    ->get(),
                'warehouses' => $warehouses,
            ];
        } catch (\Exception $e) {
            Log::error('Error al cargar datos comunes: ' . $e->getMessage(), ['exception' => $e]);
            return ['items' => collect(), 'locations' => collect(), 'warehouses' => collect()];
        }
    }

    public function index()
    {

        $this->ensureStorageLocationExists();

        $data = $this->getCommonData();
        return view('locations.index', $data);
    }

    private function ensureStorageLocationExists()
    {
        $customers = $this->getSelectedLocationCustomers();

        foreach ($customers as $customer) {
            $storageLocation = Location::where('code', 'ALMACENAMIENTO')
                ->where('customer', $customer)
                ->first();

            if (!$storageLocation) {
                Location::create([
                    'code' => 'ALMACENAMIENTO',
                    'name' => 'Almacenamiento Central',
                    'warehouse' => 'ALMACENAMIENTO',
                    'description' => 'Ubicación especial para recepción y distribución de productos',
                    'customer' => $customer,
                    'is_active' => true,
                    'is_storage' => true,
                ]);
                Log::info('Ubicación ALMACENAMIENTO creada automáticamente para cliente: ' . $customer);
            }
        }
    }

    private function getSelectedLocationCustomers(): array
    {
        $selectedCustomers = session('selected_customers', []);

        if (empty($selectedCustomers) && session('selected_customer')) {
            $selectedCustomers = [session('selected_customer')];
        }

        $selectedCustomers = array_values(array_filter($selectedCustomers));

        if (empty($selectedCustomers)) {
            return ['SKYONE'];
        }

        return Customer::query()
            ->whereIn('name', $selectedCustomers)
            ->where(function ($query) {
                $query->where('is_warehouse_client', false)
                    ->orWhereNull('is_warehouse_client');
            })
            ->pluck('name')
            ->values()
            ->all();
    }

    private function calculateAvailableStock($itemId, $locationCode, $customer, ?string $warehouse = null)
    {
        return ItemLocationStockHelper::calculateCurrentStock($itemId, $locationCode, $warehouse, $customer)['available'];
    }

    /**
     * Calcula el total de retenciones para un item en una ubicación específica
     */
    private function calculateTotalRetention($itemId, $locationCode, $customer, ?string $warehouse = null)
    {
        return ItemLocationStockHelper::calculateCurrentStock($itemId, $locationCode, $warehouse, $customer)['retained'];
    }

    private function getStockDataForLocations($itemLocations, $customer)
    {
        return ItemLocationStockHelper::getBatchStockData($itemLocations, $customer);
    }

    public function getData(Request $request)
    {
        try {
            $selectedCustomers = $this->getSelectedLocationCustomers();
            $warehouse = trim((string) $request->input('warehouse', ''));
            $warehouse = $warehouse === '' ? null : $warehouse;
            $requestedCustomer = trim((string) $request->input('customer', ''));
            $customer = in_array($requestedCustomer, $selectedCustomers, true) ? $requestedCustomer : null;
            $primaryCustomer = $customer ?? ($selectedCustomers[0] ?? null);

            if ($primaryCustomer === null) {
                return response()->json(['data' => []]);
            }
            
            $query = Location::where('is_active', true)
                ->when($warehouse, function ($q) use ($warehouse) {
                    return $q->where('warehouse', $warehouse);
                });

            if ($customer) {
                $query->where('customer', $customer);
            } else {
                $query->whereIn('customer', $selectedCustomers);
            }

            $locations = $query->orderBy('code')->get();
            
            // Note: For the rest of the logic, we might need to handle multiple customers 
            // but for simplicity and consistency with current UI, we'll use the first one if not specified
            $primaryCustomer = $customer ?? $primaryCustomer;


            // PARTE 1: Items asignados en item_locations (lógica original)
            $assignedItems = DB::table('item_locations as il')
                ->join('locations as loc', 'il.location_id', '=', 'loc.location_id')
                ->join('items as i', 'il.item_id', '=', 'i.item_id')
                ->whereIn('loc.customer', $selectedCustomers)
                ->where('loc.is_active', true)
                ->whereNotIn('loc.code', ['ALMACENAMIENTO', 'PENDIENTES'])
                ->when($warehouse, function ($query) use ($warehouse) {
                    return $query->where('loc.warehouse', $warehouse);
                })
                ->select(
                    'il.item_id',
                    'il.max_capacity',
                    'loc.location_id',
                    'loc.code as location_code',
                    'i.name as item_description',
                    'i.sku',
                    'i.ruta',
                    DB::raw("'assigned' as source")
                )
                ->get();

            // PARTE 2: Items con stock físico pero SIN asignación en item_locations
            // Replica la lógica de la vista original que mostraba productos no asignados
            $unassignedItems = DB::table('inventories as inv')
                ->join('items as i', 'inv.item_id', '=', 'i.item_id')
                ->join('locations as loc', function($join) use ($primaryCustomer) {
                    $join->on('inv.localizacion', '=', 'loc.code')
                         ->on('inv.warehouse', '=', 'loc.warehouse')
                         ->where('loc.customer', '=', $primaryCustomer);
                })
                ->leftJoin('item_locations as il', function($join) {
                    $join->on('inv.item_id', '=', 'il.item_id')
                         ->on('loc.location_id', '=', 'il.location_id');
                })
                ->where('inv.customer', $primaryCustomer)
                ->where('loc.is_active', true)
                ->whereNotIn('loc.code', ['ALMACENAMIENTO', 'PENDIENTES'])
                ->whereNull('il.item_id') // Solo items NO asignados
                ->when($warehouse, function ($query) use ($warehouse) {
                    return $query->where('loc.warehouse', $warehouse);
                })
                ->select(
                    'inv.item_id',
                    DB::raw('NULL as max_capacity'), // Sin asignación = sin capacidad definida
                    'loc.location_id',
                    'loc.code as location_code',
                    'i.name as item_description',
                    'i.sku',
                    'i.ruta',
                    DB::raw("'unassigned' as source")
                )
                ->groupBy('inv.item_id', 'loc.location_id', 'loc.code', 'i.name', 'i.sku', 'i.ruta')
                ->get();

            // COMBINAR ambas fuentes (UNION lógico)
            $stockData = $assignedItems->concat($unassignedItems);

            // Calcular stock para todos los items/ubicaciones de una vez
            $stockCalculations = $this->getStockDataForLocations($stockData, $primaryCustomer);

            // Agregar cálculos de stock a cada registro
            $stockData = $stockData->map(function ($item) use ($stockCalculations) {
                $key = $item->item_id . '|' . $item->location_code;
                $stock = $stockCalculations[$key] ?? ['available_stock' => 0, 'total_retention' => 0, 'quantity_reserved' => 0];
                $item->available_stock = $stock['available_stock'];
                $item->total_retention = $stock['total_retention'];
                $item->quantity_reserved = $stock['quantity_reserved'];
                return $item;
            });

            $stockByLocation = $stockData->groupBy('location_id')->map(function ($locationItems) {
                return $locationItems->groupBy('item_id')->map(function ($items) {
                    $firstItem = $items->first();
                    return [
                        'item_id' => $firstItem->item_id,
                        'name' => $firstItem->item_description ?? 'Sin descripción',
                        'sku' => $firstItem->sku ?? 'N/A',
                        'image_url' => $firstItem->ruta ? asset('images/' . $firstItem->ruta) : asset('images/no-image.png'),
                        'available_stock' => max(0, (int) $items->sum('available_stock')),
                        'max_capacity' => (int) ($firstItem->max_capacity ?? 0),
                        'available_capacity' => max(0, (int) (($firstItem->max_capacity ?? 0) - $items->sum('available_stock'))),
                        'total_retention' => (int) $items->sum('total_retention'),
                        'quantity_reserved' => (int) $items->sum('quantity_reserved'),
                        'status' => 'active',
                        'retention_substatus' => null,
                    ];
                })->values()->toArray();
            });


            $storageLocation = $locations->firstWhere('code', 'ALMACENAMIENTO');
            if ($storageLocation) {
                // OPTIMIZADO - Consulta directa para ALMACENAMIENTO
                $storageItemsQuery = DB::table('inventories as i')
                    ->join('items as it', 'i.item_id', '=', 'it.item_id')
                    ->where('i.customer', $primaryCustomer)
                    ->where('i.localizacion', 'ALMACENAMIENTO')
                    ->when($warehouse, function ($query) use ($warehouse) {
                        return $query->where('i.warehouse', $warehouse);
                    })
                    ->select('it.item_id', 'it.name', 'it.sku', 'it.ruta')
                    ->groupBy('it.item_id', 'it.name', 'it.sku', 'it.ruta')
                    ->get();

                $storageItems = $storageItemsQuery->map(function ($item) use ($primaryCustomer, $warehouse) {
                    $availableStock = $this->calculateAvailableStock($item->item_id, 'ALMACENAMIENTO', $primaryCustomer, $warehouse);

                    // Solo incluir si tiene stock disponible
                    if ($availableStock <= 0) {
                        return null;
                    }

                    return [
                        'item_id' => $item->item_id,
                        'name' => $item->name ?? 'Sin descripción',
                        'sku' => $item->sku ?? 'N/A',
                        'image_url' => $item->ruta ? asset('images/' . $item->ruta) : asset('images/no-image.png'),
                        'available_stock' => (int) $availableStock,
                        'max_capacity' => 999999,
                        'available_capacity' => 999999,
                        'total_retention' => 0,
                        'status' => 'available',
                        'retention_substatus' => null,
                    ];
                })->filter()->values()->toArray();

                $stockByLocation->put($storageLocation->location_id, $storageItems);

                Log::info('ALMACENAMIENTO PROCESADO', [
                    'location_id' => $storageLocation->location_id,
                    'items_count' => count($storageItems),
                    'items' => array_column($storageItems, 'name')
                ]);
            }

            $locationsData = $locations->map(function ($location) use ($stockByLocation) {
                $items = $stockByLocation->get($location->location_id, []);
                $totalStock = array_sum(array_column($items, 'available_stock'));
                $totalCapacity = array_sum(array_column($items, 'max_capacity'));
                $availableCapacity = max(0, $totalCapacity - $totalStock);
                $totalRetentions = array_sum(array_column($items, 'total_retention'));

                $isStorage = $location->code === 'ALMACENAMIENTO' || $location->is_storage;
                $isPending = $location->code === 'PENDIENTES';

                return [
                    'location_id' => $location->location_id,
                    'code' => $location->code ?? 'N/A',
                    'name' => $location->name ?? 'Sin nombre',
                    'warehouse' => $location->warehouse ?? 'N/A',
                    'customer' => $location->customer ?? 'N/A',
                    'description' => $location->description ?? '',
                    'is_pending' => $isPending,
                    'is_storage' => $isStorage,
                    'items' => $items,
                    'total_stock' => max(0, (int) $totalStock),
                    'total_capacity' => $isStorage ? 999999 : (int) $totalCapacity,
                    'available_capacity' => $isStorage ? 999999 : (int) $availableCapacity,
                    'total_retentions' => (int) $totalRetentions,
                    'alert' => $this->getAlertLevel($totalStock, $isStorage ? 999999 : $totalCapacity),
                ];
            });


            $pendingLocation = $locations->firstWhere('code', 'PENDIENTES');
            if ($pendingLocation) {
                // OPTIMIZADO - Consulta directa para retenciones (PENDIENTES)
                $pendingItems = DB::table('inventories as i')
                    ->join('items as it', 'i.item_id', '=', 'it.item_id')
                    ->where('i.customer', $primaryCustomer)
                    ->where('i.status', 'RETENCION')
                    ->when($warehouse, function ($query) use ($warehouse) {
                        return $query->where('i.warehouse', $warehouse);
                    })
                    ->select(
                        'it.item_id',
                        'it.name as item_description',
                        'it.sku',
                        'it.ruta',
                        DB::raw('SUM(i.quantity) as total_retention')
                    )
                    ->groupBy('it.item_id', 'it.name', 'it.sku', 'it.ruta')
                    ->havingRaw('SUM(i.quantity) > 0')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'item_id' => $item->item_id,
                            'name' => $item->item_description ?? 'Sin descripción',
                            'sku' => $item->sku ?? 'N/A',
                            'image_url' => $item->ruta ? asset('images/' . $item->ruta) : asset('images/no-image.png'),
                            'available_stock' => 0,
                            'max_capacity' => 0,
                            'total_retention' => (int) $item->total_retention,
                            'status' => 'RETENCION',
                            'retention_substatus' => null,
                        ];
                    })->toArray();

                $locationsData = $locationsData->map(function ($location) use ($pendingLocation, $pendingItems) {
                    if ($location['code'] === 'PENDIENTES') {
                        $location['items'] = $pendingItems;
                        $location['total_retentions'] = array_sum(array_column($pendingItems, 'total_retention'));
                    }
                    return $location;
                });
            }

            return response()->json(['data' => $locationsData]);
        } catch (\Exception $e) {
            Log::error('Error en getData: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Error al cargar datos de ubicaciones: ' . $e->getMessage()], 500);
        }
    }

    private function getAlertLevel($stock, $capacity)
    {
        if ($capacity <= 0)
            return 'success';
        $percentage = ($stock / $capacity) * 100;
        if ($percentage >= 90)
            return 'danger';
        if ($percentage >= 70)
            return 'warning';
        return 'success';
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:10|unique:locations,code',
            'name' => 'required|string|max:255',
            'warehouse' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
        ]);

        try {

            $isStorage = $request->code === 'ALMACENAMIENTO';

            $location = Location::create([
                'code' => $request->code,
                'name' => $request->name,
                'warehouse' => $request->warehouse,
                'description' => $request->description,
                'customer' => !empty(session('selected_customers', [])) ? session('selected_customers')[0] : 'SKYONE',
                'is_active' => true,
                'is_storage' => $isStorage
            ]);

            return response()->json([
                'success' => 'Ubicación creada correctamente',
                'location' => $location
            ]);
        } catch (\Exception $e) {
            Log::error('Error al crear ubicación: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Error al crear la ubicación: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'code' => 'required|string|max:10|unique:locations,code,' . $id . ',location_id',
            'name' => 'required|string|max:255',
            'warehouse' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
        ]);

        try {
            $location = Location::findOrFail($id);


            if (($location->code === 'ALMACENAMIENTO' || $location->code === 'PENDIENTES') && $request->code !== $location->code) {
                return response()->json(['error' => 'No se puede cambiar el código de ubicaciones especiales'], 400);
            }

            $location->update([
                'code' => $request->code,
                'name' => $request->name,
                'warehouse' => $request->warehouse,
                'description' => $request->description,
            ]);

            return response()->json([
                'success' => 'Ubicación actualizada correctamente',
                'location' => $location
            ]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar ubicación: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Error al actualizar ubicación: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $location = Location::findOrFail($id);


            if ($location->code === 'ALMACENAMIENTO' || $location->code === 'PENDIENTES') {
                return response()->json(['error' => 'No se puede eliminar ubicaciones especiales del sistema'], 400);
            }

            $hasItems = ItemLocation::where('location_id', $id)->exists();
            if ($hasItems) {
                return response()->json(['error' => 'No se puede eliminar una ubicación con productos asignados'], 400);
            }

            $location->delete();
            return response()->json(['success' => 'Ubicación eliminada correctamente']);
        } catch (\Exception $e) {
            Log::error('Error al eliminar ubicación: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Error al eliminar ubicación: ' . $e->getMessage()], 500);
        }
    }

    public function getStorageItems()
    {
        try {
            $selectedCustomers = session('selected_customers', []);
            $customer = !empty($selectedCustomers) ? $selectedCustomers[0] : 'SKYONE';


            $storageItems = DB::table('inventories as inv')
                ->join('items as i', 'inv.item_id', '=', 'i.item_id')
                ->where('inv.customer', $customer)
                ->where('inv.status', 'available')
                ->whereNull('inv.location_id')
                ->select(
                    'i.item_id',
                    'i.name',
                    'i.sku',
                    'i.ruta',
                    DB::raw('SUM(inv.quantity) as available_stock')
                )
                ->groupBy('i.item_id', 'i.name', 'i.sku', 'i.ruta')
                ->having('available_stock', '>', 0)
                ->get();

            return response()->json(['data' => $storageItems]);
        } catch (\Exception $e) {
            Log::error('Error al obtener items de almacenamiento: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Error al obtener items'], 500);
        }
    }

    public function moveToPending(Request $request)
    {
        $request->validate(['item_id' => 'required|exists:items,item_id']);

        try {
            DB::beginTransaction();

            $pendingLocation = Location::firstOrCreate(
                ['code' => 'PENDIENTES', 'customer' => (!empty(session('selected_customers', [])) ? session('selected_customers')[0] : 'SKYONE')],
                [
                    'name' => 'Productos en Retención',
                    'warehouse' => 'RETENCIONES',
                    'customer' => (!empty(session('selected_customers', [])) ? session('selected_customers')[0] : 'SKYONE'),
                    'is_active' => true
                ]
            );

            $existingAssignment = ItemLocation::where('item_id', $request->item_id)
                ->where('location_id', $pendingLocation->location_id)
                ->first();

            if ($existingAssignment) {
                return response()->json(['error' => 'El producto ya está en PENDIENTES'], 400);
            }

            // OPTIMIZADO - Calcular retenciones directamente
            $selectedCustomers = session('selected_customers', []);
            $retentionQuantity = DB::table('inventories as i')
                ->where('i.item_id', $request->item_id)
                ->whereIn('i.customer', $selectedCustomers)
                ->where('i.status', 'RETENCION')
                ->sum('i.quantity') ?? 0;

            if ($retentionQuantity <= 0) {
                return response()->json(['error' => 'El producto no tiene retenciones registradas'], 400);
            }

            ItemLocation::create([
                'item_id' => $request->item_id,
                'location_id' => $pendingLocation->location_id,
                'max_capacity' => $retentionQuantity,
                'current_quantity' => $retentionQuantity,
                'assigned_at' => now(),
            ]);

            Inventory::where('item_id', $request->item_id)
                ->where('status', '!=', 'RETENCION')
                ->update(['status' => 'RETENCION']);

            DB::commit();

            return response()->json(['success' => 'Producto movido a PENDIENTES correctamente']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al mover a PENDIENTES: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getValidLocationsForItem(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:items,item_id'
        ]);

        try {
            $selectedCustomers = session('selected_customers', []);
            $customer = !empty($selectedCustomers) ? $selectedCustomers[0] : 'SKYONE';


            // OPTIMIZADO - Consulta directa sin vw_inventory_unified
            $validLocations = DB::table('item_locations as il')
                ->join('locations as l', 'il.location_id', '=', 'l.location_id')
                ->where('il.item_id', $request->item_id)
                ->whereIn('l.customer', $selectedCustomers)
                ->where('l.is_active', true)
                ->whereNotIn('l.code', ['PENDIENTES', 'ALMACENAMIENTO'])
                ->select(
                    'l.location_id',
                    'l.code',
                    'l.name',
                    'l.warehouse',
                    'il.max_capacity'
                )
                ->get()
                ->map(function ($location) use ($request, $customer) {
                    $currentStock = $this->calculateAvailableStock($request->item_id, $location->code, $customer);
                    return [
                        'location_id' => $location->location_id,
                        'code' => $location->code,
                        'name' => $location->name,
                        'warehouse' => $location->warehouse,
                        'current_quantity' => $currentStock,
                        'max_capacity' => $location->max_capacity,
                        'available_capacity' => max(0, $location->max_capacity - $currentStock)
                    ];
                });

            return response()->json(['data' => $validLocations]);
        } catch (\Exception $e) {
            Log::error('Error al obtener ubicaciones válidas: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Error al obtener ubicaciones'], 500);
        }
    }

    public function assignItem(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:items,item_id',
            'location_id' => 'required|exists:locations,location_id',
            'max_capacity' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();


            $existing = ItemLocation::where('item_id', $request->item_id)
                ->where('location_id', $request->location_id)
                ->first();

            if ($existing) {
                return response()->json(['error' => 'El producto ya está asignado a esta ubicación'], 400);
            }


            ItemLocation::create([
                'item_id' => $request->item_id,
                'location_id' => $request->location_id,
                'current_quantity' => 0,
                'max_capacity' => $request->max_capacity,
                'assigned_at' => now()
            ]);

            DB::commit();
            return response()->json(['success' => 'Producto asignado correctamente']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al asignar producto: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Error al asignar producto: ' . $e->getMessage()], 500);
        }
    }

    public function updateItemCapacity(Request $request, $locationId, $itemId)
    {
        $request->validate([
            'max_capacity' => 'required|integer|min:1',
        ]);

        try {
            $itemLocation = ItemLocation::where('location_id', $locationId)
                ->where('item_id', $itemId)
                ->firstOrFail();


            // OPTIMIZADO - Calcular stock directamente
            $location = Location::find($locationId);
            $selectedCustomers = session('selected_customers', []);
            $customer = !empty($selectedCustomers) ? $selectedCustomers[0] : 'SKYONE';

            if (!$location) {
                throw new \Exception('Ubicación no encontrada');
            }

            $currentStock = $this->calculateAvailableStock($itemId, $location->code, $customer);

            if ($request->max_capacity < $currentStock) {
                return response()->json([
                    'error' => 'La capacidad máxima no puede ser menor al stock actual (' . $currentStock . ')'
                ], 400);
            }

            $itemLocation->update(['max_capacity' => $request->max_capacity]);

            return response()->json(['success' => 'Capacidad del producto actualizada correctamente']);
        } catch (\Exception $e) {
            Log::error('Error al actualizar capacidad del producto: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Error al actualizar capacidad'], 500);
        }
    }


    public function syncItemLocations()
    {
        try {
            DB::beginTransaction();

            $itemLocations = ItemLocation::all();

            foreach ($itemLocations as $itemLocation) {
                $location = Location::find($itemLocation->location_id);
                if (!$location)
                    continue;


                $realStock = DB::table('inventories')
                    ->where('item_id', $itemLocation->item_id)
                    ->where('localizacion', $location->code)
                    ->whereIn('status', ['INGRESO', 'DEVOLUCION', 'LIBERADO'])
                    ->sum('quantity');


                $itemLocation->update(['current_quantity' => $realStock]);

                Log::info('SINCRONIZADO', [
                    'item_id' => $itemLocation->item_id,
                    'location_code' => $location->code,
                    'stock_real' => $realStock
                ]);
            }

            DB::commit();
            return response()->json(['success' => 'Sincronización completada']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ERROR EN SINCRONIZACIÓN', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getAvailableItems(Request $request)
    {
        try {
            $selectedCustomers = session('selected_customers', []);
            $items = Item::select('item_id', 'name', 'sku', 'ruta')
                ->whereIn('customer', $selectedCustomers)
                ->get();

            return response()->json(['data' => $items]);
        } catch (\Exception $e) {
            Log::error('Error al obtener items: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Error al obtener items'], 500);
        }
    }

    public function moveToStorage(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:items,item_id',
            'from_location_id' => 'required|exists:locations,location_id',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $selectedCustomers = session('selected_customers', []);
            $customer = !empty($selectedCustomers) ? $selectedCustomers[0] : 'SKYONE';
            $result = $this->inventoryService->moveToStorage(
                $request->item_id,
                $request->from_location_id,
                $request->quantity,
                $customer
            );

            return response()->json([
                'success' => $result['message'],
                'data' => [
                    'moved_quantity' => $request->quantity,
                    'origin_stock_after' => $result['new_stock']
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('ERROR AL MOVER A ALMACENAMIENTO', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function moveFromStorage(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:items,item_id',
            'to_location_id' => 'required|exists:locations,location_id',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $selectedCustomers = session('selected_customers', []);
            $customer = !empty($selectedCustomers) ? $selectedCustomers[0] : 'SKYONE';
            $result = $this->inventoryService->moveFromStorage(
                $request->item_id,
                $request->to_location_id,
                $request->quantity,
                $customer
            );

            return response()->json([
                'success' => $result['message'],
                'data' => [
                    'moved_quantity' => $request->quantity,
                    'destination_stock_after' => $result['new_stock']
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('ERROR AL DISTRIBUIR DESDE ALMACENAMIENTO', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function moveItem(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:items,item_id',
            'from_location_id' => 'required|exists:locations,location_id',
            'to_location_id' => 'required|exists:locations,location_id|different:from_location_id',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $selectedCustomers = session('selected_customers', []);
            $customer = !empty($selectedCustomers) ? $selectedCustomers[0] : 'SKYONE';
            $result = $this->inventoryService->moveBetweenLocations(
                $request->item_id,
                $request->from_location_id,
                $request->to_location_id,
                $request->quantity,
                $customer
            );

            return response()->json([
                'success' => $result['message'],
                'data' => [
                    'moved_quantity' => $request->quantity,
                    'from_stock_after' => $this->calculateAvailableStock($request->item_id, Location::find($request->from_location_id)->code, $customer),
                    'to_stock_after' => $result['new_stock']
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('ERROR AL MOVER PRODUCTO', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function removeItem(Request $request)
    {
        try {

            $request->validate([
                'item_id' => 'required|integer|exists:items,item_id',
                'location_id' => 'required|integer|exists:locations,location_id'
            ]);

            $itemId = $request->input('item_id');
            $locationId = $request->input('location_id');

            $location = Location::findOrFail($locationId);


            $locationItem = $location->items()->where('item_locations.item_id', $itemId)->first();

            if (!$locationItem) {
                return response()->json([
                    'error' => 'El producto no está asignado a esta ubicación'
                ], 404);
            }


            // OPTIMIZADO - Calcular stock directamente
            $selectedCustomers = session('selected_customers', []);
            $customer = !empty($selectedCustomers) ? $selectedCustomers[0] : 'SKYONE';
            $stock = $this->calculateAvailableStock($itemId, $location->code, $customer);


            if ($stock > 0) {
                return response()->json([
                    'error' => 'No se puede remover el producto porque tiene stock existente (' . $stock . ' unidades)'
                ], 400);
            }


            $location->items()->detach($itemId);

            return response()->json([
                'success' => 'Producto removido de la ubicación correctamente'
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Datos de entrada no válidos: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al remover el producto: ' . $e->getMessage()
            ], 500);
        }
    }
}
