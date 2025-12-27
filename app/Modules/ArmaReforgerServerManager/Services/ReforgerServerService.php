<?php

namespace Flute\Modules\ArmaReforgerServerManager\Services;

use Exception;
use Flute\Modules\ArmaReforgerServerManager\Database\Entities\ReforgerServer;
use Flute\Modules\ArmaReforgerServerManager\Database\Entities\ReforgerServerMod;

/**
 * Service for managing Arma Reforger game servers.
 */
class ReforgerServerService
{
    protected SteamCMDService $steamcmd;

    public function __construct(SteamCMDService $steamcmd)
    {
        $this->steamcmd = $steamcmd;
    }

    /**
     * Get all servers.
     */
    public function getAllServers(): array
    {
        return ReforgerServer::findAll();
    }

    /**
     * Get server by ID.
     */
    public function getServer(int $id): ?ReforgerServer
    {
        return ReforgerServer::findByPK($id);
    }

    /**
     * Create a new server.
     */
    public function createServer(array $data): ReforgerServer
    {
        $server = new ReforgerServer();
        $this->fillServerData($server, $data);
        $server->status = 'stopped';
        $server->save();

        return $server;
    }

    /**
     * Update an existing server.
     */
    public function updateServer(ReforgerServer $server, array $data): ReforgerServer
    {
        $this->fillServerData($server, $data);
        $server->save();

        return $server;
    }

    /**
     * Delete a server.
     */
    public function deleteServer(ReforgerServer $server): bool
    {
        if ($server->isRunning()) {
            $this->stopServer($server);
        }

        $server->delete();
        return true;
    }

    /**
     * Install server files.
     */
    public function installServerFiles(ReforgerServer $server, ?callable $progressCallback = null): array
    {
        if (!$server->installPath) {
            $server->installPath = $this->steamcmd->getInstallBasePath() . DIRECTORY_SEPARATOR . 'server_' . $server->id;
            $server->save();
        }

        $result = $this->steamcmd->installServer($server->installPath, $progressCallback);

        if ($result['success']) {
            $server->installedVersion = $this->getInstalledVersion($server);
            $server->save();
        }

        return $result;
    }

    /**
     * Update server files.
     */
    public function updateServerFiles(ReforgerServer $server, ?callable $progressCallback = null): array
    {
        if (!$server->installPath) {
            throw new Exception('Server has no installation path');
        }

        $result = $this->steamcmd->installServer($server->installPath, $progressCallback);

        if ($result['success']) {
            $server->installedVersion = $this->getInstalledVersion($server);
            $server->save();
        }

        return $result;
    }

    /**
     * Validate server files.
     */
    public function validateServerFiles(ReforgerServer $server, ?callable $progressCallback = null): array
    {
        if (!$server->installPath) {
            throw new Exception('Server has no installation path');
        }

        return $this->steamcmd->validateServerFiles($server->installPath, $progressCallback);
    }

    /**
     * Start the server.
     */
    public function startServer(ReforgerServer $server): bool
    {
        if ($server->isRunning()) {
            throw new Exception('Server is already running');
        }

        if (!$server->installPath || !file_exists($this->steamcmd->getServerExecutable($server->installPath))) {
            throw new Exception('Server files not installed');
        }

        // Write config file
        $configPath = $this->writeServerConfig($server);

        // Build start command
        $executable = $this->steamcmd->getServerExecutable($server->installPath);
        $command = $this->buildStartCommand($executable, $configPath, $server);

        // Start the process
        $pid = $this->launchProcess($command, $server->installPath);

        if ($pid) {
            $server->pid = $pid;
            $server->status = 'running';
            $server->lastStarted = new \DateTimeImmutable();
            $server->save();

            return true;
        }

        return false;
    }

    /**
     * Stop the server.
     */
    public function stopServer(ReforgerServer $server): bool
    {
        if (!$server->isRunning()) {
            $server->status = 'stopped';
            $server->pid = null;
            $server->save();
            return true;
        }

        $killed = $this->killProcess($server->pid);

        if ($killed) {
            $server->status = 'stopped';
            $server->pid = null;
            $server->lastStopped = new \DateTimeImmutable();
            $server->save();

            return true;
        }

        return false;
    }

    /**
     * Restart the server.
     */
    public function restartServer(ReforgerServer $server): bool
    {
        $this->stopServer($server);
        sleep(2); // Give it time to fully stop
        return $this->startServer($server);
    }

    /**
     * Get server status.
     */
    public function getServerStatus(ReforgerServer $server): array
    {
        $isRunning = $server->isRunning();

        // Update status if inconsistent
        if ($server->status === 'running' && !$isRunning) {
            $server->status = 'stopped';
            $server->pid = null;
            $server->save();
        }

        $installStatus = $server->installPath
            ? $this->steamcmd->getServerInstallStatus($server->installPath)
            : ['installed' => false];

        return [
            'id' => $server->id,
            'name' => $server->name,
            'status' => $server->status,
            'running' => $isRunning,
            'pid' => $server->pid,
            'installed' => $installStatus['installed'],
            'installPath' => $server->installPath,
            'installedVersion' => $server->installedVersion,
            'lastStarted' => $server->lastStarted?->format('Y-m-d H:i:s'),
            'lastStopped' => $server->lastStopped?->format('Y-m-d H:i:s'),
            'connection' => [
                'address' => $server->publicAddress ?: $server->bindAddress,
                'port' => $server->publicPort ?: $server->bindPort,
            ],
        ];
    }

    /**
     * Write the server configuration file.
     */
    public function writeServerConfig(ReforgerServer $server): string
    {
        $configPath = $this->steamcmd->getDefaultConfigPath($server->installPath);
        $config = $server->generateConfigFile();

        if (file_put_contents($configPath, $config) === false) {
            throw new Exception('Failed to write server configuration');
        }

        return $configPath;
    }

    /**
     * Get the installed server version.
     */
    protected function getInstalledVersion(ReforgerServer $server): ?string
    {
        // Try to read version from appmanifest or steam_appid
        $manifestPath = $server->installPath . DIRECTORY_SEPARATOR . 'steamapps' . DIRECTORY_SEPARATOR . 'appmanifest_' . SteamCMDService::SERVER_APP_ID . '.acf';

        if (file_exists($manifestPath)) {
            $content = file_get_contents($manifestPath);
            if (preg_match('/"buildid"\s+"(\d+)"/', $content, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Build the server start command.
     */
    protected function buildStartCommand(string $executable, string $configPath, ReforgerServer $server): string
    {
        $args = [
            '-config ' . escapeshellarg($configPath),
            '-maxFPS 60',
        ];

        if ($server->bindAddress && $server->bindAddress !== '0.0.0.0') {
            $args[] = '-bindAddress ' . $server->bindAddress;
        }

        if ($server->bindPort) {
            $args[] = '-bindPort ' . $server->bindPort;
        }

        return escapeshellcmd($executable) . ' ' . implode(' ', $args);
    }

    /**
     * Launch a background process.
     */
    protected function launchProcess(string $command, string $workingDir): ?int
    {
        $logFile = $workingDir . DIRECTORY_SEPARATOR . 'console.log';

        if (PHP_OS_FAMILY === 'Windows') {
            // Windows: Use start /B
            $command = "start /B /D \"{$workingDir}\" {$command} > \"{$logFile}\" 2>&1";
            pclose(popen($command, 'r'));

            // Get PID - this is tricky on Windows
            // For now, we'll rely on process name
            return null;
        } else {
            // Linux: Use nohup and background
            $command = "cd \"{$workingDir}\" && nohup {$command} > \"{$logFile}\" 2>&1 & echo $!";
            $output = shell_exec($command);

            return $output ? (int) trim($output) : null;
        }
    }

    /**
     * Kill a process by PID.
     */
    protected function killProcess(int $pid): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            exec("taskkill /F /PID {$pid} 2>&1", $output, $returnCode);
        } else {
            exec("kill -15 {$pid} 2>&1", $output, $returnCode);

            // If graceful shutdown doesn't work, force kill
            if ($returnCode !== 0) {
                exec("kill -9 {$pid} 2>&1", $output, $returnCode);
            }
        }

        return $returnCode === 0;
    }

    /**
     * Fill server entity with data.
     */
    protected function fillServerData(ReforgerServer $server, array $data): void
    {
        $fields = [
            'name', 'installPath', 'bindAddress', 'bindPort', 'publicAddress',
            'publicPort', 'a2sPort', 'steamQueryPort', 'adminPassword',
            'serverPassword', 'serverName', 'scenarioId', 'maxPlayers',
            'visible', 'crossPlatform', 'battleEye', 'thirdPersonView',
            'vonDisabled', 'enabled', 'configJson'
        ];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $server->$field = $data[$field];
            }
        }
    }

    /**
     * Get available scenarios.
     */
    public function getAvailableScenarios(): array
    {
        return [
            '{ECC61978EDCC2B5A}Missions/23_Campaign.conf' => 'Campaign - Everon',
            '{59AD59368755F41A}Missions/21_GM_Eden.conf' => 'Game Master - Everon',
            '{2BBBE828037C6F4B}Missions/22_GM_Arland.conf' => 'Game Master - Arland',
            '{28802845ADA64D52}Missions/20_Conflict.conf' => 'Conflict - Everon',
            '{DAA03C6E6099D50F}Missions/24_CombatOps.conf' => 'Combat Ops',
        ];
    }

    /**
     * Get server console log.
     */
    public function getServerLog(ReforgerServer $server, int $lines = 100): array
    {
        if (!$server->installPath) {
            return [];
        }

        $logFile = $server->installPath . DIRECTORY_SEPARATOR . 'console.log';

        if (!file_exists($logFile)) {
            return [];
        }

        $output = [];
        $file = new \SplFileObject($logFile, 'r');
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();

        $startLine = max(0, $totalLines - $lines);
        $file->seek($startLine);

        while (!$file->eof()) {
            $line = $file->fgets();
            if (trim($line) !== '') {
                $output[] = $line;
            }
        }

        return $output;
    }
}
