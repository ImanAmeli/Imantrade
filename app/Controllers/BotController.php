<?php
namespace App\Controllers;

use App\Core\Database;
use App\Services\BotService;
use App\Services\Integration;

/**
 * Public webhook endpoint for Telegram / Bale bots.
 *
 * URL shape:  /bot/{channel}/{tenant}/{secret}
 * The secret is generated per-tenant when the integration is saved and is
 * the only thing protecting this endpoint, so it must stay private.
 *
 * Flow:
 *   user opens bot -> /start -> we ask them to share their phone (contact
 *   keyboard) -> Telegram/Bale sends a `contact` update -> we link the
 *   chat_id to the matching customer (creating one if needed) so future
 *   campaign / birthday PMs reach them.
 */
class BotController
{
    public function webhook(string $channel, string $tenant, string $secret): void
    {
        // always answer 200 fast; bots retry on non-2xx
        header('Content-Type: application/json; charset=utf-8');

        if (!in_array($channel, ['telegram', 'bale'], true)) {
            echo '{"ok":false}';
            return;
        }
        $tenantId = (int) $tenant;
        $integration = Integration::get($tenantId, $channel);
        $cfg = $integration['config'];

        // verify shared secret (path) and, for Telegram, the header token too
        $expected = $cfg['webhook_secret'] ?? '';
        $headerTok = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? null;
        if ($expected === '' || !hash_equals($expected, $secret)
            || ($channel === 'telegram' && $headerTok !== null && !hash_equals($expected, $headerTok))) {
            http_response_code(403);
            echo '{"ok":false}';
            return;
        }

        $update = json_decode(file_get_contents('php://input') ?: '', true);
        $message = $update['message'] ?? null;
        if (!$message) {
            echo '{"ok":true}';
            return;
        }

        $token  = $cfg['bot_token'] ?? '';
        $chatId = $message['chat']['id'] ?? null;
        if (!$token || $chatId === null) {
            echo '{"ok":true}';
            return;
        }

        // 1) shared a contact -> link chat id to a customer
        if (!empty($message['contact']['phone_number'])) {
            $this->linkContact($channel, $tenantId, $token, $message);
            echo '{"ok":true}';
            return;
        }

        $text = trim($message['text'] ?? '');

        // 2) /start (or any first touch) -> ask for phone
        if ($text === '/start' || $text === '') {
            $brand = Database::scalar('SELECT name FROM tenants WHERE id = ?', [$tenantId]) ?: '';
            BotService::sendMessage(
                $channel, $token, $chatId,
                "به ربات {$brand} خوش آمدید! 🌟\n"
                . "برای دریافت پیشنهادها و تخفیف تولد، لطفاً شماره تماس خود را با دکمه‌ی زیر ارسال کنید.",
                BotService::contactKeyboard()
            );
            echo '{"ok":true}';
            return;
        }

        // 3) anything else -> short help
        BotService::sendMessage($channel, $token, $chatId,
            'برای اتصال حساب، دستور /start را بزنید و شماره تماس‌تان را ارسال کنید.');
        echo '{"ok":true}';
    }

    private function linkContact(string $channel, int $tenantId, string $token, array $message): void
    {
        $contact = $message['contact'];
        $chatId  = (string) $message['chat']['id'];

        // only accept the sender's own contact (Telegram sets contact.user_id)
        if (isset($contact['user_id'], $message['from']['id'])
            && (int) $contact['user_id'] !== (int) $message['from']['id']) {
            BotService::sendMessage($channel, $token, $chatId, 'لطفاً شماره‌ی خودتان را ارسال کنید.');
            return;
        }

        $phone = normalize_ir_phone((string) $contact['phone_number']);
        if (!$phone) {
            BotService::sendMessage($channel, $token, $chatId, 'شماره معتبر نبود. دوباره تلاش کنید.');
            return;
        }

        $field = $channel === 'bale' ? 'bale_chat_id' : 'telegram_chat_id';
        $existing = Database::one(
            'SELECT id FROM customers WHERE tenant_id = ? AND phone = ?',
            [$tenantId, $phone]
        );
        if ($existing) {
            Database::run(
                "UPDATE customers SET {$field} = ? WHERE id = ?",
                [$chatId, $existing['id']]
            );
        } else {
            Database::run(
                "INSERT INTO customers (tenant_id, first_name, last_name, phone, {$field})
                 VALUES (?,?,?,?,?)",
                [
                    $tenantId,
                    trim($contact['first_name'] ?? 'مشتری'),
                    trim($contact['last_name'] ?? ''),
                    $phone,
                    $chatId,
                ]
            );
        }

        BotService::sendMessage($channel, $token, $chatId,
            'حساب شما با موفقیت متصل شد ✅ از این پس پیشنهادهای ویژه را دریافت می‌کنید.',
            ['remove_keyboard' => true]);
    }
}
