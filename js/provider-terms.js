/**
 * Provider Terms and Conditions Dynamic Display
 * File: js/provider-terms.js
 * 
 * Handles showing/hiding provider-specific terms modules based on cart contents
 */

jQuery(document).ready(function ($) {

    // Initialize provider terms functionality
    initializeProviderTerms();

    /**
     * Initialize the provider terms display logic
     */
    function initializeProviderTerms() {
        console.log('Initializing Provider Terms functionality...');

        // Check if we have provider terms data from PHP
        if (typeof providerTermsData === 'undefined') {
            console.log('Provider terms data not available');
            return;
        }

        // Only run on checkout page
        if (!providerTermsData.isCheckout) {
            console.log('Not on checkout page, skipping provider terms');
            return;
        }

        // Show the appropriate provider terms module
        showProviderTermsModule(providerTermsData.providerClass);

        // Optional: Listen for cart updates and refresh terms display
        setupCartUpdateListeners();

        console.log('Provider Terms functionality initialized');
    }

    /**
     * Show the appropriate provider terms module
     * @param {string} providerClass - CSS class of the module to show
     */
    function showProviderTermsModule(providerClass) {
        // All possible provider terms module classes
        const allProviderClasses = [
            'bell-terms-module',
            'rogers-terms-module',
            'cogeco-terms-module',
            'telus-terms-module',
            'shaw-terms-module'
        ];

        // Hide all provider terms modules first
        allProviderClasses.forEach(function (className) {
            $('.' + className).removeClass('visible').hide();
        });

        // Show the specific provider module if we have one
        if (providerClass && providerClass !== '') {
            $('.' + providerClass).addClass('visible').show();
            console.log('Showing terms module:', providerClass);
        } else {
            console.log('No provider terms module to show');
        }
    }

    /**
     * Optional: Setup listeners for cart updates
     * This will refresh the terms display if the cart changes
     */
    function setupCartUpdateListeners() {
        // Listen for WooCommerce cart updates
        $(document.body).on('updated_wc_div', function () {
            console.log('Cart updated, refreshing provider terms...');
            refreshProviderTerms();
        });

        // Listen for checkout updates
        $(document.body).on('checkout_error', function () {
            console.log('Checkout error, refreshing provider terms...');
            refreshProviderTerms();
        });

        // Listen for payment method changes (in case it affects terms)
        $(document.body).on('payment_method_selected', function () {
            console.log('Payment method changed, refreshing provider terms...');
            refreshProviderTerms();
        });
    }

    /**
     * Refresh provider terms via AJAX call
     * Use this if you need real-time updates when cart changes
     */
    function refreshProviderTerms() {
        $.ajax({
            url: '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: {
                action: 'get_provider_terms_class'
            },
            success: function (response) {
                if (response.success) {
                    showProviderTermsModule(response.data.providerClass);
                    console.log('Provider terms refreshed:', response.data.providerClass);
                } else {
                    console.log('Failed to refresh provider terms');
                }
            },
            error: function () {
                console.log('AJAX error refreshing provider terms');
            }
        });
    }

    /**
     * Public function to manually refresh terms (if needed)
     */
    window.refreshProviderTerms = refreshProviderTerms;

});