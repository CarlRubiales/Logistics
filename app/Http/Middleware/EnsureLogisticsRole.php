<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLogisticsRole
{
    private const ALLOWED_ROLES = ['admin', 'operations_manager', 'warehouse_staff', 'support'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role ?? null, self::ALLOWED_ROLES, true)) {
            abort(403, 'You do not have access to the logistics workspace.');
        }

        return $next($request);
    }
}
