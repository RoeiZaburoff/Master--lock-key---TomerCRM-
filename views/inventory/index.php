<?php
$isMainLocation = ($location_id === 1);
$locationsMap   = [];
foreach ($locations as $loc) {
    $locationsMap[(int) $loc['location_id']] = $loc['name'];
}
$locationLabel = $locationsMap[$location_id] ?? 'Technician / Vehicle';

$typeIcon = ['warehouse' => '🏭', 'vehicle' => '🚐', 'tech' => '👷'];

function locOption(array $loc, int $currentId, array $typeIcon): string {
    $lid   = (int) $loc['location_id'];
    $label = $lid === 1
        ? '🏭 Main Warehouse'
        : (($typeIcon[$loc['type']] ?? '') . ' ' . htmlspecialchars($loc['name'], ENT_QUOTES));
    $sel   = $lid === $currentId ? ' selected' : '';
    return '<option value="' . $lid . '"' . $sel . '>' . $label . '</option>';
}
?>

<div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
  <div>
    <h1 class="text-xl font-bold text-slate-900">Inventory</h1>
    <?php if (!$isMainLocation): ?>
      <p class="text-xs text-slate-500 mt-0.5">Technician Stock:
        <span class="font-semibold text-blue-600"><?= h($locationLabel) ?></span>
      </p>
    <?php endif; ?>
  </div>
  <div class="flex items-center gap-2 flex-wrap">
    <?php if (!empty($__is_admin)): ?>
    <a href="<?= url('inventory/categories') ?>"
       class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">
      Categories
    </a>
    <a href="<?= url('inventory/manufacturers') ?>"
       class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">
      Manufacturers
    </a>
    <a href="<?= url('inventory/locations') ?>"
       class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">
      Technicians / Vehicles
    </a>
    <?php endif; ?>
    <button type="button" id="bulk-transfer-open"
            class="inline-flex items-center px-3 py-1.5 rounded-lg border border-emerald-200 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-sm font-semibold transition-colors duration-150">
      Bulk Transfer
    </button>
    <a href="<?= url('inventory/create') ?>"
       class="inline-flex items-center px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150 no-underline">
      + Add Item
    </a>
  </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm mb-5 px-4 py-3">
  <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-wrap">
    <input type="text" id="live-search" placeholder="Search name, FCC ID, part number, or Product ID…"
           value="<?= h($search) ?>"
           class="flex-1 min-w-[180px] px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">

    <select id="mfr-filter"
            class="px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
      <option value="0">All Manufacturers</option>
      <?php foreach ($manufacturers as $mfr): ?>
        <option value="<?= (int) $mfr['manufacturer_id'] ?>"
                <?= (int) $manufacturer_id === (int) $mfr['manufacturer_id'] ? 'selected' : '' ?>>
          <?= h($mfr['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <select id="location-select"
            class="px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
      <?php foreach ($locations as $loc): ?>
        <?= locOption($loc, $location_id, $typeIcon) ?>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<!-- ── Hierarchical Accordion ──────────────────────────────────────────────── -->
<?php if (empty($tree)): ?>
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm py-16 text-center text-sm text-slate-400">
    No inventory items found.
  </div>
<?php else: ?>
<div id="inventory-tree" class="space-y-3">

<?php foreach ($tree as $catName => $manufacturers): ?>
<?php
  $catTotal = array_sum(array_map('count', $manufacturers));
?>
<!-- Level 1: Category -->
<details class="cat-group bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
  <summary class="flex items-center justify-between gap-3 px-5 py-3.5 cursor-pointer
                  hover:bg-slate-50 transition-colors select-none list-none
                  [&::-webkit-details-marker]:hidden">
    <div class="flex items-center gap-2.5">
      <svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
      </svg>
      <span class="text-sm font-bold text-slate-800"><?= h($catName) ?></span>
    </div>
    <div class="flex items-center gap-2">
      <span class="cat-count text-xs font-medium bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full"><?= $catTotal ?> item<?= $catTotal !== 1 ? 's' : '' ?></span>
      <svg class="details-chevron w-4 h-4 text-slate-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
      </svg>
    </div>
  </summary>

  <div class="divide-y divide-slate-100 border-t border-slate-100">
  <?php foreach ($manufacturers as $mfrName => $items): ?>
  <?php $mfrTotal = count($items); ?>

  <!-- Level 2: Manufacturer -->
  <details class="mfr-group">
    <summary class="flex items-center justify-between gap-3 pl-10 pr-5 py-2.5 cursor-pointer
                    hover:bg-slate-50/80 transition-colors select-none list-none
                    [&::-webkit-details-marker]:hidden bg-slate-50/40">
      <div class="flex items-center gap-2">
        <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/>
        </svg>
        <span class="text-xs font-semibold text-slate-600"><?= h($mfrName) ?></span>
      </div>
      <div class="flex items-center gap-2">
        <span class="mfr-count text-xs text-slate-400"><?= $mfrTotal ?></span>
        <svg class="details-chevron w-3.5 h-3.5 text-slate-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
      </div>
    </summary>

    <!-- Level 3: Product card grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-6 p-4">
    <?php foreach ($items as $item): ?>
    <?php
      $viewQty  = (float) ($item['stock_qty'] ?? 0);
      $mainQty  = (float) ($item['quantity']  ?? 0);
      $lowStock = $viewQty < 2;
      $pid      = $item['product_id'] ?? '';
    ?>
    <div class="inventory-row flex flex-col bg-white rounded-xl border <?= $lowStock ? 'border-red-200' : 'border-slate-200' ?> hover:shadow-lg transition-shadow duration-300 overflow-hidden"
         data-name="<?= strtolower(h($item['name'])) ?>"
         data-fcc="<?= strtolower(h($item['fcc_id'] ?? '')) ?>"
         data-part="<?= strtolower(h($item['part_number'] ?? '')) ?>"
         data-pid="<?= strtolower(h($pid)) ?>"
         data-mfr="<?= (int) ($item['manufacturer_id'] ?? 0) ?>">

      <!-- Image -->
      <?php if (!empty($item['image_path'])): ?>
        <div class="w-full h-48 bg-gray-50 flex-shrink-0 flex items-center justify-center overflow-hidden">
          <img src="<?= url($item['image_path']) ?>" alt="<?= h($item['name']) ?>"
               class="w-full h-48 object-contain p-2">
        </div>
      <?php else: ?>
        <div class="w-full h-48 bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center flex-shrink-0">
          <svg class="w-14 h-14 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
          </svg>
        </div>
      <?php endif; ?>

      <!-- Body -->
      <div class="flex flex-col flex-1 p-4 gap-2">

        <!-- Name + badges -->
        <div class="flex items-start justify-between gap-2">
          <h3 class="text-sm font-bold text-slate-900 leading-snug flex-1"><?= h($item['name']) ?></h3>
          <div class="flex flex-col items-end gap-1 flex-shrink-0">
            <?php if ($pid !== ''): ?>
              <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-mono font-bold bg-blue-100 text-blue-700"><?= h($pid) ?></span>
            <?php endif; ?>
            <?php if ($lowStock): ?>
              <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-700">Low</span>
            <?php endif; ?>
          </div>
        </div>

        <!-- FCC / Part / Freq tags -->
        <?php if (!empty($item['fcc_id']) || !empty($item['part_number']) || !empty($item['frequency'])): ?>
        <div class="flex flex-wrap gap-1.5">
          <?php if (!empty($item['fcc_id'])): ?>
            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-xs font-mono text-slate-600">FCC: <?= h($item['fcc_id']) ?></span>
          <?php endif; ?>
          <?php if (!empty($item['part_number'])): ?>
            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-xs font-mono text-slate-600">P/N: <?= h($item['part_number']) ?></span>
          <?php endif; ?>
          <?php if (!empty($item['frequency'])): ?>
            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-xs font-mono text-slate-600"><?= h($item['frequency']) ?></span>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Stock + Cost row -->
        <div class="flex items-end justify-between mt-auto pt-3 border-t border-slate-100 gap-2">
          <div class="space-y-0.5">
            <?php if ($isMainLocation): ?>
              <p class="text-xs text-slate-400">Main Stock</p>
              <p class="text-lg font-extrabold <?= $lowStock ? 'text-red-600' : 'text-slate-900' ?> leading-none">
                <?= number_format($viewQty, 0) ?> <span class="text-xs font-normal text-slate-400">units</span>
              </p>
            <?php else: ?>
              <p class="text-xs text-slate-400">Tech Stock</p>
              <p class="text-lg font-extrabold <?= $lowStock ? 'text-red-600' : 'text-slate-900' ?> leading-none">
                <?= number_format($viewQty, 0) ?> <span class="text-xs font-normal text-slate-400">units</span>
              </p>
              <p class="text-xs text-slate-400">Main: <span class="font-semibold text-slate-600"><?= number_format($mainQty, 0) ?></span></p>
            <?php endif; ?>
          </div>
          <div class="text-right">
            <p class="text-xs text-slate-400">Cost</p>
            <p class="text-sm font-bold text-slate-800"><?= money($item['cost']) ?></p>
          </div>
        </div>
      </div>

      <!-- Action footer -->
      <div class="px-4 pb-4 flex items-center gap-1.5 flex-wrap border-t border-slate-100 pt-3">
        <a href="<?= url('inventory/' . $item['inventory_id'] . '/edit') ?>"
           class="btn btn-sm">Edit</a>

        <button type="button"
                class="btn btn-sm text-violet-700 border-violet-200 bg-violet-50 hover:bg-violet-100 js-adjust-open"
                data-id="<?= (int) $item['inventory_id'] ?>"
                data-name="<?= h($item['name']) ?>"
                data-qty="<?= number_format($viewQty, 2) ?>">
          Adjust
        </button>

        <button type="button"
                class="btn btn-sm text-emerald-700 border-emerald-200 bg-emerald-50 hover:bg-emerald-100 js-transfer-open"
                data-id="<?= (int) $item['inventory_id'] ?>"
                data-name="<?= h($item['name']) ?>"
                data-location="<?= (int) $location_id ?>">
          Transfer
        </button>

        <?php if (!empty($__is_admin)): ?>
        <form method="POST" action="<?= url('inventory/' . $item['inventory_id'] . '/delete') ?>"
              onsubmit="return confirm('Delete this item? This cannot be undone.')">
          <?= \App\Core\CSRF::field() ?>
          <button type="submit" class="btn btn-sm btn-danger">Delete</button>
        </form>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
    </div>
  </details>

  <?php endforeach; ?>
  </div>
</details>

<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── Bulk Transfer Modal ─────────────────────────────────────────────────── -->
<div id="bulk-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none !important">
  <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" id="bulk-backdrop"></div>
  <div class="relative z-10 w-full max-w-2xl bg-white rounded-2xl shadow-2xl flex flex-col max-h-[90vh]">

    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 flex-shrink-0">
      <h3 class="text-base font-semibold text-slate-900">Bulk Transfer</h3>
      <button type="button" id="bulk-close"
              class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition-colors text-lg font-bold">
        &#x2715;
      </button>
    </div>

    <form id="bulk-form" class="flex flex-col flex-1 min-h-0">
      <?= \App\Core\CSRF::field() ?>

      <div class="px-5 py-4 grid grid-cols-2 gap-4 flex-shrink-0 border-b border-slate-100">
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1">Source (Warehouse / Technician)</label>
          <select name="from_location_id" id="bulk-from"
                  class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
            <?php foreach ($locations as $loc): ?>
              <?php $lid = (int) $loc['location_id']; ?>
              <option value="<?= $lid ?>" <?= $location_id === $lid ? 'selected' : '' ?>>
                <?= $lid === 1 ? '🏭 Main Warehouse' : ($typeIcon[$loc['type']] ?? '') . ' ' . h($loc['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1">Target (Technician / Vehicle)</label>
          <select name="to_location_id" id="bulk-to"
                  class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
            <?php foreach ($locations as $loc): ?>
              <?php $lid = (int) $loc['location_id']; ?>
              <option value="<?= $lid ?>" <?= $lid !== $location_id ? 'selected' : '' ?>>
                <?= $lid === 1 ? '🏭 Main Warehouse' : ($typeIcon[$loc['type']] ?? '') . ' ' . h($loc['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="px-5 pt-4 flex-shrink-0">
        <label class="block text-xs font-semibold text-slate-600 mb-1">Search Items to Add</label>
        <input type="text" id="bulk-search"
               placeholder="Type name, FCC ID, part number, or Product ID…"
               class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
               autocomplete="off">
        <div id="bulk-search-results"
             class="mt-1 rounded-lg border border-slate-200 bg-white shadow-lg max-h-44 overflow-y-auto hidden">
        </div>
      </div>

      <div class="px-5 py-3 flex-1 overflow-y-auto min-h-0">
        <div id="bulk-empty" class="py-6 text-center text-sm text-slate-400">
          Search for items above to add them to the transfer list.
        </div>
        <table id="bulk-table" class="w-full text-sm hidden">
          <thead>
            <tr class="text-left text-xs text-slate-500 border-b border-slate-100">
              <th class="pb-2 font-semibold">Item</th>
              <th class="pb-2 font-semibold w-28">Quantity</th>
              <th class="pb-2 w-8"></th>
            </tr>
          </thead>
          <tbody id="bulk-tbody"></tbody>
        </table>
      </div>

      <div class="px-5 pb-4 flex-shrink-0 border-t border-slate-100 pt-3">
        <p id="bulk-error" class="text-xs text-red-600 mb-2 hidden"></p>
        <div class="flex items-center gap-3">
          <button type="submit" id="bulk-submit"
                  class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-sm transition-colors disabled:opacity-50">
            Transfer
          </button>
          <button type="button" id="bulk-cancel"
                  class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors">
            Cancel
          </button>
          <span id="bulk-item-count" class="text-xs text-slate-400 ml-auto"></span>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Single Item Transfer Modal -->
<div id="transfer-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none !important">
  <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" id="transfer-backdrop"></div>
  <div class="relative z-10 w-full max-w-sm bg-white rounded-2xl shadow-2xl">
    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
      <h3 class="text-base font-semibold text-slate-900">Transfer Item</h3>
      <button type="button" id="transfer-close"
              class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition-colors text-lg font-bold">
        &#x2715;
      </button>
    </div>
    <form method="POST" id="transfer-form" action="">
      <?= \App\Core\CSRF::field() ?>
      <div class="px-5 py-4 space-y-4">
        <div>
          <p class="text-xs text-slate-500 mb-1">Item</p>
          <p class="text-sm font-semibold text-slate-800" id="transfer-item-name"></p>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1">Source (Warehouse / Technician)</label>
          <select name="from_location_id" id="transfer-from"
                  class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
            <?php foreach ($locations as $loc): ?>
              <?php $lid = (int) $loc['location_id']; ?>
              <option value="<?= $lid ?>">
                <?= $lid === 1 ? '🏭 Main Warehouse' : ($typeIcon[$loc['type']] ?? '') . ' ' . h($loc['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1">Target (Technician / Vehicle)</label>
          <select name="to_location_id" id="transfer-to"
                  class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
            <?php foreach ($locations as $loc): ?>
              <?php $lid = (int) $loc['location_id']; ?>
              <option value="<?= $lid ?>">
                <?= $lid === 1 ? '🏭 Main Warehouse' : ($typeIcon[$loc['type']] ?? '') . ' ' . h($loc['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="transfer-qty-input" class="block text-xs font-semibold text-slate-600 mb-1">Quantity</label>
          <input type="number" name="qty" id="transfer-qty-input" min="0.01" step="0.01" value="1" required
                 class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 transition"
                 placeholder="0.00">
        </div>
        <p id="transfer-error" class="text-xs text-red-600 hidden"></p>
      </div>
      <div class="flex items-center gap-3 px-5 py-4 border-t border-slate-100">
        <button type="submit"
                class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-sm transition-colors">
          Transfer
        </button>
        <button type="button" id="transfer-cancel"
                class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors">
          Cancel
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Adjust Stock Modal -->
<div id="adjust-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none !important">
  <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" id="adjust-backdrop"></div>
  <div class="relative z-10 w-full max-w-sm bg-white rounded-2xl shadow-2xl">
    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
      <h3 class="text-base font-semibold text-slate-900">Adjust Stock</h3>
      <button type="button" id="adjust-close"
              class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition-colors text-lg font-bold">
        &#x2715;
      </button>
    </div>
    <form method="POST" id="adjust-form" action="">
      <?= \App\Core\CSRF::field() ?>
      <input type="hidden" name="location_id" value="<?= (int) $location_id ?>">
      <div class="px-5 py-4 space-y-4">
        <div>
          <p class="text-xs text-slate-500 mb-1"><?= $isMainLocation ? 'Warehouse' : 'Technician / Vehicle' ?></p>
          <p class="text-sm font-semibold text-slate-800"><?= h($locationLabel) ?></p>
        </div>
        <div>
          <p class="text-xs text-slate-500 mb-1">Item</p>
          <p class="text-sm font-medium text-slate-700" id="adjust-item-name"></p>
        </div>
        <div>
          <label for="adjust-qty-input" class="block text-xs font-semibold text-slate-600 mb-1">New Total Quantity</label>
          <input type="number" name="new_qty" id="adjust-qty-input" min="0" step="0.01" required
                 class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-violet-500 transition"
                 placeholder="0.00">
          <p class="mt-1 text-xs text-slate-400">This overwrites the current quantity for this location.</p>
        </div>
      </div>
      <div class="flex items-center gap-3 px-5 py-4 border-t border-slate-100">
        <button type="submit"
                class="inline-flex items-center px-4 py-2 rounded-lg bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold shadow-sm transition-colors">
          Save Adjustment
        </button>
        <button type="button" id="adjust-cancel"
                class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors">
          Cancel
        </button>
      </div>
    </form>
  </div>
</div>

<style>
details[open] > summary .details-chevron { transform: rotate(180deg); }
</style>

<script>
(function () {
  'use strict';

  /* ── Catalog ─────────────────────────────────────────────────────────────── */
  var CATALOG = <?= json_encode(array_values(array_map(function ($i) {
    return [
      'id'   => (int) $i['inventory_id'],
      'name' => $i['name'],
      'fcc'  => $i['fcc_id'] ?? '',
      'part' => $i['part_number'] ?? '',
      'pid'  => $i['product_id'] ?? '',
    ];
  }, $catalog)), JSON_HEX_TAG | JSON_HEX_QUOT) ?>;

  var BULK_URL = '<?= url('inventory/transfer/bulk') ?>';

  /* ── Client-side tree filtering ─────────────────────────────────────────── */
  var searchInput = document.getElementById('live-search');
  var mfrSelect   = document.getElementById('mfr-filter');

  function filterTree() {
    var term = searchInput ? searchInput.value.toLowerCase().trim() : '';
    var mfr  = mfrSelect ? mfrSelect.value : '0';

    document.querySelectorAll('.inventory-row').forEach(function (row) {
      var matchSearch = term === '' ||
        row.dataset.name.includes(term) ||
        row.dataset.fcc.includes(term)  ||
        row.dataset.part.includes(term) ||
        (row.dataset.pid && row.dataset.pid === term);
      var matchMfr = mfr === '0' || row.dataset.mfr === mfr;
      row.style.display = (matchSearch && matchMfr) ? '' : 'none';
    });

    document.querySelectorAll('.mfr-group').forEach(function (grp) {
      var hasVisible = Array.from(grp.querySelectorAll('.inventory-row')).some(function (r) {
        return r.style.display !== 'none';
      });
      grp.style.display = hasVisible ? '' : 'none';
    });

    document.querySelectorAll('.cat-group').forEach(function (grp) {
      var hasVisible = Array.from(grp.querySelectorAll('.mfr-group')).some(function (g) {
        return g.style.display !== 'none';
      });
      grp.style.display = hasVisible ? '' : 'none';
    });
  }

  if (searchInput) searchInput.addEventListener('input', filterTree);
  if (mfrSelect)   mfrSelect.addEventListener('change', filterTree);

  /* ── Location selector ───────────────────────────────────────────────────── */
  var locationSelect = document.getElementById('location-select');
  if (locationSelect) {
    locationSelect.addEventListener('change', function () {
      var u = new URL(window.location.href);
      u.searchParams.set('location_id', this.value);
      var sv = searchInput ? searchInput.value.trim() : '';
      sv !== '' ? u.searchParams.set('search', sv) : u.searchParams.delete('search');
      var mv = mfrSelect ? mfrSelect.value : '0';
      mv !== '0' ? u.searchParams.set('manufacturer_id', mv) : u.searchParams.delete('manufacturer_id');
      window.location.href = u.toString();
    });
  }

  /* ── Bulk Transfer modal ─────────────────────────────────────────────────── */
  var bulkModal   = document.getElementById('bulk-modal');
  var bulkForm    = document.getElementById('bulk-form');
  var bulkSearch  = document.getElementById('bulk-search');
  var bulkResults = document.getElementById('bulk-search-results');
  var bulkTbody   = document.getElementById('bulk-tbody');
  var bulkTable   = document.getElementById('bulk-table');
  var bulkEmpty   = document.getElementById('bulk-empty');
  var bulkError   = document.getElementById('bulk-error');
  var bulkCount   = document.getElementById('bulk-item-count');
  var bulkSubmit  = document.getElementById('bulk-submit');

  var selectedItems = {};

  function openBulk() {
    selectedItems = {};
    renderSelected();
    bulkSearch.value = '';
    bulkResults.innerHTML = '';
    bulkResults.classList.add('hidden');
    hideError();
    showModal(bulkModal);
    bulkSearch.focus();
  }

  function closeBulk() { hideModal(bulkModal); }

  document.getElementById('bulk-transfer-open').addEventListener('click', openBulk);
  document.getElementById('bulk-backdrop').addEventListener('click', closeBulk);
  document.getElementById('bulk-close').addEventListener('click', closeBulk);
  document.getElementById('bulk-cancel').addEventListener('click', closeBulk);

  bulkSearch.addEventListener('input', function () {
    var term = this.value.toLowerCase().trim();
    if (term.length < 1) { bulkResults.classList.add('hidden'); return; }

    var exactPid = CATALOG.filter(function (item) {
      return !selectedItems[item.id] && item.pid && item.pid.toLowerCase() === term;
    });

    var matches = exactPid.length > 0 ? exactPid : CATALOG.filter(function (item) {
      return !selectedItems[item.id] && (
        item.name.toLowerCase().includes(term) ||
        (item.fcc  && item.fcc.toLowerCase().includes(term)) ||
        (item.part && item.part.toLowerCase().includes(term))
      );
    }).slice(0, 12);

    bulkResults.innerHTML = matches.length === 0
      ? '<p class="px-3 py-2 text-xs text-slate-400">No matches found.</p>'
      : matches.map(function (item) {
          return '<button type="button" class="bulk-result-row w-full text-left px-3 py-2 hover:bg-slate-50 transition flex items-center gap-2"' +
                 ' data-id="' + item.id + '" data-name="' + escAttr(item.name) + '">' +
                 (item.pid  ? '<span class="text-xs font-mono font-bold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded flex-shrink-0">' + esc(item.pid) + '</span>' : '') +
                 '<span class="text-sm text-slate-800 font-medium flex-1 truncate">' + esc(item.name) + '</span>' +
                 (item.fcc  ? '<span class="text-xs text-slate-400 font-mono">' + esc(item.fcc)  + '</span>' : '') +
                 (item.part ? '<span class="text-xs text-slate-400 font-mono">' + esc(item.part) + '</span>' : '') +
                 '</button>';
        }).join('');
    bulkResults.classList.remove('hidden');
  });

  bulkResults.addEventListener('click', function (e) {
    var btn = e.target.closest('.bulk-result-row');
    if (!btn) return;
    var id = parseInt(btn.dataset.id, 10);
    selectedItems[id] = { id: id, name: btn.dataset.name, qty: 1 };
    bulkSearch.value = '';
    bulkResults.classList.add('hidden');
    renderSelected();
  });

  document.addEventListener('click', function (e) {
    if (!bulkResults.contains(e.target) && e.target !== bulkSearch) {
      bulkResults.classList.add('hidden');
    }
  });

  function renderSelected() {
    var ids = Object.keys(selectedItems);
    bulkCount.textContent = ids.length > 0 ? ids.length + ' item' + (ids.length > 1 ? 's' : '') : '';
    if (ids.length === 0) {
      bulkTable.classList.add('hidden');
      bulkEmpty.classList.remove('hidden');
      return;
    }
    bulkEmpty.classList.add('hidden');
    bulkTable.classList.remove('hidden');
    bulkTbody.innerHTML = ids.map(function (id) {
      var item = selectedItems[id];
      return '<tr class="border-b border-slate-50 last:border-0">' +
        '<td class="py-2 pr-3 text-sm text-slate-800">' + esc(item.name) + '</td>' +
        '<td class="py-2 pr-2">' +
          '<input type="number" class="bulk-qty-input w-full px-2 py-1 rounded border border-slate-200 text-sm text-center focus:outline-none focus:ring-2 focus:ring-emerald-400"' +
          ' min="0.01" step="0.01" value="' + item.qty + '" data-id="' + id + '">' +
        '</td>' +
        '<td class="py-2">' +
          '<button type="button" class="bulk-remove w-7 h-7 flex items-center justify-center rounded hover:bg-red-50 text-slate-400 hover:text-red-500 transition" data-id="' + id + '">' +
          '&#x2715;</button>' +
        '</td>' +
        '</tr>';
    }).join('');
  }

  bulkTbody.addEventListener('input', function (e) {
    if (e.target.classList.contains('bulk-qty-input')) {
      var id = e.target.dataset.id;
      if (selectedItems[id]) selectedItems[id].qty = parseFloat(e.target.value) || 0;
    }
  });

  bulkTbody.addEventListener('click', function (e) {
    var btn = e.target.closest('.bulk-remove');
    if (!btn) return;
    delete selectedItems[btn.dataset.id];
    renderSelected();
  });

  bulkForm.addEventListener('submit', async function (e) {
    e.preventDefault();
    hideError();
    var fromId = document.getElementById('bulk-from').value;
    var toId   = document.getElementById('bulk-to').value;
    var ids    = Object.keys(selectedItems);
    if (fromId === toId)    { showError('Source and destination must differ.'); return; }
    if (ids.length === 0)   { showError('Add at least one item to transfer.'); return; }

    var fd = new FormData(bulkForm);
    ids.forEach(function (id) {
      if (selectedItems[id].qty > 0) fd.append('items[' + id + '][qty]', selectedItems[id].qty);
    });

    bulkSubmit.disabled = true;
    bulkSubmit.textContent = 'Transferring…';
    try {
      var resp = await fetch(BULK_URL, { method: 'POST', body: fd });
      var data = await resp.json();
      data.success ? window.location.reload() : showError(data.error || 'Transfer failed.');
    } catch (err) {
      showError('Network error. Please try again.');
    } finally {
      bulkSubmit.disabled = false;
      bulkSubmit.textContent = 'Transfer';
    }
  });

  function showError(msg) { bulkError.textContent = msg; bulkError.classList.remove('hidden'); }
  function hideError()    { bulkError.classList.add('hidden'); bulkError.textContent = ''; }

  /* ── Single Item Transfer modal ─────────────────────────────────────────── */
  var transferModal    = document.getElementById('transfer-modal');
  var transferForm     = document.getElementById('transfer-form');
  var transferItemName = document.getElementById('transfer-item-name');
  var transferFrom     = document.getElementById('transfer-from');
  var transferTo       = document.getElementById('transfer-to');
  var transferQtyInput = document.getElementById('transfer-qty-input');
  var transferError    = document.getElementById('transfer-error');
  var invBaseUrl       = '<?= rtrim(url('inventory'), '/') ?>/';

  function openTransfer(btn) {
    var id  = btn.dataset.id;
    var loc = parseInt(btn.dataset.location, 10);
    transferForm.action = invBaseUrl + id + '/transfer';
    transferItemName.textContent = btn.dataset.name;
    transferQtyInput.value = '1';
    transferError.classList.add('hidden');
    for (var i = 0; i < transferFrom.options.length; i++) {
      transferFrom.options[i].selected = (parseInt(transferFrom.options[i].value, 10) === loc);
    }
    var toSet = false;
    for (var j = 0; j < transferTo.options.length; j++) {
      if (parseInt(transferTo.options[j].value, 10) !== loc) {
        transferTo.options[j].selected = true;
        toSet = true;
        break;
      }
    }
    if (!toSet && transferTo.options.length > 0) transferTo.options[0].selected = true;
    showModal(transferModal);
    requestAnimationFrame(function () { transferQtyInput.focus(); transferQtyInput.select(); });
  }

  function closeTransfer() { hideModal(transferModal); }

  document.querySelectorAll('.js-transfer-open').forEach(function (btn) {
    btn.addEventListener('click', function () { openTransfer(btn); });
  });
  document.getElementById('transfer-backdrop').addEventListener('click', closeTransfer);
  document.getElementById('transfer-close').addEventListener('click', closeTransfer);
  document.getElementById('transfer-cancel').addEventListener('click', closeTransfer);

  transferForm.addEventListener('submit', function (e) {
    transferError.classList.add('hidden');
    if (transferFrom.value === transferTo.value) {
      e.preventDefault();
      transferError.textContent = 'Source and destination must differ.';
      transferError.classList.remove('hidden');
    }
  });

  /* ── Adjust Stock modal ─────────────────────────────────────────────────── */
  var adjustModal    = document.getElementById('adjust-modal');
  var adjustForm     = document.getElementById('adjust-form');
  var adjustItemName = document.getElementById('adjust-item-name');
  var adjustQtyInput = document.getElementById('adjust-qty-input');
  var adjustBackdrop = document.getElementById('adjust-backdrop');
  var adjustClose    = document.getElementById('adjust-close');
  var adjustCancel   = document.getElementById('adjust-cancel');
  var invBase        = '<?= rtrim(url('inventory'), '/') ?>/';

  function openAdjust(btn) {
    adjustForm.action = invBase + btn.dataset.id + '/adjust';
    adjustItemName.textContent = btn.dataset.name;
    adjustQtyInput.value = btn.dataset.qty;
    showModal(adjustModal);
    requestAnimationFrame(function () { adjustQtyInput.focus(); adjustQtyInput.select(); });
  }

  function closeAdjust() { hideModal(adjustModal); }

  document.querySelectorAll('.js-adjust-open').forEach(function (btn) {
    btn.addEventListener('click', function () { openAdjust(btn); });
  });
  if (adjustBackdrop) adjustBackdrop.addEventListener('click', closeAdjust);
  if (adjustClose)    adjustClose.addEventListener('click', closeAdjust);
  if (adjustCancel)   adjustCancel.addEventListener('click', closeAdjust);

  /* ── Shared helpers ─────────────────────────────────────────────────────── */
  function showModal(el) { el.style.removeProperty('display'); el.style.display = 'flex'; }
  function hideModal(el) { el.style.display = 'none'; }

  function esc(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }
  function escAttr(str) {
    return String(str).replace(/"/g,'&quot;').replace(/'/g,'&#39;');
  }

  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    if (bulkModal     && bulkModal.style.display     === 'flex') closeBulk();
    if (transferModal && transferModal.style.display === 'flex') closeTransfer();
    if (adjustModal   && adjustModal.style.display   === 'flex') closeAdjust();
  });

}());
</script>
