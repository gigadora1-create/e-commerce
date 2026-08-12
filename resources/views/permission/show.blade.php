@extends('layouts.app')
@section('title', '')
@section('contents')
<div class="row mt-4">
    <div class="col-md-12">
        <a href="{{ route('permissions.index') }}" class="d-flex align-items-center">
            <i class="fas fa-arrow-left fa-2x text-primary me-2"></i> 
            <span class="text-primary">Regresar</span>
        </a>
    </div>
</div>
<div class="text-center mb-4">
    <h1 style="font-weight: bold; font-size: 30px;">Ver Permisos</h1>
  </div>
    <div class="container">
        <h1 class="mb-0"></h1>
        <hr />

        <div class="row">
            <!-- Primer Bloque -->
            <div class="col-md-6 mb-3">
                <label class="form-label">id</label>
                <input type="text" name="id" class="form-control" placeholder="id" value="{{ $user->id }}" readonly>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Nombre</label>
                <input type="text" name="name" class="form-control" placeholder="Nombre" value="{{ $user->name }}" readonly>
            </div>

          
        </div>
    </div>
@endsection
