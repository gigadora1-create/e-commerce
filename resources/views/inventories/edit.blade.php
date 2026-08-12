@extends('layouts.app')

@section('contents')
<style>
    .custom-card {
        border-radius: 15px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
    }
    .custom-card .card-header {
        background-color: #4ae29b;
        color: white;
        border-radius: 15px 15px 0 0;
    }
    .custom-card .card-body {
        padding: 20px;
    }
    .custom-card .form-group label {
        font-weight: bold;
        color: #333;
    }
    .custom-card .form-control {
        border-radius: 10px;
        border: 1px solid #ddd;
        background-color: #f9f9f9;
    }
    .custom-card .btn-primary {
        background-color: #4ae287;
        border: none;
        border-radius: 10px;
    }
    .custom-card .btn-secondary {
        background-color: #6c757d;
        border: none;
        border-radius: 10px;
    }
</style>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="custom-card shadow-lg">
                <div class="card-header">
                    <h3 class="mb-0">Editar Inventario</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('inventories.update', ['inventory' => $inventory->id]) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="sku">SKU</label>
                                    <input type="text" class="form-control" id="sku" name="sku" value="{{ $inventory->sku }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="status">Estado</label>
                                    <input type="text" class="form-control" id="status" name="status" value="{{ $inventory->status }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="batch">Lote</label>
                                    <input type="text" class="form-control" id="batch" name="batch" value="{{ $inventory->batch }}" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="expiry_date">Fecha de Expiración</label>
                                    <input type="date" class="form-control" id="expiry_date" name="expiry_date" value="{{ $inventory->expiry_date ? $inventory->expiry_date->format('Y-m-d') : '' }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="item_condition">Condición del Artículo</label>
                                    <input type="text" class="form-control" id="item_condition" name="item_condition" value="{{ $inventory->item_condition }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="entry_date">Fecha de Ingreso</label>
                                    <input type="date" class="form-control" id="entry_date" name="entry_date" value="{{ $inventory->entry_date ? $inventory->entry_date->format('Y-m-d') : '' }}">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="warehouse">Almacén</label>
                                    <input type="text" class="form-control" id="warehouse" name="warehouse" value="{{ $inventory->warehouse }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="commerce">Comercio</label>
                                    <input type="text" class="form-control" id="commerce" name="commerce" value="{{ $inventory->commerce }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="item_description">Descripción del Artículo</label>
                                    <textarea class="form-control" id="item_description" name="item_description" required>{{ $inventory->item_description }}</textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="quantity">Cantidad</label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" value="{{ $inventory->quantity }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="value">Valor</label>
                                    <input type="number" step="0.01" class="form-control" id="value" name="value" value="{{ $inventory->value }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="type">Tipo</label>
                                    <input type="text" class="form-control" id="type" name="type" value="{{ $inventory->type }}" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="observations">Observaciones</label>
                                    <textarea class="form-control" id="observations" name="observations">{{ $inventory->observations }}</textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <button type="submit" class="btn btn-primary btn-block">Actualizar Inventario</button>
                            </div>
                            <div class="col-md-6">
                                <a href="{{ route('inventories.index') }}" class="btn btn-secondary btn-block">Cancelar</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection