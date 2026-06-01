<?php /** @var array $integrations */ $pageTitle = 'اتصالات';
/** field maps per channel: key => label */
$fields = [
    'telegram' => ['bot_token' => 'توکن ربات', 'default_chat_id' => 'چت‌آیدی پیش‌فرض (اختیاری)'],
    'bale'     => ['bot_token' => 'توکن ربات بله', 'default_chat_id' => 'چت‌آیدی پیش‌فرض (اختیاری)'],
    'sms'      => ['api_key' => 'کلید API', 'sender' => 'شماره فرستنده'],
    'payment'  => ['merchant_id' => 'کد پذیرنده (Merchant)', 'callback' => 'آدرس بازگشت (اختیاری)'],
    'accounting' => ['api_key' => 'کلید API', 'base_url' => 'آدرس سرویس'],
];
$titles = ['telegram' => 'تلگرام', 'bale' => 'بله', 'sms' => 'پنل پیامک', 'payment' => 'درگاه پرداخت', 'accounting' => 'حسابداری'];
$providers = [
    'telegram' => ['telegram' => 'Telegram Bot API'],
    'bale'     => ['bale' => 'Bale Bot API'],
    'sms'      => ['kavenegar' => 'کاوه‌نگار', 'melipayamak' => 'ملی‌پیامک', 'smsir' => 'SMS.ir'],
    'payment'  => ['zarinpal' => 'زرین‌پال', 'idpay' => 'IDPay', 'zibal' => 'زیبال', 'nextpay' => 'NextPay'],
    'accounting' => ['hesabfa' => 'حسابفا', 'holoo' => 'هلو', 'sepidar' => 'سپیدار'],
];
?>
<p class="muted">توکن‌ها و کلیدها فقط روی سرور ذخیره می‌شوند و هرگز در صفحه عمومی نمایش داده نمی‌شوند.</p>

<div class="cards-col">
<?php foreach ($integrations as $ch => $data): ?>
    <div class="card">
        <h3><?= $titles[$ch] ?? $ch ?> <?php if ($data['is_active']): ?><span class="pill on">فعال</span><?php endif; ?></h3>
        <form method="post" action="<?= url('admin/integrations/' . $ch) ?>">
            <?= csrf_field() ?>
            <label>سرویس‌دهنده
                <select name="provider">
                    <option value="">— انتخاب —</option>
                    <?php foreach ($providers[$ch] ?? [] as $pv => $plabel): ?>
                        <option value="<?= e($pv) ?>" <?= ($data['provider'] ?? '') === $pv ? 'selected' : '' ?>><?= e($plabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php foreach ($fields[$ch] ?? [] as $key => $label): ?>
                <label><?= e($label) ?>
                    <input type="text" name="config[<?= e($key) ?>]" value="<?= e($data['config'][$key] ?? '') ?>" autocomplete="off">
                </label>
            <?php endforeach; ?>
            <label class="chk"><input type="checkbox" name="is_active" <?= $data['is_active'] ? 'checked' : '' ?>> فعال باشد</label>
            <button class="btn-primary">ذخیره</button>
        </form>
    </div>
<?php endforeach; ?>
</div>
