<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $supplyRequest->request_number }}</title>
    <style>
        @page { margin: 18px 22px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        td, th { border: 1px solid #222; padding: 3px 4px; vertical-align: middle; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .small { font-size: 9px; }
        .title { font-size: 16px; font-weight: bold; line-height: 1.1; }
        .logo-cell img { width: 120px; }
        .header-note { font-size: 8px; font-weight: bold; letter-spacing: 0.3px; }
        .section-label { font-weight: bold; text-transform: uppercase; }
        .catalog-header { font-size: 9px; font-weight: bold; text-align: center; }
        .catalog-name { font-size: 8px; }
        .qty-cell { width: 11%; text-align: center; font-size: 8px; }
        .article-cell { width: 39%; }
        .line-space { height: 24px; }
        .signature-box { height: 90px; vertical-align: top; }
        .responsible-name { margin-top: 34px; font-weight: bold; text-transform: uppercase; }
    </style>
</head>
<body>
    @php
        $productRows = $supplyRequest->items->map(function ($item) {
            return [
                'label' => $item->product?->name ?? 'Producto',
                'quantity' => (int) $item->requested_quantity,
            ];
        })->values();
        $half = (int) ceil(max($productRows->count(), 1) / 2);
        $leftRows = $productRows->slice(0, $half)->values();
        $rightRows = $productRows->slice($half)->values();
        $rowCount = max($leftRows->count(), $rightRows->count(), 18);
        $logoPath = public_path('images/logogle.png');
        $requestDate = optional($supplyRequest->requested_at)->format('d/m/Y');
    @endphp

    <table>
        <tr>
            <td class="logo-cell center" rowspan="2" style="width: 28%;">
                @if (file_exists($logoPath))
                    <img src="{{ $logoPath }}" alt="GLE">
                @else
                    <div class="title">GLE</div>
                @endif
            </td>
            <td class="center" rowspan="2" style="width: 42%;">
                <div class="title">ACTA DE RECIBIDO</div>
                <div class="title">PROVEEDURIA</div>
            </td>
            <td class="center" style="width: 30%;">
                <div class="bold">SOLICITUD DE COMPRA</div>
            </td>
        </tr>
        <tr>
            <td class="center">
                <div class="small">DOCUMENTO PARA GESTION DE COMPRA</div>
            </td>
        </tr>
        <tr>
            <td colspan="3" class="center header-note">FORMATO OPERATIVO DE SOLICITUD</td>
        </tr>
    </table>

    <table style="margin-top: 2px;">
        <tr>
            <td colspan="2" class="section-label">Proceso: Operaciones</td>
            <td rowspan="3" style="width: 34%;">
                <div class="section-label">Consecutivo:</div>
                <div style="margin-top: 8px;">{{ $supplyRequest->request_number }}</div>
            </td>
        </tr>
        <tr>
            <td class="section-label" style="width: 22%;">Nombre cliente:</td>
            <td>{{ $supplyRequest->client?->name ?? 'Sin cliente asignado' }}</td>
        </tr>
        <tr>
            <td class="section-label">Nombre solicitante:</td>
            <td>{{ $supplyRequest->requestedBy?->name ?? 'Sin usuario' }}</td>
        </tr>
        <tr>
            <td class="section-label">Fecha de solicitud:</td>
            <td colspan="2">{{ $requestDate ?: 'Sin fecha' }}</td>
        </tr>
    </table>

    <table style="margin-top: 2px;">
        <tr>
            <td class="catalog-header article-cell">ARTICULO</td>
            <td class="catalog-header qty-cell">CANTIDAD<br>SOLICITADA</td>
            <td class="catalog-header article-cell">ARTICULO</td>
            <td class="catalog-header qty-cell">CANTIDAD<br>SOLICITADA</td>
        </tr>
        @for ($i = 0; $i < $rowCount; $i++)
            @php
                $left = $leftRows[$i] ?? ['label' => '', 'quantity' => ''];
                $right = $rightRows[$i] ?? ['label' => '', 'quantity' => ''];
            @endphp
            <tr>
                <td class="catalog-name">{{ $left['label'] }}</td>
                <td class="qty-cell">{{ $left['quantity'] }}</td>
                <td class="catalog-name">{{ $right['label'] }}</td>
                <td class="qty-cell">{{ $right['quantity'] }}</td>
            </tr>
        @endfor
        <tr>
            <td colspan="4" class="section-label center">Otros</td>
        </tr>
        @for ($line = 0; $line < 4; $line++)
            <tr>
                <td colspan="4" class="line-space">
                    @if ($line === 0)
                        {{ $supplyRequest->request_notes ?: '' }}
                    @endif
                </td>
            </tr>
        @endfor
    </table>

    <table style="margin-top: 2px;">
        <tr>
            <td class="center section-label" style="width: 50%;">Responsable</td>
            <td class="center section-label" style="width: 50%;">Compras</td>
        </tr>
        <tr>
            <td class="signature-box center">
                <div class="bold">Nombre Completo:</div>
                <div class="responsible-name">{{ $supplyRequest->requestedBy?->name ?? 'Sin usuario' }}</div>
            </td>
            <td class="signature-box center">
                <div class="bold">Recibido por:</div>
                <div style="margin-top: 48px;">&nbsp;</div>
            </td>
        </tr>
    </table>
</body>
</html>
