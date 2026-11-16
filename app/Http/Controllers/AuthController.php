<?php

namespace App\Http\Controllers;

use App\Models\Developer;
use App\Models\Auditor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string'],
            'username'  => ['required', 'string', 'unique:developers,username'],
            'password'  => ['required', 'string', 'min:6'],
        ]);

        $developer = Developer::create([
            'full_name' => $validated['full_name'],
            'username'  => $validated['username'],
            'password'  => Hash::make($validated['password']),
        ]);

        $token = $developer->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'user' => [
                'id'        => $developer->id,
                'full_name' => $developer->full_name,
                'username'  => $developer->username,
                'role'      => 'developer',
            ],
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $auditor = Auditor::where('username', $request->username)->first();
        if ($auditor && Hash::check($request->password, $auditor->password)) {
            $token = $auditor->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login successful',
                'user' => [
                    'id'       => $auditor->id,
                    'username' => $auditor->username,
                    'role'     => 'auditor',
                ],
                'token' => $token,
            ], 200);
        }

        $developer = Developer::where('username', $request->username)->first();
        if ($developer && Hash::check($request->password, $developer->password)) {
            $token = $developer->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login successful',
                'user' => [
                    'id'        => $developer->id,
                    'full_name' => $developer->full_name,
                    'username'  => $developer->username,
                    'role'      => 'developer',
                ],
                'token' => $token,
            ], 200);
        }

        return response()->json([
            'message' => 'Username or password incorrect'
        ], 401);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout success'
        ], 200);
    }
}
