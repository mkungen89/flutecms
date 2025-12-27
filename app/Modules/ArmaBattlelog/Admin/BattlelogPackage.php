<?php

namespace Flute\Modules\ArmaBattlelog\Admin;

use Flute\Admin\Platform\Contracts\AdminPackageInterface;

class BattlelogPackage implements AdminPackageInterface
{
    public function getName(): string
    {
        return 'ArmaBattlelog';
    }

    public function getTitle(): string
    {
        return __('battlelog.admin_title');
    }

    public function getDescription(): string
    {
        return 'Manage Arma Reforger Battlelog statistics and settings';
    }

    public function getIcon(): string
    {
        return 'ph-chart-line-up';
    }

    public function getPermissions(): array
    {
        return [
            'battlelog.admin' => 'Access Battlelog Admin',
            'battlelog.manage' => 'Manage Battlelog Data',
            'battlelog.demo' => 'Generate Demo Data',
        ];
    }

    public function getMenuItems(): array
    {
        return [
            [
                'title' => 'Battlelog',
                'icon' => 'ph-chart-line-up',
                'permission' => 'battlelog.admin',
                'items' => [
                    [
                        'title' => 'Dashboard',
                        'route' => 'admin.battlelog.dashboard',
                        'permission' => 'battlelog.admin',
                    ],
                    [
                        'title' => 'Players',
                        'route' => 'admin.battlelog.players',
                        'permission' => 'battlelog.manage',
                    ],
                    [
                        'title' => 'Sessions',
                        'route' => 'admin.battlelog.sessions',
                        'permission' => 'battlelog.manage',
                    ],
                    [
                        'title' => 'Weapons',
                        'route' => 'admin.battlelog.weapons',
                        'permission' => 'battlelog.manage',
                    ],
                    [
                        'title' => 'Vehicles',
                        'route' => 'admin.battlelog.vehicles',
                        'permission' => 'battlelog.manage',
                    ],
                    [
                        'title' => 'Achievements',
                        'route' => 'admin.battlelog.achievements',
                        'permission' => 'battlelog.manage',
                    ],
                    [
                        'title' => 'Settings',
                        'route' => 'admin.battlelog.settings',
                        'permission' => 'battlelog.manage',
                    ],
                ],
            ],
        ];
    }

    public function getRoutes(): array
    {
        return [
            __DIR__ . '/routes.php',
        ];
    }
}
