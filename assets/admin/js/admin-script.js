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

    // État initial
    updateVisibility();

    // Recalcul à chaque changement
    $("input[type=checkbox]").on("change", function () {
        updateVisibility();
    });
});
