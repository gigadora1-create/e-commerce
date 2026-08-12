@extends('layouts.app')
@section('contents')
<div class="d-flex justify-content-end align-items-center">
    @if(session('selected_customer'))
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
    <div class="container mt-4">
        <h2 class="text-center mb-4">Reporte de Producto en Retención</h2>
        <p class="text-center text-muted mb-4">Productos que no forman parte del stock disponible</p>
        @if($retentionItems->isEmpty())
            <div class="text-center py-5">
                <i class="fas fa-check-circle text-success" style="font-size: 4rem; margin-bottom: 1rem;"></i>
                <h5 class="text-muted">No hay items en retención</h5>
                <p class="text-muted">Actualmente no hay productos en estado de retención.</p>
                <a href="{{ route('inventories.index') }}" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i>Volver al Inventario
                </a>
            </div>
        @else
            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-warning text-dark">
                        <div class="card-body text-center">
                            <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                            <h5>Total en Retención</h5>
                            <h3 class="mb-0">{{ number_format($retentionItems->sum('total_retention')) }}</h3>
                            <small>Unidades totales</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-warehouse fa-2x mb-2"></i>
                            <h5>Bodegas Afectadas</h5>
                            <h3 class="mb-0">{{ $retentionItems->groupBy('warehouse')->count() }}</h3>
                            <small>Ubicaciones diferentes</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-secondary text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-box fa-2x mb-2"></i>
                            <h5>Productos Únicos</h5>
                            <h3 class="mb-0">{{ $retentionItems->groupBy('item_description')->count() }}</h3>
                            <small>Diferentes productos</small>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Important Notice -->
            <div class="alert alert-warning border-left-warning shadow mb-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle fa-lg me-3"></i>
                    <div>
                        <strong>Importante:</strong> Estos productos NO forman parte del stock disponible para operaciones normales.
                        Requieren revisión y decisión sobre su disposición final.
                    </div>
                </div>
            </div>
            <!-- Retention Items Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="retentionTable">
                    <thead class="thead-dark">
                        <tr>
                            <th>Acciones</th>
                            <th>Bodega</th>
                            <th>Producto</th>
                            <th>SKU</th>
                            <th>Cantidad</th>
                            <th>Motivos de Retención</th>
                            <th>Último Ingreso</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($retentionItems as $item)
                            <tr class="retention-row">
                                <td>
                                    <button type="button" class="btn btn-info btn-sm me-1"
                                            data-bs-toggle="tooltip"
                                            title="Ver detalles"
                                            onclick="viewDetails(
                                                '{{ $item->warehouse }}',
                                                '{{ $item->item_description }}',
                                                '{{ $item->sku }}',
                                                '{{ $item->retention_reasons }}',
                                                '{{ $item->retention_substatuses }}'
                                            )">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-success btn-sm"
                                            data-bs-toggle="tooltip"
                                            title="Liberar de retención"
                                            onclick="showReleaseModal(
                                                '{{ $item->warehouse }}',
                                                '{{ $item->item_description }}',
                                                {{ $item->total_retention }},
                                                {{ $item->item_id }},
                                                '{{ $item->location_code }}',
                                                '{{ $item->retention_substatuses }}'
                                            )">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </td>
                                <td>
                                    <span class="badge bg-secondary fs-6 px-3 py-2">
                                        {{ $item->warehouse }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <strong class="text-dark">{{ $item->item_description }}</strong>
                                        <small class="text-muted">Producto en retención</small>
                                    </div>
                                </td>
                                <td>
                                    <code class="bg-light p-2 rounded">{{ $item->sku }}</code>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-warning text-dark fs-5 px-3 py-2">
                                        {{ number_format($item->total_retention) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="reasons-container">
                                        @if($item->retention_reasons)
                                            <span class="badge bg-light text-dark me-1 mb-1 p-2">
                                                <i class="fas fa-exclamation-circle me-1"></i>{{ $item->retention_reasons }}
                                            </span>
                                        @else
                                            <span class="text-muted fst-italic">
                                                <i class="fas fa-question-circle me-1"></i>Sin especificar
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex flex-column align-items-center">
                                        <strong>{{ \Carbon\Carbon::parse($item->last_modified_date)->format('d/m/Y') }}</strong>
                                        <small class="text-muted">
                                            {{ \Carbon\Carbon::parse($item->last_modified_date)->diffForHumans() }}
                                        </small>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <!-- Action Button -->
            <div class="d-flex justify-content-start mt-4">
                <a href="{{ route('inventories.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Volver al Inventario
                </a>
            </div>
        @endif
    </div>
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<script>
$(document).ready(function(){
    // Inicializar DataTables
    var table = $('#retentionTable').DataTable({
        language: {
            "decimal": ",",
            "thousands": ".",
            "lengthMenu": "Mostrar _MENU_ entradas por página",
            "zeroRecords": "No se encontraron productos en retención",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ productos",
            "infoEmpty": "Mostrando 0 a 0 de 0 productos",
            "infoFiltered": "(filtrado de _MAX_ productos totales)",
            "search": "Buscar:",
            "paginate": {
                "first": "Primero",
                "last": "Último",
                "next": "Siguiente",
                "previous": "Anterior"
            }
        },
        responsive: true,
        columnDefs: [
            { orderable: false, targets: 0 } // Deshabilitar ordenación en la columna de acciones
        ],
        order: [[2, 'asc']] // Ordenar por producto por defecto
    });
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Función global para ver detalles
function viewDetails(warehouse, itemDescription, sku, retentionReasons, retentionSubstatuses) {
    Swal.fire({
        title: 'Detalles del Item en Retención',
        html: `
            <div class="text-start">
                <p><strong>Bodega:</strong> ${warehouse}</p>
                <p><strong>Producto:</strong> ${itemDescription}</p>
                <p><strong>SKU:</strong> ${sku}</p>
                <hr>
                <p><strong>Motivos de retención:</strong> ${retentionReasons || 'No especificado'}</p>
                <p><strong>Subestados de retención:</strong> ${retentionSubstatuses || 'No especificado'}</p>
                <hr>
                <small class="text-muted">Para ver más detalles, consulte el inventario principal.</small>
            </div>
        `,
        icon: 'info',
        confirmButtonText: 'Cerrar'
    });
}

// Función global para mostrar el modal de liberación
function showReleaseModal(warehouse, itemDescription, quantity, itemId, locationCode, retentionSubstatuses) {
    const substatusOptions = retentionSubstatuses.split(',').map(status => status.trim()).filter(status => status !== '');

    if (substatusOptions.length === 0) {
        Swal.fire({
            title: 'Error',
            text: 'No hay subestados de retención disponibles para este producto.',
            icon: 'error',
            confirmButtonText: 'Aceptar'
        });
        return;
    }

    let selectHTML = '<select id="substatusSelect" class="swal2-select" style="width: 100%;">';
    substatusOptions.forEach(substatus => {
        selectHTML += `<option value="${substatus}">${substatus}</option>`;
    });
    selectHTML += '</select>';

    Swal.fire({
        title: 'Liberar de Retención',
        html: `
            <div class="text-start">
                <p><strong>Producto:</strong> ${itemDescription}</p>
                <p><strong>Bodega:</strong> ${warehouse}</p>
                <p><strong>Cantidad disponible en retención:</strong> ${quantity}</p>
                <hr>
                <div class="mb-3">
                    <label for="quantityToRelease" class="form-label">Cantidad a liberar:</label>
                    <input type="number" id="quantityToRelease" class="swal2-input" value="${quantity}" min="1" max="${quantity}" required>
                </div>
                <div class="mb-3">
                    <label for="substatusSelect" class="form-label">Subestado de retención:</label>
                    ${selectHTML}
                </div>
                <div class="mb-3">
                    <label for="releaseReason" class="form-label">Motivo de liberación (opcional):</label>
                    <textarea id="releaseReason" class="swal2-textarea" placeholder="Ej: Producto revisado y apto para venta"></textarea>
                </div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Liberar',
        cancelButtonText: 'Cancelar',
        focusConfirm: false,
        preConfirm: () => {
            const quantityToRelease = document.getElementById('quantityToRelease').value;
            const retentionSubstatus = document.getElementById('substatusSelect').value;
            const releaseReason = document.getElementById('releaseReason').value;

            if (!quantityToRelease || isNaN(quantityToRelease) || quantityToRelease <= 0 || quantityToRelease > quantity) {
                Swal.showValidationMessage('La cantidad debe ser un número válido y no puede exceder la cantidad en retención.');
                return false;
            }

            if (!retentionSubstatus) {
                Swal.showValidationMessage('Debes seleccionar un subestado de retención.');
                return false;
            }

            return {
                quantityToRelease: quantityToRelease,
                retentionSubstatus: retentionSubstatus,
                releaseReason: releaseReason
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const { quantityToRelease, retentionSubstatus, releaseReason } = result.value;
            releaseFromRetention(warehouse, itemDescription, quantityToRelease, itemId, locationCode, retentionSubstatus, releaseReason);
        }
    });
}

// Función para enviar la solicitud de liberación al backend
function releaseFromRetention(warehouse, itemDescription, quantityToRelease, itemId, locationCode, retentionSubstatus, releaseReason) {
    const customer = "{{ session('selected_customer') }}";

    Swal.fire({
        title: 'Procesando...',
        text: 'Liberando producto de retención...',
        icon: 'info',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });

    fetch('/inventories/release-retention', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            item_id: itemId,
            warehouse: warehouse,
            customer: customer,
            location_code: locationCode,
            retention_substatus: retentionSubstatus,
            quantity_to_release: quantityToRelease,
            release_reason: releaseReason
        })
    })
    .then(async response => {
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const errorMessage = await response.text();
            throw new Error(`El servidor no devolvió JSON. Respuesta: ${errorMessage.substring(0, 200)}...`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'Éxito',
                text: data.message,
                icon: 'success',
                confirmButtonText: 'Aceptar'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                title: 'Error',
                text: data.message || 'Error desconocido al liberar el producto.',
                icon: 'error',
                confirmButtonText: 'Aceptar'
            });
        }
    })
    .catch(error => {
        console.error('Error detallado:', error);
        let errorMessage = 'Hubo un problema al liberar el producto.';
        if (error.message.includes('HTML')) {
            errorMessage = 'El servidor no respondió correctamente. Por favor, recarga la página e intenta nuevamente.';
        } else if (error.message.includes('ValidationException')) {
            errorMessage = 'Error de validación. Por favor, verifica los datos ingresados.';
        }
        Swal.fire({
            title: 'Error',
            text: errorMessage,
            icon: 'error',
            confirmButtonText: 'Aceptar'
        });
    });
}
</script>
<style>
    .border-left-warning {
        border-left: 4px solid #ffc107 !important;
    }

    .retention-row:hover {
        background-color: #fff3cd;
        transition: background-color 0.3s ease;
    }

    .reasons-container {
        max-width: 300px;
    }

    .thead-dark {
        background-color: #343a40;
        color: white;
    }

    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: 1px solid rgba(0, 0, 0, 0.125);
    }

    .btn-group .btn {
        margin: 0 1px;
    }

    .swal2-select, .swal2-input, .swal2-textarea {
        margin-bottom: 1rem;
    }
</style>
@endsection
