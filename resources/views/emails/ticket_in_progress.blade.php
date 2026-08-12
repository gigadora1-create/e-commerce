<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket En Progreso</title>
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
            background-color: #ffc107;
            color: #333;
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
            background-color: #ffc107;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Ticket En Progreso</h1>
        </div>
        
        <div class="content">
            <p>Estimado(a) {{ auth()->user()->name }},</p>
            <p>Nos complace informarte que tu ticket <strong>{{ $ticket->ticket_number }}</strong> ha sido asignado a <strong>{{ $ticket->assigned }}</strong> y está siendo procesado.</p>
            <p>Nuestro equipo ya está trabajando en la resolución de tu solicitud. Te mantendremos informado sobre cualquier actualización o avance favor completar los datos requeridos para dar solucion a su ticket .</p>

            <div class="ticket-info">
                <p><strong>Estado:</strong> <span class="status-badge">En Progreso</span></p>
                <p><strong>Número de Ticket:</strong> {{ $ticket->ticket_number }}</p>
                <p><strong>datos faltantes:</strong> {{ $ticket->observations	 }}</p>
                <p><strong>Título:</strong> {{ $ticket->title }}</p>
                <p><strong>Categoría:</strong> {{ $ticket->categoryRelation->name }}</p>
                <p><strong>Prioridad:</strong> {{ $ticket->priorityRelation->name }}</p>
                <p><strong>Asignado a:</strong> {{ $ticket->assigned }}</p>
                <p><strong>Fecha de Creación:</strong> {{ $ticket->created_at }}</p>
                <p><strong>Fecha de Asignación:</strong> {{ $ticket->updated_at }}</p>
            </div>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} Grupo Logistico Especializado. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>