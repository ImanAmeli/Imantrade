<?php
namespace App\Controllers\Admin;

use App\Core\Database;

class CategoryController extends AdminController
{
    public function index(): void
    {
        $categories = Database::all(
            'SELECT c.*, (SELECT COUNT(*) FROM items i WHERE i.category_id = c.id) AS item_count
             FROM categories c WHERE c.tenant_id = ? ORDER BY c.sort_order, c.id',
            [$this->tenantId]
        );
        $this->view('admin/categories', compact('categories'));
    }

    public function store(): void
    {
        $this->guardCsrf('admin/categories');
        $name = trim($_POST['name'] ?? '');
        if ($name !== '') {
            Database::insert(
                'INSERT INTO categories (tenant_id, name, sort_order) VALUES (?,?,?)',
                [$this->tenantId, $name, (int) ($_POST['sort_order'] ?? 0)]
            );
            flash('success', 'دسته‌بندی اضافه شد.');
        }
        redirect('admin/categories');
    }

    public function update(string $id): void
    {
        $this->guardCsrf('admin/categories');
        Database::run(
            'UPDATE categories SET name = ?, sort_order = ?, is_active = ? WHERE id = ? AND tenant_id = ?',
            [
                trim($_POST['name'] ?? ''),
                (int) ($_POST['sort_order'] ?? 0),
                isset($_POST['is_active']) ? 1 : 0,
                (int) $id, $this->tenantId,
            ]
        );
        flash('success', 'دسته‌بندی به‌روزرسانی شد.');
        redirect('admin/categories');
    }

    public function destroy(string $id): void
    {
        $this->guardCsrf('admin/categories');
        Database::run('DELETE FROM categories WHERE id = ? AND tenant_id = ?', [(int) $id, $this->tenantId]);
        flash('success', 'دسته‌بندی حذف شد.');
        redirect('admin/categories');
    }
}
