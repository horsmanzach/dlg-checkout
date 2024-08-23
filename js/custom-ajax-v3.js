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
                    $('.monthly-fee-tax').html(response.data.monthly_fee_tax);
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

 // Unified update function to handle updates
    function updateAllMonthlyFees() {
        updateMonthlyFeeSubtotal();
        updateMonthlyFeeTax();
        updateMonthlyFeeTotal();
    }

    // Listen for WooCommerce events on the cart and checkout pages
    $(document.body).on('updated_checkout country_to_state_changed updated_shipping_method', function () {
        console.log("Checkout or shipping method updated.");
        updateAllMonthlyFees();
    });

    // Listen for the WooCommerce 'added_to_cart' and other relevant events
    $('body').on('added_to_cart removed_from_cart', function () {
        console.log("Cart updated - product added or removed.");
        updateAllMonthlyFees();
    });

    // Listen for AJAX completions that indicate changes in the cart contents or quantities
    $(document).ajaxComplete(function (event, xhr, settings) {
        if (settings.url.indexOf('wc-ajax=xoo_wsc_update_item_quantity') !== -1 || settings.url.indexOf('wc-ajax=update_shipping_method') !== -1 || settings.url.indexOf('wc-ajax=update_order_review') !== -1) {
            console.log("Cart or checkout updated - AJAX request completed.");
            updateAllMonthlyFees();
        }
    });

    // Optional: Listen for changes in address fields on the checkout page
    $('#billing_country, #billing_state, #billing_postcode, #shipping_country, #shipping_state, #shipping_postcode').change(function() {
        console.log("Address changed.");
        updateAllMonthlyFees();
    });
});









