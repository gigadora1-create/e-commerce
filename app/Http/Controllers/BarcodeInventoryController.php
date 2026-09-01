<?php
namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Inventory;
use App\Models\Location;
use App\Services\InventoryService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BarcodeInventoryController extends Controller
{
    protected $inventoryService;
    protected $stockService;

    public function __construct(InventoryService $inventoryService, StockService $stockService)
    {
        $this->inventoryService = $inventoryService;
        $this->stockService = $stockService;
    }

    public function index()
    {
        $selectedCustomer = session('selected_customer');
        if (!$selectedCustomer) {
            return redirect()->route('inventories.index')->with('error', 'Debe seleccionar un cliente primero.');
        }
        return view('barcode.index');
    }

    public function searchByBarcode(Request $request)
    {
        $request->validate(['barcode' => 'required|string']);
        $barcode = $request->input('barcode');
        $item = Item::where('barcode', $barcode)->first();
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado con este código de barras'
            ]);
        }
        $selectedCustomer = session('selected_customer');
        $inventoryData = $this->getProductInventoryData($item, $selectedCustomer);
        return response()->json([
            'success' => true,
            'item' => $this->formatItem($item),
            'inventory_data' => $inventoryData
        ]);
    }

    public function searchBySkuOrBarcode(Request $request)
    {
        $request->validate(['identifier' => 'required|string']);
        $identifier = $request->input('identifier');
        $item = Item::where('sku', $identifier)->orWhere('barcode', $identifier)->first();
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ]);
        }
        $selectedCustomer = session('selected_customer');
        $inventoryData = $this->getProductInventoryData($item, $selectedCustomer);
        return response()->json([
            'success' => true,
            'item' => $this->formatItem($item),
            'inventory_data' => $inventoryData
        ]);
    }

    private function formatItem($item)
    {
        $imageUrl = $item->image_url;
        return [
            'item_id' => $item->item_id,
            'name' => $item->name,
            'description' => $item->description,
            'sku' => $item->sku,
            'barcode' => $item->barcode,
            'image_url' => $imageUrl
        ];
    }

    private function getProductInventoryData($item, $customer)
    {
        try {
            // Consulta directa a vw_inventory_unified para obtener el stock por producto y bodega
            $inventoryData = DB::table('vw_inventory_unified')
                ->where('sku', $item->sku)
                ->where('customer', $customer)
                ->where('current_stock', '>', 0)
                ->select(
                    'warehouse',
                    'location_code',
                    'location_name',
                    'current_stock',
                    'available_capacity'
                )
                ->get()
                ->map(function ($row) {
                    return [
                        'warehouse' => $row->warehouse,
                        'location_code' => $row->location_code,
                        'location_name' => $row->location_name,
                        'current_stock' => $row->current_stock,
                        'available_capacity' => $row->available_capacity
                    ];
                });

            return $inventoryData;
        } catch (\Exception $e) {
            Log::error('Error en getProductInventoryData: ' . $e->getMessage());
            return [];
        }
    }

    public function getLocationsByItemAndWarehouse(Request $request)
    {
        try {
            $request->validate([
                'item_id' => 'required|exists:items,item_id',
                'warehouse' => 'required|string'
            ]);

            $selectedCustomer = session('selected_customer');
            if (!$selectedCustomer) {
                return response()->json(['success' => false, 'message' => 'Cliente no seleccionado'], 400);
            }

            $itemId = $request->item_id;
            $warehouse = $request->warehouse;

            // Obtener ubicaciones con capacidad disponible
            $locations = DB::table('locations')
                ->join('item_locations', 'locations.location_id', '=', 'item_locations.location_id')
                ->where('locations.warehouse', $warehouse)
                ->where('item_locations.item_id', $itemId)
                ->where('locations.is_active', true)
                ->select('locations.code', 'locations.name', 'item_locations.max_capacity', 'item_locations.current_quantity')
                ->get();

            // Calcular capacidad disponible
            $locationsData = $locations->map(function ($loc) {
                $current = (int) ($loc->current_quantity ?? 0);
                $available = $loc->max_capacity - $current;
                return [
                    'code' => $loc->code,
                    'display_name' => $loc->name . ' (' . $loc->code . ')',
                    'available_capacity' => $available > 0 ? $available : 0
                ];
            })->filter(function ($loc) {
                return $loc['available_capacity'] > 0;
            });

            return response()->json([
                'success' => true,
                'locations' => $locationsData->values()
            ]);

        } catch (\Exception $e) {
            Log::error('Error en getLocationsByItemAndWarehouse: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getLocationsWithStock(Request $request)
    {
        try {
            $request->validate([
                'item_id' => 'required|exists:items,item_id',
                'warehouse' => 'required|string'
            ]);

            $selectedCustomer = session('selected_customer');
            if (!$selectedCustomer) {
                return response()->json(['success' => false, 'message' => 'Cliente no seleccionado'], 400);
            }

            $itemId = $request->item_id;
            $warehouse = $request->warehouse;

            // Obtener ubicaciones con stock
            $locations = DB::table('locations')
                ->join('item_locations', 'locations.location_id', '=', 'item_locations.location_id')
                ->where('locations.warehouse', $warehouse)
                ->where('item_locations.item_id', $itemId)
                ->where('locations.is_active', true)
                ->select('locations.code', 'locations.name')
                ->get();

            $locationsData = $locations->map(function ($loc) use ($itemId, $warehouse, $selectedCustomer) {
                $available = $this->stockService->getAvailable($itemId, $loc->code, $warehouse, $selectedCustomer);
                return [
                    'code' => $loc->code,
                    'display_name' => $loc->name . ' (' . $loc->code . ')',
                    'current_stock' => $available
                ];
            })->filter(function ($loc) {
                return $loc['current_stock'] > 0;
            });

            return response()->json([
                'success' => true,
                'locations' => $locationsData->values()
            ]);

        } catch (\Exception $e) {
            Log::error('Error en getLocationsWithStock: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

   public function storeEntry(Request $request)
{
    try {
        $selectedCustomer = session('selected_customer');
        if (!$selectedCustomer) {
            throw new \Exception('Cliente no seleccionado en sesión');
        }

        $request->validate([
            'item_id' => 'required|integer|exists:items,item_id',
            'sku' => 'required|string',
            'warehouse' => 'required|string',
            'location_code' => 'required|string|exists:locations,code',
            'batch' => 'required|string',
            'expiry_date' => 'required|date',
            'item_condition' => 'required|string',
            'entry_date' => 'required|date',
            'commerce' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'value' => 'required|numeric|min:0',
            'type' => 'required|string',
            'item_description' => 'required|string',
            'observations' => 'nullable|string',
        ]);

        $location = Location::where('code', $request->location_code)
            ->where('warehouse', $request->warehouse)
            ->where('customer', $selectedCustomer)
            ->first();
        if (!$location) {
            throw new \Exception('Ubicación no encontrada: ' . $request->location_code);
        }

        $status = 'INGRESO';
        $existing = Inventory::where('item_id', $request->item_id)
            ->where('sku', $request->sku)
            ->where('warehouse', $request->warehouse)
            ->first();

        $inventoryId = $existing ? $existing->inventory_id : (Inventory::max('inventory_id') + 1 ?? 1);

        $inventory = $this->inventoryService->registerEntry([
            'inventory_id' => $inventoryId,
            'item_id' => $request->item_id,
            'sku' => $request->sku,
            'warehouse' => $request->warehouse,
            'batch' => $request->batch,
            'expiry_date' => $request->expiry_date,
            'item_condition' => $request->item_condition,
            'entry_date' => $request->entry_date,
            'commerce' => $request->commerce,
            'quantity' => $request->quantity,
            'value' => $request->value,
            'type' => $request->type,
            'status' => $status,
            'item_description' => $request->item_description,
            'observations' => $request->observations,
            'user_id' => auth()->id(),
            'customer' => $selectedCustomer,
            'localizacion' => $request->location_code, // Campo en la tabla inventories
            'location_id' => $location->location_id,
        ]);
        Log::info('Entrada registrada: ID ' . $inventory->id);

        return response()->json([
            'success' => true,
            'message' => 'Entrada registrada correctamente para el producto: ' . $inventory->item_description,
            'inventory' => $inventory
        ]);

    } catch (\Exception $e) {
        Log::error('Error en storeEntry: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error al registrar entrada: ' . $e->getMessage()
        ], 500);
    }
}


   public function storeOutput(Request $request)
{
    try {
        $selectedCustomer = session('selected_customer');
        if (!$selectedCustomer) {
            throw new \Exception('Cliente no seleccionado en sesión');
        }

        $request->validate([
            'item_id' => 'required|integer|exists:items,item_id',
            'warehouse' => 'required|string',
            'location_code' => 'required|string|exists:locations,code',
            'quantity' => 'required|integer|min:1',
            'guide' => 'required|string',
            'declared_value' => 'required|numeric|min:0',
            'customer' => 'required|string',
        ]);

        $location = Location::where('code', $request->location_code)
            ->where('warehouse', $request->warehouse)
            ->where('customer', $selectedCustomer)
            ->first();
        if (!$location) {
            throw new \Exception('Ubicación no encontrada: ' . $request->location_code);
        }

        $this->inventoryService->registerExitByLocation(
            $request->item_id,
            $location->location_id,
            $request->quantity,
            [
                'guide' => $request->guide,
                'customer' => $selectedCustomer,
                'warehouse' => $request->warehouse,
                'declared_value' => $request->declared_value,
                'type' => 'Barcode-Exit'
            ]
        );
        Log::info('Salida registrada desde barcode', [
            'item_id' => $request->item_id,
            'location_code' => $request->location_code,
            'quantity' => $request->quantity
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Salida registrada correctamente. Guía: ' . $request->guide,
        ]);

    } catch (\Exception $e) {
        Log::error('Error en storeOutput: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error al registrar salida: ' . $e->getMessage()
        ], 500);
    }
}


    public function getWarehouses()
    {
        $selectedCustomer = session('selected_customer');
        if (!$selectedCustomer) {
            return response()->json([]);
        }
        $warehouses = Inventory::where('customer', $selectedCustomer)
                             ->distinct()
                             ->pluck('warehouse');
        return response()->json($warehouses);
    }

    public function getItemConditions()
    {
        return response()->json([
            'NUEVO',
            'USADO',
            'DAÑADO',
            'REACONDICIONADO'
        ]);
    }

    public function searchProducts(Request $request)
    {
        $request->validate(['query' => 'required|string']);
        $query = $request->input('query');
        $products = Item::where('name', 'LIKE', "%{$query}%")
            ->orWhere('sku', 'LIKE', "%{$query}%")
            ->get(['item_id', 'name', 'sku', 'barcode']);
        return response()->json($products);
    }

    public function updateBarcode(Request $request, $itemId)
    {
        $request->validate([
            'barcode' => 'nullable|string|max:255|unique:items,barcode,' . $itemId . ',item_id'
        ]);
        try {
            $item = Item::findOrFail($itemId);
            $item->barcode = $request->barcode;
            $item->save();
            return response()->json([
                'success' => true,
                'message' => 'Código de barras actualizado correctamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar código de barras: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el código de barras'
            ]);
        }
    }
}
