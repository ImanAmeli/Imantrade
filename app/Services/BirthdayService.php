<?php
namespace App\Services;

use App\Core\Database;

/**
 * Sends birthday greetings (+ discount note) to customers whose birthday
 * is today, once per year. Designed to be triggered by a daily cron:
 *
 *   php /path/to/cron/daily.php
 *
 * Falls back gracefully if no messaging channel is configured (logged only).
 */
class BirthdayService
{
    public static function runForAllTenants(): int
    {
        $tenants = Database::all('SELECT id FROM tenants WHERE is_active = 1');
        $total = 0;
        foreach ($tenants as $t) {
            $total += self::runForTenant((int) $t['id']);
        }
        return $total;
    }

    public static function runForTenant(int $tenantId): int
    {
        $year = (int) date('Y');
        // Match by month/day so it works regardless of birth year.
        $customers = Database::all(
            'SELECT c.* FROM customers c
             WHERE c.tenant_id = ?
               AND c.birthdate IS NOT NULL
               AND MONTH(c.birthdate) = MONTH(CURDATE())
               AND DAY(c.birthdate)   = DAY(CURDATE())
               AND c.consent_sms = 1
               AND NOT EXISTS (
                    SELECT 1 FROM birthday_log b
                    WHERE b.customer_id = c.id AND b.year_sent = ?
               )',
            [$tenantId, $year]
        );

        if (!$customers) {
            return 0;
        }

        $tenant = Database::one('SELECT name FROM tenants WHERE id = ?', [$tenantId]);
        $brand = $tenant['name'] ?? '';

        // Prefer an active channel: sms, else telegram, else bale.
        $channel = self::preferredChannel($tenantId);

        $sent = 0;
        foreach ($customers as $c) {
            $name = trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''));
            $body = "{$name} عزیز، تولدت مبارک! 🎉\n"
                  . "به مناسبت تولدت یک تخفیف ویژه برات فعال کردیم. منتظر دیدنت هستیم.\n"
                  . ($brand ? "— {$brand}" : '');
            if (Messenger::send($tenantId, $channel, $c, $body, 'birthday')) {
                $sent++;
            }
            Database::run(
                'INSERT IGNORE INTO birthday_log (tenant_id, customer_id, year_sent) VALUES (?,?,?)',
                [$tenantId, $c['id'], $year]
            );
        }
        return $sent;
    }

    private static function preferredChannel(int $tenantId): string
    {
        foreach (['sms', 'telegram', 'bale'] as $ch) {
            if (Integration::get($tenantId, $ch)['is_active']) {
                return $ch;
            }
        }
        return 'sms';
    }
}
