<?php
namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Services\PricingService;

class PricingController extends AdminController
{
    public function index(): void
    {
        $categories = Database::all('SELECT * FROM categories WHERE tenant_id = ? ORDER BY sort_order, id', [$this->tenantId]);
        $log = Database::all(
            'SELECT * FROM price_change_log WHERE tenant_id = ? ORDER BY id DESC LIMIT 20',
            [$this->tenantId]
        );
        $this->view('admin/pricing', compact('categories', 'log'));
    }

    public function shift(): void
    {
        $this->guardCsrf('admin/pricing');
        $scope     = in_array($_POST['scope'] ?? '', ['all', 'category'], true) ? $_POST['scope'] : 'all';
        $targetId  = $scope === 'category' ? (int) ($_POST['category_id'] ?? 0) : null;
        $mode      = ($_POST['mode'] ?? 'percent') === 'fixed' ? 'fixed' : 'percent';
        $direction = ($_POST['direction'] ?? 'up') === 'down' ? 'down' : 'up';
        $amount    = (int) preg_replace('/\D/', '', $_POST['amount'] ?? '0');

        if ($amount <= 0) {
            flash('error', 'مقدار باید بزرگ‌تر از صفر باشد.');
            redirect('admin/pricing');
        }

        $affected = PricingService::bulkShift(
            $this->tenantId, $scope, $targetId, $mode, $direction, $amount,
            Auth::user()['id'] ?? null
        );
        flash('success', "قیمت {$affected} محصول به‌روزرسانی شد.");
        redirect('admin/pricing');
    }
}
