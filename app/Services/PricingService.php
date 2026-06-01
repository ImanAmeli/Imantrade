<?php
namespace App\Services;

use App\Core\Database;

/**
 * Price logic: bulk shifts + active discount resolution.
 */
class PricingService
{
    /**
     * Bulk shift prices.
     *
     * @param string $scope     all | category
     * @param int    $direction +1 (up) or -1 (down)  (caller passes 'up'|'down')
     */
    public static function bulkShift(
        int $tenantId,
        string $scope,
        ?int $targetId,
        string $mode,        // percent | fixed
        string $direction,   // up | down
        int $amount,
        ?int $userId = null
    ): int {
        $sign = $direction === 'down' ? -1 : 1;

        if ($mode === 'percent') {
            $factor = 1 + ($sign * $amount / 100);
            $expr = "ROUND(price * {$factor})";
        } else {
            $delta = $sign * $amount;
            // never below zero
            $expr = "GREATEST(0, price + ({$delta}))";
        }

        $sql = "UPDATE items SET price = {$expr} WHERE tenant_id = ?";
        $params = [$tenantId];
        if ($scope === 'category' && $targetId) {
            $sql .= ' AND category_id = ?';
            $params[] = $targetId;
        }
        $affected = Database::run($sql, $params);

        Database::insert(
            'INSERT INTO price_change_log (tenant_id, user_id, scope, target_id, mode, direction, amount, affected)
             VALUES (?,?,?,?,?,?,?,?)',
            [$tenantId, $userId, $scope, $targetId, $mode, $direction, $amount, $affected]
        );
        return $affected;
    }

    /**
     * Return the best active discount applicable to an item (or null).
     * Considers scope (all/category/item), date window and day-of-week.
     */
    public static function activeDiscountFor(array $item, array $discounts): ?array
    {
        $today = date('Y-m-d');
        // PHP: 0=Sunday..6=Saturday. We store 0=Sat..6=Fri for Iranian week.
        $iranDow = (int) ((date('w') + 1) % 7); // Sat=0
        $best = null;

        foreach ($discounts as $d) {
            if (!$d['is_active']) {
                continue;
            }
            if ($d['starts_at'] && $today < $d['starts_at']) {
                continue;
            }
            if ($d['ends_at'] && $today > $d['ends_at']) {
                continue;
            }
            if (!empty($d['days_of_week'])) {
                $days = array_map('intval', explode(',', $d['days_of_week']));
                if (!in_array($iranDow, $days, true)) {
                    continue;
                }
            }
            // scope match
            $applies = match ($d['scope']) {
                'all'      => true,
                'category' => (int) $d['target_id'] === (int) ($item['category_id'] ?? 0),
                'item'     => (int) $d['target_id'] === (int) $item['id'],
                default    => false,
            };
            if (!$applies) {
                continue;
            }
            $final = self::applyDiscount((int) $item['price'], $d);
            if ($best === null || $final < $best['final_price']) {
                $best = $d;
                $best['final_price'] = $final;
            }
        }
        return $best;
    }

    public static function applyDiscount(int $price, array $discount): int
    {
        if ($discount['type'] === 'percent') {
            return (int) max(0, round($price * (1 - $discount['value'] / 100)));
        }
        return (int) max(0, $price - (int) $discount['value']);
    }
}
