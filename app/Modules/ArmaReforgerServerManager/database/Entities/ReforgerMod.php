<?php

namespace Flute\Modules\ArmaReforgerServerManager\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table\Index;
use Flute\Core\Database\Entities\DatabaseEntity;

#[Entity(repository: \Flute\Modules\ArmaReforgerServerManager\Database\Repositories\ReforgerModRepository::class, table: 'reforger_mods')]
#[Index(columns: ['workshop_id'], unique: true)]
class ReforgerMod extends DatabaseEntity
{
    #[Column(type: 'primary')]
    public int $id;

    #[Column(type: 'string', length: 255)]
    public string $workshopId;

    #[Column(type: 'string', length: 255)]
    public string $name;

    #[Column(type: 'text', nullable: true)]
    public ?string $description = null;

    #[Column(type: 'string', length: 255, nullable: true)]
    public ?string $author = null;

    #[Column(type: 'string', length: 50, nullable: true)]
    public ?string $version = null;

    #[Column(type: 'string', length: 500, nullable: true)]
    public ?string $imageUrl = null;

    #[Column(type: 'string', length: 500, nullable: true)]
    public ?string $workshopUrl = null;

    #[Column(type: 'bigInteger', nullable: true)]
    public ?int $fileSize = null;

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeInterface $lastUpdated = null;

    #[Column(type: 'boolean', default: false)]
    public bool $isDownloaded = false;

    #[Column(type: 'string', length: 255, nullable: true)]
    public ?string $localPath = null;

    #[Column(type: 'boolean', default: true)]
    public bool $enabled = true;

    /**
     * Get the formatted file size.
     */
    public function getFormattedFileSize(): string
    {
        if ($this->fileSize === null) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        $size = $this->fileSize;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Get the workshop URL for this mod.
     */
    public function getWorkshopUrl(): string
    {
        return $this->workshopUrl ?? "https://reforger.armaplatform.com/workshop/{$this->workshopId}";
    }
}
