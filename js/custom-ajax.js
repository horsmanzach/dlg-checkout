/* Enhanced custom-ajax.js with preloader functionality for upfront fees */

jQuery(document).ready(function ($) {
    console.log("Custom AJAX script loaded.");

    // NEW: Preloader functions
    function showPreloader(targetElement) {
        // Create spinner HTML if it doesn't exist
        if (!targetElement.find('.monthly-fee-preloader').length) {
            const spinnerHtml = '<div class="monthly-fee-preloader">' +
                '<div class="monthly-fee-spinner"></div>' +
                '<span class="preloader-text">Updating...</span>' +
                '</div>';
            targetElement.append(spinnerHtml);
        }

        // Show the preloader and hide the content
        targetElement.find('.monthly-fee-preloader').show();
        targetElement.find('.monthly-fee-content, .upfront-fee-content').hide();
    }

    function hidePreloader(targetElement) {
        targetElement.find('.monthly-fee-preloader').hide();
        targetElement.find('.monthly-fee-content, .upfront-fee-content').show();
    }

    // ENHANCED: Your existing updateMonthlyFeeSubtotal function with preloader
    function updateMonthlyFeeSubtotal() {
        // NEW: Add preloader support
        const subtotalContainer = $('.monthly-fee-subtotal-container, [data-shortcode="monthly_fee_subtotal"], .total-monthly-fees-subtotal').closest('div');
        if (subtotalContainer.length) {
            showPreloader(subtotalContainer);
        }

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
                    // NEW: Hide preloader
                    if (subtotalContainer.length) {
                        hidePreloader(subtotalContainer);
                    }
                } else {
                    console.log("AJAX error:", response);
                    // NEW: Hide preloader on error
                    if (subtotalContainer.length) {
                        hidePreloader(subtotalContainer);
                    }
                }
            },
            error: function (xhr, status, error) {
                console.log("AJAX request failed:", status, error);
                // NEW: Hide preloader on error
                if (subtotalContainer.length) {
                    hidePreloader(subtotalContainer);
                }
            }
        });
    }

    // ENHANCED: Your existing updateMonthlyFeeTax function with preloader
    function updateMonthlyFeeTax() {
        // NEW: Add preloader support
        const taxContainer = $('.monthly-fee-tax-container, [data-shortcode="monthly_fee_tax"], .monthly-fee-tax').closest('div');
        if (taxContainer.length) {
            showPreloader(taxContainer);
        }

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
                    // NEW: Hide preloader
                    if (taxContainer.length) {
                        hidePreloader(taxContainer);
                    }
                } else {
                    console.log("AJAX error:", response);
                    // NEW: Hide preloader on error
                    if (taxContainer.length) {
                        hidePreloader(taxContainer);
                    }
                }
            },
            error: function (xhr, status, error) {
                console.log("AJAX request failed:", status, error);
                // NEW: Hide preloader on error
                if (taxContainer.length) {
                    hidePreloader(taxContainer);
                }
            }
        });
    }

    // ENHANCED: Your existing updateMonthlyFeeTotal function with preloader
    function updateMonthlyFeeTotal() {
        // NEW: Add preloader support
        const totalContainer = $('.monthly-fee-total-container, [data-shortcode="monthly_fee_total"], .total-monthly-fees').closest('div');
        if (totalContainer.length) {
            showPreloader(totalContainer);
        }

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
                    // NEW: Hide preloader
                    if (totalContainer.length) {
                        hidePreloader(totalContainer);
                    }
                } else {
                    console.log("AJAX error:", response);
                    // NEW: Hide preloader on error
                    if (totalContainer.length) {
                        hidePreloader(totalContainer);
                    }
                }
            },
            error: function (xhr, status, error) {
                console.log("AJAX request failed:", status, error);
                // NEW: Hide preloader on error
                if (totalContainer.length) {
                    hidePreloader(totalContainer);
                }
            }
        });
    }

    // NEW: Enhanced function to update upfront fee total with preloader
    function updateUpfrontFeeTotal() {
        const upfrontContainer = $('.upfront-fee-total-container, [data-shortcode="upfront_fee_total"]');

        if (upfrontContainer.length) {
            console.log("Found upfront fee container, showing preloader");
            // Show preloader
            showPreloader(upfrontContainer);

            $.ajax({
                url: ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_upfront_fee_total',
                    nonce: ajax_object.modem_selection_nonce || ajax_object.nonce_total
                },
                success: function (response) {
                    if (response.success) {
                        console.log("Upfront fee total updated:", response);
                        // Update the content and hide preloader
                        upfrontContainer.find('.upfront-fee-content').html(response.data.total);
                        hidePreloader(upfrontContainer);
                    } else {
                        console.error('Upfront fee total update failed:', response.data);
                        hidePreloader(upfrontContainer);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX error updating upfront fee total:', error);
                    hidePreloader(upfrontContainer);
                }
            });
        } else {
            console.log("No upfront fee container found for preloader");
        }
    }

    // ENHANCED: Your existing updateSummaryForSelectedProduct function (keeping as is)
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
                }
                // ... rest of your existing error handling
            }
        });
    }

    // ENHANCED: Function to update all monthly fees with upfront fees
    function updateAllMonthlyFees() {
        updateMonthlyFeeSubtotal();
        updateMonthlyFeeTax();
        updateMonthlyFeeTotal();
        updateUpfrontFeeTotal(); // NEW: Add upfront fee update
    }

    // NEW: Enhanced function to update all fees including upfront
    function updateAllFees() {
        // Update monthly fees
        updateMonthlyFeeSubtotal();
        updateMonthlyFeeTax();
        updateMonthlyFeeTotal();

        // Update upfront fee with preloader
        updateUpfrontFeeTotal();
    }

    // NEW: Global functions that can be called from other scripts
    window.updateMonthlyFeesWithPreloader = updateAllMonthlyFees;
    window.updateFeesWithPreloader = updateAllFees;

    // Keep all your existing event listeners
    $(document.body).on('added_to_cart', function (event, fragments, cart_hash, $button) {
        console.log("Product added to cart via WooCommerce AJAX. Button details:");

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
        productIds.forEach(function (productId) {
            console.log("Product ID being processed:", productId);
            updateSummaryForSelectedProduct(productId);
        });

        // Add the main composite product if it's not already in the summary
        let mainProductId = $button.closest('.single-product').data('product_id'); // Adjust this selector if needed
        if (mainProductId && !productIds.includes(mainProductId)) {
            console.log("Adding main composite product ID:", mainProductId);
            updateSummaryForSelectedProduct(mainProductId);
        }

        // Finally, update all fees after processing all products
        updateAllMonthlyFees();
    });

    // Listen for WooCommerce events on the cart and checkout pages
    $(document.body).on('updated_checkout country_to_state_changed updated_shipping_method', function () {
        console.log("Checkout or shipping method updated.");
        updateAllMonthlyFees();
    });

    // Listen for AJAX completions that indicate changes in the cart contents or quantities
    $(document).ajaxComplete(function (event, xhr, settings) {
        if (settings.url.indexOf('wc-ajax=xoo_wsc_update_item_quantity') !== -1 ||
            settings.url.indexOf('wc-ajax=update_shipping_method') !== -1 ||
            settings.url.indexOf('wc-ajax=update_order_review') !== -1) {
            console.log("Cart or checkout updated - AJAX request completed.");
            updateAllMonthlyFees();
        }
    });

    // Optional: Listen for changes in address fields on the checkout page
    $('#billing_country, #billing_state, #billing_postcode, #shipping_country, #shipping_state, #shipping_postcode').change(function () {
        console.log("Address changed.");
        updateAllMonthlyFees();
    });

    // NEW: Additional event listeners for upfront fee updates
    $(document).on('click', '.add-to-cart-button, .remove-from-cart-button', function () {
        // Small delay to allow cart to update
        setTimeout(function () {
            updateUpfrontFeeTotal();
            updateAllMonthlyFees();
        }, 100);
    });

    // NEW: Listen for product selection changes
    $(document).on('change', 'input[name="product_selection"], select[name="product_selection"]', function () {
        updateUpfrontFeeTotal();
        updateAllMonthlyFees();
    });

    // NEW: Listen for custom cart update events (if you trigger them elsewhere)
    $(document).on('cart_updated', function () {
        updateAllMonthlyFees();
    });

    // NEW: Manual trigger for debugging - you can call this in browser console
    window.debugUpdateFees = function () {
        console.log("Manually triggering fee updates...");
        updateAllFees();
    };

});