<?php

namespace Flute\Modules\ArmaReforgerServerManager\Services;

use Exception;

/**
 * Service for managing SteamCMD operations.
 * Handles downloading server files and mods via SteamCMD.
 */
class SteamCMDService
{
    // Arma Reforger Dedicated Server App ID
    public const SERVER_APP_ID = 1874900;

    protected string $steamcmdPath;
    protected string $installBasePath;

    public function __construct()
    {
        $this->steamcmdPath = config('arma-reforger.steamcmd_path', '/usr/games/steamcmd');
        $this->installBasePath = config('arma-reforger.install_path', storage_path('reforger-servers'));
    }

    /**
     * Set the SteamCMD executable path.
     */
    public function setSteamCMDPath(string $path): self
    {
        $this->steamcmdPath = $path;
        return $this;
    }

    /**
     * Set the base installation path for servers.
     */
    public function setInstallBasePath(string $path): self
    {
        $this->installBasePath = $path;
        return $this;
    }

    /**
     * Get the base installation path.
     */
    public function getInstallBasePath(): string
    {
        return $this->installBasePath;
    }

    /**
     * Check if SteamCMD is available and working.
     */
    public function isAvailable(): bool
    {
        if (!file_exists($this->steamcmdPath)) {
            return false;
        }

        exec("{$this->steamcmdPath} +quit 2>&1", $output, $returnCode);
        return $returnCode === 0;
    }

    /**
     * Get SteamCMD version information.
     */
    public function getVersion(): ?string
    {
        exec("{$this->steamcmdPath} +quit 2>&1", $output, $returnCode);

        foreach ($output as $line) {
            if (str_contains($line, 'Steam Console Client')) {
                return $line;
            }
        }

        return null;
    }

    /**
     * Install or update the Arma Reforger server files.
     */
    public function installServer(string $installPath, ?callable $progressCallback = null): array
    {
        if (!$this->ensureDirectoryExists($installPath)) {
            throw new Exception("Failed to create installation directory: {$installPath}");
        }

        $command = $this->buildServerInstallCommand($installPath);

        return $this->executeCommand($command, $progressCallback);
    }

    /**
     * Validate server files integrity.
     */
    public function validateServerFiles(string $installPath, ?callable $progressCallback = null): array
    {
        $command = $this->buildServerInstallCommand($installPath, true);

        return $this->executeCommand($command, $progressCallback);
    }

    /**
     * Download a workshop mod.
     */
    public function downloadMod(string $workshopId, string $downloadPath, ?callable $progressCallback = null): array
    {
        if (!$this->ensureDirectoryExists($downloadPath)) {
            throw new Exception("Failed to create mod directory: {$downloadPath}");
        }

        $command = $this->buildModDownloadCommand($workshopId, $downloadPath);

        return $this->executeCommand($command, $progressCallback);
    }

    /**
     * Update all mods in a directory.
     */
    public function updateMods(array $workshopIds, string $modsPath, ?callable $progressCallback = null): array
    {
        $results = [];

        foreach ($workshopIds as $workshopId) {
            if ($progressCallback) {
                $progressCallback("Updating mod: {$workshopId}");
            }

            $results[$workshopId] = $this->downloadMod($workshopId, $modsPath, $progressCallback);
        }

        return $results;
    }

    /**
     * Get server installation status.
     */
    public function getServerInstallStatus(string $installPath): array
    {
        $serverExecutable = $this->getServerExecutable($installPath);
        $isInstalled = file_exists($serverExecutable);

        $status = [
            'installed' => $isInstalled,
            'path' => $installPath,
            'executable' => $serverExecutable,
            'size' => 0,
            'lastModified' => null,
        ];

        if ($isInstalled) {
            $status['lastModified'] = filemtime($serverExecutable);
            $status['size'] = $this->getDirectorySize($installPath);
        }

        return $status;
    }

    /**
     * Get the server executable path.
     */
    public function getServerExecutable(string $installPath): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return $installPath . DIRECTORY_SEPARATOR . 'ArmaReforgerServer.exe';
        }

        return $installPath . DIRECTORY_SEPARATOR . 'ArmaReforgerServer';
    }

    /**
     * Get the default server config path.
     */
    public function getDefaultConfigPath(string $installPath): string
    {
        return $installPath . DIRECTORY_SEPARATOR . 'server.json';
    }

    /**
     * Build the SteamCMD command for server installation.
     */
    protected function buildServerInstallCommand(string $installPath, bool $validate = false): string
    {
        $command = sprintf(
            '%s +force_install_dir "%s" +login anonymous +app_update %d%s +quit',
            escapeshellcmd($this->steamcmdPath),
            $installPath,
            self::SERVER_APP_ID,
            $validate ? ' validate' : ''
        );

        return $command;
    }

    /**
     * Build the SteamCMD command for mod download.
     * Note: Arma Reforger uses the Bohemia Workshop, not Steam Workshop.
     * This method is provided for reference; mods are typically downloaded
     * through the Reforger launcher or Workshop API.
     */
    protected function buildModDownloadCommand(string $workshopId, string $downloadPath): string
    {
        // Arma Reforger mods use Bohemia's workshop
        // SteamCMD workshop_download_item command is for Steam Workshop
        // This is a placeholder - actual implementation would use Bohemia Workshop API
        $command = sprintf(
            '%s +force_install_dir "%s" +login anonymous +workshop_download_item %d %s +quit',
            escapeshellcmd($this->steamcmdPath),
            $downloadPath,
            self::SERVER_APP_ID,
            $workshopId
        );

        return $command;
    }

    /**
     * Execute a SteamCMD command.
     */
    protected function executeCommand(string $command, ?callable $progressCallback = null): array
    {
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorSpec, $pipes);

        if (!is_resource($process)) {
            throw new Exception('Failed to start SteamCMD process');
        }

        fclose($pipes[0]);

        $output = [];
        $errors = [];

        // Read stdout
        while (!feof($pipes[1])) {
            $line = fgets($pipes[1]);
            if ($line !== false) {
                $output[] = trim($line);
                if ($progressCallback) {
                    $progressCallback(trim($line));
                }
            }
        }

        // Read stderr
        while (!feof($pipes[2])) {
            $line = fgets($pipes[2]);
            if ($line !== false) {
                $errors[] = trim($line);
            }
        }

        fclose($pipes[1]);
        fclose($pipes[2]);

        $returnCode = proc_close($process);

        return [
            'success' => $returnCode === 0,
            'returnCode' => $returnCode,
            'output' => $output,
            'errors' => $errors,
        ];
    }

    /**
     * Ensure a directory exists, creating it if necessary.
     */
    protected function ensureDirectoryExists(string $path): bool
    {
        if (is_dir($path)) {
            return true;
        }

        return mkdir($path, 0755, true);
    }

    /**
     * Get the total size of a directory.
     */
    protected function getDirectorySize(string $path): int
    {
        $size = 0;

        if (!is_dir($path)) {
            return $size;
        }

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path)) as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    /**
     * Get formatted size string.
     */
    public function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unitIndex = 0;
        $size = $bytes;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Get installation instructions for SteamCMD.
     */
    public function getInstallInstructions(): array
    {
        return [
            'linux' => [
                'Debian/Ubuntu' => [
                    'sudo dpkg --add-architecture i386',
                    'sudo apt update',
                    'sudo apt install steamcmd',
                ],
                'Arch Linux' => [
                    'yay -S steamcmd',
                ],
                'Manual' => [
                    'mkdir ~/steamcmd && cd ~/steamcmd',
                    'curl -sqL "https://steamcdn-a.akamaihd.net/client/installer/steamcmd_linux.tar.gz" | tar zxvf -',
                ],
            ],
            'windows' => [
                'Download' => [
                    'Download from https://steamcdn-a.akamaihd.net/client/installer/steamcmd.zip',
                    'Extract to C:\\steamcmd',
                    'Run steamcmd.exe once to initialize',
                ],
            ],
        ];
    }
}
