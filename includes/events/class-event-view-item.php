<?php
if (!defined('ABSPATH')) exit;

class MODOGTMWC_Event_View_Item {
    
    public static function init() {
        // Détection de la vue d'un produit grâce au hook "woocommerce_after_single_product"
        add_filter('woocommerce_after_single_product', [__CLASS__, 'handle_event'], 10);
    }
    

    /*
    *   Construction des données d'un produit
    */
    public static function build_item($product) {
        $advanced_settings = get_option('modogtmwc_settings', []);

        $item = [
            'item_id'   => $product->get_sku() ?: $product->get_id(),
            'item_name' => $product->get_name(),
            'price'     => (float) $product->get_price(),
            'quantity'  => 1,
        ];
        
        // Gestion de la marque
        $product_id_for_brands = $product->is_type('variable') ? $product->get_parent_id() : $product->get_id();
        $brand = get_the_terms($product_id_for_brands, 'product_brand');
        if (!empty($brand) && !is_wp_error($brand)) {
            $item['item_brand'] = $brand[0]->name;
        }
        
        // Gestion des catégories
        $product_id_for_terms = $product->is_type('variable') ? $product->get_parent_id() : $product->get_id();
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
        
        // Gestion des promotions
        $discount = 0;
        if ($product->is_on_sale()) {
            $discount = ((double) $product->get_regular_price() - (double) $product->get_sale_price());
        }
        if($discount > 0){
            $item['discount'] = $discount;
        }

        // Gestion des variations
        if ($product->is_type('variable')) {
            $attributes = $product->get_attributes();
            $selected_attributes = [];
            foreach ( $attributes as $taxonomy => $attr_obj ) {
                $param_key = 'attribute_' . $taxonomy;
                if ( isset( $_GET[$param_key] ) ) {
                    $term_slug = wc_clean( wp_unslash( $_GET[$param_key] ) );
                    $term = get_term_by( 'slug', $term_slug, $taxonomy );
                    if ( $term ) {
                        $selected_attributes[$taxonomy] = $term_slug;
                    }
                }
            }

            if ( empty( $selected_attributes ) ) {
                $selected_attributes = $product->get_default_attributes();
            }

            if(!empty($selected_attributes)) {
                foreach ($selected_attributes as $attribute) {
                    $variation_attributes[] = ucfirst($attribute);
                }
                $item['item_variant'] = implode(',', $variation_attributes);
            }

        }

        return $item;
    }
    

    /*
    *   Envoi des données au datalayer 
    */
    public static function handle_event(){
        global $product;
        $settings = get_option('modogtmwc_view_item_settings', []);
        
        // Vérification de l'activation de l'évènement
        if (empty($settings['event_view_item'])) {
            return;
        }
        $event_data = [];
        
        // Vérification de l'activation de l'envoi des données de l'évènement
        if (!empty($settings['event_view_item_products_details'])) {    
            $event_data['ecommerce'] = [
                'currency'       => get_woocommerce_currency(),
                'value'          => (float) $product->get_price(),
                'items'          => [ self::build_item($product) ]
            ];
        }
        
        // Injection dans le footer
            add_action('wp_footer', function () use ($event_data) {
                ?>
                <script>
                window.dataLayer = window.dataLayer || [];
                window.dataLayer.push({
                    event: "view_item",
                    <?php echo substr(wp_json_encode($event_data), 1, -1); ?>
                });
                </script>
                <?php
            });
        
        // if ($product->is_type('variable')) {
        //     $variations_data = [];
        //     foreach ($product->get_children() as $variation_id) {
        //         $variation = wc_get_product($variation_id);
        //         if (!$variation) continue;
        //         $variations_data[$variation_id] = self::build_item($variation);
        //     }
            
        //     // Transmition des données vers un ficher JS pour être traité en JS.
        //     wp_localize_script('modogtmwc-front-script', 'modoProductData', [
        //         'event_view_item_products_details' => $settings['event_view_item_products_details'],
        //         'currency'   => get_woocommerce_currency(),
        //         'variations' => $variations_data,
        //     ]);
        // }
    }
}
