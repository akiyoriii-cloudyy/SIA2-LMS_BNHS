<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) $request->header('Authorization');
        if (! str_starts_with($header, 'Bearer ')) {
            abort(401, 'Missing bearer token.');
        }

        $token = trim(substr($header, 7));
        if ($token === '') {
            abort(401, 'Invalid token.');
        }

        $hash = hash('sha256', $token);
        $apiToken = ApiToken::query()
            ->with('user')
            ->where('token_hash', $hash)
            ->first();

        if (! $apiToken) {
            abort(401, 'Token not found.');
        }

        if ($apiToken->expires_at && now()->greaterThan($apiToken->expires_at)) {
            abort(401, 'Token expired.');
        }

        $apiToken->forceFill(['last_used_at' => now()])->save();
        $request->setUserResolver(fn () => $apiToken->user);
        Auth::guard('web')->setUser($apiToken->user);

        return $next($request);
    }
}
