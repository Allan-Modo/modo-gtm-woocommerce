// Script admin : gère l'affichage conditionnel des sous‑options
// en fonction des cases à cocher parentes (dépendances simples/multiples).
jQuery(document).ready(function ($) {

    function updateVisibility() {
        $(".sub-option").each(function () {
            const $tr = $(this);
            const $input = $tr.find("input[type=checkbox]").first();
            const depends = $input.data("depends");
            const dependsMulti = $input.data("depends-multi");

            let show = true;

            if (dependsMulti) {
                const deps = dependsMulti.split(",");
                show = deps.every(id => $("#" + id).is(":checked"));
            } else if (depends) {
                show = $("#" + depends).is(":checked");
            }

            $tr.toggle(show);
        });
    }

    // État initial : calculer la visibilité au chargement
    updateVisibility();

    // Recalcul à chaque changement d'une case à cocher
    $("input[type=checkbox]").on("change", function () {
        updateVisibility();
    });
});
