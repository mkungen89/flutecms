<?php

namespace Flute\Modules\ArmaBattlelog\Http\Controllers;

use Flute\Core\Support\BaseController;
use Flute\Core\Support\FluteRequest;
use Flute\Modules\ArmaBattlelog\Services\BattlelogService;
use Flute\Modules\ArmaBattlelog\Services\StatsCalculatorService;
use Flute\Modules\ArmaBattlelog\Services\AchievementService;
use Flute\Modules\ArmaBattlelog\Database\Entities\BattlelogPlayer;
use Flute\Modules\ArmaBattlelog\Database\Entities\Weapon;
use Flute\Modules\ArmaBattlelog\Database\Entities\Vehicle;
use Symfony\Component\HttpFoundation\Response;

class BattlelogController extends BaseController
{
    protected BattlelogService $battlelogService;
    protected StatsCalculatorService $statsCalculator;
    protected AchievementService $achievementService;

    public function __construct(
        BattlelogService $battlelogService,
        StatsCalculatorService $statsCalculator,
        AchievementService $achievementService
    ) {
        $this->battlelogService = $battlelogService;
        $this->statsCalculator = $statsCalculator;
        $this->achievementService = $achievementService;
    }

    /**
     * Main battlelog page
     */
    public function index(FluteRequest $request): Response
    {
        $globalStats = $this->statsCalculator->getGlobalStats();

        // Get top players preview
        $topPlayers = BattlelogPlayer::query()
            ->orderBy('total_score', 'DESC')
            ->limit(10)
            ->fetchAll();

        // Get recent sessions
        $recentSessions = $this->battlelogService->getRecentSessions(5);

        // Get recently unlocked achievements
        $recentAchievements = $this->achievementService->getRecentlyUnlocked(5);

        return view('Modules/ArmaBattlelog/Resources/views/index', [
            'globalStats' => $globalStats,
            'topPlayers' => $topPlayers,
            'recentSessions' => $recentSessions,
            'recentAchievements' => $recentAchievements,
        ]);
    }

    /**
     * Player profile page
     */
    public function player(FluteRequest $request, int $id): Response
    {
        $player = $this->battlelogService->getPlayer($id);

        if (!$player) {
            return $this->error(__('battlelog.player_not_found'), 404);
        }

        $stats = $this->statsCalculator->getDetailedPlayerStats($player);
        $nemesisVictim = $this->statsCalculator->getNemesisAndVictim($player->id);
        $achievementStats = $this->achievementService->getAchievementStats($player->id);
        $recentAchievements = $this->achievementService->getUnlockedAchievements($player->id);

        // Get top weapons
        $weaponStats = $this->battlelogService->getPlayerWeaponStats($player->id);
        $topWeapons = array_slice($weaponStats, 0, 5);

        // Get top vehicles
        $vehicleStats = $this->battlelogService->getPlayerVehicleStats($player->id);
        $topVehicles = array_slice($vehicleStats, 0, 5);

        // Get recent sessions
        $recentSessions = $this->battlelogService->getPlayerSessions($player->id, 5);

        return view('Modules/ArmaBattlelog/Resources/views/player', [
            'player' => $player,
            'stats' => $stats,
            'nemesisVictim' => $nemesisVictim,
            'achievementStats' => $achievementStats,
            'recentAchievements' => array_slice($recentAchievements, 0, 6),
            'topWeapons' => $topWeapons,
            'topVehicles' => $topVehicles,
            'recentSessions' => $recentSessions,
        ]);
    }

    /**
     * Player weapons page
     */
    public function playerWeapons(FluteRequest $request, int $id): Response
    {
        $player = $this->battlelogService->getPlayer($id);

        if (!$player) {
            return $this->error(__('battlelog.player_not_found'), 404);
        }

        $weaponStats = $this->battlelogService->getPlayerWeaponStats($player->id);

        // Group by category
        $weaponsByCategory = [];
        foreach ($weaponStats as $ws) {
            $category = $ws->weapon->category;
            if (!isset($weaponsByCategory[$category])) {
                $weaponsByCategory[$category] = [
                    'name' => $ws->weapon->getCategoryName(),
                    'weapons' => [],
                ];
            }
            $weaponsByCategory[$category]['weapons'][] = $ws;
        }

        return view('Modules/ArmaBattlelog/Resources/views/player-weapons', [
            'player' => $player,
            'weaponsByCategory' => $weaponsByCategory,
            'totalWeapons' => count($weaponStats),
        ]);
    }

    /**
     * Player vehicles page
     */
    public function playerVehicles(FluteRequest $request, int $id): Response
    {
        $player = $this->battlelogService->getPlayer($id);

        if (!$player) {
            return $this->error(__('battlelog.player_not_found'), 404);
        }

        $vehicleStats = $this->battlelogService->getPlayerVehicleStats($player->id);

        // Group by category
        $vehiclesByCategory = [];
        foreach ($vehicleStats as $vs) {
            $category = $vs->vehicle->category;
            if (!isset($vehiclesByCategory[$category])) {
                $vehiclesByCategory[$category] = [
                    'name' => $vs->vehicle->getCategoryName(),
                    'vehicles' => [],
                ];
            }
            $vehiclesByCategory[$category]['vehicles'][] = $vs;
        }

        return view('Modules/ArmaBattlelog/Resources/views/player-vehicles', [
            'player' => $player,
            'vehiclesByCategory' => $vehiclesByCategory,
            'totalVehicles' => count($vehicleStats),
        ]);
    }

    /**
     * Player sessions/match history
     */
    public function playerSessions(FluteRequest $request, int $id): Response
    {
        $player = $this->battlelogService->getPlayer($id);

        if (!$player) {
            return $this->error(__('battlelog.player_not_found'), 404);
        }

        $page = max(1, (int) $request->get('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $sessions = $this->battlelogService->getPlayerSessions($player->id, $limit);

        return view('Modules/ArmaBattlelog/Resources/views/player-sessions', [
            'player' => $player,
            'sessions' => $sessions,
            'page' => $page,
        ]);
    }

    /**
     * Player achievements page
     */
    public function playerAchievements(FluteRequest $request, int $id): Response
    {
        $player = $this->battlelogService->getPlayer($id);

        if (!$player) {
            return $this->error(__('battlelog.player_not_found'), 404);
        }

        $achievements = $this->achievementService->getPlayerAchievements($player->id);
        $stats = $this->achievementService->getAchievementStats($player->id);

        // Group by category
        $byCategory = [];
        foreach ($achievements as $a) {
            $cat = $a['category'];
            if (!isset($byCategory[$cat])) {
                $byCategory[$cat] = [
                    'name' => $a['category_name'],
                    'achievements' => [],
                ];
            }
            $byCategory[$cat]['achievements'][] = $a;
        }

        return view('Modules/ArmaBattlelog/Resources/views/player-achievements', [
            'player' => $player,
            'achievementsByCategory' => $byCategory,
            'stats' => $stats,
        ]);
    }

    /**
     * Weapons encyclopedia
     */
    public function weapons(FluteRequest $request): Response
    {
        $weapons = Weapon::query()
            ->where('is_active', true)
            ->orderBy('category', 'ASC')
            ->orderBy('name', 'ASC')
            ->fetchAll();

        // Group by category
        $byCategory = [];
        foreach ($weapons as $weapon) {
            $cat = $weapon->category;
            if (!isset($byCategory[$cat])) {
                $byCategory[$cat] = [
                    'name' => $weapon->getCategoryName(),
                    'weapons' => [],
                ];
            }
            $byCategory[$cat]['weapons'][] = $weapon;
        }

        return view('Modules/ArmaBattlelog/Resources/views/weapons', [
            'weaponsByCategory' => $byCategory,
            'totalWeapons' => count($weapons),
        ]);
    }

    /**
     * Vehicles encyclopedia
     */
    public function vehicles(FluteRequest $request): Response
    {
        $vehicles = Vehicle::query()
            ->where('is_active', true)
            ->orderBy('category', 'ASC')
            ->orderBy('name', 'ASC')
            ->fetchAll();

        // Group by category
        $byCategory = [];
        foreach ($vehicles as $vehicle) {
            $cat = $vehicle->category;
            if (!isset($byCategory[$cat])) {
                $byCategory[$cat] = [
                    'name' => $vehicle->getCategoryName(),
                    'vehicles' => [],
                ];
            }
            $byCategory[$cat]['vehicles'][] = $vehicle;
        }

        return view('Modules/ArmaBattlelog/Resources/views/vehicles', [
            'vehiclesByCategory' => $byCategory,
            'totalVehicles' => count($vehicles),
        ]);
    }
}
