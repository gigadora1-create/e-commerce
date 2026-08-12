<?php

namespace App\Http\Controllers;

use App\Models\SupplyClient;
use App\Models\SupplyIssueRequest;
use App\Models\SupplyProduct;
use App\Models\SupplyStockMovement;
use App\Services\Supply\SupplyIssueService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SupplyIssueController extends Controller
{
    public function __construct(
        private readonly SupplyIssueService $issueService
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->can('supplies.admin');

        $requests = SupplyIssueRequest::query()
            ->with(['requestedBy:id,name', 'preparedBy:id,name', 'closedBy:id,name', 'client:id,name'])
            ->withCount('items')
            ->when(!$isAdmin, fn ($query) => $query->where('requested_by_user_id', $user->id))
            ->when($request->filled('search'), function ($query) use ($request, $isAdmin) {
                $search = trim((string) $request->input('search'));

                $query->where(function ($inner) use ($search, $isAdmin) {
                    $inner->where('request_number', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('client', fn ($clientQuery) => $clientQuery->where('name', 'like', "%{$search}%"));

                    if ($isAdmin) {
                        $inner->orWhereHas('requestedBy', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"));
                    }
                });
            })
            ->orderByDesc('id')
            ->paginate(10)
            ->appends($request->query());

        $stockRows = $isAdmin
            ? $this->getStockRows($request)
            : new LengthAwarePaginator(collect(), 0, 12, 1, [
                'path' => $request->url(),
                'pageName' => 'stock_page',
            ]);

        $lowStockProducts = $isAdmin ? $this->getLowStockProducts() : collect();

        $catalogProductOptions = SupplyProduct::active()
            ->orderBy('catalog_number')
            ->get(['id', 'catalog_number', 'name', 'stock_on_hand', 'reserved_stock'])
            ->map(fn ($product) => [
                'id' => $product->id,
                'label' => $product->catalog_number . ' - ' . $product->name,
                'search' => mb_strtolower($product->catalog_number . ' ' . $product->name),
                'available' => $product->available_stock,
            ])
            ->values();

        $catalogClientOptions = SupplyClient::active()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($client) => [
                'id' => $client->id,
                'label' => $client->name,
                'search' => mb_strtolower($client->name),
            ])
            ->values();

        return view('supplies.issues.index', [
            'requests' => $requests,
            'stockRows' => $stockRows,
            'catalogProductOptions' => $catalogProductOptions,
            'catalogClientOptions' => $catalogClientOptions,
            'isAdmin' => $isAdmin,
            'requestCreationAllowed' => $isAdmin || $this->canRequesterCreateIssueToday(),
            'requestCreationRestrictionMessage' => 'Solo se puede enviar proveeduria los dias jueves y viernes.',
            'lowStockProducts' => $lowStockProducts,
            'stats' => $this->getIssueStats($user, $isAdmin),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('createIssueRequest', SupplyIssueRequest::class);

        if (!$request->user()->can('supplies.admin') && !$this->canRequesterCreateIssueToday()) {
            return redirect()
                ->route('supplies.issues.index')
                ->with('error', 'Solo se puede enviar proveeduria los dias jueves y viernes.');
        }

        $request->validate([
            'supply_client_id' => ['required', 'exists:supply_clients,id'],
            'product_id' => ['required', 'array', 'min:1'],
            'product_id.*' => ['nullable', 'exists:supply_products,id'],
            'requested_quantity' => ['required', 'array', 'min:1'],
            'requested_quantity.*' => ['nullable', 'integer', 'min:0'],
            'request_notes' => ['nullable', 'string'],
        ]);

        $this->issueService->createRequest($request);

        return redirect()
            ->route('supplies.issues.index')
            ->with('success', 'Solicitud de salida creada correctamente y enviada a alistamiento.');
    }

    public function show(Request $request, SupplyIssueRequest $issueRequest)
    {
        $this->authorize('viewIssueRequest', $issueRequest);

        $issueRequest->load([
            'items.product',
            'requestedBy:id,name',
            'preparedBy:id,name',
            'closedBy:id,name',
            'client:id,name',
        ]);

        return view('supplies.issues.show', [
            'issueRequest' => $issueRequest,
            'isAdmin' => $request->user()->can('supplies.admin'),
        ]);
    }

    public function markReady(Request $request, SupplyIssueRequest $issueRequest)
    {
        $this->authorize('markReady', $issueRequest);

        $this->issueService->markReady($request, $issueRequest);

        return redirect()
            ->route('supplies.issues.show', $issueRequest)
            ->with('success', 'Solicitud marcada como lista para recoger.');
    }

    public function close(Request $request, SupplyIssueRequest $issueRequest)
    {
        $this->authorize('close', $issueRequest);

        $this->issueService->close($request, $issueRequest);

        return redirect()
            ->route('supplies.issues.show', $issueRequest)
            ->with('success', 'Solicitud cerrada y descontada del stock correctamente.');
    }

    public function reject(Request $request, SupplyIssueRequest $issueRequest)
    {
        $this->authorize('reject', $issueRequest);

        $this->issueService->reject($request, $issueRequest);

        return redirect()
            ->route('supplies.issues.show', $issueRequest)
            ->with('success', 'Solicitud rechazada y reserva liberada correctamente.');
    }

    public function pdf(Request $request, SupplyIssueRequest $issueRequest)
    {
        $this->authorize('viewIssuePdf', $issueRequest);

        $issueRequest->load([
            'items.product',
            'requestedBy:id,name',
            'preparedBy:id,name',
            'closedBy:id,name',
            'client:id,name',
        ]);

        $pdf = Pdf::loadView('supplies.issues.pdf', [
            'issueRequest' => $issueRequest,
            'catalogProducts' => SupplyProduct::orderBy('catalog_number')->get(['id', 'catalog_number', 'name']),
        ])->setPaper('letter');

        return $pdf->download('salida_proveeduria_' . $issueRequest->request_number . '.pdf');
    }

    private function getStockRows(Request $request)
    {
        $stockRows = SupplyProduct::query()
            ->when($request->filled('stock_search'), function ($query) use ($request) {
                $search = trim((string) $request->input('stock_search'));

                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('catalog_number', 'like', "%{$search}%");
                });
            })
            ->withSum(['stockMovements as total_entries' => function ($query) {
                $query->where('movement_type', 'receipt_audit')->where('quantity', '>', 0);
            }], 'quantity')
            ->withSum(['stockMovements as total_exits_raw' => function ($query) {
                $query->where('movement_type', 'close_issue_request')->where('quantity', '<', 0);
            }], 'quantity')
            ->withMax('stockMovements as last_movement_at', 'created_at')
            ->orderBy('catalog_number')
            ->paginate(12, ['*'], 'stock_page')
            ->appends($request->query());

        $stockRows->getCollection()->transform(function (SupplyProduct $product) {
            $product->stock_level = $this->resolveStockLevel($product);

            return $product;
        });

        return $stockRows;
    }

    private function getLowStockProducts(): Collection
    {
        return SupplyProduct::query()
            ->get()
            ->map(fn (SupplyProduct $product) => [
                'product' => $product,
                'level' => $this->resolveStockLevel($product),
            ])
            ->filter(fn ($row) => in_array($row['level']['key'], ['critical', 'warning'], true))
            ->map(fn ($row) => [
                'catalog_number' => $row['product']->catalog_number,
                'name' => $row['product']->name,
                'available_stock' => $row['product']->available_stock,
                'reserved_stock' => $row['product']->reserved_stock,
                'label' => $row['level']['label'],
                'note' => $row['level']['note'],
            ])
            ->values();
    }

    private function getIssueStats($user, bool $isAdmin): array
    {
        if (!$isAdmin) {
            $ownRequests = SupplyIssueRequest::query()
                ->where('requested_by_user_id', $user->id);

            return [
                'total_requests' => (clone $ownRequests)->count(),
                'preparing' => (clone $ownRequests)->where('status', SupplyIssueRequest::STATUS_PREPARING)->count(),
                'ready' => (clone $ownRequests)->where('status', SupplyIssueRequest::STATUS_READY)->count(),
                'closed' => (clone $ownRequests)->where('status', SupplyIssueRequest::STATUS_CLOSED)->count(),
            ];
        }

        $stockLevelCounts = SupplyProduct::query()
            ->get()
            ->map(fn (SupplyProduct $product) => $this->resolveStockLevel($product)['key'])
            ->countBy();

        return [
            'available' => (int) SupplyProduct::sum(DB::raw('GREATEST(stock_on_hand - reserved_stock, 0)')),
            'reserved' => (int) SupplyProduct::sum('reserved_stock'),
            'preparing' => SupplyIssueRequest::where('status', SupplyIssueRequest::STATUS_PREPARING)->count(),
            'ready' => SupplyIssueRequest::where('status', SupplyIssueRequest::STATUS_READY)->count(),
            'total_entries' => (int) SupplyStockMovement::where('movement_type', 'receipt_audit')->where('quantity', '>', 0)->sum('quantity'),
            'total_exits' => abs((int) SupplyStockMovement::where('movement_type', 'close_issue_request')->where('quantity', '<', 0)->sum('quantity')),
            'critical' => (int) ($stockLevelCounts['critical'] ?? 0),
            'warning' => (int) ($stockLevelCounts['warning'] ?? 0),
            'healthy' => (int) ($stockLevelCounts['good'] ?? 0),
        ];
    }

    private function canRequesterCreateIssueToday(): bool
    {
        return in_array(CarbonImmutable::now('America/Bogota')->dayOfWeekIso, [4, 5], true);
    }

    public function resolveStockLevel(SupplyProduct $product): array
    {
        $available = (int) $product->available_stock;
        $reserved = (int) $product->reserved_stock;

        if ($available <= 5) {
            return [
                'key' => 'critical',
                'label' => 'Crítico',
                'note' => 'Reposición urgente',
            ];
        }

        if ($available <= 15 || $reserved >= $available) {
            return [
                'key' => 'warning',
                'label' => 'Bajo',
                'note' => 'Revise consumo y reposición',
            ];
        }

        return [
            'key' => 'good',
            'label' => 'Estable',
            'note' => 'Stock operativo saludable',
        ];
    }
}
