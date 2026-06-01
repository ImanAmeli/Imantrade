<?php /** @var ?string $error */ ?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ورود به پنل مدیریت</title>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body class="login-body">
    <form class="login-card" method="post" action="<?= url('admin/login') ?>">
        <?= csrf_field() ?>
        <h1>پنل مدیریت</h1>
        <p class="login-sub">برای ادامه وارد شوید</p>
        <?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
        <label>ایمیل<input type="email" name="email" required autofocus></label>
        <label>گذرواژه<input type="password" name="password" required></label>
        <button type="submit" class="btn-primary">ورود</button>
    </form>
</body>
</html>
