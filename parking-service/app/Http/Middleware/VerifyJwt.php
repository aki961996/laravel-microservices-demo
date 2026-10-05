<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Throwable;

class VerifyJwt
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Token missing'], 401);
        }

        try {
            $payload = JWT::decode($token, new Key(config('services.jwt.secret'), 'HS256'));
        } catch (Throwable $e) {
            return response()->json(['message' => 'Invalid or expired token'], 401);
        }

        // Pass the user's id and email from the token to the controller
        $request->attributes->set('user_id', $payload->sub);
        $request->attributes->set('email', $payload->email);

        return $next($request);
    }
}
