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
                   console.log("Monthly Fee Subtotal updated:", response);
                    $('.total-monthly-fees-subtotal').replaceWith(response.data.html);
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
                    console.log("Monthly Fee Tax updated:", response);
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
                    console.log("Total Monthly Fees updated:", response);
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


function updateSummaryForSelectedProduct(productId) { 
    // Check if the product is already in the summary table
    var productExists = false;
    
    $('.monthly_summary_table .individual-monthly-fee').each(function () {
        let currentProductId = $(this).data('product-id');

        if (currentProductId == productId) {
            productExists = true;
            console.log("Product already exists in the summary table.");
            return false; // Exit the loop if product is found
        }
    });   

    // If product is present, don't add again
    if (productExists) {
        return;
    }

    // If product is not present, proceed with AJAX request
    $.ajax({
        url: ajax_object.ajax_url,
        type: 'POST',
        data: {
            action: 'update_selected_product_summary',
            product_id: productId,
            nonce: ajax_object.update_selected_product_summary_nonce
        },
        success: function (response) {
            if (response.success) {
                console.log("Product summary updated:", response);

                // Remove 'No products' placeholder if present
                $('.monthly_summary_table .individual-monthly-fee').each(function () {
                    if ($(this).find('th').text() === 'No products') {
                        $(this).remove();
                    }
                });

                // Remove any existing product in the same category before adding the new one,
                // but ensure the main composite product is not removed
                $('.monthly_summary_table .individual-monthly-fee').each(function () {
                    let $currentRow = $(this);
                    let category = $currentRow.data('category');
                    let currentProductId = $currentRow.data('product-id');

                    console.log('Checking row with category:', category, 'and product ID:', currentProductId);

                    // The logic here is essential to ensure that the main composite product isn't removed
                    if (category === response.data.category_slug && currentProductId !== productId && currentProductId !== response.data.main_product_id) {
                        // Remove the previous product from the same category
                        $currentRow.remove();
                        console.log("Previous product from the same category was removed");
                    }
                });

                // Add the new product to the summary table with the correct data attributes
                let newRow = $(response.data.html);
                newRow.attr('data-product-id', productId); // Set the product ID as a data attribute
                newRow.attr('data-category', response.data.category_slug); // Set the category as a data attribute
                $('.total-monthly-fees-subtotal').before(newRow);

                // Trigger updates for subtotal, tax, and total
                updateAllMonthlyFees(); // This line is crucial to ensuring that the summary is updated.
            } else {
                console.log("Failed to update summary:", response);
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

    // Listen for changes in the selected products
    
    $(document).ready(function ($) {
    $('.component_option_thumbnail').on('click', function () {
    console.log('Component option thumbnail clicked');
    var $selectedProduct = $(this);

    // Adding a short delay to allow class application
    setTimeout(function () {
        if ($selectedProduct.hasClass('selected')) {
            console.log('Selected class is present');
            var productId = $selectedProduct.data('val');
            console.log('Product ID:', productId);
            
            if (productId) {
                console.log('Product ID is valid, triggering updateSummaryForSelectedProduct');
                updateSummaryForSelectedProduct(productId);
            } else {
                console.log('Product ID is not valid');
            }
        } else {
            console.log('Selected class is NOT present');
        }
    }, 100); // Delay of 100ms
});
    });


 // Listen for the WooCommerce 'added_to_cart' and other relevant events
$('body').on('added_to_cart removed_from_cart', function (event, fragments, cart_hash, $button) {
    console.log("Cart updated - product added or removed.");

    // Try to extract the product IDs
    let productIds = $button.data('product_ids'); // This should be an array of product IDs

    if (!Array.isArray(productIds)) {
        productIds = [$button.data('product_id')]; // Fallback if only one product ID is available
    }

    // If productIds is still undefined, log an error
    if (!productIds || productIds.length === 0) {
        console.error("No valid product IDs found in the added_to_cart event.");
        return;
    }

    // Loop through each product being added
    productIds.forEach(function(productId) {
        console.log("Product ID being processed:", productId);

        // Update the summary for each selected product
        updateSummaryForSelectedProduct(productId);
    });

    // Add the main composite product if it’s not already in the summary
    let mainProductId = $button.closest('.single-product').data('product_id'); // Adjust this selector if needed
    if (mainProductId && !productIds.includes(mainProductId)) {
        console.log("Adding main composite product ID:", mainProductId);
        updateSummaryForSelectedProduct(mainProductId);
    }

    // Finally, update all monthly fees after processing all products
    updateAllMonthlyFees();
});



    
    // Listen for WooCommerce events on the cart and checkout pages
    $(document.body).on('updated_checkout country_to_state_changed updated_shipping_method', function () {
        console.log("Checkout or shipping method updated.");
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