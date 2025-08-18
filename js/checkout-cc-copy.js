/**
 * Credit Card Copy Functionality for New Checkout Template
 * File: js/checkout-cc-copy.js
 */

jQuery(document).ready(function ($) {
    console.log('Checkout CC Copy: Initializing...');

    // Initialize the "Same Credit Card Info" functionality
    initSameCreditCardButton();

    function initSameCreditCardButton() {
        // Add the button to the Moneris payment form
        addSameCCButtonToMonerisForm();

        // Set up event listeners
        setupEventListeners();

        // Initial button state check
        updateButtonState();

        console.log('Same Credit Card Info functionality initialized');
    }

    function addSameCCButtonToMonerisForm() {
        // Check if Moneris form exists and button hasn't been added yet
        if ($('#moneris-payment-form').length > 0 && $('#same-cc-info-btn').length === 0) {

            // Find the postal code field in the Moneris form
            const postalCodeField = $('#moneris_postal_code').closest('.moneris-form-row');

            if (postalCodeField.length > 0) {
                // Add the button after the postal code field
                const buttonHTML = `
                    <div class="moneris-form-row same-cc-button-row">
                        <button type="button" id="same-cc-info-btn" class="same-cc-btn" disabled>
                            <span class="cc-icon">💳</span> Same Credit Card Info
                        </button>
                        <small class="same-cc-help-text">
                            Complete monthly billing credit card validation first to enable this option
                        </small>
                    </div>
                `;

                postalCodeField.after(buttonHTML);
                console.log('Same Credit Card Info button added to Moneris form');
            }
        }
    }

    function setupEventListeners() {
        // Monitor monthly billing credit card validation
        $(document).on('click', '.validate-card-btn', function () {
            // Small delay to allow validation to complete
            setTimeout(updateButtonState, 1000);
        });

        // Monitor monthly billing field changes
        $(document).on('input change',
            '#cc_cardholder_name, #cc_card_number, #cc_expiry, #cc_cvv, #cc_postal_code',
            function () {
                // Update button state when fields change
                setTimeout(updateButtonState, 500);
            }
        );

        // Monitor monthly payment method selection
        $(document).on('change', 'input[name="monthly_payment_method"]', function () {
            updateButtonState();
        });

        // Same Credit Card Info button click handler
        $(document).on('click', '#same-cc-info-btn', function (e) {
            e.preventDefault();
            copyCreditCardInfo();
        });

        // Monitor for successful monthly billing validation messages
        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.type === 'childList') {
                    const addedNodes = Array.from(mutation.addedNodes);
                    addedNodes.forEach(function (node) {
                        if (node.nodeType === 1 &&
                            (node.classList.contains('cc-validation-message') ||
                                $(node).find('.cc-validation-message').length > 0)) {
                            setTimeout(updateButtonState, 500);
                        }
                    });
                }
            });
        });

        // Start observing validation message container
        const validationContainer = document.querySelector('.cc-validation-message');
        if (validationContainer) {
            observer.observe(validationContainer.parentNode, {
                childList: true,
                subtree: true
            });
        }
    }

    function updateButtonState() {
        const button = $('#same-cc-info-btn');
        const helpText = $('.same-cc-help-text');

        if (button.length === 0) return;

        // Check if credit card option is selected in monthly billing
        const isCCSelected = $('input[name="monthly_payment_method"][value="cc"]').is(':checked');

        if (!isCCSelected) {
            button.prop('disabled', true).removeClass('enabled');
            helpText.text('Select Credit Card in monthly billing section first');
            return;
        }

        // Check if all monthly billing CC fields are filled
        const monthlyFields = {
            name: $('#cc_cardholder_name').val()?.trim(),
            number: $('#cc_card_number').val()?.trim(),
            expiry: $('#cc_expiry').val()?.trim(),
            cvv: $('#cc_cvv').val()?.trim(),
            postal: $('#cc_postal_code').val()?.trim()
        };

        const allFieldsFilled = Object.values(monthlyFields).every(value => value && value.length > 0);

        if (!allFieldsFilled) {
            button.prop('disabled', true).removeClass('enabled');
            helpText.text('Complete all monthly billing credit card fields first');
            return;
        }

        // Check for validation success (look for success message or lack of error styling)
        const hasErrors = $('#cc-content .field-error-message:visible').length > 0 ||
            $('#cc-content input.error, #cc-content input.flash_error').length > 0;

        const hasSuccessMessage = $('.cc-validation-message:visible').text().toLowerCase().includes('success') ||
            $('.cc-validation-message:visible').text().toLowerCase().includes('valid');

        if (hasErrors && !hasSuccessMessage) {
            button.prop('disabled', true).removeClass('enabled');
            helpText.text('Fix validation errors in monthly billing first');
            return;
        }

        // If we get here, enable the button
        button.prop('disabled', false).addClass('enabled');
        helpText.text('Click to copy validated credit card info to upfront payment');

        console.log('Same CC button enabled - monthly billing validated');
    }

    function copyCreditCardInfo() {
        // Get monthly billing credit card data
        const monthlyData = {
            name: $('#cc_cardholder_name').val()?.trim(),
            number: $('#cc_card_number').val()?.trim(),
            expiry: $('#cc_expiry').val()?.trim(),
            cvv: $('#cc_cvv').val()?.trim(),
            postal: $('#cc_postal_code').val()?.trim()
        };

        // Validate that we have data to copy
        if (!Object.values(monthlyData).every(value => value && value.length > 0)) {
            alert('Error: Monthly billing credit card information is incomplete.');
            return;
        }

        // Copy to Moneris payment form fields
        $('#moneris_cardholder_name').val(monthlyData.name);
        $('#moneris_card_number').val(monthlyData.number);
        $('#moneris_expiry_date').val(monthlyData.expiry);
        $('#moneris_cvv').val(monthlyData.cvv);
        $('#moneris_postal_code').val(monthlyData.postal);

        // Trigger change events to ensure any validation/formatting runs
        $('#moneris_cardholder_name').trigger('change');
        $('#moneris_card_number').trigger('change');
        $('#moneris_expiry_date').trigger('change');
        $('#moneris_cvv').trigger('change');
        $('#moneris_postal_code').trigger('change');

        // Show success feedback
        showCopySuccessMessage();

        console.log('Credit card information copied from monthly billing to upfront payment');
    }

    function showCopySuccessMessage() {
        // Create or update success message
        let successMsg = $('.cc-copy-success-message');

        if (successMsg.length === 0) {
            successMsg = $(`
                <div class="cc-copy-success-message" style="
                    background: #d4edda;
                    color: #155724;
                    border: 1px solid #c3e6cb;
                    border-radius: 4px;
                    padding: 8px 12px;
                    margin: 10px 0;
                    font-size: 14px;
                ">
                    ✓ Credit card information copied successfully!
                </div>
            `);

            $('.same-cc-button-row').after(successMsg);
        } else {
            successMsg.show();
        }

        // Auto-hide after 3 seconds
        setTimeout(() => {
            successMsg.fadeOut();
        }, 3000);
    }

    // Re-initialize if Moneris form is loaded dynamically
    const formObserver = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.type === 'childList') {
                const addedNodes = Array.from(mutation.addedNodes);
                addedNodes.forEach(function (node) {
                    if (node.nodeType === 1 &&
                        (node.id === 'moneris-payment-form' ||
                            $(node).find('#moneris-payment-form').length > 0)) {
                        setTimeout(() => {
                            addSameCCButtonToMonerisForm();
                            updateButtonState();
                        }, 100);
                    }
                });
            }
        });
    });

    // Start observing for dynamic form loading
    formObserver.observe(document.body, {
        childList: true,
        subtree: true
    });
});

