<?php

namespace SikaGive;

class SikaKkiapayWebhook
{
    public function registerRoutes(): void
    {
        register_rest_route('sika-give/v1', '/kkiapay/webhook', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handleWebhook'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function handleWebhook(\WP_REST_Request $request): \WP_REST_Response
    {
        $payload = $request->get_json_params();

        // error_log('Kkiapay webhook payload: ' . json_encode($payload));

        // Vérifier que c'est bien un succès
        if (($payload['event'] ?? '') !== 'transaction.success') {
            return new \WP_REST_Response(['status' => 'ignored'], 200);
        }

        if (!($payload['isPaymentSucces'] ?? false)) {
            return new \WP_REST_Response(['status' => 'ignored'], 200);
        }

        $transactionId = $payload['transactionId'] ?? null;

        if (!$transactionId) {
            return new \WP_REST_Response(['error' => 'Transaction ID manquant'], 400);
        }

        $donationId = $this->getDonationIdByTransaction($transactionId);

        if (!$donationId) {
            return new \WP_REST_Response(['error' => 'Don introuvable'], 404);
        }

        give_update_payment_status($donationId, 'publish');
        give_set_payment_transaction_id($donationId, $transactionId);

        return new \WP_REST_Response(['status' => 'ok'], 200);
    }

    private function getDonationIdByTransaction(string $transactionId): ?int
    {
        $donations = give_get_payments([
            'meta_key'   => '_kkiapay_transaction_id',
            'meta_value' => $transactionId,
            'number'     => 1,
        ]);

        return !empty($donations) ? $donations[0]->ID : null;
    }
}