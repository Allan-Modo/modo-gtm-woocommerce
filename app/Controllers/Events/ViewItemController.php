<?php
namespace ModoGtmWc\Controllers\Events;

use ModoGtmWc\Models\EventDataBuilder;

if (!defined('ABSPATH')) exit;

/**
 * Contrôleur de l'évènement « view_item » (vue d'une page produit).
 *
 * Construit la charge item selon les réglages (devise, valeur, détails produits)
 * et programme l'injection d'un script en footer pour pousser l'évènement.
 */
class ViewItemController {
    /** Enregistre le hook d'affichage d'une page produit. */
    public function register(): void {
        add_action('woocommerce_after_single_product', [$this, 'handle']);
    }

    /**
     * Construit et rend les données view_item pour le produit courant.
     *
     * @hook woocommerce_after_single_product
     */
    public function handle(): void {
        global $product;
        if (!$product instanceof \WC_Product) return;

        $settings = get_option('modogtmwc_view_item_settings', []);
        if (empty($settings['event_view_item'])) return;

        $builder = new EventDataBuilder();
        $event_data = [];

        // Inclure les détails produit si l'option est active
        if (!empty($settings['event_view_item_products_details'])) {
            $event_data['ecommerce'] = [
                'currency' => get_woocommerce_currency(),
                'value'    => (float) $product->get_price(),
                // buildItem gère la récupération des variantes (GET attribute_* ou défauts)
                'items'    => [$builder->buildItem($product, 1, ['variant_join' => ','])],
            ];
        }

        $this->render_view($event_data);
    }

    /** Diffère l'injection du script au footer via le template partagé. */
    private function render_view(array $event_data): void {
        $event = 'view_item';
        $data = $event_data;
        $template = dirname(__DIR__, 2) . '/Views/scripts/push-event.php';
        if (file_exists($template)) {
            add_action('wp_footer', function () use ($template, $event, $data) {
                include $template;
            });
        }
    }
}
