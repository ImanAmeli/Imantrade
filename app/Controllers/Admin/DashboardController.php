<?php
namespace App\Controllers\Admin;

use App\Core\Database;
use App\Services\RankingService;

class DashboardController extends AdminController
{
    public function index(): void
    {
        $t = $this->tenantId;
        $stats = [
            'items'      => (int) Database::scalar('SELECT COUNT(*) FROM items WHERE tenant_id = ?', [$t]),
            'available'  => (int) Database::scalar('SELECT COUNT(*) FROM items WHERE tenant_id = ? AND is_available = 1', [$t]),
            'categories' => (int) Database::scalar('SELECT COUNT(*) FROM categories WHERE tenant_id = ?', [$t]),
            'customers'  => (int) Database::scalar('SELECT COUNT(*) FROM customers WHERE tenant_id = ?', [$t]),
            'orders'     => (int) Database::scalar('SELECT COUNT(*) FROM orders WHERE tenant_id = ?', [$t]),
        ];
        $topMonth = RankingService::topCustomers($t, 'month', 5);
        $topWeek  = RankingService::topCustomers($t, 'week', 5);
        $popular  = RankingService::popularItems($t, 5);

        // upcoming birthdays (next 7 days)
        $birthdays = Database::all(
            'SELECT first_name, last_name, phone, birthdate
             FROM customers
             WHERE tenant_id = ? AND birthdate IS NOT NULL
               AND ( DAYOFYEAR(birthdate) BETWEEN DAYOFYEAR(CURDATE()) AND DAYOFYEAR(CURDATE())+7
                  OR DAYOFYEAR(birthdate) <= 7 AND DAYOFYEAR(CURDATE()) >= 358 )
             ORDER BY DAYOFYEAR(birthdate) LIMIT 10',
            [$t]
        );

        $this->view('admin/dashboard', compact('stats', 'topMonth', 'topWeek', 'popular', 'birthdays'));
    }
}
