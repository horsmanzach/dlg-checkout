/**
 * Shipping Address Functionality for Checkout - INTEGRATED VERSION
 * Handles "Ship to Different Address?" checkbox and Google Maps integration
 * File: /js/shipping-address.js
 * 
 * FIXED: Now properly communicates with confirm-terms.js for coordinated validation
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

                // Notify other scripts that shipping validation is now required
                notifyShippingValidationChanged();
            } else {
                console.log('Ship to different address: UNCHECKED');
                $addressWrapper.slideUp(300);
                $addressInput.val('');
                clearShippingFields();

                // Notify other scripts that shipping validation is no longer required
                notifyShippingValidationChanged();
            }
        });

        // Handle address input
        $addressInput.on('input', function () {
            console.log('Address input changed:', $(this).val());

            // Notify other scripts about the validation state change
            notifyShippingValidationChanged();
        });

        // Initial state
        notifyShippingValidationChanged();
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

        if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
            console.warn('Google Maps API not loaded');
            return;
        }

        console.log('Initializing Google Maps Autocomplete for shipping address');

        // Restrict to Canada
        const options = {
            componentRestrictions: { country: 'ca' },
            fields: ['address_components', 'formatted_address', 'geometry']
        };

        try {
            shippingAutocomplete = new google.maps.places.Autocomplete(addressInput, options);

            shippingAutocomplete.addListener('place_changed', function () {
                const place = shippingAutocomplete.getPlace();
                console.log('Google place selected:', place);

                if (!place.address_components) {
                    console.warn('No address components available');
                    return;
                }

                // Store full address
                const fullAddress = place.formatted_address || '';
                $('#shipping-address-full').val(fullAddress);
                $('#shipping-address-input').val(fullAddress);

                // Parse and populate WooCommerce fields
                parseAndPopulateAddress(place.address_components);

                // Notify other scripts about the validation state change
                notifyShippingValidationChanged();
            });

            console.log('Google Maps Autocomplete initialized successfully');
        } catch (error) {
            console.error('Error initializing Google Maps Autocomplete:', error);
        }
    }

    /**
     * Parse Google address components and populate WooCommerce fields
     */
    function parseAndPopulateAddress(addressComponents) {
        let streetNumber = '';
        let route = '';
        let city = '';
        let state = '';
        let postalCode = '';
        let country = '';

        addressComponents.forEach(function (component) {
            const types = component.types;

            if (types.includes('street_number')) {
                streetNumber = component.short_name;
            }
            if (types.includes('route')) {
                route = component.short_name;
            }
            if (types.includes('locality')) {
                city = component.short_name;
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
     * Check if shipping address is valid
     * This is the authoritative check that other scripts will use
     */
    function isShippingAddressValid() {
        const $checkbox = $('#ship-to-different-checkbox');
        const $addressInput = $('#shipping-address-input');

        // If checkbox is unchecked, shipping validation not required
        if (!$checkbox.is(':checked')) {
            console.log('Shipping validation: PASSED (checkbox not checked)');
            return true;
        }

        // If checkbox is checked, address must be populated
        const address = $addressInput.val().trim();
        const isValid = address.length >= 10;

        console.log('Shipping validation:', isValid ? 'PASSED' : 'FAILED', '- Address length:', address.length);
        return isValid;
    }

    /**
     * FIX: Notify other scripts (especially confirm-terms.js) when shipping validation changes
     */
    function notifyShippingValidationChanged() {
        const isValid = isShippingAddressValid();

        console.log('Notifying other scripts - Shipping validation:', isValid);

        // Trigger custom event that confirm-terms.js listens for
        $(document).trigger('shippingValidationChanged', { isValid: isValid });

        // Also trigger the generic validation changed event
        $(document).trigger('monthly_billing_validated'); // This will cause button state to update
    }

    /**
     * Listen for changes to other validation requirements
     */
    $(document).on('change', 'input[name="monthly_billing_confirmed"], #terms', function () {
        console.log('Other validation changed - shipping checking if update needed');
        // Don't need to do anything here - confirm-terms.js will handle button updates
    });

    // Listen for monthly billing validation events
    $(document).on('monthly_billing_validated', function () {
        console.log('Monthly billing validated event received in shipping-address.js');
        // Don't need to do anything here - confirm-terms.js will handle button updates
    });

    // Also listen for checkout update events
    $(document.body).on('updated_checkout', function () {
        console.log('Checkout updated in shipping-address.js');
        notifyShippingValidationChanged();
    });

    // Listen for when monthly billing state changes
    $(document).on('monthlyBillingStateChanged', function () {
        console.log('Monthly billing state changed in shipping-address.js');
        // Don't need to do anything here - confirm-terms.js will handle button updates
    });

    /**
     * Public API for other scripts to check shipping validation
     */
    window.ShippingAddressValidation = {
        isValid: function () {
            return isShippingAddressValid();
        },
        isRequired: function () {
            return $('#ship-to-different-checkbox').is(':checked');
        },
        getAddress: function () {
            return $('#shipping-address-input').val().trim();
        },
        refresh: function () {
            notifyShippingValidationChanged();
        }
    };

    console.log('Shipping address functionality initialized');
});