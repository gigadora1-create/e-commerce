<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $supplyRequest->request_number }}</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; color: #111827; line-height: 1.5;">
    <p>Se creó un pedido de proveeduría. Enviar lo solicitado para su gestión de compra.</p>

    <p>
        <strong>Solicitud:</strong> {{ $supplyRequest->request_number }}<br>
        <strong>Fecha:</strong> {{ optional($supplyRequest->requested_at)->format('Y-m-d H:i') }}<br>
        <strong>Solicitado por:</strong> {{ $supplyRequest->requestedBy?->name ?? 'Sin usuario' }}<br>
        <strong>Cliente:</strong> {{ $supplyRequest->client?->name ?? 'Sin cliente asignado' }}
    </p>

    @if ($supplyRequest->request_notes)
        <p><strong>Observaciones:</strong> {{ $supplyRequest->request_notes }}</p>
    @endif

    <p><strong>Listado requerido:</strong></p>
    <ul>
        @foreach ($supplyRequest->items as $item)
            <li>{{ $item->product?->name ?? 'Producto' }}: {{ $item->requested_quantity }}</li>
        @endforeach
    </ul>

    <p>Se adjunta el PDF con el detalle de lo solicitado.</p>
</body>
</html>
