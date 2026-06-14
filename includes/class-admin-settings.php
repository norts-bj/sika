<?php
namespace SikaGive;

use SikaGive\Sika_Give_Kkiapay;

// defined('ABSPATH') || exit;

class AdminSettings {
    
    public static function init() {
        add_filter('give_get_sections_gateways', [self::class, 'register_sections']);
        add_filter('give_get_settings_gateways', [self::class, 'register_settings']);
    }

    public static function register_sections($sections) {
        $sections['kkiapay-settings'] = 'Kkiapay';
        $sections['fedapay-settings'] = 'Fedapay';
        return $sections;
    }

    public static function register_settings($settings) {
        $current_section = give_get_current_setting_section();

        switch ($current_section) {
            case 'kkiapay-settings':
                return Sika_Give_Kkiapay::get_kkiapay_admin_fields();
            case 'fedapay-settings':
                return Sika_Give_Fedapay::get_fedapay_admin_fields();
            default:
                return $settings;
        }
    }
}