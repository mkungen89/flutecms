<?php

namespace Flute\Modules\ArmaBattlelog\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Table\Index;
use Flute\Core\Database\Entities\DatabaseEntity;

#[Entity(table: 'battlelog_player_vehicle_stats')]
#[Index(columns: ['player_id', 'vehicle_id'], unique: true)]
class PlayerVehicleStats extends DatabaseEntity
{
    #[Column(type: 'primary')]
    public int $id;

    #[Column(type: 'integer')]
    public int $player_id;

    #[BelongsTo(target: BattlelogPlayer::class)]
    public BattlelogPlayer $player;

    #[Column(type: 'integer')]
    public int $vehicle_id;

    #[BelongsTo(target: Vehicle::class)]
    public Vehicle $vehicle;

    #[Column(type: 'integer', default: 0)]
    public int $kills = 0;

    #[Column(type: 'integer', default: 0)]
    public int $deaths = 0;

    /** @var int Vehicles of this type destroyed */
    #[Column(type: 'integer', default: 0)]
    public int $destroyed = 0;

    #[Column(type: 'integer', default: 0)]
    public int $roadkills = 0;

    /** @var int Time in vehicle in seconds */
    #[Column(type: 'integer', default: 0)]
    public int $time_used = 0;

    /** @var float Distance traveled in meters */
    #[Column(type: 'float', default: 0)]
    public float $distance_traveled = 0;

    #[Column(type: 'datetime')]
    public \DateTimeInterface $updated_at;

    public function __construct()
    {
        $this->updated_at = new \DateTimeImmutable();
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

    /**
     * Get formatted distance
     */
    public function getFormattedDistance(): string
    {
        if ($this->distance_traveled >= 1000) {
            return round($this->distance_traveled / 1000, 1) . ' km';
        }
        return round($this->distance_traveled) . ' m';
    }
}
