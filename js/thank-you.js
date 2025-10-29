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

    // UPDATED: Function to populate all Thank You page data
    // Modified to handle combined name and full address display
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

        // Populate Terms & Conditions timestamp
        if (data.terms_timestamp) {
            const formattedTimestamp = formatTermsTimestamp(data.terms_timestamp);
            $('#terms-timestamp').text(formattedTimestamp);
            console.log('Terms timestamp populated:', formattedTimestamp);
            $('#terms-timestamp-row').show();
        } else {
            $('#terms-timestamp-row').hide();
            console.log('No terms timestamp available');
        }

        // UPDATED: Populate Customer Information with new structure
        if (data.customer) {
            // NEW: Combined Name (replaces separate first/last name rows)
            $('#customer-name').text(data.customer.full_name || '');

            // Email and Phone remain the same
            $('#customer-email').text(data.customer.email || '');
            $('#customer-phone').text(data.customer.phone || '');

            // NEW: Service Address (full address on one line)
            $('#customer-service-address').text(data.customer.service_address_full || '');

            // NEW: Shipping Address (full address on one line)
            $('#customer-shipping-address').text(data.customer.shipping_address_full || '');

            // Log for debugging
            console.log('Customer info populated:', {
                name: data.customer.full_name,
                service_address: data.customer.service_address_full,
                shipping_address: data.customer.shipping_address_full
            });
        }

        // Populate Upfront Payment Summary
        if (data.upfront_summary) {
            populateUpfrontSummary(data.upfront_summary);
        }

        // Populate card last 4 digits
        if (data.card_last_4) {
            $('#card-last-4').text(data.card_last_4);
        }

        // Populate Authorization Code and Reference Number
        if (data.auth_code) {
            $('#auth-code').text(data.auth_code);
        }
        if (data.reference_num) {
            $('#reference-num').text(data.reference_num);
        }

        // Populate Monthly Billing Summary
        if (data.monthly_summary) {
            populateMonthlySummary(data.monthly_summary);
        }

        // Populate monthly payment method
        if (data.monthly_payment_method_display) {
            var monthlyPaymentText = data.monthly_payment_method_display;
            
            // If credit card and we have last 4 digits, add them
            if (data.monthly_payment_method_display === 'Credit Card' && data.monthly_card_last_4) {
                monthlyPaymentText += ' ending in ' + data.monthly_card_last_4;
            }
            
            $('#monthly-payment-method').text(monthlyPaymentText);
        }

        console.log('Thank You page populated successfully');
    }

    // Function to populate upfront summary table (2 columns)
    // SIMPLE VERSION: Appends everything to tbody in order
    function populateUpfrontSummary(summary) {
        console.log('Populating upfront summary:', summary);

        var $tbody = $('#upfront-items');
        $tbody.empty(); // Clear any existing rows

        // Keys to handle specially
        var skipKeys = ['subtotal', 'taxes', 'grand_total', 'ModemPurchaseOption'];

        // Step 1: Add regular products (not deposits, not totals)
        $.each(summary, function (key, value) {
            if (skipKeys.indexOf(key) === -1 && value && value[1] > 0) {
                var itemName = value[0] || key;

                // Skip deposits for now
                if (itemName.toLowerCase().includes('deposit')) {
                    return true; // continue to next iteration
                }

                var itemAmount = formatCurrency(value[1]);
                var itemDates = value[2] || '';

                var row = '<tr>' +
                    '<td>' + itemName;

                // If this is Installation Fee and has dates, add them in italics
                if (itemDates) {
                    row += '<br><em style="font-style: italic; font-size: 0.9em; color: #666;">' +
                        itemDates +
                        '</em>';
                }

                row += '</td>' +
                    '<td>' + itemAmount + '</td>' +
                    '</tr>';

                $tbody.append(row);
            }
        });

        // Step 2: Add Subtotal row
        if (summary.subtotal) {
            var subtotalRow = '<tr class="subtotal-row">' +
                '<td><strong>Subtotal</strong></td>' +
                '<td><strong>' + formatCurrency(summary.subtotal[1]) + '</strong></td>' +
                '</tr>';
            $tbody.append(subtotalRow);
        }

        // Step 3: Add Tax row
        if (summary.taxes) {
            var taxRow = '<tr class="tax-row">' +
                '<td><strong>Tax</strong></td>' +
                '<td><strong>' + formatCurrency(summary.taxes[1]) + '</strong></td>' +
                '</tr>';
            $tbody.append(taxRow);
        }

        // Step 4: Add deposits AFTER subtotal and tax
        $.each(summary, function (key, value) {
            if (skipKeys.indexOf(key) === -1 && value && value[1] > 0) {
                var itemName = value[0] || key;

                // Only process deposits
                if (itemName.toLowerCase().includes('deposit')) {
                    var itemAmount = formatCurrency(value[1]);

                    var row = '<tr>' +
                        '<td>' + itemName + '</td>' +
                        '<td>' + itemAmount + '</td>' +
                        '</tr>';

                    $tbody.append(row);
                }
            }
        });

        // Step 5: Populate the grand total in tfoot (existing element)
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