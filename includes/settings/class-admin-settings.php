<?php
if (!defined('ABSPATH')) exit;

class MODOGTMWC_Admin_Settings {

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_settings_page']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
    }

	public static function enqueue_scripts($hook) {
        wp_enqueue_script('modogtmwc-admin-script',MODOGTMWC_URL . 'assets/admin/js/admin-script.js',['jquery'],'1.0',true);
    }

    public static function add_settings_page() {
        add_options_page(
            'Paramètres GTM WooCommerce',
            'GTM WooCommerce',
            'manage_options',
            'modogtmwc-settings',
            [__CLASS__, 'render_settings_page']
        );
    }

    public static function register_settings() {
        register_setting('modogtmwc_add_to_cart_group', 'modogtmwc_add_to_cart_settings');
        register_setting('modogtmwc_remove_from_cart_group', 'modogtmwc_remove_from_cart_settings');
        register_setting('modogtmwc_purchase_group', 'modogtmwc_purchase_settings');
        register_setting('modogtmwc_view_item_group', 'modogtmwc_view_item_settings');
        register_setting('modogtmwc_view_item_list_group', 'modogtmwc_view_item_list_settings');
        register_setting('modogtmwc_group', 'modogtmwc_settings');

        /* ================ */
        /*   ADD TO CART    */
        /* ================ */

        // Section principale
        add_settings_section(
            'modogtmwc_add_to_cart_section',
            'Paramètres "Ajout au panier"',
            null,
            'modogtmwc-settings-add-to-cart'
        );

        // Case principale
        add_settings_field(
            'event_add_to_cart',
            'Activer l\'événement "Ajout au panier"',
            [__CLASS__, 'render_checkbox'],
            'modogtmwc-settings-add-to-cart',
            'modogtmwc_add_to_cart_section',
            [
                'label_for' => 'event_add_to_cart',
                'option_name' => 'modogtmwc_add_to_cart_settings'
            ]
        );

        // Case inclure données
        add_settings_field(
            'event_add_to_cart_include_data',
            'Inclure les données du panier',
            [__CLASS__, 'render_checkbox'],
            'modogtmwc-settings-add-to-cart',
            'modogtmwc_add_to_cart_section',
            [
                'label_for' => 'event_add_to_cart_include_data',
                'class' => 'sub-option sub-option-add-to-cart',
                'depends' => 'event_add_to_cart',
                'option_name' => 'modogtmwc_add_to_cart_settings'
            ]
        );

        // Case détails produit
        add_settings_field(
            'event_add_to_cart_product_details',
            'Inclure les détails du produit ajouté',
            [__CLASS__, 'render_checkbox'],
            'modogtmwc-settings-add-to-cart',
            'modogtmwc_add_to_cart_section',
            [
                'label_for' => 'event_add_to_cart_product_details',
                'class' => 'sub-option sub-option-add-to-cart',
                'depends_multi' => ['event_add_to_cart', 'event_add_to_cart_include_data'],
                'option_name' => 'modogtmwc_add_to_cart_settings'
            ]
        );

        /* ====================== */
        /*    REMOVE FROM CART    */
        /* ====================== */

        // Section principale
        add_settings_section(
            'modogtmwc_remove_from_cart_section',
            'Paramètres "Supprimer du panier"',
            null,
            'modogtmwc-settings-remove-from-cart'
        );

        // Case principale
        add_settings_field(
            'event_remove_from_cart',
            'Activer l\'événement "Supprimer du panier"',
            [__CLASS__, 'render_checkbox'],
            'modogtmwc-settings-remove-from-cart',
            'modogtmwc_remove_from_cart_section',
            [
                'label_for' => 'event_remove_from_cart',
                'option_name' => 'modogtmwc_remove_from_cart_settings'
            ]
        );

        // Case inclure données
        add_settings_field(
            'event_remove_from_cart_include_data',
            'Inclure les données du panier',
            [__CLASS__, 'render_checkbox'],
            'modogtmwc-settings-remove-from-cart',
            'modogtmwc_remove_from_cart_section',
            [
                'label_for' => 'event_remove_from_cart_include_data',
                'class' => 'sub-option sub-option-remove-from-cart',
                'depends' => 'event_remove_from_cart',
                'option_name' => 'modogtmwc_remove_from_cart_settings'
            ]
        );

        // Case détails produit
        add_settings_field(
            'event_remove_from_cart_product_details',
            'Inclure les détails du produit supprimé',
            [__CLASS__, 'render_checkbox'],
            'modogtmwc-settings-remove-from-cart',
            'modogtmwc_remove_from_cart_section',
            [
                'label_for' => 'event_remove_from_cart_product_details',
                'class' => 'sub-option sub-option-remove-from-cart',
                'depends_multi' => ['event_remove_from_cart', 'event_remove_from_cart_include_data'],
                'option_name' => 'modogtmwc_remove_from_cart_settings'
            ]
        );

        /* ================ */
        /*     PURCHASE     */
        /* ================ */

        // Section principale
        add_settings_section(
            'modogtmwc_purchase_section',
            'Paramètres "Commande effectuée"',
            null,
            'modogtmwc-settings-purchase'
        );

        // Case principale
        add_settings_field(
            'event_purchase',
            'Activer l\'événement "Commande effectuée"',
            [__CLASS__, 'render_checkbox'],
            'modogtmwc-settings-purchase',
            'modogtmwc_purchase_section',
            [
                'label_for' => 'event_purchase',
                'option_name' => 'modogtmwc_purchase_settings'
            ]
        );

        // Case inclure données
        add_settings_field(
            'event_purchase_include_data',
            'Inclure les données de la commande',
            [__CLASS__, 'render_checkbox'],
            'modogtmwc-settings-purchase',
            'modogtmwc_purchase_section',
            [
                'label_for' => 'event_purchase_include_data',
                'class' => 'sub-option sub-option-purchase',
                'depends' => 'event_purchase',
                'option_name' => 'modogtmwc_purchase_settings'
            ]
        );

        // Case détails produit
        add_settings_field(
            'event_purchase_products_details',
            'Inclure les détails des produits commandés',
            [__CLASS__, 'render_checkbox'],
            'modogtmwc-settings-purchase',
            'modogtmwc_purchase_section',
            [
                'label_for' => 'event_purchase_products_details',
                'class' => 'sub-option sub-option-purchase',
                'depends_multi' => ['event_purchase', 'event_purchase_include_data'],
                'option_name' => 'modogtmwc_purchase_settings'
            ]
        );

        /* ================= */
        /*     VIEW ITEM     */
        /* ================= */

        // Section principale
        add_settings_section(
            'modogtmwc_view_item_section',
            'Paramètres "Vue d\'une page produit"',
            null,
            'modogtmwc-settings-view-item'
        );

        // Case principale
        add_settings_field(
            'event_view_item',
            'Activer l\'événement "Vue d\'une page produit"',
            [__CLASS__, 'render_checkbox'],
            'modogtmwc-settings-view-item',
            'modogtmwc_view_item_section',
            [
                'label_for' => 'event_view_item',
                'option_name' => 'modogtmwc_view_item_settings'
            ]
        );

        // Case détails produit
        add_settings_field(
            'event_view_item_products_details',
            'Inclure les détails du produit vu',
            [__CLASS__, 'render_checkbox'],
            'modogtmwc-settings-view-item',
            'modogtmwc_view_item_section',
            [
                'label_for' => 'event_view_item_products_details',
                'class' => 'sub-option sub-option-view-item',
                'depends' => 'event_view_item',
                'option_name' => 'modogtmwc_view_item_settings'
            ]
        );

        /* ====================== */
        /*     VIEW ITEM LIST     */
        /* ====================== */

        // Section principale
        add_settings_section(
            'modogtmwc_view_item_list_section',
            'Paramètres "Vue d\'une page produit"',
            null,
            'modogtmwc-settings-view-item-list'
        );

        // Case principale
        add_settings_field(
            'event_view_item_list',
            'Activer l\'événement "Vue d\'une liste de produit"',
            [__CLASS__, 'render_checkbox'],
            'modogtmwc-settings-view-item-list',
            'modogtmwc_view_item_list_section',
            [
                'label_for' => 'event_view_item_list',
                'option_name' => 'modogtmwc_view_item_list_settings'
            ]
        );

        // Case détails produit
        add_settings_field(
            'event_view_item_list_products_details',
            'Inclure les détails des produits vus',
            [__CLASS__, 'render_checkbox'],
            'modogtmwc-settings-view-item-list',
            'modogtmwc_view_item_list_section',
            [
                'label_for' => 'event_view_item_products_details',
                'class' => 'sub-option sub-option-view-item-list',
                'depends' => 'event_view_item_list',
                'option_name' => 'modogtmwc_view_item_list_settings'
            ]
        );

        /* ========================= */
        /*     ADVANCED SETTINGS     */
        /* ========================= */

        add_settings_section(
            'modogtmwc_section',
            'Paramètres avancés',
            null,
            'modogtmwc-settings'
        );

        add_settings_field(
            'events_smart_categories',
            'Gestion intelligente des catégories',
            [__CLASS__, 'render_checkbox'],
            'modogtmwc-settings',
            'modogtmwc_section',
            [
                'label_for' => 'events_smart_categories', 
                'description' => 'Si cochée, l\'envoi se base sur la catégorie avec le plus de profondeur hiérarchique et envoi chaque catégorie jusqu\'à 5 niveau de profondeur. Si décochée, l\'envoi se base uniquement sur les catégorie de premier niveau',
                'option_name' => 'modogtmwc_settings'
            ]
        );
    }

    public static function render_checkbox($args) {
        // On récupère le nom de l’option (par défaut: modogtmwc_settings)
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


    public static function render_settings_page() {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'add_to_cart';
        ?>

        <div class="wrap modogtmwc-settings-page">
            <h1>Paramètres GTM WooCommerce</h1>
            <p> Cochez les évènements que vous souhaitez mettre en place sur votre boutique </p>

            <h2 class="nav-tab-wrapper">
                <a href="?page=modogtmwc-settings&tab=add_to_cart" 
                   class="nav-tab <?php echo $active_tab == 'add_to_cart' ? 'nav-tab-active' : ''; ?>">
                   Ajout au panier
                </a>
                <a href="?page=modogtmwc-settings&tab=remove_from_cart" 
                   class="nav-tab <?php echo $active_tab == 'remove_from_cart' ? 'nav-tab-active' : ''; ?>">
                   Suppression du panier
                </a>
                <a href="?page=modogtmwc-settings&tab=purchase" 
                   class="nav-tab <?php echo $active_tab == 'purchase' ? 'nav-tab-active' : ''; ?>">
                   Commande effectuée
                </a>
                <a href="?page=modogtmwc-settings&tab=view_item" 
                   class="nav-tab <?php echo $active_tab == 'view_item' ? 'nav-tab-active' : ''; ?>">
                   Vue d'une page produit
                </a>
                <a href="?page=modogtmwc-settings&tab=view_item_list" 
                   class="nav-tab <?php echo $active_tab == 'view_item_list' ? 'nav-tab-active' : ''; ?>">
                   Vue d'une liste de produit
                </a>
                <a href="?page=modogtmwc-settings&tab=advanced-settings" 
                   class="nav-tab <?php echo $active_tab == 'advanced-settings' ? 'nav-tab-active' : ''; ?>">
                   Paramètres avancés
                </a>
            </h2>

            <form method="post" action="options.php">
                <?php
                switch ($active_tab){
                    case 'add_to_cart':
                        settings_fields('modogtmwc_add_to_cart_group');
                        do_settings_sections('modogtmwc-settings-add-to-cart');
                        break;
                    case 'remove_from_cart':
                        settings_fields('modogtmwc_remove_from_cart_group');
                        do_settings_sections('modogtmwc-settings-remove-from-cart');
                        break;
                    case 'purchase':
                        settings_fields('modogtmwc_purchase_group');
                        do_settings_sections('modogtmwc-settings-purchase');
                        break;
                    case 'view_item':
                        settings_fields('modogtmwc_view_item_group');
                        do_settings_sections('modogtmwc-settings-view-item');
                        break;
                    case 'view_item_list':
                        settings_fields('modogtmwc_view_item_list_group');
                        do_settings_sections('modogtmwc-settings-view-item-list');
                        break;
                    case 'advanced-settings':
                        settings_fields('modogtmwc_group');
                        do_settings_sections('modogtmwc-settings');
                        break;
                }

                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}
