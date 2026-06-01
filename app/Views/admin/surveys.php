<?php /** @var array $questions @var int $totalResponses */ $pageTitle = 'نظرسنجی'; ?>
<div class="toolbar">
    <span class="muted">مجموع پاسخ‌ها: <b><?= money($totalResponses) ?></b></span>
    <?php if (!$questions): ?>
        <form method="post" action="<?= url('admin/surveys/seed') ?>"><?= csrf_field() ?><button class="btn-sm">افزودن ۲۰ سوال نمونه</button></form>
    <?php endif; ?>
</div>

<div class="grid-2">
    <div class="card">
        <h3>سوال جدید</h3>
        <form method="post" action="<?= url('admin/surveys') ?>">
            <?= csrf_field() ?>
            <label>سوال<input type="text" name="question" required></label>
            <label>گزینه ۱<input type="text" name="options[]"></label>
            <label>گزینه ۲<input type="text" name="options[]"></label>
            <label>گزینه ۳<input type="text" name="options[]"></label>
            <label>گزینه ۴<input type="text" name="options[]"></label>
            <button class="btn-primary">افزودن سوال</button>
        </form>
    </div>

    <div class="card">
        <h3>سوال‌ها (<?= count($questions) ?>)</h3>
        <?php if (!$questions): ?><p class="muted">هنوز سوالی ندارید.</p><?php endif; ?>
        <?php foreach ($questions as $i => $q): ?>
            <div class="disc-row">
                <div>
                    <strong><?= ($i + 1) . '. ' . e($q['question']) ?></strong>
                    <small class="muted"><?= implode(' • ', array_map(fn ($o) => e($o['label']), $q['options'])) ?></small>
                    <small class="muted"><?= (int) $q['responses'] ?> پاسخ</small>
                </div>
                <form method="post" action="<?= url('admin/surveys/' . (int) $q['id'] . '/delete') ?>" onsubmit="return confirm('حذف شود؟')"><?= csrf_field() ?><button class="btn-sm danger">حذف</button></form>
            </div>
        <?php endforeach; ?>
    </div>
</div>
