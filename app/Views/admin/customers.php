<?php /** @var array $customers @var array $topMonth @var string $q */ $pageTitle = 'مشتریان و رتبه‌بندی';
$tierLabel = ['platinum' => 'پلاتین', 'gold' => 'طلایی', 'silver' => 'نقره‌ای', 'bronze' => 'برنزی']; ?>
<div class="toolbar">
    <form method="get" action="<?= url('admin/customers') ?>" class="filter">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="جستجوی نام یا شماره">
        <button class="btn-sm">جستجو</button>
    </form>
    <form method="post" action="<?= url('admin/customers/recompute') ?>"><?= csrf_field() ?><button class="btn-sm">بازمحاسبه رتبه‌ها</button></form>
    <form method="post" action="<?= url('admin/customers/sync-accounting') ?>"><?= csrf_field() ?><button class="btn-sm">همگام‌سازی با حسابداری</button></form>
</div>

<div class="card">
    <h3>فهرست مشتریان (<?= count($customers) ?>)</h3>
    <?php if (!$customers): ?><p class="muted">مشتری‌ای ثبت نشده.</p><?php else: ?>
    <table class="tbl">
        <thead><tr><th>نام</th><th>موبایل</th><th>تولد</th><th>سطح</th><th>تعداد سفارش</th><th>مجموع خرید</th><th>امتیاز</th></tr></thead>
        <tbody>
        <?php foreach ($customers as $c): ?>
            <tr>
                <td><?= e(trim($c['first_name'] . ' ' . $c['last_name'])) ?></td>
                <td class="muted"><?= e($c['phone']) ?></td>
                <td class="muted"><?= e($c['birthdate'] ?? '—') ?></td>
                <td><span class="tier t-<?= e($c['tier']) ?>"><?= $tierLabel[$c['tier']] ?? $c['tier'] ?></span></td>
                <td><?= (int) $c['orders_count'] ?></td>
                <td><?= toman($c['total_spent']) ?></td>
                <td><b><?= money($c['rank_score']) ?></b></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
