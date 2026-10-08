<h1 class="text-xl font-bold mb-4">Review inquiry #<?= (int)$inquiry['inquiry_id'] ?></h1>
<p class="mb-4">Company: <?= h($inquiry['company_name']) ?>. Suggested urgency: <?= h($inquiry['priority_hint']) ?>. Confirm contact details and the service address before conversion.</p>
<?php foreach ($errors as $messages): foreach ($messages as $message): ?><p class="text-red-700"><?= h($message) ?></p><?php endforeach; endforeach; ?>
<?php if ($inquiry['status']==='promoted'): ?>
<a class="text-blue-700" href="<?= url('leads/'.$inquiry['job_id']) ?>">Open created lead</a>
<?php else: ?>
<form method="POST" action="<?= url('intake/'.$inquiry['inquiry_id'].'/promote') ?>" class="bg-white rounded-lg p-5 max-w-2xl">
<?= \App\Core\CSRF::field() ?>
<label class="block mb-4">Customer: choose an existing customer to avoid duplicates, or create a new one.
<select name="existing_customer_id" class="block border rounded w-full p-2"><option value="">Create new customer</option><?php foreach ($customers as $customer): ?><option value="<?= (int)$customer['customer_id'] ?>"><?= h($customer['full_name'].' — '.$customer['phone']) ?></option><?php endforeach; ?></select></label>
<?php foreach (['full_name'=>'Name','phone'=>'Phone','email'=>'Email','address'=>'Service address','city'=>'City','state'=>'State','zip'=>'ZIP'] as $key=>$label): ?>
<label class="block mb-3"><?= h($label) ?><input class="block border rounded w-full p-2" type="<?= $key==='email'?'email':'text' ?>" name="<?= $key ?>" value="<?= h($contact[$key] ?? '') ?>" <?= $key==='email'?'':'required' ?>></label>
<?php endforeach; ?>
<label class="block mb-3">Description<textarea class="block border rounded w-full p-2" name="description" rows="4"><?= h($contact['description'] ?? '') ?></textarea></label>
<label class="block mb-4">Confirmed priority<select class="block border rounded p-2" name="priority"><?php foreach (['low','normal','high','urgent'] as $p): ?><option <?= $p==='normal'?'selected':'' ?>><?= h($p) ?></option><?php endforeach; ?></select></label>
<button class="bg-blue-600 text-white rounded px-4 py-2">Approve and create lead</button>
</form>
<?php endif; ?>
