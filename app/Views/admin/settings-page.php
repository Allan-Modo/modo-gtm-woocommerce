<?php
// Vue admin : page de réglages principale avec onglets.
// Variable d'entrée : $active_tab (fournie par le contrôleur)
if (!defined('ABSPATH')) exit;
?>
<div class="wrap modogtmwc-settings-page">
    <h1>Paramètres GTM WooCommerce</h1>
    <p> Cochez les évènements que vous souhaitez mettre en place sur votre boutique </p>

    <h2 class="nav-tab-wrapper">
        <a href="?page=modogtmwc-settings&tab=add_to_cart" class="nav-tab <?php echo $active_tab == 'add_to_cart' ? 'nav-tab-active' : ''; ?>">Ajout au panier</a>
        <a href="?page=modogtmwc-settings&tab=remove_from_cart" class="nav-tab <?php echo $active_tab == 'remove_from_cart' ? 'nav-tab-active' : ''; ?>">Suppression du panier</a>
        <a href="?page=modogtmwc-settings&tab=view_cart" class="nav-tab <?php echo $active_tab == 'view_cart' ? 'nav-tab-active' : ''; ?>">Vue du panier</a>
        <a href="?page=modogtmwc-settings&tab=begin_checkout" class="nav-tab <?php echo $active_tab == 'begin_checkout' ? 'nav-tab-active' : ''; ?>">Début du checkout</a>
        <a href="?page=modogtmwc-settings&tab=add_payment_info" class="nav-tab <?php echo $active_tab == 'add_payment_info' ? 'nav-tab-active' : ''; ?>">Sélection paiement</a>
        <a href="?page=modogtmwc-settings&tab=add_shipping_info" class="nav-tab <?php echo $active_tab == 'add_shipping_info' ? 'nav-tab-active' : ''; ?>">Sélection livraison</a>
        <a href="?page=modogtmwc-settings&tab=purchase" class="nav-tab <?php echo $active_tab == 'purchase' ? 'nav-tab-active' : ''; ?>">Commande effectuée</a>
        <a href="?page=modogtmwc-settings&tab=view_item" class="nav-tab <?php echo $active_tab == 'view_item' ? 'nav-tab-active' : ''; ?>">Vue d'une page produit</a>
        <a href="?page=modogtmwc-settings&tab=view_item_list" class="nav-tab <?php echo $active_tab == 'view_item_list' ? 'nav-tab-active' : ''; ?>">Vue d'une liste de produit</a>
        <a href="?page=modogtmwc-settings&tab=advanced-settings" class="nav-tab <?php echo $active_tab == 'advanced-settings' ? 'nav-tab-active' : ''; ?>">Paramètres avancés</a>
    </h2>

    <!-- Formulaire relié à la Settings API -->
    <form method="post" action="options.php">
        <?php
        // Rendu conditionnel des sections/champs selon l'onglet actif
        switch ($active_tab){
            case 'add_to_cart':
                settings_fields('modogtmwc_add_to_cart_group');
                do_settings_sections('modogtmwc-settings-add-to-cart');
                break;
            case 'remove_from_cart':
                settings_fields('modogtmwc_remove_from_cart_group');
                do_settings_sections('modogtmwc-settings-remove-from-cart');
                break;
            case 'view_cart':
                settings_fields('modogtmwc_view_cart_group');
                do_settings_sections('modogtmwc-settings-view-cart');
                break;
            case 'begin_checkout':
                settings_fields('modogtmwc_begin_checkout_group');
                do_settings_sections('modogtmwc-settings-begin-checkout');
                break;
            case 'add_payment_info':
                settings_fields('modogtmwc_add_payment_info_group');
                do_settings_sections('modogtmwc-settings-add-payment-info');
                break;
            case 'add_shipping_info':
                settings_fields('modogtmwc_add_shipping_info_group');
                do_settings_sections('modogtmwc-settings-add-shipping-info');
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
