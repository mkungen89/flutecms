<?php

namespace Flute\Modules\ArmaBattlelog\Services;

use Flute\Modules\ArmaBattlelog\Database\Entities\BattlelogPlayer;
use Flute\Modules\ArmaBattlelog\Database\Entities\PlayerSession;
use Flute\Modules\ArmaBattlelog\Database\Entities\KillEvent;
use Flute\Modules\ArmaBattlelog\Database\Entities\GameSession;

class StatsCalculatorService
{
    /**
     * Get global statistics
     */
    public function getGlobalStats(): array
    {
        $totalPlayers = BattlelogPlayer::query()->count();
        $totalSessions = GameSession::query()->where('status', 'ended')->count();
        $totalKills = (int) BattlelogPlayer::query()->sum('total_kills');
        $totalPlaytime = (int) BattlelogPlayer::query()->sum('total_playtime');

        return [
            'total_players' => $totalPlayers,
            'total_sessions' => $totalSessions,
            'total_kills' => $totalKills,
            'total_playtime' => $totalPlaytime,
            'total_playtime_formatted' => $this->formatPlaytime($totalPlaytime),
        ];
    }

    /**
     * Get detailed stats for a player
     */
    public function getDetailedPlayerStats(BattlelogPlayer $player): array
    {
        return [
            'overview' => [
                'kills' => $player->total_kills,
                'deaths' => $player->total_deaths,
                'assists' => $player->total_assists,
                'kd_ratio' => $player->getKDRatio(),
                'headshots' => $player->total_headshots,
                'headshot_percentage' => $player->getHeadshotPercentage(),
                'accuracy' => $player->getAccuracy(),
                'score' => $player->total_score,
                'spm' => $player->getScorePerMinute(),
                'playtime' => $player->total_playtime,
                'playtime_formatted' => $player->getFormattedPlaytime(),
            ],
            'combat' => [
                'longest_kill' => $player->longest_kill,
                'best_killstreak' => $player->best_killstreak,
                'shots_fired' => $player->shots_fired,
                'shots_hit' => $player->shots_hit,
            ],
            'objectives' => [
                'captured' => $player->objectives_captured,
                'defended' => $player->objectives_defended,
            ],
            'teamplay' => [
                'revives' => $player->revives,
                'heals' => $player->heals,
                'repairs' => $player->repairs,
            ],
            'vehicles' => [
                'kills' => $player->vehicle_kills,
                'destroyed' => $player->vehicles_destroyed,
                'roadkills' => $player->roadkills,
            ],
            'games' => [
                'played' => $player->games_played,
                'wins' => $player->wins,
                'losses' => $player->losses,
                'win_rate' => $player->getWinRate(),
            ],
            'ranking' => [
                'rank_points' => $player->rank_points,
                'rank_name' => $player->rank_name,
            ],
        ];
    }

    /**
     * Get battle report data for a session
     */
    public function getBattleReport(GameSession $session): array
    {
        $playerSessions = PlayerSession::query()
            ->where('session_id', $session->id)
            ->orderBy('score', 'DESC')
            ->fetchAll();

        $usPlayers = [];
        $ussrPlayers = [];
        $timeline = [];

        foreach ($playerSessions as $ps) {
            $playerData = [
                'id' => $ps->player_id,
                'name' => $ps->player->name,
                'kills' => $ps->kills,
                'deaths' => $ps->deaths,
                'assists' => $ps->assists,
                'score' => $ps->score,
                'kd_ratio' => $ps->getKDRatio(),
                'headshots' => $ps->headshots,
                'objectives_captured' => $ps->objectives_captured,
                'revives' => $ps->revives,
                'is_mvp' => $ps->is_mvp,
                'spm' => $ps->getScorePerMinute(),
            ];

            if ($ps->faction === 'us') {
                $usPlayers[] = $playerData;
            } else {
                $ussrPlayers[] = $playerData;
            }
        }

        // Get kill events for timeline
        $killEvents = KillEvent::query()
            ->where('session_id', $session->id)
            ->orderBy('timestamp', 'ASC')
            ->limit(100)
            ->fetchAll();

        foreach ($killEvents as $event) {
            $timeline[] = [
                'type' => 'kill',
                'timestamp' => $event->timestamp->format('H:i:s'),
                'seconds' => $event->timestamp->getTimestamp() - $session->started_at->getTimestamp(),
                'killer' => $event->killer?->name ?? 'Unknown',
                'victim' => $event->victim->name,
                'weapon' => $event->weapon?->name ?? 'Unknown',
                'is_headshot' => $event->is_headshot,
                'distance' => $event->distance,
            ];
        }

        return [
            'session' => [
                'id' => $session->id,
                'server_name' => $session->server_name,
                'map' => $session->map?->name ?? 'Unknown',
                'game_mode' => $session->game_mode,
                'duration' => $session->getFormattedDuration(),
                'duration_seconds' => $session->getDuration(),
                'started_at' => $session->started_at->format('Y-m-d H:i:s'),
                'ended_at' => $session->ended_at?->format('Y-m-d H:i:s'),
                'winner_faction' => $session->winner_faction,
                'us_score' => $session->us_score,
                'ussr_score' => $session->ussr_score,
                'total_kills' => $session->total_kills,
                'total_players' => $session->total_players,
            ],
            'us_team' => [
                'score' => $session->us_score,
                'players' => $usPlayers,
                'total_kills' => array_sum(array_column($usPlayers, 'kills')),
            ],
            'ussr_team' => [
                'score' => $session->ussr_score,
                'players' => $ussrPlayers,
                'total_kills' => array_sum(array_column($ussrPlayers, 'kills')),
            ],
            'timeline' => $timeline,
            'mvp' => $this->findMVP($playerSessions),
        ];
    }

    /**
     * Find MVP from player sessions
     */
    protected function findMVP(array $playerSessions): ?array
    {
        foreach ($playerSessions as $ps) {
            if ($ps->is_mvp) {
                return [
                    'id' => $ps->player_id,
                    'name' => $ps->player->name,
                    'score' => $ps->score,
                    'kills' => $ps->kills,
                    'deaths' => $ps->deaths,
                ];
            }
        }
        return null;
    }

    /**
     * Format playtime in seconds to human readable
     */
    public function formatPlaytime(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . 's';
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }

        return "{$minutes}m";
    }

    /**
     * Calculate rank based on points
     */
    public function calculateRank(int $points): string
    {
        $ranks = [
            ['min' => 0, 'name' => 'Recruit'],
            ['min' => 100, 'name' => 'Private'],
            ['min' => 250, 'name' => 'Private First Class'],
            ['min' => 500, 'name' => 'Corporal'],
            ['min' => 800, 'name' => 'Sergeant'],
            ['min' => 1200, 'name' => 'Staff Sergeant'],
            ['min' => 1700, 'name' => 'Sergeant First Class'],
            ['min' => 2300, 'name' => 'Master Sergeant'],
            ['min' => 3000, 'name' => 'First Sergeant'],
            ['min' => 3800, 'name' => 'Sergeant Major'],
            ['min' => 4700, 'name' => 'Warrant Officer'],
            ['min' => 5700, 'name' => 'Chief Warrant Officer'],
            ['min' => 6800, 'name' => 'Second Lieutenant'],
            ['min' => 8000, 'name' => 'First Lieutenant'],
            ['min' => 9300, 'name' => 'Captain'],
            ['min' => 10700, 'name' => 'Major'],
            ['min' => 12200, 'name' => 'Lieutenant Colonel'],
            ['min' => 13800, 'name' => 'Colonel'],
            ['min' => 15500, 'name' => 'Brigadier General'],
            ['min' => 17300, 'name' => 'Major General'],
            ['min' => 19200, 'name' => 'Lieutenant General'],
            ['min' => 21200, 'name' => 'General'],
            ['min' => 25000, 'name' => 'General of the Army'],
        ];

        $rank = 'Recruit';
        foreach ($ranks as $r) {
            if ($points >= $r['min']) {
                $rank = $r['name'];
            } else {
                break;
            }
        }

        return $rank;
    }

    /**
     * Get nemesis (player who killed you most) and victim (player you killed most)
     */
    public function getNemesisAndVictim(int $playerId): array
    {
        // Get nemesis - who killed this player the most
        $nemesis = KillEvent::query()
            ->select('killer_id', 'COUNT(*) as kill_count')
            ->where('victim_id', $playerId)
            ->where('killer_id', '!=', null)
            ->where('is_suicide', false)
            ->groupBy('killer_id')
            ->orderBy('kill_count', 'DESC')
            ->limit(1)
            ->fetchOne();

        // Get victim - who this player killed the most
        $victim = KillEvent::query()
            ->select('victim_id', 'COUNT(*) as kill_count')
            ->where('killer_id', $playerId)
            ->where('is_suicide', false)
            ->groupBy('victim_id')
            ->orderBy('kill_count', 'DESC')
            ->limit(1)
            ->fetchOne();

        $result = [
            'nemesis' => null,
            'victim' => null,
        ];

        if ($nemesis && $nemesis['killer_id']) {
            $nemesisPlayer = BattlelogPlayer::findByPK($nemesis['killer_id']);
            if ($nemesisPlayer) {
                $result['nemesis'] = [
                    'id' => $nemesisPlayer->id,
                    'name' => $nemesisPlayer->name,
                    'times' => (int) $nemesis['kill_count'],
                ];
            }
        }

        if ($victim && $victim['victim_id']) {
            $victimPlayer = BattlelogPlayer::findByPK($victim['victim_id']);
            if ($victimPlayer) {
                $result['victim'] = [
                    'id' => $victimPlayer->id,
                    'name' => $victimPlayer->name,
                    'times' => (int) $victim['kill_count'],
                ];
            }
        }

        return $result;
    }
}
