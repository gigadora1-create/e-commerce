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
    <h2 class="text-center mb-4"><i class="fas fa-boxes"></i> Picking: {{ $pickingOrder->picking_code }}</h2>

    <div class="mb-3 text-end">
        <a href="{{ route('picking.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
        <a href="{{ route('picking.export', $pickingOrder->id) }}" class="btn btn-success">
            <i class="fas fa-file-excel"></i> Exportar
        </a>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center bg-dark text-white">
                <div class="card-body">
                    <h6 class="text-white">Estado</h6>
                    <h4>
                        @if($pickingOrder->status === 'pending')
                            <span class="badge bg-secondary">Pendiente</span>
                        @elseif($pickingOrder->status === 'in_progress')
                            <span class="badge bg-warning text-dark">En Progreso</span>
                        @elseif($pickingOrder->status === 'completed')
                            <span class="badge bg-success">Completado</span>
                        @else
                            <span class="badge bg-danger">Cancelado</span>
                        @endif
                    </h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-dark text-white">
                <div class="card-body">
                    <h6 class="text-white"><i class="fas fa-warehouse"></i> Bodega</h6>
                    <h5>{{ $pickingOrder->warehouse }}</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-dark text-white">
                <div class="card-body">
                    <h6 class="text-white"><i class="fas fa-list"></i> Total Filas Cargadas</h6>
                    <h5>{{ $pickingOrder->total_items }}</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-dark text-white">
                <div class="card-body">
                    <h6 class="text-white"><i class="fas fa-box"></i> Cantidad Total Productos</h6>
                    <h5>{{ number_format($pickingOrder->total_quantity) }}</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="fas fa-info-circle"></i> Información General</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <p><strong><i class="fas fa-user"></i> Usuario:</strong> {{ $pickingOrder->user->name ?? 'N/A' }}</p>
                </div>
                <div class="col-md-3">
                    <p><strong><i class="fas fa-building"></i> Cliente:</strong> {{ $pickingOrder->customer }}</p>
                </div>
                <div class="col-md-3">
                    <p><strong><i class="fas fa-calendar-plus"></i> Fecha Creación:</strong> 
                        @if($pickingOrder->created_at)
                            {{ is_string($pickingOrder->created_at) ? \Carbon\Carbon::parse($pickingOrder->created_at)->format('d/m/Y ') : $pickingOrder->created_at->format('d/m/Y ') }}
                        @else
                            N/A
                        @endif
                    </p>
                </div>
                <div class="col-md-3">
                    <p><strong><i class="fas fa-calendar-check"></i> Fecha Completado:</strong> 
                        @if($pickingOrder->completed_at)
                            {{ is_string($pickingOrder->completed_at) ? \Carbon\Carbon::parse($pickingOrder->completed_at)->format('d/m/Y ') : $pickingOrder->completed_at->format('d/m/Y ') }}
                        @else
                            N/A
                        @endif
                    </p>
                </div>
            </div>
            @if($pickingOrder->order_number)
            <div class="row mt-2">
                <div class="col-md-12">
                    <p><strong><i class="fas fa-receipt"></i> Número de Pedido:</strong> {{ $pickingOrder->order_number }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Detalle de Productos -->
    <div class="card mb-4">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-clipboard-list"></i> Detalle de Productos</h5>
            <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#skuSummaryModal">
                <i class="fas fa-chart-bar me-1"></i> Ver Resumen por SKU
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="detailsTable">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th>
                            <th>Código Picking</th>
                            <th>SKU</th>
                            <th>Producto</th>
                            <th>Ubicación</th>
                            <th>Nombre Ubicación</th>
                            <th>Lote</th>
                            <th>F. Vencimiento</th>
                            <th class="text-center">Cant. Solicitada</th>
                            <th class="text-center">Cant. a Sacar</th>
                            <th class="text-center">
                                {{ $pickingOrder->status === 'completed' ? 'Stock Actual' : 'Stock Disponible' }}
                            </th>
                        </tr>
                    </thead>
                 <tbody>
    @php $counter = 0; @endphp
    @foreach($pickingOrder->grouped_details as $detail)
        @php $counter++; @endphp
        <tr>
            <td>{{ $counter }}</td>
            <td><strong>{{ $pickingOrder->picking_code }}</strong></td>
            <td><code>{{ $detail->sku }}</code></td>
            <td>
                {{ $detail->item_description }}
                @if($detail->detail_count > 1)
                    <br><small class="text-muted">
                        <i class="fas fa-layer-group"></i> Agrupado ({{ $detail->detail_count }} registros)
                    </small>
                @endif
            </td>
            <td><strong>{{ $detail->location_code }}</strong></td>
            <td>
                @if(isset($detail->location_name) && $detail->location_name)
                    <span class="badge bg-info text-dark">{{ $detail->location_name }}</span>
                @else
                    <span class="text-muted">Sin nombre</span>
                @endif
            </td>
            <td>{{ $detail->batch ?? 'N/A' }}</td>
            <td class="text-center">
                @if($detail->expiry_date && $detail->expiry_date != '0000-00-00')
                    @php
                        $expiryDate = is_string($detail->expiry_date) ? \Carbon\Carbon::parse($detail->expiry_date) : $detail->expiry_date;
                        $daysToExpiry = now()->diffInDays($expiryDate, false);
                        $badgeClass = $daysToExpiry < 30 ? 'bg-danger' : ($daysToExpiry < 90 ? 'bg-warning text-dark' : 'bg-success');
                    @endphp
                    <span class="badge {{ $badgeClass }}">
                        {{ $expiryDate->format('d/m/Y') }}
                    </span>
                    <br><small>({{ $daysToExpiry >= 0 ? $daysToExpiry : 'VENCIDO' }} días)</small>
                @else
                    <span class="text-muted">Sin fecha</span>
                @endif
            </td>
            <td class="text-center">
                <span class="badge bg-secondary" style="font-size: 0.9em;">
                    {{ number_format($detail->quantity_picked) }}
                </span>
                <br><small class="text-muted">total pedido</small>
            </td>
            <td class="text-center">
                <span class="badge bg-primary" style="font-size: 1.1em;">
                    {{ number_format($detail->quantity_picked) }}
                </span>
                <br><small class="text-muted">de esta ubicación</small>
            </td>
            <td class="text-center">
                @if(isset($detail->quantity_current))
                    <strong class="text-success" style="font-size: 1.2em;">
                        {{ number_format($detail->quantity_current) }}
                    </strong>
                    <br><small class="text-muted">
                        {{ $pickingOrder->status === 'completed' ? 'stock actual' : 'en ubicación' }}
                    </small>
                    
                    @if($pickingOrder->status !== 'completed' && isset($detail->quantity_reserved) && $detail->quantity_reserved > 0)
                        <br><span class="badge bg-warning text-dark" style="font-size: 0.75em;">
                            {{ number_format($detail->quantity_reserved) }} reservadas
                        </span>
                    @endif
                    
                    @if($pickingOrder->status !== 'completed' && isset($detail->quantity_net_available))
                        <br><small class="text-info">
                            ({{ number_format($detail->quantity_net_available) }} libres)
                        </small>
                    @endif
                @else
                    <span class="badge bg-secondary">Agotado</span>
                @endif
            </td>
        </tr>
    @endforeach
</tbody>
<tfoot class="thead-dark">
    <tr>
        <th colspan="8" class="text-end">TOTALES:</th>
        <th class="text-center">{{ number_format($pickingOrder->grouped_details->sum('quantity_picked')) }}</th>
        <th class="text-center">{{ number_format($pickingOrder->grouped_details->sum('quantity_picked')) }}</th>
        <th></th>
    </tr>
</tfoot>
                </table>
            </div>
        </div>
    </div>

    @if($pickingOrder->status === 'in_progress' || $pickingOrder->status === 'pending')
    <div class="mt-4 text-center">
        <button class="btn btn-success btn-lg me-2 completeBtn" data-id="{{ $pickingOrder->id }}">
            <i class="fas fa-check-circle"></i> Finalizar Picking y Generar Salidas
        </button>
        <button class="btn btn-danger btn-lg cancelBtn" data-id="{{ $pickingOrder->id }}">
            <i class="fas fa-times-circle"></i> Cancelar Picking
        </button>
    </div>
    @endif

    @if($pickingOrder->status === 'completed')
    <div class="alert alert-success mt-3">
        <h5><i class="fas fa-check-circle"></i> Picking Completado</h5>
        <p class="mb-0">
            Este picking fue finalizado el 
            @if($pickingOrder->completed_at)
                {{ is_string($pickingOrder->completed_at) ? \Carbon\Carbon::parse($pickingOrder->completed_at)->format('d/m/Y ') : $pickingOrder->completed_at->format('d/m/Y ') }}
            @else
                N/A
            @endif. 
            Las salidas de inventario han sido generadas correctamente con la guía: <strong>{{ $pickingOrder->picking_code }}</strong>
        </p>
    </div>
    @endif

    @if($pickingOrder->status === 'cancelled')
    <div class="alert alert-danger mt-3">
        <h5><i class="fas fa-ban"></i> Picking Cancelado</h5>
        <p class="mb-0">Este picking fue cancelado y las reservas fueron liberadas.</p>
    </div>
    @endif
</div>

<!-- Modal de Resumen por SKU -->
<div class="modal fade" id="skuSummaryModal" tabindex="-1" aria-labelledby="skuSummaryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="skuSummaryModalLabel">
                    <i class="fas fa-chart-bar"></i> Resumen por SKU - {{ $pickingOrder->picking_code }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @php
                    $groupedDetails = $pickingOrder->details->groupBy('sku');
                @endphp
                @foreach($groupedDetails as $sku => $details)
                    <div class="alert alert-info mb-3">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <strong><i class="fas fa-box"></i> {{ $sku }} - {{ $details->first()->item_description }}</strong>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="badge bg-primary">Solicitado: {{ number_format($details->first()->quantity_requested) }}</span>
                                <span class="badge bg-success">A recoger: {{ number_format($details->sum('quantity_picked')) }}</span>
                                <span class="badge bg-secondary">{{ $details->count() }} ubicaciones</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
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
    $('#detailsTable').DataTable({
        language: {
            "decimal": ",",
            "thousands": ".",
            "lengthMenu": "Mostrar _MENU_ entradas por página",
            "zeroRecords": "No se encontraron detalles",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
            "infoEmpty": "Mostrando 0 a 0 de 0 registros",
            "infoFiltered": "(filtrado de _MAX_ registros totales)",
            "search": "Buscar:",
            "paginate": {
                "first": "Primero",
                "last": "Último",
                "next": "Siguiente",
                "previous": "Anterior"
            }
        },
        responsive: true,
        pageLength: 10,
        order: [[0, 'asc']],
        scrollX: true
    });

    // Mostrar alertas de sesión con SweetAlert2
    @if(session('success'))
        Swal.fire({
            title: '¡Éxito!',
            text: "{{ session('success') }}",
            icon: 'success',
            confirmButtonText: 'Aceptar'
        });
    @endif

    @if(session('error'))
        Swal.fire({
            title: 'Error',
            text: "{{ session('error') }}",
            icon: 'error',
            confirmButtonText: 'Aceptar'
        });
    @endif

    // Finalizar picking
    $(document).on('click', '.completeBtn', function(){
        let id = $(this).data('id');
        Swal.fire({
            title: '¿Estás seguro?',
            html: '¿Deseas finalizar este picking?<br><br>' +
                  '<strong>Se generarán las salidas de inventario y no se podrá revertir.</strong>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, finalizar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading
                Swal.fire({
                    title: 'Procesando...',
                    text: 'Finalizando picking y generando salidas',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: "{{ route('picking.complete', ['id' => ':id']) }}".replace(':id', id),
                    type: "POST",
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    },
                    success: function(res) {
                        Swal.fire({
                            title: '¡Completado!',
                            text: 'Picking finalizado y salidas generadas correctamente',
                            icon: 'success',
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            window.location.href = "{{ route('picking.index') }}";
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            title: 'Error',
                            html: xhr.responseJSON?.message || 'Ocurrió un error al finalizar el picking',
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        });
                    }
                });
            }
        });
    });

    // Cancelar picking
    $(document).on('click', '.cancelBtn', function(){
        let id = $(this).data('id');
        Swal.fire({
            title: '¿Estás seguro?',
            html: '¿Deseas cancelar este picking?<br><br>' +
                  '<strong>Se liberarán todas las reservas.</strong>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, cancelar',
            cancelButtonText: 'No'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('picking.cancel', ['id' => ':id']) }}".replace(':id', id),
                    type: "POST",
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    },
                    success: function(res) {
                        Swal.fire({
                            title: '¡Cancelado!',
                            text: res.message || 'Picking cancelado correctamente',
                            icon: 'success',
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            window.location.href = "{{ route('picking.index') }}";
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            title: 'Error',
                            html: xhr.responseJSON?.message || 'Ocurrió un error al cancelar el picking',
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        });
                    }
                });
            }
        });
    });
});
</script>

<style>
    .thead-dark {
        background-color: #343a40;
        color: white;
    }
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .badge {
        margin: 2px;
    }
    .card.bg-dark .text-white {
        color: white !important;
    }
    table.dataTable tbody tr:hover {
        background-color: #f8f9fa;
    }
    .modal-xl {
        max-width: 95%;
    }
    .btn-close-white {
        filter: invert(1) grayscale(100%) brightness(200%);
    }
</style>
@endsection
