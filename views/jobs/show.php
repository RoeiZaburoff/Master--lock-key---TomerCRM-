<?php
$_job_addr_parts = array_filter([
    $job['customer_address_line1'] ?? '',
    $job['customer_address_line2'] ?? '',
    $job['customer_city'] ?? '',
    $job['customer_state'] ?? '',
    $job['customer_zip'] ?? '',
]);
$_job_addr_full  = implode(', ', $_job_addr_parts);
$_job_sched_str  = $job['scheduled_date'] ?? '';
if (!empty($job['scheduled_time'])) {
    $_job_sched_str .= ' ' . date('g:i A', strtotime($job['scheduled_time']));
}
?>

<div class="flex items-start justify-between gap-4 mb-6 flex-wrap">
  <h1 class="text-xl font-bold text-slate-900">Job #<?= (int) $job['job_id'] ?> — <?= h($job['job_type']) ?></h1>
  <div class="flex items-center gap-2 flex-wrap">
    <a href="<?= url('jobs/' . $job['job_id'] . '/edit') ?>"
       class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium shadow-sm transition-colors duration-150 no-underline">
      Edit
    </a>
    <button type="button"
            onclick="copyLeadDetails(this)"
            data-company="<?= h($job['company_name'] ?? '') ?>"
            data-customer="<?= h($job['customer_name'] ?? '') ?>"
            data-address="<?= h($_job_addr_full) ?>"
            data-phone="<?= h($job['customer_phone'] ?? '') ?>"
            data-schedule="<?= h($_job_sched_str) ?>"
            data-notes="<?= h($job['description'] ?? '') ?>"
            class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium shadow-sm transition-colors duration-150">
      Copy Lead
    </button>
    <?php if ($job['status'] !== 'completed' && $job['status'] !== 'cancelled'): ?>
      <a href="<?= url('jobs/' . $job['job_id'] . '/complete') ?>"
         class="inline-flex items-center px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150 no-underline">
        Mark Complete
      </a>
    <?php endif; ?>
    <?php if ($job['status'] === 'completed'): ?>
      <a href="<?= url('invoices/create?job_id=' . $job['job_id']) ?>"
         class="inline-flex items-center px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150 no-underline">
        Create Invoice
      </a>
    <?php endif; ?>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">

  <!-- Job Details -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100">
      <h2 class="text-sm font-semibold text-slate-900">Job Details</h2>
    </div>
    <div>
      <div class="flex justify-between items-baseline py-2.5 px-5 border-b border-slate-100 gap-4">
        <span class="text-sm text-slate-500 shrink-0">Customer</span>
        <strong class="text-sm font-medium text-slate-900 text-right">
          <a href="<?= url('customers/' . $job['customer_id']) ?>" class="text-blue-600 hover:underline"><?= h($job['customer_name']) ?></a>
        </strong>
      </div>
      <?php if ($_job_addr_full): ?>
      <div class="flex justify-between items-baseline py-2.5 px-5 border-b border-slate-100 gap-4">
        <span class="text-sm text-slate-500 shrink-0">Address</span>
        <strong class="text-sm font-medium text-slate-900 text-right"><?= address_maps_link($_job_addr_full) ?></strong>
      </div>
      <?php endif; ?>
      <div class="flex justify-between items-baseline py-2.5 px-5 border-b border-slate-100 gap-4">
        <span class="text-sm text-slate-500 shrink-0">Job Type</span>
        <strong class="text-sm font-medium text-slate-900 text-right"><?= h($job['job_type']) ?></strong>
      </div>
      <div class="flex justify-between items-baseline py-2.5 px-5 border-b border-slate-100 gap-4">
        <span class="text-sm text-slate-500 shrink-0">Scheduled</span>
        <strong class="text-sm font-medium text-slate-900 text-right">
          <?= h($job['scheduled_date'] ?? '—') ?><?= $job['scheduled_time'] ? ' ' . date('g:i A', strtotime($job['scheduled_time'])) : '' ?>
        </strong>
      </div>
      <div class="flex justify-between items-center py-2.5 px-5 border-b border-slate-100 gap-4">
        <span class="text-sm text-slate-500 shrink-0">Status</span>
        <span class="badge badge-<?= h($job['status']) ?>"><?= h(ucfirst(str_replace('_', ' ', $job['status']))) ?></span>
      </div>
      <div class="flex justify-between items-baseline py-2.5 px-5 border-b border-slate-100 gap-4">
        <span class="text-sm text-slate-500 shrink-0">Lead Source</span>
        <strong class="text-sm font-medium text-slate-900 text-right"><?= h($job['lead_source_name'] ?? 'Direct') ?></strong>
      </div>
      <div class="flex justify-between items-baseline py-2.5 px-5 <?= $job['description'] ? 'border-b border-slate-100' : '' ?> gap-4">
        <span class="text-sm text-slate-500 shrink-0">Contractor</span>
        <strong class="text-sm font-medium text-slate-900 text-right"><?= h($job['contractor_name'] ?? 'Owner') ?></strong>
      </div>
      <?php if ($job['description']): ?>
      <div class="flex justify-between items-baseline py-2.5 px-5 gap-4">
        <span class="text-sm text-slate-500 shrink-0">Description</span>
        <strong class="text-sm font-medium text-slate-900 text-right"><?= h($job['description']) ?></strong>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Financials -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100">
      <h2 class="text-sm font-semibold text-slate-900">Financials</h2>
    </div>
    <div>
      <div class="flex justify-between items-baseline py-2.5 px-5 border-b border-slate-100 gap-4">
        <span class="text-sm text-slate-500 shrink-0">Job Total</span>
        <strong class="text-sm font-semibold text-slate-900 text-right"><?= money($job['job_total']) ?></strong>
      </div>
      <div class="flex justify-between items-center py-2.5 px-5 border-b border-slate-100 gap-4">
        <span class="text-sm text-slate-500 shrink-0">Payment</span>
        <strong class="text-sm font-medium text-slate-900 text-right flex items-center gap-1.5 flex-wrap justify-end">
          <span class="badge badge-payment-<?= h($job['payment_status']) ?>"><?= h(ucfirst($job['payment_status'])) ?></span>
          <?= money($job['amount_paid']) ?> received
        </strong>
      </div>
      <?php if (!empty($job['financials'])): $f = $job['financials']; ?>
      <div class="flex justify-between items-baseline py-2.5 px-5 border-b border-slate-100 gap-4">
        <span class="text-sm text-slate-500 shrink-0">Material Cost</span>
        <strong class="text-sm font-medium text-slate-900 text-right"><?= money($f['material_cost']) ?></strong>
      </div>
      <div class="flex justify-between items-baseline py-2.5 px-5 border-b border-slate-100 gap-4">
        <span class="text-sm text-slate-500 shrink-0">Lead Fee</span>
        <strong class="text-sm font-medium text-slate-900 text-right"><?= money($f['lead_fee']) ?></strong>
      </div>
      <div class="flex justify-between items-baseline py-2.5 px-5 border-b border-slate-100 gap-4">
        <span class="text-sm text-slate-500 shrink-0">Contractor Share</span>
        <strong class="text-sm font-medium text-slate-900 text-right"><?= money($f['contractor_share']) ?></strong>
      </div>
      <div class="flex justify-between items-baseline py-2.5 px-5 border-b border-slate-100 gap-4">
        <span class="text-sm text-slate-500 shrink-0">Gross Profit</span>
        <strong class="text-sm font-medium text-slate-900 text-right"><?= money($f['gross_profit']) ?></strong>
      </div>
      <div class="flex justify-between items-baseline py-3 px-5 bg-slate-50 gap-4">
        <span class="text-sm font-semibold text-slate-700 shrink-0">Net Profit</span>
        <strong class="text-base font-bold text-right <?= (float)$f['net_profit'] < 0 ? 'text-red-600' : 'text-emerald-600' ?>"><?= money($f['net_profit']) ?></strong>
      </div>
      <?php else: ?>
      <div class="py-12 text-center text-sm text-slate-400">Profit calculated on completion.</div>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php if (!empty($job['materials'])): ?>
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
  <div class="px-5 py-4 border-b border-slate-100">
    <h2 class="text-sm font-semibold text-slate-900">Materials Used</h2>
  </div>
  <div class="overflow-x-auto">
    <table class="table w-full">
      <thead>
        <tr>
          <th>Item</th>
          <th>Ownership</th>
          <th>Qty</th>
          <th>Unit Cost</th>
          <th>Line Total</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($job['materials'] as $m): ?>
        <tr>
          <td><?= h($m['catalog_name'] ?? $m['description']) ?></td>
          <td><span class="badge badge-ownership-<?= h($m['ownership']) ?>"><?= ucfirst(h($m['ownership'])) ?></span></td>
          <td><?= h($m['qty']) ?></td>
          <td><?= money($m['unit_cost']) ?></td>
          <td><?= money($m['line_total']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<script>
window.copyLeadDetails = window.copyLeadDetails || function(btn) {
  var d = btn.dataset;
  var text = 'Company: '  + (d.company  || '—') + '\n' +
             'Customer: ' + (d.customer || '—') + '\n' +
             'Address: '  + (d.address  || '—') + '\n' +
             'Phone: '    + (d.phone    || '—') + '\n' +
             'Schedule: ' + (d.schedule || '—') + '\n' +
             'Notes: '    + (d.notes    || '—');
  navigator.clipboard.writeText(text).then(function() {
    var orig = btn.textContent;
    btn.textContent = 'Copied!';
    setTimeout(function() { btn.textContent = orig; }, 2000);
  });
};
</script>
