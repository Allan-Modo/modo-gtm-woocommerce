<?php
namespace ModoGtmWc\Controllers\Events;

use ModoGtmWc\Models\EventDataBuilder;

if (!defined('ABSPATH')) exit;

/**
 * Contrôleur de l'évènement « add_shipping_info » (sélection du mode de livraison).
 *
 * Déclenche un push dataLayer lorsque la section livraison du checkout est rendue,
 * avec optionnellement la charge eCommerce (panier + items) selon les réglages.
 */
class AddShippingInfoController {
    /** Empêche les doublons si le hook est déclenché plusieurs fois. */
    private static $rendered = false;

    /** Enregistre des hooks liés à la zone livraison du checkout. */
    public function register(): void {
        $hooks = [
            'woocommerce_review_order_after_shipping', // après le bloc des méthodes de livraison
            'woocommerce_review_order_before_payment', // juste avant paiement (si template modifié)
            'woocommerce_after_checkout_form',         // fallback fin de formulaire
        ];

        foreach ($hooks as $hook) {
            add_action($hook, [$this, 'handle'], 5);
        }
    }

    /**
     * Construit et rend l'évènement add_shipping_info.
     *
     * @hook woocommerce_* (filtré en interne pour la page de checkout uniquement)
     */
    public function handle(): void {
        if (self::$rendered) return;
        if (!is_checkout() || is_order_received_page() || is_wc_endpoint_url('order-pay')) return;

        $settings = get_option('modogtmwc_add_shipping_info_settings', []);
        if (empty($settings['event_add_shipping_info'])) return;

        if (!function_exists('WC') || !WC()->cart) return;
        $cart = WC()->cart;

        $event_data = [];
        if (!empty($settings['event_add_shipping_info_include_data'])) {
            $totals = $cart->get_totals();
            $value = isset($totals['total']) ? (float) $totals['total'] : (float) $cart->get_cart_contents_total();
            $event_data['ecommerce'] = [
                'currency' => get_woocommerce_currency(),
                'value'    => $value,
            ];

            $applied_coupons = $cart->get_applied_coupons();
            if (!empty($applied_coupons)) {
                $event_data['ecommerce']['coupon'] = $applied_coupons[0];
            }

            if (!empty($settings['event_add_shipping_info_products_details'])) {
                $builder = new EventDataBuilder();
                $items = [];
                $index = 0;

                foreach ($cart->get_cart() as $cart_item) {
                    $product = wc_get_product($cart_item['variation_id'] ?: $cart_item['product_id']);
                    if (!$product) continue;

                    $item = $builder->buildItem($product, (int) $cart_item['quantity']);
                    $line_total = isset($cart_item['line_total']) ? (float) $cart_item['line_total'] : 0;
                    $qty = max(1, (int) $cart_item['quantity']);
                    $item['price'] = $qty > 0 ? $line_total / $qty : (float) $product->get_price();
                    $item['index'] = $index++;

                    if (!empty($applied_coupons)) {
                        $item['coupon'] = $applied_coupons[0];
                    }

                    $items[] = $item;
                }

                if (!empty($items)) {
                    $event_data['ecommerce']['items'] = $items;
                }
            }
        }

        // Inclure le mode de livraison choisi si disponible
        $shipping_tier = $this->resolve_shipping_tier();
        if ($shipping_tier !== '') {
            if (!isset($event_data['ecommerce'])) {
                $event_data['ecommerce'] = [];
            }
            $event_data['ecommerce']['shipping_tier'] = $shipping_tier;
        }

        $this->render_view('add_shipping_info', $event_data);
        self::$rendered = true;
    }

    /** Retourne le libellé du mode de livraison choisi ou du premier disponible. */
    private function resolve_shipping_tier(): string {
        if (!function_exists('WC')) return '';

        // Préparer une map id => label pour les tarifs disponibles
        $rates_map = [];
        $packages = WC()->shipping() ? WC()->shipping->get_packages() : [];
        foreach ($packages as $package) {
            if (empty($package['rates'])) continue;
            foreach ($package['rates'] as $rate) {
                if (!empty($rate->id)) {
                    $rates_map[$rate->id] = $rate->get_label();
                }
            }
        }

        // 1) Choix en session (tableau des méthodes par package)
        if (WC()->session) {
            $chosen = WC()->session->get('chosen_shipping_methods');
            if (is_array($chosen) && !empty($chosen)) {
                $first = reset($chosen);
                if (!empty($first)) {
                    return $this->map_rate_to_label($first, $rates_map);
                }
            }
        }

        // 2) Fallback via POST si le choix est déjà posté
        if (!empty($_POST['shipping_method'])) {
            $post_methods = wc_clean(wp_unslash($_POST['shipping_method']));
            if (is_array($post_methods) && !empty($post_methods)) {
                $first = reset($post_methods);
                if (!empty($first)) {
                    return $this->map_rate_to_label($first, $rates_map);
                }
            }
        }

        // 3) Premier mode disponible sur le premier package
        if (!empty($rates_map)) {
            $first_id = array_key_first($rates_map);
            return $this->map_rate_to_label($first_id, $rates_map);
        }

        return '';
    }

    /** Convertit un identifiant de tarif en libellé, avec fallback sur l'id nettoyé. */
    private function map_rate_to_label(string $rate_id, array $rates_map): string {
        if (isset($rates_map[$rate_id]) && $rates_map[$rate_id] !== '') {
            return sanitize_text_field($rates_map[$rate_id]);
        }
        return sanitize_key($rate_id);
    }

    /** Diffère l'injection du script au footer via le template partagé. */
    private function render_view(string $event, array $data): void {
        $template = dirname(__DIR__, 2) . '/Views/scripts/push-event.php';
        if (file_exists($template)) {
            add_action('wp_footer', function () use ($template, $event, $data) {
                include $template;
            });
        }
    }
}
