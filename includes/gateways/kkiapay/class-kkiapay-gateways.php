<?php

namespace SikaGive;

use Kkiapay\Kkiapay;

use Give\Donations\Models\Donation;
use Give\Donations\Models\DonationNote;
use Give\Donations\ValueObjects\DonationStatus;
use Give\Framework\Exceptions\Primitives\Exception;
use Give\Framework\PaymentGateways\Commands\GatewayCommand;
use Give\Framework\PaymentGateways\Commands\PaymentComplete;
use Give\Framework\PaymentGateways\Commands\PaymentRefunded;
use Give\Framework\PaymentGateways\Exceptions\PaymentGatewayException;
use Give\Framework\PaymentGateways\PaymentGateway;

defined('ABSPATH') || exit;


class SikaKkiapayGateway extends PaymentGateway
{
    protected $kkiapay;

    /**
     * @inheritDoc
     */
    public static function id(): string
    {
        return 'sika-kkiapay-gateway';
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
        return __('Sika Kkiapay Gateway', 'sika-givewp');
    }

    /**
     * @inheritDoc
     */
    public function getPaymentMethodLabel(): string
    {
        return __('KKiaPay', 'sika-givewp');
    }

    public function getLegacyPaymentFormFieldHTML(int $formId, array $args): string
    {
        return '<input
            type="hidden"
            name="kkiapay_transaction_id"
            id="kkiapay-transaction-id"
            value=""
        />';
    }

    public function enqueueScript(int $formId)
    {
        // Le widget KKiaPay : bibliothèque externe fournie par KKiaPay
        wp_enqueue_script(
            'sika-kkiapay-widget',
            'https://cdn.kkiapay.me/k.js',
            [],
            null,
            true
        );

        // Notre JS qui pilote le widget dans le contexte GiveWP
        wp_enqueue_script(
            'sika-kkiapay-gateway',
            SIKA_GIVE_PLUGIN_URL . 'js/sika-kkiapay-givewp.js',
            ['sika-kkiapay-widget', "jquery", 'react', 'wp-element'],
            SIKA_GIVE_VERSION,
            true
        );

        $kkiapay_vars = [
            'position' => give_get_option('position_kkiapay'),
            'paymentmethod' => give_get_option('payment_method_kkiapay'),
            'theme' => give_get_option('theme_kkiapay'),
            'publicKey' => give_get_option('kkiapay_give_public_key'),
            'isSandbox' => give_is_test_mode(),
        ];

        // On passe les données PHP → JS via window.kkiapaySettings
        wp_localize_script('sika-kkiapay-gateway', 'kkiapaySettings', $kkiapay_vars);
    }

    public function createPayment(Donation $donation, $gatewayData): GatewayCommand
    {
        $transactionId = sanitize_text_field($gatewayData['kkiapayTransactionId'] ?? '');

        if (empty($transactionId)) {
            throw new Exception(
                __('Aucune transaction KKiaPay reçue. Le paiement a peut-être été annulé.', 'kkiapay-givewp')
            );
        }

        // KkiapayApi sera implémentée dans le prochain fichier
        $api = new SikaKkiapayApi();
        $verified = $api->verifyTransaction($transactionId, $donation->amount->formatToDecimal());

        if (!$verified) {
            $donation->status = DonationStatus::FAILED;
            $donation->save();

            throw new Exception(
                __('Le paiement KKiaPay n\'a pas pu être vérifié.', 'kkiapay-givewp')
            );
        }

        // On attache le transactionId KKiaPay au don pour traçabilité
        $donation->gatewayTransactionId = $transactionId;
        $donation->save();

        // PaymentComplete dit à GiveWP : "c'est bon, affiche l'étape succès"
        return new PaymentComplete($transactionId);
    }
    
        public function refundDonation(Donation $donation): void
    {
        DonationNote::create([
            'donationId' => $donation->id,
            'content'    => __('Remboursement KKiaPay non supporté dans cette version.', 'sika-givewp'),
        ]);
    }
















    // public function __construct()
    // {

    //     if (is_admin()) {
    //         add_filter('give_get_sections_gateways', array($this, 'register_sections'));
    //         add_filter('give_get_settings_gateways', array($this, 'register'));
    //         add_action('give_admin_field_my_custom_subtitle', array($this, 'my_custom_subtitle'), 10, 5);
    //     }

    //     // Scripts front-end
    //     add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

    //     // add_action('give-settings_start', [$this, 'registerSettingPage']);
    //     // return parent::__construct($subscriptionModule);
    // }





    //  public function register_sections($sections)
    // {
    //     $sections['kkiapay-settings'] = 'Kkiapay';
    //     return $sections;
    // }
    
    // public function register($settings)
    // {
    //     switch (give_get_current_setting_section()) {
    //         case 'kkiapay-settings':
    //             $settings = array(
    //                 array(
    //                     'id'   => 'give_title_kkiapay',
    //                     'type' => 'title',
    //                 ),
    //                 array(
    //                     'title' => __('Clé publique', 'kkiapay-give'),
    //                     'type' => 'text',
    //                     'desc_tip' => true,
    //                     'id' => 'public_key_kkiapay',
    //                     'description' => __("Spécifiez votre clé publique (test ou live selon le mode).", 'kkiapay-give')
    //                 ),
    //                 array(
    //                     'title' => __('Clé privée', 'kkiapay-give'),
    //                     'type' => 'text',
    //                     'desc_tip' => true,
    //                     'id' => 'private_key_kkiapay',
    //                     'description' => __("Spécifiez votre clé privée.", 'kkiapay-give'),
    //                 ),
    //                 array(
    //                     'title' => __('Clé secrète', 'kkiapay-give'),
    //                     'type' => 'text',
    //                     'desc_tip' => true,
    //                     'id' => 'secret_key_kkiapay',
    //                     'description' => __("Spécifiez votre clé secrète.", 'kkiapay-give')
    //                 ),
    //                 array(
    //                     'title' => __('(Optionnel) Moyens de paiement', 'kkiapay-give'),
    //                     'description' => __("Choisissez les moyens de paiement acceptés.", 'kkiapay-give'),
    //                     'type' => 'select',
    //                     'default' => 'all',
    //                     'desc_tip' => true,
    //                     'id' => 'payment_method_kkiapay',
    //                     'options' => array(
    //                         'all' => __('Tous', 'kkiapay-give'),
    //                         'momo' => __('Mobile Money', 'kkiapay-give'),
    //                         'card' => __('Cartes bancaires', 'kkiapay-give')
    //                     )
    //                 ),
    //                 array(
    //                     'title' => __('(Optionnel) Position du widget', 'kkiapay-give'),
    //                     'type' => 'select',
    //                     'description' => __("Position de la fenêtre Kkiapay.", 'kkiapay-give'),
    //                     'default' => 'center',
    //                     'desc_tip' => true,
    //                     'id' => 'position_kkiapay',
    //                     'options' => array(
    //                         'right' => __('Droite', 'kkiapay-give'),
    //                         'left' => __('Gauche', 'kkiapay-give'),
    //                         'center' => __('Centre', 'kkiapay-give')
    //                     )
    //                 ),
    //                 array(
    //                     'title' => __('(Optionnel) Thème du widget', 'kkiapay-give'),
    //                     'type' => 'text',
    //                     'desc_tip' => true,
    //                     'id' => 'theme_kkiapay',
    //                     'description' => __('Couleur de la fenêtre Kkiapay (laisser vide pour défaut).', 'kkiapay-give')
    //                 ),
    //                 array(
    //                     'id'   => 'give_kkiapay',
    //                     'type' => 'sectionend',
    //                 )
    //             );
    //             break;
    //     }
    //     return $settings;
    // }

    // public function my_custom_subtitle()
    // {
    //     echo '<p style="font-style: italic;">Paramètres de connexion Kkiapay</p>';
    // }

    /**
     * Champ caché rendu dans le formulaire GiveWP.
     * Le JS va y écrire le transactionId après que le widget confirme le paiement.
     * Ce champ est transmis dans $_POST et atterrit dans $gatewayData côté PHP.
     */


    /**
     * Appelé par GiveWP après soumission du formulaire.
     *
     * À ce stade le JS a déjà :
     *   1. Ouvert le widget KKiaPay
     *   2. Reçu la confirmation du paiement
     *   3. Écrit le transactionId dans le champ caché
     *   4. Laissé le formulaire se soumettre
     *
     * On n'a plus qu'à vérifier le transactionId avec l'API KKiaPay.
     */
}
