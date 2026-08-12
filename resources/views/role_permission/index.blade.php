@extends('layouts.app')
@section('contents')    
<div class="text-center mb-4">
    <h1 style="font-weight: bold; font-size: 30px;">Asignar Permisos a Roles</h1>
</div>

<!-- Estilos personalizados -->
<style>
    .card { margin-bottom: 20px; }
    .form-check { margin-bottom: 10px; }
    .form-check-label { margin-left: 5px; }
    .btn-primary { background-color: #007bff; border-color: #007bff; }
    .btn-primary:hover { background-color: #0056b3; border-color: #0056b3; }
</style>

<!-- Notificación de éxito o error -->
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
@if (session()->has('error'))
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '{{ session('error') }}',
        });
    </script>
@endif

<div class="container">
    @if ($permissions->isEmpty())
        <div class="alert alert-warning text-center">
            No hay permisos disponibles. Por favor, crea algunos permisos primero.
            <a href="{{ route('permissions.index') }}" class="btn btn-primary mt-2">Ir a Permisos</a>
        </div>
    @else
        <div class="row">
            @foreach ($roles as $role)
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Rol: {{ $role->name }}</h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('role_permissions.assign', $role->id) }}" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label><strong>Permisos:</strong></label>
                                    @foreach ($permissions as $permission)
                                        <div class="form-check">
                                            <input type="checkbox" 
                                                   class="form-check-input" 
                                                   name="permissions[]" 
                                                   value="{{ $permission->id }}" 
                                                   id="permission-{{ $role->id }}-{{ $permission->id }}"
                                                   {{ $role->permissions->contains('id', $permission->id) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="permission-{{ $role->id }}-{{ $permission->id }}">
                                                {{ $permission->name }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="submit" class="btn btn-primary mt-3">Guardar Permisos</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@endsection