<?php

namespace Flute\Modules\ArmaReforgerServerManager\Http\Controllers;

use Exception;
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
            // Use cached/direct entity data instead of calling service for each server
            // to avoid N+1 performance problem
            $isRunning = $server->isRunning();

            // Update status if inconsistent (auto-correction for stale state)
            if ($server->status === 'running' && !$isRunning) {
                $previousStatus = $server->status;
                $previousPid = $server->pid;

                $server->status = 'stopped';
                $server->pid = null;

                try {
                    $server->save();

                    // Audit log: Record the automatic status correction
                    logs('modules')->info('Arma Reforger server status auto-corrected', [
                        'action' => 'server_status_auto_correction',
                        'server_id' => $server->id,
                        'server_name' => $server->name,
                        'previous_status' => $previousStatus,
                        'new_status' => 'stopped',
                        'previous_pid' => $previousPid,
                        'reason' => 'Process no longer running',
                        'triggered_by' => 'api_list_servers',
                        'user_id' => user()->id ?? null,
                        'ip_address' => request()->getClientIp(),
                        'timestamp' => date('Y-m-d H:i:s'),
                    ]);
                } catch (Exception $e) {
                    // Log the error but continue processing other servers
                    // to ensure graceful degradation
                    logs('modules')->error('Failed to persist server status correction', [
                        'server_id' => $server->id,
                        'server_name' => $server->name,
                        'error' => $e->getMessage(),
                        'previous_status' => $previousStatus,
                        'attempted_status' => 'stopped',
                    ]);

                    // Revert in-memory state to avoid inconsistency in response
                    $server->status = $previousStatus;
                    $server->pid = $previousPid;
                }
            }

            $result[] = [
                'id' => $server->id,
                'name' => $server->serverName,
                'address' => $server->publicAddress ?: $server->bindAddress,
                'port' => $server->publicPort ?: $server->bindPort,
                'maxPlayers' => $server->maxPlayers,
                'status' => $server->status,
                'running' => $isRunning,
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
