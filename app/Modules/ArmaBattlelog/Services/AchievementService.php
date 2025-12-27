<?php

namespace Flute\Modules\ArmaBattlelog\Services;

use Flute\Modules\ArmaBattlelog\Database\Entities\Achievement;
use Flute\Modules\ArmaBattlelog\Database\Entities\BattlelogPlayer;
use Flute\Modules\ArmaBattlelog\Database\Entities\KillEvent;
use Flute\Modules\ArmaBattlelog\Database\Entities\PlayerAchievement;

class AchievementService
{
    /**
     * Check all achievements for a player
     */
    public function checkAllAchievements(BattlelogPlayer $player): array
    {
        $unlocked = [];
        $achievements = Achievement::findAll(['is_active' => true]);

        foreach ($achievements as $achievement) {
            if ($this->checkAchievement($player, $achievement)) {
                $unlocked[] = $achievement;
            }
        }

        return $unlocked;
    }

    /**
     * Check a specific achievement for a player
     */
    public function checkAchievement(BattlelogPlayer $player, Achievement $achievement): bool
    {
        $playerAchievement = $this->getOrCreatePlayerAchievement($player, $achievement);

        if ($playerAchievement->is_unlocked) {
            return false; // Already unlocked
        }

        $currentValue = $this->getPlayerValueForRequirement($player, $achievement->requirement_type);
        $playerAchievement->progress = (int) $currentValue;

        if ($playerAchievement->checkUnlock()) {
            $playerAchievement->save();
            return true;
        }

        $playerAchievement->save();
        return false;
    }

    /**
     * Check kill-related achievements immediately after a kill
     */
    public function checkKillAchievements(BattlelogPlayer $player, KillEvent $event): array
    {
        $unlocked = [];

        // Get kill-related achievements
        $achievements = Achievement::query()
            ->where('is_active', true)
            ->where('requirement_type', 'IN', ['kills', 'headshots', 'longest_kill', 'roadkills'])
            ->fetchAll();

        foreach ($achievements as $achievement) {
            if ($this->checkAchievement($player, $achievement)) {
                $unlocked[] = $achievement;
            }
        }

        return $unlocked;
    }

    /**
     * Get or create player achievement record
     */
    protected function getOrCreatePlayerAchievement(BattlelogPlayer $player, Achievement $achievement): PlayerAchievement
    {
        $playerAchievement = PlayerAchievement::findOne([
            'player_id' => $player->id,
            'achievement_id' => $achievement->id,
        ]);

        if (!$playerAchievement) {
            $playerAchievement = new PlayerAchievement();
            $playerAchievement->player_id = $player->id;
            $playerAchievement->achievement_id = $achievement->id;
            $playerAchievement->achievement = $achievement;
        }

        return $playerAchievement;
    }

    /**
     * Get player's current value for a requirement type
     */
    protected function getPlayerValueForRequirement(BattlelogPlayer $player, string $requirementType): float
    {
        return match ($requirementType) {
            'kills' => $player->total_kills,
            'deaths' => $player->total_deaths,
            'headshots' => $player->total_headshots,
            'longest_kill' => $player->longest_kill,
            'wins' => $player->wins,
            'losses' => $player->losses,
            'games_played' => $player->games_played,
            'playtime' => $player->total_playtime,
            'objectives_captured' => $player->objectives_captured,
            'objectives_defended' => $player->objectives_defended,
            'revives' => $player->revives,
            'heals' => $player->heals,
            'repairs' => $player->repairs,
            'vehicle_kills' => $player->vehicle_kills,
            'vehicles_destroyed' => $player->vehicles_destroyed,
            'roadkills' => $player->roadkills,
            'score' => $player->total_score,
            'best_killstreak' => $player->best_killstreak,
            'kd_ratio' => $player->getKDRatio(),
            'accuracy' => $player->getAccuracy(),
            'win_rate' => $player->getWinRate(),
            default => 0,
        };
    }

    /**
     * Get all achievements for a player
     */
    public function getPlayerAchievements(int $playerId): array
    {
        $achievements = Achievement::query()
            ->where('is_active', true)
            ->orderBy('category', 'ASC')
            ->orderBy('sort_order', 'ASC')
            ->fetchAll();

        $result = [];
        foreach ($achievements as $achievement) {
            $playerAchievement = PlayerAchievement::findOne([
                'player_id' => $playerId,
                'achievement_id' => $achievement->id,
            ]);

            $result[] = [
                'id' => $achievement->id,
                'code' => $achievement->code,
                'name' => $achievement->name,
                'description' => $achievement->description,
                'category' => $achievement->category,
                'category_name' => $achievement->getCategoryName(),
                'rarity' => $achievement->rarity,
                'rarity_color' => $achievement->getRarityColor(),
                'icon_url' => $achievement->icon_url,
                'points' => $achievement->points,
                'requirement_type' => $achievement->requirement_type,
                'requirement_value' => $achievement->requirement_value,
                'is_hidden' => $achievement->is_hidden,
                'is_unlocked' => $playerAchievement?->is_unlocked ?? false,
                'progress' => $playerAchievement?->progress ?? 0,
                'progress_percentage' => $playerAchievement?->getProgressPercentage() ?? 0,
                'unlocked_at' => $playerAchievement?->unlocked_at?->format('Y-m-d H:i:s'),
            ];
        }

        return $result;
    }

    /**
     * Get unlocked achievements for a player
     */
    public function getUnlockedAchievements(int $playerId): array
    {
        $playerAchievements = PlayerAchievement::query()
            ->where('player_id', $playerId)
            ->where('is_unlocked', true)
            ->orderBy('unlocked_at', 'DESC')
            ->fetchAll();

        $result = [];
        foreach ($playerAchievements as $pa) {
            $result[] = [
                'id' => $pa->achievement->id,
                'code' => $pa->achievement->code,
                'name' => $pa->achievement->name,
                'description' => $pa->achievement->description,
                'rarity' => $pa->achievement->rarity,
                'rarity_color' => $pa->achievement->getRarityColor(),
                'points' => $pa->achievement->points,
                'unlocked_at' => $pa->unlocked_at?->format('Y-m-d H:i:s'),
            ];
        }

        return $result;
    }

    /**
     * Get achievement statistics for a player
     */
    public function getAchievementStats(int $playerId): array
    {
        $total = Achievement::query()->where('is_active', true)->count();
        $unlocked = PlayerAchievement::query()
            ->where('player_id', $playerId)
            ->where('is_unlocked', true)
            ->count();

        $totalPoints = Achievement::query()
            ->where('is_active', true)
            ->sum('points');

        $earnedPoints = 0;
        $playerAchievements = PlayerAchievement::query()
            ->where('player_id', $playerId)
            ->where('is_unlocked', true)
            ->fetchAll();

        foreach ($playerAchievements as $pa) {
            $earnedPoints += $pa->achievement->points;
        }

        return [
            'total' => $total,
            'unlocked' => $unlocked,
            'locked' => $total - $unlocked,
            'percentage' => $total > 0 ? round(($unlocked / $total) * 100, 1) : 0,
            'total_points' => (int) $totalPoints,
            'earned_points' => $earnedPoints,
        ];
    }

    /**
     * Get recently unlocked achievements
     */
    public function getRecentlyUnlocked(int $limit = 10): array
    {
        $playerAchievements = PlayerAchievement::query()
            ->where('is_unlocked', true)
            ->orderBy('unlocked_at', 'DESC')
            ->limit($limit)
            ->fetchAll();

        $result = [];
        foreach ($playerAchievements as $pa) {
            $result[] = [
                'player_id' => $pa->player_id,
                'player_name' => $pa->player->name,
                'achievement_name' => $pa->achievement->name,
                'achievement_rarity' => $pa->achievement->rarity,
                'rarity_color' => $pa->achievement->getRarityColor(),
                'unlocked_at' => $pa->unlocked_at?->format('Y-m-d H:i:s'),
            ];
        }

        return $result;
    }
}
