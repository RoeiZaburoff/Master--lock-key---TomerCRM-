<?php
$_inp = 'w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition';
$_lbl = 'block text-sm font-medium text-slate-700 mb-1.5';
?>

<div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
  <h1 class="text-xl font-bold text-slate-900">Edit Company</h1>
  <a href="<?= url('settings') ?>"
     class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">
    ← Back
  </a>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm max-w-2xl">
  <form method="POST" action="<?= url('settings/companies/' . $company['company_id'] . '/update') ?>" enctype="multipart/form-data">
    <?= \App\Core\CSRF::field() ?>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 px-5 pt-5">
      <div>
        <label class="<?= $_lbl ?>">Company Name *</label>
        <input type="text" name="name" value="<?= h($company['name'] ?? '') ?>" required class="<?= $_inp ?>">
        <?php if (!empty($errors['name'])): ?><p class="mt-1 text-xs text-red-600"><?= h($errors['name'][0]) ?></p><?php endif; ?>
      </div>
      <div>
        <label class="<?= $_lbl ?>">Phone</label>
        <input type="tel" name="phone" value="<?= h($company['phone'] ?? '') ?>" class="<?= $_inp ?>">
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 px-5 pt-4">
      <div>
        <label class="<?= $_lbl ?>">Email</label>
        <input type="email" name="email" value="<?= h($company['email'] ?? '') ?>" class="<?= $_inp ?>">
      </div>
      <div>
        <label class="<?= $_lbl ?>">Address</label>
        <input type="text" name="address" value="<?= h($company['address'] ?? '') ?>" data-address-autocomplete autocomplete="off" class="<?= $_inp ?>">
      </div>
    </div>

    <div class="px-5 pt-4">
      <label class="<?= $_lbl ?>">Logo</label>
      <?php if (!empty($company['logo_path'])): ?>
        <div class="flex items-center gap-3 mb-3 px-3.5 py-3 bg-slate-50 border border-slate-200 rounded-lg">
          <img src="<?= url('storage/logos/' . h($company['logo_path'])) ?>" alt="" style="height:44px;width:auto;object-fit:contain;">
          <label class="flex items-center gap-2 cursor-pointer text-sm text-red-600 font-medium">
            <input type="checkbox" name="delete_logo" value="1"
                   class="w-4 h-4 rounded border-slate-300 text-red-600 focus:ring-red-500">
            Delete current logo
          </label>
        </div>
      <?php endif; ?>
      <input type="file" name="logo" accept="image/png,image/jpeg,image/gif,image/webp"
             class="block text-sm text-slate-700">
      <p class="mt-1 text-xs text-slate-400">Upload a new file to replace the current logo.</p>
    </div>

    <div class="px-5 pt-4">
      <label class="flex items-center gap-2.5 cursor-pointer">
        <input type="checkbox" name="is_active" value="1" <?= !empty($company['is_active']) ? 'checked' : '' ?>
               class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
        <span class="text-sm font-medium text-slate-700">Active</span>
      </label>
    </div>

    <div class="flex items-center gap-3 px-5 py-5 mt-2 border-t border-slate-100">
      <button type="submit"
              class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150">
        Update Company
      </button>
      <a href="<?= url('settings') ?>"
         class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">
        Cancel
      </a>
    </div>
  </form>
</div>
