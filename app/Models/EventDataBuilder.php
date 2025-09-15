<?php
namespace ModoGtmWc\Models;

if (!defined('ABSPATH')) exit;

/**
 * Construit les tableaux d'items eCommerce pour les pushs dataLayer.
 *
 * Centralise le mapping des données produit (marque, catégories, variantes, remises)
 * et permet des ajustements selon le contexte via des options.
 */
class EventDataBuilder {
    /**
     * Build a single ecommerce item array for a product.
     *
     * @param \WC_Product $product
     * @param int $quantity
     * @return array
     */
    /**
     * Construit la structure d'un item eCommerce pour un produit.
     *
     * @param \WC_Product $product  Produit (ou variation)
     * @param int         $quantity Quantité pour cet item
     * @param array       $opts     Options de contexte: suppress_variants, variation_values, variant_join
     * @return array
     */
    public function buildItem(\WC_Product $product, int $quantity = 1, array $opts = []): array {
        $defaults = [
            'suppress_variants' => false,
            'variation_values'  => [], // array of raw attribute values from hook
            'variant_join'      => ', ', // how to join multiple variant values
        ];
        $opts = array_merge($defaults, $opts);
        $advanced_settings = get_option('modogtmwc_settings', []);

        // Champs de base de l'item
        $item = [
            'item_id'   => $product->get_sku() ?: $product->get_id(),
            'item_name' => $product->get_name(),
            'price'     => (float) $product->get_price(),
            'quantity'  => $quantity,
        ];

        // Brand (parent for variations)
        $product_id_for_terms = $product->is_type('variation') ? $product->get_parent_id() : $product->get_id();
        $brand = get_the_terms($product_id_for_terms, 'product_brand');
        if (!empty($brand) && !is_wp_error($brand)) {
            $item['item_brand'] = $brand[0]->name;
        }

        // Catégories (mode smart: chemin le plus profond; sinon: catégories de niveau 1)
        $terms = get_the_terms($product_id_for_terms, 'product_cat');
        if ($terms && !is_wp_error($terms)) {
            $cat_levels = [];

            if (!empty($advanced_settings['events_smart_categories'])) {
                $deepest_cat = null;
                $max_depth = 0;
                foreach ($terms as $term) {
                    $depth = 0;
                    $current = $term;
                    while ($current->parent) {
                        $current = get_term($current->parent, 'product_cat');
                        $depth++;
                    }
                    if ($depth > $max_depth) {
                        $max_depth = $depth;
                        $deepest_cat = $term;
                    }
                }
                $current = $deepest_cat;
                while ($current) {
                    array_unshift($cat_levels, $current->name);
                    $current = $current->parent ? get_term($current->parent, 'product_cat') : null;
                }
            } else {
                foreach ($terms as $term) {
                    if ($term->parent == 0) {
                        $cat_levels[] = $term->name;
                    }
                }
            }

            foreach ($cat_levels as $index => $cat_name) {
                $key = $index === 0 ? 'item_category' : 'item_category' . ($index + 1);
                $item[$key] = $cat_name;
            }
        }

        // Remise (variation > variable > simple)
        $discount = 0;
        if ($product->is_on_sale()) {
            if ($product->is_type('variation')) {
                $regular = (double) $product->get_regular_price();
                $sale    = (double) $product->get_sale_price();
                if ($regular > 0 && $sale > 0) { $discount = $regular - $sale; }
            } elseif ($product->is_type('variable')) {
                $regular = (double) $product->get_regular_price();
                $sale    = (double) $product->get_sale_price();
                if ($regular > 0 && $sale > 0) { $discount = $regular - $sale; }
            } else {
                $discount = ((double) $product->get_regular_price() - (double) $product->get_sale_price());
            }
        }
        if ($discount > 0) {
            $item['discount'] = $discount;
        }

        // Variantes avec priorité selon le contexte
        if (!$opts['suppress_variants']) {
            $variant_values = [];
            if (!empty($opts['variation_values'])) {
                foreach ($opts['variation_values'] as $val) {
                    $variant_values[] = ucfirst($val);
                }
            } elseif ($product->is_type('variation')) {
                $variation_attributes = $product->get_variation_attributes();
                if (!empty($variation_attributes)) {
                    $variant_values = array_values($variation_attributes);
                }
            } elseif ($product->is_type('variable')) {
                $attributes = $product->get_attributes();
                $selected_attributes = [];
                foreach ($attributes as $taxonomy => $attr_obj) {
                    $param_key = 'attribute_' . $taxonomy;
                    if (isset($_GET[$param_key])) {
                        $term_slug = wc_clean(wp_unslash($_GET[$param_key]));
                        $term = get_term_by('slug', $term_slug, $taxonomy);
                        if ($term) {
                            $selected_attributes[$taxonomy] = $term_slug;
                        }
                    }
                }
                if (empty($selected_attributes)) {
                    $selected_attributes = $product->get_default_attributes();
                }
                if (!empty($selected_attributes)) {
                    $variant_values = array_values($selected_attributes);
                }
            }
            if (!empty($variant_values)) {
                $variant_values = array_map(function($v){ return ucfirst($v); }, $variant_values);
                $item['item_variant'] = implode($opts['variant_join'], $variant_values);
            }
        }

        return $item;
    }
}
