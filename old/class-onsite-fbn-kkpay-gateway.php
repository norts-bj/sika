<?php

use Give\Donations\Models\Donation;
use Give\Donations\Models\DonationNote;
use Give\Donations\ValueObjects\DonationStatus;
use Give\Framework\Exceptions\Primitives\Exception;
use Give\Framework\PaymentGateways\Commands\GatewayCommand;
use Give\Framework\PaymentGateways\Commands\PaymentComplete;
use Give\Framework\PaymentGateways\Commands\PaymentRefunded;
use Give\Framework\PaymentGateways\Exceptions\PaymentGatewayException;
use Give\Framework\PaymentGateways\PaymentGateway;
use Give\Framework\PaymentGateways\SubscriptionModule;
use KkpaySettingPage;

use Kkiapay\Kkiapay;


/**
 * @inheritDoc
 */
class  FbnKkpayGatewayOnsiteClass extends PaymentGateway
{
    /**
     * Array of SettingPage classes to be bootstrapped
     *
     * @var string[]
     */
    private $gatewaySettingsPages = [
        // KkpaySettingPage::class,
        // TODO: m'occuper de Fedapay aussi
    ];

    protected $kkiapay;


    public function __construct()
    {
        $this->import_kkiapay();

        if (is_admin()) {
            add_filter('give_get_sections_gateways', array($this, 'register_sections'));
            add_filter('give_get_settings_gateways', array($this, 'register'));
            add_action('give_admin_field_my_custom_subtitle', array($this, 'my_custom_subtitle'), 10, 5);
        }

        // add_action('give-settings_start', [$this, 'registerSettingPage']);
        // return parent::__construct($subscriptionModule);
    }

    public function import_kkiapay()
    {
        $sandbox = give_is_test_mode();
        $this->kkiapay = new Kkiapay(
            give_get_option('public_key_kkiapay'),
            give_get_option('private_key_kkiapay'),
            give_get_option('secret_key_kkiapay'),
            $sandbox
        );
    }

    public function register_sections($sections)
    {
        $sections['kkiapay-settings'] = 'Kkiapay';
        return $sections;
    }
    
    public function register($settings)
    {
        switch (give_get_current_setting_section()) {
            case 'kkiapay-settings':
                $settings = array(
                    array(
                        'id'   => 'give_title_kkiapay',
                        'type' => 'title',
                    ),
                    array(
                        'title' => __('Clé publique', 'kkiapay-give'),
                        'type' => 'text',
                        'desc_tip' => true,
                        'id' => 'public_key_kkiapay',
                        'description' => __("Spécifiez votre clé publique (test ou live selon le mode).", 'kkiapay-give')
                    ),
                    array(
                        'title' => __('Clé privée', 'kkiapay-give'),
                        'type' => 'text',
                        'desc_tip' => true,
                        'id' => 'private_key_kkiapay',
                        'description' => __("Spécifiez votre clé privée.", 'kkiapay-give'),
                    ),
                    array(
                        'title' => __('Clé secrète', 'kkiapay-give'),
                        'type' => 'text',
                        'desc_tip' => true,
                        'id' => 'secret_key_kkiapay',
                        'description' => __("Spécifiez votre clé secrète.", 'kkiapay-give')
                    ),
                    array(
                        'title' => __('(Optionnel) Moyens de paiement', 'kkiapay-give'),
                        'description' => __("Choisissez les moyens de paiement acceptés.", 'kkiapay-give'),
                        'type' => 'select',
                        'default' => 'all',
                        'desc_tip' => true,
                        'id' => 'payment_method_kkiapay',
                        'options' => array(
                            'all' => __('Tous', 'kkiapay-give'),
                            'momo' => __('Mobile Money', 'kkiapay-give'),
                            'card' => __('Cartes bancaires', 'kkiapay-give')
                        )
                    ),
                    array(
                        'title' => __('(Optionnel) Position du widget', 'kkiapay-give'),
                        'type' => 'select',
                        'description' => __("Position de la fenêtre Kkiapay.", 'kkiapay-give'),
                        'default' => 'center',
                        'desc_tip' => true,
                        'id' => 'position_kkiapay',
                        'options' => array(
                            'right' => __('Droite', 'kkiapay-give'),
                            'left' => __('Gauche', 'kkiapay-give'),
                            'center' => __('Centre', 'kkiapay-give')
                        )
                    ),
                    array(
                        'title' => __('(Optionnel) Thème du widget', 'kkiapay-give'),
                        'type' => 'text',
                        'desc_tip' => true,
                        'id' => 'theme_kkiapay',
                        'description' => __('Couleur de la fenêtre Kkiapay (laisser vide pour défaut).', 'kkiapay-give')
                    ),
                    array(
                        'id'   => 'give_kkiapay',
                        'type' => 'sectionend',
                    )
                );
                break;
        }
        return $settings;
    }

    public function my_custom_subtitle()
    {
        echo '<p style="font-style: italic;">Paramètres de connexion Kkiapay</p>';
    }
    /**
     * @inheritDoc
     */
    public static function id(): string
    {
        return 'onsite_fbn_kkpay_gateway';
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
        return __('FBN KKPay Gateway - Onsite', 'fbn-kkpay-gateway');
    }

    /**
     * @inheritDoc
     */
    public function getPaymentMethodLabel(): string
    {
        return __('FBN KKPay Gateway - Onsite', 'fbn-kkpay-gateway');
    }

    /**
     * Display gateway fields for v2 donation forms
     */
    public function getLegacyFormFieldMarkup(int $formId, array $args): string
    {
        // Step 1: add any gateway fields to the form using html.  In order to retrieve this data later the name of the input must be inside the key gatewayData (name='gatewayData[input_name]').
        // Step 2: you can alternatively send this data to the $gatewayData param using the filter `givewp_create_payment_gateway_data_{gatewayId}`.
        return "<div><input type='text' name='gatewayData[fbn-kkpay-gateway-id]' placeholder='FBN KKPay Gateway field' /></div>";
    }

    /**
     * Register a js file to display gateway fields for v3 donation forms
     */
    public function enqueueScript(int $formId)
    {
        wp_enqueue_script('onsite-fbn-kkpay-gateway', plugin_dir_url(__FILE__) . 'js/onsite-fbn-kkpay-gateway.js', ['react', 'wp-element'], '1.0.0', true);
    }

    /**
     * Send form settings to the js gateway counterpart
     */
    public function formSettings(int $formId): array
    {
        return [
            'position' => give_get_option('position_kkiapay'),
            'paymentmethod' => give_get_option('payment_method_kkiapay'),
            'theme' => give_get_option('theme_kkiapay'),
            'clientKey' => give_get_option('public_key_kkiapay'),
            'sandbox' => give_is_test_mode()
        ];
    }

    /**
     * @inheritDoc
     */
    public function createPayment(Donation $donation, $gatewayData): GatewayCommand
    {
        try {
            // $txn_id = $gatewayData['fbn-kkpay-gateway-id']

            // Step 1: Validate any data passed from the gateway fields in $gatewayData.  Throw the PaymentGatewayException if the data is invalid.
            if (empty($gatewayData['fbn-kkpay-gateway-id'])) {
                throw new PaymentGatewayException(__('Example payment ID is required.', 'fbn-kkpay-gateway'));
            }

            // var_dump($donation);
            // exit;

            // $gateway_used = ;

            // switch ($gateway_used) {
            //     case 'value':
            //         # code...
            //         break;

            //     default:
            //         # code...
            //         break;
            // }

            // $payment_data = array(
            //     'price'           => $purchase_data['price'],
            //     'give_form_title' => $purchase_data['post_data']['give-form-title'],
            //     'give_form_id'    => intval($purchase_data['post_data']['give-form-id']),
            //     'give_price_id'   => isset($purchase_data['post_data']['give-price-id'])
            //         ? $purchase_data['post_data']['give-price-id']
            //         : '',
            //     'date'            => $purchase_data['date'],
            //     'user_email'      => $purchase_data['user_email'],
            //     'purchase_key'    => $purchase_data['purchase_key'],
            //     'currency'        => give_get_currency(),
            //     'user_info'       => $purchase_data['user_info'],
            //     'status'          => 'pending',
            //     'gateway'         => 'kkiapay'
            // );

            // $payment = give_insert_payment($payment_data);
            // if (!$payment) {
            //         throw new PaymentGatewayException(__('Impossible d’enregistrer le paiement.', 'fbn-kkpay-gateway'));
            //     }

            $response = $this->kkiapay->verifyTransaction($gatewayData['fbn-kkpay-gateway-id']);

            if (!$response || !isset($response->status)) {
                throw new PaymentGatewayException(__('Réponse invalide de Kkiapay.', 'fbn-kkpay-gateway'));
            }

            if ($response->status !== \Kkiapay\STATUS::SUCCESS) {
                throw new PaymentGatewayException(__('Paiement non confirmé par Kkiapay.', 'fbn-kkpay-gateway'));
            }

            // give_update_payment_status(payment_id: $payment, 'complete');
            // give_set_payment_transaction_id($payment, transaction_id: $response->transactionId ?? $txn_id);
            // give_insert_payment_note($payment, 'Kkiapay : paiement confirmé.');
            // give_send_to_success_page();

            // Step 2: Create a payment with your gateway.
            // $response = $this->exampleRequest(['transaction_id' => $gatewayData['fbn-kkpay-gateway-id']]);

            // Step 3: Return a command to complete the donation.
            // You can alternatively return PaymentProcessing for gateways that require a webhook or similar to confirm that the payment is complete.
            // PaymentProcessing will trigger a Payment Processing email notification, configurable in the settings.
            return new PaymentComplete($response->transactionId);
        } catch (Exception $e) {
            // Step 4: If an error occurs, you can update the donation status to something appropriate like failed, and finally throw the PaymentGatewayException for the framework to catch the message.
            $errorMessage = $e->getMessage();

            $donation->status = DonationStatus::FAILED();
            $donation->save();

            DonationNote::create([
                'donationId' => $donation->id,
                'content' => sprintf(esc_html__('Donation failed. Reason: %s', 'fbn-kkpay-gateway'), $errorMessage)
            ]);

            throw new PaymentGatewayException($errorMessage);
        }
    }

    /**
     * @inerhitDoc
     */
    public function refundDonation(Donation $donation): PaymentRefunded
    {
        // Step 1: refund the donation with your gateway.
        // Step 2: return a command to complete the refund.
        return new PaymentRefunded();
    }


    /**
     * Example request to gateway
     */
    private function exampleRequest(array $data): array
    {
        var_dump("give_is_test_mode()", give_is_test_mode());
        return array_merge([
            'success' => true,
            'transaction_id' => '1234567890',
            'subscription_id' => '0987654321',
        ], $data);
    }


    public function getOptions(): array
    {
        $setting = [
            // Section 2: PayPal Standard.
            [
                'type' => 'title',
                'id' => 'give_title_gateway_settings_3',
            ],
            [
                'name' => esc_html__('PayPal Email', 'give'),
                'desc' => esc_html__(
                    'Enter the email address associated with your PayPal account to connect with the gateway.',
                    'give'
                ),
                'id' => 'paypal_email',
                'type' => 'email',
            ],
            [
                'name' => esc_html__('PayPal Page Style', 'give'),
                'desc' => esc_html__(
                    'Enter the name of the PayPal page style to use, or leave blank to use the default.',
                    'give'
                ),
                'id' => 'paypal_page_style',
                'type' => 'text',
            ],
            [
                'name' => esc_html__('PayPal Transaction Type', 'give'),
                'desc' => esc_html__(
                    'Nonprofits must verify their status to withdraw donations they receive via PayPal. PayPal users that are not verified nonprofits must demonstrate how their donations will be used, once they raise more than $10,000. By default, GiveWP transactions are sent to PayPal as donations. You may change the transaction type using this option if you feel you may not meet PayPal\'s donation requirements.',
                    'give'
                ),
                'id' => 'paypal_button_type',
                'type' => 'radio_inline',
                'options' => [
                    'donation' => esc_html__('Donation', 'give'),
                    'standard' => esc_html__('Standard Transaction', 'give'),
                ],
                'default' => 'donation',
            ],
            [
                'name' => esc_html__('Billing Details', 'give'),
                'desc' => esc_html__(
                    'If enabled, required billing address fields are added to PayPal Standard forms. These fields are not required by PayPal to process the transaction, but you may have a need to collect the data. Billing address details are added to both the donation and donor record in GiveWP.',
                    'give'
                ),
                'id' => 'paypal_standard_billing_details',
                'type' => 'radio_inline',
                'default' => 'disabled',
                'options' => [
                    'enabled' => esc_html__('Enabled', 'give'),
                    'disabled' => esc_html__('Disabled', 'give'),
                ],
            ],
            [
                'id' => 'paypal_invoice_prefix',
                'name' => esc_html__('Invoice ID Prefix', 'give'),
                'desc' => esc_html__(
                    'Enter a prefix for your invoice numbers. If you use your PayPal account for multiple fundraising platforms or ecommerce stores, ensure this prefix is unique. PayPal will not allow orders or donations with the same invoice number.',
                    'give'
                ),
                'type' => 'text',
                'default' => 'GIVE-',
            ],
            [
                'name' => esc_html__('PayPal Standard Gateway Settings Docs Link', 'give'),
                'id' => 'paypal_standard_gateway_settings_docs_link',
                'url' => esc_url('http://docs.givewp.com/settings-gateway-paypal-standard'),
                'title' => esc_html__('PayPal Standard Gateway Settings', 'give'),
                'type' => 'give_docs_link',
            ],
            [
                'type' => 'sectionend',
                'id' => 'give_title_gateway_settings_2',
            ],
        ];

        /**
         * filter the settings.
         *
         * @since 2.9.6
         */
        return apply_filters('give_get_settings_paypal_standard', $setting);
    }


    public function registerSettingPage()
    {
        foreach ($this->gatewaySettingsPages as $page) {
            give()->make($page)->boot();
        }
    }
}
