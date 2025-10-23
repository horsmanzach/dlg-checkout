jQuery(document).ready(function ($) {

    console.log('Thank You page loaded');

    // Function to format currency
    function formatCurrency(amount) {
        if (!amount || isNaN(amount)) {
            return '$0.00';
        }
        return '$' + parseFloat(amount).toFixed(2);
    }

    // NEW: Function to format timestamp for display - MATCHES ORDER DATE FORMAT
    function formatTermsTimestamp(timestamp) {
        if (!timestamp) {
            return '';
        }

        try {
            const date = new Date(timestamp);

            // Format to match: "October 14, 2025 at 3:45 PM"
            // This matches the PHP format: 'F j, Y \a\t g:i A'
            const options = {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            };

            const formatted = date.toLocaleString('en-US', options);

            // The toLocaleString gives us "October 14, 2025 at 3:45 PM" which matches perfectly
            return formatted;
        } catch (e) {
            console.error('Error formatting timestamp:', e);
            return timestamp; // Return raw timestamp if formatting fails
        }
    }

    // Function to load Thank You page data
    function loadThankYouData() {
        console.log('Fetching Thank You page data...');

        $.ajax({
            url: thankYouData.ajaxurl, // WordPress AJAX URL
            type: 'POST',
            data: {
                action: 'get_thank_you_data'
            },
            dataType: 'json',
            success: function (response) {
                console.log('Thank You data received:', response);

                if (response.success && response.data) {
                    populateThankYouPage(response.data);
                } else {
                    console.error('Failed to load Thank You data');
                    showError('Unable to load order details. Please contact support.');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX error:', error);
                showError('Unable to load order details. Please contact support.');
            }
        });
    }

    // Function to populate all Thank You page data
    function populateThankYouPage(data) {
        console.log('Populating Thank You page with data');

        // Populate Invoice Number
        if (data.invoice_number) {
            $('#invoice-number').text(data.invoice_number);
        }

        // Populate Order Information
        if (data.order_timestamp) {
            $('#order-timestamp').text(data.order_timestamp);
        }
        if (data.customer_ip) {
            $('#customer-ip').text(data.customer_ip);
        }

        // NEW: Populate Terms & Conditions timestamp
        if (data.terms_timestamp) {
            const formattedTimestamp = formatTermsTimestamp(data.terms_timestamp);
            $('#terms-timestamp').text(formattedTimestamp);
            console.log('Terms timestamp populated:', formattedTimestamp);

            // Show the terms timestamp row if it was hidden
            $('#terms-timestamp-row').show();
        } else {
            // Hide the terms timestamp row if no timestamp available
            $('#terms-timestamp-row').hide();
            console.log('No terms timestamp available');
        }

        // Populate Customer Information
        if (data.customer) {
            $('#customer-first-name').text(data.customer.first_name || '');
            $('#customer-last-name').text(data.customer.last_name || '');
            $('#customer-email').text(data.customer.email || '');
            $('#customer-phone').text(data.customer.phone || '');
            $('#customer-address').text(data.customer.address || '');
            $('#customer-city').text(data.customer.city || '');
            $('#customer-province').text(data.customer.province || '');
            $('#customer-postal-code').text(data.customer.postal_code || '');
        }

        // Populate Upfront Payment Summary
        if (data.upfront_summary) {
            populateUpfrontSummary(data.upfront_summary);
        }

        // Populate card last 4 digits
        if (data.card_last_4) {
            $('#card-last-4').text(data.card_last_4);
        }

        // Populate Monthly Billing Summary
        if (data.monthly_summary) {
            populateMonthlySummary(data.monthly_summary);
        }

        // Populate monthly payment method
        if (data.monthly_payment_method_display) {
            $('#monthly-payment-method').text(data.monthly_payment_method_display);
        }

        console.log('Thank You page populated successfully');
    }

    // Function to populate upfront summary table (2 columns)
    function populateUpfrontSummary(summary) {
        console.log('Populating upfront summary:', summary);

        var $tbody = $('#upfront-items');
        $tbody.empty(); // Clear any existing rows

        // Add line items (skip subtotal, taxes, grand_total, and deposit as they go in footer)
        var skipKeys = ['subtotal', 'taxes', 'grand_total', 'deposit', 'ModemPurchaseOption'];

        $.each(summary, function (key, value) {
            if (skipKeys.indexOf(key) === -1 && value && value[1] > 0) {
                var itemName = value[0] || key;
                var itemAmount = formatCurrency(value[1]);

                var row = '<tr>' +
                    '<td>' + itemName + '</td>' +
                    '<td>' + itemAmount + '</td>' +
                    '</tr>';

                $tbody.append(row);
            }
        });

        // Populate footer totals
        if (summary.subtotal) {
            $('#upfront-subtotal').text(formatCurrency(summary.subtotal[1]));
        }
        if (summary.taxes) {
            $('#upfront-tax').text(formatCurrency(summary.taxes[1]));
        }
        if (summary.grand_total) {
            $('#upfront-total').text(formatCurrency(summary.grand_total[1]));
        }
    }

    // Function to populate monthly summary table (2 columns)
    function populateMonthlySummary(summary) {
        console.log('Populating monthly summary:', summary);

        var $tbody = $('#monthly-items');
        $tbody.empty(); // Clear any existing rows

        // Add line items (skip subtotal, taxes, grand_total)
        var skipKeys = ['subtotal', 'taxes', 'grand_total'];

        $.each(summary, function (key, value) {
            if (skipKeys.indexOf(key) === -1 && value && value[1] > 0) {
                var itemName = value[0] || key;
                var itemAmount = formatCurrency(value[1]);

                var row = '<tr>' +
                    '<td>' + itemName + '</td>' +
                    '<td>' + itemAmount + '</td>' +
                    '</tr>';

                $tbody.append(row);
            }
        });

        // Populate footer totals
        if (summary.subtotal) {
            $('#monthly-subtotal').text(formatCurrency(summary.subtotal[1]));
        }
        if (summary.taxes) {
            $('#monthly-tax').text(formatCurrency(summary.taxes[1]));
        }
        if (summary.grand_total) {
            $('#monthly-total').text(formatCurrency(summary.grand_total[1]));
        }
    }

    // Function to show error message
    function showError(message) {
        $('.thank-you-page-container').prepend(
            '<div class="error-message" style="background: #f8d7da; color: #721c24; padding: 15px; margin-bottom: 20px; border: 1px solid #f5c6cb; border-radius: 4px;">' +
            message +
            '</div>'
        );
    }

    // Load data when page is ready
    loadThankYouData();

});