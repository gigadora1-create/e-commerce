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
</style>
@endpush

@section('contents')
<div class="container-fluid py-4 preferences-page">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800">Preferencias personales</h1>
            <p class="mb-0 text-muted">Configura la apariencia y la seguridad de tu cuenta.</p>
        </div>
        @if(auth()->user()->isSuperAdmin())
            <a href="{{ route('profiles.preferences.index') }}" class="btn btn-primary mt-3 mt-md-0">
                <i class="fas fa-users-cog mr-1"></i> Administrar usuarios
            </a>
        @endif
    </div>

    <div class="row justify-content-center">
        <div class="col-xl-8">
            <form method="POST" action="{{ route('preferences.update') }}" class="card shadow-sm">
                @csrf
                @method('PUT')
                <div class="card-body p-4">
                    <section aria-labelledby="appearance-heading">
                        <div class="d-flex align-items-center mb-3">
                            <span class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mr-3" style="width: 42px; height: 42px;">
                                <i class="fas fa-palette"></i>
                            </span>
                            <div>
                                <h2 id="appearance-heading" class="h5 mb-0">Apariencia</h2>
                                <small class="text-muted">La seleccion se conserva en cualquier dispositivo donde inicies sesion.</small>
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label for="theme_mode" class="font-weight-bold">Modo visual</label>
                            <select id="theme_mode" name="theme_mode" class="form-control @error('theme_mode') is-invalid @enderror">
                                <option value="system" @selected(old('theme_mode', $user->theme_mode) === 'system')>Automatico segun el navegador</option>
                                <option value="light" @selected(old('theme_mode', $user->theme_mode) === 'light')>Claro</option>
                                <option value="dark" @selected(old('theme_mode', $user->theme_mode) === 'dark')>Oscuro</option>
                            </select>
                            @error('theme_mode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </section>

                    <hr>

                    <section aria-labelledby="security-heading">
                        <div class="d-flex align-items-center mb-3">
                            <span class="rounded-circle bg-danger text-white d-inline-flex align-items-center justify-content-center mr-3" style="width: 42px; height: 42px;">
                                <i class="fas fa-shield-alt"></i>
                            </span>
                            <div>
                                <h2 id="security-heading" class="h5 mb-0">Seguridad</h2>
                                <small class="text-muted">Controla la doble validacion al iniciar sesion.</small>
                            </div>
                        </div>

                        @unless($twoFactorGloballyEnabled)
                            <div class="alert alert-warning" role="alert">
                                La doble validacion esta desactivada globalmente. Esta preferencia se guardara, pero no se aplicara hasta que el administrador la habilite.
                            </div>
                        @endunless

                        <input type="hidden" name="two_factor_enabled" value="0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="two_factor_enabled" name="two_factor_enabled" value="1" @checked(old('two_factor_enabled', $user->two_factor_enabled))>
                            <label class="custom-control-label font-weight-bold" for="two_factor_enabled">Solicitar codigo de doble validacion</label>
                            <small class="form-text text-muted">Al activarlo, el sistema pedira un codigo adicional despues de la contrasena.</small>
                        </div>
                    </section>
                </div>
                <div class="card-footer bg-transparent text-right">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Guardar preferencias
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
