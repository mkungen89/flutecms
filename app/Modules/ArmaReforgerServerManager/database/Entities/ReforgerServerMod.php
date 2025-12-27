<?php

namespace Flute\Modules\ArmaReforgerServerManager\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Table\Index;
use Flute\Core\Database\Entities\DatabaseEntity;

#[Entity(table: 'reforger_server_mods')]
#[Index(columns: ['server_id', 'mod_id'], unique: true)]
class ReforgerServerMod extends DatabaseEntity
{
    #[Column(type: 'primary')]
    public int $id;

    #[BelongsTo(target: ReforgerServer::class, nullable: false)]
    public ReforgerServer $server;

    #[BelongsTo(target: ReforgerMod::class, nullable: false)]
    public ReforgerMod $mod;

    #[Column(type: 'integer', default: 0)]
    public int $loadOrder = 0;

    #[Column(type: 'boolean', default: true)]
    public bool $enabled = true;
}
