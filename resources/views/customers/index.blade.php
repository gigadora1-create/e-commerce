@extends('layouts.app')

@section('contents')
<div class="container mt-4">
    <h2 class="text-center mb-4">Gestión de Clientes</h2>

    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createModal">
        Nuevo Cliente
    </button>

    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="customersTable">
            <thead class="thead-dark">
                <tr>
                    <th>Acciones</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Tipo</th>
                    <th>Teléfono</th>
                    <th>Dirección</th>
                </tr>
            </thead>
            <tbody>
                @foreach($customers as $customer)
                <tr>
                    <td>
                        <button class="btn btn-warning btn-sm editBtn"
                            data-customer_id="{{ $customer->customer_id }}"
                            data-name="{{ $customer->name }}"
                            data-email="{{ $customer->email }}"
                            data-phone="{{ $customer->phone }}"
                            data-address="{{ $customer->address }}"
                            data-is_warehouse_client="{{ $customer->is_warehouse_client ? 1 : 0 }}">
                            Editar
                        </button>
                        <button class="btn btn-danger btn-sm deleteBtn"
                            data-customer_id="{{ $customer->customer_id }}">
                            Eliminar
                        </button>
                    </td>
                    <td>{{ $customer->name }}</td>
                    <td>{{ $customer->email }}</td>
                    <td>
                        @if($customer->is_warehouse_client)
                            <span class="badge bg-dark">Bodegaje</span>
                        @else
                            <span class="badge bg-primary">Normal</span>
                        @endif
                    </td>
                    <td>{{ $customer->phone }}</td>
                    <td>{{ $customer->address }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Crear -->
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="createForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="createModalLabel">Crear Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="create_name" class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="create_name" class="form-control" placeholder="Nombre" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="create_email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="create_email" class="form-control" placeholder="Email" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="create_phone" class="form-label">Teléfono <span class="text-danger">*</span></label>
                        <input type="text" name="phone" id="create_phone" class="form-control" placeholder="Teléfono" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="create_address" class="form-label">Dirección <span class="text-danger">*</span></label>
                        <input type="text" name="address" id="create_address" class="form-control" placeholder="Dirección" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="form-check">
                        <input type="hidden" name="is_warehouse_client" value="0">
                        <input class="form-check-input" type="checkbox" name="is_warehouse_client" id="create_is_warehouse_client" value="1">
                        <label class="form-check-label" for="create_is_warehouse_client">Cliente de bodegaje</label>
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

<!-- Modal Editar -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editForm">
                @csrf
                <input type="hidden" name="_method" value="PUT">
                <input type="hidden" name="customer_id" id="edit_customer_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Editar Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_phone" class="form-label">Teléfono <span class="text-danger">*</span></label>
                        <input type="text" name="phone" id="edit_phone" class="form-control" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_address" class="form-label">Dirección <span class="text-danger">*</span></label>
                        <input type="text" name="address" id="edit_address" class="form-control" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="form-check">
                        <input type="hidden" name="is_warehouse_client" value="0">
                        <input class="form-check-input" type="checkbox" name="is_warehouse_client" id="edit_is_warehouse_client" value="1">
                        <label class="form-check-label" for="edit_is_warehouse_client">Cliente de bodegaje</label>
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
    var table = $('#customersTable').DataTable({
        language: {
            "decimal": ",",
            "thousands": ".",
            "lengthMenu": "Mostrar _MENU_ entradas por página",
            "zeroRecords": "No se encontraron clientes",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ clientes",
            "infoEmpty": "Mostrando 0 a 0 de 0 clientes",
            "infoFiltered": "(filtrado de _MAX_ clientes totales)",
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
        ]
    });

    // Crear
    $('#createForm').on('submit', function(e){
        e.preventDefault();
        const form = this;
        if (!form.checkValidity()) {
            e.stopPropagation();
            $(form).addClass('was-validated');
            return;
        }
        
        $.ajax({
            url: "{{ route('customers.store') }}",
            type: "POST",
            data: $(this).serialize(),
            headers: {
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            success: function(res) {
                $('#createModal').modal('hide');
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
                let errors = xhr.responseJSON?.errors || {};
                $('#createForm .form-control').removeClass('is-invalid');
                $('#createForm .invalid-feedback').text('');
                
                if (errors.name) {
                    $('#create_name').addClass('is-invalid');
                    $('#create_name').next('.invalid-feedback').text(errors.name[0]);
                }
                if (errors.email) {
                    $('#create_email').addClass('is-invalid');
                    $('#create_email').next('.invalid-feedback').text(errors.email[0]);
                }
                if (errors.phone) {
                    $('#create_phone').addClass('is-invalid');
                    $('#create_phone').next('.invalid-feedback').text(errors.phone[0]);
                }
                if (errors.address) {
                    $('#create_address').addClass('is-invalid');
                    $('#create_address').next('.invalid-feedback').text(errors.address[0]);
                }
                
                Swal.fire({
                    title: 'Error',
                    text: Object.values(errors).flat().join("\n") || 'Ocurrió un error al crear el cliente',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            }
        });
    });

    // Abrir modal edición
    $(document).on('click', '.editBtn', function(){
        $('#edit_customer_id').val($(this).data('customer_id'));
        $('#edit_name').val($(this).data('name'));
        $('#edit_email').val($(this).data('email'));
        $('#edit_phone').val($(this).data('phone'));
        $('#edit_address').val($(this).data('address'));
        $('#edit_is_warehouse_client').prop('checked', Number($(this).data('is_warehouse_client')) === 1);
        $('#editModal').modal('show');
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
        
        let customer_id = $('#edit_customer_id').val();
        $.ajax({
            url: "{{ route('customers.update', ['customer_id' => ':customer_id']) }}".replace(':customer_id', customer_id),
            type: "POST",
            data: $(this).serialize(),
            headers: {
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            success: function(res) {
                $('#editModal').modal('hide');
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
                let errors = xhr.responseJSON?.errors || {};
                $('#editForm .form-control').removeClass('is-invalid');
                $('#editForm .invalid-feedback').text('');
                
                if (errors.name) {
                    $('#edit_name').addClass('is-invalid');
                    $('#edit_name').next('.invalid-feedback').text(errors.name[0]);
                }
                if (errors.email) {
                    $('#edit_email').addClass('is-invalid');
                    $('#edit_email').next('.invalid-feedback').text(errors.email[0]);
                }
                if (errors.phone) {
                    $('#edit_phone').addClass('is-invalid');
                    $('#edit_phone').next('.invalid-feedback').text(errors.phone[0]);
                }
                if (errors.address) {
                    $('#edit_address').addClass('is-invalid');
                    $('#edit_address').next('.invalid-feedback').text(errors.address[0]);
                }
                
                Swal.fire({
                    title: 'Error',
                    text: Object.values(errors).flat().join("\n") || 'Ocurrió un error al actualizar el cliente: ' + (xhr.responseJSON?.message || xhr.statusText),
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            }
        });
    });

    // Eliminar
    $(document).on('click', '.deleteBtn', function(){
        let customer_id = $(this).data('customer_id');
        Swal.fire({
            title: '¿Estás seguro?',
            text: '¿Deseas eliminar este cliente?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('customers.destroy', ['customer_id' => ':customer_id']) }}".replace(':customer_id', customer_id),
                    type: "DELETE",
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    },
                    success: function(res) {
                        Swal.fire({
                            title: '¡Eliminado!',
                            text: res.success,
                            icon: 'success',
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            title: 'Error',
                            text: xhr.responseJSON?.error || 'Ocurrió un error al eliminar el cliente',
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        });
                    }
                });
            }
        });
    });

    $('#createModal').on('hidden.bs.modal', function() {
        $('#createForm')[0].reset();
        $('#createForm').removeClass('was-validated');
        $('#createForm .form-control').removeClass('is-invalid');
        $('#createForm .invalid-feedback').text('');
        $('#create_is_warehouse_client').prop('checked', false);
    });
});
</script>
@endsection
