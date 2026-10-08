<?php
$_inp = 'w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition';
$_sel = 'w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition';
$_lbl = 'block text-sm font-medium text-slate-700 mb-1.5';
?>

<div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
  <h1 class="text-xl font-bold text-slate-900">Edit Job #<?= (int) $job['job_id'] ?></h1>
  <a href="<?= url('jobs/' . $job['job_id']) ?>"
     class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">
    ← Back
  </a>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm max-w-2xl">
  <form method="POST" action="<?= url('jobs/' . $job['job_id'] . '/update') ?>">
    <?= \App\Core\CSRF::field() ?>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 px-5 pt-5">
      <div>
        <label class="<?= $_lbl ?>">Customer *</label>
        <select name="customer_id" required class="<?= $_sel ?>">
          <?php foreach ($customers as $c): ?>
            <option value="<?= (int) $c['customer_id'] ?>" <?= $job['customer_id'] == $c['customer_id'] ? 'selected' : '' ?>><?= h($c['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="<?= $_lbl ?>">Job Type *</label>
        <input type="text" name="job_type" value="<?= h($job['job_type']) ?>" required class="<?= $_inp ?>">
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 px-5 pt-4">
      <div>
        <label class="<?= $_lbl ?>">Job Total ($) *</label>
        <input type="number" name="job_total" value="<?= h($job['job_total']) ?>" step="0.01" min="0" required class="<?= $_inp ?>">
      </div>
      <div>
        <label class="<?= $_lbl ?>">Scheduled Date</label>
        <input type="date" name="scheduled_date" value="<?= h($job['scheduled_date'] ?? '') ?>" class="<?= $_inp ?>">
      </div>
      <div>
        <label class="<?= $_lbl ?>">Scheduled Time</label>
        <input type="time" name="scheduled_time" value="<?= h($job['scheduled_time'] ?? '') ?>" class="<?= $_inp ?>">
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 px-5 pt-4">
      <div>
        <label class="<?= $_lbl ?>">Lead Source</label>
        <select name="lead_source_id" class="<?= $_sel ?>">
          <option value="">— None / Direct —</option>
          <?php foreach ($lead_sources as $ls): ?>
            <option value="<?= (int) $ls['lead_source_id'] ?>" <?= $job['lead_source_id'] == $ls['lead_source_id'] ? 'selected' : '' ?>><?= h($ls['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="<?= $_lbl ?>">Contractor</label>
        <select name="contractor_id" class="<?= $_sel ?>">
          <option value="">— Owner Operated —</option>
          <?php foreach ($contractors as $co): ?>
            <option value="<?= (int) $co['contractor_id'] ?>" <?= $job['contractor_id'] == $co['contractor_id'] ? 'selected' : '' ?>><?= h($co['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 px-5 pt-4">
      <div>
        <label class="<?= $_lbl ?>">Status</label>
        <select name="status" class="<?= $_sel ?>">
          <?php foreach (['pending','in_progress','completed','cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $job['status'] === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="<?= $_lbl ?>">Payment Status</label>
        <select name="payment_status" class="<?= $_sel ?>">
          <?php foreach (['unpaid','partial','paid'] as $ps): ?>
            <option value="<?= $ps ?>" <?= $job['payment_status'] === $ps ? 'selected' : '' ?>><?= ucfirst($ps) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="<?= $_lbl ?>">Amount Paid ($)</label>
        <input type="number" name="amount_paid" value="<?= h($job['amount_paid']) ?>" step="0.01" min="0" class="<?= $_inp ?>">
      </div>
    </div>

    <div class="px-5 pt-4">
      <label class="<?= $_lbl ?>">Description</label>
      <textarea name="description" rows="3" class="<?= $_inp ?> resize-y"><?= h($job['description'] ?? '') ?></textarea>
    </div>

    <div class="flex items-center gap-3 px-5 py-5 mt-2 border-t border-slate-100">
      <button type="submit"
              class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150">
        Update Job
      </button>
      <a href="<?= url('jobs/' . $job['job_id']) ?>"
         class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">
        Cancel
      </a>
    </div>

  </form>
</div>
