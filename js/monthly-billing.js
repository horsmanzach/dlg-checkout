/**
 * Monthly Billing Functionality for Checkout - WORKING VERSION
 */
jQuery(document).ready(function ($) {
    console.log('Monthly billing: Starting initialization...');

    // Wait for DOM to be fully ready
    setTimeout(function () {
        initializeMonthlyBilling();
    }, 500);

    function initializeMonthlyBilling() {
        if ($('#monthly-billing-section').length === 0) {
            console.warn('Monthly billing section not found');
            return;
        }

        console.log('Monthly billing section found, initializing...');

        // First ensure fields exist in the correct sections
        ensureFieldsExistInCorrectSections();

        // Then initialize all components
        initializeAccordion();
        initializeFormFormatting();
        initializeFormValidation();
        initializeCardValidation();
        initializeBankValidation();
        initializePayAfterValidation();

        console.log('Monthly billing initialization complete');
    }

    function ensureFieldsExistInCorrectSections() {
        console.log('Ensuring fields exist in correct sections...');

        const bankContainer = $('#bank-content .bank-form-fields');
        const ccContainer = $('#cc-content .cc-form-fields');

        // If bank fields don't exist in bank container, create them
        if (bankContainer.length > 0 && bankContainer.find('input[name="bank_first_name"]').length === 0) {
            console.log('Creating bank fields in bank container');
            bankContainer.html(`
                <div class="account-type-wrapper" style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;">
                    <label style="display: block; margin-bottom: 10px; font-weight: bold;">Account Type <abbr class="required" title="required">*</abbr></label>
                    <div style="display: flex; gap: 20px;">
                        <label style="display: flex; align-items: center; cursor: pointer;">
                            <input type="radio" name="bank_account_type" value="chequing" id="chequing" required style="margin-right: 8px;">
                            Chequing
                        </label>
                        <label style="display: flex; align-items: center; cursor: pointer;">
                            <input type="radio" name="bank_account_type" value="savings" id="savings" required style="margin-right: 8px;">
                            Savings
                        </label>
                    </div>
                </div>
                
                <div class="bank-details-section">
                    <p class="form-row form-row-first">
                        <label for="bank_first_name">First Name <abbr class="required" title="required">*</abbr></label>
                        <input type="text" class="input-text" name="bank_first_name" id="bank_first_name" required>
                    </p>
                    <p class="form-row form-row-last">
                        <label for="bank_last_name">Last Name <abbr class="required" title="required">*</abbr></label>
                        <input type="text" class="input-text" name="bank_last_name" id="bank_last_name" required>
                    </p>
                    <p class="form-row form-row-wide">
                        <label for="bank_institution">Financial Institution Name <abbr class="required" title="required">*</abbr></label>
                        <input type="text" class="input-text" name="bank_institution" id="bank_institution" required>
                    </p>
                    <p class="form-row form-row-first">
                        <label for="bank_transit">Transit Number <abbr class="required" title="required">*</abbr></label>
                        <input type="text" class="input-text" name="bank_transit" id="bank_transit" placeholder="5 digits" maxlength="5" pattern="[0-9]{5}" required>
                    </p>
                    <p class="form-row form-row-last">
                        <label for="bank_institution_number">Institution Number <abbr class="required" title="required">*</abbr></label>
                        <input type="text" class="input-text" name="bank_institution_number" id="bank_institution_number" placeholder="3 digits" maxlength="3" pattern="[0-9]{3}" required>
                    </p>
                    <p class="form-row form-row-wide">
                        <label for="bank_account_number">Account Number <abbr class="required" title="required">*</abbr></label>
                        <input type="text" class="input-text" name="bank_account_number" id="bank_account_number" placeholder="7+ digits" minlength="7" pattern="[0-9]{7,}" required>
                    </p>
                    <div style="clear: both;"></div>
                    <div class="bank-validation-message" style="display:none; margin-top: 10px;"></div>
                    <button type="button" class="button confirm-bank-btn" style="margin-top: 10px;">Confirm Bank Details</button>
                </div>
            `);
        }

        // If CC fields don't exist in CC container, create them
        if (ccContainer.length > 0 && ccContainer.find('input[name="cc_cardholder_name"]').length === 0) {
            console.log('Creating CC fields in CC container');
            ccContainer.html(`
                <p class="form-row form-row-wide">
                    <label for="cc_cardholder_name">Cardholder Name <abbr class="required" title="required">*</abbr></label>
                    <input type="text" class="input-text" name="cc_cardholder_name" id="cc_cardholder_name" placeholder="Full name as it appears on card" required>
                </p>
                <p class="form-row form-row-wide">
                    <label for="cc_card_number">Card Number <abbr class="required" title="required">*</abbr></label>
                    <input type="text" class="input-text" name="cc_card_number" id="cc_card_number" placeholder="1234 5678 9012 3456" maxlength="19" required>
                </p>
                <p class="form-row form-row-first">
                    <label for="cc_expiry">Expiry Date <abbr class="required" title="required">*</abbr></label>
                    <input type="text" class="input-text" name="cc_expiry" id="cc_expiry" placeholder="MM/YY" maxlength="5" required>
                </p>
                <p class="form-row form-row-last">
                    <label for="cc_cvv">CVV <abbr class="required" title="required">*</abbr></label>
                    <input type="text" class="input-text" name="cc_cvv" id="cc_cvv" placeholder="123" maxlength="4" required>
                </p>
                <div style="clear: both;"></div>
                <div class="cc-validation-message" style="display:none; margin-top: 10px;"></div>
                <button type="button" class="button validate-card-btn" style="margin-top: 10px;">Validate & Confirm Card</button>
            `);
        }

        console.log('Field creation complete');
    }

    function initializeAccordion() {
        console.log('Initializing accordion...');

        // Handle header clicks (but not radio button clicks)
        $(document).on('click', '.payment-option-header', function (e) {
            if ($(e.target).is('input[type="radio"]')) {
                return; // Let radio button handle itself
            }

            const option = $(this).data('option');
            const radio = $(this).find('input[type="radio"]');
            const content = $('#' + option + '-content');
            const arrow = $(this).find('.accordion-arrow');

            console.log('Header clicked for option:', option);

            // Close all other sections
            $('.payment-option-content').not(content).slideUp(300);
            $('.payment-option-header').not(this).removeClass('active').find('.accordion-arrow').text('▼');

            // Toggle current section
            if (content.is(':visible')) {
                content.slideUp(300);
                $(this).removeClass('active');
                arrow.text('▼');
                radio.prop('checked', false);
                $('input[name="selected_monthly_method"]').val('');
            } else {
                radio.prop('checked', true);
                $(this).addClass('active');
                arrow.text('▲');
                content.slideDown(300);
                $('input[name="selected_monthly_method"]').val(option);
            }

            resetConfirmations();
        });

        // Handle direct radio button changes
        $(document).on('change', 'input[name="monthly_payment_method"]', function () {
            const option = $(this).val();
            const header = $(this).closest('.payment-option-header');
            const content = $('#' + option + '-content');

            console.log('Radio changed to option:', option);

            // Close all sections
            $('.payment-option-content').slideUp(300);
            $('.payment-option-header').removeClass('active').find('.accordion-arrow').text('▼');

            // Open selected section
            header.addClass('active').find('.accordion-arrow').text('▲');
            content.slideDown(300);

            $('input[name="selected_monthly_method"]').val(option);
            resetConfirmations();
        });
    }

    function initializeFormFormatting() {
        console.log('Initializing form formatting...');

        // Credit card number formatting
        $(document).on('input', '#cc_card_number', function () {
            let value = $(this).val().replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            if (formattedValue.length > 19) {
                formattedValue = formattedValue.substring(0, 19);
            }
            $(this).val(formattedValue);
        });

        // Expiry date formatting
        $(document).on('input', '#cc_expiry', function () {
            let value = $(this).val().replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            $(this).val(value);
        });

        // Numeric only fields
        $(document).on('input', '#cc_cvv, #bank_transit, #bank_institution_number, #bank_account_number', function () {
            $(this).val($(this).val().replace(/\D/g, ''));
        });

        // Text only fields (allow letters, spaces, hyphens, apostrophes)
        $(document).on('input', '#cc_cardholder_name, #bank_first_name, #bank_last_name, #bank_institution', function () {
            $(this).val($(this).val().replace(/[^a-zA-Z\s\-']/g, ''));
        });
    }

    function initializeFormValidation() {
        console.log('Initializing form validation...');

        // Real-time validation feedback
        $(document).on('blur', '.cc-form-fields input, .bank-form-fields input', function () {
            validateField($(this));
        });
    }

    function validateField(field) {
        const fieldName = field.attr('name');
        const value = field.val().trim();
        let isValid = true;
        let message = '';

        switch (fieldName) {
            case 'cc_card_number':
                const cleanNumber = value.replace(/\s/g, '');
                if (cleanNumber.length < 13 || cleanNumber.length > 19) {
                    isValid = false;
                    message = 'Card number must be 13-19 digits';
                }
                break;

            case 'cc_expiry':
                if (!/^\d{2}\/\d{2}$/.test(value)) {
                    isValid = false;
                    message = 'Use MM/YY format';
                } else {
                    const [month, year] = value.split('/');
                    const currentDate = new Date();
                    const currentYear = currentDate.getFullYear() % 100;
                    const currentMonth = currentDate.getMonth() + 1;

                    if (parseInt(month) < 1 || parseInt(month) > 12) {
                        isValid = false;
                        message = 'Invalid month';
                    } else if (parseInt(year) < currentYear || (parseInt(year) === currentYear && parseInt(month) < currentMonth)) {
                        isValid = false;
                        message = 'Card has expired';
                    }
                }
                break;

            case 'bank_transit':
                if (value.length !== 5) {
                    isValid = false;
                    message = 'Transit number must be 5 digits';
                }
                break;

            case 'bank_institution_number':
                if (value.length !== 3) {
                    isValid = false;
                    message = 'Institution number must be 3 digits';
                }
                break;

            case 'bank_account_number':
                if (value.length < 7) {
                    isValid = false;
                    message = 'Account number must be at least 7 digits';
                }
                break;
        }

        // Update field styling
        if (isValid) {
            field.removeClass('error').addClass('valid');
        } else {
            field.removeClass('valid').addClass('error');
        }

        return { isValid, message };
    }

    function initializeCardValidation() {
        console.log('Initializing card validation...');

        $(document).on('click', '.validate-card-btn', function () {
            const button = $(this);
            const originalText = button.text();

            const cardData = {
                cardholder_name: $('#cc_cardholder_name').val().trim(),
                card_number: $('#cc_card_number').val().replace(/\s/g, ''),
                expiry: $('#cc_expiry').val(), // Will be in MM/YY format
                cvv: $('#cc_cvv').val()
            };

            console.log('Validating card data:', cardData);

            // Validate all fields
            let allValid = true;
            const fields = ['cc_cardholder_name', 'cc_card_number', 'cc_expiry', 'cc_cvv'];

            fields.forEach(fieldName => {
                const field = $('#' + fieldName);
                const validation = validateField(field);
                if (!validation.isValid || !field.val().trim()) {
                    allValid = false;
                }
            });

            if (!allValid) {
                $('.cc-validation-message').html('<span style="color:red;">Please fill in all card details correctly.</span>').show();
                return;
            }

            button.text('Validating...').prop('disabled', true);
            $('.cc-validation-message').html('<span style="color:blue;">Validating card...</span>').show();

            // Check if monthlyBilling is available
            if (typeof monthlyBilling === 'undefined') {
                console.error('monthlyBilling object not found');
                $('.cc-validation-message').html('<span style="color:red;">Configuration error. Please refresh and try again.</span>').show();
                button.text(originalText).prop('disabled', false);
                return;
            }

            // AJAX call to validate card
            $.ajax({
                url: monthlyBilling.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'validate_credit_card',
                    card_data: cardData,
                    nonce: monthlyBilling.nonce
                },
                success: function (response) {
                    console.log('Card validation response:', response);
                    if (response.success) {
                        $('.cc-validation-message').html('<span style="color:green;">✓ Card validated successfully!</span>').show();
                        $('input[name="monthly_billing_confirmed"]').val('1');
                        button.text('Card Confirmed ✓').addClass('confirmed');
                    } else {
                        $('.cc-validation-message').html('<span style="color:red;">' + (response.data?.message || 'Card validation failed') + '</span>').show();
                        button.text(originalText).prop('disabled', false);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Card validation error:', error);
                    $('.cc-validation-message').html('<span style="color:red;">Error validating card. Please try again.</span>').show();
                    button.text(originalText).prop('disabled', false);
                }
            });
        });
    }

    function initializeBankValidation() {
        console.log('Initializing bank validation...');

        $(document).on('click', '.confirm-bank-btn', function () {
            const requiredFields = ['bank_first_name', 'bank_last_name', 'bank_institution', 'bank_transit', 'bank_institution_number', 'bank_account_number'];
            let valid = true;
            let firstInvalidField = null;

            console.log('Validating bank details...');

            // Validate required fields
            requiredFields.forEach(function (fieldName) {
                const field = $('#' + fieldName);
                const validation = validateField(field);

                if (!field.val().trim() || !validation.isValid) {
                    valid = false;
                    field.addClass('error');
                    if (!firstInvalidField) firstInvalidField = field;
                } else {
                    field.removeClass('error').addClass('valid');
                }
            });

            // Validate account type
            if (!$('input[name="bank_account_type"]:checked').val()) {
                valid = false;
                $('.account-type-wrapper').addClass('error');
            } else {
                $('.account-type-wrapper').removeClass('error');
            }

            if (valid) {
                $('input[name="monthly_billing_confirmed"]').val('1');
                $(this).text('Bank Details Confirmed ✓').prop('disabled', true).addClass('confirmed');
                $('.bank-validation-message').html('<span style="color:green;">✓ Bank details confirmed!</span>').show();
                console.log('Bank details validated successfully');
            } else {
                $('.bank-validation-message').html('<span style="color:red;">Please fill in all required bank details correctly.</span>').show();
                if (firstInvalidField) {
                    firstInvalidField.focus();
                }
                console.log('Bank validation failed');
            }
        });
    }

    function initializePayAfterValidation() {
        console.log('Initializing pay after validation...');

        $(document).on('click', '.confirm-payafter-btn', function () {
            const button = $(this);
            button.text('Adding deposit...').prop('disabled', true);

            console.log('Adding pay after deposit...');

            // Check if monthlyBilling is available
            if (typeof monthlyBilling === 'undefined') {
                console.error('monthlyBilling object not found');
                button.text('Confirm Pay After').prop('disabled', false);
                alert('Configuration error. Please refresh and try again.');
                return;
            }

            // Add $200 deposit to cart
            $.ajax({
                url: monthlyBilling.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'add_payafter_deposit',
                    nonce: monthlyBilling.nonce
                },
                success: function (response) {
                    console.log('Pay after deposit response:', response);
                    if (response.success) {
                        $('input[name="monthly_billing_confirmed"]').val('1');
                        button.text('Pay After Confirmed ✓').addClass('confirmed');
                        $('body').trigger('update_checkout');
                    } else {
                        button.text('Confirm Pay After').prop('disabled', false);
                        alert('Error adding deposit: ' + (response.data?.message || 'Unknown error'));
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Pay after deposit error:', error);
                    button.text('Confirm Pay After').prop('disabled', false);
                    alert('Error adding deposit. Please try again.');
                }
            });
        });
    }

    function resetConfirmations() {
        console.log('Resetting confirmations...');
        $('input[name="monthly_billing_confirmed"]').val('0');
        $('.validate-card-btn, .confirm-bank-btn, .confirm-payafter-btn')
            .removeClass('confirmed')
            .prop('disabled', false);
        $('.validate-card-btn').text('Validate & Confirm Card');
        $('.confirm-bank-btn').text('Confirm Bank Details');
        $('.confirm-payafter-btn').text('Confirm Pay After');
        $('.cc-validation-message, .bank-validation-message').hide();
        $('.cc-form-fields input, .bank-form-fields input').removeClass('error valid');
        $('.account-type-wrapper').removeClass('error');
    }
});