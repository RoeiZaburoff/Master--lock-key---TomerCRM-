<div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
  <h1 class="text-xl font-bold text-slate-900">Jobs</h1>
  <a href="<?= url('jobs/create') ?>"
     class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150 no-underline">
    + New Job
  </a>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm">
  <div class="px-5 py-4 border-b border-slate-100">
    <form method="GET" action="<?= url('jobs') ?>" class="flex flex-wrap gap-3 items-end">
      <div class="flex flex-col gap-1 flex-1 min-w-[200px]">
        <label class="text-xs font-medium text-slate-500 uppercase tracking-wider">Search</label>
        <input type="text" name="search" value="<?= h($search) ?>" placeholder="Customer or job type…"
               class="px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
      </div>
      <div class="flex flex-col gap-1 min-w-[150px]">
        <label class="text-xs font-medium text-slate-500 uppercase tracking-wider">Status</label>
        <select name="status"
                class="px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
          <option value="">All Statuses</option>
          <?php foreach (['pending','in_progress','completed','cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="flex items-end gap-2">
        <button type="submit"
                class="inline-flex items-center px-4 py-2 rounded-lg bg-slate-700 hover:bg-slate-800 text-white text-sm font-semibold shadow-sm transition-colors duration-150">
          Filter
        </button>
        <a href="<?= url('jobs') ?>"
           class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">
          Clear
        </a>
      </div>
    </form>
  </div>

  <?php if (empty($jobs)): ?>
    <div class="py-16 text-center text-sm text-slate-400">No jobs found.</div>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="table w-full">
      <thead>
        <tr>
          <th>#</th>
          <th>Date</th>
          <th>Customer</th>
          <th>Job Type</th>
          <th>Total</th>
          <th>Status</th>
          <th>Payment</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($jobs as $job): ?>
        <tr>
          <td><?= (int) $job['job_id'] ?></td>
          <td><?= h($job['scheduled_date'] ?? '—') ?></td>
          <td><?= h($job['customer_name']) ?></td>
          <td><?= h($job['job_type']) ?></td>
          <td><?= money($job['job_total']) ?></td>
          <td><span class="badge badge-<?= h($job['status']) ?>"><?= h(ucfirst(str_replace('_', ' ', $job['status']))) ?></span></td>
          <td><span class="badge badge-payment-<?= h($job['payment_status']) ?>"><?= h(ucfirst($job['payment_status'])) ?></span></td>
          <td class="actions">
            <a href="<?= url('jobs/' . $job['job_id']) ?>" class="btn btn-sm">View</a>
            <?php if ($job['status'] !== 'completed' && $job['status'] !== 'cancelled'): ?>
            <a href="<?= url('jobs/' . $job['job_id'] . '/complete') ?>" class="btn btn-sm btn-success">Complete</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
