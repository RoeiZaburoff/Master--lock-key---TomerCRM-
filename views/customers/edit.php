<?php
$_inp = 'w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition';
$_lbl = 'block text-sm font-medium text-slate-700 mb-1.5';
?>

<div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
  <h1 class="text-xl font-bold text-slate-900">Edit Customer</h1>
  <a href="<?= url('customers/' . $customer['customer_id']) ?>"
     class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">
    ← Back
  </a>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm max-w-2xl">
  <form method="POST" action="<?= url('customers/' . $customer['customer_id']) ?>">
    <?= \App\Core\CSRF::field() ?>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 px-5 pt-5">
      <div>
        <label class="<?= $_lbl ?>">Full Name *</label>
        <input type="text" name="full_name" value="<?= h($customer['full_name']) ?>" required class="<?= $_inp ?>">
        <?php if (!empty($errors['full_name'])): ?><p class="mt-1 text-xs text-red-600"><?= h($errors['full_name'][0]) ?></p><?php endif; ?>
      </div>
      <div>
        <label class="<?= $_lbl ?>">Phone *</label>
        <input type="tel" name="phone" value="<?= h($customer['phone']) ?>" required class="<?= $_inp ?>">
        <?php if (!empty($errors['phone'])): ?><p class="mt-1 text-xs text-red-600"><?= h($errors['phone'][0]) ?></p><?php endif; ?>
      </div>
      <div>
        <label class="<?= $_lbl ?>">Email</label>
        <input type="email" name="email" value="<?= h($customer['email'] ?? '') ?>" class="<?= $_inp ?>">
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 px-5 pt-4">
      <div>
        <label class="<?= $_lbl ?>">Address Line 1 *</label>
        <input type="text" name="address_line1" value="<?= h($customer['address_line1']) ?>"
               required data-address-autocomplete autocomplete="off" class="<?= $_inp ?>">
      </div>
      <div>
        <label class="<?= $_lbl ?>">Address Line 2</label>
        <input type="text" name="address_line2" value="<?= h($customer['address_line2'] ?? '') ?>" class="<?= $_inp ?>">
      </div>
    </div>

    <div class="grid grid-cols-3 gap-3 px-5 pt-4">
      <div>
        <label class="<?= $_lbl ?>">City *</label>
        <input type="text" name="city" value="<?= h($customer['city']) ?>" required class="<?= $_inp ?>">
      </div>
      <div>
        <label class="<?= $_lbl ?>">State *</label>
        <input type="text" name="state" value="<?= h($customer['state']) ?>" required class="<?= $_inp ?>">
      </div>
      <div>
        <label class="<?= $_lbl ?>">ZIP *</label>
        <input type="text" name="zip" value="<?= h($customer['zip']) ?>" required class="<?= $_inp ?>">
      </div>
    </div>

    <div class="px-5 pt-4">
      <label class="<?= $_lbl ?>">Notes</label>
      <textarea name="notes" rows="3" class="<?= $_inp ?> resize-y"><?= h($customer['notes'] ?? '') ?></textarea>
    </div>

    <div class="flex items-center gap-3 px-5 py-5 mt-2 border-t border-slate-100">
      <button type="submit"
              class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150">
        Update Customer
      </button>
      <a href="<?= url('customers/' . $customer['customer_id']) ?>"
         class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">
        Cancel
      </a>
    </div>

  </form>
</div>
