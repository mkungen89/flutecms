<?php

namespace Flute\Modules\ArmaReforgerServerManager\Services;

use Exception;
use Flute\Modules\ArmaReforgerServerManager\Database\Entities\ReforgerMod;
use Flute\Modules\ArmaReforgerServerManager\Database\Entities\ReforgerServer;
use Flute\Modules\ArmaReforgerServerManager\Database\Entities\ReforgerServerMod;

/**
 * Service for managing Arma Reforger mods.
 */
class ReforgerModService
{
    protected SteamCMDService $steamcmd;

    // Bohemia Workshop API base URL
    protected string $workshopApiUrl = 'https://reforger.armaplatform.com/api/v1';

    public function __construct(SteamCMDService $steamcmd)
    {
        $this->steamcmd = $steamcmd;
    }

    /**
     * Get all mods.
     */
    public function getAllMods(): array
    {
        return ReforgerMod::findAll();
    }

    /**
     * Get mod by ID.
     */
    public function getMod(int $id): ?ReforgerMod
    {
        return ReforgerMod::findByPK($id);
    }

    /**
     * Get mod by workshop ID.
     */
    public function getModByWorkshopId(string $workshopId): ?ReforgerMod
    {
        return ReforgerMod::findOne(['workshopId' => $workshopId]);
    }

    /**
     * Add a mod from the workshop.
     */
    public function addMod(string $workshopId): ReforgerMod
    {
        // Check if already exists
        $existing = $this->getModByWorkshopId($workshopId);
        if ($existing) {
            return $existing;
        }

        // Fetch mod info from workshop
        $modInfo = $this->fetchModInfo($workshopId);

        $mod = new ReforgerMod();
        $mod->workshopId = $workshopId;
        $mod->name = $modInfo['name'] ?? 'Unknown Mod';
        $mod->description = $modInfo['description'] ?? null;
        $mod->author = $modInfo['author'] ?? null;
        $mod->version = $modInfo['version'] ?? null;
        $mod->imageUrl = $modInfo['imageUrl'] ?? null;
        $mod->workshopUrl = $modInfo['url'] ?? null;
        $mod->fileSize = $modInfo['fileSize'] ?? null;
        $mod->lastUpdated = isset($modInfo['lastUpdated'])
            ? new \DateTimeImmutable($modInfo['lastUpdated'])
            : null;
        $mod->save();

        return $mod;
    }

    /**
     * Update mod information from the workshop.
     */
    public function refreshModInfo(ReforgerMod $mod): ReforgerMod
    {
        $modInfo = $this->fetchModInfo($mod->workshopId);

        if (!empty($modInfo)) {
            $mod->name = $modInfo['name'] ?? $mod->name;
            $mod->description = $modInfo['description'] ?? $mod->description;
            $mod->author = $modInfo['author'] ?? $mod->author;
            $mod->version = $modInfo['version'] ?? $mod->version;
            $mod->imageUrl = $modInfo['imageUrl'] ?? $mod->imageUrl;
            $mod->fileSize = $modInfo['fileSize'] ?? $mod->fileSize;
            $mod->lastUpdated = isset($modInfo['lastUpdated'])
                ? new \DateTimeImmutable($modInfo['lastUpdated'])
                : $mod->lastUpdated;
            $mod->save();
        }

        return $mod;
    }

    /**
     * Delete a mod.
     */
    public function deleteMod(ReforgerMod $mod): bool
    {
        // Remove from all servers first
        $serverMods = ReforgerServerMod::query()
            ->where('mod_id', $mod->id)
            ->fetchAll();

        foreach ($serverMods as $serverMod) {
            $serverMod->delete();
        }

        // Delete local files if downloaded
        if ($mod->localPath && is_dir($mod->localPath)) {
            $this->deleteDirectory($mod->localPath);
        }

        $mod->delete();
        return true;
    }

    /**
     * Add a mod to a server.
     */
    public function addModToServer(ReforgerServer $server, ReforgerMod $mod, int $loadOrder = 0): ReforgerServerMod
    {
        // Check if already added
        $existing = ReforgerServerMod::findOne([
            'server_id' => $server->id,
            'mod_id' => $mod->id,
        ]);

        if ($existing) {
            return $existing;
        }

        $serverMod = new ReforgerServerMod();
        $serverMod->server = $server;
        $serverMod->mod = $mod;
        $serverMod->loadOrder = $loadOrder;
        $serverMod->enabled = true;
        $serverMod->save();

        return $serverMod;
    }

    /**
     * Remove a mod from a server.
     */
    public function removeModFromServer(ReforgerServer $server, ReforgerMod $mod): bool
    {
        $serverMod = ReforgerServerMod::findOne([
            'server_id' => $server->id,
            'mod_id' => $mod->id,
        ]);

        if ($serverMod) {
            $serverMod->delete();
            return true;
        }

        return false;
    }

    /**
     * Get mods for a server.
     */
    public function getServerMods(ReforgerServer $server): array
    {
        return ReforgerServerMod::query()
            ->where('server_id', $server->id)
            ->orderBy('loadOrder', 'ASC')
            ->fetchAll();
    }

    /**
     * Update mod load order for a server.
     */
    public function updateModOrder(ReforgerServer $server, array $modIds): void
    {
        foreach ($modIds as $order => $modId) {
            $serverMod = ReforgerServerMod::findOne([
                'server_id' => $server->id,
                'mod_id' => $modId,
            ]);

            if ($serverMod) {
                $serverMod->loadOrder = $order;
                $serverMod->save();
            }
        }
    }

    /**
     * Toggle mod enabled status for a server.
     */
    public function toggleServerMod(ReforgerServer $server, ReforgerMod $mod): bool
    {
        $serverMod = ReforgerServerMod::findOne([
            'server_id' => $server->id,
            'mod_id' => $mod->id,
        ]);

        if ($serverMod) {
            $serverMod->enabled = !$serverMod->enabled;
            $serverMod->save();
            return $serverMod->enabled;
        }

        return false;
    }

    /**
     * Search for mods in the workshop.
     */
    public function searchWorkshop(string $query, int $limit = 20): array
    {
        try {
            $url = $this->workshopApiUrl . '/workshop/search?' . http_build_query([
                'q' => $query,
                'limit' => $limit,
                'gameId' => 'reforger',
            ]);

            $response = $this->makeRequest($url);

            return $response['items'] ?? [];
        } catch (Exception $e) {
            logs('modules')->error('Workshop search failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetch mod information from the workshop.
     */
    protected function fetchModInfo(string $workshopId): array
    {
        try {
            // Try the Bohemia Workshop API
            $url = $this->workshopApiUrl . '/workshop/item/' . $workshopId;
            $response = $this->makeRequest($url);

            return [
                'name' => $response['name'] ?? 'Unknown',
                'description' => $response['description'] ?? null,
                'author' => $response['author']['name'] ?? null,
                'version' => $response['version'] ?? null,
                'imageUrl' => $response['thumbnail'] ?? null,
                'url' => $response['url'] ?? "https://reforger.armaplatform.com/workshop/{$workshopId}",
                'fileSize' => $response['size'] ?? null,
                'lastUpdated' => $response['updatedAt'] ?? null,
            ];
        } catch (Exception $e) {
            logs('modules')->warning('Failed to fetch mod info for ' . $workshopId . ': ' . $e->getMessage());

            // Return minimal info
            return [
                'name' => 'Mod ' . $workshopId,
                'url' => "https://reforger.armaplatform.com/workshop/{$workshopId}",
            ];
        }
    }

    /**
     * Make an HTTP request to the workshop API.
     */
    protected function makeRequest(string $url): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'User-Agent: FluteCMS-ArmaReforgerManager/1.0',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("HTTP request failed: {$error}");
        }

        if ($httpCode !== 200) {
            throw new Exception("HTTP request returned status {$httpCode}");
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response');
        }

        return $data;
    }

    /**
     * Recursively delete a directory.
     */
    protected function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }

        return rmdir($dir);
    }

    /**
     * Get popular/featured mods from the workshop.
     */
    public function getFeaturedMods(int $limit = 10): array
    {
        try {
            $url = $this->workshopApiUrl . '/workshop/featured?' . http_build_query([
                'limit' => $limit,
                'gameId' => 'reforger',
            ]);

            $response = $this->makeRequest($url);

            return $response['items'] ?? [];
        } catch (Exception $e) {
            logs('modules')->error('Failed to fetch featured mods: ' . $e->getMessage());
            return [];
        }
    }
}
