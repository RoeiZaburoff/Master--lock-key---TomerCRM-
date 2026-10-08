<?php
$_inp = 'w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition';
$_sel = 'w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition';
$_lbl = 'block text-sm font-medium text-slate-700 mb-1.5';
?>

<div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
  <h1 class="text-xl font-bold text-slate-900">Create Invoice — Lead #<?= (int) $job['job_id'] ?></h1>
  <a href="<?= url('leads/' . $job['job_id']) ?>"
     class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">
    ← Back to Lead
  </a>
</div>

<?php if ($existing): ?>
  <div class="alert alert-warning mb-4">An invoice already exists for this lead: <a href="<?= url('invoices/' . $existing['invoice_id']) ?>"><?= h($existing['invoice_number']) ?></a></div>
<?php endif; ?>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm max-w-2xl">
  <form method="POST" action="<?= url('invoices') ?>" id="invoice-form">
    <?= \App\Core\CSRF::field() ?>
    <input type="hidden" name="job_id" value="<?= (int) $job['job_id'] ?>">

    <div class="px-5 pt-5 pb-3 border-b border-slate-100 text-sm text-slate-600">
      <span class="font-semibold text-slate-900"><?= h($job['customer_name']) ?></span>
      <?php if (!empty($job['company_name'])): ?>
        <span class="text-slate-400 mx-1.5">·</span>
        <span><?= h($job['company_name']) ?></span>
      <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 px-5 pt-5">
      <div>
        <label class="<?= $_lbl ?>">Invoice Number</label>
        <input type="text" value="<?= h($next_number) ?>" disabled
               class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-400 bg-slate-50">
        <p class="mt-1 text-xs text-slate-400">Auto-generated</p>
      </div>
      <div>
        <label class="<?= $_lbl ?>">Issue Date *</label>
        <input type="date" name="issued_date" value="<?= date('Y-m-d') ?>" required class="<?= $_inp ?>">
      </div>
      <div>
        <label class="<?= $_lbl ?>">Due Date</label>
        <input type="date" name="due_date" class="<?= $_inp ?>">
      </div>
    </div>

    <!-- Line Items -->
    <div class="px-5 pt-5">
      <div class="text-sm font-semibold text-slate-700 mb-2">Line Items</div>
      <div class="border border-slate-200 rounded-lg overflow-hidden">
        <table style="width:100%;border-collapse:collapse;" id="items-table">
          <thead>
            <tr style="background:#f8fafc;">
              <th style="text-align:left;padding:8px 10px;font-size:.88em;color:#64748b;font-weight:600;width:75%;border-bottom:1px solid #e2e8f0;">Description</th>
              <th style="text-align:right;padding:8px 10px;font-size:.88em;color:#64748b;font-weight:600;width:18%;border-bottom:1px solid #e2e8f0;">Amount ($)</th>
              <th style="width:7%;border-bottom:1px solid #e2e8f0;"></th>
            </tr>
          </thead>
          <tbody id="items-body">
            <tr class="item-row" data-idx="0">
              <td style="padding:6px 4px 6px 10px;">
                <input type="text" name="items[0][description]" placeholder="Describe the service or work performed…" style="width:100%;" required>
              </td>
              <td style="padding:6px 4px;">
                <input type="number" name="items[0][amount]" step="0.01" min="0" placeholder="0.00" style="width:100%;text-align:right;" class="item-amount" required>
              </td>
              <td style="padding:6px 8px 6px 4px;text-align:center;">
                <button type="button" class="btn btn-sm btn-danger remove-row" title="Remove">×</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <button type="button" id="add-row"
              class="mt-2 inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150">
        + Add Line Item
      </button>
    </div>

    <!-- Subtotal -->
    <div class="flex justify-end px-5 pt-4">
      <div class="text-right">
        <div class="text-xs text-slate-500 mb-0.5">Subtotal</div>
        <div class="text-base font-semibold text-slate-900" id="subtotal-display">$0.00</div>
      </div>
    </div>

    <!-- Tax + Total -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 px-5 pt-4">
      <div>
        <label class="<?= $_lbl ?>">Tax Rate <span class="text-slate-400 font-normal">(decimal — e.g. 8.75% = 0.0875)</span></label>
        <input type="number" name="tax_rate" id="tax-rate" value="0" step="0.0001" min="0" max="1" class="<?= $_inp ?>">
      </div>
      <div class="flex flex-col justify-end">
        <div class="text-right pb-1">
          <span class="text-xs text-slate-500">Total (incl. tax)&nbsp;&nbsp;</span>
          <strong class="text-base text-slate-900" id="total-display">$0.00</strong>
        </div>
      </div>
    </div>

    <!-- Deposit & Balance -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 px-5 pt-4">
      <div>
        <label class="<?= $_lbl ?>">Deposit Paid <span class="text-slate-400 font-normal">(optional)</span></label>
        <input type="number" name="deposit_amount" id="deposit-amount" step="0.01" min="0" placeholder="0.00" class="<?= $_inp ?>">
      </div>
      <div class="flex flex-col justify-end">
        <div class="text-right pb-1">
          <span class="text-xs text-slate-500">Remaining Balance&nbsp;&nbsp;</span>
          <strong class="text-base text-slate-900" id="balance-display">$0.00</strong>
        </div>
      </div>
    </div>

    <!-- Payment Method -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 px-5 pt-4">
      <div>
        <label class="<?= $_lbl ?>">Payment Method</label>
        <select name="payment_method" id="payment-method" class="<?= $_sel ?>">
          <option value="">— None —</option>
          <option value="credit_card">Credit Card</option>
          <option value="check">Check</option>
          <option value="cash">Cash</option>
          <option value="zelle">Zelle</option>
          <option value="cash_app">Cash App</option>
        </select>
      </div>
      <div id="payment-ref-group" style="display:none;">
        <label class="<?= $_lbl ?>" id="payment-ref-label">Last 4 Digits</label>
        <input type="text" name="payment_reference" id="payment-reference" maxlength="50" placeholder="e.g. 4242 or 1023" class="<?= $_inp ?>">
      </div>
    </div>

    <!-- Notes -->
    <div class="px-5 pt-4">
      <label class="<?= $_lbl ?>">Notes</label>
      <textarea name="notes" rows="3" placeholder="Payment instructions, thank you note, etc."
                class="<?= $_inp ?> resize-y"></textarea>
    </div>

    <div class="flex items-center gap-3 px-5 py-5 mt-2 border-t border-slate-100">
      <button type="submit"
              class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150">
        Create Invoice
      </button>
      <a href="<?= url('leads/' . $job['job_id']) ?>"
         class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">
        Cancel
      </a>
    </div>
  </form>
</div>

<script>
(function () {
  var idx = 1;

  function recalc() {
    var amounts = document.querySelectorAll('.item-amount');
    var sub = 0;
    amounts.forEach(function (el) { sub += parseFloat(el.value) || 0; });
    var rate    = parseFloat(document.getElementById('tax-rate').value) || 0;
    var total   = sub + (sub * rate);
    var deposit = parseFloat(document.getElementById('deposit-amount').value) || 0;
    var balance = Math.max(0, total - deposit);
    document.getElementById('subtotal-display').textContent = '$' + sub.toFixed(2);
    document.getElementById('total-display').textContent    = '$' + total.toFixed(2);
    document.getElementById('balance-display').textContent  = '$' + balance.toFixed(2);
  }

  document.getElementById('items-body').addEventListener('input', function (e) {
    if (e.target.classList.contains('item-amount')) recalc();
  });
  document.getElementById('tax-rate').addEventListener('input', recalc);
  document.getElementById('deposit-amount').addEventListener('input', recalc);

  document.getElementById('add-row').addEventListener('click', function () {
    var row = document.createElement('tr');
    row.className = 'item-row';
    row.setAttribute('data-idx', idx);
    row.innerHTML =
      '<td style="padding:6px 4px 6px 0;">' +
        '<input type="text" name="items[' + idx + '][description]" placeholder="Describe the service or work performed…" style="width:100%;" required>' +
      '</td>' +
      '<td style="padding:6px 4px;">' +
        '<input type="number" name="items[' + idx + '][amount]" step="0.01" min="0" placeholder="0.00" style="width:100%;text-align:right;" class="item-amount" required>' +
      '</td>' +
      '<td style="padding:6px 0 6px 4px;text-align:center;">' +
        '<button type="button" class="btn btn-sm btn-danger remove-row" title="Remove">×</button>' +
      '</td>';
    document.getElementById('items-body').appendChild(row);
    idx++;
  });

  document.getElementById('items-body').addEventListener('click', function (e) {
    if (e.target.classList.contains('remove-row')) {
      var rows = document.querySelectorAll('#items-body .item-row');
      if (rows.length > 1) {
        e.target.closest('tr').remove();
        recalc();
      }
    }
  });

  // Payment method toggle
  var pmSelect = document.getElementById('payment-method');
  var pmGroup  = document.getElementById('payment-ref-group');
  var pmLabel  = document.getElementById('payment-ref-label');
  pmSelect.addEventListener('change', function () {
    var val = pmSelect.value;
    if (val === 'credit_card' || val === 'check') {
      pmGroup.style.display = '';
      pmLabel.textContent = val === 'check' ? 'Check Number' : 'Last 4 Digits';
    } else {
      pmGroup.style.display = 'none';
    }
  });

  recalc();
})();
</script>
