<?php

use Flute\Core\Router\RouteGroup;
use Flute\Modules\ArmaBattlelog\Admin\Screens\DashboardScreen;
use Flute\Modules\ArmaBattlelog\Admin\Screens\PlayersScreen;
use Flute\Modules\ArmaBattlelog\Admin\Screens\SessionsScreen;
use Flute\Modules\ArmaBattlelog\Admin\Screens\WeaponsScreen;
use Flute\Modules\ArmaBattlelog\Admin\Screens\VehiclesScreen;
use Flute\Modules\ArmaBattlelog\Admin\Screens\AchievementsScreen;
use Flute\Modules\ArmaBattlelog\Admin\Screens\SettingsScreen;

router()->group(function (RouteGroup $router) {
    $router->get('/', [DashboardScreen::class, 'render'])->name('admin.battlelog.dashboard');
    $router->get('/players', [PlayersScreen::class, 'render'])->name('admin.battlelog.players');
    $router->get('/sessions', [SessionsScreen::class, 'render'])->name('admin.battlelog.sessions');
    $router->get('/weapons', [WeaponsScreen::class, 'render'])->name('admin.battlelog.weapons');
    $router->get('/vehicles', [VehiclesScreen::class, 'render'])->name('admin.battlelog.vehicles');
    $router->get('/achievements', [AchievementsScreen::class, 'render'])->name('admin.battlelog.achievements');
    $router->get('/settings', [SettingsScreen::class, 'render'])->name('admin.battlelog.settings');

    // Actions
    $router->post('/generate-demo', [DashboardScreen::class, 'generateDemo'])->name('admin.battlelog.generate-demo');
    $router->post('/recalculate-leaderboards', [DashboardScreen::class, 'recalculateLeaderboards'])->name('admin.battlelog.recalculate');
}, '/admin/battlelog');
