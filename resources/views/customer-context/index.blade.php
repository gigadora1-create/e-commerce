<x-layouts.auth>
    <div class="d-flex flex-column gap-4">
        <div class="text-center">
            <div class="badge bg-primary-subtle text-primary-emphasis px-3 py-2 rounded-pill mb-3">
                Contexto de cliente
            </div>
            <h1 class="h3 mb-2">Selecciona los clientes de trabajo</h1>
            <p class="text-muted mb-0">
                Puedes elegir uno o más clientes. Esta selección se usa en el dashboard, inventarios, ubicaciones y bodega.
            </p>
        </div>

        @if(session('selected_customers') && count(session('selected_customers')) > 0)
            <div class="alert alert-info d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-0">
                <div>
                    <div class="fw-semibold">Clientes activos</div>
                    <div class="d-flex flex-wrap gap-1 mt-1">
                        @foreach(session('selected_customers') as $customer)
                            <span class="badge bg-primary">{{ $customer }}</span>
                        @endforeach
                    </div>
                </div>
                <form action="{{ route('customer.context.clear') }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary">
                        Limpiar selección
                    </button>
                </form>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger mb-0">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('customer.context.store') }}" id="customerContextForm">
            @csrf

            <div class="mb-3">
                <label for="customerSearch" class="form-label fw-semibold">Buscar cliente</label>
                <input
                    type="search"
                    class="form-control form-control-lg"
                    id="customerSearch"
                    name="search"
                    placeholder="Escribe el nombre del cliente"
                    value="{{ $search }}"
                    autocomplete="off"
                >
            </div>

            <div class="row g-3" id="customerList">
                @php
                    $sessionCustomers = session('selected_customers', []);
                    $sessionCustomersUpper = array_map('mb_strtoupper', $sessionCustomers);
                @endphp
                @forelse($customers as $customer)
                    @php
                        $isSelected = in_array(mb_strtoupper($customer->name), $sessionCustomersUpper);
                    @endphp
                    <div class="col-12 customer-card-wrapper" data-customer-card data-customer-name="{{ mb_strtolower($customer->name) }}">
                        <label class="card border-0 shadow-sm h-100 customer-card {{ $isSelected ? 'border-primary bg-primary-subtle' : '' }}" style="cursor: pointer;">
                            <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                                <div class="d-flex align-items-center gap-3">
                                    <input
                                        class="form-check-input mt-0"
                                        type="checkbox"
                                        name="customers[]"
                                        value="{{ $customer->name }}"
                                        @checked($isSelected)
                                        style="width: 1.5rem; height: 1.5rem;"
                                    >
                                    <div>
                                        <div class="d-flex align-items-center gap-2">
                                            <h2 class="h5 mb-0">{{ $customer->name }}</h2>
                                            @if($isSelected)
                                                <span class="badge bg-success">Seleccionado</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="text-md-end">
                                    <span class="text-muted small">Click para seleccionar</span>
                                </div>
                            </div>
                        </label>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-secondary mb-0">
                            No hay clientes disponibles para tu usuario.
                        </div>
                    </div>
                @endforelse
            </div>

            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-center gap-3 mt-4">
                <a href="{{ route('logout') }}" class="btn btn-outline-secondary">
                    Cerrar sesión
                </a>

                <button type="submit" class="btn btn-primary btn-lg" id="submitBtn" @disabled($customers->isEmpty())>
                    Continuar con la selección
                </button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('customerSearch');
            const customerCards = Array.from(document.querySelectorAll('[data-customer-card]'));
            const form = document.getElementById('customerContextForm');
            const submitBtn = document.getElementById('submitBtn');

            // Búsqueda en tiempo real
            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    const query = this.value.trim().toLowerCase();
                    customerCards.forEach((card) => {
                        const name = card.dataset.customerName || '';
                        card.style.display = name.includes(query) ? '' : 'none';
                    });
                });
            }

            // Efecto visual al marcar/desmarcar
            const checkboxes = document.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const card = this.closest('.card');
                    if (this.checked) {
                        card.classList.add('border-primary', 'bg-primary-subtle');
                    } else {
                        card.classList.remove('border-primary', 'bg-primary-subtle');
                    }
                    
                    // Actualizar estado del botón continuar
                    const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
                    submitBtn.disabled = !anyChecked;
                });
            });
        });
    </script>
</x-layouts.auth>
