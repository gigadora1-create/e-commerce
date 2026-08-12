@extends('layouts.app')

@section('contents')
<div class="text-center mb-4">
    <h1 style="font-weight: bold; font-size: 30px;">Agregar Log de SMS</h1>
</div>

<link rel="stylesheet" href="{{ asset('admin_assets/css/crud.css') }}">

<form action="{{ route('send.store') }}" method="POST">
    @csrf
    <div class="form-group">
        <label for="phone_number">Número de Teléfono</label>
        <input type="text" class="form-control" id="phone_number" name="phone_number" required>
    </div>
    <div class="form-group">
        <label for="message">Mensaje</label>
        <textarea class="form-control" id="message" name="message" rows="3" required></textarea>
    </div>
    <div class="form-group">
        <label for="status">Estado</label>
        <select class="form-control" id="status" name="status" required>
            <option value="enviado">Enviado</option>
            <option value="pendiente">Pendiente</option>
            <option value="fallido">Fallido</option>
        </select>
    </div>
    <button type="submit" class="btn btn-primary">Guardar</button>
    <a href="{{ route('send.index') }}" class="btn btn-secondary">Cancelar</a>
</form>
@endsection
