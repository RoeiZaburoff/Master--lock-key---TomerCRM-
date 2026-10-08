<?php
$_inp = 'w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition';
$_sel = 'w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition';
$_lbl = 'block text-sm font-medium text-slate-700 mb-1.5';
?>

<div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
  <h1 class="text-xl font-bold text-slate-900">New Inventory Item</h1>
  <a href="<?= url('inventory') ?>"
     class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">
    &larr; Back
  </a>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm max-w-2xl mb-4">
  <div class="px-5 pt-5 pb-4">
    <div class="flex items-center gap-2 mb-3">
      <svg class="w-4 h-4 text-violet-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
      </svg>
      <span class="text-sm font-semibold text-slate-700">Smart Paste</span>
      <span class="text-xs text-slate-400 font-normal">— paste supplier text to auto-fill the form</span>
    </div>
    <textarea id="smart-paste-input" rows="6"
              placeholder="Paste raw supplier text here&#10;&#10;Example:&#10;2008-2015 Honda Accord Remote Head Key&#10;$9.99&#10;&#10;SKU: 13&#10;Fcc: KR55WK49308&#10;Manufacturer: Honda&#10;Part Number: 35118-TA0-A00&#10;Frequency: 314 Mhz&#10;Chip: PHILIPS 46"
              class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition font-mono resize-y placeholder-slate-300"></textarea>
    <div class="flex items-center gap-3 mt-2">
      <button type="button" id="smart-paste-btn"
              class="inline-flex items-center px-3 py-1.5 rounded-lg bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150">
        <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
        </svg>
        Auto-Fill Form
      </button>
      <span id="smart-paste-status" class="text-xs text-slate-400 hidden"></span>
    </div>
  </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm max-w-2xl">
  <form method="POST" action="<?= url('inventory') ?>" enctype="multipart/form-data">
    <?= \App\Core\CSRF::field() ?>

    <div class="px-5 pt-5">
      <label class="<?= $_lbl ?>">Name *</label>
      <input type="text" name="name" value="<?= h($old['name'] ?? '') ?>" required class="<?= $_inp ?>">
      <?php if (!empty($errors['name'])): ?><p class="mt-1 text-xs text-red-600"><?= h($errors['name'][0]) ?></p><?php endif; ?>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 px-5 pt-4">
      <div>
        <label class="<?= $_lbl ?>">FCC ID</label>
        <input type="text" name="fcc_id" value="<?= h($old['fcc_id'] ?? '') ?>" placeholder="e.g. M3N5WY8145" class="<?= $_inp ?>">
      </div>
      <div>
        <label class="<?= $_lbl ?>">Part Number</label>
        <input type="text" name="part_number" value="<?= h($old['part_number'] ?? '') ?>" placeholder="e.g. HYQ14FBA" class="<?= $_inp ?>">
      </div>
      <div>
        <label class="<?= $_lbl ?>">Frequency</label>
        <input type="text" name="frequency" value="<?= h($old['frequency'] ?? '') ?>" placeholder="e.g. 433MHz" class="<?= $_inp ?>">
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 px-5 pt-4">
      <div>
        <label class="<?= $_lbl ?>">IC</label>
        <input type="text" name="ic" value="<?= h($old['ic'] ?? '') ?>" placeholder="e.g. 7812A-14ACA" class="<?= $_inp ?>">
      </div>
      <div>
        <label class="<?= $_lbl ?>">Chip</label>
        <input type="text" name="chip" value="<?= h($old['chip'] ?? '') ?>" placeholder="e.g. 315MHz" class="<?= $_inp ?>">
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 px-5 pt-4">
      <div>
        <label class="<?= $_lbl ?>">Category</label>
        <select name="category_id" class="<?= $_sel ?>">
          <option value="">— Uncategorized —</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= (int) $cat['category_id'] ?>"
                    <?= (int) ($old['category_id'] ?? 0) === (int) $cat['category_id'] ? 'selected' : '' ?>>
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
                    <?= (int) ($old['manufacturer_id'] ?? 0) === (int) $mfr['manufacturer_id'] ? 'selected' : '' ?>>
              <?= h($mfr['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 px-5 pt-4">
      <div>
        <label class="<?= $_lbl ?>">Initial Main Stock</label>
        <input type="number" name="initial_qty" value="<?= h($old['initial_qty'] ?? '0') ?>"
               step="1" min="0" class="<?= $_inp ?>">
      </div>
      <div>
        <label class="<?= $_lbl ?>">Cost ($) *</label>
        <input type="number" name="cost" value="<?= h($old['cost'] ?? '0') ?>"
               step="0.0001" min="0" required class="<?= $_inp ?>">
        <?php if (!empty($errors['cost'])): ?><p class="mt-1 text-xs text-red-600"><?= h($errors['cost'][0]) ?></p><?php endif; ?>
      </div>
    </div>


    <div class="mx-5 mt-4 px-4 py-3 bg-blue-50 rounded-lg border border-blue-200 flex items-center gap-3">
      <svg class="w-4 h-4 text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
      </svg>
      <p class="text-xs text-blue-700">A <span class="font-semibold">Product ID</span> (e.g. <span class="font-mono font-bold">P000042</span>) will be automatically assigned after saving.</p>
    </div>

    <div class="px-5 pt-4">
      <label class="<?= $_lbl ?>">Product Image</label>
      <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif"
             class="block w-full text-sm text-slate-700 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border file:border-slate-200 file:text-sm file:bg-slate-50 file:text-slate-700 hover:file:bg-slate-100 transition">
      <p class="mt-1 text-xs text-slate-400">JPG, PNG, WebP or GIF. Optional.</p>
    </div>

    <div class="flex items-center gap-3 px-5 py-5 mt-2 border-t border-slate-100">
      <button type="submit"
              class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150">
        Save Item
      </button>
      <a href="<?= url('inventory') ?>"
         class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">
        Cancel
      </a>
    </div>
  </form>
</div>

<script>
(function () {
  'use strict';

  function extractKV(lines, key) {
    var re = new RegExp('^\\s*' + key + '\\s*:\\s*(.+)$', 'i');
    for (var i = 0; i < lines.length; i++) {
      var m = lines[i].match(re);
      if (m) return m[1].trim();
    }
    return null;
  }

  function fuzzyMatchManufacturer(select, query) {
    if (!query) return;
    var q = query.trim().toLowerCase();
    var opts = select.options;
    for (var i = 0; i < opts.length; i++) {
      if (opts[i].text.trim().toLowerCase() === q) {
        select.value = opts[i].value;
        return;
      }
    }
    // Partial match fallback
    for (var j = 0; j < opts.length; j++) {
      if (opts[j].text.trim().toLowerCase().indexOf(q) !== -1 ||
          q.indexOf(opts[j].text.trim().toLowerCase()) !== -1) {
        select.value = opts[j].value;
        return;
      }
    }
  }

  function setField(name, value) {
    if (value === null || value === undefined) return;
    var el = document.querySelector('[name="' + name + '"]');
    if (el) el.value = value;
  }

  function showStatus(msg, isError) {
    var el = document.getElementById('smart-paste-status');
    el.textContent = msg;
    el.className = 'text-xs ' + (isError ? 'text-red-500' : 'text-emerald-600');
    el.classList.remove('hidden');
    clearTimeout(el._hideTimer);
    el._hideTimer = setTimeout(function () { el.classList.add('hidden'); }, 4000);
  }

  document.getElementById('smart-paste-btn').addEventListener('click', function () {
    var raw = document.getElementById('smart-paste-input').value;
    if (!raw.trim()) {
      showStatus('Nothing to parse — paste supplier text first.', true);
      return;
    }

    var lines = raw.split(/\r?\n/);

    // Positional fields
    var name = lines[0] ? lines[0].trim() : null;
    var costRaw = lines[1] ? lines[1].trim() : null;
    var cost = costRaw ? costRaw.replace(/^\$\s*/, '').trim() : null;

    // Key: Value fields
    var fcc_id      = extractKV(lines, 'fcc');
    var manufacturer = extractKV(lines, 'manufacturer');
    var part_number = extractKV(lines, 'part[\\s_-]*number');
    var frequency   = extractKV(lines, 'frequency');
    var chip        = extractKV(lines, 'chip');

    var filled = 0;

    if (name)        { setField('name', name);              filled++; }
    if (cost)        { setField('cost', cost);              filled++; }
    if (fcc_id)      { setField('fcc_id', fcc_id);          filled++; }
    if (part_number) { setField('part_number', part_number); filled++; }
    if (frequency)   { setField('frequency', frequency);    filled++; }
    if (chip)        { setField('chip', chip);              filled++; }

    if (manufacturer) {
      var sel = document.querySelector('[name="manufacturer_id"]');
      if (sel) { fuzzyMatchManufacturer(sel, manufacturer); filled++; }
    }

    showStatus(filled > 0 ? filled + ' field' + (filled === 1 ? '' : 's') + ' filled.' : 'No matching fields found.', filled === 0);
  });
}());
</script>
