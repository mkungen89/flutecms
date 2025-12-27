<?php

namespace Flute\Modules\ArmaBattlelog\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table\Index;
use Flute\Core\Database\Entities\DatabaseEntity;

#[Entity(table: 'battlelog_achievements')]
#[Index(columns: ['code'], unique: true)]
class Achievement extends DatabaseEntity
{
    #[Column(type: 'primary')]
    public int $id;

    /** @var string Unique code for the achievement */
    #[Column(type: 'string', length: 64)]
    public string $code;

    #[Column(type: 'string', length: 128)]
    public string $name;

    #[Column(type: 'text', nullable: true)]
    public ?string $description = null;

    /** @var string Category: combat, vehicle, teamplay, objective, general */
    #[Column(type: 'string', length: 50, default: 'general')]
    public string $category = 'general';

    /** @var string Rarity: common, uncommon, rare, epic, legendary */
    #[Column(type: 'string', length: 20, default: 'common')]
    public string $rarity = 'common';

    #[Column(type: 'string', length: 255, nullable: true)]
    public ?string $icon_url = null;

    /** @var string Requirement type: kills, headshots, wins, playtime, etc. */
    #[Column(type: 'string', length: 50)]
    public string $requirement_type;

    /** @var int Requirement value */
    #[Column(type: 'integer', default: 1)]
    public int $requirement_value = 1;

    /** @var int Points awarded */
    #[Column(type: 'integer', default: 10)]
    public int $points = 10;

    /** @var bool Is this a hidden achievement? */
    #[Column(type: 'boolean', default: false)]
    public bool $is_hidden = false;

    #[Column(type: 'boolean', default: true)]
    public bool $is_active = true;

    /** @var int Sort order for display */
    #[Column(type: 'integer', default: 0)]
    public int $sort_order = 0;

    #[Column(type: 'datetime')]
    public \DateTimeInterface $created_at;

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
    }

    /**
     * Get rarity color
     */
    public function getRarityColor(): string
    {
        return match ($this->rarity) {
            'common' => '#9e9e9e',
            'uncommon' => '#4caf50',
            'rare' => '#2196f3',
            'epic' => '#9c27b0',
            'legendary' => '#ff9800',
            default => '#9e9e9e',
        };
    }

    /**
     * Get category display name
     */
    public function getCategoryName(): string
    {
        return match ($this->category) {
            'combat' => 'Combat',
            'vehicle' => 'Vehicle',
            'teamplay' => 'Teamplay',
            'objective' => 'Objective',
            'general' => 'General',
            default => ucfirst($this->category),
        };
    }
}
