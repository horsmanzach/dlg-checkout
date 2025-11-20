/**
 * Customer Info Confirmation Functionality - FIXED VERSION
 * 
 * Handles the "Confirm Customer Info" button and validation in the checkout
 * This is the 4th validation requirement before payment can be completed
 */

jQuery(document).ready(function ($) {
    console.log('Customer Info Confirm: Initializing...');

    // Track confirmation state
    let customerInfoConfirmed = false;
    let confirmTimestamp = null;

    // Initialize the confirm button functionality
    initializeCustomerInfoConfirm();

    function initializeCustomerInfoConfirm() {
        // Check if button already exists (in case of page refresh)
        if ($('#confirm-customer-info-btn').length > 0) {
            console.log('Customer info confirm button already exists');
            setupButtonHandlers();
            return;
        }

        // FIXED: Try multiple possible container selectors
        const possibleContainers = [
            '.shipping-address-checkbox-container',
            '.customshipping-address-checkout-container',
            '#shipping-address-container',
            '.woocommerce-billing-fields',
            '.woocommerce-shipping-fields'
        ];

        // Wait for ANY of these containers to be available
        const checkContainer = setInterval(function () {
            let $container = null;

            // Try each possible container
            for (let i = 0; i < possibleContainers.length; i++) {
                const selector = possibleContainers[i];
                const $found = $(selector);

                if ($found.length > 0) {
                    console.log('Found container:', selector);
                    $container = $found;
                    break;
                }
            }

            if ($container && $container.length > 0) {
                console.log('Found target container, adding confirm button');
                clearInterval(checkContainer);
                addConfirmButton($container);
                setupButtonHandlers();
            } else {
                // Debug: Log what containers ARE present
                console.log('Container not found yet. Available containers:',
                    $('div[class*="shipping"], div[class*="billing"], div[class*="checkout"]')
                        .map(function () { return this.className; })
                        .get()
                        .slice(0, 10) // Limit output
                );
            }
        }, 100);

        // INCREASED timeout to 10 seconds
        setTimeout(function () {
            clearInterval(checkContainer);

            // If still not found, log all container classes for debugging
            if ($('#confirm-customer-info-btn').length === 0) {
                console.error('Failed to find container for confirm button after 10 seconds');
                console.log('All checkout containers:',
                    $('div[class*="checkout"], div[class*="shipping"], div[class*="billing"]')
                        .map(function () { return this.className; })
                        .get()
                );

                // FALLBACK: Try to add button to billing fields as last resort
                const $fallback = $('.woocommerce-billing-fields__field-wrapper').last();
                if ($fallback.length > 0) {
                    console.log('Using fallback container');
                    addConfirmButton($fallback);
                    setupButtonHandlers();
                }
            }
        }, 10000);
    }

    /**
     * Add the confirm customer info button below the target container
     */
    function addConfirmButton($container) {
        // Create button HTML with WooCommerce default button classes
        const buttonHtml = `
            <div class="customer-info-confirm-wrapper woocommerce" style="margin-top: 20px; margin-bottom: 100px; text-align: right; clear: both;">
                <button type="button" id="confirm-customer-info-btn" class="button">
                    <span>Confirm Customer Info</span>
                </button>
            </div>
        `;

        // Insert button after the container
        $container.after(buttonHtml);
        console.log('Customer info confirm button added to DOM');

        // Verify it was added
        setTimeout(function () {
            const $addedButton = $('#confirm-customer-info-btn');
            if ($addedButton.length > 0) {
                console.log('✓ Button successfully added and found in DOM');
                console.log('Button parent:', $addedButton.parent().attr('class'));
                console.log('Button classes:', $addedButton.attr('class'));
            } else {
                console.error('✗ Button was added but cannot be found in DOM');
            }
        }, 100);
    }

    /**
     * Setup button click handlers
     */
    function setupButtonHandlers() {
        // Remove any existing handlers first
        $(document).off('click', '#confirm-customer-info-btn');

        $(document).on('click', '#confirm-customer-info-btn', function (e) {
            e.preventDefault();
            console.log('Customer info confirm button clicked');

            // Validate customer info fields
            if (validateCustomerInfo()) {
                confirmCustomerInfo();
            } else {
                showValidationError();
            }
        });

        console.log('Button handlers attached');
    }

    /**
     * Validate all required customer info fields
     */
    function validateCustomerInfo() {
        let isValid = true;
        let missingFields = [];

        // Clear all previous error styling
        $('#billing_first_name, #billing_last_name, #billing_email, #billing_phone, #billing_address_1, #shipping-address-input').removeClass('error-field');

        // Check first name
        const firstName = $('#billing_first_name').val() || '';
        if (firstName.trim().length === 0) {
            isValid = false;
            missingFields.push('First Name');
            $('#billing_first_name').addClass('error-field');
        }

        // Check last name
        const lastName = $('#billing_last_name').val() || '';
        if (lastName.trim().length === 0) {
            isValid = false;
            missingFields.push('Last Name');
            $('#billing_last_name').addClass('error-field');
        }

        // Check email
        const email = $('#billing_email').val() || '';
        if (email.trim().length === 0 || !isValidEmail(email)) {
            isValid = false;
            missingFields.push('Email (must be valid format)');
            $('#billing_email').addClass('error-field');
        }

        // Check phone - MUST have exactly 10 digits
        const phone = $('#billing_phone').val() || '';
        const phoneDigits = phone.replace(/\D/g, ''); // Remove all non-digit characters

        if (phoneDigits.length === 0) {
            isValid = false;
            missingFields.push('Phone Number');
            $('#billing_phone').addClass('error-field');
        } else if (phoneDigits.length !== 10) {
            isValid = false;
            missingFields.push('Phone Number (must be exactly 10 digits)');
            $('#billing_phone').addClass('error-field');
        }

        // Check service address
        const serviceAddress = $('#billing_address_1').val() || '';
        if (serviceAddress.trim().length === 0) {
            isValid = false;
            missingFields.push('Service Address');
            $('#billing_address_1').addClass('error-field');
        }

        // Check shipping address if "ship to different address" is checked
        const $shippingCheckbox = $('#ship-to-different-checkbox');
        if ($shippingCheckbox.length > 0 && $shippingCheckbox.is(':checked')) {
            const shippingAddress = $('#shipping-address-input').val() || '';
            if (shippingAddress.trim().length < 10) {
                isValid = false;
                missingFields.push('Shipping Address');
                $('#shipping-address-input').addClass('error-field');
            }
        }

        console.log('Customer info validation:', {
            isValid: isValid,
            missingFields: missingFields,
            phoneDigits: phoneDigits,
            phoneDigitsLength: phoneDigits.length
        });

        // Store missing fields for error display
        if (!isValid) {
            sessionStorage.setItem('customerInfoMissingFields', JSON.stringify(missingFields));
        }

        return isValid;
    }

    /**
     * Validate email format
     */
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Confirm customer info and update button state
     */
    function confirmCustomerInfo() {
        console.log('Customer info confirmed');

        // Update state
        customerInfoConfirmed = true;
        confirmTimestamp = new Date().toISOString();

        // Store in session storage
        sessionStorage.setItem('customerInfoConfirmed', 'true');
        sessionStorage.setItem('customerInfoTimestamp', confirmTimestamp);

        // Style the button as confirmed
        styleConfirmedButton();

        // Notify other scripts that customer info is confirmed
        notifyCustomerInfoConfirmed();

        console.log('Customer info confirmation complete at:', confirmTimestamp);
    }

    /**
     * Style the button to show confirmed state
     */
    function styleConfirmedButton() {
        const $button = $('#confirm-customer-info-btn');

        $button
            .addClass('confirmed')
            .html('<span>✓ Customer Info Confirmed</span>')
            .css({
                'background-color': '#28a745',
                'color': '#fff',
                'cursor': 'default',
                'opacity': '1'
            })
            .prop('disabled', true);

        console.log('Customer info button styled as confirmed');
    }

    /**
     * Show validation error message
     */
    function showValidationError() {
        const missingFields = JSON.parse(sessionStorage.getItem('customerInfoMissingFields') || '[]');

        alert('Please fill in all required customer information fields before confirming:\n\n' + missingFields.join(', '));
    }

    /**
     * Notify other scripts that customer info is confirmed
     * This triggers updates to the Moneris payment button state
     */
    function notifyCustomerInfoConfirmed() {
        // Trigger custom event
        $(document).trigger('customerInfoConfirmed', {
            timestamp: confirmTimestamp
        });

        console.log('Customer info confirmed event dispatched');
    }

    /**
     * Check if customer info is confirmed (for other scripts to use)
     */
    function isCustomerInfoConfirmed() {
        return customerInfoConfirmed || sessionStorage.getItem('customerInfoConfirmed') === 'true';
    }

    /**
     * Reset confirmation state (if customer changes info)
     */
    function resetConfirmation() {
        if (customerInfoConfirmed) {
            console.log('Customer info changed - resetting confirmation');

            customerInfoConfirmed = false;
            confirmTimestamp = null;

            sessionStorage.removeItem('customerInfoConfirmed');
            sessionStorage.removeItem('customerInfoTimestamp');

            // Reset button appearance
            const $button = $('#confirm-customer-info-btn');
            $button
                .removeClass('confirmed')
                .html('<span>Confirm Customer Info</span>')
                .css({
                    'background-color': '#0073aa',
                    'color': '#fff',
                    'cursor': 'pointer',
                    'opacity': '1'
                })
                .prop('disabled', false);

            // Notify other scripts
            $(document).trigger('customerInfoReset');
        }
    }

    /**
     * Listen for changes to customer info fields to reset confirmation
     */
    $(document).on('input change', '#billing_first_name, #billing_last_name, #billing_email, #billing_phone, #billing_address_1', function () {
        if (customerInfoConfirmed) {
            console.log('Customer info field changed - resetting confirmation');
            resetConfirmation();
        }
    });

    // Also listen for shipping address changes
    $(document).on('change', '#ship-to-different-checkbox', function () {
        if (customerInfoConfirmed) {
            console.log('Shipping checkbox changed - resetting confirmation');
            resetConfirmation();
        }
    });

    $(document).on('input', '#shipping-address-input', function () {
        if (customerInfoConfirmed) {
            console.log('Shipping address changed - resetting confirmation');
            resetConfirmation();
        }
    });

    /**
     * Public API for other scripts
     */
    window.CustomerInfoConfirm = {
        isConfirmed: function () {
            return isCustomerInfoConfirmed();
        },
        getTimestamp: function () {
            return confirmTimestamp || sessionStorage.getItem('customerInfoTimestamp');
        },
        reset: function () {
            resetConfirmation();
        }
    };

    console.log('Customer info confirm functionality initialized');
});