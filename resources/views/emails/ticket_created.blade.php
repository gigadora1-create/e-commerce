<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Ticket Creado</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #dc3545;
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 20px;
        }
        .ticket-info {
            background-color: #f8f9fa;
            border-radius: 4px;
            padding: 15px;
            margin-top: 20px;
        }
        .ticket-info p {
            margin: 10px 0;
        }
        .footer {
            background-color: #333;
            color: #ffffff;
            text-align: center;
            padding: 10px;
            font-size: 12px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 12px;
            background-color: #dc3545;
            color: #ffffff;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Nuevo Ticket Creado</h1>
        </div>
        
        <div class="content">
            <p>Hola ,</p>
            <p>Nos complace informarte que un nuevo ticket ha sido creado en nuestro sistema. A continuación, los detalles del ticket:</p>

            <div class="ticket-info">
                <p><strong>Estado:</strong> <span class="status-badge">Creado</span></p>
                <p><strong>Número de Ticket:</strong> {{ $ticket->ticket_number }}</p>
                <p><strong>Título:</strong> {{ $ticket->title }}</p>
                <p><strong>Categoría:</strong> {{ $ticket->categoryRelation->name }}</p>
                <p><strong>Prioridad:</strong> {{ $ticket->priorityRelation->name }}</p>
                <p><strong>Creado Por:</strong> {{ auth()->user()->name }}</p>
                <p><strong>Fecha de Creación:</strong> {{ $ticket->created_at }}</p>
                <p><strong>Observaciones:</strong> {{ $ticket->observations }}</p>
            </div>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} Grupo Logistico Especializado. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>