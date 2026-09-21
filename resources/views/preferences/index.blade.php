@extends('layouts.app')

@push('styles')
<style>
    html.dark-mode .preferences-page .text-muted {
        color: #cbd5e1 !important;
    }

    html.dark-mode .preferences-page .btn-primary {
        background-color: #0056b3 !important;
        border-color: #0056b3 !important;
        color: #ffffff !important;
    }

    .preferences-toolbar {
        gap: 0.75rem;
    }
</style>
@endpush

@section('contents')
<div class="container-fluid py-4 preferences-page">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800">Preferencias de usuarios</h1>
            <p class="mb-0 text-muted">Administra el modo visual y la doble validacion de cada cuenta.</p>
        </div>
        <a href="{{ route('preferences.edit') }}" class="btn btn-primary mt-3 mt-md-0">
            <i class="fas fa-user-cog mr-1"></i> Mis preferencias
        </a>
    </div>

    @unless($twoFactorGloballyEnabled)
        <div class="alert alert-warning" role="alert">
            La doble validacion esta desactivada globalmente. Los valores individuales se guardan para cuando se habilite nuevamente.
        </div>
    @endunless

    <div class="card shadow-sm">
        <div class="card-body border-bottom">
            <div class="d-flex flex-column flex-lg-row align-items-lg-end preferences-toolbar">
                <div class="flex-grow-1">
                    <label for="user-preference-filter" class="font-weight-bold mb-1">Buscar usuario</label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
                        <input id="user-preference-filter" type="search" class="form-control" placeholder="Nombre o correo del usuario" autocomplete="off">
                    </div>
                    <small id="user-preference-filter-count" class="form-text text-muted">Mostrando {{ $users->count() }} usuario(s).</small>
                </div>
                <form method="POST" action="{{ route('profiles.preferences.two-factor.update') }}" class="d-flex flex-wrap preferences-toolbar" data-swal-confirm>
                    @csrf
                    @method('PUT')
                    <button type="submit" name="two_factor_enabled" value="1" class="btn btn-success" data-swal-title="Activar doble validacion" data-swal-text="Se activara 2FA para todos los usuarios, incluidas las cuentas inactivas." data-swal-confirm-text="Si, activar">
                        <i class="fas fa-shield-alt mr-1"></i> Activar 2FA masivamente
                    </button>
                    <button type="submit" name="two_factor_enabled" value="0" class="btn btn-outline-danger" data-swal-title="Desactivar doble validacion" data-swal-text="Se desactivara 2FA para todos los usuarios, incluidas las cuentas inactivas." data-swal-confirm-text="Si, desactivar">
                        <i class="fas fa-shield-alt mr-1"></i> Desactivar 2FA masivamente
                    </button>
                </form>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Datos organizacionales</th>
                            <th>Estado</th>
                            <th style="min-width: 310px;">Preferencias</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr data-user-preference-row data-search="{{ $user->name }} {{ $user->email }}">
                                <td>
                                    <strong>{{ $user->name }}</strong><br>
                                    <small class="text-muted">{{ $user->email }}</small>
                                </td>
                                <td>
                                    <small>{{ $user->position ?: 'Sin cargo' }}</small><br>
                                    <small class="text-muted">{{ $user->process ?: 'Sin proceso' }}{{ $user->regional ? ' / ' . $user->regional : '' }}</small>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $user->is_active ? 'success' : 'secondary' }}">{{ $user->is_active ? 'Activo' : 'Inactivo' }}</span>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('profiles.preferences.update', $user) }}" class="d-flex flex-column flex-lg-row align-items-lg-center">
                                        @csrf
                                        @method('PUT')
                                        <label class="sr-only" for="theme_{{ $user->id }}">Modo visual de {{ $user->name }}</label>
                                        <select id="theme_{{ $user->id }}" name="theme_mode" class="form-control form-control-sm mr-lg-2 mb-2 mb-lg-0">
                                            <option value="system" @selected($user->theme_mode === 'system')>Sistema</option>
                                            <option value="light" @selected($user->theme_mode === 'light')>Claro</option>
                                            <option value="dark" @selected($user->theme_mode === 'dark')>Oscuro</option>
                                        </select>
                                        <input type="hidden" name="two_factor_enabled" value="0">
                                        <div class="custom-control custom-switch mr-lg-2 mb-2 mb-lg-0">
                                            <input type="checkbox" class="custom-control-input" id="two_factor_{{ $user->id }}" name="two_factor_enabled" value="1" @checked($user->two_factor_enabled)>
                                            <label class="custom-control-label text-nowrap" for="two_factor_{{ $user->id }}">2FA</label>
                                        </div>
                                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save mr-1"></i> Guardar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No hay usuarios registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const filter = document.getElementById('user-preference-filter');
        const count = document.getElementById('user-preference-filter-count');
        const rows = Array.from(document.querySelectorAll('[data-user-preference-row]'));

        if (!filter || !count) {
            return;
        }

        const normalize = (value) => value
            .toLocaleLowerCase('es-CO')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');

        filter.addEventListener('input', () => {
            const query = normalize(filter.value.trim());
            let visible = 0;

            rows.forEach((row) => {
                const matches = normalize(row.dataset.search).includes(query);
                row.hidden = !matches;
                visible += matches ? 1 : 0;
            });

            count.textContent = `Mostrando ${visible} usuario(s).`;
        });
    });
</script>
@endpush
