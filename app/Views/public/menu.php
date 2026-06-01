<?php
/** @var array $tenant @var array $theme @var array $categories @var array $byCat @var array $popular */
$pageTitle = $tenant['name'] . ' — منوی آنلاین';
$currency = $tenant['currency'] ?: 'تومان';
$slug = $tenant['slug'];

/** render a single item card */
$renderItem = function (array $it) use ($currency, $slug) {
    $hasDiscount = !empty($it['discount']);
    $img = $it['image_path'] ? url($it['image_path']) : null;
    ?>
    <article class="menu-item <?= $it['is_available'] ? '' : 'sold-out' ?>" data-id="<?= (int) $it['id'] ?>">
        <?php if ($img): ?>
            <div class="mi-photo"><img loading="lazy" src="<?= e($img) ?>" alt="<?= e($it['name']) ?>"></div>
        <?php endif; ?>
        <div class="mi-body">
            <div class="mi-head">
                <h3><?= e($it['name']) ?></h3>
                <?php if ($it['is_featured']): ?><span class="badge feat">ویژه</span><?php endif; ?>
            </div>
            <?php if ($it['description']): ?><p class="mi-desc"><?= e($it['description']) ?></p><?php endif; ?>

            <div class="mi-foot">
                <div class="mi-price">
                    <?php if ($hasDiscount): ?>
                        <span class="old"><?= money($it['price']) ?></span>
                        <span class="new"><?= money($it['final_price']) ?> <?= e($currency) ?></span>
                        <span class="badge off">
                            <?= $it['discount']['type'] === 'percent'
                                ? e($it['discount']['value']) . '٪'
                                : 'تخفیف' ?>
                        </span>
                    <?php else: ?>
                        <span class="new"><?= money($it['final_price']) ?> <?= e($currency) ?></span>
                    <?php endif; ?>
                </div>
                <?php if (!$it['is_available']): ?>
                    <span class="badge out">ناموجود</span>
                <?php endif; ?>
            </div>

            <div class="mi-rate" data-id="<?= (int) $it['id'] ?>">
                <div class="stars" data-current="<?= (float) $it['rating'] ?>">
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                        <button type="button" class="star <?= $s <= round($it['rating']) ? 'on' : '' ?>" data-stars="<?= $s ?>">★</button>
                    <?php endfor; ?>
                </div>
                <small class="rate-info"><?= $it['rating_count'] > 0 ? e($it['rating']) . ' (' . (int) $it['rating_count'] . ')' : 'بدون امتیاز' ?></small>
            </div>
        </div>
    </article>
    <?php
};
?>

<div class="menu-page" data-slug="<?= e($slug) ?>" data-csrf="<?= e(csrf_token()) ?>">
    <header class="menu-hero" <?= !empty($theme['hero_image']) ? 'style="background-image:linear-gradient(rgba(0,0,0,.45),rgba(0,0,0,.55)),url(' . e(url($theme['hero_image'])) . ')"' : '' ?>>
        <?php if (!empty($theme['logo_path'])): ?>
            <img class="menu-logo" src="<?= e(url($theme['logo_path'])) ?>" alt="<?= e($tenant['name']) ?>">
        <?php endif; ?>
        <h1><?= e($theme['hero_title'] ?: $tenant['name']) ?></h1>
        <?php if (!empty($theme['hero_subtitle'])): ?><p><?= e($theme['hero_subtitle']) ?></p><?php endif; ?>
        <button class="btn-join" onclick="document.getElementById('joinModal').classList.add('open')">عضویت در باشگاه مشتریان</button>
    </header>

    <?php if ($categories): ?>
    <nav class="cat-nav" id="catNav">
        <?php if ($popular): ?><a href="#popular">پرطرفدارها</a><?php endif; ?>
        <?php foreach ($categories as $c): ?>
            <?php if (!empty($byCat[(int) $c['id']])): ?>
                <a href="#cat-<?= (int) $c['id'] ?>"><?= e($c['name']) ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
    <?php endif; ?>

    <main class="menu-body">
        <?php if ($popular): ?>
            <section id="popular" class="menu-section popular">
                <h2>⭐ پرطرفدارترین‌ها</h2>
                <div class="menu-grid">
                    <?php foreach ($popular as $it):
                        $it['discount'] = null;
                        $it['final_price'] = (int) $it['price'];
                        $it['rating'] = $it['rating_count'] > 0 ? round($it['rating_sum'] / $it['rating_count'], 1) : 0;
                        $renderItem($it);
                    endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php foreach ($categories as $c): ?>
            <?php $list = $byCat[(int) $c['id']] ?? []; if (!$list) continue; ?>
            <section id="cat-<?= (int) $c['id'] ?>" class="menu-section">
                <h2><?= e($c['name']) ?></h2>
                <div class="menu-grid">
                    <?php foreach ($list as $it) $renderItem($it); ?>
                </div>
            </section>
        <?php endforeach; ?>

        <?php
        $uncategorised = $byCat[0] ?? [];
        if ($uncategorised): ?>
            <section class="menu-section">
                <h2>سایر</h2>
                <div class="menu-grid"><?php foreach ($uncategorised as $it) $renderItem($it); ?></div>
            </section>
        <?php endif; ?>

        <p class="survey-cta"><a href="<?= url('m/' . e($slug) . '/survey') ?>">📝 در نظرسنجی ما شرکت کنید</a></p>
    </main>

    <!-- Join / register modal -->
    <div class="modal" id="joinModal">
        <div class="modal-card">
            <button class="modal-close" onclick="document.getElementById('joinModal').classList.remove('open')">×</button>
            <h2>عضویت در باشگاه مشتریان</h2>
            <p class="modal-sub">با عضویت، تخفیف تولد و پیشنهادهای ویژه دریافت می‌کنید.</p>
            <form method="post" action="<?= url('m/' . e($slug) . '/register') ?>">
                <?= csrf_field() ?>
                <div class="form-row">
                    <label>نام<input type="text" name="first_name" required value="<?= e(old('first_name')) ?>"></label>
                    <label>نام خانوادگی<input type="text" name="last_name" value="<?= e(old('last_name')) ?>"></label>
                </div>
                <label>شماره موبایل<input type="tel" name="phone" inputmode="numeric" placeholder="09xxxxxxxxx" required></label>
                <label>تاریخ تولد<input type="date" name="birthdate"></label>
                <button type="submit" class="btn-primary">ثبت‌نام</button>
            </form>
        </div>
    </div>
</div>
