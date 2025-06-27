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
        'modem-3': 265108,
        'modem-4': 265769 
    };

    const phoneRows = {
        'phone-0': 304,
        'phone-1': 305,
        'phone-2': 265475
    };

    const tvRows = {
        'tv-0': 264269,
        'tv-1': 264270,
        'tv-2': 265476
    };

    // Get current page product ID (for internet plan)
    const currentPageProductId = $('input[name="add-to-cart"]').val() || 0;

    // Check if any product is already in cart and highlight that row
    checkCartAndHighlight();

    // Add input field to "own modem" row
    const $ownModemRow = $('.modem-4');
    if ($ownModemRow.length) {
        const inputHtml = `
        <div class="own-modem-input-container">
            <input type="text" 
                   class="own-modem-input" 
                   placeholder="Enter modem make and model (e.g., Netgear CM1000)"
                   maxlength="100">
            <div class="own-modem-error">Please enter modem make and model (5-100 characters)</div>
        </div>
    `;
        // Try to find and append after description elements
        const $description = $ownModemRow.find('.et_pb_wc_description').last();
        if ($description.length) {
            $description.after(inputHtml);
        } else {
            $ownModemRow.append(inputHtml);
        }
    }

    
    // Make modem rows clickable
    $('.modem-0, .modem-1, .modem-2, .modem-3, .modem-4').on('click', function () {
        const $this = $(this);
        const rowClass = $this.attr('class').split(' ').find(c => c.startsWith('modem-'));

        if (!rowClass || !modemRows[rowClass]) {
            console.error('Product ID not found for this row');
            return;
        }

        const productId = modemRows[rowClass];

        // Special handling for "own modem" product (265769)
        if (productId === 265769) {
            const $input = $this.find('.own-modem-input');
            const modemDetails = $input.val().trim();

            // Validate input
            if (modemDetails.length < 5 || modemDetails.length > 100) {
                $input.addClass('error');
                $this.find('.own-modem-error').show();
                return;
            }

            // Clear any previous errors
            $input.removeClass('error');
            $this.find('.own-modem-error').hide();

            // Check if already selected
            if ($this.hasClass('modem-row-selected')) {
                // Deselect and remove from cart
                $this.removeClass('modem-row-selected');
                removeFromCart(productId);
            } else {
                // Remove selection from all modem rows
                $('.modem-0, .modem-1, .modem-2, .modem-3, .modem-4').removeClass('modem-row-selected');

                // Add selection to this row
                $this.addClass('modem-row-selected');

                // Add to cart with modem details
                addOwnModemToCart(productId, modemDetails);
            }
        } else {
            // Handle regular modem selection
            if ($this.hasClass('modem-row-selected')) {
                $this.removeClass('modem-row-selected');
                removeFromCart(productId);
            } else {
                $('.modem-0, .modem-1, .modem-2, .modem-3, .modem-4').removeClass('modem-row-selected');
                $this.addClass('modem-row-selected');
                addToCart(productId, false, 'modem');
            }
        }
    });

    // Clear input validation on typing
    $(document).on('input', '.own-modem-input', function () {
        const $input = $(this);
        const $row = $input.closest('[class*="modem-"]');

        if ($input.val().trim().length >= 5) {
            $input.removeClass('error');
            $row.find('.own-modem-error').hide();
        }
    });

    // Make phone rows clickable
    $('.phone-0, .phone-1, .phone-2').on('click', function () {
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
            $('.phone-0, .phone-1, .phone-2').removeClass('phone-row-selected');

            // Add selection to this row
            $this.addClass('phone-row-selected');

            // Add to cart via AJAX
            addToCart(phoneRows[rowClass], false, 'phone');
        }
    });

    // Make TV rows clickable
    $('.tv-0, .tv-1, .tv-2').on('click', function () {
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
            $('.tv-0, .tv-1, .tv-2').removeClass('tv-row-selected');

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

    // Function to add own modem to cart with details
    function addOwnModemToCart(productId, modemDetails) {
        $.ajax({
            type: 'POST',
            url: modem_selection_vars.ajax_url,
            data: {
                action: 'save_modem_details',
                product_id: productId,
                modem_details: modemDetails,
                nonce: modem_selection_vars.nonce
            },
            success: function (response) {
                if (response.success) {
                    console.log('Own modem added to cart with details');
                    $(document.body).trigger('wc_fragment_refresh');
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

    // Installation date selection
    const installationRow = $('.installation-row');
    const installationProductId = 265084; // Parent product ID
    let preferredDate = '';
    let secondaryDate = '';

    // Setup installation date options
    function setupInstallationDateOptions() {
        const preferredSelect = $('.installation-row select[name="attribute_preferred-date"]');
        const secondarySelect = $('.installation-row select[name="attribute_secondary-date"]');

        if (preferredSelect.length && secondarySelect.length) {
            // Create containers with column layout
            const preferredContainer = $(`
            <div class="date-radio-container preferred-date-container">
                <h4>Preferred Date</h4>
                <div class="date-options-columns">
                    <div class="earliest-column">
                    </div>
                    <div class="weekday-column">
                    </div>
                    <div class="weekend-column">
                    </div>
                </div>
            </div>
        `);

            const secondaryContainer = $(`
            <div class="date-radio-container secondary-date-container">
                <h4>Secondary Date</h4>
                <div class="date-options-columns">
                    <div class="earliest-column">
                    </div>
                    <div class="weekday-column">
                    </div>
                    <div class="weekend-column">
                    </div>
                </div>
            </div>
        `);

            // Process preferred date options
            const preferredOptions = preferredSelect.find('option').not('[value=""]');
            preferredOptions.each(function () {
                const value = $(this).attr('value');
                const label = $(this).text();

                const radioBtn = $(`
                <div class="date-option">
                    <input type="radio" name="preferred-date" id="preferred-date-${value}" 
                           value="${value}" class="date-radio preferred-date-radio" />
                    <label for="preferred-date-${value}">${label}</label>
                </div>
            `);

                // Categorize options into columns
                if (label.toLowerCase().includes('earliest')) {
                    preferredContainer.find('.earliest-column').append(radioBtn);
                } else if (label.toLowerCase().includes('weekend') || label.toLowerCase().includes('saturday') || label.toLowerCase().includes('sunday')) {
                    preferredContainer.find('.weekend-column').append(radioBtn);
                } else {
                    preferredContainer.find('.weekday-column').append(radioBtn);
                }
            });

            // Process secondary date options
            const secondaryOptions = secondarySelect.find('option').not('[value=""]');
            secondaryOptions.each(function () {
                const value = $(this).attr('value');
                const label = $(this).text();

                const radioBtn = $(`
                <div class="date-option">
                    <input type="radio" name="secondary-date" id="secondary-date-${value}" 
                           value="${value}" class="date-radio secondary-date-radio" />
                    <label for="secondary-date-${value}">${label}</label>
                </div>
            `);

                // Categorize options into columns
                if (label.toLowerCase().includes('earliest')) {
                    secondaryContainer.find('.earliest-column').append(radioBtn);
                } else if (label.toLowerCase().includes('weekend') || label.toLowerCase().includes('saturday') || label.toLowerCase().includes('sunday')) {
                    secondaryContainer.find('.weekend-column').append(radioBtn);
                } else {
                    secondaryContainer.find('.weekday-column').append(radioBtn);
                }
            });

            // Replace selects with custom layout
            preferredSelect.parent().hide().after(preferredContainer);
            secondarySelect.parent().hide().after(secondaryContainer);

            // Add clear button
            const clearButton = $('<button type="button" class="clear-installation-dates" style="display: none;">Clear Dates</button>');
            secondaryContainer.after(clearButton);

            // Handle clear button click
            clearButton.on('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                preferredDate = '';
                secondaryDate = '';
                $('.preferred-date-radio, .secondary-date-radio').prop('checked', false);
                installationRow.removeClass('installation-row-selected');
                removeInstallationFromCart();
                $(this).hide();
            });

            // Handle preferred date selection
            $('.preferred-date-radio').on('change', function () {
                preferredDate = $(this).val();
                preferredSelect.val(preferredDate).trigger('change');
                clearButton.show();
                checkInstallationSelection();
            });

            // Handle secondary date selection
            $('.secondary-date-radio').on('change', function () {
                secondaryDate = $(this).val();
                secondarySelect.val(secondaryDate).trigger('change');
                clearButton.show();
                checkInstallationSelection();
            });

            // Show clear button if selections already exist
            if (preferredDate || secondaryDate || $('.preferred-date-radio:checked').length || $('.secondary-date-radio:checked').length) {
                clearButton.show();
            }
        }
    }

    // Check if both dates are selected and update UI/cart accordingly
function checkInstallationSelection() {
    console.log("Checking selection - preferredDate:", preferredDate, "secondaryDate:", secondaryDate);
    if (preferredDate && secondaryDate) {
        $('.installation-row').addClass('installation-row-selected');
        addInstallationToCart();
    } else {
        $('.installation-row').removeClass('installation-row-selected');
        // Only remove from cart if we had both dates before but now don't
        if (!preferredDate && !secondaryDate) {
            removeInstallationFromCart();
        }
    }
}

    // Add installation date selection to cart
    function addInstallationToCart() {
        if (preferredDate && secondaryDate) {
            console.log("Sending dates:", {
                preferred: preferredDate,
                secondary: secondaryDate
            });

            $.ajax({
                type: 'POST',
                url: modem_selection_vars.ajax_url,
                data: {
                    action: 'add_installation_to_cart',
                    product_id: installationProductId,
                    'preferred-date': preferredDate,
                    'secondary-date': secondaryDate,
                    nonce: modem_selection_vars.nonce
                },
                success: function (response) {
                    console.log("Full response:", response);
                    if (response.success) {
                        console.log('Installation dates added to cart');
                        $(document.body).trigger('wc_fragment_refresh');
                        updateFeeTables();
                    } else {
                        console.error('Error adding installation dates:', response.data?.message || 'Unknown error');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX error details:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                }
            });
        }
    }

    // Remove installation from cart
    function removeInstallationFromCart() {
        $.ajax({
            type: 'POST',
            url: modem_selection_vars.ajax_url,
            data: {
                action: 'remove_installation_from_cart',
                product_id: installationProductId,
                nonce: modem_selection_vars.nonce
            },
            success: function (response) {
                if (response.success) {
                    console.log('Installation removed from cart');
                    $(document.body).trigger('wc_fragment_refresh');
                    updateFeeTables();
                }
            }
        });
    }

    // Check if installation date is in cart when page loads
    function checkInstallationInCart() {
        $.ajax({
            type: 'POST',
            url: modem_selection_vars.ajax_url,
            data: {
                action: 'get_installation_dates',
                nonce: modem_selection_vars.nonce
            },
            success: function (response) {
                if (response.success && response.data.dates) {
                    // Get the dates with correct keys
                    preferredDate = response.data.dates['preferred-date'] || '';
                    secondaryDate = response.data.dates['secondary-date'] || '';

                    // Reset all radio buttons
                    $('.preferred-date-radio').prop('checked', false);
                    $('.secondary-date-radio').prop('checked', false);

                    // Check the saved selections
                    if (preferredDate) {
                        $(`input[name="preferred-date"][value="${preferredDate}"]`).prop('checked', true);
                    }

                    if (secondaryDate) {
                        $(`input[name="secondary-date"][value="${secondaryDate}"]`).prop('checked', true);
                    }

                    // If any date is set, show the clear button
                    if (preferredDate || secondaryDate) {
                        $('.clear-installation-dates').show();
                    }

                    // Update UI state
                    checkInstallationSelection();
                }
            }
        });
    }

    // Initialize installation date interface
    setupInstallationDateOptions();

    // Run on page load
    checkInstallationInCart();

    // Update when cart fragments refresh - additional backup method
    $(document.body).on("wc_fragments_refreshed", function () {
        updateFeeTables();
        updateUpfrontTotal();
    });

    // Initial update of tables when page loads
    updateFeeTables();
    updateUpfrontTotal();
    checkInstallationInCart();
});