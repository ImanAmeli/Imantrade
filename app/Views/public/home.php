<?php /** @var array $tenants */ ?>
<div class="home-wrap">
    <header class="home-hero">
        <h1>سیستم منوی آنلاین رستوران و کافه</h1>
        <p>یک منوی سریع، زیبا و قابل سفارشی‌سازی برای هر کسب‌وکار</p>
    </header>

    <?php if ($tenants): ?>
        <h2 class="home-sub">منوهای فعال</h2>
        <div class="home-grid">
            <?php foreach ($tenants as $t): ?>
                <a class="home-card" href="<?= url('m/' . e($t['slug'])) ?>">
                    <span class="home-card-name"><?= e($t['name']) ?></span>
                    <span class="home-card-go">مشاهده منو ←</span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="home-empty">هنوز منویی ثبت نشده است. از پنل مدیریت یک مجموعه بسازید.</p>
    <?php endif; ?>

    <a class="home-admin" href="<?= url('admin') ?>">ورود به پنل مدیریت</a>
</div>
