<?php /** @var array $campaigns */ $pageTitle = 'کمپین و پیام'; ?>
<div class="grid-2">
    <div class="card">
        <h3>کمپین جدید</h3>
        <p class="muted">پیام را برای گروهی از مشتریان (با محدودیت سنی یا سطح) از طریق پیامک، تلگرام یا بله بفرستید.</p>
        <form method="post" action="<?= url('admin/campaigns') ?>">
            <?= csrf_field() ?>
            <label>عنوان<input type="text" name="title" required></label>
            <label>متن پیام<textarea name="body" rows="4" required></textarea></label>
            <div class="form-row">
                <label>کانال
                    <select name="channel"><option value="sms">پیامک</option><option value="telegram">تلگرام</option><option value="bale">بله</option></select>
                </label>
                <label>سطح مشتری
                    <select name="tier"><option value="">همه</option><option value="platinum">پلاتین</option><option value="gold">طلایی</option><option value="silver">نقره‌ای</option><option value="bronze">برنزی</option></select>
                </label>
            </div>
            <div class="form-row">
                <label>حداقل سن<input type="number" name="min_age" min="0"></label>
                <label>حداکثر سن<input type="number" name="max_age" min="0"></label>
            </div>
            <button class="btn-primary">ذخیره کمپین</button>
        </form>
    </div>

    <div class="card">
        <h3>کمپین‌ها</h3>
        <?php if (!$campaigns): ?><p class="muted">هنوز کمپینی ندارید.</p><?php endif; ?>
        <?php foreach ($campaigns as $c): ?>
            <div class="disc-row">
                <div>
                    <strong><?= e($c['title']) ?></strong>
                    <span class="muted"><?= e($c['channel']) ?> — وضعیت: <?= $c['status'] === 'sent' ? 'ارسال‌شده (' . (int) $c['sent_count'] . ')' : 'پیش‌نویس' ?></span>
                    <small class="muted"><?= e(mb_substr($c['body'], 0, 60)) ?>…</small>
                </div>
                <?php if ($c['status'] !== 'sent'): ?>
                    <form method="post" action="<?= url('admin/campaigns/' . (int) $c['id'] . '/send') ?>" onsubmit="return confirm('ارسال شود؟')"><?= csrf_field() ?><button class="btn-sm">ارسال</button></form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
