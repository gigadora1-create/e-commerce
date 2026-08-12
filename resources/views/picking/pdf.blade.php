<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Alistamiento - {{ $pickingOrder->picking_code }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 100px 25px 70px 25px;
        }

        body {
            font-family: Arial, sans-serif;
            color: #333;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }


        header {
            position: fixed;
            top: -80px;
            left: 0;
            right: 0;
            height: 80px;
            text-align: center;
            border-bottom: 1px solid #ddd;
            background-color: #f9f9f9;
            padding-top: 5px;
        }

        .header-title {
            font-size: 16px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 4px;
        }

        .header-details {
            font-size: 11px;
            color: #555;
        }


        main {
            margin-top: 10px;
            margin-bottom: 20px;
        }

        .order-section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .order-title {
            font-size: 14px;
            font-weight: bold;
            color: #2c3e50;
            border-bottom: 1px solid #ccc;
            margin-bottom: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 5px;
        }

        th {
            background-color: #f0f0f0;
            text-transform: uppercase;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }


        footer {
            position: fixed;
            bottom: -60px;
            left: 0;
            right: 0;
            height: 50px;
            border-top: 1px solid #ddd;
            background-color: #f9f9f9;
            text-align: center;
            font-size: 9px;
            color: #666;
            line-height: 1.4;
            padding-top: 5px;
        }

        .footer-text {
            margin: 0;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-title">Alistamiento #{{ $pickingOrder->picking_code }}</div>
        <div class="header-details">
            Bodega: {{ $pickingOrder->warehouse }} |
            Cliente: {{ $pickingOrder->customer }} |
            Fecha: {{ \Carbon\Carbon::parse($pickingOrder->created_at)->format('d/m/Y') }}
        </div>
    </header>

    <footer>
        <p class="footer-text">
            Grupo Logístico Especializado<br>
            Fecha de generación: {{ \Carbon\Carbon::now()->format('d/m/Y') }} |
            Página <span class="page-number"></span>
        </p>
    </footer>

    <main>
        @php
            $grouped = $pickingOrder->details->groupBy('order_number');
        @endphp

        @foreach($grouped as $orderNumber => $details)
            <div class="order-section">
                <h2 class="order-title">Pedido: {{ $orderNumber ?? 'Sin Número' }}</h2>
                <table>
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Producto</th>
                            <th>Ubicación</th>
                            <th>Cantidad</th>
                            <th>Lote</th>
                            <th>Vencimiento</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($details as $detail)
                            <tr>
                                <td>{{ $detail->sku }}</td>
                                <td>{{ $detail->item_description }}</td>
                                <td>{{ $detail->location_code }} - {{ $detail->location_name ?? 'N/A' }}</td>
                                <td style="text-align: center;">{{ $detail->quantity_picked }}</td>
                                <td>{{ $detail->batch ?? 'N/A' }}</td>
                                <td>{{ $detail->expiry_date ? \Carbon\Carbon::parse($detail->expiry_date)->format('d/m/Y') : 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align: right; font-weight: bold;">Total Pedido:</td>
                            <td style="text-align: center; font-weight: bold;">{{ $details->sum('quantity_picked') }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endforeach
    </main>

    <script type="text/php">
        if (isset($pdf)) {
            $pdf->page_script('
                $font = $fontMetrics->get_font("Arial, Helvetica, sans-serif", "normal");
                $pdf->text(510, 820, "Página $PAGE_NUM de $PAGE_COUNT", $font, 8);
            ');
        }
    </script>
</body>
</html>
