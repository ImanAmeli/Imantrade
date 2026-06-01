<?php /** @var array $tenant @var array $questions */
$pageTitle = 'نظرسنجی — ' . $tenant['name'];
$slug = $tenant['slug'];
?>
<div class="survey-page">
    <header class="survey-head">
        <h1>نظرسنجی <?= e($tenant['name']) ?></h1>
        <p>نظر شما به ما کمک می‌کند بهتر شویم 🌟</p>
    </header>

    <?php if (!$questions): ?>
        <p class="survey-empty">در حال حاضر نظرسنجی فعالی وجود ندارد.</p>
        <a href="<?= url('m/' . e($slug)) ?>">بازگشت به منو</a>
    <?php else: ?>
        <form method="post" action="<?= url('m/' . e($slug) . '/survey') ?>" class="survey-form">
            <?= csrf_field() ?>
            <?php foreach ($questions as $i => $q): ?>
                <fieldset class="survey-q">
                    <legend><?= ($i + 1) . '. ' . e($q['question']) ?></legend>
                    <?php foreach ($q['options'] as $opt): ?>
                        <label class="opt">
                            <input type="radio" name="answers[<?= (int) $q['id'] ?>]" value="<?= (int) $opt['id'] ?>" required>
                            <span><?= e($opt['label']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </fieldset>
            <?php endforeach; ?>
            <button type="submit" class="btn-primary">ثبت پاسخ‌ها</button>
        </form>
    <?php endif; ?>
</div>
