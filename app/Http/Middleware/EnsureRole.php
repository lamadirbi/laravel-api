<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class EnsureRole
{
    /**
     * @param  array<int, string>  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'غير مصرح'], 401);
        }

        if (! in_array($user->role, $roles, true)) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        return $next($request);
    }
}

