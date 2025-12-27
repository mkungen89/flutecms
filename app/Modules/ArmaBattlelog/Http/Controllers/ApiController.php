<?php

namespace Flute\Modules\ArmaBattlelog\Http\Controllers;

use Flute\Core\Support\BaseController;
use Flute\Core\Support\FluteRequest;
use Flute\Modules\ArmaBattlelog\Services\BattlelogService;
use Flute\Modules\ArmaBattlelog\Services\DemoDataService;
use Flute\Modules\ArmaBattlelog\Services\StatsCalculatorService;
use Symfony\Component\HttpFoundation\JsonResponse;

class ApiController extends BaseController
{
    protected BattlelogService $battlelogService;
    protected DemoDataService $demoDataService;
    protected StatsCalculatorService $statsCalculator;

    public function __construct(
        BattlelogService $battlelogService,
        DemoDataService $demoDataService,
        StatsCalculatorService $statsCalculator
    ) {
        $this->battlelogService = $battlelogService;
        $this->demoDataService = $demoDataService;
        $this->statsCalculator = $statsCalculator;
    }

    /**
     * Validate API key from request
     */
    protected function validateApiKey(FluteRequest $request): bool
    {
        $apiKey = $request->headers->get('Authorization');
        if (!$apiKey) {
            $apiKey = $request->get('api_key');
        }

        // Remove "Bearer " prefix if present
        if (str_starts_with($apiKey ?? '', 'Bearer ')) {
            $apiKey = substr($apiKey, 7);
        }

        // For demo mode, accept any key or no key
        // In production, validate against stored API keys
        $configKey = config('battlelog.api_key');
        if ($configKey && $apiKey !== $configKey) {
            return false;
        }

        return true;
    }

    /**
     * Start a new game session
     */
    public function startSession(FluteRequest $request): JsonResponse
    {
        if (!$this->validateApiKey($request)) {
            return $this->json(['error' => 'Invalid API key'], 401);
        }

        $data = $request->toArray();

        if (empty($data['server_id'])) {
            return $this->json(['error' => 'server_id is required'], 400);
        }

        $session = $this->battlelogService->startSession($data);

        return $this->json([
            'success' => true,
            'session_id' => $session->id,
            'message' => 'Session started',
        ]);
    }

    /**
     * End a game session
     */
    public function endSession(FluteRequest $request): JsonResponse
    {
        if (!$this->validateApiKey($request)) {
            return $this->json(['error' => 'Invalid API key'], 401);
        }

        $data = $request->toArray();

        if (empty($data['session_id'])) {
            return $this->json(['error' => 'session_id is required'], 400);
        }

        $session = $this->battlelogService->endSession((int) $data['session_id'], $data);

        if (!$session) {
            return $this->json(['error' => 'Session not found'], 404);
        }

        return $this->json([
            'success' => true,
            'message' => 'Session ended',
        ]);
    }

    /**
     * Session heartbeat (keep-alive)
     */
    public function heartbeat(FluteRequest $request): JsonResponse
    {
        if (!$this->validateApiKey($request)) {
            return $this->json(['error' => 'Invalid API key'], 401);
        }

        $sessionId = $request->get('session_id');
        $session = $this->battlelogService->getSession((int) $sessionId);

        if (!$session) {
            return $this->json(['error' => 'Session not found'], 404);
        }

        return $this->json([
            'success' => true,
            'session_id' => $session->id,
            'status' => $session->status,
        ]);
    }

    /**
     * Player connects to server
     */
    public function playerConnect(FluteRequest $request): JsonResponse
    {
        if (!$this->validateApiKey($request)) {
            return $this->json(['error' => 'Invalid API key'], 401);
        }

        $data = $request->toArray();

        $required = ['session_id', 'platform_id', 'name', 'faction'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->json(['error' => "{$field} is required"], 400);
            }
        }

        $playerSession = $this->battlelogService->playerConnect(
            (int) $data['session_id'],
            $data['platform_id'],
            $data['name'],
            $data['faction']
        );

        if (!$playerSession) {
            return $this->json(['error' => 'Failed to connect player'], 400);
        }

        return $this->json([
            'success' => true,
            'player_session_id' => $playerSession->id,
            'player_id' => $playerSession->player_id,
        ]);
    }

    /**
     * Player disconnects from server
     */
    public function playerDisconnect(FluteRequest $request): JsonResponse
    {
        if (!$this->validateApiKey($request)) {
            return $this->json(['error' => 'Invalid API key'], 401);
        }

        $data = $request->toArray();

        if (empty($data['session_id']) || empty($data['platform_id'])) {
            return $this->json(['error' => 'session_id and platform_id are required'], 400);
        }

        $stats = $data['stats'] ?? [];
        $this->battlelogService->playerDisconnect(
            (int) $data['session_id'],
            $data['platform_id'],
            $stats
        );

        return $this->json([
            'success' => true,
            'message' => 'Player disconnected',
        ]);
    }

    /**
     * Player spawned
     */
    public function playerSpawn(FluteRequest $request): JsonResponse
    {
        if (!$this->validateApiKey($request)) {
            return $this->json(['error' => 'Invalid API key'], 401);
        }

        // Just acknowledge - spawn tracking is optional
        return $this->json(['success' => true]);
    }

    /**
     * Record a kill event
     */
    public function recordKill(FluteRequest $request): JsonResponse
    {
        if (!$this->validateApiKey($request)) {
            return $this->json(['error' => 'Invalid API key'], 401);
        }

        $data = $request->toArray();

        if (empty($data['session_id']) || empty($data['victim_platform_id'])) {
            return $this->json(['error' => 'session_id and victim_platform_id are required'], 400);
        }

        $event = $this->battlelogService->recordKill((int) $data['session_id'], $data);

        if (!$event) {
            return $this->json(['error' => 'Failed to record kill'], 400);
        }

        return $this->json([
            'success' => true,
            'kill_id' => $event->id,
        ]);
    }

    /**
     * Record a death event (alias for kill from victim perspective)
     */
    public function recordDeath(FluteRequest $request): JsonResponse
    {
        return $this->recordKill($request);
    }

    /**
     * Record damage event (for future use)
     */
    public function recordDamage(FluteRequest $request): JsonResponse
    {
        if (!$this->validateApiKey($request)) {
            return $this->json(['error' => 'Invalid API key'], 401);
        }

        // Damage tracking can be implemented later
        return $this->json(['success' => true, 'message' => 'Damage tracking not yet implemented']);
    }

    /**
     * Record objective event
     */
    public function recordObjective(FluteRequest $request): JsonResponse
    {
        if (!$this->validateApiKey($request)) {
            return $this->json(['error' => 'Invalid API key'], 401);
        }

        // Objective tracking - to be implemented
        return $this->json(['success' => true]);
    }

    /**
     * Record capture event
     */
    public function recordCapture(FluteRequest $request): JsonResponse
    {
        if (!$this->validateApiKey($request)) {
            return $this->json(['error' => 'Invalid API key'], 401);
        }

        // Capture tracking - to be implemented
        return $this->json(['success' => true]);
    }

    /**
     * Vehicle enter event
     */
    public function vehicleEnter(FluteRequest $request): JsonResponse
    {
        if (!$this->validateApiKey($request)) {
            return $this->json(['error' => 'Invalid API key'], 401);
        }

        return $this->json(['success' => true]);
    }

    /**
     * Vehicle exit event
     */
    public function vehicleExit(FluteRequest $request): JsonResponse
    {
        if (!$this->validateApiKey($request)) {
            return $this->json(['error' => 'Invalid API key'], 401);
        }

        return $this->json(['success' => true]);
    }

    /**
     * Vehicle destroy event
     */
    public function vehicleDestroy(FluteRequest $request): JsonResponse
    {
        if (!$this->validateApiKey($request)) {
            return $this->json(['error' => 'Invalid API key'], 401);
        }

        return $this->json(['success' => true]);
    }

    /**
     * Batch events (preferred for performance)
     */
    public function batchEvents(FluteRequest $request): JsonResponse
    {
        if (!$this->validateApiKey($request)) {
            return $this->json(['error' => 'Invalid API key'], 401);
        }

        $data = $request->toArray();
        $events = $data['events'] ?? [];
        $sessionId = $data['session_id'] ?? null;

        if (!$sessionId) {
            return $this->json(['error' => 'session_id is required'], 400);
        }

        $processed = 0;
        $errors = [];

        foreach ($events as $event) {
            $event['session_id'] = $sessionId;

            try {
                switch ($event['type'] ?? '') {
                    case 'kill':
                        $this->battlelogService->recordKill($sessionId, $event);
                        $processed++;
                        break;
                    case 'connect':
                        $this->battlelogService->playerConnect(
                            $sessionId,
                            $event['platform_id'],
                            $event['name'],
                            $event['faction']
                        );
                        $processed++;
                        break;
                    case 'disconnect':
                        $this->battlelogService->playerDisconnect(
                            $sessionId,
                            $event['platform_id'],
                            $event['stats'] ?? []
                        );
                        $processed++;
                        break;
                    default:
                        $errors[] = "Unknown event type: " . ($event['type'] ?? 'null');
                }
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }

        return $this->json([
            'success' => true,
            'processed' => $processed,
            'total' => count($events),
            'errors' => $errors,
        ]);
    }

    /**
     * Get player stats (for in-game display)
     */
    public function getPlayerStats(FluteRequest $request, string $platformId): JsonResponse
    {
        $player = $this->battlelogService->getPlayerByPlatformId($platformId);

        if (!$player) {
            return $this->json(['error' => 'Player not found'], 404);
        }

        $stats = $this->statsCalculator->getDetailedPlayerStats($player);

        return $this->json([
            'success' => true,
            'player' => [
                'id' => $player->id,
                'name' => $player->name,
                'rank' => $player->rank_name,
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Generate demo session (for testing)
     */
    public function generateDemoSession(FluteRequest $request): JsonResponse
    {
        $numPlayers = $request->get('num_players', 20);
        $numSessions = $request->get('num_sessions', 1);

        $result = $this->demoDataService->generateFullDemo((int) $numPlayers, (int) $numSessions);

        return $this->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Generate demo player (for testing)
     */
    public function generateDemoPlayer(FluteRequest $request): JsonResponse
    {
        $count = $request->get('count', 1);

        $players = $this->demoDataService->generateDemoPlayers((int) $count);

        return $this->json([
            'success' => true,
            'players_created' => count($players),
        ]);
    }
}
