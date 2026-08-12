@extends('layouts.app')
@section('contents')

<div class="text-center mb-4">
    <h1 style="font-weight: bold; font-size: 30px;">Formatos</h1>
</div>

<link rel="stylesheet" href="{{ asset('admin_assets/css/crud.css') }}">
<hr />
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

<div class="scroll-horizontal">
    <table class="table table-bordered">
        <thead class="table-primary">
            <tr>
                <th style="color: white;">Acciones</th>
                <th style="color: white;">Número</th>
                <th style="color: white;">Imagen</th> <!-- Nueva columna para la imagen -->
            </tr>
        </thead>
        <tbody>
            @forelse ($files as $file)
                <tr>
                    <td>
                        <a href="{{ route('files.view', $file->id) }}" class="btn btn-secondary"><i
                                class="fa fa-eye"></i></a>
                        <a href="{{ route('files.download', $file->id) }}" class="btn btn-success btn">
                            <i class="fas fa-download"></i>
                        </a>

                        <form action="{{ route('files.delete', $file->id) }}" method="POST"
                            class="btn btn-danger p-0" id="deleteForm">
                            @csrf
                            @method('DELETE') <!-- Agrega esta línea para enviar la solicitud DELETE -->
                            <button type="submit" class="btn btn-danger" onclick="confirmDelete('{{ $file->id }}')">
                                <i class="fa fa-trash"></i>
                            </button>
                        </form>
                    </td>
                    <td>{{ $file->nombre }}</td>
                    <td>
                        @if($file->ruta)
                            <img src="{{ asset('storage/' . $file->ruta) }}" alt="Imagen de archivo" style="max-width: 100px;">
                        @endif
                    </td>
                </tr>
            @empty
            
            @endforelse
        </tbody>
    </table>

    <div class="d-flex justify-content-center">
        {{ $files->links('pagination.custom') }}
    </div>
    
    <!-- Formulario para cargar la imagen -->
    <form action="{{ route('files.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label for="archivo">Cargar Imagen:</label>
            <input type="file" name="archivo" class="form-control-file" id="archivo">
        </div>
        <button type="submit" class="btn btn-primary">Cargar Imagen</button>
    </form>
</div>
    
@endsection
