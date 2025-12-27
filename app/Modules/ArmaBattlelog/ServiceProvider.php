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
        $this->loadRoutesFrom(__DIR__ . '/routes.php');
        $this->loadViewsFrom(__DIR__ . '/Resources/views');
        $this->loadTranslationsFrom(__DIR__ . '/Resources/lang');
        $this->registerScss(__DIR__ . '/Resources/assets/scss/battlelog.scss');

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
