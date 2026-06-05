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

        // Container priority order — more specific targets first.
        // '.woocommerce-billing-fields' is intentionally excluded: inserting
        // after that element disrupts Divi's nth-child float layout and breaks
        // the two-column field arrangement.
        const possibleContainers = [
            '.shipping-address-checkbox-container',
            '.customshipping-address-checkout-container',
            '#shipping-address-container',
            '.woocommerce-shipping-fields',
            '.woocommerce-billing-fields__field-wrapper'
        ];

        // Wait for ANY of these containers to be available
        const checkContainer = setInterval(function () {
            let $container = null;

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
                console.log('Container not found yet. Available containers:',
                    $('div[class*="shipping"], div[class*="billing"], div[class*="checkout"]')
                        .map(function () { return this.className; })
                        .get()
                        .slice(0, 10)
                );
            }
        }, 100);

        // Timeout after 10 seconds
        setTimeout(function () {
            clearInterval(checkContainer);

            if ($('#confirm-customer-info-btn').length === 0) {
                console.error('Failed to find container for confirm button after 10 seconds');
                console.log('All checkout containers:',
                    $('div[class*="checkout"], div[class*="shipping"], div[class*="billing"]')
                        .map(function () { return this.className; })
                        .get()
                );

                // FALLBACK: Insert after the entire billing fields section, not
                // the inner field wrapper, to stay outside the float context.
                const $fallback = $('.woocommerce-billing-fields');
                if ($fallback.length > 0) {
                    console.log('Using fallback container');
                    addConfirmButton($fallback);
                    setupButtonHandlers();
                }
            }
        }, 10000);
    }

    /**
    * Add the confirm customer info button below the target container.
    * 
    * If the matched container is the billing field wrapper, we walk up to the
    * parent .woocommerce-billing-fields section before inserting. This ensures
    * the button lands outside the floated field context so it cannot shift
    * Divi's nth-child column pairing for the visible billing fields.
    */
    function addConfirmButton($container) {
        const buttonHtml = `
            <div class="customer-info-confirm-wrapper woocommerce" style="margin-top: 20px; margin-bottom: 100px; text-align: right; clear: both;">
                <button type="button" id="confirm-customer-info-btn" class="button">
                    <span>CONFIRM CUSTOMER INFO</span>
                </button>
            </div>
        `;

        if ($container.hasClass('woocommerce-billing-fields__field-wrapper')) {
            // Walk up to the section wrapper so the button is injected after
            // the entire billing block, not inside its float context.
            $container.closest('.woocommerce-billing-fields').after(buttonHtml);
            console.log('Button inserted after .woocommerce-billing-fields (walked up from field-wrapper)');
        } else {
            $container.after(buttonHtml);
            console.log('Button inserted after:', $container.attr('class'));
        }

        // Verify insertion
        setTimeout(function () {
            const $addedButton = $('#confirm-customer-info-btn');
            if ($addedButton.length > 0) {
                console.log('✓ Button successfully added and found in DOM');
                console.log('Button parent:', $addedButton.parent().attr('class'));
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

        // Clear previous error styling
        $('#billing_first_name, #billing_last_name, #billing_email, #billing_phone, #billing_address_1, #billing_unit_number, #billing_buzzer_code, #shipping-address-input').removeClass('error-field');

        // First name
        const firstName = $('#billing_first_name').val() || '';
        if (firstName.trim().length === 0) {
            isValid = false;
            missingFields.push('First Name');
            $('#billing_first_name').addClass('error-field');
        }

        // Last name
        const lastName = $('#billing_last_name').val() || '';
        if (lastName.trim().length === 0) {
            isValid = false;
            missingFields.push('Last Name');
            $('#billing_last_name').addClass('error-field');
        }

        // Email
        const email = $('#billing_email').val() || '';
        if (email.trim().length === 0 || !isValidEmail(email)) {
            isValid = false;
            missingFields.push('Email (must be valid format)');
            $('#billing_email').addClass('error-field');
        }

        // Phone — must be exactly 10 digits
        const phone = $('#billing_phone').val() || '';
        const phoneDigits = phone.replace(/\D/g, '');

        if (phoneDigits.length === 0) {
            isValid = false;
            missingFields.push('Phone Number');
            $('#billing_phone').addClass('error-field');
        } else if (phoneDigits.length !== 10) {
            isValid = false;
            missingFields.push('Phone Number (must be exactly 10 digits)');
            $('#billing_phone').addClass('error-field');
        }

        // Service address
        const serviceAddress = $('#billing_address_1').val() || '';
        if (serviceAddress.trim().length === 0) {
            isValid = false;
            missingFields.push('Service Address');
            $('#billing_address_1').addClass('error-field');
        }

        // Shipping address (only if "ship to different address" is checked)
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
     * Confirm customer info and send to server
     */
    function confirmCustomerInfo() {
        console.log('Sending customer info to server...');

        const $button = $('#confirm-customer-info-btn');

        $button.prop('disabled', true)
            .html('<span>Confirming...</span>')
            .css('opacity', '0.6');

        const customerData = {
            action: 'confirm_customer_info',
            nonce: diallog_ajax.confirm_customer_info_nonce,
            first_name: $('#billing_first_name').val() || '',
            last_name: $('#billing_last_name').val() || '',
            email: $('#billing_email').val() || '',
            phone: $('#billing_phone').val() || '',
            unit_number: $('#billing_unit_number').val() || '',
            buzzer_code: $('#billing_buzzer_code').val() || '',
            special_shipping_instructions: $('#special-shipping-instructions-input').val() || '',
            service_address: $('#billing_address_1').val() || '',
            city: $('#billing_city').val() || '',
            province: $('#billing_state').val() || '',
            postal_code: $('#billing_postcode').val() || ''
        };

        // NOTE: CCD is retrieved from user meta in PHP, not from form field

        const $shippingCheckbox = $('#ship-to-different-checkbox');
        if ($shippingCheckbox.length > 0 && $shippingCheckbox.is(':checked')) {
            customerData.ship_to_different = 'true';
            customerData.shipping_address = $('#shipping-address-input').val() || '';
        }

        console.log('Customer data to send:', customerData);

        $.ajax({
            url: diallog_ajax.ajax_url,
            type: 'POST',
            data: customerData,
            success: function (response) {
                console.log('Customer info confirmation response:', response);

                if (response.success) {
                    customerInfoConfirmed = true;
                    confirmTimestamp = new Date().toISOString();

                    sessionStorage.setItem('customerInfoConfirmed', 'true');
                    sessionStorage.setItem('customerInfoTimestamp', confirmTimestamp);

                    styleConfirmedButton();
                    notifyCustomerInfoConfirmed();

                    console.log('✓ Customer info confirmed and sent to Diallog');

                    if (response.data.ccd_included === 'yes') {
                        console.log('✓ CCD included in submission');
                    }
                } else {
                    handleConfirmError(response.data.message || 'Failed to confirm customer info');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX error confirming customer info:', error);
                handleConfirmError('Network error: ' + error);
            }
        });
    }

    /**
     * Handle confirmation error
     */
    function handleConfirmError(errorMessage) {
        const $button = $('#confirm-customer-info-btn');

        $button.prop('disabled', false)
            .html('<span>CONFIRM CUSTOMER INFO</span>')
            .css('opacity', '1');

        alert('Error confirming customer information:\n\n' + errorMessage);
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
    */
    function notifyCustomerInfoConfirmed() {
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

            const $button = $('#confirm-customer-info-btn');
            $button
                .removeClass('confirmed')
                .html('<span>CONFIRM CUSTOMER INFO</span>')
                .css({
                    'background-color': '#0073aa',
                    'color': '#fff',
                    'cursor': 'pointer',
                    'opacity': '1'
                })
                .prop('disabled', false);

            $(document).trigger('customerInfoReset');
        }
    }

    /**
    * Listen for changes to customer info fields to reset confirmation
    */
    $(document).on('input change', '#billing_first_name, #billing_last_name, #billing_email, #billing_phone, #billing_address_1, #billing_unit_number, #billing_buzzer_code, #special-shipping-instructions-input', function () {
        if (customerInfoConfirmed) {
            console.log('Customer info field changed - resetting confirmation');
            resetConfirmation();
        }
    });

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