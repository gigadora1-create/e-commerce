
<!DOCTYPE html>
<html>
<head>
    <title>QR Codigos|</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr; /* Dos columnas */
            grid-template-rows: auto; /* Filas automáticas */
            gap: 20px; /* Espacio entre cuadrantes */
            padding: 20px; /* Ajusta el padding según sea necesario */
            box-sizing: border-box;
        }
        .qr-item {
            display: flex;
            align-items: center; /* Centra verticalmente los elementos */
            border: 2px solid #000; /* Bordes más gruesos y en negrilla */
            padding: 20px; /* Espacio interno para cada cuadrante */
            box-sizing: border-box;
        }
        .qr-code {
            width: 150px; /* Tamaño del QR code */
            height: 150px;
        }
        .qr-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            margin-left: 20px; /* Espacio entre el código QR y el texto */
        }
        .qr-name, .qr-message {
            font-size: 16px; /* Aumenta el tamaño de la letra */
            text-align: center; /* Centra el texto horizontalmente */
            font-weight: bold; /* Aplica negrilla */
        }
        .logo {
            width: 400px; /* Ajusta el tamaño del logo */
            height: auto;
            margin-left: auto; /* Empuja el logo hacia la derecha */
        }
        .centered .qr-code {
            margin: 0 auto; /* Centra el QR code */
            width: 170px; /* Aumenta el tamaño del QR code */
            height: 170px;
            border: 2px solid #000; /* Bordes más gruesos alrededor del QR */
            padding: 10px; /* Espacio interno para destacar el borde */
            box-sizing: border-box;
        }
        .centered .qr-code {
            margin: 0 auto; /* Centra el QR code */
            width: 170px; /* Aumenta el tamaño del QR code */
            height: 170px;
            border: 2px solid #000; /* Bordes más gruesos alrededor del QR */
            padding: 10px; /* Espacio interno para destacar el borde */
            box-sizing: border-box;
        }
        .double-qr-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
            justify-content: center;
        }
        .double-qr {
            display: flex;
            justify-content: space-between;
        }
        .double-qr-container .qr-code {
            width: 170px; /* Ajusta el tamaño de los QR codes */
            height: 170px;
            border: 2px solid #000; /* Bordes más gruesos alrededor del QR */
            padding: 0px; /* Espacio interno para destacar el borde */
            box-sizing: border-box;
            margin-top: 30px; /* Baja los QR unos píxeles */
        }
    </style>
</head>
<body>
    <div class="container">
        @foreach ($qrCodes as $index => $qrCode)
            @if ($index == 0 || $index == 1)
                <div class="qr-item">
                    <img src="{{ $qrCode['dataUri'] }}" alt="QR Code" class="qr-code">
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents($logoPath)) }}" alt="Logotipo" class="logo">
                    <div class="qr-info">
                        <div class="qr-name">{{ $qrCode['name'] }}</div>
                        <div class="qr-message">ESTE EQUIPO PERTENECE A GRUPO LOGISTICO ESPECIALIZADO</div>
                    </div>
                </div>
            @elseif ($index == 2 || $index == 3)
                <div class="qr-item double-qr-container">
                    <div>
                        <img src="{{ $qrCode['dataUri'] }}" alt="QR Code" class="qr-code">
                        <img src="{{ $qrCode['dataUri'] }}" alt="QR Code" class="qr-code">
                    </div>
                   
                </div>
            @endif
        @endforeach
    </div>
</body>
</html>
