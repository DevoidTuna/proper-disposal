<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Login da equipe por usuário + senha.
     * Em caso de sucesso, gera um token e o devolve para o cliente.
     */
    public function login(Request $request)
    {
        $dados = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('username', $dados['username'])->first();

        if (! $user || ! Hash::check($dados['password'], $user->password)) {
            return response()->json(['message' => 'Usuário ou senha inválidos.'], 401);
        }

        // Gera um token e guarda o hash no banco (o token cru fica só com o cliente).
        $token = Str::random(60);
        $user->update(['api_token' => hash('sha256', $token)]);

        return response()->json([
            'token' => $token,
            'user' => ['username' => $user->username, 'name' => $user->name],
        ]);
    }

    /**
     * Logout: invalida o token atual.
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->update(['api_token' => null]);
        }

        return response()->noContent();
    }
}
