<?php

namespace Flute\Modules\ArmaBattlelog\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Table\Index;
use Flute\Core\Database\Entities\DatabaseEntity;

#[Entity(table: 'battlelog_player_sessions')]
#[Index(columns: ['session_id'])]
#[Index(columns: ['player_id'])]
class PlayerSession extends DatabaseEntity
{
    #[Column(type: 'primary')]
    public int $id;

    #[Column(type: 'integer')]
    public int $session_id;

    #[BelongsTo(target: GameSession::class)]
    public GameSession $session;

    #[Column(type: 'integer')]
    public int $player_id;

    #[BelongsTo(target: BattlelogPlayer::class)]
    public BattlelogPlayer $player;

    /** @var string Faction: us, ussr */
    #[Column(type: 'string', length: 20)]
    public string $faction;

    #[Column(type: 'datetime')]
    public \DateTimeInterface $joined_at;

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeInterface $left_at = null;

    /** @var int Kills in this session */
    #[Column(type: 'integer', default: 0)]
    public int $kills = 0;

    /** @var int Deaths in this session */
    #[Column(type: 'integer', default: 0)]
    public int $deaths = 0;

    /** @var int Assists in this session */
    #[Column(type: 'integer', default: 0)]
    public int $assists = 0;

    /** @var int Score in this session */
    #[Column(type: 'integer', default: 0)]
    public int $score = 0;

    /** @var int Headshots in this session */
    #[Column(type: 'integer', default: 0)]
    public int $headshots = 0;

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

    /** @var int Vehicle kills */
    #[Column(type: 'integer', default: 0)]
    public int $vehicle_kills = 0;

    /** @var float Longest kill distance */
    #[Column(type: 'float', default: 0)]
    public float $longest_kill = 0;

    /** @var int Best killstreak in this session */
    #[Column(type: 'integer', default: 0)]
    public int $best_killstreak = 0;

    /** @var bool Did the player's team win? */
    #[Column(type: 'boolean', nullable: true)]
    public ?bool $is_winner = null;

    /** @var bool Was this player MVP? */
    #[Column(type: 'boolean', default: false)]
    public bool $is_mvp = false;

    /** @var string JSON for additional stats */
    #[Column(type: 'text', nullable: true)]
    public ?string $stats_json = null;

    public function __construct()
    {
        $this->joined_at = new \DateTimeImmutable();
    }

    /**
     * Get session duration for this player
     */
    public function getDuration(): int
    {
        $end = $this->left_at ?? new \DateTimeImmutable();
        return $end->getTimestamp() - $this->joined_at->getTimestamp();
    }

    /**
     * Get K/D ratio for this session
     */
    public function getKDRatio(): float
    {
        if ($this->deaths === 0) {
            return (float) $this->kills;
        }
        return round($this->kills / $this->deaths, 2);
    }

    /**
     * Get score per minute for this session
     */
    public function getScorePerMinute(): float
    {
        $minutes = $this->getDuration() / 60;
        if ($minutes === 0) {
            return 0;
        }
        return round($this->score / $minutes, 0);
    }

    /**
     * Get additional stats as array
     */
    public function getStatsArray(): array
    {
        if (!$this->stats_json) {
            return [];
        }
        return json_decode($this->stats_json, true) ?? [];
    }
}
