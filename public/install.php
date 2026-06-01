<?php
/**
 * One-time web installer.
 *  - writes config/config.php
 *  - creates all tables (db/schema.sql)
 *  - creates the first tenant + admin user + demo data
 *
 * DELETE THIS FILE after a successful install.
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$configPath = $root . '/config/config.php';
$errors = [];
$done = false;

function h($v): string { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $in = $_POST;

    // 1) try DB connection
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $in['db_host'], (int) $in['db_port'], $in['db_name']);
    try {
        $pdo = new PDO($dsn, $in['db_user'], $in['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    } catch (Throwable $e) {
        $errors[] = 'اتصال به دیتابیس ناموفق بود: ' . $e->getMessage();
    }

    if (!$errors) {
        // 2) run schema
        $sql = file_get_contents($root . '/db/schema.sql');
        try {
            $pdo->exec($sql);
        } catch (Throwable $e) {
            $errors[] = 'اجرای schema ناموفق بود: ' . $e->getMessage();
        }
    }

    if (!$errors) {
        // 3) write config.php
        $cfg = file_get_contents($root . '/config/config.example.php');
        $cfg = str_replace(
            ["'127.0.0.1'", "3306", "'restaurant_menu'", "'root'", "'pass'    => ''", "'change-me-to-a-long-random-string'"],
            [
                var_export($in['db_host'], true),
                (string) (int) $in['db_port'],
                var_export($in['db_name'], true),
                var_export($in['db_user'], true),
                "'pass'    => " . var_export($in['db_pass'], true),
                var_export(bin2hex(random_bytes(24)), true),
            ],
            $cfg
        );
        if (!@file_put_contents($configPath, $cfg)) {
            $errors[] = 'نوشتن config/config.php ممکن نشد. دسترسی پوشه config را بررسی کنید.';
        }
    }

    if (!$errors) {
        // 4) seed tenant + admin + demo
        $name = trim($in['biz_name']) ?: 'کافه نمونه';
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($in['biz_slug']))) ?: 'demo';
        $email = trim($in['admin_email']);
        $pass = $in['admin_pass'];

        if ($email === '' || strlen($pass) < 6) {
            $errors[] = 'ایمیل مدیر و گذرواژه (حداقل ۶ کاراکتر) لازم است.';
        } else {
            $exists = (int) $pdo->query('SELECT COUNT(*) FROM tenants')->fetchColumn();
            if ($exists === 0) {
                $pdo->prepare('INSERT INTO tenants (name, slug) VALUES (?,?)')->execute([$name, $slug]);
                $tid = (int) $pdo->lastInsertId();
                $pdo->prepare('INSERT INTO themes (tenant_id, hero_title, hero_subtitle) VALUES (?,?,?)')
                    ->execute([$tid, $name, 'به منوی ما خوش آمدید']);
                $pdo->prepare('INSERT INTO users (tenant_id, name, email, password_hash, role) VALUES (?,?,?,?,?)')
                    ->execute([$tid, 'مدیر', $email, password_hash($pass, PASSWORD_DEFAULT), 'admin']);

                // demo categories + items
                $cats = ['نوشیدنی گرم', 'نوشیدنی سرد', 'دسر'];
                $catIds = [];
                $cstmt = $pdo->prepare('INSERT INTO categories (tenant_id, name, sort_order) VALUES (?,?,?)');
                foreach ($cats as $i => $cn) { $cstmt->execute([$tid, $cn, $i]); $catIds[] = (int) $pdo->lastInsertId(); }
                $items = [
                    [$catIds[0], 'اسپرسو', 'قهوه تک‌شات', 45000, 1],
                    [$catIds[0], 'کاپوچینو', 'با فوم شیر', 68000, 1],
                    [$catIds[1], 'آیس لته', 'سرد و خنک', 85000, 0],
                    [$catIds[2], 'چیزکیک', 'دسر مخصوص', 120000, 0],
                ];
                $istmt = $pdo->prepare('INSERT INTO items (tenant_id, category_id, name, description, price, is_featured) VALUES (?,?,?,?,?,?)');
                foreach ($items as $it) { $istmt->execute([$tid, $it[0], $it[1], $it[2], $it[3], $it[4]]); }
            }
            $done = !$errors;
        }
    }
}

// already installed?
$alreadyConfigured = is_file($configPath);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>نصب سیستم منوی آنلاین</title>
<style>
body{font-family:Tahoma,sans-serif;background:#f1f3f7;color:#2c3e50;display:flex;justify-content:center;padding:30px}
.box{background:#fff;max-width:520px;width:100%;padding:28px;border-radius:16px;box-shadow:0 10px 40px rgba(0,0,0,.08)}
h1{font-size:1.3rem;margin-bottom:6px}p.sub{color:#888;font-size:.85rem;margin-bottom:18px}
label{display:block;font-size:.85rem;font-weight:bold;margin:12px 0 4px}
input{width:100%;padding:9px;border:1px solid #ddd;border-radius:8px;box-sizing:border-box;font-family:inherit}
.row{display:flex;gap:10px}.row>div{flex:1}
button{margin-top:18px;width:100%;background:#e67e22;color:#fff;border:0;padding:12px;border-radius:10px;font-weight:bold;cursor:pointer;font-family:inherit;font-size:1rem}
.err{background:#fdecea;color:#c0392b;padding:10px;border-radius:8px;margin-bottom:12px;font-size:.85rem}
.ok{background:#e7f8ee;color:#1d7a44;padding:14px;border-radius:8px;font-size:.9rem}
fieldset{border:1px solid #eee;border-radius:10px;padding:10px 14px;margin-top:14px}legend{font-size:.8rem;color:#999}
a{color:#e67e22;font-weight:bold}
</style>
</head>
<body>
<div class="box">
    <h1>نصب سیستم منوی آنلاین</h1>
    <p class="sub">رستوران و کافه — راه‌اندازی اولیه</p>

    <?php if ($done): ?>
        <div class="ok">
            ✅ نصب با موفقیت انجام شد!<br><br>
            وارد پنل شوید: <a href="admin/login">admin/login</a><br>
            منوی عمومی: <a href="m/<?= h($slug ?? 'demo') ?>">m/<?= h($slug ?? 'demo') ?></a><br><br>
            <strong>مهم:</strong> همین حالا فایل <code>public/install.php</code> را حذف کنید.
        </div>
    <?php else: ?>
        <?php foreach ($errors as $er): ?><div class="err"><?= h($er) ?></div><?php endforeach; ?>
        <?php if ($alreadyConfigured): ?>
            <div class="err">فایل config.php از قبل وجود دارد. اگر می‌خواهید دوباره نصب کنید، ابتدا آن را حذف کنید.</div>
        <?php endif; ?>
        <form method="post">
            <fieldset>
                <legend>دیتابیس</legend>
                <div class="row">
                    <div><label>هاست</label><input name="db_host" value="<?= h($_POST['db_host'] ?? 'localhost') ?>"></div>
                    <div><label>پورت</label><input name="db_port" value="<?= h($_POST['db_port'] ?? '3306') ?>"></div>
                </div>
                <label>نام دیتابیس</label><input name="db_name" value="<?= h($_POST['db_name'] ?? '') ?>" required>
                <label>کاربر</label><input name="db_user" value="<?= h($_POST['db_user'] ?? '') ?>" required>
                <label>گذرواژه</label><input name="db_pass" type="password">
            </fieldset>
            <fieldset>
                <legend>مجموعه و مدیر</legend>
                <label>نام رستوران/کافه</label><input name="biz_name" value="<?= h($_POST['biz_name'] ?? '') ?>" required>
                <label>نامک (انگلیسی، در آدرس منو)</label><input name="biz_slug" value="<?= h($_POST['biz_slug'] ?? '') ?>" placeholder="mycafe" required>
                <label>ایمیل مدیر</label><input name="admin_email" type="email" value="<?= h($_POST['admin_email'] ?? '') ?>" required>
                <label>گذرواژه مدیر</label><input name="admin_pass" type="password" required>
            </fieldset>
            <button type="submit">نصب</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
