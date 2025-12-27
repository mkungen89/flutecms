<?php

namespace Flute\Modules\ArmaReforgerServerManager\Database\Repositories;

use Cycle\ORM\Select\Repository;
use Flute\Modules\ArmaReforgerServerManager\Database\Entities\ReforgerServer;

class ReforgerServerRepository extends Repository
{
    /**
     * Find all enabled servers.
     */
    public function findEnabled(): array
    {
        return $this->select()
            ->where('enabled', true)
            ->orderBy('name')
            ->fetchAll();
    }

    /**
     * Find servers by status.
     */
    public function findByStatus(string $status): array
    {
        return $this->select()
            ->where('status', $status)
            ->orderBy('name')
            ->fetchAll();
    }

    /**
     * Find running servers.
     */
    public function findRunning(): array
    {
        return $this->findByStatus('running');
    }

    /**
     * Count servers by status.
     */
    public function countByStatus(string $status): int
    {
        return $this->select()
            ->where('status', $status)
            ->count();
    }
}
