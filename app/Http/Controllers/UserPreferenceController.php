<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserPreferenceController extends Controller
{
    private const THEME_MODES = ['system', 'light', 'dark'];

    public function edit(Request $request): View
    {
        return view('preferences.edit', [
            'user' => $request->user(),
            'twoFactorGloballyEnabled' => (bool) config('auth.two_factor_enabled', true),
        ]);
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'theme_mode' => ['required', 'in:' . implode(',', self::THEME_MODES)],
            'two_factor_enabled' => ['nullable', 'boolean'],
        ]);

        $request->user()->update([
            'theme_mode' => $validated['theme_mode'],
            'two_factor_enabled' => $request->boolean('two_factor_enabled'),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Preferencia de apariencia actualizada.',
                'theme_mode' => $validated['theme_mode'],
            ]);
        }

        return back()->with('swal_alert', [
            'icon' => 'success',
            'title' => 'Preferencias actualizadas',
            'text' => 'La apariencia y la doble validacion se guardaron correctamente.',
        ]);
    }

    public function index(): View
    {
        return view('preferences.index', [
            'users' => User::query()
                ->select(['id', 'name', 'email', 'position', 'process', 'regional', 'is_active', 'theme_mode', 'two_factor_enabled'])
                ->orderBy('name')
                ->get(),
            'twoFactorGloballyEnabled' => (bool) config('auth.two_factor_enabled', true),
        ]);
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'theme_mode' => ['required', 'in:' . implode(',', self::THEME_MODES)],
            'two_factor_enabled' => ['nullable', 'boolean'],
        ]);

        $user->update([
            'theme_mode' => $validated['theme_mode'],
            'two_factor_enabled' => $request->boolean('two_factor_enabled'),
        ]);

        return back()->with('swal_alert', [
            'icon' => 'success',
            'title' => 'Usuario actualizado',
            'text' => "Las preferencias de {$user->name} se guardaron correctamente.",
        ]);
    }

    public function updateTwoFactorForAll(Request $request): RedirectResponse
    {
        $request->validate([
            'two_factor_enabled' => ['required', 'boolean'],
        ]);

        $enabled = $request->boolean('two_factor_enabled');
        $updated = User::query()->update(['two_factor_enabled' => $enabled]);

        return back()->with('swal_alert', [
            'icon' => 'success',
            'title' => 'Actualizacion masiva completada',
            'text' => sprintf('Doble validacion %s para %d usuario(s).', $enabled ? 'activada' : 'desactivada', $updated),
        ]);
    }
}
