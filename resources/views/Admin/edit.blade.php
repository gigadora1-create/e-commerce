@extends('layouts.app')
@section('title', '')
@section('contents')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Asignar Roles</h4>
                    <a href="{{ route('profiles.index') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left me-2"></i>Regresar
                    </a>
                </div>
                <div class="card-body">
                    @if (session('info'))
                        <script>
                            Swal.fire({
                                icon: 'success',
                                title: 'Éxito',
                                text: '{{ session('info') }}',
                            });
                        </script>
                    @endif

                    <div class="mb-4">
                        <h5 class="card-title">{{ $usuario->name }}</h5>
                        <p class="card-text text-muted">{{ $usuario->email }}</p>
                    </div>

                    <p class="card-text">Seleccione uno o más roles para el usuario:</p>

                    {!! Form::model($usuario, ['route' => ['admin.update', $usuario->id], 'method' => 'put']) !!}
                    <div class="mb-3">
                        @foreach ($roles as $role)
                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="roles[]"
                                    id="role{{ $role->id }}"
                                    value="{{ $role->name }}"
                                    {{ $usuario->hasRole($role->name) ? 'checked' : '' }}
                                >
                                <label class="form-check-label" for="role{{ $role->id }}">{{ $role->name }}</label>
                            </div>
                        @endforeach
                    </div>

                    <div class="border-top pt-4 mt-4">
                        <h5 class="mb-2">Clientes autorizados</h5>
                        <p class="text-muted small mb-3">
                            Los roles definen los modulos permitidos. Esta seleccion define los clientes que este usuario puede usar.
                        </p>

                        @php
                            $warehouseCustomers = $customers->where('is_warehouse_client', true);
                            $regularCustomers = $customers->where('is_warehouse_client', false);
                        @endphp

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="fw-semibold mb-2">Clientes normales</div>
                                @forelse ($regularCustomers as $customer)
                                    <div class="form-check">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="customer_ids[]"
                                            id="customer{{ $customer->customer_id }}"
                                            value="{{ $customer->customer_id }}"
                                            {{ in_array((int) $customer->customer_id, $selectedCustomerIds, true) ? 'checked' : '' }}
                                        >
                                        <label class="form-check-label" for="customer{{ $customer->customer_id }}">
                                            {{ $customer->name }}
                                        </label>
                                    </div>
                                @empty
                                    <div class="text-muted small">No hay clientes normales.</div>
                                @endforelse
                            </div>

                            <div class="col-md-6">
                                <div class="fw-semibold mb-2">Clientes de bodegaje</div>
                                @forelse ($warehouseCustomers as $customer)
                                    <div class="form-check">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="customer_ids[]"
                                            id="customer{{ $customer->customer_id }}"
                                            value="{{ $customer->customer_id }}"
                                            {{ in_array((int) $customer->customer_id, $selectedCustomerIds, true) ? 'checked' : '' }}
                                        >
                                        <label class="form-check-label" for="customer{{ $customer->customer_id }}">
                                            {{ $customer->name }}
                                        </label>
                                    </div>
                                @empty
                                    <div class="text-muted small">No hay clientes de bodegaje.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    {!! Form::submit('Guardar asignacion', ['class' => 'btn btn-primary mt-4']) !!}
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card {
        border: none;
        border-radius: 15px;
    }
    .card-header {
        border-top-left-radius: 15px;
        border-top-right-radius: 15px;
    }
    .form-check-input:checked {
        background-color: #dc2626;
        border-color: #dc2626;
    }
</style>
@endsection
