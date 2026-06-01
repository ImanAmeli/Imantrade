<?php /** @var array $theme @var array $templates */ $pageTitle = 'قالب و ظاهر';
$tnames = ['classic' => 'کلاسیک', 'modern' => 'مدرن', 'elegant' => 'شیک', 'dark' => 'تیره']; ?>
<div class="card form-card">
    <p class="muted">هر مجموعه قالب و رنگ‌بندی مستقل دارد، پس منوی دو مشتری شبیه هم نمی‌شود.</p>
    <form method="post" action="<?= url('admin/theme') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <label>قالب پایه
            <select name="template">
                <?php foreach ($templates as $tpl): ?>
                    <option value="<?= e($tpl) ?>" <?= $theme['template'] === $tpl ? 'selected' : '' ?>><?= $tnames[$tpl] ?? $tpl ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <div class="form-row">
            <label>رنگ اصلی<input type="color" name="primary_color" value="<?= e($theme['primary_color']) ?>"></label>
            <label>رنگ ثانویه<input type="color" name="secondary_color" value="<?= e($theme['secondary_color']) ?>"></label>
            <label>رنگ پس‌زمینه<input type="color" name="bg_color" value="<?= e($theme['bg_color']) ?>"></label>
            <label>رنگ متن<input type="color" name="text_color" value="<?= e($theme['text_color']) ?>"></label>
        </div>

        <label>فونت<input type="text" name="font_family" value="<?= e($theme['font_family']) ?>"></label>

        <div class="form-row">
            <label>عنوان سرصفحه<input type="text" name="hero_title" value="<?= e($theme['hero_title'] ?? '') ?>"></label>
            <label>زیرعنوان<input type="text" name="hero_subtitle" value="<?= e($theme['hero_subtitle'] ?? '') ?>"></label>
        </div>

        <div class="form-row">
            <label>لوگو<input type="file" name="logo" accept="image/*"><?php if (!empty($theme['logo_path'])): ?><img class="preview sm" src="<?= e(url($theme['logo_path'])) ?>"><?php endif; ?></label>
            <label>تصویر سرصفحه<input type="file" name="hero" accept="image/*"><?php if (!empty($theme['hero_image'])): ?><img class="preview sm" src="<?= e(url($theme['hero_image'])) ?>"><?php endif; ?></label>
        </div>

        <label>CSS سفارشی (اختیاری)<textarea name="custom_css" rows="4"><?= e($theme['custom_css'] ?? '') ?></textarea></label>

        <button class="btn-primary">ذخیره قالب</button>
    </form>
</div>
