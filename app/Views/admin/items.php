<?php /** @var array $items @var array $categories @var int $filterCat */ $pageTitle = 'محصولات'; ?>
<div class="toolbar">
    <a class="btn-primary" href="<?= url('admin/items/create') ?>">+ محصول جدید</a>
    <form method="get" action="<?= url('admin/items') ?>" class="filter">
        <select name="category" onchange="this.form.submit()">
            <option value="0">همه دسته‌ها</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= $filterCat === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<div class="card">
    <?php if (!$items): ?><p class="muted">محصولی یافت نشد.</p><?php else: ?>
    <table class="tbl items-tbl">
        <thead><tr><th>تصویر</th><th>نام</th><th>دسته</th><th>قیمت</th><th>وضعیت</th><th>ویژه</th><th>امتیاز</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
            <tr>
                <td><?php if ($it['image_path']): ?><img class="thumb" src="<?= e(url($it['image_path'])) ?>" alt=""><?php else: ?><span class="thumb empty">—</span><?php endif; ?></td>
                <td><?= e($it['name']) ?></td>
                <td class="muted"><?= e($it['category_name'] ?? '—') ?></td>
                <td><?= toman($it['price']) ?></td>
                <td>
                    <form method="post" action="<?= url('admin/items/' . (int) $it['id'] . '/toggle') ?>">
                        <?= csrf_field() ?>
                        <button class="pill <?= $it['is_available'] ? 'on' : 'off' ?>"><?= $it['is_available'] ? 'موجود' : 'ناموجود' ?></button>
                    </form>
                </td>
                <td>
                    <form method="post" action="<?= url('admin/items/' . (int) $it['id'] . '/feature') ?>">
                        <?= csrf_field() ?>
                        <button class="pill <?= $it['is_featured'] ? 'on' : 'neutral' ?>"><?= $it['is_featured'] ? '★ ویژه' : 'عادی' ?></button>
                    </form>
                </td>
                <td><?= $it['rating_count'] > 0 ? round($it['rating_sum'] / $it['rating_count'], 1) . ' ★' : '—' ?></td>
                <td class="row-actions">
                    <a class="btn-sm" href="<?= url('admin/items/' . (int) $it['id'] . '/edit') ?>">ویرایش</a>
                    <form method="post" action="<?= url('admin/items/' . (int) $it['id'] . '/delete') ?>" onsubmit="return confirm('حذف شود؟')">
                        <?= csrf_field() ?>
                        <button class="btn-sm danger">حذف</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
