<?php
namespace ModoGtmWc;

use ModoGtmWc\Core\Autoloader;
use ModoGtmWc\Controllers\Events\ViewItemController;
use ModoGtmWc\Controllers\Events\AddToCartController;
use ModoGtmWc\Controllers\Events\RemoveFromCartController;
use ModoGtmWc\Controllers\Events\PurchaseController;
use ModoGtmWc\Controllers\Events\ViewItemListController;
use ModoGtmWc\Controllers\Events\ViewCartController;
use ModoGtmWc\Controllers\Events\AddPaymentInfoController;
use ModoGtmWc\Controllers\Events\AddShippingInfoController;
use ModoGtmWc\Controllers\Events\BeginCheckoutController;
use ModoGtmWc\Controllers\Admin\SettingsController;
use ModoGtmWc\Controllers\Frontend\AssetsController;

if (!defined('ABSPATH')) exit;

/**
 * Bootstrap du plugin.
 *
 * - Enregistre l'autoloader
 * - Diffère l'enregistrement des hooks à `plugins_loaded` pour que WordPress et WooCommerce soient prêts
 * - Branche les contrôleurs Évènements, Admin et Frontend
 */
class Plugin {
    public static function init(): void {
        // S'assurer que l'autoloader est enregistré
        Autoloader::register();

        // Différer l'enregistrement des hooks jusqu'à plugins_loaded pour garantir la dispo de WC
        add_action('plugins_loaded', [__CLASS__, 'register_hooks']);
    }

    public static function register_hooks(): void {
        // N'enregistrer les contrôleurs d'évènements que si WooCommerce est actif
        if (class_exists('WooCommerce')) {
            (new ViewItemController())->register();
            (new AddToCartController())->register();
            (new RemoveFromCartController())->register();
            (new PurchaseController())->register();
            (new ViewItemListController())->register();
            (new ViewCartController())->register();
            (new AddPaymentInfoController())->register();
            (new AddShippingInfoController())->register();
            (new BeginCheckoutController())->register();
        }

        // Réglages / Admin
        if (is_admin()) {
            (new SettingsController())->register();
        }

        // Assets frontend
        (new AssetsController())->register();
    }
}
