@extends('layouts.app')

@push('styles')
    @include('supplies.partials.theme-styles')
@endpush

@section('contents')
    <div class="container-fluid py-4 supplies-shell">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div class="supplies-page-header">
                <a href="{{ route('supplies.issues.index') }}" class="text-decoration-none small text-muted">Volver a solicitudes de salida</a>
                <h1 class="h3 mb-1">{{ $issueRequest->request_number }}</h1>
                <p class="text-muted mb-0">Reserva de stock, alistamiento, retiro y cierre definitivo.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if ($isAdmin || $issueRequest->status === \App\Models\SupplyIssueRequest::STATUS_CLOSED)
                    <a class="btn btn-outline-danger" href="{{ route('supplies.issues.pdf', $issueRequest) }}">
                        <i class="fas fa-file-pdf me-1"></i> PDF
                    </a>
                @endif
                <a class="btn btn-outline-secondary" href="{{ route('supplies.issues.index') }}">Cerrar</a>
            </div>
        </div>

        @include('supplies.partials.alerts')

        <div class="row g-4 mb-4">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1">Estado</div>
                        <div class="mb-3">
                            <span class="badge bg-{{ $issueRequest->status_color }}">{{ $issueRequest->status_label }}</span>
                        </div>
                        <div class="text-muted small">Solicitado por</div>
                        <div class="fw-semibold mb-3">{{ $issueRequest->requestedBy?->name ?? 'Sin usuario' }}</div>
                        <div class="text-muted small">Cliente</div>
                        <div class="fw-semibold mb-3">{{ $issueRequest->client?->name ?? 'Sin cliente' }}</div>
                        <div class="text-muted small">Fecha solicitud</div>
                        <div class="mb-3">{{ optional($issueRequest->requested_at)->format('Y-m-d H:i') }}</div>
                        <div class="text-muted small">Listo por</div>
                        <div class="mb-3">{{ $issueRequest->preparedBy?->name ?? 'Pendiente' }}</div>
                        <div class="text-muted small">Cerrado por</div>
                        <div>{{ $issueRequest->closedBy?->name ?? 'Pendiente' }}</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small mb-1">Items</div>
                                    <div class="display-6 fw-bold">{{ $issueRequest->items->count() }}</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small mb-1">Fecha listo</div>
                                    <div class="fw-semibold">{{ optional($issueRequest->ready_at)->format('Y-m-d H:i') ?: 'Pendiente' }}</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small mb-1">Fecha cierre</div>
                                    <div class="fw-semibold">{{ optional($issueRequest->closed_at)->format('Y-m-d H:i') ?: 'Pendiente' }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="text-muted small">Notas del usuario</div>
                        <div class="mb-3">{{ $issueRequest->request_notes ?: 'Sin notas registradas.' }}</div>
                        <div class="text-muted small">Notas administrativas</div>
                        <div>{{ $issueRequest->admin_notes ?: 'Sin notas registradas.' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="supplies-section-title"><i class="fas fa-layer-group"></i> Detalle reservado</div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Producto</th>
                                <th>Solicitado</th>
                                <th>Reservado</th>
                                <th>Entregado</th>
                                @if ($isAdmin)
                                    <th>Stock al solicitar</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($issueRequest->items as $item)
                                <tr>
                                    <td class="fw-semibold">{{ $item->product->catalog_number }}</td>
                                    <td>{{ $item->product->name }}</td>
                                    <td>{{ $item->requested_quantity }}</td>
                                    <td>{{ $item->reserved_quantity }}</td>
                                    <td>{{ $item->delivered_quantity }}</td>
                                    @if ($isAdmin)
                                        <td>{{ $item->available_quantity_at_request }}</td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if ($isAdmin)
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="supplies-section-title"><i class="fas fa-shield-halved"></i> Gestion administrativa</div>
                    <div class="d-flex flex-wrap gap-2">
                        @if ($issueRequest->status === \App\Models\SupplyIssueRequest::STATUS_PREPARING)
                            <form method="POST" action="{{ route('supplies.issues.ready', $issueRequest) }}">
                                @csrf
                                @method('PUT')
                                <button class="btn btn-info text-white" type="submit">Marcar listo para recoger</button>
                            </form>
                        @endif

                        @if (in_array($issueRequest->status, [\App\Models\SupplyIssueRequest::STATUS_PREPARING, \App\Models\SupplyIssueRequest::STATUS_READY], true))
                            <form method="POST" action="{{ route('supplies.issues.reject', $issueRequest) }}">
                                @csrf
                                @method('PUT')
                                <button class="btn btn-outline-danger" type="submit">Rechazar solicitud</button>
                            </form>
                        @endif

                        @if (in_array($issueRequest->status, [\App\Models\SupplyIssueRequest::STATUS_PREPARING, \App\Models\SupplyIssueRequest::STATUS_READY], true))
                            <form method="POST" action="{{ route('supplies.issues.close', $issueRequest) }}">
                                @csrf
                                @method('PUT')
                                <div class="table-responsive mb-3">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Producto</th>
                                                <th>Reservado</th>
                                                <th style="width: 180px;">Entregar ahora</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($issueRequest->items as $item)
                                                <tr>
                                                    <td>{{ $item->product->catalog_number }} - {{ $item->product->name }}</td>
                                                    <td>{{ $item->reserved_quantity }}</td>
                                                    <td>
                                                        <input type="number" class="form-control form-control-sm"
                                                            name="delivered_quantity[{{ $item->id }}]"
                                                            min="0" max="{{ $item->reserved_quantity }}"
                                                            value="{{ old("delivered_quantity.{$item->id}", $item->reserved_quantity) }}" required>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <textarea class="form-control mb-3" name="admin_notes" rows="2"
                                    placeholder="Observacion de entrega parcial o cierre">{{ old('admin_notes', $issueRequest->admin_notes) }}</textarea>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" value="1" name="support_received" id="supportReceived">
                                    <label class="form-check-label" for="supportReceived">
                                        El cliente entrego el soporte firmado
                                    </label>
                                </div>
                                <button class="btn btn-success" type="submit">Registrar entrega y descontar stock</button>
                            </form>
                        @endif

                        @if ($issueRequest->status === \App\Models\SupplyIssueRequest::STATUS_PENDING_SUPPORT)
                            <div class="border rounded p-3">
                                <div class="fw-semibold mb-1">Cierre pendiente soporte</div>
                                <p class="text-muted small mb-3">La salida ya fue descontada. Falta confirmar que el cliente entrego el formato firmado.</p>
                                <form method="POST" action="{{ route('supplies.issues.confirm-support', $issueRequest) }}">
                                    @csrf
                                    @method('PUT')
                                    <textarea class="form-control mb-3" name="admin_notes" rows="2"
                                        placeholder="Observacion al recibir el soporte firmado"></textarea>
                                    <button class="btn btn-success" type="submit">Confirmar soporte y cerrar solicitud</button>
                                </form>
                            </div>
                        @endif
                    </div>
                    <div class="text-muted small mt-3">
                        El stock queda reservado desde la creacion. Al registrar la entrega se descuenta del inventario; sin soporte firmado, la solicitud permanece pendiente de cierre.
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
