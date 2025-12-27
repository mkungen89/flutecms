<?php

use Flute\Core\Router\RouteGroup;
use Flute\Modules\ArmaBattlelog\Http\Controllers\BattlelogController;
use Flute\Modules\ArmaBattlelog\Http\Controllers\ApiController;
use Flute\Modules\ArmaBattlelog\Http\Controllers\LeaderboardController;
use Flute\Modules\ArmaBattlelog\Http\Controllers\BattleReportController;

/**
 * Frontend routes
 */
router()->group(function (RouteGroup $router) {
    // Main battlelog page
    $router->get('/', [BattlelogController::class, 'index']);

    // Player profile
    $router->get('/player/{id}', [BattlelogController::class, 'player']);
    $router->get('/player/{id}/weapons', [BattlelogController::class, 'playerWeapons']);
    $router->get('/player/{id}/vehicles', [BattlelogController::class, 'playerVehicles']);
    $router->get('/player/{id}/sessions', [BattlelogController::class, 'playerSessions']);
    $router->get('/player/{id}/achievements', [BattlelogController::class, 'playerAchievements']);

    // Leaderboards
    $router->get('/leaderboard', [LeaderboardController::class, 'index']);
    $router->get('/leaderboard/{category}', [LeaderboardController::class, 'category']);

    // Battle reports
    $router->get('/battlereport/{id}', [BattleReportController::class, 'show']);

    // Weapons & Vehicles encyclopedia
    $router->get('/weapons', [BattlelogController::class, 'weapons']);
    $router->get('/vehicles', [BattlelogController::class, 'vehicles']);
}, '/battlelog');

/**
 * API routes for Arma Reforger mod
 */
router()->group(function (RouteGroup $router) {
    // Session management
    $router->post('/session/start', [ApiController::class, 'startSession']);
    $router->post('/session/end', [ApiController::class, 'endSession']);
    $router->post('/session/heartbeat', [ApiController::class, 'heartbeat']);

    // Player events
    $router->post('/player/connect', [ApiController::class, 'playerConnect']);
    $router->post('/player/disconnect', [ApiController::class, 'playerDisconnect']);
    $router->post('/player/spawn', [ApiController::class, 'playerSpawn']);

    // Combat events
    $router->post('/event/kill', [ApiController::class, 'recordKill']);
    $router->post('/event/death', [ApiController::class, 'recordDeath']);
    $router->post('/event/damage', [ApiController::class, 'recordDamage']);

    // Objective events
    $router->post('/event/objective', [ApiController::class, 'recordObjective']);
    $router->post('/event/capture', [ApiController::class, 'recordCapture']);

    // Vehicle events
    $router->post('/event/vehicle/enter', [ApiController::class, 'vehicleEnter']);
    $router->post('/event/vehicle/exit', [ApiController::class, 'vehicleExit']);
    $router->post('/event/vehicle/destroy', [ApiController::class, 'vehicleDestroy']);

    // Batch events (preferred for performance)
    $router->post('/events/batch', [ApiController::class, 'batchEvents']);

    // Stats retrieval (for in-game display)
    $router->get('/player/{platformId}/stats', [ApiController::class, 'getPlayerStats']);

    // Demo endpoints (for testing without mod)
    $router->post('/demo/generate-session', [ApiController::class, 'generateDemoSession']);
    $router->post('/demo/generate-player', [ApiController::class, 'generateDemoPlayer']);
}, '/api/battlelog');
