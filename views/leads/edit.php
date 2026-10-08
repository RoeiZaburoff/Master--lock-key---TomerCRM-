<?php
$_selHour = 12;
$_selAmpm = 'PM';
if (!empty($lead['scheduled_time'])) {
    $_h = (int) substr($lead['scheduled_time'], 0, 2);
    $_selAmpm = $_h >= 12 ? 'PM' : 'AM';
    $_selHour = $_h % 12 ?: 12;
}
$_selDur = (int) ($lead['duration_hours'] ?? 1) ?: 1;
$_inp = 'w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition';
$_sel = 'w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition';
$_lbl = 'block text-sm font-medium text-slate-700 mb-1.5';
?>

<!-- Page header -->
<div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
  <h1 class="text-xl font-bold text-slate-900">Edit Lead #<?= (int) $lead['job_id'] ?></h1>
  <a href="<?= url('leads/' . $lead['job_id']) ?>"
     class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">
    ← Back
  </a>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm">
  <form method="POST" action="<?= url('leads/' . $lead['job_id'] . '/update') ?>">
    <?= \App\Core\CSRF::field() ?>

    <!-- Customer + Company -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 px-5 pt-5">
      <div>
        <label class="<?= $_lbl ?>">Customer *</label>
        <div class="flex flex-col gap-1.5">
          <input type="text" id="customer-search" placeholder="Search customers…" autocomplete="off"
                 class="<?= $_inp ?>">
          <select name="customer_id" id="customer-select" required class="<?= $_sel ?>">
            <?php foreach ($customers as $c): ?>
              <option value="<?= (int) $c['customer_id'] ?>" <?= $lead['customer_id'] == $c['customer_id'] ? 'selected' : '' ?>><?= h($c['full_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div>
        <label class="<?= $_lbl ?>">Company *</label>
        <select name="company_id" required class="<?= $_sel ?>">
          <option value="">— Select Company —</option>
          <?php foreach ($companies as $co): ?>
            <option value="<?= (int) $co['company_id'] ?>" <?= ($lead['company_id'] ?? '') == $co['company_id'] ? 'selected' : '' ?>><?= h($co['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['company_id'])): ?><p class="mt-1 text-xs text-red-600"><?= h($errors['company_id'][0]) ?></p><?php endif; ?>
      </div>
    </div>

    <!-- Status + scheduling + deposit + job total -->
    <?php
      $currentStatus = $lead['status'] ?? 'progress';
      if ($currentStatus === 'deposit') { $currentStatus = 'progress'; }
      $isDepositReceived = !empty($lead['deposit_received']) || ($lead['status'] ?? '') === 'deposit';
    ?>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 px-5 pt-5">

      <div>
        <label class="<?= $_lbl ?>">Status</label>
        <select name="status" id="status-select" class="<?= $_sel ?>">
          <?php foreach (['estimate' => 'Estimate', 'progress' => 'Progress', 'scheduled' => 'Scheduled', 'pending_payment' => 'Pending Payment', 'canceled' => 'Canceled', 'completed' => 'Completed'] as $val => $label): ?>
            <option value="<?= $val ?>" <?= $currentStatus === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div id="scheduled-date-group">
        <label class="<?= $_lbl ?>">Scheduled Date</label>
        <input type="date" name="scheduled_date" id="scheduled-date" value="<?= h($lead['scheduled_date'] ?? '') ?>" class="<?= $_inp ?>">
      </div>

      <div id="scheduled-time-group">
  <label class="<?= $_lbl ?>">Time</label>
  <div class="grid grid-cols-2 gap-2">
    <select name="scheduled_hour" id="scheduled-hour" class="w-full px-2 sm:px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
      <?php for ($h = 1; $h <= 12; $h++): ?>
        <option value="<?= $h ?>" <?= $_selHour === $h ? 'selected' : '' ?>><?= $h ?>:00</option>
      <?php endfor; ?>
    </select>
    <select name="scheduled_ampm" id="scheduled-ampm" class="w-full px-2 sm:px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
      <option value="AM" <?= $_selAmpm === 'AM' ? 'selected' : '' ?>>AM</option>
      <option value="PM" <?= $_selAmpm === 'PM' ? 'selected' : '' ?>>PM</option>
    </select>
  </div>
</div>

      <div id="scheduled-duration-group">
        <label class="<?= $_lbl ?>">Time Window</label>
        <select name="duration_hours" id="scheduled-duration" class="<?= $_sel ?>">
          <?php foreach ([1 => '1 Hour', 2 => '2 Hours', 3 => '3 Hours', 4 => '4 Hours'] as $val => $label): ?>
            <option value="<?= $val ?>" <?= $_selDur === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="flex items-end pb-1">
        <label class="flex items-center gap-2 cursor-pointer text-sm font-medium text-slate-700">
          <input type="checkbox" name="deposit_received" id="deposit-received-check" value="1"
                 <?= $isDepositReceived ? 'checked' : '' ?>
                 class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
          Deposit Received
        </label>
      </div>

      <div id="deposit-amount-group">
        <label class="<?= $_lbl ?>">Deposit Amount ($)</label>
        <input type="number" name="deposit_amount" id="deposit-amount"
               value="<?= h($lead['deposit_amount'] ?? '') ?>" step="0.01" min="0" placeholder="0.00"
               class="<?= $_inp ?>">
      </div>

      <div id="job-total-group">
        <label class="<?= $_lbl ?>">Job Total ($) *</label>
        <input type="number" name="job_total" id="job-total-input"
               value="<?= h($lead['job_total'] > 0 ? $lead['job_total'] : '') ?>" step="0.01" min="0" placeholder="0.00"
               class="<?= $_inp ?>">
      </div>

    </div>

    <!-- Service address -->
    <div class="px-5 pt-5">
      <label class="<?= $_lbl ?>">Service Address</label>
      <input type="text" name="address" id="lead-address" data-address-autocomplete
             value="<?= h($lead['address'] ?? '') ?>" autocomplete="off" placeholder="Street address…"
             class="<?= $_inp ?>">
    </div>
    <div class="grid grid-cols-3 gap-3 px-5 pt-3">
      <div>
        <label class="<?= $_lbl ?>">City</label>
        <input type="text" name="city" value="<?= h($lead['city'] ?? '') ?>" class="<?= $_inp ?>">
      </div>
      <div>
        <label class="<?= $_lbl ?>">State</label>
        <input type="text" name="state" value="<?= h($lead['state'] ?? '') ?>" class="<?= $_inp ?>">
      </div>
      <div>
        <label class="<?= $_lbl ?>">ZIP</label>
        <input type="text" name="zip" value="<?= h($lead['zip'] ?? '') ?>" class="<?= $_inp ?>">
      </div>
    </div>

    <!-- Lead Source + Contractor -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 px-5 pt-5">
      <div>
        <label class="<?= $_lbl ?>">Lead Source</label>
        <select name="lead_source_id" class="<?= $_sel ?>">
          <option value="">— None / Direct —</option>
          <?php foreach ($lead_sources as $ls): ?>
            <option value="<?= (int) $ls['lead_source_id'] ?>" <?= $lead['lead_source_id'] == $ls['lead_source_id'] ? 'selected' : '' ?>><?= h($ls['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="<?= $_lbl ?>">Contractor</label>
        <select name="contractor_id" class="<?= $_sel ?>">
          <option value="">— Owner Operated —</option>
          <?php foreach ($contractors as $co): ?>
            <option value="<?= (int) $co['contractor_id'] ?>" <?= $lead['contractor_id'] == $co['contractor_id'] ? 'selected' : '' ?>><?= h($co['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <!-- Description -->
    <div class="px-5 pt-5">
      <label class="<?= $_lbl ?>">Description</label>
      <textarea name="description" rows="3" class="<?= $_inp ?> resize-y"><?= h($lead['description'] ?? '') ?></textarea>
    </div>

    <!-- Estimate fields (shown only when status = estimate) -->
    <?php $_meta = $lead['meta'] ?? []; ?>
    <div id="estimate-fields-group" class="px-5 pt-5 border-t border-amber-100 bg-amber-50/50" style="<?= $currentStatus === 'estimate' ? '' : 'display:none;' ?>">
      <p class="text-xs font-semibold text-amber-700 uppercase tracking-wider mb-4">Estimate Information</p>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="<?= $_lbl ?>">Estimate Amount ($)</label>
          <input type="number" name="estimate_amount" id="estimate-amount"
                 value="<?= h($_meta['estimate_amount'] ?? '') ?>"
                 step="0.01" min="0" placeholder="0.00" class="<?= $_inp ?>">
        </div>
        <div>
          <label class="<?= $_lbl ?>">Follow-up Date</label>
          <input type="date" name="follow_up_date" id="follow-up-date"
                 value="<?= h($_meta['follow_up_date'] ?? '') ?>" class="<?= $_inp ?>">
        </div>
        <div class="sm:col-span-2">
          <label class="<?= $_lbl ?>">Dimensions</label>
          <input type="text" name="estimate_dimensions" id="estimate-dimensions"
                 value="<?= h($_meta['estimate_dimensions'] ?? '') ?>"
                 placeholder="e.g. 12x24 ft" class="<?= $_inp ?>">
        </div>
        <div class="sm:col-span-2">
          <label class="<?= $_lbl ?>">Estimate Notes</label>
          <textarea name="estimate_notes" id="estimate-notes" rows="2"
                    class="<?= $_inp ?> resize-y"><?= h($_meta['estimate_notes'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

    <!-- Entry date -->
    <div class="px-5 pt-5">
      <label class="<?= $_lbl ?>">Entry Date</label>
      <input type="datetime-local" name="created_at"
             value="<?= !empty($lead['created_at']) ? date('Y-m-d\TH:i', strtotime($lead['created_at'])) : '' ?>"
             class="<?= $_inp ?> max-w-xs">
    </div>

    <!-- Actions -->
    <div class="flex items-center gap-3 px-5 py-5 mt-2 border-t border-slate-100">
      <button type="submit"
              class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150">
        Update Lead
      </button>
      <a href="<?= url('leads/' . $lead['job_id']) ?>"
         class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">
        Cancel
      </a>
    </div>

  </form>
</div>

<script>
(function () {
  var statusSel    = document.getElementById('status-select');
  var dateGroup    = document.getElementById('scheduled-date-group');
  var timeGroup    = document.getElementById('scheduled-time-group');
  var durGroup     = document.getElementById('scheduled-duration-group');
  var dateInput    = document.getElementById('scheduled-date');
  var depositCheck = document.getElementById('deposit-received-check');
  var depositGroup = document.getElementById('deposit-amount-group');
  var depositInput = document.getElementById('deposit-amount');
  var jobTotalGroup = document.getElementById('job-total-group');
  var jobTotalInput = document.getElementById('job-total-input');
  var estimateGroup = document.getElementById('estimate-fields-group');
  var companySelect = document.querySelector('select[name="company_id"]');

  function toggleScheduled() {
    var isScheduled = statusSel.value === 'scheduled';
    dateGroup.style.display = isScheduled ? '' : 'none';
    timeGroup.style.display = isScheduled ? '' : 'none';
    durGroup.style.display  = isScheduled ? '' : 'none';
    dateInput.required = isScheduled;
  }
  function toggleEstimate() {
    var isEstimate = statusSel.value === 'estimate';
    estimateGroup.style.display = isEstimate ? '' : 'none';
    if (companySelect) companySelect.required = !isEstimate;
  }
  function toggleDeposit() {
    var hasDeposit = depositCheck.checked;
    depositGroup.style.display = hasDeposit ? '' : 'none';
    depositInput.required = hasDeposit;
    if (!hasDeposit) depositInput.value = '';
  }
  function toggleJobTotal() {
    var isPending = statusSel.value === 'pending_payment';
    jobTotalGroup.style.display = isPending ? '' : 'none';
    jobTotalInput.required = isPending;
  }
  statusSel.addEventListener('change', function () { toggleScheduled(); toggleEstimate(); toggleJobTotal(); });
  depositCheck.addEventListener('change', toggleDeposit);
  toggleScheduled();
  toggleEstimate();
  toggleDeposit();
  toggleJobTotal();

  var search  = document.getElementById('customer-search');
  var sel     = document.getElementById('customer-select');
  var allOpts = Array.from(sel.options);
  search.addEventListener('input', function () {
    var q = this.value.toLowerCase();
    Array.from(sel.options).forEach(function (opt) { opt.remove(); });
    allOpts.forEach(function (opt) {
      if (opt.text.toLowerCase().indexOf(q) !== -1) {
        sel.appendChild(opt.cloneNode(true));
      }
    });
  });
})();
</script>
