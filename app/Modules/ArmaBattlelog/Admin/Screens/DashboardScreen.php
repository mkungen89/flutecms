<?php

namespace Flute\Modules\ArmaBattlelog\Admin\Screens;

use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Layouts\Metric;
use Flute\Admin\Platform\Layouts\Card;
use Flute\Admin\Platform\Layouts\Rows;
use Flute\Admin\Platform\Fields\Label;
use Flute\Admin\Platform\Actions\Button;
use Flute\Core\Support\FluteRequest;
use Flute\Modules\ArmaBattlelog\Services\StatsCalculatorService;
use Flute\Modules\ArmaBattlelog\Services\DemoDataService;
use Flute\Modules\ArmaBattlelog\Services\LeaderboardService;
use Flute\Modules\ArmaBattlelog\Database\Entities\BattlelogPlayer;
use Flute\Modules\ArmaBattlelog\Database\Entities\GameSession;

class DashboardScreen extends Screen
{
    public ?string $name = 'Battlelog Dashboard';
    public ?string $description = 'Overview of Arma Reforger statistics';
    public ?string $permission = 'battlelog.admin';

    protected StatsCalculatorService $statsService;
    protected DemoDataService $demoService;
    protected LeaderboardService $leaderboardService;

    public function __construct(
        StatsCalculatorService $statsService,
        DemoDataService $demoService,
        LeaderboardService $leaderboardService
    ) {
        $this->statsService = $statsService;
        $this->demoService = $demoService;
        $this->leaderboardService = $leaderboardService;
    }

    public function query(): array
    {
        $stats = $this->statsService->getGlobalStats();

        return [
            'total_players' => $stats['total_players'],
            'total_sessions' => $stats['total_sessions'],
            'total_kills' => $stats['total_kills'],
            'total_playtime' => $stats['total_playtime_formatted'],
            'recent_players' => BattlelogPlayer::query()
                ->orderBy('last_seen', 'DESC')
                ->limit(5)
                ->fetchAll(),
            'active_sessions' => GameSession::query()
                ->where('status', 'active')
                ->count(),
        ];
    }

    public function layout(): array
    {
        return [
            // Stats Row
            Rows::make([
                Metric::make('Total Players')
                    ->value($this->query()['total_players'])
                    ->icon('ph-users'),
                Metric::make('Total Battles')
                    ->value($this->query()['total_sessions'])
                    ->icon('ph-game-controller'),
                Metric::make('Total Kills')
                    ->value(number_format($this->query()['total_kills']))
                    ->icon('ph-skull'),
                Metric::make('Total Playtime')
                    ->value($this->query()['total_playtime'])
                    ->icon('ph-clock'),
            ]),

            // Actions
            Card::make('Quick Actions', [
                Rows::make([
                    Button::make('Generate Demo Data')
                        ->method('generateDemo')
                        ->icon('ph-database')
                        ->confirm('This will generate demo players and sessions. Continue?'),
                    Button::make('Recalculate Leaderboards')
                        ->method('recalculateLeaderboards')
                        ->icon('ph-ranking'),
                ]),
            ]),

            // Active Sessions
            Card::make('Active Sessions', [
                Label::make('active_sessions')
                    ->title('Currently Active Sessions')
                    ->value($this->query()['active_sessions']),
            ]),
        ];
    }

    public function generateDemo(FluteRequest $request)
    {
        $result = $this->demoService->generateFullDemo(20, 5);

        toast()->success("Demo data generated: {$result['players_created']} players, {$result['sessions_created']} sessions");

        return redirect(route('admin.battlelog.dashboard'));
    }

    public function recalculateLeaderboards(FluteRequest $request)
    {
        $this->leaderboardService->recalculateLeaderboards();

        toast()->success('Leaderboards recalculated successfully');

        return redirect(route('admin.battlelog.dashboard'));
    }
}
