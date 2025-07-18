/**
 * Moneris Payment Form JavaScript
 * Save this as /js/moneris-payment.js in your theme directory
 */
jQuery(document).ready(function($) {
    console.log('Moneris payment script loaded');
    
    // Format card number with spaces
    $(document).on('input', '#moneris_card_number', function() {
        let value = $(this).val().replace(/\s+/g, '').replace(/[^0-9]/gi, '');
        let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
        if (formattedValue.length > 19) {
            formattedValue = formattedValue.substring(0, 19);
        }
        $(this).val(formattedValue);
        
        // Validate card number and show card type
        validateCardNumber(value);
    });
    
    // Format expiry date
    $(document).on('input', '#moneris_expiry_date', function() {
        let value = $(this).val().replace(/\D/g, '');
        if (value.length >= 2) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }
        $(this).val(value);
        
        // Validate expiry date
        validateExpiryDate(value);
    });
    
    // Format CVV (numbers only)
    $(document).on('input', '#moneris_cvv', function() {
        $(this).val($(this).val().replace(/\D/g, ''));
    });
    
    // Format postal code
    $(document).on('input', '#moneris_postal_code', function() {
        let value = $(this).val().toUpperCase().replace(/[^A-Z0-9]/g, '');
        if (value.length > 3) {
            value = value.substring(0, 3) + ' ' + value.substring(3, 6);
        }
        $(this).val(value);
    });
    
    // Only allow letters and spaces for cardholder name
    $(document).on('input', '#moneris_cardholder_name', function() {
        $(this).val($(this).val().replace(/[^a-zA-Z\s]/g, ''));
    });
    
    // Handle form submission
    $(document).on('submit', '#moneris-payment-form', function(e) {
        e.preventDefault();
        
        console.log('Payment form submitted');
        
        // Validate form before submission
        if (!validateForm()) {
            return false;
        }
        
        // Disable submit button and show loading
        const $submitBtn = $('.moneris-submit-btn');
        const originalText = $submitBtn.text();
        $submitBtn.prop('disabled', true).text('Processing...');
        $('.moneris-loading').show();
        $('.moneris-message-container').empty();
        
        // Collect form data
        const formData = {
            action: 'process_moneris_payment',
            nonce: $('input[name="nonce"]').val(),
            cardholder_name: $('#moneris_cardholder_name').val().trim(),
            card_number: $('#moneris_card_number').val().trim(),
            expiry_date: $('#moneris_expiry_date').val().trim(),
            cvv: $('#moneris_cvv').val().trim(),
            postal_code: $('#moneris_postal_code').val().trim(),
            redirect_url: $('input[name="redirect_url"]').val(),
            success_message: $('input[name="success_message"]').val()
        };
        
        console.log('Sending payment data:', formData);
        
        // Send AJAX request
        $.ajax({
            url: monerisPayment.ajaxUrl,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log('Payment response:', response);
                
                $('.moneris-loading').hide();
                $submitBtn.prop('disabled', false).text(originalText);
                
                if (response.success) {
                    // Payment successful
                    showMessage('success', response.data.message);
                    
                    // Clear form
                    $('#moneris-payment-form')[0].reset();
                    
                    // Redirect if URL provided
                    if (response.data.redirect_url) {
                        setTimeout(function() {
                            window.location.href = response.data.redirect_url;
                        }, 2000);
                    }
                    
                    // Trigger custom event for successful payment
                    $(document).trigger('monerisPaymentSuccess', response.data);
                    
                } else {
                    // Payment failed
                    showMessage('error', response.data.message || 'Payment failed. Please try again.');
                    
                    // Trigger custom event for failed payment
                    $(document).trigger('monerisPaymentError', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('Payment AJAX error:', error);
                console.error('Response:', xhr.responseText);
                
                $('.moneris-loading').hide();
                $submitBtn.prop('disabled', false).text(originalText);
                
                showMessage('error', 'Network error occurred. Please check your connection and try again.');
                
                // Trigger custom event for network error
                $(document).trigger('monerisPaymentError', {message: 'Network error'});
            }
        });
    });
    
    // Validate entire form
    function validateForm() {
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
        
        if (!isValid) {
            showMessage('error', 'Please correct the highlighted fields.');
        }
        
        return isValid;
    }
    
    // Validate card number using Luhn algorithm
    function validateCardNumber(number) {
        number = number.replace(/\D/g, '');
        
        if (number.length < 13 || number.length > 19) {
            return false;
        }
        
        let sum = 0;
        let alternate = false;
        
        for (let i = number.length - 1; i >= 0; i--) {
            let digit = parseInt(number.charAt(i), 10);
            
            if (alternate) {
                digit *= 2;
                if (digit > 9) {
                    digit = (digit % 10) + 1;
                }
            }
            
            sum += digit;
            alternate = !alternate;
        }
        
        return (sum % 10 === 0);
    }
    
    // Validate expiry date
    function validateExpiryDate(value) {
        if (!/^\d{2}\/\d{2}$/.test(value)) {
            return false;
        }
        
        const [month, year] = value.split('/');
        const monthNum = parseInt(month, 10);
        const yearNum = parseInt('20' + year, 10);
        
        if (monthNum < 1 || monthNum > 12) {
            return false;
        }
        
        const currentDate = new Date();
        const currentYear = currentDate.getFullYear();
        const currentMonth = currentDate.getMonth() + 1;
        
        if (yearNum < currentYear || (yearNum === currentYear && monthNum < currentMonth)) {
            return false;
        }
        
        return true;
    }
    
    // Validate Canadian postal code
    function validateCanadianPostalCode(postalCode) {
        const pattern = /^[A-Z]\d[A-Z]\d[A-Z]\d$/;
        return pattern.test(postalCode.replace(/\s/g, '').toUpperCase());
    }
    
    // Show success/error messages
    function showMessage(type, message) {
        const $container = $('.moneris-message-container');
        const messageHtml = `<div class="moneris-message ${type}">${message}</div>`;
        $container.html(messageHtml);
        
        // Scroll to message
        $('html, body').animate({
            scrollTop: $container.offset().top - 20
        }, 300);
        
        // Auto-hide success messages after 5 seconds
        if (type === 'success') {
            setTimeout(function() {
                $container.find('.moneris-message.success').fadeOut();
            }, 5000);
        }
    }
    
    // Add CSS for error styling
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .moneris-payment-form input.error {
                border-color: #dc3545;
                box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
            }
            .moneris-payment-form input.valid {
                border-color: #28a745;
            }
        `)
        .appendTo('head');
    
    // Real-time validation feedback
    $(document).on('blur', '#moneris-payment-form input[required]', function() {
        const $input = $(this);
        const fieldName = $input.attr('name');
        
        switch(fieldName) {
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
    });
});

// Custom events for external integration
// Usage: $(document).on('monerisPaymentSuccess', function(event, data) { ... });
// Usage: $(document).on('monerisPaymentError', function(event, data) { ... });