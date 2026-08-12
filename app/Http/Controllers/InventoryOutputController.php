<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InventoryOutput;
use App\Models\Inventory;
use App\Models\InventoryConsolidated;
use App\Models\Item;
use App\Models\City;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\InventoryOutputsExport;
use Illuminate\Support\Facades\Auth;
use App\Helpers\StringHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Imports\InventoryOutputsImport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\InventoryService;
use App\Services\StockService;

class InventoryOutputController extends Controller
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
        $isAdmin = auth()->user()->can('password.create');
        $userCities = $this->getUserCities();
        $selectedCustomers = session('selected_customers', []);
        $warehouseInventories = $this->getWarehouseInventoriesOptimized($selectedCustomers, $userCities, $isAdmin);

        $outputs = InventoryOutput::whereIn('customer', $selectedCustomers)
            ->when(!$isAdmin, function ($query) use ($userCities) {
                return $query->whereIn('warehouse', $userCities);
            })
            ->orderBy('created_at', 'desc')
            ->limit(200)
            ->get();

        $cities = $isAdmin ? City::pluck('city_store')->toArray() : $userCities;

        // OPTIMIZADO - Reemplaza consulta a vw_inventory_unified
        $customers = $this->getUniqueCustomers();

        return view('inventories.stock', compact('warehouseInventories', 'outputs', 'cities', 'customers', 'isAdmin', 'userCities'));
    }


    public function searchOutputs(Request $request)
    {
        $isAdmin = auth()->user()->can('password.create');
        $userCities = $this->getUserCities();
        $selectedCustomers = session('selected_customers', []);
        $query = InventoryOutput::whereIn('customer', $selectedCustomers)
            ->when(!$isAdmin, function ($q) use ($userCities) {
                return $q->whereIn('warehouse', $userCities);
            });

        // Aplicar filtros de búsqueda
        if ($request->filled('guide')) {
            $query->where('guide', 'like', '%' . $request->guide . '%');
        }

        if ($request->filled('item_name')) {
            $query->where('item_name', 'like', '%' . $request->item_name . '%');
        }

        if ($request->filled('warehouse')) {
            $query->where('warehouse', 'like', '%' . $request->warehouse . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Ordenar y paginar
        $outputs = $query->orderBy('created_at', 'desc')
            ->paginate(50); // 50 registros por página

        return response()->json([
            'success' => true,
            'data' => $outputs->items(),
            'pagination' => [
                'current_page' => $outputs->currentPage(),
                'total_pages' => $outputs->lastPage(),
                'total_items' => $outputs->total(),
                'per_page' => $outputs->perPage(),
                'has_more' => $outputs->hasMorePages()
            ]
        ]);
    }


    public function loadMoreOutputs(Request $request)
    {
        $isAdmin = auth()->user()->can('password.create');
        $userCities = $this->getUserCities();
        $selectedCustomers = session('selected_customers', []);
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 50);
        $offset = ($page - 1) * $limit;

        $outputs = InventoryOutput::whereIn('customer', $selectedCustomers)
            ->when(!$isAdmin, function ($query) use ($userCities) {
                return $query->whereIn('warehouse', $userCities);
            })
            ->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $totalCount = InventoryOutput::whereIn('customer', $selectedCustomers)
            ->when(!$isAdmin, function ($query) use ($userCities) {
                return $query->whereIn('warehouse', $userCities);
            })
            ->count();

        $hasMore = ($offset + $limit) < $totalCount;

        return response()->json([
            'success' => true,
            'data' => $outputs,
            'pagination' => [
                'current_page' => $page,
                'has_more' => $hasMore,
                'total_items' => $totalCount,
                'loaded_items' => $offset + count($outputs)
            ]
        ]);
    }

    private function getStockStatus($quantity)
    {
        // Manejar valores nulos o no numéricos
        $quantity = is_numeric($quantity) ? (float) $quantity : 0;

        if ($quantity > 1100) {
            return 'Alta Existencias';
        } elseif ($quantity >= 1000 && $quantity <= 1100) {
            return 'Pronto a Agotar';
        } elseif ($quantity >= 1 && $quantity < 1000) {
            return 'Baja Existencias';
        } else { // $quantity == 0 (o negativos)
            return 'Sin Existencias';
        }
    }


    private function getUserCities()
    {
        $user = Auth::user();
        $userCities = [];

        if ($user->can('password.create')) {
            $userCities = City::pluck('city_store')->toArray();
        } else {
            foreach (City::pluck('city_store') as $city) {
                if ($user->can(strtoupper(StringHelper::normalizeWarehouseName($city)))) {
                    $userCities[] = $city;
                }
            }
        }

        return $userCities;
    }

    public function create($viewType = null)
    {
        $user = Auth::user();
        $userCities = $this->getUserCities();

        $items = Item::select('item_id', 'name')
            ->orderBy('name')
            ->get();

        $cities = City::select('city_id', 'city_store')
            ->whereIn('city_store', $userCities)
            ->orderBy('city_store')
            ->get();

        $view = ($viewType === 'return') ? 'inventories.create_return' : 'inventories.create_output';

        return view($view, compact('items', 'cities'));
    }


    public function store(Request $request)
    {
        Log::info('=== INICIO STORE SALIDA (REFACTORED) ===');
        try {
            $validatedData = $request->validate([
                'guide' => 'required|string|max:255',
                'created_at' => 'required|date',
                'declared_value' => 'required|numeric|min:0',
                'products' => 'required|array|min:1',
                'products.*.item_id' => 'required|exists:items,item_id',
                'products.*.warehouse' => 'required|string|max:255',
                'products.*.location_id' => 'required|integer',
                'products.*.location_code' => 'required|string|max:50',
                'products.*.quantity' => 'required|integer|min:1',
                'products.*.status' => 'required|string|max:50',
            ]);

            $selectedCustomers = session('selected_customers', []);
            if (empty($selectedCustomers)) {
                return response()->json(['success' => false, 'message' => 'No se ha seleccionado un cliente.'], 400);
            }
            $customer = $selectedCustomers[0];

            DB::transaction(function () use ($validatedData, $customer) {
                foreach ($validatedData['products'] as $product) {
                    $this->inventoryService->registerExitByLocation(
                        $product['item_id'],
                        $product['location_id'],
                        $product['quantity'],
                        [
                            'guide' => $validatedData['guide'],
                            'customer' => $customer,
                            'warehouse' => $product['warehouse'] ?? null,
                            'type' => 'Manual-Exit',
                            'observations' => $request->input('observations', 'Salida manual desde panel')
                        ]
                    );
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Salida(s) registrada(s) correctamente. Se procesaron ' . count($validatedData['products']) . ' producto(s).',
            ]);

        } catch (\Exception $e) {
            Log::error('Error en InventoryOutputController@store: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getLocationsByItem($itemId)
    {
        try {
            $selectedCustomers = session('selected_customers', []);

            if (empty($selectedCustomers)) {
                return response()->json(['error' => 'No se ha seleccionado un cliente'], 400);
            }

            // OPTIMIZADO - Obtener ubicaciones con stock
            $locations = $this->getLocationsWithStock($itemId, $selectedCustomers);

            Log::info('Ubicaciones encontradas', [
                'item_id' => $itemId,
                'customers' => $selectedCustomers,
                'count' => $locations->count()
            ]);

            return response()->json($locations);

        } catch (\Exception $e) {
            Log::error('Error en getLocationsByItem: ' . $e->getMessage(), [
                'item_id' => $itemId,
                'customers' => session('selected_customers'),
                'stack_trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error al recuperar ubicaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    public function searchLocations(Request $request)
    {
        $query = $request->get('query');
        $warehouse = $request->get('warehouse');
        $itemId = $request->get('item_id');
        $selectedCustomers = session('selected_customers', []);
        
        if (!$query || !$warehouse || !$itemId) {
            return response()->json([]);
        }

        // OPTIMIZADO - Buscar ubicaciones con stock
        $locations = DB::table('inventories as i')
            ->leftJoin('locations as l', 'i.location_id', '=', 'l.location_id')
            ->leftJoin(DB::raw('(
                SELECT inventory_id, 
                       SUM(CASE WHEN status = "completado" THEN quantity ELSE 0 END) as total_salidas,
                       SUM(CASE WHEN status = "devolucion" THEN quantity ELSE 0 END) as total_devoluciones
                FROM inventory_outputs
                GROUP BY inventory_id
            ) as io'), 'i.id', '=', 'io.inventory_id')
            ->where('i.item_id', $itemId)
            ->whereIn('i.customer', $selectedCustomers)
            ->where('i.warehouse', $warehouse)
            ->where('i.status', '!=', 'RETENCION')
            ->where('l.code', 'LIKE', "%{$query}%")
            ->whereNotNull('i.location_id')
            ->select([
                'i.location_id',
                'l.code as location_code',
                'l.name as location_name',
                DB::raw('GREATEST(0, COALESCE(i.quantity, 0) - COALESCE(io.total_salidas, 0) + COALESCE(io.total_devoluciones, 0)) as current_stock')
            ])
            ->havingRaw('current_stock > 0')
            ->get()
            ->map(function ($location) {
                return [
                    'location_id' => $location->location_id,
                    'location_code' => $location->location_code,
                    'location_name' => $location->location_name,
                    'current_quantity' => (int) $location->current_stock
                ];
            });

        return response()->json($locations);
    }

    public function checkAvailability(Request $request)
    {
        $itemName = $request->query('item');
        $warehouse = $request->query('warehouse');
        $selectedCustomers = session('selected_customers', []);
        
        if (!$itemName || !$warehouse) {
            return response()->json([
                'available' => false,
                'message' => 'Parámetros insuficientes'
            ]);
        }

        // OPTIMIZADO - Verificar disponibilidad
        $totalStock = DB::table('inventories as i')
            ->leftJoin('items as it', 'i.item_id', '=', 'it.item_id')
            ->leftJoin(DB::raw('(
                SELECT inventory_id, 
                       SUM(CASE WHEN status = "completado" THEN quantity ELSE 0 END) as total_salidas,
                       SUM(CASE WHEN status = "devolucion" THEN quantity ELSE 0 END) as total_devoluciones
                FROM inventory_outputs
                GROUP BY inventory_id
            ) as io'), 'i.id', '=', 'io.inventory_id')
            ->where('it.name', $itemName)
            ->where('i.warehouse', $warehouse)
            ->whereIn('i.customer', $selectedCustomers)
            ->where('i.status', '!=', 'RETENCION')
            ->select(DB::raw('SUM(GREATEST(0, COALESCE(i.quantity, 0) - COALESCE(io.total_salidas, 0) + COALESCE(io.total_devoluciones, 0))) as total_stock'))
            ->value('total_stock') ?? 0;

        return response()->json([
            'available' => $totalStock > 0,
            'stockAvailable' => $totalStock,
            'stockStatus' => $this->getStockStatus($totalStock),
            'message' => $totalStock > 0 ? 'Producto disponible' : 'No hay stock disponible'
        ]);
    }

    // Resto de métodos sin cambios...
    public function generateReport(Request $request)
    {
        $date = $request->input('date');

        $outputs = InventoryOutput::select('inventory_outputs.*')
            ->whereIn('customer', session('selected_customers', []))
            ->whereDate('inventory_outputs.created_at', $date)
            ->get();

        $reportNumber = InventoryOutput::max('id') + 1;

        try {
            $pdf = Pdf::loadView('inventories.report_pdf', compact('outputs', 'date', 'reportNumber'));
            return $pdf->download('reporte_salidas_' . $date . '.pdf');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al generar el PDF: ' . $e->getMessage());
        }
    }

    public function exportExcel()
    {
        try {
            $timestamp = Carbon::now()->format('Y_m_d_His');
            $filename = 'salidas_inventario_' . $timestamp . '.xlsx';

            Log::info('Exportación de salidas iniciada.', [
                'user_id' => Auth::id(),
                'filename' => $filename
            ]);

            return Excel::download(new InventoryOutputsExport, $filename);
        } catch (\Exception $e) {
            Log::error('Error en exportación de salidas: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al exportar el archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getItemImage($itemDescription)
    {
        $item = Item::where('name', $itemDescription)->first();

        if ($item && $item->ruta) {
            return response()->json(['image_url' => asset('images/' . $item->ruta)]);
        }

        return response()->json(['image_url' => null]);
    }

    public function getDashboardData()
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        $outputs = InventoryOutput::whereIn('customer', session('selected_customers', []))
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->select(
                'id',
                'warehouse',
                'item_name',
                'quantity',
                'created_at'
            )
            ->orderBy('created_at', 'desc')
            ->get();

        $result = $outputs->groupBy('warehouse')->map(function ($group) {
            return [
                'warehouse' => $group->first()->warehouse,
                'outputs' => $group->groupBy('id')->map(function ($subGroup) {
                    return [
                        'item_description' => $subGroup->first()->item_name,
                        'total_quantity' => $subGroup->sum('quantity'),
                        'created_at' => $subGroup->first()->created_at
                    ];
                })->values(),
                'total_quantity' => $group->sum('quantity')
            ];
        })->values();

        return response()->json($result);
    }

    public function edit($id)
    {
        $output = InventoryOutput::findOrFail($id);

        $items = Item::select('item_id', 'name')
            ->orderBy('name')
            ->get();

        $userCities = $this->getUserCities();
        $cities = City::select('city_id', 'city_store')
            ->whereIn('city_store', $userCities)
            ->orderBy('city_store')
            ->get();

        return view('inventories.edit_output', compact('output', 'items', 'cities'));
    }




    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $validatedData = $request->validate([
                'item_id' => 'required|exists:items,item_id',
                'warehouse' => 'required|string|max:255',
                'guide' => 'required|string|max:255',
                'quantity' => 'required|integer|min:1',
                'created_at' => 'required|date',
                'declared_value' => 'required|numeric',
                'status' => 'required|string|max:50',
            ]);

            $selectedCustomers = session('selected_customers', []);
            $customer = !empty($selectedCustomers) ? $selectedCustomers[0] : null;
            $inventoryOutput = InventoryOutput::findOrFail($id);
            $item = Item::where('item_id', $validatedData['item_id'])->firstOrFail();

            $locationCode = $inventoryOutput->localizacion; // Usar la ubicación actual

            // OPTIMIZADO - Validar stock global
            $currentStockGlobal = $this->getStockGlobal($validatedData['item_id'], $validatedData['warehouse'], $customer);

            // Ajustar stock sumando la cantidad actual del registro
            $adjustedStockGlobal = $currentStockGlobal + $inventoryOutput->quantity;

            if ($validatedData['quantity'] > $adjustedStockGlobal) {
                DB::rollBack();
                return redirect()->back()->with('error', 'La cantidad solicitada (' . $validatedData['quantity'] . ') excede el stock disponible total (' . $adjustedStockGlobal . ').');
            }

            // OPTIMIZADO - Validar stock por ubicación específica
            $currentStockLocation = $this->getStockByLocation($validatedData['item_id'], $locationCode, $validatedData['warehouse'], $customer);

            // Ajustar stock de la ubicación sumando la cantidad actual
            $adjustedStockLocation = ($currentStockLocation ?? 0) + $inventoryOutput->quantity;

            if ($currentStockLocation === null || $validatedData['quantity'] > $adjustedStockLocation) {
                DB::rollBack();
                return redirect()->back()->with('error', "La ubicación '{$locationCode}' no tiene suficiente stock. Disponible: {$adjustedStockLocation}, Solicitado: {$validatedData['quantity']} (ajustado con {$inventoryOutput->quantity}).");
            }

            $inventoryOutput->update([
                'item_id' => $validatedData['item_id'],
                'item_name' => $item->name,
                'guide' => $validatedData['guide'],
                'quantity' => $validatedData['quantity'],
                'created_at' => $validatedData['created_at'],
                'declared_value' => $validatedData['declared_value'],
                'status' => $validatedData['status'],
                'warehouse' => $validatedData['warehouse'],
            ]);

            DB::commit();
            return redirect()->route('inventory-outputs.index')->with('success', 'Salida actualizada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en update: ' . $e->getMessage(), [
                'request' => $request->all(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }


    public function destroy($id)
    {
        $inventoryOutput = InventoryOutput::findOrFail($id);
        $inventoryOutput->delete();

        return redirect()->route('inventory-outputs.index')->with('success', 'Salida eliminada correctamente.');
    }

    public function searchItems(Request $request)
    {
        $query = $request->get('query');

        if (!$query || strlen($query) < 2) {
            return response()->json([]);
        }

        $items = Item::where('name', 'LIKE', "%{$query}%")
            ->select('item_id', 'name', 'ruta')
            ->orderBy('name')
            ->limit(10)
            ->get();

        $itemsWithImages = $items->map(function ($item) {
            $item->image_url = $item->ruta ? asset('images/' . $item->ruta) : null;
            return $item;
        });

        return response()->json($itemsWithImages);
    }

    public function searchWarehouses(Request $request)
    {
        $query = $request->get('query');
        $userCities = $this->getUserCities();

        if (!$query) {
            return response()->json([]);
        }

        $cities = City::where('city_store', 'LIKE', "%{$query}%")
            ->whereIn('city_store', $userCities)
            ->select('city_id', 'city_store')
            ->orderBy('city_store')
            ->limit(10)
            ->get();

        return response()->json($cities);
    }


    public function showImportForm()
    {
        try {
            return response()->json([
                'success' => true,
                'html' => view('inventories.partials.import_modal')->render()
            ]);
        } catch (\Exception $e) {
            Log::error('Error in showImportForm: ' . $e->getMessage(), [
                'stack_trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Error al cargar el formulario: ' . $e->getMessage()
            ], 500);
        }
    }

    public function importMassive(Request $request)
    {
        DB::beginTransaction(); // Transacción global para "todo o nada"
        try {
            $request->validate([
                'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            ]);

            if (empty(session('selected_customers', []))) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No se ha seleccionado un cliente. Seleccione un cliente antes de importar.',
                    'errors' => ['No se ha seleccionado un cliente.']
                ], 400);
            }

            $file = $request->file('excel_file');
            if (!$file) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró el archivo subido. Verifique el formulario.',
                    'errors' => ['Archivo no encontrado.']
                ], 400);
            }

            Log::info('Importación masiva iniciada. Archivo recibido: ' . $file->getClientOriginalName(), [
                'user_id' => Auth::id(),
                'customers' => session('selected_customers', [])
            ]);

            $import = new InventoryOutputsImport();
            Excel::import($import, $file); // Procesa TODAS las filas

            $errors = $import->getErrors();
            $successCount = $import->getSuccessCount();
            $errorCount = $import->getErrorCount();
            $total = $successCount + $errorCount;

            if ($total === 0) {
                DB::rollBack();
                Log::warning('Importación masiva: Archivo vacío o sin datos procesables.', [
                    'user_id' => Auth::id(),
                    'file_name' => $file->getClientOriginalName(),
                    'errors' => $errors
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo está vacío, no contiene datos válidos o todas las filas fueron ignoradas. Verifique el contenido del archivo.',
                    'errors' => $errors, // Lista completa de errores (vacía en este caso)
                    'stats' => ['success' => 0, 'errors' => $errorCount, 'total' => 0]
                ], 400);
            }

            if ($successCount === 0) {
                DB::rollBack();
                Log::warning('Importación masiva: No se procesaron registros válidos.', [
                    'user_id' => Auth::id(),
                    'file_name' => $file->getClientOriginalName(),
                    'errors' => $errors
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'No se procesaron registros válidos. Verifique que el archivo cumpla con los requisitos.',
                    'errors' => $errors, // Lista completa
                    'stats' => ['success' => 0, 'errors' => $errorCount, 'total' => $total]
                ], 400);
            }

            if ($errorCount > 0) {
                DB::rollBack(); // Rollback completo: no se guarda NADA
                Log::warning('Importación masiva: Errores detectados, rollback completo.', [
                    'user_id' => Auth::id(),
                    'file_name' => $file->getClientOriginalName(),
                    'errors' => $errors // Lista completa de todos los errores
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'La importación fue bloqueada debido a errores. Corrija el archivo y vuelva a intentarlo. No se cargó ninguna fila.',
                    'errors' => $errors, // Muestra TODOS los errores recolectados
                    'stats' => ['success' => $successCount, 'errors' => $errorCount, 'total' => $total]
                ], 400);
            }

            DB::commit(); // Solo si NO hay errores
            Log::info('Importación masiva exitosa: Todas las filas válidas cargadas.', [
                'user_id' => Auth::id(),
                'file_name' => $file->getClientOriginalName(),
                'success_count' => $successCount
            ]);

            return response()->json([
                'success' => true,
                'message' => "Importación completada exitosamente. {$successCount} filas cargadas sin errores.",
                'stats' => ['success' => $successCount, 'errors' => $errorCount, 'total' => $total]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error inesperado en importación masiva: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'file_name' => $file->getClientOriginalName() ?? 'desconocido',
                'stack_trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error inesperado durante la importación: ' . $e->getMessage(),
                'errors' => [$e->getMessage()]
            ], 500);
        }
    }



    public function downloadTemplate()
    {
        $filePath = public_path('documents/Archivo_Salida_Inventario.xlsx');

        if (!file_exists($filePath)) {
            Log::error('Plantilla de importación no encontrada en: ' . $filePath);
            return response()->json([
                'success' => false,
                'message' => 'La plantilla no se encontró en el servidor. Por favor, contacte al administrador.'
            ], 404);
        }

        Log::info('Descarga de plantilla: ' . $filePath . ' solicitada por user_id: ' . Auth::id());

        return response()->download($filePath, 'plantilla_salidas_inventario.xlsx');
    }

    // ========================================
    // MÉTODOS PRIVADOS PARA CÁLCULO DE STOCK
    // Reemplazan la lógica de vw_inventory_unified
    // ========================================

    /**
     * Obtiene inventarios con stock calculado (reemplaza vw_inventory_unified)
     * Replica la lógica EXACTA de la vista de BD
     * 
     * @param string $customer
     * @param array $userCities
     * @param bool $isAdmin
     * @return \Illuminate\Support\Collection
     */
    private function getWarehouseInventoriesOptimized($customers, $userCities, $isAdmin)
    {
        // Replicar la primera parte del UNION de vw_inventory_unified
        // Solo ubicaciones normales (no ALMACENAMIENTO) con item_locations asignados
        $query = DB::table('locations as l')
            ->join('item_locations as il', 'l.location_id', '=', 'il.location_id')
            ->join('items as it', 'il.item_id', '=', 'it.item_id')
            ->leftJoin('inventories as i', function ($join) {
                $join->on('it.item_id', '=', 'i.item_id')
                    ->on('i.localizacion', '=', 'l.code');
            })
            ->where('l.is_active', 1)
            ->where('l.code', '!=', 'ALMACENAMIENTO')
            ->whereIn('l.customer', $customers)
            ->when(!$isAdmin, function ($q) use ($userCities) {
                return $q->whereIn('l.warehouse', $userCities);
            })
            ->groupBy([
                'l.location_id',
                'l.code',
                'l.name',
                'l.warehouse',
                'l.customer',
                'it.item_id',
                'it.sku',
                'it.name',
                'it.ruta',
                'il.max_capacity',
                'il.current_quantity',
                'il.assigned_at'
            ])
            ->select([
                'l.location_id',
                'l.code as location_code',
                'l.name as location_name',
                'l.warehouse',
                'l.customer',
                'it.item_id',
                'it.sku',
                'it.name as item_description',

                // original_entries: suma de INGRESO + SALIDA
                DB::raw('COALESCE(SUM(CASE WHEN i.status IN ("INGRESO", "SALIDA") THEN i.quantity ELSE 0 END), 0) as original_entries'),

                // total_returns: devoluciones desde inventory_outputs
                DB::raw('ABS(COALESCE((
                    SELECT SUM(io.quantity) 
                    FROM inventory_outputs io 
                    WHERE io.item_id = it.item_id 
                    AND io.localizacion = l.code 
                    AND io.status = "devolucion"
                ), 0)) as total_returns'),

                // total_retention: suma de retenciones
                DB::raw('COALESCE(SUM(CASE WHEN i.status = "RETENCION" THEN i.quantity ELSE 0 END), 0) as total_retention'),

                // total_outputs: salidas (excluyendo devoluciones)
                DB::raw('COALESCE((
                    SELECT SUM(io.quantity) 
                    FROM inventory_outputs io 
                    WHERE io.item_id = it.item_id 
                    AND io.localizacion = l.code 
                    AND io.status <> "devolucion"
                ), 0) as total_outputs'),

                // current_stock: (original_entries - total_outputs + total_returns) - total_retention
                DB::raw('COALESCE((
                    (
                        SUM(CASE WHEN i.status IN ("INGRESO", "SALIDA") THEN i.quantity ELSE 0 END) 
                        - COALESCE((
                            SELECT SUM(io.quantity) 
                            FROM inventory_outputs io 
                            WHERE io.item_id = it.item_id 
                            AND io.localizacion = l.code 
                            AND io.status <> "devolucion"
                        ), 0)
                        + ABS(COALESCE((
                            SELECT SUM(io.quantity) 
                            FROM inventory_outputs io 
                            WHERE io.item_id = it.item_id 
                            AND io.localizacion = l.code 
                            AND io.status = "devolucion"
                        ), 0))
                    ) - SUM(CASE WHEN i.status = "RETENCION" THEN i.quantity ELSE 0 END)
                ), 0) as current_stock'),

                // alert_level: basado en max_capacity
                DB::raw('CASE 
                    WHEN COALESCE((
                        (
                            SUM(CASE WHEN i.status IN ("INGRESO", "SALIDA") THEN i.quantity ELSE 0 END) 
                            - COALESCE((SELECT SUM(io.quantity) FROM inventory_outputs io WHERE io.item_id = it.item_id AND io.localizacion = l.code AND io.status <> "devolucion"), 0)
                            + ABS(COALESCE((SELECT SUM(io.quantity) FROM inventory_outputs io WHERE io.item_id = it.item_id AND io.localizacion = l.code AND io.status = "devolucion"), 0))
                        ) - SUM(CASE WHEN i.status = "RETENCION" THEN i.quantity ELSE 0 END)
                    ), 0) >= COALESCE(il.max_capacity, 0) THEN "danger"
                    WHEN COALESCE((
                        (
                            SUM(CASE WHEN i.status IN ("INGRESO", "SALIDA") THEN i.quantity ELSE 0 END) 
                            - COALESCE((SELECT SUM(io.quantity) FROM inventory_outputs io WHERE io.item_id = it.item_id AND io.localizacion = l.code AND io.status <> "devolucion"), 0)
                            + ABS(COALESCE((SELECT SUM(io.quantity) FROM inventory_outputs io WHERE io.item_id = it.item_id AND io.localizacion = l.code AND io.status = "devolucion"), 0))
                        ) - SUM(CASE WHEN i.status = "RETENCION" THEN i.quantity ELSE 0 END)
                    ), 0) >= (COALESCE(il.max_capacity, 0) * 0.8) THEN "warning"
                    ELSE "success"
                END as alert_level'),

                // last_modified_date
                DB::raw('COALESCE(
                    GREATEST(
                        COALESCE(MAX(i.updated_at), "1970-01-01"),
                        COALESCE((SELECT MAX(io.updated_at) FROM inventory_outputs io WHERE io.item_id = it.item_id AND io.localizacion = l.code), "1970-01-01")
                    ),
                    "1970-01-01"
                ) as last_modified_date')
            ])
            ->get();

        return $query->map(function ($item) {
            $stock = is_numeric($item->current_stock) ? (float) $item->current_stock : 0;
            $status = $this->getStockStatus($stock);

            $cssClass = $stock > 1100 ? 'stock-high' :
                ($stock >= 1000 && $stock <= 1100 ? 'stock-warning' :
                    ($stock >= 1 && $stock < 1000 ? 'stock-low' : 'stock-no'));

            $item->stock_status = $status;
            $item->stock_css_class = $cssClass;

            return $item;
        });
    }

    /**
     * Obtiene stock global por item_id, warehouse y customer
     * Usa la misma fórmula que vw_inventory_unified
     * 
     * @param int $itemId
     * @param string $warehouse
     * @param string $customer
     * @return float
     */
    private function getStockGlobal($itemId, $warehouse, $customer)
    {
        // Agrupar por ubicación y sumar el stock de cada una
        $result = DB::table('locations as l')
            ->join('inventories as i', function ($join) {
                $join->on('i.localizacion', '=', 'l.code');
            })
            ->where('i.item_id', $itemId)
            ->where('i.warehouse', $warehouse)
            ->where('i.customer', $customer)
            ->where('l.is_active', 1)
            ->select(DB::raw('
                SUM(
                    COALESCE((
                        (
                            CASE WHEN i.status IN ("INGRESO", "SALIDA") THEN i.quantity ELSE 0 END
                            - COALESCE((
                                SELECT SUM(io.quantity) 
                                FROM inventory_outputs io 
                                WHERE io.item_id = ' . $itemId . '
                                AND io.localizacion = l.code 
                                AND io.status <> "devolucion"
                            ), 0)
                            + ABS(COALESCE((
                                SELECT SUM(io.quantity) 
                                FROM inventory_outputs io 
                                WHERE io.item_id = ' . $itemId . '
                                AND io.localizacion = l.code 
                                AND io.status = "devolucion"
                            ), 0))
                        ) - CASE WHEN i.status = "RETENCION" THEN i.quantity ELSE 0 END
                    ), 0)
                ) as total_stock
            '))
            ->value('total_stock');

        return (float) ($result ?? 0);
    }

    /**
     * Obtiene stock por ubicación específica
     * Usa la misma fórmula que vw_inventory_unified
     * 
     * @param int $itemId
     * @param string $locationCode
     * @param string $warehouse
     * @param string $customer
     * @return float
     */
    private function getStockByLocation($itemId, $locationCode, $warehouse, $customer)
    {
        $result = DB::table('inventories as i')
            ->where('i.item_id', $itemId)
            ->where('i.localizacion', $locationCode)
            ->where('i.warehouse', $warehouse)
            ->where('i.customer', $customer)
            ->select(DB::raw('
                COALESCE((
                    (
                        SUM(CASE WHEN i.status IN ("INGRESO", "SALIDA") THEN i.quantity ELSE 0 END)
                        - COALESCE((
                            SELECT SUM(io.quantity) 
                            FROM inventory_outputs io 
                            WHERE io.item_id = ' . $itemId . '
                            AND io.localizacion = "' . $locationCode . '"
                            AND io.status <> "devolucion"
                        ), 0)
                        + ABS(COALESCE((
                            SELECT SUM(io.quantity) 
                            FROM inventory_outputs io 
                            WHERE io.item_id = ' . $itemId . '
                            AND io.localizacion = "' . $locationCode . '"
                            AND io.status = "devolucion"
                        ), 0))
                    ) - SUM(CASE WHEN i.status = "RETENCION" THEN i.quantity ELSE 0 END)
                ), 0) as current_stock
            '))
            ->value('current_stock');

        return (float) ($result ?? 0);
    }

    /**
     * Obtiene la descripción del item desde la tabla items
     * 
     * @param int $itemId
     * @return string|null
     */
    private function getItemDescription($itemId)
    {
        return DB::table('items')
            ->where('item_id', $itemId)
            ->value('name');
    }

    /**
     * Obtiene ubicaciones con stock disponible para un item
     * 
     * @param int $itemId
     * @param string $customer
     * @return \Illuminate\Support\Collection
     */
    private function getLocationsWithStock($itemId, $customer)
    {
        return DB::table('inventories as i')
            ->leftJoin('locations as l', 'i.location_id', '=', 'l.location_id')
            ->leftJoin(DB::raw('(
                SELECT inventory_id, 
                       SUM(CASE WHEN status = "completado" THEN quantity ELSE 0 END) as total_salidas,
                       SUM(CASE WHEN status = "devolucion" THEN quantity ELSE 0 END) as total_devoluciones
                FROM inventory_outputs
                GROUP BY inventory_id
            ) as io'), 'i.id', '=', 'io.inventory_id')
            ->where('i.item_id', $itemId)
            ->where('i.customer', $customer)
            ->where('i.status', '!=', 'RETENCION')
            ->whereNotNull('i.location_id')
            ->whereNotNull('l.code')
            ->select([
                'i.location_id',
                'l.code as location_code',
                'l.name as location_name',
                'i.warehouse',
                DB::raw('GREATEST(0, COALESCE(i.quantity, 0) - COALESCE(io.total_salidas, 0) + COALESCE(io.total_devoluciones, 0)) as current_stock'),
                DB::raw('COALESCE(io.total_salidas, 0) as total_outputs'),
                DB::raw('0 as alert_level') // Placeholder
            ])
            ->havingRaw('current_stock > 0')
            ->orderBy('l.name')
            ->get()
            ->map(function ($location) {
                return [
                    'location_id' => $location->location_id,
                    'location_code' => $location->location_code,
                    'location_name' => $location->location_name,
                    'warehouse' => $location->warehouse,
                    'current_quantity' => (int) $location->current_stock,
                    'total_outputs' => (int) $location->total_outputs,
                    'alert_level' => $location->alert_level
                ];
            });
    }

    /**
     * Obtiene lista de clientes únicos desde inventarios
     * 
     * @return array
     */
    private function getUniqueCustomers()
    {
        return DB::table('inventories')
            ->distinct()
            ->whereNotNull('customer')
            ->pluck('customer')
            ->toArray();
    }
}
