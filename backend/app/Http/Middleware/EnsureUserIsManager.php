<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsManager
{
    /**
     * Allow only admin and manager roles.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!in_array($request->user()?->role, ['admin', 'manager'])) {
            return response()->json([
                'message' => 'Bạn không có quyền thực hiện hành động này.'
            ], 403);
        }

        return $next($request);
    }
}
