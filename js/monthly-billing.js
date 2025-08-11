/**
 * Monthly Billing Functionality for Checkout - COMPLETE UPDATED VERSION
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

        // Add hidden form fields for checkout validation
        addHiddenFormFields();

        // **NEW: Restore previously selected method after initialization**
        setTimeout(function () {
            restorePreviouslySelectedMethod();
        }, 1000); // Small delay to ensure everything is loaded

        console.log('Monthly billing initialization complete');
    }

    function ensureFieldsExistInCorrectSections() {
        console.log('Ensuring fields exist in correct sections...');

        const bankContainer = $('#bank-content .bank-form-fields');
        const ccContainer = $('#cc-content .cc-form-fields');

        // FIXED: Bank fields with compact HTML (no extra line breaks)
        if (bankContainer.length > 0 && bankContainer.find('input[name="bank_first_name"]').length === 0) {
            console.log('Creating bank fields in bank container');
            bankContainer.html(`<div class="account-type-wrapper" style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;"><label style="display: block; margin-bottom: 10px; font-weight: bold;">Account Type <abbr class="required" title="required">*</abbr></label><div style="display: flex; gap: 20px; margin-bottom: 15px;"><label style="margin: 0; cursor: pointer;"><input type="radio" name="bank_account_type" value="chequing" style="margin-right: 5px;" required> Chequing</label><label style="margin: 0; cursor: pointer;"><input type="radio" name="bank_account_type" value="savings" style="margin-right: 5px;" required> Savings</label></div></div><p class="form-row form-row-first"><label for="bank_first_name">First Name <abbr class="required" title="required">*</abbr></label><input type="text" class="input-text" name="bank_first_name" id="bank_first_name" placeholder="First name" required></p><p class="form-row form-row-last"><label for="bank_last_name">Last Name <abbr class="required" title="required">*</abbr></label><input type="text" class="input-text" name="bank_last_name" id="bank_last_name" placeholder="Last name" required></p><p class="form-row form-row-wide"><label for="bank_financial_institution">Financial Institution <abbr class="required" title="required">*</abbr></label><input type="text" class="input-text" name="bank_financial_institution" id="bank_financial_institution" placeholder="Bank name" required></p><p class="form-row form-row-first"><label for="bank_transit_number">Transit Number <abbr class="required" title="required">*</abbr></label><input type="text" class="input-text" name="bank_transit_number" id="bank_transit_number" placeholder="5 digits" maxlength="5" pattern="[0-9]{5}" required></p><p class="form-row form-row-last"><label for="bank_institution_number">Institution Number <abbr class="required" title="required">*</abbr></label><input type="text" class="input-text" name="bank_institution_number" id="bank_institution_number" placeholder="3 digits" maxlength="3" pattern="[0-9]{3}" required></p><p class="form-row form-row-wide"><label for="bank_account_number">Account Number <abbr class="required" title="required">*</abbr></label><input type="text" class="input-text" name="bank_account_number" id="bank_account_number" placeholder="7+ digits" minlength="7" pattern="[0-9]{7,}" required></p><div style="clear: both;"></div><div class="bank-validation-message" style="display:none; margin-top: 10px;"></div><button type="button" class="button confirm-bank-btn" style="margin-top: 10px;">Confirm Bank Details</button></div>`);
        }

        // FIXED: CC fields with compact HTML (no extra line breaks) 
        if (ccContainer.length > 0 && ccContainer.find('input[name="cc_cardholder_name"]').length === 0) {
            console.log('Creating CC fields in CC container');
            ccContainer.html(`<p class="form-row form-row-wide"><label for="cc_cardholder_name">Cardholder Name <abbr class="required" title="required">*</abbr></label><input type="text" class="input-text" name="cc_cardholder_name" id="cc_cardholder_name" placeholder="Full name as it appears on card" required></p><p class="form-row form-row-wide"><label for="cc_card_number">Card Number <abbr class="required" title="required">*</abbr></label><input type="text" class="input-text" name="cc_card_number" id="cc_card_number" placeholder="1234 5678 9012 3456" maxlength="19" required></p><p class="form-row form-row-first"><label for="cc_expiry">Expiry Date <abbr class="required" title="required">*</abbr></label><input type="text" class="input-text" name="cc_expiry" id="cc_expiry" placeholder="MM/YY" maxlength="5" required></p><p class="form-row form-row-last"><label for="cc_cvv">CVV <abbr class="required" title="required">*</abbr></label><input type="text" class="input-text" name="cc_cvv" id="cc_cvv" placeholder="123" maxlength="4" required></p><p class="form-row form-row-wide"><label for="cc_postal_code">Billing Postal Code <abbr class="required" title="required">*</abbr></label><input type="text" class="input-text" name="cc_postal_code" id="cc_postal_code" placeholder="A1A 1A1" maxlength="7" class="forceuppercase" required><small style="display: block; color: #666; font-size: 12px; margin-top: 3px; font-style: italic;">Enter the postal code from your credit card billing statement</small></p><div style="clear: both;"></div><div class="cc-validation-message" style="display:none; margin-top: 10px;"></div><button type="button" class="button validate-card-btn" style="margin-top: 10px;">Validate & Confirm Card</button>`);
        }

        console.log('Field creation complete');
    }

    // FIXED: Accordion function with proper deselection logic
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
            const isCurrentlyActive = $(this).hasClass('active');

            console.log('Header clicked for option:', option, 'Currently active:', isCurrentlyActive);

            // If clicking on currently active header, close it and deselect
            if (isCurrentlyActive) {
                content.slideUp(300);
                $(this).removeClass('active');
                arrow.text('▼');
                radio.prop('checked', false);
                updateSelectedMethod('');
                resetConfirmations();
                return;
            }

            // Close all other sections first
            $('.payment-option-header').removeClass('active');
            $('.payment-content').slideUp(300);
            $('.accordion-arrow').text('▼');
            $('input[name="monthly_billing_method"]').prop('checked', false);

            // Reset confirmations when switching methods
            resetConfirmations();

            // Open the clicked section
            $(this).addClass('active');
            content.slideDown(300);
            arrow.text('▲');
            radio.prop('checked', true);
            updateSelectedMethod(option);
        });

        // Handle radio button changes directly
        $(document).on('change', 'input[name="monthly_billing_method"]', function () {
            const option = $(this).val();
            const header = $(this).closest('.payment-option-header');
            const content = $('#' + option + '-content');
            const arrow = header.find('.accordion-arrow');

            console.log('Radio changed to:', option);

            if ($(this).is(':checked')) {
                // Close all sections first
                $('.payment-option-header').removeClass('active');
                $('.payment-content').slideUp(300);
                $('.accordion-arrow').text('▼');

                // Reset confirmations when switching methods
                resetConfirmations();

                // Open selected section
                header.addClass('active');
                content.slideDown(300);
                arrow.text('▲');
                updateSelectedMethod(option);
            }
        });
    }

    function initializeFormFormatting() {
        // Card number formatting
        $(document).on('input', '#cc_card_number', function () {
            let value = $(this).val().replace(/\s/g, '');
            let formattedValue = value.replace(/(.{4})/g, '$1 ').trim();
            if (formattedValue !== $(this).val()) {
                $(this).val(formattedValue);
            }
        });

        // Expiry date formatting
        $(document).on('input', '#cc_expiry', function () {
            let value = $(this).val().replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            $(this).val(value);
        });

        // CVV - numbers only
        $(document).on('input', '#cc_cvv', function () {
            $(this).val($(this).val().replace(/\D/g, ''));
        });

        // Postal code formatting
        $(document).on('input', '#cc_postal_code', function () {
            let value = $(this).val().replace(/\s/g, '').toUpperCase();
            if (value.length > 3) {
                value = value.substring(0, 3) + ' ' + value.substring(3, 6);
            }
            $(this).val(value);
        });

        // Bank number fields - numbers only
        $(document).on('input', '#bank_transit_number, #bank_institution_number, #bank_account_number', function () {
            $(this).val($(this).val().replace(/\D/g, ''));
        });
    }

    function initializeFormValidation() {
        // Real-time validation for required fields
        $(document).on('blur', '.cc-form-fields input[required], .bank-form-fields input[required]', function () {
            const $field = $(this);
            const value = $field.val().trim();

            if (value === '') {
                $field.addClass('error').removeClass('valid');
            } else {
                $field.removeClass('error').addClass('valid');
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
            const messageDiv = $('.cc-validation-message');

            // Reset previous state
            messageDiv.hide().removeClass('success error');
            $('.cc-form-fields input').removeClass('error');

            // Gather card data
            const cardData = {
                cardholder_name: $('#cc_cardholder_name').val().trim(),
                card_number: $('#cc_card_number').val().replace(/\s/g, ''),
                expiry: $('#cc_expiry').val(),
                cvv: $('#cc_cvv').val(),
                postal_code: $('#cc_postal_code').val().replace(/\s/g, '')
            };

            // Basic validation
            let hasErrors = false;
            const errors = [];

            if (!cardData.cardholder_name) {
                $('#cc_cardholder_name').addClass('error');
                errors.push('Cardholder name is required');
                hasErrors = true;
            }

            if (!cardData.card_number || cardData.card_number.length < 13) {
                $('#cc_card_number').addClass('error');
                errors.push('Valid card number is required');
                hasErrors = true;
            }

            if (!cardData.expiry || !/^\d{2}\/\d{2}$/.test(cardData.expiry)) {
                $('#cc_expiry').addClass('error');
                errors.push('Valid expiry date (MM/YY) is required');
                hasErrors = true;
            }

            if (!cardData.cvv || cardData.cvv.length < 3) {
                $('#cc_cvv').addClass('error');
                errors.push('Valid CVV is required');
                hasErrors = true;
            }

            if (!cardData.postal_code || !/^[A-Z]\d[A-Z]\s?\d[A-Z]\d$/.test(cardData.postal_code)) {
                $('#cc_postal_code').addClass('error');
                errors.push('Valid Canadian postal code is required');
                hasErrors = true;
            }

            if (hasErrors) {
                messageDiv.addClass('error').html('<div style="color: #d32f2f; padding: 10px; background: #ffebee; border-radius: 4px;"><strong>Please correct the following errors:</strong><ul style="margin: 5px 0 0 20px;">' + errors.map(e => '<li>' + e + '</li>').join('') + '</ul></div>').show();
                return;
            }

            // Show loading state
            button.prop('disabled', true).text('Validating...');

            // AJAX validation
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
                        // Show success message
                        messageDiv.addClass('success').html('<div style="color: #2e7d32; padding: 10px; background: #e8f5e8; border-radius: 4px;"><strong>✓ Card validated successfully!</strong><br>Your credit card details have been confirmed and your monthly billing method is now set up.</div>').show();

                        // Update button state
                        button.text('Card Confirmed ✓').addClass('confirmed');

                        // **NEW: Save the selection to user meta**
                        saveMonthlyBillingSelection('cc', {
                            monthly_bill_payment_option: 'cc',
                            cc_monthly_billing_card_number: btoa(cardData.card_number), // Base64 encode like the old system
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

                        // Mark this method as confirmed
                        markMethodAsConfirmed('cc');

                        // Clear other monthly billing options
                        clearOtherMonthlyBillingOptions('cc');

                    } else {
                        messageDiv.addClass('error').html('<div style="color: #d32f2f; padding: 10px; background: #ffebee; border-radius: 4px;"><strong>Validation Failed</strong><br>' + (response.data?.message || 'Please check your card details and try again.') + '</div>').show();
                        button.text('Validate & Confirm Card').prop('disabled', false);
                    }
                },
                error: function () {
                    messageDiv.addClass('error').html('<div style="color: #d32f2f; padding: 10px; background: #ffebee; border-radius: 4px;"><strong>Connection Error</strong><br>Please check your connection and try again.</div>').show();
                    button.text('Validate & Confirm Card').prop('disabled', false);
                }
            });
        });
    }

    function initializeBankValidation() {
        $(document).on('click', '.confirm-bank-btn', function () {
            const button = $(this);
            const messageDiv = $('.bank-validation-message');

            // Reset previous validation state
            messageDiv.hide().removeClass('success error');
            $('.bank-form-fields input').removeClass('error');
            $('.account-type-wrapper').removeClass('error');

            // Gather form data
            const bankData = {
                account_type: $('input[name="bank_account_type"]:checked').val(),
                first_name: $('#bank_first_name').val().trim(),
                last_name: $('#bank_last_name').val().trim(),
                financial_institution: $('#bank_financial_institution').val().trim(),
                transit_number: $('#bank_transit_number').val().trim(),
                institution_number: $('#bank_institution_number').val().trim(),
                account_number: $('#bank_account_number').val().trim()
            };

            // Validation
            let hasErrors = false;
            const errors = [];

            if (!bankData.account_type) {
                $('.account-type-wrapper').addClass('error');
                errors.push('Account type is required');
                hasErrors = true;
            }

            if (!bankData.first_name) {
                $('#bank_first_name').addClass('error');
                errors.push('First name is required');
                hasErrors = true;
            }

            if (!bankData.last_name) {
                $('#bank_last_name').addClass('error');
                errors.push('Last name is required');
                hasErrors = true;
            }

            if (!bankData.financial_institution) {
                $('#bank_financial_institution').addClass('error');
                errors.push('Financial institution is required');
                hasErrors = true;
            }

            if (!bankData.transit_number || bankData.transit_number.length !== 5) {
                $('#bank_transit_number').addClass('error');
                errors.push('Transit number must be 5 digits');
                hasErrors = true;
            }

            if (!bankData.institution_number || bankData.institution_number.length !== 3) {
                $('#bank_institution_number').addClass('error');
                errors.push('Institution number must be 3 digits');
                hasErrors = true;
            }

            if (!bankData.account_number || bankData.account_number.length < 7) {
                $('#bank_account_number').addClass('error');
                errors.push('Account number must be at least 7 digits');
                hasErrors = true;
            }

            if (hasErrors) {
                messageDiv.addClass('error').html('<div style="color: #d32f2f; padding: 10px; background: #ffebee; border-radius: 4px;"><strong>Please correct the following errors:</strong><ul style="margin: 5px 0 0 20px;">' + errors.map(e => '<li>' + e + '</li>').join('') + '</ul></div>').show();
                return;
            }

            // Show success (no server validation for bank details)
            messageDiv.addClass('success').html('<div style="color: #2e7d32; padding: 10px; background: #e8f5e8; border-radius: 4px;"><strong>✓ Bank details confirmed!</strong><br>Your banking information has been saved for monthly billing.</div>').show();

            // Update button state
            button.text('Bank Details Confirmed ✓').addClass('confirmed');

            // **NEW: Save the selection to user meta**
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

            // Mark this method as confirmed
            markMethodAsConfirmed('bank');

            // Clear other monthly billing options
            clearOtherMonthlyBillingOptions('bank');
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
                        button.text('Pay After Confirmed ✓').addClass('confirmed');

                        // Mark this method as confirmed
                        markMethodAsConfirmed('payafter');

                        // Trigger checkout update
                        $('body').trigger('update_checkout');

                        // **FIXED** - Update the upfront fee summary table
                        updateFeeTables();

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

    // **ADDED** - Function to update fee tables (same mechanism as product selections)
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
        $.ajax({
            type: "POST",
            url: monthlyBilling.ajaxUrl,
            data: {
                action: "refresh_upfront_summary_shortcode",
                nonce: monthlyBilling.nonce
            },
            success: function (response) {
                console.log('Upfront table refresh response:', response);
                if (response.success) {
                    console.log('✓ Replacing .upfront-fee-table with updated content...');
                    $('.upfront-fee-table').replaceWith(response.data.upfront_table);
                    console.log('✓ Upfront fee table updated successfully');

                    // **NEW: Also update the Moneris payment amount**
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

    // **NEW FUNCTION: Update Moneris payment amount**
    function updateMonerisPaymentAmount() {
        // Check if Moneris payment amount container exists
        if ($('.moneris-payment-amount').length === 0) {
            console.log('No .moneris-payment-amount container found, skipping...');
            return;
        }

        console.log('Updating Moneris payment amount...');

        $.ajax({
            type: "POST",
            url: monthlyBilling.ajaxUrl,
            data: {
                action: "update_moneris_payment_amount",
                nonce: monthlyBilling.nonce
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

    // Enhanced resetConfirmations function
    function resetConfirmations() {
        console.log('Resetting confirmations...');

        // Update hidden form fields
        $('input[name="monthly_billing_confirmed"]').val('0');

        // Reset button states
        $('.validate-card-btn, .confirm-bank-btn, .confirm-payafter-btn')
            .removeClass('confirmed')
            .prop('disabled', false);
        $('.validate-card-btn').text('Validate & Confirm Card');
        $('.confirm-bank-btn').text('Confirm Bank Details');
        $('.confirm-payafter-btn').text('Confirm Pay After');

        // Hide validation messages
        $('.cc-validation-message, .bank-validation-message').hide();

        // Reset field styling
        $('.cc-form-fields input, .bank-form-fields input').removeClass('error valid');
        $('.account-type-wrapper').removeClass('error');

        // Reset visual confirmation states
        $('.payment-option-header').removeClass('confirmed-method');
        $('.payment-option-header .accordion-arrow').removeClass('confirmed-arrow').text('▼');
    }

    // Enhanced function to clear other monthly billing options
    function clearOtherMonthlyBillingOptions(keepOption) {
        console.log('Clearing other monthly billing options, keeping:', keepOption);

        // Remove deposits from cart (Pay After option)
        if (typeof monthlyBilling !== 'undefined') {
            $.ajax({
                url: monthlyBilling.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'remove_monthly_billing_deposits',
                    keep_option: keepOption,
                    nonce: monthlyBilling.nonce
                },
                success: function (response) {
                    console.log('Other monthly billing options cleared:', response);

                    // Update fee tables after clearing deposits
                    updateFeeTables();
                },
                error: function (xhr, status, error) {
                    console.error('Error clearing other options:', error);
                }
            });
        }

        // Reset other button states
        if (keepOption !== 'cc') {
            $('.validate-card-btn').removeClass('confirmed').prop('disabled', false).text('Validate & Confirm Card');
        }
        if (keepOption !== 'bank') {
            $('.confirm-bank-btn').removeClass('confirmed').prop('disabled', false).text('Confirm Bank Details');
        }
        if (keepOption !== 'payafter') {
            $('.confirm-payafter-btn').removeClass('confirmed').prop('disabled', false).text('Confirm Pay After');
        }
    }

    // Enhanced function to mark method as confirmed
    function markMethodAsConfirmed(method) {
        console.log('Marking method as confirmed:', method);

        // Clear other options first
        clearOtherMonthlyBillingOptions(method);

        // Mark the selected method as confirmed
        const header = $(`.payment-option-header[data-option="${method}"]`);
        header.addClass('confirmed-method');
        header.find('.accordion-arrow').addClass('confirmed-arrow').text('✓');

        // Set the confirmation state
        $('input[name="monthly_billing_confirmed"]').val('1');
        $('input[name="selected_monthly_method"]').val(method);
    }

    // Add hidden form fields to ensure data is submitted with checkout
    function addHiddenFormFields() {
        // Remove existing hidden fields to avoid duplicates
        $('input[name="monthly_billing_confirmed"], input[name="selected_monthly_method"]').remove();

        // Add hidden fields to the checkout form
        const checkoutForm = $('form.checkout');
        if (checkoutForm.length > 0) {
            checkoutForm.append('<input type="hidden" name="monthly_billing_confirmed" value="0">');
            checkoutForm.append('<input type="hidden" name="selected_monthly_method" value="">');
        }
    }

    // Update accordion functions to set selected method
    function updateSelectedMethod(option) {
        $('input[name="selected_monthly_method"]').val(option);
        console.log('Selected monthly method set to:', option);
    }

    // Add form field copying for checkout submission
    function copyFormFieldsToCheckoutForm() {
        const selectedMethod = $('input[name="selected_monthly_method"]').val();

        if (selectedMethod === 'cc') {
            // Copy credit card fields
            const ccData = {
                'billing_first_name': $('#cc_cardholder_name').val().split(' ')[0] || '',
                'billing_last_name': $('#cc_cardholder_name').val().split(' ').slice(1).join(' ') || '',
                'billing_postcode': $('#cc_postal_code').val()
            };

            Object.keys(ccData).forEach(function (key) {
                $(`input[name="${key}"]`).val(ccData[key]);
            });
        } else if (selectedMethod === 'bank') {
            // Copy bank fields
            const bankData = {
                'billing_first_name': $('#bank_first_name').val(),
                'billing_last_name': $('#bank_last_name').val()
            };

            Object.keys(bankData).forEach(function (key) {
                $(`input[name="${key}"]`).val(bankData[key]);
            });
        }
    }

    // Copy form fields before checkout submission
    $(document).on('checkout_place_order', function () {
        copyFormFieldsToCheckoutForm();
    });

    // **NEW FUNCTION: Save monthly billing selection to user meta**
    function saveMonthlyBillingSelection(method, data) {
        console.log('Saving monthly billing selection:', method, data);

        $.ajax({
            type: "POST",
            url: monthlyBilling.ajaxUrl,
            data: {
                action: "save_monthly_billing_selection",
                method: method,
                billing_data: data,
                nonce: monthlyBilling.nonce
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

    // **NEW FUNCTION: Check for previously selected monthly billing method**
    function restorePreviouslySelectedMethod() {
        console.log('Checking for previously selected monthly billing method...');

        // Check cart contents to determine which method was previously selected
        $.ajax({
            type: "POST",
            url: monthlyBilling.ajaxUrl,
            data: {
                action: "get_selected_monthly_billing_method",
                nonce: monthlyBilling.nonce
            },
            success: function (response) {
                console.log('Monthly billing method check response:', response);
                if (response.success && response.data.selected_method) {
                    const selectedMethod = response.data.selected_method;
                    console.log('Previously selected method found:', selectedMethod);

                    // Restore the accordion state
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

    // **NEW FUNCTION: Restore accordion state for a specific method**
    function restoreAccordionState(method) {
        console.log('Restoring accordion state for method:', method);

        const header = $(`.payment-option-header[data-option="${method}"]`);
        const radio = header.find('input[type="radio"]');
        const content = $('#' + method + '-content');
        const arrow = header.find('.accordion-arrow');

        if (header.length > 0) {
            // Close all sections first
            $('.payment-option-header').removeClass('active');
            $('.payment-content').hide();
            $('.accordion-arrow').text('▼');
            $('input[name="monthly_billing_method"]').prop('checked', false);

            // Open the selected section
            header.addClass('active');
            content.show();
            arrow.text('▲');
            radio.prop('checked', true);

            // Mark as confirmed and set form state
            markMethodAsConfirmed(method);

            // Update button state based on method
            if (method === 'payafter') {
                $('.confirm-payafter-btn').text('Pay After Confirmed ✓').addClass('confirmed');
            } else if (method === 'cc') {
                // Check if CC details were previously saved
                $('.validate-card-btn').text('Card Confirmed ✓').addClass('confirmed');
            } else if (method === 'bank') {
                // Check if bank details were previously saved
                $('.confirm-bank-btn').text('Bank Details Confirmed ✓').addClass('confirmed');
            }

            console.log('✓ Accordion state restored for:', method);
        }
    }

    function showRefreshNotification() {
        // Show a subtle notification that the total has been updated but page needs refresh to see it
        const notification = $('<div class="pay-after-notification" style="background: #e8f5e8; border: 1px solid #4caf50; color: #2e7d32; padding: 10px; margin: 10px 0; border-radius: 4px;">' +
            '✓ Pay After deposit added successfully! <a href="#" onclick="window.location.reload(); return false;" style="color: #1976d2; text-decoration: underline;">Refresh page</a> to see updated totals.' +
            '</div>');

        // Find the best place to show the notification
        if ($('.monthly-billing-section').length > 0) {
            $('.monthly-billing-section').after(notification);
        } else if ($('#monthly-billing-section').length > 0) {
            $('#monthly-billing-section').after(notification);
        } else {
            $('body').prepend(notification);
        }

        // Auto-hide after 10 seconds
        setTimeout(function () {
            $('.pay-after-notification').fadeOut();
        }, 10000);
    }
});