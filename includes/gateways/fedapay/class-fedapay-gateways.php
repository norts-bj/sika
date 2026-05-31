<?php

namespace SikaGive;


use Give\Donations\Models\Donation;
use Give\Donations\Models\DonationNote;
use Give\Donations\ValueObjects\DonationStatus;
use Give\Framework\Exceptions\Primitives\Exception;
use Give\Framework\PaymentGateways\Commands\GatewayCommand;
use Give\Framework\PaymentGateways\Commands\PaymentComplete;
use Give\Framework\PaymentGateways\Commands\PaymentRefunded;
use Give\Framework\PaymentGateways\Exceptions\PaymentGatewayException;
use Give\Framework\PaymentGateways\PaymentGateway;


class SikaFedapayGateway extends PaymentGateway
{
    public $supported_currencies = ['USD', 'EUR', 'XOF']; // adapte à ton cas

    /**
     * @inheritDoc
     */
    public static function id(): string
    {
        return 'sika-fedapay-gateway';
    }

    /**
     * @inheritDoc
     */
    public function getId(): string
    {
        return self::id();
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return __('Sika Fedapay Gateway', 'sika-givewp');
    }

    /**
     * @inheritDoc
     */
    public function getPaymentMethodLabel(): string
    {
        return __('Fedapay', 'sika-givewp');
    }

    public function getLegacyFormFieldMarkup(int $formId, array $args): string
    {
        // Step 1: add any gateway fields to the form using html.  In order to retrieve this data later the name of the input must be inside the key gatewayData (name='gatewayData[input_name]').
        // Step 2: you can alternatively send this data to the $gatewayData param using the filter `givewp_create_payment_gateway_data_{gatewayId}`.
        return "<div><input type='text' name='gatewayData[example-gateway-id]' placeholder='Example gateway field' /></div>";
    }

    public function getLegacyPaymentFormFieldHTML(int $formId, array $args): string
    {
        return '<input
            type="hidden"
            name="fedapay_transaction_id"
            id="fedapay-transaction-id"
            value=""
        />';
    }

    public function enqueueScript(int $formId)
    {
        wp_enqueue_script('fedapay-widget', 'https://cdn.fedapay.com/checkout.js', '', '1.1.2', true);

        // Notre JS qui pilote le widget dans le contexte GiveWP
        wp_enqueue_script(
            'sika-fedapay-gateway',
            SIKA_GIVE_PLUGIN_URL . 'js/sika-fedapay-givewp.js',
            ['fedapay-widget', "jquery", 'react', 'wp-element'],
            SIKA_GIVE_VERSION,
            true
        );


        $fedapay_vars = [
            'position' => give_get_option('position_fedapay'),
            'paymentmethod' => give_get_option('payment_method_fedapay'),
            'theme' => give_get_option('theme_fedapay'),
            'publicKey' => give_get_option('public_key_fedapay'),
            'isSandbox' => give_is_test_mode(),
        ];

        // On passe les données PHP → JS via window.fedapaySettings
        wp_localize_script('sika-fedapay-gateway', 'fedapaySettings', $fedapay_vars);
    }

    public function formSettings(int $formId): array
    {
        return [
            'clientKey' => '1234567890'
        ];
    }

    public function createPayment(Donation $donation, $gatewayData): GatewayCommand
    {

        // 1. Récupérer l'ID envoyé par le JS
        $transactionId = $gatewayData['fedapayTransactionId'] ?? null;

        if (!$transactionId) {
            throw new PaymentGatewayException('Transaction ID manquant.');
        }

        try {
            $api = new FedapayApi();
            $transaction = $api->verifyTransaction((string) $transactionId);

            if (!$api->isApproved($transaction)) {
                throw new PaymentGatewayException('Transaction non approuvée : ' . $transaction->status);
            }

            give_update_payment_meta($donation->id, '_fedapay_transaction_id', $transactionId);
            return new PaymentComplete((string) $transactionId);
        } catch (PaymentGatewayException $e) {
            throw $e; // on laisse remonter
        } catch (\Throwable $e) {
            throw new PaymentGatewayException('Erreur vérification Fedapay : ' . $e->getMessage());
        }
    }

    public function refundDonation(Donation $donation)
    {
        DonationNote::create([
            'donationId' => $donation->id,
            'content'    => __('Remboursement Fedapay non supporté dans cette version.', 'sika-givewp'),
        ]);
        return new PaymentRefunded();
    }
}
