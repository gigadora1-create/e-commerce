<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperación de contraseña - GLE COLOMBIA</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #003366;
            color: #ffffff;
            text-align: center;
            padding: 20px;
        }
        .content {
            padding: 30px;
        }
        .logo {
            max-width: 200px;
            margin-bottom: 20px;
        }
        h1 {
            color: #003366;
            margin-top: 0;
        }
        .button {
            display: inline-block;
            background-color: #ff6600;
            color: #ffffff;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
        }
        .footer {
            background-color: #f0f0f0;
            text-align: center;
            padding: 20px;
            font-size: 12px;
            color: #666;
        }
        @media only screen and (max-width: 600px) {
            .container {
                width: 100% !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="content">
            <h1>Hola {{ $notifiable->name }},</h1>
            <p>Has solicitado restablecer tu contraseña para tu cuenta en GLE COLOMBIA. Para continuar con el proceso, haz clic en el botón de abajo:</p>
            <p style="text-align: center;">
                <a href="{{ $url }}" class="button">Restablecer Contraseña</a>
            </p>
            <p>Si no has solicitado este cambio, puedes ignorar este correo y tu contraseña permanecerá sin cambios.</p>
            <p>Por razones de seguridad, este enlace de restablecimiento de contraseña caducará en 60 minutos.</p>
            <p>Si tienes alguna pregunta o necesitas ayuda adicional, no dudes en contactar a nuestro equipo de soporte.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} Grupo Logístico Especializado. Todos los derechos reservados.</p>
            <p>Este es un correo electrónico automático, por favor no responda a este mensaje.</p>
        </div>
    </div>
</body>
</html>