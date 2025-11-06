<?php
namespace ModoGtmWc\Controllers\Admin;

if (!defined('ABSPATH')) exit;

/**
 * Contrôleur des réglages (Admin).
 *
 * Enregistre la configuration de la Settings API (pages, sections, champs)
 * et rend l'interface des réglages du plugin dans l'admin WordPress.
 */
class SettingsController {
    /** Enregistre menus d'admin, réglages et assets. */
    public function register(): void {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function enqueue_scripts($hook): void {
        wp_enqueue_script('modogtmwc-admin-script', MODOGTMWC_URL . 'assets/admin/js/admin-script.js', ['jquery'], '1.0', true);
    }

    public function add_settings_page(): void {
        add_options_page(
            'Paramètres GTM WooCommerce',
            'GTM WooCommerce',
            'manage_options',
            'modogtmwc-settings',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings(): void {
        register_setting('modogtmwc_add_to_cart_group', 'modogtmwc_add_to_cart_settings');
        register_setting('modogtmwc_remove_from_cart_group', 'modogtmwc_remove_from_cart_settings');
        register_setting('modogtmwc_purchase_group', 'modogtmwc_purchase_settings');
        register_setting('modogtmwc_view_item_group', 'modogtmwc_view_item_settings');
        register_setting('modogtmwc_view_item_list_group', 'modogtmwc_view_item_list_settings');
        register_setting('modogtmwc_group', 'modogtmwc_settings');

        // ADD TO CART
        add_settings_section('modogtmwc_add_to_cart_section', 'Paramètres "Ajout au panier"', null, 'modogtmwc-settings-add-to-cart');
        add_settings_field('event_add_to_cart', 'Activer l\'événement "Ajout au panier"', [$this, 'render_checkbox'], 'modogtmwc-settings-add-to-cart', 'modogtmwc_add_to_cart_section', [
            'label_for' => 'event_add_to_cart',
            'option_name' => 'modogtmwc_add_to_cart_settings'
        ]);
        add_settings_field('event_add_to_cart_include_data', 'Inclure les données du panier', [$this, 'render_checkbox'], 'modogtmwc-settings-add-to-cart', 'modogtmwc_add_to_cart_section', [
            'label_for' => 'event_add_to_cart_include_data',
            'class' => 'sub-option sub-option-add-to-cart',
            'depends' => 'event_add_to_cart',
            'option_name' => 'modogtmwc_add_to_cart_settings'
        ]);
        add_settings_field('event_add_to_cart_product_details', 'Inclure les détails du produit ajouté', [$this, 'render_checkbox'], 'modogtmwc-settings-add-to-cart', 'modogtmwc_add_to_cart_section', [
            'label_for' => 'event_add_to_cart_product_details',
            'class' => 'sub-option sub-option-add-to-cart',
            'depends_multi' => ['event_add_to_cart', 'event_add_to_cart_include_data'],
            'option_name' => 'modogtmwc_add_to_cart_settings'
        ]);

        // REMOVE FROM CART
        add_settings_section('modogtmwc_remove_from_cart_section', 'Paramètres "Supprimer du panier"', null, 'modogtmwc-settings-remove-from-cart');
        add_settings_field('event_remove_from_cart', 'Activer l\'événement "Supprimer du panier"', [$this, 'render_checkbox'], 'modogtmwc-settings-remove-from-cart', 'modogtmwc_remove_from_cart_section', [
            'label_for' => 'event_remove_from_cart',
            'option_name' => 'modogtmwc_remove_from_cart_settings'
        ]);
        add_settings_field('event_remove_from_cart_include_data', 'Inclure les données du panier', [$this, 'render_checkbox'], 'modogtmwc-settings-remove-from-cart', 'modogtmwc_remove_from_cart_section', [
            'label_for' => 'event_remove_from_cart_include_data',
            'class' => 'sub-option sub-option-remove-from-cart',
            'depends' => 'event_remove_from_cart',
            'option_name' => 'modogtmwc_remove_from_cart_settings'
        ]);
        add_settings_field('event_remove_from_cart_product_details', 'Inclure les détails du produit supprimé', [$this, 'render_checkbox'], 'modogtmwc-settings-remove-from-cart', 'modogtmwc_remove_from_cart_section', [
            'label_for' => 'event_remove_from_cart_product_details',
            'class' => 'sub-option sub-option-remove-from-cart',
            'depends_multi' => ['event_remove_from_cart', 'event_remove_from_cart_include_data'],
            'option_name' => 'modogtmwc_remove_from_cart_settings'
        ]);

        // PURCHASE
        add_settings_section('modogtmwc_purchase_section', 'Paramètres "Commande effectuée"', null, 'modogtmwc-settings-purchase');
        add_settings_field('event_purchase', 'Activer l\'événement "Commande effectuée"', [$this, 'render_checkbox'], 'modogtmwc-settings-purchase', 'modogtmwc_purchase_section', [
            'label_for' => 'event_purchase',
            'option_name' => 'modogtmwc_purchase_settings'
        ]);
        add_settings_field('event_purchase_include_data', 'Inclure les données de la commande', [$this, 'render_checkbox'], 'modogtmwc-settings-purchase', 'modogtmwc_purchase_section', [
            'label_for' => 'event_purchase_include_data',
            'class' => 'sub-option sub-option-purchase',
            'depends' => 'event_purchase',
            'option_name' => 'modogtmwc_purchase_settings'
        ]);
        add_settings_field('event_purchase_products_details', 'Inclure les détails des produits commandés', [$this, 'render_checkbox'], 'modogtmwc-settings-purchase', 'modogtmwc_purchase_section', [
            'label_for' => 'event_purchase_products_details',
            'class' => 'sub-option sub-option-purchase',
            'depends_multi' => ['event_purchase', 'event_purchase_include_data'],
            'option_name' => 'modogtmwc_purchase_settings'
        ]);

        // VIEW ITEM
        add_settings_section('modogtmwc_view_item_section', 'Paramètres "Vue d\'une page produit"', null, 'modogtmwc-settings-view-item');
        add_settings_field('event_view_item', 'Activer l\'événement "Vue d\'une page produit"', [$this, 'render_checkbox'], 'modogtmwc-settings-view-item', 'modogtmwc_view_item_section', [
            'label_for' => 'event_view_item',
            'option_name' => 'modogtmwc_view_item_settings'
        ]);
        add_settings_field('event_view_item_products_details', 'Inclure les détails du produit vu', [$this, 'render_checkbox'], 'modogtmwc-settings-view-item', 'modogtmwc_view_item_section', [
            'label_for' => 'event_view_item_products_details',
            'class' => 'sub-option sub-option-view-item',
            'depends' => 'event_view_item',
            'option_name' => 'modogtmwc_view_item_settings'
        ]);

        // VIEW ITEM LIST
        add_settings_section('modogtmwc_view_item_list_section', 'Paramètres "Vue d\'une page produit"', null, 'modogtmwc-settings-view-item-list');
        add_settings_field('event_view_item_list', 'Activer l\'événement "Vue d\'une liste de produit"', [$this, 'render_checkbox'], 'modogtmwc-settings-view-item-list', 'modogtmwc_view_item_list_section', [
            'label_for' => 'event_view_item_list',
            'option_name' => 'modogtmwc_view_item_list_settings'
        ]);
        add_settings_field('event_view_item_list_products_details', 'Inclure les détails des produits vus', [$this, 'render_checkbox'], 'modogtmwc-settings-view-item-list', 'modogtmwc_view_item_list_section', [
            'label_for' => 'event_view_item_products_details',
            'class' => 'sub-option sub-option-view-item-list',
            'depends' => 'event_view_item_list',
            'option_name' => 'modogtmwc_view_item_list_settings'
        ]);

        // ADVANCED
        add_settings_section('modogtmwc_section', 'Paramètres avancés', null, 'modogtmwc-settings');
        add_settings_field('events_smart_categories', 'Gestion intelligente des catégories', [$this, 'render_checkbox'], 'modogtmwc-settings', 'modogtmwc_section', [
            'label_for' => 'events_smart_categories',
            'description' => 'Si cochée, l\'envoi se base sur la catégorie avec le plus de profondeur hiérarchique (jusqu\'à cinq niveaux de profondeur) lorsque des sous-catégories existent. Si le produit n\'a pas de sous-catégorie, toutes les catégories du premier niveau du produit sont envoyées. Si décochée, seules les catégories de premier niveau sont envoyées.',
            'option_name' => 'modogtmwc_settings'
        ]);
    }

    /** Rend une case à cocher compatible avec la Settings API de WP. */
    public function render_checkbox($args): void {
        $option_name = isset($args['option_name']) ? $args['option_name'] : 'modogtmwc_settings';
        $options = get_option($option_name, []);
        $id = $args['label_for'];
        $data_attributes = '';
        if (!empty($args['depends'])) {
            $data_attributes .= ' data-depends="' . esc_attr($args['depends']) . '"';
        }
        if (!empty($args['depends_multi'])) {
            $multi = is_array($args['depends_multi']) ? implode(',', $args['depends_multi']) : $args['depends_multi'];
            $data_attributes .= ' data-depends-multi="' . esc_attr($multi) . '"';
        }
        ?>
        <input type="checkbox"
               id="<?php echo esc_attr($id); ?>"
               name="<?php echo esc_attr($option_name); ?>[<?php echo esc_attr($id); ?>]"
               value="1"
               <?php checked(!empty($options[$id])); ?>
               <?php echo $data_attributes; ?>/>
        <?php
        if (!empty($args['description'])) {
            echo '<p class="description">' . $args['description'] . '</p>';
        }
    }

    /** Inclut la vue de la page de réglages avec l'onglet actif. */
    public function render_settings_page(): void {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'add_to_cart';
        $template = dirname(__DIR__, 2) . '/Views/admin/settings-page.php';
        if (file_exists($template)) {
            include $template;
        }
    }
}
