/**
 * Modem, Phone and TV Selection JavaScript - COMPLETE VERSION with Shipping Address
 * Modified to add shipping address option for product 265769
 * Includes ALL original functionality: modems, phones, TVs, installation dates, fee tables
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

    // Google Maps autocomplete object for shipping address
    let shippingAutocomplete = null;

    // Get current page product ID (for internet plan)
    const currentPageProductId = $('input[name="add-to-cart"]').val() || 0;

    // Check if any product is already in cart and highlight that row
    checkCartAndHighlight();

    // Add input fields to "own modem" row
    const $ownModemRow = $('.modem-4');
    if ($ownModemRow.length) {
        const inputHtml = `
        <div class="own-modem-input-container">
            <input type="text" 
                   class="own-modem-input" 
                   placeholder="Enter modem make and model (e.g., Netgear CM1000)"
                   maxlength="100">
            <div class="own-modem-error">Please enter modem make and model (5-100 characters)</div>
            
            <!-- Shipping Address Section -->
            <div class="shipping-address-section">
                <label class="shipping-question">Ship to a different address?</label>
                <div class="shipping-checkbox-group">
                    <label class="shipping-checkbox-label">
                        <input type="radio" name="ship_to_different_address" value="yes" class="ship-yes-radio">
                        <span>Yes</span>
                    </label>
                    <label class="shipping-checkbox-label">
                        <input type="radio" name="ship_to_different_address" value="no" class="ship-no-radio">
                        <span>No</span>
                    </label>
                </div>
                <div class="shipping-address-input-wrapper" style="display: none;">
                    <input type="text" 
                           class="shipping-address-input" 
                           id="own-modem-shipping-address"
                           placeholder="Start typing your address..."
                           maxlength="200">
                    <div class="shipping-address-error">Please enter a valid shipping address</div>
                </div>
            </div>
        </div>
    `;
        // Try to find and append after description elements
        const $description = $ownModemRow.find('.et_pb_wc_description').last();
        if ($description.length) {
            $description.after(inputHtml);
        } else {
            $ownModemRow.append(inputHtml);
        }

        // Initialize Google Maps autocomplete after adding the input
        initializeShippingAutocomplete();
    }

    
    // Initialize Google Maps Autocomplete for shipping address
    function initializeShippingAutocomplete() {
        const shippingInput = document.getElementById('own-modem-shipping-address');
        
        if (shippingInput && typeof google !== 'undefined' && google.maps && google.maps.places) {
            shippingAutocomplete = new google.maps.places.Autocomplete(shippingInput, {
                types: ['address'],
                componentRestrictions: { country: 'ca' }
            });

            shippingAutocomplete.setFields(['address_components', 'formatted_address']);

            // Listen for place selection
            shippingAutocomplete.addListener('place_changed', function() {
                const place = shippingAutocomplete.getPlace();
                if (place && place.formatted_address) {
                    console.log('Shipping address selected:', place.formatted_address);
                    // Trigger validation
                    validateOwnModemCard();
                }
            });

            console.log('Google Maps autocomplete initialized for shipping address');
        } else {
            console.warn('Google Maps API not available for autocomplete');
        }
    }

    // Handle shipping radio button changes
    $(document).on('change', 'input[name="ship_to_different_address"]', function() {
        const $row = $(this).closest('.modem-4');
        const $wrapper = $row.find('.shipping-address-input-wrapper');
        const $input = $row.find('.shipping-address-input');
        
        if ($(this).val() === 'yes') {
            $wrapper.slideDown(300);
        } else {
            $wrapper.slideUp(300);
            $input.val(''); // Clear the input
            $input.removeClass('error');
            $row.find('.shipping-address-error').hide();
        }

        // Revalidate the card
        validateOwnModemCard();
        
        // CRITICAL: Trigger button state update for navigation
        if (typeof revalidateButtonStates === 'function') {
            revalidateButtonStates();
        }
    });

    // Handle shipping address input
    $(document).on('input', '.shipping-address-input', function() {
        validateOwnModemCard();
        
        // CRITICAL: Trigger button state update for navigation
        if (typeof revalidateButtonStates === 'function') {
            revalidateButtonStates();
        }
    });

    
    // Make modem rows clickable
    $('.modem-0, .modem-1, .modem-2, .modem-3, .modem-4').on('click', function (e) {
        // Don't trigger click if user is interacting with input fields or radio buttons
        if ($(e.target).is('input, label')) {
            return;
        }

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

            // Remove selection from all other modem rows first
            $('.modem-0, .modem-1, .modem-2, .modem-3').removeClass('modem-row-selected');

            // Check if this card is already selected
            if ($this.hasClass('modem-row-selected')) {
                // If already selected and clicked again, deselect
                $this.removeClass('modem-row-selected own-modem-pending');
                removeFromCart(productId);
                return;
            }

            // Mark card as clicked/pending
            $this.addClass('own-modem-pending');
            
            // Validate the entire card
            validateOwnModemCard();

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


    // Comprehensive validation function for own modem card
    function validateOwnModemCard() {
        const $row = $('.modem-4');
        const $modemInput = $row.find('.own-modem-input');
        const $shippingYes = $row.find('.ship-yes-radio');
        const $shippingNo = $row.find('.ship-no-radio');
        const $shippingAddressInput = $row.find('.shipping-address-input');
        
        const modemDetails = $modemInput.val().trim();
        const shippingChoice = $row.find('input[name="ship_to_different_address"]:checked').val();
        const shippingAddress = $shippingAddressInput.val().trim();

        console.log('=== VALIDATION CHECK ===');
        console.log('Modem details:', modemDetails);
        console.log('Shipping choice:', shippingChoice);
        console.log('Shipping address:', shippingAddress);

        // Only validate if card has been clicked (has pending or selected class)
        if (!$row.hasClass('own-modem-pending') && !$row.hasClass('modem-row-selected')) {
            console.log('Card not yet clicked - skipping validation');
            return;
        }

        let isValid = true;
        
        // Validate modem details
        if (modemDetails.length < 5 || modemDetails.length > 100) {
            $modemInput.addClass('error');
            $row.find('.own-modem-error').show();
            isValid = false;
        } else {
            $modemInput.removeClass('error');
            $row.find('.own-modem-error').hide();
        }

        // Validate shipping choice
        if (!shippingChoice) {
            isValid = false;
            console.log('No shipping choice selected');
        }

        // Validate shipping address if "Yes" is selected
        if (shippingChoice === 'yes') {
            if (shippingAddress.length < 10) {
                $shippingAddressInput.addClass('error');
                $row.find('.shipping-address-error').show();
                isValid = false;
            } else {
                $shippingAddressInput.removeClass('error');
                $row.find('.shipping-address-error').hide();
            }
        }

        console.log('Validation result:', isValid);

        // Update card state
        if (isValid) {
            $row.removeClass('own-modem-pending');
            $row.addClass('modem-row-selected');
            
            // Add to cart with all details
            const cartData = {
                modem_details: modemDetails,
                ship_to_different: shippingChoice,
                shipping_address: shippingChoice === 'yes' ? shippingAddress : ''
            };
            addOwnModemToCart(265769, cartData);
        } else {
            // Keep in pending state (red highlight)
            if ($row.hasClass('modem-row-selected')) {
                $row.removeClass('modem-row-selected');
                $row.addClass('own-modem-pending');
                removeFromCart(265769);
            }
        }

        // Trigger button state update
        setTimeout(function () {
            if (typeof revalidateButtonStates === 'function') {
                revalidateButtonStates();
            }
        }, 50);
    }


    // Enhanced input validation handler for modem details
    $(document).on('input', '.own-modem-input', function () {
        validateOwnModemCard();
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
                    const shipToDifferent = response.data.ship_to_different || '';
                    const shippingAddress = response.data.shipping_address || '';

                    // Find which modem rows have products in cart and highlight them
                    for (const [rowClass, productId] of Object.entries(modemRows)) {
                        if (cartItems.includes(productId)) {
                            $(`.${rowClass}`).addClass('modem-row-selected');

                            // If this is the "I Have My Own Modem" product (265769), populate all fields
                            if (productId === 265769) {
                                if (modemDetails) {
                                    $(`.${rowClass} .own-modem-input`).val(modemDetails);
                                }
                                if (shipToDifferent) {
                                    $(`.${rowClass} input[name="ship_to_different_address"][value="${shipToDifferent}"]`).prop('checked', true);
                                    if (shipToDifferent === 'yes' && shippingAddress) {
                                        $(`.${rowClass} .shipping-address-input-wrapper`).show();
                                        $(`.${rowClass} .shipping-address-input`).val(shippingAddress);
                                    }
                                }
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
    function addOwnModemToCart(productId, cartData) {
        $.ajax({
            type: 'POST',
            url: modem_selection_vars.ajax_url,
            data: {
                action: 'save_modem_details',
                product_id: productId,
                modem_details: cartData.modem_details,
                ship_to_different: cartData.ship_to_different,
                shipping_address: cartData.shipping_address,
                nonce: modem_selection_vars.nonce
            },
            success: function (response) {
                if (response.success) {
                    console.log('Own modem added to cart with all details');
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
        // Simply check if the global preloader function exists and use it
        if (typeof window.updateFeesWithPreloader === 'function') {
            window.updateFeesWithPreloader();
        } else {
            // Fallback to original method
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

    // ===== INSTALLATION DATE SELECTION =====
    const installationRow = $('.installation-row');
    const installationProductId = 265084; // Parent product ID
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