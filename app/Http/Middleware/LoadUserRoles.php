<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LoadUserRoles
{
    /**
     * Ensure roles are eager-loaded for common role checks (sidebar, controllers).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $request->user()->loadMissing('roles');
        }

        return $next($request);
    }
}

