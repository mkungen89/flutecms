<?php

namespace Flute\Modules\ArmaReforgerServerManager;

use Flute\Core\Support\ModuleServiceProvider;
use Flute\Modules\ArmaReforgerServerManager\Services\ReforgerModService;
use Flute\Modules\ArmaReforgerServerManager\Services\ReforgerServerService;
use Flute\Modules\ArmaReforgerServerManager\Services\SteamCMDService;

class ServiceProvider extends ModuleServiceProvider
{
    public array $extensions = [];

    /**
     * Register module services in the container.
     */
    public function register(\DI\Container $container): void
    {
        $container->set(SteamCMDService::class, \DI\autowire(SteamCMDService::class));
        $container->set(ReforgerServerService::class, \DI\autowire(ReforgerServerService::class));
        $container->set(ReforgerModService::class, \DI\autowire(ReforgerModService::class));
    }

    /**
     * Boot the module after registration.
     */
    public function boot(\DI\Container $container): void
    {
        $this->loadEntities();
        $this->loadTranslations();
        $this->loadRoutes();
        $this->loadViews('Resources/views', 'arma-reforger');
        $this->loadScss('Resources/assets/scss/arma-reforger.scss');

        if (is_installed() && is_admin_path() && user()->can('admin')) {
            $this->registerAdminPackage();
        }
    }

    /**
     * Register the admin package for the admin panel.
     */
    protected function registerAdminPackage(): void
    {
        $package = new Admin\ArmaReforgerPackage();
        $this->loadPackage($package);
    }
}
