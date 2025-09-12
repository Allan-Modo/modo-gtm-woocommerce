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

// Loads Settings Files
require_once MODOGTMWC_PATH . 'includes/settings/class-admin-settings.php';
require_once MODOGTMWC_PATH . 'includes/settings/class-frontend-settings.php';

// Loads Events Files
require_once MODOGTMWC_PATH . 'includes/events/class-event-add-to-cart.php';
require_once MODOGTMWC_PATH . 'includes/events/class-event-purchase.php';
require_once MODOGTMWC_PATH . 'includes/events/class-event-remove-from-cart.php';
require_once MODOGTMWC_PATH . 'includes/events/class-event-view-item.php';
require_once MODOGTMWC_PATH . 'includes/events/class-event-view-item-list.php';

// Settings Hooks
add_action('plugins_loaded', ['MODOGTMWC_Admin_Settings', 'init']);
add_action('wp', ['MODOGTMWC_Frontend', 'init']);

// Events Hooks
add_action('woocommerce_init', ['MODOGTMWC_Event_Add_To_Cart', 'init']);
add_action('woocommerce_init', ['MODOGTMWC_Event_Purchase', 'init']);
add_action('woocommerce_init', ['MODOGTMWC_Event_Remove_From_Cart', 'init']);
add_action('woocommerce_init', ['MODOGTMWC_Event_View_Item', 'init']);
add_action('woocommerce_init', ['MODOGTMWC_Event_View_Item_List', 'init']);