<?php
namespace SikaGive;

// defined('ABSPATH') || exit;

class Scripts {
    
    public static function init() {
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_frontend_scripts']);
    }

    public static function enqueue_frontend_scripts() {
        // Scripts Kkiapay
        wp_enqueue_script('kkiapay-widget', 'https://cdn.kkiapay.me/k.js', [], null, true);
        
        // Scripts Fedapay (plus tard)
        // wp_enqueue_script('fedapay-widget', 'https://checkout.fedapay.com/js/checkout.js', [], null, true);
        
        // Votre script unifié (qui gérera les deux logiques)
        // wp_enqueue_script('sika-givewp-js', ...);
    }
}