<?php

namespace Flute\Modules\ArmaBattlelog\Http\Controllers;

use Flute\Core\Support\BaseController;
use Flute\Core\Support\FluteRequest;
use Flute\Modules\ArmaBattlelog\Services\LeaderboardService;
use Symfony\Component\HttpFoundation\Response;

class LeaderboardController extends BaseController
{
    protected LeaderboardService $leaderboardService;

    public function __construct(LeaderboardService $leaderboardService)
    {
        $this->leaderboardService = $leaderboardService;
    }

    /**
     * Main leaderboard page
     */
    public function index(FluteRequest $request): Response
    {
        return $this->category($request, 'kills');
    }

    /**
     * Leaderboard by category
     */
    public function category(FluteRequest $request, string $category): Response
    {
        $period = $request->get('period', 'all_time');
        $page = max(1, (int) $request->get('page', 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $leaderboard = $this->leaderboardService->getLeaderboard($category, $period, $limit, $offset);
        $categories = $this->leaderboardService->getCategories();
        $periods = $this->leaderboardService->getPeriods();

        // Get current user's rank if logged in
        $userRank = null;
        if (user()->isLoggedIn()) {
            // Try to find player linked to this user
            // This would need to be implemented based on how users link their accounts
        }

        return view('Modules/ArmaBattlelog/Resources/views/leaderboard', [
            'leaderboard' => $leaderboard,
            'categories' => $categories,
            'periods' => $periods,
            'currentCategory' => $category,
            'currentPeriod' => $period,
            'page' => $page,
            'userRank' => $userRank,
        ]);
    }
}
