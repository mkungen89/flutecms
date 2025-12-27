<?php

namespace Flute\Modules\ArmaBattlelog\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table\Index;
use Flute\Core\Database\Entities\DatabaseEntity;

#[Entity(table: 'battlelog_weapons')]
#[Index(columns: ['internal_id'], unique: true)]
class Weapon extends DatabaseEntity
{
    #[Column(type: 'primary')]
    public int $id;

    /** @var string Internal game ID for the weapon */
    #[Column(type: 'string', length: 128)]
    public string $internal_id;

    #[Column(type: 'string', length: 128)]
    public string $name;

    /** @var string Category: assault_rifle, smg, sniper, lmg, pistol, launcher, grenade, melee */
    #[Column(type: 'string', length: 50)]
    public string $category;

    /** @var string Faction: us, ussr, neutral */
    #[Column(type: 'string', length: 20, default: 'neutral')]
    public string $faction = 'neutral';

    #[Column(type: 'text', nullable: true)]
    public ?string $description = null;

    #[Column(type: 'string', length: 255, nullable: true)]
    public ?string $icon_url = null;

    /** @var int Base damage value */
    #[Column(type: 'integer', default: 0)]
    public int $base_damage = 0;

    /** @var int Fire rate (rounds per minute) */
    #[Column(type: 'integer', default: 0)]
    public int $fire_rate = 0;

    /** @var int Magazine size */
    #[Column(type: 'integer', default: 0)]
    public int $magazine_size = 0;

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
            'assault_rifle' => 'Assault Rifles',
            'smg' => 'SMGs',
            'sniper' => 'Sniper Rifles',
            'lmg' => 'Machine Guns',
            'pistol' => 'Pistols',
            'launcher' => 'Launchers',
            'grenade' => 'Grenades',
            'melee' => 'Melee',
            default => ucfirst($this->category),
        };
    }

    /**
     * Get faction display name
     */
    public function getFactionName(): string
    {
        return match ($this->faction) {
            'us' => 'US Army',
            'ussr' => 'Soviet Union',
            default => 'Neutral',
        };
    }
}
