<x-layouts.auth>
    <div class="bg-blue-100 p-4 rounded-lg">
        <form id="passwordResetForm" method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <h2 class="text-2xl font-bold text-center text-blue-800 mb-4">Recuperar contrasena</h2>
            <div>
                <label for="email" class="block text-sm font-medium mb-2">Correo electronico</label>
                <input id="email" type="email" name="email" required autofocus class="form-control">
                <div id="passwordResetEmailError" class="invalid-feedback d-none" role="alert"></div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary w-100">Enviar enlace de recuperacion</button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.getElementById('passwordResetForm').addEventListener('submit', async function (event) {
            event.preventDefault();

            const form = this;
            const emailInput = document.getElementById('email');
            const emailError = document.getElementById('passwordResetEmailError');
            emailInput.classList.remove('is-invalid');
            emailError.classList.add('d-none');
            emailError.textContent = '';

            Swal.fire({
                title: 'Procesando solicitud',
                html: 'Enviando correo electronico...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading(),
            });

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                    },
                });
                const data = await response.json().catch(() => ({
                    message: 'El servidor devolvio una respuesta inesperada.',
                }));

                if (!response.ok || !data.ok) {
                    throw new Error(data.message || data.errors?.email?.[0] || 'No fue posible procesar la solicitud.');
                }

                await Swal.fire({
                    title: 'Enlace enviado',
                    text: data.message,
                    icon: 'success',
                    confirmButtonText: 'Ir al inicio de sesion',
                });
                window.location.href = '{{ route("login") }}';
            } catch (error) {
                Swal.close();
                emailInput.classList.add('is-invalid');
                emailError.textContent = error.message;
                emailError.classList.remove('d-none');
                Swal.fire({
                    title: 'No se envio el enlace',
                    text: error.message,
                    icon: 'error',
                    confirmButtonText: 'Corregir correo',
                });
            }
        });
    </script>
</x-layouts.auth>
