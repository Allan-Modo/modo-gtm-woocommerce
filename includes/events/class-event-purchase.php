<?php
if (!defined('ABSPATH')) exit;

class MODOGTMWC_Event_Purchase {
    
    public static function init() {
        // Hook déclenché après paiement, sur la page "thank you"
        add_action('woocommerce_thankyou', [__CLASS__, 'handle_purchase'], 10, 1);
    }
    
    /**
    * Quand une commande est validée
    */
    public static function handle_purchase($order_id) {
        $settings = get_option('modogtmwc_purchase_settings', []);
        $advanced_settings = get_option('modogtmwc_settings', []);

        // Vérification de l'activation de l'évènement
        if (empty($settings['event_purchase'])) return;
        
        $order = wc_get_order($order_id);
        if (!$order) return;
        
        // Vérification de l'activation de l'envoi des données général de l'évènement
        if (!empty($settings['event_purchase_include_data'])) {
            $event_data = [];
            $event_data['ecommerce'] = [
                'transaction_id' => (string) $order->get_order_number(),
                'currency'       => $order->get_currency(),
                'value'          => (float) $order->get_total(),
            ];
            
            // Vérification de l'activation de l'envoi des données détaillées du produit
            if (!empty($settings['event_purchase_products_details'])) {
                $items = [];
                
                $product_index = 0;
                foreach ($order->get_items() as $item_id => $item) {
                    $product  = $item->get_product();
                    if (!$product) continue;
                    
                    $line_total = (float) $item->get_total();
                    $quantity   = (int) $item->get_quantity();

                    $product_data = [
                        'item_id'   => $product->get_sku() ?: $product->get_id(),
                        'item_name' => $product->get_name(),
                        'price'     => $line_total / $quantity,
                        'quantity'  => $quantity,
                        'index'     => $product_index,
                    ];
                    $product_index++;
                    
                    // Gestion de la marque
                    $product_id_for_terms = $product->is_type('variation') ? $product->get_parent_id() : $product->get_id();
                    $brand = get_the_terms($product_id_for_terms, 'product_brand');
                    if(!empty($brand)){
                        $product_data['item_brand'] = $brand[0]->name;
                    }

                    // Gestion des catégories
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
                    if ($product->is_type('variation')) {
                        $variation_attributes = $product->get_variation_attributes();
                        if (!empty($variation_attributes)) {
                            $product_data['item_variant'] = implode(', ', array_values($variation_attributes));
                        }
                    }

                    // Gestion des promotions

                    $coupon_code = $order->get_coupon_codes();
                    if(!empty($coupon_code)){
                        $product_data['coupon'] = $coupon_code[0];
                        $regular_price = (float) $product->get_price();
                        $paid_price    = (float) ($line_total / $quantity);

                    } else {
                        $regular_price = (float) $product->get_regular_price();
                        $paid_price    = (float) $product->get_price();

                    }

                    $discount = ($regular_price - $paid_price);
                    if ($discount > 0) {
                        $product_data['discount'] = round($discount, 2);
                    }
                    
                    $items[] = $product_data;
                }
                
                if (!empty($items)) {
                    $event_data['ecommerce']['items'] = $items;
                }
            }
        }
        
        // Injection dans le footer
        add_action('wp_footer', function () use ($event_data) {
            ?>
            <script>
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({
                event: "purchase",
                <?php echo substr(wp_json_encode($event_data), 1, -1); ?>
            });
            </script>
            <?php
        });
    }
}
