<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Role-based access: use as role:Guest, role:Host or role:Admin
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if ($request->user()->role !== $role) {
            return redirect('/');   // "/" sends each user to their own home
        }

        return $next($request);
    }
}