/**
 * Address Lookup JavaScript - Client-Side Geocoding
 */
jQuery(document).ready(function ($) {
    console.log('Address lookup script loading...');

    /**
     * Parse Google geocoding result into the same format as autocomplete
     */
    function parseGeocodingResult(result) {
        const components = result.address_components;

        const searched_address = {
            'street_number': '',
            'route': '',
            'street_name': '',
            'street_type': '',
            'street_dir': '',
            'sublocality_level_1': '',
            'locality': '',
            'administrative_area_level_2': '',
            'administrative_area_level_1': '',
            'country': '',
            'postal_code': '',
            'manual_search': 0
        };

        // Extract components from Google's response
        components.forEach(function (component) {
            const types = component.types;
            const longName = component.long_name;
            const shortName = component.short_name;

            if (types.includes('street_number')) {
                searched_address.street_number = shortName;
            }
            if (types.includes('route')) {
                searched_address.route = longName;
            }
            if (types.includes('sublocality_level_1')) {
                searched_address.sublocality_level_1 = longName;
            }
            if (types.includes('locality')) {
                searched_address.locality = longName;
            }
            if (types.includes('administrative_area_level_2')) {
                searched_address.administrative_area_level_2 = longName;
            }
            if (types.includes('administrative_area_level_1')) {
                searched_address.administrative_area_level_1 = shortName;
            }
            if (types.includes('country')) {
                searched_address.country = longName;
            }
            if (types.includes('postal_code')) {
                searched_address.postal_code = shortName;
            }
        });

        return searched_address;
    }

    /**
     * Send the geocoded address to the server
     */
    function sendToServerWithGeocodedAddress(streetAddress, unitNumber, buzzerCode, geocodedAddress) {
        console.log('Sending to server:', { streetAddress, unitNumber, buzzerCode, geocodedAddress });

        $.ajax({
            url: ajax_object ? ajax_object.ajax_url : '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: {
                action: 'find_address_with_redirect',
                streetAddress: streetAddress,
                unitNumber: unitNumber,
                buzzerCode: buzzerCode,
                redirect_to_plans: 'true',
                geocoded_address: geocodedAddress
            },
            success: function (response) {
                console.log('=== AJAX SUCCESS RESPONSE ===');
                console.log('Full response:', response);

                if (response.success === true && response.data && response.data.redirect === true) {
                    console.log('SUCCESS! Redirecting to:', response.data.redirect_url);

                    showSuccess('Address found! Redirecting to plans...');

                    setTimeout(function () {
                        $('#plan-building-wizard-modal').modal('hide');
                        setTimeout(function () {
                            window.location.href = response.data.redirect_url;
                        }, 500);
                    }, 1000);

                } else if (response.success === false) {
                    console.log('Address lookup failed:', response.data);
                    const errorMessage = (response.data && response.data.message) || 'Address not found or not serviceable.';
                    showError(errorMessage);
                    resetButton();
                } else {
                    console.log('Unexpected response format:', response);
                    showError('An unexpected error occurred. Please try again.');
                    resetButton();
                }
            },
            error: function (xhr, status, error) {
                console.error('=== AJAX ERROR ===');
                console.error('Status:', status);
                console.error('Error:', error);
                console.error('Response Text:', xhr.responseText);

                let errorMessage = 'Network error occurred. Please try again.';

                try {
                    const errorResponse = JSON.parse(xhr.responseText);
                    if (errorResponse.data && errorResponse.data.message) {
                        errorMessage = errorResponse.data.message;
                    }
                } catch (e) {
                    // Use default error message
                }

                showError(errorMessage);
                resetButton();
            }
        });
    }

    /**
     * Reset the button state
     */
    function resetButton() {
        $('.checkAvailabilityBtn').attr('disabled', false)
            .removeClass('checking')
            .html('Check Availability');
        window.addressLookupShouldRedirect = false;
    }

    /**
     * Show success message
     */
    function showSuccess(message) {
        console.log('Showing success:', message);

        $('.address-success-message, .address-error-message').remove();

        const successHtml = `
            <div class="address-success-message alert alert-success" style="margin-top: 15px; padding: 15px; background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; color: #155724;">
                <strong>Success!</strong> ${message}
            </div>
        `;

        $('#plan-building-wizard-modal .modal-body').append(successHtml);
    }

    /**
     * Show error message
     */
    function showError(message) {
        console.log('Showing error:', message);

        $('.address-error-message, .address-success-message').remove();

        const errorHtml = `
            <div class="address-error-message alert alert-danger" style="margin-top: 15px; padding: 15px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24;">
                <strong>Error:</strong> ${message}
            </div>
        `;

        $('#plan-building-wizard-modal .modal-body').append(errorHtml);

        setTimeout(function () {
            $('.address-error-message').fadeOut();
        }, 10000);
    }

    /**
     * Perform address lookup with redirect - CLIENT-SIDE GEOCODING
     */
    function performRedirectAddressLookup() {
        console.log('Performing redirect address lookup...');

        // Get the street address from the Google autocomplete field
        const streetAddress = $('#ssadd').val();
        const unitNumber = $('#unitNumber').val() || '';
        const buzzerCode = $('#buzzerCode').val() || '';

        console.log('Address data:', { streetAddress, unitNumber, buzzerCode });

        if (!streetAddress || streetAddress.trim() === '') {
            showError('Please enter a valid street address using the autocomplete suggestions.');
            return;
        }

        // Show loading
        $('.checkAvailabilityBtn').attr('disabled', true)
            .html('<i class="fa fa-spinner fa-pulse"></i> Checking...')
            .addClass('checking');

        // Use Google Maps Geocoding Service (client-side)
        if (typeof google !== 'undefined' && google.maps && google.maps.Geocoder) {
            console.log('Using Google Maps Geocoder...');

            const geocoder = new google.maps.Geocoder();

            geocoder.geocode({
                address: streetAddress,
                componentRestrictions: { country: 'CA' }
            }, function (results, status) {

                if (status === 'OK' && results[0]) {
                    console.log('Geocoding successful:', results[0]);

                    // Parse the geocoded result into the same format as autocomplete
                    const geocodedAddress = parseGeocodingResult(results[0]);
                    console.log('Parsed geocoded address:', geocodedAddress);

                    // Send to server with the properly formatted address components
                    sendToServerWithGeocodedAddress(streetAddress, unitNumber, buzzerCode, geocodedAddress);

                } else {
                    console.error('Geocoding failed:', status);
                    showError('Could not validate the address. Please try selecting a different address from the autocomplete suggestions.');
                    resetButton();
                }
            });

        } else {
            console.error('Google Maps API not available');
            showError('Address validation service not available. Please try again.');
            resetButton();
        }
    }

    /**
     * Initialize address display shortcode functionality
     */
    function initializeAddressDisplayShortcode() {
        console.log('Looking for address display containers...');

        const $containers = $('.internet-packages-location-button-group[data-redirect-to-plans="true"]');
        console.log('Found containers:', $containers.length);

        if ($containers.length === 0) {
            console.log('No containers found - shortcode may not be displaying');
            return;
        }

        $containers.each(function () {
            const $container = $(this);
            const $button = $container.find('.plan_check_other_availability_btn');
            console.log('Setting up button for container, button found:', $button.length);

            $button.off('click.address-redirect');

            $button.on('click.address-redirect', function (e) {
                e.preventDefault();
                e.stopPropagation();

                console.log('Address lookup button clicked from shortcode');
                window.addressLookupShouldRedirect = true;
                $('#plan-building-wizard-modal').modal('show');

                return false;
            });
        });
    }

    /**
     * Override checkAvailability for redirect behavior
     */
    function setupCheckAvailabilityOverride() {
        if (typeof window.checkAvailability !== 'function') {
            console.log('checkAvailability not found, retrying...');
            setTimeout(setupCheckAvailabilityOverride, 500);
            return;
        }

        console.log('Setting up checkAvailability override...');

        if (!window.original_checkAvailability) {
            window.original_checkAvailability = window.checkAvailability;

            window.checkAvailability = function () {
                console.log('checkAvailability called, should redirect:', window.addressLookupShouldRedirect);

                if (window.addressLookupShouldRedirect) {
                    console.log('Using redirect-enabled address lookup');
                    performRedirectAddressLookup();
                    return;
                }

                return window.original_checkAvailability();
            };

            console.log('checkAvailability successfully overridden');
        }
    }

    /**
     * NEW FUNCTION: Initialize Google Maps Autocomplete for #ssadd field
     */
    function initializeGoogleMapsForAddressLookup() {
        console.log('Checking for #ssadd field...');

        const ssaddField = document.getElementById('ssadd');

        if (!ssaddField) {
            console.log('#ssadd field not found yet, will retry on modal show');
            return false;
        }

        if (typeof google === 'undefined' || !google.maps || !google.maps.places) {
            console.error('Google Maps API not loaded');
            return false;
        }

        // Call the existing initAutocomplete function from d-main.js
        if (typeof window.initAutocomplete === 'function') {
            console.log('Calling initAutocomplete from d-main.js');
            window.initAutocomplete();
            return true;
        } else {
            console.error('initAutocomplete function not found');
            return false;
        }
    }

    // Initialize everything
    console.log('Initializing address lookup...');
    initializeAddressDisplayShortcode();
    setupCheckAvailabilityOverride();

    // Set up modal event listeners to initialize autocomplete
    $('#plan-building-wizard-modal').on('shown.bs.modal', function () {
        console.log('Modal shown, initializing Google Maps autocomplete...');

        // Wait a moment for the modal content to fully render
        setTimeout(function () {
            const initialized = initializeGoogleMapsForAddressLookup();
            if (!initialized) {
                // Retry once more if it failed
                setTimeout(initializeGoogleMapsForAddressLookup, 500);
            }
        }, 300);
    });

    // Clean up on modal close
    $('#plan-building-wizard-modal').on('hidden.bs.modal', function () {
        $('.address-error-message, .address-success-message').remove();
        window.addressLookupShouldRedirect = false;
    });

    console.log('Address lookup script initialized for client-side geocoding');
});