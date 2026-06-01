<?php
/** @var string $content */
$theme = $theme ?? null;
$pageTitle = $pageTitle ?? 'منوی آنلاین';
$primary = $theme['primary_color'] ?? '#c0392b';
$secondary = $theme['secondary_color'] ?? '#2c3e50';
$bg = $theme['bg_color'] ?? '#faf7f2';
$text = $theme['text_color'] ?? '#2c3e50';
$font = $theme['font_family'] ?? 'Vazirmatn';
$template = $theme['template'] ?? 'classic';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($pageTitle) ?></title>
    <meta name="theme-color" content="<?= e($primary) ?>">
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <style>
        :root{
            --primary: <?= e($primary) ?>;
            --secondary: <?= e($secondary) ?>;
            --bg: <?= e($bg) ?>;
            --text: <?= e($text) ?>;
            --font: '<?= e($font) ?>', Tahoma, sans-serif;
        }
    </style>
    <?php if (!empty($theme['custom_css'])): ?>
        <style><?= $theme['custom_css'] /* admin-controlled */ ?></style>
    <?php endif; ?>
</head>
<body class="tpl-<?= e($template) ?>">
<?php if ($msg = flash('success')): ?>
    <div class="toast toast-success"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($msg = flash('error')): ?>
    <div class="toast toast-error"><?= e($msg) ?></div>
<?php endif; ?>

<?= $content ?>

<script src="<?= asset('js/menu.js') ?>" defer></script>
</body>
</html>
