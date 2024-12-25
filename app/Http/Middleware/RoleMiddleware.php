<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $role): Response
    {
        if (!Auth::check()) {
            return response()->json(
                ['message' => 'Unauthenticated user' ],
                401
            );
        }

        if (Auth::user()->hasRole('admin')) {
            return $next($request);
        }

        if (!Auth::user()->hasRole($role)) {
            return response()->json(
                ['message' => 'Unauthorized user' ],
                403
            );
        }

        return $next($request);
    }
}
