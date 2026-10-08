<div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
  <h1 class="text-xl font-bold text-slate-900">Customers</h1>
  <a href="<?= url('customers/create') ?>"
     class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150 no-underline">
    + New Customer
  </a>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm">
  <div class="px-5 py-4 border-b border-slate-100">
    <form method="GET" action="<?= url('customers') ?>" class="flex gap-3 items-end flex-wrap">
      <div class="flex flex-col gap-1 flex-1 min-w-[220px]">
        <label class="text-xs font-medium text-slate-500 uppercase tracking-wider">Search</label>
        <input type="text" name="search" value="<?= h($search) ?>" placeholder="Name, phone, city, zip…"
               class="px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
      </div>
      <div class="flex items-end gap-2">
        <button type="submit"
                class="inline-flex items-center px-4 py-2 rounded-lg bg-slate-700 hover:bg-slate-800 text-white text-sm font-semibold shadow-sm transition-colors duration-150">
          Search
        </button>
        <?php if ($search !== ''): ?>
          <a href="<?= url('customers') ?>"
             class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">
            Clear
          </a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <?php if (empty($customers)): ?>
    <div class="py-16 text-center text-sm text-slate-400">No customers found.</div>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="table w-full">
      <thead>
        <tr>
          <th>Name</th>
          <th>Phone</th>
          <th>City</th>
          <th>State</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($customers as $c): ?>
        <tr>
          <td><?= h($c['full_name']) ?></td>
          <td><a href="tel:<?= h(phone_digits($c['phone'])) ?>"><?= h(format_phone($c['phone'])) ?></a></td>
          <td><?= h($c['city']) ?></td>
          <td><?= h($c['state']) ?></td>
          <td class="actions">
            <a href="<?= url('customers/' . $c['customer_id']) ?>" class="btn btn-sm">View</a>
            <a href="<?= url('customers/' . $c['customer_id'] . '/edit') ?>" class="btn btn-sm btn-ghost">Edit</a>
            <?php if (!empty($__is_admin)): ?>
            <form method="POST" action="<?= url('customers/' . $c['customer_id'] . '/delete') ?>" style="display:inline"
                  onsubmit="return confirm('Delete this customer? This cannot be undone.')">
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
