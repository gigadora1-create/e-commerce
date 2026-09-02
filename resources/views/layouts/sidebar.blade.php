<ul class="navbar-nav bg-white sidebar sidebar-light accordion" id="accordionSidebar">
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
        <li class="nav-item">
            <a class="nav-link d-flex flex-column align-items-center text-center" href="{{ route('warehouse.index') }}">
                <i class="fas fa-warehouse"></i>
                <span>Bodega / Trazabilidad</span>
            </a>
        </li>
    @elseif($isSupplyAdminOnly)
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex flex-column align-items-center text-center" href="#"
                id="supplyOnlyDropdown" role="button" data-toggle="dropdown" aria-haspopup="true"
                aria-expanded="false">
                <i class="fas fa-clipboard-list"></i>
                <span>Proveeduria</span>
            </a>
            <div class="dropdown-menu" aria-labelledby="supplyOnlyDropdown">
                <a class="dropdown-item" href="{{ route('supplies.index') }}">
                    <i class="fas fa-dolly-flatbed"></i> Abastecimiento
                </a>
                <a class="dropdown-item" href="{{ route('supplies.index', ['tab' => 'products']) }}">
                    <i class="fas fa-box-open"></i> Catalogo Proveeduria
                </a>
                <a class="dropdown-item" href="{{ route('supplies.issues.index') }}">
                    <i class="fas fa-file-export"></i> Solicitudes usuarios
                </a>
            </div>
        </li>
    @elseif($isSupplyRequesterOnly)
        <li class="nav-item">
            <a class="nav-link d-flex flex-column align-items-center text-center" href="{{ route('supplies.issues.index') }}">
                <i class="fas fa-clipboard-list"></i>
                <span>Proveeduria</span>
            </a>
        </li>
    @else
        <li class="nav-item">
            <a class="nav-link d-flex flex-column align-items-center text-center" href="{{ route('dashboard') }}">
                <i class="fas fa-fw fa-tachometer-alt"></i>
                <span>Inicio</span>
            </a>
        </li>

        @if(auth()->check() && !auth()->user()->hasRole('USUARIO_CLIENTE'))
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex flex-column align-items-center text-center" href="#"
                    id="ecommerceDropdown" role="button" data-toggle="dropdown" aria-haspopup="true"
                    aria-expanded="false">
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
            </li>
        @endif

        @if(auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->can('supplies.admin') || auth()->user()->can('supplies.request')))
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex flex-column align-items-center text-center" href="#"
                    id="suppliesDropdown" role="button" data-toggle="dropdown" aria-haspopup="true"
                    aria-expanded="false">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Proveeduria</span>
                </a>
                <div class="dropdown-menu" aria-labelledby="suppliesDropdown">
                    @if(auth()->user()->isSuperAdmin() || auth()->user()->can('supplies.admin'))
                        <a class="dropdown-item" href="{{ route('supplies.index') }}">
                            <i class="fas fa-dolly-flatbed"></i> Abastecimiento
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
            </li>
        @endif

        @if(auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->can('UBICACION')))
            <li class="nav-item">
                <a class="nav-link d-flex flex-column align-items-center text-center"
                    href="{{ route('locations.index') }}">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Ubicaciones</span>
                </a>
            </li>
        @endif

        @if(auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->can('warehouse.view') || auth()->user()->can('warehouse.manage')))
            <li class="nav-item">
                <a class="nav-link d-flex flex-column align-items-center text-center"
                    href="{{ route('warehouse.index') }}">
                    <i class="fas fa-warehouse"></i>
                    <span>Bodega</span>
                </a>
            </li>
        @endif

        <li class="nav-item">
            <a class="nav-link d-flex flex-column align-items-center text-center"
                href="{{ route('barcode.index') }}">
                <i class="fas fa-barcode"></i>
                <span>Codigos de Barras</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link d-flex flex-column align-items-center text-center"
                href="{{ route('picking.index') }}">
                <i class="fas fa-dolly"></i>
                <span>Picking</span>
            </a>
        </li>

        @can('SUPER_ADMIN')
            <li class="nav-item">
                <a class="nav-link d-flex flex-column align-items-center text-center" href="{{ route('send.index') }}">
                    <i class="fas fa-sms"></i>
                    <span>Mensajeria</span>
                </a>
            </li>
        @endcan

        @if(auth()->check() && auth()->user()->isSuperAdmin())
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex flex-column align-items-center text-center" href="#"
                    id="usuariosDropdown" role="button" data-toggle="dropdown" aria-haspopup="true"
                    aria-expanded="false">
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
                    <a class="dropdown-item icon-tooltip" href="{{ route('role_permissions.index') }}"
                        data-tooltip="Asignar Permisos a Roles">
                        <i class="fas fa-fw fa-user-lock"></i> Asignar Permisos
                    </a>
                </div>
            </li>
        @endif
    @endif
</ul>
