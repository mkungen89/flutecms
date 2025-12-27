<?php

namespace Flute\Modules\ArmaBattlelog\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Table\Index;
use Flute\Core\Database\Entities\DatabaseEntity;

#[Entity(table: 'battlelog_leaderboards')]
#[Index(columns: ['category', 'period', 'rank'])]
#[Index(columns: ['player_id', 'category', 'period'], unique: true)]
class Leaderboard extends DatabaseEntity
{
    #[Column(type: 'primary')]
    public int $id;

    #[Column(type: 'integer')]
    public int $player_id;

    #[BelongsTo(target: BattlelogPlayer::class)]
    public BattlelogPlayer $player;

    /** @var string Category: kills, kd_ratio, score, wins, headshots, accuracy, playtime, spm */
    #[Column(type: 'string', length: 50)]
    public string $category;

    /** @var string Period: all_time, season, monthly, weekly, daily */
    #[Column(type: 'string', length: 20, default: 'all_time')]
    public string $period = 'all_time';

    /** @var float Score/value for this category */
    #[Column(type: 'float', default: 0)]
    public float $score = 0;

    /** @var int Rank position */
    #[Column(type: 'integer', default: 0)]
    public int $rank = 0;

    /** @var int Previous rank (for tracking changes) */
    #[Column(type: 'integer', nullable: true)]
    public ?int $previous_rank = null;

    #[Column(type: 'datetime')]
    public \DateTimeInterface $updated_at;

    public function __construct()
    {
        $this->updated_at = new \DateTimeImmutable();
    }

    /**
     * Get rank change (positive = improved, negative = dropped)
     */
    public function getRankChange(): ?int
    {
        if ($this->previous_rank === null) {
            return null;
        }
        return $this->previous_rank - $this->rank;
    }

    /**
     * Get rank change indicator
     */
    public function getRankChangeIndicator(): string
    {
        $change = $this->getRankChange();
        if ($change === null) {
            return 'new';
        }
        if ($change > 0) {
            return 'up';
        }
        if ($change < 0) {
            return 'down';
        }
        return 'same';
    }

    /**
     * Get category display name
     */
    public static function getCategoryName(string $category): string
    {
        return match ($category) {
            'kills' => 'Most Kills',
            'kd_ratio' => 'Best K/D Ratio',
            'score' => 'Highest Score',
            'wins' => 'Most Wins',
            'headshots' => 'Most Headshots',
            'accuracy' => 'Best Accuracy',
            'playtime' => 'Most Playtime',
            'spm' => 'Best Score/Minute',
            'objectives' => 'Most Objectives',
            'revives' => 'Most Revives',
            default => ucfirst(str_replace('_', ' ', $category)),
        };
    }

    /**
     * Get period display name
     */
    public static function getPeriodName(string $period): string
    {
        return match ($period) {
            'all_time' => 'All Time',
            'season' => 'This Season',
            'monthly' => 'This Month',
            'weekly' => 'This Week',
            'daily' => 'Today',
            default => ucfirst($period),
        };
    }
}
