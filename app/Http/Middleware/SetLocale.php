<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Locales the interface is translated into. Anything else is ignored so a
     * crafted cookie or Accept-Language header cannot select an unloaded locale.
     */
    public const SUPPORTED = ['en', 'ms'];

    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale($this->resolve($request));

        return $next($request);
    }

    private function resolve(Request $request): string
    {
        foreach ([$request->cookie('locale'), $request->getPreferredLanguage(self::SUPPORTED)] as $candidate) {
            if (is_string($candidate) && in_array($candidate, self::SUPPORTED, true)) {
                return $candidate;
            }
        }

        return (string) config('app.locale');
    }
}
