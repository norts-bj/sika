<?php

namespace SikaGive;

use Kkiapay\Kkiapay;

defined('ABSPATH') || exit;

class SikaKkiapayApi
{
    private $kkiapay;
    private string $privateKey;
    private string $baseUrl;

    public function __construct() {
        $this->import_kkiapay();
    }

    public function import_kkiapay()
    {
        // Récupération des clés depuis les réglages GiveWP
        $public_key  = give_get_option('public_key_kkiapay');
        $private_key = give_get_option('private_key_kkiapay');
        $secret_key  = give_get_option('secret_key_kkiapay');
        $is_sandbox  = give_is_test_mode();

        // Initialisation du SDK Kkiapay
        $this->kkiapay = new Kkiapay(
            $public_key,
            $private_key,
            $secret_key,
            $is_sandbox
        );
    }


    /**
     * Vérifie qu'une transaction KKiaPay est bien réussie
     * ET que son montant correspond à ce qu'on attend.
     *
     * @param string $transactionId  L'ID reçu depuis le widget JS
     * @param float  $expectedAmount Le montant du don côté GiveWP (en unité principale, ex: 5000.00)
     *
     * @throws \Exception Si la clé privée est absente ou si l'appel API échoue
     */
    public function verifyTransaction($transaction_id, $expected_amount): bool {
        try {
            // Appel à l'API Kkiapay via le SDK
            $response = $this->kkiapay->verifyTransaction($transaction_id);

            // Vérification du statut de succès
            if ($response && isset($response->status) && $response->status === 'SUCCESS') {
                
                //Vérifier que le montant payé correspond au don
                $paid_amount = (float) $response->amount;
                $expected = (float) $expected_amount;

                if ($this->verifyAmount($paid_amount, $expected_amount)) {
                    return true;
                } else {
                    error_log("Sika GiveWP - Kkiapay: Montant payé insuffisant. Attendu: {$expected}, Reçu: {$paid_amount}");
                    return false;
                }
            }
        } catch (\Exception $e) {
            // Loguer l'erreur silencieusement pour le débogage
            error_log('Sika GiveWP - Erreur Kkiapay API : ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Compare le montant retourné par KKiaPay avec le montant attendu.
     *
     * On tolère une différence de 1 unité pour les éventuels arrondis
     * selon l'opérateur mobile money.
     */
    private function verifyAmount(float $receivedAmount, float $expectedAmount): bool
    {
        return abs($receivedAmount - $expectedAmount) <= 1;
    }

    /**
     * Écrit dans le log d'erreurs WordPress.
     * error_log() est le seul outil de logging disponible sans dépendance externe.
     */
    private function logError(string $message, array $context = []): void
    {
        error_log(sprintf(
            '[Sika KKiaPay GiveWP] %s | %s',
            $message,
            wp_json_encode($context)
        ));
    }
}
