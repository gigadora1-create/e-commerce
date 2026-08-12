@if(session('warehouse_import_summary'))
    @php($importSummary = session('warehouse_import_summary'))
    <div class="warehouse-import-summary alert {{ empty($importSummary['errors']) ? 'alert-success' : 'alert-warning' }} mb-4">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="fw-bold">
                    {{ ($importSummary['type'] ?? 'entries') === 'exits' ? 'Importación de salidas' : 'Importación de ingresos' }}
                </div>
                <div class="small">
                    {{ number_format((int) ($importSummary['processed'] ?? 0)) }} fila(s) procesadas correctamente.
                    @if(!empty($importSummary['errors']))
                        {{ number_format(count($importSummary['errors'])) }} fila(s) con error.
                    @endif
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
        @if(!empty($importSummary['errors']))
            <div class="warehouse-import-summary__errors mt-3">
                @foreach($importSummary['errors'] as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif
    </div>
@endif
