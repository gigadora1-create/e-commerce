@extends('layouts.app')

@section('contents')
<link rel="stylesheet" href="{{ asset('css/stock.inventories.css') }}">
<div class="container mt-4 position-relative">
    <!-- Encabezado principal -->
    <div class="container mt-4">
        <h2 class="text-center mb-4">Salidas Inventario</h2>
    </div>

    <!-- Selección de cliente y mensajes -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <h2 class="text-center mb-4"></h2>
        @if (session('selected_customer'))
            <div class="selected-customer-container">
                <div class="alert alert-info d-inline-flex align-items-center mb-0 py-2 px-3">
                    <i class="fas fa-user me-2"></i>
                    <strong>Cliente: {{ session('selected_customer') }}</strong>
                </div>
            </div>
        @endif
    </div>

    <!-- Mensajes de éxito/error -->
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <!-- Botones de acción -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 align-items-end">
                <div class="flex-grow-1" style="min-width: 200px;">
                    <a href="{{ route('inventory-outputs.create') }}" class="btn btn-primary w-100">
                        <i class="fas fa-sign-out-alt me-2"></i>Registrar Salida
                    </a>
                </div>
                <div class="flex-grow-1" style="min-width: 200px;">
                    <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#importMassiveModal">
                        <i class="fas fa-file-import me-2"></i>Carga Masiva
                    </button>
                </div>
                @can('password.create')
                    <div class="flex-grow-1" style="min-width: 200px;">
                        <button type="button" class="btn btn-success w-100" onclick="exportExcel()">
                            <i class="fas fa-file-excel me-2"></i>Exportar Excel
                        </button>
                    </div>
                    <div class="flex-grow-1" style="min-width: 200px;">
                        <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#dailyManifestModal">
                            <i class="fas fa-file-pdf me-2"></i>Manifiesto Diario
                        </button>
                    </div>
                @endcan
            </div>
        </div>
    </div>

    <!-- Información de inventario por bodega -->
    <div class="row mb-4">
        @foreach ($warehouseInventories->groupBy('warehouse') as $warehouse => $inventories)
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h2 class="mb-3" style="color: #1a1a16;">
                            <i class="fas fa-warehouse me-2"></i>Inventario de {{ $warehouse }}
                        </h2>
                        <div class="scroll-horizontal">
                            <table class="table table-bordered warehouse-table" style="width:100%">
                                <thead class="thead-dark">
                                    <tr>
                                        <th style="width: 15%;">Ubicación</th>
                                        <th style="width: 20%;">Producto</th>
                                        <th style="width: 10%;">SKU</th>
                                        <th style="width: 10%;">Salidas</th>
                                        <th style="width: 10%;">Stock Actual</th>
                                        <th style="width: 10%;">Estado</th>
                                        <th style="width: 15%;">Última Modificación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($inventories as $inventory)
                                        <tr>
                                            <td>{{ $inventory->location_code ?? 'N/A' }} - {{ $inventory->location_name ?? 'N/A' }}</td>
                                            <td>{{ $inventory->item_description }}</td>
                                            <td>{{ $inventory->sku }}</td>
                                            <td>{{ $inventory->total_outputs ?? 'N/A' }}</td>
                                            <td>{{ $inventory->current_stock }}</td>
                                            <td>
                                                <span class="badge {{ $inventory->stock_css_class }}">
                                                    {{ $inventory->stock_status }}
                                                </span>
                                            </td>
                                            <td>{{ $inventory->last_modified_date }}</td>
                                        </tr>
                                    @endforeach
                                    @if($inventories->isEmpty())
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">
                                                <i class="fas fa-inbox fa-2x mb-3"></i><br>
                                                No hay productos disponibles en esta bodega
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Tabla de salidas de inventario -->
    <div class="card">
        <div class="card-body">
            <h2 class="mb-3" style="color: #1a1a1a;">Salidas de Inventario</h2>
            <div class="scroll-horizontal">
                <table class="table table-bordered" id="consolidatedTable" style="width:100%">
                    <thead class="thead-dark">
                        <tr>
                            <th style="width: 10%;">Acciones</th>
                            <th style="width: 20%;">Producto</th>
                            <th style="width: 15%;">Bodega</th>
                            <th style="width: 15%;">Picking</th>
                            <th style="width: 10%;">Cantidad</th>
                            <th style="width: 15%;">Fecha de Salida</th>
                            <th style="width: 15%;">Valor Declarado</th>
                            <th style="width: 15%;">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($outputs as $output)
                            <tr>
                                <td>
                                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal{{ $output->id }}">
                                        Editar
                                    </button>
                                </td>
                                <td>{{ $output->item_name }}</td>
                                <td>{{ $output->warehouse }}</td>
                                <td>{{ $output->guide }}</td>
                                <td>{{ $output->quantity }}</td>
                                <td>{{ $output->created_at->format('Y-m-d') }}</td>
                                <td>{{ number_format($output->declared_value, 2) }}</td>
                                <td>{{ $output->status }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modales de edición (existentes) -->
    @foreach ($outputs as $output)
        <div class="modal fade" id="editModal{{ $output->id }}" tabindex="-1" aria-labelledby="editModalLabel{{ $output->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="editModalLabel{{ $output->id }}">Editar Salida</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('inventory-outputs.update', $output->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="mb-3">
                                <label for="item_name_{{ $output->id }}" class="form-label">Producto</label>
                                <input type="text" name="item_name" id="item_name_{{ $output->id }}" class="form-control" value="{{ $output->item_name }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="guide_{{ $output->id }}" class="form-label">Guía</label>
                                <input type="text" name="guide" id="guide_{{ $output->id }}" class="form-control" value="{{ $output->guide }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="quantity_{{ $output->id }}" class="form-label">Cantidad</label>
                                <input type="number" name="quantity" id="quantity_{{ $output->id }}" class="form-control" value="{{ $output->quantity }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="warehouse_{{ $output->id }}" class="form-label">Bodega</label>
                                <input type="text" name="warehouse" id="warehouse_{{ $output->id }}" class="form-control" value="{{ $output->warehouse }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="created_at_{{ $output->id }}" class="form-label">Fecha de Salida</label>
                                <input type="date" name="created_at" id="created_at_{{ $output->id }}" class="form-control" value="{{ $output->created_at->format('Y-m-d') }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="declared_value_{{ $output->id }}" class="form-label">Valor Declarado</label>
                                <input type="number" step="0.01" name="declared_value" id="declared_value_{{ $output->id }}" class="form-control" value="{{ $output->declared_value }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="status_{{ $output->id }}" class="form-label">Estado</label>
                                <select name="status" id="status_{{ $output->id }}" class="form-select" required>
                                    <option value="bueno" {{ $output->status === 'bueno' ? 'selected' : '' }}>Bueno</option>
                                    <option value="malo" {{ $output->status === 'malo' ? 'selected' : '' }}>Malo</option>
                                </select>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                <button type="submit" class="btn btn-primary">Guardar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <!-- Modal de Importación Masiva (con estilo mejorado) -->
    <div class="modal fade" id="importMassiveModal" tabindex="-1" aria-labelledby="importMassiveModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="importMassiveModalLabel">
                        <i class="fas fa-file-import me-2"></i>Carga Masiva de Salidas
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>¿Cómo funciona la carga masiva?</strong><br>
                        Puedes cargar múltiples salidas de inventario desde un archivo Excel (.xlsx, .xls) o CSV.
                        El sistema validará automáticamente el stock disponible y los datos antes de procesar.
                    </div>
 <div id="importProgress" class="mt-3" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Procesando importación...</span>
                                <span id="progressText">0%</span>
                            </div>
                            <div class="progress">
                                <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                                     role="progressbar" style="width: 0%"></div>
                            </div>
                        </div>
                    <div class="mb-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="card-title">
                                    <i class="fas fa-download me-2"></i>Descargar Plantilla
                                </h6>
                                <p class="card-text text-muted">
                                    Descarga la plantilla de Excel con el formato correcto
                                </p>
                                <a href="{{ route('inventory-outputs.download-template') }}" class="btn btn-outline-primary">
                                    <i class="fas fa-file-excel me-2"></i>Descargar Plantilla Excel
                                </a>
                            </div>
                        </div>
                    </div>

                    <form id="importForm" action="{{ route('inventory-outputs.import-massive') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="excel_file" class="form-label">
                                <i class="fas fa-file-upload me-2"></i>Seleccionar Archivo
                            </label>
                            <input type="file" name="excel_file" id="excel_file"
                                   class="form-control"
                                   accept=".xlsx,.xls,.csv"
                                   required>
                            <div class="form-text">
                                Formatos soportados: .xlsx, .xls, .csv (máximo 10MB)
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div id="filePreview" class="mt-3" style="display: none;">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="fas fa-file-alt me-2"></i>Archivo Seleccionado
                                    </h6>
                                    <div id="fileInfo"></div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="cancelBtn">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-success" id="importBtn">
                        <i class="fas fa-file-import me-2"></i>Iniciar Importación
                    </button>
                    <button type="button" class="btn btn-primary" id="reloadBtn" style="display: none;" onclick="window.location.reload()">
                        <i class="fas fa-sync-alt me-2"></i>Actualizar Página
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Manifiesto Diario (existente) -->
    <div class="modal fade" id="dailyManifestModal" tabindex="-1" aria-labelledby="dailyManifestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="dailyManifestModalLabel">
                        <i class="fas fa-file-pdf me-2"></i>Generar Manifiesto Diario
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>¿Qué es el Manifiesto Diario?</strong><br>
                        Un reporte completo en PDF que incluye todas las salidas de inventario y movimientos.
                    </div>
                    <form action="{{ route('inventory-outputs.generate-report') }}" method="POST" id="manifestForm">
                        @csrf
                        <div class="mb-3">
                            <label for="manifestDate" class="form-label">
                                <i class="fas fa-calendar-alt me-2"></i>Fecha del Reporte
                            </label>
                            <input type="date" name="date" id="manifestDate" class="form-control"
                                   value="{{ date('Y-m-d') }}" required>
                            <div class="form-text">Seleccione la fecha para la cual desea generar el manifiesto.</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="submit" form="manifestForm" class="btn btn-danger">
                        <i class="fas fa-file-pdf me-2"></i>Generar PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- NUEVO: Modal para mostrar errores detallados (estilo mejorado) -->
    <div class="modal fade" id="errorDetailModal" tabindex="-1" aria-labelledby="errorDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="errorDetailModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Errores de Importación
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger d-flex align-items-center">
                        <i class="fas fa-info-circle me-2"></i>
                        <div>
                            <strong>Se encontraron errores durante la importación.</strong><br>
                            Revise los detalles a continuación y corrija los datos antes de volver a intentar.
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">
                                    <i class="fas fa-list-ul me-2"></i>Lista de Errores
                                    <span id="errorCount" class="badge bg-light text-danger ms-2"></span>
                                </h6>
                                <button class="btn btn-sm btn-outline-secondary" onclick="copyErrorsToClipboard()">
                                    <i class="fas fa-copy me-1"></i>Copiar todos
                                </button>
                            </div>

                            <div id="errorContent" class="error-container" style="max-height: 400px; overflow-y: auto;">
                                <!-- Los errores se cargarán aquí dinámicamente -->
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning">
                        <i class="fas fa-lightbulb me-2"></i>
                        <strong>Recomendaciones:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Verifique que los SKUs existan en el sistema</li>
                            <li>Asegúrese de que las cantidades no superen el stock disponible</li>
                            <li>Revise que los formatos de fecha sean correctos (YYYY-MM-DD)</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const DATATABLES_SP_URL = 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json';

// Función para exportar a Excel
function exportExcel() {
    Swal.fire({
        title: 'Confirmar Exportación',
        text: '¿Está seguro de que desea exportar las salidas de inventario a Excel?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-file-excel me-2"></i>Exportar',
        cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ route("inventory-outputs.export-excel") }}',
                method: 'GET',
                xhrFields: {
                    responseType: 'blob'
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(blob) {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'salidas_inventario_' + new Date().toISOString().slice(0, 10) + '.xlsx';
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    window.URL.revokeObjectURL(url);
                    Swal.fire({
                        title: 'Éxito',
                        text: 'El archivo Excel ha sido exportado correctamente.',
                        icon: 'success',
                        confirmButtonText: 'Aceptar',
                        confirmButtonColor: '#28a745'
                    });
                },
                error: function(xhr) {
                    Swal.fire({
                        title: 'Error',
                        text: 'Error al exportar el archivo: ' + (xhr.responseJSON?.message || xhr.statusText),
                        icon: 'error',
                        confirmButtonText: 'Aceptar',
                        confirmButtonColor: '#dc3545'
                    });
                }
            });
        }
    });
}

// Función para mostrar errores en el modal
function showErrorModal(errors, stats) {
    let errorHtml = '';
    errors.forEach((error, index) => {
        errorHtml += `
        <div class="error-item mb-2 p-2 bg-light border-start border-3 border-danger rounded">
            <strong class="text-danger">Error ${index + 1}:</strong>
            <span>${error}</span>
        </div>`;
    });

    $('#errorContent').html(errorHtml);
    $('#errorCount').text(`(${errors.length} errores)`);

    let statsHtml = '';
    if (stats) {
        statsHtml = `
        <div class="alert alert-secondary mt-3">
            <i class="fas fa-chart-bar me-2"></i>
            <strong>Estadísticas:</strong>
            <ul class="mb-0 mt-1">
                <li><strong>${stats.success}</strong> registros procesados correctamente</li>
                <li><strong>${stats.errors}</strong> registros con errores</li>
                <li><strong>${stats.total}</strong> registros totales</li>
            </ul>
        </div>`;
    }

    $('#errorDetailModal .card-body').append(statsHtml);
    $('#errorDetailModal').modal('show');
}

// Función para copiar errores al portapapeles
function copyErrorsToClipboard() {
    let errors = [];
    $('#errorContent .error-item').each(function() {
        errors.push($(this).text().trim());
    });

    if (errors.length === 0) {
        Swal.fire({
            title: 'Sin errores',
            text: 'No hay errores para copiar.',
            icon: 'info',
            confirmButtonText: 'Aceptar'
        });
        return;
    }

    const textToCopy = errors.join('\n');
    navigator.clipboard.writeText(textToCopy)
        .then(() => {
            Swal.fire({
                title: 'Copiado',
                text: 'Los errores han sido copiados al portapapeles.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        })
        .catch(err => {
            console.error('Error al copiar:', err);
            const textarea = document.createElement('textarea');
            textarea.value = textToCopy;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            Swal.fire({
                title: 'Copiado',
                text: 'Los errores han sido copiados al portapapeles (método alternativo).',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        });
}


$(document).ready(function() {
    // Inicializar DataTables
    $('.warehouse-table').each(function(index, table) {
        $(table).DataTable({
            paging: true,
            lengthChange: true,
            pageLength: 10,
            lengthMenu: [5, 10, 25, 50, 100],
            searching: true,
            ordering: true,
            order: [],
            responsive: true,
            scrollX: true,
            autoWidth: false,
            language: {
                url: DATATABLES_SP_URL
            },
            columnDefs: [
                { orderable: true, targets: '_all' },
                { width: '15%', targets: 0 },
                { width: '20%', targets: 1 },
                { width: '10%', targets: [2, 3, 4, 5, 6] }
            ]
        });
    });

    $('#consolidatedTable').DataTable({
        paging: true,
        lengthChange: true,
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50, 100],
        searching: true,
        ordering: true,
        order: [[5, 'desc']],
        responsive: true,
        scrollX: true,
        autoWidth: false,
        language: {
            url: DATATABLES_SP_URL
        },
        columnDefs: [
            { orderable: false, targets: 0 },
            { width: '10%', targets: 0 },
            { width: '20%', targets: 1 },
            { width: '15%', targets: [2, 3, 5, 6, 7] },
            { width: '10%', targets: 4 }
        ]
    });

    // Confirmación para formularios
    $('form:not(#manifestForm):not(#importForm)').on('submit', function(e) {
        e.preventDefault();
        Swal.fire({
            title: '¿Confirmar acción?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí',
            cancelButtonText: 'No',
            confirmButtonColor: '#007bff',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                this.submit();
            }
        });
    });

    // Confirmación para manifiesto
    $('#manifestForm').on('submit', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Generar Manifiesto Diario',
            text: '¿Desea generar el reporte PDF para la fecha seleccionada?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-file-pdf me-2"></i>Generar PDF',
            cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                this.submit();
                const modalEl = document.getElementById('dailyManifestModal');
                const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                modal.hide();
            }
        });
    });

    // Manejo del formulario de importación
    const fileInput = $('#excel_file');
    const filePreview = $('#filePreview');
    const fileInfo = $('#fileInfo');
    const importForm = $('#importForm');
    const importProgress = $('#importProgress');
    const progressBar = $('#progressBar');
    const progressText = $('#progressText');
    const importBtn = $('#importBtn');
    const cancelBtn = $('#cancelBtn');
    const reloadBtn = $('#reloadBtn');

    fileInput.on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            fileInfo.html(`
                <p class="mb-1"><strong>Nombre:</strong> ${file.name}</p>
                <p class="mb-1"><strong>Tamaño:</strong> ${(file.size / 1024 / 1024).toFixed(2)} MB</p>
                <p class="mb-0"><strong>Tipo:</strong> ${file.type || 'Desconocido'}</p>
            `);
            filePreview.show();
        } else {
            filePreview.hide();
        }
    });

    importBtn.on('click', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Confirmar Importación',
            text: '¿Está seguro de que desea importar este archivo?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-file-import me-2"></i>Importar',
            cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData(importForm[0]);
                if (!formData.has('excel_file')) {
                    Swal.fire({
                        title: 'Error',
                        text: 'No se seleccionó ningún archivo. Por favor, elija un archivo antes de importar.',
                        icon: 'error',
                        confirmButtonText: 'Aceptar',
                        confirmButtonColor: '#dc3545'
                    });
                    return;
                }

                importProgress.show();
                importBtn.prop('disabled', true);
                cancelBtn.prop('disabled', true);

                let progress = 0;
                const progressInterval = setInterval(() => {
                    progress += Math.random() * 15;
                    if (progress > 90) progress = 90;
                    progressBar.css('width', progress + '%');
                    progressText.text(progress.toFixed(0) + '%');
                }, 200);

                $.ajax({
                    url: importForm.attr('action'),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        clearInterval(progressInterval);
                        progressBar.css('width', '100%');
                        progressText.text('100%');
                        setTimeout(() => importProgress.hide(), 500);

                        if (response.success) {
                            Swal.fire({
                                title: 'Éxito',
                                html: response.message + (response.stats ?
                                    `<br><small class="text-muted">Estadísticas: ${response.stats.success} exitosos, ${response.stats.errors} errores, ${response.stats.total} total</small>` : ''),
                                icon: 'success',
                                confirmButtonText: 'Aceptar',
                                confirmButtonColor: '#28a745'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = "{{ route('inventory-outputs.index') }}";
                                }
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: response.message,
                                icon: 'error',
                                confirmButtonText: 'Aceptar',
                                confirmButtonColor: '#dc3545'
                            });
                        }
                    },
                    error: function(xhr) {
                        clearInterval(progressInterval);
                        progressBar.css('width', '100%');
                        progressText.text('100%');
                        setTimeout(() => importProgress.hide(), 500);

                        let response = xhr.responseJSON;
                        if (response && response.errors && response.errors.length > 0) {
                            showErrorModal(response.errors, response.stats);

                            Swal.fire({
                                title: 'Errores en la Importación',
                                html: `
                                    <div>
                                        <p>Se encontraron <strong>${response.errors.length}</strong> errores durante la importación.</p>
                                        <p class="mb-3">Revise los detalles en el modal de errores que se ha abierto.</p>
                                        ${response.stats ?
                                            `<div class="alert alert-light border">
                                                <small>
                                                    <strong>Estadísticas:</strong><br>
                                                    - Éxitos: ${response.stats.success}<br>
                                                    - Errores: ${response.stats.errors}<br>
                                                    - Total: ${response.stats.total}
                                                </small>
                                            </div>` : ''}
                                    </div>
                                `,
                                icon: 'error',
                                confirmButtonText: 'Aceptar',
                                confirmButtonColor: '#dc3545',
                                width: '600px'
                            });
                        } else {
                            let errorMessage = 'Error desconocido';
                            if (response && response.message) {
                                errorMessage = response.message;
                            } else if (xhr.statusText) {
                                errorMessage = xhr.statusText;
                            }

                            Swal.fire({
                                title: 'Error',
                                text: errorMessage,
                                icon: 'error',
                                confirmButtonText: 'Aceptar',
                                confirmButtonColor: '#dc3545'
                            });
                        }
                    },
                    complete: function() {
                        importBtn.prop('disabled', false);
                        cancelBtn.prop('disabled', false);
                    }
                });
            }
        });
    });

    // Limpiar modal al cerrar
    $('#importMassiveModal').on('hidden.bs.modal', function() {
        if (reloadBtn.is(':visible')) {
            window.location.reload();
        }
        importForm[0].reset();
        filePreview.hide();
        importProgress.hide();
        importBtn.show().prop('disabled', false);
        cancelBtn.prop('disabled', false);
        reloadBtn.hide();
    });

    // Limpiar modal de errores al cerrar
    $('#errorDetailModal').on('hidden.bs.modal', function() {
        $('#errorContent').empty();
        $('#errorCount').text('');
    });
});
</script>

<!-- Estilos adicionales para el modal de errores -->
<style>
    /* Estilos para el modal de errores */
    #errorDetailModal .modal-dialog {
        max-width: 90%;
    }

    #errorDetailModal .error-container {
        font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
        font-size: 0.85em;
        line-height: 1.4;
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
        padding: 1rem;
    }

    #errorDetailModal .error-item {
        background-color: #fff;
        border-left: 3px solid #dc3545;
        padding: 0.5rem 1rem;
        margin-bottom: 0.5rem;
        border-radius: 0.25rem;
    }

    #errorDetailModal .error-item strong {
        color: #dc3545;
    }

    /* Estilos para los badges de estadísticas */
    #errorDetailModal .badge {
        font-size: 0.8em;
    }

    /* Estilos para el área de arrastrar archivos (opcional) */
    .drag-drop-area {
        border: 2px dashed #dee2e6;
        border-radius: 0.5rem;
        padding: 2rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .drag-drop-area:hover {
        border-color: #198754;
        background-color: #f8f9fa;
    }

    /* Estilos para las tablas */
    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid #ced4da;
        padding: 0.375rem 0.75rem;
    }

    /* Estilos para los modales */
    .modal-header {
        border-bottom: 1px solid #dee2e6;
        padding: 1rem;
    }

    .modal-header.bg-danger {
        background-color: #dc3545;
        color: white;
    }

    .modal-header.bg-success {
        background-color: #198754;
        color: white;
    }

    /* Estilos para los alerts */
    .alert {
        border-left: 4px solid;
    }

    .alert-info {
        border-left-color: #0dcaf0;
    }

    .alert-danger {
        border-left-color: #dc3545;
    }

    .alert-warning {
        border-left-color: #ffc107;
    }
</style>
@endsection
