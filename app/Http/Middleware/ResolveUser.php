<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Resolves the authenticated user from a Bearer token (users.api_token)
 * and sets it on the default guard so auth()->user() / Auth::id() work.
 * Does NOT block guests — route-level CheckRole enforces access.
 */
class ResolveUser
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken() ?: $request->header('X-Api-Token');
        if ($token) {
            $user = User::with('roles')->where('api_token', $token)->first();
            if ($user) {
                Auth::setUser($user);
            }
        }
        return $next($request);
    }
}
