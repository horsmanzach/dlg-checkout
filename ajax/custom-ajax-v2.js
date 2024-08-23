jQuery(document).ready(function ($) {
    console.log("Custom AJAX script loaded.");

    function updateMonthlyFeeSubtotal() {
        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'calculate_monthly_fee_subtotal',
                nonce: ajax_object.nonce_subtotal
            },
            success: function (response) {
                if (response.success) { 
                    console.log("AJAX success:", response);
                    $('.total-monthly-fees-subtotal').replaceWith(response.data.html);
                    $('.individual-monthly-fee').remove();
                    $(response.data.individual_fees_html).insertBefore('.total-monthly-fees-subtotal');
                } else {
                    console.log("AJAX error:", response);
                }
            },
            error: function (xhr, status, error) {
                console.log("AJAX request failed:", status, error);
            }
        });
    }

    function updateMonthlyFeeTax() {
        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'calculate_monthly_fee_tax',
                nonce: ajax_object.nonce_tax
            },
            success: function (response) {
                if (response.success) {
                    console.log("AJAX success:", response);
                    $('.total-monthly-fees-tax').replaceWith(response.data.html);
                } else {
                    console.log("AJAX error:", response);
                }
            },
            error: function (xhr, status, error) {
                console.log("AJAX request failed:", status, error);
            }
        });
    }

    function updateMonthlyFeeTotal() {
        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'calculate_monthly_fee_total',
                nonce: ajax_object.nonce_total
            },
            success: function (response) {
                if (response.success) {
                    console.log("AJAX success:", response);
                    $('.total-monthly-fees').replaceWith(response.data.html);
                } else {
                    console.log("AJAX error:", response);
                }
            },
            error: function (xhr, status, error) {
                console.log("AJAX request failed:", status, error);
            }
        });
    }

    // Listen for the WooCommerce 'added_to_cart' event
    $('body').on('added_to_cart', function () {
        console.log("Product added to cart.");
        updateMonthlyFeeSubtotal();
        updateMonthlyFeeTax();
        updateMonthlyFeeTotal();
    });

    // Listen for the Xootix side cart AJAX request 
    $(document).ajaxComplete(function (event, xhr, settings) {
        if (settings.url.indexOf('wc-ajax=xoo_wsc_update_item_quantity') !== -1) {
            console.log("Side cart updated - product removed or quantity changed.");
            updateMonthlyFeeSubtotal();
            updateMonthlyFeeTax();
            updateMonthlyFeeTotal();
        }
    });
});




