<?php

namespace App\Http\Controllers;

use App\Models\SupplyClient;
use App\Models\SupplyProduct;
use App\Models\SupplyPurchaseRecipient;
use App\Models\SupplyRequest;
use App\Services\Supply\SupplyPurchaseNotificationService;
use App\Services\Supply\SupplyService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SupplyController extends Controller
{
    public function __construct(
        private readonly SupplyService $supplyService,
        private readonly SupplyPurchaseNotificationService $purchaseNotificationService
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

        return view('supplies.index', compact(
            'activeTab',
            'requests',
            'products',
            'clients',
            'purchaseRecipients',
            'catalogClientOptions',
            'catalogProductOptions',
            'stats'
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
        ]);

        return view('supplies.show', [
            'supplyRequest' => $supplyRequest,
        ]);
    }

    public function storeProduct(Request $request)
    {
        $this->authorize('manageProducts');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $nextCatalogNumber = ((int) SupplyProduct::max('catalog_number')) + 1;

        SupplyProduct::create([
            'catalog_number' => $nextCatalogNumber,
            'name' => trim($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return redirect()
            ->route('supplies.index', ['tab' => 'products'])
            ->with('success', 'Producto de proveeduría creado correctamente con ID ' . $nextCatalogNumber . '.');
    }

    public function updateProduct(Request $request, SupplyProduct $product)
    {
        $this->authorize('manageProducts');

        $validated = $request->validate([
            'catalog_number' => ['required', 'integer', 'min:1', 'unique:supply_products,catalog_number,' . $product->id],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $product->update([
            'catalog_number' => $validated['catalog_number'],
            'name' => trim($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return redirect()
            ->route('supplies.index', ['tab' => 'products'])
            ->with('success', 'Producto de proveeduría actualizado correctamente.');
    }

    public function destroyProduct(SupplyProduct $product)
    {
        $this->authorize('manageProducts');

        if ($product->requestItems()->exists()) {
            return redirect()
                ->route('supplies.index', ['tab' => 'products'])
                ->with('error', 'No se puede eliminar el producto porque ya tiene solicitudes asociadas.');
        }

        $product->delete();

        return redirect()
            ->route('supplies.index', ['tab' => 'products'])
            ->with('success', 'Producto de proveeduría eliminado correctamente.');
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

        $message = 'Solicitud de compra registrada correctamente.';

        if ($sentRecipients > 0) {
            $message .= ' Correo enviado a ' . $sentRecipients . ' destinatario(s) de compras.';
        } else {
            $message .= ' No hay destinatarios activos de compras configurados o el correo no pudo enviarse.';
        }

        return redirect()
            ->route('supplies.index', ['tab' => 'requests'])
            ->with('success', $message);
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

                if ($received > $item->requested_quantity) {
                    $validator->errors()->add("received_quantity.{$item->id}", 'La cantidad recibida no puede ser mayor a la solicitada.');
                }
            }
        });

        $validator->validate();

        $this->supplyService->auditRequest($request, $supplyRequest);

        return redirect()
            ->route('supplies.show', $supplyRequest)
            ->with('success', 'Auditoría de recibido guardada correctamente.');
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
                ->with('error', 'Debe completar la auditoría antes de generar el PDF.');
        }

        $pdf = Pdf::loadView('supplies.pdf', [
            'supplyRequest' => $supplyRequest,
            'catalogProducts' => SupplyProduct::orderBy('catalog_number')->get(['id', 'catalog_number', 'name']),
        ])->setPaper('letter');

        return $pdf->download('proveeduria_' . $supplyRequest->request_number . '.pdf');
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
}
