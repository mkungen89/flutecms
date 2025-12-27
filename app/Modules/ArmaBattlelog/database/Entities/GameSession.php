<?php

namespace Flute\Modules\ArmaBattlelog\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\Annotated\Annotation\Table\Index;
use Flute\Core\Database\Entities\DatabaseEntity;

#[Entity(table: 'battlelog_game_sessions')]
#[Index(columns: ['server_id'])]
#[Index(columns: ['started_at'])]
class GameSession extends DatabaseEntity
{
    #[Column(type: 'primary')]
    public int $id;

    /** @var string Server identifier */
    #[Column(type: 'string', length: 64)]
    public string $server_id;

    #[Column(type: 'string', length: 255, nullable: true)]
    public ?string $server_name = null;

    #[Column(type: 'integer', nullable: true)]
    public ?int $map_id = null;

    #[BelongsTo(target: GameMap::class, nullable: true)]
    public ?GameMap $map = null;

    /** @var string Scenario ID from Arma Reforger */
    #[Column(type: 'string', length: 255, nullable: true)]
    public ?string $scenario_id = null;

    /** @var string Game mode */
    #[Column(type: 'string', length: 50, default: 'conflict')]
    public string $game_mode = 'conflict';

    #[Column(type: 'datetime')]
    public \DateTimeInterface $started_at;

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeInterface $ended_at = null;

    /** @var string Winning faction: us, ussr, draw, null if ongoing */
    #[Column(type: 'string', length: 20, nullable: true)]
    public ?string $winner_faction = null;

    /** @var int US team final score */
    #[Column(type: 'integer', default: 0)]
    public int $us_score = 0;

    /** @var int USSR team final score */
    #[Column(type: 'integer', default: 0)]
    public int $ussr_score = 0;

    /** @var int Maximum players during session */
    #[Column(type: 'integer', default: 0)]
    public int $max_players = 0;

    /** @var int Total players who participated */
    #[Column(type: 'integer', default: 0)]
    public int $total_players = 0;

    /** @var int Total kills in session */
    #[Column(type: 'integer', default: 0)]
    public int $total_kills = 0;

    /** @var string Session status: active, ended, cancelled */
    #[Column(type: 'string', length: 20, default: 'active')]
    public string $status = 'active';

    /** @var string JSON data for additional session info */
    #[Column(type: 'text', nullable: true)]
    public ?string $session_data = null;

    #[HasMany(target: PlayerSession::class)]
    public array $playerSessions = [];

    #[HasMany(target: KillEvent::class)]
    public array $killEvents = [];

    public function __construct()
    {
        $this->started_at = new \DateTimeImmutable();
    }

    /**
     * Get session duration in seconds
     */
    public function getDuration(): int
    {
        $end = $this->ended_at ?? new \DateTimeImmutable();
        return $end->getTimestamp() - $this->started_at->getTimestamp();
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDuration(): string
    {
        $seconds = $this->getDuration();
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }
        return "{$minutes}m";
    }

    /**
     * Check if session is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get session data as array
     */
    public function getSessionDataArray(): array
    {
        if (!$this->session_data) {
            return [];
        }
        return json_decode($this->session_data, true) ?? [];
    }
}
