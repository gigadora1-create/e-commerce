<!-- resources/views/partials/create_output_accordion.blade.php -->
<div class="accordion" id="createOutputAccordion">
    <div class="accordion-item">
        <h2 class="accordion-header" id="headingOne">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                Registrar Salida de Inventario
            </button>
        </h2>
        <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#createOutputAccordion">
            <div class="accordion-body">
                <form action="{{ route('inventory-outputs-cali.store') }}" method="POST" class="inventory-output-form">
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
                        <select name="warehouse" id="warehouse" class="form-input" required>
                            <option value="">Selecciona una ciudad</option>
                            @foreach($cities as $id => $city_name)
                                <option value="{{ $id }}">{{ $city_name }}</option>
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