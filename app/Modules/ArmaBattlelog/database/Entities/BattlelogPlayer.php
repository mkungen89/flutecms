<?php

namespace Flute\Modules\ArmaBattlelog\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\Annotated\Annotation\Table\Index;
use Flute\Core\Database\Entities\DatabaseEntity;
use Flute\Core\Database\Entities\User;

#[Entity(table: 'battlelog_players')]
#[Index(columns: ['platform_id'], unique: true)]
#[Index(columns: ['user_id'])]
class BattlelogPlayer extends DatabaseEntity
{
    #[Column(type: 'primary')]
    public int $id;

    #[Column(type: 'integer', nullable: true)]
    public ?int $user_id = null;

    #[BelongsTo(target: User::class, nullable: true)]
    public ?User $user = null;

    /** @var string Steam ID or Xbox ID */
    #[Column(type: 'string', length: 64)]
    public string $platform_id;

    /** @var string Platform type: steam, xbox */
    #[Column(type: 'string', length: 20, default: 'steam')]
    public string $platform = 'steam';

    #[Column(type: 'string', length: 255)]
    public string $name;

    #[Column(type: 'string', length: 255, nullable: true)]
    public ?string $avatar_url = null;

    /** @var int Total playtime in seconds */
    #[Column(type: 'integer', default: 0)]
    public int $total_playtime = 0;

    /** @var int Total kills */
    #[Column(type: 'integer', default: 0)]
    public int $total_kills = 0;

    /** @var int Total deaths */
    #[Column(type: 'integer', default: 0)]
    public int $total_deaths = 0;

    /** @var int Total assists */
    #[Column(type: 'integer', default: 0)]
    public int $total_assists = 0;

    /** @var int Total headshots */
    #[Column(type: 'integer', default: 0)]
    public int $total_headshots = 0;

    /** @var int Total shots fired */
    #[Column(type: 'integer', default: 0)]
    public int $shots_fired = 0;

    /** @var int Total shots hit */
    #[Column(type: 'integer', default: 0)]
    public int $shots_hit = 0;

    /** @var float Longest kill distance in meters */
    #[Column(type: 'float', default: 0)]
    public float $longest_kill = 0;

    /** @var int Best killstreak */
    #[Column(type: 'integer', default: 0)]
    public int $best_killstreak = 0;

    /** @var int Total score */
    #[Column(type: 'integer', default: 0)]
    public int $total_score = 0;

    /** @var int Games won */
    #[Column(type: 'integer', default: 0)]
    public int $wins = 0;

    /** @var int Games lost */
    #[Column(type: 'integer', default: 0)]
    public int $losses = 0;

    /** @var int Total games played */
    #[Column(type: 'integer', default: 0)]
    public int $games_played = 0;

    /** @var int Objectives captured */
    #[Column(type: 'integer', default: 0)]
    public int $objectives_captured = 0;

    /** @var int Objectives defended */
    #[Column(type: 'integer', default: 0)]
    public int $objectives_defended = 0;

    /** @var int Revives given */
    #[Column(type: 'integer', default: 0)]
    public int $revives = 0;

    /** @var int Heals given */
    #[Column(type: 'integer', default: 0)]
    public int $heals = 0;

    /** @var int Repairs done */
    #[Column(type: 'integer', default: 0)]
    public int $repairs = 0;

    /** @var int Vehicle kills */
    #[Column(type: 'integer', default: 0)]
    public int $vehicle_kills = 0;

    /** @var int Vehicles destroyed */
    #[Column(type: 'integer', default: 0)]
    public int $vehicles_destroyed = 0;

    /** @var int Roadkills */
    #[Column(type: 'integer', default: 0)]
    public int $roadkills = 0;

    /** @var int Rank points for matchmaking */
    #[Column(type: 'integer', default: 1000)]
    public int $rank_points = 1000;

    /** @var string Current rank name */
    #[Column(type: 'string', length: 50, default: 'Recruit')]
    public string $rank_name = 'Recruit';

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeInterface $first_seen = null;

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeInterface $last_seen = null;

    #[Column(type: 'datetime')]
    public \DateTimeInterface $created_at;

    #[Column(type: 'datetime')]
    public \DateTimeInterface $updated_at;

    #[HasMany(target: PlayerWeaponStats::class)]
    public array $weaponStats = [];

    #[HasMany(target: PlayerVehicleStats::class)]
    public array $vehicleStats = [];

    #[HasMany(target: PlayerSession::class)]
    public array $sessions = [];

    #[HasMany(target: PlayerAchievement::class)]
    public array $achievements = [];

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
        $this->updated_at = new \DateTimeImmutable();
    }

    /**
     * Calculate K/D ratio
     */
    public function getKDRatio(): float
    {
        if ($this->total_deaths === 0) {
            return (float) $this->total_kills;
        }
        return round($this->total_kills / $this->total_deaths, 2);
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
        if ($this->total_kills === 0) {
            return 0;
        }
        return round(($this->total_headshots / $this->total_kills) * 100, 1);
    }

    /**
     * Calculate win rate percentage
     */
    public function getWinRate(): float
    {
        if ($this->games_played === 0) {
            return 0;
        }
        return round(($this->wins / $this->games_played) * 100, 1);
    }

    /**
     * Calculate score per minute
     */
    public function getScorePerMinute(): float
    {
        if ($this->total_playtime === 0) {
            return 0;
        }
        return round($this->total_score / ($this->total_playtime / 60), 0);
    }

    /**
     * Get formatted playtime
     */
    public function getFormattedPlaytime(): string
    {
        $hours = floor($this->total_playtime / 3600);
        $minutes = floor(($this->total_playtime % 3600) / 60);

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }
        return "{$minutes}m";
    }
}
