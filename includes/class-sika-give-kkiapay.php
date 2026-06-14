<?php
namespace SikaGive;

use Kkiapay\Kkiapay;

class Sika_Give_Kkiapay {

  protected $kkiapay;



  public function __construct() {
    // Enregistrement du gateway
    add_filter('give_payment_gateways', array($this, 'register_gateway'));

    // Traitement du paiement
    // add_action('give_gateway_kkiapay', array($this, 'pay'));

    // Génération du formulaire
    // add_action('give_kkiapay_cc_form', array($this, 'generate_form'));

    // Ajout devise XOF
    // add_filter('give_currencies', array($this, 'give_kkiapay_currency'));

    // Charger SDK
    $this->import_kkiapay();

    // Admin settings
    // if (is_admin()) {
    //   add_filter('give_get_sections_gateways', array($this, 'register_sections'));
    //   add_filter('give_get_settings_gateways', array($this, 'register'));
    //   add_action('give_admin_field_my_custom_subtitle', array($this, 'my_custom_subtitle'), 10, 5);
    // }

    // // Scripts front-end
    // add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
  }

  public function import_kkiapay() {
    $sandbox = true; //give_is_test_mode();
    // $this->kkiapay = new Kkiapay(
    //   give_get_option('public_key_kkiapay')??"",
    //   give_get_option('private_key_kkiapay')??"",
    //   give_get_option('secret_key_kkiapay')??"",
    //   $sandbox
    // );
  }

  public function register_gateway($gateways) {
    $gateways['kkiapay'] = array(
      'admin_label'    => 'Kkiapay',
      'checkout_label' => 'Kkiapay'
    );
    return $gateways;
  }

  public function enqueue_scripts() {
    $script_url = plugin_dir_url(__FILE__) . '../include/assets/js/kkiapay.js';

    $kkiapay_vars = [
      'position' => give_get_option('position_kkiapay'),
      'paymentmethod' => give_get_option('payment_method_kkiapay'),
      'theme' => give_get_option('theme_kkiapay'),
      'key' => give_get_option('public_key_kkiapay'),
      'sandbox' => give_is_test_mode()
    ];

    wp_register_script('give-kkiapay-popup-js', $script_url, array('jquery'), '1.0', true);
    wp_enqueue_script('give-kkiapay-popup-js');
    wp_localize_script('give-kkiapay-popup-js', 'give_kkiapay_vars', $kkiapay_vars);
  }

  public function pay($purchase_data) {
    try {
      $txn_id = isset($_POST['give_kkiapay_transaction_id'])
        ? sanitize_text_field($_POST['give_kkiapay_transaction_id'])
        : '';

      if (empty($txn_id)) {
        give_set_error('api_error', 'Transaction ID manquant.');
        give_send_back_to_checkout('?payment-mode=' . $purchase_data['post_data']['give-gateway']);
        return;
      }

      $payment_data = array(
        'price'           => $purchase_data['price'],
        'give_form_title' => $purchase_data['post_data']['give-form-title'],
        'give_form_id'    => intval($purchase_data['post_data']['give-form-id']),
        'give_price_id'   => isset($purchase_data['post_data']['give-price-id'])
          ? $purchase_data['post_data']['give-price-id']
          : '',
        'date'            => $purchase_data['date'],
        'user_email'      => $purchase_data['user_email'],
        'purchase_key'    => $purchase_data['purchase_key'],
        'currency'        => give_get_currency(),
        'user_info'       => $purchase_data['user_info'],
        'status'          => 'pending',
        'gateway'         => 'kkiapay'
      );

      $payment = give_insert_payment($payment_data);

      if (!$payment) {
        give_set_error('api_error', 'Impossible d’enregistrer le paiement.');
        give_send_back_to_checkout('?payment-mode=' . $purchase_data['post_data']['give-gateway']);
        return;
      }

      $response = $this->kkiapay->verifyTransaction($txn_id);

      if (!$response || !isset($response->status)) {
        throw new \Exception('Réponse invalide de Kkiapay.');
      }

      if ($response->status === \Kkiapay\STATUS::SUCCESS) {
        give_update_payment_status($payment, 'complete');
        give_set_payment_transaction_id($payment, $response->transactionId ?? $txn_id);
        give_insert_payment_note($payment, 'Kkiapay : paiement confirmé.');
        give_send_to_success_page();
      } else {
        give_set_error('api_error', 'Paiement non confirmé par Kkiapay.');
        give_send_back_to_checkout('?payment-mode=' . $purchase_data['post_data']['give-gateway']);
      }
    } catch (\Throwable $th) {
      // error_log('Kkiapay error: ' . $th->getMessage());
      give_set_error('api_error', 'Erreur lors du traitement du paiement.');
      give_send_back_to_checkout('?payment-mode=' . $purchase_data['post_data']['give-gateway']);
    }
  }

  public function give_kkiapay_currency($currencies) {
    // $currencies['XOF'] = 'Fcfa';
    
    $currencies['XOF'] = [
      "admin_label" => "Dollars US ($)",
      "symbol" => "$",
      "setting" => [ 
        "currency_position" => "before",
        "thousands_separator"=> ",",
        "decimal_separator"=> ".",
        "number_decimals"=> 2
      ]
    ];
    return $currencies;
  }

  // public static function register_sections($sections) {
  //   $sections['kkiapay-settings'] = 'Kkiapay';
  //   return $sections;
  // }

  public function generate_form() {
    return null;
  }

  public function my_custom_subtitle() {
    echo '<p style="font-style: italic;">Paramètres de connexion Kkiapay</p>';
  }

  public static function get_kkiapay_admin_fields() {
    return array(
          array(
            'id'   => 'give_title_kkiapay',
            'type' => 'title',
          ),
          array(
            'title' => __('Clé publique', 'sika-givewp'),
            'type' => 'text', // 'password',
            'desc_tip' => true,
            'id' => 'public_key_kkiapay',
            'description' => __("Spécifiez votre clé publique (test ou live selon le mode).", 'sika-givewp')
          ),
          array(
            'title' => __('Clé privée', 'sika-givewp'),
            'type' => 'password',
            'desc_tip' => true,
            'id' => 'private_key_kkiapay',
            'description' => __("Spécifiez votre clé privée.", 'sika-givewp'),
          ),
          array(
            'title' => __('Clé secrète', 'sika-givewp'),
            'type' => 'password',
            'desc_tip' => true,
            'id' => 'secret_key_kkiapay',
            'description' => __("Spécifiez votre clé secrète.", 'sika-givewp')
          ),
          array(
            'title' => __('(Optionnel) Moyens de paiement', 'sika-givewp'),
            'description' => __("Choisissez les moyens de paiement acceptés.", 'sika-givewp'),
            'type' => 'select',
            'default' => 'all',
            'desc_tip' => true,
            'id' => 'payment_method_kkiapay',
            'options' => array(
              'all' => __('Tous', 'sika-givewp'),
              'momo' => __('Mobile Money', 'sika-givewp'),
              'card' => __('Cartes bancaires', 'sika-givewp')
            )
          ),
          array(
            'title' => __('(Optionnel) Position du widget', 'sika-givewp'),
            'type' => 'select',
            'description' => __("Position de la fenêtre Kkiapay.", 'sika-givewp'),
            'default' => 'center',
            'desc_tip' => true,
            'id' => 'position_kkiapay',
            'options' => array(
              'right' => __('Droite', 'sika-givewp'),
              'left' => __('Gauche', 'sika-givewp'),
              'center' => __('Centre', 'sika-givewp')
            )
          ),
          array(
            'title' => __('(Optionnel) Thème du widget', 'sika-givewp'),
            'type' => 'text',
            'desc_tip' => true,
            'id' => 'theme_kkiapay',
            'description' => __('Couleur de la fenêtre Kkiapay (laisser vide pour défaut).', 'sika-givewp')
          ),
          array(
            'id'   => 'give_kkiapay',
            'type' => 'sectionend',
          )
        );
  }
}
