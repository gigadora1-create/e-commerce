@extends('layouts.app')

@section('title', '')

@section('contents')
    <a id="scroll-to-bottom" class="scroll-to-top rounded" href="#page-bottom"
        style="position: fixed; bottom: 70px !important; right: 17px !important; display: none; background-color: #4caf50; color: white; z-index: 1000; border-radius: 50%; padding: 10px 15px; box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);">
        <i class="fas fa-angle-down"></i>
    </a>


    <style>
        .scroll-to-top {
            position: fixed;
            bottom: 20px;
            right: 70px;
            /* Movido 50px hacia la izquierda */
            display: none;
            background-color: #4caf50;
            /* Verde de éxito */
            color: white;
            border: none;
            border-radius: 50%;
            padding: 10px 15px;
            cursor: pointer;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .scroll-to-top:hover {
            background-color: #45a049;
            /* Verde más oscuro en hover */
        }

        .scroll-to-top i {
            font-size: 18px;
        }

        /* Otros estilos */
        .upload-text {
            font-size: 16px;
            color: #28a745;
        }

        .upload-file-name {
            display: block;
            margin-top: 10px;
            font-size: 14px;
        }

        .dropzone {
            background-color: #e9f9e9;
            border-color: #28a745;
        }

        .btn-block {
            width: 100%;
        }

        .btn-primary {
            background-color: #28a745;
            border-color: #28a745;
        }

        .btn-primary:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }

        .card {
            background-color: #ffffff !important;
            border: 1px solid #000000 !important;
        }

        .upload-area {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            border: 2px dashed #ccc;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s ease-in-out;
        }

        .upload-area:hover {
            background-color: #f5f5f5;
        }

        .upload-button {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            transition: background-color 0.2s ease-in-out;
        }

        .upload-button:hover {
            background-color: rgba(0, 0, 0, 0.1);
        }

        .upload-text {
            font-weight: bold;
            margin-right: 1rem;
        }

        .upload-input {
            opacity: 0;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .upload-progress {
            margin-top: 1rem;
            width: 100%;
            height: 4px;
            background-color: #ccc;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background-color: #007bff;
            transition: width 0.2s ease-in-out;
        }

        .upload-file-name {
            margin-top: 1rem;
            font-size: 0.8rem;
            color: #999;
        }

        .upload-area::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.1);
            opacity: 0;
            transition: opacity 0.2s ease-in-out;
        }

        .upload-area:hover::before {
            opacity: 0.5;
        }

        .upload-area.dropzone {
            background-color: #f5f5f5;
        }

        .upload-area.dropzone::before {
            background-color: rgba(0, 0, 0, 0.2);
        }

        .upload-area.dropzone .upload-button .upload-text {
            animation: ripple-effect 0.5s ease-in-out infinite;
        }

        @keyframes ripple-effect {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.5);
            }

            100% {
                transform: scale(1);
            }
        }

        .boton-volver {
            background-color: #34C759;
            /* Color verde manzana */
            color: white;
            padding: 15px 25px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            border-radius: 5px;
        }

        .boton-volver:hover {
            background-color: #2E865F;
        }

        .fas fa-arrow-left {
            margin-right: 10px;
        }
    </style>

@section('contents')

    <div class="container">
        <!-- Mensajes de éxito o error -->
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
            <script>
                setTimeout(function() {
                    window.location.href = '/send';
                }, 6000);
            </script>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <!-- Formulario de carga de archivo -->
        <h2 class="text-center mb-4">Cargar mensajería</h2>
        <a class="boton-volver" href="javascript:history.go(-1)">
            <i class="fas fa-arrow-left"></i> Regresar
        </a>
        <form action="{{ route('send-sms') }}" method="POST" enctype="multipart/form-data" class="d-inline"
            onsubmit="return handleFileUpload(event)">
            @csrf
            <div id="drop-area" class="upload-area" ondragover="dragOver(event)" ondrop="drop(event)">
                <label for="excel_file" class="upload-button">
                    <span class="upload-text">
                        <i class="fas fa-file-upload"></i> Arrastra y suelta aquí el archivo o haz clic para seleccionar
                    </span>
                    <input type="file" id="excel_file" name="excel_file" class="upload-input"
                        onchange="updateFileName(this)">
                </label>
                <span id="selected-file" class="upload-file-name">Seleccionar archivo</span>
            </div>
            <button type="submit" class="btn btn-primary btn-block mt-3">
                <i id="upload-icon" class="upload-icon fas fa-cloud-upload-alt"></i> Subir
            </button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>

    <div class="container">
        <!-- Tabla de mensajes cargados -->
        @if (!empty($messages))
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Cargado!',
                        text: 'Se han cargado {{ $messagesCount }} mensajes desde el archivo.',
                        showConfirmButton: true,
                    });
                });
            </script>

            <table class="table table-bordered" id="messages-table">
                <thead>
                    <tr>
                        <th>Número de Teléfonos</th>
                        <th>Mensaje</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($messages as $message)
                        <tr>
                            <td>{{ $message[0] }}</td>
                            <td>{{ $message[1] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <button id="send-messages" class="btn btn-success btn-block mt-3">Enviar Mensajes</button>
        @else
            <script>
                document.addEventListener('DOMContentLoaded'() {
                    Swal.fire({
                        icon: 'error',
                        title: '¡Error!',
                        text: 'El archivo debe contener dos columnas: "Número de Teléfono" y "Mensaje".',
                        showConfirmButton: true,
                    });
                });
            </script>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const alertNube = document.querySelector('.alert-nube');
            if (alertNube) {
                alertNube.style.display = 'block';
                alertNube.style.animationName = 'float';
            }
        });

        function dragOver(event) {
            event.preventDefault();
            document.getElementById('drop-area').classList.add('dropzone');
        }

        function drop(event) {
            event.preventDefault();
            document.getElementById('drop-area').classList.remove('dropzone');
            const files = event.dataTransfer.files;
            document.getElementById('excel_file').files = files;
            updateFileName(document.getElementById('excel_file'));
        }

        function updateFileName(input) {
            const fileName = input.files[0] ? input.files[0].name : 'Seleccionar archivo';
            document.getElementById('selected-file').textContent = fileName;
        }

        function handleFileUpload(event) {
            const fileInput = document.getElementById('excel_file');
            if (fileInput.files.length === 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Por favor, seleccione un archivo para subir.',
                });
                event.preventDefault();
                return false;
            }

            const file = fileInput.files[0];
            const reader = new FileReader();

            reader.onload = function(e) {
                const data = e.target.result;
                const workbook = XLSX.read(data, {
                    type: 'binary'
                });
                const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                const jsonData = XLSX.utils.sheet_to_json(firstSheet, {
                    header: 1
                });

                if (jsonData.length === 0 || jsonData[0].length !== 2) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'El archivo requiere dos columnas: "Número de Teléfono" y "Mensaje".',
                    });
                    event.preventDefault();
                    return false;
                }
            };

            reader.onerror = function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al leer el archivo. Por favor, inténtelo de nuevo.',
                });
                event.preventDefault();
                return false;
            };

            reader.readAsBinaryString(file);
            return true;
        }

        document.getElementById('send-messages').addEventListener('click', function() {
            const sendButton = this;
            sendButton.disabled = true; // Deshabilitar el botón para evitar múltiples envíos
            sendButton.innerText = 'Enviando...'; // Cambiar el texto del botón para indicar que está en proceso

            const table = document.getElementById('messages-table');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            const messages = [];

            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                const phoneNumber = cells[0].innerText.trim();
                const message = cells[1].innerText.trim();

                if (phoneNumber && message) {
                    messages.push({
                        phone_number: phoneNumber,
                        message: message
                    });
                }
            }

            if (messages.length > 0) {
                fetch('{{ route('send-bulk-sms') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            messages: messages
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Success', data.success, 'success');
                            setTimeout(() => {
                                window.location.href = '/send';
                            }, 4000);
                        } else if (data.error) {
                            Swal.fire('Error', data.error, 'error');
                            sendButton.disabled = false; // Rehabilitar el botón si hay un error
                            sendButton.innerText = 'Enviar Mensajes'; // Restaurar el texto del botón
                        }
                    })
                    .catch(error => {
                        Swal.fire('Error', error.message, 'error');
                        sendButton.disabled = false; // Rehabilitar el botón si ocurre un error de red
                        sendButton.innerText = 'Enviar Mensajes'; // Restaurar el texto del botón
                    });
            } else {
                Swal.fire('Error', 'No hay mensajes para enviar.', 'error');
                sendButton.disabled = false; // Rehabilitar el botón si no hay mensajes
                sendButton.innerText = 'Enviar Mensajes'; // Restaurar el texto del botón
            }
        });

        // Mostrar/ocultar el botón de scroll al bajar
        window.addEventListener('scroll', function() {
            const scrollBtn = document.getElementById('scroll-to-bottom');

            if (window.scrollY < document.documentElement.scrollHeight - window.innerHeight - 300) {
                scrollBtn.style.display = 'block';
            } else {
                scrollBtn.style.display = 'none';
            }
        });

        // Desplazarse hasta abajo de la vista al hacer clic en el botón
        document.getElementById('scroll-to-bottom').addEventListener('click', function(event) {
            event.preventDefault();
            window.scrollTo({
                top: document.documentElement.scrollHeight,
                behavior: 'smooth'
            });
        });
    </script>
@endsection
