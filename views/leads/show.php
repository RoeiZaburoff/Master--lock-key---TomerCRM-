<?php
$statusLabels = ['estimate' => 'Estimate', 'progress' => 'Progress', 'scheduled' => 'Scheduled', 'pending' => 'Pending', 'canceled' => 'Canceled', 'completed' => 'Completed', 'pending_payment' => 'Pending Payment'];
$pmLabels     = ['cash' => 'Cash', 'credit_card' => 'Credit Card', 'check' => 'Check', 'zelle' => 'Zelle', 'cash_app' => 'Cash App'];
$_svc_addr  = trim($lead['address'] ?? '');
$_svc_city  = trim($lead['city']    ?? '');
$_svc_state = trim($lead['state']   ?? '');
$_svc_zip   = trim($lead['zip']     ?? '');
if ($_svc_addr === '') {
    $_svc_addr  = $lead['customer_address_line1'] ?? '';
    $_svc_city  = $lead['customer_city']          ?? '';
    $_svc_state = $lead['customer_state']         ?? '';
    $_svc_zip   = $lead['customer_zip']           ?? '';
}
$_addr_parts  = array_filter([
    $_svc_addr,
    $lead['customer_address_line2'] ?? '',
    $_svc_city,
    $_svc_state,
    $_svc_zip,
]);
$_addr_full   = implode(', ', $_addr_parts);
$_addr_enc    = urlencode($_addr_full);
$_sched_str   = $lead['scheduled_date'] ?? '';
if (!empty($lead['scheduled_time'])) {
    $_sched_str .= ' ' . date('g:i A', strtotime($lead['scheduled_time']));
}
?>

<!-- Page header -->
<div class="flex items-start justify-between gap-4 mb-6 flex-wrap">
  <div>
    <h1 class="text-xl font-bold text-slate-900">
      Lead #<?= (int) $lead['job_id'] ?><?= !empty($lead['company_name']) ? ' <span class="font-normal text-slate-400">—</span> ' . h($lead['company_name']) : '' ?>
    </h1>
  </div>
  <div class="flex items-center gap-2 flex-wrap">
    <a href="<?= url('leads/' . $lead['job_id'] . '/edit') ?>"
       class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium shadow-sm transition-colors duration-150 no-underline">
      Edit
    </a>
    <form method="POST" action="<?= url('leads/' . $lead['job_id'] . '/duplicate') ?>" style="display:inline">
      <?= \App\Core\CSRF::field() ?>
      <button type="submit"
              class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium shadow-sm transition-colors duration-150">
        Duplicate
      </button>
    </form>
    <button type="button" class="copy-lead-btn inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium shadow-sm transition-colors duration-150"
      data-company="<?= h($lead['company_name'] ?? '') ?>"
      data-customer="<?= h($lead['customer_name'] ?? '') ?>"
      data-address="<?= h($_addr_full) ?>"
      data-phone="<?= h($lead['customer_phone'] ?? '') ?>"
      data-schedule="<?= h($_sched_str) ?>"
      data-notes="<?= h($lead['description'] ?? '') ?>">
      Copy Lead
    </button>
    <?php if ($lead['status'] === 'estimate'): ?>
      <a href="<?= url('leads/' . $lead['job_id'] . '/estimate-pdf') ?>"
         class="inline-flex items-center px-3 py-1.5 rounded-lg border border-amber-300 bg-amber-50 hover:bg-amber-100 text-amber-800 text-sm font-medium shadow-sm transition-colors duration-150 no-underline">
        Download PDF
      </a>
      <button type="button" id="btn-convert-open"
              class="inline-flex items-center px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150">
        Convert to Job
      </button>
    <?php endif; ?>
    <?php if ($lead['status'] !== 'completed' && $lead['status'] !== 'canceled' && $lead['status'] !== 'estimate'): ?>
      <a href="<?= url('leads/' . $lead['job_id'] . '/complete') ?>"
         class="inline-flex items-center px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150 no-underline">
        Close Lead
      </a>
    <?php endif; ?>
    <?php if ($lead['status'] === 'completed'): ?>
      <a href="<?= url('leads/' . $lead['job_id'] . '/materials') ?>"
         class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium shadow-sm transition-colors duration-150 no-underline">
        Edit Costs
      </a>
      <a href="<?= url('invoices/create?job_id=' . $lead['job_id']) ?>"
         class="inline-flex items-center px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150 no-underline">
        Create Invoice
      </a>
    <?php endif; ?>
    <form method="POST" action="<?= url('leads/' . $lead['job_id'] . '/delete') ?>" style="display:inline"
          onsubmit="return confirm('Are you sure you want to delete this lead?');">
      <?= \App\Core\CSRF::field() ?>
      <button type="submit"
              class="inline-flex items-center px-3 py-1.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150">
        Delete
      </button>
    </form>
  </div>
</div>

<!-- Detail grid -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

  <!-- Lead details -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100">
      <h2 class="text-sm font-semibold text-slate-900">Lead Details</h2>
    </div>
    <div>

      <div class="flex justify-between items-baseline py-2.5 px-5 border-b border-slate-100 gap-4">
        <span class="text-sm text-slate-500 shrink-0">Customer</span>
        <strong class="text-sm font-medium text-slate-900 text-right">
          <a href="<?= url('customers/' . $lead['customer_id']) ?>" class="text-blue-600 hover:underline"><?= h($lead['customer_name']) ?></a>
        </strong>
      </div>

      <?php if ($_addr_full): ?>
      <div class="flex justify-between items-baseline py-2.5 px-5 border-b border-slate-100 gap-4">
        <span class="text-sm text-slate-500 shrink-0">Address</span>
        <strong class="text-sm font-medium text-slate-900 text-right"><?= address_maps_link($_addr_full) ?></strong>
      </div>
      <?php endif; ?>

      <?php if (!empty($lead['customer_phone'])): ?>
      <div class="flex justify-between items-baseline py-2.5 px-5 border-b border-slate-100 gap-4">
        <span class="text-sm text-slate-500 shrink-0">Phone</span>
        <strong class="text-sm font-medium text-slate-900 text-right">
          <a href="tel:<?= h(phone_digits($lead['customer_phone'])) ?>"><?= h(format_phone($lead['customer_phone'])) ?></a>
        </strong>
      </div>
      <?php endif; ?>

      <div class="flex justify-between items-baseline py-2.5 px-5 border-b border-slate-100 gap-4">
        <span class="text-sm text-slate-500 shrink-0">Company</span>
        <strong class="text-sm font-medium text-slate-900 text-right"><?= !empty($lead['company_name']) ? h($lead['company_name']) : '—' ?></strong>
      </div>

      <?php $_dur = (int)($lead['duration_hours'] ?? 0); ?>
      <div class="flex justify-between items-baseline py-2.5 px-5 border-b border-slate-100 gap-4">
        <span class="text-sm text-slate-500 shrink-0">Scheduled</span>
        <strong class="text-sm font-medium text-slate-900 text-right">
          <?= h($lead['scheduled_date'] ?? '—') ?><?= !empty($lead['scheduled_time']) ? ' ' . date('g:i A', strtotime($lead['scheduled_time'])) : '' ?><?= $_dur > 0 ? ' (' . $_dur . ' ' . ($_dur === 1 ? 'hr' : 'hrs') . ')' : '' ?>
        </strong>
      </div>

      <div class="flex justify-between items-center py-2.5 px-5 border-b border-slate-100 gap-4">
        <span class="text-sm text-slate-500 shrink-0">Status</span>
        <div class="flex items-center gap-1.5 flex-wrap justify-end">
          <span class="badge badge-<?= h($lead['status']) ?>"><?= h($statusLabels[$lead['status']] ?? ucfirst($lead['status'])) ?></span>
          <?php if (!empty($lead['deposit_received'])): ?><span class="badge badge-deposit">Deposit</span><?php endif; ?>
        </div>
      </div>

      <?php if (!empty($lead['deposit_received']) && (float)($lead['deposit_amount'] ?? 0) > 0): ?>
      <div class="flex justify-between items-baseline py-2.5 px-5 border-b border-slate-100 gap-4">
        <span class="text-sm text-slate-500 shrink-0">Deposit Amount</span>
        <strong class="text-sm font-medium text-slate-900 text-right"><?= money((float)$lead['deposit_amount']) ?></strong>
      </div>
      <?php endif; ?>

      <div class="flex justify-between items-baseline py-2.5 px-5 border-b border-slate-100 gap-4">
        <span class="text-sm text-slate-500 shrink-0">Lead Source</span>
        <strong class="text-sm font-medium text-slate-900 text-right"><?= h($lead['lead_source_name'] ?? 'Direct') ?></strong>
      </div>

      <div class="flex justify-between items-baseline py-2.5 px-5 border-b border-slate-100 gap-4">
        <span class="text-sm text-slate-500 shrink-0">Contractor</span>
        <strong class="text-sm font-medium text-slate-900 text-right"><?= h($lead['contractor_name'] ?? 'Owner') ?></strong>
      </div>

      <?php if (!empty($lead['created_at'])): ?>
      <div class="flex justify-between items-baseline py-2.5 px-5 border-b border-slate-100 gap-4">
        <span class="text-sm text-slate-500 shrink-0">Entry Date</span>
        <strong class="text-sm font-medium text-slate-900 text-right"><?= h(date('M j, Y g:i A', strtotime($lead['created_at']))) ?></strong>
      </div>
      <?php endif; ?>

      <?php if (!empty($lead['payment_method'])): ?>
      <div class="flex justify-between items-baseline py-2.5 px-5 border-b border-slate-100 gap-4">
        <span class="text-sm text-slate-500 shrink-0">Payment Method</span>
        <strong class="text-sm font-medium text-slate-900 text-right"><?= h($pmLabels[$lead['payment_method']] ?? $lead['payment_method']) ?></strong>
      </div>
      <?php endif; ?>

      <?php if (!empty($lead['description'])): ?>
      <div class="flex justify-between items-baseline py-2.5 px-5 gap-4">
        <span class="text-sm text-slate-500 shrink-0">Description</span>
        <strong class="text-sm font-medium text-slate-900 text-right"><?= h($lead['description']) ?></strong>
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
        <span class="text-sm text-slate-500 shrink-0">Lead Total</span>
        <strong class="text-sm font-semibold text-slate-900 text-right"><?= $lead['job_total'] > 0 ? money($lead['job_total']) : '—' ?></strong>
      </div>

      <?php if (!empty($lead['deposit_received']) && (float)($lead['deposit_amount'] ?? 0) > 0): ?>
      <div class="flex justify-between items-baseline py-2.5 px-5 border-b border-slate-100 gap-4">
        <span class="text-sm text-slate-500 shrink-0">Deposit Received</span>
        <strong class="text-sm font-medium text-slate-900 text-right"><?= money((float)$lead['deposit_amount']) ?></strong>
      </div>
      <?php endif; ?>

      <div class="flex justify-between items-center py-2.5 px-5 border-b border-slate-100 gap-4">
        <span class="text-sm text-slate-500 shrink-0">Payment</span>
        <strong class="text-sm font-medium text-slate-900 text-right flex items-center gap-1.5 flex-wrap justify-end">
          <span class="badge badge-payment-<?= h($lead['payment_status']) ?>"><?= h(ucfirst($lead['payment_status'])) ?></span>
          <?= money($lead['amount_paid']) ?> received
        </strong>
      </div>

      <?php if (!empty($lead['financials'])): $f = $lead['financials']; ?>
      <?php $_mt = (float)($f['material_tech'] ?? 0); $_mp = (float)($f['material_provider'] ?? 0); ?>

      <?php if ($_mt > 0): ?>
      <div class="flex justify-between items-baseline py-2.5 px-5 border-b border-slate-100 gap-4">
        <span class="text-sm text-slate-500 shrink-0">Parts by Technician</span>
        <strong class="text-sm font-medium text-slate-900 text-right"><?= money($_mt) ?></strong>
      </div>
      <?php endif; ?>

      <?php if ($_mp > 0): ?>
      <div class="flex justify-between items-baseline py-2.5 px-5 border-b border-slate-100 gap-4">
        <span class="text-sm text-slate-500 shrink-0">Parts by Company</span>
        <strong class="text-sm font-medium text-slate-900 text-right"><?= money($_mp) ?></strong>
      </div>
      <?php endif; ?>

      <?php if ((float)$f['lead_fee'] > 0): ?>
      <div class="flex justify-between items-baseline py-2.5 px-5 border-b border-slate-100 gap-4">
        <span class="text-sm text-slate-500 shrink-0">Lead Fee</span>
        <strong class="text-sm font-medium text-slate-900 text-right"><?= money($f['lead_fee']) ?></strong>
      </div>
      <?php endif; ?>

      <div class="flex justify-between items-baseline py-2.5 px-5 border-b border-slate-100 gap-4">
        <span class="text-sm text-slate-500 shrink-0">Net Revenue</span>
        <strong class="text-sm font-medium text-slate-900 text-right"><?= money($f['gross_profit']) ?></strong>
      </div>

      <div class="flex justify-between items-baseline py-2.5 px-5 border-b border-slate-100 gap-4">
        <span class="text-sm text-slate-500 shrink-0">Tech Payout</span>
        <strong class="text-sm font-medium text-slate-900 text-right"><?= money($f['contractor_share']) ?></strong>
      </div>

      <div class="flex justify-between items-baseline py-3 px-5 bg-slate-50 gap-4">
        <span class="text-sm font-semibold text-slate-700 shrink-0">Provider Profit</span>
        <strong class="text-base font-bold text-right <?= (float)$f['net_profit'] < 0 ? 'text-red-600' : 'text-emerald-600' ?>"><?= money($f['net_profit']) ?></strong>
      </div>

      <?php else: ?>
      <div class="py-12 text-center text-sm text-slate-400">Profit calculated on completion.</div>
      <?php endif; ?>

    </div>
  </div>

</div>

<?php
$_sched_log = [];
if (!empty($lead['meta']['sched_log'])) {
    $_decoded = json_decode($lead['meta']['sched_log'], true);
    if (is_array($_decoded)) {
        $_sched_log = $_decoded;
    }
}
?>
<?php if (!empty($_sched_log)): ?>
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mt-5">
  <div class="px-5 py-4 border-b border-slate-100">
    <h2 class="text-sm font-semibold text-slate-900">Scheduling History</h2>
  </div>
  <div>
    <?php foreach (array_reverse($_sched_log) as $_si => $_entry): ?>
    <div class="flex justify-between items-baseline py-2.5 px-5 border-b border-slate-100 gap-4">
      <span class="text-sm text-slate-500 shrink-0">Appointment <?= count($_sched_log) - $_si ?></span>
      <strong class="text-sm font-medium text-slate-900 text-right">
        <?= h($_entry['date']) ?>
        <?= !empty($_entry['time']) ? date('g:i A', strtotime($_entry['time'])) : '' ?>
        <span class="text-xs font-normal text-slate-400 ml-2">logged <?= h(date('M j, Y g:i A', strtotime($_entry['logged_at']))) ?></span>
      </strong>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php if ($lead['status'] === 'estimate'): ?>
<?php $_meta = $lead['meta'] ?? []; ?>
<div class="bg-white rounded-xl border border-amber-200 shadow-sm overflow-hidden mt-5">
  <div class="px-5 py-4 border-b border-amber-100 flex items-center justify-between">
    <h2 class="text-sm font-semibold text-slate-900">Estimate Details</h2>
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">Estimate</span>
  </div>
  <div>
    <div class="flex justify-between items-baseline py-2.5 px-5 border-b border-slate-100 gap-4">
      <span class="text-sm text-slate-500 shrink-0">Estimate Amount</span>
      <strong class="text-sm font-semibold text-slate-900 text-right">
        <?= !empty($_meta['estimate_amount']) ? money((float) $_meta['estimate_amount']) : '—' ?>
      </strong>
    </div>
    <?php if (!empty($_meta['estimate_dimensions'])): ?>
    <div class="flex justify-between items-baseline py-2.5 px-5 border-b border-slate-100 gap-4">
      <span class="text-sm text-slate-500 shrink-0">Dimensions</span>
      <strong class="text-sm font-medium text-slate-900 text-right"><?= h($_meta['estimate_dimensions']) ?></strong>
    </div>
    <?php endif; ?>
    <?php if (!empty($_meta['follow_up_date'])): ?>
    <?php $_fup_past = $_meta['follow_up_date'] <= date('Y-m-d'); ?>
    <div class="flex justify-between items-baseline py-2.5 px-5 border-b border-slate-100 gap-4">
      <span class="text-sm text-slate-500 shrink-0">Follow-up Date</span>
      <strong class="text-sm font-medium text-right <?= $_fup_past ? 'text-red-600' : 'text-slate-900' ?>">
        <?= h($_meta['follow_up_date']) ?><?= $_fup_past ? ' — overdue' : '' ?>
      </strong>
    </div>
    <?php endif; ?>
    <?php if (!empty($_meta['estimate_notes'])): ?>
    <div class="flex justify-between items-baseline py-2.5 px-5 gap-4">
      <span class="text-sm text-slate-500 shrink-0">Notes</span>
      <strong class="text-sm font-medium text-slate-900 text-right"><?= h($_meta['estimate_notes']) ?></strong>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Convert to Job Modal -->
<div id="convert-modal" class="fixed inset-0 z-50 flex items-center justify-center" style="display:none !important">
  <div id="convert-backdrop" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
  <div class="relative z-10 w-full max-w-md mx-4">
    <div class="bg-white rounded-xl shadow-2xl overflow-hidden">
      <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
        <h3 class="text-sm font-semibold text-slate-900">Convert Estimate to Job</h3>
        <button type="button" id="convert-close"
                class="text-slate-400 hover:text-slate-600 text-lg leading-none px-1 transition-colors">&#10005;</button>
      </div>
      <form method="POST" action="<?= url('leads/' . $lead['job_id'] . '/convert') ?>">
        <?= \App\Core\CSRF::field() ?>
        <div class="px-5 pt-5 pb-2 flex flex-col gap-4">
          <p class="text-sm text-slate-600">
            Converting will set status to <strong>Pending</strong> and job total to
            <strong><?= !empty($_meta['estimate_amount']) ? money((float) $_meta['estimate_amount']) : '$0.00' ?></strong>.
          </p>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Scheduled Date <span class="text-red-500">*</span></label>
            <input type="date" name="scheduled_date" required
                   class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Scheduled Time</label>
            <input type="time" name="scheduled_time"
                   class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
          </div>
        </div>
        <div class="flex items-center gap-3 px-5 py-4 border-t border-slate-100 mt-2">
          <button type="submit"
                  class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150">
            Convert to Job
          </button>
          <button type="button" id="convert-cancel"
                  class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150">
            Cancel
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(function () {
  var modal     = document.getElementById('convert-modal');
  var backdrop  = document.getElementById('convert-backdrop');
  var btnOpen   = document.getElementById('btn-convert-open');
  var btnClose  = document.getElementById('convert-close');
  var btnCancel = document.getElementById('convert-cancel');
  if (!modal || !btnOpen) return;
  function open()  { modal.style.setProperty('display', 'flex', 'important'); }
  function close() { modal.style.setProperty('display', 'none', 'important'); }
  btnOpen.addEventListener('click',   open);
  btnClose.addEventListener('click',  close);
  btnCancel.addEventListener('click', close);
  backdrop.addEventListener('click',  close);
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal.style.display !== 'none') close();
  });
}());
</script>
<?php endif; ?>
