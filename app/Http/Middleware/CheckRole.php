<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Usage: ->middleware('role:billing,root')
 * 401 if not authenticated, 403 if authenticated but lacks any listed role.
 * 'root' always passes (handled inside User::hasRole).
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        if (!empty($roles) && !$user->hasRole($roles)) {
            return response()->json(['message' => 'Forbidden — insufficient role'], 403);
        }
        return $next($request);
    }
}
