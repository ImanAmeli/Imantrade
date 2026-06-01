<?php
namespace App\Services;

use App\Core\Database;

/**
 * Unified outbound messaging for SMS / Telegram / Bale.
 *
 * Every send is recorded in message_log. Real network delivery only
 * happens when the channel integration is active AND configured; otherwise
 * the message is logged as "queued" so nothing breaks on a fresh install
 * or an offline shared host. Swap in real provider calls inside the
 * send* methods — the call sites never change.
 */
class Messenger
{
    /**
     * Send one message to a customer over a channel.
     *
     * @return bool whether it was accepted for delivery
     */
    public static function send(
        int $tenantId,
        string $channel,
        array $customer,
        string $body,
        string $kind = 'campaign',
        ?int $campaignId = null
    ): bool {
        $integration = Integration::get($tenantId, $channel);
        $recipient = $channel === 'sms'
            ? ($customer['phone'] ?? '')
            : ($customer['phone'] ?? ''); // chat-id resolution handled per provider

        $status = 'queued';
        $error  = null;

        // resolve a better recipient label for logging (chat id for bots)
        if ($channel === 'telegram') {
            $recipient = $customer['telegram_chat_id'] ?? $recipient;
        } elseif ($channel === 'bale') {
            $recipient = $customer['bale_chat_id'] ?? $recipient;
        }

        if ($integration['is_active'] && !empty($integration['config'])) {
            try {
                // null => not deliverable yet (queued), true => sent, false => failed
                $ok = match ($channel) {
                    'sms'      => self::sendSms($integration, $recipient, $body),
                    'telegram' => self::sendBot('telegram', $integration, $customer, $body),
                    'bale'     => self::sendBot('bale', $integration, $customer, $body),
                    default    => false,
                };
                $status = $ok === null ? 'queued' : ($ok ? 'sent' : 'failed');
            } catch (\Throwable $ex) {
                $status = 'failed';
                $error  = mb_substr($ex->getMessage(), 0, 250);
            }
        }

        Database::insert(
            'INSERT INTO message_log (tenant_id, customer_id, campaign_id, channel, kind, recipient, body, status, error)
             VALUES (?,?,?,?,?,?,?,?,?)',
            [$tenantId, $customer['id'] ?? null, $campaignId, $channel, $kind, $recipient, $body, $status, $error]
        );
        return $status !== 'failed';
    }

    // --- Provider adapters (real HTTP guarded by config) ---

    /** @return bool|null  null when no provider is wired yet (queued) */
    private static function sendSms(array $integration, string $to, string $body): ?bool
    {
        $cfg = $integration['config'];
        // Example for Kavenegar; adapt to your panel (Melipayamak, SMS.ir...).
        if (($integration['provider'] ?? '') === 'kavenegar' && !empty($cfg['api_key'])) {
            $url = "https://api.kavenegar.com/v1/{$cfg['api_key']}/sms/send.json";
            $payload = http_build_query([
                'receptor' => $to,
                'sender'   => $cfg['sender'] ?? '',
                'message'  => $body,
            ]);
            return self::httpPost($url, $payload, []);
        }
        // No supported provider wired yet -> log as queued, not failed.
        return null;
    }

    /** @return bool|null  null when not deliverable yet (no chat id) */
    private static function sendBot(string $channel, array $integration, array $customer, string $body): ?bool
    {
        $cfg = $integration['config'];
        $field = $channel === 'bale' ? 'bale_chat_id' : 'telegram_chat_id';
        $chatId = $customer[$field] ?? ($cfg['default_chat_id'] ?? null);
        if (empty($cfg['bot_token']) || empty($chatId)) {
            return null; // customer hasn't linked the bot yet -> queued
        }
        $res = BotService::sendMessage($channel, $cfg['bot_token'], $chatId, $body);
        return !empty($res['ok']);
    }

    private static function httpPost(string $url, string $body, array $headers): bool
    {
        if (!function_exists('curl_init')) {
            return false;
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code >= 200 && $code < 300;
    }
}
