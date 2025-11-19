jQuery(document).ready(function ($) {
    // Phone number formatting for billing_phone field
    const phoneInput = $('#billing_phone');

    if (phoneInput.length) {
        // Format phone number as user types
        phoneInput.on('input', function () {
            let value = $(this).val();

            // Remove all non-digit characters
            let digitsOnly = value.replace(/\D/g, '');

            // Limit to 10 digits
            digitsOnly = digitsOnly.substring(0, 10);

            // Format the phone number
            let formatted = '';
            if (digitsOnly.length > 0) {
                if (digitsOnly.length <= 3) {
                    formatted = '(' + digitsOnly;
                } else if (digitsOnly.length <= 6) {
                    formatted = '(' + digitsOnly.substring(0, 3) + ') ' + digitsOnly.substring(3);
                } else {
                    formatted = '(' + digitsOnly.substring(0, 3) + ') ' + digitsOnly.substring(3, 6) + '-' + digitsOnly.substring(6);
                }
            }

            // Update the input value
            $(this).val(formatted);
        });

        // Prevent non-numeric input (except for formatting characters)
        phoneInput.on('keypress', function (e) {
            // Allow: backspace, delete, tab, escape, enter
            if ($.inArray(e.keyCode, [46, 8, 9, 27, 13]) !== -1 ||
                // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
                (e.keyCode === 65 && e.ctrlKey === true) ||
                (e.keyCode === 67 && e.ctrlKey === true) ||
                (e.keyCode === 86 && e.ctrlKey === true) ||
                (e.keyCode === 88 && e.ctrlKey === true)) {
                return;
            }

            // Ensure that it is a number and stop the keypress
            if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                e.preventDefault();
            }
        });

        // Handle paste events
        phoneInput.on('paste', function (e) {
            setTimeout(function () {
                let value = phoneInput.val();
                let digitsOnly = value.replace(/\D/g, '');
                digitsOnly = digitsOnly.substring(0, 10);

                let formatted = '';
                if (digitsOnly.length > 0) {
                    if (digitsOnly.length <= 3) {
                        formatted = '(' + digitsOnly;
                    } else if (digitsOnly.length <= 6) {
                        formatted = '(' + digitsOnly.substring(0, 3) + ') ' + digitsOnly.substring(3);
                    } else {
                        formatted = '(' + digitsOnly.substring(0, 3) + ') ' + digitsOnly.substring(3, 6) + '-' + digitsOnly.substring(6);
                    }
                }

                phoneInput.val(formatted);
            }, 10);
        });
    }
});