@extends('layouts.app')

@section('contents')
    <div class="container mt-4">
        <h1 class="mb-4" style="text-align: center;">Lista de Usuarios</h1>

        @if (session('success'))
            <div id="success-alert" data-message="{{ session('success') }}" style="display: none;"></div>
        @endif

        @if (session('error'))
            <div id="error-alert" data-message="{{ session('error') }}" style="display: none;"></div>
        @endif

        <div class="mb-3">
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">Agregar Usuario</a>
        </div>

        @if($profiles->isEmpty())
            <div class="alert alert-info">
                No hay usuarios para mostrar.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="usersTable">
                    <thead class="thead-dark">
                        <tr>
                            <th>Id</th>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Teléfono</th>
                            <th>Dirección</th>
                            <th>Tipo de Usuario</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="users-table">
                        @foreach($profiles as $rs)
                            <tr data-id="{{ $rs->id }}">
                                <td>{{ $rs->id }}</td>
                                <td>{{ $rs->name }}</td>
                                <td>{{ $rs->email }}</td>
                                <td>{{ $rs->telephone ?? 'N/A' }}</td>
                                <td>{{ $rs->address ?? 'N/A' }}</td>
                                <td>{{ $rs->user_type }}</td>
                                <td>
                                    <button class="btn btn-sm btn-secondary show-user" data-id="{{ $rs->id }}" data-bs-toggle="modal" data-bs-target="#showUserModal" title="Ver Detalles"><i class="fas fa-eye"></i></button>
                                    <button class="btn btn-sm btn-warning edit-user" data-id="{{ $rs->id }}" data-bs-toggle="modal" data-bs-target="#editUserModal" title="Editar Usuario"><i class="fas fa-edit"></i></button>
                                    <a href="{{ route('admin.edit', $rs) }}" class="btn btn-sm btn-success" data-bs-toggle="tooltip" data-bs-placement="top" title="Asignar Permisos"><i class="fas fa-key"></i></a>
                                    <button class="btn btn-sm btn-danger delete-user" data-id="{{ $rs->id }}" title="Eliminar Usuario"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <!-- Paginación -->
            <div class="pagination">
                {{ $profiles->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

    <!-- Modal para Crear Usuario -->
    <div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createUserModalLabel">Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="create-user-form">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label for="create-name">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="create-name" name="name" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="create-email">Correo <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="create-email" name="email" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="create-telephone">Teléfono</label>
                            <input type="text" class="form-control" id="create-telephone" name="telephone">
                        </div>
                        <div class="form-group mb-3">
                            <label for="create-address">Dirección</label>
                            <input type="text" class="form-control" id="create-address" name="address">
                        </div>
                        <div class="form-group mb-3">
                            <label for="create-password">Contraseña <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="create-password" name="password" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="create-user_type">Tipo de Usuario <span class="text-danger">*</span></label>
                            <select class="form-control" id="create-user_type" name="user_type" required>
                                <option value="">Seleccione un tipo</option>
                                <option value="Administrador">Administrador</option>
                                <option value="Usuario">Usuario</option>
                            </select>
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

    <!-- Modal para Mostrar Usuario -->
    <div class="modal fade" id="showUserModal" tabindex="-1" aria-labelledby="showUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="showUserModalLabel">Detalles del Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th scope="row">ID</th>
                                    <td id="show-id"></td>
                                </tr>
                                <tr>
                                    <th scope="row">Nombre</th>
                                    <td id="show-name"></td>
                                </tr>
                                <tr>
                                    <th scope="row">Correo</th>
                                    <td id="show-email"></td>
                                </tr>
                                <tr>
                                    <th scope="row">Teléfono</th>
                                    <td id="show-telephone"></td>
                                </tr>
                                <tr>
                                    <th scope="row">Dirección</th>
                                    <td id="show-address"></td>
                                </tr>
                                <tr>
                                    <th scope="row">Tipo de Usuario</th>
                                    <td id="show-user_type"></td>
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

    <!-- Modal para Editar Usuario -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Editar Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="edit-user-form">
                    @csrf
                    @method('PATCH')
                    <div class="modal-body">
                        <input type="hidden" id="edit-id" name="id">
                        <div class="form-group mb-3">
                            <label for="edit-name">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit-name" name="name" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="edit-email">Correo <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="edit-email" name="email" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="edit-telephone">Teléfono</label>
                            <input type="text" class="form-control" id="edit-telephone" name="telephone">
                        </div>
                        <div class="form-group mb-3">
                            <label for="edit-address">Dirección</label>
                            <input type="text" class="form-control" id="edit-address" name="address">
                        </div>
                        <div class="form-group mb-3">
                            <label for="edit-password">Contraseña</label>
                            <input type="password" class="form-control" id="edit-password" name="password" placeholder="Dejar en blanco para no cambiar">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="edit-user_type">Tipo de Usuario <span class="text-danger">*</span></label>
                            <select class="form-control" id="edit-user_type" name="user_type" required>
                                <option value="">Seleccione un tipo</option>
                                <option value="Administrador">Administrador</option>
                                <option value="Usuario">Usuario</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Form para eliminar usuario (oculto) -->
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
            $('#usersTable').DataTable({
                language: {
                    "decimal": ",",
                    "thousands": ".",
                    "lengthMenu": "Mostrar _MENU_ entradas por página",
                    "zeroRecords": "No se encontraron registros",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ entradas",
                    "infoEmpty": "Mostrando 0 a 0 de 0 entradas",
                    "infoFiltered": "(filtrado de _MAX_ entradas totales)",
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
                columnDefs: [
                    { "orderable": false, "targets": 6 } // Deshabilitar ordenación en la columna de Acciones
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
            $("#create-user-form").on('submit', function(e) {
                e.preventDefault();
                
                const form = $(this)[0];
                if (!form.checkValidity()) {
                    e.stopPropagation();
                    $(form).addClass('was-validated');
                    return;
                }
                
                $.ajax({
                    url: "{{ route('profiles.store') }}",
                    type: "POST",
                    data: $(this).serialize(),
                    success: function(response) {
                        $('#createUserModal').modal('hide');
                        
                        Swal.fire({
                            title: '¡Éxito!',
                            text: response.message,
                            icon: 'success',
                            confirmButtonText: 'Aceptar'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                location.reload();
                            }
                        });
                    },
                    error: function(xhr) {
                        let errors = xhr.responseJSON?.errors || {};
                        $('#create-user-form .form-control').removeClass('is-invalid');
                        $('#create-user-form .invalid-feedback').text('');
                        
                        if (errors.name) {
                            $("#create-name").addClass('is-invalid');
                            $("#create-name").next('.invalid-feedback').text(errors.name[0]);
                        }
                        if (errors.email) {
                            $("#create-email").addClass('is-invalid');
                            $("#create-email").next('.invalid-feedback').text(errors.email[0]);
                        }
                        if (errors.password) {
                            $("#create-password").addClass('is-invalid');
                            $("#create-password").next('.invalid-feedback').text(errors.password[0]);
                        }
                        if (errors.user_type) {
                            $("#create-user_type").addClass('is-invalid');
                            $("#create-user_type").next('.invalid-feedback').text(errors.user_type[0]);
                        }
                        
                        Swal.fire({
                            title: 'Error',
                            text: 'Ocurrió un error al crear el usuario',
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        });
                    }
                });
            });

            // Cargar datos para el modal de edición
            $('.edit-user').click(function() {
                const userId = $(this).data('id');
                
                $.ajax({
                    url: "{{ route('profiles.show', '') }}/" + userId,
                    type: "GET",
                    success: function(data) {
                        $('#edit-id').val(data.id);
                        $('#edit-name').val(data.name);
                        $('#edit-email').val(data.email);
                        $('#edit-telephone').val(data.telephone || '');
                        $('#edit-address').val(data.address || '');
                        $('#edit-user_type').val(data.user_type);
                        $('#edit-password').val('');
                    },
                    error: function() {
                        Swal.fire({
                            title: 'Error',
                            text: 'No se pudo cargar los datos del usuario',
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        });
                    }
                });
            });

            // Validación del formulario de edición
            $("#edit-user-form").on('submit', function(e) {
                e.preventDefault();
                
                const form = $(this)[0];
                if (!form.checkValidity()) {
                    e.stopPropagation();
                    $(form).addClass('was-validated');
                    return;
                }
                
                const userId = $("#edit-id").val();
                
                $.ajax({
                    url: "{{ route('profiles.update', '') }}/" + userId,
                    type: "PATCH",
                    data: $(this).serialize(),
                    success: function(response) {
                        $('#editUserModal').modal('hide');
                        
                        Swal.fire({
                            title: '¡Éxito!',
                            text: response.message,
                            icon: 'success',
                            confirmButtonText: 'Aceptar'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                location.reload();
                            }
                        });
                    },
                    error: function(xhr) {
                        let errors = xhr.responseJSON?.errors || {};
                        $('#edit-user-form .form-control').removeClass('is-invalid');
                        $('#edit-user-form .invalid-feedback').text('');
                        
                        if (errors.name) {
                            $("#edit-name").addClass('is-invalid');
                            $("#edit-name").next('.invalid-feedback').text(errors.name[0]);
                        }
                        if (errors.email) {
                            $("#edit-email").addClass('is-invalid');
                            $("#edit-email").next('.invalid-feedback').text(errors.email[0]);
                        }
                        if (errors.password) {
                            $("#edit-password").addClass('is-invalid');
                            $("#edit-password").next('.invalid-feedback').text(errors.password[0]);
                        }
                        if (errors.user_type) {
                            $("#edit-user_type").addClass('is-invalid');
                            $("#edit-user_type").next('.invalid-feedback').text(errors.user_type[0]);
                        }
                        
                        Swal.fire({
                            title: 'Error',
                            text: 'Ocurrió un error al actualizar el usuario',
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        });
                    }
                });
            });

            // Cargar datos para el modal de mostrar
            $('.show-user').click(function() {
                const userId = $(this).data('id');
                
                $.ajax({
                    url: "{{ route('profiles.show', '') }}/" + userId,
                    type: "GET",
                    success: function(data) {
                        $('#show-id').text(data.id);
                        $('#show-name').text(data.name);
                        $('#show-email').text(data.email);
                        $('#show-telephone').text(data.telephone || 'N/A');
                        $('#show-address').text(data.address || 'N/A');
                        $('#show-user_type').text(data.user_type);
                    },
                    error: function() {
                        Swal.fire({
                            title: 'Error',
                            text: 'No se pudo cargar los datos del usuario',
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        });
                    }
                });
            });

            // Eliminar usuario
            $('.delete-user').click(function() {
                const userId = $(this).data('id');
                
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "¿Deseas eliminar este usuario?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('profiles.destroy', '') }}/" + userId,
                            type: "DELETE",
                            data: {
                                "_token": "{{ csrf_token() }}"
                            },
                            success: function(response) {
                                Swal.fire({
                                    title: '¡Eliminado!',
                                    text: response.message,
                                    icon: 'success',
                                    confirmButtonText: 'Aceptar'
                                }).then(() => {
                                    // Eliminar la fila de la tabla
                                    $(`tr[data-id="${userId}"]`).fadeOut(500, function() {
                                        $(this).remove();
                                        
                                        // Verificar si no quedan más usuarios
                                        if ($('#users-table tr').length === 0) {
                                            location.reload();
                                        }
                                    });
                                });
                            },
                            error: function(xhr) {
                                Swal.fire({
                                    title: 'Error',
                                    text: xhr.responseJSON?.message || 'Ocurrió un error al eliminar el usuario',
                                    icon: 'error',
                                    confirmButtonText: 'Aceptar'
                                });
                            }
                        });
                    }
                });
            });

            // Limpiar el formulario al abrir el modal de crear
            $('#createUserModal').on('show.bs.modal', function() {
                $('#create-user-form')[0].reset();
                $('#create-user-form').removeClass('was-validated');
                $('#create-name, #create-email, #create-password, #create-user_type').removeClass('is-invalid');
                $('#create-user-form .invalid-feedback').text('');
            });

            // Limpiar el formulario al abrir el modal de editar
            $('#editUserModal').on('show.bs.modal', function() {
                $('#edit-user-form').removeClass('was-validated');
                $('#edit-name, #edit-email, #edit-password, #edit-user_type').removeClass('is-invalid');
                $('#edit-user-form .invalid-feedback').text('');
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
    </style>
@endsection