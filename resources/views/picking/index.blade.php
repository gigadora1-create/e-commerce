@extends('layouts.app')
@section('contents')
    <div class="container-fluid mt-4">
        <div class="row mb-4">
            <div class="col-12 text-center mb-3">
                <h2>Gestión de Picking</h2>
            </div>
            <div class="col-12 d-flex justify-content-between align-items-center mb-3">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                    Importar Picking
                </button>
                @if(session('selected_customer'))
                    <div class="alert alert-info d-inline-flex align-items-center mb-0 py-2 px-3">
                        <strong>Cliente: {{ session('selected_customer') }}</strong>
                    </div>
                @else
                    <button class="btn btn-primary" id="selectCustomerBtn">
                        Seleccionar Cliente
                    </button>
                @endif
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Listado de Picking</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="pickingTable">
                        <thead class="thead-dark">
                            <tr>
                                <th>Código</th>
                                <th>Bodega</th>
                                <th>SKU</th>
                                <th>Producto</th>
                                <th>Ubicación</th>
                                <th>Cantidad</th>
                                <th>Estado</th>
                                <th>Usuario</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pickingOrders as $order)
                                <tr>
                                    <td><strong>{{ $order->picking_code ?? 'N/A' }}</strong></td>
                                    <td>{{ $order->warehouse ?? 'N/A' }}</td>
                                    <td>
                                        @if(!empty($order->skus))
                                            @foreach(array_slice(explode(',', $order->skus), 0, 2) as $sku)
                                                <span class="badge bg-secondary">{{ $sku }}</span>
                                            @endforeach
                                            @if(count(explode(',', $order->skus)) > 2)
                                                <span class="badge bg-info">+{{ count(explode(',', $order->skus)) - 2 }}</span>
                                            @endif
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!empty($order->productos))
                                            {{ \Illuminate\Support\Str::limit(explode(',', $order->productos)[0] ?? 'N/A', 30) }}
                                            @if(count(explode(',', $order->productos)) > 1)
                                                <small class="text-muted">(+{{ count(explode(',', $order->productos)) - 1 }}
                                                    más)</small>
                                            @endif
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!empty($order->ubicaciones))
                                            @foreach(array_slice(explode(',', $order->ubicaciones), 0, 1) as $ubicacion)
                                                <span class="badge bg-primary">{{ $ubicacion }}</span>
                                            @endforeach
                                            @if(count(explode(',', $order->ubicaciones)) > 1)
                                                <span class="badge bg-info">+{{ count(explode(',', $order->ubicaciones)) - 1 }}
                                                    más</span>
                                            @endif
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td><strong>{{ number_format($order->total_quantity ?? 0) }}</strong></td>
                                    <td>
                                        @if($order->status === 'pending')
                                            <span class="badge bg-secondary">Pendiente</span>
                                        @elseif($order->status === 'in_progress')
                                            <span class="badge bg-warning text-dark">En Progreso</span>
                                        @elseif($order->status === 'completed')
                                            <span class="badge bg-success">Completado</span>
                                        @else
                                            <span class="badge bg-danger">Cancelado</span>
                                        @endif
                                    </td>
                                    <td>{{ $order->user_name ?? 'N/A' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y') }}</td>
                                    <td>
                                        <a href="{{ route('picking.show', $order->id) }}" class="btn btn-sm btn-info"
                                            title="Ver detalle">
                                            Ver
                                        </a>
                                        <!-- BOTÓN EXCEL MODIFICADO -->
                                        <a href="javascript:void(0)" class="btn btn-sm btn-success generate-excel"
                                            data-id="{{ $order->id }}" title="Exportar Excel">
                                            Excel
                                        </a>
                                        <!-- BOTÓN PDF MODIFICADO -->
                                        <a href="javascript:void(0)" class="btn btn-sm btn-primary generate-pdf"
                                            data-id="{{ $order->id }}" title="Generar PDF">
                                            PDF
                                        </a>
                                        @if($order->status === 'in_progress' || $order->status === 'pending')
                                            <button type="button" class="btn btn-sm btn-danger cancelBtn" data-id="{{ $order->id }}"
                                                data-code="{{ $order->picking_code }}" title="Cancelar">
                                                Cancelar
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr class="no-data-row">
                                    <td colspan="10" class="text-center py-4">
                                        <div class="text-muted">
                                            <strong>No hay registros de salidas</strong><br>
                                            <small>Utiliza el botón "Importar Picking" para comenzar</small>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Importación (tu original) -->
    <div class="modal fade" id="importModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Importar Picking</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="card-title">Descargar Plantilla</h6>
                                <p class="card-text text-muted">
                                    Descarga la plantilla de Excel con el formato correcto
                                </p>
                                <a href="{{ asset('documents/Archivo_Carga_Picking.xlsx') }}"
                                    class="btn btn-outline-primary">
                                    Descargar Plantilla Excel
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning">
                        <h6>Importante:</h6>
                        <ul class="mb-0 small">
                            <li><strong>NO ABANDONE esta página</strong> durante el proceso</li>
                            <li>El proceso puede tardar hasta 5 minutos dependiendo del tamaño del archivo</li>
                            <li>Se creará UNA SOLA orden de picking con todos los productos</li>
                            <li>Espere a que el proceso termine completamente</li>
                        </ul>
                    </div>

                    <form id="importForm" enctype="multipart/form-data">
                        @csrf
                        <div class="alert alert-info">
                            <h6>Formato requerido:</h6>
                            <ul class="mb-0">
                                <li><strong>SKU:</strong> Código del producto</li>
                                <li><strong>Producto:</strong> Nombre del producto</li>
                                <li><strong>Cantidad:</strong> Cantidad a sacar</li>
                                <li><strong>Bodega:</strong> Nombre de la bodega</li>
                                <li><strong>Cliente:</strong> Nombre del cliente</li>
                                <li><strong>Pedido:</strong> Número de pedido</li>
                            </ul>
                        </div>
                        <div class="mb-3">
                            <label for="file" class="form-label">
                                Archivo Excel <span class="text-danger">*</span>
                            </label>
                            <input type="file" class="form-control" id="file" name="file" accept=".xlsx,.xls" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                                id="closeModalBtn">Cerrar</button>
                            <button type="submit" class="btn btn-primary" id="uploadBtn">
                                Cargar y Procesar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Progreso Importación (tu original) -->
    <div class="modal fade" id="progressModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        Procesando Importación
                    </h5>
                </div>
                <div class="modal-body text-center py-5">
                    <div class="alert alert-warning mb-4">
                        <h6>¡NO CIERRE ESTA VENTANA!</h6>
                        <p class="mb-0 small">
                            El proceso está en curso. Puede tardar hasta 5 minutos.
                        </p>
                    </div>

                    <div class="spinner-border text-primary" role="status" style="width: 4rem; height: 4rem;">
                        <span class="visually-hidden">Cargando...</span>
                    </div>

                    <div class="mt-4">
                        <h5 id="progressMessage">Procesando archivo...</h5>
                        <p class="text-muted" id="progressDetails">
                            Esto puede tomar varios minutos dependiendo del tamaño del archivo.
                        </p>
                        <div class="mt-3">
                            <h6>Tiempo transcurrido: <span id="timerDisplay">00:00</span></h6>
                        </div>
                    </div>

                    <div class="progress mt-4" style="height: 30px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" role="progressbar"
                            style="width: 100%" id="progressBar">
                            <span class="fw-bold">Procesando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- NUEVO: Modal para Generar Excel (VERDE) -->
    <div class="modal fade" id="excelProgressModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        Generando Excel
                    </h5>
                </div>
                <div class="modal-body text-center py-5">
                    <div class="spinner-border text-success" role="status" style="width: 4rem; height: 4rem;">
                        <span class="visually-hidden">Generando...</span>
                    </div>
                    <div class="mt-4">
                        <h5 id="excelProgressMessage">Exportando datos...</h5>
                        <p class="text-muted" id="excelProgressDetails">
                            Esto puede tomar un momento Por favor, espera.
                        </p>
                        <div class="mt-3">
                            <h6>Tiempo transcurrido: <span id="excelTimerDisplay">00:00</span></h6>
                        </div>
                    </div>
                    <div class="progress mt-4" style="height: 30px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width: 100%"
                            id="excelProgressBar">
                            <span class="fw-bold">Procesando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- NUEVO: Modal para Generar PDF (AZUL) -->
    <div class="modal fade" id="pdfProgressModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        Generando PDF
                    </h5>
                </div>
                <div class="modal-body text-center py-5">
                    <div class="spinner-border text-primary" role="status" style="width: 4rem; height: 4rem;">
                        <span class="visually-hidden">Generando...</span>
                    </div>
                    <div class="mt-4">
                        <h5 id="pdfProgressMessage">Creando documento...</h5>
                        <p class="text-muted" id="pdfProgressDetails">
                            Esto puede tomar un momento Por favor, espera.
                        </p>
                        <div class="mt-3">
                            <h6>Tiempo transcurrido: <span id="pdfTimerDisplay">00:00</span></h6>
                        </div>
                    </div>
                    <div class="progress mt-4" style="height: 30px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" style="width: 100%"
                            id="pdfProgressBar">
                            <span class="fw-bold">Procesando...</span>
                        </div>
                    </div>
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
        $(document).ready(function () {
            let processingInProgress = false;
            let timerInterval = null;
            let excelTimerInterval = null;
            let pdfTimerInterval = null;
            let excelSeconds = 0;
            let pdfSeconds = 0;

            // === TEMPORIZADOR ===
            function formatTime(seconds) {
                let mins = Math.floor(seconds / 60);
                let secs = seconds % 60;
                return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
            }

            function startTimer() {
                let secondsElapsed = 0;
                $('#timerDisplay').text(formatTime(secondsElapsed));
                timerInterval = setInterval(function () {
                    secondsElapsed++;
                    $('#timerDisplay').text(formatTime(secondsElapsed));
                }, 1000);
            }

            function startExcelTimer() {
                excelSeconds = 0;
                $('#excelTimerDisplay').text('00:00');
                excelTimerInterval = setInterval(function () {
                    excelSeconds++;
                    $('#excelTimerDisplay').text(formatTime(excelSeconds));
                }, 1000);
            }

            function startPdfTimer() {
                pdfSeconds = 0;
                $('#pdfTimerDisplay').text('00:00');
                pdfTimerInterval = setInterval(function () {
                    pdfSeconds++;
                    $('#pdfTimerDisplay').text(formatTime(pdfSeconds));
                }, 1000);
            }

            // === DATATABLE ===
            var rowCount = $('#pickingTable tbody tr').length;
            var emptyRowCount = $('#pickingTable tbody tr.no-data-row').length;
            var hasRealData = rowCount > emptyRowCount;

            if (hasRealData) {
                $('#pickingTable').DataTable({
                    language: {
                        "decimal": ",",
                        "thousands": ".",
                        "lengthMenu": "Mostrar _MENU_ entradas",
                        "zeroRecords": "No se encontraron salidas",
                        "info": "Mostrando _START_ a _END_ de _TOTAL_",
                        "infoEmpty": "Mostrando 0 a 0 de 0",
                        "infoFiltered": "(filtrado de _MAX_ totales)",
                        "search": "Buscar:",
                        "paginate": {
                            "first": "Primero",
                            "last": "Último",
                            "next": "Siguiente",
                            "previous": "Anterior"
                        }
                    },
                    order: [], // DESACTIVADO: Respetar orden del controlador (En Progreso primero)
                    columnDefs: [{ orderable: false, targets: 9 }],
                    pageLength: 10, // CAMBIADO: 10 filas por página
                    responsive: true
                });
            }

            // === BLOQUEO DE RECARGA ===
            window.addEventListener('beforeunload', function (e) {
                if (processingInProgress) {
                    e.preventDefault();
                    e.returnValue = '¡ATENCIÓN! El proceso de importación está en curso.';
                    return e.returnValue;
                }
            });

            // === MENSAJES DE SESIÓN ===
            @if(session('success'))
                Swal.fire({
                    title: 'Éxito',
                    html: '<pre style="text-align: left; white-space: pre-wrap;">{{ session('success') }}</pre>',
                    icon: 'success',
                    confirmButtonText: 'Aceptar',
                    width: '600px'
                });
            @endif
            @if(session('error'))
                Swal.fire({
                    title: 'Error',
                    html: '<pre style="text-align: left; white-space: pre-wrap;">{{ session('error') }}</pre>',
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    width: '600px'
                });
            @endif

            // === IMPORTACIÓN (tu código original) ===
            $('#importForm').on('submit', function (e) {
                e.preventDefault();
                const form = this;
                if (!form.checkValidity()) {
                    e.stopPropagation();
                    $(form).addClass('was-validated');
                    return;
                }

                let formData = new FormData(this);
                processingInProgress = true;
                $('#importModal').modal('hide');
                $('#progressModal').modal('show');
                startTimer();

                $('#progressMessage').text('Procesando archivo...');
                $('#progressDetails').text('Validando datos, verificando inventario y creando orden de picking.');

                let keepAliveInterval = setInterval(function () {
                    $.ajax({ url: "{{ route('picking.keep_alive') }}", type: "GET", timeout: 30000, error: function () { } });
                }, 30000);

                $.ajax({
                    url: "{{ route('picking.import') }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    timeout: 0,
                    xhr: function () {
                        var xhr = new window.XMLHttpRequest();
                        var messages = [
                            'Leyendo archivo Excel...',
                            'Validando estructura de datos...',
                            'Verificando disponibilidad de inventario...',
                            'Seleccionando ubicaciones óptimas...',
                            'Creando orden de picking...',
                            'Reservando inventario...',
                            'Finalizando proceso...'
                        ];
                        var messageIndex = 0;
                        var messageInterval = setInterval(function () {
                            if (messageIndex < messages.length) {
                                $('#progressMessage').text(messages[messageIndex]);
                                messageIndex++;
                            }
                        }, 8000);

                        xhr.addEventListener('loadend', function () {
                            clearInterval(messageInterval);
                            clearInterval(keepAliveInterval);
                            clearInterval(timerInterval);
                        });
                        return xhr;
                    },
                    success: function (response) {
                        processingInProgress = false;
                        $('#progressModal').modal('hide');
                        if (response.success) {
                            Swal.fire({
                                title: '¡Importación Exitosa!',
                                html: `
                                    <div class="alert alert-success mb-3">
                                        <h5>Orden Creada</h5>
                                        <p class="mb-0">
                                            <strong>Código:</strong> 
                                            <span class="badge bg-success fs-6">${response.message.replace('Salida generada exitosamente: ', '')}</span>
                                        </p>
                                    </div>
                                `,
                                icon: 'success',
                                confirmButtonText: 'Ver Listado'
                            }).then(() => location.reload());
                        } else {
                            Swal.fire('Error en Importación', response.message, 'error');
                        }
                    },
                    error: function (xhr) {
                        clearInterval(keepAliveInterval);
                        processingInProgress = false;
                        $('#progressModal').modal('hide');
                        clearInterval(timerInterval);
                        Swal.fire('Error', xhr.responseJSON?.message || 'Error al procesar', 'error');
                    }
                });
            });

            // === GENERAR EXCEL (NUEVO - VERDE) ===
            $(document).on('click', '.generate-excel', function () {
                const id = $(this).data('id');
                const url = `{{ route('picking.export', ':id') }}`.replace(':id', id);

                const modal = $('#excelProgressModal');
                modal.modal('show');
                startExcelTimer();
                $('#excelProgressMessage').text('Exportando datos a Excel...');
                $('#excelProgressDetails').text('Esto puede tomar un momento');

                fetch(url, {
                    method: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(response => {
                        if (!response.ok) throw new Error('Error en el servidor');
                        const contentLength = response.headers.get('content-length');
                        let loaded = 0;

                        return new Response(
                            new ReadableStream({
                                start(controller) {
                                    const reader = response.body.getReader();
                                    function pump() {
                                        return reader.read().then(({ done, value }) => {
                                            if (done) { controller.close(); return; }
                                            loaded += value.byteLength;
                                            if (contentLength) {
                                                const percent = Math.round((loaded / contentLength) * 100);
                                                $('#excelProgressBar').css('width', percent + '%').text(percent + '%');
                                            }
                                            controller.enqueue(value);
                                            return pump();
                                        });
                                    }
                                    return pump();
                                }
                            })
                        ).blob();
                    })
                    .then(blob => {
                        clearInterval(excelTimerInterval);
                        const fileName = `salida_${id}.xlsx`;
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = fileName;
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);

                        $('#excelProgressMessage').html('¡Excel generado con éxito!');
                        $('#excelProgressDetails').html('Descarga iniciada.');
                        setTimeout(() => modal.modal('hide'), 1500);
                    })
                    .catch(err => {
                        clearInterval(excelTimerInterval);
                        modal.modal('hide');
                        Swal.fire('Error', 'No se pudo generar el Excel.', 'error');
                    });
            });

            // === GENERAR PDF (AZUL) ===
            $(document).on('click', '.generate-pdf', function () {
                const id = $(this).data('id');
                const url = `{{ route('picking.pdf', ':id') }}`.replace(':id', id);

                const modal = $('#pdfProgressModal');
                modal.modal('show');
                startPdfTimer();
                $('#pdfProgressMessage').text('Generando PDF...');
                $('#pdfProgressDetails').text('Esto puede tomar un momento');

                fetch(url, {
                    method: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(response => {
                        if (!response.ok) throw new Error('Error en el servidor');
                        const contentLength = response.headers.get('content-length');
                        let loaded = 0;

                        return new Response(
                            new ReadableStream({
                                start(controller) {
                                    const reader = response.body.getReader();
                                    function pump() {
                                        return reader.read().then(({ done, value }) => {
                                            if (done) { controller.close(); return; }
                                            loaded += value.byteLength;
                                            if (contentLength) {
                                                const percent = Math.round((loaded / contentLength) * 100);
                                                $('#pdfProgressBar').css('width', percent + '%').text(percent + '%');
                                            }
                                            controller.enqueue(value);
                                            return pump();
                                        });
                                    }
                                    return pump();
                                }
                            })
                        ).blob();
                    })
                    .then(blob => {
                        clearInterval(pdfTimerInterval);
                        const fileName = `alistamiento_${id}.pdf`;
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = fileName;
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);

                        $('#pdfProgressMessage').html('¡PDF generado con éxito!');
                        $('#pdfProgressDetails').html('Descarga iniciada.');
                        setTimeout(() => modal.modal('hide'), 1500);
                    })
                    .catch(err => {
                        clearInterval(pdfTimerInterval);
                        modal.modal('hide');
                        Swal.fire('Error', 'No se pudo generar el PDF.', 'error');
                    });
            });

            // === CANCELAR PICKING ===
            $(document).on('click', '.cancelBtn', function () {
                let id = $(this).data('id');
                let code = $(this).data('code');

                Swal.fire({
                    title: '¿Estás seguro?',
                    html: `¿Deseas cancelar la salida <strong>${code}</strong>?<br><small>Las reservas serán liberadas.</small>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, cancelar',
                    cancelButtonText: 'No'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('picking.cancel', ['id' => ':id']) }}".replace(':id', id),
                            type: "POST",
                            data: { _token: "{{ csrf_token() }}" },
                            success: function (res) {
                                Swal.fire('Cancelado', res.message, 'success').then(() => location.reload());
                            },
                            error: function (xhr) {
                                Swal.fire('Error', xhr.responseJSON?.message || 'Error al cancelar', 'error');
                            }
                        });
                    }
                });
            });

            $('#importModal').on('hidden.bs.modal', function () {
                if (!processingInProgress) {
                    $('#importForm')[0].reset();
                    $('#importForm').removeClass('was-validated');
                }
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
        }

        .card {
            border: none;
            border-radius: 10px;
        }

        .badge {
            font-size: 0.85em;
            padding: 0.35em 0.65em;
            margin: 2px;
        }

        .table th {
            white-space: nowrap;
            font-size: 0.9rem;
        }

        .table td {
            vertical-align: middle;
            font-size: 0.9rem;
        }

        .no-data-row td {
            background-color: #f8f9fa !important;
        }

        .progress {
            height: 30px;
            border-radius: 15px;
        }

        #progressModal .modal-content,
        #excelProgressModal .modal-content,
        #pdfProgressModal .modal-content {
            border: 3px solid #17a2b8;
            border-radius: 15px;
        }

        #excelProgressModal .modal-header {
            background-color: #28a745;
        }

        #excelProgressModal .spinner-border {
            --bs-spinner-border-color: #28a745;
        }

        #excelProgressModal .progress-bar {
            background-color: #28a745;
        }

        #pdfProgressModal .modal-header {
            background-color: #0d6efd;
        }

        #pdfProgressModal .spinner-border {
            --bs-spinner-border-color: #0d6efd;
        }

        #progressMessage,
        #excelProgressMessage,
        #pdfProgressMessage {
            color: #0d6efd;
            font-weight: 600;
        }

        #timerDisplay,
        #excelTimerDisplay,
        #pdfTimerDisplay {
            font-family: monospace;
            font-size: 1.2rem;
            color: #dc3545;
        }
    </style>
@endsection