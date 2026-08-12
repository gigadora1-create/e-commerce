@extends('layouts.app')


@push('styles')
<link rel="stylesheet" href="{{ asset('css/warehouse.index.css') }}?v={{ filemtime(public_path('css/warehouse.index.css')) }}">
@endpush

@section('contents')
<script>
    (function () {
        const body = document.body;
        const wrapperRoot = document.getElementById('wrapper');
        const wrapper = document.getElementById('content-wrapper');
        const content = document.getElementById('content');
        if (body) body.classList.add('warehouse-page');
        if (wrapperRoot) wrapperRoot.classList.add('warehouse-page');
        if (wrapper) wrapper.classList.add('warehouse-page');
        if (content) content.classList.add('warehouse-page');
    })();
</script>
   <div class="d-flex justify-content-end align-items-center">
    @if(!empty($selectedCustomers))
        <div class="alert alert-info d-inline-flex align-items-center mb-0 py-2 px-3">
            <i class="fas fa-user me-2"></i>
            <strong>{{ count($selectedCustomers) > 1 ? 'Clientes' : 'Cliente' }}: {{ implode(', ', $selectedCustomers) }}</strong>
        </div>
    @elseif(session('selected_customer'))
        <div class="alert alert-info d-inline-flex align-items-center mb-0 py-2 px-3">
            <i class="fas fa-user me-2"></i>
            <strong>Cliente: {{ session('selected_customer') }}</strong>
        </div>
    @else
        <button class="btn btn-primary" id="selectCustomerBtn">
            <i class="fas fa-user-plus me-1"></i> Seleccionar Cliente
        </button>
    @endif
</div>
@php
    $selectedSearch = trim((string) ($filters['search'] ?? ''));
    $activeTab = $activeTab ?? 'locations';
    $isSuperAdmin = auth()->check() && auth()->user()->isSuperAdmin();
    $importCustomerOptions = $isSuperAdmin ? $customerOptions : $customers;
@endphp

<div class="warehouse-shell">
    <div class="warehouse-shell__inner">
        <div class="warehouse-hero">
            <div class="warehouse-hero__head">
                <div class="warehouse-kicker">Bodega</div>
                <h2>
                    GLE
                </h2>
                <p>Ingreso, ubicación y salida de guías.</p>
            </div>

            <div class="warehouse-hero-actions">
                <button type="button" class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#guideEntryModal" onclick="openGuideEntryModal()">
                    <i class="fas fa-barcode me-2"></i>Registrar ingreso
                </button>
                <button type="button" class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#importWarehouseModal">
                    <i class="fas fa-file-import me-2"></i>Importar Excel
                </button>
                <button type="button" class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#statisticsModal">
                    <i class="fas fa-chart-column me-2"></i>Estadísticas
                </button>
                <button type="button" class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#groupExitModal" onclick="openGroupExitModal()">
                    <i class="fas fa-layer-group me-2"></i>salida nacional
                </button>
                <button type="button" class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#locationModal" onclick="openLocationModal()">
                    <i class="fas fa-map-marker-alt me-2"></i>Nueva ubicación
                </button>
            </div>

            <div class="warehouse-context">
                <div class="warehouse-context__copy">
                    <div class="warehouse-context__label">Cliente activo</div>
                    <div class="warehouse-context__value">{{ $customer }}</div>
                    <div class="warehouse-context__hint">
                        @if($isSuperAdmin)
                            {{ !empty($selectedCustomer) ? 'Contexto seleccionado manualmente.' : 'Sin selección manual. Se usa el cliente con mayor actividad.' }}
                        @else
                            El contexto se toma de la sesión actual.
                        @endif
                    </div>
                </div>

                @if($isSuperAdmin)
                    <div class="warehouse-context__actions">
                        <form method="POST" action="{{ route('warehouse.customer.select') }}" class="warehouse-context__form">
                            @csrf
                            <select name="customer" class="form-select form-select-sm" required>
                                @foreach($customerOptions as $option)
                                    <option value="{{ $option }}" {{ $option === $customer ? 'selected' : '' }}>
                                        {{ $option }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fas fa-sync-alt me-1"></i>Cambiar
                            </button>
                        </form>

                        @if(!empty($selectedCustomer))
                            <form method="POST" action="{{ route('warehouse.customer.clear') }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                    Restablecer
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>

            <form method="GET" action="{{ route('warehouse.index') }}" class="warehouse-context mt-3">
                <input type="hidden" name="tab" value="{{ $activeTab }}">
                <div class="row g-2 align-items-end w-100">
                    <div class="col-md-5">
                        <label for="warehousePageFilter" class="form-label mb-1">Cliente bodega activo</label>
                        <select name="warehouse" id="warehousePageFilter" class="form-select form-select-sm">
                            <option value="">Selecciona cliente bodega</option>
                            @foreach($warehouseOptions as $warehouse)
                                <option value="{{ $warehouse }}" {{ ($activeWarehouse ?? null) === $warehouse ? 'selected' : '' }}>
                                    {{ $warehouse }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fas fa-filter me-1"></i>Filtrar
                        </button>
                    </div>
                    @if(!empty($activeWarehouse))
                        <div class="col-md-auto">
                            <a href="{{ route('warehouse.index', ['tab' => $activeTab]) }}" class="btn btn-sm btn-outline-secondary">
                                Limpiar
                            </a>
                        </div>
                    @endif
                </div>
            </form>
        </div>

        <div class="card warehouse-main-card">
    <div class="card-body p-0">
        <ul class="nav nav-pills warehouse-tabs px-4 pt-4" id="warehouseTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab === 'guides' ? 'active' : '' }}" id="guides-tab" data-bs-toggle="pill" data-bs-target="#guidesPane" type="button" role="tab" aria-controls="guidesPane" aria-selected="{{ $activeTab === 'guides' ? 'true' : 'false' }}">
                    <i class="fas fa-route me-2"></i>Trazabilidad
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab === 'locations' ? 'active' : '' }}" id="locations-tab" data-bs-toggle="pill" data-bs-target="#locationsPane" type="button" role="tab" aria-controls="locationsPane" aria-selected="{{ $activeTab === 'locations' ? 'true' : 'false' }}">
                    <i class="fas fa-map-marker-alt me-2"></i>Localizaciones
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab === 'reports' ? 'active' : '' }}" id="reports-tab" data-bs-toggle="pill" data-bs-target="#reportsPane" type="button" role="tab" aria-controls="reportsPane" aria-selected="{{ $activeTab === 'reports' ? 'true' : 'false' }}">
                    <i class="fas fa-file-excel me-2"></i>Reportes
                </button>
            </li>
        </ul>

        <div class="tab-content p-4">
            <div class="tab-pane fade {{ $activeTab === 'guides' ? 'show active' : '' }}" id="guidesPane" role="tabpanel" aria-labelledby="guides-tab">
                <div class="tab-intro">
                    <div>
                        <h4>Guías en bodega</h4>
                        <p>Consulta y administra los ingresos desde una sola vista.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-outline-secondary btn-pill" data-bs-toggle="modal" data-bs-target="#importWarehouseModal">
                            <i class="fas fa-file-import me-2"></i>Importar
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-pill" data-bs-toggle="modal" data-bs-target="#groupExitModal" onclick="openGroupExitModal()">
                            <i class="fas fa-layer-group me-2"></i>salida nacional
                        </button>
                        <button type="button" class="btn btn-primary btn-pill" data-bs-toggle="modal" data-bs-target="#guideEntryModal" onclick="openGuideEntryModal()">
                            <i class="fas fa-plus me-2"></i>Nuevo ingreso
                        </button>
                    </div>
                </div>

                @include('warehouse.partials.import-summary')

                <div class="lookup-toolbar mb-3">
                    <div class="lookup-card lookup-card--compact">
                        <label for="guideLookupCode" class="form-label">Consultar gu&iacute;a</label>
                        <div class="input-group">
                            <input type="search" class="form-control" id="guideLookupCode" value="{{ $selectedSearch }}" placeholder="Escribe la gu&iacute;a" autocomplete="off" spellcheck="false">
                            <button type="button" class="btn btn-outline-secondary" id="guideLookupClear" aria-label="Limpiar búsqueda">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="muted-note mt-2">Escribe la guía y la tabla se filtrará automáticamente.</div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-warehouse align-middle">
                        <thead>
                            <tr>
                                <th>Guía</th>
                                <th>Estado</th>
                                <th>Bodega</th>
                                <th>Ubicación actual</th>
                                <th>Ingreso</th>
                                <th>Salida</th>
                                <th>Duración</th>
                                <th>Movimientos</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($guides as $guide)
                                <tr data-guide-row data-guide-search="{{ strtolower(trim((string) ($guide->guide . ' ' . ($guide->national_guide ?? '')))) }}">
                                    <td>
                                        <div class="guide-code">{{ $guide->guide }}</div>
                                        <div class="guide-meta">
                                            {{ strtoupper($guide->entry_source ?? 'manual') }}
                                        </div>
                                        @if($guide->national_guide)
                                            <div class="guide-meta">Nacional: {{ $guide->national_guide }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $guide->status_badge_class }}">{{ $guide->status_label }}</span>
                                    </td>
                                    <td>{{ $guide->warehouse }}</td>
                                    <td>{{ $guide->current_location_label }}</td>
                                    <td>{{ optional($guide->entry_at)->format('d/m/Y H:i') }}</td>
                                    <td>{{ $guide->exit_at ? optional($guide->exit_at)->format('d/m/Y H:i') : 'En curso' }}</td>
                                    <td>{{ $guide->duration_label }}</td>
                                    <td><span class="badge bg-light text-dark">{{ number_format($guide->movements_count ?? 0) }}</span></td>
                                    <td class="text-end">
                                        <div class="warehouse-actions d-inline-flex justify-content-end align-items-center flex-nowrap">
                                            <button type="button" class="btn btn-sm btn-outline-secondary warehouse-action-btn" aria-label="Ver trazabilidad" onclick="openGuideTimeline('{{ $guide->guide }}')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            @if($isSuperAdmin)
                                                <button type="button" class="btn btn-sm btn-outline-secondary warehouse-action-btn" data-bs-toggle="tooltip" title="Editar ingreso" aria-label="Editar ingreso" onclick="openGuideEditModal('{{ $guide->guide }}')">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger warehouse-action-btn" data-bs-toggle="tooltip" title="Eliminar ingreso" aria-label="Eliminar ingreso" onclick="deleteGuide('{{ $guide->guide }}')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endif
                                            @if($guide->is_active)
                                                <button type="button" class="btn btn-sm btn-outline-primary warehouse-action-btn" data-bs-toggle="tooltip" title="Mover ubicación" aria-label="Mover ubicación" onclick="openMoveGuideModal('{{ $guide->guide }}')">
                                                    <i class="fas fa-exchange-alt"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger warehouse-action-btn" data-bs-toggle="tooltip" title="Registrar salida" aria-label="Registrar salida" onclick="openExitGuideModal('{{ $guide->guide }}')">
                                                    <i class="fas fa-sign-out-alt"></i>
                                                </button>
                                            @else
                                                <span class="d-inline-flex" data-bs-toggle="tooltip" title="Guía cerrada">
                                                    <button type="button" class="btn btn-sm btn-outline-primary warehouse-action-btn" disabled aria-label="Mover ubicación">
                                                        <i class="fas fa-exchange-alt"></i>
                                                    </button>
                                                </span>
                                                <span class="d-inline-flex" data-bs-toggle="tooltip" title="Guía cerrada">
                                                    <button type="button" class="btn btn-sm btn-outline-danger warehouse-action-btn" disabled aria-label="Registrar salida">
                                                        <i class="fas fa-sign-out-alt"></i>
                                                    </button>
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr id="guideTableEmptyState">
                                    <td colspan="9" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="fas fa-box-open fa-2x mb-3"></i>
                                            <p class="mb-0">No hay guías registradas.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                            @if($guides->count() > 0)
                                <tr id="guideTableEmptyState" class="d-none">
                                    <td colspan="9" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="fas fa-box-open fa-2x mb-3"></i>
                                            <p class="mb-0">No hay guías registradas con esa búsqueda.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <div class="warehouse-pagination mt-4">
                    {{ $guides->links('pagination::bootstrap-5') }}
                </div>
            </div>

            <div class="tab-pane fade {{ $activeTab === 'locations' ? 'show active' : '' }}" id="locationsPane" role="tabpanel" aria-labelledby="locations-tab">
                <div class="tab-intro">
                    <div>
                        <h4>Tablero de localizaciones</h4>
                        <p>Revisa cada localización como un contenedor, mira las guías dentro y administra movimientos sin cambiar de pantalla.</p>
                    </div>
                    <button type="button" class="btn  btn-pill" data-bs-toggle="modal" data-bs-target="#locationModal" onclick="openLocationModal()">
                        <i class="fas fa-plus me-2"></i>Nueva ubicación
                    </button>
                </div>

                <div class="location-search-toolbar mb-4">
                    <div class="lookup-card location-search-card">
                        <label for="locationBoardSearch" class="form-label">Buscar ubicación</label>
                        <div class="input-group">
                            <input
                                type="search"
                                class="form-control"
                                id="locationBoardSearch"
                                placeholder="Código, nombre, bodega, guía o descripción"
                                autocomplete="off"
                                spellcheck="false"
                            >
                            <button type="button" class="btn btn-outline-secondary" id="locationBoardSearchClear">
                                <i class="fas fa-eraser me-1"></i>Limpiar
                            </button>
                        </div>
                        <div class="muted-note mt-2">Busca por código, nombre, bodega, guía o descripción.</div>
                    </div>
                </div>

                @include('warehouse.partials.location-board', ['locationBoards' => $locationBoards])

                <div class="alert alert-light border warehouse-board__empty-state d-none mt-4" id="locationBoardEmptyState" role="status" aria-live="polite">
                    No se encontraron ubicaciones con esa búsqueda.
                </div>
            </div>

            <div class="tab-pane fade {{ $activeTab === 'reports' ? 'show active' : '' }}" id="reportsPane" role="tabpanel" aria-labelledby="reports-tab">
                <div class="tab-intro">
                    <div>
                        <h4>Reportes y exportación</h4>
                        <p>Exporta las guías con ingreso, salida y duración en bodega.</p>
                    </div>
                </div>

                <div class="report-panel">
                    <div class="report-panel__hero mb-4">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                            <div>
                                <h4 class="mb-2">Reportes operativos de bodega</h4>
                                <p>
                                    Genera reportes por rango de fechas, tipo de operación y guía nacional para auditoría, seguimiento y cierre logístico.
                                </p>
                            </div>
                            <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#reportModal">
                                <i class="fas fa-file-excel me-2"></i>Exportar Excel
                            </button>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="p-3 border rounded-4 h-100">
                                <div class="text-uppercase small text-muted fw-bold">Formato</div>
                                <div class="h5 mb-2">Excel</div>
                                <div class="text-muted small">Compatible con reportes operativos y auditoría.</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 border rounded-4 h-100">
                                <div class="text-uppercase small text-muted fw-bold">Tipos</div>
                                <div class="h5 mb-2">Ingresos / Salidas</div>
                                <div class="text-muted small">Puedes exportar solo entradas, solo salidas o ambos eventos.</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 border rounded-4 h-100">
                                <div class="text-uppercase small text-muted fw-bold">Rango</div>
                                <div class="h5 mb-2">Desde / Hasta</div>
                                <div class="text-muted small">Cruza fechas de ingreso y salida según el tipo de operación seleccionado.</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 border rounded-4 h-100">
                                <div class="text-uppercase small text-muted fw-bold">Filtro extra</div>
                                <div class="h5 mb-2">Guía nacional</div>
                                <div class="text-muted small">Útil para consolidaciones de salida y trazabilidad de despacho.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    </div>
</div>

<div class="modal fade" id="guideEntryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="guideEntryForm">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1">Registrar ingreso de guía</h5>
                        <small>Escaneo por barra o captura manual.</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="guideEntryGuide" class="form-label">Guía *</label>
                            <input type="text" class="form-control text-uppercase" id="guideEntryGuide" placeholder="" autocomplete="off" required>
                            
                        </div>
                        <div class="col-md-4">
                            <label for="guideEntrySource" class="form-label">Captura *</label>
                            <select class="form-select" id="guideEntrySource">
                                <option value="manual">Manual</option>
                                <option value="barcode">Código de barras</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="guideEntryWarehouse" class="form-label">Bodega (opcional)</label>
                            <select class="form-select" id="guideEntryWarehouse"></select>
                        </div>
                        <div class="col-12">
                            <label for="guideEntryLocation" class="form-label">Ubicación de ingreso *</label>
                            <select class="form-select" id="guideEntryLocation" required></select>
                            <div class="muted-note mt-1">Selecciona una ubicación o el almacenamiento antes de guardar.</div>
                        </div>
                        <div class="col-12">
                            <label for="guideEntryNotes" class="form-label">Observaciones</label>
                            <textarea class="form-control" id="guideEntryNotes" rows="3" maxlength="2000" placeholder="Datos adicionales del ingreso..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="spinner-border spinner-border-sm d-none me-2" id="guideEntrySpinner"></span>
                        Guardar ingreso
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="guideEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="guideEditForm">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1">Editar ingreso</h5>
                        <small>Solo SUPER_ADMIN puede modificar este registro.</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="guideEditOriginalGuide">
                    <div class="alert alert-warning py-2 small mb-3" id="guideEditHint">
                        Corrige el ingreso sin perder la trazabilidad.
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="guideEditGuide" class="form-label">Guía *</label>
                            <input type="text" class="form-control text-uppercase" id="guideEditGuide" placeholder="" autocomplete="off" required>
                        </div>
                        <div class="col-md-4">
                            <label for="guideEditSource" class="form-label">Captura *</label>
                            <select class="form-select" id="guideEditSource">
                                <option value="manual">Manual</option>
                                <option value="barcode">Código de barras</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="guideEditWarehouse" class="form-label">Bodega *</label>
                            <select class="form-select" id="guideEditWarehouse"></select>
                        </div>
                        <div class="col-12">
                            <label for="guideEditLocation" class="form-label">Ubicación</label>
                            <select class="form-select" id="guideEditLocation"></select>
                        </div>
                        <div class="col-12">
                            <label for="guideEditNotes" class="form-label">Observaciones</label>
                            <textarea class="form-control" id="guideEditNotes" rows="3" maxlength="2000" placeholder="Datos adicionales del ingreso..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="spinner-border spinner-border-sm d-none me-2" id="guideEditSpinner"></span>
                        Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="moveGuideModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="moveGuideForm">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1">Mover guía de ubicación</h5>
                        <small>Cambia la guía a otra ubicación activa.</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="moveGuideCode">
                    <input type="hidden" id="moveGuideCurrentLocationId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="moveGuideCurrentLocation" class="form-label">Ubicación actual</label>
                            <input type="text" class="form-control" id="moveGuideCurrentLocation" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="moveGuideWarehouse" class="form-label">Filtrar bodega</label>
                            <select class="form-select" id="moveGuideWarehouse"></select>
                        </div>
                        <div class="col-12">
                            <label for="moveGuideLocation" class="form-label">Nueva ubicación *</label>
                            <select class="form-select" id="moveGuideLocation" required></select>
                        </div>
                        <div class="col-12">
                            <label for="moveGuideNotes" class="form-label">Observaciones</label>
                            <textarea class="form-control" id="moveGuideNotes" rows="3" maxlength="2000"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="spinner-border spinner-border-sm d-none me-2" id="moveGuideSpinner"></span>
                        Guardar movimiento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="exitGuideModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <form id="exitGuideForm">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1">Registrar salida</h5>
                        <small>Cierra la estadía de la guía en bodega.</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="exitGuideCode">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="exitGuideCurrentLocation" class="form-label">Ubicación actual</label>
                            <input type="text" class="form-control" id="exitGuideCurrentLocation" readonly>
                        </div>
                        <div class="col-12">
                            <label for="exitGuideNationalGuide" class="form-label">Guía nacional *</label>
                            <input type="text" class="form-control text-uppercase" id="exitGuideNationalGuide" maxlength="60" placeholder="Guía nacional de salida" required>
                        </div>
                        <div class="col-12">
                            <label for="exitGuideNotes" class="form-label">Observaciones</label>
                            <textarea class="form-control" id="exitGuideNotes" rows="3" maxlength="2000" placeholder="Motivo de salida o anotaciones operativas..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <span class="spinner-border spinner-border-sm d-none me-2" id="exitGuideSpinner"></span>
                        Registrar salida
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="locationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="locationForm">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1" id="locationModalTitle">Nueva ubicación</h5>
                        <small>Administra ubicaciones para la carga en bodega.</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="locationFormId">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="locationCode" class="form-label">Código *</label>
                            <input type="text" class="form-control text-uppercase" id="locationCode" maxlength="30" required>
                        </div>
                        <div class="col-md-4">
                            <label for="locationName" class="form-label">Nombre *</label>
                            <input type="text" class="form-control" id="locationName" maxlength="255" required>
                        </div>
                        <div class="col-md-4">
                            <label for="locationWarehouse" class="form-label">Cliente bodega *</label>
                            <input type="text" class="form-control" id="locationWarehouse" list="warehouseSuggestions" maxlength="100" readonly required>
                            <datalist id="warehouseSuggestions">
                                @foreach($warehouseOptions as $warehouse)
                                    <option value="{{ $warehouse }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <div class="col-12">
                            <label for="locationDescription" class="form-label">Descripción</label>
                            <textarea class="form-control" id="locationDescription" rows="3" maxlength="1000"></textarea>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="locationActive" checked>
                                <label class="form-check-label" for="locationActive">Ubicación activa</label>
                            </div>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <div class="form-check mt-2 d-inline-flex align-items-center gap-2">
                                <input class="form-check-input" type="checkbox" id="locationStorage">
                                <label class="form-check-label" for="locationStorage">Usar como almacenamiento</label>
                            </div>
                            <div class="muted-note">La ubicación de almacenamiento se protege para conservar la trazabilidad.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="spinner-border spinner-border-sm d-none me-2" id="locationSpinner"></span>
                        Guardar ubicación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="timelineModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1">Línea de tiempo de la guía</h5>
                    <small>Consulta trazabilidad, tiempos y ubicación actual.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="p-3 border rounded-4 h-100">
                            <div class="text-muted small text-uppercase fw-bold">Guía</div>
                            <div class="h5 mb-1" id="timelineGuideCode">-</div>
                            <span class="badge" id="timelineGuideStatus">-</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded-4 h-100">
                            <div class="text-muted small text-uppercase fw-bold">Ubicación actual</div>
                            <div class="h5 mb-1" id="timelineGuideLocation">-</div>
                            <div class="text-muted small" id="timelineGuideWarehouse">-</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded-4 h-100">
                            <div class="text-muted small text-uppercase fw-bold">Duración</div>
                            <div class="h5 mb-1" id="timelineGuideDuration">-</div>
                            <div class="text-muted small" id="timelineGuideUsers">-</div>
                        </div>
                    </div>
                </div>

                <div class="timeline-shell mb-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <div>
                            <h5 class="mb-1">Detalles operativos</h5>
                            <div class="text-muted small" id="timelineGuideNotes">-</div>
                        </div>
                        <div class="warehouse-actions d-inline-flex align-items-center flex-nowrap gap-1" id="timelineActionButtons"></div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="p-3 border rounded-4 h-100">
                                <div class="text-muted small text-uppercase fw-bold">Ingreso</div>
                                <div id="timelineGuideEntry">-</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 border rounded-4 h-100">
                                <div class="text-muted small text-uppercase fw-bold">Salida</div>
                                <div id="timelineGuideExit">En curso</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 border rounded-4 h-100">
                                <div class="text-muted small text-uppercase fw-bold">Captura</div>
                                <div id="timelineGuideSource">-</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 border rounded-4 h-100">
                                <div class="text-muted small text-uppercase fw-bold">Movimientos</div>
                                <div id="timelineGuideCount">-</div>
                            </div>
                        </div>
                    </div>

                    <div id="timelineMovements" class="timeline"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="locationDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1" id="locationDetailTitle">Detalle de ubicación</h5>
                    <small id="locationDetailSubtitle">Cajas y guías dentro de la ubicación.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="location-detail-summary">
                            <div class="text-muted small text-uppercase fw-bold">Bodega</div>
                            <div class="h5 mb-0" id="locationDetailWarehouse">-</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="location-detail-summary">
                            <div class="text-muted small text-uppercase fw-bold">Estado</div>
                            <div class="h5 mb-0" id="locationDetailState">-</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="location-detail-summary">
                            <div class="text-muted small text-uppercase fw-bold">Tipo</div>
                            <div class="h5 mb-0" id="locationDetailType">-</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="location-detail-summary location-detail-summary--highlight">
                            <div class="text-muted small text-uppercase fw-bold">Cajas dentro</div>
                            <div class="location-detail-summary__boxes">
                                <div class="location-detail-summary__icon" aria-hidden="true">
                                    <i class="fas fa-boxes"></i>
                                </div>
                                <div>
                                    <div class="h4 mb-0" id="locationDetailBoxes">0</div>
                                    <div class="text-muted small">Cada guía representa una caja</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <h5 class="mb-1">Guías en la ubicación</h5>
                        <div class="text-muted small">La lista completa se muestra aquí para no saturar la tarjeta principal.</div>
                    </div>
                    <span class="badge bg-dark" id="locationDetailCount">0 cajas</span>
                </div>

                <div id="locationDetailGuides" class="location-detail-guides"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="statisticsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1">Estadísticas de bodega</h5>
                    <small>Resumen operativo y promedio de duración desde ingreso hasta salida.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="stats-grid mb-4">
                    <div class="stats-card">
                        <div class="stats-card__label">Guías activas</div>
                        <div class="stats-card__value">{{ number_format($stats['active_guides'] ?? 0) }}</div>
                        <div class="stats-card__meta">En seguimiento</div>
                    </div>
                    <div class="stats-card">
                        <div class="stats-card__label">En almacenamiento</div>
                        <div class="stats-card__value">{{ number_format($stats['storage_guides'] ?? 0) }}</div>
                        <div class="stats-card__meta">Zona transitoria</div>
                    </div>
                    <div class="stats-card">
                        <div class="stats-card__label">Ingresos hoy</div>
                        <div class="stats-card__value">{{ number_format($stats['today_entries'] ?? 0) }}</div>
                        <div class="stats-card__meta">Recibidos en el día</div>
                    </div>
                    <div class="stats-card">
                        <div class="stats-card__label">Salidas hoy</div>
                        <div class="stats-card__value">{{ number_format($stats['today_exits'] ?? 0) }}</div>
                        <div class="stats-card__meta">Guías cerradas</div>
                    </div>
                    <div class="stats-card">
                        <div class="stats-card__label">Estadía promedio</div>
                        <div class="stats-card__value">{{ $stats['average_label'] ?? '00m' }}</div>
                        <div class="stats-card__meta">Promedio entrada a salida</div>
                    </div>
                    <div class="stats-card">
                        <div class="stats-card__label">Ubicaciones activas</div>
                        <div class="stats-card__value">{{ number_format($stats['active_locations'] ?? 0) }}</div>
                        <div class="stats-card__meta">Disponibles para carga</div>
                    </div>
                </div>

                <div class="duration-chart-card">
                    <div class="duration-chart-card__head">
                        <div>
                            <h5 class="mb-1">Promedio diario de duración</h5>
                            <div class="text-muted small">Basado en guías con salida registrada en los últimos 7 días.</div>
                        </div>
                        <div class="duration-chart-card__summary">
                            <span class="badge bg-light text-dark">{{ number_format($stats['exited_guides'] ?? 0) }} guías cerradas</span>
                        </div>
                    </div>

                    @if(!empty($stats['duration_series']))
                        <div class="duration-chart">
                            @foreach($stats['duration_series'] as $point)
                                @php
                                    $barHeight = max(8, (int) round((($point['average_minutes'] ?? 0) / max(1, $stats['duration_series_max'] ?? 1)) * 160));
                                @endphp
                                <div class="duration-chart__item">
                                    <div class="duration-chart__value">{{ $point['average_label'] }}</div>
                                    <div class="duration-chart__bar-wrap">
                                        <div class="duration-chart__bar" style="height: {{ $barHeight }}px;"></div>
                                    </div>
                                    <div class="duration-chart__label">{{ $point['label'] }}</div>
                                    <div class="duration-chart__meta">{{ number_format($point['guides'] ?? 0) }} guía(s)</div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-light border mb-0">
                            Aún no hay guías con salida registrada para calcular el promedio de duración.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="groupExitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form id="groupExitForm">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1">Salida nacional</h5>
                        <small>Primero carga las guías internacionales y, cuando confirmes el grupo, asigna la guía nacional de salida.</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="groupExitGuideInput" class="form-label">Escanear o escribir guía internacional *</label>
                            <input type="text" class="form-control text-uppercase" id="groupExitGuideInput" maxlength="30" placeholder="GL000024273CO" autocomplete="off" spellcheck="false">
                            <div class="muted-note mt-1">Puedes ingresar una o muchas guías internacionales. Presiona Enter o usa Agregar para incluir cada una.</div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <div class="fw-semibold">Guías seleccionadas</div>
                                    <div class="text-muted small">Cada guía internacional cargada quedará asociada a una sola guía nacional de salida.</div>
                                </div>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="groupExitAddGuide">
                                    <i class="fas fa-plus me-1"></i>Agregar
                                </button>
                            </div>
                            <div class="group-exit-list mt-3" id="groupExitList"></div>
                            <div class="alert alert-light border mt-3 mb-0 d-none" id="groupExitEmptyState">
                                Aún no hay guías agregadas para la salida nacional.
                            </div>
                        </div>
                        <div class="col-12 d-none" id="groupExitNationalStep">
                            <div class="group-exit-step">
                                <div class="fw-semibold mb-2">Guía nacional de salida</div>
                                <div class="text-muted small mb-3">Cuando termines de cargar las guías internacionales, asigna la guía nacional para cerrar la salida automáticamente.</div>
                                <label for="groupExitNationalGuide" class="form-label">Guía nacional *</label>
                                <input type="text" class="form-control text-uppercase" id="groupExitNationalGuide" maxlength="60" placeholder="Guía nacional consolidada">
                            </div>
                        </div>
                        <div class="col-12 d-none" id="groupExitNotesWrapper">
                            <label for="groupExitNotes" class="form-label">Observaciones</label>
                            <textarea class="form-control" id="groupExitNotes" rows="3" maxlength="2000" placeholder="Notas operativas para la salida nacional..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger d-none" id="groupExitSubmitButton">
                        <span class="spinner-border spinner-border-sm d-none me-2" id="groupExitSpinner"></span>
                        Hacer salida nacional
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="importWarehouseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1">Importaci&oacute;n masiva de gu&iacute;as</h5>
                    <small>Procesa ingresos y salidas desde Excel o CSV sin salir de bodega.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="import-card h-100">
                            <div class="import-card__header">
                                <div>
                                    <div class="import-card__kicker">Ingreso</div>
                                    <h5 class="mb-1">Cargar gu&iacute;as con localizaci&oacute;n</h5>
                                    <p class="mb-0">Usa las columnas <strong>GUIA</strong> y <strong>LOCALIZACION</strong>. La localizaci&oacute;n puede ser una ubicaci&oacute;n normal o <strong>ALMACENAMIENTO</strong>.</p>
                                </div>
                                <a href="{{ route('warehouse.templates.entries') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-download me-1"></i>Plantilla
                                </a>
                            </div>

                            <div class="import-card__format">
                                <span class="badge bg-light text-dark">CLIENTE</span>
                                <span class="badge bg-light text-dark">GUIA</span>
                                <span class="badge bg-light text-dark">LOCALIZACION</span>
                            </div>

                            <form action="{{ route('warehouse.import.entries') }}" method="POST" enctype="multipart/form-data" class="import-card__form">
                                @csrf
                                <div class="row g-3 mb-3">
                                    <div class="col-12">
                                        <label for="warehouseEntryImportCustomer" class="form-label">Cliente</label>
                                        <select class="form-select" id="warehouseEntryImportCustomer" name="customer" required>
                                            @foreach($importCustomerOptions as $option)
                                                <option value="{{ $option }}" {{ $option === $customer ? 'selected' : '' }}>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="warehouseEntryImportFile" class="form-label">Archivo de ingreso</label>
                                    <div class="import-dropzone" data-import-dropzone data-import-input="warehouseEntryImportFile" role="button" tabindex="0" aria-label="Seleccionar archivo de ingreso">
                                        <div class="import-dropzone__icon" aria-hidden="true">
                                            <i class="fas fa-file-upload"></i>
                                        </div>
                                        <div class="import-dropzone__title">Arrastra el archivo aquí</div>
                                        <div class="import-dropzone__copy">o haz clic para seleccionar un Excel o CSV</div>
                                        <div class="import-dropzone__meta" data-import-filename>Sin archivo seleccionado</div>
                                        <input type="file" class="form-control import-dropzone__input" id="warehouseEntryImportFile" name="file" accept=".xlsx,.xls,.csv,.txt" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-file-import me-2"></i>Importar ingresos
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="import-card h-100">
                            <div class="import-card__header">
                                <div>
                                    <div class="import-card__kicker">Salida</div>
                                    <h5 class="mb-1">Cerrar gu&iacute;as por archivo</h5>
                                    <p class="mb-0">Usa las columnas <strong>GUIA</strong> y <strong>GUIA_NACIONAL</strong>. Puedes repetir la misma gu&iacute;a nacional para varias gu&iacute;as internacionales.</p>
                                </div>
                                <a href="{{ route('warehouse.templates.exits') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-download me-1"></i>Plantilla
                                </a>
                            </div>

                            <div class="import-card__format">
                                <span class="badge bg-light text-dark">CLIENTE</span>
                                <span class="badge bg-light text-dark">GUIA</span>
                                <span class="badge bg-light text-dark">GUIA_NACIONAL</span>
                            </div>

                            <form action="{{ route('warehouse.import.exits') }}" method="POST" enctype="multipart/form-data" class="import-card__form">
                                @csrf
                                <div class="row g-3 mb-3">
                                    <div class="col-12">
                                        <label for="warehouseExitImportCustomer" class="form-label">Cliente</label>
                                        <select class="form-select" id="warehouseExitImportCustomer" name="customer" required>
                                            @foreach($importCustomerOptions as $option)
                                                <option value="{{ $option }}" {{ $option === $customer ? 'selected' : '' }}>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="warehouseExitImportFile" class="form-label">Archivo de salida</label>
                                    <div class="import-dropzone" data-import-dropzone data-import-input="warehouseExitImportFile" role="button" tabindex="0" aria-label="Seleccionar archivo de salida">
                                        <div class="import-dropzone__icon" aria-hidden="true">
                                            <i class="fas fa-file-export"></i>
                                        </div>
                                        <div class="import-dropzone__title">Arrastra el archivo aquí</div>
                                        <div class="import-dropzone__copy">o haz clic para seleccionar un Excel o CSV</div>
                                        <div class="import-dropzone__meta" data-import-filename>Sin archivo seleccionado</div>
                                        <input type="file" class="form-control import-dropzone__input" id="warehouseExitImportFile" name="file" accept=".xlsx,.xls,.csv,.txt" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-sign-out-alt me-2"></i>Importar salidas
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="reportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('warehouse.export') }}" method="GET">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1">Configurar reporte</h5>
                        <small>Filtra por fechas, tipo de operación y guía nacional antes de descargar el Excel.</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="reportStartDate" class="form-label">Desde</label>
                            <input
                                type="date"
                                class="form-control @error('start_date') is-invalid @enderror"
                                id="reportStartDate"
                                name="start_date"
                                value="{{ old('start_date', now()->startOfMonth()->format('Y-m-d')) }}"
                                required
                            >
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="reportEndDate" class="form-label">Hasta</label>
                            <input
                                type="date"
                                class="form-control @error('end_date') is-invalid @enderror"
                                id="reportEndDate"
                                name="end_date"
                                value="{{ old('end_date', now()->format('Y-m-d')) }}"
                                required
                            >
                            @error('end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="reportType" class="form-label">Tipo de operación</label>
                            <select
                                class="form-select @error('report_type') is-invalid @enderror"
                                id="reportType"
                                name="report_type"
                            >
                                <option value="all" {{ old('report_type', 'all') === 'all' ? 'selected' : '' }}>Ingresos y salidas</option>
                                <option value="entries" {{ old('report_type') === 'entries' ? 'selected' : '' }}>Solo ingresos</option>
                                <option value="exits" {{ old('report_type') === 'exits' ? 'selected' : '' }}>Solo salidas</option>
                            </select>
                            @error('report_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="reportWarehouse" class="form-label">Cliente bodega</label>
                            <select
                                class="form-select @error('warehouse') is-invalid @enderror"
                                id="reportWarehouse"
                                name="warehouse"
                            >
                                <option value="">Cliente bodega activo</option>
                                @foreach($warehouseOptions as $warehouse)
                                    <option value="{{ $warehouse }}" {{ old('warehouse', $activeWarehouse ?? '') === $warehouse ? 'selected' : '' }}>
                                        {{ $warehouse }}
                                    </option>
                                @endforeach
                            </select>
                            @error('warehouse')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="reportNationalGuide" class="form-label">Guía nacional</label>
                            <input
                                type="text"
                                class="form-control text-uppercase @error('national_guide') is-invalid @enderror"
                                id="reportNationalGuide"
                                name="national_guide"
                                value="{{ old('national_guide') }}"
                                maxlength="60"
                                placeholder="Filtrar una guía nacional específica"
                            >
                            @error('national_guide')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <div class="report-panel__helper">
                                El reporte exporta guía internacional, guía nacional, estado, ubicación, fechas, duración y responsables.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-file-excel me-2"></i>Generar Excel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const warehouseData = {
        locations: @json($locationOptions),
        locationCards: @json($locationCards),
        warehouses: @json($warehouseOptions),
        activeWarehouse: @json($activeWarehouse ?? null),
        permissions: {
            isSuperAdmin: @json($isSuperAdmin),
        },
        urls: {
            index: @json(route('warehouse.index')),
            guideBase: @json(url('/warehouse/guides')),
            guideStore: @json(route('warehouse.guides.store')),
            guideMove: @json(route('warehouse.guides.move')),
            guideExit: @json(route('warehouse.guides.exit')),
            guideExitGrouped: @json(route('warehouse.guides.exit-grouped')),
            locationStore: @json(route('warehouse.locations.store')),
            locationBase: @json(url('/warehouse/locations')),
        },
    };

    const guideEntryForm = document.getElementById('guideEntryForm');
    const guideEditForm = document.getElementById('guideEditForm');
    const moveGuideForm = document.getElementById('moveGuideForm');
    const exitGuideForm = document.getElementById('exitGuideForm');
    const groupExitForm = document.getElementById('groupExitForm');
    const locationForm = document.getElementById('locationForm');
    const groupExitState = {
        guides: [],
    };
    const guideEntryCaptureState = {
        startedAt: 0,
        lastAt: 0,
        keyCount: 0,
        autoDetected: false,
    };

    const guideEntryModalElement = document.getElementById('guideEntryModal');
    const guideEditModalElement = document.getElementById('guideEditModal');
    const moveGuideModalElement = document.getElementById('moveGuideModal');
    const exitGuideModalElement = document.getElementById('exitGuideModal');
    const groupExitModalElement = document.getElementById('groupExitModal');
    const locationModalElement = document.getElementById('locationModal');
    const locationDetailModalElement = document.getElementById('locationDetailModal');
    const timelineModalElement = document.getElementById('timelineModal');
    const reportModalElement = document.getElementById('reportModal');
    const guideEntryModal = guideEntryModalElement ? bootstrap.Modal.getOrCreateInstance(guideEntryModalElement) : null;
    const guideEditModal = guideEditModalElement ? bootstrap.Modal.getOrCreateInstance(guideEditModalElement) : null;
    const moveGuideModal = moveGuideModalElement ? bootstrap.Modal.getOrCreateInstance(moveGuideModalElement) : null;
    const exitGuideModal = exitGuideModalElement ? bootstrap.Modal.getOrCreateInstance(exitGuideModalElement) : null;
    const groupExitModal = groupExitModalElement ? bootstrap.Modal.getOrCreateInstance(groupExitModalElement) : null;
    const locationModal = locationModalElement ? bootstrap.Modal.getOrCreateInstance(locationModalElement) : null;
    const locationDetailModal = locationDetailModalElement ? bootstrap.Modal.getOrCreateInstance(locationDetailModalElement) : null;
    const timelineModal = timelineModalElement ? bootstrap.Modal.getOrCreateInstance(timelineModalElement) : null;
    const reportModal = reportModalElement ? bootstrap.Modal.getOrCreateInstance(reportModalElement) : null;
    const locationBoardSearch = document.getElementById('locationBoardSearch');
    const locationBoardSearchClear = document.getElementById('locationBoardSearchClear');
    const locationBoardGroups = Array.from(document.querySelectorAll('[data-warehouse-group]'));
    const locationBoardCards = Array.from(document.querySelectorAll('[data-location-card-wrapper]'));
    const locationBoardEmptyState = document.getElementById('locationBoardEmptyState');
    const importDropzones = Array.from(document.querySelectorAll('[data-import-dropzone]'));

    window.warehouseData = warehouseData;
    window.openGuideEntryModal = openGuideEntryModal;
    window.openGuideTimeline = openGuideTimeline;
    window.openGuideEditModal = openGuideEditModal;
    window.openMoveGuideModal = openMoveGuideModal;
    window.openExitGuideModal = openExitGuideModal;
    window.openGroupExitModal = openGroupExitModal;
    window.removeGuideFromGroupExit = removeGuideFromGroupExit;
    window.openLocationModal = openLocationModal;
    window.openLocationDetail = openLocationDetail;
    window.lookupGuide = lookupGuide;
    window.clearGuideLookup = clearGuideLookup;
    window.deleteGuide = deleteGuide;
    window.deleteLocation = deleteLocation;

    const guideEntryWarehouse = document.getElementById('guideEntryWarehouse');
    const guideEntryLocation = document.getElementById('guideEntryLocation');
    const guideEntryGuide = document.getElementById('guideEntryGuide');
    const guideEntrySource = document.getElementById('guideEntrySource');
    const guideEditWarehouse = document.getElementById('guideEditWarehouse');
    const guideEditLocation = document.getElementById('guideEditLocation');
    const moveGuideWarehouse = document.getElementById('moveGuideWarehouse');
    const moveGuideLocation = document.getElementById('moveGuideLocation');
    const moveGuideCurrentLocationId = document.getElementById('moveGuideCurrentLocationId');
    const locationStorage = document.getElementById('locationStorage');
    const locationCode = document.getElementById('locationCode');
    const guideLookupCode = document.getElementById('guideLookupCode');
    const guideLookupClear = document.getElementById('guideLookupClear');
    const guideTableRows = Array.from(document.querySelectorAll('[data-guide-row]'));
    const guideTableEmptyState = document.getElementById('guideTableEmptyState');

    if (guideEntryForm) guideEntryForm.addEventListener('submit', submitGuideEntry);
    if (guideEditForm) guideEditForm.addEventListener('submit', submitGuideEdit);
    if (moveGuideForm) moveGuideForm.addEventListener('submit', submitMoveGuide);
    if (exitGuideForm) exitGuideForm.addEventListener('submit', submitExitGuide);
    if (groupExitForm) groupExitForm.addEventListener('submit', submitGroupExit);
    if (locationForm) locationForm.addEventListener('submit', submitLocationForm);

    const groupExitGuideInput = document.getElementById('groupExitGuideInput');
    const groupExitAddGuide = document.getElementById('groupExitAddGuide');

    if (groupExitGuideInput) {
        groupExitGuideInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                addGuideToGroupExit();
            }
        });

        groupExitGuideInput.addEventListener('input', () => {
            groupExitGuideInput.value = groupExitGuideInput.value.toUpperCase();
        });
    }

    if (groupExitAddGuide) {
        groupExitAddGuide.addEventListener('click', addGuideToGroupExit);
    }


    if (guideEntryWarehouse && guideEntryLocation) {
        guideEntryWarehouse.addEventListener('change', () => {
            renderLocationSelect(
                guideEntryLocation,
                guideEntryWarehouse.value,
                null,
                guideEntryLocation.value,
                true,
                false
            );
        });
    }

    if (guideEntryGuide && guideEntryLocation) {
        guideEntryGuide.addEventListener('keydown', (event) => {
            trackGuideEntryCaptureKey(event);

            if (event.key === 'Enter') {
                maybeAutoAssignGuideEntryCapture();
                event.preventDefault();
                guideEntryLocation.focus();
            }
        });
    }

    if (guideEntryGuide && guideEntrySource) {
        guideEntryGuide.addEventListener('input', () => {
            guideEntryGuide.value = guideEntryGuide.value.toUpperCase();

            if (!guideEntryGuide.value.trim()) {
                guideEntrySource.value = 'manual';
                resetGuideEntryCaptureState();
                return;
            }

            maybeAutoAssignGuideEntryCapture();
        });
    }

    if (guideEditWarehouse && guideEditLocation) {
        guideEditWarehouse.addEventListener('change', () => {
            renderLocationSelect(
                guideEditLocation,
                guideEditWarehouse.value,
                null,
                guideEditLocation.value,
                true
            );
        });
    }

    if (moveGuideWarehouse && moveGuideLocation && moveGuideCurrentLocationId) {
        moveGuideWarehouse.addEventListener('change', () => {
            renderLocationSelect(
                moveGuideLocation,
                moveGuideWarehouse.value,
                moveGuideCurrentLocationId.value,
                moveGuideLocation.value,
                true
            );
        });
    }

    if (locationStorage) locationStorage.addEventListener('change', syncStorageLocationFields);
    if (locationCode) locationCode.addEventListener('input', handleLocationCodeInput);

    if (guideLookupCode) {
        guideLookupCode.addEventListener('input', applyGuideTableFilter);
        guideLookupCode.addEventListener('search', applyGuideTableFilter);
        applyGuideTableFilter();
    }

    if (guideLookupClear) {
        guideLookupClear.addEventListener('click', clearGuideLookup);
    }

    if (guideEntryWarehouse) renderWarehouseSelect(guideEntryWarehouse);
    if (moveGuideWarehouse) renderWarehouseSelect(moveGuideWarehouse);

    if (guideEntryLocation) renderLocationSelect(guideEntryLocation, '', null, '', true, false);
    if (moveGuideLocation) renderLocationSelect(moveGuideLocation, '', null, '', true);
    initImportDropzones();
    initTooltips();
    applyLocationBoardFilter();

    if (locationBoardSearch) {
        locationBoardSearch.addEventListener('input', applyLocationBoardFilter);
        locationBoardSearch.addEventListener('search', applyLocationBoardFilter);
    }

    if (locationBoardSearchClear) {
        locationBoardSearchClear.addEventListener('click', resetLocationBoardFilter);
    }

    @if($errors->has('start_date') || $errors->has('end_date') || $errors->has('report_type') || $errors->has('national_guide') || $errors->has('warehouse'))
        if (reportModal) {
            reportModal.show();
        }
    @endif

    syncStorageLocationFields();

    document.querySelectorAll('#warehouseTabs [data-bs-toggle="pill"]').forEach((tabButton) => {
        tabButton.addEventListener('shown.bs.tab', (event) => {
            const target = event.target.getAttribute('data-bs-target');
            const tabName = target === '#guidesPane' ? 'guides' : (target === '#locationsPane' ? 'locations' : 'reports');
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('tab', tabName);
            window.history.replaceState({}, '', currentUrl);
        });
    });

    function renderWarehouseSelect(selectElement, placeholder = 'Seleccione una bodega') {
        const currentValue = selectElement.value;
        selectElement.innerHTML = `<option value="">${placeholder}</option>`;

        warehouseData.warehouses.forEach((warehouse) => {
            const option = document.createElement('option');
            option.value = warehouse;
            option.textContent = warehouse;
            selectElement.appendChild(option);
        });

        if (currentValue) {
            selectElement.value = currentValue;
        }
    }

    function renderLocationSelect(selectElement, warehouse, excludeLocationId = null, selectedValue = '', onlyActive = false, autoSelectFirst = true) {
        const currentValue = selectedValue || selectElement.value;
        const locations = warehouseData.locations.filter((location) => {
            const byWarehouse = !warehouse || location.warehouse === warehouse;
            const byExclude = !excludeLocationId || String(location.location_id) !== String(excludeLocationId);
            const byActive = !onlyActive || location.is_active !== false;
            return byWarehouse && byExclude && byActive;
        });

        selectElement.innerHTML = '<option value="">Seleccione una ubicación</option>';

        locations.forEach((location) => {
            const option = document.createElement('option');
            option.value = location.location_id;
            option.textContent = location.label + (location.is_storage ? ' (Almacenamiento)' : '');
            selectElement.appendChild(option);
        });

        if (currentValue) {
            selectElement.value = currentValue;
        }

        if (!selectElement.value && autoSelectFirst && locations.length > 0) {
            selectElement.value = locations[0].location_id;
        }
    }

    function findGuideLocation(guideData) {
        if (!guideData) {
            return null;
        }

        if (guideData.current_location_id) {
            return warehouseData.locations.find((location) => String(location.location_id) === String(guideData.current_location_id));
        }

        return warehouseData.locations.find((location) => location.code === guideData.current_location_code);
    }

    function findStorageLocation() {
        const selectedWarehouse = document.getElementById('locationWarehouse')?.value || '';
        return warehouseData.locations.find((location) => {
            const isStorage = location.code === 'ALMACENAMIENTO';
            return isStorage && (!selectedWarehouse || location.warehouse === selectedWarehouse);
        });
    }

    function setLoading(button, spinner, loading) {
        if (!button) {
            return;
        }

        button.disabled = loading;
        if (spinner) {
            spinner.classList.toggle('d-none', !loading);
        }
    }

    function showSuccess(message) {
        Swal.fire({
            icon: 'success',
            title: 'Éxito',
            text: message,
            timer: 2600,
            showConfirmButton: false,
        });
    }

    function showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: message,
        });
    }

    function initTooltips(root = document) {
        if (!window.bootstrap || !bootstrap.Tooltip) {
            return;
        }

        root.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
            bootstrap.Tooltip.getOrCreateInstance(element);
        });
    }

    function normalizeWarehouseSearch(value) {
        return String(value ?? '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();
    }

    function applyGuideTableFilter() {
        if (!guideLookupCode || !guideTableRows.length) {
            return;
        }

        const query = normalizeWarehouseSearch(guideLookupCode.value);
        let visibleRows = 0;

        guideTableRows.forEach((row) => {
            const searchValue = normalizeWarehouseSearch(row.dataset.guideSearch || '');
            const matches = !query || searchValue.includes(query);
            row.classList.toggle('d-none', !matches);

            if (matches) {
                visibleRows += 1;
            }
        });

        if (guideTableEmptyState) {
            guideTableEmptyState.classList.toggle('d-none', visibleRows > 0);
        }
    }

    function applyLocationBoardFilter() {
        if (!locationBoardSearch || !locationBoardCards.length) {
            return;
        }

        const query = normalizeWarehouseSearch(locationBoardSearch.value);
        let visibleCards = 0;
        const groupVisibility = new Map();

        locationBoardGroups.forEach((group) => {
            groupVisibility.set(group, false);
        });

        locationBoardCards.forEach((card) => {
            const searchValue = normalizeWarehouseSearch(card.dataset.locationSearch);
            const matches = !query || searchValue.includes(query);
            card.classList.toggle('d-none', !matches);

            if (matches) {
                visibleCards += 1;
                const group = card.closest('[data-warehouse-group]');
                if (group) {
                    groupVisibility.set(group, true);
                }
            }
        });

        locationBoardGroups.forEach((group) => {
            group.classList.toggle('d-none', !groupVisibility.get(group));
        });

        if (locationBoardEmptyState) {
            locationBoardEmptyState.classList.toggle('d-none', visibleCards > 0);
        }
    }

    function resetLocationBoardFilter() {
        if (!locationBoardSearch) {
            return;
        }

        locationBoardSearch.value = '';
        applyLocationBoardFilter();
        locationBoardSearch.focus();
    }

    function initImportDropzones() {
        importDropzones.forEach((dropzone) => {
            const inputId = dropzone.getAttribute('data-import-input');
            const input = inputId ? document.getElementById(inputId) : null;
            const fileName = dropzone.querySelector('[data-import-filename]');

            if (!input || !fileName) {
                return;
            }

            const syncFileName = () => {
                const selectedFile = input.files && input.files[0] ? input.files[0].name : 'Sin archivo seleccionado';
                fileName.textContent = selectedFile;
            };

            dropzone.addEventListener('click', (event) => {
                const target = event.target;
                if (target instanceof HTMLElement && target.closest('a, button')) {
                    return;
                }

                input.click();
            });

            dropzone.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    input.click();
                }
            });

            ['dragenter', 'dragover'].forEach((eventName) => {
                dropzone.addEventListener(eventName, (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    dropzone.classList.add('is-dragover');
                });
            });

            ['dragleave', 'dragend', 'drop'].forEach((eventName) => {
                dropzone.addEventListener(eventName, (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    if (eventName !== 'drop') {
                        dropzone.classList.remove('is-dragover');
                    }
                });
            });

            dropzone.addEventListener('drop', (event) => {
                const droppedFiles = event.dataTransfer ? event.dataTransfer.files : null;
                dropzone.classList.remove('is-dragover');

                if (!droppedFiles || !droppedFiles.length) {
                    return;
                }

                input.files = droppedFiles;
                syncFileName();
            });

            input.addEventListener('change', syncFileName);
            syncFileName();
        });
    }

    async function confirmAction(title, text) {
        const result = await Swal.fire({
            title,
            text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar',
        });

        return result.isConfirmed;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function isSuperAdmin() {
        return Boolean(warehouseData.permissions && warehouseData.permissions.isSuperAdmin);
    }

    function resetGuideEntryCaptureState() {
        guideEntryCaptureState.startedAt = 0;
        guideEntryCaptureState.lastAt = 0;
        guideEntryCaptureState.keyCount = 0;
        guideEntryCaptureState.autoDetected = false;
    }

    function trackGuideEntryCaptureKey(event) {
        const key = event.key;
        const allowedKeys = ['Backspace', 'Delete', 'Enter', 'Tab'];

        if (!key || (key.length !== 1 && !allowedKeys.includes(key))) {
            return;
        }

        const now = performance.now();

        if (!guideEntryCaptureState.startedAt || (now - guideEntryCaptureState.lastAt) > 450) {
            guideEntryCaptureState.startedAt = now;
            guideEntryCaptureState.keyCount = 0;
            guideEntryCaptureState.autoDetected = false;
        }

        if (key.length === 1) {
            guideEntryCaptureState.keyCount += 1;
        }

        guideEntryCaptureState.lastAt = now;
    }

    function maybeAutoAssignGuideEntryCapture() {
        const guideInput = document.getElementById('guideEntryGuide');
        const sourceSelect = document.getElementById('guideEntrySource');

        if (!guideInput || !sourceSelect || sourceSelect.value === 'barcode' || guideEntryCaptureState.autoDetected) {
            return;
        }

        const guideValue = guideInput.value.trim().toUpperCase();
        guideInput.value = guideValue;

        if (!/^GL[0-9]{9}CO$/i.test(guideValue)) {
            return;
        }

        const elapsed = guideEntryCaptureState.startedAt
            ? performance.now() - guideEntryCaptureState.startedAt
            : Number.POSITIVE_INFINITY;
        const averageInterval = guideEntryCaptureState.keyCount > 0 ? elapsed / guideEntryCaptureState.keyCount : Number.POSITIVE_INFINITY;
        const rapidSequence = guideEntryCaptureState.keyCount >= 10 && elapsed <= 1400 && averageInterval <= 65;

        if (rapidSequence) {
            sourceSelect.value = 'barcode';
            guideEntryCaptureState.autoDetected = true;
        }
    }

    function syncStorageLocationFields() {
        const storageToggle = document.getElementById('locationStorage');
        const codeInput = document.getElementById('locationCode');
        const warehouseInput = document.getElementById('locationWarehouse');
        const isStorage = storageToggle.checked;

        if (isStorage) {
            codeInput.value = 'ALMACENAMIENTO';
            warehouseInput.value = warehouseData.activeWarehouse || warehouseInput.value;
            codeInput.readOnly = true;
        } else {
            codeInput.readOnly = false;
            warehouseInput.readOnly = true;
            warehouseInput.value = warehouseData.activeWarehouse || warehouseInput.value;
        }
    }

    function handleLocationCodeInput() {
        const codeInput = document.getElementById('locationCode');
        const storageToggle = document.getElementById('locationStorage');
        const value = codeInput.value.trim().toUpperCase();

        codeInput.value = value;

        if (value === 'ALMACENAMIENTO') {
            storageToggle.checked = true;
            syncStorageLocationFields();
        } else if (!storageToggle.checked) {
            codeInput.readOnly = false;
            document.getElementById('locationWarehouse').readOnly = true;
        }
    }

    function resetGuideEntryForm() {
        guideEntryForm.reset();
        resetGuideEntryCaptureState();
        document.getElementById('guideEntryGuide').value = '';
        document.getElementById('guideEntrySource').value = 'manual';
        document.getElementById('guideEntryNotes').value = '';
        document.getElementById('guideEntryWarehouse').value = warehouseData.activeWarehouse || '';
        renderLocationSelect(
            document.getElementById('guideEntryLocation'),
            warehouseData.activeWarehouse || '',
            null,
            '',
            true,
            false
        );
        document.getElementById('guideEntryGuide').focus();
    }

    function openGuideEntryModal(guideCode = '', entrySource = null) {
        resetGuideEntryForm();
        if (guideCode) {
            const source = entrySource || 'barcode';
            document.getElementById('guideEntryGuide').value = String(guideCode).trim().toUpperCase();
            document.getElementById('guideEntrySource').value = source === 'barcode' ? 'barcode' : 'manual';
            document.getElementById('guideEntryLocation').focus();
        }
    }

    async function submitGuideEntry(event) {
        event.preventDefault();

        const submitButton = event.target.querySelector('button[type="submit"]');
        const spinner = document.getElementById('guideEntrySpinner');
        const guide = document.getElementById('guideEntryGuide').value.trim().toUpperCase();
        const locationId = document.getElementById('guideEntryLocation').value;

        if (!guide || !locationId) {
            showError('Debe completar la guía y la ubicación de ingreso.');
            return;
        }

        setLoading(submitButton, spinner, true);

        try {
            const response = await fetch(warehouseData.urls.guideStore, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    guide,
                    location_id: locationId,
                    entry_source: document.getElementById('guideEntrySource').value,
                    notes: document.getElementById('guideEntryNotes').value,
                }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || data.error || 'No fue posible registrar el ingreso.');
            }

            showSuccess(data.message || 'Ingreso registrado correctamente.');
            guideEntryModal.hide();
            window.location.reload();
        } catch (error) {
            showError(error.message);
        } finally {
            setLoading(submitButton, spinner, false);
        }
    }

    function updateGuideEditHint(canEditLocation) {
        const hint = document.getElementById('guideEditHint');

        if (!hint) {
            return;
        }

        hint.textContent = canEditLocation
            ? 'Puedes corregir la ubicación inicial, el código, la captura y las observaciones.'
            : 'Esta guía ya tiene movimientos o salida registrada. Solo puedes corregir el código, la captura y las observaciones.';
    }

    async function openGuideEditModal(guideCode) {
        if (!isSuperAdmin()) {
            showError('Acceso denegado. Solo SUPER_ADMIN puede editar ingresos.');
            return;
        }

        try {
            const data = await fetchGuide(guideCode);
            const guide = data.guide || {};
            const currentWarehouse = guide.warehouse || '';
            const canEditLocation = guide.status !== 'EXITED' && !guide.exit_at && Number(guide.movements_count ?? 0) <= 1;

            document.getElementById('guideEditOriginalGuide').value = guide.guide || '';
            document.getElementById('guideEditGuide').value = guide.guide || '';
            document.getElementById('guideEditSource').value = guide.entry_source || 'manual';
            document.getElementById('guideEditNotes').value = guide.notes || '';

            renderWarehouseSelect(document.getElementById('guideEditWarehouse'));
            document.getElementById('guideEditWarehouse').value = currentWarehouse;

            renderLocationSelect(
                document.getElementById('guideEditLocation'),
                currentWarehouse,
                null,
                guide.current_location_id || '',
                true
            );

            document.getElementById('guideEditWarehouse').disabled = !canEditLocation;
            document.getElementById('guideEditLocation').disabled = !canEditLocation;
            updateGuideEditHint(canEditLocation);

            guideEditModal.show();
        } catch (error) {
            showError(error.message);
        }
    }

    async function submitGuideEdit(event) {
        event.preventDefault();

        if (!isSuperAdmin()) {
            showError('Acceso denegado. Solo SUPER_ADMIN puede editar ingresos.');
            return;
        }

        const submitButton = event.target.querySelector('button[type="submit"]');
        const spinner = document.getElementById('guideEditSpinner');
        const originalGuide = document.getElementById('guideEditOriginalGuide').value.trim();
        const guide = document.getElementById('guideEditGuide').value.trim().toUpperCase();

        if (!originalGuide || !guide) {
            showError('Debe indicar la guía.');
            return;
        }

        const payload = {
            guide,
            entry_source: document.getElementById('guideEditSource').value,
            notes: document.getElementById('guideEditNotes').value,
        };

        const locationSelect = document.getElementById('guideEditLocation');
        if (locationSelect && !locationSelect.disabled && locationSelect.value) {
            payload.location_id = locationSelect.value;
        }

        setLoading(submitButton, spinner, true);

        try {
            const response = await fetch(`${warehouseData.urls.guideBase}/${encodeURIComponent(originalGuide)}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || data.error || 'No fue posible guardar el ingreso.');
            }

            showSuccess(data.message || 'Ingreso actualizado correctamente.');
            guideEditModal.hide();
            window.location.reload();
        } catch (error) {
            showError(error.message);
        } finally {
            setLoading(submitButton, spinner, false);
        }
    }

    async function deleteGuide(guideCode) {
        if (!isSuperAdmin()) {
            showError('Acceso denegado. Solo SUPER_ADMIN puede eliminar ingresos.');
            return;
        }

        try {
            const data = await fetchGuide(guideCode);
            const guide = data.guide || {};
            const confirmed = await confirmAction(
                '¿Eliminar ingreso?',
                `La guía "${guide.guide || guideCode}" y toda su trazabilidad serán eliminadas.`
            );

            if (!confirmed) {
                return;
            }

            const response = await fetch(`${warehouseData.urls.guideBase}/${encodeURIComponent(guide.guide || guideCode)}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || result.error || 'No fue posible eliminar el ingreso.');
            }

            showSuccess(result.message || 'Ingreso eliminado correctamente.');
            window.location.reload();
        } catch (error) {
            showError(error.message);
        }
    }

    async function fetchGuide(guideCode) {
        const response = await fetch(`${warehouseData.urls.guideBase}/${encodeURIComponent(guideCode)}`, {
            headers: {
                'Accept': 'application/json',
            },
        });

        const contentType = response.headers.get('content-type') || '';
        const data = contentType.includes('application/json')
            ? await response.json()
            : { message: await response.text() };

        if (!response.ok) {
            throw new Error(data.message || data.error || 'No fue posible consultar la guía.');
        }

        return data;
    }

    function renderTimeline(data) {
        const guide = data.guide || {};
        const movements = data.movements || [];

        document.getElementById('timelineGuideCode').textContent = guide.guide || '-';
        document.getElementById('timelineGuideStatus').textContent = guide.status_label || '-';
        document.getElementById('timelineGuideStatus').className = `badge ${guide.status_badge_class || 'bg-secondary'}`;
        document.getElementById('timelineGuideLocation').textContent = guide.current_location_label || '-';
        document.getElementById('timelineGuideWarehouse').textContent = guide.warehouse || '-';
        document.getElementById('timelineGuideDuration').textContent = guide.duration_label || '-';
        document.getElementById('timelineGuideUsers').textContent = `Ingreso: ${guide.entry_user || 'N/A'} | Salida: ${guide.exit_user || 'N/A'}`;
        document.getElementById('timelineGuideNotes').textContent = guide.notes || 'Sin observaciones.';
        document.getElementById('timelineGuideEntry').textContent = guide.entry_at || '-';
        document.getElementById('timelineGuideExit').textContent = guide.exit_at || 'En curso';
        document.getElementById('timelineGuideSource').textContent = guide.entry_source ? String(guide.entry_source).toUpperCase() : '-';
        document.getElementById('timelineGuideCount').textContent = guide.movements_count ?? movements.length;

        if (guide.national_guide) {
            document.getElementById('timelineGuideNotes').textContent = `Guía nacional: ${guide.national_guide}${guide.notes ? ' | ' + guide.notes : ''}`;
        }

        const actionButtons = document.getElementById('timelineActionButtons');
        const adminButtons = isSuperAdmin()
            ? `
                <button type="button" class="btn btn-sm btn-outline-secondary warehouse-action-btn" data-bs-toggle="tooltip" title="Editar ingreso" aria-label="Editar ingreso" onclick="openGuideEditModal('${escapeHtml(guide.guide)}')">
                    <i class="fas fa-pen"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger warehouse-action-btn" data-bs-toggle="tooltip" title="Eliminar ingreso" aria-label="Eliminar ingreso" onclick="deleteGuide('${escapeHtml(guide.guide)}')">
                    <i class="fas fa-trash"></i>
                </button>
            `
            : '';

        if (guide.status === 'EXITED' || guide.exit_at) {
            actionButtons.innerHTML = `${adminButtons}<span class="badge bg-secondary align-self-center">Guía cerrada</span>`;
        } else {
            actionButtons.innerHTML = `
                ${adminButtons}
                <button type="button" class="btn btn-sm btn-outline-primary warehouse-action-btn" data-bs-toggle="tooltip" title="Mover ubicación" aria-label="Mover ubicación" onclick="openMoveGuideModal('${escapeHtml(guide.guide)}')">
                    <i class="fas fa-exchange-alt"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger warehouse-action-btn" data-bs-toggle="tooltip" title="Registrar salida" aria-label="Registrar salida" onclick="openExitGuideModal('${escapeHtml(guide.guide)}')">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            `;
        }

        initTooltips(actionButtons);

        const movementsContainer = document.getElementById('timelineMovements');
        if (!movements.length) {
            movementsContainer.innerHTML = '<div class="text-muted">Sin movimientos registrados.</div>';
            return;
        }

        movementsContainer.innerHTML = movements.map((movement) => `
            <div class="timeline-item">
                <span class="timeline-dot"></span>
                <div class="timeline-card">
                    <div class="timeline-card__head">
                        <div>
                            <h6 class="timeline-card__title">${escapeHtml(movement.action_label || movement.action)}</h6>
                            <div class="timeline-card__meta">${escapeHtml(movement.performed_at || '')}${movement.user ? ' - ' + escapeHtml(movement.user) : ''}</div>
                        </div>
                        <span class="badge ${escapeHtml(movement.action_badge_class || 'bg-secondary')}">${escapeHtml(movement.action_label || movement.action)}</span>
                    </div>
                    <div class="small text-muted">
                        ${movement.from_location_label ? `<div><strong>Desde:</strong> ${escapeHtml(movement.from_location_label)}</div>` : ''}
                        ${movement.to_location_label ? `<div><strong>Hacia:</strong> ${escapeHtml(movement.to_location_label)}</div>` : ''}
                        ${movement.notes ? `<div><strong>Notas:</strong> ${escapeHtml(movement.notes)}</div>` : ''}
                    </div>
                </div>
            </div>
        `).join('');
    }

    async function openGuideTimeline(guideCode) {
        try {
            const data = await fetchGuide(guideCode);
            renderTimeline(data);
            timelineModal.show();
        } catch (error) {
            showError(error.message);
        }
    }

    async function lookupGuide() {
        applyGuideTableFilter();
    }

    function clearGuideLookup() {
        const guideLookupCode = document.getElementById('guideLookupCode');

        if (guideLookupCode) {
            guideLookupCode.value = '';
            applyGuideTableFilter();
            guideLookupCode.focus();
        }
    }

    function renderGroupExitList() {
        const list = document.getElementById('groupExitList');
        const emptyState = document.getElementById('groupExitEmptyState');

        if (!list || !emptyState) {
            return;
        }

        if (!groupExitState.guides.length) {
            list.innerHTML = '';
            emptyState.classList.remove('d-none');
            updateGroupExitStepState();
            return;
        }

        emptyState.classList.add('d-none');
        list.innerHTML = groupExitState.guides.map((guide) => `
            <div class="group-exit-item">
                <div>
                    <div class="group-exit-item__code">${escapeHtml(guide.guide)}</div>
                    <div class="group-exit-item__meta">${escapeHtml(guide.current_location_label || '-')} · ${escapeHtml(guide.duration_label || '-')}</div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger warehouse-action-btn" onclick="removeGuideFromGroupExit('${escapeHtml(guide.guide)}')" aria-label="Quitar guía">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `).join('');
        updateGroupExitStepState();
    }

    function resetGroupExitForm() {
        if (groupExitForm) {
            groupExitForm.reset();
        }

        groupExitState.guides = [];
        renderGroupExitList();

        const guideInput = document.getElementById('groupExitGuideInput');
        if (guideInput) {
            guideInput.value = '';
        }
    }

    function openGroupExitModal(prefilledGuide = null) {
        resetGroupExitForm();

        if (prefilledGuide) {
            groupExitState.guides = [prefilledGuide];
            renderGroupExitList();
        }

        const guideInput = document.getElementById('groupExitGuideInput');
        if (guideInput) {
            guideInput.focus();
        }
    }

    function removeGuideFromGroupExit(guideCode) {
        groupExitState.guides = groupExitState.guides.filter((guide) => guide.guide !== guideCode);
        renderGroupExitList();
    }

    function updateGroupExitStepState() {
        const nationalStep = document.getElementById('groupExitNationalStep');
        const notesWrapper = document.getElementById('groupExitNotesWrapper');
        const submitButton = document.getElementById('groupExitSubmitButton');
        const hasGuides = groupExitState.guides.length > 0;

        if (nationalStep) {
            nationalStep.classList.toggle('d-none', !hasGuides);
        }

        if (notesWrapper) {
            notesWrapper.classList.toggle('d-none', !hasGuides);
        }

        if (submitButton) {
            submitButton.classList.toggle('d-none', !hasGuides);
        }
    }

    async function addGuideToGroupExit() {
        const guideInput = document.getElementById('groupExitGuideInput');

        if (!guideInput) {
            return;
        }

        const guideCode = guideInput.value.trim().toUpperCase();
        if (!guideCode) {
            return;
        }

        if (groupExitState.guides.some((guide) => guide.guide === guideCode)) {
            showError('La guia ya fue agregada a la salida nacional.');
            guideInput.focus();
            guideInput.select();
            return;
        }

        try {
            const data = await fetchGuide(guideCode);
            const guide = data.guide || {};

            if (guide.status === 'EXITED' || guide.exit_at) {
                showError('La guia ya tiene salida registrada.');
                return;
            }

            groupExitState.guides.push(guide);
            renderGroupExitList();
            guideInput.value = '';
            guideInput.focus();
        } catch (error) {
            showError(error.message);
        }
    }

    async function openMoveGuideModal(guideCode) {
        try {
            const data = await fetchGuide(guideCode);
            const guide = data.guide || {};

            if (guide.status === 'EXITED' || guide.exit_at) {
                showError('La guía ya tiene salida registrada.');
                return;
            }

            document.getElementById('moveGuideCode').value = guide.guide || '';
            document.getElementById('moveGuideCurrentLocationId').value = guide.current_location_id || '';
            document.getElementById('moveGuideCurrentLocation').value = guide.current_location_label || '';
            document.getElementById('moveGuideNotes').value = '';

            const currentWarehouse = guide.warehouse || '';
            document.getElementById('moveGuideWarehouse').value = currentWarehouse;
            renderLocationSelect(
                document.getElementById('moveGuideLocation'),
                currentWarehouse,
                guide.current_location_id || null,
                '',
                true
            );

            moveGuideModal.show();
        } catch (error) {
            showError(error.message);
        }
    }

    async function submitMoveGuide(event) {
        event.preventDefault();

        const submitButton = event.target.querySelector('button[type="submit"]');
        const spinner = document.getElementById('moveGuideSpinner');
        const guideCode = document.getElementById('moveGuideCode').value.trim();
        const locationId = document.getElementById('moveGuideLocation').value;

        if (!guideCode || !locationId) {
            showError('Debe seleccionar una guía y una nueva ubicación.');
            return;
        }

        setLoading(submitButton, spinner, true);

        try {
            const response = await fetch(warehouseData.urls.guideMove, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    guide: guideCode,
                    location_id: locationId,
                    notes: document.getElementById('moveGuideNotes').value,
                }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || data.error || 'No fue posible mover la guía.');
            }

            showSuccess(data.message || 'Guía actualizada correctamente.');
            moveGuideModal.hide();
            window.location.reload();
        } catch (error) {
            showError(error.message);
        } finally {
            setLoading(submitButton, spinner, false);
        }
    }

    async function openExitGuideModal(guideCode) {
        try {
            const data = await fetchGuide(guideCode);
            const guide = data.guide || {};

            if (guide.status === 'EXITED' || guide.exit_at) {
                showError('La guía ya tiene salida registrada.');
                return;
            }

            document.getElementById('exitGuideCode').value = guide.guide || '';
            document.getElementById('exitGuideCurrentLocation').value = guide.current_location_label || '';
            document.getElementById('exitGuideNationalGuide').value = guide.national_guide || '';
            document.getElementById('exitGuideNotes').value = '';
            exitGuideModal.show();
        } catch (error) {
            showError(error.message);
        }
    }

    async function submitExitGuide(event) {
        event.preventDefault();

        const submitButton = event.target.querySelector('button[type="submit"]');
        const spinner = document.getElementById('exitGuideSpinner');
        const guideCode = document.getElementById('exitGuideCode').value.trim();
        const nationalGuide = document.getElementById('exitGuideNationalGuide').value.trim().toUpperCase();

        if (!guideCode || !nationalGuide) {
            showError('Debe indicar la guia y la guia nacional.');
            return;
        }

        setLoading(submitButton, spinner, true);

        try {
            const response = await fetch(warehouseData.urls.guideExit, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    guide: guideCode,
                    national_guide: nationalGuide,
                    notes: document.getElementById('exitGuideNotes').value,
                }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || data.error || 'No fue posible registrar la salida.');
            }

            showSuccess(data.message || 'Salida registrada correctamente.');
            exitGuideModal.hide();
            window.location.reload();
        } catch (error) {
            showError(error.message);
        } finally {
            setLoading(submitButton, spinner, false);
        }
    }

    async function submitGroupExit(event) {
        event.preventDefault();

        const submitButton = event.target.querySelector('button[type="submit"]');
        const spinner = document.getElementById('groupExitSpinner');
        const nationalGuideInput = document.getElementById('groupExitNationalGuide');
        const nationalGuide = nationalGuideInput ? nationalGuideInput.value.trim().toUpperCase() : '';

        if (!nationalGuide || !groupExitState.guides.length) {
            showError('Debe indicar la guía nacional y agregar al menos una guía internacional.');
            return;
        }

        setLoading(submitButton, spinner, true);

        try {
            const response = await fetch(warehouseData.urls.guideExitGrouped, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    guides: groupExitState.guides.map((guide) => guide.guide),
                    national_guide: nationalGuide,
                    notes: document.getElementById('groupExitNotes').value,
                }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || data.error || 'No fue posible registrar la salida nacional.');
            }

            showSuccess(data.message || 'salida nacional registrada correctamente.');
            if (groupExitModal) {
                groupExitModal.hide();
            }
            window.location.reload();
        } catch (error) {
            showError(error.message);
        } finally {
            setLoading(submitButton, spinner, false);
        }
    }

    function openLocationDetail(locationId) {
        const location = warehouseData.locationCards.find((item) => String(item.location_id) === String(locationId));

        if (!location) {
            showError('No se encontró la ubicación seleccionada.');
            return;
        }

        document.getElementById('locationDetailTitle').textContent = `${location.code || 'Ubicación'} - ${location.name || ''}`.trim();
        document.getElementById('locationDetailSubtitle').textContent = location.description || 'Sin descripción registrada.';
        document.getElementById('locationDetailWarehouse').textContent = location.warehouse || '-';
        document.getElementById('locationDetailState').textContent = location.is_active ? 'Activa' : 'Inactiva';
        document.getElementById('locationDetailType').textContent = location.is_storage ? 'Almacenamiento' : 'Normal';
        document.getElementById('locationDetailCount').textContent = `${Number(location.active_guides_count || 0)} cajas`;
        document.getElementById('locationDetailBoxes').textContent = Number(location.active_guides_count || 0);

        const guidesContainer = document.getElementById('locationDetailGuides');
        const guides = Array.isArray(location.guides) ? location.guides : [];

        if (!guides.length) {
            guidesContainer.innerHTML = `
                <div class="location-detail-empty">
                    <div class="location-detail-empty__icon">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <div>
                        <div class="fw-bold">Sin cajas en esta ubicación</div>
                        <div class="text-muted small">El espacio está disponible para recibir carga.</div>
                    </div>
                </div>
            `;
        } else {
            guidesContainer.innerHTML = guides.map((guide) => `
                <article class="location-detail-guide">
                    <div class="location-detail-guide__head">
                        <div>
                            <div class="location-detail-guide__code">${escapeHtml(guide.guide)}</div>
                            <div class="location-detail-guide__meta">${escapeHtml(guide.entry_at || 'Sin fecha de ingreso')}</div>
                        </div>
                        <span class="badge ${escapeHtml(guide.status_badge_class || 'bg-secondary')}">${escapeHtml(guide.status_label || 'Sin estado')}</span>
                    </div>

                    <div class="location-detail-guide__details">
                        <span class="badge bg-light text-dark">${escapeHtml(guide.duration_label || '-')} en bodega</span>
                        <span class="guide-tile__source">Ingreso ${escapeHtml(String(guide.entry_source || 'manual').toUpperCase())}</span>
                    </div>

                    <div class="guide-tile__actions">
                        <button type="button" class="btn btn-sm btn-outline-dark warehouse-action-btn" data-bs-toggle="tooltip" title="Ver trazabilidad" aria-label="Ver trazabilidad" onclick="openGuideTimeline('${escapeHtml(guide.guide)}')">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary warehouse-action-btn" data-bs-toggle="tooltip" title="Mover ubicación" aria-label="Mover ubicación" onclick="openMoveGuideModal('${escapeHtml(guide.guide)}')">
                            <i class="fas fa-exchange-alt"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger warehouse-action-btn" data-bs-toggle="tooltip" title="Registrar salida" aria-label="Registrar salida" onclick="openExitGuideModal('${escapeHtml(guide.guide)}')">
                            <i class="fas fa-sign-out-alt"></i>
                        </button>
                    </div>
                </article>
            `).join('');

            initTooltips(guidesContainer);
        }

        locationDetailModal.show();
    }

    function openLocationModal(locationId = null) {
        const form = document.getElementById('locationForm');
        const modalTitle = document.getElementById('locationModalTitle');
        const formId = document.getElementById('locationFormId');

        form.reset();
        formId.value = '';
        modalTitle.textContent = 'Nueva ubicación';
        document.getElementById('locationActive').checked = true;
        document.getElementById('locationStorage').checked = false;
        document.getElementById('locationCode').readOnly = false;
        document.getElementById('locationWarehouse').readOnly = true;
        document.getElementById('locationWarehouse').value = warehouseData.activeWarehouse || '';

        if (locationId) {
            const location = warehouseData.locations.find((item) => String(item.location_id) === String(locationId));
            if (!location) {
                showError('No se encontró la ubicación seleccionada.');
                return;
            }

            formId.value = location.location_id;
            modalTitle.textContent = 'Editar ubicación';
            document.getElementById('locationCode').value = location.code || '';
            document.getElementById('locationName').value = location.name || '';
            document.getElementById('locationWarehouse').value = location.warehouse || '';
            document.getElementById('locationDescription').value = location.description || '';
            document.getElementById('locationActive').checked = Boolean(location.is_active);
            document.getElementById('locationStorage').checked = Boolean(location.is_storage || location.code === 'ALMACENAMIENTO');
        } else if (findStorageLocation()) {
            const storage = findStorageLocation();
            document.getElementById('locationCode').value = '';
            document.getElementById('locationWarehouse').value = warehouseData.activeWarehouse || storage.warehouse || '';
        }

        syncStorageLocationFields();
        locationModal.show();
    }

    async function submitLocationForm(event) {
        event.preventDefault();

        const submitButton = event.target.querySelector('button[type="submit"]');
        const spinner = document.getElementById('locationSpinner');
        const locationId = document.getElementById('locationFormId').value.trim();
        const payload = {
            code: document.getElementById('locationCode').value.trim().toUpperCase(),
            name: document.getElementById('locationName').value.trim(),
            warehouse: warehouseData.activeWarehouse || document.getElementById('locationWarehouse').value.trim().toUpperCase(),
            customer: warehouseData.activeWarehouse || '',
            description: document.getElementById('locationDescription').value.trim(),
            is_active: document.getElementById('locationActive').checked,
            is_storage: document.getElementById('locationStorage').checked,
        };

        if (!payload.code || !payload.name || !payload.warehouse) {
            showError('Complete código, nombre y bodega.');
            return;
        }

        setLoading(submitButton, spinner, true);

        try {
            const response = await fetch(locationId ? `${warehouseData.urls.locationBase}/${locationId}` : warehouseData.urls.locationStore, {
                method: locationId ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || data.error || 'No fue posible guardar la ubicación.');
            }

            showSuccess(data.message || 'Ubicación guardada correctamente.');
            locationModal.hide();
            window.location.reload();
        } catch (error) {
            showError(error.message);
        } finally {
            setLoading(submitButton, spinner, false);
        }
    }

    async function deleteLocation(locationId) {
        const location = warehouseData.locations.find((item) => String(item.location_id) === String(locationId));

        if (!location) {
            showError('No se encontró la ubicación seleccionada.');
            return;
        }

        const confirmed = await confirmAction('¿Eliminar ubicación?', `La ubicación "${location.code} - ${location.name}" será eliminada.`);
        if (!confirmed) {
            return;
        }

        try {
            const response = await fetch(`${warehouseData.urls.locationBase}/${locationId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || data.error || 'No fue posible eliminar la ubicación.');
            }

            showSuccess(data.message || 'Ubicación eliminada correctamente.');
            window.location.reload();
        } catch (error) {
            showError(error.message);
        }
    }
});
</script>
@endsection
