<?php
$_inp = 'w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition';
$_sel = 'w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition';
$_lbl = 'block text-sm font-medium text-slate-700 mb-1.5';
?>

<div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
  <h1 class="text-xl font-bold text-slate-900">Edit Inventory Item</h1>
  <a href="<?= url('inventory') ?>"
     class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">
    &larr; Back
  </a>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm max-w-2xl">
  <form method="POST" action="<?= url('inventory/' . (int) $item['inventory_id']) ?>" enctype="multipart/form-data">
    <?= \App\Core\CSRF::field() ?>

    <div class="px-5 pt-5">
      <label class="<?= $_lbl ?>">Name *</label>
      <input type="text" name="name" value="<?= h($item['name']) ?>" required class="<?= $_inp ?>">
      <?php if (!empty($errors['name'])): ?><p class="mt-1 text-xs text-red-600"><?= h($errors['name'][0]) ?></p><?php endif; ?>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 px-5 pt-4">
      <div>
        <label class="<?= $_lbl ?>">FCC ID</label>
        <input type="text" name="fcc_id" value="<?= h($item['fcc_id'] ?? '') ?>" placeholder="e.g. M3N5WY8145" class="<?= $_inp ?>">
      </div>
      <div>
        <label class="<?= $_lbl ?>">Part Number</label>
        <input type="text" name="part_number" value="<?= h($item['part_number'] ?? '') ?>" placeholder="e.g. HYQ14FBA" class="<?= $_inp ?>">
      </div>
      <div>
        <label class="<?= $_lbl ?>">Frequency</label>
        <input type="text" name="frequency" value="<?= h($item['frequency'] ?? '') ?>" placeholder="e.g. 433MHz" class="<?= $_inp ?>">
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 px-5 pt-4">
      <div>
        <label class="<?= $_lbl ?>">IC</label>
        <input type="text" name="ic" value="<?= h($item['ic'] ?? '') ?>" placeholder="e.g. 7812A-14ACA" class="<?= $_inp ?>">
      </div>
      <div>
        <label class="<?= $_lbl ?>">Chip</label>
        <input type="text" name="chip" value="<?= h($item['chip'] ?? '') ?>" placeholder="e.g. 315MHz" class="<?= $_inp ?>">
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 px-5 pt-4">
      <div>
        <label class="<?= $_lbl ?>">Category</label>
        <select name="category_id" class="<?= $_sel ?>">
          <option value="">— Uncategorized —</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= (int) $cat['category_id'] ?>"
                    <?= (int) ($item['category_id'] ?? 0) === (int) $cat['category_id'] ? 'selected' : '' ?>>
              <?= h($cat['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="<?= $_lbl ?>">Manufacturer</label>
        <select name="manufacturer_id" class="<?= $_sel ?>">
          <option value="">— Select —</option>
          <?php foreach ($manufacturers as $mfr): ?>
            <option value="<?= (int) $mfr['manufacturer_id'] ?>"
                    <?= (int) ($item['manufacturer_id'] ?? 0) === (int) $mfr['manufacturer_id'] ? 'selected' : '' ?>>
              <?= h($mfr['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="<?= $_lbl ?>">Cost ($) *</label>
        <input type="number" name="cost" value="<?= h($item['cost']) ?>" step="0.0001" min="0" required class="<?= $_inp ?>">
        <?php if (!empty($errors['cost'])): ?><p class="mt-1 text-xs text-red-600"><?= h($errors['cost'][0]) ?></p><?php endif; ?>
      </div>
    </div>

    <!-- Product ID (read-only) -->
    <?php if (!empty($item['product_id'])): ?>
    <div class="mx-5 mt-4 px-4 py-3 bg-blue-50 rounded-lg border border-blue-200 flex items-center justify-between gap-4">
      <div>
        <p class="text-xs font-semibold text-blue-600 mb-0.5">Product ID</p>
        <p class="text-lg font-mono font-bold text-blue-800 tracking-widest"><?= h($item['product_id']) ?></p>
      </div>
      <p class="text-xs text-blue-500 text-right">Share this ID with technicians<br>for instant catalog lookup.</p>
    </div>
    <?php endif; ?>

    <!-- Current stock (read-only) -->
    <div class="mx-5 mt-4 px-4 py-3 bg-slate-50 rounded-lg border border-slate-200 flex items-center justify-between">
      <div>
        <p class="text-xs font-semibold text-slate-500 mb-0.5">Main Company Stock</p>
        <p class="text-sm font-bold text-slate-800"><?= number_format((float) ($item['quantity'] ?? 0), 0) ?> units</p>
      </div>
      <a href="<?= url('inventory') ?>"
         class="text-xs text-blue-600 hover:underline">Adjust via Transfer &rarr;</a>
    </div>

    <!-- Image -->
    <div class="px-5 pt-4">
      <label class="<?= $_lbl ?>">Product Image</label>
      <?php if (!empty($item['image_path'])): ?>
      <div class="mb-2">
        <img src="<?= url($item['image_path']) ?>" alt="Current image"
             class="h-24 w-auto rounded-lg border border-slate-200 object-cover">
        <p class="mt-1 text-xs text-slate-400">Upload a new image to replace the current one.</p>
      </div>
      <?php endif; ?>
      <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif"
             class="block w-full text-sm text-slate-700 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border file:border-slate-200 file:text-sm file:bg-slate-50 file:text-slate-700 hover:file:bg-slate-100 transition">
      <p class="mt-1 text-xs text-slate-400">JPG, PNG, WebP or GIF. Optional.</p>
    </div>

    <div class="flex items-center gap-3 px-5 py-5 mt-2 border-t border-slate-100">
      <button type="submit"
              class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150">
        Save Changes
      </button>
      <a href="<?= url('inventory') ?>"
         class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">
        Cancel
      </a>
    </div>
  </form>
</div>
