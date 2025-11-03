/**
 * Shipping Address Functionality for Checkout - AJAX VERSION
 * Handles "Ship to Different Address?" checkbox and Google Maps integration
 * 
 * FIXED: Saves to session via AJAX before payment (doesn't rely on WooCommerce order creation)
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
                $('#shipping-address-full').val(''); // Clear hidden field

                // ADDED: Save empty state to session when unchecked
                saveShippingToSession();

                // Notify other scripts that shipping validation is no longer required
                notifyShippingValidationChanged();
            }
        });

        // Handle address input changes for validation
        $addressInput.on('input', function () {
            notifyShippingValidationChanged();
        });
    }

    /**
     * Initialize Google Maps Autocomplete for the shipping address field
     */
    function initializeGoogleMapsAutocomplete() {
        const addressInput = document.getElementById('shipping-address-input');

        if (!addressInput) {
            console.warn('Shipping address input field not found');
            return;
        }

        // Check if Google Maps API is loaded
        if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
            console.error('Google Maps API not loaded');
            return;
        }

        try {
            // Initialize autocomplete with Canadian addresses restriction
            shippingAutocomplete = new google.maps.places.Autocomplete(addressInput, {
                types: ['address'],
                componentRestrictions: { country: 'ca' }
            });

            // Listen for place selection
            shippingAutocomplete.addListener('place_changed', function () {
                const place = shippingAutocomplete.getPlace();

                if (!place.geometry) {
                    console.log('No geometry for selected place');
                    return;
                }

                // Get the formatted address from Google
                const fullAddress = place.formatted_address;

                // Store in visible input
                $('#shipping-address-input').val(fullAddress);

                // CRITICAL: Store in hidden field that gets submitted with checkout
                $('#shipping-address-full').val(fullAddress);

                console.log('Shipping address selected:', fullAddress);

                // ADDED: Save to session immediately via AJAX
                saveShippingToSession();

                // Notify other scripts about the validation state change
                notifyShippingValidationChanged();
            });

            console.log('Google Maps Autocomplete initialized successfully');
        } catch (error) {
            console.error('Error initializing Google Maps Autocomplete:', error);
        }
    }

    /**
     * NEW FUNCTION: Save shipping address to WooCommerce session via AJAX
     * This ensures the data is available on the Thank You page
     */
    function saveShippingToSession() {
        const $checkbox = $('#ship-to-different-checkbox');
        const $addressInput = $('#shipping-address-input');

        const shipToDifferent = $checkbox.is(':checked');
        const shippingAddress = $addressInput.val().trim();

        console.log('💾 Saving to session via AJAX:', {
            shipToDifferent: shipToDifferent,
            address: shippingAddress
        });

        // Make AJAX call to save to session
        $.ajax({
            url: shippingAddressVars.ajax_url,
            type: 'POST',
            data: {
                action: 'save_shipping_to_session',
                nonce: shippingAddressVars.nonce,
                ship_to_different: shipToDifferent,
                shipping_address: shippingAddress
            },
            success: function (response) {
                if (response.success) {
                    console.log('✓ Shipping saved to session:', response.data);
                } else {
                    console.error('✗ Failed to save shipping:', response.data);
                }
            },
            error: function (xhr, status, error) {
                console.error('✗ AJAX error saving shipping:', error);
            }
        });
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
     * Notify other scripts (especially confirm-terms.js) when shipping validation changes
     */
    function notifyShippingValidationChanged() {
        const isValid = isShippingAddressValid();

        console.log('Notifying other scripts - Shipping validation:', isValid);

        // Trigger custom event that confirm-terms.js listens for
        $(document).trigger('shippingValidationChanged', { isValid: isValid });

        // Also trigger the generic validation changed event
        $(document).trigger('monthly_billing_validated');
    }

    /**
     * NEW: Save to session before form submission (backup method)
     * This ensures data is saved even if the Google autocomplete save didn't fire
     */
    $(document).on('submit', 'form.checkout, form#moneris-payment-form', function (e) {
        console.log('📤 Form submission detected - ensuring shipping is saved...');

        const $checkbox = $('#ship-to-different-checkbox');

        if ($checkbox.is(':checked')) {
            const $addressInput = $('#shipping-address-input');
            const address = $addressInput.val().trim();

            // Make synchronous AJAX call to ensure data is saved before payment
            $.ajax({
                url: shippingAddressVars.ajax_url,
                type: 'POST',
                async: false, // CRITICAL: Wait for this to complete
                data: {
                    action: 'save_shipping_to_session',
                    nonce: shippingAddressVars.nonce,
                    ship_to_different: true,
                    shipping_address: address
                },
                success: function (response) {
                    console.log('✓ Final save before payment:', response);
                },
                error: function (xhr, status, error) {
                    console.error('✗ Failed final save before payment:', error);
                }
            });
        }
    });

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
        },
        // ADDED: Allow manual save trigger
        saveToSession: function () {
            saveShippingToSession();
        }
    };

    console.log('Shipping address functionality initialized');
});