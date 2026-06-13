/**
 * Modem, Phone and TV Selection JavaScript - COMPLETE VERSION
 * Includes: modems, phones, TVs, installation dates, fee tables
 * Note: Shipping address functionality moved to checkout (shipping-address.js)
 */
jQuery(document).ready(function ($) {

	let cartAjaxPending = false;
	window.isCartAjaxPending = function() { return cartAjaxPending; };
    // Store the product IDs for each row
    const modemRows = {
        'modem-0': 267980,
        'modem-1': 267981,
        'modem-2': 267983,
        'modem-3': 267984,
        'modem-4': 267979,
        'modem-5': 268259,
        'modem-6': 268266,
        'modem-7': 268258,
        'modem-8': 268265,
        'modem-9': 268264,
        'modem-10': 268260,
        'modem-11': 268267
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


    // Make modem rows clickable - dynamic via shared .divi-portable-row class
    $(document).on('click', '.divi-portable-row', function (e) {
        // Don't trigger click if user is interacting with radio buttons or labels (but allow input clicks)
        if ($(e.target).is('label') && !$(e.target).closest('.own-modem-input-container').length) {
            return;
        }

        const $this = $(this);
        const rowClass = $this.attr('class').split(' ').find(c => /^modem-\d+$/.test(c));

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
            $('.divi-portable-row').not('.modem-4').removeClass('modem-row-selected');

            // Check if this card is already selected WITH valid input
            if ($this.hasClass('modem-row-selected') && modemDetails.length >= 5 && modemDetails.length <= 100) {
                // Card is already valid - just keep it selected, don't deselect
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
            // Handle regular modem selection
            if ($this.hasClass('modem-row-selected')) {
                $this.removeClass('modem-row-selected');
                removeFromCart(productId);
            } else {
                $('.divi-portable-row').removeClass('modem-row-selected own-modem-pending');
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
        $('.divi-portable-row').not('.modem-4').removeClass('modem-row-selected');

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
        $('.divi-portable-row').not('.modem-4').removeClass('modem-row-selected');

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

    // Blur handler - saves to cart when user leaves the field
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

    // Make phone rows clickable - dynamic via shared .divi-portable-phone class
    $(document).on('click', '.divi-portable-phone', function () {
        const $this = $(this);
        const rowClass = $this.attr('class').split(' ').find(c => /^phone-\d+$/.test(c));

        if (!rowClass || !phoneRows[rowClass]) {
            console.error('Product ID not found for this row');
            return;
        }

        if ($this.hasClass('phone-row-selected')) {
            $this.removeClass('phone-row-selected');
            removeFromCart(phoneRows[rowClass]);
        } else {
            $('.divi-portable-phone').removeClass('phone-row-selected');
            $this.addClass('phone-row-selected');
            addToCart(phoneRows[rowClass], false, 'phone');
        }
    });

    // Make TV rows clickable - dynamic via shared .divi-portable-tv class
    $(document).on('click', '.divi-portable-tv', function () {
        const $this = $(this);
        const rowClass = $this.attr('class').split(' ').find(c => /^tv-\d+$/.test(c));

        if (!rowClass || !tvRows[rowClass]) {
            console.error('Product ID not found for this row');
            return;
        }

        if ($this.hasClass('tv-row-selected')) {
            $this.removeClass('tv-row-selected');
            removeFromCart(tvRows[rowClass]);
        } else {
            $('.divi-portable-tv').removeClass('tv-row-selected');
            $this.addClass('tv-row-selected');
            addToCart(tvRows[rowClass], false, 'tv');
        }
    });


    // Function to add product to cart
    function addToCart(productId, isInternetPlan = false, productType = '') {
		 cartAjaxPending = true;  
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
				  cartAjaxPending = false;  
                if (response.success) {
                    console.log('Product added to cart:', response.data.product_id);
					  // Update total immediately from this response
        		if (response.data.upfront_total) {
            		$('.upfront-fee-total-container .upfront-fee-content').html(response.data.upfront_total);
        			}
                    $(document.body).trigger('wc_fragment_refresh');
                    updateFeeTables();
                } else { 
                    console.error('Error:', response.data.message);
                }
            },
            error: function (xhr, status, error) {
				cartAjaxPending = false; 
                console.error('AJAX Error:', error);
            }
        });
    }

    // Function to remove product from cart
    function removeFromCart(productId) {
		cartAjaxPending = true;
        $.ajax({
            type: 'POST',
            url: modem_selection_vars.ajax_url,
            data: {
                action: 'modem_remove_from_cart',
                product_id: productId,
                nonce: modem_selection_vars.nonce
            },
            success: function (response) {
				cartAjaxPending = false;
                if (response.success) {
                    console.log('Product removed from cart:', productId);
					  // Update total immediately from this response
        		if (response.data.upfront_total) {
            		$('.upfront-fee-total-container .upfront-fee-content').html(response.data.upfront_total);
        		}
                    $(document.body).trigger('wc_fragment_refresh');
                    updateFeeTables();
                } else {
                    console.error('Error:', response.data.message);
                }
            },
            error: function (xhr, status, error) {
				cartAjaxPending = false;
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

				 // Signal that cart highlighting is complete
            $(document.body).trigger('cartHighlightComplete');
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

    // Function to update upfront fee total with preloader
    function updateUpfrontTotal() {
        const upfrontContainer = $('.upfront-fee-total-container, [data-shortcode="upfront_fee_total"]');

        if (upfrontContainer.length && typeof showPreloaderManually === 'function') {
            showPreloaderManually(upfrontContainer);
        }

        if (typeof window.updateUpfrontFeeWithPreloader === 'function') {
            window.updateUpfrontFeeWithPreloader();
        } else {
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

	

   function updateFeeTables() {
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

                // Apply total directly — no extra AJAX call, no setTimeout
                if (response.data.upfront_total) {
                    upfrontContainer.find('.upfront-fee-content').html(response.data.upfront_total);
                }

                if (typeof hidePreloaderManually === 'function') {
                    hidePreloaderManually(upfrontContainer);
                }
            }
        },
        error: function () {
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

                $('.preferred-date-radio').prop('checked', false);
                $('.secondary-date-radio').prop('checked', false);
                preferredDate = '';
                secondaryDate = '';

                resetDateOptionVisibility();

                $(this).hide();

                checkInstallationSelection();
            });

            if (preferredDate || secondaryDate || $('.preferred-date-radio:checked').length || $('.secondary-date-radio:checked').length) {
                clearButton.show();
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

                        setTimeout(function () {
                            updateFeeTables();
                        }, 100);
                    } else {
                        console.error('Error adding installation dates:', response.data?.message || 'Unknown error');
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
                    preferredDate = response.data.dates['preferred-date'] || '';
                    secondaryDate = response.data.dates['secondary-date'] || '';

                    $('.preferred-date-radio').prop('checked', false);
                    $('.secondary-date-radio').prop('checked', false);
                    resetDateOptionVisibility();

                    if (preferredDate) {
                        $(`input[name="attribute_preferred-date"][value="${preferredDate}"]`).prop('checked', true);
                        updateSecondaryDateOptions(preferredDate);
                    }

                    if (secondaryDate) {
                        $(`input[name="attribute_secondary-date"][value="${secondaryDate}"]`).prop('checked', true);
                        updatePreferredDateOptions(secondaryDate);
                    }

                    if (preferredDate || secondaryDate) {
                        $('.clear-installation-dates').show();
                    }

                    checkInstallationSelection();
                }
            }
        });
    }

    // Function to update secondary date options based on preferred selection
    function updateSecondaryDateOptions(selectedPreferredValue) {
        $('.secondary-date-radio').closest('.date-option').removeClass('disabled-option disabled-option-visible').show();
        $('.secondary-date-radio').prop('disabled', false);

        if (selectedPreferredValue) {
            const matchingSecondaryOption = $(`.secondary-date-radio[value="${selectedPreferredValue}"]`).closest('.date-option');
            if (matchingSecondaryOption.length) {
                matchingSecondaryOption.addClass('disabled-option-visible');
                matchingSecondaryOption.find('.secondary-date-radio').prop('disabled', true);

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
        $('.preferred-date-radio').closest('.date-option').removeClass('disabled-option disabled-option-visible').show();
        $('.preferred-date-radio').prop('disabled', false);

        if (selectedSecondaryValue) {
            const matchingPreferredOption = $(`.preferred-date-radio[value="${selectedSecondaryValue}"]`).closest('.date-option');
            if (matchingPreferredOption.length) {
                matchingPreferredOption.addClass('disabled-option-visible');
                matchingPreferredOption.find('.preferred-date-radio').prop('disabled', true);

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

    // Update when cart fragments refresh
    $(document.body).on("wc_fragments_refreshed", function () {
    updateFeeTables();
});

    // Initial update of tables when page loads
    updateFeeTables();
checkInstallationInCart();
});