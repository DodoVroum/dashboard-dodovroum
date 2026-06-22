<?php

namespace App\Http\Middleware;

use App\Models\ApiUser;
use App\Services\DodoVroumApi\ApiAuthService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RestoreAuthFromToken
{
    public function __construct(protected ApiAuthService $apiAuthService) {}

    /**
     * Restaurer la session depuis le header X-Api-Token (requêtes Inertia/axios).
     * Couvre le cas où la session a expiré pendant que l'onglet est ouvert.
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            return $next($request);
        }

        $token = $request->header('X-Api-Token');
        if (!$token) {
            return $next($request);
        }

        $userData = $this->apiAuthService->decodeAndValidateToken($token);
        if (!$userData) {
            return $next($request);
        }

        $fullUserData = array_merge($userData, ['token' => $token]);
        $user = new ApiUser($fullUserData);

        $request->session()->put('api_token', $token);
        $request->session()->put('nest_jwt_token', $token);
        $request->session()->put('api_user', $fullUserData);

        Auth::login($user);

        return $next($request);
    }
}
