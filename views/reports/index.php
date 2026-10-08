<?php
$_inp = 'w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition';
$_sel = 'w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition';
$_lbl = 'block text-sm font-medium text-slate-700 mb-1.5';
?>

<div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
  <h1 class="text-xl font-bold text-slate-900">Financial Reports</h1>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm mb-5">
  <div class="px-5 py-3.5 border-b border-slate-100">
    <h2 class="text-sm font-semibold text-slate-700">Filters</h2>
  </div>
  <form method="POST" action="<?= url('reports') ?>">
    <?= \App\Core\CSRF::field() ?>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 px-5 pt-4">
      <div>
        <label class="<?= $_lbl ?>">Date From</label>
        <input type="date" name="date_from" value="<?= h($filters['date_from'] ?? '') ?>" class="<?= $_inp ?>">
      </div>
      <div>
        <label class="<?= $_lbl ?>">Date To</label>
        <input type="date" name="date_to" value="<?= h($filters['date_to'] ?? '') ?>" class="<?= $_inp ?>">
      </div>
      <div>
        <label class="<?= $_lbl ?>">Company</label>
        <select name="company_id" class="<?= $_sel ?>">
          <option value="">— All Companies —</option>
          <?php foreach ($companies as $co): ?>
            <option value="<?= (int) $co['company_id'] ?>" <?= (($filters['company_id'] ?? '') == $co['company_id']) ? 'selected' : '' ?>><?= h($co['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="<?= $_lbl ?>">Lead Source</label>
        <select name="lead_source_id" class="<?= $_sel ?>">
          <option value="">— All —</option>
          <?php foreach ($lead_sources as $ls): ?>
            <option value="<?= (int) $ls['lead_source_id'] ?>" <?= (($filters['lead_source_id'] ?? '') == $ls['lead_source_id']) ? 'selected' : '' ?>><?= h($ls['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="<?= $_lbl ?>">Contractor</label>
        <select name="contractor_id" class="<?= $_sel ?>">
          <option value="">— All —</option>
          <?php foreach ($contractors as $co): ?>
            <option value="<?= (int) $co['contractor_id'] ?>" <?= (($filters['contractor_id'] ?? '') == $co['contractor_id']) ? 'selected' : '' ?>><?= h($co['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="<?= $_lbl ?>">Lead Status</label>
        <select name="status_filter" class="<?= $_sel ?>">
          <option value="completed" <?= ($filters['status_filter'] ?? 'completed') === 'completed' ? 'selected' : '' ?>>Completed</option>
          <option value="deposit"   <?= ($filters['status_filter'] ?? '') === 'deposit'   ? 'selected' : '' ?>>Deposit Received</option>
          <option value="all"       <?= ($filters['status_filter'] ?? '') === 'all'       ? 'selected' : '' ?>>All (Completed + Deposit)</option>
        </select>
      </div>
    </div>
    <div class="px-5 py-4 mt-1">
      <button type="submit"
              class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150">
        Generate Report
      </button>
    </div>
  </form>
</div>

<?php if (!empty($rows)): ?>
<?php
  $showDeposit  = !empty($totals['deposit_amount']);
  $statusFilter = $filters['status_filter'] ?? 'completed';
  $mixedStatuses = $statusFilter === 'all';
?>
<div class="bg-white rounded-xl border border-slate-200 shadow-sm">
  <div class="flex items-center justify-between gap-4 px-5 py-3.5 border-b border-slate-100 flex-wrap">
    <h2 class="text-sm font-semibold text-slate-700">Results <span class="text-slate-400 font-normal">(<?= count($rows) ?> leads)</span></h2>
    <a href="<?= url('reports/pdf?') . http_build_query($filters) ?>" target="_blank"
       class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium shadow-sm transition-colors duration-150 no-underline">
      Export PDF
    </a>
  </div>
  <div class="table-scroll">
    <table class="table w-full">
      <thead>
        <tr>
          <th>#</th>
          <?php if ($mixedStatuses): ?><th>Status</th><?php endif; ?>
          <th>Date</th>
          <th>Customer</th>
          <th>Company</th>
          <th>Lead Source</th>
          <th>Contractor</th>
          <th>Total</th>
          <?php if ($showDeposit || $statusFilter === 'deposit'): ?><th>Deposit</th><?php endif; ?>
          <th>Parts by Tech</th>
          <th>Parts by Co.</th>
          <th>Lead Fee</th>
          <th>CC Fee</th>
          <th>Tech Payout</th>
          <th>Provider Profit</th>
        </tr>
      </thead>
      <tbody>
        <?php
          $statusLabels = ['completed' => 'Completed', 'progress' => 'Progress', 'scheduled' => 'Scheduled'];
          foreach ($rows as $row):
            $isDeposit = !empty($row['deposit_received']) && $row['status'] !== 'completed';
            $dateVal   = $row['reporting_date'] ?? $row['scheduled_date'];
        ?>
        <tr>
          <td><a href="<?= url('leads/' . $row['job_id']) ?>" class="text-blue-600 no-underline hover:underline font-medium">#<?= (int) $row['job_id'] ?></a></td>
          <?php if ($mixedStatuses): ?>
          <td>
            <span class="badge badge-<?= h($row['status']) ?>"><?= h($statusLabels[$row['status']] ?? ucfirst($row['status'])) ?></span>
            <?php if (!empty($row['deposit_received'])): ?><span class="badge badge-deposit">Deposit</span><?php endif; ?>
          </td>
          <?php endif; ?>
          <td><?= !empty($dateVal) ? h(date('m/d/Y', strtotime($dateVal))) : '—' ?></td>
          <td><?= h($row['customer_name']) ?></td>
          <td><?= h($row['company_name']) ?></td>
          <td><?= h($row['lead_source_name']) ?></td>
          <td><?= h($row['contractor_name']) ?></td>
          <td class="font-medium"><?= money($row['job_total']) ?></td>
          <?php if ($showDeposit || $statusFilter === 'deposit'): ?>
          <td><?= (float)($row['deposit_amount'] ?? 0) > 0 ? money($row['deposit_amount']) : '—' ?></td>
          <?php endif; ?>
          <td><?= !$isDeposit && (float)$row['parts_by_tech']    > 0 ? money($row['parts_by_tech'])    : '—' ?></td>
          <td><?= !$isDeposit && (float)$row['parts_by_company'] > 0 ? money($row['parts_by_company']) : '—' ?></td>
          <td><?= !$isDeposit && (float)$row['lead_fee']         > 0 ? money($row['lead_fee'])         : '—' ?></td>
          <td><?= !$isDeposit && (float)$row['cc_fee']           > 0 ? money($row['cc_fee'])           : '—' ?></td>
          <td><?= !$isDeposit ? money($row['contractor_share']) : '—' ?></td>
          <td class="font-bold <?= !$isDeposit && (float)$row['net_profit'] < 0 ? 'text-danger' : 'text-success' ?>"><?= !$isDeposit ? money($row['net_profit']) : '—' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr class="totals-row">
          <td colspan="<?= ($mixedStatuses ? 7 : 6) ?>"><strong>TOTALS</strong></td>
          <td><strong><?= money($totals['job_total']) ?></strong></td>
          <?php if ($showDeposit || $statusFilter === 'deposit'): ?>
          <td><strong><?= money($totals['deposit_amount']) ?></strong></td>
          <?php endif; ?>
          <td><strong><?= money($totals['parts_by_tech']) ?></strong></td>
          <td><strong><?= money($totals['parts_by_company']) ?></strong></td>
          <td><strong><?= money($totals['lead_fee']) ?></strong></td>
          <td><strong><?= money($totals['cc_fee']) ?></strong></td>
          <td><strong><?= money($totals['contractor_share']) ?></strong></td>
          <td class="<?= $totals['net_profit'] < 0 ? 'text-danger' : 'text-success' ?>"><strong><?= money($totals['net_profit']) ?></strong></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>
<?php elseif (isset($filters) && !empty(array_filter($filters))): ?>
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
    <div class="py-16 text-center text-sm text-slate-400 px-6">No leads found for the selected filters.</div>
  </div>
<?php endif; ?>
