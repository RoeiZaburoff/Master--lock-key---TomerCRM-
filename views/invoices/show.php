<?php
$_inv_addr_parts = array_filter([
    $invoice['address_line1'] ?? '',
    $invoice['address_line2'] ?? '',
    $invoice['city'] ?? '',
    $invoice['state'] ?? '',
    $invoice['zip'] ?? '',
]);
$_inv_addr_full = implode(', ', $_inv_addr_parts);
?>

<!-- Page header -->
<div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
  <h1 class="text-xl font-bold text-slate-900">Invoice <?= h($invoice['invoice_number']) ?></h1>
  <div class="flex items-center gap-2 flex-wrap">
    <?php if (!empty($__is_admin)): ?>
    <a href="<?= url('invoices/' . $invoice['invoice_id'] . '/edit') ?>"
       class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium shadow-sm transition-colors duration-150 no-underline">
      Edit Invoice
    </a>
    <?php endif; ?>
    <a href="<?= url('invoices/' . $invoice['invoice_id'] . '/pdf') ?>" target="_blank"
       class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium shadow-sm transition-colors duration-150 no-underline">
      Download PDF
    </a>
    <a href="<?= url('leads/' . $invoice['job_id']) ?>"
       class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition-colors duration-150 no-underline">
      View Lead
    </a>
  </div>
</div>

<!-- Invoice document -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm max-w-2xl">
  <div class="px-8 py-8">

    <!-- Header: company + invoice meta -->
    <div class="flex items-start justify-between gap-6 mb-8 flex-wrap">
      <div>
        <?php if (!empty($invoice['company_logo'])): ?>
          <img src="<?= url('storage/logos/' . h($invoice['company_logo'])) ?>" alt=""
               class="h-11 w-auto max-w-[160px] object-contain block mb-2">
        <?php endif; ?>
        <div class="text-base font-bold text-slate-900">
          <?= !empty($invoice['company_name']) ? h($invoice['company_name']) : h(APP_NAME) ?>
        </div>
        <?php if (!empty($invoice['company_address'])): ?>
          <div class="text-xs text-slate-500 mt-0.5"><?= h($invoice['company_address']) ?></div>
        <?php endif; ?>
        <?php if (!empty($invoice['company_phone'])): ?>
          <div class="text-xs text-slate-500 mt-0.5">
            <a href="tel:<?= h(phone_digits($invoice['company_phone'])) ?>" class="text-slate-500 no-underline hover:underline"><?= h(format_phone($invoice['company_phone'])) ?></a>
          </div>
        <?php endif; ?>
        <?php if (!empty($invoice['company_email'])): ?>
          <div class="text-xs text-slate-500 mt-0.5"><?= h($invoice['company_email']) ?></div>
        <?php endif; ?>
      </div>
      <div class="text-right">
        <div class="text-lg font-bold text-slate-900"><?= h($invoice['invoice_number']) ?></div>
        <div class="text-xs text-slate-500 mt-1">Issued: <?= h($invoice['issued_date']) ?></div>
        <div class="text-xs text-slate-500 mt-0.5">Due: <?= h($invoice['due_date'] ?? 'Upon Receipt') ?></div>
      </div>
    </div>

    <!-- Bill to -->
    <div class="mb-8 text-sm leading-relaxed">
      <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Bill To</div>
      <div class="font-semibold text-slate-900"><?= h($invoice['customer_name']) ?></div>
      <?php if ($_inv_addr_full): ?>
        <div class="text-slate-600"><?= address_maps_link($_inv_addr_full) ?></div>
      <?php endif; ?>
      <div class="text-slate-600">
        <a href="tel:<?= h(phone_digits($invoice['customer_phone'])) ?>" class="text-slate-600 no-underline hover:underline"><?= h(format_phone($invoice['customer_phone'])) ?></a>
      </div>
    </div>

    <!-- Line items -->
    <div class="table-scroll mb-6">
      <table class="table invoice-lines table-raw w-full">
        <thead>
          <tr>
            <th>Description</th>
            <th style="text-align:right;">Amount</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($invoice['items'])): ?>
            <?php foreach ($invoice['items'] as $item): ?>
              <tr>
                <td><?= h($item['description']) ?></td>
                <td style="text-align:right;"><?= money($item['amount']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td><?= !empty($invoice['job_description']) ? h($invoice['job_description']) : '—' ?></td>
              <td style="text-align:right;"><?= money($invoice['subtotal']) ?></td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Totals -->
    <div class="ml-auto max-w-xs">
      <div class="flex justify-between items-center py-2 border-b border-slate-100 text-sm">
        <span class="text-slate-500">Subtotal</span>
        <span class="font-medium text-slate-900"><?= money($invoice['subtotal']) ?></span>
      </div>
      <?php if ((float) $invoice['tax_amount'] > 0): ?>
      <div class="flex justify-between items-center py-2 border-b border-slate-100 text-sm">
        <span class="text-slate-500">Tax (<?= round((float) $invoice['tax_rate'] * 100, 2) ?>%)</span>
        <span class="font-medium text-slate-900"><?= money($invoice['tax_amount']) ?></span>
      </div>
      <?php endif; ?>
      <div class="flex justify-between items-center py-2.5 text-sm font-bold text-slate-900">
        <span>Total</span>
        <span><?= money($invoice['total']) ?></span>
      </div>
      <?php if (!empty($invoice['deposit_amount']) && (float) $invoice['deposit_amount'] > 0):
        $balance = (float) $invoice['total'] - (float) $invoice['deposit_amount']; ?>
      <div class="flex justify-between items-center py-2 border-t border-slate-100 text-sm text-emerald-700">
        <span>Deposit Paid</span>
        <span>−<?= money($invoice['deposit_amount']) ?></span>
      </div>
      <div class="flex justify-between items-center py-2.5 border-t-2 border-slate-200 text-sm font-bold text-slate-900">
        <span>Balance Due</span>
        <span><?= money(max(0, $balance)) ?></span>
      </div>
      <?php endif; ?>
    </div>

    <!-- Payment + Notes -->
    <?php if (!empty($invoice['payment_method'])): ?>
    <div class="mt-6 text-xs text-slate-500">
      <strong class="text-slate-700">Payment:</strong>
      <?php
        $pm = match($invoice['payment_method']) {
          'credit_card' => 'Credit Card',
          'check'       => 'Check',
          'cash'        => 'Cash',
          default       => h($invoice['payment_method']),
        };
        echo $pm;
        if (!empty($invoice['payment_reference'])):
          $refLabel = $invoice['payment_method'] === 'check' ? 'Check #' : 'Card ending ';
          echo ' — ' . $refLabel . h($invoice['payment_reference']);
        endif;
      ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($invoice['notes'])): ?>
    <div class="mt-3 text-xs text-slate-500">
      <strong class="text-slate-700">Notes:</strong> <?= h($invoice['notes']) ?>
    </div>
    <?php endif; ?>

  </div>
</div>
