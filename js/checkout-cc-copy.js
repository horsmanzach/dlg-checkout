/**
 * Credit Card Copy Functionality for New Checkout Template - CHECKBOX VERSION
 * File: js/checkout-cc-copy.js
 * UPDATED: Fixed validation requirement for copy checkbox
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
        // UPDATED: Listen for custom events from monthly-billing.js
        $(document).on('monthlyBillingValidationSuccess', function (event, data) {
            console.log('Monthly billing validation success event received:', data);
            setTimeout(updateCheckboxState, 100);
        });

        $(document).on('monthlyBillingStateChanged', function () {
            console.log('Monthly billing state changed event received');
            setTimeout(updateCheckboxState, 100);
        });

        // UPDATED: Monitor for successful validation completion
        $(document).on('click', '.validate-card-btn', function () {
            // Wait for validation AJAX to complete and update checkbox state
            setTimeout(updateCheckboxState, 1500);
        });

        // Monitor monthly billing field changes (but don't enable until validated)
        $(document).on('input change',
            '#cc_cardholder_name, #cc_card_number, #cc_expiry, #cc_cvv, #cc_postal_code',
            function () {
                // Update checkbox state when fields change (will disable if validation is lost)
                setTimeout(updateCheckboxState, 200);
            }
        );

        // Monitor monthly payment method selection
        $(document).on('change', 'input[name="monthly_payment_method"]', function () {
            setTimeout(updateCheckboxState, 100);
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

        // UPDATED: Use MutationObserver for better detection of dynamic content changes
        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.type === 'childList' || mutation.type === 'characterData') {
                    const target = $(mutation.target);

                    // Check if validation message or button state changed
                    if (target.hasClass('cc-validation-message') ||
                        target.hasClass('validate-card-btn') ||
                        target.closest('.cc-validation-message').length > 0 ||
                        target.closest('.validate-card-btn').length > 0) {
                        setTimeout(updateCheckboxState, 200);
                    }
                }
            });
        });

        // Start observing the document for changes
        observer.observe(document.body, {
            childList: true,
            subtree: true,
            characterData: true
        });

        // UPDATED: Also listen for form reset events
        $(document).on('reset', '#moneris-payment-form', function () {
            setTimeout(updateCheckboxState, 100);
        });

        // UPDATED: Listen for validation button text changes
        const buttonObserver = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.type === 'childList' || mutation.type === 'characterData') {
                    const target = mutation.target;
                    if ($(target).hasClass('validate-card-btn') ||
                        $(target).closest('.validate-card-btn').length > 0) {
                        setTimeout(updateCheckboxState, 100);
                    }
                }
            });
        });

        // Observe changes to validation button
        const validateBtn = document.querySelector('.validate-card-btn');
        if (validateBtn) {
            buttonObserver.observe(validateBtn, {
                childList: true,
                subtree: true,
                characterData: true,
                attributes: true,
                attributeFilter: ['class']
            });
        }
    }

    function updateCheckboxState() {
        const checkbox = $('#same-cc-info-checkbox');
        const helpText = $('.same-cc-help-text');
        const checkboxRow = $('.same-cc-checkbox-row');

        if (checkbox.length === 0) return;

        console.log('Updating checkbox state...');

        // Check if monthly payment method is set to credit card
        const monthlyPaymentMethod = $('input[name="monthly_payment_method"]:checked').val();
        if (monthlyPaymentMethod !== 'cc') {
            checkbox.prop('disabled', true).prop('checked', false).removeClass('enabled');
            checkboxRow.removeClass('success').addClass('disabled');
            helpText.text('Select credit card for monthly billing to enable this option');
            console.log('Monthly payment method not CC, disabling checkbox');
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
            console.log('Not all fields filled, disabling checkbox');
            return;
        }

        // UPDATED: More robust validation success detection
        const isValidationSuccessful = checkValidationSuccess();

        if (!isValidationSuccessful) {
            checkbox.prop('disabled', true).prop('checked', false).removeClass('enabled');
            checkboxRow.removeClass('success').addClass('disabled');
            helpText.text('Credit card validation required before copying info');
            console.log('Validation not successful, disabling checkbox');
            return;
        }

        // If we get here, enable the checkbox
        checkbox.prop('disabled', false).addClass('enabled');
        checkboxRow.removeClass('disabled').addClass('success');
        helpText.text('Check to copy validated credit card info to upfront payment');

        console.log('Same CC checkbox enabled - monthly billing validated successfully');
    }

    // UPDATED: Enhanced validation success detection with multiple fallback methods
    function checkValidationSuccess() {
        console.log('Checking validation success...');

        // Method 1: Check if validation button shows "Card Confirmed ✓" with confirmed class
        const validateButton = $('.validate-card-btn');
        const buttonIsConfirmed = validateButton.hasClass('confirmed') &&
            (validateButton.text().includes('Card Confirmed ✓') ||
                validateButton.text().includes('Confirmed ✓'));

        if (buttonIsConfirmed) {
            console.log('✓ Validation success detected: Button shows confirmed state');
            return true;
        }

        // Method 2: Check for success validation message with green styling
        const validationMessage = $('.cc-validation-message:visible');
        if (validationMessage.length > 0) {
            const messageText = validationMessage.text().toLowerCase();
            const messageHtml = validationMessage.html().toLowerCase();

            const hasSuccessKeywords = messageText.includes('validated through moneris') ||
                messageText.includes('card validated') ||
                messageText.includes('card details confirmed') ||
                messageText.includes('✓') ||
                messageText.includes('success');

            const hasSuccessColor = messageHtml.includes('#27ae60') ||
                messageHtml.includes('color: #27ae60') ||
                messageHtml.includes('rgb(39, 174, 96)') ||
                validationMessage.find('div[style*="#27ae60"]').length > 0;

            if (hasSuccessKeywords && hasSuccessColor) {
                console.log('✓ Validation success detected: Success message present');
                return true;
            }
        }

        // Method 3: Check if all fields have "valid" class (green borders)
        const requiredFields = ['#cc_cardholder_name', '#cc_card_number', '#cc_expiry', '#cc_cvv', '#cc_postal_code'];
        const allFieldsValid = requiredFields.every(selector => {
            return $(selector).hasClass('valid');
        });

        if (allFieldsValid && requiredFields.length > 0) {
            console.log('✓ Validation success detected: All fields have valid class');
            return true;
        }

        // Method 4: Check payment option header for confirmed state
        const ccHeader = $('.payment-option-header[data-option="cc"]');
        if (ccHeader.hasClass('confirmed-method')) {
            console.log('✓ Validation success detected: CC header has confirmed-method class');
            return true;
        }

        // Method 5: Check for confirmed arrow on CC header
        const ccArrow = ccHeader.find('.accordion-arrow');
        if (ccArrow.hasClass('confirmed-arrow') && ccArrow.text() === '✓') {
            console.log('✓ Validation success detected: CC arrow shows confirmed state');
            return true;
        }

        // Method 6: Check if there are no visible error messages and validation was attempted
        const hasVisibleErrors = $('.cc-validation-message:visible').find('div').css('color') === 'rgb(231, 76, 60)' || // #e74c3c
            $('.cc-validation-message:visible').html().includes('#e74c3c') ||
            $('.field-error-message:visible').length > 0 ||
            $('#cc_cardholder_name.error, #cc_card_number.error, #cc_expiry.error, #cc_cvv.error, #cc_postal_code.error').length > 0;

        if (!hasVisibleErrors) {
            // Check if validation was attempted (button was clicked or message exists)
            const validationAttempted = $('.cc-validation-message').length > 0 ||
                validateButton.text() !== 'Validate Card' ||
                validateButton.hasClass('confirmed');

            if (validationAttempted) {
                console.log('✓ Validation success detected: No errors present after validation attempt');
                return true;
            }
        }

        // Method 7: Check for global validation state variables (if they exist)
        if (typeof window.monthlyBillingValidated !== 'undefined' && window.monthlyBillingValidated === true) {
            console.log('✓ Validation success detected: Global validation flag set');
            return true;
        }

        console.log('❌ Validation not successful - no success indicators found');
        return false;
    }

    function copyCreditCardInfo() {
        // Double-check validation before copying
        if (!checkValidationSuccess()) {
            alert('Error: Monthly billing credit card must be validated before copying.');
            $('#same-cc-info-checkbox').prop('checked', false);
            return;
        }

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

        console.log('Copying validated credit card data:', {
            name: monthlyData.name,
            number: monthlyData.number.substring(0, 4) + '****', // Log partial number for security
            expiry: monthlyData.expiry,
            cvv: monthlyData.cvv ? '***' : 'empty',
            postal: monthlyData.postal
        });

        // Map to upfront payment form fields (update these selectors based on actual form)
        // Copy to Moneris upfront payment fields
        const monerisFields = {
            '#moneris_cardholder_name': monthlyData.name,
            '#moneris_card_number': monthlyData.number,
            '#moneris_expiry_date': monthlyData.expiry,
            '#moneris_cvv': monthlyData.cvv,
            '#moneris_postal_code': monthlyData.postal
        };

        // Fill each field and trigger change events
        let copiedFields = 0;
        Object.entries(monerisFields).forEach(([selector, value]) => {
            const field = $(selector);
            if (field.length > 0) {
                // Clear field first, then set value
                field.val('').val(value).trigger('change').trigger('input');
                copiedFields++;
                console.log(`✓ Copied to ${selector}: ${selector.includes('cvv') ? '***' : value}`);

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
                console.warn(`⚠ Field not found: ${selector}`);

                // For missing fields, log what fields are actually available
                if (selector === '#moneris_cvv') {
                    console.log('Available fields containing "cvv" or "cvd":',
                        $('input[id*="cvv"], input[name*="cvv"], input[id*="cvd"], input[name*="cvd"]').map(function () {
                            return '#' + this.id + ' (name: ' + this.name + ')';
                        }).get());
                }
            }
        });

        // Show feedback to user
        if (copiedFields > 0) {
            // Update help text to show success
            const helpText = $('.same-cc-help-text');
            const originalText = helpText.text();
            helpText.text(`✓ Credit card info copied successfully! (${copiedFields} fields)`).css('color', '#27ae60');

            // Revert help text after 4 seconds
            setTimeout(function () {
                helpText.text(originalText).css('color', '');
            }, 4000);

            console.log(`✓ Credit card info copying completed: ${copiedFields} fields copied from monthly billing to upfront payment`);
        } else {
            alert('Warning: No upfront payment fields were found to copy to. Please check the form structure.');
            $('#same-cc-info-checkbox').prop('checked', false);
        }
    }

    // UPDATED: Expose function globally for other scripts if needed
    window.updateCopyCheckboxState = updateCheckboxState;

    // UPDATED: Add debugging function for troubleshooting
    window.debugCopyCheckbox = function () {
        console.log('=== Copy Checkbox Debug Info ===');
        console.log('Checkbox exists:', $('#same-cc-info-checkbox').length > 0);
        console.log('Monthly method selected:', $('input[name="monthly_payment_method"]:checked').val());
        console.log('All fields filled:', ['#cc_cardholder_name', '#cc_card_number', '#cc_expiry', '#cc_cvv', '#cc_postal_code'].every(s => $(s).val()?.trim().length > 0));
        console.log('Validation successful:', checkValidationSuccess());
        console.log('Button state:', {
            text: $('.validate-card-btn').text(),
            hasConfirmed: $('.validate-card-btn').hasClass('confirmed')
        });
        console.log('Validation message:', $('.cc-validation-message:visible').text());
        console.log('Fields with valid class:', $('#cc_cardholder_name.valid, #cc_card_number.valid, #cc_expiry.valid, #cc_cvv.valid, #cc_postal_code.valid').length);
        console.log('=== End Debug Info ===');
    };
});