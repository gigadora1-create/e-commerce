@extends('layouts.app')
@php
    use Illuminate\Support\Str;
@endphp
@section('contents')
<div class="container mt-4">
    <h2 class="text-center mb-4">Inventario Consolidado</h2>
    <!-- Botones de acción -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex gap-2">
            @if(session('selected_customer'))
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#searchModal">
                    <i class="fas fa-search me-1"></i> Búsqueda Avanzada
                </button>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importInventoryModal">
                    <i class="fas fa-file-import me-1"></i> Importar
                </button>
                <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#exportModal">
                    <i class="fas fa-file-export me-1"></i> Exportar
                </button>
                @can('password.create')
                    <a href="{{ route('inventory-details.index') }}" class="btn btn-warning">
                        <i class="fas fa-list me-1"></i> Ver Detalle
                    </a>
                @endcan
                @if(auth()->user()->isSuperAdmin())
                    <button class="btn btn-dark" id="reconcileInventoryBtn">
                        <i class="fas fa-sync-alt me-1"></i> Reconciliar Inventario
                    </button>
                @endif
            @endif
        </div>
        <div>
            @if(session('selected_customer'))
                <div class="d-flex align-items-center gap-3">
                    <div class="alert alert-info d-inline-flex align-items-center mb-0 py-2 px-3">
                        <i class="fas fa-user me-2"></i>
                        <strong>Cliente: {{ session('selected_customer') }}</strong>
                    </div>
                    <a href="{{ route('customer.context.index') }}" class="btn btn-outline-primary">
                        <i class="fas fa-exchange-alt me-1"></i> Cambiar cliente
                    </a>
                </div>
            @else
                <a href="{{ route('customer.context.index') }}" class="btn btn-primary">
                    <i class="fas fa-user-plus me-1"></i> Seleccionar Cliente
                </a>
            @endif
        </div>
    </div>

    @if(session('selected_customer'))
        <!-- Tarjetas de resumen -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title">Ingresos Iniciales</h5>
                        <h3 class="card-text">{{ number_format($inventory_unified->sum('original_entries')) }}</h3>
                        <small>Productos nuevos</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title">Devoluciones</h5>
                        <h3 class="card-text">{{ number_format($inventory_unified->sum('total_returns')) }}</h3>
                        <small>Productos devueltos</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title">Salidas Totales</h5>
                        <h3 class="card-text">{{ number_format($inventory_unified->sum('total_outputs')) }}</h3>
                        <small>Productos despachados</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title">Stock Disponible</h5>
                        <h3 class="card-text">{{ number_format($inventory_unified->sum('stock_available')) }}</h3>
                        <small>Disponible para despacho</small>
                    </div>
                </div>
            </div>
        </div>

<!-- Tabla de inventario consolidado -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table class="table table-bordered table-hover mb-0" id="consolidatedTable" style="width: 100%">
                <thead class="thead-dark">
                    <tr>
                        <th>SKU</th>
                        <th>Descripción</th>
                        <th>Bodega</th>
                        <th>Ubicación</th>
                        <th>Ingresos</th>
                        <th>Devoluciones</th>
                        <th>Retenciones</th>
                        <th>Salidas</th>
                        <th>Stock</th>
                        <th>Estado</th>
                        <th>Fechas de Vencimiento</th>
                        <th>Última Modificación</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($inventory_unified as $inventory)
                        @php
                            // Filtrar: Solo mostrar si tiene al menos un movimiento o stock
                            $hasMovement = $inventory['original_entries'] > 0 
                                || $inventory['total_returns'] > 0 
                                || $inventory['total_retention_quantity'] > 0 
                                || $inventory['total_outputs'] > 0;
                            
                            // Si no tiene movimientos Y el stock es null o vacío, no mostrar
                            if (!$hasMovement && empty($inventory['stock_available'])) {
                                continue;
                            }

                            // Obtener fechas de vencimiento del producto en estado INGRESO
                            $expiryDates = collect($inventory['expiry_dates'] ?? [])
                                ->filter(function($date) {
                                    return !empty($date);
                                })
                                ->unique()
                                ->sort()
                                ->values();
                        @endphp
                        
                        @if (auth()->user()->can('password.create') || in_array(strtoupper($inventory['warehouse']), $cityPermissions))
                            @if ($inventory['customer'] == session('selected_customer'))
                                <tr>
                                    <td><span class="badge bg-secondary">{{ $inventory['sku'] }}</span></td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 250px;" title="{{ $inventory['item_description'] }}">
                                            {{ $inventory['item_description'] }}
                                        </div>
                                    </td>
                                    <td><span class="badge bg-info">{{ $inventory['warehouse'] }}</span></td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            {{ $inventory['location'] ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="text-center text-primary fw-semibold">
                                        {{ number_format($inventory['original_entries']) }}
                                    </td>
                                    <td class="text-center text-success fw-semibold">
                                        @if($inventory['total_returns'] > 0)
                                            +{{ number_format($inventory['total_returns']) }}
                                        @else
                                            0
                                        @endif
                                    </td>
                                    <td class="text-center text-warning fw-semibold">
                                        @if($inventory['total_retention_quantity'] > 0)
                                            {{ number_format($inventory['total_retention_quantity']) }}
                                        @else
                                            0
                                        @endif
                                    </td>
                                    <td class="text-center text-danger fw-semibold">
                                        @if($inventory['total_outputs'] > 0)
                                            -{{ number_format($inventory['total_outputs']) }}
                                        @else
                                            0
                                        @endif
                                    </td>
                                    <td class="text-center
                                        @if($inventory['stock_available'] > 0) 
                                            text-success 
                                        @elseif($inventory['stock_available'] < 0) 
                                            text-danger 
                                        @else 
                                            text-dark
                                        @endif fw-bold">
                                        {{ number_format($inventory['stock_available']) }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge 
                                            @if($inventory['stock_status'] === 'Alta Existencias') 
                                                bg-success 
                                            @elseif($inventory['stock_status'] === 'Pronto a Agotar') 
                                                bg-warning 
                                            @elseif($inventory['stock_status'] === 'Sin Existencias') 
                                                bg-dark
                                            @else 
                                                bg-danger 
                                            @endif">
                                            {{ $inventory['stock_status'] }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($expiryDates->count() > 0)
                                            <div class="expiry-dates-container">
                                                @foreach($expiryDates as $index => $date)
                                                    @php
                                                        $expiryDate = \Carbon\Carbon::parse($date);
                                                        $today = \Carbon\Carbon::today();
                                                        $daysUntilExpiry = $today->diffInDays($expiryDate, false);
                                                        
                                                        // Determinar el color según los días restantes
                                                        if ($expiryDate->isPast()) {
                                                            $badgeClass = 'bg-danger';
                                                            $icon = 'fa-times-circle';
                                                            $label = 'Vencido';
                                                        } elseif ($daysUntilExpiry <= 30) {
                                                            $badgeClass = 'bg-warning text-dark';
                                                            $icon = 'fa-exclamation-triangle';
                                                            $label = $daysUntilExpiry . ' días';
                                                        } elseif ($daysUntilExpiry <= 90) {
                                                            $badgeClass = 'bg-info';
                                                            $icon = 'fa-info-circle';
                                                            $label = $daysUntilExpiry . ' días';
                                                        } else {
                                                            $badgeClass = 'bg-success';
                                                            $icon = 'fa-check-circle';
                                                            $label = $daysUntilExpiry . ' días';
                                                        }
                                                    @endphp
                                                    <div class="expiry-date-item mb-1">
                                                        <span class="badge {{ $badgeClass }}" title="Días restantes: {{ $label }}">
                                                            <i class="fas {{ $icon }} me-1"></i>
                                                            {{ $expiryDate->format('d/m/Y') }}
                                                            <small class="ms-1">({{ $label }})</small>
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-muted small">
                                                <i class="fas fa-minus-circle me-1"></i>Sin fechas
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <small>
                                            {{ $inventory['last_modified_date'] ? \Carbon\Carbon::parse($inventory['last_modified_date'])->format('d/m/Y') : 'N/A' }}
                                        </small>
                                    </td>
                                </tr>
                            @endif
                        @endif
                    @endforeach
                </tbody>
                <tfoot class="bg-light">
                    <tr>
                        <th colspan="4" class="text-end">TOTALES</th>
                        <th class="text-center text-primary">{{ number_format($inventory_unified->sum('original_entries')) }}</th>
                        <th class="text-center text-success">+{{ number_format($inventory_unified->sum('total_returns')) }}</th>
                        <th class="text-center text-warning">{{ number_format($inventory_unified->sum('total_retention_quantity')) }}</th>
                        <th class="text-center text-danger">-{{ number_format($inventory_unified->sum('total_outputs')) }}</th>
                        <th class="text-center fw-bold">{{ number_format($inventory_unified->sum('stock_available')) }}</th>
                        <th colspan="3"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
    @else
        <div class="alert alert-warning text-center py-4">
            <h4 class="mb-3"><i class="fas fa-exclamation-circle me-2"></i> Selecciona un cliente para comenzar</h4>
            <p>Debes seleccionar un cliente para poder visualizar y gestionar el inventario.</p>
        </div>
    @endif

    @if($retentionItems && $retentionItems->count() > 0)
        <div class="card mt-4">
            <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Productos en Retención ({{ $retentionItems->count() }})
                </h5>
                <a href="{{ route('inventories.retention_report') }}" class="btn btn-sm btn-outline-dark">
                    Ver Reporte Completo
                </a>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <strong>Importante:</strong> Estos productos NO forman parte del stock disponible.
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="table-warning">
                            <tr>
                                <th>SKU</th>
                                <th>Producto</th>
                                <th>Bodega</th>
                                <th>Cantidad</th>
                                <th>Motivo</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($retentionItems->take(5) as $retention)
                                <tr>
                                    <td><code>{{ $retention['sku'] }}</code></td>
                                    <td>{{ Str::limit($retention['item_description'], 30) }}</td>
                                    <td><span class="badge bg-secondary">{{ $retention['warehouse'] }}</span></td>
                                    <td>
                                        <span class="badge bg-warning text-dark">
                                            {{ number_format($retention['total_retention_quantity']) }}
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ Str::limit($retention['retention_reason'] ?: 'Sin especificar', 10) }}
                                        </small>
                                    </td>
                                    <td>
                                        <small>{{ \Carbon\Carbon::parse($retention['last_modified_date'])->format('d/m/Y') }}</small>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($retentionItems->count() > 5)
                    <div class="text-center">
                        <small class="text-muted">
                            Mostrando 5 de {{ $retentionItems->count() }} items en retención.
                            <a href="{{ route('inventories.retention_report') }}">Ver todos</a>
                        </small>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

@if(session('selected_customer'))
    <!-- Modal de Búsqueda Avanzada -->
    <div class="modal fade" id="searchModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-search me-2"></i> Búsqueda Avanzada
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="searchForm" action="{{ route('inventories.index') }}" method="GET">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="warehouse" class="form-label">Bodega</label>
                                <select name="warehouse" id="warehouse" class="form-select">
                                    <option value="">Todas las bodegas</option>
                                    @foreach ($uniqueWarehouses as $warehouseOption)
                                        <option value="{{ $warehouseOption }}" {{ $warehouseOption == $warehouse ? 'selected' : '' }}>
                                            {{ $warehouseOption }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="product" class="form-label">Producto</label>
                                <select name="product" id="product" class="form-select">
                                    <option value="">Todos los productos</option>
                                    @foreach ($uniqueProducts as $productOption)
                                        <option value="{{ $productOption }}" {{ $productOption == $product ? 'selected' : '' }}>
                                            {{ Str::limit($productOption, 30) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="location" class="form-label">Ubicación</label>
                                <select name="location" id="location" class="form-select">
                                    <option value="">Todas las ubicaciones</option>
                                    @foreach ($uniqueLocations as $loc)
                                        <option value="{{ $loc }}" {{ $loc === ($location ?? '') ? 'selected' : '' }}>{{ $loc }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="start_date" class="form-label">Fecha Inicial</label>
                                <input type="date" name="start_date" id="start_date" class="form-control"
                                       value="{{ $startDate ?? '' }}">
                            </div>
                            <div class="col-md-6">
                                <label for="end_date" class="form-label">Fecha Final</label>
                                <input type="date" name="end_date" id="end_date" class="form-control"
                                       value="{{ $endDate ?? '' }}">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i> Buscar
                        </button>
                        <a href="{{ route('inventories.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-eraser me-1"></i> Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Importación -->
    <div class="modal fade" id="importInventoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-file-import me-2"></i> Importar Inventario
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="importForm" action="{{ route('inventories.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <div class="border-dashed p-4 text-center" style="border: 2px dashed #dee2e6; border-radius: 5px;">
                                <i class="fas fa-file-excel fa-3x text-success mb-3"></i>
                                <p class="mb-2">Arrastra y suelta tu archivo Excel aquí</p>
                                <p class="text-muted small">o</p>
                                <label for="file" class="btn btn-outline-primary mt-2">
                                    Seleccionar archivo
                                    <input type="file" name="file" id="file" class="d-none" accept=".xlsx,.xls" required>
                                </label>
                                <div id="file-name" class="mt-2 text-muted small"></div>
                            </div>
                        </div>
                        <div class="d-grid gap-2 mt-3">
                            <a href="{{ asset('documents/Archivo_Carga_Inventario.xlsx') }}"
                               class="btn btn-outline-success" download>
                                <i class="fas fa-download me-1"></i> Descargar plantilla
                            </a>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-file-import me-1"></i> Importar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Exportación -->
    <div class="modal fade" id="exportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-file-export me-2"></i> Exportar Inventario
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="exportForm" action="{{ route('inventories.export') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="export_start_date" class="form-label">Fecha Inicial</label>
                                <input type="date" name="start_date" id="export_start_date" class="form-control"
                                       value="{{ $startDate ?? '' }}">
                            </div>
                            <div class="col-md-6">
                                <label for="export_end_date" class="form-label">Fecha Final</label>
                                <input type="date" name="end_date" id="export_end_date" class="form-control"
                                       value="{{ $endDate ?? '' }}">
                            </div>
                            <div class="col-md-12">
                                <label for="export_product" class="form-label">Producto</label>
                                <select name="product" id="export_product" class="form-select">
                                    <option value="">Todos los productos</option>
                                    @foreach ($uniqueProducts as $productOption)
                                        <option value="{{ $productOption }}" {{ $productOption == $product ? 'selected' : '' }}>
                                            {{ Str::limit($productOption, 30) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label for="export_warehouse" class="form-label">Bodega</label>
                                <select name="warehouse" id="export_warehouse" class="form-select">
                                    <option value="">Todas las bodegas</option>
                                    @foreach ($uniqueWarehouses as $warehouseOption)
                                        <option value="{{ $warehouseOption }}" {{ $warehouseOption == $warehouse ? 'selected' : '' }}>
                                            {{ $warehouseOption }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label for="export_location" class="form-label">Ubicación</label>
                                <select name="location" id="export_location" class="form-select">
                                    <option value="">Todas las ubicaciones</option>
                                    @foreach ($uniqueLocations as $loc)
                                        <option value="{{ $loc }}" {{ $loc === ($location ?? '') ? 'selected' : '' }}>{{ $loc }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-file-export me-1"></i> Exportar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

<!-- Modal para mostrar errores detallados -->
<div class="modal fade" id="errorDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i> Errores de Importación Detallados
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <strong>Importante:</strong> Corrija todos los errores antes de volver a intentar la importación.
                </div>
                <div id="error-content" style="max-height: 400px; overflow-y: auto;">
                    <pre class="bg-light p-3 rounded" style="white-space: pre-wrap; font-size: 0.9em;">{{ session('detailed_errors') }}</pre>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" onclick="copyErrorsToClipboard()">
                    <i class="fas fa-copy me-1"></i> Copiar Errores
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    // Manejar el envío del formulario de búsqueda
    @if(session('selected_customer'))
    $('#searchForm').on('submit', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Procesando...',
            text: 'Buscando registros, por favor espere.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
                this.submit();
            }
        });
    });
    @endif

    // Manejar el envío del formulario de exportación
    @if(session('selected_customer'))
    $('#exportForm').on('submit', function(e) {
        e.preventDefault();
        let form = this;

        Swal.fire({
            title: '¿Estás seguro?',
            text: '¿Deseas exportar los datos con los filtros actuales?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0dcaf0',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, exportar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Exportando...',
                    text: 'Generando archivo, por favor espere.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                        form.submit();
                        setTimeout(() => {
                            Swal.close();
                        }, 5000);
                    }
                });
            }
        });
    });
    @endif

    // Configuración para el modal de importación
    const fileInput = document.getElementById('file');
    const fileNameDisplay = document.getElementById('file-name');
    if (fileInput && fileNameDisplay) {
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                fileNameDisplay.textContent = 'Archivo seleccionado: ' + this.files[0].name;
            }
        });
    }

    // Notificaciones
    @if (session('success'))
        Swal.fire({
            icon: 'success',
            title: '¡Exitoso!',
            html: `
                <div class="text-center">
                    <i class="fas fa-check-circle text-success" style="font-size: 2rem;"></i>
                    <p class="mt-2">{{ session('success') }}</p>
                </div>
            `,
            timer: 4000,
            showConfirmButton: true,
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#198754'
        });
    @endif

    @if (session('error') && !session('detailed_errors'))
        Swal.fire({
            icon: 'error',
            title: 'Error en la Importación',
            html: `
                <div class="text-center">
                    <i class="fas fa-times-circle text-danger" style="font-size: 2rem;"></i>
                    <p class="mt-2">{{ session('error') }}</p>
                </div>
            `,
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#dc3545'
        });
    @endif

    @if (session('error') && session('detailed_errors'))
        Swal.fire({
            icon: 'error',
            title: 'Errores Encontrados en el Archivo',
            html: `
                <div class="text-center">
                    <i class="fas fa-exclamation-triangle text-warning" style="font-size: 2rem;"></i>
                    <p class="mt-2">{{ session('error') }}</p>
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Se abrirá una ventana con los detalles completos de los errores.
                    </div>
                </div>
            `,
            confirmButtonText: 'Ver Detalles',
            confirmButtonColor: '#dc3545'
        }).then(() => {
            $('#errorDetailModal').modal('show');
        });
    @endif

    // Inicializar DataTable solo si hay cliente seleccionado
    @if(session('selected_customer'))
    $('#consolidatedTable').DataTable({
        language: {
            decimal: ",",
            thousands: ".",
            processing: "Procesando...",
            search: "Buscar:",
            lengthMenu: "Mostrar _MENU_ registros",
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            infoEmpty: "Mostrando 0 a 0 de 0 registros",
            infoFiltered: "(filtrado de _MAX_ registros totales)",
            infoPostFix: "",
            loadingRecords: "Cargando...",
            zeroRecords: "No se encontraron registros coincidentes",
            emptyTable: "No hay datos disponibles en la tabla",
            paginate: {
                first: "Primero",
                previous: "Anterior",
                next: "Siguiente",
                last: "Último"
            },
            aria: {
                sortAscending: ": activar para ordenar la columna de manera ascendente",
                sortDescending: ": activar para ordenar la columna de manera descendente"
            }
        },
        responsive: true,
        pageLength: 10,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        columnDefs: [
            { responsivePriority: 1, targets: 0 },
            { responsivePriority: 2, targets: 1 },
            { responsivePriority: 3, targets: 8 },
            { orderable: false, targets: [10] },
            { className: "text-center", targets: [4, 5, 6, 7, 8, 9] }
        ],
        order: [[11, 'desc']]
    });
    @endif

    // Mostrar modal de errores detallados si existe
    @if(session('detailed_errors'))
        $('#errorDetailModal').modal('show');
    @endif

    // Botón de Reconciliación de Inventario (Solo SUPERADMIN)
    $('#reconcileInventoryBtn').on('click', function() {
        Swal.fire({
            title: '¿Reconciliar Inventario?',
            text: 'Esta acción recalculará el stock de todos los productos y ubicaciones. Puede tardar un momento.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#343a40',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, reconciliar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Procesando Reconciliación',
                    text: 'Recalculando niveles de stock...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: "{{ route('inventories.reconcile') }}",
                    type: "POST",
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    },
                    success: function(res) {
                        if (res.report && res.report.length > 0) {
                            let reportHtml = `
                                <div class="table-responsive" style="max-height: 300px;">
                                    <table class="table table-sm table-striped">
                                        <thead>
                                            <tr>
                                                <th>SKU</th>
                                                <th>Ubicación</th>
                                                <th>Antiguo</th>
                                                <th>Nuevo</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${res.report.map(item => `
                                                <tr>
                                                    <td><small>${item.sku}</small></td>
                                                    <td><span class="badge bg-light text-dark">${item.location}</span></td>
                                                    <td class="text-danger">${item.old_stock}</td>
                                                    <td class="text-success"><strong>${item.new_stock}</strong></td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                            `;

                            Swal.fire({
                                icon: 'info',
                                title: 'Reporte de Correcciones',
                                html: reportHtml,
                                width: '600px',
                                confirmButtonText: 'Entendido'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Éxito!',
                                text: res.message,
                                confirmButtonText: 'Genial'
                            }).then(() => {
                                location.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Ocurrió un error al reconciliar el inventario.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: errorMessage
                        });
                    }
                });
            }
    });
});

    // Manejar el envío del formulario de importación
    @if(session('selected_customer'))
    $('#importForm').on('submit', function(e) {
        e.preventDefault();
        
        const fileInput = document.getElementById('file');
        if (!fileInput.files || fileInput.files.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Archivo requerido',
                text: 'Por favor seleccione un archivo para importar.',
                confirmButtonColor: '#198754'
            });
            return;
        }

        const fileName = fileInput.files[0].name;
        const validExtensions = ['xlsx', 'xls', 'csv'];
        const fileExtension = fileName.split('.').pop().toLowerCase();
        
        if (!validExtensions.includes(fileExtension)) {
            Swal.fire({
                icon: 'error',
                title: 'Archivo no válido',
                text: 'Por favor seleccione un archivo Excel (.xlsx, .xls) o CSV.',
                confirmButtonColor: '#dc3545'
            });
            return;
        }

        Swal.fire({
            title: '¿Estás seguro?',
            html: `
                <p>¿Deseas importar el archivo <strong>${fileName}</strong>?</p>
                <div class="alert alert-info mt-2">
                    <small><i class="fas fa-info-circle me-1"></i> 
                    El sistema validará todos los datos antes de procesarlos.</small>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, importar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Procesando importación...',
                    html: `
                        <div class="d-flex justify-content-center mb-3">
                            <div class="spinner-border text-success" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </div>
                        <p>Validando y procesando archivo: <strong>${fileName}</strong></p>
                        <small class="text-muted">Este proceso puede tardar varios minutos dependiendo del tamaño del archivo.</small>
                    `,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        this.submit();
                    }
                });
            }
        });
    });
    @endif

    // Función para copiar errores al portapapeles
    window.copyErrorsToClipboard = function() {
        const errorContent = document.querySelector('#error-content pre').textContent;
        navigator.clipboard.writeText(errorContent).then(function() {
            Swal.fire({
                icon: 'success',
                title: '¡Copiado!',
                text: 'Los errores han sido copiados al portapapeles.',
                timer: 2000,
                showConfirmButton: false
            });
        }, function(err) {
            const textArea = document.createElement('textarea');
            textArea.value = errorContent;
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                document.execCommand('copy');
                Swal.fire({
                    icon: 'success',
                    title: '¡Copiado!',
                    text: 'Los errores han sido copiados al portapapeles.',
                    timer: 2000,
                    showConfirmButton: false
                });
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo copiar al portapapeles.'
                });
            }
            document.body.removeChild(textArea);
        });
    };

    // Limpiar modales al cerrar
    $('#searchModal, #importInventoryModal, #exportModal').on('hidden.bs.modal', function() {
        $(this).find('form')[0].reset();
        if (fileNameDisplay) {
            fileNameDisplay.textContent = '';
        }
    });
});
</script>
<style>
    /* Estilos mejorados para la tabla */
    .thead-dark {
        background-color: #343a40;
        color: white;
    }
    
    .thead-dark th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        padding: 12px 8px;
        border-color: #454d55;
    }
    
    #consolidatedTable tbody tr {
        transition: background-color 0.2s ease;
    }
    
    #consolidatedTable tbody tr:hover {
        background-color: #f8f9fa;
        cursor: pointer;
    }
    
    /* Estilos para fechas de vencimiento */
    .expiry-dates-container {
        max-height: 120px;
        overflow-y: auto;
        padding: 2px;
    }
    
    .expiry-date-item {
        animation: fadeIn 0.3s ease-in;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-5px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .expiry-dates-container::-webkit-scrollbar {
        width: 6px;
    }
    
    .expiry-dates-container::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }
    
    .expiry-dates-container::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 3px;
    }
    
    .expiry-dates-container::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
    
    /* Estilos para badges */
    .badge {
        font-size: 0.75rem;
        padding: 0.35em 0.65em;
        font-weight: 500;
    }
    
    /* Estilos para modal de errores */
    #errorDetailModal .modal-dialog {
        max-width: 90%;
    }
    
    #errorDetailModal pre {
        font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
        font-size: 0.85em;
        line-height: 1.4;
        margin: 0;
        border: 1px solid #dee2e6;
    }
    
    #errorDetailModal .alert {
        border-left: 4px solid #dc3545;
    }
    
    .spinner-border {
        width: 2rem;
        height: 2rem;
    }
    
    /* Mejorar el área de arrastrar archivo */
    .border-dashed {
        position: relative;
        transition: all 0.3s ease;
    }
    
    .border-dashed:hover {
        background-color: #f8f9fa;
        border-color: #198754 !important;
        transform: scale(1.02);
    }
    
    .border-dashed.dragover {
        background-color: #e8f5e9;
        border-color: #4caf50 !important;
        transform: scale(1.05);
    }
    
    /* Estilos para los modales */
    .modal-header {
        border-bottom: 1px solid #dee2e6;
        padding: 1rem;
        border-top-left-radius: calc(0.3rem - 1px);
        border-top-right-radius: calc(0.3rem - 1px);
    }
    
    .bg-primary .modal-header {
        background-color: #0d6efd;
        color: white;
    }
    
    .bg-success .modal-header {
        background-color: #198754;
        color: white;
    }
    
    .bg-info .modal-header {
        background-color: #0dcaf0;
        color: white;
    }
    
    #drag-drop-area {
        min-height: 150px;
        cursor: pointer;
    }
    
    /* Estilos para las tarjetas de resumen */
    .card {
        transition: transform 0.2s;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
    /* Estilos para DataTables */
    .dataTables_wrapper {
        padding: 15px;
    }
    
    .dataTables_wrapper .dataTables_length select {
        padding: 0.375rem 2.25rem 0.375rem 0.75rem;
    }
    
    .dataTables_wrapper .dataTables_filter input {
        padding: 0.375rem 0.75rem;
        margin-left: 0.5rem;
    }
    
    /* Truncar texto largo */
    .text-truncate {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    /* Mejorar legibilidad de números */
    .fw-semibold {
        font-weight: 600 !important;
    }
</style>
@endsection
