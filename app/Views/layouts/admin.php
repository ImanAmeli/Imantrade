<?php
/** @var string $content */
$user = $_user ?? null;
$tenant = $_tenant ?? null;
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '';
$nav = [
    'admin'              => ['داشبورد', '📊'],
    'admin/items'        => ['محصولات', '🍽️'],
    'admin/categories'   => ['دسته‌بندی‌ها', '📂'],
    'admin/pricing'      => ['قیمت‌گذاری گروهی', '💰'],
    'admin/discounts'    => ['تخفیف‌ها', '🏷️'],
    'admin/customers'    => ['مشتریان و رتبه‌بندی', '👥'],
    'admin/campaigns'    => ['کمپین و پیام', '📣'],
    'admin/surveys'      => ['نظرسنجی', '📝'],
    'admin/theme'        => ['قالب و ظاهر', '🎨'],
    'admin/integrations' => ['اتصالات', '🔌'],
];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>داشبورد مدیریت</title>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="brand">
            <span class="brand-dot"></span>
            <div>
                <strong>پنل مدیریت</strong>
                <small><?= e($tenant['name'] ?? '') ?></small>
            </div>
        </div>
        <nav>
            <?php foreach ($nav as $href => [$label, $icon]):
                $active = ('/' . $href) === rtrim($path, '/') ? 'active' : ''; ?>
                <a class="nav-link <?= $active ?>" href="<?= url($href) ?>">
                    <span class="ic"><?= $icon ?></span><?= e($label) ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-foot">
            <?php if ($tenant): ?>
                <a class="view-site" target="_blank" href="<?= url('m/' . $tenant['slug']) ?>">مشاهده منوی عمومی ↗</a>
            <?php endif; ?>
            <a class="logout" href="<?= url('admin/logout') ?>">خروج</a>
        </div>
    </aside>

    <main class="content">
        <header class="topbar">
            <h1><?= e($pageTitle ?? 'داشبورد') ?></h1>
            <div class="user-chip"><?= e($user['name'] ?? '') ?></div>
        </header>

        <?php if ($m = flash('success')): ?><div class="alert ok"><?= e($m) ?></div><?php endif; ?>
        <?php if ($m = flash('error')): ?><div class="alert err"><?= e($m) ?></div><?php endif; ?>

        <div class="page">
            <?= $content ?>
        </div>
    </main>
</div>
</body>
</html>
