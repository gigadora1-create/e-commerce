@extends('layouts.app')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
@section('title', '')
@section('contents')
    <div class="container">
        <div class="text-center mb-4">
            <h1 style="font-weight: bold; font-size: 30px;"> Crear Permisos</h1>
          </div>        
        <h1 class="mb-0"></h1>
        <hr />

        <form action="{{ route('permissions.store') }}" method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
            @csrf
            <div class="row mb-3">
                <div class="col-md-6">
                    <input type="text" name="name" class="form-control" placeholder="Nombre">
                </div>
            </div>


                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">Enviar</button>
                    </div>
                </div>
        </form>
    </div>
    <script>
        function validateForm() {
            var inputs = document.getElementsByTagName("input");
            for (var i = 0; i < inputs.length; i++) {
                if (inputs[i].value.trim() === "") {
                    Swal.fire('Por favor, complete todos los campos antes de enviar.');
                    return false;
                }
            }
            return true;
        }
    </script>
@endsection
