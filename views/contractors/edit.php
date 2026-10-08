<?php
$_inp = 'w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition';
$_sel = 'w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition';
$_lbl = 'block text-sm font-medium text-slate-700 mb-1.5';
?>

<div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
  <h1 class="text-xl font-bold text-slate-900">Edit Contractor</h1>
  <a href="<?= url('contractors') ?>"
     class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">
    ← Back
  </a>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm max-w-2xl">
  <form method="POST" action="<?= url('contractors/' . $contractor['contractor_id']) ?>">
    <?= \App\Core\CSRF::field() ?>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 px-5 pt-5">
      <div>
        <label class="<?= $_lbl ?>">Full Name *</label>
        <input type="text" name="full_name" value="<?= h($contractor['full_name']) ?>" required class="<?= $_inp ?>">
      </div>
      <div>
        <label class="<?= $_lbl ?>">Phone *</label>
        <input type="tel" name="phone" value="<?= h($contractor['phone']) ?>" required class="<?= $_inp ?>">
      </div>
      <div>
        <label class="<?= $_lbl ?>">Email</label>
        <input type="email" name="email" value="<?= h($contractor['email'] ?? '') ?>" class="<?= $_inp ?>">
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 px-5 pt-4">
      <div>
        <label class="<?= $_lbl ?>">Split Type *</label>
        <select name="split_type" class="<?= $_sel ?>">
          <option value="percent" <?= $contractor['split_type'] === 'percent' ? 'selected' : '' ?>>Percent (%)</option>
          <option value="flat"    <?= $contractor['split_type'] === 'flat'    ? 'selected' : '' ?>>Flat ($)</option>
        </select>
      </div>
      <div>
        <label class="<?= $_lbl ?>">Split Value * <span class="text-slate-400 font-normal">(decimal for %: 40% = 0.40)</span></label>
        <input type="number" name="split_value" value="<?= h($contractor['split_value']) ?>" step="0.0001" min="0" required class="<?= $_inp ?>">
      </div>
    </div>

    <div class="px-5 pt-4">
      <label class="flex items-center gap-2.5 cursor-pointer">
        <input type="checkbox" name="is_active" value="1" <?= $contractor['is_active'] ? 'checked' : '' ?>
               class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
        <span class="text-sm font-medium text-slate-700">Active</span>
      </label>
    </div>

    <div class="flex items-center gap-3 px-5 py-5 mt-2 border-t border-slate-100">
      <button type="submit"
              class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150">
        Update Contractor
      </button>
      <a href="<?= url('contractors') ?>"
         class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">
        Cancel
      </a>
    </div>
  </form>
</div>
