<?php
/**
 * Customer Email Template Functions
 * Handles sending order confirmation emails with custom fields from revamped checkout
 * Does NOT use WooCommerce orders - uses custom Moneris payment data
 */

/**
 * Generate HTML email template with order confirmation details
 * 
 * @param array $order_data All order data from the payment processing
 * @return string Complete HTML email template
 */
function generate_order_confirmation_email_html($order_data) {
    
    // Extract data from order_data array
    $customer_name = isset($order_data['customer_name']) ? $order_data['customer_name'] : '';
    $customer_email = isset($order_data['customer_email']) ? $order_data['customer_email'] : '';
    $customer_phone = isset($order_data['customer_phone']) ? $order_data['customer_phone'] : '';
    $service_address = isset($order_data['service_address']) ? $order_data['service_address'] : '';
    $shipping_address = isset($order_data['shipping_address']) ? $order_data['shipping_address'] : '';
    $customer_ip = isset($order_data['customer_ip']) ? $order_data['customer_ip'] : '';
    $ccd = isset($order_data['ccd']) ? $order_data['ccd'] : '';
    
    // Order timestamps
    $order_timestamp = isset($order_data['order_timestamp']) ? $order_data['order_timestamp'] : date('Y-m-d H:i:s');
    $terms_timestamp = isset($order_data['terms_timestamp']) ? $order_data['terms_timestamp'] : '';
    
    // Payment information
    $auth_code = isset($order_data['authorization_code']) ? $order_data['authorization_code'] : '';
    $transaction_number = isset($order_data['transaction_number']) ? $order_data['transaction_number'] : '';
    $card_last_4 = isset($order_data['card_last_4']) ? $order_data['card_last_4'] : '****';
    
    // Upfront payment summary
    $upfront_items = isset($order_data['upfront_summary']['items']) ? $order_data['upfront_summary']['items'] : array();
    $upfront_subtotal = isset($order_data['upfront_summary']['subtotal']) ? $order_data['upfront_summary']['subtotal'] : '0.00';
    $upfront_tax = isset($order_data['upfront_summary']['tax']) ? $order_data['upfront_summary']['tax'] : '0.00';
    $upfront_total = isset($order_data['upfront_summary']['total']) ? $order_data['upfront_summary']['total'] : '0.00';
    
    // Monthly billing summary
    $monthly_items = isset($order_data['monthly_summary']['items']) ? $order_data['monthly_summary']['items'] : array();
    $monthly_subtotal = isset($order_data['monthly_summary']['subtotal']) ? $order_data['monthly_summary']['subtotal'] : '0.00';
    $monthly_tax = isset($order_data['monthly_summary']['tax']) ? $order_data['monthly_summary']['tax'] : '0.00';
    $monthly_total = isset($order_data['monthly_summary']['total']) ? $order_data['monthly_summary']['total'] : '0.00';
    $monthly_payment_method = isset($order_data['monthly_summary']['payment_method']) ? $order_data['monthly_summary']['payment_method'] : 'Not specified';
    
    // Start building the HTML email
    ob_start();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Diallog</title>
    <style>
        /* Email-safe CSS */
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f4f4f4;
            color: #333333;
        }
        .email-wrapper {
            width: 100%;
            background-color: #f4f4f4;
            padding: 20px 0;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .email-header {
            background-color: #139948;
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: normal;
        }
        .email-body {
            padding: 30px 20px;
        }
        .email-body p {
            line-height: 1.6;
            margin: 0 0 15px 0;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .info-table th {
            background-color: #139948;
            color: #ffffff;
            padding: 12px;
            text-align: left;
            font-size: 16px;
        }
        .info-table td {
            padding: 12px;
            border: 1px solid #dddddd;
        }
        .info-table td:first-child {
            font-weight: bold;
            width: 40%;
            background-color: #f9f9f9;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        .items-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #eeeeee;
        }
        .items-table td:first-child {
            text-align: left;
        }
        .items-table td:last-child {
            text-align: right;
            font-weight: bold;
        }
        .total-row {
            background-color: #f0f8ff;
            font-weight: bold;
            font-size: 18px;
        }
        .total-row td {
            padding: 12px;
            border-top: 2px solid #139948;
        }
        .email-footer {
            background-color: #333333;
            color: #ffffff;
            padding: 20px;
            text-align: center;
            font-size: 14px;
        }
        .email-footer p {
            margin: 5px 0;
        }
        .email-footer a {
            color: #66b3ff;
            text-decoration: none;
        }
        .logo {
            max-width: 200px;
            height: auto;
            margin-bottom: 10px;
            border-radius: 10px;
        }
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #139948;
            margin: 25px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 2px solid #139948;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            
            <!-- Email Header -->
            <div class="email-header">
                <!-- Company Logo - Update with your actual logo URL -->
                <img src="<?php echo esc_url(home_url('/wp-content/uploads/2022/02/Diallog-Logo-tall-110.jpg')); ?>" 
                     alt="Diallog Logo" class="logo">
                <h1>Thank You for Your Order!</h1>
            </div>
            
            <!-- Email Body -->
            <div class="email-body">
                <p>Dear <?php echo esc_html($customer_name); ?>,</p>
                
                <p>Thank you for submitting your order and trusting us with your Internet needs. Your order has been logged in our system, and your installation will be scheduled shortly. Our customer onboarding team will contact you as soon as the installation date is confirmed and provide the next steps.</p>
                
                <p>Below are your complete order details for your reference:</p>
                
                <!-- Order Information Section -->
                <div class="section-title">Order Information</div>
                <table class="info-table">
                    <tbody>
                        <tr>
                            <td>Order Date & Time:</td>
                            <td><?php echo esc_html(date('F j, Y g:i A', strtotime($order_timestamp))); ?></td>
                        </tr>
                        <?php if (!empty($terms_timestamp)): ?>
                        <tr>
                            <td>Terms & Conditions Confirmed at:</td>
                            <td><?php echo esc_html(date('F j, Y g:i A', strtotime($terms_timestamp))); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td>Customer IP Address:</td>
                            <td><?php echo esc_html($customer_ip); ?></td>
                        </tr>
                    </tbody>
                </table>
                
                <!-- Customer Information Section -->
                <div class="section-title">Customer Information</div>
                <table class="info-table">
                    <tbody>
                        <tr>
                            <td>Name:</td>
                            <td><?php echo esc_html($customer_name); ?></td>
                        </tr>
                        <tr>
                            <td>Email:</td>
                            <td><?php echo esc_html($customer_email); ?></td>
                        </tr>
                        <tr>
                            <td>Phone:</td>
                            <td><?php echo esc_html($customer_phone); ?></td>
                        </tr>
                        <tr>
                            <td>Service Address:</td>
                            <td><?php echo esc_html($service_address); ?></td>
                        </tr>
                        <tr>
                            <td>Shipping Address:</td>
                            <td><?php echo esc_html($shipping_address); ?></td>
                        </tr>
                        <?php if (!empty($ccd)): ?>
                        <tr>
                            <td>CCD:</td>
                            <td><?php echo esc_html($ccd); ?></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <!-- Upfront Payment Summary Section -->
                <div class="section-title">Upfront Payment Summary</div>
                <table class="items-table">
                    <tbody>
                        <?php if (!empty($upfront_items)): ?>
                            <?php foreach ($upfront_items as $item): ?>
                            <tr>
                                <td><?php echo esc_html($item['name']); ?></td>
                                <td>$<?php echo esc_html(number_format($item['total'], 2)); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td>Subtotal:</td>
                                <td>$<?php echo esc_html(number_format($upfront_subtotal, 2)); ?></td>
                            </tr>
                            <tr>
                                <td>Tax:</td>
                                <td>$<?php echo esc_html(number_format($upfront_tax, 2)); ?></td>
                            </tr>
                        <?php endif; ?>
                        <tr class="total-row">
                            <td>Total Paid Today:</td>
                            <td>$<?php echo esc_html(number_format($upfront_total, 2)); ?></td>
                        </tr>
                    </tbody>
                </table>
                
                <!-- Payment Details -->
                <table class="info-table" style="margin-top: 10px;">
                    <tbody>
                        <tr>
                            <td>Authorization Code:</td>
                            <td><?php echo esc_html($auth_code); ?></td>
                        </tr>
                        <tr>
                            <td>Transaction Number:</td>
                            <td><?php echo esc_html($transaction_number); ?></td>
                        </tr>
                        <tr>
                            <td>Payment Method:</td>
                            <td>Credit Card ending in <?php echo esc_html($card_last_4); ?></td>
                        </tr>
                    </tbody>
                </table>
                
                <!-- Monthly Billing Summary Section -->
                <div class="section-title">Monthly Billing Summary</div>
                <table class="items-table">
                    <tbody>
                        <?php if (!empty($monthly_items)): ?>
                            <?php foreach ($monthly_items as $item): ?>
                            <tr>
                                <td><?php echo esc_html($item['name']); ?></td>
                                <td>$<?php echo esc_html(number_format($item['total'], 2)); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td>Subtotal:</td>
                                <td>$<?php echo esc_html(number_format($monthly_subtotal, 2)); ?></td>
                            </tr>
                            <tr>
                                <td>Tax:</td>
                                <td>$<?php echo esc_html(number_format($monthly_tax, 2)); ?></td>
                            </tr>
                        <?php endif; ?>
                        <tr class="total-row">
                            <td>Total Monthly Recurring:</td>
                            <td>$<?php echo esc_html(number_format($monthly_total, 2)); ?></td>
                        </tr>
                    </tbody>
                </table>
                
                <!-- Monthly Payment Method -->
                <table class="info-table" style="margin-top: 10px;">
                    <tbody>
                        <tr>
                            <td>Monthly Payment Method:</td>
                            <td><?php echo esc_html($monthly_payment_method); ?></td>
                        </tr>
                    </tbody>
                </table>
                
                <p style="margin-top: 30px;">If you have any questions about your order, please don't hesitate to contact us.</p>
                
                <p>Thank you for choosing Diallog!</p>
            </div>
            
            <!-- Email Footer -->
            <div class="email-footer">
                <p><strong>Diallog Telecommunications Corp.</strong></p>
                <p>Phone: <a href="tel:+18884433876">1-888-443-3876</a></p>
                <p>Website: <a href="https://diallog.com">https://diallog.com</a></p>
            </div>
            
        </div>
    </div>
</body>
</html>
    <?php
    
    return ob_get_clean();
}

/**
 * Send order confirmation email to customer
 * Replaces the old send_email_confirmation_of_order() function
 * 
 * @param string $customer_email Email address to send to
 * @param array $order_data Complete order data from payment processing
 * @return bool True if email sent successfully, false otherwise
 */
function send_customer_order_confirmation_email($customer_email, $order_data) {
    
    // Validate email address
    if (empty($customer_email) || !is_email($customer_email)) {
        error_log('Invalid email address provided to send_customer_order_confirmation_email: ' . $customer_email);
        return false;
    }
    
    // Generate the HTML email content
    $email_html = generate_order_confirmation_email_html($order_data);
    
    // Set up email headers
    $headers = array();
    $headers[] = 'From: Diallog Orders <residential.orders@diallog.com>';
    
    // Only BCC company email in live/production mode, not during testing
    // Check if we're in test mode by looking at order data or using config
    $moneris_config = get_moneris_config();
    if (!$moneris_config['test_mode']) {
        $headers[] = 'BCC: residential.orders@diallog.com'; // BCC to company for record keeping (LIVE MODE ONLY)
    }
    
    $headers[] = 'Reply-To: residential.orders@diallog.com';
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = 'MIME-Version: 1.0';
    
    // Email subject
    $subject = 'Your Diallog Order Confirmation';
    
    // Log the email attempt
    error_log('Attempting to send order confirmation email to: ' . $customer_email);
    
    // Send the email using WordPress wp_mail function
    $sent = wp_mail($customer_email, $subject, $email_html, $headers);
    
    if ($sent) {
        error_log('Order confirmation email sent successfully to: ' . $customer_email);
    } else {
        error_log('Failed to send order confirmation email to: ' . $customer_email);
    }
    
    return $sent;
}

/**
 * Send test order confirmation email (for development/testing)
 * 
 * @param string $test_email Email address to send test to
 * @param array $order_data Optional order data, will use sample data if not provided
 * @return bool True if sent successfully
 */
function send_test_order_confirmation_email($test_email, $order_data = null) {
    
    // If no order data provided, use sample data
    if (empty($order_data)) {
        $order_data = array(
            'customer_name' => 'John Doe',
            'customer_email' => $test_email,
            'customer_phone' => '306-555-1234',
            'service_address' => '123 Main Street, Saskatoon, SK, S7K 1A1',
            'shipping_address' => '123 Main Street, Saskatoon, SK, S7K 1A1',
            'customer_ip' => '192.168.1.1',
            'ccd' => 'TEST123',
            'order_timestamp' => date('Y-m-d H:i:s'),
            'terms_timestamp' => date('Y-m-d H:i:s'),
            'authorization_code' => 'TEST12345',
            'transaction_number' => 'TXN' . time(),
            'card_last_4' => '1234',
            'upfront_summary' => array(
                'items' => array(
                    array('name' => 'Internet Plan - 100 Mbps', 'total' => 99.99),
                    array('name' => 'Modem Rental', 'total' => 10.00),
                    array('name' => 'Installation Fee', 'total' => 50.00)
                ),
                'subtotal' => 159.99,
                'tax' => 16.00,
                'total' => 175.99
            ),
            'monthly_summary' => array(
                'items' => array(
                    array('name' => 'Internet Service - 100 Mbps', 'total' => 79.99),
                    array('name' => 'Modem Rental', 'total' => 10.00)
                ),
                'subtotal' => 89.99,
                'tax' => 9.00,
                'total' => 98.99,
                'payment_method' => 'Credit Card ending in 1234'
            )
        );
    }
    
    return send_customer_order_confirmation_email($test_email, $order_data);
}

/**
 * AJAX handler for sending test emails during development
 */
add_action('wp_ajax_send_test_order_email', 'ajax_send_test_order_email');
add_action('wp_ajax_nopriv_send_test_order_email', 'ajax_send_test_order_email');

function ajax_send_test_order_email() {
    
    // Get test email from POST or use default
    $test_email = isset($_POST['test_email']) ? sanitize_email($_POST['test_email']) : 'your-test-email@example.com';
    
    // Get the thank you page data
    if (function_exists('dg_get_thank_you_page_data')) {
        $thank_you_data = dg_get_thank_you_page_data();
        
        // Transform it for email using the same function that the real checkout uses
        if (function_exists('transform_order_data_for_email')) {
            // Build the diallog order data structure that transform function expects
            $diallog_order_data = array(
                'upfront_summary' => isset($thank_you_data['upfront_summary']) ? $thank_you_data['upfront_summary'] : array(),
                'monthly_summary' => isset($thank_you_data['monthly_summary']) ? $thank_you_data['monthly_summary'] : array(),
                'order_timestamp' => isset($thank_you_data['order_timestamp_raw']) ? strtotime($thank_you_data['order_timestamp_raw']) : time(),
                'terms_acceptance_timestamp' => isset($thank_you_data['terms_timestamp']) ? $thank_you_data['terms_timestamp'] : ''
            );
            
            // Transform to email format
            $order_data = transform_order_data_for_email($diallog_order_data);
        } else {
            $order_data = null;
        }
    } else {
        $order_data = null; // Will use sample data
    }
    
    // Override the email with test email
    if (is_array($order_data)) {
        $order_data['customer_email'] = $test_email;
    }
    
    // Send the test email
    $sent = send_test_order_confirmation_email($test_email, $order_data);
    
    if ($sent) {
        wp_send_json_success(array(
            'message' => 'Test order confirmation email sent successfully to: ' . $test_email
        ));
    } else {
        wp_send_json_error(array(
            'message' => 'Failed to send test email. Check error logs for details.'
        ));
    }
}

?>