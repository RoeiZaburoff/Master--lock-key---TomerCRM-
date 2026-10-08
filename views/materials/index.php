<div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
  <h1 class="text-xl font-bold text-slate-900">Materials Catalog</h1>
  <a href="<?= url('materials/create') ?>"
     class="inline-flex items-center px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150 no-underline">
    + Add Material
  </a>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm">
  <?php if (empty($materials)): ?>
    <div class="py-16 text-center text-sm text-slate-400 px-6">No materials in catalog yet.</div>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="table w-full">
      <thead>
        <tr>
          <th>Name</th>
          <th>Unit</th>
          <th>Unit Cost</th>
          <th>Stock Qty</th>
          <th>Active</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($materials as $m): ?>
        <tr>
          <td class="font-medium"><?= h($m['name']) ?></td>
          <td><?= h($m['unit']) ?></td>
          <td><?= money($m['unit_cost']) ?></td>
          <td><?= h($m['stock_qty']) ?></td>
          <td><?= $m['is_active'] ? '<span class="badge badge-active">Active</span>' : '<span class="badge badge-inactive">Inactive</span>' ?></td>
          <td class="actions">
            <a href="<?= url('materials/' . $m['material_id'] . '/edit') ?>" class="btn btn-sm">Edit</a>
            <?php if (!empty($__is_admin)): ?>
            <form method="POST" action="<?= url('materials/' . $m['material_id'] . '/delete') ?>" style="display:inline"
                  onsubmit="return confirm('Delete this material? This cannot be undone.')">
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
