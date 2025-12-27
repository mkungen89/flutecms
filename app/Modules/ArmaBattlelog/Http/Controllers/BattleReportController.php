<?php

namespace Flute\Modules\ArmaBattlelog\Http\Controllers;

use Flute\Core\Support\BaseController;
use Flute\Core\Support\FluteRequest;
use Flute\Modules\ArmaBattlelog\Services\BattlelogService;
use Flute\Modules\ArmaBattlelog\Services\StatsCalculatorService;
use Symfony\Component\HttpFoundation\Response;

class BattleReportController extends BaseController
{
    protected BattlelogService $battlelogService;
    protected StatsCalculatorService $statsCalculator;

    public function __construct(
        BattlelogService $battlelogService,
        StatsCalculatorService $statsCalculator
    ) {
        $this->battlelogService = $battlelogService;
        $this->statsCalculator = $statsCalculator;
    }

    /**
     * Show battle report
     */
    public function show(FluteRequest $request, int $id): Response
    {
        $session = $this->battlelogService->getSession($id);

        if (!$session) {
            return $this->error(__('battlelog.session_not_found'), 404);
        }

        $report = $this->statsCalculator->getBattleReport($session);

        return view('Modules/ArmaBattlelog/Resources/views/battle-report', [
            'report' => $report,
            'session' => $session,
        ]);
    }
}
