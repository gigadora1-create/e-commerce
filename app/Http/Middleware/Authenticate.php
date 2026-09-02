<?php

namespace App\Http\Middleware;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Authenticate extends Middleware
{
    protected function authenticate($request, array $guards): void
    {
        parent::authenticate($request, $guards);

        if ($request->user()?->is_active !== false) {
            return;
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        throw new AuthenticationException(
            'This user account is inactive.',
            $guards,
            $this->redirectTo($request),
        );
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('login');
    }
}
