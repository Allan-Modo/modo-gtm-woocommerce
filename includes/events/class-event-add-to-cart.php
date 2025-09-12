<?php
if (!defined('ABSPATH')) exit;

class MODOGTMWC_Event_Add_To_Cart {

    public static function init() {
        add_action('woocommerce_add_to_cart', [__CLASS__, 'handle_event'], 10, 6);
        add_filter('woocommerce_loop_add_to_cart_link', [__CLASS__, 'add_data_to_loop_button'], 10, 3);
    }

    /**
     * Construit les données ecommerce pour un produit
     */
    private static function build_event_data($product, $quantity = 1, $variation_id = 0, $variation = []) {
        $settings = get_option('modogtmwc_add_to_cart_settings', []);
        $advanced_settings = get_option('modogtmwc_settings', []);

        // Vérification de l'activation de l'évènement
        if (empty($settings['event_add_to_cart'])) return [];

        $event_data = [];

        // Vérification de l'activation de l'envoi des données général de l'évènement
        if (!empty($settings['event_add_to_cart_include_data'])) { 
                        
            $event_data['ecommerce']['currency'] = get_woocommerce_currency(); 
            $event_data['ecommerce']['value'] = (float) $product->get_price() * (int) $quantity;

            // Vérification de l'activation de l'envoi des données détaillées du produit
            if (!empty($settings['event_add_to_cart_product_details'])) {
                $item = [
                    'item_id'   => $product->get_sku() ?: $product->get_id(),
                    'item_name' => $product->get_name(),
                    'price'     => (float) $product->get_price(),
                    'quantity'  => (int) $quantity,
                ];

                // Gestion de la marque
                $brand = get_the_terms($product->get_id(), 'product_brand');
                if(!empty($brand)){
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
                            if ($key == $variation_id) {
                                $discount = ((float) $reg_price - (float) $sale_prices[$key]);
                            }
                        }
                    } else {
                        $discount = ((double) $product->get_regular_price() - (double) $product->get_sale_price());
                    }
                    if($discount > 0){
                        $item['discount'] = $discount;
                    }
                }

                // Gestion des catégories
                $terms = get_the_terms($product->get_id(), 'product_cat');
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
                if (!empty($variation)) {
                    $variation_attributes = [];
                    foreach ($variation as $variation_attribute) {
                        $variation_attributes[] = ucfirst($variation_attribute);
                    }
                    $item['item_variant'] = implode(',', $variation_attributes);
                }

                $event_data['ecommerce']['items'] = [$item];
            }

        }

        return $event_data;
    }

    /**
     * Envoi des données au datalayer 
     */
    public static function handle_event($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
        $product = wc_get_product($product_id);
        if (!$product) return;

        $event_data = self::build_event_data($product, $quantity, $variation_id, $variation);

        // Injection du script dans le footer
        add_action('wp_footer', function () use ($event_data) {
            ?>
            <script>
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({
                event: "add_to_cart",
                <?php echo substr(wp_json_encode($event_data), 1, -1); ?>
            });
            </script>
            <?php
        });
    }

    /**
     * Ajoute les data-* dans les boutons loop pour l'ajout en AJAX
     */
    public static function add_data_to_loop_button($html, $product, $args) {
        $event_data = self::build_event_data($product);

        if (!empty($event_data['ecommerce'])) {
            $json = esc_attr(wp_json_encode($event_data['ecommerce']));
            $html = str_replace('class="', 'data-event_data=\''.$json.'\' class="', $html);
        }

        return $html;
    }
}
