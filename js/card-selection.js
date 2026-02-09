/**
 * Modem, Phone and TV Selection JavaScript - COMPLETE VERSION
 * Includes: modems, phones, TVs, installation dates, fee tables
 * Note: Shipping address functionality moved to checkout (shipping-address.js)
 */
jQuery(document).ready(function ($) {
    // Store the product IDs for each row
    const modemRows = {
        'modem-0': 267980,
        'modem-1': 267981,
        'modem-2': 267983,
        'modem-3': 267984,
        'modem-4': 267979
    };

    const phoneRows = {
        'phone-0': 304,
        'phone-1': 305,
        'phone-2': 267991
    };

    const tvRows = {
        'tv-0': 267996,
        'tv-1': 267997,
        'tv-2': 267993
    };

    // Get current page product ID (for internet plan)
    const currentPageProductId = $('input[name="add-to-cart"]').val() || 0;

    // declare Modem Details
    let lastSavedModemDetails = ''; // Track what we last sent to server

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
    $('.modem-0, .modem-1, .modem-2, .modem-3, .modem-4').on('click', function (e) {
        // Don't trigger click if user is interacting with radio buttons or labels (but allow input clicks)
        if ($(e.target).is('label') && !$(e.target).closest('.own-modem-input-container').length) {
            return;
        }

        const $this = $(this);
        const rowClass = $this.attr('class').split(' ').find(c => c.startsWith('modem-'));

        if (!rowClass || !modemRows[rowClass]) {
            console.error('Product ID not found for this row');
            return;
        }

        const productId = modemRows[rowClass];

        // Special handling for "own modem" product (267979)
        if (productId === 267979) {
            const $input = $this.find('.own-modem-input');
            const modemDetails = $input.val().trim();

            // Remove selection from all other modem rows first
            $('.modem-0, .modem-1, .modem-2, .modem-3').removeClass('modem-row-selected');

            // Check if this card is already selected WITH valid input
            if ($this.hasClass('modem-row-selected') && modemDetails.length >= 5 && modemDetails.length <= 100) {
                // Card is already valid - just keep it selected, don't deselect
                // This allows clicking back into the card without losing the selection
                console.log('Own modem card already valid - maintaining selection');
                return;
            }

            // Check if clicking to deselect (card is selected but input is now invalid/empty)
            if ($this.hasClass('modem-row-selected') && modemDetails.length === 0) {
                // User cleared the input, so deselect
                $this.removeClass('modem-row-selected own-modem-pending');
                removeFromCart(productId);
                return;
            }

            // If we get here, card is either:
            // 1. Not selected yet (new click)
            // 2. Has pending state (red, needs completion)
            // Mark card as clicked/pending if not already in a valid state
            if (!$this.hasClass('modem-row-selected')) {
                $this.addClass('own-modem-pending');
            }

            // Re-validate the card based on current input
            const inputLength = modemDetails.length;
            if (inputLength >= 5 && inputLength <= 100) {
                // Valid input - convert to selected state
                $this.removeClass('own-modem-pending').addClass('modem-row-selected');
                $input.removeClass('error');
                $this.find('.own-modem-error').hide();
            } else if (inputLength > 0 && inputLength < 5) {
                // Invalid input - show error
                $this.removeClass('modem-row-selected').addClass('own-modem-pending');
                $input.addClass('error');
                $this.find('.own-modem-error').show();
            }
            // If empty, just mark as pending (already done above)

        } else {
            // Handle regular modem selection (unchanged)
            if ($this.hasClass('modem-row-selected')) {
                $this.removeClass('modem-row-selected');
                removeFromCart(productId);
            } else {
                $('.modem-0, .modem-1, .modem-2, .modem-3, .modem-4').removeClass('modem-row-selected own-modem-pending');
                $this.addClass('modem-row-selected');
                addToCart(productId, false, 'modem');
            }
        }
    });


    // Handle focus on own modem input - treat it as card selection
    $(document).on('focus', '.own-modem-input', function () {
        console.log('=== OWN MODEM INPUT FOCUSED ===');

        const $input = $(this);
        const $row = $input.closest('.modem-4');
        const modemDetails = $input.val().trim();

        console.log('Input has focus, current value:', modemDetails);
        console.log('Row classes before focus:', $row.attr('class'));

        // Remove selection from all other modem rows
        $('.modem-0, .modem-1, .modem-2, .modem-3').removeClass('modem-row-selected');

        // If the card isn't already selected or pending, mark it as pending
        if (!$row.hasClass('modem-row-selected') && !$row.hasClass('own-modem-pending')) {
            console.log('Adding own-modem-pending class on focus');
            $row.addClass('own-modem-pending');
        }

        // If there's already valid text in the field, convert to selected immediately
        if (modemDetails.length >= 5 && modemDetails.length <= 100) {
            console.log('Valid text already exists - converting to selected on focus');
            $row.removeClass('own-modem-pending').addClass('modem-row-selected');
            $input.removeClass('error');
            $row.find('.own-modem-error').hide();

            // Trigger button state update
            if (typeof revalidateButtonStates === 'function') {
                revalidateButtonStates();
            }
        }
    });

    // Enhanced input validation handler for modem details - VISUAL FEEDBACK ONLY
    $(document).on('input', '.own-modem-input', function () {
        console.log('=== INPUT HANDLER TRIGGERED ===');

        const $input = $(this);
        const $row = $input.closest('.modem-4');
        const modemDetails = $input.val().trim();
        const productId = 267979; // Own modem product ID

        console.log('Input value:', modemDetails);
        console.log('Input length:', modemDetails.length);
        console.log('Row classes BEFORE:', $row.attr('class'));

        // Remove selection from all other modem rows
        $('.modem-0, .modem-1, .modem-2, .modem-3').removeClass('modem-row-selected');

        // Ensure the card is at least marked as pending if user is typing
        if (!$row.hasClass('modem-row-selected') && !$row.hasClass('own-modem-pending') && modemDetails.length > 0) {
            console.log('User started typing - adding own-modem-pending class');
            $row.addClass('own-modem-pending');
        }

        if (modemDetails.length >= 5 && modemDetails.length <= 100) {
            console.log('VALIDATION PASSED');

            // Clear errors and show as valid (green)
            $input.removeClass('error');
            $row.find('.own-modem-error').hide();

            // Convert from pending to selected (visual state only - NO AJAX)
            $row.removeClass('own-modem-pending');
            $row.addClass('modem-row-selected');

        } else if (modemDetails.length < 5 && modemDetails.length > 0) {
            console.log('VALIDATION FAILED - too short');

            // Show error (red state)
            $input.addClass('error');
            $row.find('.own-modem-error').show();

            // Revert to pending state
            if ($row.hasClass('modem-row-selected')) {
                console.log('Converting from selected back to pending');
                $row.removeClass('modem-row-selected');
                $row.addClass('own-modem-pending');
            }

        } else if (modemDetails.length === 0) {
            // Empty field - clear everything
            $input.removeClass('error');
            $row.find('.own-modem-error').hide();
            $row.removeClass('modem-row-selected own-modem-pending');
        }

        // Trigger button state update after changes
        setTimeout(function () {
            console.log('Row classes AFTER:', $row.attr('class'));

            // Trigger validation update
            if (typeof revalidateButtonStates === 'function') {
                revalidateButtonStates();
            }
        }, 50);
    });

    // NEW: Blur handler - saves to cart when user leaves the field
    $(document).on('blur', '.own-modem-input', function () {
        const $input = $(this);
        const $row = $input.closest('.modem-4');
        const modemDetails = $input.val().trim();
        const productId = 267979;

        console.log('=== BLUR EVENT - Checking if we need to save ===');
        console.log('Current value:', modemDetails);
        console.log('Last saved value:', lastSavedModemDetails);

        // Only send AJAX if:
        // 1. Validation passes AND
        // 2. Value has changed since last save
        if (modemDetails.length >= 5 && modemDetails.length <= 100) {
            if (modemDetails !== lastSavedModemDetails) {
                console.log('Value changed and valid - saving to cart');
                addOwnModemToCart(productId, modemDetails);
                lastSavedModemDetails = modemDetails;
            } else {
                console.log('Value unchanged - no save needed');
            }
        } else if (modemDetails.length === 0 && lastSavedModemDetails) {
            // User cleared the field - remove from cart
            console.log('Field cleared - removing from cart');
            removeFromCart(productId);
            lastSavedModemDetails = '';
        } else if (modemDetails.length > 0 && modemDetails.length < 5) {
            // Invalid input - if we had saved something before, remove it
            if (lastSavedModemDetails) {
                console.log('Invalid input - removing previously saved data from cart');
                removeFromCart(productId);
                lastSavedModemDetails = '';
            }
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
                    console.log('Product added to cart:', response.data.product_id);
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
                    const modemDetails = response.data.modem_details || '';

                    // Find which modem rows have products in cart and highlight them
                    for (const [rowClass, productId] of Object.entries(modemRows)) {
                        if (cartItems.includes(productId)) {
                            $(`.${rowClass}`).addClass('modem-row-selected');

                            // If this is the "I Have My Own Modem" product (267979), populate the input field
                            if (productId === 267979 && modemDetails) {
                                $(`.${rowClass} .own-modem-input`).val(modemDetails);
                            }
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
            console.log('=== AJAX CALL DEBUG ===');
    console.log('modemDetails parameter:', modemDetails);
    console.log('modemDetails length:', modemDetails.length);
    console.log('modemDetails type:', typeof modemDetails);

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

                    // CRITICAL FIX A: Trigger cart fragment refresh
                    $(document.body).trigger('wc_fragment_refresh');

                    // CRITICAL FIX A: Update upfront total immediately
                    setTimeout(function () {
                        updateUpfrontTotal();
                    }, 100);

                    // Note: We don't update fee tables here because own modem 
                    // doesn't appear in monthly summary (it's $0)
                } else {
                    console.error('Error:', response.data.message);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
            }
        });
    }

    // Function to update upfront fee total with preloader
    function updateUpfrontTotal() {
        const upfrontContainer = $('.upfront-fee-total-container, [data-shortcode="upfront_fee_total"]');

        // CRITICAL: Show preloader BEFORE any AJAX
        if (upfrontContainer.length && typeof showPreloaderManually === 'function') {
            showPreloaderManually(upfrontContainer);
        }

        // Check if the global preloader function exists
        if (typeof window.updateUpfrontFeeWithPreloader === 'function') {
            window.updateUpfrontFeeWithPreloader();
        } else {
            // Fallback: Make AJAX call directly
            $.ajax({
                type: "POST",
                url: modem_selection_vars.ajax_url,
                data: {
                    action: "get_upfront_fee_total",
                    nonce: modem_selection_vars.nonce
                },
                success: function (response) {
                    if (response.success) {
                        upfrontContainer.find('.upfront-fee-content').html(response.data.total);

                        // Hide preloader after update
                        setTimeout(function () {
                            if (typeof hidePreloaderManually === 'function') {
                                hidePreloaderManually(upfrontContainer);
                            }
                        }, 250);
                    }
                },
                error: function () {
                    if (typeof hidePreloaderManually === 'function') {
                        hidePreloaderManually(upfrontContainer);
                    }
                }
            });
        }
    }

    // Function to update fee tables
    function updateFeeTables() {
        // CRITICAL: Show preloader for upfront total BEFORE updating tables
        const upfrontContainer = $('.upfront-fee-total-container, [data-shortcode="upfront_fee_total"]');
        if (upfrontContainer.length && typeof showPreloaderManually === 'function') {
            showPreloaderManually(upfrontContainer);
        }

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

                    // CRITICAL: Update upfront total AFTER tables are updated
                    setTimeout(function () {
                        updateUpfrontTotal();
                    }, 100);
                }
            },
            error: function () {
                // Hide preloader on error
                if (typeof hidePreloaderManually === 'function') {
                    hidePreloaderManually(upfrontContainer);
                }
            }
        });
    }

    // ===== INSTALLATION DATE SELECTION =====
    const installationRow = $('.installation-row');
    const installationProductId = 267986; // Parent product ID
    let preferredDate = '';
    let secondaryDate = '';

    function setupInstallationDateOptions() {
        const preferredSelect = $('.installation-row select[name="attribute_preferred-date"]');
        const secondarySelect = $('.installation-row select[name="attribute_secondary-date"]');

        if (preferredSelect.length && secondarySelect.length) {
            // Create preferred date container
            const preferredContainer = $(`
                <div class="date-selection-container">
                    <h4>Preferred Date</h4>
                    <div class="date-options-grid">
                        <div class="earliest-column">
                            <h5>Earliest Available</h5>
                        </div>
                        <div class="weekday-column">
                            <h5>Weekday Options</h5>
                        </div>
                        <div class="weekend-column">
                            <h5>Weekend Options</h5>
                        </div>
                    </div>
                </div>
            `);

            // Create secondary date container
            const secondaryContainer = $(`
                <div class="date-selection-container">
                    <h4>Secondary Date</h4>
                    <div class="date-options-grid">
                        <div class="earliest-column">
                            <h5>Earliest Available</h5>
                        </div>
                        <div class="weekday-column">
                            <h5>Weekday Options</h5>
                        </div>
                        <div class="weekend-column">
                            <h5>Weekend Options</h5>
                        </div>
                    </div>
                </div>
            `);

            // Build preferred date options
            preferredSelect.find('option').each(function () {
                if ($(this).val() === '') return; // Skip empty option

                const value = $(this).attr('value');
                const label = $(this).text();

                const radioBtn = $(`
                <div class="date-option" data-value="${value}">
                    <input type="radio" name="attribute_preferred-date" id="preferred-date-${value}" 
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

            // Build secondary date options
            secondarySelect.find('option').each(function () {
                if ($(this).val() === '') return; // Skip empty option

                const value = $(this).attr('value');
                const label = $(this).text();

                const radioBtn = $(`
                    <div class="date-option" data-value="${value}">
                        <input type="radio" name="attribute_secondary-date" id="secondary-date-${value}" 
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

            // Add Clear button
            const clearButton = $(`
                <button type="button" class="clear-installation-dates" style="display: none;">
                    Clear Installation Dates
                </button>
            `);

            // Replace the original select elements with our new interface
            preferredSelect.after(preferredContainer).hide();
            secondarySelect.after(secondaryContainer).after(clearButton).hide();

            // Event listeners for date selections
            $(document).on('change', '.preferred-date-radio', function () {
                preferredDate = $(this).val();
                console.log('Preferred date selected:', preferredDate);
                updateSecondaryDateOptions(preferredDate);
                checkInstallationSelection();
                $('.clear-installation-dates').show();
            });

            $(document).on('change', '.secondary-date-radio', function () {
                secondaryDate = $(this).val();
                console.log('Secondary date selected:', secondaryDate);
                updatePreferredDateOptions(secondaryDate);
                checkInstallationSelection();
                $('.clear-installation-dates').show();
            });

            // Clear button functionality
            $(document).on('click', '.clear-installation-dates', function () {
                console.log('Clearing installation dates');

                // Clear selections
                $('.preferred-date-radio').prop('checked', false);
                $('.secondary-date-radio').prop('checked', false);
                preferredDate = '';
                secondaryDate = '';

                // Reset all options visibility
                resetDateOptionVisibility();

                // Hide clear button
                $(this).hide();

                // Update state
                checkInstallationSelection();
            });

            // Show clear button if dates exist
            if (preferredDate || secondaryDate || $('.preferred-date-radio:checked').length || $('.secondary-date-radio:checked').length) {
                clearButton.show();
                // Update option visibility based on existing selections
                if (preferredDate) {
                    updateSecondaryDateOptions(preferredDate);
                }
                if (secondaryDate) {
                    updatePreferredDateOptions(secondaryDate);
                }
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

            // CRITICAL: Show preloader BEFORE AJAX call
            const upfrontContainer = $('.upfront-fee-total-container, [data-shortcode="upfront_fee_total"]');
            if (upfrontContainer.length && typeof showPreloaderManually === 'function') {
                showPreloaderManually(upfrontContainer);
            }

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

                        // CRITICAL: Small delay before updating tables
                        setTimeout(function () {
                            updateFeeTables();
                        }, 100);
                    } else {
                        console.error('Error adding installation dates:', response.data?.message || 'Unknown error');
                        // Hide preloader on error
                        if (typeof hidePreloaderManually === 'function') {
                            hidePreloaderManually(upfrontContainer);
                        }
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX error details:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    // Hide preloader on error
                    if (typeof hidePreloaderManually === 'function') {
                        hidePreloaderManually(upfrontContainer);
                    }
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

                    // Reset all radio buttons and options
                    $('.preferred-date-radio').prop('checked', false);
                    $('.secondary-date-radio').prop('checked', false);
                    resetDateOptionVisibility();

                    // Check the saved selections
                    if (preferredDate) {
                        $(`input[name="attribute_preferred-date"][value="${preferredDate}"]`).prop('checked', true);
                        updateSecondaryDateOptions(preferredDate);
                    }

                    if (secondaryDate) {
                        $(`input[name="attribute_secondary-date"][value="${secondaryDate}"]`).prop('checked', true);
                        updatePreferredDateOptions(secondaryDate);
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

    // Function to update secondary date options based on preferred selection
    function updateSecondaryDateOptions(selectedPreferredValue) {
        // Reset all secondary options first
        $('.secondary-date-radio').closest('.date-option').removeClass('disabled-option disabled-option-visible').show();
        $('.secondary-date-radio').prop('disabled', false);

        if (selectedPreferredValue) {
            // Find and disable the matching option in secondary dates
            const matchingSecondaryOption = $(`.secondary-date-radio[value="${selectedPreferredValue}"]`).closest('.date-option');
            if (matchingSecondaryOption.length) {
                matchingSecondaryOption.addClass('disabled-option-visible');
                matchingSecondaryOption.find('.secondary-date-radio').prop('disabled', true);

                // If the disabled option was selected, clear it
                if (secondaryDate === selectedPreferredValue) {
                    matchingSecondaryOption.find('.secondary-date-radio').prop('checked', false);
                    secondaryDate = '';
                    checkInstallationSelection();
                }
            }
        }
    }

    // Function to update preferred date options based on secondary selection
    function updatePreferredDateOptions(selectedSecondaryValue) {
        // Reset all preferred options first
        $('.preferred-date-radio').closest('.date-option').removeClass('disabled-option disabled-option-visible').show();
        $('.preferred-date-radio').prop('disabled', false);

        if (selectedSecondaryValue) {
            // Find and disable the matching option in preferred dates
            const matchingPreferredOption = $(`.preferred-date-radio[value="${selectedSecondaryValue}"]`).closest('.date-option');
            if (matchingPreferredOption.length) {
                matchingPreferredOption.addClass('disabled-option-visible');
                matchingPreferredOption.find('.preferred-date-radio').prop('disabled', true);

                // If the disabled option was selected, clear it
                if (preferredDate === selectedSecondaryValue) {
                    matchingPreferredOption.find('.preferred-date-radio').prop('checked', false);
                    preferredDate = '';
                    checkInstallationSelection();
                }
            }
        }
    }

    // Function to reset all date option visibility
    function resetDateOptionVisibility() {
        $('.date-option').removeClass('disabled-option disabled-option-visible').show();
        $('.date-radio').prop('disabled', false);
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