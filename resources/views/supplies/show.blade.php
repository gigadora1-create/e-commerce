@extends('layouts.app')

@push('styles')
    @include('supplies.partials.theme-styles')
@endpush

@section('contents')
    <div class="container-fluid py-4 supplies-shell">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div class="supplies-page-header">
                <a href="{{ route('supplies.index') }}" class="text-decoration-none small text-muted">Volver a proveeduria</a>
                <h1 class="h3 mb-1">{{ $supplyRequest->request_number }}</h1>
                <p class="text-muted mb-0">Audite lo recibido contra la solicitud original y genere el soporte en PDF.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if ($supplyRequest->audited_at)
                    <a class="btn btn-danger" href="{{ route('supplies.requests.pdf', $supplyRequest) }}">
                        <i class="fas fa-file-pdf me-1"></i> Descargar PDF
                    </a>
                @endif
                <a class="btn btn-outline-secondary" href="{{ route('supplies.index') }}">Cerrar</a>
            </div>
        </div>

        @include('supplies.partials.alerts')

        <div class="row g-4 mb-4">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1">Estado</div>
                        <div class="mb-3">
                            <span class="badge bg-{{ $supplyRequest->status_color }}">{{ $supplyRequest->status_label }}</span>
                        </div>
                        <div class="text-muted small">Solicitado por</div>
                        <div class="fw-semibold mb-3">{{ $supplyRequest->requestedBy?->name ?? 'Sin usuario' }}</div>
                        <div class="text-muted small">Cliente</div>
                        <div class="fw-semibold mb-3">{{ $supplyRequest->client?->name ?? 'Sin cliente' }}</div>
                        <div class="text-muted small">Fecha solicitud</div>
                        <div class="mb-3">{{ optional($supplyRequest->requested_at)->format('Y-m-d H:i') }}</div>
                        <div class="text-muted small">Auditado por</div>
                        <div class="mb-3">{{ $supplyRequest->auditedBy?->name ?? 'Sin auditoria' }}</div>
                        <div class="text-muted small">Fecha auditoria</div>
                        <div>{{ optional($supplyRequest->audited_at)->format('Y-m-d H:i') ?: 'Pendiente' }}</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small mb-1">Total solicitado</div>
                                    <div class="display-6 fw-bold">{{ $supplyRequest->total_requested }}</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small mb-1">Total recibido</div>
                                    <div class="display-6 fw-bold">{{ $supplyRequest->total_received }}</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small mb-1">Total faltante</div>
                                    <div class="display-6 fw-bold">{{ $supplyRequest->total_missing }}</div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="text-muted small">Notas de solicitud</div>
                                <div>{{ $supplyRequest->request_notes ?: 'Sin notas registradas.' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Notas de auditoria</div>
                                <div>{{ $supplyRequest->audit_notes ?: 'Sin notas registradas.' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="supplies-section-title"><i class="fas fa-list-check"></i> Detalle solicitado</div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>ID catalogo</th>
                                <th>Producto</th>
                                <th>Solicitado</th>
                                <th>Recibido</th>
                                <th>Faltante</th>
                                <th>Observacion</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($supplyRequest->items as $item)
                                <tr>
                                    <td class="fw-semibold">{{ $item->product->catalog_number }}</td>
                                    <td>{{ $item->product->name }}</td>
                                    <td>{{ $item->requested_quantity }}</td>
                                    <td>{{ $item->received_quantity }}</td>
                                    <td>{{ $item->missing_quantity }}</td>
                                    <td>{{ $item->observation ?: 'Sin observacion' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <div class="supplies-section-title"><i class="fas fa-clipboard-check"></i> Auditoria de recibido</div>
                        <p class="text-muted mb-0">Confirme cantidades, faltantes y deje el acta lista para firma manual en fisico.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('supplies.requests.audit', $supplyRequest) }}" id="auditForm">
                    @csrf
                    @method('PUT')

                    <div class="table-responsive mb-4">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th style="width: 140px;">Solicitado</th>
                                    <th style="width: 160px;">Recibido</th>
                                    <th>Observacion</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($supplyRequest->items as $item)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $item->product->catalog_number }} - {{ $item->product->name }}</div>
                                        </td>
                                        <td>{{ $item->requested_quantity }}</td>
                                        <td>
                                            <input type="number"
                                                class="form-control"
                                                min="0"
                                                max="{{ $item->requested_quantity }}"
                                                name="received_quantity[{{ $item->id }}]"
                                                value="{{ old("received_quantity.{$item->id}", $item->received_quantity) }}"
                                                required>
                                        </td>
                                        <td>
                                            <input type="text"
                                                class="form-control"
                                                name="observation[{{ $item->id }}]"
                                                value="{{ old("observation.{$item->id}", $item->observation) }}"
                                                placeholder="Opcional">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="row g-4">
                        <div class="col-lg-6">
                            <label class="form-label">Quien recibe</label>
                            <input type="text" class="form-control mb-3" name="received_by_name"
                                value="{{ old('received_by_name', $supplyRequest->received_by_name) }}" required>
                            <div class="border rounded p-3 bg-light audit-signature-placeholder">
                                <div class="small text-muted mb-2">Espacio reservado para firma manual de quien recibe en el acta impresa.</div>
                                <div class="signature-line-placeholder"></div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label">Quien entrega</label>
                            <input type="text" class="form-control mb-3" name="delivered_by_name"
                                value="{{ old('delivered_by_name', $supplyRequest->delivered_by_name) }}" required>
                            <div class="border rounded p-3 bg-light audit-signature-placeholder">
                                <div class="small text-muted mb-2">Espacio reservado para firma manual de quien entrega en el acta impresa.</div>
                                <div class="signature-line-placeholder"></div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="form-label">Notas generales de auditoria</label>
                        <textarea class="form-control" name="audit_notes" rows="3"
                            placeholder="Explique faltantes, conformidad o novedades generales">{{ old('audit_notes', $supplyRequest->audit_notes) }}</textarea>
                    </div>

                    <div class="mt-4 d-flex flex-wrap gap-2">
                        <button class="btn btn-danger" type="submit">Guardar auditoria</button>
                        @if ($supplyRequest->audited_at)
                            <a class="btn btn-outline-danger" href="{{ route('supplies.requests.pdf', $supplyRequest) }}">
                                Generar PDF
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <style>
        .audit-signature-placeholder {
            min-height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .signature-line-placeholder {
            width: 100%;
            border-bottom: 2px dashed #cbd5e1;
            height: 56px;
        }
    </style>
@endsection
