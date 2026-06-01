<?php
namespace App\Services;

use App\Core\Database;

/**
 * Iranian accounting integration (extension point).
 *
 * Pulls customer purchase totals from an external accounting system
 * (Hesabfa, Holoo, Sepidar, Parsian ...) so customer ranking reflects
 * real-world spend, not just online orders. Until a provider is wired in,
 * syncSpend() is a no-op and ranking falls back to local order data.
 */
class AccountingService
{
    /**
     * Sync spend/orders from the accounting provider into customers.
     * Match key is phone number. Returns number of customers updated.
     */
    public static function syncSpend(int $tenantId): int
    {
        $integration = Integration::get($tenantId, 'accounting');
        if (!$integration['is_active'] || empty($integration['config'])) {
            return 0;
        }

        // Fetch external totals keyed by phone:
        //   $external = self::fetchFromProvider($integration);
        // Example shape: [ '09120000000' => ['spent'=>1234000, 'orders'=>5], ... ]
        $external = self::fetchFromProvider($integration);

        $updated = 0;
        foreach ($external as $phone => $data) {
            $updated += Database::run(
                'UPDATE customers SET total_spent = ?, orders_count = ? WHERE tenant_id = ? AND phone = ?',
                [(int) $data['spent'], (int) $data['orders'], $tenantId, $phone]
            );
        }
        if ($updated) {
            RankingService::recomputeCustomers($tenantId);
        }
        return $updated;
    }

    private static function fetchFromProvider(array $integration): array
    {
        // TODO: call the real accounting API (provider-specific) and
        // normalise to [phone => ['spent'=>int, 'orders'=>int]].
        return [];
    }
}
