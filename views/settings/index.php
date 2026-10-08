<?php
$_inp = 'w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition';
$_sel = 'w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition';
$_lbl = 'block text-sm font-medium text-slate-700 mb-1.5';
$_tab = $tab ?? 'companies';
?>

<?php if (!empty($migration_needed)): ?>
<div class="alert alert-error mb-4">
  Database migration required. Run <strong>docs/COMPANIES_MIGRATION.sql</strong>, then <strong>docs/SETTINGS_INVOICE_MIGRATION.sql</strong>, then <strong>docs/FINANCIAL_RBAC_MIGRATION.sql</strong>.
</div>
<?php endif; ?>

<div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
  <h1 class="text-xl font-bold text-slate-900">Settings</h1>
</div>

<!-- Tab bar -->
<div class="flex items-center gap-1 border-b border-slate-200 mb-6">
  <?php
    $tabs = ['companies' => 'Companies', 'users' => 'Users &amp; Security', 'general' => 'General', 'backup' => 'Backup &amp; Restore'];
    foreach ($tabs as $key => $label):
      $active = $_tab === $key;
  ?>
  <a href="<?= url('settings?tab=' . $key) ?>"
     class="inline-flex items-center px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors duration-150 no-underline
            <?= $active ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' ?>">
    <?= $label ?>
  </a>
  <?php endforeach; ?>
</div>

<?php if ($_tab === 'backup'): ?>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

  <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
    <div class="px-5 py-3.5 border-b border-slate-100">
      <h2 class="text-sm font-semibold text-slate-700">Full System Backup</h2>
    </div>
    <div class="px-5 py-4">
      <p class="text-sm text-slate-500 mb-4">Downloads a <code class="bg-slate-100 px-1 rounded text-xs">.zip</code> archive containing a uploaded storage files and a database dump, without server configuration or credentials.</p>
      <a href="<?= url('settings/backup/download') ?>"
         class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150 no-underline">
        Download Full System Backup
      </a>
    </div>
  </div>

  <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
    <div class="px-5 py-3.5 border-b border-slate-100">
      <h2 class="text-sm font-semibold text-slate-700">Import Database Backup</h2>
    </div>
    <div class="px-5 py-4">
      <div class="mb-4 px-3.5 py-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
        <strong>Warning:</strong> This will overwrite all current data. This action cannot be undone.
      </div>
      <p class="text-sm text-slate-600">Restore a reviewed backup through your hosting database tools or the MySQL CLI. Test it in a separate database first. See the deployment guide.</p>
    </div>
  </div>

</div>

<?php elseif ($_tab === 'general'): ?>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm max-w-md">
  <div class="px-5 py-3.5 border-b border-slate-100">
    <h2 class="text-sm font-semibold text-slate-700">Financial Settings</h2>
  </div>
  <form method="POST" action="<?= url('settings/general') ?>">
    <?= \App\Core\CSRF::field() ?>
    <div class="px-5 pt-5">
      <label class="<?= $_lbl ?>">Credit Card Fee (%)</label>
      <input type="number" step="0.01" min="0" max="100" name="cc_fee_percentage"
             value="<?= number_format((float) ($cc_fee_percentage ?? 0), 2) ?>" class="<?= $_inp ?>">
      <p class="mt-1.5 text-xs text-slate-400">Applied to Provider Profit only when payment method is Credit Card.</p>
    </div>
    <div class="px-5 py-4 mt-2 border-t border-slate-100">
      <button type="submit"
              class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150">
        Save
      </button>
    </div>
  </form>
</div>

<?php elseif ($_tab === 'companies'): ?>

<div class="flex justify-end mb-4">
  <a href="<?= url('settings/companies/create') ?>"
     class="inline-flex items-center px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150 no-underline">
    + Add Company
  </a>
</div>

<?php if (empty($companies)): ?>
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
    <div class="py-16 text-center text-sm text-slate-400 px-6">No companies configured. Add your first company to get started.</div>
  </div>
<?php else: ?>
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-x-auto">
    <table class="table w-full">
      <thead>
        <tr>
          <th style="width:60px;">Logo</th>
          <th>Company Name</th>
          <th>Phone</th>
          <th>Email</th>
          <th>Address</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($companies as $co): ?>
          <tr>
            <td>
              <?php if (!empty($co['logo_path'])): ?>
                <img src="<?= url('storage/logos/' . h($co['logo_path'])) ?>" alt="" style="height:32px;width:auto;object-fit:contain;">
              <?php else: ?>
                <span class="text-slate-300 text-sm">—</span>
              <?php endif; ?>
            </td>
            <td class="font-medium"><?= h($co['name']) ?></td>
            <td><?= h($co['phone'] ?? '—') ?></td>
            <td><?= h($co['email'] ?? '—') ?></td>
            <td><?= h($co['address'] ?? '—') ?></td>
            <td><span class="badge <?= $co['is_active'] ? 'badge-active' : 'badge-inactive' ?>"><?= $co['is_active'] ? 'Active' : 'Inactive' ?></span></td>
            <td class="actions">
              <a href="<?= url('settings/companies/' . $co['company_id'] . '/edit') ?>" class="btn btn-sm">Edit</a>
              <form method="POST" action="<?= url('settings/companies/' . $co['company_id'] . '/delete') ?>" style="display:inline;"
                    onsubmit="return confirm('Delete this company?');">
                <?= \App\Core\CSRF::field() ?>
                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php else: /* users tab */ ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

  <!-- System Users -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
    <div class="px-5 py-3.5 border-b border-slate-100">
      <h2 class="text-sm font-semibold text-slate-700">System Users</h2>
    </div>
    <div class="overflow-x-auto">
      <table class="table w-full">
        <thead>
          <tr><th>Username</th><th>Role</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
          <tr>
            <td>
              <?= h($u['username']) ?>
              <?= $u['user_id'] == $current_user_id ? '<span class="badge badge-active ml-1" style="font-size:.75em;">You</span>' : '' ?>
            </td>
            <td>
              <?php $uRole = $u['role'] ?? 'admin'; ?>
              <span class="badge <?= $uRole === 'admin' ? 'badge-active' : 'badge-inactive' ?>"><?= ucfirst(h($uRole)) ?></span>
            </td>
            <td class="actions">
              <?php if ($u['user_id'] != $current_user_id): ?>
              <form method="POST" action="<?= url('settings/users/' . $u['user_id'] . '/delete') ?>" style="display:inline;"
                    onsubmit="return confirm('Delete user <?= h($u['username']) ?>?');">
                <?= \App\Core\CSRF::field() ?>
                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="px-5 pt-4 pb-5 border-t border-slate-100">
      <h3 class="text-sm font-semibold text-slate-700 mb-3">Add New User</h3>
      <form method="POST" action="<?= url('settings/users') ?>">
        <?= \App\Core\CSRF::field() ?>
        <div class="grid grid-cols-2 gap-3 mb-3">
          <div>
            <label class="<?= $_lbl ?>">Username *</label>
            <input type="text" name="new_username" value="" autocomplete="off" required class="<?= $_inp ?>">
            <?php if (!empty($user_errors['new_username'])): ?><p class="mt-1 text-xs text-red-600"><?= h($user_errors['new_username']) ?></p><?php endif; ?>
          </div>
          <div>
            <label class="<?= $_lbl ?>">Role *</label>
            <select name="new_role" class="<?= $_sel ?>">
              <option value="user">User</option>
              <option value="admin">Admin</option>
            </select>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3 mb-3">
          <div>
            <label class="<?= $_lbl ?>">Password *</label>
            <input type="password" name="new_password" autocomplete="new-password" required class="<?= $_inp ?>">
            <?php if (!empty($user_errors['new_password'])): ?><p class="mt-1 text-xs text-red-600"><?= h($user_errors['new_password']) ?></p><?php endif; ?>
          </div>
          <div>
            <label class="<?= $_lbl ?>">Confirm Password *</label>
            <input type="password" name="new_password_confirm" autocomplete="new-password" required class="<?= $_inp ?>">
            <?php if (!empty($user_errors['new_password_confirm'])): ?><p class="mt-1 text-xs text-red-600"><?= h($user_errors['new_password_confirm']) ?></p><?php endif; ?>
          </div>
        </div>
        <div class="mb-3 px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-500">
          <strong>Roles:</strong> Admin has full system access. User can create leads and close (complete) jobs — no deletes, no settings, no reports.
        </div>
        <button type="submit"
                class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150">
          Create User
        </button>
      </form>
    </div>
  </div>

  <!-- Change Password -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
    <div class="px-5 py-3.5 border-b border-slate-100">
      <h2 class="text-sm font-semibold text-slate-700">Change Password</h2>
    </div>
    <div class="px-5 py-4">
      <form method="POST" action="<?= url('settings/password') ?>">
        <?= \App\Core\CSRF::field() ?>
        <div class="mb-3">
          <label class="<?= $_lbl ?>">Current Password *</label>
          <input type="password" name="current_password" autocomplete="current-password" required class="<?= $_inp ?>">
          <?php if (!empty($pw_errors['current_password'])): ?><p class="mt-1 text-xs text-red-600"><?= h($pw_errors['current_password']) ?></p><?php endif; ?>
        </div>
        <div class="mb-3">
          <label class="<?= $_lbl ?>">New Password *</label>
          <input type="password" name="new_password" autocomplete="new-password" required class="<?= $_inp ?>">
          <?php if (!empty($pw_errors['new_password'])): ?><p class="mt-1 text-xs text-red-600"><?= h($pw_errors['new_password']) ?></p><?php endif; ?>
        </div>
        <div class="mb-4">
          <label class="<?= $_lbl ?>">Confirm New Password *</label>
          <input type="password" name="confirm_password" autocomplete="new-password" required class="<?= $_inp ?>">
          <?php if (!empty($pw_errors['confirm_password'])): ?><p class="mt-1 text-xs text-red-600"><?= h($pw_errors['confirm_password']) ?></p><?php endif; ?>
        </div>
        <button type="submit"
                class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition-colors duration-150">
          Update Password
        </button>
      </form>
    </div>
  </div>

</div>

<?php endif; ?>
