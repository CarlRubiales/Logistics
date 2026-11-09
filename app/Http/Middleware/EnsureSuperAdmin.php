<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route-level guard: only Super Admin (Master Administration) accounts may pass.
 * Used for Finance pages such as /commission.
 */
class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! method_exists($user, 'isMasterAdmin') || ! $user->isMasterAdmin()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Super Admin privileges are required.',
                ], 403);
            }
            abort(403, 'Access denied. Super Admin privileges are required.');
        }

        return $next($request);
    }
}
