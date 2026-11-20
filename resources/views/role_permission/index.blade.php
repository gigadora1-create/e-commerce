@extends('layouts.app')

@section('contents')
@php
    $groupCatalog = config('role_permissions.groups', []);
    $permissionCatalog = config('role_permissions.permissions', []);
    $selectedPermissionIds = $selectedRole?->permissions->pluck('id')->all() ?? [];
@endphp

<style>
    .role-permission-page { max-width: 1320px; margin: 0 auto; }
    .role-list .list-group-item { border-left: 3px solid transparent; }
    .role-list .list-group-item.active { border-left-color: #fff; font-weight: 700; }
    .permission-group { border: 1px solid var(--bs-border-color, #dee2e6); border-radius: .75rem; overflow: hidden; height: 100%; }
    .permission-group__header { background: rgba(13, 110, 253, .08); padding: .85rem 1rem; }
    .permission-row { padding: .8rem 1rem; border-top: 1px solid var(--bs-border-color, #dee2e6); }
    .permission-row:hover { background: rgba(13, 110, 253, .04); }
    .dark-mode .permission-group__header { background: rgba(79, 140, 255, .16); }
    .dark-mode .permission-row:hover { background: rgba(255, 255, 255, .05); }
</style>

<div class="role-permission-page py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Permisos por rol</h1>
            <p class="text-muted mb-0">Define las vistas y operaciones disponibles para cada rol. Los permisos de cliente heredados no se modifican aqui.</p>
        </div>
        <a href="{{ route('roles.index') }}" class="btn btn-outline-primary">
            <i class="fas fa-user-plus me-1"></i> Crear o editar roles
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <div class="row g-4">
        <aside class="col-lg-3">
            <div class="card role-list shadow-sm">
                <div class="card-header"><strong>Roles disponibles</strong></div>
                <div class="list-group list-group-flush">
                    @forelse($roles as $role)
                        <a href="{{ route('role_permissions.index', ['role' => $role->id]) }}"
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $selectedRole?->id === $role->id ? 'active' : '' }}">
                            <span>{{ $role->name }}</span>
                            <span class="badge {{ $selectedRole?->id === $role->id ? 'bg-light text-dark' : 'bg-secondary' }} rounded-pill">{{ $role->permissions->count() }}</span>
                        </a>
                    @empty
                        <div class="list-group-item text-muted">No hay roles creados.</div>
                    @endforelse
                </div>
            </div>
        </aside>

        <section class="col-lg-9">
            @if($selectedRole)
                <form action="{{ route('role_permissions.assign', $selectedRole->id) }}" method="POST">
                    @csrf
                    <div class="card shadow-sm mb-3">
                        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <div class="text-muted small">Configurando rol</div>
                                <h2 class="h4 mb-0">{{ $selectedRole->name }}</h2>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary" id="clearPermissions">Limpiar seleccion</button>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Guardar permisos</button>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        @foreach($permissionGroups as $groupKey => $groupPermissions)
                            @php($group = $groupCatalog[$groupKey] ?? ['label' => 'Otros permisos', 'icon' => 'fa-key'])
                            <div class="col-md-6">
                                <div class="permission-group">
                                    <div class="permission-group__header d-flex justify-content-between align-items-center">
                                        <strong><i class="fas {{ $group['icon'] }} me-2"></i>{{ $group['label'] }}</strong>
                                        <label class="mb-0 small">
                                            <input type="checkbox" class="form-check-input group-toggle" data-group="{{ $groupKey }}">
                                            Todo
                                        </label>
                                    </div>
                                    @foreach($groupPermissions as $permission)
                                        <label class="permission-row d-flex gap-2 align-items-start mb-0" for="permission-{{ $permission->id }}">
                                            <input class="form-check-input permission-toggle mt-1" type="checkbox" name="permissions[]"
                                                   value="{{ $permission->id }}" id="permission-{{ $permission->id }}"
                                                   data-group="{{ $groupKey }}" {{ in_array($permission->id, $selectedPermissionIds, true) ? 'checked' : '' }}>
                                            <span>
                                                <span class="d-block">{{ $permissionCatalog[$permission->name]['label'] ?? $permission->name }}</span>
                                                <small class="text-muted">{{ $permission->name }}</small>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </form>
            @endif
        </section>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const updateGroupToggle = (group) => {
        const permissions = [...document.querySelectorAll(`.permission-toggle[data-group="${group}"]`)];
        const toggle = document.querySelector(`.group-toggle[data-group="${group}"]`);
        if (toggle && permissions.length) {
            toggle.checked = permissions.every((permission) => permission.checked);
            toggle.indeterminate = !toggle.checked && permissions.some((permission) => permission.checked);
        }
    };

    document.querySelectorAll('.group-toggle').forEach((toggle) => {
        updateGroupToggle(toggle.dataset.group);
        toggle.addEventListener('change', () => {
            document.querySelectorAll(`.permission-toggle[data-group="${toggle.dataset.group}"]`)
                .forEach((permission) => { permission.checked = toggle.checked; });
            updateGroupToggle(toggle.dataset.group);
        });
    });

    document.querySelectorAll('.permission-toggle').forEach((permission) => {
        permission.addEventListener('change', () => updateGroupToggle(permission.dataset.group));
    });

    document.getElementById('clearPermissions')?.addEventListener('click', () => {
        document.querySelectorAll('.permission-toggle').forEach((permission) => { permission.checked = false; });
        document.querySelectorAll('.group-toggle').forEach((toggle) => updateGroupToggle(toggle.dataset.group));
    });
});
</script>
@endsection
