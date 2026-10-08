<?php
$_inp = 'w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition';
$_lbl = 'block text-sm font-medium text-slate-700 mb-1.5';
?>

<div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
  <h1 class="text-xl font-bold text-slate-900">New Material</h1>
  <a href="<?= url('materials') ?>"
     class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">
    ← Back
  </a>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm max-w-2xl">
  <form method="POST" action="<?= url('materials') ?>">
    <?= \App\Core\CSRF::field() ?>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 px-5 pt-5">
      <div class="sm:col-span-2">
        <label class="<?= $_lbl ?>">Name *</label>
        <input type="text" name="name" value="<?= h($old['name'] ?? '') ?>" required class="<?= $_inp ?>">
        <?php if (!empty($errors['name'])): ?><p class="mt-1 text-xs text-red-600"><?= h($errors['name'][0]) ?></p><?php endif; ?>
      </div>
      <div>
        <label class="<?= $_lbl ?>">Unit</label>
        <input type="text" name="unit" value="<?= h($old['unit'] ?? 'each') ?>" placeholder="each, ft, lb…" class="<?= $_inp ?>">
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 px-5 pt-4">
      <div>
        <label class="<?= $_lbl ?>">Unit Cost ($) *</label>
        <input type="number" name="unit_cost" value="<?= h($old['unit_cost'] ?? '0') ?>" step="0.0001" min="0" required class="<?= $_inp ?>">
        <?php if (!empty($errors['unit_cost'])): ?><p class="mt-1 text-xs text-red-600"><?= h($errors['unit_cost'][0]) ?></p><?php endif; ?>
      </div>
      <div>
        <label class="<?= $_lbl ?>">Initial Stock Qty</label>
        <input type="number" name="stock_qty" value="<?= h($old['stock_qty'] ?? '0') ?>" step="0.01" min="0" class="<?= $_inp ?>">
      </div>
    </div>

    <div class="px-5 pt-4">
      <label class="flex items-center gap-2.5 cursor-pointer">
        <input type="checkbox" name="is_active" value="1" checked
               class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
        <span class="text-sm font-medium text-slate-700">Active</span>
      </label>
    </div>

    <div class="flex items-center gap-3 px-5 py-5 mt-2 border-t border-slate-100">
      <button type="submit"
              class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150">
        Save Material
      </button>
      <a href="<?= url('materials') ?>"
         class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">
        Cancel
      </a>
    </div>
  </form>
</div>
