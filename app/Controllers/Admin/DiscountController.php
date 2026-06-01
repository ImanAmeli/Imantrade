<?php
namespace App\Controllers\Admin;

use App\Core\Database;

class DiscountController extends AdminController
{
    public function index(): void
    {
        $discounts = Database::all('SELECT * FROM discounts WHERE tenant_id = ? ORDER BY id DESC', [$this->tenantId]);
        $categories = Database::all('SELECT * FROM categories WHERE tenant_id = ? ORDER BY sort_order, id', [$this->tenantId]);
        $items = Database::all('SELECT id, name FROM items WHERE tenant_id = ? ORDER BY name', [$this->tenantId]);
        $this->view('admin/discounts', compact('discounts', 'categories', 'items'));
    }

    public function store(): void
    {
        $this->guardCsrf('admin/discounts');
        $scope = in_array($_POST['scope'] ?? '', ['all', 'category', 'item', 'tier'], true) ? $_POST['scope'] : 'all';
        $days = $_POST['days'] ?? [];
        $daysStr = is_array($days) && $days ? implode(',', array_map('intval', $days)) : null;

        Database::insert(
            'INSERT INTO discounts (tenant_id, name, type, value, scope, target_id, days_of_week, starts_at, ends_at, is_active)
             VALUES (?,?,?,?,?,?,?,?,?,1)',
            [
                $this->tenantId,
                trim($_POST['name'] ?? 'تخفیف'),
                ($_POST['type'] ?? 'percent') === 'fixed' ? 'fixed' : 'percent',
                (int) preg_replace('/\D/', '', $_POST['value'] ?? '0'),
                $scope,
                in_array($scope, ['category', 'item'], true) ? (int) ($_POST['target_id'] ?? 0) : null,
                $daysStr,
                $_POST['starts_at'] ?: null,
                $_POST['ends_at'] ?: null,
            ]
        );
        flash('success', 'تخفیف ایجاد شد.');
        redirect('admin/discounts');
    }

    public function toggle(string $id): void
    {
        $this->guardCsrf('admin/discounts');
        Database::run('UPDATE discounts SET is_active = 1 - is_active WHERE id = ? AND tenant_id = ?', [(int) $id, $this->tenantId]);
        redirect('admin/discounts');
    }

    public function destroy(string $id): void
    {
        $this->guardCsrf('admin/discounts');
        Database::run('DELETE FROM discounts WHERE id = ? AND tenant_id = ?', [(int) $id, $this->tenantId]);
        flash('success', 'تخفیف حذف شد.');
        redirect('admin/discounts');
    }
}
