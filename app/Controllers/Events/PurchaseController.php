<?php
namespace ModoGtmWc\Controllers\Events;

if (!defined('ABSPATH')) exit;

/**
 * Gère l'évènement purchase sur la page de remerciement WooCommerce.
 * Construit la charge eCommerce au niveau commande et item puis l'injecte en footer.
 */
class PurchaseController {
    /** Enregistre le handler purchase sur le hook thankyou. */
    public function register(): void {
        add_action('woocommerce_thankyou', [$this, 'handle'], 10, 1);
    }

    /**
     * Construit et rend les données de l'évènement purchase.
     *
     * @hook woocommerce_thankyou
     */
    public function handle($order_id): void {
        $settings = get_option('modogtmwc_purchase_settings', []);
        if (empty($settings['event_purchase'])) return;

        $order = wc_get_order($order_id);
        if (!$order) return;

        $event_data = [];
        if (!empty($settings['event_purchase_include_data'])) {
            $event_data['ecommerce'] = [
                'transaction_id' => (string) $order->get_order_number(),
                'currency'       => $order->get_currency(),
                'value'          => (float) $order->get_total(),
            ];

            if (!empty($settings['event_purchase_products_details'])) {
                $items = [];
                $advanced_settings = get_option('modogtmwc_settings', []);
                $product_index = 0;

                foreach ($order->get_items() as $item_id => $item) {
                    $product = $item->get_product();
                    if (!$product) continue;

                    $line_total = (float) $item->get_total();
                    $quantity   = (int) $item->get_quantity();

                    $product_data = [
                        'item_id'   => $product->get_sku() ?: $product->get_id(),
                        'item_name' => $product->get_name(),
                        'price'     => $quantity > 0 ? $line_total / $quantity : 0,
                        'quantity'  => $quantity,
                        'index'     => $product_index++,
                    ];

                    // Brand on parent for variations
                    $product_id_for_terms = $product->is_type('variation') ? $product->get_parent_id() : $product->get_id();
                    $brand = get_the_terms($product_id_for_terms, 'product_brand');
                    if (!empty($brand) && !is_wp_error($brand)) {
                        $product_data['item_brand'] = $brand[0]->name;
                    }

                    // Categories
                    $terms = get_the_terms($product_id_for_terms, 'product_cat');
                    if ($terms && !is_wp_error($terms)) {
                        $cat_levels = [];
                        if (!empty($advanced_settings['events_smart_categories'])) {
                            $deepest_cat = null; $max_depth = 0;
                            foreach ($terms as $term) {
                                $depth = 0; $current = $term;
                                while ($current->parent) { $current = get_term($current->parent, 'product_cat'); $depth++; }
                                if ($depth > $max_depth) { $max_depth = $depth; $deepest_cat = $term; }
                            }
                            $current = $deepest_cat;
                            while ($current) { array_unshift($cat_levels, $current->name); $current = $current->parent ? get_term($current->parent, 'product_cat') : null; }
                        } else {
                            foreach ($terms as $term) { if ($term->parent == 0) { $cat_levels[] = $term->name; } }
                        }
                        foreach ($cat_levels as $index => $cat_name) {
                            $key = $index === 0 ? 'item_category' : 'item_category' . ($index + 1);
                            $product_data[$key] = $cat_name;
                        }
                    }

                    // Variations
                    if ($product->is_type('variation')) {
                        $variation_attributes = $product->get_variation_attributes();
                        if (!empty($variation_attributes)) {
                            $product_data['item_variant'] = implode(', ', array_values($variation_attributes));
                        }
                    }

                    // Discount and coupon
                    $coupon_code = $order->get_coupon_codes();
                    if (!empty($coupon_code)) {
                        $product_data['coupon'] = $coupon_code[0];
                        $regular_price = (float) $product->get_price();
                        $paid_price    = (float) ($line_total / max(1, $quantity));
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

        $this->render_view('purchase', $event_data);
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
