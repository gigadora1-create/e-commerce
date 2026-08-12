<!-- resources/views/partials/create_output_form.blade.php -->
<style>
    .custom-card {
        border-radius: 15px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
    }
    .custom-card .card-header {
        background-color: #4ae29b;
        color: white;
        border-radius: 15px 15px 0 0;
    }
    .custom-card .card-body {
        padding: 20px;
    }
    .custom-card .form-group label {
        font-weight: bold;
        color: #333;
    }
    .custom-card .form-control, .custom-card .form-select, .custom-card .form-input {
        border-radius: 10px;
        border: 1px solid #ddd;
        background-color: #f9f9f9;
        padding: 10px;
        width: 100%;
        margin-top: 5px;
    }
    .custom-card .btn-primary, .custom-card .submit-btn {
        background-color: #4ae287;
        border: none;
        border-radius: 10px;
        color: white;
        padding: 10px 20px;
        cursor: pointer;
        transition: background-color 0.3s;
    }
    .custom-card .btn-primary:hover, .custom-card .submit-btn:hover {
        background-color: #3bc574;
    }
    .custom-card .btn-secondary {
        background-color: #6c757d;
        border: none;
        border-radius: 10px;
        color: white;
        padding: 10px 20px;
        cursor: pointer;
        transition: background-color 0.3s;
    }
    .custom-card .btn-secondary:hover {
        background-color: #5a6268;
    }
    .form-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
    }
    .form-group-half {
        width: 48%;
    }
    #item-image-container {
        text-align: center;
        margin-top: 15px;
    }
    #item-image {
        max-width: 200px;
        max-height: 200px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
</style>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="custom-card shadow-lg">
                <div class="card-header">
                    <h3 class="mb-0">Registrar Salida de Inventario</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('inventory-outputs.store') }}" method="POST" class="inventory-output-form">
                        @csrf
                        <!-- Selección de Inventario -->
                        <div class="form-group">
                            <label for="inventory_id" class="form-label">Inventario</label>
                            <select name="inventory_id" id="inventory_id" class="form-select" required>
                                <option value="">Seleccione un Producto</option>
                                @foreach($inventories as $inventory)
                                <option value="{{ $inventory->inventory_id }}" data-item-description="{{ $inventory->item_description }}">{{ $inventory->item_description }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Imagen del Producto (oculta hasta seleccionar) -->
                        <div class="form-group">
                            <div id="item-image-container" style="display: none;">
                                <img id="item-image" src="" alt="Imagen del producto">
                            </div>
                        </div>

                        <!-- Campo Guía -->
                        <div class="form-group">
                            <label for="guide" class="form-label">Guía</label>
                            <input type="text" name="guide" id="guide" class="form-input" required>
                        </div>

                        <!-- Cantidad y Fecha de Salida -->
                        <div class="form-row">
                            <div class="form-group form-group-half">
                                <label for="quantity" class="form-label">Cantidad</label>
                                <input type="number" name="quantity" id="quantity" class="form-input" required>
                            </div>
                            <div class="form-group form-group-half">
                                <label for="output_date" class="form-label">Fecha de Salida</label>
                                <input type="date" name="output_date" id="output_date" class="form-input" required>
                            </div>
                        </div>

                        <!-- Valor Declarado y Estado -->
                        <div class="form-row">
                            <div class="form-group form-group-half">
                                <label for="declared_value" class="form-label">Valor Declarado</label>
                                <input type="number" step="0.01" name="declared_value" id="declared_value" class="form-input" required>
                            </div>
                            <div class="form-group form-group-half">
                                <label for="status" class="form-label">Estado</label>
                                <select name="status" id="status" class="form-select" required>
                                    <option value="">Seleccione una opción</option>
                                    <option value="bueno">Bueno</option>
                                    <option value="malo">Malo</option>
                                </select>
                            </div>
                        </div>

                         <!-- Campo de Selección Bodega -->
                         <div class="form-group">
                            <label for="warehouse" class="form-label">Bodega</label>
                            <select name="warehouse" id="warehouse" class="form-select" required>
                                <option value="">Selecciona una ciudad</option>
                                @foreach($cities as $city_id => $name)
                                    <option value="{{ $city_id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        

                        <!-- Botón de Enviar -->
                        <div class="form-group">
                            <button type="submit" class="submit-btn">Registrar Salida</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inventorySelect = document.getElementById('inventory_id');
    const imageContainer = document.getElementById('item-image-container');
    const itemImage = document.getElementById('item-image');

    inventorySelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const itemDescription = selectedOption.getAttribute('data-item-description');

        if (itemDescription) {
            fetch(`/get-item-image/${encodeURIComponent(itemDescription)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.image_url) {
                        itemImage.src = data.image_url;
                        imageContainer.style.display = 'block';
                    } else {
                        imageContainer.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    imageContainer.style.display = 'none';
                });
        } else {
            imageContainer.style.display = 'none';
        }
    });
});
</script>