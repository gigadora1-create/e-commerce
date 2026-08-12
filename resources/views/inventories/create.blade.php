@extends('layouts.app')

@section('contents')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/create.inventories.css') }}">

   

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


    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header bg-danger text-white text-center">
                        <h3 class="mb-0">Devolución o Retención de Productos</h3>
                        <small>Seleccione el tipo de trámite a realizar</small>
                    </div>
                    <div class="card-body">
                        <form id="inventoryForm" method="POST">
                            @csrf

                            <!-- Tipo de Trámite -->
                            <div class="form-group mb-4">
                                <label for="transaction_type" class="form-label">
                                    <i class="fas fa-clipboard-list me-2"></i>Tipo de Trámite <span class="text-danger">*</span>
                                </label>
                                <select name="transaction_type" id="transaction_type" class="form-select" required>
                                    <option value="">Seleccione el tipo de trámite</option>
                                    <option value="DEVOLUCION">DEVOLUCIÓN</option>
                                    <option value="RETENCION">RETENCIÓN</option>
                                </select>
                            </div>

<!-- Alerta de instrucciones -->
<div class="instructions-alert alert-info" id="instructions_devolucion" style="display: none;">
    <div class="d-flex align-items-start">
        <i class="fas fa-info-circle me-3 mt-1" style="font-size: 1.5rem;"></i>
        <div>
            <h6 class="mb-2"><strong>📦 ¿Cómo funcionan las DEVOLUCIONES?</strong></h6>
            <p class="mb-2">Las devoluciones se recuperan de las <strong>SALIDAS registradas</strong>, no de los ingresos originales.</p>
            <ul class="mb-0 ps-3">
                <li>El sistema mostrará las fechas de vencimiento que tienen salidas activas</li>
                <li>El stock disponible corresponde a la cantidad que salió y aún no ha sido devuelta</li>
                <li>Al procesar la devolución, se resta de las salidas y regresa al inventario disponible</li>
                <li><strong>Debe seleccionar la condición en que retorna el producto</strong></li>
            </ul>
        </div>
    </div>
</div>

<div class="instructions-alert alert-warning" id="instructions_retencion" style="display: none;">
    <div class="d-flex align-items-start">
        <i class="fas fa-exclamation-triangle me-3 mt-1" style="font-size: 1.5rem;"></i>
        <div>
            <h6 class="mb-2"><strong>⚠️ ¿Cómo funcionan las RETENCIONES?</strong></h6>
            <p class="mb-2">Las retenciones se aplican sobre el <strong>INVENTARIO DISPONIBLE</strong> (stock que aún no ha salido).</p>
            <ul class="mb-0 ps-3">
                <li>El sistema mostrará las fechas de vencimiento con stock disponible en bodega</li>
                <li>El stock disponible es la cantidad que ingresó menos las salidas realizadas</li>
                <li>Al retener productos, se marcan como no disponibles y se asigna un motivo</li>
                <li>Debe completar el motivo de retención y las observaciones obligatoriamente</li>
            </ul>
        </div>
    </div>
</div>

                            <!-- Producto -->
                            <div class="form-group mb-3">
                                <label for="product_search" class="form-label">
                                    <i class="fas fa-box me-2"></i>Buscar Producto <span class="text-danger">*</span>
                                </label>
                                <div class="search-container">
                                    <input type="text" id="product_search" class="form-control"
                                           placeholder="Escriba para buscar un producto..." autocomplete="off">
                                    <div class="search-results" id="product_results"></div>
                                </div>
                                <input type="hidden" name="return_item_id" id="return_item_id">
                                <div id="selected_product" style="display: none; margin-top: 10px;">
                                    <div class="selected-item-card">
                                        <div>
                                            <h6 class="mb-0" id="selected_product_name"></h6>
                                            <small class="text-muted">Producto seleccionado</small>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger" id="clear_product">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Bodega -->
                            <div class="form-group mb-3">
                                <label for="warehouse_search" class="form-label">
                                    <i class="fas fa-warehouse me-2"></i>Seleccionar Bodega <span class="text-danger">*</span>
                                </label>
                                <div class="search-container">
                                    <input type="text" id="warehouse_search" class="form-control"
                                           placeholder="Escriba para buscar una bodega..." autocomplete="off">
                                    <div class="search-results" id="warehouse_results"></div>
                                </div>
                                <input type="hidden" name="return_warehouse" id="return_warehouse">
                                <div id="selected_warehouse" style="display: none; margin-top: 10px;">
                                    <div class="selected-item-card">
                                        <div>
                                            <h6 class="mb-0" id="selected_warehouse_name"></h6>
                                            <small class="text-muted">Bodega seleccionada</small>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger" id="clear_warehouse">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Fecha de Vencimiento -->
                            <div class="form-group mb-3">
                                <label for="expiry_date_select" class="form-label">
                                    <i class="fas fa-calendar-times me-2"></i>Fecha de Vencimiento <span class="text-danger">*</span>
                                </label>
                                <select name="return_expiry_date" id="expiry_date_select" class="form-select" disabled>
                                    <option value="">Primero seleccione producto y bodega</option>
                                </select>
                                <small class="form-text text-muted">Al seleccionar la fecha se cargarán todos los datos automáticamente.</small>
                            </div>

                            <hr class="my-4">

                            <!-- Datos Precargados -->
                            <h5 class="mb-3">Datos del Producto</h5>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label"><i class="fas fa-map-marker-alt me-2"></i>Ubicación</label>
                                    <input type="text" id="location_display" class="form-control bg-light" readonly>
                                    <input type="hidden" name="return_location" id="return_location">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label"><i class="fas fa-barcode me-2"></i>SKU</label>
                                    <input type="text" id="sku_display" class="form-control bg-light" readonly>
                                </div>
                            </div>

                            <div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label"><i class="fas fa-tags me-2"></i>Lote</label>
        <input type="text" id="batch_display" class="form-control bg-light" readonly>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">
            <i class="fas fa-clipboard-check me-2"></i>Condición al Retornar
            <span class="text-danger" id="condition_required">*</span>
        </label>
        <select name="return_item_condition" id="condition_select" class="form-select" disabled>
            <option value="">Seleccione la condición</option>
            <option value="bueno">BUEN ESTADO</option>
            <option value="malo">MAL ESTADO</option>
        </select>
        <small class="form-text text-muted">Indique en qué estado retorna el producto</small>
    </div>
</div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label"><i class="fas fa-calendar-plus me-2"></i>Fecha Ingreso</label>
                                    <input type="text" id="entry_date_display" class="form-control bg-light" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label"><i class="fas fa-cube me-2"></i>Tipo</label>
                                    <input type="text" id="type_display" class="form-control bg-light" readonly>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label"><i class="fas fa-store me-2"></i>Comercio</label>
                                    <input type="text" id="commerce_display" class="form-control bg-light" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label"><i class="fas fa-dollar-sign me-2"></i>Valor</label>
                                    <input type="text" id="value_display" class="form-control bg-light" readonly>
                                </div>
                            </div>

                            <!-- Stock Disponible -->
                            <div class="alert alert-info" id="stock_info" style="display: none;">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Stock disponible:</strong> <span id="stock_text"></span>
                            </div>

                            <hr class="my-4">

                            <!-- Cantidad -->
                            <div class="form-group mb-3">
                                <label for="quantity" class="form-label">
                                    <i class="fas fa-sort-numeric-up me-2"></i>Cantidad <span class="text-danger">*</span>
                                </label>
                                <input type="number" name="return_quantity" id="quantity"
                                       class="form-control" min="1" required disabled>
                            </div>

                            <!-- Campos de Retención -->
                            <div id="retention_fields" style="display: none;">
                                <div class="form-group mb-3">
                                    <label for="retention_substatus" class="form-label">
                                        <i class="fas fa-exclamation-triangle me-2"></i>Motivo <span class="text-danger">*</span>
                                    </label>
                                    <select name="retention_substatus" id="retention_substatus" class="form-select">
                                        <option value="">Seleccione</option>
                                        <option value="AVERIAS">AVERÍAS</option>
                                        <option value="REZAGOS">REZAGOS</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Observaciones -->
                            <div class="form-group mb-3">
                                <label for="observations" class="form-label">
                                    <i class="fas fa-comment-alt me-2"></i>Observaciones
                                    <span class="text-danger" id="obs_required" style="display: none;">*</span>
                                </label>
                                <textarea name="return_observations" id="observations"
                                          class="form-control" rows="3"
                                          placeholder="Describa el motivo..."></textarea>
                            </div>

                            <input type="hidden" name="inventory_id" id="inventory_id">

                            <!-- Botones -->
                            <div class="d-flex justify-content-center gap-3 mt-4">
                                <a href="{{ route('inventories.index') }}" class="btn btn-secondary btn-lg">
                                    <i class="fas fa-arrow-left me-2"></i>Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary btn-lg" id="submit_btn" disabled>
                                    <i class="fas fa-save me-2"></i>Procesar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mostrar errores con SweetAlert
            @if ($errors->any())
                Swal.fire({
                    icon: 'error',
                    title: 'Errores en el formulario',
                    html: '<ul style="text-align: left;">' +
                        @foreach ($errors->all() as $error)
                            '<li>{{ $error }}</li>' +
                        @endforeach
                        '</ul>',
                    confirmButtonText: 'Entendido'
                });
            @endif

            @if (session('success'))
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: '{{ session('success') }}',
                    timer: 3000,
                    showConfirmButton: true
                });
            @endif

            // Elementos del DOM
            const transactionType = document.getElementById('transaction_type');
            const productSearch = document.getElementById('product_search');
            const productResults = document.getElementById('product_results');
            const warehouseSearch = document.getElementById('warehouse_search');
            const warehouseResults = document.getElementById('warehouse_results');
            const expirySelect = document.getElementById('expiry_date_select');
            const quantityInput = document.getElementById('quantity');
            const submitBtn = document.getElementById('submit_btn');
            const retentionFields = document.getElementById('retention_fields');
            const obsRequired = document.getElementById('obs_required');
            const stockInfo = document.getElementById('stock_info');
            const stockText = document.getElementById('stock_text');
            const form = document.getElementById('inventoryForm');
            const customer = "{{ session('selected_customer') }}";
            let selectedProduct = null;
            let selectedWarehouse = null;
            let availableStock = 0;

            // Verificar si hay cliente seleccionado
            if (!customer || customer.trim() === '') {
                Swal.fire({
                    icon: 'warning',
                    title: '¡Atención!',
                    text: 'Debe seleccionar un cliente para gestionar inventario.',
                    confirmButtonText: 'Entendido',
                    allowOutsideClick: false
                });
                transactionType.disabled = true;
                productSearch.disabled = true;
                warehouseSearch.disabled = true;
                submitBtn.disabled = true;
            }

// Cambio de tipo de trámite
transactionType.addEventListener('change', function() {
    const type = this.value;
    resetForm();

    if (!type) {
        document.getElementById('instructions_devolucion').style.display = 'none';
        document.getElementById('instructions_retencion').style.display = 'none';
        return;
    }

    if (type === 'DEVOLUCION') {
        form.action = "{{ route('inventories.processDevolution') }}";
        document.getElementById('instructions_devolucion').style.display = 'block';
        document.getElementById('instructions_retencion').style.display = 'none';
        retentionFields.style.display = 'none';
        obsRequired.style.display = 'none';
        document.getElementById('condition_required').style.display = 'inline';
        document.getElementById('condition_select').disabled = false;
    } else if (type === 'RETENCION') {
        form.action = "{{ route('inventories.processRetention') }}";
        document.getElementById('instructions_devolucion').style.display = 'none';
        document.getElementById('instructions_retencion').style.display = 'block';
        retentionFields.style.display = 'block';
        obsRequired.style.display = 'inline';
        document.getElementById('condition_required').style.display = 'none';
        document.getElementById('condition_select').disabled = true;
    }
});

            // Búsqueda de productos
            productSearch.addEventListener('input', function() {
                const query = this.value.trim();
                if (query.length < 2) {
                    productResults.style.display = 'none';
                    return;
                }

                fetch(`/search-items?query=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(items => {
                        if (items.length === 0) {
                            productResults.innerHTML = '<div class="search-result-item">No se encontraron productos</div>';
                        } else {
                            productResults.innerHTML = items.map(item => `
                                <div class="search-result-item" data-id="${item.item_id}" data-name="${escapeHtml(item.name)}">
                                    <i class="fas fa-box me-2"></i>${escapeHtml(item.name)}
                                    <small class="text-muted ms-2">(SKU: ${escapeHtml(item.sku || 'N/A')})</small>
                                </div>
                            `).join('');
                        }
                        productResults.style.display = 'block';
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        productResults.innerHTML = '<div class="search-result-item">Error al buscar</div>';
                        productResults.style.display = 'block';
                    });
            });

            productResults.addEventListener('click', function(e) {
                const item = e.target.closest('.search-result-item');
                if (item && item.dataset.id) {
                    selectedProduct = {
                        id: item.dataset.id,
                        name: item.dataset.name
                    };

                    document.getElementById('return_item_id').value = selectedProduct.id;
                    document.getElementById('selected_product_name').textContent = selectedProduct.name;
                    document.getElementById('selected_product').style.display = 'block';
                    productSearch.style.display = 'none';
                    productResults.style.display = 'none';

                    checkLoadDates();
                }
            });

            // Búsqueda de bodegas
            warehouseSearch.addEventListener('input', function() {
                const query = this.value.trim();
                if (query.length < 1) {
                    warehouseResults.style.display = 'none';
                    return;
                }

                fetch(`/search-warehouses?query=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(cities => {
                        if (cities.length === 0) {
                            warehouseResults.innerHTML = '<div class="search-result-item">No se encontraron bodegas</div>';
                        } else {
                            warehouseResults.innerHTML = cities.map(city => `
                                <div class="search-result-item" data-name="${escapeHtml(city)}">
                                    <i class="fas fa-warehouse me-2"></i>${escapeHtml(city)}
                                </div>
                            `).join('');
                        }
                        warehouseResults.style.display = 'block';
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        warehouseResults.innerHTML = '<div class="search-result-item">Error al buscar</div>';
                        warehouseResults.style.display = 'block';
                    });
            });

            warehouseResults.addEventListener('click', function(e) {
                const item = e.target.closest('.search-result-item');
                if (item && item.dataset.name) {
                    selectedWarehouse = item.dataset.name;

                    document.getElementById('return_warehouse').value = selectedWarehouse;
                    document.getElementById('selected_warehouse_name').textContent = selectedWarehouse;
                    document.getElementById('selected_warehouse').style.display = 'block';
                    warehouseSearch.style.display = 'none';
                    warehouseResults.style.display = 'none';

                    checkLoadDates();
                }
            });

            function checkLoadDates() {
                if (selectedProduct && selectedWarehouse && transactionType.value) {
                    loadExpiryDates();
                }
            }

            async function loadExpiryDates() {
                const type = transactionType.value;
                const url = type === 'DEVOLUCION'
                    ? '/get-expiry-dates-from-outputs'
                    : '/get-expiry-dates-from-inventories';

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            item_id: selectedProduct.id,
                            warehouse: selectedWarehouse,
                            customer: customer
                        })
                    });

                    if (response.status === 422) {
                        const data = await response.json();
                        Swal.fire({
                            icon: 'warning',
                            title: 'Seleccione un cliente',
                            text: data.message || 'Debe seleccionar un cliente para gestionar inventario.',
                            confirmButtonText: 'Entendido'
                        });
                        return;
                    }

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.message || 'Error en la petición');
                    }

                    if (data.success && data.expiry_dates.length > 0) {
                        expirySelect.innerHTML = '<option value="">Seleccione una fecha</option>' +
                            data.expiry_dates.map(item => `
                                <option value="${item.expiry_date}"
                                        data-quantity="${item.quantity}"
                                        data-inventory-id="${item.inventory_id || ''}">
                                    ${item.expiry_date} - Disponible: ${item.quantity} unidades
                                </option>
                            `).join('');
                        expirySelect.disabled = false;
                    } else {
                        expirySelect.innerHTML = '<option value="">No hay registros disponibles</option>';
                        expirySelect.disabled = true;
                        Swal.fire({
                            icon: 'info',
                            title: 'Sin registros',
                            text: data.message || 'No se encontraron registros'
                        });
                    }
                } catch (error) {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'No se pudieron cargar las fechas'
                    });
                }
            }

            expirySelect.addEventListener('change', async function() {
                const option = this.options[this.selectedIndex];
                if (!option.value) return;

                availableStock = parseInt(option.dataset.quantity);
                const inventoryId = option.dataset.inventoryId;
                
                // FIX: Asegurarse de que inventory_id tenga valor
                if (inventoryId && inventoryId !== 'undefined') {
                    document.getElementById('inventory_id').value = inventoryId;
                }

                await loadRecordData(option.value);
            });

            async function loadRecordData(expiryDate) {
                const type = transactionType.value;
                const url = type === 'DEVOLUCION'
                    ? '/get-output-record-data'
                    : '/get-inventory-record-data';

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            item_id: selectedProduct.id,
                            warehouse: selectedWarehouse,
                            expiry_date: expiryDate,
                            customer: customer
                        })
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.message || 'Error en la petición');
                    }

                    if (data.success) {
                        fillFormData(data.data);
                        // FIX: Asegurarse de que inventory_id se guarde correctamente
                        if (data.data.inventory_id) {
                            document.getElementById('inventory_id').value = data.data.inventory_id;
                        }
                        stockInfo.style.display = 'block';
                        stockText.textContent = `${availableStock} unidades`;
                        quantityInput.disabled = false;
                        quantityInput.max = availableStock;
                        submitBtn.disabled = false;
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message
                        });
                    }
                } catch (error) {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudieron cargar los datos'
                    });
                }
            }

function fillFormData(data) {
    document.getElementById('location_display').value = data.location_code || '';
    document.getElementById('return_location').value = data.location_code || '';
    document.getElementById('sku_display').value = data.sku || '';
    document.getElementById('batch_display').value = data.batch || '';
   
    // FIX: Ya no llenar automáticamente la condición, dejar que el usuario la seleccione
    // Solo mostrar la condición original como referencia en observaciones si lo deseas
   
    document.getElementById('entry_date_display').value = data.entry_date || '';
    document.getElementById('type_display').value = data.type || '';
    document.getElementById('commerce_display').value = data.commerce || '';
    document.getElementById('value_display').value = data.value || '';
}

            quantityInput.addEventListener('input', function() {
                const qty = parseInt(this.value);
                if (qty > availableStock) {
                    this.value = availableStock;
                    Swal.fire({
                        icon: 'warning',
                        title: 'Cantidad excedida',
                        text: `Solo hay ${availableStock} unidades disponibles`,
                        toast: true,
                        position: 'top-end',
                        timer: 3000,
                        showConfirmButton: false
                    });
                }
            });

form.addEventListener('submit', function(e) {
    e.preventDefault();

    const type = transactionType.value;
    const qty = parseInt(quantityInput.value);

    if (!selectedProduct || !selectedWarehouse || !qty) {
        Swal.fire({
            icon: 'error',
            title: 'Campos incompletos',
            text: 'Por favor complete todos los campos requeridos',
            confirmButtonText: 'Entendido'
        });
        return;
    }

    const inventoryId = document.getElementById('inventory_id').value;
    if (!inventoryId || inventoryId === '' || inventoryId === 'undefined') {
        Swal.fire({
            icon: 'error',
            title: 'Error de datos',
            text: 'No se pudo obtener el ID del inventario. Por favor refresque la página e intente nuevamente.',
            confirmButtonText: 'Entendido'
        });
        return;
    }

    // FIX: Validar condición para devoluciones
    if (type === 'DEVOLUCION') {
        const condition = document.getElementById('condition_select').value;
        if (!condition) {
            Swal.fire({
                icon: 'warning',
                title: 'Condición requerida',
                text: 'Debe seleccionar la condición en que retorna el producto',
                confirmButtonText: 'Entendido'
            });
            return;
        }
    }

    if (type === 'RETENCION') {
        const substatus = document.getElementById('retention_substatus').value;
        const obs = document.getElementById('observations').value.trim();

        if (!substatus || !obs) {
            Swal.fire({
                icon: 'warning',
                title: 'Campos requeridos',
                text: 'Debe completar el motivo y las observaciones para la retención',
                confirmButtonText: 'Entendido'
            });
            return;
        }
    }

    const config = type === 'DEVOLUCION' ? {
        title: '¿Confirmar devolución?',
        text: `Se restarán ${qty} unidades de las salidas registradas`,
        icon: 'question',
        iconColor: '#0d6efd'
    } : {
        title: '¿Confirmar retención?',
        text: `Se marcarán ${qty} unidades como retenidas`,
        icon: 'warning',
        iconColor: '#ffc107'
    };

    Swal.fire({
        ...config,
        showCancelButton: true,
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Sí, confirmar',
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Procesando...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading()
            });
            this.submit();
        }
    });
});

            document.getElementById('clear_product').addEventListener('click', function() {
                selectedProduct = null;
                productSearch.style.display = 'block';
                productSearch.value = '';
                document.getElementById('selected_product').style.display = 'none';
                document.getElementById('return_item_id').value = '';
                resetForm();
            });

            document.getElementById('clear_warehouse').addEventListener('click', function() {
                selectedWarehouse = null;
                warehouseSearch.style.display = 'block';
                warehouseSearch.value = '';
                document.getElementById('selected_warehouse').style.display = 'none';
                document.getElementById('return_warehouse').value = '';
                expirySelect.innerHTML = '<option value="">Primero seleccione bodega</option>';
                expirySelect.disabled = true;
                clearFormData();
            });

            document.addEventListener('click', function(e) {
                if (!e.target.closest('.search-container')) {
                    productResults.style.display = 'none';
                    warehouseResults.style.display = 'none';
                }
            });

function resetForm() {
    expirySelect.innerHTML = '<option value="">Primero seleccione producto y bodega</option>';
    expirySelect.disabled = true;
    quantityInput.disabled = true;
    quantityInput.value = '';
    submitBtn.disabled = true;
    stockInfo.style.display = 'none';
    document.getElementById('inventory_id').value = '';
    document.getElementById('condition_select').value = '';
    document.getElementById('condition_select').disabled = true;
    clearFormData();
}

            function clearFormData() {
                ['location', 'sku', 'batch', 'entry_date', 'type', 'commerce', 'value'].forEach(field => {
                    const el = document.getElementById(`${field}_display`);
                    if (el) el.value = '';
                });
                const locEl = document.getElementById('return_location');
                if (locEl) locEl.value = '';
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        });
    </script>
@endsection