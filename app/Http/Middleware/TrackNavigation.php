<?php

/* app/Http/Middleware/TrackNavigation.php */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackNavigation
{
    protected $excludedRoutes = [
        'logout',
        'login',
        'register'
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldTrack($request)) {
            $this->addToHistory($request->fullUrl());
        }

        return $next($request);
    }

    protected function shouldTrack(Request $request): bool
    {
        if (!$request->isMethod('GET')) {
            return false;
        }

        if ($request->ajax() || $request->wantsJson()) {
            return false;
        }

        $currentRoute = $request->route() ? $request->route()->getName() : null;

        if (!$currentRoute) {
            return false;
        }

        foreach ($this->excludedRoutes as $pattern) {
            if (fnmatch($pattern, $currentRoute)) {
                return false;
            }
        }

        return true;
    }

    protected function addToHistory(string $url): void
    {
        $history = session()->get('navigation_history', []);

        if (end($history) === $url) {
            return;
        }

        $history[] = $url;

        if (count($history) > 10) {
            array_shift($history);
        }

        session()->put('navigation_history', $history);
    }
}