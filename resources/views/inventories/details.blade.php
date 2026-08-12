@extends('layouts.app')

@section('contents')
<div class="container mt-4">
    <h2 class="text-center mb-4">Detalle de Inventario</h2>

    <div class="mb-4 d-flex flex-wrap align-items-center gap-2">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exportModal">
            <i class="fas fa-file-export me-2"></i> Exportar
        </button>
        <a href="{{ route('inventories.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i> Vista Consolidada
        </a>
        <button class="btn btn-success" id="groupBtn">
            <i class="fas fa-object-group me-2"></i> Documentos
        </button>
        @if (session('selected_customer'))
            <div class="alert alert-info d-inline-flex align-items-center mb-0 py-2 px-3 ms-auto">
                <i class="fas fa-user me-2"></i>
                <strong>Cliente: {{ session('selected_customer') }}</strong>
            </div>
        @endif
    </div>

<div class="table-responsive">
    <table class="table table-bordered table-striped" id="detailedTable">
        <thead class="thead-dark">
            <tr>
                <th style="width: 50px;"><input type="checkbox" id="selectAll"></th>
                <th style="width: 120px;">Acciones</th>
                <th style="width: 100px;">SKU</th>
                <th style="width: 100px;">Estado</th>
                <th style="width: 100px;">Lote</th>
                <th style="width: 120px;">Fecha de Expiración</th>
                <th style="width: 120px;">Condición del Artículo</th>
                <th style="width: 120px;">Fecha de Ingreso</th>
                <th style="width: 100px;">Almacén</th>
                <th style="width: 150px;">Localización</th>
                <th style="width: 100px;">Comercio</th>
                <th style="width: 150px;">Descripción del Artículo</th>
                <th style="width: 80px;">Cantidad</th>
                <th style="width: 80px;">Valor</th>
                <th style="width: 80px;">Tipo</th>
                <th style="width: 150px;">Observaciones</th>
                <th style="width: 120px;">Documento Ingreso</th>
                <th style="width: 100px;">Documento</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($detailedInventories as $inventory)
                @if ($inventory->customer == session('selected_customer'))
                    @php
                        // Formatear fechas para mostrar y para edición
                        $expiryDateDisplay = 'N/A';
                        $expiryDateValue = null;
                        if ($inventory->expiry_date && $inventory->expiry_date != '0000-00-00' && $inventory->expiry_date != '0000-00-00 00:00:00') {
                            try {
                                $carbonDate = is_string($inventory->expiry_date) 
                                    ? \Carbon\Carbon::parse($inventory->expiry_date) 
                                    : $inventory->expiry_date;
                                $expiryDateDisplay = $carbonDate->format('d/m/Y');
                                $expiryDateValue = $carbonDate->format('Y-m-d');
                            } catch (\Exception $e) {
                                $expiryDateDisplay = 'N/A';
                            }
                        }

                        $entryDateDisplay = 'N/A';
                        $entryDateValue = null;
                        if ($inventory->entry_date && $inventory->entry_date != '0000-00-00' && $inventory->entry_date != '0000-00-00 00:00:00') {
                            try {
                                $carbonDate = is_string($inventory->entry_date) 
                                    ? \Carbon\Carbon::parse($inventory->entry_date) 
                                    : $inventory->entry_date;
                                $entryDateDisplay = $carbonDate->format('d/m/Y');
                                $entryDateValue = $carbonDate->format('Y-m-d');
                            } catch (\Exception $e) {
                                $entryDateDisplay = 'N/A';
                            }
                        }

                        // Obtener localización directamente del campo
                        $localizacion = $inventory->localizacion ?? 'N/A';
                    @endphp
                    <tr @if($inventory->entry_document) class="bg-danger text-white" @endif>
                        <td>
                            @if(!$inventory->entry_document)
                                <input type="checkbox" class="selectRow" data-id="{{ $inventory->id }}">
                            @endif
                        </td>
                        <td>
                            <button class="btn btn-warning btn-sm editBtn"
                                data-id="{{ $inventory->id }}"
                                data-sku="{{ $inventory->sku }}"
                                data-status="{{ $inventory->status }}"
                                data-batch="{{ $inventory->batch }}"
                                data-expiry_date="{{ $expiryDateValue }}"
                                data-item_condition="{{ $inventory->item_condition }}"
                                data-entry_date="{{ $entryDateValue }}"
                                data-warehouse="{{ $inventory->warehouse }}"
                                data-localizacion="{{ $inventory->localizacion }}"
                                data-commerce="{{ $inventory->commerce }}"
                                data-item_description="{{ $inventory->item_description }}"
                                data-quantity="{{ $inventory->quantity }}"
                                data-value="{{ $inventory->value }}"
                                data-type="{{ $inventory->type }}"
                                data-observations="{{ $inventory->observations }}"
                                data-entry_document="{{ $inventory->entry_document }}"
                                data-document_path="{{ $inventory->document_path }}">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="btn btn-danger btn-sm deleteBtn"
                                data-id="{{ $inventory->id }}">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        </td>
                        <td>{{ $inventory->sku }}</td>
                        <td>{{ $inventory->status }}</td>
                        <td>{{ $inventory->batch }}</td>
                        <td>{{ $expiryDateDisplay }}</td>
                        <td>{{ $inventory->item_condition }}</td>
                        <td>{{ $entryDateDisplay }}</td>
                        <td>{{ $inventory->warehouse }}</td>
                        <td><span class="badge bg-info">{{ $localizacion }}</span></td>
                        <td>{{ $inventory->commerce }}</td>
                        <td>{{ $inventory->item_description }}</td>
                        <td>{{ $inventory->quantity }}</td>
                        <td>{{ $inventory->value }}</td>
                        <td>{{ $inventory->type }}</td>
                        <td>{{ $inventory->observations }}</td>
                        <td>{{ $inventory->entry_document ?? 'N/A' }}</td>
                        <td>
                            @if ($inventory->document_path)
                                <a href="{{ asset($inventory->document_path) }}" target="_blank" class="btn btn-sm btn-info">
                                    <i class="fas fa-file-pdf"></i> Ver PDF
                                </a>
                            @else
                                N/A
                            @endif
                        </td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>
</div>

<!-- Modal Editar -->
<div class="modal fade" id="editInventoryModal" tabindex="-1" aria-labelledby="editInventoryModalLabel">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" value="PUT">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="editInventoryModalLabel">Editar Inventario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_item_description" class="form-label">Descripción del Artículo <span class="text-danger">*</span></label>
                            <select name="item_description" id="edit_item_description" class="form-select" required>
                                <option value="">Seleccione</option>
                                @foreach ($uniqueProducts as $product)
                                    <option value="{{ $product }}">{{ $product }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_sku" class="form-label">SKU <span class="text-danger">*</span></label>
                            <input type="text" name="sku" id="edit_sku" class="form-control" readonly required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_status" class="form-label">Estado <span class="text-danger">*</span></label>
                            <input type="text" name="status" id="edit_status" class="form-control" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_batch" class="form-label">Lote <span class="text-danger">*</span></label>
                            <input type="text" name="batch" id="edit_batch" class="form-control" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_expiry_date" class="form-label">Fecha de Expiración <span class="text-danger">*</span></label>
                            <input type="date" name="expiry_date" id="edit_expiry_date" class="form-control" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_item_condition" class="form-label">Condición del Artículo <span class="text-danger">*</span></label>
                            <input type="text" name="item_condition" id="edit_item_condition" class="form-control" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_entry_date" class="form-label">Fecha de Ingreso <span class="text-danger">*</span></label>
                            <input type="date" name="entry_date" id="edit_entry_date" class="form-control" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_warehouse" class="form-label">Almacén <span class="text-danger">*</span></label>
                            <select name="warehouse" id="edit_warehouse" class="form-select" required>
                                <option value="">Seleccione</option>
                                @foreach ($uniqueWarehouses as $warehouse)
                                    <option value="{{ $warehouse }}">{{ $warehouse }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_localizacion" class="form-label">Localización</label>
                            <input type="text" name="localizacion" id="edit_localizacion" class="form-control">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_commerce" class="form-label">Comercio <span class="text-danger">*</span></label>
                            <input type="text" name="commerce" id="edit_commerce" class="form-control" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_quantity" class="form-label">Cantidad <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" id="edit_quantity" class="form-control" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_value" class="form-label">Valor <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="value" id="edit_value" class="form-control" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_type" class="form-label">Tipo <span class="text-danger">*</span></label>
                            <input type="text" name="type" id="edit_type" class="form-control" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_observations" class="form-label">Observaciones</label>
                            <textarea name="observations" id="edit_observations" class="form-control"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_entry_document" class="form-label">Documento Ingreso</label>
                            <input type="text" name="entry_document" id="edit_entry_document" class="form-control">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_document" class="form-label">Documento PDF (reemplazar)</label>
                            <input type="file" name="document" id="edit_document" class="form-control" accept=".pdf">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Documento Actual</label>
                            <p id="current-document"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-warning">Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Modal Exportar -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="exportForm" action="{{ route('inventory-details.export') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="exportModalLabel">Exportar Inventario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Fecha de Inicio:</label>
                        <input type="date" class="form-control" id="start_date" name="start_date">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="end_date" class="form-label">Fecha de Fin:</label>
                        <input type="date" class="form-control" id="end_date" name="end_date">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="product" class="form-label">Producto:</label>
                        <select class="form-control" id="product" name="product">
                            <option value="">Todos los productos</option>
                            @foreach ($uniqueProducts as $product)
                                <option value="{{ $product }}">{{ $product }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="warehouse" class="form-label">Bodega:</label>
                        <select class="form-control" id="warehouse" name="warehouse">
                            <option value="">Todas las bodegas</option>
                            @foreach ($uniqueWarehouses as $warehouse)
                                <option value="{{ $warehouse }}">{{ $warehouse }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="location" class="form-label">Ubicación:</label>
                        <select class="form-control" id="location" name="location">
                            <option value="">Todas las ubicaciones</option>
                            @foreach ($uniqueLocations as $loc)
                                <option value="{{ $loc }}" {{ ($loc === ($location ?? '')) ? 'selected' : '' }}>{{ $loc }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Exportar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Agrupar -->
<div class="modal fade" id="groupModal" tabindex="-1" aria-labelledby="groupModalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="groupForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="groupModalLabel">Agrupar Ingresos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="entry_document" class="form-label">Numero de documento</label>
                        <input type="text" name="entry_document" id="entry_document" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="document" class="form-label">Documento PDF</label>
                        <input type="file" name="document" id="document" class="form-control" accept=".pdf">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Agrupar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

<script>
$(document).ready(function(){
    console.log('Group route:', "{{ route('inventory-details.group') }}");

    // Inicializar DataTables
    var table = $('#detailedTable').DataTable({
        language: {
            "decimal": ",",
            "thousands": ".",
            "lengthMenu": "Mostrar _MENU_ entradas por página",
            "zeroRecords": "No se encontraron registros de inventario",
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
        columnDefs: [
            { orderable: false, targets: [0, 1] }, 
            { width: '50px', targets: 0 },
            { width: '120px', targets: 1 },
            { width: '100px', targets: [2, 3, 4, 8, 9] },
            { width: '120px', targets: [5, 6, 7, 16] },
            { width: '150px', targets: [10, 11, 15] },
            { width: '80px', targets: [12, 13, 14] },
            { width: '100px', targets: 17 }
        ],
        scrollX: true,
        autoWidth: false,
        order: [[7, "desc"]]
    });

    // Mostrar mensajes de sesión
    @if (session('success'))
        Swal.fire({
            title: '¡Éxito!',
            text: '{{ session('success') }}',
            icon: 'success',
            confirmButtonText: 'Aceptar',
            timer: 3000
        });
    @endif
    @if (session('error'))
        Swal.fire({
            title: 'Error',
            html: `{!! is_array(session('error')) ? '<ul>' . implode('', array_map(fn($e) => '<li>' . htmlspecialchars($e) . '</li>', session('error'))) . '</ul>' : htmlspecialchars(session('error')) !!}`,
            icon: 'error',
            confirmButtonText: 'Aceptar',
            timer: 5000
        });
    @endif

    // MODIFICADO: Abrir modal edición con fechas corregidas
    $(document).on('click', '.editBtn', function(){
        let expiryDate = $(this).data('expiry_date');
        let entryDate = $(this).data('entry_date');
        
        console.log('Expiry Date:', expiryDate);
        console.log('Entry Date:', entryDate);
        
        $('#edit_id').val($(this).data('id'));
        $('#edit_item_description').val($(this).data('item_description'));
        $('#edit_sku').val($(this).data('sku'));
        $('#edit_status').val($(this).data('status'));
        $('#edit_batch').val($(this).data('batch'));
        
        // Asignar las fechas en formato Y-m-d
        $('#edit_expiry_date').val(expiryDate || '');
        $('#edit_entry_date').val(entryDate || '');
        
        $('#edit_item_condition').val($(this).data('item_condition'));
        $('#edit_warehouse').val($(this).data('warehouse'));
        $('#edit_commerce').val($(this).data('commerce'));
        $('#edit_quantity').val($(this).data('quantity'));
        $('#edit_value').val($(this).data('value'));
        $('#edit_type').val($(this).data('type'));
        $('#edit_observations').val($(this).data('observations'));
        $('#edit_entry_document').val($(this).data('entry_document'));
        
        $('#current-document').html($(this).data('document_path') ?
            `<a href="{{ asset('') }}${$(this).data('document_path')}" target="_blank" class="btn btn-sm btn-info">
                <i class="fas fa-file-pdf"></i> Ver PDF Actual
            </a>` : '<span class="text-muted">N/A</span>');
        
        $('#editInventoryModal').modal('show');
    });

    // Editar
    $('#editForm').on('submit', function(e){
        e.preventDefault();
        const form = this;
        if (!form.checkValidity()) {
            e.stopPropagation();
            $(form).addClass('was-validated');
            return;
        }

        let id = $('#edit_id').val();
        let formData = new FormData(form);
        
        $.ajax({
            url: "{{ route('inventory-details.update', ['id' => ':id']) }}".replace(':id', id),
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            success: function(res) {
                $('#editInventoryModal').modal('hide');
                Swal.fire({
                    title: '¡Éxito!',
                    text: res.success || 'Inventario actualizado con éxito',
                    icon: 'success',
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    location.reload();
                });
            },
            error: function(xhr) {
                let errors = xhr.responseJSON?.errors || {};
                $('#editForm .form-control, #editForm .form-select').removeClass('is-invalid');
                $('#editForm .invalid-feedback').text('');

                Object.keys(errors).forEach(field => {
                    let input = $(`#edit_${field}`);
                    input.addClass('is-invalid');
                    input.next('.invalid-feedback').text(errors[field][0]);
                });

                Swal.fire({
                    title: 'Error',
                    text: Object.values(errors).flat().join("\n") || 'Ocurrió un error al actualizar el inventario',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            }
        });
    });

    // Eliminar
    $(document).on('click', '.deleteBtn', function(){
        let id = $(this).data('id');
        let $button = $(this);
        
        Swal.fire({
            title: '¿Estás seguro?',
            text: '¿Deseas eliminar este inventario? Esto también eliminará el archivo PDF asociado.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Eliminando...');
                
                $.ajax({
                    url: "{{ route('inventory-details.destroy', ['id' => ':id']) }}".replace(':id', id),
                    type: "DELETE",
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}",
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    timeout: 10000,
                    success: function(res, textStatus, xhr) {
                        console.log('Delete success response:', res);
                        
                        Swal.fire({
                            title: '¡Eliminado!',
                            text: res.success || res.message || 'Inventario eliminado con éxito',
                            icon: 'success',
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr, textStatus, errorThrown) {
                        console.error('Delete error:', xhr.status, xhr.responseText);
                        
                        $button.prop('disabled', false).html('<i class="fas fa-trash"></i> Eliminar');
                        
                        let errorMessage = 'Ocurrió un error al eliminar el inventario';
                        try {
                            let response = JSON.parse(xhr.responseText);
                            errorMessage = response.error || response.message || errorMessage;
                        } catch (e) {
                            if (xhr.status === 0) {
                                errorMessage = 'Error de conexión. Verifique su conexión a internet.';
                            } else if (xhr.status === 404) {
                                errorMessage = 'El inventario no fue encontrado.';
                            } else if (xhr.status === 500) {
                                errorMessage = 'Error interno del servidor. Contacte al administrador.';
                            }
                        }
                        
                        Swal.fire({
                            title: 'Error',
                            text: errorMessage,
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        });
                    }
                });
            }
        });
    });

    // Obtener SKU al cambiar descripción
    $('#create_item_description, #edit_item_description').on('change', function(){
        const description = $(this).val().trim();
        const skuInput = $(this).attr('id') === 'create_item_description' ? '#create_sku' : '#edit_sku';
        if (description) {
            $.ajax({
                url: `{{ route('inventory-details.get-sku', '') }}/${encodeURIComponent(description)}`,
                type: 'GET',
                success: function(data) {
                    $(skuInput).val(data.sku || '');
                },
                error: function(xhr) {
                    $(skuInput).val('');
                    Swal.fire({
                        title: 'Error',
                        text: 'No se pudo obtener el SKU para el producto seleccionado.',
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                }
            });
        } else {
            $(skuInput).val('');
        }
    });

    // Limpiar formularios al abrir modales
    $('#createInventoryModal').on('show.bs.modal', function() {
        $('#createForm')[0].reset();
        $('#createForm').removeClass('was-validated');
        $('#createForm .form-control, #createForm .form-select').removeClass('is-invalid');
        $('#createForm .invalid-feedback').text('');
    });

    $('#editInventoryModal').on('show.bs.modal', function() {
        $('#editForm').removeClass('was-validated');
        $('#editForm .form-control, #editForm .form-select').removeClass('is-invalid');
        $('#editForm .invalid-feedback').text('');
    });

    // Validar formulario de exportación
    $('#exportForm').on('submit', function(e){
        e.preventDefault();
        const form = this;
        if (!form.checkValidity()) {
            e.stopPropagation();
            $(form).addClass('was-validated');
            return;
        }
        form.submit();
    });

    // Seleccionar todos los checkboxes
    $('#selectAll').on('change', function() {
        $('.selectRow').prop('checked', this.checked);
    });

    // Agrupar
    $('#groupBtn').on('click', function() {
        const selected = $('.selectRow:checked').map(function() { return $(this).data('id'); }).get();
        if (selected.length === 0) {
            Swal.fire('Atención', 'Seleccione al menos un ingreso para agrupar.', 'warning');
            return;
        }
        $('#groupModal').modal('show');

        $('#groupForm').off('submit').on('submit', function(e) {
            e.preventDefault();
            let formData = new FormData(this);
            selected.forEach(id => formData.append('ids[]', id));
            $.ajax({
                url: "{{ route('inventory-details.group') }}",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
                success: function(res) {
                    $('#groupModal').modal('hide');
                    Swal.fire({
                        title: '¡Éxito!',
                        text: res.success,
                        icon: 'success',
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        location.reload();
                    });
                },
                error: function(xhr) {
                    console.error('Group error:', xhr.responseText);
                    Swal.fire({
                        title: 'Error',
                        text: xhr.responseJSON?.message || 'Ocurrió un error al agrupar.',
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                }
            });
        });
    });

    // Limpiar modal de agrupación
    $('#groupModal').on('show.bs.modal', function() {
        $('#groupForm')[0].reset();
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
    .modal-content {
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    .modal-header {
        border-bottom: none;
        padding: 1.5rem;
        background: linear-gradient(90deg, #007bff, #0056b3);
    }
    .modal-title {
        font-weight: 600;
        color: white;
    }
    .btn {
        border-radius: 6px;
        padding: 8px 16px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }
    
    /* NUEVO: Estilos para las ubicaciones */
    .badge.bg-primary {
        font-size: 0.9em;
        padding: 0.5em 0.8em;
        font-weight: 600;
        box-shadow: 0 2px 4px rgba(0, 123, 255, 0.3);
    }
    
    .badge.bg-secondary {
        font-size: 0.9em;
        padding: 0.5em 0.8em;
    }
    
    small.text-muted {
        display: block;
        margin-top: 0.5em;
        font-size: 0.85em;
        line-height: 1.4;
    }
    
    body.dark-mode {
        background-color: #121212;
        color: #e0e0e0;
    }
    body.dark-mode .thead-dark {
        background-color: #1e1e1e;
    }
    body.dark-mode .table {
        background-color: #1e1e1e;
        color: #e0e0e0;
    }
    body.dark-mode .modal-content {
        background-color: #1e1e1e;
        color: #e0e0e0;
    }
    body.dark-mode .btn-primary {
        background-color: #1976d2;
        border-color: #1976d2;
    }
    body.dark-mode .btn-warning {
        background-color: #f57c00;
        border-color: #f57c00;
    }
    body.dark-mode .btn-danger {
        background-color: #d32f2f;
        border-color: #d32f2f;
    }
    body.dark-mode .btn-secondary {
        background-color: #546e7a;
        border-color: #546e7a;
    }
    .table th, .table td {
        vertical-align: middle;
        white-space: nowrap;
    }
    .table td:nth-child(12) {
        white-space: normal;
        min-width: 200px;
    }
    .table th {
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .bg-danger {
        color: white !important;
    }
    .bg-danger .btn {
        color: white;
        border-color: white;
    }
    .bg-danger .btn-warning {
        background-color: #e0a800;
    }
    .bg-danger .btn-danger {
        background-color: #c82333;
    }
    .bg-danger .badge {
        color: white;
        border: 1px solid white;
    }
</style>
@endsection