<?php
namespace App\Controllers\Admin;

use App\Core\Database;
use App\Services\AccountingService;
use App\Services\RankingService;

class CustomerController extends AdminController
{
    public function index(): void
    {
        $q = trim($_GET['q'] ?? '');
        $sql = 'SELECT * FROM customers WHERE tenant_id = ?';
        $params = [$this->tenantId];
        if ($q !== '') {
            $sql .= ' AND (first_name LIKE ? OR last_name LIKE ? OR phone LIKE ?)';
            $like = "%{$q}%";
            array_push($params, $like, $like, $like);
        }
        $sql .= ' ORDER BY rank_score DESC, id DESC LIMIT 500';
        $customers = Database::all($sql, $params);
        $topMonth = RankingService::topCustomers($this->tenantId, 'month', 10);
        $this->view('admin/customers', compact('customers', 'topMonth', 'q'));
    }

    public function recompute(): void
    {
        $this->guardCsrf('admin/customers');
        RankingService::recomputeCustomers($this->tenantId);
        flash('success', 'رتبه‌بندی مشتریان بازمحاسبه شد.');
        redirect('admin/customers');
    }

    public function syncAccounting(): void
    {
        $this->guardCsrf('admin/customers');
        $n = AccountingService::syncSpend($this->tenantId);
        flash($n ? 'success' : 'error', $n
            ? "اطلاعات {$n} مشتری از حسابداری همگام شد."
            : 'اتصال حسابداری فعال نیست یا داده‌ای دریافت نشد.');
        redirect('admin/customers');
    }
}
