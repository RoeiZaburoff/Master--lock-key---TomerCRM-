<div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
  <h1 class="text-xl font-bold text-slate-900">Lead Sources</h1>
  <a href="<?= url('lead-sources/create') ?>"
     class="inline-flex items-center px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150 no-underline">
    + Add Lead Source
  </a>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm">
  <?php if (empty($sources)): ?>
    <div class="py-16 text-center text-sm text-slate-400 px-6">No lead sources defined yet.</div>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="table w-full">
      <thead>
        <tr>
          <th>Name</th>
          <th>Fee Type</th>
          <th>Fee Value</th>
          <th>Active</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($sources as $s): ?>
        <tr>
          <td class="font-medium"><?= h($s['name']) ?></td>
          <td><?= ucfirst(h($s['fee_type'])) ?></td>
          <td><?= $s['fee_type'] === 'percent' ? round((float)$s['fee_value'] * 100, 2) . '%' : money($s['fee_value']) ?></td>
          <td><?= $s['is_active'] ? '<span class="badge badge-active">Active</span>' : '<span class="badge badge-inactive">Inactive</span>' ?></td>
          <td class="actions">
            <a href="<?= url('lead-sources/' . $s['lead_source_id'] . '/edit') ?>" class="btn btn-sm">Edit</a>
            <?php if (!empty($__is_admin)): ?>
            <form method="POST" action="<?= url('lead-sources/' . $s['lead_source_id'] . '/delete') ?>" style="display:inline"
                  onsubmit="return confirm('Delete this lead source? This cannot be undone.')">
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
