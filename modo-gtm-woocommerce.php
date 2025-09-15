<?php
/**
 * Plugin Name: Modo GTM WooCommerce
 * Description: Envoi d'événements divers de WooCommerce dans le dataLayer.
 * Version: 1.0.0
 * Author: Modo
 * Text Domain: modo-gtm-woocommerce
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) exit;

define('MODOGTMWC_PATH', plugin_dir_path(__FILE__));
define('MODOGTMWC_URL', plugin_dir_url(__FILE__));

// Bootstrap MVC : charger l'autoloader et le point d'entrée du plugin
require_once MODOGTMWC_PATH . 'app/Core/Autoloader.php';
\ModoGtmWc\Core\Autoloader::register();

// Boot the new MVC plugin scaffold
require_once MODOGTMWC_PATH . 'app/Plugin.php';
\ModoGtmWc\Plugin::init();

// Settings migrated to MVC controllers (see app/Plugin.php)

// Events Hooks migrated to MVC controllers via app/Plugin.php
