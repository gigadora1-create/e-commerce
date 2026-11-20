<?php

namespace App\Services;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuthenticatedRedirectService
{
    public function intendedOr(Request $request, string $fallback): RedirectResponse
    {
        $intended = $request->session()->pull('url.intended');

        if (!is_string($intended) || !$this->isApplicationDestination($request, $intended)) {
            return redirect()->to($fallback);
        }

        return redirect()->to($this->sameOriginUrl($request, $intended));
    }

    private function isApplicationDestination(Request $request, string $url): bool
    {
        $parts = parse_url($url);

        if ($parts === false) {
            return false;
        }

        if (isset($parts['host']) && !hash_equals(strtolower($request->getHost()), strtolower($parts['host']))) {
            return false;
        }

        $path = '/' . ltrim($parts['path'] ?? '/', '/');

        return !Str::is([
            '/login',
            '/logout',
            '/forgot-password*',
            '/reset-password*',
            '/two-factor*',
            '/customer-context*',
        ], $path);
    }

    private function sameOriginUrl(Request $request, string $url): string
    {
        $parts = parse_url($url);
        $path = '/' . ltrim($parts['path'] ?? '/', '/');
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';

        return $request->getSchemeAndHttpHost() . $path . $query;
    }
}
