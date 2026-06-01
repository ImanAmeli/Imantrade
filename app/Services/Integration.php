<?php
namespace App\Services;

use App\Core\Database;

/**
 * Loads/saves per-tenant channel configuration (tokens, endpoints).
 */
class Integration
{
    /** Return decoded config for a channel, or [] if not set. */
    public static function get(int $tenantId, string $channel): array
    {
        $row = Database::one(
            'SELECT * FROM integrations WHERE tenant_id = ? AND channel = ? LIMIT 1',
            [$tenantId, $channel]
        );
        if (!$row) {
            return ['is_active' => 0, 'provider' => null, 'config' => []];
        }
        return [
            'is_active' => (int) $row['is_active'],
            'provider'  => $row['provider'],
            'config'    => json_decode($row['config_json'] ?? '[]', true) ?: [],
        ];
    }

    public static function save(int $tenantId, string $channel, ?string $provider, array $config, bool $active): void
    {
        Database::run(
            'INSERT INTO integrations (tenant_id, channel, provider, config_json, is_active)
             VALUES (?,?,?,?,?)
             ON DUPLICATE KEY UPDATE provider = VALUES(provider),
                                     config_json = VALUES(config_json),
                                     is_active = VALUES(is_active)',
            [$tenantId, $channel, $provider, json_encode($config, JSON_UNESCAPED_UNICODE), $active ? 1 : 0]
        );
    }
}
