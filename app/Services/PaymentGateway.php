<?php
namespace App\Services;

/**
 * Iranian payment gateway abstraction (extension point).
 *
 * Implemented as a clean seam so a real gateway (Zarinpal, IDPay,
 * Zibal, NextPay ...) can be wired in later without touching order flow.
 * createPayment() returns a redirect URL; verify() confirms the callback.
 */
class PaymentGateway
{
    /**
     * Begin a payment. Returns ['ok'=>bool, 'redirect'=>?string, 'authority'=>?string, 'error'=>?string].
     */
    public static function createPayment(int $tenantId, int $orderId, int $amount, string $callbackUrl, string $description = ''): array
    {
        $integration = Integration::get($tenantId, 'payment');
        if (!$integration['is_active'] || empty($integration['config'])) {
            return ['ok' => false, 'redirect' => null, 'authority' => null, 'error' => 'gateway_not_configured'];
        }

        $provider = $integration['provider'] ?? '';
        $cfg = $integration['config'];

        // --- Example skeleton for Zarinpal (fill in when going live) ---
        if ($provider === 'zarinpal' && !empty($cfg['merchant_id'])) {
            // POST to https://api.zarinpal.com/pg/v4/payment/request.json
            // with merchant_id, amount, callback_url, description; then
            // redirect to https://www.zarinpal.com/pg/StartPay/{authority}
            // Returning a placeholder until credentials/network are available:
            return [
                'ok'        => false,
                'redirect'  => null,
                'authority' => null,
                'error'     => 'not_implemented_yet',
            ];
        }

        return ['ok' => false, 'redirect' => null, 'authority' => null, 'error' => 'unknown_provider'];
    }

    /**
     * Verify a gateway callback. Returns ['ok'=>bool, 'ref_id'=>?string].
     */
    public static function verify(int $tenantId, int $amount, array $callbackParams): array
    {
        // Implement per-provider verification here.
        return ['ok' => false, 'ref_id' => null];
    }
}
