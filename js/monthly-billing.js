/**
 * Monthly Billing Functionality for Checkout - CORRECTED FOR ACTUAL HTML STRUCTURE
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

        // Initialize all components
        initializeAccordion();
        initializeFormFormatting();
        initializeFormValidation();
        initializeCardValidation();
        initializeBankValidation();
        initializePayAfterValidation();

        // **NEW: Restore previously selected method after initialization**
        setTimeout(function () {
            restorePreviouslySelectedMethod();
        }, 1000);

        console.log('Monthly billing initialization complete');
    }

    // **CORRECTED: Accordion function based on actual HTML structure**
    function initializeAccordion() {
        console.log('Initializing accordion...');

        // **FIX 1: Handle radio button changes to open accordions**
        $(document).on('change', 'input[name="monthly_payment_method"]', function () {
            const option = $(this).val();
            const header = $(this).closest('.payment-option-header');
            const content = $('#' + option + '-content');
            const arrow = header.find('.accordion-arrow');

            console.log('Radio changed to:', option);

            if ($(this).is(':checked')) {
                // **FIX 2: Close all other sections first (proper accordion behavior)**
                $('.payment-option-header').removeClass('active');
                $('.payment-option-content').slideUp(600);
                $('.accordion-arrow').text('▼');

                // Uncheck other radio buttons
                $('input[name="monthly_payment_method"]').not(this).prop('checked', false);

                // Reset confirmations when switching methods
                resetConfirmations();

                // Open selected section
                header.addClass('active');
                content.slideDown(300);
                arrow.text('▲');
                updateSelectedMethod(option);
            }
        });

        // Handle header clicks (but not when clicking radio button or label)
        $(document).on('click', '.payment-option-header', function (e) {
            // **FIX 1: Prevent header action when radio button or label is clicked**
            if ($(e.target).is('input[type="radio"]') || $(e.target).is('label')) {
                return; // Let radio button handle itself
            }

            const option = $(this).data('option');
            const radio = $(this).find('input[type="radio"]');
            const content = $('#' + option + '-content');
            const arrow = $(this).find('.accordion-arrow');
            const isCurrentlyActive = $(this).hasClass('active');

            console.log('Header clicked for option:', option, 'Currently active:', isCurrentlyActive);

            // If clicking on currently active header, close it and deselect
            if (isCurrentlyActive) {
                content.slideUp(600);
                $(this).removeClass('active');
                arrow.text('▼');
                radio.prop('checked', false);
                updateSelectedMethod('');
                resetConfirmations();
                return;
            }

            // **FIX 2: Close all other sections first (proper accordion behavior)**
            $('.payment-option-header').removeClass('active');
            $('.payment-option-content').slideUp(600);
            $('.accordion-arrow').text('▼');
            $('input[name="monthly_payment_method"]').prop('checked', false);

            // Reset confirmations when switching methods
            resetConfirmations();

            // Open the clicked section
            $(this).addClass('active');
            content.slideDown(300);
            arrow.text('▲');
            radio.prop('checked', true);
            updateSelectedMethod(option);
        });
    }

    function initializeFormFormatting() {
        // Card number formatting
        $(document).on('input', '#cc_card_number', function () {
            let value = $(this).val().replace(/\s/g, '');
            let formattedValue = value.replace(/(.{4})/g, '$1 ').trim();
            if (formattedValue.length > 19) formattedValue = formattedValue.substring(0, 19);
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

        // CVV numeric only
        $(document).on('input', '#cc_cvv', function () {
            $(this).val($(this).val().replace(/\D/g, ''));
        });

        // Postal code uppercase and formatting
        $(document).on('input', '#cc_postal_code', function () {
            let value = $(this).val().replace(/\s/g, '').toUpperCase();
            if (value.length > 3) {
                value = value.substring(0, 3) + ' ' + value.substring(3, 6);
            }
            $(this).val(value);
        });

        // Bank numeric fields
        $(document).on('input', '#bank_transit, #bank_institution_number, #bank_account_number', function () {
            $(this).val($(this).val().replace(/\D/g, ''));
        });
    }

    function initializeFormValidation() {
        // Real-time validation for all required fields
        $(document).on('blur', 'input[required]', function () {
            const $field = $(this);
            const value = $field.val().trim();
            const errorSpan = $field.siblings('.field-error-message');

            if (value === '') {
                $field.addClass('error').removeClass('valid');
                errorSpan.text('This field is required').show();
            } else {
                $field.removeClass('error').addClass('valid');
                errorSpan.hide();
            }
        });

        // Account type validation
        $(document).on('change', 'input[name="bank_account_type"]', function () {
            $('.account-type-wrapper').removeClass('error');
        });
    }

    function initializeCardValidation() {
        $(document).on('click', '.validate-card-btn', function () {
            const button = $(this);
            let isValid = true;

            // Collect card data using actual field IDs
            const cardData = {
                cardholder_name: $('#cc_cardholder_name').val().trim(),
                card_number: $('#cc_card_number').val().replace(/\s/g, ''),
                expiry: $('#cc_expiry').val().trim(),
                cvv: $('#cc_cvv').val().trim(),
                postal_code: $('#cc_postal_code').val().trim()
            };

            // Reset previous validation states
            $('.cc-form-fields input').removeClass('error');
            $('.cc-form-fields .field-error-message').hide();
            $('.cc-validation-message').hide();

            // Validate each field with actual field names
            if (!cardData.cardholder_name || cardData.cardholder_name.length < 2) {
                $('#cc_cardholder_name').addClass('error');
                $('#cc_cardholder_name').siblings('.field-error-message').text('Please enter cardholder name').show();
                isValid = false;
            }

            if (!cardData.card_number || cardData.card_number.length < 13) {
                $('#cc_card_number').addClass('error');
                $('#cc_card_number').siblings('.field-error-message').text('Please enter valid card number').show();
                isValid = false;
            }

            if (!cardData.expiry || cardData.expiry.length !== 5) {
                $('#cc_expiry').addClass('error');
                $('#cc_expiry').siblings('.field-error-message').text('Please enter MM/YY format').show();
                isValid = false;
            }

            if (!cardData.cvv || cardData.cvv.length < 3) {
                $('#cc_cvv').addClass('error');
                $('#cc_cvv').siblings('.field-error-message').text('Please enter CVV').show();
                isValid = false;
            }

            if (!cardData.postal_code || cardData.postal_code.length < 6) {
                $('#cc_postal_code').addClass('error');
                $('#cc_postal_code').siblings('.field-error-message').text('Please enter valid postal code').show();
                isValid = false;
            }

            if (!isValid) {
                $('.cc-validation-message').html('<div style="color: #e74c3c; padding: 10px; background: #fdf2f2; border: 1px solid #e74c3c; border-radius: 4px;"><strong>⚠ Please fix the errors above</strong></div>').show();
                return;
            }

            // Disable button and show loading
            button.prop('disabled', true).text('Validating...');

            // Call actual Moneris API validation
            $.ajax({
                url: monthlyBilling.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'validate_credit_card',
                    card_data: {
                        cardholder_name: cardData.cardholder_name,
                        card_number: cardData.card_number,
                        expiry: cardData.expiry,
                        cvv: cardData.cvv,
                        postal_code: cardData.postal_code
                    },
                    nonce: monthlyBilling.checkoutNonce
                },
                success: function (response) {
                    console.log('Full response:', response);
                    button.prop('disabled', false);

                    if (response.success) {
                        // SUCCESS: Show success message
                        $('.cc-validation-message').html('<div style="color: #27ae60; padding: 10px; background: #e8f5e8; border: 1px solid #27ae60; border-radius: 4px;"><strong>✓ Card validated through Moneris!</strong><br><br>   Your credit card information has been validated for monthly billing.</div>').show();

                        // SUCCESS: Apply green borders to ALL fields
                        $('#cc_cardholder_name, #cc_card_number, #cc_expiry, #cc_cvv, #cc_postal_code').removeClass('error').addClass('valid');

                        button.text('Card Confirmed ✓').addClass('confirmed');

                        // Save the selection
                        saveMonthlyBillingSelection('cc', {
                            monthly_bill_payment_option: 'cc',
                            cc_monthly_billing_card_number: cardData.card_number,
                            cc_monthly_billing_card_expiry: cardData.expiry,
                            cc_monthly_billing_card_cvv: cardData.cvv,
                            cc_monthly_billing_full_name: cardData.cardholder_name,
                            cc_monthly_billing_postcode: cardData.postal_code,
                            // Clear bank fields
                            bank_monthly_billing_first_name: "",
                            bank_monthly_billing_last_name: "",
                            bank_monthly_billing_account_type: "",
                            bank_monthly_billing_financial_institution: "",
                            bank_monthly_billing_transit_number: "",
                            bank_monthly_billing_institution_number: "",
                            bank_monthly_billing_account_number: ""
                        });

                        // Clear other options FIRST, then mark as confirmed
                        clearOtherMonthlyBillingOptions('cc');
                        markMethodAsConfirmed('cc');

                    } else {
                        // ERROR HANDLING: First check if response.data exists and has message
                        let errorMessage = 'Unknown error';

                        // Fix for "response data is undefined" - check response structure properly
                        console.log('Response data:', response.data);
                        console.log('Response message:', response.message);
                        console.log('Full response object:', JSON.stringify(response));

                        if (response.data && response.data.message) {
                            // WordPress AJAX success=false, message in response.data.message
                            errorMessage = response.data.message;
                        } else if (response.message) {
                            // Direct message in response.message
                            errorMessage = response.message;
                        } else if (typeof response === 'string') {
                            // Sometimes the response might be a string
                            try {
                                const parsedResponse = JSON.parse(response);
                                if (parsedResponse.msg) {
                                    errorMessage = parsedResponse.msg;
                                } else if (parsedResponse.message) {
                                    errorMessage = parsedResponse.message;
                                }
                            } catch (e) {
                                errorMessage = response;
                            }
                        }

                        // Display the specific error message to user
                        $('.cc-validation-message').html('<div style="color: #e74c3c; padding: 10px; background: #fdf2f2; border: 1px solid #e74c3c; border-radius: 4px;"><strong>❌ Card validation failed</strong><br><br> &nbsp; &nbsp; ' + errorMessage + '</div>').show();

                        // FIELD-SPECIFIC ERROR STYLING: Apply red borders based on error message content
                        // First, remove all previous error/valid classes
                        $('#cc_cardholder_name, #cc_card_number, #cc_expiry, #cc_cvv, #cc_postal_code').removeClass('error valid');

                        // Apply red borders to specific fields based on error message
                        const lowerErrorMessage = errorMessage.toLowerCase();

                        if (lowerErrorMessage.includes('card number') || lowerErrorMessage.includes('invalid credit card')) {
                            $('#cc_card_number').addClass('error');
                        }

                        if (lowerErrorMessage.includes('expiry') || lowerErrorMessage.includes('expired')) {
                            $('#cc_expiry').addClass('error');
                        }

                        if (lowerErrorMessage.includes('cvv') || lowerErrorMessage.includes('security code') || lowerErrorMessage.includes('cvd')) {
                            $('#cc_cvv').addClass('error');
                        }

                        if (lowerErrorMessage.includes('postal') || lowerErrorMessage.includes('zip') || lowerErrorMessage.includes('billing')) {
                            $('#cc_postal_code').addClass('error');
                        }

                        if (lowerErrorMessage.includes('name') || lowerErrorMessage.includes('cardholder')) {
                            $('#cc_cardholder_name').addClass('error');
                        }

                        // If we can't determine the specific field, highlight the most likely culprit based on common errors
                        if (!$('#cc_card_number, #cc_expiry, #cc_cvv, #cc_postal_code, #cc_cardholder_name').hasClass('error')) {
                            // Default to card number field for generic errors
                            $('#cc_card_number').addClass('error');
                        }

                        button.text('Validate Card').removeClass('confirmed');
                    }
                },
                error: function (xhr, status, error) {
                    console.log('AJAX error:', xhr.responseText);
                    button.prop('disabled', false);
                    $('.cc-validation-message').html('<div style="color: #e74c3c; padding: 10px; background: #fdf2f2; border: 1px solid #e74c3c; border-radius: 4px;"><strong>❌ Validation error</strong><br><br>Please try again.</div>').show();
                    button.text('Validate Card').removeClass('confirmed');
                }
            });
        });
    }

    function initializeBankValidation() {
        $(document).on('click', '.validate-bank-btn', function () {
            const button = $(this);
            let isValid = true;

            // Collect bank data using actual field IDs
            const bankData = {
                first_name: $('#bank_first_name').val().trim(),
                last_name: $('#bank_last_name').val().trim(),
                account_type: $('input[name="bank_account_type"]:checked').val(),
                financial_institution: $('#bank_institution').val().trim(),
                transit_number: $('#bank_transit').val().trim(),
                institution_number: $('#bank_institution_number').val().trim(),
                account_number: $('#bank_account_number').val().trim()
            };

            // Reset previous validation states
            $('.bank-form-fields input').removeClass('error');
            $('.bank-form-fields .field-error-message').hide();
            $('.account-type-wrapper').removeClass('error');
            $('.bank-validation-message').hide();

            // Validate each field
            if (!bankData.first_name || bankData.first_name.length < 2) {
                $('#bank_first_name').addClass('error');
                $('#bank_first_name').siblings('.field-error-message').text('Please enter first name').show();
                isValid = false;
            }

            if (!bankData.last_name || bankData.last_name.length < 2) {
                $('#bank_last_name').addClass('error');
                $('#bank_last_name').siblings('.field-error-message').text('Please enter last name').show();
                isValid = false;
            }

            if (!bankData.account_type) {
                $('.account-type-wrapper').addClass('error');
                isValid = false;
            }

            if (!bankData.financial_institution || bankData.financial_institution.length < 3) {
                $('#bank_institution').addClass('error');
                $('#bank_institution').siblings('.field-error-message').text('Please enter financial institution').show();
                isValid = false;
            }

            if (!bankData.transit_number || bankData.transit_number.length !== 5) {
                $('#bank_transit').addClass('error');
                $('#bank_transit').siblings('.field-error-message').text('Must be 5 digits').show();
                isValid = false;
            }

            if (!bankData.institution_number || bankData.institution_number.length !== 3) {
                $('#bank_institution_number').addClass('error');
                $('#bank_institution_number').siblings('.field-error-message').text('Must be 3 digits').show();
                isValid = false;
            }

            if (!bankData.account_number || bankData.account_number.length < 7) {
                $('#bank_account_number').addClass('error');
                $('#bank_account_number').siblings('.field-error-message').text('Must be at least 7 digits').show();
                isValid = false;
            }

            if (!isValid) {
                $('.bank-validation-message').html('<div style="color: #e74c3c; padding: 10px; background: #fdf2f2; border: 1px solid #e74c3c; border-radius: 4px;"><strong>⚠ Please fix the errors above</strong></div>').show();
                return;
            }

            // Show success message and update button
            $('.bank-validation-message').html('<div style="color: #27ae60; padding: 10px; background: #e8f5e8; border: 1px solid #27ae60; border-radius: 4px;"><strong>✓ Bank details confirmed!</strong><br>Your banking information has been saved for monthly billing.</div>').show();

            // Update button state
            button.text('Bank Details Confirmed ✓').addClass('confirmed');

            // Save the selection
            saveMonthlyBillingSelection('bank', {
                monthly_bill_payment_option: 'bank',
                bank_monthly_billing_first_name: bankData.first_name,
                bank_monthly_billing_last_name: bankData.last_name,
                bank_monthly_billing_account_type: bankData.account_type,
                bank_monthly_billing_financial_institution: bankData.financial_institution,
                bank_monthly_billing_transit_number: bankData.transit_number,
                bank_monthly_billing_institution_number: bankData.institution_number,
                bank_monthly_billing_account_number: bankData.account_number,
                // Clear CC fields
                cc_monthly_billing_card_number: "",
                cc_monthly_billing_card_expiry: "",
                cc_monthly_billing_card_cvv: "",
                cc_monthly_billing_full_name: "",
                cc_monthly_billing_postcode: ""
            });

            // Clear other options FIRST, then mark as confirmed
            clearOtherMonthlyBillingOptions('bank');

            // Mark as confirmed after clearing
            markMethodAsConfirmed('bank');
        });
    }

    function initializePayAfterValidation() {
        $(document).on('click', '.confirm-payafter-btn', function () {
            const button = $(this);

            // Disable button and show loading
            button.prop('disabled', true).text('Adding Deposit...');

            // Check if monthlyBilling object exists
            if (typeof monthlyBilling === 'undefined') {
                button.text('Confirm Pay After').prop('disabled', false);
                alert('Configuration error. Please refresh and try again.');
                return;
            }

            // Add Pay After deposits to cart
            $.ajax({
                url: monthlyBilling.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'add_payafter_deposit',  // CORRECTED: Match the actual PHP action name
                    nonce: monthlyBilling.checkoutNonce
                },
                success: function (response) {
                    console.log('Pay After response:', response);

                    if (response.success) {
                        // Update button state
                        button.text('Pay After Confirmed ✓').addClass('confirmed').prop('disabled', false);

                        // Save the selection
                        saveMonthlyBillingSelection('payafter', {
                            monthly_bill_payment_option: 'payafter',
                            // Clear other fields
                            cc_monthly_billing_card_number: "",
                            cc_monthly_billing_card_expiry: "",
                            cc_monthly_billing_card_cvv: "",
                            cc_monthly_billing_full_name: "",
                            cc_monthly_billing_postcode: "",
                            bank_monthly_billing_first_name: "",
                            bank_monthly_billing_last_name: "",
                            bank_monthly_billing_account_type: "",
                            bank_monthly_billing_financial_institution: "",
                            bank_monthly_billing_transit_number: "",
                            bank_monthly_billing_institution_number: "",
                            bank_monthly_billing_account_number: ""
                        });

                        // Mark as confirmed
                        markMethodAsConfirmed('payafter');
                        clearOtherMonthlyBillingOptions('payafter');

                        // Update fee tables
                        updateFeeTables();
                        showRefreshNotification();
                    } else {
                        button.text('Confirm Pay After').prop('disabled', false);
                        alert('Error adding Pay After deposit: ' + (response.data || 'Unknown error'));
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Pay After error:', error);
                    button.text('Confirm Pay After').prop('disabled', false);
                    alert('Error processing request. Please try again.');
                }
            });
        });
    }

    function updateFeeTables() {
        console.log('updateFeeTables called - targeting .upfront-fee-table container...');

        // Check if the target container exists
        if ($('.upfront-fee-table').length === 0) {
            console.warn('Target .upfront-fee-table container not found!');
            showRefreshNotification();
            return;
        }

        console.log('Found .upfront-fee-table container, refreshing...');

        // Refresh the upfront summary table directly
        if (typeof monthlyBilling !== 'undefined') {
            $.ajax({
                type: "POST",
                url: monthlyBilling.ajaxUrl,
                data: {
                    action: "refresh_upfront_summary_shortcode",
                    nonce: monthlyBilling.checkoutNonce
                },
                success: function (response) {
                    console.log('Upfront table refresh response:', response);
                    if (response.success) {
                        console.log('✓ Replacing .upfront-fee-table with updated content...');
                        $('.upfront-fee-table').replaceWith(response.data.upfront_table);
                        console.log('✓ Upfront fee table updated successfully');

                        // Also update the Moneris payment amount
                        updateMonerisPaymentAmount();

                    } else {
                        console.error('Failed to refresh upfront table:', response);
                        showRefreshNotification();
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error refreshing upfront table:', error);
                    showRefreshNotification();
                }
            });
        }
    }

    function updateMonerisPaymentAmount() {
        // Check if Moneris payment amount container exists
        if ($('.moneris-payment-amount').length === 0) {
            console.log('No .moneris-payment-amount container found, skipping...');
            return;
        }

        console.log('Updating Moneris payment amount...');

        if (typeof monthlyBilling !== 'undefined') {
            $.ajax({
                type: "POST",
                url: monthlyBilling.ajaxUrl,
                data: {
                    action: "update_moneris_payment_amount",
                    nonce: monthlyBilling.checkoutNonce
                },
                success: function (response) {
                    console.log('Moneris payment amount response:', response);
                    if (response.success) {
                        $('.moneris-payment-amount').html(response.data.payment_amount_html);
                        console.log('✓ Moneris payment amount updated successfully');
                    } else {
                        console.error('Failed to update Moneris payment amount:', response);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error updating Moneris payment amount:', error);
                }
            });
        }
    }

    function resetConfirmations() {
        console.log('Resetting confirmations...');

        // Update hidden form fields
        $('input[name="monthly_billing_confirmed"]').val('0');
        $('input[name="selected_monthly_method"]').val('');

        // Reset button states
        $('.validate-card-btn, .validate-bank-btn, .confirm-payafter-btn')
            .removeClass('confirmed')
            .prop('disabled', false);
        $('.validate-card-btn').text('Validate Card');
        $('.validate-bank-btn').text('Validate Bank Details');
        $('.confirm-payafter-btn').text('Confirm Pay After');

        // Hide validation messages
        $('.cc-validation-message, .bank-validation-message').hide();
        $('.field-error-message').hide();

        // Reset field styling
        $('input').removeClass('error valid');
        $('.account-type-wrapper').removeClass('error');

        // Reset visual confirmation states
        $('.payment-option-header').removeClass('confirmed-method');
        $('.payment-option-header .accordion-arrow').removeClass('confirmed-arrow').text('▼');
    }

    function clearOtherMonthlyBillingOptions(keepOption) {
        console.log('Clearing other monthly billing options, keeping:', keepOption);

        // Remove Pay After deposits from cart when selecting CC or Bank
        if (typeof monthlyBilling !== 'undefined' && (keepOption === 'cc' || keepOption === 'bank')) {
            $.ajax({
                url: monthlyBilling.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'remove_monthly_billing_deposits',
                    keep_option: keepOption,
                    nonce: monthlyBilling.checkoutNonce
                },
                success: function (response) {
                    console.log('Pay After deposits cleared for:', keepOption, response);
                    updateFeeTables();
                },
                error: function (xhr, status, error) {
                    console.error('Error clearing Pay After deposits:', error);
                }
            });
        }

        // Reset other button states
        if (keepOption !== 'cc') {
            $('.validate-card-btn').removeClass('confirmed').prop('disabled', false).text('Validate Card');
            $('.cc-validation-message').hide();
        }
        if (keepOption !== 'bank') {
            $('.validate-bank-btn').removeClass('confirmed').prop('disabled', false).text('Validate Bank Details');
            $('.bank-validation-message').hide();
        }
        if (keepOption !== 'payafter') {
            $('.confirm-payafter-btn').removeClass('confirmed').prop('disabled', false).text('Confirm Pay After');
        }
    }

    function markMethodAsConfirmed(method) {
        console.log('Marking method as confirmed:', method);

        // DO NOT call clearOtherMonthlyBillingOptions here - it should be called before this function

        // Mark the selected method as confirmed
        const header = $(`.payment-option-header[data-option="${method}"]`);
        header.addClass('confirmed-method');
        header.find('.accordion-arrow').addClass('confirmed-arrow').text('✓');

        // Set the confirmation state
        $('input[name="monthly_billing_confirmed"]').val('1');
        $('input[name="selected_monthly_method"]').val(method);
    }

    function updateSelectedMethod(option) {
        $('input[name="selected_monthly_method"]').val(option);
        console.log('Selected monthly method set to:', option);
    }

    function saveMonthlyBillingSelection(method, data) {
        console.log('Saving monthly billing selection:', method, data);

        if (typeof monthlyBilling !== 'undefined') {
            $.ajax({
                type: "POST",
                url: monthlyBilling.ajaxUrl,
                data: {
                    action: "save_monthly_billing_selection",
                    method: method,
                    billing_data: data,
                    nonce: monthlyBilling.checkoutNonce
                },
                success: function (response) {
                    console.log('Monthly billing selection saved:', response);
                    if (response.success) {
                        console.log('✓ Monthly billing selection saved successfully');
                    } else {
                        console.error('Failed to save monthly billing selection:', response);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error saving monthly billing selection:', error);
                }
            });
        }
    }

    function restorePreviouslySelectedMethod() {
        console.log('Checking for previously selected monthly billing method...');

        if (typeof monthlyBilling === 'undefined') {
            console.log('monthlyBilling not available, skipping restore');
            return;
        }

        $.ajax({
            type: "POST",
            url: monthlyBilling.ajaxUrl,
            data: {
                action: "get_selected_monthly_billing_method",
                nonce: monthlyBilling.checkoutNonce
            },
            success: function (response) {
                console.log('Monthly billing method check response:', response);
                if (response.success && response.data.selected_method) {
                    const selectedMethod = response.data.selected_method;
                    console.log('Previously selected method found:', selectedMethod);
                    restoreAccordionState(selectedMethod);
                } else {
                    console.log('No previously selected monthly billing method found');
                }
            },
            error: function (xhr, status, error) {
                console.error('Error checking monthly billing method:', error);
            }
        });
    }

    function restoreAccordionState(method) {
        console.log('Restoring accordion state for method:', method);

        const header = $(`.payment-option-header[data-option="${method}"]`);
        const radio = header.find('input[type="radio"]');
        const content = $('#' + method + '-content');
        const arrow = header.find('.accordion-arrow');

        if (header.length > 0) {
            // Close all sections first
            $('.payment-option-header').removeClass('active');
            $('.payment-option-content').hide();
            $('.accordion-arrow').text('▼');
            $('input[name="monthly_payment_method"]').prop('checked', false);

            // Open the selected section
            header.addClass('active');
            content.show();
            arrow.text('▲');
            radio.prop('checked', true);

            // Mark as confirmed and set form state
            markMethodAsConfirmed(method);

            // **FIX 3: Update button state based on method to show confirmation**
            if (method === 'payafter') {
                $('.confirm-payafter-btn').text('Pay After Confirmed ✓').addClass('confirmed');
            } else if (method === 'cc') {
                $('.validate-card-btn').text('Card Confirmed ✓').addClass('confirmed');
                $('.cc-validation-message').html('<div style="color: #27ae60; padding: 10px; background: #e8f5e8; border: 1px solid #27ae60; border-radius: 4px;"><strong>✓ Card details confirmed!</strong><br>Your credit card information has been validated for monthly billing.</div>').show();
            } else if (method === 'bank') {
                $('.validate-bank-btn').text('Bank Details Confirmed ✓').addClass('confirmed');
                $('.bank-validation-message').html('<div style="color: #27ae60; padding: 10px; background: #e8f5e8; border: 1px solid #27ae60; border-radius: 4px;"><strong>✓ Bank details confirmed!</strong><br>Your banking information has been saved for monthly billing.</div>').show();
            }

            console.log('✓ Accordion state restored for:', method);
        }
    }

    function showRefreshNotification() {
        const notification = $('<div class="pay-after-notification" style="background: #e8f5e8; border: 1px solid #4caf50; color: #2e7d32; padding: 10px; margin: 10px 0; border-radius: 4px;">' +
            '✓ Pay After deposit added successfully!' +
            '</div>');

        if ($('.monthly-billing-section').length > 0) {
            $('.monthly-billing-section').after(notification);
        } else if ($('#monthly-billing-section').length > 0) {
            $('#monthly-billing-section').after(notification);
        } else {
            $('body').prepend(notification);
        }

        setTimeout(function () {
            $('.pay-after-notification').fadeOut();
        }, 10000);
    }
});