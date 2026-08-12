<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de Verificación</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 24px;
        }

        .content {
            padding: 30px;
            font-size: 16px;
            line-height: 1.5;
        }

        h1 {
            font-size: 26px;
            color: #333;
        }

        p {
            margin: 15px 0;
        }

        .code {
            background-color: #f0f0f0;
            padding: 10px;
            border-radius: 5px;
            display: inline-block;
            font-size: 20px;
            color: #E91E63;
            font-weight: bold;
        }

        .footer {
            background-color: #f4f4f4;
            color: #777;
            padding: 20px;
            text-align: center;
            font-size: 12px;
        }

        .footer p {
            margin: 0;
        }

        img.logo {
            max-width: 150px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            Código de Verificación
        </div>

        <!-- Email Content -->
        <div class="content">
            <h1>Hola, {{ $userName }}!</h1>
            <p>Tu código de verificación es:</p>
            <p class="code">{{ $code }}</p>
            <p>Por favor, ingresa este código en la página de verificación para completar tu inicio de sesión.</p>
            <p>Si no has solicitado este código, por favor ignora este correo.</p>
        </div>

        <!-- Pie de página -->
        <div class="footer">
            <p>© {{ date('Y') }} Grupo Logistico Especializado. Todos los derechos reservados.</p>
        </div>
    </div>
</body>

</html>
