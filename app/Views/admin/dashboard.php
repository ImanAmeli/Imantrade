<?php
/** @var array $stats @var array $topMonth @var array $topWeek @var array $popular @var array $birthdays */
$pageTitle = 'داشبورد';
$tierLabel = ['platinum' => 'پلاتین', 'gold' => 'طلایی', 'silver' => 'نقره‌ای', 'bronze' => 'برنزی'];
?>
<div class="cards">
    <div class="card stat"><span class="num"><?= money($stats['items']) ?></span><span class="lbl">محصول</span></div>
    <div class="card stat"><span class="num"><?= money($stats['available']) ?></span><span class="lbl">موجود</span></div>
    <div class="card stat"><span class="num"><?= money($stats['categories']) ?></span><span class="lbl">دسته‌بندی</span></div>
    <div class="card stat"><span class="num"><?= money($stats['customers']) ?></span><span class="lbl">مشتری</span></div>
    <div class="card stat"><span class="num"><?= money($stats['orders']) ?></span><span class="lbl">سفارش</span></div>
</div>

<div class="grid-2">
    <div class="card">
        <h3>🏆 برترین مشتریان ماه</h3>
        <?php if (!$topMonth): ?><p class="muted">هنوز داده‌ای نیست.</p><?php else: ?>
        <table class="tbl">
            <thead><tr><th>#</th><th>مشتری</th><th>سطح</th><th>خرید</th></tr></thead>
            <tbody>
            <?php foreach ($topMonth as $i => $c): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= e(trim($c['first_name'] . ' ' . $c['last_name'])) ?></td>
                    <td><span class="tier t-<?= e($c['tier']) ?>"><?= $tierLabel[$c['tier']] ?? $c['tier'] ?></span></td>
                    <td><?= toman($c['spent']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>🔥 برترین مشتریان هفته</h3>
        <?php if (!$topWeek): ?><p class="muted">هنوز داده‌ای نیست.</p><?php else: ?>
        <ol class="rank-list">
            <?php foreach ($topWeek as $c): ?>
                <li><span><?= e(trim($c['first_name'] . ' ' . $c['last_name'])) ?></span><b><?= toman($c['spent']) ?></b></li>
            <?php endforeach; ?>
        </ol>
        <?php endif; ?>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <h3>⭐ محصولات پرطرفدار</h3>
        <?php if (!$popular): ?><p class="muted">هنوز داده‌ای نیست.</p><?php else: ?>
        <ul class="pop-list">
            <?php foreach ($popular as $p): ?>
                <li><span><?= e($p['name']) ?></span><small><?= (int) $p['order_count'] ?> سفارش</small></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>🎂 تولدهای پیش‌رو</h3>
        <?php if (!$birthdays): ?><p class="muted">در ۷ روز آینده تولدی نیست.</p><?php else: ?>
        <ul class="bday-list">
            <?php foreach ($birthdays as $b): ?>
                <li><span><?= e(trim($b['first_name'] . ' ' . $b['last_name'])) ?></span><small><?= e($b['phone']) ?> — <?= e($b['birthdate']) ?></small></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>
