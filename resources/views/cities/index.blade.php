@extends('layouts.app')

@section('contents')
<div class="container mt-4">
    <h2 class="text-center mb-4">Gestión de Bodegas</h2>

    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createModal">
        Nueva Bodega
    </button>

    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="citiesTable">
            <thead class="thead-dark">
                <tr>
                    <th>Acciones</th>
                    <th>Ciudad</th>
                    <th>Bodega</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cities as $city)
                <tr>
                    <td>
                        <button class="btn btn-warning btn-sm editBtn"
                            data-id="{{ $city->city_id }}"
                            data-name="{{ $city->city_name }}"
                            data-store="{{ $city->city_store }}">
                            Editar
                        </button>
                        <button class="btn btn-danger btn-sm deleteBtn"
                            data-id="{{ $city->city_id }}">
                            Eliminar
                        </button>
                    </td>
                    <td>{{ $city->city_name }}</td>
                    <td>{{ $city->city_store }}</td>
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
                    <h5 class="modal-title" id="createModalLabel">Crear Ciudad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="create_city_name" class="form-label">Ciudad <span class="text-danger">*</span></label>
                        <input type="text" name="city_name" id="create_city_name" class="form-control" placeholder="Ciudad" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="create_city_store" class="form-label">Bodega <span class="text-danger">*</span></label>
                        <input type="text" name="city_store" id="create_city_store" class="form-control" placeholder="Bodega" required>
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

<!-- Modal Editar -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editForm">
                @csrf
                <input type="hidden" name="_method" value="PUT">
                <input type="hidden" name="city_id" id="edit_city_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Editar Ciudad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_city_name" class="form-label">Ciudad <span class="text-danger">*</span></label>
                        <input type="text" name="city_name" id="edit_city_name" class="form-control" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_city_store" class="form-label">Bodega <span class="text-danger">*</span></label>
                        <input type="text" name="city_store" id="edit_city_store" class="form-control" required>
                        <div class="invalid-feedback"></div>
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
    var table = $('#citiesTable').DataTable({
        language: {
            "decimal": ",",
            "thousands": ".",
            "lengthMenu": "Mostrar _MENU_ entradas por página",
            "zeroRecords": "No se encontraron bodegas",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ bodegas",
            "infoEmpty": "Mostrando 0 a 0 de 0 bodegas",
            "infoFiltered": "(filtrado de _MAX_ bodegas totales)",
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
            url: "{{ route('cities.store') }}",
            type: "POST",
            data: $(this).serialize(),
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
                
                if (errors.city_name) {
                    $('#create_city_name').addClass('is-invalid');
                    $('#create_city_name').next('.invalid-feedback').text(errors.city_name[0]);
                }
                if (errors.city_store) {
                    $('#create_city_store').addClass('is-invalid');
                    $('#create_city_store').next('.invalid-feedback').text(errors.city_store[0]);
                }
                
                Swal.fire({
                    title: 'Error',
                    text: Object.values(errors).flat().join("\n") || 'Ocurrió un error al crear la ciudad',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            }
        });
    });

    // Abrir modal edición
    $(document).on('click', '.editBtn', function(){
        $('#edit_city_id').val($(this).data('id'));
        $('#edit_city_name').val($(this).data('name'));
        $('#edit_city_store').val($(this).data('store'));
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
        
        let id = $('#edit_city_id').val();
        $.ajax({
            url: "{{ route('cities.update', ['city_id' => ':city_id']) }}".replace(':city_id', id),
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
                
                if (errors.city_name) {
                    $('#edit_city_name').addClass('is-invalid');
                    $('#edit_city_name').next('.invalid-feedback').text(errors.city_name[0]);
                }
                if (errors.city_store) {
                    $('#edit_city_store').addClass('is-invalid');
                    $('#edit_city_store').next('.invalid-feedback').text(errors.city_store[0]);
                }
                
                Swal.fire({
                    title: 'Error',
                    text: Object.values(errors).flat().join("\n") || 'Ocurrió un error al actualizar la ciudad: ' + (xhr.responseJSON?.message || xhr.statusText),
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            }
        });
    });

    // Eliminar
    $(document).on('click', '.deleteBtn', function(){
        let id = $(this).data('id');
        Swal.fire({
            title: '¿Estás seguro?',
            text: '¿Deseas eliminar esta ciudad?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('cities.destroy', ['city_id' => ':city_id']) }}".replace(':city_id', id),
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
                            text: xhr.responseJSON?.error || 'Ocurrió un error al eliminar la ciudad',
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        });
                    }
                });
            }
        });
    });

    // Limpiar formularios al abrir modales
    $('#createModal').on('show.bs.modal', function() {
        $('#createForm')[0].reset();
        $('#createForm').removeClass('was-validated');
        $('#create_city_name, #create_city_store').removeClass('is-invalid');
        $('#createForm .invalid-feedback').text('');
    });

    $('#editModal').on('show.bs.modal', function() {
        $('#editForm').removeClass('was-validated');
        $('#edit_city_name, #edit_city_store').removeClass('is-invalid');
        $('#editForm .invalid-feedback').text('');
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
</style>
@endsection
