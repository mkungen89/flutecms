<?php

namespace Flute\Modules\ArmaReforgerServerManager\Admin;

use Flute\Admin\Support\AbstractAdminPackage;
use Flute\Modules\ArmaReforgerServerManager\Database\Entities\ReforgerServer;

class ArmaReforgerPackage extends AbstractAdminPackage
{
    /**
     * {@inheritdoc}
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadViews('Resources/views', 'arma-reforger-admin');
        $this->loadRoutesFromFile('routes.php');
        $this->loadTranslations('Resources/lang');
        $this->registerScss('Resources/assets/scss/admin.scss');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions(): array
    {
        return ['admin', 'admin.reforger'];
    }

    /**
     * {@inheritdoc}
     */
    public function getMenuItems(): array
    {
        return [
            [
                'type' => 'header',
                'title' => __('arma-reforger.admin.menu.header'),
            ],
            [
                'title' => __('arma-reforger.admin.menu.servers'),
                'icon' => 'ph.bold.game-controller-bold',
                'url' => url('/admin/reforger/servers'),
                'badge' => $this->getServersCount(),
            ],
            [
                'title' => __('arma-reforger.admin.menu.mods'),
                'icon' => 'ph.bold.puzzle-piece-bold',
                'url' => url('/admin/reforger/mods'),
                'badge' => $this->getModsCount(),
            ],
            [
                'title' => __('arma-reforger.admin.menu.settings'),
                'icon' => 'ph.bold.gear-six-bold',
                'url' => url('/admin/reforger/settings'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority(): int
    {
        return 50;
    }

    /**
     * Get the number of servers.
     */
    protected function getServersCount(): int
    {
        try {
            return ReforgerServer::query()->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get the number of mods.
     */
    protected function getModsCount(): int
    {
        try {
            return \Flute\Modules\ArmaReforgerServerManager\Database\Entities\ReforgerMod::query()->count();
        } catch (\Exception $e) {
            return 0;
        }
    }
}
