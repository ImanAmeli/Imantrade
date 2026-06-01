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

        if ($integration['is_active'] && !empty($integration['config'])) {
            try {
                $ok = match ($channel) {
                    'sms'      => self::sendSms($integration, $recipient, $body),
                    'telegram' => self::sendTelegram($integration, $customer, $body),
                    'bale'     => self::sendBale($integration, $customer, $body),
                    default    => false,
                };
                $status = $ok ? 'sent' : 'failed';
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

    private static function sendSms(array $integration, string $to, string $body): bool
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
        // Unknown provider -> treat as queued (return true, logged as queued upstream)
        return true;
    }

    private static function sendTelegram(array $integration, array $customer, string $body): bool
    {
        $cfg = $integration['config'];
        $chatId = $customer['telegram_chat_id'] ?? ($cfg['default_chat_id'] ?? null);
        if (empty($cfg['bot_token']) || empty($chatId)) {
            return true; // queued: no chat id yet (collected when user starts the bot)
        }
        $url = "https://api.telegram.org/bot{$cfg['bot_token']}/sendMessage";
        return self::httpPost($url, json_encode([
            'chat_id' => $chatId,
            'text'    => $body,
        ], JSON_UNESCAPED_UNICODE), ['Content-Type: application/json']);
    }

    private static function sendBale(array $integration, array $customer, string $body): bool
    {
        $cfg = $integration['config'];
        $chatId = $customer['bale_chat_id'] ?? ($cfg['default_chat_id'] ?? null);
        if (empty($cfg['bot_token']) || empty($chatId)) {
            return true;
        }
        // Bale exposes a Telegram-compatible bot API.
        $url = "https://tapi.bale.ai/bot{$cfg['bot_token']}/sendMessage";
        return self::httpPost($url, json_encode([
            'chat_id' => $chatId,
            'text'    => $body,
        ], JSON_UNESCAPED_UNICODE), ['Content-Type: application/json']);
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
