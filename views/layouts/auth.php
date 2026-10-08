<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= asset('css/tailwind.css') ?>">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="min-h-screen bg-slate-900 flex items-center justify-center p-5">
  <div class="w-full max-w-sm">
    <?= $content ?>
  </div>
</body>
</html>
