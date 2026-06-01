<?php
namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

/**
 * Base for dashboard controllers: enforces login + resolves the
 * active tenant for the logged-in user.
 */
abstract class AdminController
{
    protected int $tenantId;

    public function __construct()
    {
        Auth::requireLogin();
        // Super admin without a tenant context acts on the first tenant
        // (a full tenant switcher can be layered on later).
        $tid = Auth::tenantId();
        if ($tid === null) {
            $tid = (int) (Database::scalar('SELECT id FROM tenants ORDER BY id LIMIT 1') ?: 0);
        }
        $this->tenantId = (int) $tid;
    }

    protected function view(string $view, array $data = []): void
    {
        $data['_tenant'] = Database::one('SELECT * FROM tenants WHERE id = ?', [$this->tenantId]);
        $data['_user']   = Auth::user();
        View::render($view, $data, 'admin');
    }

    protected function guardCsrf(string $redirectTo): void
    {
        if (!csrf_check()) {
            flash('error', 'نشست شما منقضی شده. دوباره تلاش کنید.');
            redirect($redirectTo);
        }
    }
}
