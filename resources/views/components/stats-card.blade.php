<div class="chart-card bg-white rounded-3 shadow-sm overflow-hidden">
    <div class="chart-header p-3 border-bottom bg-light">
        <h5 class="chart-title text-dark fw-bold fs-5 mb-0">
            <i class="fas fa-info-circle me-2"></i>
            Estadísticas Adicionales
        </h5>
    </div>
    <div class="chart-body p-4">
        <div class="stats-grid d-grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
            @foreach($stats as $stat)
                <div class="stat-item d-flex align-items-center gap-3 p-3 rounded bg-light border">
                    <div class="stat-icon rounded bg-{{ $stat['bg'] }} text-white d-flex align-items-center justify-content-center fs-5" style="width: 50px; height: 50px;">
                        <i class="{{ $stat['icon'] }}"></i>
                    </div>
                    <div class="stat-content">
                        <h4 class="fs-4 fw-bold text-dark mb-1">{{ $stat['value'] }}</h4>
                        <p class="text-muted mb-0">{{ $stat['label'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>