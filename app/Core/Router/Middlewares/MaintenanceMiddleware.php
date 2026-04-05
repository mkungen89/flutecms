<?php

namespace Flute\Core\Router\Middlewares;

use Closure;
use Flute\Core\Support\BaseMiddleware;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceMiddleware extends BaseMiddleware
{
    public function handle(FluteRequest $request, Closure $next, ...$args): Response
    {
        if (!is_installed()) {
            return $next($request);
        }

        $path = $request->getPathInfo();

        if ($path === '/login' || $path === '/live' || strpos($path, '/social/') === 0) {
            return $next($request);
        }

        if (user()->can('admin')) {
            return $next($request);
        }

        if (config('app.maintenance_mode')) {
            if ($request->expectsJson() || $request->isAjax()) {
                return json([
                    'error' => config('app.maintenance_message')
                        ? __(config('app.maintenance_message'))
                        : __('def.maintenance_mode'),
                ], 503);
            }

            $title = config('app.maintenance_title') ? __(config('app.maintenance_title')) : __('def.maintenance_mode');

            $message = config('app.maintenance_message') ? __(config('app.maintenance_message')) : '';

            $showTimer = (bool) config('app.maintenance_show_timer', false);
            $endTime = config('app.maintenance_end_time', '');

            return response()->view(
                'flute::pages.maintenance',
                [
                    'title' => $title,
                    'message' => $message,
                    'showTimer' => $showTimer,
                    'endTime' => $endTime,
                ],
                503,
            );
        }

        return $next($request);
    }
}
