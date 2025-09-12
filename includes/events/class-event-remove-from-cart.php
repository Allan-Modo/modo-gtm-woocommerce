<?php
if (!defined('ABSPATH')) exit;

class MODOGTMWC_Event_Remove_From_Cart {
    
    public static function init() {
        // On modifie le bouton "supprimer du panier"
        add_filter('woocommerce_cart_item_remove_link', [__CLASS__, 'render_remove_link'], 10, 2);
    }
    
    /**
    * Injecte les dataLayer data-* directement dans le lien "remove"
    */
    public static function render_remove_link($link, $cart_item_key) {
        $settings = get_option('modogtmwc_remove_from_cart_settings', []);
        $advanced_settings = get_option('modogtmwc_settings', []);

        // Vérification de l'activation de l'évènement
        if (empty($settings['event_remove_from_cart'])) {
            return $link;
        }

        
        $cart = WC()->cart->get_cart();
        if (!isset($cart[$cart_item_key])) {
            return $link;
        }

        // Vérification de l'activation de l'envoi des données général de l'évènement
        if (!empty($settings['event_remove_from_cart_include_data'])) { 
                $cart_item = $cart[$cart_item_key];
                $event_data = [];
                $event_data['ecommerce'] = [
                    'currency'       => get_woocommerce_currency(),
                    'value'          => $cart_item['line_total'],
                ];

            // Vérification de l'activation de l'envoi des données détaillées du produit
            if (!empty($settings['event_remove_from_cart_product_details'])) {
                $product = wc_get_product($cart_item['variation_id'] ?: $cart_item['product_id']);
                
                $item = [
                    'item_id'   => $product->get_sku() ?: $product->get_id(),
                    'item_name' => $product->get_name(),
                    'price'     => $cart_item['line_total'],
                    'quantity'  => $cart_item['quantity'],
                ];

                // Gestion de la marque
                $brand = get_the_terms($product->get_id(), 'product_brand');
                if (!empty($brand) && !is_wp_error($brand)) {
                    $item['item_brand'] = $brand[0]->name;
                }

                // Gestion des promotions
                $discount = 0;
                if ($product->is_on_sale()) {
                    if ($product->is_type('variable')) {
                        $prices = $product->get_variation_prices();
                        $reg_prices = $prices['regular_price'];
                        $sale_prices = $prices['sale_price'];
                        foreach ($reg_prices as $key => $reg_price) {
                            if ($key == $cart_item['variation_id']) {
                                $discount = ((float) $reg_price - (float) $sale_prices[$key]);
                            }
                        }
                    } else {
                        $discount = ((float) $product->get_regular_price() - (float) ($cart_item['line_total'] / $cart_item['quantity']));
                    }
                    if($discount > 0){
                        $item['discount'] = round($discount, 2);
                    }
                }

                // Gestion du code promo
                $cart_coupon = WC()->cart->get_applied_coupons();
                if(!empty($cart_coupon)){
                    $item['coupon'] = $cart_coupon[0];
                }

                // Gestion des catégories
                $product_id_for_terms = $product->is_type('variation') ? $product->get_parent_id() : $product->get_id();
                $terms = get_the_terms($product_id_for_terms, 'product_cat');
                if ($terms && !is_wp_error($terms)) {
                    $cat_levels = [];

                    // Vérification de l'actication de la gestion intelligente des catégories
                    if (!empty($advanced_settings['events_smart_categories'])) {

                        $deepest_cat = null;    // Catégorie la plus profonde dans l'arborescence
                        $max_depth = 0;         // Profondeur maximale trouvée

                        // Parcourt toutes les catégories assignées au produit
                        foreach ($terms as $term) {
                            $depth = 0;
                            $current = $term;

                            // Monte dans la hiérarchie pour calculer la profondeur de la catégorie
                            while ($current->parent) {
                                $current = get_term($current->parent, 'product_cat');
                                $depth++;
                            }

                            // On garde la catégorie la plus profonde
                            if ($depth > $max_depth) {
                                $max_depth = $depth;
                                $deepest_cat = $term;
                            }
                        }

                        // Construire le tableau des catégories depuis la plus haute jusqu'à la plus profonde
                        $current = $deepest_cat;
                        while ($current) {
                            array_unshift($cat_levels, $current->name);
                            $current = $current->parent ? get_term($current->parent, 'product_cat') : null;
                        }
                    } else {
                        // Sinon, on ne prend que les catégories de niveau 1
                        foreach ($terms as $term) {
                            if ($term->parent == 0) {
                                $cat_levels[] = $term->name;
                            }
                        }
                    }

                    // On mappe les catégories dans le format attendu par le datalayer
                    foreach ($cat_levels as $index => $cat_name) {
                        $key = $index === 0 ? 'item_category' : 'item_category' . ($index + 1);
                        $item[$key] = $cat_name;
                    }
                }

                // Gestion des variations
                if ($product->is_type('variable')) {
                    $variation_attributes = $product->get_variation_attributes();
                    if (!empty($variation_attributes)) {
                        $item['item_variant'] = implode(', ', array_values($variation_attributes));
                    }
                }

                $event_data['ecommerce']['items'] = [$item];
            }
        }
        
        // Encodage JSON pour le passer en data-attribute
        $json_data = esc_attr(wp_json_encode($event_data));
        
        // Ajout du data-item au lien pour un traitement en JS
        $link = str_replace(
            'class="remove"',
            'class="remove" data-event_data=\'' . $json_data . '\'',
            $link
        );
        
        return $link;
    }
}
