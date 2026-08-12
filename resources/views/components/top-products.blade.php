<div class="chart-card bg-white rounded-3 shadow-sm overflow-hidden">
    <div class="chart-header p-3 border-bottom bg-light">
        <h5 class="chart-title text-dark fw-bold fs-5 mb-0">
            <i class="fas fa-trophy me-2"></i>
            Top 5 Productos
        </h5>
    </div>
    <div class="chart-body p-4">
        <div class="top-products-list d-flex flex-column gap-3">
            @foreach($products as $index => $product)
                <div class="top-product-item d-flex align-items-center gap-3 p-3 rounded bg-light border">
                    <div class="product-rank rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">
                        {{ $index + 1 }}
                    </div>
                    <div class="product-info">
                        <span class="product-name fw-bold text-dark">{{ $product->name }}</span>
                        <span class="product-quantity text-muted">{{ number_format($product->total_movements) }} unidades</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>