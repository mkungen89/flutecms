<?php

namespace Flute\Modules\ArmaBattlelog\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table\Index;
use Flute\Core\Database\Entities\DatabaseEntity;

#[Entity(table: 'battlelog_vehicles')]
#[Index(columns: ['internal_id'], unique: true)]
class Vehicle extends DatabaseEntity
{
    #[Column(type: 'primary')]
    public int $id;

    /** @var string Internal game ID for the vehicle */
    #[Column(type: 'string', length: 128)]
    public string $internal_id;

    #[Column(type: 'string', length: 128)]
    public string $name;

    /** @var string Category: tank, apc, ifv, transport, helicopter, boat */
    #[Column(type: 'string', length: 50)]
    public string $category;

    /** @var string Faction: us, ussr, neutral */
    #[Column(type: 'string', length: 20, default: 'neutral')]
    public string $faction = 'neutral';

    #[Column(type: 'text', nullable: true)]
    public ?string $description = null;

    #[Column(type: 'string', length: 255, nullable: true)]
    public ?string $icon_url = null;

    /** @var int Number of seats */
    #[Column(type: 'integer', default: 1)]
    public int $seats = 1;

    /** @var bool Has weapons */
    #[Column(type: 'boolean', default: false)]
    public bool $has_weapons = false;

    #[Column(type: 'boolean', default: true)]
    public bool $is_active = true;

    #[Column(type: 'datetime')]
    public \DateTimeInterface $created_at;

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
    }

    /**
     * Get category display name
     */
    public function getCategoryName(): string
    {
        return match ($this->category) {
            'tank' => 'Tanks',
            'apc' => 'APCs',
            'ifv' => 'IFVs',
            'transport' => 'Transport',
            'helicopter' => 'Helicopters',
            'boat' => 'Boats',
            default => ucfirst($this->category),
        };
    }
}
