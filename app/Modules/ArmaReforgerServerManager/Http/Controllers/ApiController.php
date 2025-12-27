<?php

namespace Flute\Modules\ArmaReforgerServerManager\Http\Controllers;

use Flute\Core\Support\BaseController;
use Flute\Modules\ArmaReforgerServerManager\Database\Entities\ReforgerServer;
use Flute\Modules\ArmaReforgerServerManager\Services\ReforgerServerService;

class ApiController extends BaseController
{
    protected ReforgerServerService $serverService;

    public function __construct(ReforgerServerService $serverService)
    {
        $this->serverService = $serverService;
    }

    /**
     * List all enabled servers with basic info.
     */
    public function listServers()
    {
        $servers = ReforgerServer::query()
            ->where('enabled', true)
            ->fetchAll();

        $result = [];
        foreach ($servers as $server) {
            $status = $this->serverService->getServerStatus($server);
            $result[] = [
                'id' => $server->id,
                'name' => $server->serverName,
                'address' => $status['connection']['address'],
                'port' => $status['connection']['port'],
                'maxPlayers' => $server->maxPlayers,
                'status' => $server->status,
                'running' => $status['running'],
            ];
        }

        return json([
            'success' => true,
            'servers' => $result,
        ]);
    }

    /**
     * Get detailed status for a specific server.
     */
    public function serverStatus(int $id)
    {
        $server = ReforgerServer::findByPK($id);

        if (!$server) {
            return json([
                'success' => false,
                'error' => 'Server not found',
            ], 404);
        }

        if (!$server->enabled) {
            return json([
                'success' => false,
                'error' => 'Server is disabled',
            ], 403);
        }

        $status = $this->serverService->getServerStatus($server);

        return json([
            'success' => true,
            'server' => $status,
        ]);
    }
}
