@extends('layouts.app')

@push('styles')
    @include('supplies.partials.theme-styles')
@endpush

@section('contents')
    <div class="container-fluid py-4 supplies-shell">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1">{{ $isAdmin ? 'Solicitudes de salida de proveeduria' : 'Mis solicitudes de proveeduria' }}</h1>
                <p class="text-muted mb-0">
                    {{ $isAdmin ? 'Administre alistamiento, retiro y cierre definitivo del stock reservado.' : 'Cree sus solicitudes y consulte el estado de sus requerimientos.' }}
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button
                    class="btn btn-danger"
                    type="button"
                    id="createSupplyIssueRequestButton"
                    @if ($requestCreationAllowed)
                        data-bs-toggle="modal"
                        data-bs-target="#createSupplyIssueRequestModal"
                    @endif
                >
                    <i class="fas fa-file-export me-1"></i> Nueva solicitud
                </button>
            </div>
        </div>

        @include('supplies.partials.alerts')

        <div class="supplies-stats-grid mb-4">
            @if ($isAdmin)
                <div class="supplies-kpi">
                    <div class="supplies-kpi-label">Stock disponible</div>
                    <div class="supplies-kpi-value">{{ $stats['available'] }}</div>
                    <div class="supplies-kpi-note">Unidades libres para entrega</div>
                </div>
                <div class="supplies-kpi">
                    <div class="supplies-kpi-label">Stock retenido</div>
                    <div class="supplies-kpi-value">{{ $stats['reserved'] }}</div>
                    <div class="supplies-kpi-note">En alistamiento, aun no cerrado</div>
                </div>
                <div class="supplies-kpi">
                    <div class="supplies-kpi-label">Entradas acumuladas</div>
                    <div class="supplies-kpi-value">{{ $stats['total_entries'] }}</div>
                    <div class="supplies-kpi-note">Recibidas por auditoria</div>
                </div>
                <div class="supplies-kpi">
                    <div class="supplies-kpi-label">Salidas acumuladas</div>
                    <div class="supplies-kpi-value">{{ $stats['total_exits'] }}</div>
                    <div class="supplies-kpi-note">Entregas cerradas</div>
                </div>
            @else
                <div class="supplies-kpi">
                    <div class="supplies-kpi-label">Mis solicitudes</div>
                    <div class="supplies-kpi-value">{{ $stats['total_requests'] }}</div>
                    <div class="supplies-kpi-note">Pedidos creados por su usuario</div>
                </div>
                <div class="supplies-kpi">
                    <div class="supplies-kpi-label">En alistamiento</div>
                    <div class="supplies-kpi-value">{{ $stats['preparing'] }}</div>
                    <div class="supplies-kpi-note">Reservas activas pendientes</div>
                </div>
                <div class="supplies-kpi">
                    <div class="supplies-kpi-label">Listas para recoger</div>
                    <div class="supplies-kpi-value">{{ $stats['ready'] }}</div>
                    <div class="supplies-kpi-note">Solicitudes disponibles para retiro</div>
                </div>
                <div class="supplies-kpi">
                    <div class="supplies-kpi-label">Cerradas</div>
                    <div class="supplies-kpi-value">{{ $stats['closed'] }}</div>
                    <div class="supplies-kpi-note">Solicitudes finalizadas de su usuario</div>
                </div>
            @endif
        </div>

        @if ($isAdmin)
            <div class="supplies-panel mb-4">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Panel operativo de stock</h2>
                        <p class="text-muted mb-0">Control de entradas, salidas, disponible, retenido y alertas de reposicion para el equipo operativo.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <input type="text" class="form-control table-live-filter" data-target="#suppliesStockTable"
                            placeholder="Filtrar por letras: producto o ID">
                        <button class="btn btn-outline-secondary table-live-filter-clear" type="button" data-target="#suppliesStockTable">Limpiar</button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle supplies-stock-table" id="suppliesStockTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Producto</th>
                                <th>Entradas</th>
                                <th>Salidas</th>
                                <th>Stock fisico</th>
                                <th>Disponible</th>
                                <th>Retenido</th>
                                <th>Semaforo</th>
                                <th>Ult. movimiento</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($stockRows as $productRow)
                                @php($level = $productRow->stock_level)
                                <tr>
                                    <td class="fw-semibold">{{ $productRow->catalog_number }}</td>
                                    <td>{{ $productRow->name }}</td>
                                    <td>{{ (int) ($productRow->total_entries ?? 0) }}</td>
                                    <td>{{ abs((int) ($productRow->total_exits_raw ?? 0)) }}</td>
                                    <td>{{ $productRow->stock_on_hand }}</td>
                                    <td>{{ $productRow->available_stock }}</td>
                                    <td>{{ $productRow->reserved_stock }}</td>
                                    <td>
                                        <span class="supplies-traffic-light">
                                            <span class="supplies-light-dot {{ $level['key'] === 'good' ? 'is-good' : ($level['key'] === 'warning' ? 'is-warning' : 'is-critical') }}"></span>
                                            {{ $level['label'] }}
                                        </span>
                                    </td>
                                    <td>{{ $productRow->last_movement_at ? \Carbon\Carbon::parse($productRow->last_movement_at)->format('Y-m-d H:i') : 'Sin movimiento' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">No hay productos para mostrar en el panel operativo.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $stockRows->links('pagination.custom') }}
                </div>
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="supplies-toolbar mb-3">
                    <div>
                        <h2 class="h5 mb-1">{{ $isAdmin ? 'Todas las solicitudes' : 'Solicitudes del usuario' }}</h2>
                        <p class="text-muted mb-0">
                            {{ $isAdmin ? 'Aqui puede validar alistamiento, entrega y cierre con impacto real en stock.' : 'Solo se muestran las solicitudes creadas por su usuario.' }}
                        </p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <input type="text" class="form-control table-live-filter" data-target="#suppliesIssuesTable"
                            placeholder="{{ $isAdmin ? 'Filtrar por letras: solicitud, cliente o usuario' : 'Filtrar por letras: solicitud o estado' }}">
                        <button class="btn btn-outline-secondary table-live-filter-clear" type="button" data-target="#suppliesIssuesTable">Limpiar</button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle" id="suppliesIssuesTable">
                        <thead>
                            <tr>
                                <th>Solicitud</th>
                                <th>Cliente</th>
                                @if ($isAdmin)
                                    <th>Usuario</th>
                                @endif
                                <th>Fecha</th>
                                <th>Items</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($requests as $requestRow)
                                <tr>
                                    <td class="fw-semibold">{{ $requestRow->request_number }}</td>
                                    <td>{{ $requestRow->client?->name ?? 'Sin cliente' }}</td>
                                    @if ($isAdmin)
                                        <td>{{ $requestRow->requestedBy?->name ?? 'Sin usuario' }}</td>
                                    @endif
                                    <td>{{ optional($requestRow->requested_at)->format('Y-m-d H:i') }}</td>
                                    <td>{{ $requestRow->items_count }}</td>
                                    <td>
                                        <span class="badge bg-{{ $requestRow->status_color }}">
                                            {{ $requestRow->status_label }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2">
                                            <a class="btn btn-sm btn-outline-primary" href="{{ route('supplies.issues.show', $requestRow) }}">
                                                Abrir
                                            </a>
                                            @if ($isAdmin || $requestRow->status === \App\Models\SupplyIssueRequest::STATUS_CLOSED)
                                                <a class="btn btn-sm btn-outline-danger" href="{{ route('supplies.issues.pdf', $requestRow) }}">
                                                    PDF
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $isAdmin ? 7 : 6 }}" class="text-center text-muted py-4">
                                        No hay solicitudes registradas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $requests->links('pagination.custom') }}
            </div>
        </div>
    </div>

    <div class="modal fade supply-request-modal supplies-themed-modal" id="createSupplyIssueRequestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva solicitud de salida de inventario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form method="POST" action="{{ route('supplies.issues.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Cliente</label>
                            <div class="supply-client-picker">
                                <input type="hidden" class="client-id-input" name="supply_client_id" required>
                                <input type="text" class="form-control client-search-input"
                                    placeholder="Escriba para buscar cliente"
                                    autocomplete="off" required>
                                <div class="client-search-results"></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Observaciones de la solicitud</label>
                            <textarea class="form-control" name="request_notes" rows="3"
                                placeholder="Detalle para alistamiento o entrega interna"></textarea>
                        </div>

                        <div class="mb-3">
                            <h6 class="mb-1">Productos solicitados</h6>
                            <div class="text-muted small">El sistema valida la disponibilidad real al enviar la solicitud. Si un producto no tiene unidades disponibles, se mostrara el aviso correspondiente.</div>
                        </div>

                        <div class="table-responsive supply-request-table-wrap">
                            <table class="table align-middle" id="issueRequestItemsTable">
                                <thead>
                                    <tr>
                                        <th style="min-width: 420px;">Producto</th>
                                        <th style="width: 180px;">Cantidad</th>
                                        @if ($isAdmin)
                                            <th style="width: 170px;">Stock</th>
                                        @endif
                                        <th style="width: 90px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <div class="supply-product-picker">
                                                <input type="hidden" class="product-id-input" name="product_id[]" required>
                                                <input type="text" class="form-control product-search-input mb-2"
                                                    placeholder="Escriba nombre o ID para filtrar en tiempo real"
                                                    autocomplete="off">
                                                <div class="product-search-results"></div>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" min="0" class="form-control requested-quantity-input" name="requested_quantity[]" required>
                                            <div class="availability-feedback invalid-feedback d-block"></div>
                                        </td>
                                        @if ($isAdmin)
                                            <td>
                                                <div class="stock-badge-container text-muted small">Seleccione un producto</div>
                                            </td>
                                        @endif
                                        <td class="text-end">
                                            <div class="request-row-actions"></div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-danger" type="submit">Enviar solicitud</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if ($isAdmin && $lowStockProducts->isNotEmpty())
        <div class="modal fade supplies-themed-modal" id="lowStockAlertModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Alerta de stock bajo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-3">Hay productos con nivel critico o bajo. Revise los siguientes items:</p>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Producto</th>
                                        <th>Disponible</th>
                                        <th>Retenido</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($lowStockProducts as $product)
                                        <tr>
                                            <td class="fw-semibold">{{ $product['catalog_number'] }}</td>
                                            <td>{{ $product['name'] }}</td>
                                            <td>{{ $product['available_stock'] }}</td>
                                            <td>{{ $product['reserved_stock'] }}</td>
                                            @php($levelClass = $product['key'] === 'critical' ? 'bg-danger' : ($product['key'] === 'warning' ? 'bg-warning text-dark' : 'bg-success'))
                                            <td><span class="badge {{ $levelClass }}">{{ $product['label'] }}</span> <span class="small text-muted">{{ $product['note'] }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const requestItemsTableBody = document.querySelector('#issueRequestItemsTable tbody');
            const catalogOptions = @json($catalogProductOptions);
            const clientOptions = @json($catalogClientOptions);
            const lowStockProducts = @json($lowStockProducts);
            const canViewStock = @json($isAdmin);
            const availabilityUrl = @json(route('supplies.issues.availability'));
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const requestCreationAllowed = @json($requestCreationAllowed);
            const requestCreationRestrictionMessage = @json($requestCreationRestrictionMessage);
            let availabilityCheckTimer;
            let availabilityCheckVersion = 0;

            document.getElementById('createSupplyIssueRequestButton')?.addEventListener('click', (event) => {
                if (requestCreationAllowed) {
                    return;
                }

                event.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Envio restringido',
                    text: requestCreationRestrictionMessage,
                    confirmButtonColor: '#bb0000'
                });
            });

            function renderOptionsHtml(matches) {
                return matches.length
                    ? matches.map((product) => `
                        <button type="button" class="product-result-option" data-id="${product.id}" data-label="${product.label}" data-available="${product.available ?? ''}">
                            <span>${product.label}</span>
                            ${canViewStock ? `<span class="product-result-stock">Disp: ${product.available}</span>` : ''}
                        </button>
                    `).join('')
                    : '<div class="product-result-empty">Sin coincidencias</div>';
            }

            function renderClientOptionsHtml(matches) {
                return matches.length
                    ? matches.map((client) => `
                        <button type="button" class="product-result-option client-result-option" data-id="${client.id}" data-label="${client.label}">
                            <span>${client.label}</span>
                        </button>
                    `).join('')
                    : '<div class="product-result-empty">Sin coincidencias</div>';
            }

            function updateRowAvailability(row, available, label = '') {
                const stockContainer = row.querySelector('.stock-badge-container');
                const quantityInput = row.querySelector('.requested-quantity-input');
                const searchInput = row.querySelector('.product-search-input');

                if (!quantityInput) {
                    return;
                }

                if (!canViewStock) {
                    quantityInput.removeAttribute('max');
                    return;
                }

                quantityInput.removeAttribute('max');
                stockContainer.innerHTML = label
                    ? (available > 0
                        ? `<span class="badge bg-success">Disponible: ${available}</span>`
                        : '<span class="badge bg-danger">No hay producto disponible para solicitar</span>')
                    : '<span class="text-muted small">Seleccione un producto</span>';

                if (available === 0 && searchInput) {
                    searchInput.classList.add('is-invalid');
                    searchInput.setAttribute('title', 'No hay producto disponible para solicitar.');
                } else if (searchInput) {
                    searchInput.classList.remove('is-invalid');
                    searchInput.removeAttribute('title');
                }
            }

            function clearAvailabilityFeedback(row) {
                row.querySelector('.requested-quantity-input')?.classList.remove('is-invalid');
                const feedback = row.querySelector('.availability-feedback');
                if (feedback) {
                    feedback.textContent = '';
                }
            }

            function scheduleAvailabilityCheck() {
                window.clearTimeout(availabilityCheckTimer);
                availabilityCheckTimer = window.setTimeout(checkAvailability, 250);
            }

            async function checkAvailability() {
                if (!requestItemsTableBody) {
                    return;
                }

                const rows = Array.from(requestItemsTableBody.querySelectorAll('tr'));
                const productIds = rows.map((row) => row.querySelector('.product-id-input')?.value || '');
                const quantities = rows.map((row) => row.querySelector('.requested-quantity-input')?.value || 0);

                rows.forEach(clearAvailabilityFeedback);

                if (!productIds.some((productId, index) => productId && Number(quantities[index]) > 0)) {
                    return;
                }

                const requestVersion = ++availabilityCheckVersion;

                try {
                    const response = await fetch(availabilityUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken || '',
                        },
                        body: JSON.stringify({
                            product_id: productIds,
                            requested_quantity: quantities,
                        }),
                    });

                    if (!response.ok || requestVersion !== availabilityCheckVersion) {
                        return;
                    }

                    const result = await response.json();
                    rows.forEach((row) => {
                        const productId = row.querySelector('.product-id-input')?.value;
                        const message = result.errors?.[productId];

                        if (!message) {
                            return;
                        }

                        row.querySelector('.requested-quantity-input')?.classList.add('is-invalid');
                        const feedback = row.querySelector('.availability-feedback');
                        if (feedback) {
                            feedback.textContent = message;
                        }
                    });
                } catch (error) {
                    // The final server-side validation remains authoritative if the live check is unavailable.
                }
            }

            function renderProductOptions(row) {
                const searchInput = row.querySelector('.product-search-input');
                const resultsContainer = row.querySelector('.product-search-results');

                if (!searchInput || !resultsContainer) {
                    return;
                }

                const search = searchInput.value.trim().toLowerCase();

                if (!search) {
                    resultsContainer.innerHTML = '';
                    resultsContainer.classList.remove('is-visible');
                    return;
                }

                const matches = catalogOptions.filter((product) => product.search.includes(search));
                resultsContainer.innerHTML = renderOptionsHtml(matches);
                resultsContainer.classList.add('is-visible');
            }

            function buildRowActions(isLastRow, totalRows) {
                const removeDisabled = totalRows === 1 ? 'disabled' : '';

                return `
                    <div class="d-flex justify-content-end gap-2 flex-nowrap">
                        <button type="button" class="btn btn-outline-danger btn-sm remove-request-row" title="Eliminar fila" ${removeDisabled}>
                            <i class="fas fa-trash-alt"></i>
                        </button>
                        ${isLastRow ? `
                            <button type="button" class="btn btn-danger btn-sm add-request-row-inline" title="Agregar nueva fila arriba">
                                <i class="fas fa-plus"></i>
                            </button>
                        ` : ''}
                    </div>
                `;
            }

            function refreshRowActions() {
                const rows = Array.from(requestItemsTableBody.querySelectorAll('tr'));

                rows.forEach((row, index) => {
                    row.querySelector('.request-row-actions').innerHTML = buildRowActions(index === rows.length - 1, rows.length);
                });
            }

            function attachRowListeners(row) {
                const searchInput = row.querySelector('.product-search-input');
                const productIdInput = row.querySelector('.product-id-input');
                const resultsContainer = row.querySelector('.product-search-results');
                const quantityInput = row.querySelector('.requested-quantity-input');

                searchInput?.addEventListener('input', () => {
                    productIdInput.value = '';
                    updateRowAvailability(row, 0);
                    clearAvailabilityFeedback(row);
                    renderProductOptions(row);
                    scheduleAvailabilityCheck();
                });

                searchInput?.addEventListener('focus', () => {
                    if (searchInput.value.trim()) {
                        renderProductOptions(row);
                    }
                });

                quantityInput?.addEventListener('input', () => {
                    const max = Number(quantityInput.max || 0);
                    const current = Number(quantityInput.value || 0);

                    if (canViewStock && max > 0 && current > max) {
                        quantityInput.value = max;
                    }

                    scheduleAvailabilityCheck();
                });

                resultsContainer?.addEventListener('click', (event) => {
                    const option = event.target.closest('.product-result-option');

                    if (!option) {
                        return;
                    }

                    productIdInput.value = option.dataset.id;
                    searchInput.value = option.dataset.label;
                    searchInput.classList.remove('is-invalid');
                    resultsContainer.innerHTML = '';
                    resultsContainer.classList.remove('is-visible');
                    updateRowAvailability(row, Number(option.dataset.available || 0), option.dataset.label);
                    scheduleAvailabilityCheck();
                });
            }

            function addRequestRow() {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        <div class="supply-product-picker">
                            <input type="hidden" class="product-id-input" name="product_id[]" required>
                            <input type="text" class="form-control product-search-input mb-2"
                                placeholder="Escriba nombre o ID para filtrar en tiempo real"
                                autocomplete="off">
                            <div class="product-search-results"></div>
                        </div>
                    </td>
                    <td>
                        <input type="number" min="0" class="form-control requested-quantity-input" name="requested_quantity[]" required>
                        <div class="availability-feedback invalid-feedback d-block"></div>
                    </td>
                    ${canViewStock ? `<td><div class="stock-badge-container text-muted small">Seleccione un producto</div></td>` : ''}
                    <td class="text-end">
                        <div class="request-row-actions"></div>
                    </td>
                `;

                requestItemsTableBody.prepend(row);
                attachRowListeners(row);
                refreshRowActions();
                row.querySelector('.product-search-input')?.focus();
            }

            requestItemsTableBody?.addEventListener('click', (event) => {
                if (event.target.closest('.add-request-row-inline')) {
                    addRequestRow();
                    return;
                }

                const removeButton = event.target.closest('.remove-request-row');
                if (!removeButton) {
                    return;
                }

                const rows = requestItemsTableBody.querySelectorAll('tr');
                if (rows.length === 1) {
                    rows[0].querySelector('.product-search-input').value = '';
                    rows[0].querySelector('.product-id-input').value = '';
                    rows[0].querySelector('.requested-quantity-input').value = '';
                    rows[0].querySelector('.product-search-results').innerHTML = '';
                    rows[0].querySelector('.product-search-results').classList.remove('is-visible');
                    updateRowAvailability(rows[0], 0);
                    clearAvailabilityFeedback(rows[0]);
                    scheduleAvailabilityCheck();
                    refreshRowActions();
                    return;
                }

                removeButton.closest('tr')?.remove();
                refreshRowActions();
                scheduleAvailabilityCheck();
            });

            requestItemsTableBody?.querySelectorAll('tr').forEach((row) => attachRowListeners(row));
            if (requestItemsTableBody) {
                refreshRowActions();
            }

            document.querySelectorAll('.supply-client-picker').forEach((picker) => {
                const searchInput = picker.querySelector('.client-search-input');
                const clientIdInput = picker.querySelector('.client-id-input');
                const resultsContainer = picker.querySelector('.client-search-results');

                function renderClientResults() {
                    const search = searchInput.value.trim().toLowerCase();

                    if (!search) {
                        resultsContainer.innerHTML = '';
                        resultsContainer.classList.remove('is-visible');
                        return;
                    }

                    const matches = clientOptions.filter((client) => client.search.includes(search));
                    resultsContainer.innerHTML = renderClientOptionsHtml(matches);
                    resultsContainer.classList.add('is-visible');
                }

                searchInput?.addEventListener('input', () => {
                    clientIdInput.value = '';
                    renderClientResults();
                });

                searchInput?.addEventListener('focus', renderClientResults);

                resultsContainer?.addEventListener('click', (event) => {
                    const option = event.target.closest('.client-result-option');

                    if (!option) {
                        return;
                    }

                    clientIdInput.value = option.dataset.id;
                    searchInput.value = option.dataset.label;
                    resultsContainer.innerHTML = '';
                    resultsContainer.classList.remove('is-visible');
                    searchInput.classList.remove('is-invalid');
                });
            });

            document.addEventListener('click', (event) => {
                document.querySelectorAll('.supply-product-picker').forEach((picker) => {
                    if (!picker.contains(event.target)) {
                        picker.querySelector('.product-search-results')?.classList.remove('is-visible');
                    }
                });

                document.querySelectorAll('.supply-client-picker').forEach((picker) => {
                    if (!picker.contains(event.target)) {
                        picker.querySelector('.client-search-results')?.classList.remove('is-visible');
                    }
                });
            });

            function bindLiveTableFilters() {
                document.querySelectorAll('.table-live-filter').forEach((input) => {
                    const target = document.querySelector(input.dataset.target);
                    const rows = target?.querySelectorAll('tbody tr');

                    if (!target || !rows?.length) {
                        return;
                    }

                    input.addEventListener('input', () => {
                        const search = input.value.trim().toLowerCase();

                        rows.forEach((row) => {
                            if (row.querySelector('td[colspan]')) {
                                row.style.display = '';
                                return;
                            }

                            row.style.display = !search || row.innerText.toLowerCase().includes(search) ? '' : 'none';
                        });
                    });
                });

                document.querySelectorAll('.table-live-filter-clear').forEach((button) => {
                    button.addEventListener('click', () => {
                        const input = document.querySelector(`.table-live-filter[data-target="${button.dataset.target}"]`);
                        const target = document.querySelector(button.dataset.target);
                        const rows = target?.querySelectorAll('tbody tr');

                        if (input) {
                            input.value = '';
                        }

                        rows?.forEach((row) => {
                            row.style.display = '';
                        });
                    });
                });
            }

            bindLiveTableFilters();

            if (lowStockProducts.length && typeof bootstrap !== 'undefined') {
                const lowStockModalElement = document.getElementById('lowStockAlertModal');
                if (lowStockModalElement) {
                    new bootstrap.Modal(lowStockModalElement).show();
                }
            }
        });
    </script>
    <style>
        .supply-request-modal .modal-content,
        .supply-request-modal .modal-body,
        .supply-request-table-wrap,
        .supply-request-table-wrap .table,
        .supply-request-table-wrap tbody,
        .supply-request-table-wrap tr,
        .supply-request-table-wrap td {
            overflow: visible !important;
        }

        .supply-request-modal .modal-body {
            padding-bottom: 6rem;
        }

        .supply-product-picker,
        .supply-client-picker {
            position: relative;
        }

        .product-search-results,
        .client-search-results {
            display: none;
            position: absolute;
            left: 0;
            right: 0;
            top: calc(100% - 0.5rem);
            z-index: 2056;
            max-height: 240px;
            overflow-y: auto;
            border: 1px solid var(--sup-border);
            border-radius: 0.75rem;
            background: var(--sup-card);
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.14);
            padding: 0.35rem;
        }

        .product-search-results.is-visible,
        .client-search-results.is-visible {
            display: block;
        }

        .product-result-option {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            width: 100%;
            text-align: left;
            border: 0;
            background: transparent;
            padding: 0.65rem 0.75rem;
            border-radius: 0.55rem;
            color: var(--sup-text);
        }

        .product-result-option:hover {
            background: #fee2e2;
            color: #991b1b;
        }

        .product-result-stock {
            font-size: 0.82rem;
            color: var(--sup-muted);
            white-space: nowrap;
        }

        .product-result-empty {
            padding: 0.7rem 0.75rem;
            color: var(--sup-muted);
            font-size: 0.92rem;
        }
    </style>
@endsection
