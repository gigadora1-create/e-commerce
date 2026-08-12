<?php

namespace App\Http\Controllers;

use App\Mail\TwoFactorCode;
use App\Models\User;
use App\Services\CustomerAccessService;
use App\Services\InfobipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected $infobipService;

    public function __construct(InfobipService $infobipService)
    {
        $this->infobipService = $infobipService;
    }

    public function register()
    {
        return view('auth/register');
    }

    public function registerSave(Request $request)
    {
        Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'post' => 'required',
            'address' => 'required',
            'password' => 'required|confirmed',
            'telephone' => 'required|string|max:20',
        ])->validate();

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'level' => 'Admin',
            'post' => $request->post,
            'address' => $request->address,
            'telephone' => $request->telephone,
        ]);

        return redirect()->route('login');
    }

    public function login()
    {
        return view('auth/login');
    }

    public function loginAction(Request $request)
    {
        Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ])->validate();

        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        $request->session()->regenerate();
        $request->session()->put('login_email', $request->email);
        $request->session()->put('login_time', now());

        if (!$this->isTwoFactorEnabled()) {
            $this->markTwoFactorAsVerified($request);

            return $this->redirectAfterAuthentication($request);
        }

        $request->session()->put('two_factor_verified', false);
        $request->session()->put('pending_2fa', true);

        return $this->sendTwoFactorCode($request);
    }

    protected function sendTwoFactorCode(Request $request)
    {
        $user = Auth::user();
        $email = $request->session()->get('login_email');

        if (!$user->telephone) {
            Auth::logout();
            $request->session()->invalidate();

            return redirect()->route('login')->with('error', 'Por favor contacta al administrador para actualizar tu número de teléfono.');
        }

        $code = Str::random(6);

        $request->session()->put('two_factor_code', $code);
        $request->session()->put('two_factor_code_expires', now()->addMinutes(10));

        try {
            Mail::to($email)->send(new TwoFactorCode($code, $user->name));
        } catch (\Exception $e) {
            Log::error('Error enviando código 2FA: ' . $e->getMessage());

            return redirect()->route('two-factor.show-code-form')
                ->with('warning', 'Código enviado por SMS. Error al enviar email.');
        }

        return redirect()->route('two-factor.show-code-form');
    }

    public function showCodeForm(Request $request)
    {
        if (!$this->isTwoFactorEnabled()) {
            if (!Auth::check()) {
                return redirect()->route('login');
            }

            $this->markTwoFactorAsVerified($request);

            return $this->redirectAfterAuthentication($request);
        }

        if (!session('pending_2fa') || !Auth::check()) {
            return redirect()->route('login');
        }

        return view('auth.code');
    }

    public function verifyCode(Request $request)
    {
        if (!$this->isTwoFactorEnabled()) {
            if (!Auth::check()) {
                return redirect()->route('login');
            }

            $this->markTwoFactorAsVerified($request);

            return $this->redirectAfterAuthentication($request);
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

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $response = redirect('/login');
        $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->header('Pragma', 'no-cache');
        $response->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');

        return $response;
    }

    public function profile()
    {
        return view('Profiles');
    }

    protected function isTwoFactorEnabled(): bool
    {
        return (bool) config('auth.two_factor_enabled', true);
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

                if ($request->user() && method_exists($request->user(), 'isSupplyRequesterOnly') && $request->user()->isSupplyRequesterOnly()) {
                    return redirect()->route('supplies.issues.index');
                }

                return redirect()->intended(route('dashboard'));
            }

            return redirect()->route('customer.context.index');
        }

        if ($request->user() && method_exists($request->user(), 'isWarehouseOnly') && $request->user()->isWarehouseOnly()) {
            return redirect()->route('warehouse.index');
        }

        if ($request->user() && method_exists($request->user(), 'isSupplyRequesterOnly') && $request->user()->isSupplyRequesterOnly()) {
            return redirect()->route('supplies.issues.index');
        }

        return redirect()->intended(route('dashboard'));
    }
}
