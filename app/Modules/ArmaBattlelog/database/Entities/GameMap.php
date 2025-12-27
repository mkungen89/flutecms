<?php

namespace Flute\Modules\ArmaBattlelog\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table\Index;
use Flute\Core\Database\Entities\DatabaseEntity;

#[Entity(table: 'battlelog_maps')]
#[Index(columns: ['internal_id'], unique: true)]
class GameMap extends DatabaseEntity
{
    #[Column(type: 'primary')]
    public int $id;

    /** @var string Internal game ID for the map */
    #[Column(type: 'string', length: 128)]
    public string $internal_id;

    #[Column(type: 'string', length: 128)]
    public string $name;

    #[Column(type: 'text', nullable: true)]
    public ?string $description = null;

    #[Column(type: 'string', length: 255, nullable: true)]
    public ?string $image_url = null;

    /** @var string Game mode: conflict, combat_ops, game_master, campaign */
    #[Column(type: 'string', length: 50, default: 'conflict')]
    public string $game_mode = 'conflict';

    /** @var int Map size in square km */
    #[Column(type: 'integer', default: 0)]
    public int $size_km = 0;

    #[Column(type: 'boolean', default: true)]
    public bool $is_active = true;

    #[Column(type: 'datetime')]
    public \DateTimeInterface $created_at;

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
    }

    /**
     * Get game mode display name
     */
    public function getGameModeName(): string
    {
        return match ($this->game_mode) {
            'conflict' => 'Conflict',
            'combat_ops' => 'Combat Ops',
            'game_master' => 'Game Master',
            'campaign' => 'Campaign',
            default => ucfirst($this->game_mode),
        };
    }
}
