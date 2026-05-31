<?php

namespace SikaGive;

use FedaPay\FedaPay;
use FedaPay\Transaction;

class FedapayApi
{
    public function __construct()
    {
        FedaPay::setApiKey(give_get_option('private_key_fedapay'));
        FedaPay::setEnvironment(give_is_test_mode() ? 'sandbox' : 'live');
    }

    /**
     * Vérifie une transaction et retourne son statut
     * @throws \Exception si la transaction est introuvable
     */
    public function verifyTransaction(string $transactionId): object
    {
        return Transaction::retrieve($transactionId);
    }

    public function isApproved(object $transaction): bool
    {
        return $transaction->status === 'approved';
    }
}