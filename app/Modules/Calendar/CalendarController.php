<?php
declare(strict_types=1);

namespace App\Modules\Calendar;

use App\Core\QueryBuilder;
use App\Core\Request;
use App\Core\View;
use App\Modules\Leads\LeadRepository;

class CalendarController
{
    public function index(Request $request, array $params = []): void
    {
        $view      = (string) $request->get('view', 'week');
        $repo      = new LeadRepository();
        $db        = new QueryBuilder();
        $customers = $db->table('customers')->orderBy('full_name')->get();
        $companies = $db->table('companies')->where('is_active', 1)->orderBy('name')->get();

        if ($view === 'day') {
            $anchor  = (string) $request->get('day', '');
            $dayDate = date('Y-m-d', strtotime($anchor !== '' ? $anchor : 'today'));
            $prevDay = date('Y-m-d', strtotime($dayDate . ' -1 day'));
            $nextDay = date('Y-m-d', strtotime($dayDate . ' +1 day'));

            $leads = $repo->findForWeek($dayDate, $dayDate);
            $byDay = [];
            foreach ($leads as $lead) {
                $byDay[$lead['scheduled_date']][] = $lead;
            }

            View::render('calendar/index', [
                'view'          => 'day',
                'dayDate'       => $dayDate,
                'prevDay'       => $prevDay,
                'nextDay'       => $nextDay,
                'byDay'         => $byDay,
                'followUpByDay' => $this->getFollowUpsByDay($db, $dayDate, $dayDate),
                'customers'     => $customers,
                'companies'     => $companies,
            ]);
            return;
        }

        if ($view === 'month') {
            $anchor = (string) $request->get('month', '');
            $year   = (int) ($anchor !== '' ? substr($anchor, 0, 4) : date('Y'));
            $month  = (int) ($anchor !== '' ? substr($anchor, 5, 2) : date('n'));
            $prevTs = mktime(0, 0, 0, $month - 1, 1, $year);
            $nextTs = mktime(0, 0, 0, $month + 1, 1, $year);
            $from   = sprintf('%04d-%02d-01', $year, $month);
            $to     = date('Y-m-t', strtotime($from));

            $leads = $repo->findForMonth($year, $month);
            $byDay = [];
            foreach ($leads as $lead) {
                $byDay[$lead['scheduled_date']][] = $lead;
            }

            View::render('calendar/index', [
                'view'          => 'month',
                'year'          => $year,
                'month'         => $month,
                'prevMonth'     => date('Y-m', $prevTs),
                'nextMonth'     => date('Y-m', $nextTs),
                'byDay'         => $byDay,
                'followUpByDay' => $this->getFollowUpsByDay($db, $from, $to),
                'customers'     => $customers,
                'companies'     => $companies,
            ]);
            return;
        }

        $anchor          = (string) $request->get('week', '');
        [$monday, $sunday] = week_bounds($anchor);
        $prevMonday = date('Y-m-d', strtotime($monday . ' -7 days'));
        $nextMonday = date('Y-m-d', strtotime($monday . ' +7 days'));

        $leads = $repo->findForWeek($monday, $sunday);
        $byDay = [];
        foreach ($leads as $lead) {
            $byDay[$lead['scheduled_date']][] = $lead;
        }

        View::render('calendar/index', [
            'view'          => 'week',
            'monday'        => $monday,
            'sunday'        => $sunday,
            'prevMonday'    => $prevMonday,
            'nextMonday'    => $nextMonday,
            'byDay'         => $byDay,
            'followUpByDay' => $this->getFollowUpsByDay($db, $monday, $sunday),
            'customers'     => $customers,
            'companies'     => $companies,
        ]);
    }

    private function getFollowUpsByDay(QueryBuilder $db, string $from, string $to): array
    {
        $rows = $db->raw(
            "SELECT j.job_id, jm.meta_value AS follow_up_date,
                    c.full_name AS customer_name,
                    comp.name AS company_name
             FROM jobs j
             JOIN customers c ON c.customer_id = j.customer_id
             LEFT JOIN companies comp ON comp.company_id = j.company_id
             JOIN job_meta jm ON jm.job_id = j.job_id AND jm.meta_key = 'follow_up_date'
             WHERE j.status = 'estimate'
               AND jm.meta_value IS NOT NULL
               AND jm.meta_value BETWEEN :from AND :to
             ORDER BY jm.meta_value ASC",
            [':from' => $from, ':to' => $to]
        );
        $byDay = [];
        foreach ($rows as $row) {
            $byDay[$row['follow_up_date']][] = $row;
        }
        return $byDay;
    }
}
