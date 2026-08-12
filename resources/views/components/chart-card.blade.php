<div class="chart-card bg-white rounded-3 shadow-sm overflow-hidden">
    <div class="chart-header p-3 border-bottom bg-light">
        <h5 class="chart-title text-dark fw-bold fs-5 mb-0">
            <i class="{{ $icon }} me-2"></i>
            {{ $title }}
        </h5>
    </div>
    <div class="chart-body p-4">
        <div id="{{ $chartId }}"></div>
    </div>
</div>