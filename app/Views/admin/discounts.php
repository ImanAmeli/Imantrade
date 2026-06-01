<?php /** @var array $discounts @var array $categories @var array $items */ $pageTitle = 'تخفیف‌ها';
$dows = ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه']; ?>
<div class="grid-2">
    <div class="card">
        <h3>تخفیف جدید</h3>
        <form method="post" action="<?= url('admin/discounts') ?>">
            <?= csrf_field() ?>
            <label>عنوان<input type="text" name="name" required></label>
            <div class="form-row">
                <label>نوع<select name="type"><option value="percent">درصدی</option><option value="fixed">مبلغ ثابت</option></select></label>
                <label>مقدار<input type="text" inputmode="numeric" name="value" required></label>
            </div>
            <label>دامنه
                <select name="scope" id="dScope" onchange="dToggle(this.value)">
                    <option value="all">همه محصولات</option>
                    <option value="category">یک دسته</option>
                    <option value="item">یک محصول</option>
                </select>
            </label>
            <label id="dCat" style="display:none">دسته
                <select name="target_id" id="dCatSel" disabled><?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select>
            </label>
            <label id="dItem" style="display:none">محصول
                <select name="target_id" id="dItemSel" disabled><?php foreach ($items as $it): ?><option value="<?= (int) $it['id'] ?>"><?= e($it['name']) ?></option><?php endforeach; ?></select>
            </label>

            <fieldset class="days">
                <legend>روزهای هفته (خالی = همه روزها)</legend>
                <?php foreach ($dows as $i => $d): ?>
                    <label class="chk"><input type="checkbox" name="days[]" value="<?= $i ?>"> <?= $d ?></label>
                <?php endforeach; ?>
            </fieldset>

            <div class="form-row">
                <label>از تاریخ<input type="date" name="starts_at"></label>
                <label>تا تاریخ<input type="date" name="ends_at"></label>
            </div>
            <button class="btn-primary">ایجاد تخفیف</button>
        </form>
    </div>

    <div class="card">
        <h3>تخفیف‌های فعال/غیرفعال</h3>
        <?php if (!$discounts): ?><p class="muted">هنوز تخفیفی ندارید.</p><?php endif; ?>
        <?php foreach ($discounts as $d): ?>
            <div class="disc-row">
                <div>
                    <strong><?= e($d['name']) ?></strong>
                    <span class="muted"><?= $d['type'] === 'percent' ? (int) $d['value'] . '٪' : money($d['value']) . ' ت' ?> — <?= e($d['scope']) ?></span>
                    <?php if ($d['days_of_week']): ?><small class="muted">روزها: <?= e($d['days_of_week']) ?></small><?php endif; ?>
                </div>
                <div class="disc-actions">
                    <form method="post" action="<?= url('admin/discounts/' . (int) $d['id'] . '/toggle') ?>"><?= csrf_field() ?><button class="pill <?= $d['is_active'] ? 'on' : 'off' ?>"><?= $d['is_active'] ? 'فعال' : 'غیرفعال' ?></button></form>
                    <form method="post" action="<?= url('admin/discounts/' . (int) $d['id'] . '/delete') ?>" onsubmit="return confirm('حذف شود؟')"><?= csrf_field() ?><button class="btn-sm danger">حذف</button></form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<script>
function dToggle(v){
  var cat=v==='category',item=v==='item';
  document.getElementById('dCat').style.display=cat?'block':'none';
  document.getElementById('dItem').style.display=item?'block':'none';
  document.getElementById('dCatSel').disabled=!cat;
  document.getElementById('dItemSel').disabled=!item;
}
</script>
