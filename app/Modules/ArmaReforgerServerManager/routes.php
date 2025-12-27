<?php

use Flute\Core\Router\Router;
use Flute\Modules\ArmaReforgerServerManager\Http\Controllers\ApiController;

// Public API routes for server status (if needed for widgets etc.)
Router::group(['prefix' => 'api/reforger', 'middleware' => ['csrf']], function () {
    Router::get('/servers', [ApiController::class, 'listServers']);
    Router::get('/servers/{id}/status', [ApiController::class, 'serverStatus']);
});
