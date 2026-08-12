<?php

namespace App\Http\Controllers;

use App\Models\PickingOrder;
use App\Models\PickingDetail;
use App\Models\PickingReservation;
use App\Models\Inventory;
use App\Models\InventoryOutput;
use App\Imports\PickingRequestImport;
use App\Exports\PickingReportExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use App\Services\InventoryService;
use App\Services\StockService;

class PickingController extends Controller
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
        $pickingOrders = DB::table('picking_orders as po')
            ->leftJoin('picking_details as pd', 'po.id', '=', 'pd.picking_order_id')
            ->leftJoin('users as u', 'po.user_id', '=', 'u.id')
            ->select(
                'po.id',
                'po.picking_code',
                'po.warehouse',
                'po.status',
                'po.total_quantity',
                'po.created_at',
                'u.name as user_name',
                DB::raw('GROUP_CONCAT(DISTINCT pd.sku SEPARATOR ",") as skus'),
                DB::raw('GROUP_CONCAT(DISTINCT pd.item_description SEPARATOR ",") as productos'),
                DB::raw('GROUP_CONCAT(DISTINCT pd.location_code SEPARATOR ",") as ubicaciones')
            )
            ->groupBy(
                'po.id',
                'po.picking_code',
                'po.warehouse',
                'po.status',
                'po.total_quantity',
                'po.created_at',
                'u.name'
            )
            // OPTIMIZADO: Primero "En Progreso", luego "Pendiente", luego por fecha más reciente
            ->orderByRaw("
                CASE
                    WHEN po.status = 'in_progress' THEN 1
                    WHEN po.status = 'pending' THEN 2
                    WHEN po.status = 'completed' THEN 3
                    WHEN po.status = 'cancelled' THEN 4
                    ELSE 5
                END
            ")
            ->orderBy('po.created_at', 'desc')
            ->get(); // DataTables maneja la paginación del lado del cliente

        return view('picking.index', compact('pickingOrders'));
    }


    public function import(Request $request)
    {
        set_time_limit(300);
        ini_set('max_execution_time', 300);

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        try {
            Log::info("=== INICIANDO IMPORTACIÓN OPTIMIZADA (SIN VISTAS) ===");

            DB::beginTransaction();

            $import = new PickingRequestImport(session('selected_customer'));
            Excel::import($import, $request->file('file'));

            $pickingOrder = $import->getPickingOrder();

            DB::commit();

            Log::info("=== IMPORTACIÓN COMPLETADA EXITOSAMENTE ===");
            Log::info("Picking Code: {$pickingOrder->picking_code}");

            return response()->json([
                'success' => true,
                'message' => 'Salida generada exitosamente: ' . $pickingOrder->picking_code,
                'picking_id' => $pickingOrder->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("=== ERROR EN IMPORTACIÓN ===");
            Log::error($e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function show($id)
    {
        $pickingOrder = PickingOrder::with(['details', 'user'])->findOrFail($id);
        $inventoryIds = $pickingOrder->details->pluck('inventory_id')->unique();

        $inventoryDataAll = DB::table('inventories as i')
            ->leftJoin('locations as l', 'i.location_id', '=', 'l.location_id')
            ->select(
                'i.id',
                'i.item_id',
                'i.quantity as quantity_original',
                'i.batch',
                'i.expiry_date',
                'i.localizacion as location_code',
                'i.warehouse',
                'i.customer',
                'l.name as location_name'
            )
            ->whereIn('i.id', $inventoryIds)
            ->get()
            ->map(function($item) {
                $physical = $this->stockService->getPhysical($item->item_id, $item->location_code, $item->warehouse, $item->customer);
                $reserved = $this->stockService->getReserved($item->item_id, $item->location_code, $item->warehouse, $item->customer);
                $available = $this->stockService->getAvailable($item->item_id, $item->location_code, $item->warehouse, $item->customer);

                $item->quantity_current = (int) $physical;
                $item->quantity_reserved = (int) $reserved;
                $item->quantity_net_available = (int) $available;
                $item->total_salidas = max(0, (int) $item->quantity_original - (int) $physical);
                $item->picking_status = $item->quantity_net_available > 0 ? 'DISPONIBLE' : 'AGOTADO';
                return $item;
            })
            ->keyBy('id');

        // Enriquecer cada detalle
        foreach ($pickingOrder->details as $detail) {
            $viewData = $inventoryDataAll->get($detail->inventory_id);

            if ($viewData) {
                $detail->quantity_original = $viewData->quantity_original;
                $detail->quantity_current = $viewData->quantity_current;
                $detail->quantity_reserved = $viewData->quantity_reserved;
                $detail->quantity_net_available = $viewData->quantity_net_available;
                $detail->total_salidas = $viewData->total_salidas;
                $detail->quantity_after_picking = $viewData->quantity_net_available;
                $detail->picking_status = $viewData->picking_status;
                $detail->location_name = $viewData->location_name;
                $detail->location_code = $viewData->location_code;
                $detail->warehouse = $viewData->warehouse;
                $detail->customer = $viewData->customer;
                $detail->batch = $viewData->batch;
                $detail->expiry_date = $viewData->expiry_date;
            } else {
                $detail->quantity_original = 0;
                $detail->quantity_current = 0;
                $detail->quantity_reserved = 0;
                $detail->quantity_net_available = 0;
                $detail->total_salidas = 0;
                $detail->quantity_after_picking = 0;
                $detail->picking_status = 'AGOTADO';
            }
        }

        // PASO 1: Agrupar por inventory_id y consolidar duplicados
        $consolidatedByInventory = $pickingOrder->details->groupBy('inventory_id')->map(function ($group) {
            $first = $group->first();
            $consolidated = clone $first;

            // Sumar cantidades del picking (pueden estar duplicadas)
            $consolidated->quantity_requested = $group->sum('quantity_requested');
            $consolidated->quantity_picked = $group->sum('quantity_picked');

            return $consolidated;
        });

        // PASO 2: Agrupar por SKU + Ubicación + Fecha de Vencimiento
        $finalGrouped = $consolidatedByInventory->groupBy(function ($detail) {
            $locationKey = $detail->location_code ?? 'sin_ubicacion';
            $expiryKey = $detail->expiry_date ?? 'sin_fecha';
            return $detail->sku . '|' . $locationKey . '|' . $expiryKey;
        });

        // PASO 3: Consolidar el agrupamiento final
        $pickingOrder->grouped_details = $finalGrouped->map(function ($group) {
            $first = $group->first();
            $consolidated = clone $first;

            // Sumar cantidades del picking
            $consolidated->quantity_requested = $group->sum('quantity_requested');
            $consolidated->quantity_picked = $group->sum('quantity_picked');

            // Sumar stocks (ahora son diferentes inventory_id)
            $consolidated->quantity_original = $group->sum('quantity_original');
            $consolidated->quantity_current = $first->quantity_current;
            $consolidated->quantity_reserved = $first->quantity_reserved;
            $consolidated->quantity_net_available = $first->quantity_net_available;
            $consolidated->total_salidas = $first->total_salidas;
            $consolidated->quantity_after_picking = $first->quantity_after_picking;

            $consolidated->detail_count = $group->count();

            return $consolidated;
        })->values();

        return view('picking.show', compact('pickingOrder'));
    }

    public function exportReport($id)
    {
        $pickingOrder = PickingOrder::findOrFail($id);

        return Excel::download(
            new PickingReportExport($id),
            'salida_' . $pickingOrder->picking_code . '.xlsx'
        );
    }

    public function pdf($id)
    {
        $pickingOrder = PickingOrder::with('details')->findOrFail($id);

        $pdf = Pdf::loadView('picking.pdf', compact('pickingOrder'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'sans-serif',
                'isRemoteEnabled' => true,
            ]);

        return $pdf->download('alistamiento_' . $pickingOrder->picking_code . '.pdf');
    }

    public function complete($id)
    {
        Log::info("=== INICIANDO COMPLETAR PICKING ID: {$id} (REFACTORED) ===");
        try {
            DB::beginTransaction();

            $pickingOrder = PickingOrder::with(['details', 'reservations'])->findOrFail($id);

            if ($pickingOrder->status === 'completed') {
                return response()->json(['success' => false, 'message' => 'Esta salida ya fue completada'], 400);
            }

            if ($pickingOrder->status === 'cancelled') {
                return response()->json(['success' => false, 'message' => 'No se puede completar una salida cancelada'], 400);
            }

            foreach ($pickingOrder->details as $detail) {
                // Delegate Exit to Service
                $this->inventoryService->registerExit($detail->inventory_id, $detail->quantity_picked, [
                    'guide' => $pickingOrder->picking_code,
                    'type' => 'Picking',
                    'picking_order_id' => $pickingOrder->id,
                    'customer' => $pickingOrder->customer ?? null
                ]);
            }

            // Cleanup reservations
            $pickingOrder->reservations()->delete();

            $pickingOrder->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Salida completada exitosamente.']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al completar picking ID {$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function cancel($id)
    {
        try {
            DB::beginTransaction();

            $pickingOrder = PickingOrder::with(['reservations', 'details'])->findOrFail($id);

            if ($pickingOrder->status === 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede cancelar una salida completada'
                ], 400);
            }

            if ($pickingOrder->status === 'cancelled') {
                return response()->json([  // CORREGIDO: era response()->()->json
                    'success' => false,
                    'message' => 'Esta salida ya está cancelada'
                ], 400);
            }

            // 1. Eliminar reservas
            $pickingOrder->reservations()->delete();

            // 2. Eliminar detalles
            $pickingOrder->details()->delete();

            // 3. Eliminar la orden completa
            $pickingOrder->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Salida cancelada y eliminada completamente.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar la salida: ' . $e->getMessage()
            ], 500);
        }
    }

    public function keepAlive()
    {
        return response()->json(['alive' => true, 'time' => now()->toIso8601String()]);
    }
}
