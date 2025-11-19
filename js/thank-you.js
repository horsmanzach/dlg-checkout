jQuery(document).ready(function ($) {

    console.log('Thank You page loaded');

    // Function to format currency
    function formatCurrency(amount) {
        if (!amount || isNaN(amount)) {
            return '$0.00';
        }
        return '$' + parseFloat(amount).toFixed(2);
    }


    // NEW: Function to format timestamp - UPDATE THIS EXISTING FUNCTION
    function formatTermsTimestamp(timestamp) {
        if (!timestamp) {
            return '';
        }

        try {
            const date = new Date(timestamp);

            // Format to match: "October 14, 2025 at 3:45 PM EST"
            // Use WordPress site timezone from localized data
            const options = {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true,
                timeZone: thankYouData.timezone, // Use WP timezone setting
                timeZoneName: 'short'
            };

            const formatted = date.toLocaleString('en-US', options);

            return formatted;
        } catch (e) {
            console.error('Error formatting timestamp:', e);
            return timestamp;
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
        console.log('=== TAX RATE DEBUG ===');
        console.log('Tax rate from data:', data.tax_rate);
        console.log('Full data object:', data);

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

        // NEW: Populate CCD row if value exists
        if (data.ccd && data.ccd.trim() !== '') {
            $('#customer-ccd').text(data.ccd);
            $('#customer-ccd-row').show();
            console.log('CCD populated:', data.ccd);
        } else {
            $('#customer-ccd-row').hide();
            console.log('No CCD value to display');
        }

        // Populate Upfront Payment Summary - PASS data object
        if (data.upfront_summary) {
            populateUpfrontSummary(data.upfront_summary, data);
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

        // Populate Monthly Billing Summary - PASS data object
        if (data.monthly_summary) {
            populateMonthlySummary(data.monthly_summary, data);
        }

        // Populate monthly payment method
        if (data.monthly_payment_method) {
            var monthlyPaymentText = data.monthly_payment_method;

            // Format the payment method text nicely
            if (monthlyPaymentText === 'cc') {
                monthlyPaymentText = 'Credit Card';

                // If we have card last 4 to display
                if (data.monthly_card_last_4) {
                    monthlyPaymentText += ' ending in ' + data.monthly_card_last_4;
                }
            } else if (monthlyPaymentText === 'bank' || monthlyPaymentText === 'pad') {
                monthlyPaymentText = 'Bank Account - Pre-Authorized Debit (PAD)';
            } else if (monthlyPaymentText === 'payafter') {
                monthlyPaymentText = 'Pay After (Non Pre-Authorized)';
            }

            $('#monthly-payment-method').text(monthlyPaymentText);
        }

        console.log('Thank You page populated successfully');
    }

    // Function to populate upfront summary table (2 columns)
    // UPDATED: Now accepts data parameter for tax rate
    function populateUpfrontSummary(summary, data) {
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
                    '<td style="width: 50%; border: 1px solid #ddd; padding: 10px;">' + itemName;

                // If this is Installation Fee and has dates, add them in italics
                if (itemDates) {
                    row += '<br><em style="font-style: italic; font-size: 0.9em; color: #666;">' +
                        itemDates +
                        '</em>';
                }

                row += '</td>' +
                    '<td style="width: 50%; border: 1px solid #ddd; padding: 10px;">' + itemAmount + '</td>' +
                    '</tr>';

                $tbody.append(row);
            }
        });

        // Step 2: Add Subtotal row
        if (summary.subtotal) {
            var subtotalRow = '<tr class="subtotal-row">' +
                '<td style="width: 50%; border: 1px solid #ddd; padding: 10px;">Subtotal</td>' +
                '<td style="width: 50%; border: 1px solid #ddd; padding: 10px;">' + formatCurrency(summary.subtotal[1]) + '</td>' +
                '</tr>';
            $tbody.append(subtotalRow);
        }

        // Step 3: Add Tax row with dynamic rate
        if (summary.taxes) {
            var taxLabel = 'Tax';
            if (data.tax_rate && data.tax_rate > 0) {
                taxLabel = 'Tax (' + data.tax_rate + '%)';
            }
            var taxRow = '<tr class="tax-row">' +
                '<td style="width: 50%; border: 1px solid #ddd; padding: 10px;">' + taxLabel + '</td>' +
                '<td style="width: 50%; border: 1px solid #ddd; padding: 10px;">' + formatCurrency(summary.taxes[1]) + '</td>' +
                '</tr>';
            $tbody.append(taxRow);
        }

        // Step 4: Add deposits AFTER subtotal and tax
        $.each(summary, function (key, value) {
            if (value && value[1] > 0) {
                var itemName = value[0] || key;

                // Process items with 'deposit' in name OR if key is 'deposit'
                if (key === 'deposit' || itemName.toLowerCase().includes('deposit')) {
                    var itemAmount = formatCurrency(value[1]);

                    // Clean up Pay After deposit name
                    var displayName = itemName;
                    if (itemName.toLowerCase().includes('pay-after') || itemName.toLowerCase().includes('payafter')) {
                        displayName = 'Pay After Deposit';
                    }

                    var row = '<tr>' +
                        '<td style="width: 50%; border: 1px solid #ddd; padding: 10px;">' + displayName + '</td>' +
                        '<td style="width: 50%; border: 1px solid #ddd; padding: 10px;">' + itemAmount + '</td>' +
                        '</tr>';

                    $tbody.append(row);
                }
            }
        });

        // Step 5: Populate the grand total in tfoot (existing element)
        if (summary.grand_total) {
            $('#upfront-total').html('<strong>' + formatCurrency(summary.grand_total[1]) + '</strong>');
        }
    }

    // Function to populate monthly summary table (2 columns)
    // UPDATED: Now accepts data parameter for tax rate and handles promotional pricing
    function populateMonthlySummary(summary, data) {
        console.log('Populating monthly summary:', summary);

        var $tbody = $('#monthly-items');
        $tbody.empty(); // Clear any existing rows

        // Add line items (skip subtotal, taxes, grand_total)
        var skipKeys = ['subtotal', 'taxes', 'grand_total'];

        $.each(summary, function (key, value) {
            if (skipKeys.indexOf(key) === -1 && value && (value[1] > 0 || value[5])) {
                var itemName = value[0] || key;

                // NEW: Check if this item has promotional pricing data
                var originalPrice = value[2] || 0;  // [2] = original_price
                var promoPrice = value[3] || 0;     // [3] = promo_price
                var promoBlurb = value[4] || '';    // [4] = promo_blurb
                var modemDetails = value[5] || '';   // [5] = modem_details
                var finalPrice = value[1];          // [1] = final price (for display)

                // Build the product name cell with optional promo blurb
                var nameCell = itemName;
                if (promoBlurb) {
                    // Add promo blurb below product name (green, italic)
                    nameCell += '<br><span style="color: green; font-style: italic; font-size: 0.9em;">' +
                        promoBlurb + '</span>';
                }

                // ADD: Modem details display (grey, italic)
                if (modemDetails) {
                    nameCell += '<br><em style="font-style: italic; font-size: 0.9em; color: #666;">' +
                        modemDetails + '</em>';
                }

                // Build the price cell with optional strikethrough
                var priceCell = '';
                if (promoPrice > 0) {
                    // Show strikethrough original price and green promo price (without bold)
                    priceCell = '<span style="text-decoration: line-through; color: grey; font-size: 0.9em;">' +
                        formatCurrency(originalPrice) + '</span><br>' +
                        '<span style="color: green;">' +
                        formatCurrency(promoPrice) + '</span>';
                } else {
                    // Regular price display
                    priceCell = formatCurrency(finalPrice);
                }

                var row = '<tr>' +
                    '<td style="width: 50%; border: 1px solid #ddd; padding: 10px;">' + nameCell + '</td>' +
                    '<td style="width: 50%; border: 1px solid #ddd; padding: 10px;">' + priceCell + '</td>' +
                    '</tr>';

                $tbody.append(row);
            }
        });

        // Populate footer totals
        if (summary.subtotal) {
            $('#monthly-subtotal').text(formatCurrency(summary.subtotal[1]));
        }

        if (summary.taxes) {
            var taxLabel = 'Tax';
            if (data.tax_rate && data.tax_rate > 0) {
                taxLabel = 'Tax (' + data.tax_rate + '%)';
            }
            // Find and update the tax label in the monthly summary table
            $('#monthly-tax').closest('tr').find('td:first').text(taxLabel);
            $('#monthly-tax').text(formatCurrency(summary.taxes[1]));
        }

        if (summary.grand_total) {
            $('#monthly-total').html('<strong>' + formatCurrency(summary.grand_total[1]) + '</strong>');
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