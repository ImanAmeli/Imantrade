<?php
/** @var ?array $item @var array $categories */
$editing = $item !== null;
$pageTitle = $editing ? 'ویرایش محصول' : 'محصول جدید';
$action = $editing ? url('admin/items/' . (int) $item['id'] . '/update') : url('admin/items');
?>
<div class="card form-card">
    <form method="post" action="<?= $action ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <label>نام محصول<input type="text" name="name" required value="<?= e($item['name'] ?? '') ?>"></label>
        <label>توضیحات<textarea name="description" rows="3"><?= e($item['description'] ?? '') ?></textarea></label>
        <div class="form-row">
            <label>دسته‌بندی
                <select name="category_id">
                    <option value="0">— بدون دسته —</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= ($item['category_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>قیمت (تومان)<input type="text" inputmode="numeric" name="price" value="<?= (int) ($item['price'] ?? 0) ?>"></label>
            <label>ترتیب<input type="number" name="sort_order" value="<?= (int) ($item['sort_order'] ?? 0) ?>"></label>
        </div>

        <div class="form-row checks">
            <label class="chk"><input type="checkbox" name="is_available" <?= ($item['is_available'] ?? 1) ? 'checked' : '' ?>> موجود</label>
            <label class="chk"><input type="checkbox" name="is_featured" <?= ($item['is_featured'] ?? 0) ? 'checked' : '' ?>> ویژه (اول لیست)</label>
        </div>

        <label>تصویر محصول
            <input type="file" name="image" accept="image/*">
        </label>
        <?php if (!empty($item['image_path'])): ?>
            <img class="preview" src="<?= e(url($item['image_path'])) ?>" alt="">
        <?php endif; ?>

        <div class="form-actions">
            <button class="btn-primary"><?= $editing ? 'به‌روزرسانی' : 'ذخیره' ?></button>
            <a class="btn-sm" href="<?= url('admin/items') ?>">انصراف</a>
        </div>
    </form>
</div>
