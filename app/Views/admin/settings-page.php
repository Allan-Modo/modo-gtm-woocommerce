<?php
if (!defined('ABSPATH')) exit;
// $active_tab is provided by the controller
?>
<div class="wrap modogtmwc-settings-page">
    <h1>Paramètres GTM WooCommerce</h1>
    <p> Cochez les évènements que vous souhaitez mettre en place sur votre boutique </p>

    <h2 class="nav-tab-wrapper">
        <a href="?page=modogtmwc-settings&tab=add_to_cart" class="nav-tab <?php echo $active_tab == 'add_to_cart' ? 'nav-tab-active' : ''; ?>">Ajout au panier</a>
        <a href="?page=modogtmwc-settings&tab=remove_from_cart" class="nav-tab <?php echo $active_tab == 'remove_from_cart' ? 'nav-tab-active' : ''; ?>">Suppression du panier</a>
        <a href="?page=modogtmwc-settings&tab=purchase" class="nav-tab <?php echo $active_tab == 'purchase' ? 'nav-tab-active' : ''; ?>">Commande effectuée</a>
        <a href="?page=modogtmwc-settings&tab=view_item" class="nav-tab <?php echo $active_tab == 'view_item' ? 'nav-tab-active' : ''; ?>">Vue d'une page produit</a>
        <a href="?page=modogtmwc-settings&tab=view_item_list" class="nav-tab <?php echo $active_tab == 'view_item_list' ? 'nav-tab-active' : ''; ?>">Vue d'une liste de produit</a>
        <a href="?page=modogtmwc-settings&tab=advanced-settings" class="nav-tab <?php echo $active_tab == 'advanced-settings' ? 'nav-tab-active' : ''; ?>">Paramètres avancés</a>
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

