<?php
namespace ModoGtmWc\Controllers\Events;

use ModoGtmWc\Models\EventDataBuilder;

if (!defined('ABSPATH')) exit;

/**
 * Contrôleur de l'évènement « begin_checkout » (arrivée sur la page de paiement).
 *
 * Pousse l'évènement dans le footer lorsqu'un utilisateur atteint la page de checkout.
 * Optionnellement, inclut la charge eCommerce (panier + items) selon les réglages.
 */
class BeginCheckoutController {
    /** Empêche les doublons si le hook est déclenché plusieurs fois. */
    private static $rendered = false;

    /** Enregistre le hook sur le début du formulaire de checkout. */
    public function register(): void {
        add_action('woocommerce_before_checkout_form', [$this, 'handle'], 5);
    }

    /**
     * Construit et rend l'évènement begin_checkout.
     *
     * @hook woocommerce_before_checkout_form
     */
    public function handle(): void {
        if (self::$rendered) return;
        if (!is_checkout() || is_order_received_page() || is_wc_endpoint_url('order-pay')) return;

        $settings = get_option('modogtmwc_begin_checkout_settings', []);
        if (empty($settings['event_begin_checkout'])) return;

        if (!function_exists('WC') || !WC()->cart) return;
        $cart = WC()->cart;

        $event_data = [];
        if (!empty($settings['event_begin_checkout_include_data'])) {
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

            if (!empty($settings['event_begin_checkout_products_details'])) {
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

        $this->render_view('begin_checkout', $event_data);
        self::$rendered = true;
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
