<!-- Page header -->
<div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
  <h1 class="text-xl font-bold text-slate-900">Leads</h1>
  <a href="<?= url('leads/create') ?>"
     class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150 no-underline">
    + New Lead
  </a>
</div>

<!-- Filter card -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm mb-6">
  <div class="px-5 py-4 border-b border-slate-100">
    <form method="GET" action="<?= url('leads') ?>" class="flex flex-wrap gap-3 items-end">
      <div class="flex flex-col gap-1 flex-1 min-w-[180px]">
        <label class="text-xs font-medium text-slate-500 uppercase tracking-wider">Search</label>
        <input type="text" name="q" value="<?= h($q) ?>" placeholder="Name, phone, address, amount…"
               class="px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
      </div>
      <div class="flex flex-col gap-1 min-w-[150px]">
        <label class="text-xs font-medium text-slate-500 uppercase tracking-wider">Status</label>
        <select name="status"
                class="px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
          <option value="">All Statuses</option>
          <?php foreach (['estimate' => 'Estimate', 'progress' => 'Progress', 'scheduled' => 'Scheduled', 'pending_payment' => 'Pending Payment', 'deposit' => 'Deposit Received', 'canceled' => 'Canceled', 'completed' => 'Completed'] as $val => $label): ?>
            <option value="<?= $val ?>" <?= $status === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="flex flex-col gap-1 min-w-[130px]">
        <label class="text-xs font-medium text-slate-500 uppercase tracking-wider">Period</label>
        <select name="period"
                class="px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
          <option value="">All Periods</option>
          <?php foreach (['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'] as $val => $label): ?>
            <option value="<?= $val ?>" <?= ($period ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="flex items-end gap-2">
        <button type="submit"
                class="inline-flex items-center px-4 py-2 rounded-lg bg-slate-700 hover:bg-slate-800 text-white text-sm font-semibold shadow-sm transition-colors duration-150">
          Filter
        </button>
        <a href="<?= url('leads') ?>"
           class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">
          Clear
        </a>
      </div>
    </form>
  </div>

  <?php if (empty($leads)): ?>
    <div class="py-16 text-center text-sm text-slate-400">No leads found.</div>
  <?php else: ?>
  <?php
    $statusLabels = ['estimate' => 'Estimate', 'progress' => 'Progress', 'scheduled' => 'Scheduled', 'pending_payment' => 'Pending Payment', 'canceled' => 'Canceled', 'completed' => 'Completed'];
    $pmLabels = ['cash' => 'Cash', 'credit_card' => 'Credit Card', 'check' => 'Check', 'zelle' => 'Zelle', 'cash_app' => 'Cash App'];
  ?>
  <div class="overflow-x-auto">
    <table class="table w-full">
      <thead>
        <tr>
          <th>#</th>
          <th>Date</th>
          <th>Customer</th>
          <th>Phone</th>
          <th>Address</th>
          <th>Company</th>
          <th>Total</th>
          <th>Status</th>
          <th>Payment</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($leads as $lead):
          $_lead_addr_parts = array_filter([
              $lead['address_line1'] ?? '',
              $lead['city']          ?? '',
              $lead['state']         ?? '',
              $lead['zip']           ?? '',
          ]);
          $_lead_addr_full = implode(', ', $_lead_addr_parts);
          $_sched_str = $lead['scheduled_date'] ?? '';
          if (!empty($lead['scheduled_time'])) {
              $_sched_str .= ' ' . date('g:i A', strtotime($lead['scheduled_time']));
          }
        ?>
        <tr>
          <td><?= (int) $lead['job_id'] ?></td>
          <td><?= h($lead['status'] === 'completed' && !empty($lead['completed_at']) ? date('Y-m-d', strtotime($lead['completed_at'])) : ($lead['scheduled_date'] ?? '—')) ?></td>
          <td><?= h($lead['customer_name']) ?></td>
          <td style="white-space:nowrap;"><a href="tel:<?= h(phone_digits($lead['customer_phone'] ?? '')) ?>"><?= h(format_phone($lead['customer_phone'] ?? '')) ?></a></td>
          <td><?= $_lead_addr_full ? address_maps_link($_lead_addr_full) : '—' ?></td>
          <td><?= !empty($lead['company_name']) ? h($lead['company_name']) : '<span class="text-slate-300">—</span>' ?></td>
          <td><?= $lead['job_total'] > 0 ? money($lead['job_total']) : '—' ?></td>
          <td>
            <span class="badge badge-<?= h($lead['status']) ?>"><?= h($statusLabels[$lead['status']] ?? ucfirst($lead['status'])) ?></span>
            <?php if (!empty($lead['deposit_received'])): ?><span class="badge badge-deposit">Deposit</span><?php endif; ?>
          </td>
          <td><span class="badge badge-payment-<?= h($lead['payment_status']) ?>"><?= h(ucfirst($lead['payment_status'])) ?></span></td>
          <td class="actions">
            <a href="<?= url('leads/' . $lead['job_id']) ?>" class="btn btn-sm">View</a>
            <button type="button" class="btn btn-sm btn-secondary copy-lead-btn"
              data-company="<?= h($lead['company_name'] ?? '') ?>"
              data-customer="<?= h($lead['customer_name'] ?? '') ?>"
              data-address="<?= h($_lead_addr_full) ?>"
              data-phone="<?= h($lead['customer_phone'] ?? '') ?>"
              data-schedule="<?= h($_sched_str) ?>"
              data-notes="<?= h($lead['description'] ?? '') ?>"
            >Copy</button>
            <?php if ($lead['status'] !== 'completed' && $lead['status'] !== 'canceled'): ?>
            <a href="<?= url('leads/' . $lead['job_id'] . '/complete') ?>" class="btn btn-sm btn-success">Close</a>
            <?php endif; ?>
            <?php if (!empty($__is_admin)): ?>
            <form method="POST" action="<?= url('leads/' . $lead['job_id'] . '/delete') ?>" style="display:inline" onsubmit="return confirm('Delete this lead? This cannot be undone.')">
              <?= \App\Core\CSRF::field() ?>
              <button type="submit" class="btn btn-sm btn-danger">Delete</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
