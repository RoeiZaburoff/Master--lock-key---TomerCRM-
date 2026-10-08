<?php
$catalogJson = json_encode(array_values(array_map(static function (array $item): array {
    return [
        'id'           => (int)   $item['inventory_id'],
        'name'         => (string)$item['name'],
        'fcc_id'       => (string)($item['fcc_id'] ?? ''),
        'part_number'  => (string)($item['part_number'] ?? ''),
        'manufacturer' => (string)($item['manufacturer_name'] ?? ''),
        'cost'         => (float) $item['cost'],
        'product_id'   => (string)($item['product_id'] ?? ''),
    ];
}, $catalog)), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

$existingItemsJson = json_encode(array_values(array_map(static function (array $m): array {
    $isManual = empty($m['inventory_id']);
    return [
        'inventory_id' => $isManual ? null : (int) $m['inventory_id'],
        'name'         => $isManual ? (string) $m['description'] : (string) ($m['catalog_name'] ?? $m['description']),
        'description'  => (string) $m['description'],
        'ownership'    => $m['ownership'] === 'company' ? 'company' : 'technician',
        'location_id'  => empty($m['inventory_id']) ? null : (int) ($m['location_id'] ?? 1),
        'qty'          => (float) $m['qty'],
        'unit_cost'    => (float) $m['unit_cost'],
        'isManual'     => $isManual,
    ];
}, $materials)), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

$locationsJson = json_encode(array_values(array_map(static function (array $loc): array {
    return ['id' => (int) $loc['location_id'], 'name' => (string) $loc['name']];
}, $locations)), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

$_inp = 'w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition';
$_sel = 'w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition';
$_lbl = 'block text-xs font-semibold text-slate-600 mb-1';
?>

<div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
  <h1 class="text-xl font-bold text-slate-900">
    Edit Costs — Lead #<?= (int) $lead['job_id'] ?><?= !empty($lead['company_name']) ? ' <span class="font-normal text-slate-400">—</span> ' . h($lead['company_name']) : '' ?>
  </h1>
  <a href="<?= url('leads/' . $lead['job_id']) ?>"
     class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">
    ← Cancel
  </a>
</div>

<!-- ── Inventory Search Modal ───────────────────────────────────────────── -->
<div id="inv-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none !important">
  <!-- Backdrop -->
  <div id="inv-backdrop" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
  <!-- Panel -->
  <div class="relative z-10 w-full max-w-2xl bg-white rounded-2xl shadow-2xl flex flex-col" style="max-height:85vh">
    <!-- Header -->
    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 flex-shrink-0">
      <h3 class="text-base font-semibold text-slate-900">Search Inventory Catalog</h3>
      <button type="button" id="inv-modal-close"
              class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition-colors text-lg font-bold leading-none">
        ✕
      </button>
    </div>
    <!-- Search input -->
    <div class="px-5 py-3 border-b border-slate-100 flex-shrink-0">
      <input type="text" id="inv-search" placeholder="Search by name, manufacturer, FCC ID, part number, or Product ID…"
             autocomplete="off"
             class="w-full px-4 py-2.5 rounded-lg border border-slate-200 text-sm text-slate-800 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
    </div>
    <!-- Results list -->
    <div class="overflow-y-auto flex-1 px-2 py-2" id="inv-list-wrap">
      <ul id="inv-list" class="divide-y divide-slate-100">
        <!-- populated by JS -->
      </ul>
      <p id="inv-no-results" class="py-8 text-center text-sm text-slate-400" style="display:none">No items match your search.</p>
    </div>
    <!-- Manual entry footer -->
    <div class="px-5 py-3 border-t border-slate-100 flex-shrink-0">
      <button type="button" id="inv-manual"
              class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg border border-dashed border-slate-300 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150">
        &#x270F; Add Manual Entry (not in catalog)
      </button>
    </div>
  </div>
</div>

<form method="POST" id="edit-materials-form" action="<?= url('leads/' . $lead['job_id'] . '/materials') ?>">
  <?= \App\Core\CSRF::field() ?>

  <!-- ── Inventory section ─────────────────────────────────────────────── -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm mb-5">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-4 flex-wrap">
      <div>
        <h2 class="text-sm font-semibold text-slate-900">Materials Used</h2>
        <p class="text-xs text-slate-500 mt-0.5">Add, remove, or edit materials. Stock will not be re-decremented.</p>
      </div>
      <button type="button" id="inv-open"
              class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-sm font-semibold shadow-sm transition-colors duration-150 flex-shrink-0">
        <span>🔍</span> Search &amp; Add Inventory Item
      </button>
    </div>

    <!-- Items table -->
    <div class="overflow-x-auto">
      <div id="items-empty" class="py-10 text-center text-sm text-slate-400">No items added yet. Use the button above to search the catalog.</div>
      <table class="table w-full" id="items-table" style="display:none">
        <thead>
          <tr>
            <th>Item</th>
            <th>Provided By</th>
            <th>Inventory</th>
            <th class="text-center">Qty</th>
            <th class="text-center">Unit Cost ($)</th>
            <th class="text-center">Line Total</th>
            <th></th>
          </tr>
        </thead>
        <tbody id="items-body"></tbody>
      </table>
    </div>
  </div>

  <!-- Hidden inputs injected by JS on submit -->
  <div id="items-hidden"></div>

  <!-- ── Edit details ──────────────────────────────────────────────────── -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
    <div class="px-5 py-4 border-b border-slate-100">
      <h2 class="text-sm font-semibold text-slate-900">Cost Details</h2>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 px-5 pt-5 pb-4">
      <div>
        <label class="<?= $_lbl ?>">Job Amount ($) *</label>
        <input type="number" name="job_total"
               value="<?= h($lead['job_total'] > 0 ? $lead['job_total'] : '') ?>"
               step="0.01" min="0" placeholder="Enter final amount" required
               class="<?= $_inp ?>">
      </div>
      <div>
        <label class="<?= $_lbl ?>">Payment Method</label>
        <select name="payment_method" class="<?= $_sel ?>">
          <option value="">— Select —</option>
          <?php foreach (['cash' => 'Cash', 'credit_card' => 'Credit Card', 'check' => 'Check', 'zelle' => 'Zelle', 'cash_app' => 'Cash App'] as $val => $label): ?>
            <option value="<?= $val ?>" <?= ($lead['payment_method'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="px-5 pb-5">
      <label class="block text-sm font-medium text-slate-700 mb-1.5">Close Date/Time</label>
      <input type="datetime-local" name="completed_at" id="completed_at"
             value="<?= h(!empty($lead['completed_at']) ? date('Y-m-d\TH:i', strtotime($lead['completed_at'])) : date('Y-m-d\TH:i')) ?>"
             class="w-full max-w-xs px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
    </div>

    <div class="flex items-center gap-3 px-5 py-5 border-t border-slate-100">
      <button type="submit"
              class="inline-flex items-center px-5 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150">
        Update &amp; Recalculate
      </button>
      <a href="<?= url('leads/' . $lead['job_id']) ?>"
         class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">
        Cancel
      </a>
    </div>
  </div>

</form>

<script>
(function () {
  'use strict';

  /* ── Catalog data ─────────────────────────────────────────────────────── */
  var CATALOG    = <?= $catalogJson ?>;
  var LOCATIONS  = <?= $locationsJson ?>;

  /* ── State ────────────────────────────────────────────────────────────── */
  var items = <?= $existingItemsJson ?>;

  /* ── DOM refs ─────────────────────────────────────────────────────────── */
  var modal       = document.getElementById('inv-modal');
  var backdrop    = document.getElementById('inv-backdrop');
  var btnOpen     = document.getElementById('inv-open');
  var btnClose    = document.getElementById('inv-modal-close');
  var searchInput = document.getElementById('inv-search');
  var listEl      = document.getElementById('inv-list');
  var noResults   = document.getElementById('inv-no-results');
  var elEmpty     = document.getElementById('items-empty');
  var elTable     = document.getElementById('items-table');
  var elBody      = document.getElementById('items-body');
  var elHidden    = document.getElementById('items-hidden');
  var elForm      = document.getElementById('edit-materials-form');

  /* ── Modal helpers ────────────────────────────────────────────────────── */
  function openModal() {
    modal.style.removeProperty('display');
    modal.style.display = 'flex';
    searchInput.value = '';
    renderList('');
    requestAnimationFrame(function () { searchInput.focus(); });
  }

  function closeModal() {
    modal.style.display = 'none';
  }

  btnOpen.addEventListener('click', openModal);
  btnClose.addEventListener('click', closeModal);
  backdrop.addEventListener('click', closeModal);

  document.getElementById('inv-manual').addEventListener('click', function () {
    closeModal();
    addManualItem();
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal.style.display === 'flex') closeModal();
  });

  /* ── Live search ──────────────────────────────────────────────────────── */
  searchInput.addEventListener('input', function () {
    renderList(this.value.trim().toLowerCase());
  });

  function renderList(query) {
    var filtered;
    if (!query) {
      filtered = CATALOG;
    } else {
      // Exact product_id match takes absolute priority — enables instant secretary lookup
      var exactPid = CATALOG.filter(function (c) {
        return c.product_id && c.product_id.toLowerCase() === query;
      });
      if (exactPid.length > 0) {
        filtered = exactPid;
      } else {
        filtered = CATALOG.filter(function (c) {
          return (
            c.name.toLowerCase().indexOf(query) !== -1 ||
            c.manufacturer.toLowerCase().indexOf(query) !== -1 ||
            c.fcc_id.toLowerCase().indexOf(query) !== -1 ||
            c.part_number.toLowerCase().indexOf(query) !== -1
          );
        });
      }
    }

    if (filtered.length === 0) {
      listEl.innerHTML = '';
      noResults.style.display = '';
      return;
    }

    noResults.style.display = 'none';
    listEl.innerHTML = filtered.map(function (c) {
      var meta = [];
      if (c.manufacturer) meta.push(c.manufacturer);
      if (c.fcc_id)        meta.push('FCC: ' + c.fcc_id);
      if (c.part_number)   meta.push('P/N: ' + c.part_number);

      return '<li data-id="' + c.id + '"' +
             '    class="flex items-center justify-between gap-4 px-3 py-3 rounded-lg cursor-pointer hover:bg-blue-50 active:bg-blue-100 transition-colors group">' +
             '  <div class="min-w-0 flex-1">' +
             '    <div class="text-sm font-medium text-slate-900 truncate group-hover:text-blue-700">' + esc(c.name) + '</div>' +
             (meta.length ? '<div class="text-xs text-slate-400 mt-0.5 truncate">' + esc(meta.join(' · ')) + '</div>' : '') +
             '  </div>' +
             '  <div class="flex items-center gap-2 flex-shrink-0">' +
             (c.product_id ? '<span class="text-xs font-mono font-bold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded group-hover:bg-blue-100">' + esc(c.product_id) + '</span>' : '') +
             '  <div class="text-sm font-semibold text-slate-700 group-hover:text-blue-700">$' + parseFloat(c.cost).toFixed(2) + '</div>' +
             '  </div>' +
             '</li>';
    }).join('');
  }

  /* ── Select item from modal ───────────────────────────────────────────── */
  listEl.addEventListener('click', function (e) {
    var li = e.target.closest('li[data-id]');
    if (!li) return;

    var id = parseInt(li.dataset.id, 10);
    var c  = CATALOG.find(function (x) { return x.id === id; });
    if (!c) return;

    closeModal();
    addItem(c.id, c.name, c.cost);
  });

  /* ── Add catalog item ─────────────────────────────────────────────────── */
  function addItem(inventoryId, name, defaultCost) {
    items.push({
      inventory_id: inventoryId,
      name:         name,
      description:  name,
      ownership:    'technician',
      location_id:  inventoryId ? (LOCATIONS.length ? LOCATIONS[0].id : null) : null,
      qty:          1,
      unit_cost:    defaultCost,
      isManual:     false,
    });
    renderTable();
  }

  /* ── Add manual item ──────────────────────────────────────────────────── */
  function addManualItem() {
    items.push({
      inventory_id: null,
      name:         '',
      description:  '',
      ownership:    'technician',
      location_id:  null,
      qty:          1,
      unit_cost:    0,
      isManual:     true,
    });
    renderTable();
  }

  /* ── Render table ─────────────────────────────────────────────────────── */
  function renderTable() {
    if (items.length === 0) {
      elEmpty.style.display = '';
      elTable.style.display = 'none';
      return;
    }
    elEmpty.style.display = 'none';
    elTable.style.display = '';

    var inpCls = 'px-2 py-1.5 rounded-md border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition w-full';
    var selCls = 'px-2 py-1.5 rounded-md border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition w-full';

    var html = '';
    var grandTotal = 0;

    items.forEach(function (item, i) {
      var lineTotal = Math.round(item.qty * item.unit_cost * 100) / 100;
      grandTotal += lineTotal;

      var locHtml;
      if (item.inventory_id) {
        var locOpts = LOCATIONS.map(function (l) {
          return '<option value="' + l.id + '"' + (item.location_id === l.id ? ' selected' : '') + '>' + esc(l.name) + '</option>';
        }).join('');
        locHtml = '<select class="' + selCls + '" data-field="location_id">' + locOpts + '</select>';
      } else {
        locHtml = '<span class="text-xs text-slate-400 font-medium px-2">N/A (Manual)</span>';
      }

      html +=
        '<tr data-idx="' + i + '">' +
        '<td class="font-medium text-slate-800 min-w-[160px]">' +
          (item.isManual
            ? '<span class="text-slate-400 italic text-sm">Manual Entry</span>'
            : esc(item.name)) +
        '</td>' +
        '<td class="min-w-[140px]">' +
          '<select class="' + selCls + '" data-field="ownership">' +
            '<option value="technician"' + (item.ownership === 'technician' ? ' selected' : '') + '>Technician</option>' +
            '<option value="company"'    + (item.ownership === 'company'    ? ' selected' : '') + '>Company</option>' +
          '</select>' +
        '</td>' +
        '<td class="min-w-[160px]">' + locHtml + '</td>' +
        '<td class="min-w-[80px] text-center">' +
          (item.isManual
            ? '<span class="text-slate-400 text-sm">1</span>'
            : '<input type="number" class="' + inpCls + '" data-field="qty" value="' + item.qty + '" min="1" step="1">') +
        '</td>' +
        '<td class="min-w-[110px] text-center">' +
          '<input type="number" class="' + inpCls + '" data-field="unit_cost" value="' + item.unit_cost.toFixed(2) + '" min="0" step="0.01">' +
        '</td>' +
        '<td class="text-center font-semibold text-slate-800 line-total" data-idx="' + i + '">$' + lineTotal.toFixed(2) + '</td>' +
        '<td class="text-center">' +
          '<button type="button" class="btn btn-sm btn-danger" data-remove="' + i + '">✕</button>' +
        '</td>' +
        '</tr>';
    });

    html +=
      '<tr class="bg-slate-50">' +
      '<td colspan="5" class="text-right text-xs font-semibold text-slate-600 py-2.5 px-4">Grand Total</td>' +
      '<td class="text-center font-bold text-slate-900 py-2.5" id="grand-total">$' + grandTotal.toFixed(2) + '</td>' +
      '<td></td>' +
      '</tr>';

    elBody.innerHTML = html;
  }

  /* ── Inline editing via event delegation ──────────────────────────────── */
  elBody.addEventListener('input', function (e) {
    var field = e.target.dataset.field;
    if (field !== 'qty' && field !== 'unit_cost' && field !== 'description') return;

    var tr = e.target.closest('tr[data-idx]');
    if (!tr) return;
    var idx = parseInt(tr.dataset.idx, 10);

    if (field === 'description') {
      items[idx].description = e.target.value;
      items[idx].name        = e.target.value;
      return;
    }

    var val = parseFloat(e.target.value);
    if (isNaN(val) || val < 0) return;

    if (field === 'qty')       items[idx].qty       = val;
    if (field === 'unit_cost') items[idx].unit_cost = val;

    // Update line total cell without full re-render
    var lineTotal = Math.round(items[idx].qty * items[idx].unit_cost * 100) / 100;
    var ltCell    = elBody.querySelector('.line-total[data-idx="' + idx + '"]');
    if (ltCell) ltCell.textContent = '$' + lineTotal.toFixed(2);

    // Recompute grand total
    var grand = items.reduce(function (sum, it) {
      return sum + Math.round(it.qty * it.unit_cost * 100) / 100;
    }, 0);
    var gtEl = document.getElementById('grand-total');
    if (gtEl) gtEl.textContent = '$' + grand.toFixed(2);
  });

  elBody.addEventListener('change', function (e) {
    var field = e.target.dataset.field;
    if (field !== 'ownership' && field !== 'location_id') return;
    var tr = e.target.closest('tr[data-idx]');
    if (!tr) return;
    var idx = parseInt(tr.dataset.idx, 10);
    if (field === 'ownership')   items[idx].ownership  = e.target.value;
    if (field === 'location_id') items[idx].location_id = parseInt(e.target.value, 10);
  });

  /* ── Remove row ───────────────────────────────────────────────────────── */
  elBody.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-remove]');
    if (!btn) return;
    items.splice(parseInt(btn.dataset.remove, 10), 1);
    renderTable();
  });

  /* ── Serialize to hidden inputs on submit ─────────────────────────────── */
  elForm.addEventListener('submit', function () {
    elHidden.innerHTML = '';
    items.forEach(function (item, i) {
      [
        ['inventory_id', item.inventory_id],
        ['description',  item.description],
        ['ownership',    item.ownership],
        ['location_id',  item.location_id],
        ['qty',          item.qty],
        ['unit_cost',    item.unit_cost],
      ].forEach(function (pair) {
        var inp   = document.createElement('input');
        inp.type  = 'hidden';
        inp.name  = 'materials[' + i + '][' + pair[0] + ']';
        inp.value = (pair[1] === null || pair[1] === undefined) ? '' : pair[1];
        elHidden.appendChild(inp);
      });
    });
  });

  /* ── Utility ──────────────────────────────────────────────────────────── */
  function esc(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  /* ── Init ─────────────────────────────────────────────────────────────── */
  renderTable();
  renderList('');

}());
</script>
