@extends('layouts.app')

@section('contents')
<link rel="stylesheet" href="{{ asset('css/create_output.inventories.css') }}">    

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header bg-danger text-white text-center">
                        <h3 class="mb-0">Registrar Salida de Inventario</h3>
                    </div>
                    <div class="card-body">
                        <form id="inventoryOutputForm" action="{{ route('inventory-outputs.store') }}" method="POST" class="inventory-output-form">
                            @csrf

                            <!-- Contenedor de productos -->
                            <div id="products-container">
                                <div class="product-item border rounded p-3 mb-3" data-index="0">

                                    <!-- Selección de Producto -->
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-box me-2"></i>Buscar Producto
                                        </label>
                                        <div class="search-container">
                                            <input type="text" class="form-control search-input item_search" 
                                                   placeholder="Escriba para buscar un producto..." autocomplete="off">
                                            <div class="search-results item_results"></div>
                                        </div>
                                        <input type="hidden" name="products[0][item_id]" class="item_id" required>
                                        <div class="selected-item selected_item" style="display: none;">
                                            <div class="selected-item-card">
                                                <div class="selected-item-info">
                                                    <h6 class="selected-item-name"></h6>
                                                    <small class="text-muted">Producto seleccionado</small>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-danger clear-selection">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Imagen del producto -->
                                    <div class="form-group">
                                        <div class="item-image-container" style="display: none;">
                                            <div class="image-wrapper">
                                                <img class="product-image item-image" src="" alt="Imagen del producto" onerror="this.onerror=null; this.src='{{ asset('img/no-image.png') }}';">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Selección de Bodega (Solo en el primer producto) -->
                                    <div class="form-group warehouse_section" style="display: none;">
                                        <label class="form-label">
                                            <i class="fas fa-warehouse me-2"></i>Seleccionar Bodega
                                        </label>
                                        <div class="search-container">
                                            <input type="text" class="form-control search-input warehouse_search" 
                                                   placeholder="Escriba para buscar una bodega..." autocomplete="off">
                                            <div class="search-results warehouse_results"></div>
                                        </div>
                                        <input type="hidden" name="products[0][warehouse]" class="warehouse" required>
                                        <div class="selected-item selected_warehouse" style="display: none;">
                                            <div class="selected-item-card">
                                                <div class="selected-item-info">
                                                    <h6 class="selected-warehouse-name"></h6>
                                                    <small class="text-muted">Bodega seleccionada</small>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-danger clear-warehouse">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
<!-- Selección de Ubicación -->
<div class="form-group location_section" style="display: none;">
    <label class="form-label">
        <i class="fas fa-map-marker-alt me-2"></i>Seleccionar Ubicación
    </label>
    <div class="search-container">
        <input type="text" class="form-control search-input location_search"
               placeholder="Escriba para buscar una ubicación..." autocomplete="off">
        <div class="search-results location_results"></div>
    </div>
    <input type="hidden" name="products[0][location_id]" class="location_id" required>
    <input type="hidden" name="products[0][location_code]" class="location_code" required>
    <div class="selected-item selected_location" style="display: none;">
        <div class="selected-item-card">
            <div class="selected-item-info">
                <h6 class="selected-location-name"></h6>
                <small class="text-muted">Ubicación seleccionada</small>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger clear-location">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
</div>

                                    <!-- Campos del producto -->
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label class="form-label">
                                                <i class="fas fa-sort-numeric-up me-2"></i>Cantidad
                                            </label>
                                            <input type="number" name="products[0][quantity]" class="form-control quantity" required min="1">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">
                                                <i class="fas fa-check-circle me-2"></i>Estado
                                            </label>
                                            <select name="products[0][status]" class="form-select status" required>
                                                <option value="">Seleccione una opción</option>
                                                <option value="bueno">Bueno</option>
                                                <option value="malo">Malo</option>
                                            </select>
                                        </div>
                                    </div>

                                    <button type="button" class="btn btn-danger btn-sm remove-product">
                                        <i class="fas fa-trash"></i> Eliminar Producto
                                    </button>
                                </div>
                            </div>

                            <!-- Botón agregar producto -->
                            <div class="d-flex justify-content-center my-3">
                                <button type="button" id="add-product" class="btn btn-success">
                                    <i class="fas fa-plus"></i> Agregar Producto
                                </button>
                            </div>

                            <!-- Datos generales -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-file-alt me-2"></i>Guía
                                </label>
                                <input type="text" name="guide" id="guide" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-dollar-sign me-2"></i>Valor Declarado
                                </label>
                                <input type="number" step="0.01" name="declared_value" id="declared_value" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-calendar me-2"></i>Fecha de Salida
                                </label>
                                <input type="date" name="created_at" id="created_at" class="form-control" required>
                            </div>

                            <div class="form-group d-flex justify-content-center gap-3 mt-4">
                                <a href="{{ route('inventories.index') }}" class="btn btn-secondary btn-lg">
                                    <i class="fas fa-arrow-left me-2"></i>Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-2"></i>Registrar Salida
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Incluir SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let productIndex = 1;
            const container = document.getElementById('products-container');
            const inventoryForm = document.getElementById('inventoryOutputForm');
            let searchTimeouts = new Map();
            let globalWarehouse = null; // Variable global para la bodega seleccionada

            // Configurar fecha actual
            document.getElementById('created_at').valueAsDate = new Date();

            // Función mejorada para obtener índice del producto
            function getProductIndex(productItem) {
                return productItem.getAttribute('data-index') || '0';
            }

            // Función para actualizar índices de un producto clonado
            function updateProductIndexes(productItem, newIndex) {
                productItem.setAttribute('data-index', newIndex);
                
                // Actualizar nombres de inputs
                productItem.querySelectorAll('input[name], select[name]').forEach(el => {
                    const currentName = el.name;
                    el.name = currentName.replace(/\[\d+\]/, `[${newIndex}]`);
                });
            }

            // Agregar producto dinámico mejorado
            document.getElementById('add-product').addEventListener('click', function() {
                const first = container.querySelector('.product-item');
                const clone = first.cloneNode(true);

                // Reset valores del clon
                resetProductItem(clone);
                
                // Actualizar índices
                updateProductIndexes(clone, productIndex);

                // Si hay bodega global, aplicarla automáticamente
                if (globalWarehouse) {
                    applyWarehouseToProduct(clone, globalWarehouse);
                }

                container.appendChild(clone);
                productIndex++;
            });

            // Función para resetear un item de producto
            function resetProductItem(productItem) {
                // Limpiar inputs y selects
                productItem.querySelectorAll('input:not([type="hidden"]), select').forEach(el => {
                    el.value = '';
                });
                
                // Limpiar hidden inputs
                productItem.querySelectorAll('input[type="hidden"]').forEach(el => {
                    el.value = '';
                });
                
                // Ocultar elementos seleccionados e imágenes
                productItem.querySelectorAll('.selected-item, .item-image-container').forEach(el => {
                    el.style.display = 'none';
                });
                
                // Mostrar inputs de búsqueda
                productItem.querySelectorAll('.search-input').forEach(el => {
                    el.style.display = 'block';
                    el.value = '';
                });
                
                // Ocultar sección de bodega (solo el primer producto debe mostrarla)
                productItem.querySelector('.warehouse_section').style.display = 'none';
                
                // Limpiar src de imagen
                const image = productItem.querySelector('.item-image');
                if (image) image.src = '';
            }

            // Función para aplicar bodega a un producto específico
            function applyWarehouseToProduct(productItem, warehouseData) {
                const warehouseInput = productItem.querySelector('.warehouse');
                const warehouseName = productItem.querySelector('.selected-warehouse-name');
                const selectedWarehouse = productItem.querySelector('.selected_warehouse');
                const warehouseSearch = productItem.querySelector('.warehouse_search');
                
                warehouseInput.value = warehouseData.name;
                warehouseName.textContent = warehouseData.name;
                selectedWarehouse.style.display = 'block';
                warehouseSearch.style.display = 'none';
            }

            // Función para propagar bodega a todos los productos
            function propagateWarehouseToAll(warehouseData) {
                globalWarehouse = warehouseData;
                const allProducts = document.querySelectorAll('.product-item');
                
                allProducts.forEach(productItem => {
                    applyWarehouseToProduct(productItem, warehouseData);
                });
                
                // Ocultar sección de bodega en todos menos el primero
                allProducts.forEach((productItem, index) => {
                    if (index > 0) {
                        productItem.querySelector('.warehouse_section').style.display = 'none';
                    }
                });
            }

            // Eliminar producto mejorado
            document.addEventListener('click', function(e) {
                if (e.target.closest('.remove-product')) {
                    const productItems = document.querySelectorAll('.product-item');
                    if (productItems.length > 1) {
                        const productToRemove = e.target.closest('.product-item');
                        const isFirstProduct = getProductIndex(productToRemove) === '0';
                        
                        productToRemove.remove();
                        
                        // Si se elimina el primer producto, promover el siguiente
                        if (isFirstProduct) {
                            const newFirstProduct = document.querySelector('.product-item');
                            if (newFirstProduct) {
                                updateProductIndexes(newFirstProduct, 0);
                                // Mostrar sección de bodega en el nuevo primer producto
                                newFirstProduct.querySelector('.warehouse_section').style.display = 'block';
                            }
                        }
                    } else {
                        Swal.fire('Aviso', 'Debe haber al menos un producto', 'warning');
                    }
                }
            });

            // Event listeners delegados mejorados
            document.addEventListener('input', function(e) {
                const productItem = e.target.closest('.product-item');
                if (!productItem) return;

                if (e.target.classList.contains('item_search')) {
                    handleItemSearch(e.target, productItem);
                } else if (e.target.classList.contains('warehouse_search')) {
                    handleWarehouseSearch(e.target, productItem);
                }
            });

            document.addEventListener('click', function(e) {
                // Ocultar resultados si click fuera
                if (!e.target.closest('.search-container')) {
                    document.querySelectorAll('.search-results').forEach(results => {
                        results.style.display = 'none';
                    });
                }

                const productItem = e.target.closest('.product-item');
                if (!productItem) return;

                if (e.target.closest('.search-result-item') && e.target.closest('.item_results')) {
                    handleItemSelection(e.target.closest('.search-result-item'), productItem);
                } else if (e.target.closest('.search-result-item') && e.target.closest('.warehouse_results')) {
                    handleWarehouseSelection(e.target.closest('.search-result-item'), productItem);
                } else if (e.target.closest('.clear-selection')) {
                    clearItemSelection(productItem);
                } else if (e.target.closest('.clear-warehouse')) {
                    clearWarehouseSelection(productItem);
                }
            });

            // Funciones de búsqueda mejoradas
            function handleItemSearch(input, productItem) {
                const query = input.value.trim();
                const itemResults = productItem.querySelector('.item_results');
                
                // Limpiar timeout existente para este input específico
                if (searchTimeouts.has(input)) {
                    clearTimeout(searchTimeouts.get(input));
                }
                
                if (query.length < 2) {
                    itemResults.style.display = 'none';
                    return;
                }

                const timeoutId = setTimeout(() => {
                    searchItems(query, itemResults, productItem);
                }, 300);
                searchTimeouts.set(input, timeoutId);
            }

            function handleWarehouseSearch(input, productItem) {
                const query = input.value.trim();
                const warehouseResults = productItem.querySelector('.warehouse_results');
                
                if (query.length < 1) {
                    warehouseResults.style.display = 'none';
                    return;
                }

                searchWarehouses(query, warehouseResults, productItem);
            }

            function searchItems(query, resultsContainer, productItem) {
                fetch(`/search-items?query=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(items => {
                        displayItemResults(items, resultsContainer);
                    })
                    .catch(error => {
                        console.error('Error searching items:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error al buscar productos'
                        });
                    });
            }

          function searchWarehouses(query, resultsContainer, productItem) {
    fetch(`/search-warehouses?query=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(cities => {
            console.log('Warehouse search response:', cities); // Depuración
            displayWarehouseResults(cities, resultsContainer);
        })
        .catch(error => {
            console.error('Error searching warehouses:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al buscar bodegas'
            });
        });
}

            function displayItemResults(items, resultsContainer) {
                if (items.length === 0) {
                    resultsContainer.innerHTML = '<div class="search-result-item">No se encontraron productos</div>';
                } else {
                    resultsContainer.innerHTML = items.map(item => `
                        <div class="search-result-item" data-item-id="${item.item_id}" data-item-name="${item.name}">
                            <i class="fas fa-box"></i>
                            <span>${item.name}</span>
                        </div>
                    `).join('');
                }
                resultsContainer.style.display = 'block';
            }

         function displayWarehouseResults(cities, resultsContainer) {
    if (cities.length === 0) {
        resultsContainer.innerHTML = '<div class="search-result-item">No se encontraron bodegas</div>';
    } else {
        resultsContainer.innerHTML = cities.map(city => `
            <div class="search-result-item" data-warehouse-name="${city}">
                <i class="fas fa-warehouse"></i>
                <span>${city}</span>
            </div>
        `).join('');
    }
    resultsContainer.style.display = 'block';
}

            // Funciones de selección mejoradas
           async function handleItemSelection(resultItem, productItem) {
    if (resultItem && resultItem.dataset.itemId) {
        const selectedItem = {
            id: resultItem.dataset.itemId,
            name: resultItem.dataset.itemName
        };
        selectItem(selectedItem, productItem);
        productItem.querySelector('.item_results').style.display = 'none';
        productItem.querySelector('.item_search').value = '';
        await loadItemImage(selectedItem.name, productItem);
        // Cargar ubicaciones para el producto seleccionado
        await loadLocationsForItem(selectedItem.id, productItem);
    }
}
// Evento para manejar la búsqueda de ubicaciones
document.addEventListener('input', function(e) {
    const productItem = e.target.closest('.product-item');
    if (!productItem) return;
    if (e.target.classList.contains('location_search')) {
        handleLocationSearch(e.target, productItem);
    }
});

// Evento para manejar la selección de ubicación
document.addEventListener('click', function(e) {
    const productItem = e.target.closest('.product-item');
    if (!productItem) return;
    if (e.target.closest('.search-result-item') && e.target.closest('.location_results')) {
        handleLocationSelection(e.target.closest('.search-result-item'), productItem);
    } else if (e.target.closest('.clear-location')) {
        clearLocationSelection(productItem);
    }
});

// Función para manejar la búsqueda de ubicaciones
function handleLocationSearch(input, productItem) {
    const query = input.value.trim();
    const locationResults = productItem.querySelector('.location_results');
    if (query.length < 1) {
        locationResults.style.display = 'none';
        return;
    }
    // Filtrar ubicaciones ya cargadas
    const locations = Array.from(locationResults.querySelectorAll('.search-result-item'))
        .filter(item => item.textContent.toLowerCase().includes(query.toLowerCase()));
    if (locations.length > 0) {
        locationResults.innerHTML = '';
        locations.forEach(item => locationResults.appendChild(item));
        locationResults.style.display = 'block';
    }
}


          async function handleWarehouseSelection(resultItem, productItem) {
    if (resultItem && resultItem.dataset.warehouseName) {
        const selectedWarehouse = {
            name: resultItem.dataset.warehouseName
        };
        console.log('Selected warehouse:', selectedWarehouse); // Depuración
        
        // Obtener el producto seleccionado para verificar disponibilidad
        const selectedItemName = productItem.querySelector('.selected-item-name').textContent;
        if (!selectedItemName) {
            Swal.fire({
                icon: 'warning',
                title: 'Error',
                text: 'Por favor seleccione un producto primero'
            });
            return;
        }

        // Verificar disponibilidad
        const availability = await checkAvailability(selectedItemName, selectedWarehouse.name);
        if (!availability.available) {
            Swal.fire({
                icon: 'error',
                title: 'Producto no disponible',
                text: `El producto ${selectedItemName} no está disponible en la bodega ${selectedWarehouse.name}`
            });
            return;
        }

        if (availability.stockStatus === 'Pronto a Agotar') {
            Swal.fire({
                icon: 'warning',
                title: 'Stock bajo',
                text: `El producto ${selectedItemName} tiene existencias bajas en ${selectedWarehouse.name} (Stock: ${availability.stockAvailable})`
            });
        }

        // Propagar bodega a todos los productos
        propagateWarehouseToAll(selectedWarehouse);
        productItem.querySelector('.warehouse_results').style.display = 'none';
        productItem.querySelector('.warehouse_search').value = '';
    } else {
        console.error('No warehouse name found in resultItem:', resultItem);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo seleccionar la bodega. Por favor intenta de nuevo.'
        });
    }
}

            function selectItem(item, productItem) {
                productItem.querySelector('.item_id').value = item.id;
                productItem.querySelector('.selected-item-name').textContent = item.name;
                productItem.querySelector('.selected_item').style.display = 'block';
                productItem.querySelector('.item_search').style.display = 'none';
                
                // Mostrar sección de bodega solo si es el primer producto y no hay bodega global
                const index = getProductIndex(productItem);
                if (index === '0' && !globalWarehouse) {
                    productItem.querySelector('.warehouse_section').style.display = 'block';
                } else if (globalWarehouse) {
                    // Si ya hay bodega global, aplicarla
                    applyWarehouseToProduct(productItem, globalWarehouse);
                }
            }

            function clearItemSelection(productItem) {
                productItem.querySelector('.item_id').value = '';
                productItem.querySelector('.selected_item').style.display = 'none';
                productItem.querySelector('.item_search').style.display = 'block';
                productItem.querySelector('.item_search').value = '';
                productItem.querySelector('.item-image-container').style.display = 'none';
                
                // Ocultar sección de bodega si no es el primer producto
                const index = getProductIndex(productItem);
                if (index !== '0') {
                    productItem.querySelector('.warehouse_section').style.display = 'none';
                }
                
                clearWarehouseSelection(productItem);
            }

            function clearWarehouseSelection(productItem) {
                const index = getProductIndex(productItem);
                
                if (index === '0') {
                    // Si es el primer producto, limpiar toda la bodega global
                    globalWarehouse = null;
                    
                    // Limpiar todos los productos
                    document.querySelectorAll('.product-item').forEach(product => {
                        product.querySelector('.warehouse').value = '';
                        product.querySelector('.selected_warehouse').style.display = 'none';
                        product.querySelector('.warehouse_search').style.display = 'block';
                        
                        // Solo mostrar sección de bodega en el primer producto
                        const productIndex = getProductIndex(product);
                        if (productIndex === '0') {
                            product.querySelector('.warehouse_section').style.display = 'block';
                        } else {
                            product.querySelector('.warehouse_section').style.display = 'none';
                        }
                    });
                } else {
                    // Para productos secundarios, solo limpiar local
                    productItem.querySelector('.warehouse').value = '';
                    productItem.querySelector('.selected_warehouse').style.display = 'none';
                    productItem.querySelector('.warehouse_search').style.display = 'block';
                    productItem.querySelector('.warehouse_section').style.display = 'none';
                }
            }
            // Función para cargar ubicaciones de un producto
async function loadLocationsForItem(itemId, productItem) {
    try {
        const response = await fetch(`/get-locations-by-item/${itemId}`);
        const locations = await response.json();
        if (locations.length > 0) {
            displayLocationResults(locations, productItem.querySelector('.location_results'));
            productItem.querySelector('.location_section').style.display = 'block';
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Sin ubicaciones',
                text: 'No hay ubicaciones disponibles para este producto.'
            });
        }
    } catch (error) {
        console.error('Error loading locations:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al cargar ubicaciones.'
        });
    }
}

// Función para mostrar resultados de ubicaciones
function displayLocationResults(locations, resultsContainer) {
    if (locations.length === 0) {
        resultsContainer.innerHTML = '<div class="search-result-item">No se encontraron ubicaciones</div>';
    } else {
        resultsContainer.innerHTML = locations.map(location => `
            <div class="search-result-item"
                 data-location-id="${location.location_id}"
                 data-location-code="${location.location_code}"
                 data-location-name="${location.location_name}">
                <i class="fas fa-map-marker-alt"></i>
                <span>${location.location_name} (${location.location_code}) - Stock: ${location.current_quantity}</span>
            </div>
        `).join('');
    }
    resultsContainer.style.display = 'block';
}

// Función para manejar la selección de ubicación
function handleLocationSelection(resultItem, productItem) {
    if (resultItem && resultItem.dataset.locationId) {
        const selectedLocation = {
            id: resultItem.dataset.locationId,
            code: resultItem.dataset.locationCode,
            name: resultItem.dataset.locationName
        };
        selectLocation(selectedLocation, productItem);
        productItem.querySelector('.location_results').style.display = 'none';
        productItem.querySelector('.location_search').value = '';
    }
}

// Función para seleccionar una ubicación
function selectLocation(location, productItem) {
    productItem.querySelector('.location_id').value = location.id;
    productItem.querySelector('.location_code').value = location.code;
    productItem.querySelector('.selected-location-name').textContent = location.name;
    productItem.querySelector('.selected_location').style.display = 'block';
    productItem.querySelector('.location_search').style.display = 'none';
}

// Función para limpiar la selección de ubicación
function clearLocationSelection(productItem) {
    productItem.querySelector('.location_id').value = '';
    productItem.querySelector('.location_code').value = '';
    productItem.querySelector('.selected_location').style.display = 'none';
    productItem.querySelector('.location_search').style.display = 'block';
    productItem.querySelector('.location_search').value = '';
}


            async function loadItemImage(itemName, productItem) {
                try {
                    const response = await fetch(`/get-item-image/${encodeURIComponent(itemName)}`);
                    const data = await response.json();
                    const itemImage = productItem.querySelector('.item-image');
                    if (data.image_url) {
                        itemImage.src = data.image_url;
                        productItem.querySelector('.item-image-container').style.display = 'block';
                    } else {
                        productItem.querySelector('.item-image-container').style.display = 'none';
                    }
                } catch (error) {
                    console.error('Error loading image:', error);
                    productItem.querySelector('.item-image-container').style.display = 'none';
                }
            }

            async function checkAvailability(itemName, warehouse) {
                try {
                    const response = await fetch(`/check-availability?item=${encodeURIComponent(itemName)}&warehouse=${encodeURIComponent(warehouse)}`);
                    return await response.json();
                } catch (error) {
                    console.error('Error checking availability:', error);
                    return { available: false };
                }
            }

            // Validación del formulario mejorada
            inventoryForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                const productItems = document.querySelectorAll('.product-item');
                let hasErrors = false;

                // Verificar si hay bodega global
                if (!globalWarehouse) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Error',
                        text: 'Por favor seleccione una bodega'
                    });
                    return;
                }

                // Validar cada producto
                for (let productItem of productItems) {
                    const itemId = productItem.querySelector('.item_id').value;
                    const warehouse = productItem.querySelector('.warehouse').value;
                    const quantity = parseInt(productItem.querySelector('.quantity').value);
                    const status = productItem.querySelector('.status').value;
                    const locationId = productItem.querySelector('.location_id').value;
    if (!locationId) {
        Swal.fire({
            icon: 'warning',
            title: 'Error',
            text: 'Por favor seleccione una ubicación en todos los ítems'
        });
        hasErrors = true;
        break;
    }

                    if (!itemId) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Error',
                            text: 'Por favor seleccione un producto en todos los ítems'
                        });
                        hasErrors = true;
                        break;
                    }
                    
                    if (!warehouse) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Error',
                            text: 'Por favor seleccione una bodega'
                        });
                        hasErrors = true;
                        break;
                    }

                    if (!quantity || quantity <= 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Error',
                            text: 'La cantidad debe ser mayor que 0 en todos los ítems'
                        });
                        hasErrors = true;
                        break;
                    }

                    if (!status) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Error',
                            text: 'Por favor seleccione un estado en todos los ítems'
                        });
                        hasErrors = true;
                        break;
                    }

                    // Verificar disponibilidad y cantidad
                    const selectedItemName = productItem.querySelector('.selected-item-name').textContent;
                    const availability = await checkAvailability(selectedItemName, warehouse);
                    
                    if (!availability.available) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: `El producto ${selectedItemName} no está disponible en la bodega ${warehouse}`
                        });
                        hasErrors = true;
                        break;
                    }

                    if (quantity > availability.stockAvailable) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: `La cantidad solicitada (${quantity}) excede el stock disponible (${availability.stockAvailable}) para ${selectedItemName}`
                        });
                        hasErrors = true;
                        break;
                    }
                }

                if (hasErrors) return;

                // Verificar campos generales
                const guide = document.getElementById('guide').value.trim();
                const declaredValue = parseFloat(document.getElementById('declared_value').value);
                
                if (!guide) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Error',
                        text: 'Por favor ingrese la guía'
                    });
                    return;
                }

                if (!declaredValue || declaredValue <= 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Error',
                        text: 'Por favor ingrese un valor declarado válido'
                    });
                    return;
                }

                // Mostrar loading
                Swal.fire({
                    title: 'Procesando...',
                    text: 'Registrando salida de inventario',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Enviar formulario con AJAX
                const formData = new FormData(inventoryForm);
                
                try {
                    const response = await fetch(inventoryForm.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') || '{{ csrf_token() }}'
                        }
                    });

                    const result = await response.json();
                    
                    if (response.ok) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: result.message || 'Salida de inventario registrada correctamente',
                            showConfirmButton: true,
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.href = '{{ route('inventory-outputs.index') }}';
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: result.message || 'Error al registrar la salida de inventario'
                        });
                    }
                } catch (error) {
                    console.error('Form submission error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error de conexión. Por favor intente nuevamente.'
                    });
                }
            });

            // Limpiar timeouts al descargar la página
            window.addEventListener('beforeunload', function() {
                searchTimeouts.forEach((timeoutId, input) => {
                    clearTimeout(timeoutId);
                });
                searchTimeouts.clear();
            });
        });
    </script>
@endsection
