@extends('layouts.app')
@section('contents')
    <div class="container mt-4">
        <h1 class="mb-4" style="text-align: center;">Gestión de Productos</h1>
        @if (session('success'))
            <div id="success-alert" data-message="{{ session('success') }}" style="display: none;"></div>
        @endif
        @if (session('error'))
            <div id="error-alert" data-message="{{ session('error') }}" style="display: none;"></div>
        @endif
        <div class="mb-3">
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createItemModal">Nuevo Producto</a>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="itemsTable">
                <thead class="thead-dark">
                    <tr>
                        <th>Acciones</th>
                        <th>Imagen</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>SKU</th>
                        <th>Código de Barras</th>
                    </tr>
                </thead>
                <tbody id="items-table">
                    <!-- Los datos se cargarán dinámicamente vía AJAX -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal para Crear Producto -->
    <div class="modal fade" id="createItemModal" tabindex="-1" aria-labelledby="createItemModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createItemModalLabel">Nuevo Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="createItemForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label for="create-name">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="create-name" name="name" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="create-description">Descripción <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="create-description" name="description" required></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="create-sku">SKU <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="create-sku" name="sku" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="create-barcode">Código de Barras</label>
                            <input type="text" class="form-control" id="create-barcode" name="barcode">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="create-image">Imagen <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="create-image" name="image" accept="image/*" required>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para Mostrar Producto -->
    <div class="modal fade" id="showItemModal" tabindex="-1" aria-labelledby="showItemModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="showItemModalLabel">Detalles del Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th scope="row">Imagen</th>
                                    <td><img id="show-image" src="" alt="Producto" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px;" onerror="this.onerror=null; this.src='{{ asset('img/no-image.png') }}';"></td>
                                </tr>
                                <tr>
                                    <th scope="row">Nombre</th>
                                    <td id="show-name"></td>
                                </tr>
                                <tr>
                                    <th scope="row">Descripción</th>
                                    <td id="show-description"></td>
                                </tr>
                                <tr>
                                    <th scope="row">SKU</th>
                                    <td id="show-sku"></td>
                                </tr>
                                <tr>
                                    <th scope="row">Código de Barras</th>
                                    <td id="show-barcode"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Editar Producto -->
    <div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editItemModalLabel">Editar Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editItemForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="edit-id" name="id">
                        <div class="form-group mb-3">
                            <label for="edit-name">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit-name" name="name" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="edit-description">Descripción <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="edit-description" name="description" required></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="edit-sku">SKU <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit-sku" name="sku" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="edit-barcode">Código de Barras</label>
                            <input type="text" class="form-control" id="edit-barcode" name="barcode">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="edit-image">Imagen (opcional)</label>
                            <input type="file" class="form-control" id="edit-image" name="image" accept="image/*">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-primary">Actualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Form para eliminar producto (oculto) -->
    <form id="delete-form" method="POST" style="display:none;">
        @csrf
        @method('DELETE')
    </form>
@endsection

@section('scripts')
    <!-- Cargar jQuery primero -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Cargar DataTables y su integración con Bootstrap 5 -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <!-- Cargar SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Cargar FontAwesome para íconos -->
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
    <!-- Cargar CSS de DataTables para Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <script>
        $(document).ready(function() {
            // Inicializar DataTables con traducción al español
            let table = $('#itemsTable').DataTable({
                language: {
                    "decimal": ",",
                    "thousands": ".",
                    "lengthMenu": "Mostrar _MENU_ entradas por página",
                    "zeroRecords": "No se encontraron productos",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ productos",
                    "infoEmpty": "Mostrando 0 a 0 de 0 productos",
                    "infoFiltered": "(filtrado de _MAX_ productos totales)",
                    "search": "Buscar:",
                    "paginate": {
                        "first": "Primero",
                        "last": "Último",
                        "next": "Siguiente",
                        "previous": "Anterior"
                    },
                    "aria": {
                        "sortAscending": ": activar para ordenar la columna de manera ascendente",
                        "sortDescending": ": activar para ordenar la columna de manera descendente"
                    }
                },
                responsive: true,
                ajax: '{{ route("items.data") }}',
                columns: [
                    {
                        data: 'item_id',
                        render: function(id) {
                            return `
                                <button class="btn btn-sm btn-secondary show-item" data-id="${id}" data-bs-toggle="modal" data-bs-target="#showItemModal" title="Ver Detalles"><i class="fas fa-eye"></i></button>
                                <button class="btn btn-sm btn-warning edit-item" data-id="${id}" data-bs-toggle="modal" data-bs-target="#editItemModal" title="Editar Producto"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-danger delete-item" data-id="${id}" title="Eliminar Producto"><i class="fas fa-trash"></i></button>
                            `;
                        }
                    },
                    {
                        data: 'image_url',
                        render: function(url) {
                            const fallback = '{{ asset('img/no-image.png') }}';
                            return `<img src="${url || fallback}" alt="Producto" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px;" onerror="this.onerror=null; this.src='${fallback}';">`;
                        }
                    },
                    { data: 'name' },
                    { data: 'description' },
                    { data: 'sku' },
                    { data: 'barcode' }
                ],
                columnDefs: [
                    { "orderable": false, "targets": 0 } // Deshabilitar ordenación en la columna de Acciones
                ]
            });

            // Mostrar alertas de sesión si existen
            if ($("#success-alert").length) {
                Swal.fire({
                    title: '¡Éxito!',
                    text: $("#success-alert").data('message'),
                    icon: 'success',
                    confirmButtonText: 'Aceptar'
                });
            }
            if ($("#error-alert").length) {
                Swal.fire({
                    title: 'Error',
                    text: $("#error-alert").data('message'),
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            }

            // Validación del formulario de creación
            $("#createItemForm").on('submit', function(e) {
                e.preventDefault();

                const form = $(this)[0];
                if (!form.checkValidity()) {
                    e.stopPropagation();
                    $(form).addClass('was-validated');
                    return;
                }

                let formData = new FormData(this);
                $.ajax({
                    url: "{{ route('items.store') }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('#createItemModal').modal('hide');
                        Swal.fire({
                            title: '¡Éxito!',
                            text: response.success,
                            icon: 'success',
                            confirmButtonText: 'Aceptar'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                table.ajax.reload();
                            }
                        });
                    },
                    error: function(xhr) {
                        let errors = xhr.responseJSON?.errors || {};
                        $('#createItemForm .form-control').removeClass('is-invalid');
                        $('#createItemForm .invalid-feedback').text('');

                        if (errors.name) {
                            $("#create-name").addClass('is-invalid');
                            $("#create-name").next('.invalid-feedback').text(errors.name[0]);
                        }
                        if (errors.description) {
                            $("#create-description").addClass('is-invalid');
                            $("#create-description").next('.invalid-feedback').text(errors.description[0]);
                        }
                        if (errors.sku) {
                            $("#create-sku").addClass('is-invalid');
                            $("#create-sku").next('.invalid-feedback').text(errors.sku[0]);
                        }
                        if (errors.barcode) {
                            $("#create-barcode").addClass('is-invalid');
                            $("#create-barcode").next('.invalid-feedback').text(errors.barcode[0]);
                        }
                        if (errors.image) {
                            $("#create-image").addClass('is-invalid');
                            $("#create-image").next('.invalid-feedback').text(errors.image[0]);
                        }

                        Swal.fire({
                            title: 'Error',
                            text: 'Ocurrió un error al crear el producto',
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        });
                    }
                });
            });

            // Cargar datos para el modal de edición
            $(document).on('click', '.edit-item', function() {
                const itemId = $(this).data('id');

                $.ajax({
                    url: "{{ route('items.data') }}",
                    type: "GET",
                    success: function(response) {
                        let item = response.data.find(p => p.item_id == itemId);
                        if (item) {
                            $('#edit-id').val(item.item_id);
                            $('#edit-name').val(item.name);
                            $('#edit-description').val(item.description);
                            $('#edit-sku').val(item.sku);
                            $('#edit-barcode').val(item.barcode);
                            $('#editItemModal').modal('show');
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'Error',
                            text: 'No se pudo cargar los datos del producto',
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        });
                    }
                });
            });

            // Validación del formulario de edición
            $("#editItemForm").on('submit', function(e) {
                e.preventDefault();

                const form = $(this)[0];
                if (!form.checkValidity()) {
                    e.stopPropagation();
                    $(form).addClass('was-validated');
                    return;
                }

                const itemId = $("#edit-id").val();
                let formData = new FormData(this);
                formData.append('_method', 'POST');

                $.ajax({
                    url: `/items/${itemId}`,
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('#editItemModal').modal('hide');
                        Swal.fire({
                            title: '¡Éxito!',
                            text: response.success,
                            icon: 'success',
                            confirmButtonText: 'Aceptar'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                table.ajax.reload();
                            }
                        });
                    },
                    error: function(xhr) {
                        let errors = xhr.responseJSON?.errors || {};
                        $('#editItemForm .form-control').removeClass('is-invalid');
                        $('#editItemForm .invalid-feedback').text('');

                        if (errors.name) {
                            $("#edit-name").addClass('is-invalid');
                            $("#edit-name").next('.invalid-feedback').text(errors.name[0]);
                        }
                        if (errors.description) {
                            $("#edit-description").addClass('is-invalid');
                            $("#edit-description").next('.invalid-feedback').text(errors.description[0]);
                        }
                        if (errors.sku) {
                            $("#edit-sku").addClass('is-invalid');
                            $("#edit-sku").next('.invalid-feedback').text(errors.sku[0]);
                        }
                        if (errors.barcode) {
                            $("#edit-barcode").addClass('is-invalid');
                            $("#edit-barcode").next('.invalid-feedback').text(errors.barcode[0]);
                        }
                        if (errors.image) {
                            $("#edit-image").addClass('is-invalid');
                            $("#edit-image").next('.invalid-feedback').text(errors.image[0]);
                        }

                        Swal.fire({
                            title: 'Error',
                            text: Object.values(errors).flat().join("\n") || 'Ocurrió un error al actualizar el producto',
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        });
                    }
                });
            });

            // Cargar datos para el modal de mostrar
            $(document).on('click', '.show-item', function() {
                const itemId = $(this).data('id');

                $.ajax({
                    url: "{{ route('items.data') }}",
                    type: "GET",
                    success: function(response) {
                        let item = response.data.find(p => p.item_id == itemId);
                        if (item) {
                            $('#show-image').attr('src', item.image_url);
                            $('#show-name').text(item.name);
                            $('#show-description').text(item.description);
                            $('#show-sku').text(item.sku);
                            $('#show-barcode').text(item.barcode);
                            $('#showItemModal').modal('show');
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'Error',
                            text: 'No se pudo cargar los datos del producto',
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        });
                    }
                });
            });

            // Eliminar producto
            $(document).on('click', '.delete-item', function() {
                const itemId = $(this).data('id');

                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "¿Deseas eliminar este producto?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/items/${itemId}`,
                            type: "DELETE",
                            data: {
                                "_token": "{{ csrf_token() }}"
                            },
                            success: function(response) {
                                Swal.fire({
                                    title: '¡Eliminado!',
                                    text: response.success,
                                    icon: 'success',
                                    confirmButtonText: 'Aceptar'
                                }).then(() => {
                                    table.ajax.reload();
                                });
                            },
                            error: function(xhr) {
                                Swal.fire({
                                    title: 'Error',
                                    text: xhr.responseJSON?.error || 'Ocurrió un error al eliminar el producto',
                                    icon: 'error',
                                    confirmButtonText: 'Aceptar'
                                });
                            }
                        });
                    }
                });
            });

            // Limpiar el formulario al abrir el modal de crear
            $('#createItemModal').on('show.bs.modal', function() {
                $('#createItemForm')[0].reset();
                $('#createItemForm').removeClass('was-validated');
                $('#create-name, #create-description, #create-sku, #create-barcode, #create-image').removeClass('is-invalid');
                $('#createItemForm .invalid-feedback').text('');
            });

            // Limpiar el formulario al abrir el modal de editar
            $('#editItemModal').on('show.bs.modal', function() {
                $('#editItemForm').removeClass('was-validated');
                $('#edit-name, #edit-description, #edit-sku, #edit-barcode, #edit-image').removeClass('is-invalid');
                $('#editItemForm .invalid-feedback').text('');
            });

            // Inicializar tooltips de Bootstrap
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
        });
    </script>
    <style>
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .table th, .table td {
            white-space: nowrap;
        }
        .table th {
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .thead-dark {
            background-color: #343a40;
            color: white;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #004085;
        }
        .table img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
@endsection
