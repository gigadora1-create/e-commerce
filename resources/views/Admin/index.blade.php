@extends('layouts.app')

@section('title', '')

@section('contents')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <table id="example" class="table table-bordered mb-0 table-centered">
                    <thead class="thead-light">
                        <tr>
                            <th style="text-align: center">EDITAR</th>
                            <th style="text-align: center">ID</th>
                            <th style="text-align: center">NOMBRE</th>
                            <th style="text-align: center">EMAIL</th>
                        </tr>
                    </thead>
                    <tbody style="text-align: center; font-size: 11px;">
                        @foreach ($users as $user)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.edit', $user) }}" aria-label="Editar usuario">
                                        <i class="fas fa-key fa-lg" style="color: #FFB822;"></i>
                                    </a>
                                </td>
                                <td>{{ $user->id }}</td>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
@endpush