@extends('layouts.app')

@section('contents')
<div class="text-center mb-4">
    <h1 style="font-weight: bold; font-size: 30px;">Editar Log de SMS</h1>
</div>

<link rel="stylesheet" href="{{ asset('admin_assets/css/crud.css') }}">

<form action="{{ route('send.update', $log->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="form-group">
        <label for="phone_number">Número de Teléfono</label>
        <input type="text" class="form-control" id="phone_number" name="phone_number" value="{{ $log->phone_number }}" required>
    </div>
    <div class="form-group">
        <label for="message">Mensaje</label>
        <textarea class="form-control" id="message" name="message" rows="3" required>{{ $log->message }}</textarea>
    </div>
    <div class="form-group">
        <label for="status">Estado</label>
        <select class="form-control" id="status" name="status" required>
            <option value="enviado" {{ $log->status == 'enviado' ? 'selected' : '' }}>Enviado</option>
            <option value="pendiente" {{ $log->status == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
            <option value="fallido" {{ $log->status == 'fallido' ? 'selected' : '' }}>Fallido</option>
        </select>
    </div>
    <button type="submit" class="btn btn-primary">Actualizar</button>
    <a href="{{ route('send.index') }}" class="btn btn-secondary">Cancelar</a>
</form>
@endsection
    