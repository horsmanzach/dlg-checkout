/**
 * Simple Working Address Lookup JavaScript
 * Save this as /js/address-lookup.js in your theme directory
 */
jQuery(document).ready(function ($) {
    console.log('Simple address lookup script loading...');

    // TEST MODE - set to true to test redirect without address processing
    const TEST_MODE = false;

    /**
     * Initialize address display shortcode functionality
     */
    function initializeAddressDisplayShortcode() {
        console.log('Looking for address display containers...');

        // Find shortcode containers that need redirect behavior
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

            // Remove any existing handlers
            $button.off('click.address-redirect');

            // Add our click handler
            $button.on('click.address-redirect', function (e) {
                e.preventDefault();
                e.stopPropagation();

                console.log('Address lookup button clicked from shortcode');

                if (TEST_MODE) {
                    // Test mode - just test the redirect
                    testRedirect();
                    return false;
                }

                // Set flags for redirect behavior
                window.addressLookupShouldRedirect = true;

                // Open the modal
                $('#plan-building-wizard-modal').modal('show');

                return false;
            });
        });
    }

    /**
     * Test redirect function
     */
    function testRedirect() {
        console.log('Testing redirect functionality...');

        $.ajax({
            url: ajax_object ? ajax_object.ajax_url : '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: {
                action: 'test_redirect_simple'
            },
            success: function (response) {
                console.log('Test response:', response);
                if (response.success && response.data.redirect) {
                    console.log('Test successful, redirecting...');
                    window.location.href = response.data.redirect_url;
                }
            },
            error: function (xhr, status, error) {
                console.error('Test failed:', { status, error });
            }
        });
    }

    /**
     * Override checkAvailability for redirect behavior
     */
    function setupCheckAvailabilityOverride() {
        // Wait for d-main.js to load
        if (typeof window.checkAvailability !== 'function') {
            console.log('checkAvailability not found, retrying...');
            setTimeout(setupCheckAvailabilityOverride, 500);
            return;
        }

        console.log('Setting up checkAvailability override...');

        // Store original function
        if (!window.original_checkAvailability) {
            window.original_checkAvailability = window.checkAvailability;

            // Override with our version
            window.checkAvailability = function () {
                console.log('checkAvailability called, should redirect:', window.addressLookupShouldRedirect);

                if (window.addressLookupShouldRedirect) {
                    console.log('Using redirect-enabled address lookup');
                    performRedirectAddressLookup();
                    return;
                }

                // Call original function
                return window.original_checkAvailability();
            };

            console.log('checkAvailability successfully overridden');
        }
    }

    /**
     * Perform address lookup with redirect
     */
    function performRedirectAddressLookup() {
        console.log('Performing redirect address lookup...');

        // Get the street address from the modal
        const streetAddress = $('#ssadd').val();
        const unitNumber = $('#unitNumber').val() || '';
        const buzzerCode = $('#buzzerCode').val() || '';

        console.log('Address data:', { streetAddress, unitNumber, buzzerCode });

        if (!streetAddress || streetAddress.trim() === '') {
            showError('Please enter a valid street address.');
            return;
        }

        // Show loading
        $('.checkAvailabilityBtn').attr('disabled', true)
            .html('<i class="fa fa-spinner fa-pulse"></i> Checking...')
            .addClass('checking');

        // Make AJAX request
        $.ajax({
            url: ajax_object ? ajax_object.ajax_url : '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: {
                action: 'find_address_with_redirect',
                streetAddress: streetAddress,
                unitNumber: unitNumber,
                buzzerCode: buzzerCode,
                redirect_to_plans: 'true'
            },
            success: function (response) {
                console.log('Address lookup response:', response);

                if (response.success && response.data.redirect) {
                    console.log('Success! Redirecting to:', response.data.redirect_url);

                    // Close modal and redirect
                    $('#plan-building-wizard-modal').modal('hide');
                    setTimeout(function () {
                        window.location.href = response.data.redirect_url;
                    }, 300);

                } else if (response.success === false) {
                    console.log('Address lookup failed:', response.data.message);
                    showError(response.data.message || 'Address not found.');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX error:', { status, error, response: xhr.responseText });
                showError('Network error occurred. Please try again.');
            },
            complete: function () {
                // Reset button
                $('.checkAvailabilityBtn').attr('disabled', false)
                    .removeClass('checking')
                    .html('Check Availability');

                // Reset flag
                window.addressLookupShouldRedirect = false;
            }
        });
    }

    /**
     * Show error message
     */
    function showError(message) {
        console.log('Showing error:', message);

        $('.address-error-message').remove();

        const errorHtml = `
            <div class="address-error-message alert alert-danger" style="margin-top: 15px;">
                <strong>Error:</strong> ${message}
            </div>
        `;

        $('#plan-building-wizard-modal .modal-body').append(errorHtml);

        setTimeout(function () {
            $('.address-error-message').fadeOut();
        }, 5000);
    }

    // Initialize everything
    console.log('Initializing address lookup...');
    initializeAddressDisplayShortcode();
    setupCheckAvailabilityOverride();

    // Clean up on modal close
    $('#plan-building-wizard-modal').on('hidden.bs.modal', function () {
        $('.address-error-message').remove();
        window.addressLookupShouldRedirect = false;
    });

    console.log('Address lookup script initialized');
});