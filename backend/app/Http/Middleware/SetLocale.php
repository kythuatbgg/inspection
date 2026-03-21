<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->header('Accept-Language', 'vi');

        // Extract primary language tag (e.g., "vi" from "vi-VN,vi;q=0.9,en;q=0.8")
        $locale = strtolower(substr($locale, 0, 2));

        if (!in_array($locale, ['vi', 'en'])) {
            $locale = 'vi';
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
