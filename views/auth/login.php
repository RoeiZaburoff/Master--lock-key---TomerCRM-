<div class="bg-white rounded-2xl shadow-2xl shadow-black/30 px-8 py-10 w-full">

  <div class="mb-7">
    <h1 class="text-xl font-bold text-slate-900"><?= h(APP_NAME) ?></h1>
    <p class="text-sm text-slate-500 mt-1">Sign in to continue</p>
  </div>

  <?php if (!empty($errors['login'])): ?>
    <div class="mb-5 flex items-start gap-3 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">
      <svg class="mt-0.5 h-4 w-4 shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-9a1 1 0 012 0v4a1 1 0 01-2 0V9zm1-4a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"/>
      </svg>
      <?= h($errors['login']) ?>
    </div>
  <?php endif; ?>

  <form method="POST" action="<?= url('login') ?>" class="space-y-5">
    <?= \App\Core\CSRF::field() ?>

    <div class="flex flex-col gap-1.5">
      <label for="username" class="text-sm font-medium text-slate-700">Username</label>
      <input type="text" id="username" name="username"
             value="<?= h($old_username ?? '') ?>"
             required autofocus
             class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm text-slate-900 bg-white
                    focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                    placeholder:text-slate-400 transition">
      <?php if (!empty($errors['username'])): ?>
        <span class="text-xs text-red-600"><?= h($errors['username']) ?></span>
      <?php endif; ?>
    </div>

    <div class="flex flex-col gap-1.5">
      <label for="password" class="text-sm font-medium text-slate-700">Password</label>
      <input type="password" id="password" name="password"
             required
             class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm text-slate-900 bg-white
                    focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                    placeholder:text-slate-400 transition">
      <?php if (!empty($errors['password'])): ?>
        <span class="text-xs text-red-600"><?= h($errors['password']) ?></span>
      <?php endif; ?>
    </div>

    <button type="submit"
            class="w-full flex items-center justify-center px-4 py-2.5 rounded-lg
                   bg-blue-600 hover:bg-blue-700 active:bg-blue-800
                   text-white text-sm font-semibold
                   focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                   transition-colors duration-150">
      Sign In
    </button>

  </form>
</div>
