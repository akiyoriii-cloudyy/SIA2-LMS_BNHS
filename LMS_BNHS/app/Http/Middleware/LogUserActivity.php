<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogUserActivity
{
    private array $loggableActions = [
        'POST' => ['store', 'create', 'add'],
        'PUT' => ['update', 'edit', 'modify'],
        'PATCH' => ['update', 'edit', 'modify'],
        'DELETE' => ['destroy', 'delete', 'remove'],
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (auth()->check() && $this->shouldLogActivity($request)) {
            $this->logActivity($request, $response);
        }

        return $response;
    }

    private function shouldLogActivity(Request $request): bool
    {
        $method = $request->getMethod();
        $routeName = $request->route()?->getName() ?? '';

        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'])) {
            return false;
        }

        $excludedRoutes = ['login', 'logout', 'password', 'mfa', 'api'];
        foreach ($excludedRoutes as $excluded) {
            if (str_contains($routeName, $excluded)) {
                return false;
            }
        }

        return true;
    }

    private function logActivity(Request $request, Response $response): void
    {
        $routeName = $request->route()?->getName() ?? 'unknown';
        $method = $request->getMethod();
        $status = $response->getStatusCode();

        $action = $this->determineAction($routeName, $method);
        $statusText = $status >= 200 && $status < 400 ? 'success' : 'failed';
        $description = $this->generateDescription($routeName, $method, $status);

        ActivityLogger::log($action, $description, [
            'route' => $routeName,
            'method' => $method,
            'status_code' => $status,
            'url' => $request->fullUrl(),
        ], $statusText);
    }

    private function determineAction(string $routeName, string $method): string
    {
        $actionMap = [
            'POST' => 'create',
            'PUT' => 'update',
            'PATCH' => 'update',
            'DELETE' => 'delete',
        ];

        $baseAction = $actionMap[$method] ?? 'action';
        $resource = $this->extractResourceFromRoute($routeName);

        return $resource ? "$resource.$baseAction" : $baseAction;
    }

    private function extractResourceFromRoute(string $routeName): ?string
    {
        $parts = explode('.', $routeName);

        return $parts[0] ?? null;
    }

    private function generateDescription(string $routeName, string $method, int $status): string
    {
        $statusText = $status >= 200 && $status < 400 ? 'successful' : 'failed';
        $action = match ($method) {
            'POST' => 'creation',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'deletion',
            default => 'action',
        };

        return "{$statusText} {$action} on {$routeName}";
    }
}
