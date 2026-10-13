<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateIntegration
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) env('LOGISTICS_API_KEY', 'demo-logistics-key');
        $provided = (string) $request->header('X-Integration-Key', '');

        if ($provided === '' || ! hash_equals($expected, $provided)) {
            return response()->json(['message' => 'Invalid or missing integration key.'], 401);
        }

        return $next($request);
    }
}
