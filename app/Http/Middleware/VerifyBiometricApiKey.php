<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyBiometricApiKey
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {

        $providedKey = $request->header('X-API-KEY');

        $services = config('services', []);
        $storedKey = $services['biometric']['api_key'] ?? null;

        if (
            empty($storedKey) ||
            empty($providedKey) ||
            !hash_equals($storedKey, $providedKey)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API key.'
            ], 401);
        }

        return $next($request);
    }
}