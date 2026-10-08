<div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
  <h1 class="text-xl font-bold text-slate-900">Invoices</h1>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm">
  <?php if (empty($invoices)): ?>
    <div class="py-16 text-center text-sm text-slate-400 px-6">
      No invoices yet. Complete a lead first, then create an invoice from the lead detail page.
    </div>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="table w-full">
      <thead>
        <tr>
          <th>Invoice #</th>
          <th>Customer</th>
          <th>Company</th>
          <th>Date</th>
          <th>Due</th>
          <th>Total</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($invoices as $inv): ?>
        <tr>
          <td class="font-medium"><?= h($inv['invoice_number']) ?></td>
          <td><?= h($inv['customer_name']) ?></td>
          <td><?= !empty($inv['company_name']) ? h($inv['company_name']) : '<span class="text-slate-300">—</span>' ?></td>
          <td><?= h($inv['issued_date']) ?></td>
          <td><?= h($inv['due_date'] ?? 'Upon Receipt') ?></td>
          <td class="font-semibold"><?= money($inv['total']) ?></td>
          <td class="actions">
            <a href="<?= url('invoices/' . $inv['invoice_id']) ?>" class="btn btn-sm">View</a>
            <a href="<?= url('invoices/' . $inv['invoice_id'] . '/pdf') ?>" class="btn btn-sm btn-secondary" target="_blank">PDF</a>
            <?php if (!empty($__is_admin)): ?>
            <form method="POST" action="<?= url('invoices/' . $inv['invoice_id'] . '/delete') ?>" style="display:inline"
                  onsubmit="return confirm('Delete this invoice? This cannot be undone.')">
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
