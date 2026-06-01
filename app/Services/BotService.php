<?php
namespace App\Services;

/**
 * Telegram & Bale Bot API client.
 *
 * Bale exposes a Telegram-compatible Bot API, so the same methods work for
 * both — only the API base host differs.
 */
class BotService
{
    public static function apiBase(string $channel, string $token): string
    {
        return $channel === 'bale'
            ? "https://tapi.bale.ai/bot{$token}"
            : "https://api.telegram.org/bot{$token}";
    }

    /** Send a text message, optionally with a reply markup (keyboard). */
    public static function sendMessage(string $channel, string $token, $chatId, string $text, ?array $replyMarkup = null): array
    {
        $payload = ['chat_id' => $chatId, 'text' => $text];
        if ($replyMarkup !== null) {
            $payload['reply_markup'] = $replyMarkup;
        }
        return self::request(self::apiBase($channel, $token) . '/sendMessage', $payload);
    }

    /** Register this app's webhook URL with the bot. */
    public static function setWebhook(string $channel, string $token, string $url, ?string $secret = null): array
    {
        $payload = ['url' => $url, 'allowed_updates' => ['message']];
        // Telegram supports a secret token header it echoes back; Bale ignores it.
        if ($secret && $channel === 'telegram') {
            $payload['secret_token'] = $secret;
        }
        return self::request(self::apiBase($channel, $token) . '/setWebhook', $payload);
    }

    /** A one-time keyboard that asks the user to share their phone number. */
    public static function contactKeyboard(string $buttonText = 'ارسال شماره تماس 📱'): array
    {
        return [
            'keyboard' => [[['text' => $buttonText, 'request_contact' => true]]],
            'resize_keyboard'   => true,
            'one_time_keyboard' => true,
        ];
    }

    /** POST JSON and decode the response. */
    public static function request(string $url, array $payload): array
    {
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'error' => 'curl_unavailable'];
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 12,
        ]);
        $raw  = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($raw === false) {
            return ['ok' => false, 'error' => 'request_failed', 'http' => $code];
        }
        $decoded = json_decode((string) $raw, true);
        return is_array($decoded) ? $decoded : ['ok' => false, 'error' => 'bad_response', 'http' => $code];
    }
}
