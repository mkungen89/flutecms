<?php

namespace Flute\Modules\ArmaReforgerServerManager\Database\Repositories;

use Cycle\ORM\Select\Repository;

class ReforgerModRepository extends Repository
{
    /**
     * Find all enabled mods.
     */
    public function findEnabled(): array
    {
        return $this->select()
            ->where('enabled', true)
            ->orderBy('name')
            ->fetchAll();
    }

    /**
     * Find mod by workshop ID.
     */
    public function findByWorkshopId(string $workshopId)
    {
        return $this->select()
            ->where('workshopId', $workshopId)
            ->fetchOne();
    }

    /**
     * Find downloaded mods.
     */
    public function findDownloaded(): array
    {
        return $this->select()
            ->where('isDownloaded', true)
            ->orderBy('name')
            ->fetchAll();
    }

    /**
     * Search mods by name.
     */
    public function searchByName(string $query): array
    {
        return $this->select()
            ->where('name', 'LIKE', "%{$query}%")
            ->orderBy('name')
            ->fetchAll();
    }
}
