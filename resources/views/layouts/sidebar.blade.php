<nav class="navbar-nav bg-white sidebar sidebar-light accordion" id="accordionSidebar" aria-label="Navegación principal">
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="https://www.glecolombia.com/">
        <div class="logo-container">
            <img src="/images/logo_ecommerce.png" alt="Logotipo" class="logo-image">
        </div>
    </a>

    <hr class="sidebar-divider">

    @php
        $isWarehouseOnly = auth()->check() && method_exists(auth()->user(), 'isWarehouseOnly')
            ? auth()->user()->isWarehouseOnly()
            : false;
        $isSupplyRequesterOnly = auth()->check() && method_exists(auth()->user(), 'isSupplyRequesterOnly')
            ? auth()->user()->isSupplyRequesterOnly()
            : false;
        $isSupplyAdminOnly = auth()->check() && method_exists(auth()->user(), 'isSupplyAdminOnly')
            ? auth()->user()->isSupplyAdminOnly()
            : false;
    @endphp

    @if($isWarehouseOnly)
        <div class="nav-item">
            <a class="nav-link d-flex flex-column align-items-center text-center" href="{{ route('warehouse.index') }}" aria-label="Bodega y trazabilidad">
                <i class="fas fa-warehouse"></i>
                <span>Bodega / Trazabilidad</span>
            </a>
        </div>
    @elseif($isSupplyAdminOnly)
        <div class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex flex-column align-items-center text-center" href="#"
                id="supplyOnlyDropdown" role="button" data-toggle="dropdown" aria-haspopup="true"
                aria-expanded="false" aria-label="Proveeduría">
                <i class="fas fa-clipboard-list"></i>
                <span>Proveeduria</span>
            </a>
            <div class="dropdown-menu" aria-labelledby="supplyOnlyDropdown">
                <a class="dropdown-item" href="{{ route('supplies.index') }}">
                    <i class="fas fa-dolly-flatbed"></i> Proveeduria
                </a>
                <a class="dropdown-item" href="{{ route('supplies.index', ['tab' => 'products']) }}">
                    <i class="fas fa-box-open"></i> Catalogo Proveeduria
                </a>
                <a class="dropdown-item" href="{{ route('supplies.issues.index') }}">
                    <i class="fas fa-file-export"></i> Solicitudes usuarios
                </a>
            </div>
        </div>
    @elseif($isSupplyRequesterOnly)
        <div class="nav-item">
            <a class="nav-link d-flex flex-column align-items-center text-center" href="{{ route('supplies.issues.index') }}" aria-label="Proveeduria">
                <i class="fas fa-clipboard-list"></i>
                <span>Proveeduria</span>
            </a>
        </div>
    @else
        <div class="nav-item">
            <a class="nav-link d-flex flex-column align-items-center text-center" href="{{ route('dashboard') }}" aria-label="Inicio">
                <i class="fas fa-fw fa-tachometer-alt"></i>
                <span>Inicio</span>
            </a>
        </div>

        @if(auth()->check() && !auth()->user()->hasRole('USUARIO_CLIENTE'))
            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex flex-column align-items-center text-center" href="#"
                    id="ecommerceDropdown" role="button" data-toggle="dropdown" aria-haspopup="true"
                    aria-expanded="false" aria-label="E-commerce">
                    <i class="fas fa-store"></i>
                    <span>E-commerce</span>
                </a>
                <div class="dropdown-menu" aria-labelledby="ecommerceDropdown">
                    <a class="dropdown-item" href="{{ route('inventories.index') }}">
                        <i class="fas fa-boxes"></i> Ingreso Productos
                    </a>
                    <a class="dropdown-item" href="{{ route('inventory-outputs.index') }}">
                        <i class="fas fa-truck"></i> Salida Productos
                    </a>
                    <a class="dropdown-item" href="{{ route('inventories.create') }}">
                        <i class="fas fa-box-open"></i> Devolucion_Retencion
                    </a>
                    <a class="dropdown-item" href="{{ route('items.index') }}">
                        <i class="fas fa-archive"></i> Productos
                    </a>
                    <a class="dropdown-item" href="{{ route('cities.index') }}">
                        <i class="fas fa-city"></i> Bodegas
                    </a>
                    <a class="dropdown-item" href="{{ route('customers.index') }}">
                        <i class="fas fa-users"></i> Clientes
                    </a>
                </div>
            </div>
        @endif

        @if(auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->can('supplies.admin') || auth()->user()->can('supplies.request')))
            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex flex-column align-items-center text-center" href="#"
                    id="suppliesDropdown" role="button" data-toggle="dropdown" aria-haspopup="true"
                    aria-expanded="false" aria-label="Proveeduria">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Proveeduria</span>
                </a>
                <div class="dropdown-menu" aria-labelledby="suppliesDropdown">
                    @if(auth()->user()->isSuperAdmin() || auth()->user()->can('supplies.admin'))
                        <a class="dropdown-item" href="{{ route('supplies.index') }}">
                            <i class="fas fa-dolly-flatbed"></i> Proveeduria
                        </a>
                        <a class="dropdown-item" href="{{ route('supplies.index', ['tab' => 'products']) }}">
                            <i class="fas fa-box-open"></i> Catalogo Proveeduria
                        </a>
                    @endif
                    @if(auth()->user()->isSuperAdmin() || auth()->user()->can('supplies.request'))
                        <a class="dropdown-item" href="{{ route('supplies.issues.index') }}">
                            <i class="fas fa-file-export"></i> Solicitudes usuarios
                        </a>
                    @endif
                </div>
            </div>
        @endif

        @if(auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->can('UBICACION')))
            <div class="nav-item">
                <a class="nav-link d-flex flex-column align-items-center text-center"
                    href="{{ route('locations.index') }}" aria-label="Ubicaciones">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Ubicaciones</span>
                </a>
            </div>
        @endif

        @if(auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->can('warehouse.view') || auth()->user()->can('warehouse.manage')))
            <div class="nav-item">
                <a class="nav-link d-flex flex-column align-items-center text-center"
                    href="{{ route('warehouse.index') }}" aria-label="Bodega">
                    <i class="fas fa-warehouse"></i>
                    <span>Bodega</span>
                </a>
            </div>
        @endif

        <div class="nav-item">
            <a class="nav-link d-flex flex-column align-items-center text-center"
                href="{{ route('barcode.index') }}" aria-label="Codigos de barras">
                <i class="fas fa-barcode"></i>
                <span>Codigos de Barras</span>
            </a>
        </div>

        <div class="nav-item">
            <a class="nav-link d-flex flex-column align-items-center text-center"
                href="{{ route('picking.index') }}" aria-label="Picking">
                <i class="fas fa-dolly"></i>
                <span>Picking</span>
            </a>
        </div>

        @can('SUPER_ADMIN')
            <div class="nav-item">
                <a class="nav-link d-flex flex-column align-items-center text-center" href="{{ route('send.index') }}" aria-label="Mensajeria">
                    <i class="fas fa-sms"></i>
                    <span>Mensajeria</span>
                </a>
            </div>
        @endcan

        @if(auth()->check() && auth()->user()->isSuperAdmin())
            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex flex-column align-items-center text-center" href="#"
                    id="usuariosDropdown" role="button" data-toggle="dropdown" aria-haspopup="true"
                    aria-expanded="false" aria-label="Usuarios">
                    <i class="fas fa-fw fa-users"></i>
                    <span>Usuarios</span>
                </a>
                <div class="dropdown-menu" aria-labelledby="usuariosDropdown">
                    <a class="dropdown-item" href="{{ route('roles.index') }}">
                        <i class="fas fa-fw fa-user-cog"></i> Roles
                    </a>
                    <a class="dropdown-item" href="{{ route('permissions.index') }}">
                        <i class="fas fa-fw fa-lock"></i> Permisos
                    </a>
                    <a class="dropdown-item" href="{{ route('admin.index') }}">
                        <i class="fas fa-fw fa-id-card"></i> Administrador
                    </a>
                    <a class="dropdown-item" href="{{ route('profiles.preferences.index') }}">
                        <i class="fas fa-fw fa-sliders-h"></i> Preferencias
                    </a>
                    <a class="dropdown-item icon-tooltip" href="{{ route('role_permissions.index') }}"
                        data-tooltip="Asignar Permisos a Roles">
                        <i class="fas fa-fw fa-user-lock"></i> Asignar Permisos
                    </a>
                </div>
            </div>
        @endif
    @endif
</nav>
