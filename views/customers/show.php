<?php
$_cust_addr_parts = array_filter([
    $customer['address_line1'] ?? '',
    $customer['address_line2'] ?? '',
    $customer['city'] ?? '',
    $customer['state'] ?? '',
    $customer['zip'] ?? '',
]);
$_cust_addr_full = implode(', ', $_cust_addr_parts);
$statusLabels = ['progress' => 'Progress', 'scheduled' => 'Scheduled', 'canceled' => 'Canceled', 'completed' => 'Completed'];
?>

<div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
  <h1 class="text-xl font-bold text-slate-900"><?= h($customer['full_name']) ?></h1>
  <div class="flex items-center gap-2">
    <a href="<?= url('customers/' . $customer['customer_id'] . '/edit') ?>"
       class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium shadow-sm transition-colors duration-150 no-underline">
      Edit
    </a>
    <a href="<?= url('leads/create?customer_id=' . $customer['customer_id']) ?>"
       class="inline-flex items-center px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150 no-underline">
      + New Lead
    </a>
  </div>
</div>

<!-- Contact card -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-5 max-w-lg">
  <div class="px-5 py-4 border-b border-slate-100">
    <h2 class="text-sm font-semibold text-slate-900">Contact Info</h2>
  </div>
  <div>
    <div class="flex justify-between items-baseline py-2.5 px-5 border-b border-slate-100 gap-4">
      <span class="text-sm text-slate-500 shrink-0">Phone</span>
      <strong class="text-sm font-medium text-slate-900 text-right">
        <a href="tel:<?= h(phone_digits($customer['phone'])) ?>"><?= h(format_phone($customer['phone'])) ?></a>
      </strong>
    </div>
    <div class="flex justify-between items-baseline py-2.5 px-5 border-b border-slate-100 gap-4">
      <span class="text-sm text-slate-500 shrink-0">Email</span>
      <strong class="text-sm font-medium text-slate-900 text-right"><?= h($customer['email'] ?: '—') ?></strong>
    </div>
    <div class="flex justify-between items-baseline py-2.5 px-5 <?= $customer['notes'] ? 'border-b border-slate-100' : '' ?> gap-4">
      <span class="text-sm text-slate-500 shrink-0">Address</span>
      <strong class="text-sm font-medium text-slate-900 text-right"><?= $_cust_addr_full ? address_maps_link($_cust_addr_full) : '—' ?></strong>
    </div>
    <?php if ($customer['notes']): ?>
    <div class="flex justify-between items-baseline py-2.5 px-5 gap-4">
      <span class="text-sm text-slate-500 shrink-0">Notes</span>
      <strong class="text-sm font-medium text-slate-900 text-right"><?= h($customer['notes']) ?></strong>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Lead history -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
  <div class="px-5 py-4 border-b border-slate-100">
    <h2 class="text-sm font-semibold text-slate-900">Lead History</h2>
  </div>
  <?php if (empty($customer['jobs'])): ?>
    <div class="py-12 text-center text-sm text-slate-400">No leads yet.</div>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="table w-full">
      <thead>
        <tr>
          <th>Date</th>
          <th>Company</th>
          <th>Total</th>
          <th>Status</th>
          <th>Payment</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($customer['jobs'] as $job): ?>
        <tr>
          <td><?= h($job['scheduled_date'] ?? $job['created_at']) ?></td>
          <td><?= !empty($job['company_name']) ? h($job['company_name']) : '—' ?></td>
          <td><?= $job['job_total'] > 0 ? money($job['job_total']) : '—' ?></td>
          <td><span class="badge badge-<?= h($job['status']) ?>"><?= h($statusLabels[$job['status']] ?? ucfirst(str_replace('_', ' ', $job['status']))) ?></span></td>
          <td><span class="badge badge-payment-<?= h($job['payment_status']) ?>"><?= h(ucfirst($job['payment_status'])) ?></span></td>
          <td><a href="<?= url('leads/' . $job['job_id']) ?>" class="btn btn-sm">View</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
