/**
 * Credit Card Copy Functionality for New Checkout Template - CHECKBOX VERSION
 * File: js/checkout-cc-copy.js
 */

jQuery(document).ready(function ($) {
    console.log('Checkout CC Copy: Initializing...');

    // Initialize the "Same Credit Card Info" functionality
    initSameCreditCardCheckbox();

    function initSameCreditCardCheckbox() {
        // Add the checkbox to the Moneris payment form
        addSameCCCheckboxToMonerisForm();

        // Set up event listeners
        setupEventListeners();

        // Initial checkbox state check
        updateCheckboxState();

        console.log('Same Credit Card Info functionality initialized');
    }

    function addSameCCCheckboxToMonerisForm() {
        // Check if Moneris form exists and checkbox hasn't been added yet
        if ($('#moneris-payment-form').length > 0 && $('#same-cc-info-checkbox').length === 0) {

            // Find the message container (first element in the form) to insert checkbox before all form fields
            const messageContainer = $('#moneris-payment-form .moneris-message-container');

            if (messageContainer.length > 0) {
                // Add the checkbox right after the message container (before all form fields)
                const checkboxHTML = `
                    <div class="moneris-form-row same-cc-checkbox-row">
                        <input type="checkbox" id="same-cc-info-checkbox" class="same-cc-checkbox" disabled>
                        <div class="same-cc-text-content">
                            <div class="same-cc-checkbox-title">Copy Monthly Billing Credit Card Info</div>
                            <small class="same-cc-help-text">
                                Complete monthly billing credit card validation first to enable this option
                            </small>
                        </div>
                    </div>
                `;

                messageContainer.after(checkboxHTML);
                console.log('Same Credit Card Info checkbox added to top of Moneris form');
            } else {
                // Fallback: if message container not found, add at the beginning of the form
                const monerisForm = $('#moneris-payment-form');
                if (monerisForm.length > 0) {
                    const checkboxHTML = `
                        <div class="moneris-form-row same-cc-checkbox-row">
                            <input type="checkbox" id="same-cc-info-checkbox" class="same-cc-checkbox" disabled>
                            <div class="same-cc-text-content">
                                <div class="same-cc-checkbox-title">Copy Monthly Billing Credit Card Info</div>
                                <small class="same-cc-help-text">
                                    Complete monthly billing credit card validation first to enable this option
                                </small>
                            </div>
                        </div>
                    `;

                    monerisForm.prepend(checkboxHTML);
                    console.log('Same Credit Card Info checkbox added to top of Moneris form (fallback)');
                }
            }
        }
    }

    function setupEventListeners() {
        // Monitor monthly billing credit card validation
        $(document).on('click', '.validate-card-btn', function () {
            // Small delay to allow validation to complete
            setTimeout(updateCheckboxState, 1000);
        });

        // Monitor monthly billing field changes
        $(document).on('input change',
            '#cc_cardholder_name, #cc_card_number, #cc_expiry, #cc_cvv, #cc_postal_code',
            function () {
                // Update checkbox state when fields change
                setTimeout(updateCheckboxState, 500);
            }
        );

        // Monitor monthly payment method selection
        $(document).on('change', 'input[name="monthly_payment_method"]', function () {
            updateCheckboxState();
        });

        // Same Credit Card Info checkbox change handler
        $(document).on('change', '#same-cc-info-checkbox', function () {
            if ($(this).is(':checked')) {
                copyCreditCardInfo();
            }
        });

        // Make the entire checkbox row clickable
        $(document).on('click', '.same-cc-checkbox-row', function (e) {
            // Don't trigger if clicking directly on the checkbox (avoid double toggle)
            if (!$(e.target).is('#same-cc-info-checkbox')) {
                const checkbox = $('#same-cc-info-checkbox');
                if (!checkbox.prop('disabled')) {
                    checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
                }
            }
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
                            setTimeout(updateCheckboxState, 500);
                        }
                    });
                }
            });
        });

        // Start observing the document for changes
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    function updateCheckboxState() {
        const checkbox = $('#same-cc-info-checkbox');
        const helpText = $('.same-cc-help-text');
        const checkboxRow = $('.same-cc-checkbox-row');

        if (checkbox.length === 0) return;

        // Check if monthly payment method is set to credit card
        const monthlyPaymentMethod = $('input[name="monthly_payment_method"]:checked').val();
        if (monthlyPaymentMethod !== 'cc') {
            checkbox.prop('disabled', true).prop('checked', false).removeClass('enabled');
            checkboxRow.removeClass('success').addClass('disabled');
            helpText.text('Select credit card for monthly billing to enable this option');
            return;
        }

        // Check if all monthly billing fields are filled
        const requiredFields = ['#cc_cardholder_name', '#cc_card_number', '#cc_expiry', '#cc_cvv', '#cc_postal_code'];
        const allFieldsFilled = requiredFields.every(selector => {
            const value = $(selector).val();
            return value && value.trim().length > 0;
        });

        if (!allFieldsFilled) {
            checkbox.prop('disabled', true).prop('checked', false).removeClass('enabled');
            checkboxRow.removeClass('success').removeClass('disabled');
            helpText.text('Complete all monthly billing credit card fields first');
            return;
        }

        // Check for validation success (look for success message or lack of error styling)
        const hasErrors = $('#cc-content .field-error-message:visible').length > 0 ||
            $('#cc-content input.error, #cc-content input.flash_error').length > 0;

        const hasSuccessMessage = $('.cc-validation-message:visible').text().toLowerCase().includes('success') ||
            $('.cc-validation-message:visible').text().toLowerCase().includes('valid');

        if (hasErrors && !hasSuccessMessage) {
            checkbox.prop('disabled', true).prop('checked', false).removeClass('enabled');
            checkboxRow.removeClass('success').removeClass('disabled');
            helpText.text('Fix validation errors in monthly billing first');
            return;
        }

        // If we get here, enable the checkbox
        checkbox.prop('disabled', false).addClass('enabled');
        checkboxRow.removeClass('disabled').addClass('success');
        helpText.text('Check to copy validated credit card info to upfront payment');

        console.log('Same CC checkbox enabled - monthly billing validated');
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
            $('#same-cc-info-checkbox').prop('checked', false);
            return;
        }

        console.log('Copying credit card data:', {
            name: monthlyData.name,
            number: monthlyData.number.substring(0, 4) + '****', // Log partial number for security
            expiry: monthlyData.expiry,
            cvv: monthlyData.cvv ? '***' : 'MISSING', // Log if CVV exists
            postal: monthlyData.postal
        });

        // Copy to Moneris upfront payment fields
        const monerisFields = {
            '#moneris_cardholder_name': monthlyData.name,
            '#moneris_card_number': monthlyData.number,
            '#moneris_expiry_date': monthlyData.expiry,
            '#moneris_cvv': monthlyData.cvv,
            '#moneris_postal_code': monthlyData.postal
        };

        // Fill each field and trigger change events
        Object.entries(monerisFields).forEach(([selector, value]) => {
            const field = $(selector);
            if (field.length > 0) {
                // Clear field first, then set value
                field.val('').val(value).trigger('change').trigger('input');
                console.log(`Copied to ${selector}: ${selector.includes('cvv') ? '***' : value} (Field found: ${field.length > 0})`);

                // Special handling for CVV field - try alternative selectors if main one doesn't work
                if (selector === '#moneris_cvv' && field.val() !== value) {
                    console.warn('CVV field may not accept programmatic input. Field value after setting:', field.val());

                    // Try alternative CVV field names
                    const alternativeSelectors = ['#cvv', '#card_cvv', '[name*="cvv"]', '[name*="cvd"]'];
                    alternativeSelectors.forEach(altSelector => {
                        const altField = $(altSelector);
                        if (altField.length > 0) {
                            console.log(`Trying alternative CVV selector: ${altSelector}`);
                            altField.val(value).trigger('change');
                        }
                    });
                }
            } else {
                console.warn(`Field not found: ${selector}`);

                // For missing fields, log what fields are actually available
                if (selector === '#moneris_cvv') {
                    console.log('Available fields containing "cvv" or "cvd":',
                        $('input[id*="cvv"], input[name*="cvv"], input[id*="cvd"], input[name*="cvd"]').map(function () {
                            return '#' + this.id + ' (name: ' + this.name + ')';
                        }).get());
                }
            }
        });

        // Show success feedback
        const helpText = $('.same-cc-help-text');
        const originalText = helpText.text();
        helpText.text('✓ Credit card information copied successfully!').css('color', '#27ae60');

        // Revert help text after 3 seconds
        setTimeout(function () {
            helpText.text(originalText).css('color', '');
        }, 3000);

        console.log('Credit card info copying completed from monthly billing to upfront payment');
    }
});