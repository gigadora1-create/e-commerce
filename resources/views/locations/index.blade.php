@extends('layouts.app')

@section('contents')
<div class="d-flex justify-content-end align-items-center">
    @if(session('selected_customer'))
        <div class="alert alert-info d-inline-flex align-items-center mb-0 py-2 px-3">
            <i class="fas fa-user me-2"></i>
            <strong>Cliente: {{ session('selected_customer') }}</strong>
        </div>
    @else
        <button class="btn btn-primary" id="selectCustomerBtn">
            <i class="fas fa-user-plus me-1"></i> Seleccionar Cliente
        </button>
    @endif
</div>

<link rel="stylesheet" href="{{ asset('css/location.index.css') }}">    
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Gestión de Ubicaciones</h3>
                    <div>
                        <button class="btn btn-light me-2" data-bs-toggle="modal" data-bs-target="#locationModal" id="createLocationBtn">
                            <i class="fas fa-plus me-2"></i>Crear Ubicación
                        </button>
                        <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#assignItemModal">
                            <i class="fas fa-box-open me-2"></i>Asignar Producto
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Controles de filtro mejorados -->
                    <div class="row mb-4 filter-controls bg-light p-3 rounded">
                        <div class="col-md-3">
                            <label for="warehouse-filter" class="form-label">Filtrar por Bodega</label>
                            <select id="warehouse-filter" class="form-select">
                                <option value="">Todas las bodegas</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse }}">{{ $warehouse }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="search-filter" class="form-label">Buscar</label>
                            <input type="text" id="search-filter" class="form-control" placeholder="Buscar por código, nombre o producto...">
                        </div>
                        <div class="col-md-2">
                            <label for="capacity-filter" class="form-label">Ocupación</label>
                            <select id="capacity-filter" class="form-select">
                                <option value="">Todas</option>
                                <option value="high">Alta (>80%)</option>
                                <option value="medium">Media (50-80%)</option>
                                <option value="low">Baja (<50%)</option>
                                <option value="empty">Vacías</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="items-per-page" class="form-label">Mostrar</label>
                            <select id="items-per-page" class="form-select">
                                <option value="6">6 por página</option>
                                <option value="12" selected>12 por página</option>
                                <option value="24">24 por página</option>
                                <option value="all">Todas</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" id="clear-filters" class="btn btn-outline-secondary w-100" title="Limpiar filtros">
                                <i class="fas fa-times me-2"></i>Limpiar
                            </button>
                        </div>
                    </div>

                    <!-- Información y controles de paginación -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div id="pagination-info" class="text-muted">
                                Mostrando 0 de 0 ubicaciones
                            </div>
                        </div>
                        <div class="col-md-6">
                            <nav aria-label="Paginación de ubicaciones">
                                <ul class="pagination pagination-sm justify-content-end mb-0" id="pagination-controls">
                                    <!-- Controles generados dinámicamente -->
                                </ul>
                            </nav>
                        </div>
                    </div>

                    <div class="row" id="locations-container">
                        <div class="col-12 text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para crear/editar ubicación -->
    <div class="modal fade" id="locationModal" tabindex="-1" aria-labelledby="locationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="locationForm">
                    <input type="hidden" id="location-id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="locationModalLabel">Crear Ubicación</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="location-code" class="form-label">Código *</label>
                            <input type="text" class="form-control" id="location-code" required maxlength="10">
                        </div>
                        <div class="mb-3">
                            <label for="location-name" class="form-label">Nombre *</label>
                            <input type="text" class="form-control" id="location-name" required maxlength="255">
                        </div>
                        <div class="mb-3">
                            <label for="location-warehouse" class="form-label">Bodega *</label>
                            <select class="form-select" id="location-warehouse" required>
                                <option value="">Seleccionar bodega</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse }}">{{ $warehouse }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="location-description" class="form-label">Descripción</label>
                            <textarea class="form-control" id="location-description" rows="2" maxlength="1000"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="location-max-capacity" class="form-label">Capacidad Máxima *</label>
                            <input type="number" class="form-control" id="location-max-capacity" min="1" value="100" required>
                            <small class="text-muted">Número máximo de unidades totales para esta ubicación.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="spinner-border spinner-border-sm d-none me-2" id="location-submit-spinner"></span>
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para asignar producto -->
    <div class="modal fade" id="assignItemModal" tabindex="-1" aria-labelledby="assignItemModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="assignItemForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="assignItemModalLabel">Asignar Producto a Ubicación</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="assign-item" class="form-label">Producto *</label>
                            <select class="form-select" id="assign-item" required>
                                <option value="">Seleccione un producto</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->item_id }}" data-sku="{{ $item->sku }}">
                                        {{ $item->name }} ({{ $item->sku }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="assign-location" class="form-label">Ubicación *</label>
                            <select class="form-select" id="assign-location" required>
                                <option value="">Seleccione una ubicación</option>
                                @foreach($locations as $location)
                                    @if($location->code !== 'PENDIENTES')
                                        <option value="{{ $location->location_id }}" data-code="{{ $location->code }}" data-name="{{ $location->name }}">
                                            {{ $location->code }} - {{ $location->name }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="assign-max-capacity" class="form-label">Capacidad Máxima para este Producto *</label>
                            <input type="number" class="form-control" id="assign-max-capacity" min="1" value="100" required>
                            <small class="text-muted">Máximo de unidades de este producto en esta ubicación.</small>
                        </div>
                        <div class="alert alert-warning d-none" id="capacity-warning">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <span id="capacity-warning-text"></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="spinner-border spinner-border-sm d-none me-2" id="assign-submit-spinner"></span>
                            Asignar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para mover producto entre ubicaciones -->
    <div class="modal fade" id="moveProductModal" tabindex="-1" aria-labelledby="moveProductModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="moveProductForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="moveProductModalLabel">Mover Producto</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="move-item-id">
                        <input type="hidden" id="move-from-location-id">
                        
                        <div class="mb-3">
                            <label class="form-label">Producto</label>
                            <input type="text" class="form-control" id="move-product-name" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Ubicación Origen</label>
                            <input type="text" class="form-control" id="move-from-location-name" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Stock Disponible</label>
                            <input type="text" class="form-control" id="move-available-stock" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="move-quantity" class="form-label">Cantidad a Mover *</label>
                            <input type="number" class="form-control" id="move-quantity" min="1" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="move-to-location" class="form-label">Ubicación Destino *</label>
                            <select class="form-select" id="move-to-location" required>
                                <option value="">Seleccione ubicación destino</option>
                                <option value="STORAGE">🏬 ALMACENAMIENTO</option>
                            </select>
                        </div>
                        
                        <div class="alert alert-warning d-none" id="move-capacity-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <span id="move-capacity-warning-text"></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="spinner-border spinner-border-sm d-none me-2" id="move-submit-spinner"></span>
                            Mover Producto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para mover desde almacenamiento -->
    <div class="modal fade" id="moveFromStorageModal" tabindex="-1" aria-labelledby="moveFromStorageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="moveFromStorageForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Distribuir desde Almacenamiento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="storage-item-id">
                        
                        <div class="mb-3">
                            <label class="form-label">Producto</label>
                            <input type="text" class="form-control" id="storage-product-name" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Stock en Almacenamiento</label>
                            <input type="text" class="form-control" id="storage-available-stock" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="storage-quantity" class="form-label">Cantidad a Distribuir *</label>
                            <input type="number" class="form-control" id="storage-quantity" min="1" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="storage-to-location" class="form-label">Ubicación Destino *</label>
                            <select class="form-select" id="storage-to-location" required>
                                <option value="">Seleccione ubicación destino</option>
                            </select>
                        </div>
                        
                        <div class="alert alert-warning d-none" id="storage-capacity-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <span id="storage-capacity-warning-text"></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <span class="spinner-border spinner-border-sm d-none me-2" id="storage-submit-spinner"></span>
                            Distribuir
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para editar capacidad de producto -->
    <div class="modal fade" id="editItemCapacityModal" tabindex="-1" aria-labelledby="editItemCapacityModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editItemCapacityForm">
                    <input type="hidden" id="edit-item-capacity-location-id">
                    <input type="hidden" id="edit-item-capacity-item-id">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Capacidad de Producto</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Producto</label>
                            <input type="text" class="form-control" id="edit-item-capacity-product-name" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ubicación</label>
                            <input type="text" class="form-control" id="edit-item-capacity-location-name" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Stock Actual</label>
                            <input type="text" class="form-control" id="edit-item-capacity-current-stock" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="edit-item-capacity-value" class="form-label">Nueva Capacidad Máxima *</label>
                            <input type="number" class="form-control" id="edit-item-capacity-value" min="1" required>
                        </div>
                        <div class="alert alert-warning d-none" id="edit-item-capacity-warning">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <span>La capacidad no puede ser menor al stock actual.</span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="spinner-border spinner-border-sm d-none me-2" id="edit-item-submit-spinner"></span>
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- Modal para Ajuste de Almacenamiento (Solo Superadmin) -->
    <div class="modal fade" id="adjustStorageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i> Ajustar Stock en Almacenamiento
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="adjustStorageForm">
                    <div class="modal-body">
                        <input type="hidden" id="adjust-item-id">
                        <input type="hidden" id="adjust-location-id">
                        <input type="hidden" id="adjust-warehouse">
                        <input type="hidden" id="adjust-customer">
                        
                        <div class="mb-3">
                            <label class="form-label">Producto</label>
                            <input type="text" class="form-control" id="adjust-item-name" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bodega / Cliente</label>
                            <input type="text" class="form-control" id="adjust-context" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Stock Físico Actual</label>
                            <input type="text" class="form-control" id="adjust-current-stock" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="adjust-new-quantity" class="form-label">Nuevo Stock Total *</label>
                            <input type="number" class="form-control" id="adjust-new-quantity" min="0" required>
                            <div class="form-text text-danger">
                                <i class="fas fa-exclamation-triangle me-1"></i> 
                                ¡Atención! Esto creará un registro de ajuste para igualar el total indicado.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">
                            <span class="spinner-border spinner-border-sm d-none me-2" id="adjust-submit-spinner"></span>
                            Confirmar Ajuste
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Verificar Bootstrap
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap no está cargado');
        showError('Error: Bootstrap no está disponible');
        return;
    }

    // Variables globales
    window.isSuperAdmin = {{ auth()->user()->isSuperAdmin() ? 'true' : 'false' }};
    let locationsData = [];
    let filteredLocations = [];
    let isLoading = false;
    let currentPage = 1;
    let itemsPerPage = 12;
    let currentFilters = {
        warehouse: '',
        search: '',
        capacity: ''
    };

    // Elementos del DOM
    const warehouseFilter = document.getElementById('warehouse-filter');
    const searchFilter = document.getElementById('search-filter');
    const capacityFilter = document.getElementById('capacity-filter');
    const itemsPerPageSelect = document.getElementById('items-per-page');
    const clearFiltersBtn = document.getElementById('clear-filters');
    const locationsContainer = document.getElementById('locations-container');

    // Inicializar modales
    const locationModal = new bootstrap.Modal(document.getElementById('locationModal'));
    const assignItemModal = new bootstrap.Modal(document.getElementById('assignItemModal'));
    const moveProductModal = new bootstrap.Modal(document.getElementById('moveProductModal'));
    const editItemCapacityModal = new bootstrap.Modal(document.getElementById('editItemCapacityModal'));
    const moveFromStorageModal = new bootstrap.Modal(document.getElementById('moveFromStorageModal'));
    const adjustStorageModal = window.isSuperAdmin ? new bootstrap.Modal(document.getElementById('adjustStorageModal')) : null;

    // Token CSRF
    function getCSRFToken() {
        const token = document.querySelector('meta[name="csrf-token"]');
        return token ? token.getAttribute('content') : '';
    }


    // ===== AJUSTE DE STOCK EN ALMACENAMIENTO (SUPERADMIN) =====
    function showAdjustStorageModal(dataset) {
        if (!window.isSuperAdmin || !adjustStorageModal) return;
        document.getElementById('adjust-item-id').value = dataset.itemId;
        document.getElementById('adjust-item-name').value = dataset.itemName;
        document.getElementById('adjust-current-stock').value = dataset.currentStock;
        document.getElementById('adjust-location-id').value = dataset.locationId;
        document.getElementById('adjust-warehouse').value = dataset.warehouse;
        document.getElementById('adjust-customer').value = dataset.customer;
        document.getElementById('adjust-context').value = (dataset.warehouse || '') + ' / ' + (dataset.customer || '');
        document.getElementById('adjust-new-quantity').value = dataset.currentStock;
        adjustStorageModal.show();
    }

    const adjustStorageForm = document.getElementById('adjustStorageForm');
    if (adjustStorageForm) {
        adjustStorageForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const spinner = document.getElementById('adjust-submit-spinner');
            spinner.classList.remove('d-none');
            const itemId = document.getElementById('adjust-item-id').value;
            const newQuantity = parseInt(document.getElementById('adjust-new-quantity').value);
            const warehouse = document.getElementById('adjust-warehouse').value;
            const customer = document.getElementById('adjust-customer').value;
            const currentStock = parseInt(document.getElementById('adjust-current-stock').value);
            const itemName = document.getElementById('adjust-item-name').value;

            try {
                const response = await fetch('/inventories/adjust-storage', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCSRFToken(),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ item_id: itemId, new_quantity: newQuantity, warehouse: warehouse, customer: customer })
                });
                const data = await response.json();
                if (data.success) {
                    adjustStorageModal.hide();
                    const diff = newQuantity - currentStock;
                    const sign = diff > 0 ? '+' : '';
                    Swal.fire({
                        icon: 'success',
                        title: '\u00a1Ajuste Aplicado!',
                        html: `Stock de <strong>${itemName}</strong> ajustado.<br>Diferencia: <strong>${sign}${diff}</strong> unidades.<br>Nuevo total: <strong>${newQuantity}</strong>`,
                        timer: 4000,
                        showConfirmButton: true
                    });
                    loadLocations(currentFilters.warehouse);
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Error al ajustar el stock.' });
                }
            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Error de Red', text: 'No se pudo conectar con el servidor.' });
            } finally {
                spinner.classList.add('d-none');
            }
        });
    }
    // ===== FIN AJUSTE DE STOCK =====

    // Inicializar aplicación
    init();

    function init() {
        loadLocations();
        attachGlobalEventListeners();
    }

    // Event Listeners globales
    function attachGlobalEventListeners() {
        window.addEventListener('focus', () => {
            loadLocations(currentFilters.warehouse);
        });

        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                loadLocations(currentFilters.warehouse);
            }
        });
        // Filtro por bodega
        if (warehouseFilter) {
            warehouseFilter.addEventListener('change', function() {
                currentFilters.warehouse = this.value;
                currentPage = 1;
                loadLocations(this.value);
            });
        }

        // Filtro de búsqueda con debounce
        if (searchFilter) {
            let searchTimeout;
            searchFilter.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    currentFilters.search = this.value.trim();
                    currentPage = 1;
                    applyFilters();
                }, 300);
            });
        }

        // Filtro de capacidad
        if (capacityFilter) {
            capacityFilter.addEventListener('change', function() {
                currentFilters.capacity = this.value;
                currentPage = 1;
                applyFilters();
            });
        }

        // Items per page
        if (itemsPerPageSelect) {
            itemsPerPageSelect.addEventListener('change', function() {
                itemsPerPage = this.value === 'all' ? 'all' : parseInt(this.value);
                currentPage = 1;
                renderFilteredLocations();
            });
        }

        // Limpiar filtros
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', function() {
                warehouseFilter.selectedIndex = 0;
                searchFilter.value = '';
                capacityFilter.selectedIndex = 0;
                itemsPerPageSelect.selectedIndex = 1;
                
                currentFilters = { warehouse: '', search: '', capacity: '' };
                currentPage = 1;
                itemsPerPage = 12;
                
                loadLocations();
            });
        }

        // Botón crear ubicación
        const createLocationBtn = document.getElementById('createLocationBtn');
        if (createLocationBtn) {
            createLocationBtn.addEventListener('click', function() {
                resetLocationForm();
                locationModal.show();
            });
        }

        // Formularios
        const locationForm = document.getElementById('locationForm');
        if (locationForm) {
            locationForm.addEventListener('submit', handleLocationSubmit);
        }

        const assignItemForm = document.getElementById('assignItemForm');
        if (assignItemForm) {
            assignItemForm.addEventListener('submit', handleAssignItemSubmit);
        }

        const moveProductForm = document.getElementById('moveProductForm');
        if (moveProductForm) {
            moveProductForm.addEventListener('submit', handleMoveProductSubmit);
        }

        const moveFromStorageForm = document.getElementById('moveFromStorageForm');
        if (moveFromStorageForm) {
            moveFromStorageForm.addEventListener('submit', handleMoveFromStorageSubmit);
        }

        const editItemCapacityForm = document.getElementById('editItemCapacityForm');
        if (editItemCapacityForm) {
            editItemCapacityForm.addEventListener('submit', handleEditItemCapacitySubmit);
            
            const capacityInput = document.getElementById('edit-item-capacity-value');
            if (capacityInput) {
                capacityInput.addEventListener('input', validateItemCapacity);
            }
        }

        // Validaciones para asignación
        const assignLocationSelect = document.getElementById('assign-location');
        if (assignLocationSelect) {
            assignLocationSelect.addEventListener('change', validateAssignmentCapacity);
        }

        // Validaciones para movimiento
        const moveQuantityInput = document.getElementById('move-quantity');
        if (moveQuantityInput) {
            moveQuantityInput.addEventListener('input', validateMoveCapacity);
        }

        const moveToLocationSelect = document.getElementById('move-to-location');
        if (moveToLocationSelect) {
            moveToLocationSelect.addEventListener('change', validateMoveCapacity);
        }

        const storageQuantityInput = document.getElementById('storage-quantity');
        if (storageQuantityInput) {
            storageQuantityInput.addEventListener('input', validateStorageCapacity);
        }

        const storageToLocationSelect = document.getElementById('storage-to-location');
        if (storageToLocationSelect) {
            storageToLocationSelect.addEventListener('change', validateStorageCapacity);
        }
    }

    // Función de filtrado principal
    function applyFilters() {
        filteredLocations = locationsData.filter(location => {
            // Filtro por bodega
            if (currentFilters.warehouse && location.warehouse !== currentFilters.warehouse) {
                return false;
            }
            
            // Filtro por búsqueda
            if (currentFilters.search) {
                const searchTerm = currentFilters.search.toLowerCase();
                const matchesLocation = (location.code || '').toLowerCase().includes(searchTerm) ||
                                      (location.name || '').toLowerCase().includes(searchTerm);
                const matchesItems = location.items && location.items.some(item => 
                    (item.name || '').toLowerCase().includes(searchTerm) ||
                    (item.sku || '').toLowerCase().includes(searchTerm)
                );
                if (!matchesLocation && !matchesItems) return false;
            }
            
            // Filtro por capacidad
            if (currentFilters.capacity) {
                const totalStock = location.total_stock || 0;
                const totalCapacity = location.max_capacity || location.total_capacity || 0;
                const percentage = totalCapacity > 0 ? (totalStock / totalCapacity) * 100 : 0;
                
                switch (currentFilters.capacity) {
                    case 'high': return percentage > 80;
                    case 'medium': return percentage >= 50 && percentage <= 80;
                    case 'low': return percentage < 50 && percentage > 0;
                    case 'empty': return percentage === 0;
                    default: return true;
                }
            }
            
            return true;
        });
        
        currentPage = 1;
        renderFilteredLocations();
    }

    // Función de paginación y renderizado
    function renderFilteredLocations() {
        locationsContainer.innerHTML = '';

        // Identificar ubicaciones especiales
        const specialLocations = filteredLocations.filter(l => l.code === 'ALMACENAMIENTO' || l.code === 'PENDIENTES');
        const normalLocations = filteredLocations.filter(l => l.code !== 'ALMACENAMIENTO' && l.code !== 'PENDIENTES');

        const totalNormal = normalLocations.length;

        if (totalNormal === 0 && specialLocations.length === 0) {
            showEmptyState();
            updatePaginationInfo(0, 0, 0);
            updatePaginationControls(0, 0);
            return;
        }

        // Calcular paginación para ubicaciones normales
        let startIndex, endIndex, totalPages;
        if (itemsPerPage === 'all') {
            startIndex = 0;
            endIndex = totalNormal;
            totalPages = 1;
        } else {
            startIndex = (currentPage - 1) * itemsPerPage;
            endIndex = Math.min(startIndex + itemsPerPage, totalNormal);
            totalPages = Math.ceil(totalNormal / itemsPerPage);
        }

        const pageNormalLocations = normalLocations.slice(startIndex, endIndex);

        // Renderizar ubicaciones especiales en un contenedor
        if (specialLocations.length > 0) {
            const specialContainer = document.createElement('div');
            specialContainer.className = 'special-locations-container row';
            
            const specialHeader = document.createElement('div');
            specialHeader.className = 'col-12 mb-3';
            specialHeader.innerHTML = `
                <div class="warehouse-header">
                    <h4><i class="fas fa-star me-2"></i>Ubicaciones Especiales</h4>
                    <small>Mostradas: ${specialLocations.length}</small>
                </div>
            `;
            specialContainer.appendChild(specialHeader);

            // Ordenar para que ALMACENAMIENTO esté a la izquierda y PENDIENTES a la derecha
            const sortedSpecials = specialLocations.sort((a, b) => {
                if (a.code === 'ALMACENAMIENTO') return -1;
                if (b.code === 'ALMACENAMIENTO') return 1;
                return 0;
            });

            sortedSpecials.forEach(location => {
                const cardCol = createLocationCard(location, true);
                specialContainer.appendChild(cardCol);
            });

            locationsContainer.appendChild(specialContainer);
        }

        // Renderizar ubicaciones normales agrupadas por bodega
        if (pageNormalLocations.length > 0) {
            const locationsByWarehouse = pageNormalLocations.reduce((acc, location) => {
                const warehouse = location.warehouse || 'Sin Bodega';
                if (!acc[warehouse]) {
                    acc[warehouse] = [];
                }
                acc[warehouse].push(location);
                return acc;
            }, {});

            Object.entries(locationsByWarehouse).forEach(([warehouse, warehouseLocations]) => {
                const warehouseHeader = document.createElement('div');
                warehouseHeader.className = 'col-12 mb-3';
                warehouseHeader.innerHTML = `
                    <div class="warehouse-header">
                        <h4><i class="fas fa-warehouse me-2"></i>${warehouse}</h4>
                        <small>Ubicaciones mostradas: ${warehouseLocations.length}</small>
                    </div>
                `;
                locationsContainer.appendChild(warehouseHeader);

                warehouseLocations.forEach(location => {
                    const locationCard = createLocationCard(location, false);
                    locationsContainer.appendChild(locationCard);
                });
            });
        }

        updatePaginationInfo(startIndex + 1, endIndex, totalNormal);
        updatePaginationControls(currentPage, totalPages);

        attachCardEventListeners();
    }

    // Actualizar información de paginación
    function updatePaginationInfo(start, end, total) {
        const info = document.getElementById('pagination-info');
        if (info) {
            if (total === 0) {
                info.textContent = 'No se encontraron ubicaciones normales';
            } else {
                info.textContent = `Mostrando ${start}-${end} de ${total} ubicaciones normales`;
            }
        }
    }

    // Actualizar controles de paginación
    function updatePaginationControls(current, totalPages) {
        const controls = document.getElementById('pagination-controls');
        if (!controls) return;
        
        if (totalPages <= 1) {
            controls.innerHTML = '';
            return;
        }
        
        let html = '';
        
        // Botón anterior
        html += `<li class="page-item ${current === 1 ? 'disabled' : ''}">
            <button class="page-link" data-page="${current - 1}" ${current === 1 ? 'disabled' : ''} title="Página anterior">
                <i class="fas fa-chevron-left"></i>
            </button>
        </li>`;
        
        // Páginas
        const maxVisible = 5;
        let startPage = Math.max(1, current - Math.floor(maxVisible / 2));
        let endPage = Math.min(totalPages, startPage + maxVisible - 1);
        
        if (endPage - startPage + 1 < maxVisible) {
            startPage = Math.max(1, endPage - maxVisible + 1);
        }
        
        if (startPage > 1) {
            html += `<li class="page-item"><button class="page-link" data-page="1">1</button></li>`;
            if (startPage > 2) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            html += `<li class="page-item ${i === current ? 'active' : ''}">
                <button class="page-link" data-page="${i}">${i}</button>
            </li>`;
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
            html += `<li class="page-item"><button class="page-link" data-page="${totalPages}">${totalPages}</button></li>`;
        }
        
        // Botón siguiente
        html += `<li class="page-item ${current === totalPages ? 'disabled' : ''}">
            <button class="page-link" data-page="${current + 1}" ${current === totalPages ? 'disabled' : ''} title="Página siguiente">
                <i class="fas fa-chevron-right"></i>
            </button>
        </li>`;
        
        controls.innerHTML = html;
        
        // Event listeners para paginación
        controls.querySelectorAll('button[data-page]').forEach(btn => {
            btn.addEventListener('click', function() {
                const page = parseInt(this.dataset.page);
                if (page > 0 && page <= totalPages && page !== current) {
                    currentPage = page;
                    renderFilteredLocations();
                }
            });
        });
    }

    // Cargar ubicaciones
    async function loadLocations(warehouse = '') {
        if (isLoading) return;
        
        isLoading = true;
        showLoading();

        try {
            const response = await fetch(`/locations/data?warehouse=${encodeURIComponent(warehouse)}`, {
                cache: 'no-store'
            });
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Error al cargar datos');
            }

            locationsData = data.data || [];
            currentFilters.warehouse = warehouse;
            applyFilters();
        } catch (error) {
            console.error('Error cargando ubicaciones:', error);
            showError('Error al cargar las ubicaciones: ' + error.message);
            showEmptyState();
        } finally {
            isLoading = false;
        }
    }

    // Mostrar loading
    function showLoading() {
        if (locationsContainer) {
            locationsContainer.innerHTML = `
                <div class="col-12 text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando ubicaciones...</span>
                    </div>
                    <p class="mt-3 text-muted">Cargando ubicaciones...</p>
                </div>
            `;
        }
        updatePaginationInfo(0, 0, 0);
        updatePaginationControls(0, 0);
    }

    // Mostrar estado vacío
    function showEmptyState() {
        if (locationsContainer) {
            const hasFilters = currentFilters.search || currentFilters.capacity || currentFilters.warehouse;
            const message = hasFilters ? 
                'No se encontraron ubicaciones con los filtros aplicados' : 
                'No se encontraron ubicaciones';
            const suggestion = hasFilters ? 
                'Intente modificar los filtros de búsqueda.' : 
                'Comience creando una nueva ubicación.';
                
            locationsContainer.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle fa-2x mb-3"></i>
                        <h5>${message}</h5>
                        <p class="mb-0">${suggestion}</p>
                    </div>
                </div>
            `;
        }
    }

   function createLocationCard(location, isSpecial = false) {
    const col = document.createElement('div');
    col.className = isSpecial ? 'col-md-6 mb-4' : 'col-lg-4 col-md-6 mb-4';

    const items = location.items || [];
    const isPending = location.is_pending || location.code === 'PENDIENTES';
    const isStorage = location.is_storage || location.code === 'ALMACENAMIENTO';
    
    // Calcular datos de capacidad
    const totalStock = location.total_stock || 0;
    const totalCapacity = location.max_capacity || location.total_capacity || 0;
    const percentage = totalCapacity > 0 ? Math.min(100, (totalStock / totalCapacity) * 100) : 0;
    
    // Clase de alerta
    const alertClass = getAlertClass(percentage, isPending);
    const progressBarColor = getProgressBarColor(percentage);

    // HTML de productos (modificado para ALMACENAMIENTO)
    let productsHtml;
    if (isStorage && items.length > 0) {
        // Para ALMACENAMIENTO, crear el contenedor con filtros
        productsHtml = `
            <div class="storage-filters-container">
                <!-- Búsqueda -->
                <div class="storage-search-box">
                    <i class="fas fa-search storage-search-icon"></i>
                    <input 
                        type="text" 
                        class="form-control storage-search-input" 
                        placeholder="Buscar en almacenamiento..."
                        data-location-id="${location.location_id}">
                </div>
                
                <!-- Filtro alfabético -->
                <div class="storage-alphabet-filter" data-location-id="${location.location_id}">
                    <!-- Se genera dinámicamente -->
                </div>
                
                <!-- Indicador de filtro activo -->
                <div class="storage-active-filter" data-location-id="${location.location_id}">
                    <div>
                        <span class="storage-filter-tag"></span>
                    </div>
                    <button class="btn btn-sm btn-outline-info storage-clear-filter" data-location-id="${location.location_id}">
                        <i class="fas fa-times"></i> Limpiar
                    </button>
                </div>
                
                <!-- Contenedor de productos -->
                <div class="storage-products-list" data-location-id="${location.location_id}">
                    ${items.map(item => createProductItemHtml(item, location, isPending)).join('')}
                </div>
            </div>
        `;
    } else if (items.length > 0) {
        productsHtml = items.map(item => createProductItemHtml(item, location, isPending)).join('');
    } else {
        productsHtml = `<div class="empty-location">
            <i class="fas fa-box-open fa-2x mb-2"></i>
            <p class="mb-0">Sin productos asignados</p>
        </div>`;
    }

    // Barra de progreso (solo para ubicaciones normales)
    const progressBarHtml = !isPending && !isStorage ? `
        <div class="progress mb-3" title="Ocupación: ${percentage.toFixed(1)}%">
            <div class="progress-bar bg-${progressBarColor}" 
                 role="progressbar" 
                 style="width: ${percentage}%"
                 aria-valuenow="${totalStock}" 
                 aria-valuemin="0" 
                 aria-valuemax="${totalCapacity}">
            </div>
        </div>
    ` : '';

    // Información de capacidad
    let capacityInfoHtml;
    if (isPending) {
        capacityInfoHtml = `
            <div class="location-capacity-info">
                <i class="fas fa-exclamation-triangle location-capacity-icon text-warning"></i>
                <span><strong>${location.total_retentions || 0}</strong> productos retenidos</span>
            </div>
        `;
    } else if (isStorage) {
        capacityInfoHtml = `
            <div class="location-capacity-info">
                <i class="fas fa-warehouse location-capacity-icon text-info"></i>
                <span><strong>${totalStock}</strong> unidades en almacenamiento</span>
                <small class="ms-2 text-muted">(<span class="storage-visible-count">${items.length}</span> productos visibles)</small>
            </div>
        `;
    } else {
        capacityInfoHtml = `
            <div class="location-capacity-info">
                <i class="fas fa-chart-bar location-capacity-icon"></i>
                <span><strong>${totalStock}</strong> / <strong>${totalCapacity}</strong> unidades</span>
                <small class="ms-2 text-muted">(${percentage.toFixed(1)}%)</small>
            </div>
        `;
    }

    // Botones de acción
    const actionButtonsHtml = `
        <div class="location-actions">
            <button class="btn btn-sm btn-outline-primary edit-location" 
                    data-location-id="${location.location_id}" 
                    title="Editar ubicación">
                <i class="fas fa-edit"></i> Editar
            </button>
            ${location.code !== 'PENDIENTES' && location.code !== 'ALMACENAMIENTO' ? `
            <button class="btn btn-sm btn-outline-danger delete-location" 
                    data-location-id="${location.location_id}" 
                    title="Eliminar ubicación">
                <i class="fas fa-trash"></i> Eliminar
            </button>
            ` : ''}
        </div>
    `;

    col.innerHTML = `
        <div class="card location-card ${isPending ? 'pending' : isStorage ? 'storage' : 'normal'} ${alertClass}">
            <div class="card-header location-header bg-${isPending ? 'warning' : isStorage ? 'info' : getHeaderColor(percentage)}">
                <div>
                    <strong>${location.code || 'N/A'}</strong>
                    <div class="small">${location.name || 'Sin nombre'}</div>
                </div>
                ${!isPending && !isStorage ? `<span class="badge bg-light text-dark">${percentage.toFixed(0)}%</span>` : ''}
            </div>
            <div class="card-body location-body">
                ${location.description ? `<p class="card-text text-muted mb-3">${location.description}</p>` : ''}
                
                ${progressBarHtml}
                ${capacityInfoHtml}
                
                <h6 class="mt-3 mb-2">
                    <i class="fas fa-boxes me-2"></i>
                    ${isPending ? 'Productos Retenidos:' : isStorage ? 'Productos en Almacén:' : 'Productos:'}
                </h6>
                <div class="products-list" style="max-height: 300px; overflow-y: auto;">
                    ${productsHtml}
                </div>
                
                ${actionButtonsHtml}
            </div>
        </div>
    `;

    // Si es ALMACENAMIENTO, inicializar el filtro alfabético
    if (isStorage && items.length > 0) {
        setTimeout(() => {
            initStorageAlphabetFilter(location.location_id, items);
        }, 100);
    }

    return col;
}

// NUEVA FUNCIÓN: Inicializar filtro alfabético para ALMACENAMIENTO
function initStorageAlphabetFilter(locationId, items) {
    const alphabetContainer = document.querySelector(`.storage-alphabet-filter[data-location-id="${locationId}"]`);
    if (!alphabetContainer) return;

    // Contar productos por letra
    const letterCounts = {};
    const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ#'.split('');
    
    items.forEach(item => {
        const firstChar = (item.name || '').charAt(0).toUpperCase();
        const letter = /[A-Z]/.test(firstChar) ? firstChar : '#';
        letterCounts[letter] = (letterCounts[letter] || 0) + 1;
    });

    // Generar botones alfabéticos
    alphabetContainer.innerHTML = alphabet.map(letter => {
        const count = letterCounts[letter] || 0;
        const hasProducts = count > 0;
        
        return `
            <button 
                class="storage-alphabet-btn ${!hasProducts ? 'disabled' : ''}" 
                data-letter="${letter}"
                data-location-id="${locationId}"
                title="${letter === '#' ? 'Números y símbolos' : 'Letra ' + letter} - ${count} productos"
                ${!hasProducts ? 'disabled' : ''}>
                ${letter}
                ${hasProducts ? `<span class="storage-letter-count">${count}</span>` : ''}
            </button>
        `;
    }).join('');

    // Event listeners para filtro alfabético
    alphabetContainer.querySelectorAll('.storage-alphabet-btn:not(.disabled)').forEach(btn => {
        btn.addEventListener('click', function() {
            const letter = this.dataset.letter;
            const locId = this.dataset.locationId;
            
            // Toggle activo
            const isActive = this.classList.contains('active');
            
            // Limpiar otros botones
            alphabetContainer.querySelectorAll('.storage-alphabet-btn').forEach(b => {
                b.classList.remove('active');
            });
            
            if (!isActive) {
                this.classList.add('active');
                filterStorageByLetter(locId, letter, items);
            } else {
                filterStorageByLetter(locId, null, items);
            }
        });
    });

    // Event listener para búsqueda
    const searchInput = document.querySelector(`.storage-search-input[data-location-id="${locationId}"]`);
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const searchTerm = this.value.toLowerCase().trim();
                filterStorageBySearch(locationId, searchTerm, items);
            }, 300);
        });
    }

    // Event listener para limpiar filtros
    const clearBtn = document.querySelector(`.storage-clear-filter[data-location-id="${locationId}"]`);
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            clearStorageFilters(locationId, items);
        });
    }
}

// NUEVA FUNCIÓN: Filtrar por letra
function filterStorageByLetter(locationId, letter, allItems) {
    const productsContainer = document.querySelector(`.storage-products-list[data-location-id="${locationId}"]`);
    const activeFilterDiv = document.querySelector(`.storage-active-filter[data-location-id="${locationId}"]`);
    const filterTag = activeFilterDiv?.querySelector('.storage-filter-tag');
    const visibleCountSpan = document.querySelector(`.storage-visible-count`);
    
    if (!productsContainer) return;

    let filteredItems = allItems;
    
    if (letter) {
        filteredItems = allItems.filter(item => {
            const firstChar = (item.name || '').charAt(0).toUpperCase();
            const itemLetter = /[A-Z]/.test(firstChar) ? firstChar : '#';
            return itemLetter === letter;
        });
        
        // Mostrar indicador de filtro
        if (activeFilterDiv && filterTag) {
            filterTag.textContent = `Letra: ${letter} (${filteredItems.length} productos)`;
            activeFilterDiv.classList.add('show');
        }
    } else {
        // Ocultar indicador
        if (activeFilterDiv) {
            activeFilterDiv.classList.remove('show');
        }
    }

    // Actualizar contador visible
    if (visibleCountSpan) {
        visibleCountSpan.textContent = filteredItems.length;
    }

    renderStorageProducts(productsContainer, filteredItems, locationId, letter);
}

// NUEVA FUNCIÓN: Filtrar por búsqueda
function filterStorageBySearch(locationId, searchTerm, allItems) {
    const productsContainer = document.querySelector(`.storage-products-list[data-location-id="${locationId}"]`);
    const activeFilterDiv = document.querySelector(`.storage-active-filter[data-location-id="${locationId}"]`);
    const filterTag = activeFilterDiv?.querySelector('.storage-filter-tag');
    const alphabetContainer = document.querySelector(`.storage-alphabet-filter[data-location-id="${locationId}"]`);
    const visibleCountSpan = document.querySelector(`.storage-visible-count`);
    
    if (!productsContainer) return;

    let filteredItems = allItems;
    
    if (searchTerm) {
        filteredItems = allItems.filter(item => {
            const matchName = (item.name || '').toLowerCase().includes(searchTerm);
            const matchSku = (item.sku || '').toLowerCase().includes(searchTerm);
            return matchName || matchSku;
        });
        
        // Limpiar filtro alfabético
        if (alphabetContainer) {
            alphabetContainer.querySelectorAll('.storage-alphabet-btn').forEach(btn => {
                btn.classList.remove('active');
            });
        }
        
        // Mostrar indicador de filtro
        if (activeFilterDiv && filterTag) {
            filterTag.textContent = `Búsqueda: "${searchTerm}" (${filteredItems.length} resultados)`;
            activeFilterDiv.classList.add('show');
        }
    } else {
        // Ocultar indicador
        if (activeFilterDiv) {
            activeFilterDiv.classList.remove('show');
        }
    }

    // Actualizar contador visible
    if (visibleCountSpan) {
        visibleCountSpan.textContent = filteredItems.length;
    }

    renderStorageProducts(productsContainer, filteredItems, locationId, null);
}

// NUEVA FUNCIÓN: Limpiar filtros
function clearStorageFilters(locationId, allItems) {
    const searchInput = document.querySelector(`.storage-search-input[data-location-id="${locationId}"]`);
    const alphabetContainer = document.querySelector(`.storage-alphabet-filter[data-location-id="${locationId}"]`);
    const activeFilterDiv = document.querySelector(`.storage-active-filter[data-location-id="${locationId}"]`);
    const productsContainer = document.querySelector(`.storage-products-list[data-location-id="${locationId}"]`);
    const visibleCountSpan = document.querySelector(`.storage-visible-count`);
    
    // Limpiar búsqueda
    if (searchInput) {
        searchInput.value = '';
    }
    
    // Limpiar filtro alfabético
    if (alphabetContainer) {
        alphabetContainer.querySelectorAll('.storage-alphabet-btn').forEach(btn => {
            btn.classList.remove('active');
        });
    }
    
    // Ocultar indicador
    if (activeFilterDiv) {
        activeFilterDiv.classList.remove('show');
    }

    // Actualizar contador visible
    if (visibleCountSpan) {
        visibleCountSpan.textContent = allItems.length;
    }
    
    // Renderizar todos los productos
    if (productsContainer) {
        renderStorageProducts(productsContainer, allItems, locationId, null);
    }
}

// NUEVA FUNCIÓN: Renderizar productos de almacenamiento
function renderStorageProducts(container, items, locationId, groupByLetter) {
    if (items.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4 text-muted">
                <i class="fas fa-search fa-2x mb-2"></i>
                <p class="mb-0">No se encontraron productos</p>
            </div>
        `;
        return;
    }

    const location = locationsData.find(l => l.location_id == locationId);
    
    if (groupByLetter) {
        // Agrupar por letra seleccionada
        container.innerHTML = `
            <div class="storage-letter-group">
                <div class="storage-letter-header">
                    <span class="storage-letter-title">${groupByLetter}</span>
                    <span class="storage-letter-count-badge">${items.length} productos</span>
                </div>
                ${items.map(item => createProductItemHtml(item, location, false)).join('')}
            </div>
        `;
    } else {
        // Mostrar todos sin agrupar
        container.innerHTML = items.map(item => createProductItemHtml(item, location, false)).join('');
    }
    
    // Re-adjuntar event listeners para las acciones de productos
    attachCardEventListeners();
}

    // Crear HTML de producto
    function createProductItemHtml(item, location, isPending) {
        const currentStock = item.available_stock || 0; // Ya tiene reservas descontadas
        const maxCapacity = item.max_capacity || 0;
        const stockPercentage = maxCapacity > 0 ? (currentStock / maxCapacity) * 100 : 0;
        const isStorage = location.is_storage || location.code === 'ALMACENAMIENTO';
        
        // Calcular reservas si existen (para mostrar al usuario)
        const quantityReserved = item.quantity_reserved || 0;
        const totalRetention = item.total_retention || 0;
        
        // Detectar si el texto del stock es muy largo
        const stockText = isStorage ? `${currentStock}` : `${currentStock} / ${maxCapacity}`;
        const isLongText = stockText.length > 8;
        
        // Crear tooltip explicativo
        let stockTooltip = `Stock disponible: ${currentStock}`;
        if (quantityReserved > 0) {
            stockTooltip += `\nReservado para picking: ${quantityReserved}`;
            stockTooltip += `\nTotal físico: ${currentStock + quantityReserved}`;
        }
        if (!isStorage) {
            stockTooltip += `\nCapacidad máxima: ${maxCapacity}`;
        }
        
        return `
            <div class="product-item" 
                 data-item-id="${item.item_id}" 
                 data-location-id="${location.location_id}"
                 data-stock="${currentStock}"
                 data-max-capacity="${maxCapacity}">
                <div class="product-info">
                    <img src="${item.image_url || '{{ asset('img/no-image.png') }}'}"
                         alt="${item.name || 'Sin nombre'}" 
                         class="product-image"
                         onerror="this.src='{{ asset('img/no-image.png') }}'"
                         style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                    <div class="product-details">
                        <div class="product-name" style="font-weight: 500; font-size: 14px;">${item.name || 'Sin nombre'}</div>
                        <div class="product-sku" style="color: #6c757d; font-size: 12px;">${item.sku || 'N/A'}</div>
                        ${!isPending ? `
                        <div class="mt-1">
                            <span class="stock-indicator ${isStorage ? 'stock-storage' : getStockIndicatorClass(stockPercentage)} ${isLongText ? 'long-text' : ''}" 
                                  title="${stockTooltip}">
                                ${stockText}${isStorage ? ' unid.' : ''}
                            </span>
                            ${quantityReserved > 0 ? `
                            <span class="badge bg-warning text-dark ms-1" 
                                  style="font-size: 10px; padding: 2px 5px;" 
                                  title="Unidades reservadas para picking (no disponibles para mover)">
                                <i class="fas fa-lock"></i> ${quantityReserved}
                            </span>
                            ` : ''}
                            ${totalRetention > 0 ? `
                            <span class="badge bg-danger text-white ms-1" 
                                  style="font-size: 10px; padding: 2px 5px;" 
                                  title="Unidades en retención">
                                <i class="fas fa-exclamation-triangle"></i> ${totalRetention}
                            </span>
                            ` : ''}
                        </div>
                        ` : `
                        <div class="mt-1">
                            <span class="retention-badge" style="background: #fff3cd; color: #856404; padding: 2px 6px; border-radius: 8px; font-size: 11px;">
                                Retención: ${item.total_retention || 0}
                            </span>
                            ${item.retention_substatus ? `
                            <span class="pending-badge" style="background: #f8d7da; color: #721c24; padding: 2px 6px; border-radius: 8px; font-size: 11px; margin-left: 4px;">
                                ${item.retention_substatus}
                            </span>
                            ` : ''}
                        </div>
                        `}
                    </div>
                </div>
                <div class="product-actions">
                    ${!isPending && currentStock > 0 && !isStorage ? `
                    <button class="btn btn-sm btn-outline-primary move-product" 
                            data-item-id="${item.item_id}" 
                            data-location-id="${location.location_id}"
                            title="Mover producto"
                            style="padding: 4px 8px;">
                        <i class="fas fa-arrows-alt"></i>
                    </button>
                    ` : ''}
                    ${isStorage && currentStock > 0 ? `
                    <button class="btn btn-sm btn-outline-success distribute-product" 
                            data-item-id="${item.item_id}" 
                            data-stock="${currentStock}"
                            title="Distribuir desde almacenamiento"
                            style="padding: 4px 8px;">
                        <i class="fas fa-share"></i>
                    </button>
                    ` : ''}
                    ${isStorage && window.isSuperAdmin ? `
                    <button class="btn btn-sm btn-outline-warning adjust-storage-stock" 
                            data-item-id="${item.item_id}" 
                            data-item-name="${item.name || 'Sin nombre'}"
                            data-current-stock="${currentStock}"
                            data-location-id="${location.location_id}"
                            data-warehouse="${location.warehouse}"
                            data-customer="${location.customer}"
                            title="Ajustar stock (Solo Superadmin)"
                            style="padding: 4px 8px;">
                        <i class="fas fa-edit"></i>
                    </button>
                    ` : ''}
                    ${!isPending && !isStorage ? `
                    <span class="capacity-edit" 
                          data-item-id="${item.item_id}" 
                          data-location-id="${location.location_id}"
                          title="Editar capacidad máxima"
                          style="cursor: pointer; padding: 4px 6px; border-radius: 4px; background: #e9ecef; font-size: 11px; color: #495057;">
                        <i class="fas fa-expand-arrows-alt"></i> ${maxCapacity}
                    </span>
                    ${currentStock === 0 ? `
                    <button class="btn btn-sm btn-outline-danger remove-product" 
                            data-item-id="${item.item_id}" 
                            data-location-id="${location.location_id}"
                            title="Remover producto de ubicación"
                            style="padding: 4px 8px;">
                        <i class="fas fa-trash"></i>
                    </button>
                    ` : `
                    <span class="text-muted" title="No se puede remover con stock existente" style="padding: 4px 8px; opacity: 0.5;">
                        <i class="fas fa-trash"></i>
                    </span>
                    `}
                    ` : ''}
                </div>
            </div>
        `;
    }

    // Asignar eventos a las tarjetas
    function attachCardEventListeners() {
        // Botones editar ubicación
        document.querySelectorAll('.edit-location').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const locationId = this.dataset.locationId;
                editLocation(locationId);
            });
        });

        // Botones remover producto
        document.querySelectorAll('.remove-product').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const itemId = this.dataset.itemId;
                const locationId = this.dataset.locationId;
                removeProduct(itemId, locationId);
            });
        });

        // Botones eliminar ubicación
        document.querySelectorAll('.delete-location').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const locationId = this.dataset.locationId;
                deleteLocation(locationId);
            });
        });

        // Botones mover producto
        document.querySelectorAll('.move-product').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const itemId = this.dataset.itemId;
                const locationId = this.dataset.locationId;
                showMoveProductModal(itemId, locationId);
            });
        });

        // Botones distribuir desde almacenamiento
        document.querySelectorAll('.distribute-product').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const itemId = this.dataset.itemId;
                const stock = parseInt(this.dataset.stock);
                showMoveFromStorageModal(itemId, stock);
            });
        });
        // Botones ajustar stock almacenamiento (Solo Superadmin)
        document.querySelectorAll('.adjust-storage-stock').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const dataset = this.dataset;
                showAdjustStorageModal(dataset);
            });
        });

        // Enlaces editar capacidad
        document.querySelectorAll('.capacity-edit').forEach(link => {
            link.addEventListener('click', function(e) {
                e.stopPropagation();
                const itemId = this.dataset.itemId;
                const locationId = this.dataset.locationId;
                editItemCapacity(itemId, locationId);
            });
        });
    }

    // Funciones auxiliares de colores y clases
    function getAlertClass(percentage, isPending) {
        if (isPending) return '';
        if (percentage >= 95) return 'alert-danger';
        if (percentage >= 80) return 'alert-warning';
        return '';
    }

    function getProgressBarColor(percentage) {
        if (percentage >= 95) return 'danger';
        if (percentage >= 80) return 'warning';
        if (percentage >= 60) return 'info';
        return 'success';
    }

    function getHeaderColor(percentage) {
        if (percentage >= 95) return 'danger';
        if (percentage >= 80) return 'warning';
        return 'primary';
    }

    function getStockIndicatorClass(percentage) {
        if (percentage >= 95) return 'stock-low';
        if (percentage >= 80) return 'stock-medium';
        return 'stock-high';
    }

    // Handlers de formularios
    async function handleLocationSubmit(e) {
        e.preventDefault();
        
        const submitBtn = e.target.querySelector('button[type="submit"]');
        const spinner = document.getElementById('location-submit-spinner');
        
        setLoading(submitBtn, spinner, true);

        try {
            const locationId = document.getElementById('location-id').value;
            const url = locationId ? `/locations/${locationId}` : '/locations';
            const method = locationId ? 'PUT' : 'POST';

            const formData = {
                code: document.getElementById('location-code').value.trim(),
                name: document.getElementById('location-name').value.trim(),
                warehouse: document.getElementById('location-warehouse').value,
                description: document.getElementById('location-description').value.trim(),
                max_capacity: parseInt(document.getElementById('location-max-capacity').value),
                customer: '{{ session("selected_customer") ?? "SKYONE" }}'
            };

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCSRFToken(),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || data.message || 'Error al guardar ubicación');
            }

            showSuccess(data.success || 'Ubicación guardada correctamente');
            locationModal.hide();
            loadLocations(currentFilters.warehouse);
        } catch (error) {
            console.error('Error guardando ubicación:', error);
            showError(error.message);
        } finally {
            setLoading(submitBtn, spinner, false);
        }
    }

    async function handleAssignItemSubmit(e) {
        e.preventDefault();
        
        const submitBtn = e.target.querySelector('button[type="submit"]');
        const spinner = document.getElementById('assign-submit-spinner');
        
        setLoading(submitBtn, spinner, true);

        try {
            const formData = {
                item_id: document.getElementById('assign-item').value,
                location_id: document.getElementById('assign-location').value,
                max_capacity: parseInt(document.getElementById('assign-max-capacity').value)
            };

            if (!formData.item_id || !formData.location_id) {
                throw new Error('Debe seleccionar un producto y una ubicación');
            }

            const response = await fetch('/locations/assign-item', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCSRFToken(),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || data.message || 'Error al asignar producto');
            }

            showSuccess(data.success || 'Producto asignado correctamente');
            assignItemModal.hide();
            document.getElementById('assignItemForm').reset();
            hideWarning('capacity-warning');
            loadLocations(currentFilters.warehouse);
        } catch (error) {
            console.error('Error asignando producto:', error);
            showError(error.message);
        } finally {
            setLoading(submitBtn, spinner, false);
        }
    }

    async function handleMoveProductSubmit(e) {
        e.preventDefault();
        
        const submitBtn = e.target.querySelector('button[type="submit"]');
        const spinner = document.getElementById('move-submit-spinner');
        
        const itemId = document.getElementById('move-item-id').value;
        const fromLocationId = document.getElementById('move-from-location-id').value;
        const toLocationValue = document.getElementById('move-to-location').value;
        const quantity = parseInt(document.getElementById('move-quantity').value);

        if (!toLocationValue || !quantity) {
            showError('Debe seleccionar una ubicación destino y cantidad válida');
            return;
        }

        // Determinar el tipo de movimiento
        let endpoint, formData;
        
        if (toLocationValue === 'STORAGE') {
            // Mover a almacenamiento
            endpoint = '/locations/move-to-storage';
            formData = {
                item_id: itemId,
                from_location_id: fromLocationId,
                quantity: quantity
            };
        } else {
            // Mover entre ubicaciones normales
            endpoint = '/locations/move-item';
            formData = {
                item_id: itemId,
                from_location_id: fromLocationId,
                to_location_id: toLocationValue,
                quantity: quantity
            };
        }

        const confirmed = await showConfirmation(
            '¿Confirmar movimiento?',
            `Se moverán ${quantity} unidades del producto a la nueva ubicación`,
            'warning'
        );

        if (!confirmed) return;

        setLoading(submitBtn, spinner, true);

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCSRFToken(),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || data.message || 'Error al mover producto');
            }

            showSuccess(data.success || 'Producto movido correctamente');
            moveProductModal.hide();
            loadLocations(currentFilters.warehouse);
        } catch (error) {
            console.error('Error moviendo producto:', error);
            showError(error.message);
        } finally {
            setLoading(submitBtn, spinner, false);
        }
    }

    async function handleMoveFromStorageSubmit(e) {
        e.preventDefault();
        
        const submitBtn = e.target.querySelector('button[type="submit"]');
        const spinner = document.getElementById('storage-submit-spinner');
        
        const itemId = document.getElementById('storage-item-id').value;
        const toLocationId = document.getElementById('storage-to-location').value;
        const quantity = parseInt(document.getElementById('storage-quantity').value);

        if (!toLocationId || !quantity) {
            showError('Debe seleccionar una ubicación destino y cantidad válida');
            return;
        }

        const confirmed = await showConfirmation(
            '¿Confirmar distribución?',
            `Se distribuirán ${quantity} unidades desde almacenamiento`,
            'info'
        );

        if (!confirmed) return;

        setLoading(submitBtn, spinner, true);

        try {
            const response = await fetch('/locations/move-from-storage', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCSRFToken(),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    item_id: itemId,
                    to_location_id: toLocationId,
                    quantity: quantity
                })
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || data.message || 'Error al distribuir producto');
            }

            showSuccess(data.success || 'Producto distribuido correctamente');
            moveFromStorageModal.hide();
            loadLocations(currentFilters.warehouse);
        } catch (error) {
            console.error('Error distribuyendo producto:', error);
            showError(error.message);
        } finally {
            setLoading(submitBtn, spinner, false);
        }
    }

    async function handleEditItemCapacitySubmit(e) {
        e.preventDefault();
        
        const submitBtn = e.target.querySelector('button[type="submit"]');
        const spinner = document.getElementById('edit-item-submit-spinner');
        
        setLoading(submitBtn, spinner, true);

        try {
            const locationId = document.getElementById('edit-item-capacity-location-id').value;
            const itemId = document.getElementById('edit-item-capacity-item-id').value;
            const maxCapacity = parseInt(document.getElementById('edit-item-capacity-value').value);
            const currentStock = parseInt(document.getElementById('edit-item-capacity-current-stock').value);

            if (maxCapacity < currentStock) {
                throw new Error(`La capacidad no puede ser menor al stock actual (${currentStock})`);
            }

            const response = await fetch(`/locations/${locationId}/update-item-capacity/${itemId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCSRFToken(),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ max_capacity: maxCapacity })
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || data.message || 'Error al actualizar capacidad');
            }

            showSuccess(data.success || 'Capacidad actualizada correctamente');
            editItemCapacityModal.hide();
            loadLocations(currentFilters.warehouse);
        } catch (error) {
            console.error('Error actualizando capacidad:', error);
            showError(error.message);
        } finally {
            setLoading(submitBtn, spinner, false);
        }
    }

    // Funciones de modal
    function resetLocationForm() {
        const form = document.getElementById('locationForm');
        form.reset();
        document.getElementById('location-id').value = '';
        document.getElementById('locationModalLabel').textContent = 'Crear Ubicación';
        document.getElementById('location-max-capacity').value = 100;
    }

    function editLocation(locationId) {
        const location = locationsData.find(l => l.location_id == locationId);
        if (!location) {
            showError('Ubicación no encontrada');
            return;
        }

        document.getElementById('location-id').value = location.location_id;
        document.getElementById('location-code').value = location.code || '';
        document.getElementById('location-name').value = location.name || '';
        document.getElementById('location-warehouse').value = location.warehouse || '';
        document.getElementById('location-description').value = location.description || '';
        document.getElementById('location-max-capacity').value = location.max_capacity || location.total_capacity || 100;
        document.getElementById('locationModalLabel').textContent = 'Editar Ubicación';
        
        locationModal.show();
    }

    async function deleteLocation(locationId) {
        const location = locationsData.find(l => l.location_id == locationId);
        if (!location) {
            showError('Ubicación no encontrada');
            return;
        }

        const confirmed = await showConfirmation(
            '¿Eliminar ubicación?',
            `Se eliminará la ubicación "${location.code} - ${location.name}". Esta acción no se puede deshacer.`,
            'warning'
        );

        if (!confirmed) return;

        try {
            const response = await fetch(`/locations/${locationId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCSRFToken(),
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || data.message || 'Error al eliminar ubicación');
            }

            showSuccess(data.success || 'Ubicación eliminada correctamente');
            loadLocations(currentFilters.warehouse);
        } catch (error) {
            console.error('Error eliminando ubicación:', error);
            showError(error.message);
        }
    }

    function showMoveProductModal(itemId, fromLocationId) {
        const fromLocation = locationsData.find(l => l.location_id == fromLocationId);
        const item = fromLocation?.items?.find(i => i.item_id == itemId);

        // Llenar datos básicos
        document.getElementById('move-item-id').value = itemId;
        document.getElementById('move-from-location-id').value = fromLocationId;
        document.getElementById('move-product-name').value = `${item.name} (${item.sku})`;
        document.getElementById('move-from-location-name').value = `${fromLocation.code} - ${fromLocation.name}`;
        document.getElementById('move-available-stock').value = item.available_stock || 0;
        document.getElementById('move-quantity').value = '';
        document.getElementById('move-quantity').max = item.available_stock || 0;

        // Llenar ubicaciones destino
        const toLocationSelect = document.getElementById('move-to-location');
        toLocationSelect.innerHTML = '<option value="">Seleccione ubicación destino</option>';
        
        // Agregar opción de almacenamiento siempre
        toLocationSelect.innerHTML += '<option value="STORAGE">🏬 ALMACENAMIENTO</option>';
        
        // Agregar ubicaciones válidas donde el producto está asignado
        locationsData.forEach(location => {
            // Excluir la ubicación actual y ubicaciones especiales
            if (location.location_id != fromLocationId && 
                location.code !== 'PENDIENTES' && 
                location.code !== 'ALMACENAMIENTO') {
                
                // Verificar si el producto está asignado a esta ubicación
                const hasProduct = location.items?.some(i => i.item_id == itemId);
                if (hasProduct) {
                    const option = document.createElement('option');
                    option.value = location.location_id;
                    option.textContent = `${location.code} - ${location.name}`;
                    toLocationSelect.appendChild(option);
                }
            }
        });
        
        hideWarning('move-capacity-warning');
        moveProductModal.show();
    }

    function showMoveFromStorageModal(itemId, storageStock) {
        const item = locationsData
            .flatMap(l => l.items || [])
            .find(i => i.item_id == itemId);

        if (!item) {
            showError('Producto no encontrado');
            return;
        }

        document.getElementById('storage-item-id').value = itemId;
        document.getElementById('storage-product-name').value = `${item.name} (${item.sku})`;
        document.getElementById('storage-available-stock').value = storageStock;
        document.getElementById('storage-quantity').value = '';
        document.getElementById('storage-quantity').max = storageStock;

        // Llenar ubicaciones destino válidas
        const toLocationSelect = document.getElementById('storage-to-location');
        toLocationSelect.innerHTML = '<option value="">Seleccione ubicación destino</option>';
        
        locationsData.forEach(location => {
            if (location.code !== 'PENDIENTES' && 
                location.code !== 'ALMACENAMIENTO') {
                
                // Verificar si el producto está asignado a esta ubicación
                const hasProduct = location.items?.some(i => i.item_id == itemId);
                if (hasProduct) {
                    const option = document.createElement('option');
                    option.value = location.location_id;
                    option.textContent = `${location.code} - ${location.name}`;
                    toLocationSelect.appendChild(option);
                }
            }
        });

        hideWarning('storage-capacity-warning');
        moveFromStorageModal.show();
    }

    async function removeProduct(itemId, locationId) {
        const location = locationsData.find(l => l.location_id == locationId);
        const item = location?.items?.find(i => i.item_id == itemId);

        if (!location || !item) {
            showError('Datos no encontrados');
            return;
        }

        const confirmed = await showConfirmation(
            '¿Remover producto?',
            `Se removerá "${item.name}" de la ubicación "${location.code}". Esta acción no se puede deshacer.`,
            'warning'
        );

        if (!confirmed) return;

        try {
            const response = await fetch('/locations/remove-item', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCSRFToken(),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    item_id: itemId,
                    location_id: locationId
                })
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || data.message || 'Error al remover producto');
            }

            showSuccess(data.success || 'Producto removido correctamente');
            loadLocations(currentFilters.warehouse);
        } catch (error) {
            console.error('Error removiendo producto:', error);
            showError(error.message);
        }
    }

    function editItemCapacity(itemId, locationId) {
        const location = locationsData.find(l => l.location_id == locationId);
        const item = location?.items?.find(i => i.item_id == itemId);

        if (!location || !item) {
            showError('Datos no encontrados');
            return;
        }

        document.getElementById('edit-item-capacity-location-id').value = locationId;
        document.getElementById('edit-item-capacity-item-id').value = itemId;
        document.getElementById('edit-item-capacity-value').value = item.max_capacity || 0;
        document.getElementById('edit-item-capacity-product-name').value = `${item.name} (${item.sku})`;
        document.getElementById('edit-item-capacity-location-name').value = `${location.code} - ${location.name}`;
        document.getElementById('edit-item-capacity-current-stock').value = item.available_stock || 0;

        hideWarning('edit-item-capacity-warning');
        editItemCapacityModal.show();
    }

    // Validaciones
    function validateAssignmentCapacity() {
        const locationSelect = document.getElementById('assign-location');
        const selectedLocationId = locationSelect.value;

        if (!selectedLocationId) {
            hideWarning('capacity-warning');
            return true;
        }

        const location = locationsData.find(l => l.location_id == selectedLocationId);
        if (!location) {
            hideWarning('capacity-warning');
            return true;
        }

        const currentStock = location.total_stock || 0;
        const maxCapacity = location.max_capacity || location.total_capacity || 0;
        const warningElement = document.getElementById('capacity-warning');
        const warningText = document.getElementById('capacity-warning-text');

        if (currentStock >= maxCapacity && maxCapacity > 0) {
            warningText.textContent = `La ubicación seleccionada está llena (${currentStock}/${maxCapacity}).`;
            showWarning('capacity-warning');
            return false;
        } else {
            hideWarning('capacity-warning');
            return true;
        }
    }

    function validateMoveCapacity() {
        const toLocationSelect = document.getElementById('move-to-location');
        const toLocationValue = toLocationSelect.value;
        const quantity = parseInt(document.getElementById('move-quantity').value) || 0;

        if (!toLocationValue || !quantity) {
            hideWarning('move-capacity-warning');
            return true;
        }

        // Si es movimiento a almacenamiento, no hay límite de capacidad
        if (toLocationValue === 'STORAGE') {
            hideWarning('move-capacity-warning');
            return true;
        }

        const itemId = document.getElementById('move-item-id').value;
        const toLocation = locationsData.find(l => l.location_id == toLocationValue);
        const toItem = toLocation?.items?.find(i => i.item_id == itemId);

        if (!toLocation || !toItem) {
            hideWarning('move-capacity-warning');
            return true;
        }

        const currentStock = toItem.available_stock || 0;
        const maxCapacity = toItem.max_capacity || 0;
        const available = Math.max(0, maxCapacity - currentStock);

        const warningElement = document.getElementById('move-capacity-warning');
        const warningText = document.getElementById('move-capacity-warning-text');

        if (quantity > available) {
            warningText.textContent = `Capacidad insuficiente. Disponible: ${available} unidades.`;
            showWarning('move-capacity-warning');
            return false;
        } else {
            hideWarning('move-capacity-warning');
            return true;
        }
    }

    function validateStorageCapacity() {
        const toLocationSelect = document.getElementById('storage-to-location');
        const toLocationId = toLocationSelect.value;
        const quantity = parseInt(document.getElementById('storage-quantity').value) || 0;

        if (!toLocationId || !quantity) {
            hideWarning('storage-capacity-warning');
            return true;
        }

        const itemId = document.getElementById('storage-item-id').value;
        const toLocation = locationsData.find(l => l.location_id == toLocationId);
        const toItem = toLocation?.items?.find(i => i.item_id == itemId);

        if (!toLocation || !toItem) {
            hideWarning('storage-capacity-warning');
            return true;
        }

        const currentStock = toItem.available_stock || 0;
        const maxCapacity = toItem.max_capacity || 0;
        const available = Math.max(0, maxCapacity - currentStock);

        const warningElement = document.getElementById('storage-capacity-warning');
        const warningText = document.getElementById('storage-capacity-warning-text');

        if (quantity > available) {
            warningText.textContent = `Capacidad insuficiente. Disponible: ${available} unidades.`;
            showWarning('storage-capacity-warning');
            return false;
        } else {
            hideWarning('storage-capacity-warning');
            return true;
        }
    }

    function validateItemCapacity() {
        const newCapacity = parseInt(document.getElementById('edit-item-capacity-value').value);
        const currentStock = parseInt(document.getElementById('edit-item-capacity-current-stock').value) || 0;

        if (newCapacity < currentStock) {
            showWarning('edit-item-capacity-warning');
            return false;
        } else {
            hideWarning('edit-item-capacity-warning');
            return true;
        }
    }

    // Funciones de utilidad
    function setLoading(button, spinner, loading) {
        if (!button) return;
        
        if (loading) {
            button.disabled = true;
            if (spinner) spinner.classList.remove('d-none');
        } else {
            button.disabled = false;
            if (spinner) spinner.classList.add('d-none');
        }
    }

    function showWarning(elementId) {
        const element = document.getElementById(elementId);
        if (element) element.classList.remove('d-none');
    }

    function hideWarning(elementId) {
        const element = document.getElementById(elementId);
        if (element) element.classList.add('d-none');
    }

    function showSuccess(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: message,
                timer: 3000,
                showConfirmButton: false
            });
        } else {
            alert('Éxito: ' + message);
        }
    }

    function showError(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message
            });
        } else {
            alert('Error: ' + message);
        }
    }

    async function showConfirmation(title, text, icon = 'warning') {
        if (typeof Swal !== 'undefined') {
            const result = await Swal.fire({
                title: title,
                text: text,
                icon: icon,
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, continuar',
                cancelButtonText: 'Cancelar'
            });
            return result.isConfirmed;
        } else {
            return confirm(`${title}\n\n${text}`);
        }
    }

    // Exponer funciones globalmente para uso en otros contextos
    window.locationFunctions = {
        showMoveProductModal,
        showMoveFromStorageModal,
        removeProduct,
        validateMoveCapacity,
        validateStorageCapacity,
        editItemCapacity
    };
});
</script>

@endsection
