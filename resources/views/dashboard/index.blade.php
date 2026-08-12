@extends('layouts.app')
@section('contents')
<style>
:root {
    --text-color: #2d3748; /* Color para modo claro (negro) */
    --text-color-dark: #ffffff; /* Color para modo oscuro (blanco) */
    --card-min-height: 220px; /* Altura mínima uniforme para todas las tarjetas */
}
body.dark-mode .dark-mode-text {
    color: var(--text-color-dark) !important;
}
body:not(.dark-mode) .dark-mode-text {
    color: var(--text-color) !important;
}
body.dark-mode .dark-mode-list {
    color: var(--text-color-dark) !important;
}
body:not(.dark-mode) .dark-mode-list {
    color: var(--text-color) !important;
}
/* Altura mínima uniforme para todas las tarjetas */
.metric-card {
    min-height: var(--card-min-height) !important;
    display: flex !important;
    flex-direction: column !important;
}
.metric-card-body {
    flex: 1 !important;
    display: flex !important;
    flex-direction: column !important;
}
.metric-content {
    flex: 1 !important;
    display: flex !important;
    flex-direction: column !important;
}
.metric-content ul {
    flex: 1 !important;
    overflow-y: auto !important;
    max-height: 120px !important;
}
    .badge.bg-purple {
        background-color: #805ad5 !important;
    }
    .badge.bg-teal {
        background-color: #319795 !important;
    }
    /* Asegurar que los íconos en las badges sean blancos */
    .badge.bg-purple i,
    .badge.bg-teal i {
        color: white !important;
    }
</style>
<div class="dashboard-container container-fluid py-4">
    <!-- Header with Customer Info -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="dashboard-title mb-2">Dashboard E_Commerce</h1>
            <p class="dashboard-subtitle mb-0">Resumen general del sistema de inventarios</p>
        </div>

        <!-- Customer Information Display -->
        @if(!empty($selectedCustomers))
            <div class="selected-customer-container d-flex flex-column flex-sm-row align-items-sm-center gap-2">
                <div class="alert alert-info d-inline-flex align-items-center mb-0 py-2 px-3">
                    <i class="fas fa-user me-2"></i>
                    <strong>{{ count($selectedCustomers) > 1 ? 'Clientes' : 'Cliente' }}: {{ implode(', ', $selectedCustomers) }}</strong>
                </div>
                <a href="{{ route('customer.context.index') }}" class="btn btn-outline-primary btn-sm">
                    Cambiar cliente
                </a>
            </div>
        @endif
    </div>
    <!-- Mensaje si no hay cliente seleccionado -->
    @if(empty($selectedCustomers))
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-warning d-flex align-items-center" role="alert">
                    <i class="fas fa-exclamation-triangle me-3 fs-4"></i>
                    <div>
                        <h5 class="alert-heading mb-2">Cliente no seleccionado</h5>
                        <p class="mb-0">Selecciona un cliente para ver las métricas específicas del dashboard.</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Métricas -->
    <div class="row g-4 mb-5">
        <!-- Entradas por Bodega -->
        <div class="col-xl-3 col-md-6">
            <div class="metric-card metric-card-entries rounded-3 shadow-sm overflow-hidden h-100">
                <div class="metric-card-body p-4">
                    <div class="metric-icon rounded-circle d-flex align-items-center justify-content-center text-white fs-4" style="width: 60px; height: 60px; background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <div class="metric-content">
                        <h3 class="metric-value dark-mode-text fw-bold fs-4 mb-2">Entradas por Bodega</h3>
                        <ul class="list-unstyled dark-mode-list mb-2">
                            @if(!empty($metrics['total_entries_by_warehouse']))
                                @foreach($metrics['total_entries_by_warehouse'] as $warehouse => $quantity)
                                    <li>{{ $warehouse }}: {{ number_format($quantity) }}</li>
                                @endforeach
                            @else
                                <li>Sin entradas registradas</li>
                            @endif
                        </ul>
                        <span class="metric-badge badge bg-success text-white px-2 py-1 rounded-pill fs-6">
                            <i class="fas fa-arrow-up me-1"></i> Últimos 30 días
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Retenciones por Subtipo -->
        <div class="col-xl-3 col-md-6">
            <div class="metric-card metric-card-retentions-substatus rounded-3 shadow-sm overflow-hidden h-100">
                <div class="metric-card-body p-4">
                    <div class="metric-icon rounded-circle d-flex align-items-center justify-content-center text-white fs-4" style="width: 60px; height: 60px; background: linear-gradient(135deg, #718096 0%, #4a5568 100%);">
                        <i class="fas fa-ban"></i>
                    </div>
                    <div class="metric-content">
                        <h3 class="metric-value dark-mode-text fw-bold fs-4 mb-2">Retenciones</h3>
                        <ul class="list-unstyled dark-mode-list mb-2">
                            @if(!empty($metrics['retentions_by_substatus']))
                                @foreach($metrics['retentions_by_substatus'] as $substatus => $quantity)
                                    <li>
                                        {{ ucfirst(strtolower($substatus)) }}: {{ number_format($quantity) }}
                                    </li>
                                @endforeach
                            @else
                                <li>Sin retenciones registradas</li>
                            @endif
                        </ul>
                        <span class="metric-badge badge bg-secondary text-white px-2 py-1 rounded-pill fs-6">
                            <i class="fas fa-ban me-1"></i> Por tipo
                        </span>
                    </div>
                </div>
            </div>
        </div>



        <!-- Salidas por Bodega -->
        <div class="col-xl-3 col-md-6">
            <div class="metric-card metric-card-outputs rounded-3 shadow-sm overflow-hidden h-100">
                <div class="metric-card-body p-4">
                    <div class="metric-icon rounded-circle d-flex align-items-center justify-content-center text-white fs-4" style="width: 60px; height: 60px; background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <div class="metric-content">
                        <h3 class="metric-value dark-mode-text fw-bold fs-4 mb-2">Salidas por Bodega</h3>
                        <ul class="list-unstyled dark-mode-list mb-2">
                            @if(!empty($metrics['total_outputs_by_warehouse']))
                                @foreach($metrics['total_outputs_by_warehouse'] as $warehouse => $quantity)
                                    <li>{{ $warehouse }}: {{ number_format($quantity) }}</li>
                                @endforeach
                            @else
                                <li>Sin salidas registradas</li>
                            @endif
                        </ul>
                        <span class="metric-badge badge bg-warning text-white px-2 py-1 rounded-pill fs-6">
                            <i class="fas fa-arrow-down me-1"></i> Últimos 30 días
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock por Bodega -->
        <div class="col-xl-3 col-md-6">
            <div class="metric-card metric-card-stock rounded-3 shadow-sm overflow-hidden h-100">
                <div class="metric-card-body p-4">
                    <div class="metric-icon rounded-circle d-flex align-items-center justify-content-center text-white fs-4" style="width: 60px; height: 60px; background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="metric-content">
                        <h3 class="metric-value dark-mode-text fw-bold fs-4 mb-2">Stock por Bodega</h3>
                        <ul class="list-unstyled dark-mode-list mb-2">
                            @if(!empty($metrics['total_stock_by_warehouse']))
                                @foreach($metrics['total_stock_by_warehouse'] as $warehouse => $stock)
                                    <li>{{ $warehouse }}: {{ number_format($stock) }}</li>
                                @endforeach
                            @else
                                <li>Sin stock disponible</li>
                            @endif
                        </ul>
                        <span class="metric-badge badge bg-info text-white px-2 py-1 rounded-pill fs-6">
                            <i class="fas fa-warehouse me-1"></i> Total acumulado
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock Bajo (< 1000) -->
        <div class="col-xl-3 col-md-6">
            <div class="metric-card metric-card-low-stock rounded-3 shadow-sm overflow-hidden h-100">
                <div class="metric-card-body p-4">
                    <div class="metric-icon rounded-circle d-flex align-items-center justify-content-center text-white fs-4" style="width: 60px; height: 60px; background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="metric-content">
                        <h3 class="metric-value dark-mode-text fw-bold fs-4 mb-2">Stock Bajo (&lt; 1000)</h3>
                        <ul class="list-unstyled dark-mode-list mb-2">
                            @if($metrics['low_stock_products']->isNotEmpty())
                                @foreach($metrics['low_stock_products'] as $product)
                                    <li>
                                        {{ $product->item_description }}
                                        ({{ $product->warehouse }}@if($product->customer) - {{ $product->customer }}@endif):
                                        {{ number_format($product->stock) }}
                                    </li>
                                @endforeach
                            @else
                                <li>Sin productos con stock bajo</li>
                            @endif
                        </ul>
                        <span class="metric-badge badge bg-danger text-white px-2 py-1 rounded-pill fs-6">
                            <i class="fas fa-exclamation-circle me-1"></i> Por bodega
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Devoluciones -->
        <div class="col-xl-3 col-md-6">
            <div class="metric-card metric-card-returns rounded-3 shadow-sm overflow-hidden h-100">
                <div class="metric-card-body p-4">
                    <div class="metric-icon rounded-circle d-flex align-items-center justify-content-center text-white fs-4" style="width: 60px; height: 60px; background: linear-gradient(135deg, #9f7aea 0%, #805ad5 100%);">
                        <i class="fas fa-undo"></i>
                    </div>
                    <div class="metric-content">
                        <h3 class="metric-value dark-mode-text fw-bold fs-4 mb-2">Devoluciones</h3>
                        <ul class="list-unstyled dark-mode-list mb-2">
                            @if($metrics['returns_by_reason_and_warehouse']->isNotEmpty())
                                @foreach($metrics['returns_by_reason_and_warehouse'] as $return)
                                    <li>
                                        <strong>{{ $return->item_description }}</strong> ({{ $return->warehouse }}): {{ number_format($return->total_quantity) }}
                                    </li>
                                @endforeach
                            @else
                                <li>Sin devoluciones registradas</li>
                            @endif
                        </ul>
                        <span class="metric-badge badge bg-purple text-white px-2 py-1 rounded-pill fs-6">
                            <i class="fas fa-undo me-1"></i> Por producto y bodega
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock por Cliente (solo si hay cliente seleccionado) -->
        @if(!empty($selectedCustomers) && !empty($metrics['total_stock_by_customer']))
        <div class="col-xl-3 col-md-6">
            <div class="metric-card metric-card-customer-stock rounded-3 shadow-sm overflow-hidden h-100">
                <div class="metric-card-body p-4">
                    <div class="metric-icon rounded-circle d-flex align-items-center justify-content-center text-white fs-4" style="width: 60px; height: 60px; background: linear-gradient(135deg, #38b2ac 0%, #319795 100%);">
                        <i class="fas fa-user-tag"></i>
                    </div>
                    <div class="metric-content">
                        <h3 class="metric-value dark-mode-text fw-bold fs-4 mb-2">Stock por Cliente</h3>
                        <ul class="list-unstyled dark-mode-list mb-2">
                            @foreach($metrics['total_stock_by_customer'] as $customer => $stock)
                                <li>{{ $customer }}: {{ number_format($stock) }}</li>
                            @endforeach
                        </ul>
                        <span class="metric-badge badge bg-teal text-white px-2 py-1 rounded-pill fs-6">
                            <i class="fas fa-users me-1"></i> Cliente actual
                        </span>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Gráficos y Top Productos -->
    <div class="row g-4 mb-5">
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-bar me-2"></i>Entradas vs Salidas por Mes
                    </h5>
                </div>
                <div class="card-body">
                    <div id="monthlyChart"></div>
                    @if(empty($monthlyData['months']))
                        <div class="text-center text-muted mt-4">
                            <i class="fas fa-chart-bar fs-1 mb-3"></i>
                            <p>No hay datos disponibles para mostrar en este gráfico</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-xl-4">
           @if($topProducts->isNotEmpty())
                <x-top-products :products="$topProducts" />
            @else
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-trophy me-2"></i>Top Productos
                        </h5>
                    </div>
                    <div class="card-body d-flex align-items-center justify-content-center">
                        <div class="text-center text-muted">
                            <i class="fas fa-box-open fs-1 mb-3"></i>
                            <p>No hay productos disponibles con los filtros aplicados</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Gráfico de Retenciones por Subtipo -->
    <div class="row g-4 mb-5">
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-pie me-2"></i>Retenciones
                    </h5>
                </div>
                <div class="card-body">
                    <div id="retentionsSubstatusChart"></div>
                    @if(empty($metrics['retentions_by_substatus']))
                        <div class="text-center text-muted mt-4">
                            <i class="fas fa-chart-pie fs-1 mb-3"></i>
                            <p>No hay datos disponibles para mostrar en este gráfico</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Distribución y Estadísticas -->
    <div class="row g-4 mb-5">
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-pie me-2"></i>Distribución por Producto en stock
                    </h5>
                </div>
                <div class="card-body">
                    <div id="distributionChart"></div>
                    @if(empty($productDistribution))
                        <div class="text-center text-muted mt-4">
                            <i class="fas fa-chart-pie fs-1 mb-3"></i>
                            <p>No hay datos disponibles para mostrar en este gráfico</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <x-stats-card :stats="[
                [
                    'value' => $metrics['most_sold_product'] ?? 'N/A',
                    'label' => 'Producto con más salidas',
                    'icon' => 'fas fa-box',
                    'bg' => 'success'
                ],
                [
                    'value' => $metrics['most_active_warehouse'] ?? 'N/A',
                    'label' => 'Bodega más movida',
                    'icon' => 'fas fa-warehouse',
                    'bg' => 'info'
                ],
                [
                    'value' => number_format($metrics['total_movements'] ?? 0),
                    'label' => 'Total Movimientos',
                    'icon' => 'fas fa-exchange-alt',
                    'bg' => 'warning'
                ],
                [
                    'value' => number_format($metrics['inactive_products'] ?? 0),
                    'label' => 'Productos Inactivos',
                    'icon' => 'fas fa-pause-circle',
                    'bg' => 'danger'
                ]
            ]" />
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validar datos mensuales
    const monthlyData = @json($monthlyData ?? []);
    const monthlyMonths = Array.isArray(monthlyData.months) ? monthlyData.months : [];
    const monthlyEntries = Array.isArray(monthlyData.entries) ? monthlyData.entries.map(e => parseInt(e) || 0) : [];
    const monthlyOutputs = Array.isArray(monthlyData.outputs) ? monthlyData.outputs.map(o => parseInt(o) || 0) : [];

    // Validar datos de distribución
    const productDistribution = @json($productDistribution ?? []);
    const distributionData = Array.isArray(productDistribution) ? productDistribution.map(item => parseInt(item.total_quantity) || 0) : [];
    const distributionLabels = Array.isArray(productDistribution) ? productDistribution.map(item => item.name || 'Sin nombre') : [];

    // Validar datos de retenciones por subtipo
    const retentionsBySubstatus = @json($metrics['retentions_by_substatus'] ?? []);
    const retentionsSubstatusData = Object.values(retentionsBySubstatus);
    const retentionsSubstatusLabels = Object.keys(retentionsBySubstatus).map(label =>
    label.charAt(0).toUpperCase() + label.slice(1).toLowerCase()
    );

    // Solo crear el gráfico mensual si hay datos
    if (monthlyMonths.length > 0) {
        const monthlyOptions = {
            series: [
                { name: 'Entradas', data: monthlyEntries, color: '#48bb78' },
                { name: 'Salidas', data: monthlyOutputs, color: '#ed8936' }
            ],
            chart: {
                type: 'bar',
                height: 350,
                toolbar: { show: false },
                animations: { enabled: true, easing: 'easeinout', speed: 800 }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    borderRadius: 8,
                    dataLabels: { position: 'top' }
                }
            },
            dataLabels: {
                enabled: true,
                offsetY: -20,
                style: { fontSize: '12px', colors: ['#304758'] },
                formatter: function(val) {
                    return val.toLocaleString();
                }
            },
            stroke: { show: true, width: 2, colors: ['transparent'] },
            xaxis: {
                categories: monthlyMonths,
                labels: { style: { colors: '#6b7280' } }
            },
            yaxis: {
                title: { text: 'Cantidad', style: { color: '#6b7280' } },
                labels: { style: { colors: '#6b7280' } }
            },
            fill: {
                opacity: 1,
                gradient: {
                    shade: 'light',
                    type: 'vertical',
                    opacityFrom: 1,
                    opacityTo: 0.8
                }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val.toLocaleString() + ' unidades';
                    }
                },
                theme: 'light'
            },
            legend: { position: 'top', horizontalAlign: 'right' },
            grid: { borderColor: '#e5e7eb', strokeDashArray: 4 }
        };
        const monthlyChartElement = document.querySelector('#monthlyChart');
        if (monthlyChartElement) {
            const monthlyChart = new ApexCharts(monthlyChartElement, monthlyOptions);
            monthlyChart.render();
        }
    }

    // Solo crear el gráfico de distribución si hay datos
    if (distributionData.length > 0) {
        const distributionOptions = {
            series: distributionData,
            chart: {
                type: 'donut',
                height: 350,
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800
                }
            },
            labels: distributionLabels,
            colors: ['#667eea', '#764ba2', '#48bb78', '#ed8936', '#4299e1', '#9f7aea', '#38b2ac', '#ec4899'],
            plotOptions: {
                pie: {
                    donut: {
                        size: '70%',
                        labels: {
                            show: true,
                            name: {
                                show: true,
                                fontSize: '16px',
                                fontWeight: 600,
                                color: '#374151'
                            },
                            value: {
                                show: true,
                                fontSize: '14px',
                                fontWeight: 400,
                                color: '#6b7280',
                                formatter: function(val) {
                                    return parseInt(val).toLocaleString();
                                }
                            },
                            total: {
                                show: true,
                                showAlways: true,
                                label: 'Total',
                                fontSize: '16px',
                                fontWeight: 600,
                                color: '#374151',
                                formatter: function(w) {
                                    return w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString();
                                }
                            }
                        }
                    }
                }
            },
            dataLabels: { enabled: false },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val.toLocaleString() + ' unidades';
                    }
                }
            },
            legend: { position: 'bottom', horizontalAlign: 'center' }
        };
        const distributionChartElement = document.querySelector('#distributionChart');
        if (distributionChartElement) {
            const distributionChart = new ApexCharts(distributionChartElement, distributionOptions);
            distributionChart.render();
        }
    }

    // Solo crear el gráfico de retenciones por subtipo si hay datos
    if (retentionsSubstatusData.length > 0) {
        const retentionsSubstatusOptions = {
            series: retentionsSubstatusData,
            chart: {
                type: 'donut',
                height: 350,
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800
                }
            },
            labels: retentionsSubstatusLabels,
            colors: ['#f56565', '#48bb78'],
            plotOptions: {
                pie: {
                    donut: {
                        size: '70%',
                        labels: {
                            show: true,
                            name: {
                                show: true,
                                fontSize: '16px',
                                fontWeight: 600,
                                color: '#374151'
                            },
                            value: {
                                show: true,
                                fontSize: '14px',
                                fontWeight: 400,
                                color: '#6b7280',
                                formatter: function(val) {
                                    return parseInt(val).toLocaleString();
                                }
                            },
                            total: {
                                show: true,
                                showAlways: true,
                                label: 'Total Retenciones',
                                fontSize: '16px',
                                fontWeight: 600,
                                color: '#374151',
                                formatter: function(w) {
                                    return w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString();
                                }
                            }
                        }
                    }
                }
            },
            dataLabels: { enabled: false },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val.toLocaleString() + ' unidades';
                    }
                }
            },
            legend: { position: 'bottom', horizontalAlign: 'center' }
        };
        const retentionsSubstatusChartElement = document.querySelector('#retentionsSubstatusChart');
        if (retentionsSubstatusChartElement) {
            const retentionsSubstatusChart = new ApexCharts(retentionsSubstatusChartElement, retentionsSubstatusOptions);
            retentionsSubstatusChart.render();
        }
    }
});
</script>
@endsection
