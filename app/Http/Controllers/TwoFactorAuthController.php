<?php

namespace App\Http\Controllers;

use App\Mail\TwoFactorCode;
use App\Services\CustomerAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TwoFactorAuthController extends Controller
{
    public function sendCode(Request $request)
    {
        if (!$this->isTwoFactorEnabled()) {
            return $this->completeWithoutTwoFactor($request);
        }

        if (!Auth::check() || !session('pending_2fa')) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $email = $request->session()->get('login_email');

        if (!$email) {
            Auth::logout();
            $request->session()->invalidate();

            return redirect()->route('login')->with('error', 'No se pudo determinar tu dirección de correo electrónico.');
        }

        $code = Str::random(6);

        $request->session()->put('two_factor_code', $code);
        $request->session()->put('two_factor_code_expires', now()->addMinutes(10));

        try {
            Mail::to($email)->send(new TwoFactorCode($code, $user->name));
            Log::info("Código 2FA enviado correctamente a {$email}");
        } catch (\Exception $e) {
            Log::error('Error enviando código 2FA por correo: ' . $e->getMessage());

            return back()->with('error', 'Error al enviar el código. Por favor intenta nuevamente.');
        }

        return redirect()->route('two-factor.show-code-form');
    }

    public function showCodeForm(Request $request)
    {
        if (!$this->isTwoFactorEnabled()) {
            return $this->completeWithoutTwoFactor($request);
        }

        if (!session('pending_2fa') || !Auth::check()) {
            return redirect()->route('login');
        }

        return view('auth.code');
    }

    public function verifyCode(Request $request)
    {
        if (!$this->isTwoFactorEnabled()) {
            return $this->completeWithoutTwoFactor($request);
        }

        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        if (!session('pending_2fa') || !Auth::check()) {
            return redirect()->route('login');
        }

        if (session('two_factor_code_expires') && now()->gt(session('two_factor_code_expires'))) {
            $request->session()->forget(['two_factor_code', 'two_factor_code_expires']);

            return back()->withErrors(['code' => 'El código ha expirado. Solicita uno nuevo.']);
        }

        if ($request->code !== session('two_factor_code')) {
            return back()->withErrors(['code' => 'El código ingresado es incorrecto.']);
        }

        $this->markTwoFactorAsVerified($request);

        return $this->redirectAfterAuthentication($request);
    }

    protected function isTwoFactorEnabled(): bool
    {
        return (bool) config('auth.two_factor_enabled', true);
    }

    protected function completeWithoutTwoFactor(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $this->markTwoFactorAsVerified($request);

        return $this->redirectAfterAuthentication($request);
    }

    protected function markTwoFactorAsVerified(Request $request): void
    {
        $request->session()->put('two_factor_verified', true);
        $request->session()->forget([
            'two_factor_code',
            'two_factor_code_expires',
            'pending_2fa',
            'login_email',
        ]);
    }

    protected function redirectAfterAuthentication(Request $request)
    {
        if (!session()->has('selected_customers') || count(session('selected_customers', [])) === 0) {
            $customerAccess = app(CustomerAccessService::class);
            $allowedCustomers = $customerAccess->availableCustomers($request->user());

            if ($allowedCustomers->count() === 1) {
                $customerName = (string) $allowedCustomers->first()->name;
                session([
                    'selected_customers' => [$customerName],
                    'selected_customer' => $customerName,
                ]);

                if ($request->user() && method_exists($request->user(), 'isWarehouseOnly') && $request->user()->isWarehouseOnly()) {
                    return redirect()->route('warehouse.index');
                }

                return redirect()->intended(route('dashboard'));
            }

            return redirect()->route('customer.context.index');
        }

        if ($request->user() && method_exists($request->user(), 'isWarehouseOnly') && $request->user()->isWarehouseOnly()) {
            return redirect()->route('warehouse.index');
        }

        return redirect()->intended(route('dashboard'));
    }
}
