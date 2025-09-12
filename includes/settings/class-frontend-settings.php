<?php
if (!defined('ABSPATH')) exit;

class MODOGTMWC_Frontend {

    public static function init() {
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
    }

    public static function enqueue_scripts() {
        wp_enqueue_script('modogtmwc-front-script',MODOGTMWC_URL . 'assets/front/js/front-script.js',['jquery'],'1.0',true);
    }
}
