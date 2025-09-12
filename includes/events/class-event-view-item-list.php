<?php
if (!defined('ABSPATH')) exit;

class MODOGTMWC_Event_View_Item_List {

    private static $already_collected = [];
    private static $position_index = 0;
    private static $event_data = [
        'ecommerce' => [
            'item_list_id' => '',
            'item_list_name' => '',
            'items' => [],
        ]
    ];

    public static function init() {
        add_action('the_post', [__CLASS__, 'handle_event'], 20, 1);
        add_action('wp_footer', [__CLASS__, 'render'], 10, 0);
    }

    public static function handle_event($post) {
        $settings = get_option('modogtmwc_view_item_list_settings', []);

        if (empty($settings['event_view_item_list'])) return [];

        if ($post->post_type !== 'product') return;
        $product = wc_get_product(is_object($post) ? $post->ID : (int) $post);

        if (is_product()) {
            $current_product_id = get_queried_object()->ID;
            if ($product->get_id() === $current_product_id || $product->get_sku() === get_post_meta($current_product_id, '_sku', true)) {
                return;
            }
        }
        if ($product) self::collect_unique_product($product);
    }

    private static function collect_unique_product(\WC_Product $product) {
        // Création d'une clé unique pour différencier les produits
        $unique_key = $product->get_sku() ?: $product->get_id();

        // Détection des produits déja enregistrés
        if (isset(self::$already_collected[$unique_key])) return;

        // Ajout des données des produits dans les données de l'évènement view_item_list
        $products_data = self::build_product_data($product);
        array_push(self::$event_data['ecommerce']['items'], $products_data);

        if (!self::$event_data) return;

        self::$already_collected[$unique_key] = true;
    }

    private static function build_product_data(\WC_Product $product) {
        $advanced_settings = get_option('modogtmwc_settings', []);

        $data = [
            'item_id'   => $product->get_sku() ?: $product->get_id(),
            'item_name' => $product->get_name(),
            'price'     => (float) $product->get_price(),
            'index'     => self::$position_index++,

        ];

        // Gestion de la marque
        $product_id_for_brands = $product->get_id();
        $brand = get_the_terms($product_id_for_brands, 'product_brand');
        if (!empty($brand) && !is_wp_error($brand)) {
            $data['item_brand'] = $brand[0]->name;
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
                $data[$key] = $cat_name;
            }
        }

        // Discount
        $discount = 0;
        if ($product->is_on_sale()) {
            $regularPrice = (float) $product->get_regular_price();
            $salePrice    = (float) $product->get_sale_price();
            if ($regularPrice > 0 && $salePrice > 0) {
                $discount = $regularPrice - $salePrice;
            }
        }
        if ($discount > 0) {
            $data['discount'] = $discount;
        }

        return $data;
    }

    public static function render() {
        if (empty(self::$event_data)) return;

        $settings = get_option('modogtmwc_view_item_list_settings', []);

        if (empty($settings['event_view_item_list'])) return [];

        if (!empty($settings['event_view_item_products_details'])){
            if (is_shop()) {
                $listName = 'Shop';
                $listId   = (string) get_option('woocommerce_shop_page_id');
            } elseif (is_product_category()) {
                $obj = get_queried_object();
                $listName = $obj->name ?? 'Category';
                $listId   = (string) ($obj->term_id ?? '');
            } elseif (is_search()) {
                $listName = 'Search: ' . get_search_query();
                $listId   = 'search';
            } else {
                $qo = get_queried_object();
                $listName = $qo->post_title ?? 'Products';
                $listId   = $qo->ID ?? 'context';
            }

            self::$event_data['ecommerce']['item_list_id'] = esc_js($listId);
            self::$event_data['ecommerce']['item_list_name'] = esc_js($listName);
        } else {
            self::$event_data = [];
        }

        ?>
        <script>
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
            event: "view_item_list",
            <?php echo substr(wp_json_encode(self::$event_data), 1, -1); ?>
        });
        </script>
        <?php
    }
}
