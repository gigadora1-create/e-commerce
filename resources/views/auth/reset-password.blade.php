<x-layouts.auth>
    <div class="bg-blue-100 p-4 rounded-lg">
        @if (session('status'))
            <div class="alert alert-success mb-4">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger mb-4">
                <div class="font-medium">
                    {{ __('Whoops! Something went wrong.') }}
                </div>

                <ul class="mt-3 list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <h2 class="text-2xl font-bold text-center text-blue-800 mb-4">Restablecer contraseña</h2>
            <div>
                <label for="email" class="block text-sm font-medium mb-2">Correo electrónico</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="form-control">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium mb-2">Nueva contraseña</label>
                <input id="password" type="password" name="password" required class="form-control">
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium mb-2">Confirmar nueva contraseña</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required class="form-control">
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary w-100">
                    Restablecer contraseña
                </button>
            </div>
        </form>
    </div>
</x-layouts.auth>