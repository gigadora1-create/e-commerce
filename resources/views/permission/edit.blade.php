@extends('layouts.app')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
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
    <h1 style="font-weight: bold; font-size: 30px;"> Editar Permisos</h1>
  </div>
    <div class="container">
        <h1 class="mb-0"></h1>
        <hr />
        <form action="{{ route('permissions.update', $user->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">id</label>
                    <input type="text" name="id" class="form-control" placeholder="id" value="{{ $user->id }}"
                        onblur="if(this.value=='')Swal.fire('El campo ' + this.getAttribute('placeholder') + ' está vacío')">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="name" class="form-control" placeholder="Nombre"
                        value="{{ $user->name }}"
                        onblur="if(this.value=='')Swal.fire('El campo ' + this.getAttribute('placeholder') + ' está vacío')">
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="d-grid">
                        <button class="btn btn-warning">Enviar</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
