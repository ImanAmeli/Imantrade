<?php
namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Upload;

class ThemeController extends AdminController
{
    private const TEMPLATES = ['classic', 'modern', 'elegant', 'dark'];

    public function index(): void
    {
        $theme = Database::one('SELECT * FROM themes WHERE tenant_id = ?', [$this->tenantId]);
        if (!$theme) {
            Database::insert('INSERT INTO themes (tenant_id) VALUES (?)', [$this->tenantId]);
            $theme = Database::one('SELECT * FROM themes WHERE tenant_id = ?', [$this->tenantId]);
        }
        $this->view('admin/theme', ['theme' => $theme, 'templates' => self::TEMPLATES]);
    }

    public function save(): void
    {
        $this->guardCsrf('admin/theme');
        $theme = Database::one('SELECT * FROM themes WHERE tenant_id = ?', [$this->tenantId]);

        $template = in_array($_POST['template'] ?? '', self::TEMPLATES, true) ? $_POST['template'] : 'classic';

        $logo = Upload::image('logo', $this->tenantId) ?? ($theme['logo_path'] ?? null);
        $hero = Upload::image('hero', $this->tenantId) ?? ($theme['hero_image'] ?? null);

        Database::run(
            'UPDATE themes SET template=?, primary_color=?, secondary_color=?, bg_color=?, text_color=?,
                    font_family=?, logo_path=?, hero_image=?, hero_title=?, hero_subtitle=?, custom_css=?
             WHERE tenant_id=?',
            [
                $template,
                $this->color($_POST['primary_color'] ?? '#c0392b'),
                $this->color($_POST['secondary_color'] ?? '#2c3e50'),
                $this->color($_POST['bg_color'] ?? '#faf7f2'),
                $this->color($_POST['text_color'] ?? '#2c3e50'),
                preg_replace('/[^A-Za-z0-9 _-]/', '', $_POST['font_family'] ?? 'Vazirmatn'),
                $logo, $hero,
                trim($_POST['hero_title'] ?? ''),
                trim($_POST['hero_subtitle'] ?? ''),
                mb_substr(trim($_POST['custom_css'] ?? ''), 0, 5000),
                $this->tenantId,
            ]
        );
        flash('success', 'قالب ذخیره شد.');
        redirect('admin/theme');
    }

    private function color(string $v): string
    {
        return preg_match('/^#[0-9a-fA-F]{3,8}$/', $v) ? $v : '#000000';
    }
}
