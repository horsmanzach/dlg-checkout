/**
 * Shipping Address Functionality for Checkout
 * Handles "Ship to Different Address?" checkbox and Google Maps integration
 * File: /js/shipping-address.js
 */

jQuery(document).ready(function ($) {
    console.log('Shipping Address: Initializing...');

    // Google Maps autocomplete object
    let shippingAutocomplete = null;

    // Initialize the shipping address functionality
    initShippingAddress();

    function initShippingAddress() {
        const $checkbox = $('#ship-to-different-checkbox');
        const $addressWrapper = $('.shipping-address-wrapper');
        const $addressInput = $('#shipping-address-input');

        if ($checkbox.length === 0) {
            console.warn('Shipping checkbox not found');
            return;
        }

        console.log('Shipping address checkbox found, initializing...');

        // Initialize Google Maps autocomplete
        initializeGoogleMapsAutocomplete();

        // Handle checkbox change
        $checkbox.on('change', function () {
            if ($(this).is(':checked')) {
                console.log('Ship to different address: CHECKED');
                $addressWrapper.slideDown(300);
                updateValidationMessage(true);
                updateCompletePaymentButton();
            } else {
                console.log('Ship to different address: UNCHECKED');
                $addressWrapper.slideUp(300);
                $addressInput.val('');
                clearShippingFields();
                updateValidationMessage(false);
                updateCompletePaymentButton();
            }
        });

        // Handle address input
        $addressInput.on('input', function () {
            console.log('Address input changed:', $(this).val());
            updateCompletePaymentButton();
        });

        // Initial state
        updateValidationMessage($checkbox.is(':checked'));
        updateCompletePaymentButton();
    }

    /**
     * Initialize Google Maps Autocomplete
     */
    function initializeGoogleMapsAutocomplete() {
        const addressInput = document.getElementById('shipping-address-input');

        if (!addressInput) {
            console.warn('Shipping address input not found');
            return;
        }

        if (typeof google !== 'undefined' && google.maps && google.maps.places) {
            shippingAutocomplete = new google.maps.places.Autocomplete(addressInput, {
                types: ['address'],
                componentRestrictions: { country: 'ca' }
            });

            shippingAutocomplete.setFields(['address_components', 'formatted_address']);

            // Listen for place selection
            shippingAutocomplete.addListener('place_changed', function () {
                const place = shippingAutocomplete.getPlace();
                console.log('Address selected from Google Maps:', place);

                if (place && place.address_components) {
                    parseAndPopulateShippingFields(place);
                    updateCompletePaymentButton();
                }
            });

            console.log('Google Maps autocomplete initialized for shipping address');
        } else {
            console.warn('Google Maps API not available');
        }
    }

    /**
     * Parse Google Maps address and populate WooCommerce shipping fields
     */
    function parseAndPopulateShippingFields(place) {
        const addressComponents = place.address_components;
        let streetNumber = '';
        let route = '';
        let city = '';
        let state = '';
        let postalCode = '';
        let country = '';

        // Extract address components
        addressComponents.forEach(function (component) {
            const types = component.types;

            if (types.includes('street_number')) {
                streetNumber = component.long_name;
            }
            if (types.includes('route')) {
                route = component.long_name;
            }
            if (types.includes('locality')) {
                city = component.long_name;
            }
            if (types.includes('administrative_area_level_1')) {
                state = component.short_name;
            }
            if (types.includes('postal_code')) {
                postalCode = component.short_name;
            }
            if (types.includes('country')) {
                country = component.short_name;
            }
        });

        // Construct full street address
        const streetAddress = (streetNumber + ' ' + route).trim();

        console.log('Parsed address:', {
            street: streetAddress,
            city: city,
            state: state,
            postal: postalCode,
            country: country
        });

        // Populate WooCommerce shipping fields
        $('#shipping_address_1').val(streetAddress);
        $('#shipping_city').val(city);
        $('#shipping_state').val(state).trigger('change');
        $('#shipping_postcode').val(postalCode);
        $('#shipping_country').val(country).trigger('change');

        console.log('WooCommerce shipping fields populated');
    }

    /**
     * Clear all shipping fields
     */
    function clearShippingFields() {
        $('#shipping_address_1').val('');
        $('#shipping_city').val('');
        $('#shipping_state').val('').trigger('change');
        $('#shipping_postcode').val('');
        $('#shipping_country').val('CA').trigger('change'); // Default to Canada
        console.log('Shipping fields cleared');
    }

    /**
     * Update validation message display
     */
    function updateValidationMessage(isCheckboxChecked) {
        const $validationContainer = $('.validation-requirement-message');
        let $shippingValidation = $validationContainer.find('.shipping-address-validation');

        if (isCheckboxChecked) {
            // Show validation message
            if ($shippingValidation.length === 0) {
                // Create validation message if it doesn't exist
                const validationHtml = `
                    <div class="validation-item shipping-address-validation">
                        <span class="validation-icon">✗</span>
                        <span class="validation-text">Shipping address must be populated</span>
                    </div>
                `;
                $validationContainer.append(validationHtml);
                $shippingValidation = $validationContainer.find('.shipping-address-validation');
            }
            $shippingValidation.show();
        } else {
            // Hide validation message
            if ($shippingValidation.length > 0) {
                $shippingValidation.hide();
            }
        }
    }

    /**
     * Update validation message state (red X or green checkmark)
     */
    function updateValidationState() {
        const $checkbox = $('#ship-to-different-checkbox');
        const $addressInput = $('#shipping-address-input');
        const $shippingValidation = $('.shipping-address-validation');

        if ($checkbox.is(':checked')) {
            const address = $addressInput.val().trim();
            const isValid = address.length >= 10; // Minimum valid address length

            if (isValid) {
                // Valid - green checkmark
                $shippingValidation.removeClass('invalid').addClass('valid');
                $shippingValidation.find('.validation-icon').text('✓');
            } else {
                // Invalid - red X
                $shippingValidation.removeClass('valid').addClass('invalid');
                $shippingValidation.find('.validation-icon').text('✗');
            }
        }
    }

    /**
     * Check if shipping address is valid
     */
    function isShippingAddressValid() {
        const $checkbox = $('#ship-to-different-checkbox');
        const $addressInput = $('#shipping-address-input');

        // If checkbox is unchecked, shipping validation not required
        if (!$checkbox.is(':checked')) {
            return true;
        }

        // If checkbox is checked, address must be populated
        const address = $addressInput.val().trim();
        return address.length >= 10;
    }

    /**
     * Update Complete Payment button state
     */
    function updateCompletePaymentButton() {
        const $completeButton = $('#place_order, .complete-payment-btn');

        // Check all three validations
        const monthlyBillingValid = checkMonthlyBillingValid();
        const termsChecked = checkTermsChecked();
        const shippingValid = isShippingAddressValid();

        console.log('Validation states:', {
            monthlyBilling: monthlyBillingValid,
            terms: termsChecked,
            shipping: shippingValid
        });

        // Update validation state visual
        updateValidationState();

        // Enable button only if all validations pass
        if (monthlyBillingValid && termsChecked && shippingValid) {
            $completeButton.removeClass('disabled').prop('disabled', false);
            console.log('Complete Payment button ENABLED');
        } else {
            $completeButton.addClass('disabled').prop('disabled', true);
            console.log('Complete Payment button DISABLED');
        }
    }

    /**
     * Check if monthly billing is validated
     * This checks the existing monthly billing validation
     */
    function checkMonthlyBillingValid() {
        // Check if monthly billing has been confirmed
        const confirmed = $('input[name="monthly_billing_confirmed"]').val();
        return confirmed === '1';
    }

    /**
     * Check if terms checkbox is checked
     */
    function checkTermsChecked() {
        return $('#terms').is(':checked');
    }

    /**
     * Listen for changes to other validation requirements
     */
    $(document).on('change', 'input[name="monthly_billing_confirmed"], #terms', function () {
        console.log('Other validation changed');
        updateCompletePaymentButton();
    });

    // Listen for monthly billing validation events
    $(document).on('monthly_billing_validated', function () {
        console.log('Monthly billing validated event received');
        updateCompletePaymentButton();
    });

    // Also listen for checkout update events
    $(document.body).on('updated_checkout', function () {
        console.log('Checkout updated');
        updateCompletePaymentButton();
    });

    console.log('Shipping address functionality initialized');
});