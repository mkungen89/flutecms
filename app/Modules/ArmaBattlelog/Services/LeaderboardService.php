<?php

namespace Flute\Modules\ArmaBattlelog\Services;

use Flute\Modules\ArmaBattlelog\Database\Entities\BattlelogPlayer;
use Flute\Modules\ArmaBattlelog\Database\Entities\Leaderboard;

class LeaderboardService
{
    protected array $categories = [
        'kills' => 'total_kills',
        'kd_ratio' => null, // Calculated
        'score' => 'total_score',
        'wins' => 'wins',
        'headshots' => 'total_headshots',
        'accuracy' => null, // Calculated
        'playtime' => 'total_playtime',
        'spm' => null, // Calculated
        'objectives' => 'objectives_captured',
        'revives' => 'revives',
    ];

    /**
     * Get leaderboard for a category
     */
    public function getLeaderboard(string $category, string $period = 'all_time', int $limit = 100, int $offset = 0): array
    {
        if (!isset($this->categories[$category])) {
            return [];
        }

        $leaderboard = Leaderboard::query()
            ->where('category', $category)
            ->where('period', $period)
            ->orderBy('rank', 'ASC')
            ->limit($limit)
            ->offset($offset)
            ->fetchAll();

        $result = [];
        foreach ($leaderboard as $entry) {
            $result[] = [
                'rank' => $entry->rank,
                'previous_rank' => $entry->previous_rank,
                'rank_change' => $entry->getRankChangeIndicator(),
                'player_id' => $entry->player_id,
                'player_name' => $entry->player->name,
                'player_avatar' => $entry->player->avatar_url,
                'score' => $entry->score,
                'formatted_score' => $this->formatScore($category, $entry->score),
            ];
        }

        return $result;
    }

    /**
     * Get player's rank in a category
     */
    public function getPlayerRank(int $playerId, string $category, string $period = 'all_time'): ?array
    {
        $entry = Leaderboard::findOne([
            'player_id' => $playerId,
            'category' => $category,
            'period' => $period,
        ]);

        if (!$entry) {
            return null;
        }

        return [
            'rank' => $entry->rank,
            'score' => $entry->score,
            'formatted_score' => $this->formatScore($category, $entry->score),
        ];
    }

    /**
     * Recalculate all leaderboards
     */
    public function recalculateLeaderboards(string $period = 'all_time'): void
    {
        foreach (array_keys($this->categories) as $category) {
            $this->recalculateCategory($category, $period);
        }
    }

    /**
     * Recalculate a specific leaderboard category
     */
    public function recalculateCategory(string $category, string $period = 'all_time'): void
    {
        $players = $this->getPlayersForCategory($category, $period);

        $rank = 1;
        foreach ($players as $playerData) {
            $existing = Leaderboard::findOne([
                'player_id' => $playerData['id'],
                'category' => $category,
                'period' => $period,
            ]);

            if (!$existing) {
                $existing = new Leaderboard();
                $existing->player_id = $playerData['id'];
                $existing->category = $category;
                $existing->period = $period;
            } else {
                $existing->previous_rank = $existing->rank;
            }

            $existing->score = $playerData['score'];
            $existing->rank = $rank;
            $existing->updated_at = new \DateTimeImmutable();
            $existing->save();

            $rank++;
        }
    }

    /**
     * Get players sorted by category
     */
    protected function getPlayersForCategory(string $category, string $period): array
    {
        $column = $this->categories[$category];
        $players = [];

        // For simple column-based categories
        if ($column) {
            $query = BattlelogPlayer::query()
                ->where($column, '>', 0)
                ->orderBy($column, 'DESC')
                ->limit(1000);

            foreach ($query->fetchAll() as $player) {
                $players[] = [
                    'id' => $player->id,
                    'score' => $player->$column,
                ];
            }
        } else {
            // For calculated categories
            $allPlayers = BattlelogPlayer::query()
                ->where('games_played', '>', 0)
                ->fetchAll();

            foreach ($allPlayers as $player) {
                $score = match ($category) {
                    'kd_ratio' => $player->getKDRatio(),
                    'accuracy' => $player->getAccuracy(),
                    'spm' => $player->getScorePerMinute(),
                    default => 0,
                };

                if ($score > 0) {
                    $players[] = [
                        'id' => $player->id,
                        'score' => $score,
                    ];
                }
            }

            // Sort descending
            usort($players, fn($a, $b) => $b['score'] <=> $a['score']);
            $players = array_slice($players, 0, 1000);
        }

        return $players;
    }

    /**
     * Format score for display
     */
    protected function formatScore(string $category, float $score): string
    {
        return match ($category) {
            'kd_ratio' => number_format($score, 2),
            'accuracy' => number_format($score, 1) . '%',
            'spm' => number_format($score, 0),
            'playtime' => $this->formatPlaytime((int) $score),
            default => number_format($score, 0),
        };
    }

    /**
     * Format playtime
     */
    protected function formatPlaytime(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }
        return "{$minutes}m";
    }

    /**
     * Get available categories
     */
    public function getCategories(): array
    {
        return array_map(fn($cat) => [
            'key' => $cat,
            'name' => Leaderboard::getCategoryName($cat),
        ], array_keys($this->categories));
    }

    /**
     * Get available periods
     */
    public function getPeriods(): array
    {
        return [
            ['key' => 'all_time', 'name' => 'All Time'],
            ['key' => 'monthly', 'name' => 'This Month'],
            ['key' => 'weekly', 'name' => 'This Week'],
            ['key' => 'daily', 'name' => 'Today'],
        ];
    }
}
