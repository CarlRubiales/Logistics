<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRiderRole
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user() && $request->user()->role === 'rider', 403, 'Rider access only.');
        return $next($request);
    }
}
