<?php
namespace App\Controllers\Admin;

use App\Core\Database;
use App\Services\Messenger;

class CampaignController extends AdminController
{
    public function index(): void
    {
        $campaigns = Database::all('SELECT * FROM campaigns WHERE tenant_id = ? ORDER BY id DESC', [$this->tenantId]);
        $this->view('admin/campaigns', compact('campaigns'));
    }

    public function store(): void
    {
        $this->guardCsrf('admin/campaigns');
        $channel = in_array($_POST['channel'] ?? '', ['sms', 'telegram', 'bale'], true) ? $_POST['channel'] : 'sms';
        Database::insert(
            'INSERT INTO campaigns (tenant_id, title, body, channel, min_age, max_age, tier)
             VALUES (?,?,?,?,?,?,?)',
            [
                $this->tenantId,
                trim($_POST['title'] ?? 'کمپین'),
                trim($_POST['body'] ?? ''),
                $channel,
                $_POST['min_age'] !== '' ? (int) $_POST['min_age'] : null,
                $_POST['max_age'] !== '' ? (int) $_POST['max_age'] : null,
                $_POST['tier'] ?: null,
            ]
        );
        flash('success', 'کمپین ذخیره شد. حالا می‌توانید ارسالش کنید.');
        redirect('admin/campaigns');
    }

    public function send(string $id): void
    {
        $this->guardCsrf('admin/campaigns');
        $c = Database::one('SELECT * FROM campaigns WHERE id = ? AND tenant_id = ?', [(int) $id, $this->tenantId]);
        if (!$c) {
            redirect('admin/campaigns');
        }

        // build audience with age / tier targeting
        $sql = 'SELECT * FROM customers WHERE tenant_id = ? AND consent_sms = 1';
        $params = [$this->tenantId];
        if ($c['tier']) {
            $sql .= ' AND tier = ?';
            $params[] = $c['tier'];
        }
        if ($c['min_age'] !== null) {
            $sql .= ' AND birthdate IS NOT NULL AND TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) >= ?';
            $params[] = (int) $c['min_age'];
        }
        if ($c['max_age'] !== null) {
            $sql .= ' AND birthdate IS NOT NULL AND TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) <= ?';
            $params[] = (int) $c['max_age'];
        }
        $audience = Database::all($sql, $params);

        $sent = 0;
        foreach ($audience as $cust) {
            if (Messenger::send($this->tenantId, $c['channel'], $cust, $c['body'], 'campaign', (int) $c['id'])) {
                $sent++;
            }
        }
        Database::run('UPDATE campaigns SET status = "sent", sent_count = ? WHERE id = ?', [$sent, (int) $id]);
        flash('success', "کمپین برای {$sent} مخاطب ثبت/ارسال شد.");
        redirect('admin/campaigns');
    }
}
