<h1 class="text-xl font-bold mb-4">Website &amp; Calls</h1>
<p class="mb-4 text-sm text-slate-600">New requests require review. Voice integration is <?= \App\Core\RuntimeConfig::enabled('RETELL_ENABLED')?'enabled':'disabled' ?>. Website integration is <?= \App\Core\RuntimeConfig::enabled('WEBSITE_ENABLED')?'enabled':'disabled' ?>.</p>
<?php if ($failed): ?><p class="text-red-700 mb-4">Failed queue events: <?= $failed ?>. Ask the administrator to inspect the queue before retrying.</p><?php endif; ?>
<div class="bg-white rounded-lg p-4 mb-6">
<h2 class="font-semibold mb-3">Inquiries (latest 100)</h2>
<?php foreach ($inquiries as $i): $c=json_decode($i['contact'],true); ?>
<a class="block border-b py-3 text-blue-700" href="<?= url('intake/'.$i['inquiry_id']) ?>">#<?= (int)$i['inquiry_id'] ?> — <?= h($c['full_name'] ?: 'Needs identification') ?> — <?= h($i['company_name']) ?> — <?= h($i['source']) ?> — <?= h($i['status']) ?></a>
<?php endforeach; ?>
<?php if (!$inquiries): ?><p>No inquiries yet.</p><?php endif; ?>
</div>
<div class="bg-white rounded-lg p-4"><h2 class="font-semibold mb-3">Calls (latest 50)</h2>
<?php foreach ($calls as $call): ?><div class="border-b py-3"><p><?= h($call['company_name']) ?> — <?= h($call['from_number']) ?> — <?= h($call['status']) ?></p><p class="text-sm whitespace-pre-wrap"><?= h($call['summary']) ?></p></div><?php endforeach; ?>
<?php if (!$calls): ?><p>No calls have been collected.</p><?php endif; ?></div>
