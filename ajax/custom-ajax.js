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
                    $('.monthly-fee-subtotal').html('Monthly Fees Subtotal: ' + response.data.monthly_fee_subtotal);
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
                    $('.monthly-fee-tax').html('Total Monthly Fees Tax: ' + response.data.monthly_fee_tax);
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
                    $('.monthly-fee-total').html('Monthly Fee Total: ' + response.data.monthly_fee_total);
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

    // Listen for the Xootix side cart AJAX request completion
    $(document).ajaxComplete(function (event, xhr, settings) {
        if (settings.url.indexOf('wc-ajax=xoo_wsc_update_item_quantity') !== -1) {
            console.log("Side cart updated - product removed or quantity changed.");
            updateMonthlyFeeSubtotal();
            updateMonthlyFeeTax();
            updateMonthlyFeeTotal();
        }
    });
});




