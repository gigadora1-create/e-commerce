<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Customer;
use App\Models\Item;
use App\Models\City;
use App\Models\ItemLocation;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\InventoryImport;
use App\Exports\InventoryExport;
use App\Helpers\StringHelper;
use App\Models\InventoryOutput;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use App\Services\InventoryService;
use App\Services\StockService;


class InventoryController extends Controller
{
    protected $inventoryService;
    protected $stockService;

    public function __construct(InventoryService $inventoryService, StockService $stockService)
    {
        $this->inventoryService = $inventoryService;
        $this->stockService = $stockService;
    }


public function index(Request $request)
{
    $user = auth()->user();
    $search = $request->input('search');
    $warehouse = $request->input('warehouse');
    $product = $request->input('product');
    $location = $request->input('location');
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');
    $selectedCustomers = session('selected_customers', []);
    $customers = Customer::all();
    $inventory_unified = new Collection();

    $detailedInventories = new Collection();
    $retentionItems = new Collection();
    $cityPermissions = [];
    $customerPermissions = [];
    $uniqueProducts = new Collection();
    $uniqueWarehouses = new Collection();
    $uniqueLocations = new Collection();

 
    $userPermissions = $user->getAllPermissions()->pluck('name')->toArray();
    
   
    $customerNames = $customers->pluck('name')->map(function($name) {
        return strtoupper($name);
    })->toArray();
    

    $cityPermissions = array_filter($userPermissions, function($permission) use ($customerNames) {
        return !in_array($permission, ['password.create', 'user.management']) && 
               !in_array(strtoupper($permission), $customerNames);
    });

    $customerPermissions = array_filter($userPermissions, function($permission) use ($customerNames) {
        return in_array(strtoupper($permission), $customerNames);
    });


    if (!empty($customerPermissions)) {
        $allowedCustomers = array_map('strtoupper', $customerPermissions);
        $customers = $customers->filter(function($customer) use ($allowedCustomers) {
            return in_array(strtoupper($customer->name), $allowedCustomers);
        });
        
      
        if (!empty($selectedCustomers)) {
            $selectedCustomers = array_filter($selectedCustomers, function($sc) use ($allowedCustomers) {
                return in_array(strtoupper($sc), $allowedCustomers);
            });
            session(['selected_customers' => array_values($selectedCustomers)]);
        }
    }

    if (!empty($selectedCustomers)) {
$getStockStatus = function ($quantity) {
    if ($quantity > 1100) {
        return 'Alta Existencias';
    } elseif ($quantity >= 1000 && $quantity <= 1100) {
        return 'Pronto a Agotar';
    } elseif ($quantity >= 1 && $quantity < 1000) {
        return 'Baja Existencias';
    } else { 
        return 'Sin Existencias';
    }
};

        // OPTIMIZADO - Consulta directa a tablas en lugar de vw_inventory_unified
        // Construir query consolidada optimizada con agregaciones correctas
        // PRE-AGREGADO: Resumen de salidas, devoluciones y últimas modificaciones por ítem/loc
        $outputsSummary = DB::table('inventory_outputs')
            ->select(
                'item_id',
                'localizacion',
                DB::raw('SUM(CASE WHEN status = "devolucion" THEN ABS(quantity) ELSE 0 END) as total_returns'),
                DB::raw('SUM(CASE WHEN status <> "devolucion" THEN quantity ELSE 0 END) as total_outputs'),
                DB::raw('MAX(updated_at) as last_output_modified')
            )
            ->groupBy('item_id', 'localizacion');

        // Query principal optimizada
        $consolidatedQuery = DB::table('inventories as i')
            ->join('items as it', 'i.item_id', '=', 'it.item_id')
            ->leftJoin('locations as l', function($join) {
                $join->on('i.localizacion', '=', 'l.code')
                     ->on('i.warehouse', '=', 'l.warehouse')
                     ->on('i.customer', '=', 'l.customer');
            })
            ->leftJoinSub($outputsSummary, 'os', function($join) {
                $join->on('i.item_id', '=', 'os.item_id')
                     ->on(DB::raw('COALESCE(l.code, i.localizacion, "")'), '=', 'os.localizacion');
            })
            ->whereIn('i.customer', $selectedCustomers)
            ->whereNotNull('it.item_id')
            ->whereNotNull('it.sku')
            ->select(
                'it.sku',
                'it.name as item_description',
                'it.item_id',
                'i.warehouse',
                'i.customer',
                DB::raw('COALESCE(l.code, i.localizacion, "") as location_code'),
                'l.name as location_name',
                'l.location_id',
                DB::raw('SUM(CASE WHEN i.status IN ("INGRESO", "SALIDA") THEN i.quantity ELSE 0 END) as original_entries'),
                DB::raw('COALESCE(os.total_returns, 0) as total_returns'),
                DB::raw('SUM(CASE WHEN i.status = "RETENCION" THEN i.quantity ELSE 0 END) as total_retention'),
                DB::raw('COALESCE(os.total_outputs, 0) as total_outputs'),
                DB::raw('GREATEST(0, 
                    SUM(CASE WHEN i.status IN ("INGRESO", "SALIDA") THEN i.quantity ELSE 0 END) - 
                    COALESCE(os.total_outputs, 0) + 
                    COALESCE(os.total_returns, 0) - 
                    SUM(CASE WHEN i.status = "RETENCION" THEN i.quantity ELSE 0 END)
                ) as stock_available'),
                DB::raw('GREATEST(
                    COALESCE(MAX(i.updated_at), "1970-01-01"), 
                    COALESCE(os.last_output_modified, "1970-01-01")
                ) as last_modified_date')
            )
            ->groupBy(
                'it.item_id', 'it.sku', 'it.name', 'i.warehouse', 'i.customer', 
                'location_code', 'l.name', 'l.location_id', 
                'os.total_returns', 'os.total_outputs', 'os.last_output_modified'
            );

        if (!empty($cityPermissions)) {
            $consolidatedQuery->whereIn('i.warehouse', $cityPermissions);
        }

        // Query para retenciones (optimizado)
        $retentionQuery = DB::table('inventories as i')
            ->join('items as it', 'i.item_id', '=', 'it.item_id')
            ->leftJoin('locations as l', function($join) {
                $join->on('i.localizacion', '=', 'l.code')
                     ->on('i.warehouse', '=', 'l.warehouse')
                     ->on('i.customer', '=', 'l.customer');
            })
            ->whereIn('i.customer', $selectedCustomers)
            ->where('i.status', 'RETENCION')
            ->whereNotNull('it.item_id')
            ->whereNotNull('it.sku')
            ->whereNotNull('it.name')
            ->whereNotNull('it.ruta')
            ->select(
                'it.sku',
                'it.name as item_description',
                'i.warehouse',
                'i.customer',
                'l.code as location_code',
                'l.name as location_name',
                'l.location_id',
                DB::raw('SUM(i.quantity) as total_retention'),
                DB::raw('GREATEST(COALESCE(MAX(i.updated_at), "1970-01-01"), COALESCE((SELECT MAX(updated_at) FROM inventory_outputs WHERE inventory_id = i.id), "1970-01-01")) as last_modified_date')
            )
            ->groupBy('it.sku', 'it.name', 'i.warehouse', 'i.customer', 'l.code', 'l.name', 'l.location_id')
            ->havingRaw('SUM(i.quantity) > 0');

        if (!empty($cityPermissions)) {
            $retentionQuery->whereIn('i.warehouse', $cityPermissions);
        }

        // Query detallada (ya estaba optimizada)
        $detailedQuery = Inventory::with(['item'])
            ->whereIn('customer', $selectedCustomers);

        if (!empty($cityPermissions)) {
            $detailedQuery->whereIn('warehouse', $cityPermissions);
        }

        // OPTIMIZADO - Stock por ubicación (consulta directa con agregaciones)
        $stockPerLocationQuery = DB::table('inventories as i')
            ->join('items as it', 'i.item_id', '=', 'it.item_id')
            ->join('locations as l', function($join) {
                $join->on('i.localizacion', '=', 'l.code')
                     ->on('i.warehouse', '=', 'l.warehouse')
                     ->on('i.customer', '=', 'l.customer');
            })
            ->leftJoin(DB::raw('(SELECT inventory_id, 
                SUM(CASE WHEN status = "completado" THEN quantity ELSE 0 END) as total_salidas,
                SUM(CASE WHEN status = "devolucion" THEN ABS(quantity) ELSE 0 END) as total_devoluciones
                FROM inventory_outputs 
                GROUP BY inventory_id) as io'), 'i.id', '=', 'io.inventory_id')
            ->whereIn('i.customer', $selectedCustomers)
            ->where('i.status', '!=', 'RETENCION')
            ->select(
                'it.sku',
                'l.code as location_code',
                'l.name as location_name',
                'i.warehouse',
                DB::raw('GREATEST(0, 
                    SUM(i.quantity) - 
                    COALESCE(SUM(io.total_salidas), 0) + 
                    COALESCE(SUM(io.total_devoluciones), 0)
                ) as stock_per_loc')
            )
            ->groupBy('it.sku', 'l.code', 'l.name', 'i.warehouse')
            ->havingRaw('stock_per_loc > 0');

        if (!empty($cityPermissions)) {
            $stockPerLocationQuery->whereIn('i.warehouse', $cityPermissions);
        }

     
        if ($search) {
            $consolidatedQuery->where(function($query) use ($search) {
                $query->where('it.sku', 'like', "%{$search}%")
                      ->orWhere('it.name', 'like', "%{$search}%");
            });
            $retentionQuery->where(function($query) use ($search) {
                $query->where('it.sku', 'like', "%{$search}%")
                      ->orWhere('it.name', 'like', "%{$search}%");
            });
            $detailedQuery->where(function($query) use ($search) {
                $query->whereHas('item', function($q) use ($search) {
                    $q->where('sku', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%");
                });
            });
            $stockPerLocationQuery->where(function($q) use ($search) {
                $q->where('it.sku', 'like', "%{$search}%")
                  ->orWhere('it.name', 'like', "%{$search}%");
            });
        }

        if ($warehouse) {
            $consolidatedQuery->where('i.warehouse', $warehouse);
            $retentionQuery->where('i.warehouse', $warehouse);
            $detailedQuery->where('warehouse', $warehouse);
            $stockPerLocationQuery->where('i.warehouse', $warehouse);
        }

        if ($product) {
            $consolidatedQuery->where('it.name', $product);
            $retentionQuery->where('it.name', $product);
            $detailedQuery->whereHas('item', fn($q) => $q->where('name', $product));
            $stockPerLocationQuery->where('it.name', $product);
        }

        if ($startDate) {
            $consolidatedQuery->where('i.entry_date', '>=', $startDate);
            $retentionQuery->where('i.entry_date', '>=', $startDate);
            $detailedQuery->where('entry_date', '>=', $startDate);
        }

        if ($endDate) {
            $consolidatedQuery->where('i.entry_date', '<=', $endDate);
            $retentionQuery->where('i.entry_date', '<=', $endDate);
            $detailedQuery->where('entry_date', '<=', $endDate);
        }

  
        // OPTIMIZADO - Precargar todos los datos de una vez para evitar N+1 queries
        // Precargar stock por ubicación agrupado por SKU
        $allStockPerLocation = $stockPerLocationQuery->get()->groupBy('sku');
        
        // Precargar todas las fechas de vencimiento agrupadas por SKU, warehouse y location_code
        // IMPORTANTE: Usar la misma lógica de agrupación que en la consulta consolidada
        $expiryDatesQuery = DB::table('inventories as i')
            ->join('items as it', 'i.item_id', '=', 'it.item_id')
            ->leftJoin('locations as l', function($join) {
                $join->on('i.localizacion', '=', 'l.code')
                     ->on('i.warehouse', '=', 'l.warehouse')
                     ->on('i.customer', '=', 'l.customer');
            })
            ->leftJoin('inventory_outputs as io', function($join) {
                $join->on('io.inventory_id', '=', 'i.id')
                     ->orOn('io.inventory_id', '=', 'i.inventory_id');
            })
            ->whereIn('i.customer', $selectedCustomers)
            ->whereIn('i.status', ['INGRESO', 'SALIDA']) // Incluir ambos estados
            ->whereNotNull('i.expiry_date')
            ->where('i.expiry_date', '!=', '')
            ->where('i.expiry_date', '!=', '0000-00-00')
            ->where('i.expiry_date', '!=', '0000-00-00 00:00:00')
            ->select(
                'i.id',
                'it.sku',
                'i.warehouse',
                DB::raw('COALESCE(l.code, i.localizacion, "") as location_code'),
                'i.expiry_date',
                DB::raw('MAX(i.quantity) as quantity_original'),
                DB::raw('COALESCE(SUM(CASE WHEN io.status IN ("completado", "SALIDA") THEN io.quantity ELSE 0 END), 0) as total_salidas'),
                DB::raw('COALESCE(SUM(CASE WHEN io.status = "devolucion" THEN io.quantity ELSE 0 END), 0) as total_devoluciones')
            )
            ->groupBy('i.id', 'it.sku', 'i.warehouse', DB::raw('COALESCE(l.code, i.localizacion, "")'), 'i.expiry_date')
            ->havingRaw('GREATEST(0, COALESCE(MAX(i.quantity), 0) - COALESCE(SUM(CASE WHEN io.status IN ("completado", "SALIDA") THEN io.quantity ELSE 0 END), 0) + COALESCE(SUM(CASE WHEN io.status = "devolucion" THEN io.quantity ELSE 0 END), 0)) > 0');

        
        if (!empty($cityPermissions)) {
            $expiryDatesQuery->whereIn('i.warehouse', $cityPermissions);
        }
        
        // Aplicar los mismos filtros que la consulta consolidada
        if ($search) {
            $expiryDatesQuery->where(function($query) use ($search) {
                $query->where('it.sku', 'like', "%{$search}%")
                      ->orWhere('it.name', 'like', "%{$search}%");
            });
        }
        
        if ($warehouse) {
            $expiryDatesQuery->where('i.warehouse', $warehouse);
        }
        
        if ($product) {
            $expiryDatesQuery->where('it.name', $product);
        }
        
        if ($startDate) {
            $expiryDatesQuery->where('i.entry_date', '>=', $startDate);
        }
        
        if ($endDate) {
            $expiryDatesQuery->where('i.entry_date', '<=', $endDate);
        }
        
        $allExpiryDates = $expiryDatesQuery->get()
            ->groupBy(function($item) {
                // Usar la misma clave que en el mapeo de inventory_unified
                $locationCode = $item->location_code ?? '';
                return $item->sku . '|' . $item->warehouse . '|' . $locationCode;
            })
            ->map(function($group) {
                return $group->pluck('expiry_date')
                    ->unique()
                    ->filter(function($date) {
                        return !empty($date) && $date !== '0000-00-00';
                    })
                    ->sort()
                    ->values()
                    ->toArray();
            });

        // Obtener datos consolidados
        $consolidatedData = $consolidatedQuery->get();
        
        $inventory_unified = $consolidatedData->map(function ($item) use ($allStockPerLocation, $allExpiryDates, $getStockStatus) {
            $locationDisplay = $item->location_code ?? 'N/A';
            
            // Obtener stock por ubicación desde datos precargados
            $perLocStocks = [];
            if (isset($allStockPerLocation[$item->sku])) {
                foreach ($allStockPerLocation[$item->sku] as $locStock) {
                    $perLocStocks[] = [
                        'location_code' => $locStock->location_code,
                        'location_name' => $locStock->location_name,
                        'warehouse' => $locStock->warehouse,
                        'stock' => (int) $locStock->stock_per_loc
                    ];
                }
            }

            $stockAvailable = (int) ($item->stock_available ?? 0);
            
            // Obtener fechas de vencimiento desde datos precargados
            // IMPORTANTE: Usar la misma lógica de clave que en la agrupación
            $locationCode = $item->location_code ?? '';
            $expiryKey = $item->sku . '|' . $item->warehouse . '|' . $locationCode;
            
            // Si no encuentra con location_code, intentar buscar por SKU y warehouse solamente
            $expiryDates = $allExpiryDates[$expiryKey] ?? [];
            
            // Si no hay fechas con location específica, buscar todas las fechas del SKU en esa bodega
            if (empty($expiryDates)) {
                $skuWarehouseKey = $item->sku . '|' . $item->warehouse . '|';
                foreach ($allExpiryDates as $key => $dates) {
                    if (strpos($key, $item->sku . '|' . $item->warehouse . '|') === 0) {
                        $expiryDates = array_merge($expiryDates, $dates);
                    }
                }
                // Eliminar duplicados y ordenar
                $expiryDates = array_values(array_unique($expiryDates));
                sort($expiryDates);
            }

            return [
                'sku' => $item->sku,
                'item_description' => $item->item_description,
                'warehouse' => $item->warehouse,
                'customer' => $item->customer,
                'location' => $locationDisplay,
                'original_entries' => (int) ($item->original_entries ?? 0),
                'total_returns' => (int) ($item->total_returns ?? 0),
                'total_retention_quantity' => (int) ($item->total_retention ?? 0),
                'total_outputs' => (int) ($item->total_outputs ?? 0),
                'stock_available' => $stockAvailable,
                'stock_status' => $getStockStatus($stockAvailable),
                'last_modified_date' => $item->last_modified_date,
                'stocks_per_location' => $perLocStocks,
                'expiry_dates' => $expiryDates
            ];
        });
        // OPTIMIZADO - Retenciones usando datos precargados
        $retentionData = $retentionQuery->get();
        $retentionItems = $retentionData->map(function ($item) use ($allStockPerLocation, $getStockStatus) {
            $locationDisplay = $item->location_code ?? 'N/A';

            // Obtener stock por ubicación desde datos precargados
            $perLocStocks = [];
            if (isset($allStockPerLocation[$item->sku])) {
                foreach ($allStockPerLocation[$item->sku] as $locStock) {
                    $perLocStocks[] = [
                        'location_code' => $locStock->location_code,
                        'location_name' => $locStock->location_name,
                        'warehouse' => $locStock->warehouse,
                        'stock' => (int) $locStock->stock_per_loc
                    ];
                }
            }

            $retentionQuantity = (int) ($item->total_retention ?? 0);

            return [
                'inventory_id' => null,
                'sku' => $item->sku,
                'item_description' => $item->item_description,
                'warehouse' => $item->warehouse,
                'customer' => $item->customer,
                'location' => $locationDisplay,
                'total_retention_quantity' => $retentionQuantity,
                'retention_reason' => 'En retención',
                'stock_status' => $getStockStatus($retentionQuantity),
                'last_modified_date' => $item->last_modified_date,
                'stocks_per_location' => $perLocStocks
            ];
        });

        $detailedInventories = $detailedQuery->get();

        // OPTIMIZADO - Productos únicos (consulta directa)
        $uniqueProductsQuery = DB::table('inventories as i')
            ->join('items as it', 'i.item_id', '=', 'it.item_id')
            ->whereIn('i.customer', $selectedCustomers)
            ->whereNotNull('it.item_id')
            ->whereNotNull('it.sku')
            ->whereNotNull('it.name')
            ->whereNotNull('it.ruta');
        
        if (!empty($cityPermissions)) {
            $uniqueProductsQuery->whereIn('i.warehouse', $cityPermissions);
        }
        
        $uniqueProducts = $uniqueProductsQuery->distinct()->pluck('it.name as item_description');

        // OPTIMIZADO - Bodegas únicas (consulta directa)
        $uniqueWarehousesQuery = DB::table('inventories as i')
            ->join('items as it', 'i.item_id', '=', 'it.item_id')
            ->whereIn('i.customer', $selectedCustomers)
            ->whereNotNull('it.item_id')
            ->whereNotNull('it.sku')
            ->whereNotNull('it.name')
            ->whereNotNull('it.ruta');
        
        if (!empty($cityPermissions)) {
            $uniqueWarehousesQuery->whereIn('i.warehouse', $cityPermissions);
        }
        
        $uniqueWarehouses = $uniqueWarehousesQuery->distinct()->pluck('i.warehouse');

        $uniqueLocations = $detailedInventories
            ->pluck('localizacion')
            ->unique()
            ->values();
    }

    return view('inventories.index', compact(
        'inventory_unified',
        'detailedInventories',
        'retentionItems',
        'cityPermissions',
        'customerPermissions',
        'uniqueProducts',
        'uniqueWarehouses',
        'uniqueLocations',
        'search',
        'warehouse',
        'product',
        'location',
        'startDate',
        'endDate',
        'customers',
        'request'
    ));
}


    public function store(Request $request)
    {
        Log::info('=== INICIO STORE INVENTARIO (REFACTORED) ===');
        try {
            $validatedData = $request->validate([
                'sku' => 'required|string|max:255',
                'status' => 'required|in:INGRESO,DEVOLUCION,RETENCION',
                'batch' => 'required|string|max:255',
                'expiry_date' => 'required|date',
                'item_condition' => 'required|in:bueno,malo',
                'entry_date' => 'required|date',
                'warehouse' => 'required|string|max:255',
                'commerce' => 'required|string|max:255',
                'item_description' => 'required|string|max:500',
                'quantity' => 'required|integer|min:1',
                'value' => 'required|numeric|min:0',
                'type' => 'required|string|max:255',
                'localizacion' => 'nullable|string|max:255',
                'location_id' => 'nullable|integer',
                'observations' => 'nullable|string|max:1000',
                'retention_substatus' => 'nullable|in:AVERIAS,REZAGOS',
                'devolution_substatus' => 'nullable|in:AVERIAS',
            ]);

            $selectedCustomers = session('selected_customers', []);
            if (empty($selectedCustomers)) {
                return redirect()->back()->withErrors(['general' => 'Debe seleccionar al menos un cliente'])->withInput();
            }
            $customer = $selectedCustomers[0];

            // 1. Resolve Item
            $item = Item::where('sku', $validatedData['sku'])->first();
            if (!$item) {
                return redirect()->back()->withErrors(['sku' => 'SKU no encontrado en el catálogo'])->withInput();
            }
            $validatedData['item_id'] = $item->item_id;
            $validatedData['customer'] = $customer;

            // 2. Handle Logic based on Status
            if ($validatedData['status'] === 'DEVOLUCION') {
                // For devolutions, we use cancelExit if it's not and 'averia'
                if ($request->has('devolution_substatus') && $validatedData['devolution_substatus'] === 'AVERIAS') {
                    $validatedData['status'] = 'RETENCION';
                    $validatedData['retention_substatus'] = 'AVERIAS';
                } else {
                    // Logic to find the output to reverse
                    $output = InventoryOutput::where('sku', $validatedData['sku'])
                        ->where('customer', $customer)
                        ->where('warehouse', $validatedData['warehouse'])
                        ->where('status', 'completado')
                        ->latest()
                        ->first();

                    if (!$output || $output->quantity < $validatedData['quantity']) {
                        return redirect()->back()->withErrors(['general' => 'No se encontró una salida válida para realizar la devolución'])->withInput();
                    }

                    $this->inventoryService->cancelExit($output->id, $validatedData['quantity']);
                    return redirect()->route('inventories.index')->with('success', "Devolución procesada correctamente.");
                }
            }

            // 3. Handle Retention specific validations
            if ($validatedData['status'] === 'RETENCION') {
                $available = $this->stockService->getAvailable($item->item_id, $validatedData['localizacion'], $validatedData['warehouse'], $customer);
                if ($available < $validatedData['quantity']) {
                    return redirect()->back()->withErrors(['quantity' => "Stock insuficiente para retener. Disponible: {$available}"])->withInput();
                }
            }

            // 4. Resolve Inventory ID (Persistent per product/bodega/client)
            $validatedData['inventory_id'] = Inventory::where([
                'warehouse' => $validatedData['warehouse'],
                'sku' => $validatedData['sku'],
                'customer' => $customer
            ])->value('inventory_id') ?? 'INV-' . strtoupper(substr($customer, 0, 3)) . '-' . time();

            // 5. Execute via Service
            $this->inventoryService->registerEntry($validatedData);

            return redirect()->route('inventories.index')->with('success', 'Registro procesado exitosamente.');

        } catch (\Exception $e) {
            Log::error('Error en InventoryController@store: ' . $e->getMessage());
            return redirect()->back()->withErrors(['general' => 'Error: ' . $e->getMessage()])->withInput();
        }
    }
public function getInventoryByLocation(Request $request)
{
    try {
        $locationCode = $request->input('location_code');
        $warehouse = $request->input('warehouse');
        $selectedCustomers = session('selected_customers', []);
        $customer = !empty($selectedCustomers) ? $selectedCustomers[0] : session('selected_customer');

        if (!$locationCode || !$warehouse || !$customer) {
            return response()->json(['success' => false, 'message' => 'Faltan parámetros requeridos'], 400);
        }

        // Find the location
        $location = Location::where('code', $locationCode)->where('warehouse', $warehouse)->first();
        if (!$location) {
            return response()->json(['success' => false, 'message' => 'Ubicación no encontrada'], 404);
        }

        // Get an example inventory record for the basic item data (SKU, description, etc.)
        // In this system, one location usually holds one SKU.
        $inventory = Inventory::where('localizacion', $locationCode)
            ->where('warehouse', $warehouse)
            ->where('customer', $customer)
            ->where('status', 'INGRESO')
            ->first();

        if (!$inventory) {
            return response()->json(['success' => false, 'message' => 'No hay inventario activo en esta ubicación'], 404);
        }

        $physical = $this->stockService->getPhysical($inventory->item_id, $locationCode, $warehouse, $customer);
        $reserved = $this->stockService->getReserved($inventory->item_id, $locationCode, $warehouse, $customer);
        $available = $this->stockService->getAvailable($inventory->item_id, $locationCode, $warehouse, $customer);

        if ($physical <= 0) {
            return response()->json(['success' => false, 'message' => 'Sin stock físico en esta ubicación'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'sku' => $inventory->sku,
                'item_description' => $inventory->item_description,
                'item_id' => $inventory->item_id,
                'location_code' => $locationCode,
                'location_name' => $location->name ?? $locationCode,
                'location_id' => $location->location_id,
                'batch' => $inventory->batch,
                'expiry_date' => $inventory->expiry_date,
                'item_condition' => $inventory->item_condition,
                'entry_date' => $inventory->entry_date,
                'value' => $inventory->value,
                'type' => $inventory->type,
                'commerce' => $inventory->commerce,
                'current_stock' => $physical,
                'reserved_stock' => $reserved,
                'available_stock' => $available
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('Error en getInventoryByLocation: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}
public function searchItems(Request $request)
{
    $query = $request->get('query');
    if (!$query || strlen($query) < 2) {
        return response()->json([]);
    }

    try {
        $items = DB::table('items')
            ->where('name', 'like', "%{$query}%")
            ->select('item_id', 'name', 'sku')
            ->limit(10)
            ->get();

        return response()->json($items);
    } catch (\Exception $e) {
        Log::error('Error en searchItems: ' . $e->getMessage());
        return response()->json([]);
    }
}


    public function getLocationsByItem(Request $request, $itemId)
    {
        $selectedCustomers = session('selected_customers', []);
        $customer = !empty($selectedCustomers) ? $selectedCustomers[0] : session('selected_customer');
        if (!$customer) {
            return response()->json(['error' => 'Cliente no seleccionado'], 400);
        }

        // Find locations where this item is assigned and has physical stock
        $itemLocations = ItemLocation::with('location')
            ->where('item_id', $itemId)
            ->where('current_quantity', '>', 0)
            ->get();

        $result = $itemLocations->map(function($il) use ($customer) {
            // We use StockService to double check and provide accurate value
            $physical = $this->stockService->getPhysical($il->item_id, $il->location->code, $il->location->warehouse, $customer);
            
            if ($physical <= 0) return null;

            return [
                'location_id' => $il->location_id,
                'location_code' => $il->location->code,
                'location_name' => $il->location->name ?? $il->location->code,
                'warehouse' => $il->location->warehouse,
                'current_quantity' => $physical
            ];
        })->filter()->values();

        return response()->json($result);
    }


    public function selectCustomer(Request $request)
    {
        $request->validate([
            'customers' => 'required|array|min:1',
            'customers.*' => 'exists:customers,name'
        ]);

        session([
            'selected_customers' => array_values($request->customers),
            'selected_customer' => $request->customers[0],
        ]);
        return redirect()->route('inventories.index')->with('success', 'Clientes seleccionados correctamente.');
    }

    public function exitCustomer(Request $request)
    {
        $request->session()->forget(['selected_customers', 'selected_customer']);
        return response()->json(['success' => true]);
    }

    public function create()
    {
        $uniqueItemDescriptions = Inventory::distinct()->pluck('item_description');
        $uniqueWarehouses = Inventory::distinct()->pluck('warehouse');
        return view('inventories.create', compact('uniqueItemDescriptions', 'uniqueWarehouses'));
    }

    public function searchProducts(Request $request)
    {
        $query = $request->get('query');

        $products = Item::where('name', 'like', "%{$query}%")
            ->select('name', 'sku')
            ->limit(10)
            ->get();

        return response()->json($products);
    }

    public function searchWarehouses(Request $request)
{
    $query = $request->get('query', '');
    $user = Auth::user();

    try {
        $warehousesQuery = City::query();
        if (!$user->can('password.create')) {
            $userCities = $user->getAllPermissions()->pluck('name')
                ->filter(function ($permission) {
                    return !in_array($permission, ['password.create', 'user.management']);
                })
                ->toArray();

            if (!empty($userCities)) {
                $warehousesQuery->whereIn('city_store', $userCities);
            }
        }
        $warehouses = $warehousesQuery
            ->where('city_store', 'like', "%{$query}%")
            ->pluck('city_store')
            ->unique()
            ->values()
            ->take(10);

        return response()->json($warehouses);

    } catch (\Exception $e) {
        Log::error('Error en searchWarehouses: ' . $e->getMessage());
        return response()->json([]);
    }
}


    public function getProductImage($productName)
    {
        try {
            $item = Item::where('name', $productName)
                ->orWhere('description', 'like', "%{$productName}%")
                ->first();

            if ($item) {
                return response()->json([
                    'image_url' => $item->image_url
                ]);
            }

            return response()->json(['image_url' => null]);

        } catch (\Exception $e) {
            return response()->json(['image_url' => null]);
        }
    }

   
    public function sendToRetention($id)
    {
        try {
            $inventory = Inventory::findOrFail($id);
            
            $this->inventoryService->registerEntry([
                'item_id' => $inventory->item_id,
                'item_description' => $inventory->item_description,
                'batch' => $inventory->batch,
                'expiry_date' => $inventory->expiry_date,
                'warehouse' => $inventory->warehouse,
                'localizacion' => $inventory->localizacion,
                'location_id' => $inventory->location_id,
                'customer' => $inventory->customer,
                'quantity' => $inventory->quantity, // Retain the whole record amount? Or ask quantity? Original code took whole.
                'status' => 'RETENCION',
                'observations' => 'Retención manual: ' . ($inventory->observations ?? ''),
                'user_id' => auth()->id()
            ]);

            return redirect()->route('inventories.index')->with('success', 'Producto enviado a retención correctamente.');
        } catch (\Exception $e) {
            Log::error('Error en sendToRetention: ' . $e->getMessage());
            return redirect()->route('inventories.index')->with('error', 'Error al procesar la retención: ' . $e->getMessage());
        }
    }


  


    public function edit($id)
    {
        $inventory = Inventory::findOrFail($id);
        $inventory->expiry_date = $inventory->expiry_date ? \Carbon\Carbon::parse($inventory->expiry_date) : null;
        $inventory->entry_date = $inventory->entry_date ? \Carbon\Carbon::parse($inventory->entry_date) : null;
        return view('inventories.edit', compact('inventory'));
    }

    public function update(Request $request, $inventory_id)
    {
        $inventory = Inventory::findOrFail($inventory_id);
        $validatedData = $request->validate([
            'sku' => 'required',
            'status' => 'required',
            'batch' => 'required',
            'expiry_date' => 'required|date',
            'item_condition' => 'required',
            'entry_date' => 'required|date',
            'warehouse' => 'required',
            'commerce' => 'required',
            'item_description' => 'required',
            'quantity' => 'required|integer',
            'value' => 'required|numeric',
            'type' => 'required',
            'observations' => 'nullable',
        ]);

        if ($inventory->item_description !== $validatedData['item_description'] ||
            $inventory->sku !== $validatedData['sku']) {

            $item = Item::where('name', $validatedData['item_description'])
                ->orWhere('sku', $validatedData['sku'])
                ->first();
            if ($item) {
                $validatedData['item_id'] = $item->id;
            }
        }

        $selectedCustomers = session('selected_customers', []);
        if (!empty($selectedCustomers)) {
            $validatedData['customer'] = $selectedCustomers[0];
        } elseif (session('selected_customer')) {
            $validatedData['customer'] = session('selected_customer');
        }

        $inventory->update($validatedData);
        return redirect()->route('inventories.index')->with('success', 'Inventario actualizado correctamente.');
    }

    public function destroy($id)
    {
        $inventory = Inventory::findOrFail($id);
        $inventory->delete();
        return redirect()->route('inventories.index')->with('success', 'Registro Eliminado Correctamente.');
    }

    public function storeInventory(Request $request)
    {
        return $this->store($request);
    }

    public function updateInventory(Request $request, Inventory $inventory)
    {
        return $this->update($request, $inventory->id);
    }

    public function storeCustomer(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:customers',
            'phone' => 'required',
            'address' => 'required',
        ]);
        Customer::create($validatedData);
        return redirect()->route('inventories.index')->with('success', 'Cliente creado correctamente.');
    }

    public function storeItem(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required',
            'description' => 'required',
            'sku' => 'required|unique:items',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images'), $imageName);
            $validatedData['ruta'] = $imageName;
        }
        Item::create($validatedData);
        return redirect()->route('items.index')->with('success', 'Artículo creado correctamente.');
    }

  public function import(Request $request)
{
    $request->validate([
        'file' => 'required|mimes:xlsx,xls,csv',
    ]);
    
    $file = $request->file('file');
    
    try {
        $import = new InventoryImport();
        Excel::import($import, $file);
        
        $rowsProcessed = $import->getRowCount();
        $errors = $import->getErrors();
        $warnings = $import->getWarnings();
        
        Log::info('Importación completada exitosamente', [
            'rows_processed' => $rowsProcessed,
            'errors_count' => count($errors),
            'warnings_count' => count($warnings),
        ]);
        
        $successMessage = "Importación completada exitosamente. Filas procesadas: {$rowsProcessed}";
        
        if (!empty($warnings)) {
            $successMessage .= " (con " . count($warnings) . " advertencias)";
        }
        
        return back()->with('success', $successMessage);
        
    } catch (\Exception $e) {
        Log::error('Error durante la importación', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
        $errorMessage = $e->getMessage();
    
        if (strlen($errorMessage) > 500) {
            session()->flash('detailed_errors', $errorMessage);
            return back()->with('error', 'Se encontraron múltiples errores en el archivo. Ver detalles.');
        }
        
        return back()->with('error', $errorMessage);
    }
}

    public function storeCity(Request $request)
{
    $validatedData = $request->validate([
        'city_name' => 'required',
        'city_store' => 'required|unique:cities,city_store',
    ]);

    City::create([
        'city_name' => $request->city_name,
        'city_store' => $request->city_store,
    ]);

    return redirect()->route('inventories.index')->with('success', 'Ciudad creada correctamente.');
}


    public function getProducts()
    {
        $products = Item::all();
        return response()->json($products);
    }

    public function show($id)
    {
        $inventory = Inventory::findOrFail($id);
        return view('inventories.show', compact('inventory'));
    }

    private function getUserCities()
{
    $user = Auth::user();
    $userCities = [];

    if ($user->can('password.create')) {
        $userCities = City::pluck('city_store')->toArray();
    } else {
        foreach (City::pluck('city_store') as $cityStore) {
            if ($user->can(strtoupper($cityStore))) {
                $userCities[] = $cityStore;
            }
        }
    }

    return $userCities;
}


public function export(Request $request)
{
    return Excel::download(
        new InventoryExport(
            $request->input('start_date'),
            $request->input('end_date'),
            $request->input('product'),
            $request->input('warehouse'),
            $request->input('location'),
            !empty(session('selected_customers', [])) ? session('selected_customers')[0] : session('selected_customer')
        ), 
        'inventario_e-commerce_' . now()->format('Y_m_d_His') . '.xlsx'
    );
}

    private function getAllowedWarehouses($user)
    {
        if ($user->can('password.create')) {
            $selectedCustomers = session('selected_customers', []);
            return Inventory::whereIn('customer', $selectedCustomers)->pluck('warehouse')->unique();
        }
        $selectedCustomers = session('selected_customers', []);
        return Inventory::whereIn('customer', $selectedCustomers)
            ->pluck('warehouse')
            ->unique()
            ->filter(function ($warehouse) use ($user) {
                return $user->can(strtoupper(StringHelper::normalizeWarehouseName($warehouse)));
            })
            ->values();
    }

    public function stock()
    {
        $isAdmin = auth()->user()->can('password.create');
        $userCities = auth()->user()->cities ?? [];
        $inventories = Inventory::with(['outputs'])
            ->where('customer', session('selected_customer'))
            ->where('status', '!=', 'RETENCION')
            ->when(!$isAdmin, function ($query) use ($userCities) {
                return $query->whereIn('warehouse', $userCities);
            })
            ->get()
            ->map(function ($inventory) {
                $inventory->total_quantity = $inventory->quantity;
                $inventory->total_outputs = $inventory->outputs->sum('quantity');
                $inventory->normalized_warehouse = \App\Helpers\StringHelper::normalizeWarehouseName($inventory->warehouse);
                return $inventory;
            });

        $retentionStock = Inventory::where('customer', session('selected_customer'))
            ->where('status', 'RETENCION')
            ->when(!$isAdmin, function ($query) use ($userCities) {
                return $query->whereIn('warehouse', $userCities);
            })
            ->get()
            ->map(function ($inventory) {
                $inventory->normalized_warehouse = \App\Helpers\StringHelper::normalizeWarehouseName($inventory->warehouse);
                return $inventory;
            });

        $cities = $isAdmin ? City::pluck('name') : $userCities;
        return view('inventories.stock', compact('inventories', 'retentionStock', 'cities', 'isAdmin', 'userCities'));
    }

    public function retentionReport()
{
    $isAdmin = auth()->user()->can('password.create');
    $userCities = auth()->user()->cities ?? [];
    $selectedCustomer = session('selected_customer');
    
    if (!$selectedCustomer) {
        return redirect()->route('inventories.index')
            ->with('error', 'Debe seleccionar un cliente primero.');
    }
    
    $retentionItems = DB::table('vw_inventory_unified')
        ->where('customer', $selectedCustomer)
        ->where('total_retention', '>', 0) 
        ->when(!$isAdmin, function ($query) use ($userCities) {
            return $query->whereIn('warehouse', $userCities);
        })
        ->select([
            'item_id',
            'sku',
            'item_description',
            'warehouse',
            'customer',
            'location_code',
            'location_name',
            'total_retention',
            'current_stock',
            'last_modified_date'
        ])
        ->orderBy('warehouse')
        ->orderBy('item_description')
        ->get()
        ->map(function ($item) {
            $retentionDetails = DB::table('inventories')
                ->where('item_id', $item->item_id)
                ->where('warehouse', $item->warehouse)
                ->where('customer', $item->customer)
                ->where('status', 'RETENCION')
                ->select([
                    'retention_substatus',
                    'observations',
                    'quantity',
                    'entry_date',
                    'batch'
                ])
                ->get();
            
            $item->retention_details = $retentionDetails;
            $item->retention_reasons = $retentionDetails->pluck('observations')->unique()->implode('; ');
            $item->retention_substatuses = $retentionDetails->pluck('retention_substatus')->unique()->implode(', ');
            
            return $item;
        });
    
    return view('inventories.retention_report', compact('retentionItems'));
}





public function releaseFromRetention(Request $request)
{
    $validatedData = $request->validate([
        'item_id' => 'required|integer',
        'warehouse' => 'required|string',
        'customer' => 'required|string',
        'location_code' => 'required|string',
        'retention_substatus' => 'required|string',
        'quantity_to_release' => 'required|integer|min:1',
        'release_reason' => 'nullable|string|max:500'
    ]);

    $customer = session('selected_customer');
    if (!$customer || $customer !== $validatedData['customer']) {
        return response()->json([
            'success' => false,
            'message' => 'Cliente no válido o sesión expirada.'
        ], 403);
    }

    DB::beginTransaction();

    try {
        $retentionRecord = DB::table('inventories')
            ->where('item_id', $validatedData['item_id'])
            ->where('warehouse', $validatedData['warehouse'])
            ->where('customer', $validatedData['customer'])
            ->where('localizacion', $validatedData['location_code'])
            ->where('status', 'RETENCION')
            ->where('retention_substatus', $validatedData['retention_substatus'])
            ->first();

        if (!$retentionRecord) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró un registro de retención para los datos proporcionados.'
            ], 404);
        }

        if ($retentionRecord->quantity < $validatedData['quantity_to_release']) {
            return response()->json([
                'success' => false,
                'message' => "No hay suficientes productos en retención. Disponibles: {$retentionRecord->quantity}, solicitados: {$validatedData['quantity_to_release']}"
            ], 400);
        }
        $newQuantity = $retentionRecord->quantity - $validatedData['quantity_to_release'];

        if ($newQuantity <= 0) {
            DB::table('inventories')
                ->where('id', $retentionRecord->id)
                ->delete();
        } else {
            DB::table('inventories')
                ->where('id', $retentionRecord->id)
                ->update(['quantity' => $newQuantity]);
        }
        DB::table('inventories')
            ->where('item_id', $validatedData['item_id'])
            ->where('warehouse', $validatedData['warehouse'])
            ->where('customer', $validatedData['customer'])
            ->where('status', 'DISPONIBLE')
            ->increment('quantity', $validatedData['quantity_to_release']);

        DB::commit();

        Log::info("Liberación de retención exitosa:", [
            'item_id' => $validatedData['item_id'],
            'quantity_released' => $validatedData['quantity_to_release'],
            'remaining_quantity' => $newQuantity
        ]);

        return response()->json([
            'success' => true,
            'message' => "Se liberaron {$validatedData['quantity_to_release']} unidades del producto correctamente."
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Error de validación: ' . $e->getMessage(),
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error liberando retención:', [
            'error' => $e->getMessage(),
            'data' => $request->all()
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Error al liberar productos: ' . $e->getMessage()
        ], 500);
    }
}
public function getSkuFromInventory(Request $request)
{
    $request->validate([
        'item_id' => 'required|integer',
        'customer' => 'required|string'
    ]);

    try {
        $sku = DB::table('vw_inventory_unified')
            ->where('item_id', $request->item_id)
            ->where('customer', $request->customer)
            ->value('sku');

        return response()->json([
            'sku' => $sku
        ]);
    } catch (\Exception $e) {
        Log::error('Error al obtener SKU del inventario: ' . $e->getMessage());
        return response()->json([
            'sku' => null,
            'error' => 'Error al consultar el inventario'
        ], 500);
    }
}
    public function validateSku(Request $request)
{
    $request->validate([
        'sku' => 'required|string',
        'customer' => 'required|string',
        'warehouse' => 'required|string'
    ]);

    $exists = DB::table('vw_inventory_unified')
        ->where('sku', $request->sku)
        ->where('customer', $request->customer)
        ->where('warehouse', $request->warehouse)
        ->exists();

    return response()->json([
        'valid' => $exists
    ]);
}


public function getExpiryDatesFromOutputs(Request $request)
{
    $validated = $request->validate([
        'item_id'   => 'required|integer',
        'warehouse' => 'required|string',
        'customer'  => 'required|string'
    ]);

    $results = DB::table('vw_salidas_pendientes_devolucion')
        ->where('item_id', $validated['item_id'])
        ->where('warehouse', $validated['warehouse'])
        ->where('customer', $validated['customer'])
        ->select([
            'expiry_date',
            'disponible_para_devolver as quantity',
            'sample_inventory_id as inventory_id'
        ])
        ->orderBy('expiry_date')
        ->get();

    if ($results->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'No hay salidas pendientes de devolución',
            'expiry_dates' => []
        ]);
    }

    return response()->json([
        'success' => true,
        'expiry_dates' => $results
    ]);
}

public function getExpiryDatesFromInventories(Request $request)
{
    try {
        $validated = $request->validate([
            'item_id' => 'required|integer',
            'warehouse' => 'required|string',
            'customer' => 'required|string'
        ]);

        $expiryDates = DB::table('inventories as inv')
            ->leftJoin('inventory_outputs as io', 'inv.id', '=', 'io.inventory_id')
            ->where('inv.item_id', $validated['item_id'])
            ->where('inv.warehouse', $validated['warehouse'])
            ->where('inv.customer', $validated['customer'])
            ->where('inv.status', 'INGRESO')
            ->whereNotNull('inv.expiry_date')
            ->select([
                'inv.expiry_date',
                'inv.id as inventory_id',
                'inv.quantity',
                'inv.localizacion',
                DB::raw('COALESCE(SUM(io.quantity), 0) as total_outputs')
            ])
            ->groupBy('inv.id', 'inv.expiry_date', 'inv.quantity', 'inv.localizacion')
            ->havingRaw('inv.quantity - COALESCE(SUM(io.quantity), 0) > 0')
            ->orderBy('inv.expiry_date', 'asc')
            ->get();

        if ($expiryDates->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró stock disponible para este producto en esta bodega',
                'expiry_dates' => []
            ]);
        }

        return response()->json([
            'success' => true,
            'expiry_dates' => $expiryDates->map(function($item) {
                return [
                    'expiry_date' => $item->expiry_date,
                    'quantity' => (int) ($item->quantity - $item->total_outputs),
                    'inventory_id' => $item->inventory_id,
                    'location_code' => $item->localizacion
                ];
            })
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error de validación',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        Log::error('Error obteniendo fechas de vencimiento (inventories):', [
            'error' => $e->getMessage(),
            'data' => $request->all()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Error al cargar fechas de vencimiento: ' . $e->getMessage()
        ], 500);
    }
}

public function getOutputRecordData(Request $request)
{
    $validated = $request->validate([
        'item_id' => 'required|integer',
        'warehouse' => 'required|string',
        'expiry_date' => 'required|date',
        'customer' => 'required|string'
    ]);

    try {
        $record = DB::table('inventories as inv')
            ->where('inv.item_id', $validated['item_id'])
            ->where('inv.warehouse', $validated['warehouse'])
            ->where('inv.expiry_date', $validated['expiry_date'])
            ->where('inv.customer', $validated['customer'])
            ->select([
                'inv.id as inventory_id',
                'inv.sku',
                'inv.batch',
                'inv.item_condition',  // Mantener el valor original
                'inv.entry_date',
                'inv.type',
                'inv.commerce',
                'inv.value',
                'inv.item_id',
                'inv.location_id',
                'inv.localizacion as location_code'
            ])
            ->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontraron datos para esta selección'
            ], 404);
        }

        $salidasCompletadas = DB::table('inventory_outputs')
            ->where('inventory_id', $record->inventory_id)
            ->whereIn('status', ['completado', 'COMPLETADO'])
            ->sum('quantity');
        
        $devolucionesRealizadas = DB::table('inventory_outputs')
            ->where('inventory_id', $record->inventory_id)
            ->where('status', 'devolucion')
            ->sum('quantity');
        
        $stockDisponible = $salidasCompletadas - $devolucionesRealizadas;

        return response()->json([
            'success' => true,
            'data' => [
                'inventory_id' => $record->inventory_id,
                'sku' => $record->sku,
                'batch' => $record->batch,
                'item_condition' => $record->item_condition,  // FIX: Devolver el valor original sin conversión
                'entry_date' => $record->entry_date,
                'type' => $record->type,
                'commerce' => $record->commerce,
                'value' => $record->value,
                'item_id' => $record->item_id,
                'location_id' => $record->location_id,
                'location_code' => $record->location_code,
                'available_quantity' => (int) $stockDisponible
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('Error obteniendo datos del registro (outputs):', [
            'error' => $e->getMessage(),
            'data' => $validated
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Error al cargar datos: ' . $e->getMessage()
        ], 500);
    }
}

public function getInventoryRecordData(Request $request)
{
    $validated = $request->validate([
        'item_id' => 'required|integer',
        'warehouse' => 'required|string',
        'expiry_date' => 'required|date',
        'customer' => 'required|string'
    ]);

    try {
        $record = DB::table('inventories as inv')
            ->leftJoin('inventory_outputs as io', 'inv.id', '=', 'io.inventory_id')
            ->where('inv.item_id', $validated['item_id'])
            ->where('inv.warehouse', $validated['warehouse'])
            ->where('inv.expiry_date', $validated['expiry_date'])
            ->where('inv.customer', $validated['customer'])
            ->where('inv.status', 'INGRESO')
            ->select([
                'inv.id as inventory_id',
                'inv.sku',
                'inv.batch',
                'inv.item_condition',
                'inv.entry_date',
                'inv.type',
                'inv.commerce',
                'inv.value',
                'inv.quantity',
                'inv.item_id',
                'inv.location_id',
                'inv.localizacion as location_code',
                DB::raw('COALESCE(SUM(io.quantity), 0) as total_outputs')
            ])
            ->groupBy('inv.id', 'inv.sku', 'inv.batch', 'inv.item_condition', 
                     'inv.entry_date', 'inv.type', 'inv.commerce', 'inv.value', 
                     'inv.quantity', 'inv.item_id', 'inv.location_id', 'inv.localizacion')
            ->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontraron datos para esta selección'
            ], 404);
        }

        $availableQuantity = $record->quantity - $record->total_outputs;

        return response()->json([
            'success' => true,
            'data' => [
                'inventory_id' => $record->inventory_id,
                'sku' => $record->sku,
                'batch' => $record->batch,
                'item_condition' => $record->item_condition === 'bueno' ? 'BUEN ESTADO' : 'MAL ESTADO',
                'entry_date' => $record->entry_date,
                'type' => $record->type,
                'commerce' => $record->commerce,
                'value' => $record->value,
                'item_id' => $record->item_id,
                'location_id' => $record->location_id,
                'location_code' => $record->location_code,
                'available_quantity' => (int) $availableQuantity
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('Error obteniendo datos del registro (inventories):', [
            'error' => $e->getMessage(),
            'data' => $validated
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Error al cargar datos: ' . $e->getMessage()
        ], 500);
    }
}

    public function processDevolution(Request $request)
    {
        Log::info('=== INICIO PROCESO DE DEVOLUCIÓN (REFACTORED) ===');
        try {
            $validated = $request->validate([
                'inventory_id' => 'required|integer',
                'return_quantity' => 'required|integer|min:1',
            ]);

            $output = InventoryOutput::where('inventory_id', $validated['inventory_id'])
                ->where('status', 'completado')
                ->latest()
                ->first();

            if (!$output || $output->quantity < $validated['return_quantity']) {
                return redirect()->back()->withErrors(['return_quantity' => "No se encontró una salida válida para realizar la devolución."]);
            }

            $this->inventoryService->cancelExit($output->id, $validated['return_quantity']);

            return redirect()->route('inventories.index')->with('success', "Devolución registrada correctamente.");

        } catch (\Exception $e) {
            Log::error('Error en processDevolution: ' . $e->getMessage());
            return redirect()->back()->withErrors(['general' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function processRetention(Request $request)
    {
        Log::info('=== INICIO PROCESO DE RETENCIÓN (REFACTORED) ===');
        try {
            $validated = $request->validate([
                'inventory_id' => 'required|integer',
                'return_quantity' => 'required|integer|min:1',
                'retention_substatus' => 'required|in:AVERIAS,REZAGOS',
                'return_observations' => 'required|string|max:1000'
            ]);

            $inventory = Inventory::findOrFail($validated['inventory_id']);
            
            $available = $this->stockService->getAvailable($inventory->item_id, $inventory->localizacion, $inventory->warehouse, $inventory->customer);
            if ($available < $validated['return_quantity']) {
                return redirect()->back()->withErrors(['return_quantity' => "Solo hay {$available} unidades disponibles para retener."]);
            }

            $data = $inventory->toArray();
            $data['status'] = 'RETENCION';
            $data['retention_substatus'] = $validated['retention_substatus'];
            $data['observations'] = $validated['return_observations'];
            $data['quantity'] = $validated['return_quantity'];
            $data['entry_date'] = now();

            $this->inventoryService->registerEntry($data);

            return redirect()->route('inventories.index')->with('success', "Retención registrada correctamente.");

        } catch (\Exception $e) {
            Log::error('Error en processRetention: ' . $e->getMessage());
            return redirect()->back()->withErrors(['general' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function reconcile(Request $request)
    {
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Solo el SUPERADMIN puede realizar esta acción.'
            ], 403);
        }

        try {
            Artisan::call('inventory:reconcile');
            $output = Artisan::output();

            // Parse report if present
            $report = [];
            if (preg_match('/REPORT_START\n(.*?)\nREPORT_END/s', $output, $matches)) {
                $report = json_decode($matches[1], true);
            }

            return response()->json([
                'success' => true,
                'message' => count($report) > 0 
                    ? 'Se encontraron y corrigieron discrepancias en el inventario.' 
                    : 'Inventario reconciliado exitosamente. No se encontraron discrepancias.',
                'report' => $report,
                'output' => $output
            ]);
        } catch (\Exception $e) {
            Log::error('Error reconciliando inventario desde UI: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al reconciliar inventario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Adjusts the stock of a product in the ALMACENAMIENTO location.
     * Only accessible by SUPERADMIN.
     */
    public function adjustStorageStock(Request $request)
    {
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Solo el SUPERADMIN puede realizar esta acción.'
            ], 403);
        }

        try {
            $validated = $request->validate([
                'item_id'      => 'required|integer|exists:items,item_id',
                'new_quantity' => 'required|integer|min:0',
                'warehouse'    => 'required|string',
                'customer'     => 'required|string',
            ]);

            $itemId      = $validated['item_id'];
            $newQuantity = $validated['new_quantity'];
            $warehouse   = $validated['warehouse'];
            $customer    = $validated['customer'];

            // Get current physical stock in ALMACENAMIENTO
            $stockData      = \App\Helpers\ItemLocationStockHelper::calculateCurrentStock($itemId, 'ALMACENAMIENTO');
            $currentPhysical = (int) $stockData['physical'];
            $diff            = $newQuantity - $currentPhysical;

            if ($diff === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sin cambios: el stock ya es igual al valor indicado.',
                    'diff'    => 0,
                ]);
            }

            // Find the ALMACENAMIENTO location record
            $storageLocation = \App\Models\Location::where('code', 'ALMACENAMIENTO')
                ->where('customer', $customer)
                ->first();

            $locationId = $storageLocation ? $storageLocation->location_id : null;

            // Get item info for the record
            $item = \App\Models\Item::find($itemId);

            DB::transaction(function () use ($itemId, $diff, $warehouse, $customer, $locationId, $item) {
                if ($diff > 0) {
                    // Create a positive adjustment entry
                    Inventory::create([
                        'item_id'          => $itemId,
                        'item_description' => $item ? $item->name : 'N/A',
                        'sku'              => $item ? $item->sku : 'N/A',
                        'quantity'         => $diff,
                        'status'           => 'INGRESO',
                        'warehouse'        => $warehouse,
                        'localizacion'     => 'ALMACENAMIENTO',
                        'location_id'      => $locationId,
                        'customer'         => $customer,
                        'entry_date'       => now(),
                        'observations'     => 'AJUSTE MANUAL SUPERADMIN',
                        'type'             => 'AJUSTE',
                        'user_id'          => Auth::id(),
                    ]);
                } else {
                    // For negative diff: find existing INGRESO records and create output records
                    $toRemove = abs($diff);
                    $records  = Inventory::where('item_id', $itemId)
                        ->where('warehouse', $warehouse)
                        ->where('customer', $customer)
                        ->where('localizacion', 'ALMACENAMIENTO')
                        ->where('status', 'INGRESO')
                        ->where('quantity', '>', 0)
                        ->orderBy('created_at', 'asc')
                        ->get();

                    foreach ($records as $record) {
                        if ($toRemove <= 0) break;
                        $physInRecord = $record->quantity - DB::table('inventory_outputs')
                            ->where('inventory_id', $record->id)
                            ->where('status', 'completado')
                            ->sum('quantity');
                        $canTake = min($physInRecord, $toRemove);
                        if ($canTake <= 0) continue;

                        \App\Models\InventoryOutput::create([
                            'inventory_id' => $record->id,
                            'item_id'      => $record->item_id,
                            'item_name'    => $record->item_description,
                            'quantity'     => $canTake,
                            'localizacion' => 'ALMACENAMIENTO',
                            'warehouse'    => $warehouse,
                            'customer'     => $customer,
                            'location_id'  => $record->location_id,
                            'output_date'  => now(),
                            'status'       => 'completado',
                            'guide'        => 'ADJ-SUPERADMIN-' . now()->timestamp,
                            'observations' => 'AJUSTE MANUAL SUPERADMIN',
                            'user_id'      => Auth::id(),
                        ]);
                        $toRemove -= $canTake;
                    }
                }

                // Sync item_locations table
                \App\Helpers\ItemLocationStockHelper::syncStock($itemId, 'ALMACENAMIENTO', $warehouse, $customer);
            });

            Log::info("AJUSTE SUPERADMIN: item_id={$itemId}, diff={$diff}, nuevo_total={$newQuantity}, warehouse={$warehouse}");

            return response()->json([
                'success'      => true,
                'message'      => "Stock ajustado correctamente. Diferencia: {$diff} unidades.",
                'diff'         => $diff,
                'new_quantity' => $newQuantity,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => implode(' ', $e->errors())], 422);
        } catch (\Exception $e) {
            Log::error('Error en adjustStorageStock: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al ajustar stock: ' . $e->getMessage()], 500);
        }
    }

}
