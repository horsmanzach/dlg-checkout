/**
 * Updated Moneris Payment JavaScript - Supports Separate Complete Payment Button
 * File: js/moneris-payment.js
 */

jQuery(document).ready(function ($) {
    console.log('Moneris Payment: Initializing with separate button support...');

    // Initialize form validation and button functionality
    initializeMonerisPayment();

    function initializeMonerisPayment() {
        // Set up form field validation
        setupFieldValidation();

        // Set up separate complete payment button
        setupCompletePaymentButton();

        // Monitor monthly billing state for button enabling/disabling
        monitorMonthlyBillingState();
    }

    function setupFieldValidation() {
        // Card number formatting
        $(document).on('input', '#moneris_card_number', function () {
            let value = $(this).val().replace(/\s/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            $(this).val(formattedValue);
            validateField($(this), 'card_number');
        });

        // Expiry date formatting
        $(document).on('input', '#moneris_expiry_date', function () {
            let value = $(this).val().replace(/[^0-9]/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            $(this).val(value);
            validateField($(this), 'expiry_date');
        });

        // CVV validation
        $(document).on('input', '#moneris_cvv', function () {
            let value = $(this).val().replace(/[^0-9]/g, '');
            $(this).val(value);
            validateField($(this), 'cvv');
        });

        // Postal code formatting
        $(document).on('input', '#moneris_postal_code', function () {
            let value = $(this).val().toUpperCase().replace(/[^A-Z0-9]/g, '');
            if (value.length > 3) {
                value = value.substring(0, 3) + ' ' + value.substring(3, 6);
            }
            $(this).val(value);
            validateField($(this), 'postal_code');
        });

        // Cardholder name validation
        $(document).on('input', '#moneris_cardholder_name', function () {
            validateField($(this), 'cardholder_name');
        });
    }

    function setupCompletePaymentButton() {
        // Handle external complete payment button click
        $(document).on('click', '#moneris-complete-payment-btn, #moneris-mobile-payment-btn', function (e) {
            e.preventDefault();

            console.log('Complete Payment button clicked');

            // Validate prerequisites before processing
            if (!validatePaymentPrerequisites()) {
                return false;
            }

            // If validation passes, process the payment
            processPayment();
        });

        // Also handle original form submission (if button is still inside form)
        $(document).on('submit', '#moneris-payment-form', function (e) {
            e.preventDefault();
            console.log('Form submitted directly');

            if (!validatePaymentPrerequisites()) {
                return false;
            }

            processPayment();
        });
    }

    function validatePaymentPrerequisites() {
        console.log('Validating payment prerequisites...');

        // 1. Check if monthly billing is confirmed
        const monthlyBillingConfirmed = $('input[name="monthly_billing_confirmed"]').val();
        const selectedMethod = $('input[name="selected_monthly_method"]').val();

        console.log('Monthly billing confirmed:', monthlyBillingConfirmed);
        console.log('Selected method:', selectedMethod);

        if (monthlyBillingConfirmed !== '1' || !selectedMethod) {
            showValidationMessage('error', 'Please complete and confirm one of the monthly billing payment methods first.');
            return false;
        }

        // 2. Validate Moneris form fields
        if (!validateMonerisForm()) {
            showValidationMessage('error', 'Please correct the payment form errors.');
            return false;
        }

        // 3. Additional check: ensure monthly billing method is actually validated
        if (!checkMonthlyBillingValidationState()) {
            showValidationMessage('error', 'Monthly billing validation incomplete. Please validate your selected payment method.');
            return false;
        }

        console.log('✓ All payment prerequisites validated');
        return true;
    }

    function checkMonthlyBillingValidationState() {
        // Check for confirmed buttons or validation messages
        const hasConfirmedCC = $('.validate-card-btn').hasClass('confirmed');
        const hasConfirmedBank = $('.validate-bank-btn').hasClass('confirmed');
        const hasConfirmedPayAfter = $('.confirm-payafter-btn').hasClass('confirmed');

        return hasConfirmedCC || hasConfirmedBank || hasConfirmedPayAfter;
    }

    function validateMonerisForm() {
        let isValid = true;
        const $form = $('#moneris-payment-form');

        // Clear previous error styling
        $form.find('input').removeClass('error');

        // Validate cardholder name
        const cardholderName = $('#moneris_cardholder_name').val().trim();
        if (cardholderName.length < 2) {
            $('#moneris_cardholder_name').addClass('error');
            isValid = false;
        }

        // Validate card number
        const cardNumber = $('#moneris_card_number').val().replace(/\s/g, '');
        if (!validateCardNumber(cardNumber)) {
            $('#moneris_card_number').addClass('error');
            isValid = false;
        }

        // Validate expiry date
        const expiryDate = $('#moneris_expiry_date').val();
        if (!validateExpiryDate(expiryDate)) {
            $('#moneris_expiry_date').addClass('error');
            isValid = false;
        }

        // Validate CVV
        const cvv = $('#moneris_cvv').val();
        if (cvv.length < 3 || cvv.length > 4) {
            $('#moneris_cvv').addClass('error');
            isValid = false;
        }

        // Validate postal code
        const postalCode = $('#moneris_postal_code').val().replace(/\s/g, '');
        if (!validateCanadianPostalCode(postalCode)) {
            $('#moneris_postal_code').addClass('error');
            isValid = false;
        }

        return isValid;
    }

    function processPayment() {
        console.log('Processing payment...');

        // Disable button and show loading
        const $submitBtn = $('#moneris-complete-payment-btn, #moneris-mobile-payment-btn');
        const originalText = $submitBtn.text();

        // Add spinner HTML and set processing state
        $submitBtn.prop('disabled', true)
            .html('<span class="payment-spinner"></span> Processing...')
            .css({
                'background-color': '#999999',
                'cursor': 'wait',
                'pointer-events': 'none'
            });

        $('.moneris-loading').show();
        $('.moneris-message-container, .moneris-payment-validation-message').empty();

        // Collect form data
        const formData = {
            action: 'process_moneris_payment',
            nonce: $('input[name="nonce"]').val(),
            cardholder_name: $('#moneris_cardholder_name').val().trim(),
            card_number: $('#moneris_card_number').val().trim(),
            expiry_date: $('#moneris_expiry_date').val().trim(),
            cvv: $('#moneris_cvv').val().trim(),
            postal_code: $('#moneris_postal_code').val().trim(),
            redirect_url: $submitBtn.data('redirect-url') || '',
            success_message: $('input[name="success_message"]').val()
        };

        console.log('Sending payment data:', formData);

        // Send AJAX request
        $.ajax({
            url: monerisPayment.ajaxUrl,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function (response) {
                console.log('Payment response:', response);

                $('.moneris-loading').hide();

                if (response.success) {
                    // Payment successful - KEEP button disabled and in processing state
                    // Do NOT re-enable or change the button back
                    $submitBtn.html('<span class="payment-spinner"></span> Redirecting...')
                        .css({
                            'background-color': '#999999',
                            'cursor': 'wait',
                            'pointer-events': 'none'
                        });

                    // Create and show green success message
                    const successMessage = `
        <div class="payment-success-message" style="color: #28a745; text-align: center; font-size: 14px; margin: 15px 0; font-weight: 500;">
            ✓ Payment Successful
        </div>
    `;

                    // Insert success message after the payment button
                    if ($('.payment-success-message').length === 0) {
                        $submitBtn.after(successMessage);
                    }

                    showValidationMessage('success', response.data.message);

                    // Clear form
                    $('#moneris-payment-form')[0].reset();

                    // Redirect if URL provided
                    const redirectUrl = response.data.redirect_url || $submitBtn.data('redirect-url');
                    if (redirectUrl) {
                        setTimeout(function () {
                            window.location.href = redirectUrl;
                        }, 500);
                    }

                    // Trigger custom event for successful payment
                    $(document).trigger('monerisPaymentSuccess', response.data);
                } else {
                    // Payment failed - only restore button on failure
                    $submitBtn.prop('disabled', false)
                        .text(originalText)
                        .css({
                            'background-color': '',
                            'cursor': '',
                            'pointer-events': ''
                        });

                    showValidationMessage('error', response.data.message || 'Payment failed. Please try again.');

                    // Trigger custom event for failed payment
                    $(document).trigger('monerisPaymentError', response.data);
                }
            },
            error: function (xhr, status, error) {
                console.error('Payment AJAX error:', error);
                console.error('Response:', xhr.responseText);

                $('.moneris-loading').hide();

                // Only restore button on error
                $submitBtn.prop('disabled', false)
                    .text(originalText)
                    .css({
                        'background-color': '',
                        'cursor': '',
                        'pointer-events': ''
                    });

                showValidationMessage('error', 'Network error occurred. Please check your connection and try again.');

                // Trigger custom event for network error
                $(document).trigger('monerisPaymentError', { message: 'Network error' });
            }
        });
    }

    function monitorMonthlyBillingState() {
        // Monitor changes in monthly billing confirmation state
        $(document).on('change', 'input[name="monthly_billing_confirmed"], input[name="selected_monthly_method"]', function () {
            updateCompletePaymentButtonState();
        });

        // Monitor monthly billing button state changes
        $(document).on('DOMSubtreeModified', '.validate-card-btn, .validate-bank-btn, .confirm-payafter-btn', function () {
            setTimeout(updateCompletePaymentButtonState, 100);
        });

        // Alternative monitoring using MutationObserver (more modern)
        if (window.MutationObserver) {
            const observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    if (mutation.type === 'attributes' &&
                        (mutation.attributeName === 'class' || mutation.attributeName === 'disabled')) {
                        updateCompletePaymentButtonState();
                    }
                });
            });

            // Observe monthly billing buttons
            $('.validate-card-btn, .validate-bank-btn, .confirm-payafter-btn').each(function () {
                observer.observe(this, { attributes: true });
            });
        }

        // Initial state check
        updateCompletePaymentButtonState();
    }

        
    function updateCompletePaymentButtonState() {
        const $button = $('#moneris-complete-payment-btn, #moneris-mobile-payment-btn');
        if ($button.length === 0) return;

        const isMonthlyBillingConfirmed = checkMonthlyBillingValidationState();
        const monthlyBillingConfirmed = $('input[name="monthly_billing_confirmed"]').val();

        if (isMonthlyBillingConfirmed && monthlyBillingConfirmed === '1') {
            $button.prop('disabled', false).removeClass('disabled');
            $('.moneris-payment-validation-message').hide();
        } else {
            $button.prop('disabled', true).addClass('disabled');
            showValidationMessage('info', 'Complete monthly billing validation to enable payment.');
        }
    }

    function showValidationMessage(type, message) {
        const $container = $('.moneris-payment-validation-message');
        if ($container.length === 0) return;

        const alertClass = type === 'error' ? 'alert-danger' :
            type === 'success' ? 'alert-success' : 'alert-info';

        $container.html(`
            <div class="alert ${alertClass}" style="margin: 10px 0; padding: 10px; border-radius: 4px;">
                ${message}
            </div>
        `).show();
    }

    function validateField($input, fieldType) {
        switch (fieldType) {
            case 'cardholder_name':
                if ($input.val().trim().length >= 2) {
                    $input.removeClass('error').addClass('valid');
                } else {
                    $input.removeClass('valid').addClass('error');
                }
                break;

            case 'card_number':
                const cardNum = $input.val().replace(/\s/g, '');
                if (validateCardNumber(cardNum)) {
                    $input.removeClass('error').addClass('valid');
                } else {
                    $input.removeClass('valid').addClass('error');
                }
                break;

            case 'expiry_date':
                if (validateExpiryDate($input.val())) {
                    $input.removeClass('error').addClass('valid');
                } else {
                    $input.removeClass('valid').addClass('error');
                }
                break;

            case 'cvv':
                const cvv = $input.val();
                if (cvv.length >= 3 && cvv.length <= 4) {
                    $input.removeClass('error').addClass('valid');
                } else {
                    $input.removeClass('valid').addClass('error');
                }
                break;

            case 'postal_code':
                const postal = $input.val().replace(/\s/g, '');
                if (validateCanadianPostalCode(postal)) {
                    $input.removeClass('error').addClass('valid');
                } else {
                    $input.removeClass('valid').addClass('error');
                }
                break;
        }
    }

    // Validation helper functions (keep existing ones)
    function validateCardNumber(number) {
        const cleanNumber = number.replace(/\s/g, '');
        if (!/^\d{13,19}$/.test(cleanNumber)) return false;

        // Luhn algorithm
        let sum = 0;
        let isEven = false;
        for (let i = cleanNumber.length - 1; i >= 0; i--) {
            let digit = parseInt(cleanNumber.charAt(i));
            if (isEven) {
                digit *= 2;
                if (digit > 9) digit -= 9;
            }
            sum += digit;
            isEven = !isEven;
        }
        return sum % 10 === 0;
    }

    function validateExpiryDate(expiry) {
        if (!/^\d{2}\/\d{2}$/.test(expiry)) return false;

        const [month, year] = expiry.split('/').map(num => parseInt(num));
        if (month < 1 || month > 12) return false;

        const currentDate = new Date();
        const currentYear = currentDate.getFullYear() % 100;
        const currentMonth = currentDate.getMonth() + 1;

        if (year < currentYear || (year === currentYear && month < currentMonth)) {
            return false;
        }

        return true;
    }

    function validateCanadianPostalCode(postal) {
        return /^[A-Za-z]\d[A-Za-z][\s-]?\d[A-Za-z]\d$/.test(postal);
    }
});

// Custom events for external integration
// Usage: $(document).on('monerisPaymentSuccess', function(event, data) { ... });
// Usage: $(document).on('monerisPaymentError', function(event, data) { ... });