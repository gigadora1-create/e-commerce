<!DOCTYPE html>
<html>
<head>
    <title>Reporte de Salidas</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 20px;
        }
        body {
            font-family: Arial, sans-serif;
            color: #333;
            line-height: 1.6;
            position: relative;
            padding-bottom: 60px; /* Space for footer */
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            width: 200px;
            margin-bottom: 20px;
        }
        .report-number {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #4a4a4a;
        }
        .report-title {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .report-date {
            font-size: 16px;
            color: #898687;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
            box-shadow: 0 2px 3px rgba(0,0,0,0.1);
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
            color: #333;
            font-weight: bold;
            text-transform: uppercase;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tfoot tr {
            background-color: #e8e8e8;
            font-weight: bold;
        }
        .footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            padding: 10px;
            font-size: 12px;
            color: #e43333;
            border-top: 1px solid #ddd;
        }
        .footer img {
            width: 50px;
            vertical-align: middle;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('images/logogle.png') }}" class="logo" alt="Logo">
        <div class="report-number">Reporte #{{ $reportNumber }}</div>
        <h1 class="report-title">Reporte de Salidas</h1>
        <div class="report-date">{{ $date }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Guía</th>
                <th>Bodega</th>
                <th>Ubicación</th>
                <th>Producto</th>
                <th>Cantidad</th>
            </tr>
        </thead>
        <tbody>
            @foreach($outputs as $output)
            <tr>
                <td>{{ $output->guide }}</td>
                <td>{{ $output->warehouse }}</td>
                <td>{{ $output->localizacion ?? 'N/A' }}</td>
                <td>{{ $output->item_name ?? 'N/A' }}</td>
                <td>{{ $output->quantity }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align: right;"><strong>Total Productos:</strong></td>
                <td>{{ $outputs->sum('quantity') }}</td>
            </tr>
            <tr>
                <td colspan="4" style="text-align: right;"><strong>Total Guías:</strong></td>
                <td>{{ $outputs->unique('guide')->count() }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <img src="{{ public_path('images/logo_obscuro.png') }}" alt="Logo pequeño">
        Grupo Logístico Especializado
    </div>
</body>
</html>