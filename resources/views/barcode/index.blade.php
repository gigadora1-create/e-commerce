@extends('layouts.app')

@section('contents')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">Búsqueda de Productos</div>
                <div class="card-body">
                    <form id="searchForm">
                        <div class="form-group">
                            <label for="identifier">Código o SKU</label>
                            <input type="text" class="form-control" id="identifier" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Buscar</button>
                    </form>
                    <div id="productDetails" class="d-none mt-4">
                        <div class="row">
                            <div class="col-md-4">
                                <img id="productImage" src="" alt="Producto" class="img-fluid">
                            </div>
                            <div class="col-md-8">
                                <h4 id="productName"></h4>
                                <p><strong>SKU:</strong> <span id="productSku"></span></p>
                                <p><strong>Código de Barras:</strong> <span id="productBarcode"></span></p>
                                <p><strong>Descripción:</strong> <span id="productDescription"></span></p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <h5>Inventario</h5>
                            <div id="inventoryData"></div>
                        </div>
                        <div class="mt-3">
                            <button id="btnEntry" class="btn btn-success">Registrar Entrada</button>
                            <button id="btnOutput" class="btn btn-danger">Registrar Salida</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Entrada -->
<div class="modal fade" id="entryModal" tabindex="-1" role="dialog" aria-labelledby="entryModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="entryModalLabel">Registrar Entrada</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="entryForm">
                <div class="modal-body">
                    <input type="hidden" id="entryItemId" name="item_id">
                    <input type="hidden" id="entrySku" name="sku">
                    <div class="form-group">
                        <label for="entryWarehouse">Bodega</label>
                        <select class="form-control" id="entryWarehouse" name="warehouse" required></select>
                    </div>
                    <div class="form-group">
                        <label for="entryLocation">Ubicación</label>
                        <select class="form-control" id="entryLocation" name="location_code" required></select>
                    </div>
                    <div class="form-group">
                        <label for="entryQuantity">Cantidad</label>
                        <input type="number" class="form-control" id="entryQuantity" name="quantity" min="1" required>
                    </div>
                    <div class="form-group">
                        <label for="entryBatch">Lote</label>
                        <input type="text" class="form-control" id="entryBatch" name="batch" required>
                    </div>
                    <div class="form-group">
                        <label for="entryExpiryDate">Fecha de Vencimiento</label>
                        <input type="date" class="form-control" id="entryExpiryDate" name="expiry_date" required>
                    </div>
                    <div class="form-group">
                        <label for="entryItemCondition">Condición</label>
                        <select class="form-control" id="entryItemCondition" name="item_condition" required>
                            <option value="NUEVO">Nuevo</option>
                            <option value="USADO">Usado</option>
                            <option value="DAÑADO">Dañado</option>
                            <option value="REACONDICIONADO">Reacondicionado</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="entryDate">Fecha de Entrada</label>
                        <input type="date" class="form-control" id="entryDate" name="entry_date" required>
                    </div>
                    <div class="form-group">
                        <label for="entryCommerce">Comercio</label>
                        <input type="text" class="form-control" id="entryCommerce" name="commerce" required>
                    </div>
                    <div class="form-group">
                        <label for="entryValue">Valor</label>
                        <input type="number" class="form-control" id="entryValue" name="value" min="0" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="entryType">Tipo</label>
                        <input type="text" class="form-control" id="entryType" name="type" required>
                    </div>
                    <div class="form-group">
                        <label for="entryItemDescription">Descripción</label>
                        <textarea class="form-control" id="entryItemDescription" name="item_description" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="entryObservations">Observaciones</label>
                        <textarea class="form-control" id="entryObservations" name="observations"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Salida -->
<div class="modal fade" id="outputModal" tabindex="-1" role="dialog" aria-labelledby="outputModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="outputModalLabel">Registrar Salida</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="outputForm">
                <div class="modal-body">
                    <input type="hidden" id="outputItemId" name="item_id">
                    <div class="form-group">
                        <label for="outputWarehouse">Bodega</label>
                        <select class="form-control" id="outputWarehouse" name="warehouse" required></select>
                    </div>
                    <div class="form-group">
                        <label for="outputLocation">Ubicación</label>
                        <select class="form-control" id="outputLocation" name="location_code" required></select>
                    </div>
                    <div class="form-group">
                        <label for="outputQuantity">Cantidad</label>
                        <input type="number" class="form-control" id="outputQuantity" name="quantity" min="1" required>
                    </div>
                    <div class="form-group">
                        <label for="outputGuide">Guía</label>
                        <input type="text" class="form-control" id="outputGuide" name="guide" required>
                    </div>
                    <div class="form-group">
                        <label for="outputDeclaredValue">Valor Declarado</label>
                        <input type="number" class="form-control" id="outputDeclaredValue" name="declared_value" min="0" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="outputCustomer">Cliente</label>
                        <input type="text" class="form-control" id="outputCustomer" name="customer" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    function showAlert(icon, title, text) {
        Swal.fire({ icon, title, text, confirmButtonText: 'OK' });
    }

    $('#searchForm').submit(function(e) {
        e.preventDefault();
        const identifier = $('#identifier').val().trim();
        if (!identifier) return showAlert('warning', 'Advertencia', 'Ingresa un código o SKU.');
        $.ajax({
            url: "{{ route('barcode.searchBySkuOrBarcode') }}",
            method: 'POST',
            data: { identifier },
            success: function(response) {
                if (response.success) {
                    $('#productName').text(response.item.name);
                    $('#productSku').text(response.item.sku);
                    $('#productBarcode').text(response.item.barcode);
                    $('#productDescription').text(response.item.description);
                    $('#productImage').attr('src', response.item.image_url);
                    $('#entryItemDescription').val(response.item.description);
                    $('#productDetails').removeClass('d-none');
                    loadInventoryData(response.inventory_data);
                    $('#entryItemId').val(response.item.item_id);
                    $('#entrySku').val(response.item.sku);
                    $('#outputItemId').val(response.item.item_id);
                } else {
                    showAlert('error', 'Error', response.message);
                }
            },
            error: function(xhr) {
                console.error('Error búsqueda:', xhr.responseText);
                showAlert('error', 'Error', xhr.responseJSON?.message || 'Error al buscar producto');
            }
        });
    });

    function loadInventoryData(inventoryData) {
        let html = '';
        if (!inventoryData || inventoryData.length === 0) {
            html = '<p>El producto no tiene inventario en ninguna bodega.</p>';
        } else {
            html = `
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Bodega</th>
                            <th>Ubicación</th>
                            <th>Stock</th>
                            <th>Capacidad Disponible</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            inventoryData.forEach(row => {
                html += `
                    <tr>
                        <td>${row.warehouse}</td>
                        <td>${row.location_code} - ${row.location_name}</td>
                        <td>${row.current_stock}</td>
                        <td>${row.available_capacity}</td>
                    </tr>
                `;
            });
            html += `
                    </tbody>
                </table>
            `;
        }
        $('#inventoryData').html(html);
    }

    $('#btnEntry').click(function() {
        $.ajax({
            url: "{{ route('barcode.getWarehouses') }}",
            method: 'GET',
            success: function(warehouses) {
                const select = $('#entryWarehouse');
                select.empty().append('<option value="">Seleccione una bodega</option>');
                warehouses.forEach(w => select.append(`<option value="${w}">${w}</option>`));
                $('#entryModal').modal('show');
            },
            error: function(xhr) {
                showAlert('error', 'Error', xhr.responseJSON?.message || 'Error al cargar bodegas');
            }
        });
    });

    $('#entryWarehouse').change(function() {
        const warehouse = $(this).val();
        const itemId = $('#entryItemId').val();
        if (!warehouse || !itemId) return showAlert('warning', 'Advertencia', 'Selecciona producto y bodega.');
        $.ajax({
            url: "{{ route('barcode.getLocationsByItemAndWarehouse') }}",
            method: 'POST',
            data: { item_id: itemId, warehouse },
            success: function(response) {
                const select = $('#entryLocation');
                select.empty().append('<option value="">Seleccione una ubicación</option>');
                if (response.success && response.locations.length > 0) {
                    response.locations.forEach(loc => {
                        select.append(`<option value="${loc.code}" data-capacity="${loc.available_capacity}">${loc.display_name} (Disponible: ${loc.available_capacity})</option>`);
                    });
                } else {
                    select.append('<option value="">Sin ubicaciones disponibles</option>');
                    showAlert('info', 'Información', response.message || 'No hay ubicaciones disponibles.');
                }
            },
            error: function(xhr) {
                showAlert('error', 'Error', xhr.responseJSON?.message || 'Error al cargar ubicaciones.');
            }
        });
    });

   $('#entryForm').submit(function(e) {
    e.preventDefault();
    const quantity = parseInt($('#entryQuantity').val()) || 0;
    const capacity = parseInt($('#entryLocation option:selected').data('capacity')) || 0;
    if (quantity > capacity) return showAlert('warning', 'Advertencia', `Cantidad excede capacidad (${capacity}).`);

    const formData = $(this).serialize();
    console.log(formData); // Verifica que location_code esté presente

    $.ajax({
        url: "{{ route('barcode.storeEntry') }}",
        method: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                showAlert('success', 'Éxito', response.message);
                $('#entryModal').modal('hide');
                $('#entryForm')[0].reset();
                location.reload();
            } else {
                showAlert('error', 'Error', response.message);
            }
        },
        error: function(xhr) {
            console.error('Error entrada:', xhr.responseText);
            showAlert('error', 'Error', xhr.responseJSON?.message || 'Error al registrar entrada.');
        }
    });
});


    $('#btnOutput').click(function() {
        $.ajax({
            url: "{{ route('barcode.getWarehouses') }}",
            method: 'GET',
            success: function(warehouses) {
                const select = $('#outputWarehouse');
                select.empty().append('<option value="">Seleccione una bodega</option>');
                warehouses.forEach(w => select.append(`<option value="${w}">${w}</option>`));
                $('#outputModal').modal('show');
            },
            error: function(xhr) {
                showAlert('error', 'Error', xhr.responseJSON?.message || 'Error al cargar bodegas');
            }
        });
    });

    $('#outputWarehouse').change(function() {
        const warehouse = $(this).val();
        const itemId = $('#outputItemId').val();
        if (!warehouse || !itemId) return showAlert('warning', 'Advertencia', 'Selecciona producto y bodega.');
        $.ajax({
            url: "{{ route('barcode.getLocationsWithStock') }}",
            method: 'POST',
            data: { item_id: itemId, warehouse },
            success: function(response) {
                const select = $('#outputLocation');
                select.empty().append('<option value="">Seleccione una ubicación</option>');
                if (response.success && response.locations.length > 0) {
                    response.locations.forEach(loc => {
                        select.append(`<option value="${loc.code}" data-stock="${loc.current_stock}">${loc.display_name} (Stock: ${loc.current_stock})</option>`);
                    });
                } else {
                    select.append('<option value="">Sin ubicaciones con stock</option>');
                    showAlert('info', 'Información', response.message || 'No hay stock en ubicaciones.');
                }
            },
            error: function(xhr) {
                showAlert('error', 'Error', xhr.responseJSON?.message || 'Error al cargar ubicaciones.');
            }
        });
    });

$('#outputForm').submit(function(e) {
    e.preventDefault();
    const quantity = parseInt($('#outputQuantity').val()) || 0;
    const stock = parseInt($('#outputLocation option:selected').data('stock')) || 0;
    if (quantity > stock) return showAlert('warning', 'Advertencia', `Cantidad excede stock (${stock}).`);

    const formData = $(this).serialize();
    console.log(formData); // Verifica que location_code esté presente

    $.ajax({
        url: "{{ route('barcode.storeOutput') }}",
        method: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                showAlert('success', 'Éxito', response.message);
                $('#outputModal').modal('hide');
                $('#outputForm')[0].reset();
                location.reload();
            } else {
                showAlert('error', 'Error', response.message);
            }
        },
        error: function(xhr) {
            console.error('Error salida:', xhr.responseText);
            showAlert('error', 'Error', xhr.responseJSON?.message || 'Error al registrar salida.');
        }
    });
});
});
</script>
@endsection
