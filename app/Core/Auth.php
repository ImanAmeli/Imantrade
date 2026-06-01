<?php
namespace App\Core;

/**
 * Session-based authentication for dashboard users.
 */
class Auth
{
    public static function attempt(string $email, string $password): bool
    {
        $user = Database::one(
            'SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1',
            [$email]
        );
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        // refresh hash if algorithm changed
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            Database::run('UPDATE users SET password_hash = ? WHERE id = ?', [
                password_hash($password, PASSWORD_DEFAULT), $user['id'],
            ]);
        }
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'        => (int) $user['id'],
            'tenant_id' => $user['tenant_id'] !== null ? (int) $user['tenant_id'] : null,
            'name'      => $user['name'],
            'role'      => $user['role'],
        ];
        return true;
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function tenantId(): ?int
    {
        return $_SESSION['user']['tenant_id'] ?? null;
    }

    public static function isSuperAdmin(): bool
    {
        return (($_SESSION['user']['role'] ?? '') === 'superadmin');
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            redirect('admin/login');
        }
    }

    public static function logout(): void
    {
        unset($_SESSION['user']);
        session_regenerate_id(true);
    }
}
