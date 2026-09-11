<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdminRole
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user || ! $user->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo el administrador puede gestionar usuarios.',
                ], 403);
            }

            abort(403, 'Solo el administrador puede gestionar usuarios.');
        }

        return $next($request);
    }
}
