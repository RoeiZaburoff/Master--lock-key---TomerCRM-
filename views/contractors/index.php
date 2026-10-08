<div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
  <h1 class="text-xl font-bold text-slate-900">Contractors</h1>
  <a href="<?= url('contractors/create') ?>"
     class="inline-flex items-center px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150 no-underline">
    + Add Contractor
  </a>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm">
  <?php if (empty($contractors)): ?>
    <div class="py-16 text-center text-sm text-slate-400 px-6">No contractors defined yet.</div>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="table w-full">
      <thead>
        <tr>
          <th>Name</th>
          <th>Phone</th>
          <th>Split Type</th>
          <th>Split Value</th>
          <th>Active</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($contractors as $c): ?>
        <tr>
          <td class="font-medium"><?= h($c['full_name']) ?></td>
          <td><a href="tel:<?= h(phone_digits($c['phone'])) ?>" class="text-slate-700 no-underline hover:underline"><?= h(format_phone($c['phone'])) ?></a></td>
          <td><?= ucfirst(h($c['split_type'])) ?></td>
          <td><?= $c['split_type'] === 'percent' ? round((float)$c['split_value'] * 100, 2) . '%' : money($c['split_value']) ?></td>
          <td><?= $c['is_active'] ? '<span class="badge badge-active">Active</span>' : '<span class="badge badge-inactive">Inactive</span>' ?></td>
          <td class="actions">
            <a href="<?= url('contractors/' . $c['contractor_id'] . '/edit') ?>" class="btn btn-sm">Edit</a>
            <?php if (!empty($__is_admin)): ?>
            <form method="POST" action="<?= url('contractors/' . $c['contractor_id'] . '/delete') ?>" style="display:inline"
                  onsubmit="return confirm('Delete this contractor? This cannot be undone.')">
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
