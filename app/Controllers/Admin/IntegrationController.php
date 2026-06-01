<?php
namespace App\Controllers\Admin;

use App\Services\BotService;
use App\Services\Integration;

class IntegrationController extends AdminController
{
    private const CHANNELS = ['telegram', 'bale', 'sms', 'payment', 'accounting'];

    public function index(): void
    {
        $data = [];
        foreach (self::CHANNELS as $ch) {
            $data[$ch] = Integration::get($this->tenantId, $ch);
        }
        $this->view('admin/integrations', [
            'integrations' => $data,
            'tenantId'     => $this->tenantId,
            'webhookBase'  => $this->absoluteBase(),
        ]);
    }

    public function save(string $channel): void
    {
        $this->guardCsrf('admin/integrations');
        if (!in_array($channel, self::CHANNELS, true)) {
            redirect('admin/integrations');
        }
        $provider = trim($_POST['provider'] ?? '') ?: null;
        $active   = isset($_POST['is_active']);

        // collect arbitrary config key/values posted as config[key]=value
        $config = [];
        foreach (($_POST['config'] ?? []) as $k => $v) {
            $config[preg_replace('/[^a-z0-9_]/i', '', $k)] = trim((string) $v);
        }

        // bots need a stable per-tenant webhook secret; generate once and keep it
        if (in_array($channel, ['telegram', 'bale'], true)) {
            $existing = Integration::get($this->tenantId, $channel)['config'];
            $config['webhook_secret'] = $existing['webhook_secret'] ?? bin2hex(random_bytes(16));
        }

        Integration::save($this->tenantId, $channel, $provider, $config, $active);
        flash('success', 'تنظیمات اتصال ذخیره شد.');
        redirect('admin/integrations');
    }

    /** Register the webhook URL with Telegram/Bale. */
    public function setWebhook(string $channel): void
    {
        $this->guardCsrf('admin/integrations');
        if (!in_array($channel, ['telegram', 'bale'], true)) {
            redirect('admin/integrations');
        }
        $integration = Integration::get($this->tenantId, $channel);
        $cfg = $integration['config'];
        if (empty($cfg['bot_token']) || empty($cfg['webhook_secret'])) {
            flash('error', 'ابتدا توکن ربات را ذخیره کنید.');
            redirect('admin/integrations');
        }
        $url = $this->absoluteBase() . '/bot/' . $channel . '/' . $this->tenantId . '/' . $cfg['webhook_secret'];
        $res = BotService::setWebhook($channel, $cfg['bot_token'], $url, $cfg['webhook_secret']);
        if (!empty($res['ok'])) {
            flash('success', 'وب‌هوک با موفقیت ثبت شد. حالا ربات آماده است.');
        } else {
            flash('error', 'ثبت وب‌هوک ناموفق بود: ' . ($res['description'] ?? $res['error'] ?? 'نامشخص'));
        }
        redirect('admin/integrations');
    }

    /** Best-effort absolute base URL (scheme + host + base_path). */
    private function absoluteBase(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base   = rtrim((string) config('app.base_path'), '/');
        return $scheme . '://' . $host . $base;
    }
}
