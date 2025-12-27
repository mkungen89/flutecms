<?php

namespace Flute\Modules\ArmaBattlelog\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Table\Index;
use Flute\Core\Database\Entities\DatabaseEntity;

#[Entity(table: 'battlelog_player_weapon_stats')]
#[Index(columns: ['player_id', 'weapon_id'], unique: true)]
class PlayerWeaponStats extends DatabaseEntity
{
    #[Column(type: 'primary')]
    public int $id;

    #[Column(type: 'integer')]
    public int $player_id;

    #[BelongsTo(target: BattlelogPlayer::class)]
    public BattlelogPlayer $player;

    #[Column(type: 'integer')]
    public int $weapon_id;

    #[BelongsTo(target: Weapon::class)]
    public Weapon $weapon;

    #[Column(type: 'integer', default: 0)]
    public int $kills = 0;

    #[Column(type: 'integer', default: 0)]
    public int $deaths = 0;

    #[Column(type: 'integer', default: 0)]
    public int $headshots = 0;

    #[Column(type: 'integer', default: 0)]
    public int $shots_fired = 0;

    #[Column(type: 'integer', default: 0)]
    public int $shots_hit = 0;

    /** @var float Longest kill distance with this weapon */
    #[Column(type: 'float', default: 0)]
    public float $longest_kill = 0;

    /** @var int Time used in seconds */
    #[Column(type: 'integer', default: 0)]
    public int $time_used = 0;

    #[Column(type: 'datetime')]
    public \DateTimeInterface $updated_at;

    public function __construct()
    {
        $this->updated_at = new \DateTimeImmutable();
    }

    /**
     * Calculate accuracy percentage
     */
    public function getAccuracy(): float
    {
        if ($this->shots_fired === 0) {
            return 0;
        }
        return round(($this->shots_hit / $this->shots_fired) * 100, 1);
    }

    /**
     * Calculate headshot percentage
     */
    public function getHeadshotPercentage(): float
    {
        if ($this->kills === 0) {
            return 0;
        }
        return round(($this->headshots / $this->kills) * 100, 1);
    }

    /**
     * Get formatted time used
     */
    public function getFormattedTimeUsed(): string
    {
        $hours = floor($this->time_used / 3600);
        $minutes = floor(($this->time_used % 3600) / 60);

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }
        return "{$minutes}m";
    }
}
