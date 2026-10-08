<?php
$_inp = 'w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition';
$_lbl = 'block text-sm font-medium text-slate-700 mb-1.5';
?>

<div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
  <h1 class="text-xl font-bold text-slate-900">New Customer</h1>
  <a href="<?= url('customers') ?>"
     class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">
    ← Back
  </a>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm max-w-2xl">
  <form method="POST" action="<?= url('customers') ?>">
    <?= \App\Core\CSRF::field() ?>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 px-5 pt-5">
      <div>
        <label class="<?= $_lbl ?>">Full Name *</label>
        <input type="text" name="full_name" value="<?= h($old['full_name'] ?? '') ?>" required class="<?= $_inp ?>">
        <?php if (!empty($errors['full_name'])): ?><p class="mt-1 text-xs text-red-600"><?= h($errors['full_name'][0]) ?></p><?php endif; ?>
      </div>
      <div>
        <label class="<?= $_lbl ?>">Phone *</label>
        <input type="tel" id="cust-phone" name="phone" value="<?= h($old['phone'] ?? '') ?>" required class="<?= $_inp ?>">
        <?php if (!empty($errors['phone'])): ?><p class="mt-1 text-xs text-red-600"><?= h($errors['phone'][0]) ?></p><?php endif; ?>
        <div id="dup-phone-notice"
             class="mt-2 px-3 py-2 rounded-lg bg-amber-50 border border-amber-200 text-xs text-amber-800"
             style="display:none;"></div>
      </div>
      <div>
        <label class="<?= $_lbl ?>">Email</label>
        <input type="email" id="cust-email" name="email" value="<?= h($old['email'] ?? '') ?>" class="<?= $_inp ?>">
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 px-5 pt-4">
      <div>
        <label class="<?= $_lbl ?>">Address Line 1 *</label>
        <input type="text" id="cust-address" name="address_line1" value="<?= h($old['address_line1'] ?? '') ?>"
               required data-address-autocomplete autocomplete="off" class="<?= $_inp ?>">
        <?php if (!empty($errors['address_line1'])): ?><p class="mt-1 text-xs text-red-600"><?= h($errors['address_line1'][0]) ?></p><?php endif; ?>
      </div>
      <div>
        <label class="<?= $_lbl ?>">Address Line 2</label>
        <input type="text" name="address_line2" value="<?= h($old['address_line2'] ?? '') ?>" class="<?= $_inp ?>">
      </div>
    </div>

    <div class="grid grid-cols-3 gap-3 px-5 pt-4">
      <div>
        <label class="<?= $_lbl ?>">City *</label>
        <input type="text" name="city" value="<?= h($old['city'] ?? '') ?>" required class="<?= $_inp ?>">
        <?php if (!empty($errors['city'])): ?><p class="mt-1 text-xs text-red-600"><?= h($errors['city'][0]) ?></p><?php endif; ?>
      </div>
      <div>
        <label class="<?= $_lbl ?>">State *</label>
        <input type="text" name="state" value="<?= h($old['state'] ?? '') ?>" maxlength="50" required class="<?= $_inp ?>">
        <?php if (!empty($errors['state'])): ?><p class="mt-1 text-xs text-red-600"><?= h($errors['state'][0]) ?></p><?php endif; ?>
      </div>
      <div>
        <label class="<?= $_lbl ?>">ZIP *</label>
        <input type="text" name="zip" value="<?= h($old['zip'] ?? '') ?>" required class="<?= $_inp ?>">
        <?php if (!empty($errors['zip'])): ?><p class="mt-1 text-xs text-red-600"><?= h($errors['zip'][0]) ?></p><?php endif; ?>
      </div>
    </div>

    <div class="px-5 pt-4">
      <label class="<?= $_lbl ?>">Notes</label>
      <textarea name="notes" rows="3" class="<?= $_inp ?> resize-y"><?= h($old['notes'] ?? '') ?></textarea>
    </div>

    <div class="flex items-center gap-3 px-5 py-5 mt-2 border-t border-slate-100">
      <button type="submit"
              class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150">
        Save Customer
      </button>
      <a href="<?= url('customers') ?>"
         class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">
        Cancel
      </a>
    </div>

  </form>
</div>

<script>
(function () {
  var phoneEl   = document.getElementById('cust-phone');
  var emailEl   = document.getElementById('cust-email');
  var addrEl    = document.getElementById('cust-address');
  var notice    = document.getElementById('dup-phone-notice');
  var _dupFound = false;

  if (!phoneEl || !notice) return;

  function checkDuplicate(phone, email, address) {
    var qs = [];
    if (phone)   qs.push('phone='   + encodeURIComponent(phone));
    if (email)   qs.push('email='   + encodeURIComponent(email));
    if (address) qs.push('address=' + encodeURIComponent(address));
    if (!qs.length) return;
    fetch('<?= url('customers/check-duplicate') ?>?' + qs.join('&'))
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.exists) {
          var c = data.customer;
          var typeStr = data.match_type ? ' (' + data.match_type + ')' : '';
          _dupFound = true;
          notice.innerHTML = '&#9888; Duplicate found' + typeStr + ': <a href="<?= url('customers/') ?>' + c.customer_id + '" style="font-weight:600;">' +
            c.full_name.replace(/</g, '&lt;') + '</a>' +
            ([c.phone, c.email, c.address_line1].filter(Boolean).join(' &middot; ')
              ? ' &mdash; ' + [c.phone, c.email, c.address_line1].filter(Boolean).join(' &middot; ').replace(/</g, '&lt;')
              : '');
          notice.style.display = '';
        } else {
          _dupFound = false;
          notice.style.display = 'none';
        }
      })
      .catch(function () {});
  }

  phoneEl.addEventListener('blur', function () {
    var digits = this.value.replace(/\D/g, '');
    if (digits.length < 10) { _dupFound = false; notice.style.display = 'none'; return; }
    checkDuplicate(this.value, '', '');
  });

  if (emailEl) {
    emailEl.addEventListener('blur', function () {
      var v = this.value.trim();
      if (v && v.indexOf('@') !== -1 && !_dupFound) {
        checkDuplicate('', v, '');
      }
    });
  }

  if (addrEl) {
    addrEl.addEventListener('blur', function () {
      var v = this.value.trim();
      if (v.length >= 5 && !_dupFound) {
        checkDuplicate('', '', v);
      }
    });
  }
}());
</script>
