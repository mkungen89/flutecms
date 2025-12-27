<?php

namespace Flute\Modules\ArmaBattlelog\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Table\Index;
use Flute\Core\Database\Entities\DatabaseEntity;

#[Entity(table: 'battlelog_player_achievements')]
#[Index(columns: ['player_id', 'achievement_id'], unique: true)]
class PlayerAchievement extends DatabaseEntity
{
    #[Column(type: 'primary')]
    public int $id;

    #[Column(type: 'integer')]
    public int $player_id;

    #[BelongsTo(target: BattlelogPlayer::class)]
    public BattlelogPlayer $player;

    #[Column(type: 'integer')]
    public int $achievement_id;

    #[BelongsTo(target: Achievement::class)]
    public Achievement $achievement;

    /** @var int Current progress towards achievement */
    #[Column(type: 'integer', default: 0)]
    public int $progress = 0;

    /** @var bool Is the achievement unlocked? */
    #[Column(type: 'boolean', default: false)]
    public bool $is_unlocked = false;

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeInterface $unlocked_at = null;

    #[Column(type: 'datetime')]
    public \DateTimeInterface $updated_at;

    public function __construct()
    {
        $this->updated_at = new \DateTimeImmutable();
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentage(): float
    {
        if ($this->achievement->requirement_value === 0) {
            return 100;
        }
        return min(100, round(($this->progress / $this->achievement->requirement_value) * 100, 1));
    }

    /**
     * Check and update unlock status
     */
    public function checkUnlock(): bool
    {
        if ($this->is_unlocked) {
            return true;
        }

        if ($this->progress >= $this->achievement->requirement_value) {
            $this->is_unlocked = true;
            $this->unlocked_at = new \DateTimeImmutable();
            return true;
        }

        return false;
    }
}
