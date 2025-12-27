<?php

namespace Flute\Modules\ArmaBattlelog\Services;

use Flute\Modules\ArmaBattlelog\Database\Entities\BattlelogPlayer;
use Flute\Modules\ArmaBattlelog\Database\Entities\GameSession;
use Flute\Modules\ArmaBattlelog\Database\Entities\PlayerSession;
use Flute\Modules\ArmaBattlelog\Database\Entities\KillEvent;
use Flute\Modules\ArmaBattlelog\Database\Entities\Weapon;
use Flute\Modules\ArmaBattlelog\Database\Entities\Vehicle;
use Flute\Modules\ArmaBattlelog\Database\Entities\GameMap;
use Flute\Modules\ArmaBattlelog\Database\Entities\PlayerWeaponStats;
use Flute\Modules\ArmaBattlelog\Database\Entities\PlayerVehicleStats;

/**
 * Service for generating demo data to test the Battlelog system
 */
class DemoDataService
{
    protected BattlelogService $battlelogService;
    protected LeaderboardService $leaderboardService;

    protected array $demoNames = [
        'ShadowHunter', 'NightWolf', 'IronFist', 'StormBreaker', 'GhostRider',
        'DeathBringer', 'SilentSniper', 'ThunderBolt', 'DarkKnight', 'FireStorm',
        'IceQueen', 'BloodRaven', 'SteelNinja', 'CyberWolf', 'VenomStrike',
        'PhantomX', 'RapidFire', 'HawkEye', 'ViperKing', 'TitanForce',
        'AlphaWolf', 'BravoSix', 'CharlieTeam', 'DeltaForce', 'EchoSquad',
        'FoxtrotLead', 'GolfActual', 'HotelZulu', 'IndiaCompany', 'JulietOne',
    ];

    public function __construct(
        BattlelogService $battlelogService,
        LeaderboardService $leaderboardService
    ) {
        $this->battlelogService = $battlelogService;
        $this->leaderboardService = $leaderboardService;
    }

    /**
     * Generate a complete demo dataset
     */
    public function generateFullDemo(int $numPlayers = 20, int $numSessions = 10): array
    {
        $players = $this->generateDemoPlayers($numPlayers);
        $sessions = [];

        for ($i = 0; $i < $numSessions; $i++) {
            $sessions[] = $this->generateDemoSession($players);
        }

        // Recalculate leaderboards
        $this->leaderboardService->recalculateLeaderboards();

        return [
            'players_created' => count($players),
            'sessions_created' => count($sessions),
            'message' => 'Demo data generated successfully!',
        ];
    }

    /**
     * Generate demo players
     */
    public function generateDemoPlayers(int $count = 20): array
    {
        $players = [];
        $usedNames = [];

        for ($i = 0; $i < $count; $i++) {
            // Generate unique name
            $name = $this->getUniqueName($usedNames);
            $usedNames[] = $name;

            // Generate fake Steam ID
            $platformId = '7656119' . str_pad((string) random_int(1000000000, 9999999999), 10, '0', STR_PAD_LEFT);

            $player = $this->battlelogService->getOrCreatePlayer($platformId, $name);

            // Set some random base stats
            $player->total_playtime = random_int(3600, 360000); // 1h to 100h
            $player->rank_points = random_int(100, 15000);
            $player->rank_name = $this->calculateRank($player->rank_points);
            $player->save();

            $players[] = $player;
        }

        return $players;
    }

    /**
     * Generate a demo game session
     */
    public function generateDemoSession(array $players, ?int $duration = null): GameSession
    {
        $duration = $duration ?? random_int(1800, 7200); // 30min to 2h
        $startTime = new \DateTimeImmutable('-' . random_int(1, 30) . ' days');
        $endTime = $startTime->modify('+' . $duration . ' seconds');

        // Get random map
        $maps = GameMap::findAll(['is_active' => true]);
        $map = $maps[array_rand($maps)] ?? null;

        // Create session
        $session = new GameSession();
        $session->server_id = 'demo_server_' . random_int(1, 5);
        $session->server_name = 'Demo Server #' . random_int(1, 5);
        $session->map_id = $map?->id;
        $session->game_mode = 'conflict';
        $session->started_at = $startTime;
        $session->ended_at = $endTime;
        $session->status = 'ended';
        $session->max_players = count($players);
        $session->total_players = count($players);

        // Determine winner
        $usScore = random_int(200, 500);
        $ussrScore = random_int(200, 500);
        $session->us_score = $usScore;
        $session->ussr_score = $ussrScore;
        $session->winner_faction = $usScore > $ussrScore ? 'us' : 'ussr';

        $session->save();

        // Shuffle players and assign to teams
        shuffle($players);
        $usPlayers = array_slice($players, 0, (int) ceil(count($players) / 2));
        $ussrPlayers = array_slice($players, (int) ceil(count($players) / 2));

        // Create player sessions
        $playerSessions = [];
        foreach ($usPlayers as $player) {
            $playerSessions[] = $this->createPlayerSession($session, $player, 'us', $duration);
        }
        foreach ($ussrPlayers as $player) {
            $playerSessions[] = $this->createPlayerSession($session, $player, 'ussr', $duration);
        }

        // Generate kill events
        $this->generateKillEvents($session, $playerSessions);

        // Update session totals
        $session->total_kills = KillEvent::query()->where('session_id', $session->id)->count();
        $session->save();

        // Find and mark MVP
        $mvp = null;
        $mvpScore = 0;
        foreach ($playerSessions as $ps) {
            if ($ps->score > $mvpScore) {
                $mvpScore = $ps->score;
                $mvp = $ps;
            }
        }
        if ($mvp) {
            $mvp->is_mvp = true;
            $mvp->save();
        }

        return $session;
    }

    /**
     * Create a player session
     */
    protected function createPlayerSession(GameSession $session, BattlelogPlayer $player, string $faction, int $duration): PlayerSession
    {
        $ps = new PlayerSession();
        $ps->session_id = $session->id;
        $ps->player_id = $player->id;
        $ps->faction = $faction;
        $ps->joined_at = $session->started_at;
        $ps->left_at = $session->ended_at;

        // Generate random stats
        $ps->kills = random_int(0, 30);
        $ps->deaths = random_int(0, 20);
        $ps->assists = random_int(0, 10);
        $ps->headshots = random_int(0, (int) ($ps->kills * 0.3));
        $ps->score = ($ps->kills * 100) + ($ps->assists * 25) + ($ps->headshots * 25) + random_int(0, 500);
        $ps->objectives_captured = random_int(0, 5);
        $ps->objectives_defended = random_int(0, 3);
        $ps->revives = random_int(0, 8);
        $ps->heals = random_int(0, 15);
        $ps->vehicle_kills = random_int(0, 5);
        $ps->longest_kill = random_int(10, 800);
        $ps->best_killstreak = random_int(0, 10);
        $ps->is_winner = ($faction === $session->winner_faction);

        $ps->save();

        // Update player totals
        $player->total_kills += $ps->kills;
        $player->total_deaths += $ps->deaths;
        $player->total_assists += $ps->assists;
        $player->total_headshots += $ps->headshots;
        $player->total_score += $ps->score;
        $player->objectives_captured += $ps->objectives_captured;
        $player->objectives_defended += $ps->objectives_defended;
        $player->revives += $ps->revives;
        $player->heals += $ps->heals;
        $player->vehicle_kills += $ps->vehicle_kills;
        $player->games_played++;

        if ($ps->is_winner) {
            $player->wins++;
        } else {
            $player->losses++;
        }

        if ($ps->longest_kill > $player->longest_kill) {
            $player->longest_kill = $ps->longest_kill;
        }
        if ($ps->best_killstreak > $player->best_killstreak) {
            $player->best_killstreak = $ps->best_killstreak;
        }

        $player->updated_at = new \DateTimeImmutable();
        $player->save();

        return $ps;
    }

    /**
     * Generate kill events for a session
     */
    protected function generateKillEvents(GameSession $session, array $playerSessions): void
    {
        $weapons = Weapon::findAll(['is_active' => true]);
        $vehicles = Vehicle::findAll(['is_active' => true, 'has_weapons' => true]);

        $sessionDuration = $session->getDuration();

        foreach ($playerSessions as $killerSession) {
            for ($i = 0; $i < $killerSession->kills; $i++) {
                // Find a random victim from opposite team
                $victimSession = $this->findRandomVictim($playerSessions, $killerSession->faction);
                if (!$victimSession) {
                    continue;
                }

                $event = new KillEvent();
                $event->session_id = $session->id;
                $event->killer_id = $killerSession->player_id;
                $event->victim_id = $victimSession->player_id;
                $event->killer_faction = $killerSession->faction;
                $event->victim_faction = $victimSession->faction;

                // Random weapon
                $weapon = $weapons[array_rand($weapons)] ?? null;
                $event->weapon_id = $weapon?->id;

                // Sometimes use vehicle
                if (random_int(1, 10) <= 2 && !empty($vehicles)) {
                    $vehicle = $vehicles[array_rand($vehicles)];
                    $event->vehicle_id = $vehicle->id;
                }

                $event->distance = random_int(5, 500);
                $event->is_headshot = random_int(1, 100) <= 20;
                $event->is_roadkill = random_int(1, 100) <= 5;

                // Random time during session
                $offset = random_int(0, $sessionDuration);
                $event->timestamp = $session->started_at->modify('+' . $offset . ' seconds');

                // Random positions
                $event->killer_position = json_encode([random_int(0, 10000), random_int(0, 10000), random_int(0, 100)]);
                $event->victim_position = json_encode([random_int(0, 10000), random_int(0, 10000), random_int(0, 100)]);

                $event->save();

                // Update weapon stats
                if ($weapon) {
                    $this->updateDemoWeaponStats($killerSession->player_id, $weapon->id, $event);
                }

                // Update vehicle stats
                if ($event->vehicle_id) {
                    $this->updateDemoVehicleStats($killerSession->player_id, $event->vehicle_id, $event);
                }
            }
        }
    }

    /**
     * Find a random victim from the opposite team
     */
    protected function findRandomVictim(array $playerSessions, string $killerFaction): ?PlayerSession
    {
        $oppositeTeam = array_filter($playerSessions, fn($ps) => $ps->faction !== $killerFaction);
        if (empty($oppositeTeam)) {
            return null;
        }
        return $oppositeTeam[array_rand($oppositeTeam)];
    }

    /**
     * Update weapon stats for demo
     */
    protected function updateDemoWeaponStats(int $playerId, int $weaponId, KillEvent $event): void
    {
        $stats = PlayerWeaponStats::findOne([
            'player_id' => $playerId,
            'weapon_id' => $weaponId,
        ]);

        if (!$stats) {
            $stats = new PlayerWeaponStats();
            $stats->player_id = $playerId;
            $stats->weapon_id = $weaponId;
        }

        $stats->kills++;
        if ($event->is_headshot) {
            $stats->headshots++;
        }
        if ($event->distance > $stats->longest_kill) {
            $stats->longest_kill = $event->distance;
        }
        $stats->shots_fired += random_int(3, 20);
        $stats->shots_hit += random_int(1, 5);
        $stats->time_used += random_int(60, 600);
        $stats->updated_at = new \DateTimeImmutable();
        $stats->save();
    }

    /**
     * Update vehicle stats for demo
     */
    protected function updateDemoVehicleStats(int $playerId, int $vehicleId, KillEvent $event): void
    {
        $stats = PlayerVehicleStats::findOne([
            'player_id' => $playerId,
            'vehicle_id' => $vehicleId,
        ]);

        if (!$stats) {
            $stats = new PlayerVehicleStats();
            $stats->player_id = $playerId;
            $stats->vehicle_id = $vehicleId;
        }

        $stats->kills++;
        if ($event->is_roadkill) {
            $stats->roadkills++;
        }
        $stats->time_used += random_int(60, 600);
        $stats->distance_traveled += random_int(100, 5000);
        $stats->updated_at = new \DateTimeImmutable();
        $stats->save();
    }

    /**
     * Get a unique name
     */
    protected function getUniqueName(array $usedNames): string
    {
        $attempts = 0;
        do {
            $name = $this->demoNames[array_rand($this->demoNames)];
            if (!in_array($name, $usedNames)) {
                return $name;
            }
            $name .= random_int(1, 999);
            $attempts++;
        } while (in_array($name, $usedNames) && $attempts < 100);

        return $name . '_' . uniqid();
    }

    /**
     * Calculate rank from points
     */
    protected function calculateRank(int $points): string
    {
        $ranks = [
            0 => 'Recruit', 100 => 'Private', 250 => 'Corporal',
            500 => 'Sergeant', 1000 => 'Staff Sergeant', 2000 => 'Master Sergeant',
            3500 => 'Warrant Officer', 5000 => 'Lieutenant', 7000 => 'Captain',
            9500 => 'Major', 12000 => 'Colonel', 15000 => 'General',
        ];

        $rank = 'Recruit';
        foreach ($ranks as $minPoints => $rankName) {
            if ($points >= $minPoints) {
                $rank = $rankName;
            }
        }
        return $rank;
    }

    /**
     * Clear all demo data
     */
    public function clearDemoData(): array
    {
        // This would delete all data - use with caution
        // In production, you might want to mark demo data instead

        return [
            'message' => 'Demo data clearing is disabled for safety. Please clear manually if needed.',
        ];
    }
}
