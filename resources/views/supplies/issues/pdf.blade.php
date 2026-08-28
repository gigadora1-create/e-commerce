@php
    $allProducts = $catalogProducts ?? collect();
    $quantities = $issueRequest->items->mapWithKeys(fn ($item) => [$item->supply_product_id => (int) $item->delivered_quantity]);
    $productRows = $allProducts->map(function ($product) use ($quantities) {
        return [
            'label' => $product->name,
            'quantity' => $quantities[$product->id] ?? '',
        ];
    })->values();
    $half = (int) ceil(max($productRows->count(), 1) / 2);
    $leftRows = $productRows->slice(0, $half)->values();
    $rightRows = $productRows->slice($half)->values();
    $rowCount = max($leftRows->count(), $rightRows->count(), 18);
    $fixedArticleArea = 455;
    $adaptiveRows = $rowCount + 3;
    $articleRowHeight = max(8.9, min(13.2, $fixedArticleArea / max($adaptiveRows, 1)));
    $extraRows = max($rowCount - 18, 0);
    $articleFontSize = max(4.9, min(6.0, 4.9 + (($articleRowHeight - 8.9) * 0.38)));
    $qtyFontSize = max(5.2, min(6.2, 5.2 + (($articleRowHeight - 8.9) * 0.24)));
    $articleLineHeight = $rowCount > 22 ? 0.96 : 1.02;
    $logoPath = public_path('images/logogle.png');
    $requestDate = optional($issueRequest->requested_at)->format('d/m/Y');
    $otherNotes = trim(collect([
        $issueRequest->request_notes,
        $issueRequest->admin_notes,
    ])->filter()->implode(' | '));
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $issueRequest->request_number }}</title>
    <style>
        @page { margin: 12px 16px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8.2px; color: #111; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        td, th { border: 1px solid #222; padding: 2px 3px; vertical-align: middle; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .tiny { font-size: 7px; }
        .small { font-size: 8px; }
        .title { font-size: 13.5px; font-weight: bold; line-height: 1.08; }
        .logo-cell img { width: 135px; }
        .header-note { font-size: 7px; font-weight: bold; letter-spacing: 0.2px; }
        .section-label { font-weight: bold; text-transform: uppercase; }
        .catalog-header { font-size: 7px; font-weight: bold; text-align: center; line-height: 1.05; }
        .catalog-name { font-size: {{ $articleFontSize }}px; line-height: {{ $articleLineHeight }}; }
        .qty-cell { width: 10%; text-align: center; font-size: {{ $qtyFontSize }}px; line-height: 1; }
        .article-cell { width: 39%; }
        .article-row td { height: {{ $articleRowHeight }}px; }
        .line-space { height: {{ $articleRowHeight }}px; }
        .signature-box { height: 62px; vertical-align: top; }
        .responsible-name { margin-top: 18px; font-weight: bold; text-transform: uppercase; }
        .emission-cell {
            padding-top: 12px;
            padding-bottom: 10px;
            line-height: 1.25;
        }
        .version-cell {
            padding-top: 4px;
            padding-bottom: 4px;
        }
    </style>
</head>
<body>
    <table>
        <tr>
            <td class="logo-cell center" rowspan="2" style="width: 35%;">
                @if (file_exists($logoPath))
                    <img src="{{ $logoPath }}" alt="GLE">
                @else
                    <div class="title">GLE</div>
                @endif
            </td>
            <td class="center" rowspan="2" style="width: 40%;">
                <div class="title">FORMATO SOLICITUD</div>
                <div class="title">PROVEEDURIA</div>
            </td>
            <td style="width: 25%; padding: 0;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td class="center bold version-cell">VERSION: 05</td>
                    </tr>
                    <tr>
                        <td class="center bold emission-cell">FECHA DE EMISION:<br>30 DE MARZO DE 2026</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr></tr>
        <tr>
            <td colspan="3" class="center header-note">DOCUMENTO CONTROLADO</td>
        </tr>
    </table>

    <table style="margin-top: 2px;">
        <tr>
            <td colspan="2" class="section-label">Proceso: Operaciones</td>
            <td rowspan="3" style="width: 34%;">
                <div class="section-label">Consecutivo:</div>
                <div style="margin-top: 4px;">{{ $issueRequest->request_number }}</div>
            </td>
        </tr>
        <tr>
            <td class="section-label" style="width: 22%;">Nombre cliente:</td>
            <td>{{ $issueRequest->client?->name ?? 'Sin cliente asignado' }}</td>
        </tr>
        <tr>
            <td class="section-label">Nombre solicitante:</td>
            <td>{{ $issueRequest->requestedBy?->name ?? 'Sin usuario' }}</td>
        </tr>
        <tr>
            <td class="section-label">Fecha de solicitud:</td>
            <td colspan="2">{{ $requestDate ?: 'Sin fecha' }}</td>
        </tr>
    </table>

    <table style="margin-top: 2px;">
        <tr>
            <td class="catalog-header article-cell">ARTICULO</td>
            <td class="catalog-header qty-cell">CANTIDAD<br>ENTREGADA</td>
            <td class="catalog-header article-cell">ARTICULO</td>
            <td class="catalog-header qty-cell">CANTIDAD<br>ENTREGADA</td>
        </tr>
        @for ($i = 0; $i < $rowCount; $i++)
            @php
                $left = $leftRows[$i] ?? ['label' => '', 'quantity' => ''];
                $right = $rightRows[$i] ?? ['label' => '', 'quantity' => ''];
            @endphp
            <tr class="article-row">
                <td class="catalog-name">{{ $left['label'] }}</td>
                <td class="qty-cell">{{ $left['quantity'] }}</td>
                <td class="catalog-name">{{ $right['label'] }}</td>
                <td class="qty-cell">{{ $right['quantity'] }}</td>
            </tr>
        @endfor
        <tr>
            <td colspan="4" class="section-label center">Otros</td>
        </tr>
        @for ($line = 0; $line < 3; $line++)
            <tr>
                <td colspan="4" class="line-space">
                    @if ($line === 0)
                        {{ $otherNotes }}
                    @endif
                </td>
            </tr>
        @endfor
    </table>

    <table style="margin-top: 2px;">
        <tr>
            <td class="center section-label" style="width: 50%;">Responsable</td>
            <td class="center section-label" style="width: 50%;">Cliente</td>
        </tr>
        <tr>
            <td class="signature-box center">
                <div class="bold">Nombre Completo:</div>
                <div class="responsible-name">{{ $issueRequest->requestedBy?->name ?? 'Sin usuario' }}</div>
            </td>
            <td class="signature-box center">
                <div class="bold">Firma o sello:</div>
                <div style="margin-top: 40px;">&nbsp;</div>
            </td>
        </tr>
    </table>
</body>
</html>
