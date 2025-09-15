<?php
// Vue partagée utilisée par tous les contrôleurs pour pousser un évènement dataLayer dans le footer.
if (!defined('ABSPATH')) exit;
?>
<script>
window.dataLayer = window.dataLayer || [];
window.dataLayer.push({
    event: "<?php echo esc_js($event); ?>",
    <?php echo substr(wp_json_encode($data), 1, -1); ?>
});
</script>
