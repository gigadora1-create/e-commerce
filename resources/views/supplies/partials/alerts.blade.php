@php
    $errorMessages = $errors->any() ? $errors->all() : [];
    $errorText = $errorMessages !== [] ? implode("\n", $errorMessages) : null;
@endphp

@if (session('success') || session('error') || $errorText)
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                @if (session('success'))
                    Swal.fire({
                        icon: 'success',
                        title: 'Proceso completado',
                        text: @json(session('success')),
                        confirmButtonColor: '#bb0000'
                    });
                @elseif (session('error'))
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: @json(session('error')),
                        confirmButtonColor: '#bb0000'
                    });
                @elseif ($errorText)
                    Swal.fire({
                        icon: 'error',
                        title: 'Revise los datos ingresados',
                        text: @json($errorText),
                        confirmButtonColor: '#bb0000'
                    });
                @endif
            });
        </script>
    @endpush
@endif
