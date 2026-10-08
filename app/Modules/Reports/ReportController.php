<?php
declare(strict_types=1);

namespace App\Modules\Reports;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;

class ReportController extends \App\Core\BaseController
{
    private ReportService $service;

    public function __construct()
    {
        $this->service = new ReportService();
        $this->adminActions = ['*'];
    }

    public function index(Request $request, array $params = []): void
    {
        $options = $this->service->getFilterOptions();
        View::render('reports/index', array_merge($options, ['rows' => [], 'totals' => [], 'filters' => []]));
    }

    public function generate(Request $request, array $params = []): void
    {
        $filters = $request->all();
        $result  = $this->service->generate($filters);
        $options = $this->service->getFilterOptions();
        View::render('reports/index', array_merge($options, $result, ['filters' => $filters]));
    }

    public function pdf(Request $request, array $params = []): void
    {
        $filters = $request->all();
        $result  = $this->service->generate($filters);
        $pdf = new ReportPdf();
        $pdf->generate($result['rows'], $result['totals'], $filters);
    }
}
