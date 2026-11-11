<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureHostVerified
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user()->is_verified) {
            $message = 'Your host account must be verified by the admin first. Upload your documents in Profile.';

            return $request->ajax()
                ? response()->json(['message' => $message], 403)
                : redirect()->route('host.profile')->with('warning', $message);
        }

        return $next($request);
    }
}