/**
 * Modem, Phone and TV Selection JavaScript
 * Save this as /js/card-selection.js in your theme directory
 */
jQuery(document).ready(function ($) {
    // Store the product IDs for each row
    const modemRows = {
        'modem-0': 265104,
        'modem-1': 265105,
        'modem-2': 265107,
        'modem-3': 265108
    };

    const phoneRows = {
        'phone-0': 304,
        'phone-1': 305
    };

    const tvRows = {
        'tv-0': 264269,
        'tv-1': 264270
    };

    // Get current page product ID (for internet plan)
    const currentPageProductId = $('input[name="add-to-cart"]').val() || 0;

    // Check if any product is already in cart and highlight that row
    checkCartAndHighlight();

    // Make modem rows clickable
    $('.modem-0, .modem-1, .modem-2, .modem-3').on('click', function () {
        const $this = $(this);
        const rowClass = $this.attr('class').split(' ').find(c => c.startsWith('modem-'));

        if (!rowClass || !modemRows[rowClass]) {
            console.error('Product ID not found for this row');
            return;
        }

        // Check if this row is already selected
        if ($this.hasClass('modem-row-selected')) {
            // Deselect this row and remove from cart
            $this.removeClass('modem-row-selected');
            removeFromCart(modemRows[rowClass]);
        } else {
            // Remove selection from all modem rows
            $('.modem-0, .modem-1, .modem-2, .modem-3').removeClass('modem-row-selected');

            // Add selection to this row
            $this.addClass('modem-row-selected');

            // Add to cart via AJAX
            addToCart(modemRows[rowClass], false, 'modem');
        }
    });

    // Make phone rows clickable
    $('.phone-0, .phone-1').on('click', function () {
        const $this = $(this);
        const rowClass = $this.attr('class').split(' ').find(c => c.startsWith('phone-'));

        if (!rowClass || !phoneRows[rowClass]) {
            console.error('Product ID not found for this row');
            return;
        }

        // Check if this row is already selected
        if ($this.hasClass('phone-row-selected')) {
            // Deselect this row and remove from cart
            $this.removeClass('phone-row-selected');
            removeFromCart(phoneRows[rowClass]);
        } else {
            // Remove selection from all phone rows
            $('.phone-0, .phone-1').removeClass('phone-row-selected');

            // Add selection to this row
            $this.addClass('phone-row-selected');

            // Add to cart via AJAX
            addToCart(phoneRows[rowClass], false, 'phone');
        }
    });

    // Make TV rows clickable
    $('.tv-0, .tv-1').on('click', function () {
        const $this = $(this);
        const rowClass = $this.attr('class').split(' ').find(c => c.startsWith('tv-'));

        if (!rowClass || !tvRows[rowClass]) {
            console.error('Product ID not found for this row');
            return;
        }

        // Check if this row is already selected
        if ($this.hasClass('tv-row-selected')) {
            // Deselect this row and remove from cart
            $this.removeClass('tv-row-selected');
            removeFromCart(tvRows[rowClass]);
        } else {
            // Remove selection from all TV rows
            $('.tv-0, .tv-1').removeClass('tv-row-selected');

            // Add selection to this row
            $this.addClass('tv-row-selected');

            // Add to cart via AJAX
            addToCart(tvRows[rowClass], false, 'tv');
        }
    });

    // Function to add product to cart
    function addToCart(productId, isInternetPlan = false, productType = '') {
        $.ajax({
            type: 'POST',
            url: modem_selection_vars.ajax_url,
            data: {
                action: 'modem_add_to_cart',
                product_id: productId,
                is_internet_plan: isInternetPlan,
                product_type: productType,
                nonce: modem_selection_vars.nonce
            },
            success: function (response) {
                if (response.success) {
                    // Optionally show a success message
                    console.log('Product added to cart:', response.data.product_id);

                    // Update cart fragments/totals (WooCommerce specific)
                    $(document.body).trigger('wc_fragment_refresh');

                    // Update fee tables immediately after cart update
                    updateFeeTables();
                } else {
                    console.error('Error:', response.data.message);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
            }
        });
    }

    // Function to remove product from cart
    function removeFromCart(productId) {
        $.ajax({
            type: 'POST',
            url: modem_selection_vars.ajax_url,
            data: {
                action: 'modem_remove_from_cart',
                product_id: productId,
                nonce: modem_selection_vars.nonce
            },
            success: function (response) {
                if (response.success) {
                    console.log('Product removed from cart:', productId);

                    // Update cart fragments/totals
                    $(document.body).trigger('wc_fragment_refresh');

                    // Update fee tables immediately
                    updateFeeTables();
                } else {
                    console.error('Error:', response.data.message);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
            }
        });
    }

    // Function to check cart and highlight selected row
    function checkCartAndHighlight() {
        $.ajax({
            type: 'POST',
            url: modem_selection_vars.ajax_url,
            data: {
                action: 'get_cart_items',
                nonce: modem_selection_vars.nonce
            },
            success: function (response) {
                if (response.success && response.data.items.length > 0) {
                    const cartItems = response.data.items;

                    // Find which modem rows have products in cart and highlight them
                    for (const [rowClass, productId] of Object.entries(modemRows)) {
                        if (cartItems.includes(productId)) {
                            $(`.${rowClass}`).addClass('modem-row-selected');
                        }
                    }

                    // Find which phone rows have products in cart and highlight them
                    for (const [rowClass, productId] of Object.entries(phoneRows)) {
                        if (cartItems.includes(productId)) {
                            $(`.${rowClass}`).addClass('phone-row-selected');
                        }
                    }

                    // Find which TV rows have products in cart and highlight them
                    for (const [rowClass, productId] of Object.entries(tvRows)) {
                        if (cartItems.includes(productId)) {
                            $(`.${rowClass}`).addClass('tv-row-selected');
                        }
                    }
                }
            }
        });
    }

    // Function to update upfront fee total
    function updateUpfrontTotal() {
        $.ajax({
            type: "POST",
            url: modem_selection_vars.ajax_url,
            data: {
                action: "get_upfront_fee_total",
                nonce: modem_selection_vars.nonce
            },
            success: function (response) {
                if (response.success) {
                    $(".upfront-fee-total-container").html(response.data.total);
                }
            }
        });
    }

    // Function to update fee tables
    function updateFeeTables() {
        $.ajax({
            type: "POST",
            url: modem_selection_vars.ajax_url,
            data: {
                action: "update_fee_tables",
                current_product_id: currentPageProductId,
                nonce: modem_selection_vars.nonce
            },
            success: function (response) {
                if (response.success) {
                    $(".upfront-fee-summary-container").html(response.data.upfront_table);
                    $(".monthly-fee-summary-container").html(response.data.monthly_table);

                    // Update upfront fee total if that function exists
                    if (typeof updateUpfrontTotal === 'function') {
                        updateUpfrontTotal();
                    }
                }
            }
        });
    }

    // Update when cart fragments refresh - additional backup method
    $(document.body).on("wc_fragments_refreshed", function () {
        updateFeeTables();
        updateUpfrontTotal();
    });

    // Initial update of tables when page loads
    updateFeeTables();
    updateUpfrontTotal();
});