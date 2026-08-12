<x-layouts.auth>
    <div class="bg-blue-100 p-4 rounded-lg">
        <form id="passwordResetForm" method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <h2 class="text-2xl font-bold text-center text-blue-800 mb-4">Recuperar contraseña</h2>
            <div>
                <label for="email" class="block text-sm font-medium mb-2">Correo electrónico</label>
                <input id="email" type="email" name="email" required autofocus class="form-control">
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary w-100">
                    Enviar enlace de recuperación
                </button>
            </div>
        </form>
    </div>

    <!-- SweetAlert2 Library -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.getElementById('passwordResetForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            let timerInterval;
            Swal.fire({
                title: 'Procesando solicitud',
                html: 'Enviando correo electrónico...',
                timer: 10000,
                timerProgressBar: true,
                didOpen: () => {
                    Swal.showLoading()
                    const b = Swal.getHtmlContainer().querySelector('b')
                    timerInterval = setInterval(() => {
                        const remainingTime = Math.ceil(Swal.getTimerLeft() / 1000);
                        b.textContent = remainingTime
                    }, 100)
                },
                willClose: () => {
                    clearInterval(timerInterval)
                }
            }).then((result) => {
                if (result.dismiss === Swal.DismissReason.timer) {
                    Swal.fire({
                        title: '¡Éxito!',
                        text: 'Mensaje enviado correctamente',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = '{{ route("login") }}';
                        }
                    });
                }
            });

            // Realizar la petición al servidor en segundo plano
            fetch(this.action, {
                method: 'POST',
                body: new FormData(this),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                console.log('Respuesta del servidor:', data);
                // No hacemos nada con la respuesta, ya que mostraremos el mensaje de éxito de todos modos
            })
            .catch(error => {
                console.error('Error:', error);
                // Registramos el error en la consola, pero no lo mostramos al usuario
            });
        });
    </script>
</x-layouts.auth>