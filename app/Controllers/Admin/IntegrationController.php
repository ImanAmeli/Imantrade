<?php
namespace App\Controllers\Admin;

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
        $this->view('admin/integrations', ['integrations' => $data]);
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

        Integration::save($this->tenantId, $channel, $provider, $config, $active);
        flash('success', 'تنظیمات اتصال ذخیره شد.');
        redirect('admin/integrations');
    }
}
