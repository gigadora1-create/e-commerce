<div class="metric-card {{ $class }} rounded-3 shadow-sm overflow-hidden">
    <div class="metric-card-body p-4 d-flex align-items-center gap-3">
        <div class="metric-icon rounded-circle d-flex align-items-center justify-content-center text-white fs-4" style="width: 60px; height: 60px; background: {{ $iconBg }};">
            <i class="{{ $icon }}"></i>
        </div>
        <div class="metric-content">
            <h3 class="metric-value text-dark fw-bold fs-1 mb-2">{{ $value }}</h3>
            <p class="metric-label text-muted mb-2">{{ $label }}</p>
            <span class="metric-badge badge bg-{{ $badgeColor }} text-white px-2 py-1 rounded-pill fs-6">
                <i class="{{ $badgeIcon }} me-1"></i> {{ $badgeText }}
            </span>
        </div>
    </div>
</div>