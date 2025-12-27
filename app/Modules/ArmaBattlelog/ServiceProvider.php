<?php

namespace Flute\Modules\ArmaBattlelog;

use Flute\Core\Support\ModuleServiceProvider;
use Flute\Modules\ArmaBattlelog\Services\BattlelogService;
use Flute\Modules\ArmaBattlelog\Services\StatsCalculatorService;
use Flute\Modules\ArmaBattlelog\Services\LeaderboardService;
use Flute\Modules\ArmaBattlelog\Services\AchievementService;
use Flute\Modules\ArmaBattlelog\Services\DemoDataService;

class ServiceProvider extends ModuleServiceProvider
{
    public array $extensions = [];

    public function boot(\DI\ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            BattlelogService::class => \DI\autowire(),
            StatsCalculatorService::class => \DI\autowire(),
            LeaderboardService::class => \DI\autowire(),
            AchievementService::class => \DI\autowire(),
            DemoDataService::class => \DI\autowire(),
        ]);
    }

    public function register(\DI\Container $container): void
    {
        $this->loadRoutesFrom('routes.php');
        $this->loadViewsFrom('Resources/views');
        $this->loadTranslationsFrom('Resources/lang');
        $this->registerScss('Resources/assets/scss/battlelog.scss');

        // Register navbar item for Battlelog
        $this->addNavbarItem(
            'battlelog',
            'Battlelog',
            '/battlelog',
            'ph-chart-line-up',
            5
        );
    }
}
