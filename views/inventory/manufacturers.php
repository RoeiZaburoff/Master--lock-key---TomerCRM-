<?php
$_inp     = 'w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition';
$_lbl     = 'block text-sm font-medium text-slate-700 mb-1.5';
$isEdit   = !empty($editing);
$editId   = $isEdit ? (int) $editing['manufacturer_id'] : 0;
$editName = $isEdit ? ($editing['name'] ?? '') : '';
?>

<div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
  <h1 class="text-xl font-bold text-slate-900">Manufacturers</h1>
  <a href="<?= url('inventory') ?>"
     class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">
    ← Inventory
  </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

  <!-- Create / Edit form -->
  <div class="bg-white rounded-xl border <?= $isEdit ? 'border-blue-300' : 'border-slate-200' ?> shadow-sm">
    <div class="px-5 py-4 border-b <?= $isEdit ? 'border-blue-100 bg-blue-50/40' : 'border-slate-100' ?>">
      <h2 class="text-sm font-semibold text-slate-900"><?= $isEdit ? 'Edit Manufacturer' : 'Add Manufacturer' ?></h2>
    </div>

    <?php if ($isEdit): ?>
    <form method="POST" action="<?= url('inventory/manufacturers/' . $editId . '/update') ?>">
      <?= \App\Core\CSRF::field() ?>
      <div class="px-5 pt-4">
        <label class="<?= $_lbl ?>">Name *</label>
        <input type="text" name="name" value="<?= h($editName) ?>" required autofocus class="<?= $_inp ?>">
        <?php if (!empty($errors['name'])): ?><p class="mt-1 text-xs text-red-600"><?= h($errors['name'][0]) ?></p><?php endif; ?>
      </div>
      <div class="flex items-center gap-3 px-5 py-4">
        <button type="submit"
                class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150">
          Save Changes
        </button>
        <a href="<?= url('inventory/manufacturers') ?>"
           class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">
          Cancel
        </a>
      </div>
    </form>

    <?php else: ?>
    <form method="POST" action="<?= url('inventory/manufacturers') ?>">
      <?= \App\Core\CSRF::field() ?>
      <div class="px-5 pt-4">
        <label class="<?= $_lbl ?>">Name *</label>
        <input type="text" name="name" value="" required class="<?= $_inp ?>" placeholder="e.g. Ford, Chevrolet, Toyota">
        <?php if (!empty($errors['name'])): ?><p class="mt-1 text-xs text-red-600"><?= h($errors['name'][0]) ?></p><?php endif; ?>
      </div>
      <div class="px-5 py-4">
        <button type="submit"
                class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150">
          Add
        </button>
      </div>
    </form>
    <?php endif; ?>
  </div>

  <!-- Manufacturer list -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
    <div class="px-5 py-4 border-b border-slate-100">
      <h2 class="text-sm font-semibold text-slate-900">All Manufacturers</h2>
    </div>
    <?php if (empty($manufacturers)): ?>
      <div class="py-12 text-center text-sm text-slate-400">No manufacturers yet.</div>
    <?php else: ?>
    <div class="overflow-x-auto">
      <table class="table w-full">
        <thead>
          <tr>
            <th>Name</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($manufacturers as $mfr): ?>
          <?php $isActive = $isEdit && (int) $mfr['manufacturer_id'] === $editId; ?>
          <tr class="<?= $isActive ? 'bg-blue-50' : '' ?>">
            <td class="font-medium"><?= h($mfr['name']) ?></td>
            <td class="actions">
              <a href="<?= url('inventory/manufacturers/' . $mfr['manufacturer_id'] . '/edit') ?>"
                 class="btn btn-sm <?= $isActive ? 'bg-blue-600 text-white border-blue-600' : '' ?>">Edit</a>
              <form method="POST" action="<?= url('inventory/manufacturers/' . $mfr['manufacturer_id'] . '/delete') ?>" style="display:inline"
                    onsubmit="return confirm('Delete this manufacturer? Items using it will become unassigned.')">
                <?= \App\Core\CSRF::field() ?>
                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

</div>
