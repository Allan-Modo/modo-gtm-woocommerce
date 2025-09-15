<?php
namespace ModoGtmWc\Controllers\Events;

use ModoGtmWc\Models\EventDataBuilder;

if (!defined('ABSPATH')) exit;

/**
 * Agrège les produits affichés en listes et pousse un unique évènement view_item_list.
 * Collecte les items pendant la boucle et rend une seule fois en footer avec le contexte.
 */
class ViewItemListController {
    private static $already_collected = [];
    private static $position_index = 0;
    private static $event_data = [
        'ecommerce' => [
            'item_list_id' => '',
            'item_list_name' => '',
            'items' => [],
        ]
    ];

    /** Enregistre les hooks de collecte (boucle) et de rendu (footer). */
    public function register(): void {
        add_action('the_post', [$this, 'handle'], 20, 1);
        add_action('wp_footer', [$this, 'render'], 10, 0);
    }

    /**
     * Lorsqu'un produit est rencontré dans la boucle, collecte ses données une seule fois.
     *
     * @hook the_post
     */
    public function handle($post): void {
        $settings = get_option('modogtmwc_view_item_list_settings', []);
        if (empty($settings['event_view_item_list'])) return;

        if (!is_object($post) || $post->post_type !== 'product') return;

        $product = wc_get_product($post->ID);
        if (!$product) return;

        // avoid adding the current single product to the list
        if (is_product()) {
            $current_product_id = get_queried_object()->ID ?? 0;
            if ($product->get_id() === $current_product_id || $product->get_sku() === get_post_meta($current_product_id, '_sku', true)) {
                return;
            }
        }

        $this->collect_unique_product($product);
    }

    /** Ajoute un produit à la charge eCommerce s'il n'a pas déjà été vu. */
    private function collect_unique_product(\WC_Product $product): void {
        $unique_key = $product->get_sku() ?: $product->get_id();
        if (isset(self::$already_collected[$unique_key])) return;

        $builder = new EventDataBuilder();
        $data = $builder->buildItem($product, 1, ['suppress_variants' => true]);
        $data['index'] = self::$position_index++;
        self::$event_data['ecommerce']['items'][] = $data;

        self::$already_collected[$unique_key] = true;
    }

    /** Rend l'évènement view_item_list agrégé dans le footer. */
    public function render(): void {
        $settings = get_option('modogtmwc_view_item_list_settings', []);
        if (empty($settings['event_view_item_list'])) return;

        // map list context
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
                $listId   = (string) ($qo->ID ?? 'context');
            }
            self::$event_data['ecommerce']['item_list_id'] = esc_js($listId);
            self::$event_data['ecommerce']['item_list_name'] = esc_js($listName);
        } else {
            self::$event_data = [];
        }

        $this->render_view('view_item_list', self::$event_data);
    }

    /** Inclut le template partagé et affiche le rendu. */
    private function render_view(string $event, array $data): void {
        $template = dirname(__DIR__, 2) . '/Views/scripts/push-event.php';
        if (file_exists($template)) {
            // Use direct output in footer for consistency
            echo $this->render_template($template, compact('event', 'data'));
        }
    }

    /** Helper simple pour rendre un template PHP avec variables scellées. */
    private function render_template(string $template, array $vars): string {
        ob_start();
        extract($vars);
        include $template;
        return ob_get_clean();
    }
}
