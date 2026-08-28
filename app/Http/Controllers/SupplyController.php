<?php

namespace App\Http\Controllers;

use App\Exports\SupplyClientConsumptionExport;
use App\Models\SupplyClient;
use App\Models\SupplyIssueRequest;
use App\Models\SupplyIssueRequestItem;
use App\Models\SupplyProduct;
use App\Models\SupplyPurchaseRecipient;
use App\Models\SupplyRequest;
use App\Services\Supply\SupplyPurchaseNotificationService;
use App\Services\Supply\ReqCaseSyncService;
use App\Services\Supply\SupplyService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class SupplyController extends Controller
{
    public function __construct(
        private readonly SupplyService $supplyService,
        private readonly SupplyPurchaseNotificationService $purchaseNotificationService,
        private readonly ReqCaseSyncService $reqCaseSyncService
    ) {}

    public function index(Request $request)
    {
        $activeTab = $request->string('tab', 'requests')->toString();

        $requests = SupplyRequest::query()
            ->with(['requestedBy:id,name', 'auditedBy:id,name', 'client:id,name'])
            ->withCount('items')
            ->orderByDesc('id')
            ->get();

        $products = SupplyProduct::query()
            ->orderBy('catalog_number')
            ->get();

        $clients = SupplyClient::query()
            ->orderBy('name')
            ->get();

        $purchaseRecipients = Schema::hasTable('supply_purchase_recipients')
            ? SupplyPurchaseRecipient::query()
                ->orderByDesc('is_active')
                ->orderBy('email')
                ->get()
            : new Collection();

        $catalogProductOptions = SupplyProduct::active()
            ->orderBy('catalog_number')
            ->get(['id', 'catalog_number', 'name'])
            ->map(fn ($product) => [
                'id' => $product->id,
                'label' => $product->catalog_number . ' - ' . $product->name,
                'search' => mb_strtolower($product->catalog_number . ' ' . $product->name),
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

        $stats = $this->getRequestStats();
        $analyticsFilters = $this->resolveAnalyticsFilters($request);
        $analytics = $this->buildClientAnalytics($analyticsFilters);

        return view('supplies.index', compact(
            'activeTab',
            'requests',
            'products',
            'clients',
            'purchaseRecipients',
            'catalogClientOptions',
            'catalogProductOptions',
            'stats',
            'analyticsFilters',
            'analytics'
        ));
    }

    public function show(SupplyRequest $supplyRequest)
    {
        $this->authorize('viewRequest', $supplyRequest);

        $supplyRequest->load([
            'items.product',
            'requestedBy:id,name',
            'auditedBy:id,name',
            'client:id,name',
            'reqCaseSync',
        ]);

        return view('supplies.show', [
            'supplyRequest' => $supplyRequest,
        ]);
    }

    public function storeProduct(Request $request)
    {
        $this->authorize('manageProducts', SupplyProduct::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $nextCatalogNumber = ((int) SupplyProduct::max('catalog_number')) + 1;

        $payload = [
            'catalog_number' => $nextCatalogNumber,
            'name' => trim($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ];

        SupplyProduct::create($payload);

        return redirect()
            ->route('supplies.index', ['tab' => 'products'])
            ->with('success', 'Producto de proveeduria creado correctamente con ID ' . $nextCatalogNumber . '.');
    }

    public function updateProduct(Request $request, SupplyProduct $product)
    {
        $this->authorize('manageProducts', SupplyProduct::class);

        $validated = $request->validate([
            'catalog_number' => ['required', 'integer', 'min:1', 'unique:supply_products,catalog_number,' . $product->id],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'catalog_number' => $validated['catalog_number'],
            'name' => trim($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];

        $product->update($payload);

        return redirect()
            ->route('supplies.index', ['tab' => 'products'])
            ->with('success', 'Producto de proveeduria actualizado correctamente.');
    }

    public function destroyProduct(SupplyProduct $product)
    {
        $this->authorize('manageProducts', SupplyProduct::class);

        if ($product->requestItems()->exists()) {
            return redirect()
                ->route('supplies.index', ['tab' => 'products'])
                ->with('error', 'No se puede eliminar el producto porque ya tiene solicitudes asociadas.');
        }

        $product->delete();

        return redirect()
            ->route('supplies.index', ['tab' => 'products'])
            ->with('success', 'Producto de proveeduria eliminado correctamente.');
    }

    public function updateStockThresholds(Request $request)
    {
        $this->authorize('manageProducts', SupplyProduct::class);

        $validated = $request->validate([
            'apply_to' => ['required', 'in:all,selected'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', 'exists:supply_products,id'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'medium_stock' => ['required', 'integer', 'gte:minimum_stock'],
        ]);

        $query = SupplyProduct::query();

        if ($validated['apply_to'] === 'selected') {
            $productIds = array_values(array_unique($validated['product_ids'] ?? []));

            if ($productIds === []) {
                return redirect()
                    ->route('supplies.index', ['tab' => 'products'])
                    ->with('error', 'Seleccione al menos un producto para parametrizar existencias.');
            }

            $query->whereIn('id', $productIds);
        }

        $updated = $query->update([
            'minimum_stock' => $validated['minimum_stock'],
            'medium_stock' => $validated['medium_stock'],
        ]);

        return redirect()
            ->route('supplies.index', ['tab' => 'products'])
            ->with('success', "Parametros de existencias actualizados para {$updated} producto(s).");
    }

    public function storeRequest(Request $request)
    {
        $this->authorize('createRequest', SupplyRequest::class);

        $request->validate([
            'supply_client_id' => ['nullable', 'exists:supply_clients,id'],
            'product_id' => ['required', 'array', 'min:1'],
            'product_id.*' => ['nullable', 'exists:supply_products,id'],
            'requested_quantity' => ['required', 'array', 'min:1'],
            'requested_quantity.*' => ['nullable', 'integer', 'min:1'],
            'request_notes' => ['nullable', 'string'],
        ]);

        $supplyRequest = $this->supplyService->createRequest($request);
        $sentRecipients = $this->purchaseNotificationService->sendRequestCreatedNotification($supplyRequest);
        $sync = $this->reqCaseSyncService->syncCreatedPurchaseRequest($supplyRequest);

        $message = 'Solicitud de compra registrada correctamente.';

        if ($sentRecipients > 0) {
            $message .= ' Correo enviado a ' . $sentRecipients . ' destinatario(s) de compras.';
        } else {
            $message .= ' No hay destinatarios activos de compras configurados o el correo no pudo enviarse.';
        }

        if ($sync->status === \App\Models\SupplyReqCaseSync::STATUS_SYNCED) {
            $message .= ' Caso creado en req con ID ' . $sync->external_case_id . '.';
        } elseif ($sync->status === \App\Models\SupplyReqCaseSync::STATUS_NOT_CONFIGURED) {
            $message .= ' El envio a req quedo pendiente: la integracion no esta configurada.';
        } else {
            $message .= ' req no pudo crear el caso: ' . Str::limit((string) $sync->last_error, 180) . ' Puede reintentarse desde el detalle.';
        }

        return redirect()
            ->route('supplies.index', ['tab' => 'requests'])
            ->with('success', $message);
    }

    public function syncReqCase(SupplyRequest $supplyRequest)
    {
        $this->authorize('createRequest', SupplyRequest::class);

        $sync = $this->reqCaseSyncService->syncCreatedPurchaseRequest($supplyRequest);

        return redirect()
            ->route('supplies.show', $supplyRequest)
            ->with(
                $sync->status === \App\Models\SupplyReqCaseSync::STATUS_SYNCED ? 'success' : 'error',
                $sync->status === \App\Models\SupplyReqCaseSync::STATUS_SYNCED
                    ? 'Caso sincronizado con req. ID externo: ' . $sync->external_case_id . '.'
                    : 'No fue posible sincronizar con req. Revise la configuracion e intente nuevamente.'
            );
    }

    public function auditRequest(Request $request, SupplyRequest $supplyRequest)
    {
        $this->authorize('auditRequest', $supplyRequest);

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'received_by_name' => ['required', 'string', 'max:255'],
            'delivered_by_name' => ['required', 'string', 'max:255'],
            'audit_notes' => ['nullable', 'string'],
            'received_quantity' => ['required', 'array'],
            'observation' => ['nullable', 'array'],
        ]);

        $validator->after(function ($validator) use ($request, $supplyRequest) {
            foreach ($supplyRequest->items as $item) {
                $received = (int) ($request->input("received_quantity.{$item->id}") ?? 0);

                if ($received < 0) {
                    $validator->errors()->add("received_quantity.{$item->id}", 'La cantidad recibida no puede ser negativa.');
                }

                if ($received > $item->missing_quantity) {
                    $validator->errors()->add("received_quantity.{$item->id}", 'La cantidad recibida ahora no puede ser mayor al faltante pendiente (' . $item->missing_quantity . ').');
                }
            }
        });

        $validator->validate();

        $this->supplyService->auditRequest($request, $supplyRequest);

        return redirect()
            ->route('supplies.show', $supplyRequest)
            ->with('success', 'Auditoria de recibido guardada correctamente.');
    }

    public function pdf(SupplyRequest $supplyRequest)
    {
        $this->authorize('viewRequest', $supplyRequest);

        $supplyRequest->load([
            'items.product',
            'requestedBy:id,name',
            'auditedBy:id,name',
            'client:id,name',
        ]);

        if (!$supplyRequest->audited_at) {
            return redirect()
                ->route('supplies.show', $supplyRequest)
                ->with('error', 'Debe completar la auditoria antes de generar el PDF.');
        }

        $pdf = Pdf::loadView('supplies.pdf', [
            'supplyRequest' => $supplyRequest,
            'catalogProducts' => SupplyProduct::orderBy('catalog_number')->get(['id', 'catalog_number', 'name']),
        ])->setPaper('letter');

        return $pdf->download('proveeduria_' . $supplyRequest->request_number . '.pdf');
    }

    public function analyticsExport(Request $request)
    {
        $filters = $this->resolveAnalyticsFilters($request);
        $clientSlug = $filters['selected_client']?->name
            ? str($filters['selected_client']->name)->slug('_')
            : 'todos_los_clientes';

        return Excel::download(
            new SupplyClientConsumptionExport($filters),
            'analitica_proveeduria_' . $clientSlug . '_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    private function getRequestStats(): array
    {
        return [
            'products' => SupplyProduct::count(),
            'clients' => SupplyClient::count(),
            'stock_on_hand' => (int) SupplyProduct::sum('stock_on_hand'),
            'reserved_stock' => (int) SupplyProduct::sum('reserved_stock'),
            'requested' => SupplyRequest::where('status', SupplyRequest::STATUS_REQUESTED)->count(),
            'partial' => SupplyRequest::where('status', SupplyRequest::STATUS_PARTIAL)->count(),
            'complete' => SupplyRequest::where('status', SupplyRequest::STATUS_COMPLETE)->count(),
        ];
    }

    private function resolveAnalyticsFilters(Request $request): array
    {
        $defaultFrom = now()->subMonths(5)->startOfMonth();
        $defaultTo = now()->endOfDay();

        $from = $request->filled('analytics_from')
            ? Carbon::parse((string) $request->input('analytics_from'))->startOfDay()
            : $defaultFrom;

        $to = $request->filled('analytics_to')
            ? Carbon::parse((string) $request->input('analytics_to'))->endOfDay()
            : $defaultTo;

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $clientId = $request->filled('analytics_client_id')
            ? (int) $request->input('analytics_client_id')
            : null;

        $selectedClient = $clientId
            ? SupplyClient::query()->find($clientId)
            : null;

        return [
            'client_id' => $selectedClient?->id,
            'selected_client' => $selectedClient,
            'from' => $from,
            'to' => $to,
            'from_input' => $from->format('Y-m-d'),
            'to_input' => $to->format('Y-m-d'),
            'range_label' => $from->format('d/m/Y') . ' - ' . $to->format('d/m/Y'),
        ];
    }

    private function buildClientAnalytics(array $filters): array
    {
        $filteredRequests = $this->baseIssueAnalyticsQuery($filters, 'requested_at')
            ->with([
                'client:id,name',
                'requestedBy:id,name',
                'items.product:id,catalog_number,name',
            ])
            ->get();

        $closedRequests = $this->baseIssueAnalyticsQuery($filters, 'closed_at')
            ->where('status', SupplyIssueRequest::STATUS_CLOSED)
            ->with([
                'client:id,name',
                'requestedBy:id,name',
                'items.product:id,catalog_number,name',
            ])
            ->get();

        $activeReservedItems = SupplyIssueRequestItem::query()
            ->with(['product:id,catalog_number,name'])
            ->whereHas('issueRequest', function ($query) use ($filters) {
                $query->whereIn('status', [
                    SupplyIssueRequest::STATUS_PREPARING,
                    SupplyIssueRequest::STATUS_READY,
                ]);

                if (!empty($filters['client_id'])) {
                    $query->where('supply_client_id', $filters['client_id']);
                }
            })
            ->get();

        $closedItems = $closedRequests->flatMap(function (SupplyIssueRequest $issueRequest) {
            return $issueRequest->items->map(function ($item) use ($issueRequest) {
                $deliveredQuantity = (int) $item->delivered_quantity;

                return [
                    'request_id' => $issueRequest->id,
                    'request_number' => $issueRequest->request_number,
                    'closed_at' => $issueRequest->closed_at,
                    'requested_at' => $issueRequest->requested_at,
                    'client_id' => $issueRequest->supply_client_id,
                    'client_name' => $issueRequest->client?->name ?? 'Sin cliente',
                    'requested_by' => $issueRequest->requestedBy?->name ?? 'Sin usuario',
                    'product_id' => $item->supply_product_id,
                    'catalog_number' => $item->product?->catalog_number,
                    'product_name' => $item->product?->name ?? 'Sin producto',
                    'requested_quantity' => (int) $item->requested_quantity,
                    'reserved_quantity' => (int) $item->reserved_quantity,
                    'delivered_quantity' => $deliveredQuantity,
                ];
            });
        })->values();

        $requestStatus = [
            'preparing' => $filteredRequests->where('status', SupplyIssueRequest::STATUS_PREPARING)->count(),
            'ready' => $filteredRequests->where('status', SupplyIssueRequest::STATUS_READY)->count(),
            'pending_support' => $filteredRequests->where('status', SupplyIssueRequest::STATUS_PENDING_SUPPORT)->count(),
            'rejected' => $filteredRequests->where('status', SupplyIssueRequest::STATUS_REJECTED)->count(),
            'closed' => $filteredRequests->where('status', SupplyIssueRequest::STATUS_CLOSED)->count(),
        ];

        $totalDeliveredUnits = (int) $closedItems->sum('delivered_quantity');
        $totalRequestedUnits = (int) $closedItems->sum('requested_quantity');
        $totalReservedUnits = (int) $activeReservedItems->sum('reserved_quantity');

        $topProducts = $closedItems
            ->groupBy('product_id')
            ->map(function (Collection $rows) {
                $first = $rows->first();

                return [
                    'product_id' => $first['product_id'],
                    'catalog_number' => $first['catalog_number'],
                    'product_name' => $first['product_name'],
                    'units' => (int) $rows->sum('delivered_quantity'),
                ];
            })
            ->sortByDesc('units')
            ->values();

        $granularity = $filters['from']->diffInDays($filters['to']) > 45 ? 'month' : 'day';
        $trend = $closedItems
            ->groupBy(function (array $row) use ($granularity) {
                /** @var Carbon|null $closedAt */
                $closedAt = $row['closed_at'];

                if (!$closedAt) {
                    return 'Sin fecha';
                }

                return $granularity === 'month'
                    ? $closedAt->format('Y-m')
                    : $closedAt->format('Y-m-d');
            })
            ->map(function (Collection $rows, string $period) use ($granularity) {
                $label = $period === 'Sin fecha'
                    ? $period
                    : Carbon::parse($period)->format($granularity === 'month' ? 'M Y' : 'd/m');

                return [
                    'period' => $period,
                    'label' => $label,
                    'units' => (int) $rows->sum('delivered_quantity'),
                    'requests' => $rows->pluck('request_id')->unique()->count(),
                ];
            })
            ->sortBy('period')
            ->values();

        $clientLeaderboard = SupplyIssueRequestItem::query()
            ->with(['issueRequest.client:id,name', 'product:id'])
            ->whereHas('issueRequest', function ($query) use ($filters) {
                $query->where('status', SupplyIssueRequest::STATUS_CLOSED)
                    ->whereBetween('closed_at', [$filters['from'], $filters['to']]);
            })
            ->get()
            ->groupBy(fn ($item) => $item->issueRequest?->supply_client_id ?: 0)
            ->map(function (Collection $items, $clientId) {
                $first = $items->first();
                $clientName = $first?->issueRequest?->client?->name ?? 'Sin cliente';

                return [
                    'client_id' => (int) $clientId,
                    'client_name' => $clientName,
                    'units' => (int) $items->sum('delivered_quantity'),
                ];
            })
            ->sortByDesc('units')
            ->take(8)
            ->values();

        $selectedClientName = $filters['selected_client']?->name ?? 'Todos los clientes';
        $closedRequestCount = max($closedRequests->count(), 1);
        $topProduct = $topProducts->first();
        $fillRate = $totalRequestedUnits > 0
            ? round(($totalDeliveredUnits / $totalRequestedUnits) * 100, 1)
            : 0;

        return [
            'selected_client_name' => $selectedClientName,
            'summary' => [
                'total_requests' => $filteredRequests->count(),
                'closed_requests' => $closedRequests->count(),
                'delivered_units' => $totalDeliveredUnits,
                'reserved_units' => $totalReservedUnits,
                'avg_units_per_request' => round($totalDeliveredUnits / $closedRequestCount, 1),
                'unique_products' => $closedItems->pluck('product_id')->filter()->unique()->count(),
                'fill_rate' => $fillRate,
                'top_product_name' => $topProduct['product_name'] ?? 'Sin consumo',
                'top_product_units' => $topProduct['units'] ?? 0,
            ],
            'status_mix' => $requestStatus,
            'top_products' => $topProducts->take(10)->values(),
            'trend' => $trend,
            'leaderboard' => $clientLeaderboard,
            'insights' => [
                'highest_pressure_products' => $this->buildPressureProducts($topProducts, $activeReservedItems),
                'requesters' => $closedItems
                    ->groupBy('requested_by')
                    ->map(fn (Collection $rows, string $name) => [
                        'name' => $name,
                        'units' => (int) $rows->sum('delivered_quantity'),
                    ])
                    ->sortByDesc('units')
                    ->take(5)
                    ->values(),
            ],
            'chart_data' => [
                'top_products' => [
                    'labels' => $topProducts->take(8)->map(fn ($row) => $row['catalog_number'] . ' - ' . $row['product_name'])->values(),
                    'units' => $topProducts->take(8)->pluck('units')->values(),
                ],
                'trend' => [
                    'labels' => $trend->pluck('label')->values(),
                    'units' => $trend->pluck('units')->values(),
                ],
                'status_mix' => [
                    'labels' => ['En alistamiento', 'Listas', 'Pendientes de soporte', 'Rechazadas', 'Cerradas'],
                    'values' => [
                        $requestStatus['preparing'],
                        $requestStatus['ready'],
                        $requestStatus['pending_support'],
                        $requestStatus['rejected'],
                        $requestStatus['closed'],
                    ],
                ],
                'leaderboard' => [
                    'labels' => $clientLeaderboard->pluck('client_name')->values(),
                    'units' => $clientLeaderboard->pluck('units')->values(),
                ],
            ],
        ];
    }

    private function baseIssueAnalyticsQuery(array $filters, string $dateColumn)
    {
        return SupplyIssueRequest::query()
            ->when(!empty($filters['client_id']), fn ($query) => $query->where('supply_client_id', $filters['client_id']))
            ->whereBetween($dateColumn, [$filters['from'], $filters['to']]);
    }

    private function buildPressureProducts(Collection $topProducts, Collection $activeReservedItems): Collection
    {
        $reservedByProduct = $activeReservedItems
            ->groupBy('supply_product_id')
            ->map(fn (Collection $items) => (int) $items->sum('reserved_quantity'));

        return $topProducts
            ->map(function (array $row) use ($reservedByProduct) {
                $reserved = (int) ($reservedByProduct[$row['product_id']] ?? 0);

                return [
                    'catalog_number' => $row['catalog_number'],
                    'product_name' => $row['product_name'],
                    'delivered_units' => $row['units'],
                    'reserved_units' => $reserved,
                    'pressure_units' => $row['units'] + $reserved,
                ];
            })
            ->sortByDesc('pressure_units')
            ->take(6)
            ->values();
    }
}
