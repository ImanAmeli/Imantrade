<?php
namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Upload;

class ItemController extends AdminController
{
    public function index(): void
    {
        $filterCat = (int) ($_GET['category'] ?? 0);
        $sql = 'SELECT i.*, c.name AS category_name
                FROM items i LEFT JOIN categories c ON c.id = i.category_id
                WHERE i.tenant_id = ?';
        $params = [$this->tenantId];
        if ($filterCat) {
            $sql .= ' AND i.category_id = ?';
            $params[] = $filterCat;
        }
        $sql .= ' ORDER BY i.is_featured DESC, i.sort_order, i.id';
        $items = Database::all($sql, $params);
        $categories = $this->categories();
        $this->view('admin/items', compact('items', 'categories', 'filterCat'));
    }

    public function create(): void
    {
        $this->view('admin/item_form', [
            'item'       => null,
            'categories' => $this->categories(),
        ]);
    }

    public function edit(string $id): void
    {
        $item = Database::one('SELECT * FROM items WHERE id = ? AND tenant_id = ?', [(int) $id, $this->tenantId]);
        if (!$item) {
            redirect('admin/items');
        }
        $this->view('admin/item_form', [
            'item'       => $item,
            'categories' => $this->categories(),
        ]);
    }

    public function store(): void
    {
        $this->guardCsrf('admin/items');
        $data = $this->input();
        $image = Upload::image('image', $this->tenantId);
        Database::insert(
            'INSERT INTO items (tenant_id, category_id, name, description, price, image_path, is_available, is_featured, sort_order)
             VALUES (?,?,?,?,?,?,?,?,?)',
            [
                $this->tenantId, $data['category_id'], $data['name'], $data['description'],
                $data['price'], $image, $data['is_available'], $data['is_featured'], $data['sort_order'],
            ]
        );
        flash('success', 'محصول اضافه شد.');
        redirect('admin/items');
    }

    public function update(string $id): void
    {
        $this->guardCsrf('admin/items');
        $item = Database::one('SELECT * FROM items WHERE id = ? AND tenant_id = ?', [(int) $id, $this->tenantId]);
        if (!$item) {
            redirect('admin/items');
        }
        $data = $this->input();
        $image = Upload::image('image', $this->tenantId);
        if ($image) {
            Upload::delete($item['image_path']);
        } else {
            $image = $item['image_path'];
        }
        Database::run(
            'UPDATE items SET category_id=?, name=?, description=?, price=?, image_path=?,
                    is_available=?, is_featured=?, sort_order=? WHERE id=? AND tenant_id=?',
            [
                $data['category_id'], $data['name'], $data['description'], $data['price'], $image,
                $data['is_available'], $data['is_featured'], $data['sort_order'], (int) $id, $this->tenantId,
            ]
        );
        flash('success', 'محصول به‌روزرسانی شد.');
        redirect('admin/items');
    }

    public function toggle(string $id): void
    {
        $this->guardCsrf('admin/items');
        Database::run(
            'UPDATE items SET is_available = 1 - is_available WHERE id = ? AND tenant_id = ?',
            [(int) $id, $this->tenantId]
        );
        redirect('admin/items');
    }

    public function feature(string $id): void
    {
        $this->guardCsrf('admin/items');
        Database::run(
            'UPDATE items SET is_featured = 1 - is_featured WHERE id = ? AND tenant_id = ?',
            [(int) $id, $this->tenantId]
        );
        redirect('admin/items');
    }

    public function destroy(string $id): void
    {
        $this->guardCsrf('admin/items');
        $item = Database::one('SELECT image_path FROM items WHERE id = ? AND tenant_id = ?', [(int) $id, $this->tenantId]);
        if ($item) {
            Upload::delete($item['image_path']);
            Database::run('DELETE FROM items WHERE id = ? AND tenant_id = ?', [(int) $id, $this->tenantId]);
            flash('success', 'محصول حذف شد.');
        }
        redirect('admin/items');
    }

    private function input(): array
    {
        $catId = (int) ($_POST['category_id'] ?? 0);
        return [
            'category_id'  => $catId ?: null,
            'name'         => trim($_POST['name'] ?? ''),
            'description'  => trim($_POST['description'] ?? ''),
            'price'        => (int) preg_replace('/\D/', '', $_POST['price'] ?? '0'),
            'is_available' => isset($_POST['is_available']) ? 1 : 0,
            'is_featured'  => isset($_POST['is_featured']) ? 1 : 0,
            'sort_order'   => (int) ($_POST['sort_order'] ?? 0),
        ];
    }

    private function categories(): array
    {
        return Database::all(
            'SELECT * FROM categories WHERE tenant_id = ? ORDER BY sort_order, id',
            [$this->tenantId]
        );
    }
}
