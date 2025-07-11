/**
 * Monthly Billing Functionality for Checkout
 * Now that HTML is embedded in template, we just need to add functionality
 */
jQuery(document).ready(function ($) {

    // Check if the monthly billing section exists
    if ($('#monthly-billing-section').length === 0) {
        console.warn('Monthly billing section not found in template');
        return;
    }

    console.log('Monthly billing section found, initializing functionality...');

    // Initialize all functionality
    initializeAccordion();
    initializeFormValidation();
    initializeCardValidation();
    initializeBankValidation();
    initializePayAfterValidation();
    initializeFormFormatting();

    function initializeAccordion() {
        // Accordion header click handler
        $(document).on('click', '.payment-option-header', function (e) {
            // Don't trigger if clicking on radio button
            if ($(e.target).is('input[type="radio"]')) return;

            const option = $(this).data('option');
            const radio = $(this).find('input[type="radio"]');
            const content = $('#' + option + '-content');
            const arrow = $(this).find('.accordion-arrow');

            // Close all other accordions
            $('.payment-option-content').not(content).slideUp();
            $('.payment-option-header').not(this).removeClass('active').find('.accordion-arrow').text('▼');

            // Toggle current accordion
            if (content.is(':visible')) {
                content.slideUp();
                $(this).removeClass('active');
                arrow.text('▼');
                radio.prop('checked', false);
                $('input[name="selected_monthly_method"]').val('');
            } else {
                radio.prop('checked', true);
                $(this).addClass('active');
                arrow.text('▲');
                content.slideDown();
                $('input[name="selected_monthly_method"]').val(option);
            }
        });

        // Radio button change handler
        $(document).on('change', 'input[name="monthly_payment_method"]', function () {
            const option = $(this).val();
            const header = $(this).closest('.payment-option-header');
            const content = $('#' + option + '-content');

            // Close all other accordions
            $('.payment-option-content').slideUp();
            $('.payment-option-header').removeClass('active').find('.accordion-arrow').text('▼');

            // Open selected accordion
            header.addClass('active').find('.accordion-arrow').text('▲');
            content.slideDown();

            $('input[name="selected_monthly_method"]').val(option);
            resetConfirmations();
        });
    }

    function initializeFormFormatting() {
        // Credit card number formatting
        $(document).on('input', '#cc_card_number', function () {
            let value = $(this).val().replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
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

        // Text only fields
        $(document).on('input', '#cc_cardholder_name, #bank_first_name, #bank_last_name, #bank_institution', function () {
            $(this).val($(this).val().replace(/[^a-zA-Z\s]/g, ''));
        });
    }

    function initializeFormValidation() {
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
        $(document).on('click', '.validate-card-btn', function () {
            const button = $(this);
            const originalText = button.text();

            const cardData = {
                cardholder_name: $('#cc_cardholder_name').val().trim(),
                card_number: $('#cc_card_number').val().replace(/\s/g, ''),
                expiry: $('#cc_expiry').val(),
                cvv: $('#cc_cvv').val()
            };

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
                    if (response.success) {
                        $('.cc-validation-message').html('<span style="color:green;">✓ Card validated successfully!</span>').show();
                        $('input[name="monthly_billing_confirmed"]').val('1');
                        button.text('Card Confirmed ✓').addClass('confirmed');
                    } else {
                        $('.cc-validation-message').html('<span style="color:red;">' + (response.data?.message || 'Card validation failed') + '</span>').show();
                        button.text(originalText).prop('disabled', false);
                    }
                },
                error: function () {
                    $('.cc-validation-message').html('<span style="color:red;">Error validating card. Please try again.</span>').show();
                    button.text(originalText).prop('disabled', false);
                }
            });
        });
    }

    function initializeBankValidation() {
        $(document).on('click', '.confirm-bank-btn', function () {
            const requiredFields = ['bank_first_name', 'bank_last_name', 'bank_institution', 'bank_transit', 'bank_institution_number', 'bank_account_number'];
            let valid = true;
            let firstInvalidField = null;

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
            } else {
                $('.bank-validation-message').html('<span style="color:red;">Please fill in all required bank details correctly.</span>').show();
                if (firstInvalidField) {
                    firstInvalidField.focus();
                }
            }
        });
    }

    function initializePayAfterValidation() {
        $(document).on('click', '.confirm-payafter-btn', function () {
            const button = $(this);
            button.text('Adding deposit...').prop('disabled', true);

            // Add $200 deposit to cart
            $.ajax({
                url: monthlyBilling.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'add_payafter_deposit',
                    nonce: monthlyBilling.nonce
                },
                success: function (response) {
                    if (response.success) {
                        $('input[name="monthly_billing_confirmed"]').val('1');
                        button.text('Pay After Confirmed ✓').addClass('confirmed');
                        $('body').trigger('update_checkout');
                    } else {
                        button.text('Confirm Pay After').prop('disabled', false);
                        alert('Error adding deposit. Please try again.');
                    }
                },
                error: function () {
                    button.text('Confirm Pay After').prop('disabled', false);
                    alert('Error adding deposit. Please try again.');
                }
            });
        });
    }

    function resetConfirmations() {
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