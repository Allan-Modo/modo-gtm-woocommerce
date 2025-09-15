# Modo GTM WooCommerce — Guide de refactorisation MVC

Ce document explique tous les changements apportés pour migrer le plugin vers une structure MVC (Model–View–Controller), comment le code s’organise désormais, et ce qu’il faut savoir pour maintenir et étendre les fonctionnalités.

## Objectifs

- Isoler proprement la logique par « responsabilité » (évènements, settings, assets).
- Éviter les doublons d’accrochage de hooks WordPress/WooCommerce.
- Factoriser la construction des données eCommerce (produits, catégories, variantes, remises).
- Faciliter l’extension (ajouter de nouveaux évènements ou pages d’admin sans tout mélanger).

## Architecture

- Controllers: logique métier orchestrant les hooks et le rendu
  - `app/Controllers/Events/*Controller.php` — un contrôleur par évènement WooCommerce
  - `app/Controllers/Admin/SettingsController.php` — page et sections de réglages (Settings API)
  - `app/Controllers/Frontend/AssetsController.php` — enqueue des scripts front
- Models: services de données (aucun accès au rendu)
  - `app/Models/EventDataBuilder.php` — construit les objets `ecommerce.items[*]`
- Views: rendu des scripts `dataLayer.push`
  - `app/Views/scripts/push-event.php` — template générique d’injection dans `wp_footer`
  - `app/Views/admin/settings-page.php` — rendu de la page de paramètres (onglets + sections)
- Core / Bootstrap
  - `app/Core/Autoloader.php` — autoload simple pour le namespace `ModoGtmWc\\`
  - `app/Plugin.php` — point d’entrée: enregistre tous les contrôleurs au bon moment
  - `modo-gtm-woocommerce.php` — bootstrap minimal: charge l’autoloader et `Plugin::init()`

## Circuit de chargement

`modo-gtm-woocommerce.php`

```php
// Minimal autoloader bootstrap for MVC classes
require_once MODOGTMWC_PATH . 'app/Core/Autoloader.php';
\ModoGtmWc\Core\Autoloader::register();

// Boot the new MVC plugin scaffold
require_once MODOGTMWC_PATH . 'app/Plugin.php';
\ModoGtmWc\Plugin::init();
```

`app/Plugin.php`

```php
class Plugin {
    public static function init(): void {
        Autoloader::register();
        add_action('plugins_loaded', [__CLASS__, 'register_hooks']);
    }

    public static function register_hooks(): void {
        if (class_exists('WooCommerce')) {
            (new ViewItemController())->register();
            (new AddToCartController())->register();
            (new RemoveFromCartController())->register();
            (new PurchaseController())->register();
            (new ViewItemListController())->register();
        }
        if (is_admin()) { (new SettingsController())->register(); }
        (new AssetsController())->register();
    }
}
```

## Détails par évènement

### View Item

- Hook: `woocommerce_after_single_product`
- Contrôleur: `app/Controllers/Events/ViewItemController.php`
- Rendu: injecté en footer via `push-event.php`
- Points clefs:
  - `value = prix du produit`, `currency` via `get_woocommerce_currency()`
  - Variantes lues depuis `$_GET['attribute_*']` avec fallback sur attributs par défaut
  - Jointure des variantes: `","` (sans espace)

Extrait:

```php
$event_data['ecommerce'] = [
  'currency' => get_woocommerce_currency(),
  'value'    => (float) $product->get_price(),
  'items'    => [$builder->buildItem($product, 1, ['variant_join' => ','])],
];
```

### Add To Cart

- Hooks: `woocommerce_add_to_cart` et filtre `woocommerce_loop_add_to_cart_link`
- Contrôleur: `app/Controllers/Events/AddToCartController.php`
- Rendu: injection en footer (pour non‑AJAX) + `data-event_data` dans les boutons (AJAX)
- Points clefs:
  - Non‑AJAX: valeur = `unit_price * quantity`
  - AJAX: `assets/front/js/front-script.js` lit `data-event_data` et pousse l’évènement
  - Variantes: valeurs brutes du hook `$variation` quand dispo; jointure `","`

Extrait:

```php
$item = $builder->buildItem($product, (int) $quantity, [
  'variation_values' => is_array($variation) ? array_values($variation) : [],
  'variant_join'     => ',',
]);
```

### Remove From Cart

- Filtre: `woocommerce_cart_item_remove_link`
- Contrôleur: `app/Controllers/Events/RemoveFromCartController.php`
- Rendu: `data-event_data` ajouté au lien « remove », poussé côté JS au clic
- Points clefs:
  - `value = line_total` de la ligne panier, `price` de l’item = `line_total`
  - Coupon du panier ajouté si présent
  - Variantes: reconstruites depuis `get_variation_attributes()`

Extrait:

```php
$event_data['ecommerce'] = [
  'currency' => get_woocommerce_currency(),
  'value'    => (float) $cart_item['line_total'],
  'items'    => [ array_merge($item, ['price' => (float) $cart_item['line_total']]) ],
];
```

### Purchase

- Hook: `woocommerce_thankyou`
- Contrôleur: `app/Controllers/Events/PurchaseController.php`
- Points clefs:
  - `transaction_id`, `currency`, `value = total commande`
  - Items: `price = line_total / quantity`, `index` incrémental
  - Coupon: `get_coupon_codes()`; remise calculée par produit
  - Variantes: `get_variation_attributes()`

Extrait correction catégories (bug d’origine corrigé):

```php
foreach ($cat_levels as $index => $cat_name) {
  $key = $index === 0 ? 'item_category' : 'item_category' . ($index + 1);
  $product_data[$key] = $cat_name; // (et non $item)
}
```

### View Item List

- Hooks: `the_post` (collecte), `wp_footer` (rendu)
- Contrôleur: `app/Controllers/Events/ViewItemListController.php`
- Points clefs:
  - Pas de variantes (suppression explicite)
  - `item_list_id/name` selon contexte (shop/catégorie/recherche/autre)
  - `index` pour l’ordre d’affichage

Extrait:

```php
$data = $builder->buildItem($product, 1, ['suppress_variants' => true]);
$data['index'] = self::$position_index++;
```

## Construction des items: `EventDataBuilder`

Fichier: `app/Models/EventDataBuilder.php`

Signature:

```php
public function buildItem(\WC_Product $product, int $quantity = 1, array $opts = [])
// Options supportées:
// - suppress_variants: bool (ne pas produire item_variant)
// - variation_values: array (valeurs brutes des attributs quand le hook les fournit)
// - variant_join: string (séparateur, ", " par défaut)
```

- Marque: terme `product_brand` sur le parent si variation
- Catégories: mode « smart » (chemin le plus profond) sinon niveau 1 uniquement
- Remises: variation > variable > simple
- Variantes: selon priorité
  1) `variation_values` passées par le contrôleur (ex: Add to cart)
  2) Variation: `get_variation_attributes()`
  3) Variable: lecture de `$_GET['attribute_*']` sinon `get_default_attributes()`

## Vues (rendu des scripts)

Fichier: `app/Views/scripts/push-event.php`

```php
<script>
window.dataLayer = window.dataLayer || [];
window.dataLayer.push({
  event: "<?php echo esc_js($event); ?>",
  <?php echo substr(wp_json_encode($data), 1, -1); ?>
});
</script>
```

Note chemin: tous les contrôleurs pointent vers `dirname(__DIR__, 2) . '/Views/scripts/push-event.php'` (soit `<plugin>/app/Views/...`).

## Settings (Admin)

- Contrôleur: `app/Controllers/Admin/SettingsController.php`
- Vue: `app/Views/admin/settings-page.php`
- Identiques aux réglages d’origine: mêmes `option_name`, groupes, sections, et champs.
- Scripts admin conservés: `assets/admin/js/admin-script.js`.

Extrait (enregistrement d’une section) :

```php
add_settings_section(
  'modogtmwc_purchase_section',
  'Paramètres "Commande effectuée"',
  null,
  'modogtmwc-settings-purchase'
);
```

## Frontend assets

- Contrôleur: `app/Controllers/Frontend/AssetsController.php`
- Enqueue: `assets/front/js/front-script.js`
- Gère les évènements AJAX (`added_to_cart`) et le clic `remove` via `data-event_data`.

## Nettoyage des hooks legacy

Dans `modo-gtm-woocommerce.php`, les `require_once` et `add_action` des anciens fichiers `includes/events/*` et `includes/settings/*` ont été retirés. Les contrôleurs MVC les remplacent via `app/Plugin.php`.

## Étendre: ajouter un évènement

1. Créer `app/Controllers/Events/MyEventController.php`
2. Dans `register()`, accrocher les hooks Woo nécessaires
3. Construire `$event_data` et appeler la vue générique `push-event.php`
4. Enregistrer le contrôleur dans `app/Plugin.php` (dans `register_hooks()`)

## Débogage rapide

- Activer `WP_DEBUG_LOG` et consulter `wp-content/debug.log`
- Vérifier le chemin template: `file_exists(<plugin>/app/Views/scripts/push-event.php)`
- Inspecter le `dataLayer` dans la console: `window.dataLayer`
- Valider la présence de `data-event_data` sur les boutons (liste/loop) et liens remove

## Check‑list de tests

- Page produit: `view_item` poussé avec `items[0]` correct
- Boutique/catégorie/recherche: `view_item_list` avec `item_list_id/name` et `index`
- Ajout panier AJAX et non‑AJAX: `add_to_cart` avec `value` attendu
- Suppression panier: `remove_from_cart` avec `line_total` et coupon si présent
- Page « thank you »: `purchase` avec `transaction_id`, `value`, et items

## Export en PDF

- Ouvrir `docs/mvc-refactor-guide.html` dans un navigateur et « Imprimer > Enregistrer en PDF ».
- Ou convertir ce Markdown (`docs/mvc-refactor-guide.md`) vers PDF via vos outils (ex: Pandoc) si disponibles.

---

# Lecture détaillée du code

Cette section reprend, fichier par fichier, le rôle exact, les hooks utilisés, et les points importants de la logique. Des extraits sont inclus pour illustrer où ajuster le code si besoin.

## Bootstrap et Autoloader

Fichier: `modo-gtm-woocommerce.php`

```php
require_once MODOGTMWC_PATH . 'app/Core/Autoloader.php';
\ModoGtmWc\Core\Autoloader::register();
require_once MODOGTMWC_PATH . 'app/Plugin.php';
\ModoGtmWc\Plugin::init();
```

- Charge un autoloader minimal puis le coeur du plugin MVC.
- Ne contient plus les anciens `require_once` ni `add_action` d’évènements ou de settings (ils sont migrés dans `app/Plugin.php`).

Fichier: `app/Core/Autoloader.php`

```php
$prefix = 'ModoGtmWc\\';
$base_dir = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR; // app/
$relative_class = substr($class, $len);
$file = $base_dir . str_replace('\\', DIRECTORY_SEPARATOR, $relative_class) . '.php';
```

- Convertit un nom de classe du namespace `ModoGtmWc` en chemin sous `app/`.
- Si le fichier existe, il est inclus automatiquement.

Fichier: `app/Plugin.php`

```php
add_action('plugins_loaded', [__CLASS__, 'register_hooks']);
if (class_exists('WooCommerce')) { /* enregistre tous les contrôleurs d'évènements */ }
if (is_admin()) { (new SettingsController())->register(); }
(new AssetsController())->register();
```

- Sépare l’enregistrement des hooks: évènements (si WooCommerce), admin (si back‑office), assets (toujours côté front).

## Modèle: EventDataBuilder

Fichier: `app/Models/EventDataBuilder.php`

```php
public function buildItem(\WC_Product $product, int $quantity = 1, array $opts = [])
```

Options prises en charge:
- `suppress_variants`: ne pas générer `item_variant` (utile pour les listes).
- `variation_values`: valeurs brutes fournies par un hook, priorité la plus haute (ex: add_to_cart).
- `variant_join`: séparateur des valeurs ("," ou ", ").

Construction de l’item:
- ID/Name/Price/Quantity depuis le produit.
- Brand: `product_brand` sur le parent si variation.
- Catégories: mode « smart » (plus profonde → remonte) sinon niveau 1.
- Remise: priorité variation > variable > simple; ajout si > 0.
- Variantes: priorité `variation_values` > variation `get_variation_attributes()` > variable (GET `attribute_*` → défauts).

Extrait (variantes via hook add_to_cart):

```php
$item = $builder->buildItem($product, (int) $quantity, [
  'variation_values' => is_array($variation) ? array_values($variation) : [],
  'variant_join'     => ',',
]);
```

## Vues: Injection dataLayer

Fichier: `app/Views/scripts/push-event.php`

```php
<script>
window.dataLayer = window.dataLayer || [];
window.dataLayer.push({
  event: "<?php echo esc_js($event); ?>",
  <?php echo substr(wp_json_encode($data), 1, -1); ?>
});
</script>
```

- Tous les contrôleurs incluent ce template en footer pour pousser l’évènement.
- Chemin correct: `dirname(__DIR__, 2) . '/Views/scripts/push-event.php'`.

## Contrôleurs d’évènements

### ViewItemController

Fichier: `app/Controllers/Events/ViewItemController.php`

- Hook: `woocommerce_after_single_product`.
- Vérifie l’option `event_view_item`.
- Si `event_view_item_products_details` actif: construit `ecommerce` avec `value = prix`, `items[0]` via `buildItem(..., ['variant_join' => ','])`.
- Rend `push-event.php` avec `event = 'view_item'`.

### AddToCartController

Fichier: `app/Controllers/Events/AddToCartController.php`

- Hooks:
  - `woocommerce_add_to_cart`: non‑AJAX; injection en footer.
  - `woocommerce_loop_add_to_cart_link`: ajoute `data-event_data` pour le JS AJAX.
- Non‑AJAX:
  - `ecommerce.value = unit_price * quantity`.
  - Variantes depuis `$variation` du hook si dispo (join `,`).
- AJAX:
  - Sérialise `ecommerce` en JSON dans l’attribut `data-event_data` du bouton.
  - `assets/front/js/front-script.js` écoute `added_to_cart` et pousse l’event.

### RemoveFromCartController

Fichier: `app/Controllers/Events/RemoveFromCartController.php`

- Filtre: `woocommerce_cart_item_remove_link`.
- Construit `ecommerce` si `include_data` actif et l’injecte en `data-event_data`.
- Particularités:
  - `item.price = line_total`, `ecommerce.value = line_total`.
  - Ajoute `coupon` si le panier en contient.
  - Variantes reconstruites via `get_variation_attributes()` si variation.
- Le JS front pousse `remove_from_cart` au clic.

### PurchaseController

Fichier: `app/Controllers/Events/PurchaseController.php`

- Hook: `woocommerce_thankyou`.
- `ecommerce = { transaction_id, currency, value }`.
- Si `products_details` actif: parcourt les lignes de commande, calcule `price = line_total / quantity`, définit `index`, set `coupon` si présent et une `discount` cohérente.
- Catégories: correction d’un bug (mapping sur `$product_data` et non `$item`).

### ViewItemListController

Fichier: `app/Controllers/Events/ViewItemListController.php`

- Collecte sur `the_post`, rendu unique en `wp_footer`.
- Ignore le produit courant si on est sur la page produit.
- `suppress_variants => true`, définit `index` incrémental.
- Contexte liste: `item_list_id/name` selon Shop/Catégorie/Recherche/Autre.

## Admin et Frontend

### SettingsController

Fichier: `app/Controllers/Admin/SettingsController.php`

- Enregistre la page d’options (onglets), les groupes d’options, sections, et champs.
- Conserve les `option_name` d’origine; la compatibilité est totale.
- `render_settings_page()` inclut `app/Views/admin/settings-page.php` et délègue à la Settings API.

### AssetsController

Fichier: `app/Controllers/Frontend/AssetsController.php`

- Enqueue `assets/front/js/front-script.js`.
- Le JS gère add_to_cart AJAX et remove_from_cart via les `data-event_data`.

## Dépannage fréquent (render_view)

- Symptôme: rien ne s’injecte en footer alors que le hook est bien appelé.
- Cause probable: chemin du template incorrect.
- Solution: vérifier que tous les contrôleurs utilisent `dirname(__DIR__, 2) . '/Views/scripts/push-event.php'`.

