@php
    $selectedClientLabel = $analyticsFilters['selected_client']?->name ?? '';
    $chartData = $analytics['chart_data'];
    $summary = $analytics['summary'];
@endphp

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="supplies-toolbar mb-4">
            <div>
                <div class="supplies-section-title"><i class="fas fa-chart-line"></i> Analitica de consumo por cliente</div>
                <p class="text-muted mb-0">Mida solicitudes, unidades, stock comprometido y consumo por cliente en un solo panel.</p>
            </div>
            <a class="btn btn-danger"
                href="{{ route('supplies.analytics.export', [
                    'analytics_client_id' => $analyticsFilters['client_id'],
                    'analytics_from' => $analyticsFilters['from_input'],
                    'analytics_to' => $analyticsFilters['to_input'],
                ]) }}">
                <i class="fas fa-file-excel me-1"></i> Exportar Excel
            </a>
        </div>

        <form method="GET" action="{{ route('supplies.index') }}" class="analytics-filter-panel mb-4">
            <input type="hidden" name="tab" value="analytics">
            <div class="row g-3 align-items-end">
                <div class="col-lg-5">
                    <label class="form-label">Cliente</label>
                    <div class="supply-client-picker analytics-client-picker">
                        <input type="hidden" class="client-id-input" name="analytics_client_id"
                            value="{{ $analyticsFilters['client_id'] }}">
                        <input type="text" class="form-control client-search-input"
                            placeholder="Escriba para buscar cliente"
                            value="{{ $selectedClientLabel }}"
                            autocomplete="off">
                        <div class="client-search-results"></div>
                    </div>
                </div>
                <div class="col-md-3 col-lg-2">
                    <label class="form-label">Desde</label>
                    <input type="date" class="form-control" name="analytics_from" value="{{ $analyticsFilters['from_input'] }}">
                </div>
                <div class="col-md-3 col-lg-2">
                    <label class="form-label">Hasta</label>
                    <input type="date" class="form-control" name="analytics_to" value="{{ $analyticsFilters['to_input'] }}">
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="d-flex gap-2">
                        <button class="btn btn-danger w-100" type="submit">Actualizar</button>
                        <a class="btn btn-outline-secondary w-100" href="{{ route('supplies.index', ['tab' => 'analytics']) }}">Limpiar</a>
                    </div>
                </div>
            </div>
            <div class="analytics-filter-meta mt-3">
                <span class="analytics-filter-chip">Cliente: {{ $analytics['selected_client_name'] }}</span>
                <span class="analytics-filter-chip">Rango: {{ $analyticsFilters['range_label'] }}</span>
            </div>
        </form>

        <div class="analytics-kpi-grid mb-4">
            <div class="analytics-kpi-card">
                <div class="analytics-kpi-title">Solicitudes analizadas</div>
                <div class="analytics-kpi-value">{{ $summary['total_requests'] }}</div>
                <div class="analytics-kpi-note">{{ $summary['closed_requests'] }} cerradas dentro del rango</div>
            </div>
            <div class="analytics-kpi-card">
                <div class="analytics-kpi-title">Unidades entregadas</div>
                <div class="analytics-kpi-value">{{ number_format($summary['delivered_units']) }}</div>
                <div class="analytics-kpi-note">{{ $summary['unique_products'] }} productos distintos consumidos</div>
            </div>
            <div class="analytics-kpi-card">
                <div class="analytics-kpi-title">Pendientes de soporte</div>
                <div class="analytics-kpi-value">{{ $analytics['status_mix']['pending_support'] }}</div>
                <div class="analytics-kpi-note">Entregas que requieren formato firmado</div>
            </div>
            <div class="analytics-kpi-card">
                <div class="analytics-kpi-title">Stock comprometido</div>
                <div class="analytics-kpi-value">{{ number_format($summary['reserved_units']) }}</div>
                <div class="analytics-kpi-note">Unidades retenidas antes de la entrega</div>
            </div>
            <div class="analytics-kpi-card">
                <div class="analytics-kpi-title">Cumplimiento</div>
                <div class="analytics-kpi-value">{{ number_format($summary['fill_rate'], 1) }}%</div>
                <div class="analytics-kpi-note">Entrega real frente a lo solicitado</div>
            </div>
            <div class="analytics-kpi-card">
                <div class="analytics-kpi-title">Producto dominante</div>
                <div class="analytics-kpi-value analytics-kpi-value--compact">{{ $summary['top_product_name'] }}</div>
                <div class="analytics-kpi-note">{{ number_format($summary['top_product_units']) }} unidades movidas</div>
            </div>
        </div>

        <div class="analytics-reading-grid mb-4">
            <div class="analytics-reading-card">
                <div class="analytics-reading-title">Lectura operativa</div>
                <ul class="analytics-reading-list">
                    <li>El cliente filtrado concentra {{ number_format($summary['delivered_units']) }} unidades entregadas.</li>
                    <li>Hay {{ $analytics['status_mix']['preparing'] + $analytics['status_mix']['ready'] }} solicitudes aun activas entre alistamiento y listo.</li>
                    <li>El consumo promedio por solicitud cerrada es de {{ number_format($summary['avg_units_per_request'], 1) }} unidades.</li>
                </ul>
            </div>
            <div class="analytics-reading-card">
                <div class="analytics-reading-title">Uso de recursos</div>
                <ul class="analytics-reading-list">
                    <li>El stock comprometido mide reservas activas que todavia no salen de inventario.</li>
                    <li>La exportacion baja el detalle por solicitud, producto y cantidades entregadas.</li>
                </ul>
            </div>
        </div>

        <div class="analytics-chart-grid mb-4">
            <div class="analytics-chart-card">
                <div class="analytics-card-header">
                    <div>
                        <h5 class="mb-1">Consumo por producto</h5>
                        <p class="text-muted mb-0">Top de referencias entregadas al cliente o conjunto filtrado.</p>
                    </div>
                </div>
                <div class="analytics-chart-wrap">
                    <canvas id="analyticsTopProductsChart"></canvas>
                </div>
            </div>

            <div class="analytics-chart-card">
                <div class="analytics-card-header">
                    <div>
                        <h5 class="mb-1">Tendencia de unidades</h5>
                        <p class="text-muted mb-0">Lectura temporal del consumo confirmado en el periodo.</p>
                    </div>
                </div>
                <div class="analytics-chart-wrap">
                    <canvas id="analyticsTrendChart"></canvas>
                </div>
            </div>

            <div class="analytics-chart-card">
                <div class="analytics-card-header">
                    <div>
                        <h5 class="mb-1">Estado de solicitudes</h5>
                        <p class="text-muted mb-0">Distribucion del flujo operativo por estado.</p>
                    </div>
                </div>
                <div class="analytics-chart-wrap analytics-chart-wrap--compact">
                    <canvas id="analyticsStatusChart"></canvas>
                </div>
            </div>

            <div class="analytics-chart-card">
                <div class="analytics-card-header">
                    <div>
                        <h5 class="mb-1">Comparativo de clientes</h5>
                        <p class="text-muted mb-0">Contexto del cliente elegido frente al resto del periodo.</p>
                    </div>
                </div>
                <div class="analytics-chart-wrap">
                    <canvas id="analyticsLeaderboardChart"></canvas>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-7">
                <div class="analytics-table-card h-100">
                    <div class="analytics-card-header">
                        <div>
                            <h5 class="mb-1">Productos con mayor presion operativa</h5>
                            <p class="text-muted mb-0">Suma de unidades entregadas y unidades aun reservadas.</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Catalogo</th>
                                    <th>Producto</th>
                                    <th>Entregado</th>
                                    <th>Reservado</th>
                                    <th>Presion total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($analytics['insights']['highest_pressure_products'] as $row)
                                    <tr>
                                        <td class="fw-semibold">{{ $row['catalog_number'] }}</td>
                                        <td>{{ $row['product_name'] }}</td>
                                        <td>{{ number_format($row['delivered_units']) }}</td>
                                        <td>{{ number_format($row['reserved_units']) }}</td>
                                        <td>{{ number_format($row['pressure_units']) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="supplies-empty-state">No hay datos suficientes para esta lectura.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="analytics-table-card h-100">
                    <div class="analytics-card-header">
                        <div>
                            <h5 class="mb-1">Usuarios con mayor consumo gestionado</h5>
                            <p class="text-muted mb-0">Solicitantes que mas mueven proveeduria en el rango.</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Unidades</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($analytics['insights']['requesters'] as $row)
                                    <tr>
                                        <td class="fw-semibold">{{ $row['name'] }}</td>
                                        <td>{{ number_format($row['units']) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="supplies-empty-state">No hay cierres de solicitudes en este rango.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    window.suppliesAnalyticsData = @json($chartData);
</script>
