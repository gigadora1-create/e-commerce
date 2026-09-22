<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function showForgotForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $email = mb_strtolower(trim((string) $request->input('email')));
        $user = User::query()->where('email', $email)->first();

        if (!$user) {
            return $this->resetLinkError($request, 'No existe una cuenta registrada con ese correo electronico.');
        }

        if (!$user->is_active) {
            return $this->resetLinkError($request, 'La cuenta esta inactiva. Contacte al administrador del sistema.');
        }

        try {
            $status = Password::sendResetLink(['email' => $user->email]);
        } catch (\Throwable $exception) {
            Log::error('Password reset link could not be sent.', [
                'user_id' => $user->id,
                'exception' => $exception->getMessage(),
            ]);

            return $this->resetLinkError($request, 'No fue posible enviar el enlace de recuperacion. Intente nuevamente.');
        }

        if ($status !== Password::RESET_LINK_SENT) {
            return $this->resetLinkError($request, __($status));
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Enlace de recuperacion enviado correctamente.',
            ]);
        }

        return back()->with('status', 'Enlace de recuperacion enviado correctamente.');
    }

    private function resetLinkError(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => false,
                'message' => $message,
                'errors' => ['email' => [$message]],
            ], 422);
        }

        return back()->withErrors(['email' => $message])->withInput();
    }

    public function showResetForm(string $token)
    {
        return view('auth.reset-password', ['token' => $token]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
                    ? redirect()->route('login')->with('status', __($status))
                    : back()->withErrors(['email' => [__($status)]]);
    }
}
