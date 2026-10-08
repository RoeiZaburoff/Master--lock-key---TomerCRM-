<?php
$_inp = 'w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition';
$_sel = 'w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition';
$_lbl = 'block text-sm font-medium text-slate-700 mb-1.5';
?>

<!-- Page header -->
<div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
  <h1 class="text-xl font-bold text-slate-900">New Lead</h1>
  <a href="<?= url('leads') ?>"
     class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">
    ← Back
  </a>
</div>

<!-- Smart paste card -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm mb-4">
  <div class="px-5 py-4">
    <label class="block text-sm font-semibold text-slate-700 mb-2">Smart Paste — Paste Raw Lead Data</label>
    <textarea id="smart-paste-input" rows="3"
              placeholder="Paste lead info here (Name, Phone, Address, Job/Description)…"
              class="w-full px-3 py-2 rounded-lg border-2 border-dashed border-slate-200 text-sm text-slate-800 bg-slate-50 focus:outline-none focus:border-blue-400 resize-y transition placeholder:text-slate-400"></textarea>
  </div>
</div>

<!-- Main form card -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm">
  <form id="lead-form" method="POST" action="<?= url('leads') ?>">
    <?= \App\Core\CSRF::field() ?>

    <!-- Customer + Company -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 px-5 pt-5">

      <div class="lg:col-span-1">
        <label class="<?= $_lbl ?>">Customer *</label>
        <div class="flex gap-2 items-start flex-wrap">
          <div class="flex-1 min-w-0 flex flex-col gap-1.5">
            <input type="text" id="customer-search" placeholder="Search customers…" autocomplete="off"
                   class="<?= $_inp ?> mb-1.5">
            <select name="customer_id" id="customer-select" required class="<?= $_sel ?>">
              <option value="">— Select Customer —</option>
              <?php foreach ($customers as $c): ?>
                <option value="<?= (int) $c['customer_id'] ?>" <?= (($old['customer_id'] ?? '') == $c['customer_id']) ? 'selected' : '' ?>><?= h($c['full_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="button" id="open-customer-modal"
                  class="inline-flex items-center px-3 py-2 rounded-lg bg-slate-600 hover:bg-slate-700 text-white text-sm font-medium shadow-sm transition-colors duration-150 whitespace-nowrap mt-[38px]">
            + New
          </button>
        </div>
        <?php if (!empty($errors['customer_id'])): ?><p class="mt-1 text-xs text-red-600"><?= h($errors['customer_id'][0]) ?></p><?php endif; ?>
      </div>

      <div>
        <label class="<?= $_lbl ?>">Company *</label>
        <select name="company_id" required class="<?= $_sel ?>">
          <option value="" selected disabled>— Select Company —</option>
          <?php foreach ($companies as $co): ?>
            <option value="<?= (int) $co['company_id'] ?>" <?= (($old['company_id'] ?? '') == $co['company_id']) ? 'selected' : '' ?>><?= h($co['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['company_id'])): ?><p class="mt-1 text-xs text-red-600"><?= h($errors['company_id'][0]) ?></p><?php endif; ?>
        <?php if (empty($companies)): ?>
          <p class="mt-1 text-xs text-red-600">No companies configured. <a href="<?= url('settings/companies/create') ?>">Add one in Settings.</a></p>
        <?php endif; ?>
      </div>

    </div>

    <!-- Service address -->
    <div class="px-5 pt-5">
      <label class="<?= $_lbl ?>">Service Address</label>
      <input type="text" name="address" id="lead-address" data-address-autocomplete data-parser="address"
             value="<?= h($old['address'] ?? '') ?>"
             autocomplete="off" placeholder="Street address…"
             class="<?= $_inp ?>">
    </div>
    <div class="grid grid-cols-3 gap-3 px-5 pt-3">
      <div>
        <label class="<?= $_lbl ?>">City</label>
        <input type="text" name="city" id="lead-city" data-parser="city" value="<?= h($old['city'] ?? '') ?>" class="<?= $_inp ?>">
      </div>
      <div>
        <label class="<?= $_lbl ?>">State</label>
        <input type="text" name="state" id="lead-state" data-parser="state" value="<?= h($old['state'] ?? '') ?>" class="<?= $_inp ?>">
      </div>
      <div>
        <label class="<?= $_lbl ?>">ZIP</label>
        <input type="text" name="zip" id="lead-zip" data-parser="zip" value="<?= h($old['zip'] ?? '') ?>" class="<?= $_inp ?>">
      </div>
    </div>

    <!-- Status + scheduling + deposit -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 px-5 pt-5">

      <div>
        <label class="<?= $_lbl ?>">Status</label>
        <select name="status" id="status-select" class="<?= $_sel ?>">
          <?php foreach (['progress' => 'Progress', 'estimate' => 'Estimate', 'scheduled' => 'Scheduled', 'canceled' => 'Canceled'] as $val => $label): ?>
            <option value="<?= $val ?>" <?= (($old['status'] ?? 'progress') === $val) ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div id="scheduled-date-group" style="display:none;">
        <label class="<?= $_lbl ?>">Scheduled Date *</label>
        <input type="date" name="scheduled_date" id="scheduled-date" value="<?= h($old['scheduled_date'] ?? '') ?>" class="<?= $_inp ?>">
        <?php if (!empty($errors['scheduled_date'])): ?><p class="mt-1 text-xs text-red-600"><?= h($errors['scheduled_date'][0]) ?></p><?php endif; ?>
      </div>

      <div id="scheduled-time-group" style="display:none;">
        <label class="<?= $_lbl ?>">Time</label>
        <div class="grid grid-cols-2 gap-2">
          <select name="scheduled_hour" id="scheduled-hour" class="w-full px-2 sm:px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
            <?php for ($h = 1; $h <= 12; $h++): ?>
              <option value="<?= $h ?>" <?= (int)($old['scheduled_hour'] ?? 12) === $h ? 'selected' : '' ?>><?= $h ?>:00</option>
            <?php endfor; ?>
          </select>
          <select name="scheduled_ampm" id="scheduled-ampm" class="w-full px-2 sm:px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
            <option value="AM" <?= ($old['scheduled_ampm'] ?? 'PM') === 'AM' ? 'selected' : '' ?>>AM</option>
            <option value="PM" <?= ($old['scheduled_ampm'] ?? 'PM') === 'PM' ? 'selected' : '' ?>>PM</option>
          </select>
        </div>
      </div>

      <div id="scheduled-duration-group" style="display:none;">
        <label class="<?= $_lbl ?>">Time Window</label>
        <select name="duration_hours" id="scheduled-duration" class="<?= $_sel ?>">
          <?php foreach ([1 => '1 Hour', 2 => '2 Hours', 3 => '3 Hours', 4 => '4 Hours'] as $val => $label): ?>
            <option value="<?= $val ?>" <?= (int)($old['duration_hours'] ?? 1) === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="flex items-end pb-1">
        <label class="flex items-center gap-2 cursor-pointer text-sm font-medium text-slate-700">
          <input type="checkbox" name="deposit_received" id="deposit-received-check" value="1"
                 <?= !empty($old['deposit_received']) ? 'checked' : '' ?>
                 class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
          Deposit Received
        </label>
      </div>

      <div id="deposit-amount-group" style="display:none;">
        <label class="<?= $_lbl ?>">Deposit Amount ($)</label>
        <input type="number" name="deposit_amount" id="deposit-amount" value="<?= h($old['deposit_amount'] ?? '') ?>"
               step="0.01" min="0" placeholder="0.00" class="<?= $_inp ?>">
        <?php if (!empty($errors['deposit_amount'])): ?><p class="mt-1 text-xs text-red-600"><?= h($errors['deposit_amount'][0]) ?></p><?php endif; ?>
      </div>

    </div>

    <!-- Lead Source + Contractor -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 px-5 pt-5">
      <div>
        <label class="<?= $_lbl ?>">Lead Source</label>
        <select name="lead_source_id" class="<?= $_sel ?>">
          <option value="">— None / Direct —</option>
          <?php foreach ($lead_sources as $ls): ?>
            <option value="<?= (int) $ls['lead_source_id'] ?>" <?= (($old['lead_source_id'] ?? '') == $ls['lead_source_id']) ? 'selected' : '' ?>><?= h($ls['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="<?= $_lbl ?>">Contractor</label>
        <select name="contractor_id" class="<?= $_sel ?>">
          <option value="">— Owner Operated —</option>
          <?php foreach ($contractors as $co): ?>
            <option value="<?= (int) $co['contractor_id'] ?>" <?= (($old['contractor_id'] ?? '') == $co['contractor_id']) ? 'selected' : '' ?>><?= h($co['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <!-- Description -->
    <div class="px-5 pt-5">
      <label class="<?= $_lbl ?>">Description</label>
      <textarea id="description" name="description" rows="3"
                class="<?= $_inp ?> resize-y"><?= h($old['description'] ?? '') ?></textarea>
    </div>

    <!-- Estimate fields (shown only when status = estimate) -->
    <div id="estimate-fields-group" class="px-5 pt-5 border-t border-amber-100 bg-amber-50/50" style="display:none;">
      <p class="text-xs font-semibold text-amber-700 uppercase tracking-wider mb-4">Estimate Information</p>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="<?= $_lbl ?>">Estimate Amount ($)</label>
          <input type="number" name="estimate_amount" id="estimate-amount"
                 value="<?= h($old['estimate_amount'] ?? '') ?>"
                 step="0.01" min="0" placeholder="0.00" class="<?= $_inp ?>">
        </div>
        <div>
          <label class="<?= $_lbl ?>">Follow-up Date</label>
          <input type="date" name="follow_up_date" id="follow-up-date"
                 value="<?= h($old['follow_up_date'] ?? '') ?>" class="<?= $_inp ?>">
        </div>
        <div class="sm:col-span-2">
          <label class="<?= $_lbl ?>">Dimensions</label>
          <input type="text" name="estimate_dimensions" id="estimate-dimensions"
                 value="<?= h($old['estimate_dimensions'] ?? '') ?>"
                 placeholder="e.g. 12x24 ft" class="<?= $_inp ?>">
        </div>
        <div class="sm:col-span-2">
          <label class="<?= $_lbl ?>">Estimate Notes</label>
          <textarea name="estimate_notes" id="estimate-notes" rows="2"
                    class="<?= $_inp ?> resize-y"><?= h($old['estimate_notes'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center gap-3 px-5 py-5 mt-2 border-t border-slate-100">
      <button type="submit"
              class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150">
        Create Lead
      </button>
      <a href="<?= url('leads') ?>"
         class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">
        Cancel
      </a>
    </div>

  </form>
</div>

<!-- New Customer Modal -->
<div id="customer-modal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" style="display:none;">
  <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">

    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
      <h3 class="text-sm font-semibold text-slate-900">New Customer</h3>
      <button type="button" id="close-customer-modal"
              class="text-slate-400 hover:text-slate-600 text-lg leading-none px-1 transition-colors">✕</button>
    </div>

    <div class="px-5 pt-4">
      <div id="modal-error" class="mb-4 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm" style="display:none;"></div>

      <div id="dup-alert" class="mb-4 px-4 py-3 rounded-lg bg-amber-50 border border-amber-200 text-sm" style="display:none;">
        <p class="font-semibold text-amber-800">⚠ Customer found: <span id="dup-name"></span></p>
        <p id="dup-phone-email" class="text-amber-700 mt-0.5"></p>
        <button type="button" id="dup-load-btn"
                class="mt-2 inline-flex items-center px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold transition-colors duration-150">
          Load Existing Customer
        </button>
      </div>
    </div>

    <form id="quick-customer-form">
      <?= \App\Core\CSRF::field() ?>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 px-5 pb-2">
        <div class="sm:col-span-1">
          <label class="<?= $_lbl ?>">Full Name *</label>
          <input type="text" id="smart-full-name" name="full_name" required class="<?= $_inp ?>">
        </div>
        <div class="sm:col-span-1">
          <label class="<?= $_lbl ?>">Phone *</label>
          <input type="tel" id="smart-phone" name="phone" required class="<?= $_inp ?>">
        </div>
        <div class="sm:col-span-2">
          <label class="<?= $_lbl ?>">Email</label>
          <input type="email" id="smart-email" name="email" class="<?= $_inp ?>">
        </div>
        <div class="sm:col-span-2">
          <label class="<?= $_lbl ?>">Billing Address *</label>
          <input type="text" id="modal-address" name="address_line1" required data-address-autocomplete autocomplete="off" class="<?= $_inp ?>">
        </div>
        <div>
          <label class="<?= $_lbl ?>">City *</label>
          <input type="text" id="modal-city" name="city" required class="<?= $_inp ?>">
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="<?= $_lbl ?>">State *</label>
            <input type="text" id="modal-state" name="state" required class="<?= $_inp ?>">
          </div>
          <div>
            <label class="<?= $_lbl ?>">ZIP *</label>
            <input type="text" id="modal-zip" name="zip" required class="<?= $_inp ?>">
          </div>
        </div>
      </div>

      <div class="flex items-center gap-3 px-5 py-4 border-t border-slate-100">
        <button type="submit"
                class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150">
          Save Customer
        </button>
        <button type="button" id="close-customer-modal-btn"
                class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150">
          Cancel
        </button>
      </div>
    </form>

  </div>
</div>

<script>
(function () {
  /* ── Element references ─────────────────────────────────── */
  var smartPaste    = document.getElementById('smart-paste-input');
  var modalNameEl   = document.getElementById('smart-full-name');
  var modalPhoneEl  = document.getElementById('smart-phone');
  var modalEmailEl  = document.getElementById('smart-email');
  var modalAddrEl   = document.getElementById('modal-address');
  var modalCityEl   = document.getElementById('modal-city');
  var modalStateEl  = document.getElementById('modal-state');
  var modalZipEl    = document.getElementById('modal-zip');
  var leadAddrEl    = document.getElementById('lead-address');
  var leadCityEl    = document.getElementById('lead-city');
  var leadStateEl   = document.getElementById('lead-state');
  var leadZipEl     = document.getElementById('lead-zip');
  var descEl        = document.getElementById('description');
  var customerModal = document.getElementById('customer-modal');
  var sel           = document.getElementById('customer-select');
  var dupAlert      = document.getElementById('dup-alert');
  var dupName       = document.getElementById('dup-name');
  var dupPhoneEmail = document.getElementById('dup-phone-email');
  var dupLoadBtn    = document.getElementById('dup-load-btn');

  /* ── Customer address data (for auto-fill on select change) */
  var customerData = <?= json_encode(
    array_combine(
      array_column($customers, 'customer_id'),
      array_map(function($c) {
        return [
          'customer_id'   => (int) $c['customer_id'],
          'address_line1' => $c['address_line1'] ?? '',
          'city'          => $c['city']          ?? '',
          'state'         => $c['state']         ?? '',
          'zip'           => $c['zip']           ?? '',
        ];
      }, $customers)
    )
  ) ?>;

  /* ── Helpers ─────────────────────────────────────────────── */
  function flashField(el) {
    if (!el) return;
    el.style.transition = 'background .15s';
    el.style.background = '#d1fae5';
    setTimeout(function () { el.style.background = ''; }, 1200);
  }

  function setVal(el, val) {
    if (!el || !val) return;
    el.value = val;
    flashField(el);
  }

  function fillLeadAddrFromObj(obj) {
    setVal(leadAddrEl,  obj.address_line1 || obj.address || '');
    setVal(leadCityEl,  obj.city  || '');
    setVal(leadStateEl, obj.state || '');
    setVal(leadZipEl,   obj.zip   || '');
  }

  /* ── Smart Paste ─────────────────────────────────────────── */
  function fillLeadForm(d) {
    setVal(modalNameEl,  d.name);
    setVal(modalPhoneEl, d.phone);
    setVal(modalEmailEl, d.email);
    /* Billing address → modal fields */
    setVal(modalAddrEl,  d.address);
    setVal(modalCityEl,  d.city);
    setVal(modalStateEl, d.state);
    setVal(modalZipEl,   d.zip);
    /* Service address → lead form fields (data-parser targets) */
    setVal(leadAddrEl,  d.address);
    setVal(leadCityEl,  d.city);
    setVal(leadStateEl, d.state);
    setVal(leadZipEl,   d.zip);

    if (descEl && d.notes) {
      setVal(descEl, '\n--- Smart Paste Data ---\n' + d.notes);
    }
    if (d.name || d.phone) {
      customerModal.style.display = 'flex';
    }
    if (d.phone || d.email || d.address) {
      checkDuplicate(d.phone || '', d.email || '', d.address || '');
    }
  }

  var debounceTimer;
  function handlePaste() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(function () {
      var raw = smartPaste.value;
      if (!raw.trim()) return;
      fillLeadForm(LeadParser.parse(raw));
      smartPaste.style.borderColor = '#10b981';
      setTimeout(function () { smartPaste.style.borderColor = ''; }, 2000);
    }, 300);
  }
  smartPaste.addEventListener('paste', handlePaste);
  smartPaste.addEventListener('input', handlePaste);

  /* ── Duplicate detection ─────────────────────────────────── */
  var _dupCustomer = null;

  function showDupAlert(c, matchType) {
    _dupCustomer = c;
    dupName.textContent = c.full_name + ' [' + (matchType || 'Duplicate') + ']';
    dupPhoneEmail.textContent = [c.phone, c.email, c.address_line1].filter(Boolean).join(' · ');
    dupAlert.style.display = '';
  }
  function hideDupAlert() {
    _dupCustomer          = null;
    dupAlert.style.display = 'none';
  }

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
          showDupAlert(data.customer, data.match_type);
        } else {
          hideDupAlert();
        }
      })
      .catch(function () {});
  }

  modalPhoneEl.addEventListener('blur', function () {
    var digits = this.value.replace(/\D/g, '');
    if (digits.length >= 10) {
      checkDuplicate(this.value, '', '');
    } else {
      hideDupAlert();
    }
  });

  modalEmailEl.addEventListener('blur', function () {
    var v = this.value.trim();
    if (v && v.indexOf('@') !== -1 && !_dupCustomer) {
      checkDuplicate('', v, '');
    }
  });

  if (modalAddrEl) {
    modalAddrEl.addEventListener('blur', function () {
      var v = this.value.trim();
      if (v.length >= 5 && !_dupCustomer) {
        checkDuplicate('', '', v);
      }
    });
  }

  if (leadAddrEl) {
    leadAddrEl.addEventListener('blur', function () {
      var v = this.value.trim();
      if (v.length >= 5 && !_dupCustomer) {
        checkDuplicate('', '', v);
      }
    });
  }

  dupLoadBtn.addEventListener('click', function () {
    if (!_dupCustomer) return;
    var c = _dupCustomer;
    /* Select or add customer in dropdown */
    var existing = sel.querySelector('option[value="' + c.customer_id + '"]');
    if (!existing) {
      var opt = document.createElement('option');
      opt.value = c.customer_id;
      opt.text  = c.full_name;
      sel.appendChild(opt);
      allOpts.push(opt.cloneNode(true));
    }
    sel.value = c.customer_id;
    /* Auto-fill lead service address */
    fillLeadAddrFromObj(c);
    /* Close modal & clear dup state */
    customerModal.style.display = 'none';
    hideDupAlert();
  });

  /* ── Customer select → auto-fill lead address ────────────── */
  sel.addEventListener('change', function () {
    var cid = this.value;
    if (cid && customerData[cid]) {
      fillLeadAddrFromObj(customerData[cid]);
    }
  });

  /* ── Modal AJAX submit ───────────────────────────────────── */
  var statusSel    = document.getElementById('status-select');
  var dateGroup    = document.getElementById('scheduled-date-group');
  var timeGroup    = document.getElementById('scheduled-time-group');
  var durGroup     = document.getElementById('scheduled-duration-group');
  var dateInput    = document.getElementById('scheduled-date');
  var depositCheck = document.getElementById('deposit-received-check');
  var depositGroup = document.getElementById('deposit-amount-group');
  var depositInput = document.getElementById('deposit-amount');

  var estimateGroup  = document.getElementById('estimate-fields-group');
  var companySelect  = document.querySelector('select[name="company_id"]');

  function toggleScheduled() {
    var isScheduled = statusSel.value === 'scheduled';
    dateGroup.style.display = isScheduled ? '' : 'none';
    timeGroup.style.display = isScheduled ? '' : 'none';
    durGroup.style.display  = isScheduled ? '' : 'none';
    dateInput.required = isScheduled;
  }
  function toggleEstimate() {
    var isEstimate = statusSel.value === 'estimate';
    estimateGroup.style.display = isEstimate ? '' : 'none';
    if (companySelect) companySelect.required = !isEstimate;
  }
  function toggleDeposit() {
    var hasDeposit = depositCheck.checked;
    depositGroup.style.display = hasDeposit ? '' : 'none';
    depositInput.required = hasDeposit;
    if (!hasDeposit) depositInput.value = '';
  }
  statusSel.addEventListener('change', function () { toggleScheduled(); toggleEstimate(); });
  depositCheck.addEventListener('change', toggleDeposit);
  toggleScheduled();
  toggleEstimate();
  toggleDeposit();

  /* ── Customer search filter ──────────────────────────────── */
  var search  = document.getElementById('customer-search');
  var allOpts = Array.from(sel.options);
  search.addEventListener('input', function () {
    var q = this.value.toLowerCase();
    Array.from(sel.options).forEach(function (opt) { opt.remove(); });
    allOpts.forEach(function (opt) {
      if (opt.value === '' || opt.text.toLowerCase().indexOf(q) !== -1) {
        sel.appendChild(opt.cloneNode(true));
      }
    });
  });

  /* ── Modal open / close ──────────────────────────────────── */
  var modal      = document.getElementById('customer-modal');
  var openBtn    = document.getElementById('open-customer-modal');
  var closeBtn   = document.getElementById('close-customer-modal');
  var closeBtnB  = document.getElementById('close-customer-modal-btn');
  var modalForm  = document.getElementById('quick-customer-form');
  var modalError = document.getElementById('modal-error');

  openBtn.addEventListener('click',   function () { modal.style.display = 'flex'; });
  closeBtn.addEventListener('click',  function () { modal.style.display = 'none'; hideDupAlert(); });
  closeBtnB.addEventListener('click', function () { modal.style.display = 'none'; hideDupAlert(); });
  modal.addEventListener('click', function (e) {
    if (e.target === modal) { modal.style.display = 'none'; hideDupAlert(); }
  });

  modalForm.addEventListener('submit', function (e) {
    e.preventDefault();
    modalError.style.display = 'none';
    var fd = new FormData(modalForm);
    fetch('<?= url('customers/quick') ?>', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success) {
          var opt = document.createElement('option');
          opt.value    = data.id;
          opt.text     = data.name;
          opt.selected = true;
          sel.appendChild(opt);
          allOpts.push(opt.cloneNode(true));
          /* Copy new customer's billing address to lead service address fields */
          if (data.address_line1) setVal(leadAddrEl,  data.address_line1);
          if (data.city)          setVal(leadCityEl,  data.city);
          if (data.state)         setVal(leadStateEl, data.state);
          if (data.zip)           setVal(leadZipEl,   data.zip);
          modal.style.display = 'none';
          hideDupAlert();
          modalForm.reset();
        } else {
          modalError.textContent   = data.message || 'Could not save customer.';
          modalError.style.display = '';
        }
      })
      .catch(function () {
        modalError.textContent   = 'Request failed.';
        modalError.style.display = '';
      });
  });
}());
</script>
