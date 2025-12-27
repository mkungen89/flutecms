<?php

namespace Flute\Modules\ArmaBattlelog\Services;

use Flute\Modules\ArmaBattlelog\Database\Entities\BattlelogPlayer;
use Flute\Modules\ArmaBattlelog\Database\Entities\GameSession;
use Flute\Modules\ArmaBattlelog\Database\Entities\KillEvent;
use Flute\Modules\ArmaBattlelog\Database\Entities\PlayerSession;
use Flute\Modules\ArmaBattlelog\Database\Entities\PlayerWeaponStats;
use Flute\Modules\ArmaBattlelog\Database\Entities\PlayerVehicleStats;
use Flute\Modules\ArmaBattlelog\Database\Entities\Weapon;
use Flute\Modules\ArmaBattlelog\Database\Entities\Vehicle;

class BattlelogService
{
    protected StatsCalculatorService $statsCalculator;
    protected AchievementService $achievementService;

    public function __construct(
        StatsCalculatorService $statsCalculator,
        AchievementService $achievementService
    ) {
        $this->statsCalculator = $statsCalculator;
        $this->achievementService = $achievementService;
    }

    /**
     * Get or create a player by platform ID
     */
    public function getOrCreatePlayer(string $platformId, string $name, string $platform = 'steam'): BattlelogPlayer
    {
        $player = BattlelogPlayer::findOne(['platform_id' => $platformId]);

        if (!$player) {
            $player = new BattlelogPlayer();
            $player->platform_id = $platformId;
            $player->platform = $platform;
            $player->name = $name;
            $player->first_seen = new \DateTimeImmutable();
            $player->save();
        } else {
            // Update name if changed
            if ($player->name !== $name) {
                $player->name = $name;
                $player->save();
            }
        }

        return $player;
    }

    /**
     * Start a new game session
     */
    public function startSession(array $data): GameSession
    {
        $session = new GameSession();
        $session->server_id = $data['server_id'];
        $session->server_name = $data['server_name'] ?? null;
        $session->scenario_id = $data['scenario_id'] ?? null;
        $session->game_mode = $data['game_mode'] ?? 'conflict';
        $session->max_players = $data['max_players'] ?? 0;
        $session->status = 'active';
        $session->save();

        return $session;
    }

    /**
     * End a game session
     */
    public function endSession(int $sessionId, array $data): ?GameSession
    {
        $session = GameSession::findByPK($sessionId);
        if (!$session) {
            return null;
        }

        $session->ended_at = new \DateTimeImmutable();
        $session->status = 'ended';
        $session->winner_faction = $data['winner_faction'] ?? null;
        $session->us_score = $data['us_score'] ?? $session->us_score;
        $session->ussr_score = $data['ussr_score'] ?? $session->ussr_score;

        // Update player session results
        $this->updatePlayerSessionResults($session);

        $session->save();

        return $session;
    }

    /**
     * Record a player connecting to a session
     */
    public function playerConnect(int $sessionId, string $platformId, string $name, string $faction): ?PlayerSession
    {
        $session = GameSession::findByPK($sessionId);
        if (!$session || !$session->isActive()) {
            return null;
        }

        $player = $this->getOrCreatePlayer($platformId, $name);
        $player->last_seen = new \DateTimeImmutable();
        $player->save();

        // Check if player already has a session
        $playerSession = PlayerSession::findOne([
            'session_id' => $sessionId,
            'player_id' => $player->id,
        ]);

        if (!$playerSession) {
            $playerSession = new PlayerSession();
            $playerSession->session_id = $sessionId;
            $playerSession->player_id = $player->id;
            $playerSession->faction = $faction;
            $playerSession->save();

            // Update session player count
            $session->total_players++;
            if ($session->total_players > $session->max_players) {
                $session->max_players = $session->total_players;
            }
            $session->save();
        }

        return $playerSession;
    }

    /**
     * Record a player disconnecting from a session
     * Security: Only allow specific safe fields to be updated (whitelist approach)
     */
    public function playerDisconnect(int $sessionId, string $platformId, array $stats = []): void
    {
        $player = BattlelogPlayer::findOne(['platform_id' => $platformId]);
        if (!$player) {
            return;
        }

        $playerSession = PlayerSession::findOne([
            'session_id' => $sessionId,
            'player_id' => $player->id,
        ]);

        if ($playerSession) {
            $playerSession->left_at = new \DateTimeImmutable();

            // Security: Whitelist of allowed fields to prevent mass assignment attacks
            // Do NOT allow: is_winner, is_mvp, score (these are calculated server-side)
            $allowedFields = [
                'kills', 'deaths', 'assists', 'headshots',
                'objectives_captured', 'objectives_defended',
                'revives', 'heals', 'vehicle_kills',
                'longest_kill', 'best_killstreak',
            ];

            foreach ($allowedFields as $field) {
                if (isset($stats[$field]) && is_numeric($stats[$field])) {
                    $playerSession->$field = (int) $stats[$field];
                }
            }

            // Handle float field separately
            if (isset($stats['longest_kill']) && is_numeric($stats['longest_kill'])) {
                $playerSession->longest_kill = (float) $stats['longest_kill'];
            }

            $playerSession->save();

            // Update player total stats
            $this->updatePlayerTotals($player, $playerSession);
        }
    }

    /**
     * Record a kill event
     */
    public function recordKill(int $sessionId, array $data): ?KillEvent
    {
        $session = GameSession::findByPK($sessionId);
        if (!$session) {
            return null;
        }

        $killer = null;
        $victim = null;

        if (!empty($data['killer_platform_id'])) {
            $killer = BattlelogPlayer::findOne(['platform_id' => $data['killer_platform_id']]);
        }

        if (!empty($data['victim_platform_id'])) {
            $victim = BattlelogPlayer::findOne(['platform_id' => $data['victim_platform_id']]);
        }

        if (!$victim) {
            return null;
        }

        $event = new KillEvent();
        $event->session_id = $sessionId;
        $event->killer_id = $killer?->id;
        $event->victim_id = $victim->id;
        $event->distance = $data['distance'] ?? 0;
        $event->is_headshot = $data['is_headshot'] ?? false;
        $event->is_teamkill = $data['is_teamkill'] ?? false;
        $event->is_suicide = $data['is_suicide'] ?? ($killer === null || $killer->id === $victim->id);
        $event->is_roadkill = $data['is_roadkill'] ?? false;
        $event->killer_position = isset($data['killer_position']) ? json_encode($data['killer_position']) : null;
        $event->victim_position = isset($data['victim_position']) ? json_encode($data['victim_position']) : null;
        $event->killer_faction = $data['killer_faction'] ?? null;
        $event->victim_faction = $data['victim_faction'] ?? null;

        // Weapon lookup
        if (!empty($data['weapon_id'])) {
            $weapon = Weapon::findOne(['internal_id' => $data['weapon_id']]);
            $event->weapon_id = $weapon?->id;
        }

        // Vehicle lookup
        if (!empty($data['vehicle_id'])) {
            $vehicle = Vehicle::findOne(['internal_id' => $data['vehicle_id']]);
            $event->vehicle_id = $vehicle?->id;
        }

        $event->save();

        // Update session stats
        $session->total_kills++;
        $session->save();

        // Update player session stats
        $this->updateKillStats($sessionId, $killer, $victim, $event);

        // Check achievements
        if ($killer) {
            $this->achievementService->checkKillAchievements($killer, $event);
        }

        return $event;
    }

    /**
     * Update kill statistics for players in a session
     */
    protected function updateKillStats(int $sessionId, ?BattlelogPlayer $killer, BattlelogPlayer $victim, KillEvent $event): void
    {
        // Update killer stats
        if ($killer && !$event->is_suicide && !$event->is_teamkill) {
            $killerSession = PlayerSession::findOne([
                'session_id' => $sessionId,
                'player_id' => $killer->id,
            ]);

            if ($killerSession) {
                $killerSession->kills++;
                if ($event->is_headshot) {
                    $killerSession->headshots++;
                }
                if ($event->distance > $killerSession->longest_kill) {
                    $killerSession->longest_kill = $event->distance;
                }
                $killerSession->score += $this->calculateKillScore($event);
                $killerSession->save();
            }

            // Update weapon stats
            if ($event->weapon_id) {
                $this->updateWeaponStats($killer, $event);
            }

            // Update vehicle stats
            if ($event->vehicle_id) {
                $this->updateVehicleKillStats($killer, $event);
            }
        }

        // Update victim stats
        $victimSession = PlayerSession::findOne([
            'session_id' => $sessionId,
            'player_id' => $victim->id,
        ]);

        if ($victimSession) {
            $victimSession->deaths++;
            $victimSession->save();
        }
    }

    /**
     * Update weapon statistics for a player
     */
    protected function updateWeaponStats(BattlelogPlayer $player, KillEvent $event): void
    {
        $stats = PlayerWeaponStats::findOne([
            'player_id' => $player->id,
            'weapon_id' => $event->weapon_id,
        ]);

        if (!$stats) {
            $stats = new PlayerWeaponStats();
            $stats->player_id = $player->id;
            $stats->weapon_id = $event->weapon_id;
        }

        $stats->kills++;
        if ($event->is_headshot) {
            $stats->headshots++;
        }
        if ($event->distance > $stats->longest_kill) {
            $stats->longest_kill = $event->distance;
        }
        $stats->updated_at = new \DateTimeImmutable();
        $stats->save();
    }

    /**
     * Update vehicle kill statistics
     */
    protected function updateVehicleKillStats(BattlelogPlayer $player, KillEvent $event): void
    {
        $stats = PlayerVehicleStats::findOne([
            'player_id' => $player->id,
            'vehicle_id' => $event->vehicle_id,
        ]);

        if (!$stats) {
            $stats = new PlayerVehicleStats();
            $stats->player_id = $player->id;
            $stats->vehicle_id = $event->vehicle_id;
        }

        $stats->kills++;
        if ($event->is_roadkill) {
            $stats->roadkills++;
        }
        $stats->updated_at = new \DateTimeImmutable();
        $stats->save();
    }

    /**
     * Calculate score for a kill
     */
    protected function calculateKillScore(KillEvent $event): int
    {
        $score = 100; // Base kill score

        if ($event->is_headshot) {
            $score += 25;
        }

        if ($event->distance > 200) {
            $score += 25;
        }

        if ($event->distance > 500) {
            $score += 50;
        }

        return $score;
    }

    /**
     * Update player total statistics from a session
     */
    protected function updatePlayerTotals(BattlelogPlayer $player, PlayerSession $session): void
    {
        $duration = $session->getDuration();

        $player->total_playtime += $duration;
        $player->total_kills += $session->kills;
        $player->total_deaths += $session->deaths;
        $player->total_assists += $session->assists;
        $player->total_headshots += $session->headshots;
        $player->total_score += $session->score;
        $player->objectives_captured += $session->objectives_captured;
        $player->objectives_defended += $session->objectives_defended;
        $player->revives += $session->revives;
        $player->heals += $session->heals;
        $player->vehicle_kills += $session->vehicle_kills;

        if ($session->longest_kill > $player->longest_kill) {
            $player->longest_kill = $session->longest_kill;
        }

        if ($session->best_killstreak > $player->best_killstreak) {
            $player->best_killstreak = $session->best_killstreak;
        }

        $player->games_played++;
        $player->updated_at = new \DateTimeImmutable();
        $player->save();

        // Check achievements
        $this->achievementService->checkAllAchievements($player);
    }

    /**
     * Update player session results when session ends
     */
    protected function updatePlayerSessionResults(GameSession $session): void
    {
        $playerSessions = PlayerSession::findAll(['session_id' => $session->id]);

        $mvpScore = 0;
        $mvpSession = null;

        foreach ($playerSessions as $ps) {
            // Determine if player won
            if ($session->winner_faction) {
                $ps->is_winner = ($ps->faction === $session->winner_faction);

                // Update player win/loss
                $player = $ps->player;
                if ($ps->is_winner) {
                    $player->wins++;
                } else {
                    $player->losses++;
                }
                $player->save();
            }

            // Find MVP
            if ($ps->score > $mvpScore) {
                $mvpScore = $ps->score;
                $mvpSession = $ps;
            }

            $ps->save();
        }

        // Mark MVP
        if ($mvpSession) {
            $mvpSession->is_mvp = true;
            $mvpSession->save();
        }
    }

    /**
     * Get player by ID
     */
    public function getPlayer(int $id): ?BattlelogPlayer
    {
        return BattlelogPlayer::findByPK($id);
    }

    /**
     * Get player by platform ID
     */
    public function getPlayerByPlatformId(string $platformId): ?BattlelogPlayer
    {
        return BattlelogPlayer::findOne(['platform_id' => $platformId]);
    }

    /**
     * Get recent sessions for a player
     */
    public function getPlayerSessions(int $playerId, int $limit = 10): array
    {
        return PlayerSession::query()
            ->where('player_id', $playerId)
            ->orderBy('joined_at', 'DESC')
            ->limit($limit)
            ->fetchAll();
    }

    /**
     * Get weapon stats for a player
     */
    public function getPlayerWeaponStats(int $playerId): array
    {
        return PlayerWeaponStats::query()
            ->where('player_id', $playerId)
            ->orderBy('kills', 'DESC')
            ->fetchAll();
    }

    /**
     * Get vehicle stats for a player
     */
    public function getPlayerVehicleStats(int $playerId): array
    {
        return PlayerVehicleStats::query()
            ->where('player_id', $playerId)
            ->orderBy('kills', 'DESC')
            ->fetchAll();
    }

    /**
     * Get session by ID
     */
    public function getSession(int $id): ?GameSession
    {
        return GameSession::findByPK($id);
    }

    /**
     * Get active sessions
     */
    public function getActiveSessions(): array
    {
        return GameSession::findAll(['status' => 'active']);
    }

    /**
     * Get recent sessions
     */
    public function getRecentSessions(int $limit = 20): array
    {
        return GameSession::query()
            ->where('status', 'ended')
            ->orderBy('ended_at', 'DESC')
            ->limit($limit)
            ->fetchAll();
    }
}
