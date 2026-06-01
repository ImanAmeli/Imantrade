<?php
namespace App\Services;

use App\Core\Database;

/**
 * Customer ranking + item popularity.
 *
 * Customer rank_score and tier are derived from spend + order counts.
 * In production these aggregates are kept in sync from the Iranian
 * accounting integration (see AccountingService); here we also recompute
 * from local orders so the dashboard works standalone.
 */
class RankingService
{
    /** Recompute total_spent / orders_count / rank_score / tier for a tenant. */
    public static function recomputeCustomers(int $tenantId): void
    {
        $rows = Database::all(
            'SELECT c.id,
                    COALESCE(SUM(o.total),0)  AS spent,
                    COUNT(o.id)               AS cnt
             FROM customers c
             LEFT JOIN orders o
                    ON o.customer_id = c.id
                   AND o.status NOT IN ("canceled")
             WHERE c.tenant_id = ?
             GROUP BY c.id',
            [$tenantId]
        );

        foreach ($rows as $r) {
            $spent = (int) $r['spent'];
            $cnt   = (int) $r['cnt'];
            // score weights spend heavily, rewards frequency
            $score = (int) round($spent / 1000) + $cnt * 5;
            $tier  = self::tierFor($spent, $cnt);
            Database::run(
                'UPDATE customers SET total_spent = ?, orders_count = ?, rank_score = ?, tier = ? WHERE id = ?',
                [$spent, $cnt, $score, $tier, $r['id']]
            );
        }
    }

    public static function tierFor(int $spent, int $orders): string
    {
        return match (true) {
            $spent >= 10_000_000 || $orders >= 50 => 'platinum',
            $spent >=  3_000_000 || $orders >= 20 => 'gold',
            $spent >=  1_000_000 || $orders >= 8  => 'silver',
            default                                => 'bronze',
        };
    }

    /** Top customers for a period: 'week' | 'month' | 'all'. */
    public static function topCustomers(int $tenantId, string $period = 'month', int $limit = 10): array
    {
        $where = 'o.tenant_id = ? AND o.status NOT IN ("canceled")';
        $params = [$tenantId];
        if ($period === 'week') {
            $where .= ' AND o.created_at >= (NOW() - INTERVAL 7 DAY)';
        } elseif ($period === 'month') {
            $where .= ' AND o.created_at >= (NOW() - INTERVAL 30 DAY)';
        }
        return Database::all(
            "SELECT c.id, c.first_name, c.last_name, c.phone, c.tier,
                    SUM(o.total) AS spent, COUNT(o.id) AS orders
             FROM orders o
             JOIN customers c ON c.id = o.customer_id
             WHERE {$where}
             GROUP BY c.id
             ORDER BY spent DESC
             LIMIT {$limit}",
            $params
        );
    }

    /** Recompute item rating cache + popularity rank from ratings/orders. */
    public static function recomputeItemRating(int $itemId): void
    {
        $agg = Database::one(
            'SELECT COUNT(*) c, COALESCE(SUM(stars),0) s FROM ratings WHERE item_id = ?',
            [$itemId]
        );
        Database::run(
            'UPDATE items SET rating_count = ?, rating_sum = ? WHERE id = ?',
            [(int) $agg['c'], (int) $agg['s'], $itemId]
        );
    }

    /** Most popular items (by order_count, then rating). */
    public static function popularItems(int $tenantId, int $limit = 8): array
    {
        return Database::all(
            'SELECT * FROM items
             WHERE tenant_id = ? AND is_available = 1
             ORDER BY order_count DESC,
                      (rating_sum / GREATEST(rating_count,1)) DESC
             LIMIT ' . (int) $limit,
            [$tenantId]
        );
    }
}
