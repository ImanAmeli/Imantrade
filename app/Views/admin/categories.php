<?php /** @var array $categories */ $pageTitle = 'دسته‌بندی‌ها'; ?>
<div class="grid-2">
    <div class="card">
        <h3>افزودن دسته‌بندی</h3>
        <form method="post" action="<?= url('admin/categories') ?>">
            <?= csrf_field() ?>
            <label>نام<input type="text" name="name" required></label>
            <label>ترتیب<input type="number" name="sort_order" value="0"></label>
            <button class="btn-primary">افزودن</button>
        </form>
    </div>

    <div class="card">
        <h3>دسته‌بندی‌ها</h3>
        <?php if (!$categories): ?><p class="muted">هنوز دسته‌بندی ندارید.</p><?php endif; ?>
        <?php foreach ($categories as $c): ?>
            <form method="post" action="<?= url('admin/categories/' . (int) $c['id'] . '/update') ?>" class="inline-row">
                <?= csrf_field() ?>
                <input type="text" name="name" value="<?= e($c['name']) ?>">
                <input type="number" name="sort_order" value="<?= (int) $c['sort_order'] ?>" style="width:70px">
                <label class="chk"><input type="checkbox" name="is_active" <?= $c['is_active'] ? 'checked' : '' ?>> فعال</label>
                <span class="muted"><?= (int) $c['item_count'] ?> محصول</span>
                <button class="btn-sm">ذخیره</button>
                <button class="btn-sm danger" formaction="<?= url('admin/categories/' . (int) $c['id'] . '/delete') ?>" onclick="return confirm('حذف شود؟')">حذف</button>
            </form>
        <?php endforeach; ?>
    </div>
</div>
