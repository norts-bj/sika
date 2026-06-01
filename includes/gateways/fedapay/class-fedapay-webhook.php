<?php

namespace SikaGive;

class SikaFedapayWebhook
{
    public function registerRoutes(): void
    {
        register_rest_route('sika-give/v1', '/fedapay/webhook', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handleWebhook'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function handleWebhook(\WP_REST_Request $request): \WP_REST_Response
    {
        $payload = $request->get_json_params();

        if (($payload['name'] ?? '') !== 'transaction.approved') {
            return new \WP_REST_Response(['status' => 'ignored'], 200);
        }

        $transactionId = $payload['entity']['id'] ?? null; // ← 'entity' pas 'data'

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

    private function getDonationIdByTransaction(int $transactionId): ?int
    {
        // À implémenter — cherche dans les métadonnées du don
        $donations = give_get_payments([
            'meta_key'   => '_fedapay_transaction_id',
            'meta_value' => $transactionId,
            'number'     => 1,
        ]);

        return !empty($donations) ? $donations[0]->ID : null;
    }
}
