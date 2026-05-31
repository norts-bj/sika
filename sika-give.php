<?php

/**
 * Plugin Name: Sika for GiveWP
 * Plugin URI: https://github.com/norts-bj/sika
 * Description: Intégration des passerelles KkiaPay et FedaPay pour GiveWP
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.2
 * Author: Norts
 * Author URI: https://norts.dev
 * Text Domain: sika-givewp
 * Domain Path: /languages
 * Requires Plugins: give
 */

namespace SikaGive;

// use SikaGive\SikaKkiapayWebhook;
// use SikaGive\SikaFedapayWebhook;

defined('ABSPATH') || exit;
define('SIKA_GIVE_VERSION', '1.0.0');
define('SIKA_GIVE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SIKA_GIVE_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once(SIKA_GIVE_PLUGIN_DIR . 'vendor/autoload.php');

final class Plugin
{
    private static ?Plugin $instance = null;

    public static function instance(): Plugin
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->loadDependencies();
        $this->registerGateways();

        // Initialiser les réglages et les scripts
        AdminSettings::init();
        Scripts::init();

        /**
         * 1. S'assurer que le Franc CFA (XOF) est bien enregistré dans GiveWP
         */
        // add_filter('give_currencies', function($currencies) {
        //     if (!isset($currencies['XOF'])) {
        //         $currencies['XOF'] = [
        //             'admin_label' => __('Franc CFA (XOF)', 'sika-givewp'),
        //             'name'        => __('Franc CFA', 'sika-givewp'),
        //             'symbol'      => 'FCFA',
        //             'setting'     => [
        //                 'currency_position'   => 'postfix', // Affiche le symbole après le montant
        //                 'thousands_separator' => ' ',
        //                 'decimal_separator'   => '.',
        //                 'number_decimals'     => 0,         // Pas de centimes pour le XOF
        //             ]
        //         ];
        //     }
        //     return $currencies;
        // });

        /**
         * 2. Déclarer à GiveWP que votre passerelle supporte le XOF (et le XAF)
         */
        // add_filter('give_payment_gateway_supported_currencies', function ($currencies, $gateway_id) {
        //     // L'ID doit correspondre exactement à votre ID PHP 'sika-kkiapay'
        //     if ('sika-kkiapay' === $gateway_id) {
        //         return ['XOF', 'XAF'];
        //     }
        //     return $currencies;
        // }, 10, 2);
    }

    private function loadDependencies(): void
    {
        require_once SIKA_GIVE_PLUGIN_DIR . 'includes/class-admin-settings.php';
        require_once SIKA_GIVE_PLUGIN_DIR . 'includes/class-scripts.php';

        require_once SIKA_GIVE_PLUGIN_DIR . 'includes/class-sika-give-kkiapay.php';
        require_once SIKA_GIVE_PLUGIN_DIR . 'includes/class-sika-give-fedapay.php';

        // kkiapay
        require_once SIKA_GIVE_PLUGIN_DIR . 'includes/gateways/kkiapay/class-kkiapay-api.php';
        require_once SIKA_GIVE_PLUGIN_DIR . 'includes/gateways/kkiapay/class-kkiapay-webhook.php';
        require_once SIKA_GIVE_PLUGIN_DIR . 'includes/gateways/kkiapay/class-kkiapay-gateways.php';

        //fedapay
        require_once SIKA_GIVE_PLUGIN_DIR . 'includes/gateways/fedapay/class-fedapay-api.php';
        require_once SIKA_GIVE_PLUGIN_DIR . 'includes/gateways/fedapay/class-fedapay-webhook.php';
        require_once SIKA_GIVE_PLUGIN_DIR . 'includes/gateways/fedapay/class-fedapay-gateways.php';
    }

    private function registerGateways(): void
    {
        // Enregistrement dynamique des Gateways v3
        add_action('givewp_register_payment_gateway', function ($registrar) {

            // Ajoutez simplement vos futures classes ici
            $gateways = [
                \SikaGive\SikaKkiapayGateway::class,
                \SikaGive\SikaFedapayGateway::class,
            ];

            foreach ($gateways as $gateway) {
                $registrar->registerGateway($gateway);
            }
        });


        // Endpoints REST (webhook KKiaPay)
        // add_action('rest_api_init', [new SikaKkiapayWebhook(), 'registerRoutes']);
        
        // Endpoints REST (webhook KKiaPay)
        add_action('rest_api_init', function () {
            (new \SikaGive\SikaFedapayWebhook())->registerRoutes();
        });

        // Page intermédiaire de checkout (rewrite rule)
        add_action('init', [$this, 'addRewriteEndpoint']);
        add_filter('template_include', [$this, 'loadCheckoutTemplate']);
    }

    /**
     * Crée une URL virtuelle /kkiapay-checkout/ sans créer de page WP.
     */
    public function addRewriteEndpoint(): void
    {
        add_rewrite_rule('^kkiapay-checkout/?$', 'index.php?kkiapay_checkout=1', 'top');
        add_rewrite_tag('%kkiapay_checkout%', '1');
    }

    public function loadCheckoutTemplate(string $template): string
    {
        if (get_query_var('kkiapay_checkout')) {
            return SIKA_GIVE_PLUGIN_DIR . 'templates/checkout-redirect.php';
        }
        return $template;
    }
}



// On démarre uniquement si GiveWP est actif
add_action('plugins_loaded', function () {
    if (!class_exists('Give')) {
        add_action('admin_notices', function () {
            echo '<div class="error"><p>'
                . esc_html__('KKiaPay for GiveWP nécessite que GiveWP soit activé.', 'kkiapay-givewp')
                . '</p></div>';
        });
        return;
    }
    Plugin::instance();
});

// Flush des rewrite rules à l'activation/désactivation
register_activation_hook(__FILE__, 'flush_rewrite_rules');
register_deactivation_hook(__FILE__, 'flush_rewrite_rules');
