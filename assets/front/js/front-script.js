jQuery(function($){
    $(document.body).on('added_to_cart', function(event, fragments, cart_hash, $button){
        let eventData = $button.data("event_data");

        if (eventData) {
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({
                event: "add_to_cart",
                ecommerce: eventData
            });
        } else {
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({
                event: "add_to_cart"
            });
        }
    });

    $(document).on('click', '.remove[data-event_data]', function(){
        let eventData = $(this).data('event_data');

        if (eventData) {
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({
                event: "remove_from_cart",
                ecommerce: eventData
            });
        } else {
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({
                event: "remove_from_cart"
            });
        }
    });

    // $('form.variations_form').on('show_variation', function(event, variation){
    //     if (!window.modoProductData || !modoProductData.variations) return;

    //     let item = modoProductData.variations[variation.variation_id];
    //     if (!item) return;

    //     window.dataLayer = window.dataLayer || [];
    //     if(modoProductData.event_view_item_products_details){
    //         window.dataLayer.push({
    //             event: "view_item",
    //             ecommerce: {
    //                 currency: modoProductData.currency,
    //                 value: item.price,
    //                 items: [item]
    //             }
    //         });
    //     } else {
    //         window.dataLayer.push({
    //             event: "view_item",
    //         });
    //     }
    // });
});
