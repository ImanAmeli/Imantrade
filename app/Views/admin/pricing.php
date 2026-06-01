<?php /** @var array $categories @var array $log */ $pageTitle = 'قیمت‌گذاری گروهی'; ?>
<div class="grid-2">
    <div class="card">
        <h3>تغییر گروهی قیمت</h3>
        <p class="muted">می‌توانید قیمت همه محصولات یا یک دسته را به صورت درصدی یا مبلغ ثابت، بالا یا پایین ببرید.</p>
        <form method="post" action="<?= url('admin/pricing/shift') ?>">
            <?= csrf_field() ?>
            <div class="form-row">
                <label>دامنه
                    <select name="scope" id="scopeSel" onchange="document.getElementById('catWrap').style.display=this.value==='category'?'block':'none'">
                        <option value="all">همه محصولات</option>
                        <option value="category">یک دسته‌بندی</option>
                    </select>
                </label>
                <label id="catWrap" style="display:none">دسته‌بندی
                    <select name="category_id">
                        <?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
                    </select>
                </label>
            </div>
            <div class="form-row">
                <label>نوع
                    <select name="mode">
                        <option value="percent">درصدی (%)</option>
                        <option value="fixed">مبلغ ثابت (تومان)</option>
                    </select>
                </label>
                <label>جهت
                    <select name="direction">
                        <option value="up">افزایش ▲</option>
                        <option value="down">کاهش ▼</option>
                    </select>
                </label>
                <label>مقدار<input type="text" inputmode="numeric" name="amount" required></label>
            </div>
            <button class="btn-primary" onclick="return confirm('قیمت‌ها تغییر می‌کند. مطمئنید؟')">اعمال</button>
        </form>
    </div>

    <div class="card">
        <h3>تاریخچه تغییرات</h3>
        <?php if (!$log): ?><p class="muted">هنوز تغییری ثبت نشده.</p><?php else: ?>
        <table class="tbl">
            <thead><tr><th>تاریخ</th><th>دامنه</th><th>نوع</th><th>جهت</th><th>مقدار</th><th>تعداد</th></tr></thead>
            <tbody>
            <?php foreach ($log as $l): ?>
                <tr>
                    <td class="muted"><?= e($l['created_at']) ?></td>
                    <td><?= $l['scope'] === 'all' ? 'همه' : 'دسته' ?></td>
                    <td><?= $l['mode'] === 'percent' ? 'درصدی' : 'ثابت' ?></td>
                    <td><?= $l['direction'] === 'up' ? '▲' : '▼' ?></td>
                    <td><?= money($l['amount']) ?><?= $l['mode'] === 'percent' ? '٪' : '' ?></td>
                    <td><?= (int) $l['affected'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
