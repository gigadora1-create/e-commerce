<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\InventoryConsolidated;
use App\Models\InventoryRetention;
use App\Models\InventoryDetailed;
use App\Models\InventoryOutput;


class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = auth()->user();

        $selectedCustomers = session('selected_customers', []);
        $selectedCustomer = !empty($selectedCustomers) ? $selectedCustomers[0] : session('selected_customer');

        if (empty($selectedCustomers)) {
            return redirect()->route('customer.context.index');
        }

        if ($user && method_exists($user, 'isWarehouseOnly') && $user->isWarehouseOnly()) {
            return redirect()->route('warehouse.index');
        }

        if ($user && method_exists($user, 'isSupplyRequesterOnly') && $user->isSupplyRequesterOnly()) {
            return redirect()->route('supplies.issues.index');
        }

        $isSuperAdmin = $user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();
        $cityPermissions = [];

        if (!$isSuperAdmin) {
            $userPermissions = $user->getAllPermissions()->pluck('name')->toArray();
            $cityPermissions = array_filter($userPermissions, function($permission) {
                return !in_array($permission, ['password.create', 'user.management']);
            });
        }

        $last30Days = Carbon::now()->subDays(30);

        $metrics = $this->getMetrics($last30Days, $cityPermissions, $selectedCustomers, $isSuperAdmin);
        $topProducts = $this->getTopProducts($cityPermissions, $selectedCustomers, $isSuperAdmin);
        $monthlyData = $this->getMonthlyData($cityPermissions, $selectedCustomers, $isSuperAdmin);
        $productDistribution = $this->getProductDistribution($last30Days, $cityPermissions, $selectedCustomers, $isSuperAdmin);

        $metrics = $metrics ?? [];
        $topProducts = $topProducts ?? collect();
        $monthlyData = $monthlyData ?? ['months' => [], 'entries' => [], 'outputs' => []];
        $productDistribution = $productDistribution ?? [];

        return view('dashboard.index', compact(
            'metrics',
            'topProducts',
            'monthlyData',
            'productDistribution',
            'selectedCustomers',
            'selectedCustomer'
        ));
    }

    private function getMetrics($last30Days, $cityPermissions, $selectedCustomers, $isSuperAdmin = false)
    {
        // CONSULTAS OPTIMIZADAS - Reemplazando vw_inventory_unified costosa
        // Construir condiciones base
        $inventoryQuery = DB::table('inventories as i');
        
        if (!empty($selectedCustomers)) {
            $inventoryQuery->whereIn('i.customer', $selectedCustomers);
        }

        if (!$isSuperAdmin && !empty($cityPermissions)) {
            $inventoryQuery->where(function($query) use ($cityPermissions) {
                foreach ($cityPermissions as $permission) {
                    $query->orWhere('i.warehouse', 'like', "%{$permission}%");
                }
            });
        }

        // ENTRADAS por bodega (optimizado - consulta directa)
        $entriesQuery = clone $inventoryQuery;
        $totalEntriesByWarehouse = $entriesQuery
            ->whereIn('i.status', ['INGRESO', 'SALIDA'])
            ->select('i.warehouse', DB::raw('SUM(i.quantity) as total'))
            ->groupBy('i.warehouse')
            ->pluck('total', 'warehouse')
            ->toArray();

        // DEVOLUCIONES por bodega (optimizado - desde inventory_outputs)
        $returnsQuery = DB::table('inventory_outputs as io');
        if (!empty($selectedCustomers)) {
            $returnsQuery->whereIn('io.customer', $selectedCustomers);
        }
        if (!$isSuperAdmin && !empty($cityPermissions)) {
            $returnsQuery->where(function($query) use ($cityPermissions) {
                foreach ($cityPermissions as $permission) {
                    $query->orWhere('io.warehouse', 'like', "%{$permission}%");
                }
            });
        }
        $returnsByWarehouse = $returnsQuery
            ->where('io.status', 'devolucion')
            ->select('io.warehouse', DB::raw('SUM(io.quantity) as total'))
            ->groupBy('io.warehouse')
            ->pluck('total', 'warehouse')
            ->toArray();
        $totalReturns = array_sum($returnsByWarehouse);

        // DEVOLUCIONES detalladas (optimizado)
        $returnsDetailQuery = clone $returnsQuery;
        $returnsByReasonAndWarehouse = $returnsDetailQuery
            ->where('io.status', 'devolucion')
            ->join('inventories as i', 'io.inventory_id', '=', 'i.id')
            ->select(
                'io.warehouse',
                'i.item_description',
                'io.customer',
                DB::raw('SUM(io.quantity) as total_quantity')
            )
            ->groupBy('io.warehouse', 'i.item_description', 'io.customer')
            ->get()
            ->map(function($item) {
                return (object)[
                    'warehouse' => $item->warehouse,
                    'item_description' => $item->item_description ?? 'Sin descripción',
                    'customer' => $item->customer ?? 'N/A',
                    'reason' => 'Devolución',
                    'total_quantity' => (int)$item->total_quantity
                ];
            });

        // LIBERADOS por bodega (optimizado)
        $releasedQuery = clone $inventoryQuery;
        $releasedByWarehouse = $releasedQuery
            ->where('i.status', 'LIBERADO')
            ->select('i.warehouse', DB::raw('SUM(i.quantity) as total'))
            ->groupBy('i.warehouse')
            ->pluck('total', 'warehouse')
            ->toArray();

        // RETENCIONES por bodega (optimizado)
        $retentionsQuery = clone $inventoryQuery;
        $retentionsByWarehouse = $retentionsQuery
            ->where('i.status', 'RETENCION')
            ->select('i.warehouse', DB::raw('SUM(i.quantity) as total'))
            ->groupBy('i.warehouse')
            ->pluck('total', 'warehouse')
            ->toArray();
        $totalRetentions = array_sum($retentionsByWarehouse);

        // RETENCIONES por subtipo (optimizado)
        $retentionsSubstatusQuery = clone $inventoryQuery;
        $retentionsBySubstatus = $retentionsSubstatusQuery
            ->where('i.status', 'RETENCION')
            ->whereNotNull('i.retention_substatus')
            ->select('i.retention_substatus', DB::raw('SUM(i.quantity) as total'))
            ->groupBy('i.retention_substatus')
            ->pluck('total', 'retention_substatus')
            ->toArray();

        // RETENCIONES por subtipo y bodega (optimizado)
        $retentionsSubstatusWarehouseQuery = clone $inventoryQuery;
        $retentionsBySubstatusAndWarehouse = $retentionsSubstatusWarehouseQuery
            ->where('i.status', 'RETENCION')
            ->whereNotNull('i.retention_substatus')
            ->select('i.warehouse', 'i.retention_substatus', DB::raw('SUM(i.quantity) as total'))
            ->groupBy('i.warehouse', 'i.retention_substatus')
            ->get()
            ->groupBy('warehouse')
            ->map(function($group) {
                return $group->pluck('total', 'retention_substatus')->toArray();
            })
            ->toArray();

        // SALIDAS desde InventoryOutput
        $outputsQuery = InventoryOutput::query();

        if (!empty($selectedCustomers)) {
            $outputsQuery->whereIn('customer', $selectedCustomers);
        }

        if (!$isSuperAdmin && !empty($cityPermissions)) {
            $outputsQuery->where(function($query) use ($cityPermissions) {
                foreach ($cityPermissions as $permission) {
                    $query->orWhere('warehouse', 'like', "%{$permission}%");
                }
            });
        }

        $outputsData = $outputsQuery
            ->select('warehouse', DB::raw('SUM(quantity) as total_quantity'))
            ->groupBy('warehouse')
            ->pluck('total_quantity', 'warehouse')
            ->toArray();

        $allOutputs = array_sum($outputsData);

        // STOCK DISPONIBLE por bodega (optimizado - cálculo directo)
        // Stock = Entradas - Salidas - Retenciones + Devoluciones
        $stockQuery = DB::table('inventories as i')
            ->leftJoin(DB::raw('(SELECT inventory_id, SUM(quantity) as total_salidas 
                FROM inventory_outputs 
                WHERE status = "completado" 
                GROUP BY inventory_id) as io'), 'i.id', '=', 'io.inventory_id')
            ->leftJoin(DB::raw('(SELECT inventory_id, SUM(quantity) as total_devoluciones 
                FROM inventory_outputs 
                WHERE status = "devolucion" 
                GROUP BY inventory_id) as dev'), 'i.id', '=', 'dev.inventory_id')
            ->where('i.status', '!=', 'RETENCION')
            ->select(
                'i.warehouse',
                DB::raw('SUM(COALESCE(i.quantity, 0) - COALESCE(io.total_salidas, 0) + COALESCE(dev.total_devoluciones, 0)) as stock')
            )
            ->groupBy('i.warehouse');
        
        if (!empty($selectedCustomers)) {
            $stockQuery->whereIn('i.customer', $selectedCustomers);
        }
        if (!$isSuperAdmin && !empty($cityPermissions)) {
            $stockQuery->where(function($query) use ($cityPermissions) {
                foreach ($cityPermissions as $permission) {
                    $query->orWhere('i.warehouse', 'like', "%{$permission}%");
                }
            });
        }
        
        $totalStockByWarehouse = $stockQuery
            ->havingRaw('stock > 0')
            ->pluck('stock', 'warehouse')
            ->toArray();

        // Stock por cliente (optimizado)
        $totalStockByCustomer = [];
        if (!empty($selectedCustomers)) {
            $customerStockQuery = DB::table('inventories as i')
                ->leftJoin(DB::raw('(SELECT inventory_id, SUM(quantity) as total_salidas 
                    FROM inventory_outputs 
                    WHERE status = "completado" 
                    GROUP BY inventory_id) as io'), 'i.id', '=', 'io.inventory_id')
                ->leftJoin(DB::raw('(SELECT inventory_id, SUM(quantity) as total_devoluciones 
                    FROM inventory_outputs 
                    WHERE status = "devolucion" 
                    GROUP BY inventory_id) as dev'), 'i.id', '=', 'dev.inventory_id')
                ->whereIn('i.customer', $selectedCustomers)
                ->where('i.status', '!=', 'RETENCION')
                ->select('i.customer', DB::raw('SUM(COALESCE(i.quantity, 0) - COALESCE(io.total_salidas, 0) + COALESCE(dev.total_devoluciones, 0)) as stock'))
                ->groupBy('i.customer');
            
            if (!$isSuperAdmin && !empty($cityPermissions)) {
                $customerStockQuery->where(function($query) use ($cityPermissions) {
                    foreach ($cityPermissions as $permission) {
                        $query->orWhere('i.warehouse', 'like', "%{$permission}%");
                    }
                });
            }
            
            $totalStockByCustomer = $customerStockQuery->pluck('stock', 'i.customer')->toArray();
        }

        // PRODUCTOS CON STOCK BAJO (< 1000) (optimizado)
        $lowStockQuery = DB::table('inventories as i')
            ->leftJoin(DB::raw('(SELECT inventory_id, SUM(quantity) as total_salidas 
                FROM inventory_outputs 
                WHERE status = "completado" 
                GROUP BY inventory_id) as io'), 'i.id', '=', 'io.inventory_id')
            ->leftJoin(DB::raw('(SELECT inventory_id, SUM(quantity) as total_devoluciones 
                FROM inventory_outputs 
                WHERE status = "devolucion" 
                GROUP BY inventory_id) as dev'), 'i.id', '=', 'dev.inventory_id')
            ->select(
                'i.id as inventory_id',
                'i.item_description',
                'i.warehouse',
                'i.customer',
                DB::raw('(COALESCE(i.quantity, 0) - COALESCE(io.total_salidas, 0) + COALESCE(dev.total_devoluciones, 0)) as stock')
            )
            ->where('i.status', '!=', 'RETENCION')
            ->havingRaw('stock >= 0 AND stock < 1000');
        
        if (!empty($selectedCustomers)) {
            $lowStockQuery->whereIn('i.customer', $selectedCustomers);
        }
        if (!$isSuperAdmin && !empty($cityPermissions)) {
            $lowStockQuery->where(function($query) use ($cityPermissions) {
                foreach ($cityPermissions as $permission) {
                    $query->orWhere('i.warehouse', 'like', "%{$permission}%");
                }
            });
        }
        
        $lowStockProductsFinal = $lowStockQuery
            ->get()
            ->unique(function($item) {
                return $item->inventory_id . '-' . $item->warehouse;
            })
            ->map(function($item) {
                return (object)[
                    'inventory_id' => $item->inventory_id ?? 'N/A',
                    'item_description' => $item->item_description ?? 'Sin descripción',
                    'warehouse' => $item->warehouse ?? 'N/A',
                    'customer' => $item->customer ?? 'N/A',
                    'stock' => (int)$item->stock
                ];
            })
            ->values();

        // CÁLCULOS TOTALES
        $totalNormal = array_sum($totalEntriesByWarehouse);
        $totalReleased = array_sum($releasedByWarehouse);
        $totalInputs = $totalNormal + $totalReturns + $totalReleased;

        $totalStock = array_sum($totalStockByWarehouse);

        // PRODUCTOS INACTIVOS (sin movimientos en 30 días) (optimizado)
        $inactiveQuery = clone $inventoryQuery;
        $inactiveProducts = $inactiveQuery
            ->where('i.entry_date', '<', $last30Days)
            ->distinct('i.item_description')
            ->count('i.item_description');

        // PROMEDIO DE ENTRADAS POR PRODUCTO (optimizado - cálculo en memoria)
        $avgEntriesQuery = DB::table('inventories as i')
            ->whereIn('i.status', ['INGRESO', 'SALIDA'])
            ->select('i.item_description', DB::raw('SUM(i.quantity) as entry_sum'))
            ->groupBy('i.item_description');
        
        if (!empty($selectedCustomers)) {
            $avgEntriesQuery->whereIn('i.customer', $selectedCustomers);
        }
        if (!$isSuperAdmin && !empty($cityPermissions)) {
            $avgEntriesQuery->where(function($q) use ($cityPermissions) {
                foreach ($cityPermissions as $permission) {
                    $q->orWhere('i.warehouse', 'like', "%{$permission}%");
                }
            });
        }
        
        $entriesByProduct = $avgEntriesQuery->get();
        $avgEntriesPerProduct = $entriesByProduct->isNotEmpty() 
            ? $entriesByProduct->avg('entry_sum') 
            : 0;

        // PRODUCTO CON MÁS SALIDAS (optimizado)
        $mostSoldQuery = DB::table('inventory_outputs as io')
            ->join('inventories as i', 'io.inventory_id', '=', 'i.id')
            ->where('io.status', 'completado')
            ->select('i.item_description', DB::raw('SUM(io.quantity) as total_outputs'))
            ->groupBy('i.item_description')
            ->orderByDesc('total_outputs')
            ->limit(1);
        
        if (!empty($selectedCustomers)) {
            $mostSoldQuery->whereIn('io.customer', $selectedCustomers);
        }
        if (!$isSuperAdmin && !empty($cityPermissions)) {
            $mostSoldQuery->where(function($query) use ($cityPermissions) {
                foreach ($cityPermissions as $permission) {
                    $query->orWhere('io.warehouse', 'like', "%{$permission}%");
                }
            });
        }
        
        $mostSoldProduct = $mostSoldQuery->first();

        // BODEGA MÁS ACTIVA (optimizado - combinando todas las operaciones)
        $warehouseActivity = [];
        
        // Sumar entradas
        foreach ($totalEntriesByWarehouse as $warehouse => $entries) {
            $warehouseActivity[$warehouse] = ($warehouseActivity[$warehouse] ?? 0) + $entries;
        }
        
        // Sumar devoluciones
        foreach ($returnsByWarehouse as $warehouse => $returns) {
            $warehouseActivity[$warehouse] = ($warehouseActivity[$warehouse] ?? 0) + $returns;
        }
        
        // Sumar salidas
        foreach ($outputsData as $warehouse => $outputs) {
            $warehouseActivity[$warehouse] = ($warehouseActivity[$warehouse] ?? 0) + $outputs;
        }
        
        arsort($warehouseActivity);
        $mostActiveWarehouse = !empty($warehouseActivity) ? array_key_first($warehouseActivity) : 'N/A';

        Log::info('=== DASHBOARD METRICS DEBUG ===');
        Log::info('Total Devoluciones: ' . $totalReturns);
        Log::info('Devoluciones por bodega:', $returnsByWarehouse);
        Log::info('Productos con stock bajo: ' . $lowStockProductsFinal->count());

        return [
            'total_entries_by_warehouse' => $totalEntriesByWarehouse,
            'total_outputs_by_warehouse' => $outputsData,
            'total_stock_by_warehouse' => $totalStockByWarehouse,
            'total_stock_by_customer' => $totalStockByCustomer,
            'low_stock_products' => $lowStockProductsFinal,
            'returns_by_warehouse' => $returnsByWarehouse,
            'returns_by_reason_and_warehouse' => $returnsByReasonAndWarehouse,
            'total_returns' => $totalReturns,
            'retentions_by_warehouse' => $retentionsByWarehouse,
            'retentions_by_substatus' => $retentionsBySubstatus,
            'retentions_by_substatus_and_warehouse' => $retentionsBySubstatusAndWarehouse,
            'stock_efficiency' => $totalInputs > 0 ? ($totalStock / $totalInputs) * 100 : 0,
            'total_movements' => $totalInputs + $allOutputs,
            'inactive_products' => $inactiveProducts,
            'avg_entries_per_product' => round($avgEntriesPerProduct, 2),
            'most_sold_product' => $mostSoldProduct && isset($mostSoldProduct->item_description) ? $mostSoldProduct->item_description : 'N/A',
            'most_active_warehouse' => $mostActiveWarehouse ?? 'N/A',
        ];
    }

    private function getTopProducts($cityPermissions, $selectedCustomers, $isSuperAdmin = false)
    {
        // OPTIMIZADO - Consulta directa a tablas
        $entriesQuery = DB::table('inventories as i')
            ->whereIn('i.status', ['INGRESO', 'SALIDA'])
            ->select('i.item_description', DB::raw('SUM(i.quantity) as entries'));
        
        $returnsQuery = DB::table('inventory_outputs as io')
            ->where('io.status', 'devolucion')
            ->join('inventories as i', 'io.inventory_id', '=', 'i.id')
            ->select('i.item_description', DB::raw('SUM(io.quantity) as returns'));
        
        if (!empty($selectedCustomers)) {
            $entriesQuery->whereIn('i.customer', $selectedCustomers);
            $returnsQuery->whereIn('io.customer', $selectedCustomers);
        }
        
        if (!$isSuperAdmin && !empty($cityPermissions)) {
            $entriesQuery->where(function($query) use ($cityPermissions) {
                foreach ($cityPermissions as $permission) {
                    $query->orWhere('i.warehouse', 'like', "%{$permission}%");
                }
            });
            $returnsQuery->where(function($query) use ($cityPermissions) {
                foreach ($cityPermissions as $permission) {
                    $query->orWhere('io.warehouse', 'like', "%{$permission}%");
                }
            });
        }
        
        $entries = $entriesQuery->groupBy('i.item_description')->get()->keyBy('item_description');
        $returns = $returnsQuery->groupBy('i.item_description')->get()->keyBy('item_description');
        
        // Combinar y ordenar
        $combined = collect();
        foreach ($entries as $item) {
            $combined->push([
                'name' => $item->item_description,
                'total_movements' => (int)$item->entries + (int)($returns[$item->item_description]->returns ?? 0)
            ]);
        }
        
        foreach ($returns as $item) {
            if (!isset($entries[$item->item_description])) {
                $combined->push([
                    'name' => $item->item_description,
                    'total_movements' => (int)$item->returns
                ]);
            }
        }
        
        return $combined->sortByDesc('total_movements')->take(5)->map(function($item) {
            return (object)$item;
        });
    }

    private function getMonthlyData($cityPermissions, $selectedCustomers, $isSuperAdmin = false)
    {
        // OPTIMIZADO - Consulta directa a tablas
        $months = [];
        $entries = [];
        $outputs = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();
            $months[] = $month->format('M Y');

            // Entradas (optimizado - consulta directa)
            $entriesQuery = DB::table('inventories as i')
                ->whereIn('i.status', ['INGRESO', 'SALIDA'])
                ->whereBetween('i.entry_date', [$monthStart, $monthEnd]);

            if (!empty($selectedCustomers)) {
                $entriesQuery->whereIn('i.customer', $selectedCustomers);
            }

            if (!$isSuperAdmin && !empty($cityPermissions)) {
                $entriesQuery->where(function($query) use ($cityPermissions) {
                    foreach ($cityPermissions as $permission) {
                        $query->orWhere('i.warehouse', 'like', "%{$permission}%");
                    }
                });
            }

            $monthEntries = $entriesQuery->sum('i.quantity') ?? 0;

            // Salidas (ya estaba optimizado)
            $outputsQuery = InventoryOutput::query();

            if (!empty($selectedCustomers)) {
                $outputsQuery->whereIn('customer', $selectedCustomers);
            }

            if (!$isSuperAdmin && !empty($cityPermissions)) {
                $outputsQuery->where(function($query) use ($cityPermissions) {
                    foreach ($cityPermissions as $permission) {
                        $query->orWhere('warehouse', 'like', "%{$permission}%");
                    }
                });
            }

            $monthOutputs = $outputsQuery
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('quantity') ?? 0;

            $entries[] = (int)$monthEntries;
            $outputs[] = (int)$monthOutputs;
        }

        return [
            'months' => $months,
            'entries' => $entries,
            'outputs' => $outputs
        ];
    }

    private function getProductDistribution($last30Days, $cityPermissions, $selectedCustomers, $isSuperAdmin = false)
    {
        // OPTIMIZADO - Cálculo directo de stock por producto
        $stockQuery = DB::table('inventories as i')
            ->leftJoin(DB::raw('(SELECT inventory_id, SUM(quantity) as total_salidas 
                FROM inventory_outputs 
                WHERE status = "completado" 
                GROUP BY inventory_id) as io'), 'i.id', '=', 'io.inventory_id')
            ->leftJoin(DB::raw('(SELECT inventory_id, SUM(quantity) as total_devoluciones 
                FROM inventory_outputs 
                WHERE status = "devolucion" 
                GROUP BY inventory_id) as dev'), 'i.id', '=', 'dev.inventory_id')
            ->where('i.status', '!=', 'RETENCION')
            ->select(
                'i.item_description as name',
                DB::raw('SUM(COALESCE(i.quantity, 0) - COALESCE(io.total_salidas, 0) + COALESCE(dev.total_devoluciones, 0)) as total_quantity'),
                DB::raw('COUNT(DISTINCT i.warehouse) as warehouses_count')
            )
            ->groupBy('i.item_description');

        if (!empty($selectedCustomers)) {
            $stockQuery->whereIn('i.customer', $selectedCustomers);
        }

        if (!$isSuperAdmin && !empty($cityPermissions)) {
            $stockQuery->where(function($query) use ($cityPermissions) {
                foreach ($cityPermissions as $permission) {
                    $query->orWhere('i.warehouse', 'like', "%{$permission}%");
                }
            });
        }

        $productDistribution = $stockQuery
            ->orderByDesc('total_quantity')
            ->get()
            ->map(function($item) {
                return [
                    'name' => $item->name,
                    'total_quantity' => (int)$item->total_quantity,
                    'warehouses_count' => $item->warehouses_count
                ];
            });

        return $productDistribution->isNotEmpty() ? $productDistribution->toArray() : [];
    }
}
