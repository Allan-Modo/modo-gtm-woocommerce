<?php
namespace ModoGtmWc\Controllers\Frontend;

if (!defined('ABSPATH')) exit;

/**
 * Contrôleur des assets Frontend.
 * Enfile le script front qui gère les pushs AJAX pour add_to_cart et remove_from_cart.
 */
class AssetsController {
    /** Enregistre le hook d'enqueue des scripts frontend. */
    public function register(): void {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function enqueue_scripts(): void {
        wp_enqueue_script('modogtmwc-front-script', MODOGTMWC_URL . 'assets/front/js/front-script.js', ['jquery'], '1.0', true);
    }
}
