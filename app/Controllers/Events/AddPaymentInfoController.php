<?php
namespace ModoGtmWc\Controllers\Events;

use ModoGtmWc\Models\EventDataBuilder;

if (!defined('ABSPATH')) exit;

/**
 * Contrôleur de l'évènement « add_payment_info » (sélection d'un moyen de paiement).
 *
 * Déclenche un push dataLayer lorsque la section paiement du checkout est rendue,
 * avec optionnellement la charge eCommerce (panier + items) selon les réglages.
 */
class AddPaymentInfoController {
    /** Empêche les doublons si le hook est déclenché plusieurs fois. */
    private static $rendered = false;

    /** Enregistre le hook sur la section paiement du checkout. */
    public function register(): void {
        add_action('woocommerce_review_order_after_payment', [$this, 'handle'], 5);
    }

    /**
     * Construit et rend l'évènement add_payment_info.
     *
     * @hook woocommerce_review_order_after_payment
     */
    public function handle(): void {
        if (self::$rendered) return;
        if (!is_checkout() || is_order_received_page() || is_wc_endpoint_url('order-pay')) return;

        $settings = get_option('modogtmwc_add_payment_info_settings', []);
        if (empty($settings['event_add_payment_info'])) return;

        if (!function_exists('WC') || !WC()->cart) return;
        $cart = WC()->cart;

        $event_data = [];
        if (!empty($settings['event_add_payment_info_include_data'])) {
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

            if (!empty($settings['event_add_payment_info_products_details'])) {
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

        // Inclure le moyen de paiement choisi si disponible
        $payment_type = $this->resolve_payment_type();
        if ($payment_type !== '') {
            if (!isset($event_data['ecommerce'])) {
                $event_data['ecommerce'] = [];
            }
            $event_data['ecommerce']['payment_type'] = $payment_type;
        }

        $this->render_view('add_payment_info', $event_data);
        self::$rendered = true;
    }

    /** Retourne le libellé du moyen de paiement choisi (ou premier disponible). */
    private function resolve_payment_type(): string {
        if (!function_exists('WC')) return '';

        // Préparer une map id => label pour les moyens de paiement disponibles
        $gateways_map = [];
        $gateways = WC()->payment_gateways() ? WC()->payment_gateways()->get_available_payment_gateways() : [];
        foreach ($gateways as $gateway) {
            if (!empty($gateway->id)) {
                $gateways_map[$gateway->id] = method_exists($gateway, 'get_title') ? $gateway->get_title() : ($gateway->title ?? '');
            }
        }

        // 1) Choix en session (courant en checkout)
        if (WC()->session) {
            $chosen = WC()->session->get('chosen_payment_method');
            if (!empty($chosen)) {
                return $this->map_gateway_to_label($chosen, $gateways_map);
            }
        }

        // 2) Fallback via POST si le template poste déjà le choix
        if (!empty($_POST['payment_method'])) {
            return $this->map_gateway_to_label(wp_unslash($_POST['payment_method']), $gateways_map);
        }

        // 3) Premier moyen disponible
        if (!empty($gateways_map)) {
            $first_id = array_key_first($gateways_map);
            return $this->map_gateway_to_label($first_id, $gateways_map);
        }

        return '';
    }

    /** Convertit un identifiant de gateway en libellé, avec fallback sur l'id nettoyé. */
    private function map_gateway_to_label($gateway_id, array $map): string {
        if (is_string($gateway_id) && isset($map[$gateway_id]) && $map[$gateway_id] !== '') {
            return sanitize_text_field(wp_strip_all_tags($map[$gateway_id]));
        }

        $gateway_id = is_string($gateway_id) ? $gateway_id : ''; // ne pas exposer d'array/objet
        return sanitize_key($gateway_id);
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
