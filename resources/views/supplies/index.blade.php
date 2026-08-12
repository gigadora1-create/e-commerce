@extends('layouts.app')

@push('styles')
    @include('supplies.partials.theme-styles')
@endpush

@section('contents')
    <div class="container-fluid py-4 supplies-shell">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div class="supplies-page-header">
                <h1 class="h3 mb-1">Modulo de proveeduria</h1>
                <p class="text-muted mb-0">Solicitudes internas, auditoria de recibido, clientes y catalogo editable.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#createSupplyRequestModal">
                    <i class="fas fa-file-signature me-1"></i> Nueva solicitud
                </button>
                <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#managePurchaseRecipientsModal">
                    <i class="fas fa-envelope me-1"></i> Correos compras
                </button>
                <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#createSupplyClientModal">
                    <i class="fas fa-building me-1"></i> Nuevo cliente
                </button>
                <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#createSupplyProductModal">
                    <i class="fas fa-box-open me-1"></i> Nuevo producto
                </button>
            </div>
        </div>

        @include('supplies.partials.alerts')

        <div class="supplies-stats-grid mb-4">
            <div class="supplies-kpi">
                <div class="supplies-kpi-header">
                    <div class="supplies-kpi-icon kpi-red"><i class="fas fa-box-open"></i></div>
                    <div class="supplies-kpi-label">Productos catalogados</div>
                </div>
                <div class="supplies-kpi-value">{{ $stats['products'] }}</div>
                <div class="supplies-kpi-note">Base editable del modulo</div>
            </div>
            <div class="supplies-kpi">
                <div class="supplies-kpi-header">
                    <div class="supplies-kpi-icon kpi-blue"><i class="fas fa-building"></i></div>
                    <div class="supplies-kpi-label">Clientes activos</div>
                </div>
                <div class="supplies-kpi-value">{{ $stats['clients'] }}</div>
                <div class="supplies-kpi-note">Solo para documentos y salidas</div>
            </div>
            <div class="supplies-kpi">
                <div class="supplies-kpi-header">
                    <div class="supplies-kpi-icon kpi-amber"><i class="fas fa-clock"></i></div>
                    <div class="supplies-kpi-label">Solicitudes pendientes</div>
                </div>
                <div class="supplies-kpi-value">{{ $stats['requested'] }}</div>
                <div class="supplies-kpi-note">Compras por auditar</div>
            </div>
            <div class="supplies-kpi">
                <div class="supplies-kpi-header">
                    <div class="supplies-kpi-icon kpi-green"><i class="fas fa-cubes"></i></div>
                    <div class="supplies-kpi-label">Stock fisico</div>
                </div>
                <div class="supplies-kpi-value">{{ $stats['stock_on_hand'] }}</div>
                <div class="supplies-kpi-note">Unidades disponibles en sistema</div>
            </div>
        </div>

        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'requests' ? 'active' : '' }}"
                    href="{{ route('supplies.index', array_merge(request()->query(), ['tab' => 'requests'])) }}">
                    Solicitudes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'products' ? 'active' : '' }}"
                    href="{{ route('supplies.index', array_merge(request()->query(), ['tab' => 'products'])) }}">
                    Catalogo
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'clients' ? 'active' : '' }}"
                    href="{{ route('supplies.index', array_merge(request()->query(), ['tab' => 'clients'])) }}">
                    Clientes
                </a>
            </li>
        </ul>

        @if ($activeTab === 'requests')
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="supplies-toolbar mb-3">
                        <div>
                            <div class="supplies-section-title"><i class="fas fa-file-signature"></i> Solicitudes de compra</div>
                            <p class="text-muted mb-0">Cree la solicitud inicial y luego audite el recibido contra lo comprado.</p>
                        </div>
                        <div class="text-muted small">
                            Destinatarios activos de compras: {{ $purchaseRecipients->where('is_active', true)->count() }}
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle" id="suppliesRequestsTable">
                            <thead>
                                <tr>
                                    <th>Solicitud</th>
                                    <th>Fecha</th>
                                    <th>Solicitado por</th>
                                    <th>Items</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($requests as $supplyRequest)
                                    <tr>
                                        <td class="fw-semibold">{{ $supplyRequest->request_number }}</td>
                                        <td>{{ optional($supplyRequest->requested_at)->format('Y-m-d H:i') }}</td>
                                        <td>{{ $supplyRequest->requestedBy?->name ?? 'Sin usuario' }}</td>
                                        <td>{{ $supplyRequest->items_count }}</td>
                                        <td>
                                            <span class="badge bg-{{ $supplyRequest->status_color }}">
                                                {{ $supplyRequest->status_label }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-2">
                                                <a class="btn btn-sm btn-outline-primary"
                                                    href="{{ route('supplies.show', $supplyRequest) }}">
                                                    Abrir
                                                </a>
                                                @if ($supplyRequest->audited_at)
                                                    <a class="btn btn-sm btn-outline-danger"
                                                        href="{{ route('supplies.requests.pdf', $supplyRequest) }}">
                                                        PDF
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="supplies-empty-state">
                                            <i class="fas fa-file-circle-plus d-block"></i>
                                            No hay solicitudes registradas
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        @elseif ($activeTab === 'products')
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="supplies-toolbar mb-3">
                        <div>
                            <div class="supplies-section-title"><i class="fas fa-boxes-stacked"></i> Catalogo de productos</div>
                            <p class="text-muted mb-0">Administre el listado base de papeleria y proveeduria.</p>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle" id="suppliesProductsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Producto</th>
                                    <th>Descripcion</th>
                                    <th>Stock fisico</th>
                                    <th>Reservado</th>
                                    <th>Disponible</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($products as $product)
                                    <tr>
                                        <td class="fw-semibold">{{ $product->catalog_number }}</td>
                                        <td>{{ $product->name }}</td>
                                        <td>{{ $product->description ?: 'Sin descripcion' }}</td>
                                        <td>{{ $product->stock_on_hand }}</td>
                                        <td>{{ $product->reserved_stock }}</td>
                                        <td>{{ $product->available_stock }}</td>
                                        <td>
                                            <span class="badge bg-{{ $product->is_active ? 'success' : 'secondary' }}">
                                                {{ $product->is_active ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-2">
                                                <button
                                                    class="btn btn-sm btn-outline-primary edit-product-button"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editSupplyProductModal"
                                                    data-id="{{ $product->id }}"
                                                    data-catalog-number="{{ $product->catalog_number }}"
                                                    data-name="{{ $product->name }}"
                                                    data-description="{{ $product->description }}"
                                                    data-is-active="{{ $product->is_active ? 1 : 0 }}">
                                                    Editar
                                                </button>
                                                <form method="POST" action="{{ route('supplies.products.destroy', $product) }}"
                                                    onsubmit="return confirm('Desea eliminar este producto de proveeduria?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="supplies-empty-state">
                                            <i class="fas fa-box-open d-block"></i>
                                            No hay productos en el catalogo
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        @else
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="supplies-toolbar mb-3">
                        <div>
                            <div class="supplies-section-title"><i class="fas fa-address-book"></i> Clientes de proveeduria</div>
                            <p class="text-muted mb-0">Base independiente usada en solicitudes y formatos PDF.</p>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <a class="btn btn-outline-success" href="{{ route('supplies.clients.template') }}">
                                Plantilla
                            </a>
                            <button class="btn btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#importSupplyClientModal">
                                Importar clientes
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle" id="suppliesClientsTable">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Direccion</th>
                                    <th>Ciudad</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($clients as $client)
                                    <tr>
                                        <td class="fw-semibold">{{ $client->name }}</td>
                                        <td>{{ $client->address ?: 'Sin direccion' }}</td>
                                        <td>{{ $client->city ?: 'Sin ciudad' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $client->is_active ? 'success' : 'secondary' }}">
                                                {{ $client->is_active ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-2">
                                                <button
                                                    class="btn btn-sm btn-outline-primary edit-client-button"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editSupplyClientModal"
                                                    data-id="{{ $client->id }}"
                                                    data-name="{{ $client->name }}"
                                                    data-address="{{ $client->address }}"
                                                    data-city="{{ $client->city }}"
                                                    data-is-active="{{ $client->is_active ? 1 : 0 }}">
                                                    Editar
                                                </button>
                                                <form method="POST" action="{{ route('supplies.clients.destroy', $client) }}"
                                                    onsubmit="return confirm('Desea eliminar este cliente de proveeduria?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="supplies-empty-state">
                                            <i class="fas fa-users-slash d-block"></i>
                                            No hay clientes registrados
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        @endif
    </div>

    <div class="modal fade supply-request-modal supplies-themed-modal" id="createSupplyRequestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva solicitud de compra</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form method="POST" action="{{ route('supplies.requests.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Observaciones de la solicitud</label>
                            <textarea class="form-control" name="request_notes" rows="3"
                                placeholder="Detalle general de la compra o necesidad interna"></textarea>
                        </div>

                        <div class="mb-3">
                            <h6 class="mb-1">Productos solicitados</h6>
                            <div class="text-muted small">Agregue una o varias lineas con el producto y la cantidad requerida.</div>
                        </div>

                        <div class="table-responsive supply-request-table-wrap">
                            <table class="table align-middle" id="requestItemsTable">
                                <thead>
                                    <tr>
                                        <th style="min-width: 420px;">Producto</th>
                                        <th style="width: 180px;">Cantidad</th>
                                        <th style="width: 90px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <div class="supply-product-picker">
                                                <input type="hidden" class="product-id-input" name="product_id[]" required>
                                                <input type="text" class="form-control product-search-input mb-2"
                                                    placeholder="Escriba nombre o ID para filtrar en tiempo real"
                                                    autocomplete="off">
                                                <div class="product-search-results"></div>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" min="1" class="form-control" name="requested_quantity[]" required>
                                        </td>
                                        <td class="text-end">
                                            <div class="request-row-actions"></div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-danger" type="submit">Guardar solicitud</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade supplies-themed-modal" id="managePurchaseRecipientsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Destinatarios de compras</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border">
                        Configure uno o varios correos para recibir automáticamente cada nueva solicitud de compra de proveeduría.
                    </div>

                    <form method="POST" id="purchaseRecipientForm" action="{{ route('supplies.purchase-recipients.store') }}">
                        @csrf
                        <input type="hidden" name="_method" id="purchaseRecipientMethod" value="POST">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label">Nombre</label>
                                <input type="text" name="name" id="purchaseRecipientName" class="form-control" placeholder="Compras principal">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Correo</label>
                                <input type="email" name="email" id="purchaseRecipientEmail" class="form-control" placeholder="compras@empresa.com" required>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="purchaseRecipientActive" checked>
                                    <label class="form-check-label" for="purchaseRecipientActive">Activo</label>
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-danger w-100" type="submit" id="purchaseRecipientSubmit">Guardar</button>
                                    <button class="btn btn-outline-secondary d-none" type="button" id="purchaseRecipientCancel">Cancelar</button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <hr class="my-4">

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Correo</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($purchaseRecipients as $recipient)
                                    <tr>
                                        <td>{{ $recipient->name ?: 'Sin nombre' }}</td>
                                        <td>{{ $recipient->email }}</td>
                                        <td>
                                            <span class="badge bg-{{ $recipient->is_active ? 'success' : 'secondary' }}">
                                                {{ $recipient->is_active ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-2">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-primary edit-purchase-recipient-button"
                                                    data-id="{{ $recipient->id }}"
                                                    data-name="{{ $recipient->name }}"
                                                    data-email="{{ $recipient->email }}"
                                                    data-is-active="{{ $recipient->is_active ? 1 : 0 }}">
                                                    Editar
                                                </button>
                                                <form method="POST" action="{{ route('supplies.purchase-recipients.destroy', $recipient) }}"
                                                    onsubmit="return confirm('Desea eliminar este destinatario de compras?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="supplies-empty-state">
                                            <i class="fas fa-envelope-open-text d-block"></i>
                                            No hay correos de compras configurados
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade supplies-themed-modal" id="createSupplyClientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo cliente de proveeduria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form method="POST" action="{{ route('supplies.clients.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Direccion</label>
                            <input type="text" name="address" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ciudad</label>
                            <input type="text" name="city" class="form-control" required>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="createClientActive" checked>
                            <label class="form-check-label" for="createClientActive">Cliente activo</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-danger" type="submit">Guardar cliente</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade supplies-themed-modal" id="createSupplyProductModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo producto de proveeduria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form method="POST" action="{{ route('supplies.products.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripcion</label>
                            <textarea name="description" rows="3" class="form-control"></textarea>
                        </div>
                        <div class="alert alert-light border mb-0">
                            El ID de catalogo se asigna automaticamente con el siguiente consecutivo disponible.
                        </div>
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="createProductActive" checked>
                            <label class="form-check-label" for="createProductActive">Producto activo</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-danger" type="submit">Guardar producto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade supplies-themed-modal" id="editSupplyProductModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar producto de proveeduria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form method="POST" id="editSupplyProductForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">ID catalogo</label>
                            <input type="number" min="1" name="catalog_number" id="editCatalogNumber" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="name" id="editProductName" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripcion</label>
                            <textarea name="description" rows="3" id="editProductDescription" class="form-control"></textarea>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editProductActive">
                            <label class="form-check-label" for="editProductActive">Producto activo</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-danger" type="submit">Actualizar producto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade supplies-themed-modal" id="editSupplyClientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar cliente de proveeduria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form method="POST" id="editSupplyClientForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="name" id="editClientName" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Direccion</label>
                            <input type="text" name="address" id="editClientAddress" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ciudad</label>
                            <input type="text" name="city" id="editClientCity" class="form-control" required>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editClientActive">
                            <label class="form-check-label" for="editClientActive">Cliente activo</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-danger" type="submit">Actualizar cliente</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade supplies-themed-modal" id="importSupplyClientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Importar clientes de proveeduria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form method="POST" action="{{ route('supplies.clients.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <p class="text-muted mb-3">Cargue un archivo Excel o CSV con las columnas: <strong>NOMBRE</strong>, <strong>DIRECCIÓN</strong> y <strong>CIUDAD</strong>.</p>
                        <div class="mb-3">
                            <label class="form-label">Archivo</label>
                            <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-danger" type="submit">Importar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const requestItemsTableBody = document.querySelector('#requestItemsTable tbody');
            const catalogOptions = @json($catalogProductOptions);
            const clientOptions = @json($catalogClientOptions);

            function renderProductResults(search) {
                const matches = catalogOptions.filter((product) => product.search.includes(search));
                return matches.length
                    ? matches.map((product) => `
                        <button type="button" class="product-result-option" data-id="${product.id}" data-label="${product.label}">
                            ${product.label}
                        </button>
                    `).join('')
                    : '<div class="product-result-empty">Sin coincidencias</div>';
            }

            function renderClientResults(search) {
                const matches = clientOptions.filter((client) => client.search.includes(search));
                return matches.length
                    ? matches.map((client) => `
                        <button type="button" class="product-result-option client-result-option" data-id="${client.id}" data-label="${client.label}">
                            ${client.label}
                        </button>
                    `).join('')
                    : '<div class="product-result-empty">Sin coincidencias</div>';
            }

            function renderProductOptions(row) {
                const searchInput = row.querySelector('.product-search-input');
                const resultsContainer = row.querySelector('.product-search-results');
                const productIdInput = row.querySelector('.product-id-input');

                if (!searchInput || !resultsContainer || !productIdInput) {
                    return;
                }

                const search = searchInput.value.trim().toLowerCase();

                if (!search) {
                    resultsContainer.innerHTML = '';
                    resultsContainer.classList.remove('is-visible');
                    return;
                }

                resultsContainer.innerHTML = renderProductResults(search);
                resultsContainer.classList.add('is-visible');
            }

            function attachRowListeners(row) {
                const searchInput = row.querySelector('.product-search-input');
                const productIdInput = row.querySelector('.product-id-input');
                const resultsContainer = row.querySelector('.product-search-results');

                searchInput?.addEventListener('input', () => {
                    productIdInput.value = '';
                    renderProductOptions(row);
                });

                searchInput?.addEventListener('focus', () => {
                    if (searchInput.value.trim()) {
                        renderProductOptions(row);
                    }
                });

                resultsContainer?.addEventListener('click', (event) => {
                    const option = event.target.closest('.product-result-option');

                    if (!option) {
                        return;
                    }

                    productIdInput.value = option.dataset.id;
                    searchInput.value = option.dataset.label;
                    resultsContainer.innerHTML = '';
                    resultsContainer.classList.remove('is-visible');
                });
            }

            function buildRowActions(isLastRow, totalRows) {
                const removeDisabled = totalRows === 1 ? 'disabled' : '';

                return `
                    <div class="d-flex justify-content-end gap-2 flex-nowrap">
                        <button type="button" class="btn btn-outline-danger btn-sm remove-request-row" title="Eliminar fila" ${removeDisabled}>
                            <i class="fas fa-trash-alt"></i>
                        </button>
                        ${isLastRow ? `
                            <button type="button" class="btn btn-danger btn-sm add-request-row-inline" title="Agregar nueva fila arriba">
                                <i class="fas fa-plus"></i>
                            </button>
                        ` : ''}
                    </div>
                `;
            }

            function refreshRowActions() {
                if (!requestItemsTableBody) {
                    return;
                }

                const rows = Array.from(requestItemsTableBody.querySelectorAll('tr'));

                rows.forEach((row, index) => {
                    const actionCell = row.querySelector('.request-row-actions');
                    if (actionCell) {
                        actionCell.innerHTML = buildRowActions(index === rows.length - 1, rows.length);
                    }
                });
            }

            function addRequestRow() {
                if (!requestItemsTableBody) {
                    return;
                }

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        <div class="supply-product-picker">
                            <input type="hidden" class="product-id-input" name="product_id[]" required>
                            <input type="text" class="form-control product-search-input mb-2"
                                placeholder="Escriba nombre o ID para filtrar en tiempo real"
                                autocomplete="off">
                            <div class="product-search-results"></div>
                        </div>
                    </td>
                    <td>
                        <input type="number" min="1" class="form-control" name="requested_quantity[]" required>
                    </td>
                    <td class="text-end">
                        <div class="request-row-actions"></div>
                    </td>
                `;

                requestItemsTableBody.prepend(row);
                attachRowListeners(row);
                refreshRowActions();
                row.querySelector('.product-search-input')?.focus();
            }

            if (requestItemsTableBody) {
                requestItemsTableBody.addEventListener('click', (event) => {
                    if (event.target.closest('.add-request-row-inline')) {
                        addRequestRow();
                        return;
                    }

                    const removeButton = event.target.closest('.remove-request-row');

                    if (!removeButton) {
                        return;
                    }

                    const rows = requestItemsTableBody.querySelectorAll('tr');
                    if (rows.length === 1) {
                        rows[0].querySelector('.product-search-input').value = '';
                        rows[0].querySelector('.product-id-input').value = '';
                        rows[0].querySelector('.product-search-results').innerHTML = '';
                        rows[0].querySelector('.product-search-results').classList.remove('is-visible');
                        rows[0].querySelector('input[name="requested_quantity[]"]').value = '';
                        refreshRowActions();
                        return;
                    }

                    removeButton.closest('tr')?.remove();
                    refreshRowActions();
                });

                requestItemsTableBody.querySelectorAll('tr').forEach((row) => attachRowListeners(row));
                refreshRowActions();
            }

            document.querySelectorAll('.supply-client-picker').forEach((picker) => {
                const searchInput = picker.querySelector('.client-search-input');
                const clientIdInput = picker.querySelector('.client-id-input');
                const resultsContainer = picker.querySelector('.client-search-results');

                function paintResults() {
                    const search = searchInput.value.trim().toLowerCase();

                    if (!search) {
                        resultsContainer.innerHTML = '';
                        resultsContainer.classList.remove('is-visible');
                        return;
                    }

                    resultsContainer.innerHTML = renderClientResults(search);
                    resultsContainer.classList.add('is-visible');
                }

                searchInput?.addEventListener('input', () => {
                    clientIdInput.value = '';
                    paintResults();
                });

                searchInput?.addEventListener('focus', paintResults);

                resultsContainer?.addEventListener('click', (event) => {
                    const option = event.target.closest('.client-result-option');

                    if (!option) {
                        return;
                    }

                    clientIdInput.value = option.dataset.id;
                    searchInput.value = option.dataset.label;
                    resultsContainer.innerHTML = '';
                    resultsContainer.classList.remove('is-visible');
                });
            });

            document.addEventListener('click', (event) => {
                document.querySelectorAll('.supply-product-picker').forEach((picker) => {
                    if (!picker.contains(event.target)) {
                        picker.querySelector('.product-search-results')?.classList.remove('is-visible');
                    }
                });

                document.querySelectorAll('.supply-client-picker').forEach((picker) => {
                    if (!picker.contains(event.target)) {
                        picker.querySelector('.client-search-results')?.classList.remove('is-visible');
                    }
                });
            });

            document.querySelectorAll('.edit-product-button').forEach((button) => {
                button.addEventListener('click', () => {
                    const editForm = document.getElementById('editSupplyProductForm');

                    if (!editForm) {
                        return;
                    }

                    editForm.action = `{{ url('/supplies/products') }}/${button.dataset.id}`;
                    document.getElementById('editCatalogNumber').value = button.dataset.catalogNumber || '';
                    document.getElementById('editProductName').value = button.dataset.name || '';
                    document.getElementById('editProductDescription').value = button.dataset.description || '';
                    document.getElementById('editProductActive').checked = button.dataset.isActive === '1';
                });
            });

            document.querySelectorAll('.edit-client-button').forEach((button) => {
                button.addEventListener('click', () => {
                    const editForm = document.getElementById('editSupplyClientForm');

                    if (!editForm) {
                        return;
                    }

                    editForm.action = `{{ url('/supplies/clients') }}/${button.dataset.id}`;
                    document.getElementById('editClientName').value = button.dataset.name || '';
                    document.getElementById('editClientAddress').value = button.dataset.address || '';
                    document.getElementById('editClientCity').value = button.dataset.city || '';
                    document.getElementById('editClientActive').checked = button.dataset.isActive === '1';
                });
            });

            document.querySelectorAll('.edit-purchase-recipient-button').forEach((button) => {
                button.addEventListener('click', () => {
                    const form = document.getElementById('purchaseRecipientForm');
                    const method = document.getElementById('purchaseRecipientMethod');
                    const submit = document.getElementById('purchaseRecipientSubmit');
                    const cancel = document.getElementById('purchaseRecipientCancel');

                    if (!form || !method || !submit || !cancel) {
                        return;
                    }

                    form.action = `{{ url('/supplies/purchase-recipients') }}/${button.dataset.id}`;
                    method.value = 'PUT';
                    document.getElementById('purchaseRecipientName').value = button.dataset.name || '';
                    document.getElementById('purchaseRecipientEmail').value = button.dataset.email || '';
                    document.getElementById('purchaseRecipientActive').checked = button.dataset.isActive === '1';
                    submit.textContent = 'Actualizar';
                    cancel.classList.remove('d-none');
                });
            });

            document.getElementById('purchaseRecipientCancel')?.addEventListener('click', () => {
                const form = document.getElementById('purchaseRecipientForm');
                const method = document.getElementById('purchaseRecipientMethod');
                const submit = document.getElementById('purchaseRecipientSubmit');
                const cancel = document.getElementById('purchaseRecipientCancel');

                if (!form || !method || !submit || !cancel) {
                    return;
                }

                form.action = `{{ route('supplies.purchase-recipients.store') }}`;
                method.value = 'POST';
                form.reset();
                document.getElementById('purchaseRecipientActive').checked = true;
                submit.textContent = 'Guardar';
                cancel.classList.add('d-none');
            });

            const DATATABLES_LANG = 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json';
            const DT_COMMON = {
                language: { url: DATATABLES_LANG },
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50, 100],
                order: [],
                autoWidth: false,
                responsive: true
            };

            if ($('#suppliesRequestsTable tbody tr').length > 0 && !$('#suppliesRequestsTable tbody tr td[colspan]').length) {
                $('#suppliesRequestsTable').DataTable($.extend({}, DT_COMMON, { pageLength: 10 }));
            }

            if ($('#suppliesProductsTable tbody tr').length > 0 && !$('#suppliesProductsTable tbody tr td[colspan]').length) {
                $('#suppliesProductsTable').DataTable($.extend({}, DT_COMMON, { pageLength: 25 }));
            }

            if ($('#suppliesClientsTable tbody tr').length > 0 && !$('#suppliesClientsTable tbody tr td[colspan]').length) {
                $('#suppliesClientsTable').DataTable($.extend({}, DT_COMMON, { pageLength: 10 }));
            }
        });
    </script>
    <style>
        .supply-request-modal .modal-content,
        .supply-request-modal .modal-body,
        .supply-request-table-wrap,
        .supply-request-table-wrap .table,
        .supply-request-table-wrap tbody,
        .supply-request-table-wrap tr,
        .supply-request-table-wrap td {
            overflow: visible !important;
        }

        .supply-request-modal .modal-body {
            padding-bottom: 6rem;
        }

        .supply-product-picker,
        .supply-client-picker {
            position: relative;
        }

        .product-search-results,
        .client-search-results {
            display: none;
            position: absolute;
            left: 0;
            right: 0;
            top: calc(100% - 0.5rem);
            z-index: 2056;
            max-height: 240px;
            overflow-y: auto;
            border: 1px solid var(--sup-border);
            border-radius: 0.75rem;
            background: var(--sup-card);
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.14);
            padding: 0.35rem;
        }

        .product-search-results.is-visible,
        .client-search-results.is-visible {
            display: block;
        }

        .product-result-option {
            display: block;
            width: 100%;
            text-align: left;
            border: 0;
            background: transparent;
            padding: 0.65rem 0.75rem;
            border-radius: 0.55rem;
            color: var(--sup-text);
        }

        .product-result-option:hover {
            background: #fee2e2;
            color: #991b1b;
        }

        .product-result-empty {
            padding: 0.7rem 0.75rem;
            color: var(--sup-muted);
            font-size: 0.92rem;
        }
    </style>
@endsection
