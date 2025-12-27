<?php

namespace Flute\Modules\ArmaBattlelog\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Table\Index;
use Flute\Core\Database\Entities\DatabaseEntity;

#[Entity(table: 'battlelog_kill_events')]
#[Index(columns: ['session_id'])]
#[Index(columns: ['killer_id'])]
#[Index(columns: ['victim_id'])]
#[Index(columns: ['timestamp'])]
class KillEvent extends DatabaseEntity
{
    #[Column(type: 'primary')]
    public int $id;

    #[Column(type: 'integer')]
    public int $session_id;

    #[BelongsTo(target: GameSession::class)]
    public GameSession $session;

    #[Column(type: 'integer', nullable: true)]
    public ?int $killer_id = null;

    #[BelongsTo(target: BattlelogPlayer::class, nullable: true)]
    public ?BattlelogPlayer $killer = null;

    #[Column(type: 'integer')]
    public int $victim_id;

    #[BelongsTo(target: BattlelogPlayer::class)]
    public BattlelogPlayer $victim;

    #[Column(type: 'integer', nullable: true)]
    public ?int $weapon_id = null;

    #[BelongsTo(target: Weapon::class, nullable: true)]
    public ?Weapon $weapon = null;

    #[Column(type: 'integer', nullable: true)]
    public ?int $vehicle_id = null;

    #[BelongsTo(target: Vehicle::class, nullable: true)]
    public ?Vehicle $vehicle = null;

    /** @var float Kill distance in meters */
    #[Column(type: 'float', default: 0)]
    public float $distance = 0;

    #[Column(type: 'boolean', default: false)]
    public bool $is_headshot = false;

    #[Column(type: 'boolean', default: false)]
    public bool $is_teamkill = false;

    #[Column(type: 'boolean', default: false)]
    public bool $is_suicide = false;

    #[Column(type: 'boolean', default: false)]
    public bool $is_roadkill = false;

    /** @var string Killer position as JSON [x, y, z] */
    #[Column(type: 'string', length: 100, nullable: true)]
    public ?string $killer_position = null;

    /** @var string Victim position as JSON [x, y, z] */
    #[Column(type: 'string', length: 100, nullable: true)]
    public ?string $victim_position = null;

    /** @var string Killer faction */
    #[Column(type: 'string', length: 20, nullable: true)]
    public ?string $killer_faction = null;

    /** @var string Victim faction */
    #[Column(type: 'string', length: 20, nullable: true)]
    public ?string $victim_faction = null;

    #[Column(type: 'datetime')]
    public \DateTimeInterface $timestamp;

    public function __construct()
    {
        $this->timestamp = new \DateTimeImmutable();
    }

    /**
     * Get killer position as array
     */
    public function getKillerPositionArray(): ?array
    {
        if (!$this->killer_position) {
            return null;
        }
        return json_decode($this->killer_position, true);
    }

    /**
     * Get victim position as array
     */
    public function getVictimPositionArray(): ?array
    {
        if (!$this->victim_position) {
            return null;
        }
        return json_decode($this->victim_position, true);
    }

    /**
     * Get kill type description
     */
    public function getKillType(): string
    {
        if ($this->is_suicide) {
            return 'Suicide';
        }
        if ($this->is_teamkill) {
            return 'Team Kill';
        }
        if ($this->is_roadkill) {
            return 'Roadkill';
        }
        if ($this->is_headshot) {
            return 'Headshot';
        }
        return 'Kill';
    }
}
