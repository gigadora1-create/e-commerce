@extends('layouts.app')
@section('contents')
<div class="text-center mb-4">
    <h1 style="font-weight: bold; font-size: 30px;">Permisos</h1>
</div>

<!-- Estilos personalizados -->
<link rel="stylesheet" href="{{ asset('admin_assets/css/crud.css') }}">
<style>
    .table-responsive { max-height: 500px; overflow-y: auto; }
    .thead-dark { background-color: #343a40; color: white; }
    .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.875rem; }
    .modal-header { background-color: #007bff; color: white; }
</style>

<!-- Botón para agregar permiso -->
<div class="d-flex justify-content-end mb-3">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPermissionModal" style="background-color: orange; border-color: orange;">
        <i class="fa fa-plus" style="color: white;"></i> Agregar Permiso
    </button>
</div>

<!-- Notificación de éxito -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
@if (session()->has('success'))
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Éxito',
            text: '{{ session('success') }}',
        });
    </script>
@endif

<!-- Tabla mejorada -->
<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th>Acción</th>
                <th>ID</th>
                <th>Nombre Permiso</th>
            </tr>
        </thead>
        <tbody>
            @if ($Permissions->count() > 0)
                @foreach ($Permissions as $rs)
                    <tr>
                        <td class="align-middle">
                            <div class="btn-group" role="group">
                                <button class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#showPermissionModal{{ $rs->id }}">
                                    <i class="fa fa-eye"></i>
                                </button>
                                <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editPermissionModal{{ $rs->id }}">
                                    <i class="fa fa-edit"></i>
                                </button>
                                <form action="{{ route('permissions.destroy', $rs->id) }}" method="POST" class="d-inline" onsubmit="confirmDelete(event)">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm"><i class="fa fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                        <td class="align-middle">{{ $rs->id }}</td>
                        <td class="align-middle">{{ $rs->name }}</td>
                    </tr>

                    <!-- Modal para Ver Permiso -->
                    <div class="modal fade" id="showPermissionModal{{ $rs->id }}" tabindex="-1" role="dialog">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Detalles del Permiso</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p><strong>ID:</strong> {{ $rs->id }}</p>
                                    <p><strong>Nombre:</strong> {{ $rs->name }}</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal para Editar Permiso -->
                    <div class="modal fade" id="editPermissionModal{{ $rs->id }}" tabindex="-1" role="dialog">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Editar Permiso</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form action="{{ route('permissions.update', $rs->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label for="name">Nombre del Permiso</label>
                                            <input type="text" class="form-control" name="name" value="{{ $rs->name }}" required>
                                            @error('name')
                                                <div class="text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="submit" class="btn btn-primary">Guardar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <tr>
                    <td class="text-center" colspan="3">Permiso no encontrado</td>
                </tr>
            @endif
        </tbody>
    </table>
    <div class="d-flex justify-content-center">
        {{ $Permissions->links('pagination.custom') }}
    </div>
</div>

<!-- Modal para Crear Permiso -->
<div class="modal fade" id="createPermissionModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Crear Permiso</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('permissions.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name">Nombre del Permiso</label>
                        <input type="text" class="form-control" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
<script>
    function confirmDelete(event) {
        event.preventDefault();
        Swal.fire({
            title: "¿Estás seguro?",
            text: "¡No podrás revertir esto!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Sí, eliminarlo"
        }).then((result) => {
            if (result.isConfirmed) {
                event.target.submit();
            }
        });
    }

    // Reabrir el modal si hay errores de validación
    @if ($errors->any())
        document.addEventListener('DOMContentLoaded', function () {
            var createModal = new bootstrap.Modal(document.getElementById('createPermissionModal'));
            createModal.show();
        });
    @endif
</script>

@endsection