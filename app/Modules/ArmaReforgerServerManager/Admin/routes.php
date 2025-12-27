<?php

use Flute\Core\Router\Router;
use Flute\Modules\ArmaReforgerServerManager\Admin\Screens\ModListScreen;
use Flute\Modules\ArmaReforgerServerManager\Admin\Screens\ServerEditScreen;
use Flute\Modules\ArmaReforgerServerManager\Admin\Screens\ServerListScreen;
use Flute\Modules\ArmaReforgerServerManager\Admin\Screens\SettingsScreen;

// Server routes
Router::screen('/admin/reforger/servers', ServerListScreen::class);
Router::screen('/admin/reforger/servers/add', ServerEditScreen::class);
Router::screen('/admin/reforger/servers/{id}/edit', ServerEditScreen::class);

// Mod routes
Router::screen('/admin/reforger/mods', ModListScreen::class);

// Settings route
Router::screen('/admin/reforger/settings', SettingsScreen::class);
