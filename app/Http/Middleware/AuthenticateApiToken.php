<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    /**
     * Protege as rotas da equipe: exige um token válido no cabeçalho
     * Authorization: Bearer <token>.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Não autenticado.'], 401);
        }

        $user = User::where('api_token', hash('sha256', $token))->first();

        if (! $user) {
            return response()->json(['message' => 'Token inválido ou expirado.'], 401);
        }

        // Disponibiliza o usuário autenticado para os controllers.
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
