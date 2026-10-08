<?php
$today        = date('Y-m-d');
$monday       = $monday       ?? '';
$sunday       = $sunday       ?? '';
$prevMonday   = $prevMonday   ?? '';
$nextMonday   = $nextMonday   ?? '';
$dayDate      = $dayDate      ?? $today;
$prevDay      = $prevDay      ?? $today;
$nextDay      = $nextDay      ?? $today;
$year         = $year         ?? (int) date('Y');
$month        = $month        ?? (int) date('n');
$prevMonth    = $prevMonth    ?? '';
$nextMonth    = $nextMonth    ?? '';
$customers    = $customers    ?? [];
$companies    = $companies    ?? [];
$followUpByDay = $followUpByDay ?? [];

function cal_pastel(int $id): string {
    static $p = ['#fde8e8','#fef3c7','#d1fae5','#dbeafe','#ede9fe','#fce7f3','#e0f2fe','#fff7ed','#ecfdf5','#fdf4ff'];
    return $p[abs($id) % 10];
}

if ($view === 'week') {
    $dayNames  = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
    $days      = [];
    for ($i = 0; $i < 7; $i++) {
        $days[] = date('Y-m-d', strtotime($monday . " +$i days"));
    }
    $rangeLabel = date('M j', strtotime($monday)) . ' – ' . date('M j, Y', strtotime($sunday));
} elseif ($view === 'month') {
    $monthLabel    = date('F Y', mktime(0, 0, 0, $month, 1, $year));
    $firstDow      = (int) date('N', mktime(0, 0, 0, $month, 1, $year));
    $daysInMonth   = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
    $prevMonthDays = (int) date('t', mktime(0, 0, 0, $month - 1, 1, $year));
    $leadingDays   = $firstDow - 1;
    $totalCells    = (int) ceil(($leadingDays + $daysInMonth) / 7) * 7;
} elseif ($view === 'day') {
    $dayLabel = date('l, F j, Y', strtotime($dayDate));
}
?>

<!-- Page header -->
<div class="flex items-center justify-between gap-3 mb-6 flex-wrap">
  <h1 class="text-xl font-bold text-slate-900">
    Calendar<?php if ($view === 'week'): ?> <span class="font-normal text-slate-500">— <?= h($rangeLabel) ?></span>
    <?php elseif ($view === 'month'): ?> <span class="font-normal text-slate-500">— <?= h($monthLabel) ?></span>
    <?php elseif ($view === 'day'): ?> <span class="font-normal text-slate-500">— <?= h($dayLabel) ?></span>
    <?php endif; ?>
  </h1>
  <div class="flex items-center gap-2 flex-wrap">
    <!-- View switcher -->
    <div class="flex items-center bg-white border border-slate-200 rounded-lg p-0.5 shadow-sm">
      <?php
        $_vBtn = function(string $href, string $label, bool $active): string {
            $cls = $active
              ? 'inline-flex items-center px-3 py-1.5 rounded-md text-sm font-semibold bg-slate-900 text-white shadow-sm'
              : 'inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors duration-150 no-underline';
            return '<a href="' . $href . '" class="' . $cls . ' no-underline">' . htmlspecialchars($label) . '</a>';
        };
        echo $_vBtn(url('calendar?view=day&day=' . ($view === 'day' ? $dayDate : $today)), 'Day', $view === 'day');
        echo $_vBtn(url('calendar?view=week' . ($view === 'week' ? '&week=' . $monday : '')), 'Week', $view === 'week');
        echo $_vBtn(url('calendar?view=month' . ($view === 'month' ? '&month=' . $year . '-' . str_pad((string)$month, 2, '0', STR_PAD_LEFT) : '')), 'Month', $view === 'month');
      ?>
    </div>
    <!-- Navigation -->
    <?php if ($view === 'week'): ?>
      <a href="<?= url('calendar?view=week&week=' . $prevMonday) ?>" class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">← Prev</a>
      <a href="<?= url('calendar?view=week') ?>" class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">This Week</a>
      <a href="<?= url('calendar?view=week&week=' . $nextMonday) ?>" class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">Next →</a>
    <?php elseif ($view === 'month'): ?>
      <a href="<?= url('calendar?view=month&month=' . $prevMonth) ?>" class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">← Prev</a>
      <a href="<?= url('calendar?view=month') ?>" class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">This Month</a>
      <a href="<?= url('calendar?view=month&month=' . $nextMonth) ?>" class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">Next →</a>
    <?php elseif ($view === 'day'): ?>
      <a href="<?= url('calendar?view=day&day=' . $prevDay) ?>" class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">← Prev</a>
      <a href="<?= url('calendar?view=day&day=' . $today) ?>" class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">Today</a>
      <a href="<?= url('calendar?view=day&day=' . $nextDay) ?>" class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">Next →</a>
    <?php endif; ?>
    <a href="<?= url('leads/create') ?>"
       class="inline-flex items-center px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150 no-underline">
      + New Lead
    </a>
  </div>
</div>

<?php if ($view === 'week'): ?>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-x-auto">
  <table class="cal-table">
    <thead>
      <tr>
        <?php foreach ($days as $i => $date):
          $isPast  = $date < $today;
          $isToday = $date === $today;
        ?>
          <th class="<?= $isToday ? 'cal-today-header' : '' ?>">
            <div class="cal-th-inner">
              <span><?= $dayNames[$i] ?> <?= date('M j', strtotime($date)) ?></span>
              <?php if (!$isPast): ?>
                <button class="cal-quick-add" data-date="<?= $date ?>" title="Add Lead">+</button>
              <?php endif; ?>
            </div>
          </th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <tr>
        <?php foreach ($days as $date):
          $isPast  = $date < $today;
          $isToday = $date === $today;
          $leads   = $byDay[$date] ?? [];
        ?>
          <td class="cal-cell<?= $isToday ? ' cal-today' : '' ?><?= $isPast ? ' past-day' : '' ?>" data-date="<?= $date ?>">
            <div class="cal-events">
              <?php $fuLeads = $followUpByDay[$date] ?? []; ?>
              <?php foreach ($fuLeads as $fuLead): ?>
                <a href="<?= url('leads/' . $fuLead['job_id']) ?>" class="cal-event-card" style="background:#fff7ed;border-left:3px solid #f97316;">
                  <div class="event-time" style="color:#c2410c;">Follow-Up</div>
                  <div class="event-customer"><?= h($fuLead['customer_name'] ?? '') ?></div>
                  <?php if (!empty($fuLead['company_name'])): ?><div class="event-company"><?= h($fuLead['company_name']) ?></div><?php endif; ?>
                </a>
              <?php endforeach; ?>
              <?php if ($leads): ?>
                <?php foreach ($leads as $lead):
                  $statusRaw = strtolower(trim($lead['status'] ?? ''));
                  $time = !empty($lead['scheduled_time']) ? date('g:ia', strtotime($lead['scheduled_time'])) : '';
                ?>
                  <a href="<?= url('leads/' . $lead['job_id']) ?>" class="cal-event-card status-<?= h($statusRaw) ?>" style="background:<?= cal_pastel((int)$lead['job_id']) ?>;">
                    <?php if ($time): ?><div class="event-time"><?= $time ?></div><?php endif; ?>
                    <div class="event-customer"><?= h($lead['customer_name'] ?? '') ?></div>
                    <?php if (!empty($lead['company_name'])): ?><div class="event-company"><?= h($lead['company_name']) ?></div><?php endif; ?>
                  </a>
                <?php endforeach; ?>
              <?php elseif (!$fuLeads): ?>
                <div class="cal-no-leads">—</div>
              <?php endif; ?>
            </div>
          </td>
        <?php endforeach; ?>
      </tr>
    </tbody>
  </table>
</div>

<div class="cal-week-list">
  <?php foreach ($days as $i => $date):
    $isPast   = $date < $today;
    $isToday  = $date === $today;
    $dayLeads = $byDay[$date] ?? [];
  ?>
    <div class="cal-day-block<?= $dayLeads ? ' has-leads' : '' ?>">
      <div class="cal-day-block-header<?= $isToday ? ' today-header' : '' ?><?= $isPast ? ' past-header' : '' ?>">
        <span>
          <?= $dayNames[$i] ?>, <?= date('M j', strtotime($date)) ?>
          <?php if ($dayLeads): ?><span class="badge" style="margin-left:6px;font-size:11px;"><?= count($dayLeads) ?></span><?php endif; ?>
        </span>
        <?php if (!$isPast): ?>
          <button class="cal-quick-add" data-date="<?= $date ?>" title="Add Lead">+</button>
        <?php endif; ?>
      </div>
      <div class="cal-day-block-content<?= $isToday ? ' is-open' : '' ?>">
        <div class="cal-events" data-date="<?= $date ?>">
          <?php $fuDayLeads = $followUpByDay[$date] ?? []; ?>
          <?php foreach ($fuDayLeads as $fuLead): ?>
            <a href="<?= url('leads/' . $fuLead['job_id']) ?>" class="cal-event-card" style="background:#fff7ed;border-left:3px solid #f97316;">
              <div class="event-time" style="color:#c2410c;">Follow-Up</div>
              <div class="event-customer"><?= h($fuLead['customer_name'] ?? '') ?></div>
              <?php if (!empty($fuLead['company_name'])): ?><div class="event-company"><?= h($fuLead['company_name']) ?></div><?php endif; ?>
            </a>
          <?php endforeach; ?>
          <?php if ($dayLeads): ?>
            <?php foreach ($dayLeads as $lead):
              $statusRaw = strtolower(trim($lead['status'] ?? ''));
              $time = !empty($lead['scheduled_time']) ? date('g:ia', strtotime($lead['scheduled_time'])) : '';
            ?>
              <a href="<?= url('leads/' . $lead['job_id']) ?>" class="cal-event-card status-<?= h($statusRaw) ?>" style="background:<?= cal_pastel((int)$lead['job_id']) ?>;">
                <?php if ($time): ?><div class="event-time"><?= $time ?></div><?php endif; ?>
                <div class="event-customer"><?= h($lead['customer_name'] ?? '') ?></div>
                <?php if (!empty($lead['company_name'])): ?><div class="event-company"><?= h($lead['company_name']) ?></div><?php endif; ?>
              </a>
            <?php endforeach; ?>
          <?php elseif (!$fuDayLeads): ?>
            <div class="cal-day-empty">No leads scheduled</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php elseif ($view === 'month'): ?>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm cal-month-card">
  <div class="cal-month-scroll">
  <div class="cal-month-header">
    <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $dow): ?>
      <div class="cal-month-dow"><?= $dow ?></div>
    <?php endforeach; ?>
  </div>
  <div class="cal-month-grid">
    <?php for ($i = 0; $i < $totalCells; $i++):
      $otherMonth = false;
      if ($i < $leadingDays) {
          $dayNum   = $prevMonthDays - $leadingDays + 1 + $i;
          $cellDate = date('Y-m-d', mktime(0, 0, 0, $month - 1, $dayNum, $year));
          $otherMonth = true;
      } elseif ($i >= $leadingDays + $daysInMonth) {
          $dayNum   = $i - $leadingDays - $daysInMonth + 1;
          $cellDate = date('Y-m-d', mktime(0, 0, 0, $month + 1, $dayNum, $year));
          $otherMonth = true;
      } else {
          $dayNum   = $i - $leadingDays + 1;
          $cellDate = date('Y-m-d', mktime(0, 0, 0, $month, $dayNum, $year));
      }
      $isPast  = $cellDate < $today;
      $isToday = $cellDate === $today;
      $cellLeads = $byDay[$cellDate] ?? [];
      $cls  = 'cal-month-cell';
      $cls .= $otherMonth ? ' other-month' : '';
      $cls .= $isToday   ? ' cal-today'   : '';
      $cls .= ($isPast && !$otherMonth) ? ' past-day' : '';
    ?>
      <div class="<?= $cls ?>" data-date="<?= $cellDate ?>">
        <div class="cal-month-num<?= $isToday ? ' today-num' : '' ?>">
          <span><?= $dayNum ?></span>
          <?php if (!$isPast && !$otherMonth): ?>
            <button class="cal-quick-add" data-date="<?= $cellDate ?>" title="Add Lead">+</button>
          <?php endif; ?>
        </div>
        <?php $fuCellLeads = $followUpByDay[$cellDate] ?? []; ?>
        <div class="cal-events cal-month-cards">
          <?php foreach ($fuCellLeads as $fuLead): ?>
            <a href="<?= url('leads/' . $fuLead['job_id']) ?>" class="cal-event-card" style="background:#fff7ed;border-left:3px solid #f97316;">
              <div class="event-time" style="color:#c2410c;">Follow-Up</div>
              <div class="event-customer"><?= h($fuLead['customer_name'] ?? '') ?></div>
            </a>
          <?php endforeach; ?>
          <?php foreach ($cellLeads as $lead):
            $statusRaw = strtolower(trim($lead['status'] ?? ''));
            $time = !empty($lead['scheduled_time']) ? date('g:ia', strtotime($lead['scheduled_time'])) : '';
          ?>
            <a href="<?= url('leads/' . $lead['job_id']) ?>" class="cal-event-card status-<?= h($statusRaw) ?>" style="background:<?= cal_pastel((int)$lead['job_id']) ?>;">
              <?php if ($time): ?><div class="event-time"><?= $time ?></div><?php endif; ?>
              <div class="event-customer"><?= h($lead['customer_name'] ?? '') ?></div>
            </a>
          <?php endforeach; ?>
        </div>
        <?php if ($cellLeads || $fuCellLeads): ?>
          <div class="cal-month-dots">
            <?php foreach ($fuCellLeads as $_): ?>
              <span class="cal-month-dot" style="background:#f97316;"></span>
            <?php endforeach; ?>
            <?php foreach (array_slice($cellLeads, 0, 5) as $lead): ?>
              <span class="cal-month-dot status-<?= strtolower($lead['status'] ?? '') ?>"></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endfor; ?>
  </div>
  </div><!-- /.cal-month-scroll -->
  <div class="cal-month-agenda-wrap">
    <div id="cal-month-agenda" class="cal-month-agenda"></div>
  </div>
</div>

<?php elseif ($view === 'day'): ?>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm">
  <div class="cal-day-view px-5 py-5">
    <?php $dayLeads = $byDay[$dayDate] ?? []; ?>
    <?php $fuDayViewLeads = $followUpByDay[$dayDate] ?? []; ?>
    <?php if ($dayLeads || $fuDayViewLeads): ?>
      <div class="cal-events" data-date="<?= $dayDate ?>">
        <?php foreach ($fuDayViewLeads as $fuLead): ?>
          <a href="<?= url('leads/' . $fuLead['job_id']) ?>" class="cal-event-card" style="background:#fff7ed;border-left:3px solid #f97316;">
            <div class="event-time" style="color:#c2410c;">Follow-Up</div>
            <div class="event-customer"><?= h($fuLead['customer_name'] ?? '') ?></div>
            <?php if (!empty($fuLead['company_name'])): ?><div class="event-company"><?= h($fuLead['company_name']) ?></div><?php endif; ?>
          </a>
        <?php endforeach; ?>
        <?php foreach ($dayLeads as $lead):
          $statusRaw = strtolower(trim($lead['status'] ?? ''));
          $time = !empty($lead['scheduled_time']) ? date('g:ia', strtotime($lead['scheduled_time'])) : '';
        ?>
          <a href="<?= url('leads/' . $lead['job_id']) ?>" class="cal-event-card status-<?= h($statusRaw) ?>" style="background:<?= cal_pastel((int)$lead['job_id']) ?>;">
            <?php if ($time): ?><div class="event-time"><?= $time ?></div><?php endif; ?>
            <div class="event-customer"><?= h($lead['customer_name'] ?? '') ?></div>
            <?php if (!empty($lead['company_name'])): ?><div class="event-company"><?= h($lead['company_name']) ?></div><?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="cal-day-empty">No leads scheduled for this day.</div>
    <?php endif; ?>
    <?php if ($dayDate >= $today): ?>
      <div class="mt-4">
        <button class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150 cal-quick-add-btn" data-date="<?= $dayDate ?>">+ Quick Add Lead</button>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php endif; ?>

<!-- Quick Add Lead Modal -->
<div id="quick-lead-modal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" style="display:none;" aria-modal="true" role="dialog">
  <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
      <h3 class="text-sm font-semibold text-slate-900">Quick Add Lead — <span id="quick-modal-date-label"></span></h3>
      <button id="quick-modal-close" type="button" class="text-slate-400 hover:text-slate-600 text-xl leading-none">&times;</button>
    </div>
    <div class="px-5 py-4">
      <div id="quick-modal-error" class="alert alert-error mb-3" style="display:none;"></div>
      <form id="quick-lead-form" action="<?= url('leads') ?>" method="POST">
        <?= \App\Core\CSRF::field() ?>
        <input type="hidden" name="status" value="scheduled">
        <input type="hidden" name="scheduled_date" id="quick-date" value="">

        <?php
        $_qi = 'w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition';
        $_ql = 'block text-sm font-medium text-slate-700 mb-1.5';
        $_qs = 'w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition';
        ?>

        <div class="mb-3">
          <label class="<?= $_ql ?>">Smart Paste</label>
          <textarea id="quick-smart-paste" rows="2" placeholder="Paste lead info here — auto-fills fields…"
                    class="w-full px-3 py-2 rounded-lg border-2 border-dashed border-slate-200 text-sm text-slate-800 bg-slate-50 focus:outline-none focus:border-blue-400 transition resize-none"></textarea>
        </div>

        <div class="mb-3">
          <label class="<?= $_ql ?>">Customer *</label>
          <div class="flex gap-2 mb-1.5">
            <input type="text" id="quick-customer-search" placeholder="Search customers…" autocomplete="off" class="<?= $_qi ?> flex-1">
          </div>
          <div class="flex gap-2">
            <select name="customer_id" id="quick-customer-select" required class="<?= $_qs ?> flex-1">
              <option value="">— Select Customer —</option>
              <?php foreach ($customers as $c): ?>
                <option value="<?= (int) $c['customer_id'] ?>"><?= h($c['full_name']) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="button" id="open-customer-modal"
                    class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium transition-colors duration-150 whitespace-nowrap">
              + New
            </button>
          </div>
        </div>

        <div class="mb-3">
          <label class="<?= $_ql ?>">Company *</label>
          <select name="company_id" id="quick-company-select" required class="<?= $_qs ?>">
            <option value="" disabled selected>— Select Company —</option>
            <?php foreach ($companies as $co): ?>
              <option value="<?= (int) $co['company_id'] ?>"><?= h($co['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3">
          <label class="<?= $_ql ?>">Service Address</label>
          <input type="text" name="address" id="quick-address" data-address-autocomplete autocomplete="off"
                 placeholder="Street address…" class="<?= $_qi ?>">
        </div>

        <div class="grid grid-cols-3 gap-2 mb-3">
          <div>
            <label class="<?= $_ql ?>">City</label>
            <input type="text" name="city" id="quick-city" class="<?= $_qi ?>">
          </div>
          <div>
            <label class="<?= $_ql ?>">State</label>
            <input type="text" name="state" id="quick-state" class="<?= $_qi ?>">
          </div>
          <div>
            <label class="<?= $_ql ?>">ZIP</label>
            <input type="text" name="zip" id="quick-zip" class="<?= $_qi ?>">
          </div>
        </div>

        <div class="grid grid-cols-2 gap-2 mb-3">
          <div>
            <label class="<?= $_ql ?>">Hour</label>
            <select name="scheduled_hour" id="quick-hour" class="<?= $_qs ?>">
              <?php for ($h = 1; $h <= 12; $h++): ?>
                <option value="<?= $h ?>"<?= $h === 8 ? ' selected' : '' ?>><?= $h ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div>
            <label class="<?= $_ql ?>">AM / PM</label>
            <select name="scheduled_ampm" id="quick-ampm" class="<?= $_qs ?>">
              <option value="AM">AM</option>
              <option value="PM" selected>PM</option>
            </select>
          </div>
        </div>

        <div class="mb-4">
          <label class="<?= $_ql ?>">Description</label>
          <textarea name="description" id="quick-description" rows="3" class="<?= $_qi ?> resize-y"></textarea>
        </div>

        <div class="flex items-center gap-3 pt-2 border-t border-slate-100">
          <button type="submit" id="quick-submit-btn"
                  class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150">
            Add Lead
          </button>
          <button type="button" id="quick-modal-cancel"
                  class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150">
            Cancel
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- New Customer Modal -->
<div id="customer-modal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" style="display:none;">
  <div class="bg-white rounded-xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
      <h3 class="text-sm font-semibold text-slate-900">New Customer</h3>
      <button type="button" id="close-customer-modal" class="text-slate-400 hover:text-slate-600 text-xl leading-none">&#x2715;</button>
    </div>
    <div class="px-5 py-4">
      <div id="modal-error" class="alert alert-error mb-3" style="display:none;"></div>
      <div id="dup-alert" class="mb-3 px-3.5 py-3 bg-amber-50 border border-amber-300 rounded-lg text-sm" style="display:none;">
        <strong class="text-amber-800">&#9888; Customer found:</strong> <span id="dup-name" class="font-semibold text-amber-900"></span>
        <br><span id="dup-phone-email" class="text-slate-500 text-xs"></span>
        <div class="mt-2">
          <button type="button" id="dup-load-btn"
                  class="inline-flex items-center px-3 py-1.5 rounded-lg border border-amber-300 bg-white hover:bg-amber-50 text-amber-800 text-xs font-medium transition-colors duration-150">
            Load Existing Customer
          </button>
        </div>
      </div>
      <form id="quick-customer-form">
        <?= \App\Core\CSRF::field() ?>
        <div class="grid grid-cols-2 gap-3 mb-3">
          <div>
            <label class="<?= $_ql ?>">Full Name *</label>
            <input type="text" id="smart-full-name" name="full_name" required class="<?= $_qi ?>">
          </div>
          <div>
            <label class="<?= $_ql ?>">Phone *</label>
            <input type="tel" id="smart-phone" name="phone" required class="<?= $_qi ?>">
          </div>
        </div>
        <div class="mb-3">
          <label class="<?= $_ql ?>">Email</label>
          <input type="email" id="smart-email" name="email" class="<?= $_qi ?>">
        </div>
        <div class="mb-3">
          <label class="<?= $_ql ?>">Billing Address *</label>
          <input type="text" id="modal-address" name="address_line1" required data-address-autocomplete autocomplete="off" class="<?= $_qi ?>">
        </div>
        <div class="grid grid-cols-3 gap-2 mb-4">
          <div>
            <label class="<?= $_ql ?>">City *</label>
            <input type="text" id="modal-city" name="city" required class="<?= $_qi ?>">
          </div>
          <div>
            <label class="<?= $_ql ?>">State *</label>
            <input type="text" id="modal-state" name="state" required class="<?= $_qi ?>">
          </div>
          <div>
            <label class="<?= $_ql ?>">ZIP *</label>
            <input type="text" id="modal-zip" name="zip" required class="<?= $_qi ?>">
          </div>
        </div>
        <div class="flex items-center gap-3 pt-2 border-t border-slate-100">
          <button type="submit"
                  class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150">
            Save Customer
          </button>
          <button type="button" id="close-customer-modal-btn"
                  class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150">
            Cancel
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
window.__crmBase = '<?= defined('APP_BASE') ? rtrim(APP_BASE, '/') : '' ?>';

(function () {
  /* ── Day-block accordion ────────────────────────────────── */
  document.querySelectorAll('.cal-day-block-header').forEach(function (hdr) {
    hdr.addEventListener('click', function (e) {
      if (e.target.closest('.cal-quick-add')) return;
      var content = this.parentElement.querySelector('.cal-day-block-content');
      if (content) content.classList.toggle('is-open');
    });
  });

  /* ── Month agenda panel ─────────────────────────────────── */
  var monthAgenda       = document.getElementById('cal-month-agenda');
  var selectedMonthCell = null;
  document.querySelectorAll('.cal-month-cell:not(.other-month)').forEach(function (cell) {
    cell.addEventListener('click', function (e) {
      if (e.target.closest('.cal-quick-add')) return;
      if (window.innerWidth >= 768) return;
      if (selectedMonthCell === this) {
        selectedMonthCell = null;
        this.classList.remove('is-selected');
        if (monthAgenda) { monthAgenda.innerHTML = ''; monthAgenda.classList.remove('is-open'); }
        return;
      }
      if (selectedMonthCell) selectedMonthCell.classList.remove('is-selected');
      selectedMonthCell = this;
      this.classList.add('is-selected');
      var cellDate = this.dataset.date;
      var cards    = this.querySelectorAll('.cal-event-card');
      if (monthAgenda) {
        if (cards.length) {
          var dateStr = new Date(cellDate + 'T00:00:00').toLocaleDateString('en-US', {weekday:'short',month:'short',day:'numeric'});
          var inner = '<strong style="font-size:13px;display:block;margin-bottom:8px;">' + dateStr + '</strong><div class="cal-events" data-date="' + cellDate + '">';
          cards.forEach(function (c) { inner += c.outerHTML; });
          inner += '</div>';
          monthAgenda.innerHTML = inner;
        } else {
          monthAgenda.innerHTML = '<div class="cal-day-empty">No leads scheduled</div>';
        }
        monthAgenda.classList.add('is-open');
      }
    });
  });

  /* ── Element refs ────────────────────────────────────────── */
  var leadAddrEl    = document.getElementById('quick-address');
  var leadCityEl    = document.getElementById('quick-city');
  var leadStateEl   = document.getElementById('quick-state');
  var leadZipEl     = document.getElementById('quick-zip');
  var quickDesc     = document.getElementById('quick-description');
  var quickPaste    = document.getElementById('quick-smart-paste');
  var qSearch       = document.getElementById('quick-customer-search');
  var qSelect       = document.getElementById('quick-customer-select');
  var modalNameEl   = document.getElementById('smart-full-name');
  var modalPhoneEl  = document.getElementById('smart-phone');
  var modalEmailEl  = document.getElementById('smart-email');
  var modalAddrEl   = document.getElementById('modal-address');
  var modalCityEl   = document.getElementById('modal-city');
  var modalStateEl  = document.getElementById('modal-state');
  var modalZipEl    = document.getElementById('modal-zip');
  var customerModal = document.getElementById('customer-modal');
  var dupAlert      = document.getElementById('dup-alert');
  var dupName       = document.getElementById('dup-name');
  var dupPhoneEmail = document.getElementById('dup-phone-email');
  var dupLoadBtn    = document.getElementById('dup-load-btn');
  var modalError    = document.getElementById('modal-error');
  var modalForm     = document.getElementById('quick-customer-form');

  /* ── Customer address data (for auto-fill on select change) ── */
  var customerData = <?= json_encode(
    array_combine(
      array_column($customers, 'customer_id'),
      array_map(function ($c) {
        return [
          'address_line1' => $c['address_line1'] ?? '',
          'city'          => $c['city']          ?? '',
          'state'         => $c['state']         ?? '',
          'zip'           => $c['zip']           ?? '',
        ];
      }, $customers)
    )
  ) ?>;

  /* ── Helpers ─────────────────────────────────────────────── */
  function flashField(el) {
    if (!el) return;
    el.style.transition = 'background .15s';
    el.style.background = '#d1fae5';
    setTimeout(function () { el.style.background = ''; }, 1200);
  }

  function setVal(el, val) {
    if (!el || !val) return;
    el.value = val;
    flashField(el);
  }

  function fillLeadAddrFromObj(obj) {
    setVal(leadAddrEl,  obj.address_line1 || obj.address || '');
    setVal(leadCityEl,  obj.city  || '');
    setVal(leadStateEl, obj.state || '');
    setVal(leadZipEl,   obj.zip   || '');
  }

  /* ── Duplicate detection ─────────────────────────────────── */
  var _dupCustomer = null;

  function showDupAlert(c, matchType) {
    _dupCustomer = c;
    if (dupName)       dupName.textContent      = c.full_name + ' [' + (matchType || 'Duplicate') + ']';
    if (dupPhoneEmail) dupPhoneEmail.textContent = [c.phone, c.email, c.address_line1].filter(Boolean).join(' · ');
    if (dupAlert)      dupAlert.style.display    = '';
  }

  function hideDupAlert() {
    _dupCustomer = null;
    if (dupAlert) dupAlert.style.display = 'none';
  }

  function checkDuplicate(phone, email, address) {
    var qs = [];
    if (phone)   qs.push('phone='   + encodeURIComponent(phone));
    if (email)   qs.push('email='   + encodeURIComponent(email));
    if (address) qs.push('address=' + encodeURIComponent(address));
    if (!qs.length) return;
    fetch('<?= url('customers/check-duplicate') ?>?' + qs.join('&'))
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.exists) showDupAlert(data.customer, data.match_type);
        else hideDupAlert();
      })
      .catch(function () {});
  }

  /* ── Smart Paste → fill modal + lead service address ────── */
  function fillLeadForm(d) {
    setVal(modalNameEl,  d.name);
    setVal(modalPhoneEl, d.phone);
    setVal(modalEmailEl, d.email);
    setVal(modalAddrEl,  d.address);
    setVal(modalCityEl,  d.city);
    setVal(modalStateEl, d.state);
    setVal(modalZipEl,   d.zip);
    setVal(leadAddrEl,   d.address);
    setVal(leadCityEl,   d.city);
    setVal(leadStateEl,  d.state);
    setVal(leadZipEl,    d.zip);
    if (quickDesc && !quickDesc.value && d.notes) {
      setVal(quickDesc, d.notes);
    }
    if (d.name || d.phone) {
      if (customerModal) customerModal.style.display = 'flex';
    }
    if (d.phone || d.email || d.address) {
      checkDuplicate(d.phone || '', d.email || '', d.address || '');
    }
  }

  if (quickPaste) {
    var pasteTimer;
    function handleQuickPaste() {
      clearTimeout(pasteTimer);
      pasteTimer = setTimeout(function () {
        var raw = quickPaste.value.trim();
        if (!raw) return;
        var d = window.LeadParser ? window.LeadParser.parse(raw) : {};
        fillLeadForm(d);
        if (qSearch && d.name && !qSearch.value) {
          qSearch.value = d.name;
          qSearch.dispatchEvent(new Event('input'));
        }
        quickPaste.style.borderColor = '#10b981';
        setTimeout(function () { quickPaste.style.borderColor = ''; }, 2000);
      }, 300);
    }
    quickPaste.addEventListener('paste', handleQuickPaste);
    quickPaste.addEventListener('input', function () { if (this.value.length > 15) handleQuickPaste(); });
  }

  /* ── Customer select → auto-fill lead service address ───── */
  if (qSelect) {
    qSelect.addEventListener('change', function () {
      var cid = this.value;
      if (cid && customerData[cid]) fillLeadAddrFromObj(customerData[cid]);
    });
  }

  /* ── Customer search filter ──────────────────────────────── */
  var allOpts = qSelect ? Array.from(qSelect.options) : [];
  if (qSearch && qSelect) {
    qSearch.addEventListener('input', function () {
      var q = this.value.toLowerCase();
      Array.from(qSelect.options).forEach(function (opt) { opt.remove(); });
      allOpts.forEach(function (opt) {
        if (opt.value === '' || opt.text.toLowerCase().indexOf(q) !== -1) {
          qSelect.appendChild(opt.cloneNode(true));
        }
      });
    });
  }

  /* ── Customer modal blur-based dup checks ────────────────── */
  if (modalPhoneEl) {
    modalPhoneEl.addEventListener('blur', function () {
      if (this.value.replace(/\D/g, '').length >= 10) checkDuplicate(this.value, '', '');
      else hideDupAlert();
    });
  }
  if (modalEmailEl) {
    modalEmailEl.addEventListener('blur', function () {
      var v = this.value.trim();
      if (v && v.indexOf('@') !== -1 && !_dupCustomer) checkDuplicate('', v, '');
    });
  }
  if (modalAddrEl) {
    modalAddrEl.addEventListener('blur', function () {
      var v = this.value.trim();
      if (v.length >= 5 && !_dupCustomer) checkDuplicate('', '', v);
    });
  }

  /* ── Dup load button ─────────────────────────────────────── */
  if (dupLoadBtn) {
    dupLoadBtn.addEventListener('click', function () {
      if (!_dupCustomer) return;
      var c = _dupCustomer;
      if (qSelect) {
        if (!qSelect.querySelector('option[value="' + c.customer_id + '"]')) {
          var opt = document.createElement('option');
          opt.value = c.customer_id;
          opt.text  = c.full_name;
          qSelect.appendChild(opt);
          allOpts.push(opt.cloneNode(true));
        }
        qSelect.value = c.customer_id;
      }
      fillLeadAddrFromObj(c);
      if (customerModal) customerModal.style.display = 'none';
      hideDupAlert();
    });
  }

  /* ── Customer modal open / close ─────────────────────────── */
  var openBtn   = document.getElementById('open-customer-modal');
  var closeBtn  = document.getElementById('close-customer-modal');
  var closeBtnB = document.getElementById('close-customer-modal-btn');

  function openCustomerModal() {
    hideDupAlert();
    if (modalError) modalError.style.display = 'none';
    if (customerModal) customerModal.style.display = 'flex';
  }

  function closeCustomerModal() {
    if (customerModal) customerModal.style.display = 'none';
    hideDupAlert();
  }

  if (openBtn)   openBtn.addEventListener('click',   openCustomerModal);
  if (closeBtn)  closeBtn.addEventListener('click',  closeCustomerModal);
  if (closeBtnB) closeBtnB.addEventListener('click', closeCustomerModal);
  if (customerModal) {
    customerModal.addEventListener('click', function (e) {
      if (e.target === customerModal) closeCustomerModal();
    });
  }

  /* ── Customer modal AJAX submit ──────────────────────────── */
  if (modalForm) {
    modalForm.addEventListener('submit', function (e) {
      e.preventDefault();
      if (modalError) modalError.style.display = 'none';
      fetch('<?= url('customers/quick') ?>', { method: 'POST', body: new FormData(modalForm) })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data.success) {
            var opt = document.createElement('option');
            opt.value    = data.id;
            opt.text     = data.name;
            opt.selected = true;
            if (qSelect) qSelect.appendChild(opt);
            allOpts.push(opt.cloneNode(true));
            if (data.address_line1) setVal(leadAddrEl,  data.address_line1);
            if (data.city)          setVal(leadCityEl,  data.city);
            if (data.state)         setVal(leadStateEl, data.state);
            if (data.zip)           setVal(leadZipEl,   data.zip);
            closeCustomerModal();
            modalForm.reset();
          } else {
            if (modalError) {
              modalError.textContent   = data.message || 'Could not save customer.';
              modalError.style.display = '';
            }
          }
        })
        .catch(function () {
          if (modalError) {
            modalError.textContent   = 'Request failed.';
            modalError.style.display = '';
          }
        });
    });
  }
}());
</script>
