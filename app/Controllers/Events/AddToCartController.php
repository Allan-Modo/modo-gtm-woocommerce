<?php
namespace ModoGtmWc\Controllers\Events;

use ModoGtmWc\Models\EventDataBuilder;

if (!defined('ABSPATH')) exit;

/**
 * Contrôleur de l'évènement « add_to_cart » (ajout au panier).
 *
 * Deux modes sont pris en charge :
 * - Non‑AJAX : lors du hook `woocommerce_add_to_cart`, on prépare les données et
 *   on injecte un script en footer qui pousse l'évènement dans le dataLayer.
 * - AJAX : sur les listes/loops, on ajoute un attribut `data-event_data` au bouton ;
 *   le script front lira ces données après l'ajout et poussera l'évènement côté navigateur.
 */
class AddToCartController {
    /**
     * Enregistre les hooks WooCommerce liés à l'ajout au panier.
     * - Action: `woocommerce_add_to_cart` (non‑AJAX)
     * - Filtre: `woocommerce_loop_add_to_cart_link` (AJAX via attribut HTML)
     */
    public function register(): void {
        add_action('woocommerce_add_to_cart', [$this, 'handle'], 10, 6);
        add_filter('woocommerce_loop_add_to_cart_link', [$this, 'augmentLoopButton'], 10, 3);
    }

    /**
     * Cas non‑AJAX : construit la charge eCommerce et programme son injection en footer.
     *
     * @hook woocommerce_add_to_cart
     * @param string $cart_item_key  Clé de la ligne dans le panier
     * @param int    $product_id     ID du produit (parent si variation)
     * @param int    $quantity       Quantité ajoutée
     * @param int    $variation_id   ID de la variation (0 si aucune)
     * @param array  $variation      Attributs de variation (ex: [pa_taille => m])
     * @param array  $cart_item_data Données additionnelles de la ligne panier
     */
    public function handle($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data): void {
        // Vérifier l'activation de l'évènement dans les réglages
        $settings = get_option('modogtmwc_add_to_cart_settings', []);
        if (empty($settings['event_add_to_cart'])) return;

        // Récupérer le produit (priorité à la variation le cas échéant)
        $product = wc_get_product($variation_id ?: $product_id);
        if (!$product) return;

        // Préparer la charge eCommerce si l'option est cochée
        $event_data = [];
        if (!empty($settings['event_add_to_cart_include_data'])) {
            $builder = new EventDataBuilder();

            // Construire l'item en passant les attributs de variation du hook
            $item = $builder->buildItem($product, (int) $quantity, [
                'variation_values' => is_array($variation) ? array_values($variation) : [],
                'variant_join'     => ',',
            ]);

            $event_data['ecommerce'] = [
                'currency' => get_woocommerce_currency(),
                // value = prix unitaire courant × quantité
                'value'    => (float) $product->get_price() * (int) $quantity,
                'items'    => [$item],
            ];
        }

        // Injecter le push dataLayer dans le footer
        $this->render_view('add_to_cart', $event_data);
    }

    /**
     * Cas AJAX (loop/liste) : enrichit le bouton avec un attribut `data-event_data`.
     * Le script front lira cet attribut lors de l'évènement jQuery `added_to_cart`.
     *
     * @filter woocommerce_loop_add_to_cart_link
     * @param string      $html    HTML du bouton
     * @param \WC_Product $product Produit concerné
     * @param array       $args    Arguments additionnels
     * @return string     HTML (potentiellement enrichi)
     */
    public function augmentLoopButton($html, $product, $args) {
        // Vérifier l'activation de l'évènement
        $settings = get_option('modogtmwc_add_to_cart_settings', []);
        if (empty($settings['event_add_to_cart'])) return $html;

        // Préparer un payload eCommerce minimal si requis
        $event_data = [];
        if (!empty($settings['event_add_to_cart_include_data'])) {
            $builder = new EventDataBuilder();
            $item = $builder->buildItem($product, 1, [ 'variant_join' => ',' ]);
            $event_data['ecommerce'] = [
                'currency' => get_woocommerce_currency(),
                'value'    => (float) $product->get_price(),
                'items'    => [$item],
            ];
        }

        // Injecter data-event_data s'il y a des données à transmettre
        if (!empty($event_data['ecommerce'])) {
            $json = esc_attr(wp_json_encode($event_data['ecommerce']));
            $html = str_replace('class=\"', 'data-event_data=\'' . $json . '\' class=\"', $html);
        }
        return $html;
    }

    /**
     * Diffère l'injection du script au footer via le template partagé.
     */
    private function render_view(string $event, array $data): void {
        $template = dirname(__DIR__, 2) . '/Views/scripts/push-event.php';
        if (file_exists($template)) {
            add_action('wp_footer', function () use ($template, $event, $data) {
                include $template;
            });
        }
    }
}

