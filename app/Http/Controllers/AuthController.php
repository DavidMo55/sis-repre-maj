<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; 

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string', 
            'password' => 'required',
        ]);
        
        $authCredentials = [
            'name' => $credentials['username'], 
            'password' => $credentials['password'],
        ];

        if (Auth::attempt($authCredentials)) {
            $user = Auth::user();
            $token = $user->createToken('auth_token')->plainTextToken; 

            return response()->json([
                'message' => 'Login exitoso',
                'user' => $user,
                'token' => $token, 
            ]);
        }
        
        return response()->json([
            'message' => 'Credenciales inválidas',
        ], 401);
    }
}