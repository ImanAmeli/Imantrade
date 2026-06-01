<?php
namespace App\Core;

/**
 * Tiny template renderer. Views are plain PHP files under app/Views.
 * Layout: 'public' or 'admin' (or null for bare partial).
 */
class View
{
    public static function render(string $view, array $data = [], ?string $layout = null): void
    {
        echo self::capture($view, $data, $layout);
    }

    public static function capture(string $view, array $data = [], ?string $layout = null): string
    {
        $content = self::renderFile($view, $data);
        if ($layout === null) {
            return $content;
        }
        return self::renderFile('layouts/' . $layout, array_merge($data, ['content' => $content]));
    }

    private static function renderFile(string $view, array $data): string
    {
        $file = __DIR__ . '/../Views/' . $view . '.php';
        if (!is_file($file)) {
            return "<!-- view not found: {$view} -->";
        }
        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        return (string) ob_get_clean();
    }
}
