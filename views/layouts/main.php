<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= asset('css/tailwind.css') ?>">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<style>
  /* Sidebar mobile slide-in */
  @media (max-width: 767px) {
    #sidebar { transform: translateX(-100%); transition: transform 0.25s ease; }
    #sidebar.is-open { transform: translateX(0); }
  }
  /* Overlay — hidden until JS adds .is-open */
  #nav-overlay { display: none; }
  #nav-overlay.is-open { display: block; }
</style>
</head>
<body class="h-full bg-slate-50 text-slate-800 antialiased">

<?php
  $uri     = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
  $base    = defined('APP_BASE') ? rtrim(APP_BASE, '/') : '';
  $isDash  = ($uri === $base . '/' || $uri === $base || $uri === $base . '');
  $navLink = function(string $href, string $label, bool $active): string {
    $cls = $active
      ? 'flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-semibold bg-slate-700 text-white'
      : 'flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition-colors duration-150';
    return '<a href="' . $href . '" class="' . $cls . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
  };
?>

<!-- Mobile top bar -->
<header class="md:hidden fixed top-0 inset-x-0 z-30 h-14 bg-slate-900 flex items-center justify-between px-4 shadow-md">
  <button id="nav-toggle" aria-label="Open menu"
          class="flex flex-col justify-center items-center w-10 h-10 gap-[5px] shrink-0 focus:outline-none">
    <span class="block w-5 h-0.5 bg-white rounded-full"></span>
    <span class="block w-5 h-0.5 bg-white rounded-full"></span>
    <span class="block w-5 h-0.5 bg-white rounded-full"></span>
  </button>
  <span class="text-white font-bold text-sm tracking-tight"><?= h(APP_NAME) ?></span>
  <div class="w-10 shrink-0"></div>
</header>

<!-- Mobile overlay -->
<div id="nav-overlay" class="fixed inset-0 bg-black/50 z-40"></div>

<!-- Sidebar -->
<aside id="sidebar"
       class="fixed inset-y-0 left-0 z-50 w-56 bg-slate-900 flex flex-col overflow-y-auto">

  <!-- Brand -->
  <div class="flex items-center h-16 px-4 border-b border-slate-700/60 shrink-0">
    <span class="text-white font-bold text-sm leading-snug tracking-tight"><?= h(APP_NAME) ?></span>
  </div>

  <!-- Nav -->
  <nav class="flex-1 px-2 py-3 flex flex-col gap-0.5">

    <?= $navLink(url(),            'Dashboard',    $isDash) ?>
    <?= $navLink(url('leads'),     'Leads',        !$isDash && str_contains($uri, '/leads')) ?>
    <?= $navLink(url('calendar'),  'Calendar',     str_contains($uri, '/calendar')) ?>
    <?= $navLink(url('customers'), 'Customers',    str_contains($uri, '/customers')) ?>
    <?= $navLink(url('invoices'),  'Invoices',     str_contains($uri, '/invoices')) ?>

    <?php if (!empty($__is_admin)): ?>
      <?= $navLink(url('reports'), 'Reports',      str_contains($uri, '/reports')) ?>
    <?php endif; ?>

    <div class="my-2 border-t border-slate-700/60"></div>

    <?php if (!empty($__is_admin)): ?>
      <?= $navLink(url('lead-sources'), 'Lead Sources', str_contains($uri, '/lead-sources')) ?>
      <?= $navLink(url('contractors'),  'Contractors',  str_contains($uri, '/contractors')) ?>
      <?= $navLink(url('inventory'),     'Inventory',    str_contains($uri, '/inventory')) ?>
      <div class="my-2 border-t border-slate-700/60"></div>
      <?= $navLink(url('intake'), 'Website & Calls', str_contains($uri, '/intake')) ?>
      <?= $navLink(url('settings'),     'Settings',     str_contains($uri, '/settings')) ?>
    <?php endif; ?>

    <a href="<?= url('logout') ?>"
       class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-slate-400 hover:bg-slate-800 hover:text-white transition-colors duration-150 mt-auto">
      Logout
    </a>

  </nav>

  <!-- Role pill -->
  <div class="px-4 py-3 border-t border-slate-700/60 shrink-0">
    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                 <?= !empty($__is_admin) ? 'bg-blue-900/60 text-blue-300' : 'bg-slate-700 text-slate-300' ?>">
      <?= !empty($__is_admin) ? 'Admin' : 'User' ?>
    </span>
  </div>

</aside>

<!-- Page wrapper -->
<div class="md:pl-56 flex flex-col min-h-screen pt-14 md:pt-0">
  <main class="flex-1 p-6">

    <?php if (!empty($flash_success)): ?>
      <div class="alert mb-5 flex items-start gap-3 px-4 py-3 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm leading-relaxed">
        <svg class="mt-0.5 h-4 w-4 shrink-0 text-green-500" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <?= h($flash_success) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($flash_error)): ?>
      <div class="alert mb-5 flex items-start gap-3 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm leading-relaxed">
        <svg class="mt-0.5 h-4 w-4 shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-9a1 1 0 012 0v4a1 1 0 01-2 0V9zm1-4a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"/>
        </svg>
        <?= h($flash_error) ?>
      </div>
    <?php endif; ?>

    <?= $content ?>

  </main>
</div>

<script src="<?= asset('js/app.js?v=' . time()) ?>"></script>
<script src="<?= asset('js/LeadParser.js') ?>"></script>
<script src="<?= asset('js/address-autocomplete.js') ?>"></script>
<?php if (defined('GOOGLE_PLACES_API_KEY') && GOOGLE_PLACES_API_KEY): ?>
<script>
function mapsApiReady() {
  if (typeof initAddressAutocomplete === 'function') initAddressAutocomplete();
  if (typeof dashboardMapInit === 'function') dashboardMapInit();
}
</script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=<?= h(GOOGLE_PLACES_API_KEY) ?>&libraries=places,geocoding&callback=mapsApiReady"></script>
<?php endif; ?>
</body>
</html>
