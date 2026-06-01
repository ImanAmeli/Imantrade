<?php
/**
 * Global helper functions.
 */

if (!function_exists('e')) {
    /** HTML-escape for safe output. */
    function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('config')) {
    function config(?string $key = null)
    {
        static $cfg;
        if ($cfg === null) {
            $path = __DIR__ . '/../../config/config.php';
            $cfg = is_file($path) ? require $path : require __DIR__ . '/../../config/config.example.php';
        }
        if ($key === null) {
            return $cfg;
        }
        $parts = explode('.', $key);
        $val = $cfg;
        foreach ($parts as $p) {
            if (!isset($val[$p])) {
                return null;
            }
            $val = $val[$p];
        }
        return $val;
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $base = rtrim((string) config('app.base_path'), '/');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        return base_path($path);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return base_path('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): void
    {
        header('Location: ' . (str_starts_with($path, 'http') ? $path : base_path($path)));
        exit;
    }
}

if (!function_exists('old')) {
    function old(string $key, $default = '')
    {
        return $_SESSION['_old'][$key] ?? $default;
    }
}

if (!function_exists('flash')) {
    function flash(string $key, ?string $msg = null)
    {
        if ($msg !== null) {
            $_SESSION['_flash'][$key] = $msg;
            return null;
        }
        $val = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $val;
    }
}

if (!function_exists('fa_digits')) {
    /** Convert ASCII digits to Persian digits. */
    function fa_digits(string $s): string
    {
        return strtr($s, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
                          '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
    }
}

if (!function_exists('money')) {
    /** Format an integer amount with thousands separators (Persian digits). */
    function money($amount): string
    {
        return fa_digits(number_format((float) $amount));
    }
}

if (!function_exists('toman')) {
    function toman($amount, string $currency = 'تومان'): string
    {
        return money($amount) . ' ' . $currency;
    }
}

if (!function_exists('slugify')) {
    function slugify(string $text): string
    {
        $text = trim($text);
        // keep latin letters/numbers, replace the rest with dashes
        $text = preg_replace('/[^A-Za-z0-9]+/u', '-', $text);
        $text = trim((string) $text, '-');
        return strtolower($text) ?: 'menu';
    }
}

if (!function_exists('age_from')) {
    /** Age in (gregorian) years from a Y-m-d birthdate, or null. */
    function age_from(?string $birthdate): ?int
    {
        if (!$birthdate) {
            return null;
        }
        try {
            $b = new DateTime($birthdate);
            return (new DateTime('today'))->diff($b)->y;
        } catch (\Exception $e) {
            return null;
        }
    }
}

if (!function_exists('normalize_ir_phone')) {
    /**
     * Normalise an Iranian mobile number to canonical 09xxxxxxxxx form.
     * Accepts inputs like +98912..., 0098912..., 98912..., 9121234567,
     * 0912... and Persian/Arabic digits. Returns null if not a mobile.
     */
    function normalize_ir_phone(string $raw): ?string
    {
        // Persian/Arabic digits -> ASCII
        $map = ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
                '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9'];
        $d = preg_replace('/\D+/', '', strtr($raw, $map));
        if ($d === '') {
            return null;
        }
        if (str_starts_with($d, '0098')) {
            $d = substr($d, 4);
        } elseif (str_starts_with($d, '98') && strlen($d) === 12) {
            $d = substr($d, 2);
        }
        if (strlen($d) === 10 && $d[0] === '9') {
            $d = '0' . $d;
        }
        return preg_match('/^09\d{9}$/', $d) ? $d : null;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('csrf_check')) {
    function csrf_check(): bool
    {
        $sent = $_POST['_csrf'] ?? '';
        return is_string($sent) && hash_equals($_SESSION['_csrf'] ?? '', $sent);
    }
}
