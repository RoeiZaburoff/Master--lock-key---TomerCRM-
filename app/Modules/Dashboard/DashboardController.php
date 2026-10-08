<?php
declare(strict_types=1);

namespace App\Modules\Dashboard;

use App\Core\Request;
use App\Core\View;

class DashboardController extends \App\Core\BaseController
{
    public function index(Request $request, array $params = []): void
    {
        $period = $request->get('period') ?? 'daily';
        if (!in_array($period, ['daily', 'weekly', 'monthly'])) {
            $period = 'daily';
        }

        $service = new DashboardService();
        $stats   = $service->getStats($period);
        $stats['current_period'] = $period;

        View::render('dashboard/index', $stats);
    }

    public function schedule(Request $request, array $params = []): void
    {
        $date = $request->get('date') ?? date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }
        $service = new DashboardService();
        header('Content-Type: application/json');
        echo json_encode($service->getLeadsForDate($date));
        exit;
    }
}
