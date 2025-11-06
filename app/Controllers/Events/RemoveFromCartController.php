<?php
namespace ModoGtmWc\Controllers\Events;

use ModoGtmWc\Models\EventDataBuilder;

if (!defined('ABSPATH')) exit;

/**
 * Contrôleur de l'évènement « remove_from_cart » (suppression du panier).
 *
 * Le lien « remove » est enrichi avec un attribut `data-event_data` contenant la charge eCommerce.
 * Le script front écoute le clic et pousse l'évènement côté navigateur.
 */
class RemoveFromCartController {
    /** Enregistre le filtre sur le lien de suppression dans le panier. */
    public function register(): void {
        add_filter('woocommerce_cart_item_remove_link', [$this, 'augmentRemoveLink'], 10, 2);
    }

    /**
     * Injecte data-event_data dans le lien « remove » pour que le JS pousse remove_from_cart.
     *
     * @filter woocommerce_cart_item_remove_link
     */
    public function augmentRemoveLink($link, $cart_item_key) {
        $settings = get_option('modogtmwc_remove_from_cart_settings', []);
        if (empty($settings['event_remove_from_cart'])) {
            return $link;
        }

        $cart = WC()->cart ? WC()->cart->get_cart() : [];
        if (!isset($cart[$cart_item_key])) {
            return $link;
        }

        $event_data = [];
        if (!empty($settings['event_remove_from_cart_include_data'])) {
            $cart_item = $cart[$cart_item_key];
            $product = wc_get_product($cart_item['variation_id'] ?: $cart_item['product_id']);
            if ($product) {
                $builder = new EventDataBuilder();
                $item = $builder->buildItem($product, (int) $cart_item['quantity']);

                // Aligner sur le comportement historique :
                // - item.price = total de ligne
                // - ecommerce.value = total de ligne
                $item['price'] = (float) $cart_item['line_total'];

                $cart_coupon = WC()->cart ? WC()->cart->get_applied_coupons() : [];
                if (!empty($cart_coupon)) {
                    $item['coupon'] = $cart_coupon[0];
                }

                $event_data['ecommerce'] = [
                    'currency' => get_woocommerce_currency(),
                    'value'    => (float) $cart_item['line_total'],
                    'items'    => [$item],
                ];
            }
        }

        // N'encoder que la charge utile ecommerce pour le JS front
        $json_data = esc_attr(wp_json_encode($event_data ? $event_data['ecommerce'] : []));
        $link = str_replace('class="remove"', 'class="remove" data-event_data=\'' . $json_data . '\'', $link);
        return $link;
    }
}
