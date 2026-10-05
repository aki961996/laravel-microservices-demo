<?php

namespace App\Http\Controllers;

use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create($data); // password is auto-hashed by the User model

        return response()->json([
            'user' => $user,
            'token' => $this->makeToken($user),
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        return response()->json(['token' => $this->makeToken($user)]);
    }

    private function makeToken(User $user): string
    {
        $now = time();

        return JWT::encode([
            'iss' => 'auth-service',
            'sub' => $user->id,
            'email' => $user->email,
            'iat' => $now,
            'exp' => $now + config('services.jwt.ttl'),
        ], config('services.jwt.secret'), 'HS256');
    }
}
