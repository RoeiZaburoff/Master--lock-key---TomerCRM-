<?php
$typeIcon  = ['warehouse' => '🏭', 'vehicle' => '🚐', 'tech' => '👷'];
$typeLabel = ['warehouse' => 'Warehouse', 'vehicle' => 'Vehicle', 'tech' => 'Technician'];
$isEdit    = !empty($editing);
$editId    = $isEdit ? (int) $editing['location_id'] : 0;
$_inp      = 'w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition';
$_sel      = 'w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition';
$_lbl      = 'block text-sm font-medium text-slate-700 mb-1.5';

$formType  = $isEdit ? ($editing['type'] ?? 'warehouse') : 'warehouse';
?>

<div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
  <h1 class="text-xl font-bold text-slate-900">Technicians / Vehicles</h1>
  <a href="<?= url('inventory') ?>"
     class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">
    ← Inventory
  </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

  <!-- Create / Edit form -->
  <div class="bg-white rounded-xl border <?= $isEdit ? 'border-blue-300' : 'border-slate-200' ?> shadow-sm">
    <div class="px-5 py-4 border-b <?= $isEdit ? 'border-blue-100 bg-blue-50/40' : 'border-slate-100' ?>">
      <h2 class="text-sm font-semibold text-slate-900"><?= $isEdit ? 'Edit Technician / Vehicle' : 'Add Technician / Vehicle' ?></h2>
    </div>

    <?php if ($isEdit): ?>
    <form method="POST" action="<?= url('inventory/locations/' . $editId . '/update') ?>">
      <?= \App\Core\CSRF::field() ?>
      <div class="px-5 pt-4 space-y-4">
        <div>
          <label class="<?= $_lbl ?>">Name *</label>
          <input type="text" name="name" value="<?= h($editing['name'] ?? '') ?>" required autofocus class="<?= $_inp ?>">
          <?php if (!empty($errors['name'])): ?><p class="mt-1 text-xs text-red-600"><?= h($errors['name'][0]) ?></p><?php endif; ?>
        </div>
        <div>
          <label class="<?= $_lbl ?>">Type *</label>
          <select name="type" id="edit-type-select" class="<?= $_sel ?>" onchange="toggleContractor('edit', this.value)">
            <option value="warehouse" <?= $formType === 'warehouse' ? 'selected' : '' ?>>🏭 Warehouse</option>
            <option value="vehicle"   <?= $formType === 'vehicle'   ? 'selected' : '' ?>>🚐 Vehicle</option>
            <option value="tech"      <?= $formType === 'tech'      ? 'selected' : '' ?>>👷 Technician</option>
          </select>
        </div>
        <div id="edit-contractor-wrap" style="<?= $formType !== 'tech' ? 'display:none' : '' ?>">
          <label class="<?= $_lbl ?>">Assigned Contractor</label>
          <select name="contractor_id" class="<?= $_sel ?>">
            <option value="">— None —</option>
            <?php foreach ($contractors as $c): ?>
              <option value="<?= (int) $c['contractor_id'] ?>"
                      <?= (int) ($editing['contractor_id'] ?? 0) === (int) $c['contractor_id'] ? 'selected' : '' ?>>
                <?= h($c['full_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="flex items-center gap-3 px-5 py-4">
        <button type="submit"
                class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150">
          Save Changes
        </button>
        <a href="<?= url('inventory/locations') ?>"
           class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">
          Cancel
        </a>
      </div>
    </form>

    <?php else: ?>
    <form method="POST" action="<?= url('inventory/locations') ?>">
      <?= \App\Core\CSRF::field() ?>
      <div class="px-5 pt-4 space-y-4">
        <div>
          <label class="<?= $_lbl ?>">Name *</label>
          <input type="text" name="name" value="" required class="<?= $_inp ?>" placeholder="e.g. John's Van, Truck #3, Maria Tech">
          <?php if (!empty($errors['name'])): ?><p class="mt-1 text-xs text-red-600"><?= h($errors['name'][0]) ?></p><?php endif; ?>
        </div>
        <div>
          <label class="<?= $_lbl ?>">Type *</label>
          <select name="type" id="create-type-select" class="<?= $_sel ?>" onchange="toggleContractor('create', this.value)">
            <option value="warehouse">🏭 Warehouse</option>
            <option value="vehicle">🚐 Vehicle</option>
            <option value="tech">👷 Technician</option>
          </select>
        </div>
        <div id="create-contractor-wrap" style="display:none">
          <label class="<?= $_lbl ?>">Assigned Contractor</label>
          <select name="contractor_id" class="<?= $_sel ?>">
            <option value="">— None —</option>
            <?php foreach ($contractors as $c): ?>
              <option value="<?= (int) $c['contractor_id'] ?>">
                <?= h($c['full_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="px-5 py-4">
        <button type="submit"
                class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150">
          Add Technician / Vehicle
        </button>
      </div>
    </form>
    <?php endif; ?>
  </div>

  <!-- Locations list -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
    <div class="px-5 py-4 border-b border-slate-100">
      <h2 class="text-sm font-semibold text-slate-900">All Technicians / Vehicles</h2>
    </div>
    <?php if (empty($locations)): ?>
      <div class="py-12 text-center text-sm text-slate-400">No technicians or vehicles configured.</div>
    <?php else: ?>
    <div class="overflow-x-auto">
      <table class="table w-full">
        <thead>
          <tr>
            <th>Name</th>
            <th>Type</th>
            <th class="text-center">Items Stocked</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($locations as $loc): ?>
          <?php
            $lid      = (int) $loc['location_id'];
            $isMain   = $lid === 1;
            $isActive = $isEdit && $lid === $editId;
            $icon     = $typeIcon[$loc['type']] ?? '';
            $label    = $typeLabel[$loc['type']] ?? $loc['type'];
            $stock    = (int) ($loc['stocked_items'] ?? 0);
          ?>
          <tr class="<?= $isActive ? 'bg-blue-50' : '' ?>">
            <td>
              <div class="flex items-center gap-2">
                <span class="font-medium text-slate-800"><?= h($loc['name']) ?></span>
                <?php if ($isMain): ?>
                  <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-700">Default</span>
                <?php endif; ?>
              </div>
              <?php if (!empty($loc['contractor_name'])): ?>
                <p class="text-xs text-slate-400 mt-0.5"><?= h($loc['contractor_name']) ?></p>
              <?php endif; ?>
            </td>
            <td>
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium
                <?= match($loc['type']) {
                  'warehouse' => 'bg-blue-100 text-blue-700',
                  'vehicle'   => 'bg-amber-100 text-amber-700',
                  'tech'      => 'bg-violet-100 text-violet-700',
                  default     => 'bg-slate-100 text-slate-600',
                } ?>">
                <?= $icon ?> <?= $label ?>
              </span>
            </td>
            <td class="text-center">
              <?php if ($stock > 0): ?>
                <span class="text-sm font-semibold text-slate-800"><?= $stock ?></span>
                <span class="text-xs text-slate-400 ml-0.5">items</span>
              <?php else: ?>
                <span class="text-xs text-slate-400">—</span>
              <?php endif; ?>
            </td>
            <td class="actions">
              <a href="<?= url('inventory/locations/' . $lid . '/edit') ?>"
                 class="btn btn-sm <?= $isActive ? 'bg-blue-600 text-white border-blue-600' : '' ?>">Edit</a>
              <?php if (!$isMain): ?>
                <form method="POST" action="<?= url('inventory/locations/' . $lid . '/delete') ?>" style="display:inline"
                      onsubmit="return confirm('Delete this location?<?= $stock > 0 ? ' Warning: it currently has stocked items.' : '' ?>')">
                  <?= \App\Core\CSRF::field() ?>
                  <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                </form>
              <?php else: ?>
                <button type="button" disabled
                        class="btn btn-sm opacity-40 cursor-not-allowed"
                        title="The default warehouse cannot be deleted.">Delete</button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

</div>

<script>
function toggleContractor(prefix, type) {
  var wrap = document.getElementById(prefix + '-contractor-wrap');
  if (wrap) wrap.style.display = (type === 'tech') ? '' : 'none';
}
</script>
