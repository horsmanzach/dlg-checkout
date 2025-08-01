<?php 

include_once("includes/crypt.php");
include_once("includes/hubspot.php");
include_once("includes/availability_check.php");
include_once("mpgClasses.php");
include_once("includes/ProcessPayment.php");

/* add_action( 'wp_enqueue_scripts', 'add_step8_script' );
function add_step8_script() {
	wp_enqueue_script( 'step8-script', get_stylesheet_directory_uri() . '/js/step8-script.js', array( 'jquery' ), '1.0', true );
}
*/ 

function verify_card_ex($payment_info) {

	$mpg_response = VerifyCard( $payment_info );
	error_log( "Got verify response back " . $mpg_response->getComplete() );

	if( $mpg_response == false ||
		strcmp( $mpg_response->getComplete(), "true") ||
		$mpg_response->getResponseCode() == false ||
		$mpg_response->getResponseCode() == null ||
		$mpg_response->getResponseCode() >= 50 ) {

		$msg = "Invalid credit card number. Please double check the card number entered " . $mpg_response->getMessage();
		$response['status'] = "failed";
		$response['msg'] = $msg;
		$response['code'] = $mpg_response->getResponseCode();
		$response['ref'] = $mpg_response->getReferenceNum();

		$data_response = json_encode( $response );
		error_log( $data_response );
		die( $data_response );
	}

	error_log( "Got verify cvd result code " . $mpg_response->getCvdResultCode() );
	if( $mpg_response->getCvdResultCode() != "1M" ) {
		$response['status'] = "failed";

		$msg = "Invalid expiry date or CVV. Please double check the information entered " . $mpg_response->getMessage();

		$response['msg'] = $msg;
		$response['code'] = $mpg_response->getResponseCode();
		$response['ref'] = $mpg_response->getReferenceNum();

		$data_response = json_encode( $response );
		error_log( $data_response );
		die( $data_response );
	}

	error_log( "Got verify avs result code " . $mpg_response->getAvsResultCode() );
	if( $mpg_response->getAvsResultCode() == "N" ) {
		$response['status'] = "failed";

		$msg = "Invalid postal code. Please provide the billing postal code from your recent credit card statement (may be different from the service address postal code).";

		$response['msg'] = $msg;
		$response['code'] = $mpg_response->getResponseCode();
		$response['ref'] = $mpg_response->getReferenceNum();

		$data_response = json_encode( $response );
		error_log( $data_response );
		die( $data_response );
	}

	error_log( "Got response back " . $mpg_response->getComplete() );
}


// ========== Zach's AJAX handlers for monthly billing section on checkout====

// Add this AJAX handler to your functions.php

add_action('wp_ajax_remove_monthly_billing_deposits', 'ajax_remove_monthly_billing_deposits');
add_action('wp_ajax_nopriv_remove_monthly_billing_deposits', 'ajax_remove_monthly_billing_deposits');

function ajax_remove_monthly_billing_deposits() {
    check_ajax_referer('checkout_nonce', 'nonce');
    
    $keep_option = isset($_POST['keep_option']) ? sanitize_text_field($_POST['keep_option']) : '';
    
    // If we're not keeping the payafter option, remove all deposit products
    if ($keep_option !== 'payafter') {
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $product_id = $cart_item['product_id'];
            
            // Remove the specific Pay After deposit product
            if ($product_id == 265827) {
                WC()->cart->remove_cart_item($cart_item_key);
                continue;
            }
            
            // Also remove any product in the "deposit" category
            $product_cats = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'slugs'));
            if (in_array('deposit', $product_cats)) {
                WC()->cart->remove_cart_item($cart_item_key);
            }
        }
    }
    
    // Recalculate cart totals
    WC()->cart->calculate_totals();
    
    wp_send_json_success(array(
        'message' => 'Other monthly billing options cleared',
        'kept_option' => $keep_option
    ));
}

// -----

// Credit card validation using your existing Moneris integration
add_action('wp_ajax_validate_credit_card', 'ajax_validate_credit_card');
add_action('wp_ajax_nopriv_validate_credit_card', 'ajax_validate_credit_card');

function ajax_validate_credit_card() {
    check_ajax_referer('checkout_nonce', 'nonce');
    
    $card_data = $_POST['card_data'];
    
    // Convert MM/YY to YYMM format for Moneris
    $expiry = $card_data['expiry']; // Should be in MM/YY format
    if (strpos($expiry, '/') !== false) {
        list($month, $year) = explode('/', $expiry);
        $formatted_expiry = $year . $month; // YYMM format
    } else {
        $formatted_expiry = $expiry;
    }
    
    // Use your existing Moneris validation logic
    $payment_info = array(
        'custid' => '',
        'orderid' => 'validate-' . time(),
        'amount' => '0.01',
        'cardno' => $card_data['card_number'],
        'expdate' => $formatted_expiry, // Now in YYMM format
        'cvd' => $card_data['cvv']
    );
    
    try {
        // Call your existing verify_card_ex function
        verify_card_ex($payment_info);
        wp_send_json_success(array('message' => 'Card is valid'));
    } catch (Exception $e) {
        wp_send_json_error(array('message' => 'Invalid card details: ' . $e->getMessage()));
    }
}

// Add Pay After deposit AJAX handler
add_action('wp_ajax_add_payafter_deposit', 'ajax_add_payafter_deposit');
add_action('wp_ajax_nopriv_add_payafter_deposit', 'ajax_add_payafter_deposit');

function ajax_add_payafter_deposit() {
    check_ajax_referer('checkout_nonce', 'nonce');
    
    // Remove any existing pay-after deposits first
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $product_id = $cart_item['product_id'];
        $product_cats = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'slugs'));
        
        if (in_array('deposit', $product_cats)) {
            WC()->cart->remove_cart_item($cart_item_key);
        }
    }
    
    // Add the Pay After deposit product to cart
    $payafter_product_id = 265827; // Your Pay After product ID
    $added = WC()->cart->add_to_cart($payafter_product_id, 1);
    
    if ($added) {
        // Trigger cart update to refresh totals
        WC()->cart->calculate_totals();
        
        wp_send_json_success(array(
            'message' => 'Pay After deposit added successfully',
            'redirect' => false
        ));
    } else {
        wp_send_json_error(array(
            'message' => 'Failed to add Pay After deposit'
        ));
    }
}

// Enqueue Monthly Billing scripts and styles
function enqueue_monthly_billing_assets() {
    // Only load on checkout page
    if (!is_checkout()) return;
    
    // Enqueue JavaScript
    wp_enqueue_script(
        'monthly-billing-js',
        get_stylesheet_directory_uri() . '/js/monthly-billing.js',
        array('jquery'),
        '1.0.0',
        true
    );
    
    // Localize script with AJAX data
    wp_localize_script('monthly-billing-js', 'monthlyBilling', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('checkout_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_monthly_billing_assets');



// Add Filter to Exclude Pay Later Product From Being Added to Monthly Summary

add_filter('woocommerce_cart_item_visible', 'exclude_payafter_from_monthly_summary', 10, 3);

function exclude_payafter_from_monthly_summary($visible, $cart_item, $cart_item_key) {
    // Check if we're in the context of monthly fee calculation
    if (did_action('monthly_fee_summary_calculation')) {
        $product_id = $cart_item['product_id'];
        
        // Exclude the Pay After deposit product from monthly calculations
        if ($product_id == 265827) {
            return false;
        }
        
        // Also exclude any product in the "deposit" category from monthly summary
        $product_cats = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'slugs'));
        if (in_array('deposit', $product_cats)) {
            return false;
        }
    }
    
    return $visible;
}

/*=====Handle Deposit payments better======*/

// IMPROVED FIX: Add this to your functions.php
function get_upfront_fee_summary_with_deposits() {
    $summary = array(
        'ModemPurchaseOption'=>true,
        'internet-plan'=>array('',0.0),
        'modems'=>array('Modem Security Deposit',0.0),
        'fixed-fee'=>array('Installation Fee',0.0),
        'deposit'=>array('Pay-after Deposit',0.0),
        'phone-plan'=>array('Phone Plan',0.0),
        'tv-plan'=>array('TV Plan',0.0),
        'subtotal'=>array('Subtotal',0.0),
        'taxes'=>array('Taxes',0.0),
        'grand_total'=>array('UPFRONT TOTAL',0.0)
    );

    $tax_rate = function_exists('GetTaxRate') ? GetTaxRate() : 13;
    $show_included_taxes = wc_tax_enabled() && WC()->cart->display_prices_including_tax();
    $total_deposits = 0;
    
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
        
        if (!$_product || !$_product->exists() || $cart_item['quantity'] <= 0) {
            continue;
        }
        
        $product_id = $_product->get_id();
        $product_price = $_product->get_price();
        
        // Get category safely
        $product_cat_ids = $_product->get_category_ids();
        $product_category = 'uncategorized';
        
        if (!empty($product_cat_ids) && isset($product_cat_ids[0])) {
            $product_cat = get_term($product_cat_ids[0], 'product_cat');
            if ($product_cat && !is_wp_error($product_cat) && isset($product_cat->slug)) {
                $product_category = $product_cat->slug;
            }
        }
        
        error_log("Processing: " . $_product->get_name() . " | Category: " . $product_category . " | Price: $" . $product_price);
        
        // Handle the main product categories
        if (array_key_exists($product_category, $summary)) {
            $summary[$product_category][0] = $_product->get_title();
            $summary[$product_category][1] = round(floatval($product_price), 2);
            
            // Add tax for taxable items (not deposits)
            if ($product_category !== 'deposit') {
                $summary['taxes'][1] += round(floatval(($summary[$product_category][1] * $tax_rate) / 100), 2);
            }
        }
        
        // CHECK FOR ACF DEPOSIT FIELDS on this product
        $deposit_title = get_field('deposit-title', $product_id);
        $deposit_amount = get_field('deposit-fee', $product_id);
        
        // Alternative ACF field names (in case they're different)
        if (empty($deposit_title)) {
            $deposit_title = get_field('deposit_title', $product_id);
        }
        if (empty($deposit_amount)) {
            $deposit_amount = get_field('deposit_amount', $product_id);
        }
        
        if (!empty($deposit_title) && is_numeric($deposit_amount) && $deposit_amount > 0) {
            error_log("Found ACF deposit: " . $deposit_title . " = $" . $deposit_amount);
            $total_deposits += floatval($deposit_amount);
        }
    }
    
    // Add total deposits to the deposit category
    if ($total_deposits > 0) {
        $summary['deposit'][0] = 'Deposits';
        $summary['deposit'][1] = $total_deposits;
        error_log("Total deposits: $" . $total_deposits);
    }
    
    // Calculate totals
    $summary['subtotal'][1] = $summary['internet-plan'][1] + $summary['modems'][1] + $summary['fixed-fee'][1] + $summary['phone-plan'][1] + $summary['tv-plan'][1];
    
    if (wc_tax_enabled() && !$show_included_taxes) {
        $summary['taxes'][0] = esc_html(WC()->countries->tax_or_vat());
        $summary['grand_total'][1] = $summary['subtotal'][1] + $summary['taxes'][1] + $summary['deposit'][1];
    } else {
        $summary['taxes'][0] = "Tax";
        $summary['grand_total'][1] = $summary['subtotal'][1] + $summary['deposit'][1];
    }
    
    error_log("=== FINAL SUMMARY ===");
    error_log("Subtotal: $" . $summary['subtotal'][1]);
    error_log("Tax: $" . $summary['taxes'][1]);
    error_log("Deposits: $" . $summary['deposit'][1]);
    error_log("Grand Total: $" . $summary['grand_total'][1]);
    
    return $summary;
}

/*=======================NEW MONERIS PAYMENT GATEWAY INTEGRATION VIA SHORTCODE=======================*/



/**
 * Moneris Payment Gateway Functions for functions.php
 */

// Moneris Test Mode Control - Change this to switch between test and production
function is_moneris_test_mode() {
    // Change this to true for testing, false for production
    return true; // Set to true for test mode, false for live transactions
}

// Moneris Account Configuration
function get_moneris_config() {
    if (is_moneris_test_mode()) {
        // TEST ENVIRONMENT CREDENTIALS
        return array(
            'store_id' => 'store5',     // Replace with your test store ID tomorrow
            'api_token' => 'yesguy',   // Replace with your test API token tomorrow
            'test_mode' => true
        );
    } else {
        // PRODUCTION ENVIRONMENT CREDENTIALS (from your existing processpayment.php)
        return array(
            'store_id' => 'store5',                 // Your current production store ID
            'api_token' => 'yesguy',               // Your current production API token
            'test_mode' => false
        );
    }
}

// Enqueue payment gateway scripts
function enqueue_moneris_payment_assets() {
    if (is_checkout() || has_shortcode(get_post()->post_content, 'moneris_payment_form')) {
        wp_enqueue_script(
            'moneris-payment-js',
            get_stylesheet_directory_uri() . '/js/moneris-payment.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        wp_localize_script('moneris-payment-js', 'monerisPayment', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('moneris_payment_nonce')
        ));
    }
}
add_action('wp_enqueue_scripts', 'enqueue_moneris_payment_assets');

/**
 * Moneris Payment Form Shortcode
 */
function moneris_payment_form_shortcode($atts) {
    $atts = shortcode_atts(array(
        'button_text' => 'Process Payment',
        'show_amount' => 'true',
        'redirect_url' => '',
        'success_message' => 'Payment processed successfully!'
    ), $atts);
    
    // Get cart total for amount display
    $total_amount = 0;
    if (function_exists('get_upfront_fee_summary')) {
        $summary = get_upfront_fee_summary();
        $total_amount = $summary['grand_total'][1];
        $total_display = wc_price($total_amount);
    } else if (function_exists('WC') && WC()->cart) {
        $total_amount = WC()->cart->get_total('edit');
        $total_display = wc_price($total_amount);
    }
    
    ob_start();
    ?>
    <div class="moneris-payment-container">
        <?php if ($atts['show_amount'] === 'true' && $total_amount > 0): ?>
            <div class="moneris-payment-amount">
                <h3>Payment Amount: <?php echo $total_display; ?></h3>
            </div>
        <?php endif; ?>
        
        <form id="moneris-payment-form" class="moneris-payment-form">
            <div class="moneris-message-container"></div>
            
            <div class="moneris-form-row">
                <label for="moneris_cardholder_name">Cardholder Name <span style="color:red;">*</span></label>
                <input type="text" id="moneris_cardholder_name" name="cardholder_name" 
                       placeholder="John Doe" required maxlength="50">
            </div>
            
            <div class="moneris-form-row">
                <label for="moneris_card_number">Card Number <span style="color:red;">*</span></label>
                <input type="text" id="moneris_card_number" name="card_number" 
                       placeholder="1234 5678 9012 3456" required maxlength="19">
            </div>
            
            <div class="moneris-form-row half">
                <label for="moneris_expiry_date">Expiry Date <span style="color:red;">*</span></label>
                <input type="text" id="moneris_expiry_date" name="expiry_date" 
                       placeholder="MM/YY" required maxlength="5">
            </div>
            
            <div class="moneris-form-row half">
                <label for="moneris_cvv">CVV <span style="color:red;">*</span></label>
                <input type="text" id="moneris_cvv" name="cvv" 
                       placeholder="123" required maxlength="4">
            </div>
            
            <div class="moneris-form-row">
                <label for="moneris_postal_code">Postal Code <span style="color:red;">*</span></label>
                <input type="text" id="moneris_postal_code" name="postal_code" 
                       placeholder="A1A 1A1" required maxlength="7">
            </div>
            
            <div class="moneris-loading">
                <p>Processing payment, please wait...</p>
            </div>
            
            <button type="submit" class="moneris-submit-btn"><?php echo esc_html($atts['button_text']); ?></button>
            
            <input type="hidden" name="action" value="process_moneris_payment">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('moneris_payment_nonce'); ?>">
            <input type="hidden" name="redirect_url" value="<?php echo esc_url($atts['redirect_url']); ?>">
            <input type="hidden" name="success_message" value="<?php echo esc_attr($atts['success_message']); ?>">
        </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('moneris_payment_form', 'moneris_payment_form_shortcode');

/**
 * AJAX handler for processing Moneris payments
 */
add_action('wp_ajax_process_moneris_payment', 'ajax_process_moneris_payment');
add_action('wp_ajax_nopriv_process_moneris_payment', 'ajax_process_moneris_payment');

function ajax_process_moneris_payment() {
    // Verify nonce
    check_ajax_referer('moneris_payment_nonce', 'nonce');
    
    // Get and sanitize form data
    $cardholder_name = sanitize_text_field($_POST['cardholder_name']);
    $card_number = sanitize_text_field($_POST['card_number']);
    $expiry_date = sanitize_text_field($_POST['expiry_date']);
    $cvv = sanitize_text_field($_POST['cvv']);
    $postal_code = sanitize_text_field($_POST['postal_code']);
    $success_message = sanitize_text_field($_POST['success_message']);
    $redirect_url = esc_url_raw($_POST['redirect_url']);
    
    // Validate required fields
    if (empty($cardholder_name) || empty($card_number) || empty($expiry_date) || empty($cvv) || empty($postal_code)) {
        wp_send_json_error(array(
            'message' => 'Please fill in all required fields.'
        ));
        return;
    }
    
    // Format card number (remove spaces)
    $clean_card_number = preg_replace('/\s+/', '', $card_number);
    
    // Format expiry date (convert MM/YY to YYMM for Moneris)
    if (strpos($expiry_date, '/') !== false) {
        list($month, $year) = explode('/', $expiry_date);
        $formatted_expiry = $year . $month;
    } else {
        wp_send_json_error(array(
            'message' => 'Invalid expiry date format. Please use MM/YY.'
        ));
        return;
    }
    
    // Format postal code (remove spaces and convert to uppercase)
    $clean_postal_code = strtoupper(preg_replace('/\s+/', '', $postal_code));
    
    // Get payment amount from upfront fee summary
    $amount = 0;
    
    // Debug: Check what we're getting
    error_log('=== MONERIS PAYMENT DEBUG ===');
    error_log('Cart exists: ' . (function_exists('WC') && WC()->cart ? 'YES' : 'NO'));
    error_log('Cart empty: ' . (WC()->cart && WC()->cart->is_empty() ? 'YES' : 'NO'));
    error_log('Cart item count: ' . (WC()->cart ? WC()->cart->get_cart_contents_count() : '0'));
    
    // Try the improved version that includes ACF deposits
    if (function_exists('get_upfront_fee_summary_with_deposits')) {
        $summary = get_upfront_fee_summary_with_deposits();
        error_log('Using IMPROVED upfront summary with deposits');
        if (isset($summary['grand_total'][1])) {
            $amount = $summary['grand_total'][1];
            error_log('Amount from IMPROVED summary: ' . $amount);
        }
    } elseif (function_exists('get_upfront_fee_summary_fixed')) {
        $summary = get_upfront_fee_summary_fixed();
        error_log('Using FIXED upfront summary');
        if (isset($summary['grand_total'][1])) {
            $amount = $summary['grand_total'][1];
            error_log('Amount from FIXED summary: ' . $amount);
        }
    } elseif (function_exists('get_upfront_fee_summary')) {
        $summary = get_upfront_fee_summary();
        error_log('Using original upfront summary');
        if (isset($summary['grand_total'][1])) {
            $amount = $summary['grand_total'][1];
            error_log('Amount from original summary: ' . $amount);
        }
    } else {
        error_log('No upfront summary function available');
        if (function_exists('WC') && WC()->cart) {
            $amount = WC()->cart->get_total('edit');
            error_log('Amount from WC cart: ' . $amount);
        }
    }
    
    error_log('Final amount: ' . $amount);
    error_log('=== END DEBUG ===');
    
    if ($amount <= 0) {
        wp_send_json_error(array(
            'message' => 'Invalid payment amount: ' . $amount . '. Please ensure you have items in your cart with proper categories assigned.'
        ));
        return;
    }
    
    // Get Moneris configuration (test or production)
    $moneris_config = get_moneris_config();
    
    // Generate unique order ID
    $order_id = ($moneris_config['test_mode'] ? 'test-' : 'web-') . time() . '-' . wp_rand(1000, 9999);
    
    // Get customer ID from session or generate one
    $customer_id = '';
    if (WC()->session) {
        $customer_id = WC()->session->get('customer_id');
    }
    if (empty($customer_id)) {
        $customer_id = 'guest-' . wp_rand(10000, 99999);
        if (WC()->session) {
            WC()->session->set('customer_id', $customer_id);
        }
    }
    
    // Prepare payment data with dynamic credentials
    $payment_data = array(
        'type' => 'purchase',
        'custid' => $customer_id,
        'orderid' => $order_id,
        'amount' => $amount,
        'cardno' => $clean_card_number,
        'expdate' => $formatted_expiry,
        'cvd' => $cvv,
        'postal_code' => $clean_postal_code,
        'store_id' => $moneris_config['store_id'],
        'api_token' => $moneris_config['api_token'],
        'test_mode' => $moneris_config['test_mode']
    );
    
    try {
        // Skip card verification in test mode due to routing issues
        if (!$moneris_config['test_mode']) {
            // Only verify card in production mode
            $verify_data = $payment_data;
            $verify_data['amount'] = '0.01'; // Verification amount
            $verify_data['orderid'] = 'verify-' . time();
            
            if (function_exists('VerifyCard')) {
                $verify_response = VerifyCard($verify_data);
                
                if (!$verify_response || $verify_response->getResponseCode() >= 50) {
                    $error_msg = $verify_response ? $verify_response->getMessage() : 'Unknown error';
                    wp_send_json_error(array(
                        'message' => 'Card verification failed: ' . $error_msg
                    ));
                    return;
                }
            }
        } else {
            error_log('Test mode: Skipping card verification due to routing issues');
        }
        
        // Process the actual payment
        if (function_exists('ProcessPayment')) {
            $payment_response = ProcessPayment($payment_data);
            
            if ($payment_response && $payment_response->getResponseCode() < 50 && 
                strcasecmp($payment_response->getComplete(), 'true') == 0) {
                
                // Payment successful - Store payment details in session
                if (WC()->session) {
                    WC()->session->set('payment_status', 'completed');
                    WC()->session->set('payment_transaction_id', $payment_response->getTxnNumber());
                    WC()->session->set('payment_receipt_id', $payment_response->getReceiptId());
                    WC()->session->set('payment_amount', $payment_response->getTransAmount());
                    WC()->session->set('payment_date', $payment_response->getTransDate());
                    WC()->session->set('order_complete_timestamp', time());
                    WC()->session->set('cardholder_name', $cardholder_name);
                    WC()->session->set('payment_test_mode', $moneris_config['test_mode']);
                }
                
                // Prepare order data for Diallog database
                $order_data = prepare_diallog_order_data($payment_response, $cardholder_name, $moneris_config);
                
                // Send order to Diallog database (skip in test mode to avoid test data in production DB)
                $diallog_response = 'skipped';
                if (!$moneris_config['test_mode']) {
                    $diallog_response = send_order_to_diallog($order_data);
                } else {
                    error_log('Test mode: Skipping Diallog database submission');
                }
                
                // Trigger WooCommerce email notifications
                trigger_woocommerce_emails($order_data);
                
                // Clear cart after successful payment and database submission
                if (function_exists('WC') && WC()->cart) {
                    WC()->cart->empty_cart();
                }
                
                $success_msg = $success_message;
                if ($moneris_config['test_mode']) {
                    $success_msg .= ' (Test Transaction)';
                }
                
                wp_send_json_success(array(
                    'message' => $success_msg,
                    'transaction_id' => $payment_response->getTxnNumber(),
                    'receipt_id' => $payment_response->getReceiptId(),
                    'redirect_url' => $redirect_url,
                    'diallog_status' => $diallog_response,
                    'test_mode' => $moneris_config['test_mode']
                ));
                
            } else {
                $error_msg = 'Payment failed: ' . ($payment_response ? $payment_response->getMessage() : 'Unknown error');
                if ($moneris_config['test_mode']) {
                    $error_msg .= ' (Test Mode)';
                }
                wp_send_json_error(array(
                    'message' => $error_msg
                ));
            }
        } else {
            wp_send_json_error(array(
                'message' => 'Payment processing function not available.'
            ));
        }
        
    } catch (Exception $e) {
        error_log('Moneris payment error: ' . $e->getMessage());
        wp_send_json_error(array(
            'message' => 'Payment processing error. Please try again.'
        ));
    }
    
    wp_die();
}

/**
 * Prepare order data for Diallog database
 */
function prepare_diallog_order_data($payment_response, $cardholder_name, $moneris_config) {
    // Get cart items and customer data
    $cart_items = array();
    $monthly_summary = array();
    $upfront_summary = array();
    
    if (function_exists('WC') && WC()->cart && !WC()->cart->is_empty()) {
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $cart_items[] = array(
                'product_id' => $cart_item['product_id'],
                'product_name' => $product->get_name(),
                'quantity' => $cart_item['quantity'],
                'price' => $product->get_price(),
                'variation_data' => isset($cart_item['variation']) ? $cart_item['variation'] : array()
            );
        }
    }
    
    // Get fee summaries
    if (function_exists('get_upfront_fee_summary')) {
        $upfront_summary = get_upfront_fee_summary();
    }
    
    if (function_exists('get_monthly_fee_summary')) {
        $monthly_summary = get_monthly_fee_summary();
    }
    
    // Get customer information from session/cart
    $customer_data = array(
        'cardholder_name' => $cardholder_name,
        'billing_email' => WC()->session->get('customer')['email'] ?? '',
        'billing_phone' => WC()->session->get('customer')['phone'] ?? '',
    );
    
    // Build complete order data structure
    $order_data = array(
        'payment_info' => array(
            'transaction_id' => $payment_response->getTxnNumber(),
            'receipt_id' => $payment_response->getReceiptId(),
            'amount' => $payment_response->getTransAmount(),
            'date' => $payment_response->getTransDate(),
            'time' => $payment_response->getTransTime(),
            'card_type' => $payment_response->getCardType(),
            'auth_code' => $payment_response->getAuthCode(),
            'test_mode' => $moneris_config['test_mode']
        ),
        'customer_data' => $customer_data,
        'cart_items' => $cart_items,
        'upfront_summary' => $upfront_summary,
        'monthly_summary' => $monthly_summary,
        'order_timestamp' => time(),
        'order_source' => 'website_checkout'
    );
    
    return $order_data;
}

/**
 * Send order data to Diallog database
 * Based on the pattern from your existing SendInfoToSignupServer function
 */
function send_order_to_diallog($order_data) {
    $payload_data = array(
        'status' => 0,
        'magic' => 'Sl2soDSpLAsHqetS', // Using same magic key from your existing code
        'api' => '1.00',
        'method' => 'complete_order',
        'state' => 100, // Order complete state
        'order_data' => base64_encode(json_encode($order_data)),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
    );
    
    $payload = json_encode($payload_data);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://207.167.88.7/signup.php"); // Using URL from your existing code
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload)
    ));
    
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    error_log("Order sent to Diallog database. Response: " . $response);
    
    return $response;
}

/**
 * Trigger WooCommerce email notifications using existing WooCommerce email system
 */
function trigger_woocommerce_emails($order_data) {
    // Store order data in session for WooCommerce email templates to access
    if (WC()->session) {
        WC()->session->set('moneris_order_data', $order_data);
    }
    
    // Trigger WooCommerce email hooks that you can customize in your email templates
    do_action('moneris_payment_completed', $order_data);
    
    // You can now customize your existing WooCommerce email templates to use:
    // $order_data = WC()->session->get('moneris_order_data');
    // to access all the payment and order information
}

/**
 * Helper function to validate credit card number using Luhn algorithm
 */
function validate_credit_card_number($number) {
    $number = preg_replace('/\D/', '', $number);
    $length = strlen($number);
    
    if ($length < 13 || $length > 19) {
        return false;
    }
    
    $sum = 0;
    $alternate = false;
    
    for ($i = $length - 1; $i >= 0; $i--) {
        $digit = intval($number[$i]);
        
        if ($alternate) {
            $digit *= 2;
            if ($digit > 9) {
                $digit = ($digit % 10) + 1;
            }
        }
        
        $sum += $digit;
        $alternate = !$alternate;
    }
    
    return ($sum % 10 == 0);
}

/**
 * Helper function to get card type from number
 */
function get_card_type($number) {
    $number = preg_replace('/\D/', '', $number);
    
    if (preg_match('/^4/', $number)) {
        return 'Visa';
    } elseif (preg_match('/^5[1-5]/', $number)) {
        return 'MasterCard';
    } elseif (preg_match('/^3[47]/', $number)) {
        return 'American Express';
    } elseif (preg_match('/^6(?:011|5)/', $number)) {
        return 'Discover';
    }
    
    return 'Unknown';
}

// Ensure the mpgClasses.php file is loaded for Moneris functionality
if (!class_exists('mpgTransaction')) {
    include_once(get_stylesheet_directory() . '/mpgClasses.php');
}

// Make sure ProcessPayment.php functions are available
if (!function_exists('ProcessPayment')) {
    include_once(get_stylesheet_directory() . '/includes/ProcessPayment.php');
}


/*=================ZACH NEW EDITS AS OF MARCH 31=================*/

/*==========Register monthly_fee shortcode========*/


function display_monthly_fee_shortcode($atts) {
    // Extract shortcode attributes, requiring product_id
    $atts = shortcode_atts(array(
        'product_id' => '', // No default - must be specified
    ), $atts);
    
    // Check if product_id is provided
    if (empty($atts['product_id'])) {
        return 'Product ID required'; // Or just return empty: return '';
    }
    
    // Get the 'monthly_fee' field value from the specified product
    $monthly_fee = get_field('monthly_fee', $atts['product_id']);
    
    // Alternative way to get the field if the above doesn't work
    if ($monthly_fee === null && function_exists('get_field')) {
        $monthly_fee = get_field('monthly_fee', 'product_' . $atts['product_id']);
    }
    
    // Check if the value is numeric (including 0) or a numeric string
    if (is_numeric($monthly_fee) || is_string($monthly_fee)) {
        // Convert to string to ensure consistent output
        return esc_html((string)$monthly_fee);
    } else {
        return ''; // Return empty if no monthly fee set or not numeric
    }
}
add_shortcode('display_monthly_fee', 'display_monthly_fee_shortcode');

/*======Register deposit-title shortcode========*/

function display_deposit_title_shortcode($atts) {
   // Extract shortcode attributes, requiring product_id
   $atts = shortcode_atts(array(
       'product_id' => '', // No default - must be specified
   ), $atts);
   
   // Check if product_id is provided
   if (empty($atts['product_id'])) {
       return 'Product ID required';
   }
   
   // Get the 'deposit-title' field value from the specified product
   $deposit_title = get_field('deposit-title', $atts['product_id']);
   
   // Alternative way to get the field if the above doesn't work
   if ($deposit_title === null && function_exists('get_field')) {
       $deposit_title = get_field('deposit-title', 'product_' . $atts['product_id']);
   }
   
   // Return the title or empty string
   return esc_html($deposit_title ?: '');
}
add_shortcode('display_deposit_title', 'display_deposit_title_shortcode');

/*=======Register deposit-fee shortcode=======*/

function display_deposit_fee_shortcode($atts) {
    // Extract shortcode attributes, requiring product_id
    $atts = shortcode_atts(array(
        'product_id' => '', // No default - must be specified
    ), $atts);
    
    // Check if product_id is provided
    if (empty($atts['product_id'])) {
        return 'Product ID required';
    }
    
    // Get the 'deposit-fee' field value from the specified product
    $deposit_fee = get_field('deposit-fee', $atts['product_id']);
    
    // Alternative way to get the field if the above doesn't work
    if ($deposit_fee === null && function_exists('get_field')) {
        $deposit_fee = get_field('deposit-fee', 'product_' . $atts['product_id']);
    }
    
    // Check if the value is numeric (including 0) or a numeric string
    if (is_numeric($deposit_fee) || is_string($deposit_fee)) {
        // Convert to string to ensure consistent output
        return esc_html((string)$deposit_fee);
    } else {
        return ''; // Return empty if no deposit fee set or not numeric
    }
}
add_shortcode('display_deposit_fee', 'display_deposit_fee_shortcode');



/*==========Register installation-date sale shortcode========*/

function display_installation_sale_shortcode($atts) {
    // Extract shortcode attributes, requiring product_id
    $atts = shortcode_atts(array(
        'product_id' => get_the_ID(), // Default to current post ID if not specified
    ), $atts);
    
    // Check if product_id is provided or default is available
    if (empty($atts['product_id'])) {
        return ''; // Return empty if no product ID available
    }
    
    // Get the 'install_discount_percentage' field value from the specified product
    $installation_sale_value = get_field('install_discount_percentage', $atts['product_id']);
    
    // Alternative way to get the field if the above doesn't work
    if ($installation_sale_value === null && function_exists('get_field')) {
        $installation_sale_value = get_field('install_discount_percentage', 'product_' . $atts['product_id']);
    }
    
    // Check if the value exists and is numeric
    if (is_numeric($installation_sale_value) && $installation_sale_value > 0) {
        // Convert to integer to remove any decimal places and add the % sign
        $sale_percentage = intval($installation_sale_value);
        return 'Now ' . $sale_percentage . '% off!';
    } else {
        return ''; // Return empty if no sale value set or not numeric
    }
}
add_shortcode('display_installation_sale', 'display_installation_sale_shortcode');


/*==========Installation variation price display shortcode========*/

function display_installation_variation_price_shortcode($atts) {
    // Extract shortcode attributes
    $atts = shortcode_atts(array(
        'variation_id' => 265450, // Default to the installation variation ID
    ), $atts);
    
    // Get the variation product
    $variation = wc_get_product($atts['variation_id']);
    
    // Check if variation exists and is valid
    if (!$variation || !$variation->exists()) {
        return ''; // Return empty if variation not found
    }
    
    // Get regular and sale prices
    $regular_price = $variation->get_regular_price();
    $sale_price = $variation->get_sale_price();
    
    // Check if both prices exist and it's actually on sale
    if ($regular_price && $sale_price && $sale_price < $regular_price) {
        // Format with span classes for styling
        return '<span class="installation-regular-price"><s>' . wc_price($regular_price) . '</s></span> <span class="installation-sale-price">' . wc_price($sale_price) . '</span>';
    } elseif ($regular_price) {
        // If no sale price, just show regular price
        return '<span class="installation-regular-price">' . wc_price($regular_price) . '</span>';
    }
    
    return ''; // Return empty if no prices found
}
add_shortcode('display_installation_variation_price', 'display_installation_variation_price_shortcode');

/*==========ADDRESS LOOKUP SHORTCODES======================*/

/*==========Fixed Address Display Shortcode that always shows========*/

function address_display_shortcode($atts) {
    // Extract shortcode attributes
    $atts = shortcode_atts(array(
        'show_search_button' => 'true',
        'container_class' => 'internet-packages-location-button-group',
        'redirect_to_plans' => 'true',
    ), $atts);
    
    // Get the API response from user meta
    $apiResponse = dg_get_user_meta("_api_response");
    
    // Build the address display HTML
    $output = '<div class="' . esc_attr($atts['container_class']) . '"';
    
    // Add data attribute to indicate redirect behavior
    if ($atts['redirect_to_plans'] === 'true') {
        $output .= ' data-redirect-to-plans="true"';
    }
    
    $output .= '>';
    
    // Address display section
    $output .= '<div class="internet-packages-check-availability-title">';
    $output .= '<i class="fa fa-map-marker internet-packages-location-icon" aria-hidden="true"></i>';
    
    // Check if we have address data, if not show default message
    if ($apiResponse && !empty($apiResponse["address"])) {
        $output .= '<small class="internet-packages-location">' . esc_html($apiResponse["address"]) . '</small>';
    } else {
        $output .= '<small class="internet-packages-location">No address selected</small>';
    }
    
    $output .= '</div>';
    
    // Search different address button (conditional)
    if ($atts['show_search_button'] === 'true') {
        $output .= '<a class="btn_mute plan_check_other_availability_btn" href="#" ';
        $output .= 'data-target="#plan-building-wizard-modal" data-toggle="modal" ';
        $output .= 'rel="noopener noreferrer">Search Different Address</a>';
    }
    
    $output .= '</div>';
    
    return $output;
}

// Register the shortcode (make sure this replaces any existing registration)
add_shortcode('address_display', 'address_display_shortcode');

function enqueue_address_lookup_script() {
    if (!is_admin()) {
        wp_enqueue_script(
            'address-lookup-script', 
            get_stylesheet_directory_uri() . '/js/address-lookup.js', 
            array('jquery'), 
            '1.1.0', // Increment version to force reload
            true
        );
        
        // Always enqueue the ajax_object for this script
        wp_localize_script('address-lookup-script', 'ajax_object', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('address_lookup_nonce')
        ));
    }
}
add_action('wp_enqueue_scripts', 'enqueue_address_lookup_script');


/*======Add Modem Details to 'I have my own Modem' product=====*/

// AJAX handler to save modem details to cart
function save_modem_details_to_cart() {
    check_ajax_referer('modem_selection_nonce', 'nonce');
    
    $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
    $modem_details = isset($_POST['modem_details']) ? sanitize_text_field($_POST['modem_details']) : '';
    
    if ($product_id !== 265769 || strlen($modem_details) < 5 || strlen($modem_details) > 100) {
        wp_send_json_error(array('message' => 'Invalid modem details'));
        return;
    }
    
    // Remove any existing modems from cart (same logic as regular modem selection)
    $modem_category_id = 62; // Your modem category ID
    
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $cart_product_id = $cart_item['product_id'];
        $product_cats = wp_get_post_terms($cart_product_id, 'product_cat', array('fields' => 'ids'));
        $is_modem = in_array($modem_category_id, $product_cats);
        
        if ($is_modem) {
            WC()->cart->remove_cart_item($cart_item_key);
        }
    }
    
    // Add to cart with custom data
    $cart_item_data = array(
        'modem_details' => $modem_details
    );
    
    $added = WC()->cart->add_to_cart($product_id, 1, 0, array(), $cart_item_data);
    
    if ($added) {
        wp_send_json_success(array('message' => 'Own modem added to cart'));
    } else {
        wp_send_json_error(array('message' => 'Failed to add to cart'));
    }
    
    wp_die();
}
add_action('wp_ajax_save_modem_details', 'save_modem_details_to_cart');
add_action('wp_ajax_nopriv_save_modem_details', 'save_modem_details_to_cart');

// Display modem details in cart
function display_modem_details_in_cart($item_data, $cart_item) {
    if (isset($cart_item['modem_details'])) {
        $item_data[] = array(
            'key'     => 'Modem Make & Model',
            'value'   => $cart_item['modem_details'],
            'display' => '',
        );
    }
    return $item_data;
}
add_filter('woocommerce_get_item_data', 'display_modem_details_in_cart', 10, 2);

// Save modem details to order
function save_modem_details_to_order($item, $cart_item_key, $values, $order) {
    if (isset($values['modem_details'])) {
        $item->add_meta_data('Modem Make & Model', $values['modem_details']);
    }
}
add_action('woocommerce_checkout_create_order_line_item', 'save_modem_details_to_order', 10, 4);



/*Create Clickable Rows Instead of Add to Cart Buttons*/

/**
 * Clickable Modem Rows - Add to Cart Functionality
 * Add this to your theme's functions.php or a custom plugin
 */

// Enqueue the JavaScript
function modem_selection_scripts() {
    // Load on product pages and custom checkout pages
    if (is_product() || is_page(array('checkout', 'internet-plans'))) { // Add your custom page slugs here
        wp_enqueue_script('card-selection', get_stylesheet_directory_uri() . '/js/card-selection.js', array('jquery'), '1.0', true);
        
        // Pass AJAX URL and nonce to JavaScript
        wp_localize_script('card-selection', 'modem_selection_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('modem_selection_nonce'),
        ));
    }
}
add_action('wp_enqueue_scripts', 'modem_selection_scripts');

function enqueue_product_selection_scripts() {
    // Load on all product pages
    if (is_product()) {
        wp_enqueue_script('product-selection-nav', get_stylesheet_directory_uri() . '/js/product-selection-navigation.js', array('jquery'), '1.0', true);
        
        // Pass any PHP variables the script needs
        wp_localize_script('product-selection-nav', 'product_selection_vars', array(
            'checkout_url' => home_url('/checkout'), // Or your specific checkout URL
            'total_screens' => 4,
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('product_selection_nonce')
        ));
    }
}
add_action('wp_enqueue_scripts', 'enqueue_product_selection_scripts');

// Function to get cart items - useful for checking currently selected modem
function get_cart_items_ajax() {
    check_ajax_referer('modem_selection_nonce', 'nonce');
    
    $items = array();
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $items[] = $cart_item['product_id'];
    }
    
    wp_send_json_success(array(
        'items' => $items
    ));
    
    wp_die();
}
add_action('wp_ajax_get_cart_items', 'get_cart_items_ajax');
add_action('wp_ajax_nopriv_get_cart_items', 'get_cart_items_ajax');



/*========Add Deposit Fees As Payable Items=======*/

add_action('woocommerce_cart_calculate_fees', 'add_deposit_fees_to_cart');

function add_deposit_fees_to_cart() {
    if (is_admin() && !defined('DOING_AJAX')) return;
    
    foreach (WC()->cart->get_cart() as $cart_item) {
        $product_id = $cart_item['data']->get_id();
        
        // Get deposit information
        $deposit_title = get_field('deposit-title', $product_id) ?: get_field('deposit-title', 'product_' . $product_id);
        $deposit_fee = get_field('deposit-fee', $product_id) ?: get_field('deposit-fee', 'product_' . $product_id);
        
        // Add as fee if deposit exists
        if (!empty($deposit_title) && is_numeric($deposit_fee) && $deposit_fee > 0) {
            // Use unique ID to avoid duplicates
            WC()->cart->add_fee($deposit_title, $deposit_fee, false);
        }
    }
}


/* ==============Creation of UPFRONT FEE TABLE =====================*/

function upfront_fee_summary_shortcode() {
   // Get cart items
   $cart = WC()->cart;
   
   if ($cart->is_empty()) {
       return '<p>No products selected.</p>';
   }
   
   $output = '<table class="fee-summary-table upfront-fee-table">';
   $output .= '<thead><tr><th>Product</th><th>Category</th><th>Price</th></tr></thead>';
   $output .= '<tbody>';
   
   $subtotal = 0;
   $installation_found = false;
   $installation_dates = array();
   $installation_price = 0;
   $deposits = array(); // Store deposit information
   
   // First pass: Check for installation and store its details
   foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
       $product = $cart_item['data'];
       $product_id = $product->get_id();
       $parent_id = $product->get_parent_id();
       
       // Check if this is an installation product
       $installation_category_id = 63; // Installation category ID
       $installation_product_id = 265084; // Parent product ID
       $product_cats = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
       $is_installation = in_array($installation_category_id, $product_cats) || $parent_id == $installation_product_id;
       
       if ($is_installation) {
           $installation_found = true;
           $installation_price = $product->get_price();
           
           // Check for variation attributes
           if (isset($cart_item['variation']) && is_array($cart_item['variation'])) {
               $installation_dates = array(
                   'preferred-date' => isset($cart_item['variation']['attribute_preferred-date']) ? 
                       $cart_item['variation']['attribute_preferred-date'] : '',
                   'secondary-date' => isset($cart_item['variation']['attribute_secondary-date']) ? 
                       $cart_item['variation']['attribute_secondary-date'] : ''
               );
           }
           break; // Exit loop once installation is found
       }
   }
   
   // Add installation section FIRST if found
   if ($installation_found && !empty($installation_dates)) {
       $output .= '<tr class="installation-row-header">';
       $output .= '<td colspan="3"><strong>Installation</strong></td>';
       $output .= '</tr>';
       
       // Combine dates into one row
       $combined_dates = '';
       if (!empty($installation_dates['preferred-date']) && !empty($installation_dates['secondary-date'])) {
           $combined_dates = esc_html($installation_dates['preferred-date'] . ', ' . $installation_dates['secondary-date']);
       } elseif (!empty($installation_dates['preferred-date'])) {
           $combined_dates = esc_html($installation_dates['preferred-date']);
       } elseif (!empty($installation_dates['secondary-date'])) {
           $combined_dates = esc_html($installation_dates['secondary-date']);
       }
       
       // Get the specific installation variation (265450) for pricing
       $installation_variation = wc_get_product(265450);
       $price_display = wc_price($installation_price);
       
       if ($installation_variation && $installation_variation->exists()) {
           $regular_price = $installation_variation->get_regular_price();
           $sale_price = $installation_variation->get_sale_price();
           
           // Always show strikethrough for installation if both prices exist
           if ($regular_price && $sale_price && $sale_price < $regular_price) {
               $price_display = '<span class="installation-regular-price"><s>' . wc_price($regular_price) . '</s></span> <span class="installation-sale-price">' . wc_price($sale_price) . '</span>';
           }
       }
       
       $output .= '<tr class="installation-details">';
       $output .= '<td>Dates</td>';
       $output .= '<td>' . $combined_dates . '</td>';
       $output .= '<td>' . $price_display . '</td>';
       $output .= '</tr>';
       
       $subtotal += $installation_price; // Add to subtotal
   }
   
   // Check if we have any extras (non-installation products with price > 0)
   $has_extras = false;
   foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
       $product = $cart_item['data'];
       $product_id = $product->get_id();
       $parent_id = $product->get_parent_id();
       $product_price = $product->get_price();
       
       // Skip installation products
       $installation_category_id = 63;
       $installation_product_id = 265084;
       $product_cats = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
       $is_installation = in_array($installation_category_id, $product_cats) || $parent_id == $installation_product_id;
       
       if (!$is_installation && $product_price > 0) {
           $has_extras = true;
           break;
       }
   }
   
   // Add Extras subheader if we have extras
   if ($has_extras) {
       $output .= '<tr class="extras-row-header">';
       $output .= '<td colspan="3"><strong>Extras</strong></td>';
       $output .= '</tr>';
   }
   
   // Second pass: Loop through cart items for non-installation products
   foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
       $product = $cart_item['data'];
       $product_id = $product->get_id();
       $parent_id = $product->get_parent_id();
       $product_name = $product->get_name();
       $product_price = $product->get_price();
       
       // Get product category
       $category_name = '';
       $terms = get_the_terms($product_id, 'product_cat');
       if (!empty($terms) && !is_wp_error($terms)) {
           $category_name = $terms[0]->name;
       }
       
       // Skip installation products (already handled above)
       $installation_category_id = 63;
       $installation_product_id = 265084;
       $product_cats = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
       $is_installation = in_array($installation_category_id, $product_cats) || $parent_id == $installation_product_id;
       
       if ($is_installation) {
           continue; // Skip - already displayed above
       }
       
       // Get deposit information
       $deposit_title = '';
       $deposit_fee = 0;
       
       if (function_exists('get_field')) {
           $deposit_title = get_field('deposit-title', $product_id);
           $deposit_fee = get_field('deposit-fee', $product_id);
           
           // If direct approach fails, try with product_ prefix
           if (empty($deposit_title)) {
               $deposit_title = get_field('deposit-title', 'product_' . $product_id);
           }
           
           if (empty($deposit_fee) && $deposit_fee !== '0') {
               $deposit_fee = get_field('deposit-fee', 'product_' . $product_id);
           }
           
           // Convert deposit fee to numeric value
           $deposit_fee = is_numeric($deposit_fee) ? floatval($deposit_fee) : 0;
       }
       
       // Store deposit information if it exists
       if (!empty($deposit_title) && $deposit_fee > 0) {
           $deposits[] = array(
               'title' => $deposit_title,
               'fee' => $deposit_fee
           );
       }
       
       // Add non-installation product to table ONLY if price is not zero
       if ($product_price > 0) {
           $output .= '<tr>';
           $output .= '<td>' . esc_html($product_name) . '</td>';
           $output .= '<td>' . esc_html($category_name) . '</td>';
           $output .= '<td>' . wc_price($product_price) . '</td>';
           $output .= '</tr>';
       }
       
       // Always add to subtotal regardless of display
       $subtotal += $product_price;
   }
   
   // Calculate tax on subtotal (excluding deposits)
   $tax_total = 0;
   if (wc_tax_enabled()) {
       $tax_rates = WC_Tax::get_rates();
       if (!empty($tax_rates)) {
           $taxes = WC_Tax::calc_tax($subtotal, $tax_rates);
           $tax_total = array_sum($taxes);
       }
   }
   
   // Add subtotal and tax rows
   $output .= '<tr class="subtotal-row"><td colspan="2">Subtotal</td><td>' . wc_price($subtotal) . '</td></tr>';
   $output .= '<tr class="tax-row"><td colspan="2">Tax</td><td>' . wc_price($tax_total) . '</td></tr>';
   
   // Add deposit rows if any exist
   $deposit_total = 0;
   if (!empty($deposits)) {
       foreach ($deposits as $deposit) {
           $output .= '<tr class="deposit-row">';
           $output .= '<td colspan="2">' . esc_html($deposit['title']) . '</td>';
           $output .= '<td>' . wc_price($deposit['fee']) . '</td>';
           $output .= '</tr>';
           $deposit_total += $deposit['fee'];
       }
   }
   
   // Add final total row (subtotal + tax + deposits)
   $grand_total = $subtotal + $tax_total + $deposit_total;
   $output .= '<tr class="total-row"><td colspan="2">Total Upfront</td><td>' . wc_price($grand_total) . '</td></tr>';
   
   $output .= '</tbody></table>';
   
   return $output;
}
add_shortcode('upfront_fee_summary', 'upfront_fee_summary_shortcode');


/*-------*/



// Monthly Fee Summary Table Shortcode with Internet Plan

// Update your monthly fee summary function to trigger the action
function monthly_fee_summary_shortcode() {
    global $post;
    
    // Trigger action to indicate we're calculating monthly fees
    do_action('monthly_fee_summary_calculation');
    
    // Get cart items
    $cart = WC()->cart;
    
    $output = '<table class="fee-summary-table monthly-fee-table">';
    $output .= '<thead><tr><th>Product</th><th>Category</th><th>Monthly Fee</th></tr></thead>';
    $output .= '<tbody>';
    
    $subtotal = 0;
    $internet_plan_in_cart = false;
    $current_product_id = 0;
    
    // Installation category ID to exclude from monthly fee table
    $installation_category_id = 63;
    $installation_product_id = 265084;
    $deposit_category_id = get_term_by('slug', 'deposit', 'product_cat');
    $deposit_category_id = $deposit_category_id ? $deposit_category_id->term_id : null;
    
    // Check if we're on a product page
    if (is_product() && $post) {
        $current_product_id = $post->ID;
        
        // Skip if this is the installation product
        if ($current_product_id == $installation_product_id) {
            // Do nothing - skip this product
        } else {
            $current_product = wc_get_product($current_product_id);
            
            if ($current_product && $current_product->get_type() === 'simple') {
                // Check if this is an internet plan product
                $product_cats = wp_get_post_terms($current_product_id, 'product_cat', array('fields' => 'ids'));
                $is_internet_plan = in_array(19, $product_cats); // Replace with your actual category ID
                
                if ($is_internet_plan) {
                    // Get current plan monthly fee
                    $monthly_fee = 0;
                    if (function_exists('get_field')) {
                        $monthly_fee = get_field('monthly_fee', $current_product_id);
                        if (empty($monthly_fee) && $monthly_fee !== '0') {
                            $monthly_fee = get_field('monthly_fee', 'product_' . $current_product_id);
                        }
                        $monthly_fee = is_numeric($monthly_fee) ? floatval($monthly_fee) : 0;
                    }
                    
                    // Get product category name
                    $category_name = '';
                    $terms = get_the_terms($current_product_id, 'product_cat');
                    if (!empty($terms) && !is_wp_error($terms)) {
                        $category_name = $terms[0]->name;
                    }
                    
                    // Add current plan to table
                    $output .= '<tr class="internet-plan-row">';
                    $output .= '<td>' . esc_html($current_product->get_name()) . '</td>';
                    $output .= '<td>' . esc_html($category_name) . '</td>';
                    $output .= '<td>' . wc_price($monthly_fee) . '</td>';
                    $output .= '</tr>';
                    
                    $subtotal += $monthly_fee;
                }
            }
        }
    }
    
    // Check if cart is empty
    if (!$cart->is_empty()) {
        // Loop through cart items
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $product_id = $product->get_id();
            $parent_id = $product->get_parent_id();
            
            // Skip if this is an internet plan and we're on its product page
            if ($product_id == $current_product_id) {
                $internet_plan_in_cart = true;
                continue;
            }
            
            // Get product categories
            $product_cats = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
            
            // Skip installation products
            if (in_array($installation_category_id, $product_cats) || $product_id == $installation_product_id || $parent_id == $installation_product_id) {
                continue;
            }
            
            // SKIP DEPOSIT PRODUCTS from monthly summary
            if ($deposit_category_id && in_array($deposit_category_id, $product_cats)) {
                continue;
            }
            
            // Skip the specific Pay After deposit product
            if ($product_id == 265827) {
                continue;
            }
            
            // Check if this is an internet plan but not the current one
            $is_internet_plan = in_array(19, $product_cats); // Replace with actual category ID
            
            // Remove other internet plans from cart
            if ($is_internet_plan && $current_product_id > 0) {
                WC()->cart->remove_cart_item($cart_item_key);
                continue;
            }
            
            $product_name = $product->get_name();
            
            // Get product category name
            $category_name = '';
            $terms = get_the_terms($product_id, 'product_cat');
            if (!empty($terms) && !is_wp_error($terms)) {
                $category_name = $terms[0]->name;
            }
            
            // Get ACF monthly fee field
            $monthly_fee = 0;
            if (function_exists('get_field')) {
                $monthly_fee = get_field('monthly_fee', $product_id);
                
                if (empty($monthly_fee) && $monthly_fee !== '0') {
                    $monthly_fee = get_field('monthly_fee', 'product_' . $product_id);
                }
                
                $monthly_fee = is_numeric($monthly_fee) ? floatval($monthly_fee) : 0;
            }
            
            $output .= '<tr>';
            $output .= '<td>' . esc_html($product_name) . '</td>';
            $output .= '<td>' . esc_html($category_name) . '</td>';
            $output .= '<td>' . wc_price($monthly_fee) . '</td>';
            $output .= '</tr>';
            
            $subtotal += $monthly_fee;
        }
    }
    
    // Add current internet plan to cart if it's not there already
    if ($current_product_id > 0 && !$internet_plan_in_cart && $current_product_id != $installation_product_id) {
        $product_cats = wp_get_post_terms($current_product_id, 'product_cat', array('fields' => 'ids'));
        $is_internet_plan = in_array(19, $product_cats);
        
        if ($is_internet_plan) {
            WC()->cart->add_to_cart($current_product_id, 1);
        }
    }
    
    // Calculate tax
    $tax_total = 0;
    if (wc_tax_enabled()) {
        $tax_rates = WC_Tax::get_rates();
        if (!empty($tax_rates)) {
            $taxes = WC_Tax::calc_tax($subtotal, $tax_rates);
            $tax_total = array_sum($taxes);
        }
    }
    
    // Add subtotal, tax and total rows
    $output .= '<tr class="subtotal-row"><td colspan="2">Subtotal</td><td>' . wc_price($subtotal) . '</td></tr>';
    $output .= '<tr class="tax-row"><td colspan="2">Tax</td><td>' . wc_price($tax_total) . '</td></tr>';
    $output .= '<tr class="total-row"><td colspan="2">Total Monthly</td><td>' . wc_price($subtotal + $tax_total) . '</td></tr>';
    
    $output .= '</tbody></table>';
    
    return $output;
}
add_shortcode('monthly_fee_summary', 'monthly_fee_summary_shortcode');


/*-----*/

function modem_add_to_cart_ajax() {
    // Check nonce for security
    check_ajax_referer('modem_selection_nonce', 'nonce');
    
    // Get the product ID and type
    $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
    $is_internet_plan = isset($_POST['is_internet_plan']) && $_POST['is_internet_plan'] === 'true';
    $product_type = isset($_POST['product_type']) ? sanitize_text_field($_POST['product_type']) : '';
    
    if ($product_id > 0) {
        // If this is an internet plan, handle specifically
        if ($is_internet_plan) {
            // Remove any existing internet plans from cart
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $cart_product_id = $cart_item['product_id'];
                
                // Check if this is an internet plan
                $product_cats = wp_get_post_terms($cart_product_id, 'product_cat', array('fields' => 'ids'));
                $is_cart_item_internet_plan = in_array(19, $product_cats); // Replace with your category ID
                
                if ($is_cart_item_internet_plan) {
                    WC()->cart->remove_cart_item($cart_item_key);
                }
            }
            
            // Add the new internet plan
            $added = WC()->cart->add_to_cart($product_id, 1);
        }
        // If this is a phone plan
        elseif ($product_type === 'phone') {
            // Remove any existing phone plans from cart
            $phone_category_id = 22; // Replace with your phone category ID
            
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $cart_product_id = $cart_item['product_id'];
                
                // Check if this is a phone plan
                $product_cats = wp_get_post_terms($cart_product_id, 'product_cat', array('fields' => 'ids'));
                $is_phone_plan = in_array($phone_category_id, $product_cats);
                
                if ($is_phone_plan) {
                    WC()->cart->remove_cart_item($cart_item_key);
                }
            }
            
            // Add the new phone plan to cart
            $added = WC()->cart->add_to_cart($product_id, 1);
        }
        // If this is a TV plan
        elseif ($product_type === 'tv') {
            // Remove any existing TV plans from cart
            $tv_category_id = 59; // Your TV category ID
            
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $cart_product_id = $cart_item['product_id'];
                
                // Check if this is a TV plan
                $product_cats = wp_get_post_terms($cart_product_id, 'product_cat', array('fields' => 'ids'));
                $is_tv_plan = in_array($tv_category_id, $product_cats);
                
                if ($is_tv_plan) {
                    WC()->cart->remove_cart_item($cart_item_key);
                }
            }
            
            // Add the new TV plan to cart
            $added = WC()->cart->add_to_cart($product_id, 1);
        }
        // If this is a modem or other accessory
        elseif ($product_type === 'modem') {
            // Remove any existing modems from cart
            $modem_category_id = 62; // Replace with your modem category ID
            
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $cart_product_id = $cart_item['product_id'];
                
                // Check if this is a modem
                $product_cats = wp_get_post_terms($cart_product_id, 'product_cat', array('fields' => 'ids'));
                $is_modem = in_array($modem_category_id, $product_cats);
                
                if ($is_modem) {
                    WC()->cart->remove_cart_item($cart_item_key);
                }
            }
            
            // Add the new modem to cart
            $added = WC()->cart->add_to_cart($product_id, 1);
        }
        // For any other product type
        else {
            // Simply add to cart
            $added = WC()->cart->add_to_cart($product_id, 1);
        }
        
        wp_send_json_success(array(
            'message' => 'Product added to cart',
            'product_id' => $product_id
        ));
    } else {
        wp_send_json_error(array(
            'message' => 'Invalid product ID'
        ));
    }
    
    wp_die();
}

add_action('wp_ajax_modem_add_to_cart', 'modem_add_to_cart_ajax');
add_action('wp_ajax_nopriv_modem_add_to_cart', 'modem_add_to_cart_ajax');

// Helper function to remove all cart items except specified ones
function remove_all_except_items($cart_keys_to_keep) {
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        if (!in_array($cart_item_key, $cart_keys_to_keep)) {
            WC()->cart->remove_cart_item($cart_item_key);
        }
    }
}



/**
 * Upfront Fee Total Shortcode
 * Displays only the total upfront fee amount
 */
function upfront_fee_total_shortcode($atts) {
    // Get cart items
    $cart = WC()->cart;
    
    if ($cart->is_empty()) {
        return '$0.00';
    }
    
    $subtotal = 0;
    $deposit_total = 0;
    
    // Loop through cart items
    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        $product_id = $product->get_id();
        $product_price = $product->get_price();
        $subtotal += $product_price;
        
        // Get deposit fee
        $deposit_fee = 0;
        if (function_exists('get_field')) {
            $deposit_fee = get_field('deposit-fee', $product_id);
            
            // If direct approach fails, try with product_ prefix
            if (empty($deposit_fee) && $deposit_fee !== '0') {
                $deposit_fee = get_field('deposit-fee', 'product_' . $product_id);
            }
            
            // Convert to numeric value
            $deposit_fee = is_numeric($deposit_fee) ? floatval($deposit_fee) : 0;
            $deposit_total += $deposit_fee;
        }
    }
    
    // Calculate tax (excluding deposits)
    $tax_total = 0;
    if (wc_tax_enabled()) {
        $tax_rates = WC_Tax::get_rates();
        if (!empty($tax_rates)) {
            $taxes = WC_Tax::calc_tax($subtotal, $tax_rates);
            $tax_total = array_sum($taxes);
        }
    }
    
    // Calculate total (subtotal + tax + deposits)
    $total = $subtotal + $tax_total + $deposit_total;
    
    // Format with wc_price for consistency
    return wc_price($total);
}
add_shortcode('upfront_fee_total', 'upfront_fee_total_shortcode');

function update_fee_summary_tables() {
    check_ajax_referer('modem_selection_nonce', 'nonce');
    
    // Get current product ID from AJAX request
    $current_product_id = isset($_POST['current_product_id']) ? absint($_POST['current_product_id']) : 0;
    
    // Store current product ID in session for use in shortcodes
    WC()->session->set('current_product_id', $current_product_id);
    
    $upfront_table = upfront_fee_summary_shortcode();
    $monthly_table = monthly_fee_summary_shortcode();
    
    wp_send_json_success(array(
        'upfront_table' => $upfront_table,
        'monthly_table' => $monthly_table
    ));
    
    wp_die();
}
add_action('wp_ajax_update_fee_tables', 'update_fee_summary_tables');
add_action('wp_ajax_nopriv_update_fee_tables', 'update_fee_summary_tables');

/**
 * AJAX handler to get upfront fee total
 * Used for dynamic updates
 */
function get_upfront_fee_total_ajax() {
    check_ajax_referer('modem_selection_nonce', 'nonce');
    
    $total = upfront_fee_total_shortcode(array());
    
    wp_send_json_success(array(
        'total' => $total
    ));
    
    wp_die();
}
add_action('wp_ajax_get_upfront_fee_total', 'get_upfront_fee_total_ajax');
add_action('wp_ajax_nopriv_get_upfront_fee_total', 'get_upfront_fee_total_ajax');


// AJAX handler to remove product from cart
function modem_remove_from_cart_ajax() {
    // Check nonce for security
    check_ajax_referer('modem_selection_nonce', 'nonce');
    
    // Get the product ID
    $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
    
    if ($product_id > 0) {
        // Find the cart item key for this product
        $cart_item_key = '';
        
        foreach (WC()->cart->get_cart() as $key => $cart_item) {
            if ($cart_item['product_id'] == $product_id) {
                $cart_item_key = $key;
                break;
            }
        }
        
        // If item found, remove it
        if (!empty($cart_item_key)) {
            WC()->cart->remove_cart_item($cart_item_key);
            wp_send_json_success(array(
                'message' => 'Product removed from cart',
                'product_id' => $product_id
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'Product not found in cart'
            ));
        }
    } else {
        wp_send_json_error(array(
            'message' => 'Invalid product ID'
        ));
    }
    
    wp_die();
}
add_action('wp_ajax_modem_remove_from_cart', 'modem_remove_from_cart_ajax');
add_action('wp_ajax_nopriv_modem_remove_from_cart', 'modem_remove_from_cart_ajax');

/**
 * Installation Date Selection Functions
 * Modified to use separate Preferred Date and Secondary Date attributes
 */

// Add installation date selection to cart
function add_installation_to_cart_ajax() {
    check_ajax_referer('modem_selection_nonce', 'nonce');
    
    // Dump all POST data to help debug
    error_log('Installation AJAX POST data: ' . print_r($_POST, true));
    
    $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
    $preferred_date = isset($_POST['preferred-date']) ? sanitize_text_field($_POST['preferred-date']) : '';
    $secondary_date = isset($_POST['secondary-date']) ? sanitize_text_field($_POST['secondary-date']) : '';
    
    if ($product_id <= 0 || empty($preferred_date) || empty($secondary_date)) {
        $error_msg = 'Invalid data: product_id=' . $product_id . ', preferred-date=' . $preferred_date . ', secondary-date=' . $secondary_date;
        error_log($error_msg);
        wp_send_json_error(array('message' => $error_msg));
        wp_die();
    }
    
    // First, remove any existing installation product from cart
    remove_installation_from_cart($product_id);
    
    // Find the variation ID based on the attributes
    $product = wc_get_product($product_id);
    $variation_id = 0;
    
    if (!$product) {
        $error_msg = 'Product not found: ' . $product_id;
        error_log($error_msg);
        wp_send_json_error(array('message' => $error_msg));
        wp_die();
    }
    
    if (!$product->is_type('variable')) {
        $error_msg = 'Product is not variable: ' . $product_id;
        error_log($error_msg);
        wp_send_json_error(array('message' => $error_msg));
        wp_die();
    }
    
    error_log('Available variations: ' . print_r($product->get_available_variations(), true));
    
    foreach ($product->get_available_variations() as $variation) {
        $variation_obj = wc_get_product($variation['variation_id']);
        $attributes = $variation_obj->get_attributes();
        error_log('Checking variation ' . $variation['variation_id'] . ' attributes: ' . print_r($attributes, true));
        
        if (isset($attributes['preferred-date']) && $attributes['preferred-date'] === $preferred_date &&
            isset($attributes['secondary-date']) && $attributes['secondary-date'] === $secondary_date) {
            $variation_id = $variation['variation_id'];
            break;
        }
    }
    
    // If no exact match was found, try to create a new variation
    if ($variation_id === 0) {
        $data_store = WC_Data_Store::load('product-variable');
        $variation_id = $data_store->find_matching_product_variation(
            $product,
            array(
                'attribute_preferred-date' => $preferred_date,
                'attribute_secondary-date' => $secondary_date
            )
        );
        error_log('Tried to find variation using data store: ' . $variation_id);
    }
    
    if ($variation_id === 0) {
        $error_msg = 'Could not find matching variation for attributes: preferred-date=' . $preferred_date . ', secondary-date=' . $secondary_date;
        error_log($error_msg);
        wp_send_json_error(array('message' => $error_msg));
        wp_die();
    }
    
    // Add the chosen variation to cart with date information
    $cart_item_data = array(
        'installation_dates' => array(
            'preferred-date' => $preferred_date,
            'secondary-date' => $secondary_date
        )
    );
    
    try {
        $cart_item_key = WC()->cart->add_to_cart(
            $product_id,
            1,
            $variation_id,
            array(
                'attribute_preferred-date' => $preferred_date,
                'attribute_secondary-date' => $secondary_date
            ),
            $cart_item_data
        );
        
        if ($cart_item_key) {
            wp_send_json_success(array(
                'message' => 'Installation dates added to cart',
                'cart_item_key' => $cart_item_key
            ));
        } else {
            $error_msg = 'Failed to add to cart - WC()->cart->add_to_cart returned false';
            error_log($error_msg);
            wp_send_json_error(array('message' => $error_msg));
        }
    } catch (Exception $e) {
        $error_msg = 'Exception adding to cart: ' . $e->getMessage();
        error_log($error_msg);
        wp_send_json_error(array('message' => $error_msg));
    }
    
    wp_die();
}
add_action('wp_ajax_add_installation_to_cart', 'add_installation_to_cart_ajax');
add_action('wp_ajax_nopriv_add_installation_to_cart', 'add_installation_to_cart_ajax');

// Get installation dates from cart
function get_installation_dates_ajax() {
    check_ajax_referer('modem_selection_nonce', 'nonce');
    
    $installation_category_id = 63; // Installation date category ID
    $dates = array(
        'preferred-date' => '',
        'secondary-date' => ''
    );
    
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $product_id = $cart_item['product_id'];
        
        // Check if this is an installation date product
        $product_cats = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
        if (in_array($installation_category_id, $product_cats)) {
            // First check for our custom installation_dates array
            if (isset($cart_item['installation_dates'])) {
                $dates = $cart_item['installation_dates'];
            } 
            // Fallback to variation attributes
            else if (isset($cart_item['variation']) && is_array($cart_item['variation'])) {
                $dates = array(
                    'preferred' => isset($cart_item['variation']['attribute_preferred-date']) ? 
                        $cart_item['variation']['attribute_preferred-date'] : '',
                    'secondary' => isset($cart_item['variation']['attribute_secondary-date']) ? 
                        $cart_item['variation']['attribute_secondary-date'] : ''
                );
            }
            break;
        }
    }
    
    wp_send_json_success(array('dates' => $dates));
    wp_die();
}
add_action('wp_ajax_get_installation_dates', 'get_installation_dates_ajax');
add_action('wp_ajax_nopriv_get_installation_dates', 'get_installation_dates_ajax');

// Remove installation from cart AJAX handler
function remove_installation_from_cart_ajax() {
    check_ajax_referer('modem_selection_nonce', 'nonce');
    
    $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
    
    $removed = remove_installation_from_cart($product_id);
    
    wp_send_json_success(array('message' => 'Installation removed from cart', 'removed' => $removed));
    wp_die();
}
add_action('wp_ajax_remove_installation_from_cart', 'remove_installation_from_cart_ajax');
add_action('wp_ajax_nopriv_remove_installation_from_cart', 'remove_installation_from_cart_ajax');

// Helper function to remove installation from cart
function remove_installation_from_cart($product_id) {
    $installation_category_id = 63;
    $removed = false;
    
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $cart_product_id = $cart_item['product_id'];
        
        // Check if this is an installation product
        if ($cart_product_id == $product_id) {
            $product_cats = wp_get_post_terms($cart_product_id, 'product_cat', array('fields' => 'ids'));
            if (in_array($installation_category_id, $product_cats)) {
                WC()->cart->remove_cart_item($cart_item_key);
                $removed = true;
            }
        }
    }
    
    return $removed;
}

// Display selected dates in cart and checkout
function display_selected_dates_in_cart($item_data, $cart_item) {
    // Check for our custom installation_dates array
    if (isset($cart_item['installation_dates']) && is_array($cart_item['installation_dates'])) {
        if (!empty($cart_item['installation_dates']['preferred'])) {
            $item_data[] = array(
                'key'     => 'Preferred Date',
                'value'   => $cart_item['installation_dates']['preferred'],
                'display' => '',
            );
        }
        
        if (!empty($cart_item['installation_dates']['secondary'])) {
            $item_data[] = array(
                'key'     => 'Secondary Date',
                'value'   => $cart_item['installation_dates']['secondary'],
                'display' => '',
            );
        }
    }
    // Fallback to variation attributes
    else if (isset($cart_item['variation']) && is_array($cart_item['variation'])) {
        if (isset($cart_item['variation']['attribute_preferred-date'])) {
            $item_data[] = array(
                'key'     => 'Preferred Date',
                'value'   => $cart_item['variation']['attribute_preferred-date'],
                'display' => '',
            );
        }
        
        if (isset($cart_item['variation']['attribute_secondary-date'])) {
            $item_data[] = array(
                'key'     => 'Secondary Date',
                'value'   => $cart_item['variation']['attribute_secondary-date'],
                'display' => '',
            );
        }
    }
    
    return $item_data;
}
add_filter('woocommerce_get_item_data', 'display_selected_dates_in_cart', 10, 2);





/*=============SHOW ACF FIELDS WITHIN INDIVIDUAL VARIATIONS OF VARIABLE PRODUCTS================*/

/* ACF filter for Variations */
/* ACF filter for Variations */
// Initialize global variable to track the current variation ID
$GLOBALS['wc_loop_variation_id'] = null;

// Check if field group should be applied to variations
function is_field_group_for_variation($field_group) {
    return (preg_match('/Variation/i', $field_group['title']) == true);
}

// Display ACF fields in the variation admin form
add_action('woocommerce_product_after_variable_attributes', function($loop_index, $variation_data, $variation_post) {
    $GLOBALS['wc_loop_variation_id'] = $variation_post->ID;
    
    foreach (acf_get_field_groups() as $field_group) {
        if (is_field_group_for_variation($field_group)) {
            $fields = acf_get_fields($field_group);
            acf_render_fields($variation_post->ID, $fields);
        }
    }
    
    $GLOBALS['wc_loop_variation_id'] = null;
}, 10, 3);

// Save ACF fields when variation is saved
add_action('woocommerce_save_product_variation', function($variation_id, $loop_index) {
    if (!isset($_POST['acf_variation'][$variation_id])) {
        return;
    }
    
    $_POST['acf'] = $_POST['acf_variation'][$variation_id];
    
    do_action('acf/save_post', $variation_id);
}, 10, 2);

// Modify field name to work with variations
add_filter('acf/prepare_field', function($field) {
    if (!$GLOBALS['wc_loop_variation_id']) {
        return $field;
    }
    
    // Fix: We need to modify the name attribute, not a non-existent property
    if (isset($field['name'])) {
        $field['name'] = preg_replace('/^acf\[/', 'acf_variation[' . $GLOBALS['wc_loop_variation_id'] . '][', $field['name']);
    }
    
    return $field;
}, 10, 1);

// Add product_variation as a valid post type for ACF
add_filter('acf/location/rule_values/post_type', function($choices) {
    $choices['product_variation'] = 'Product Variation';
    return $choices;
});

// Display the modem_monthly_fee on the frontend for each variation
add_filter('woocommerce_available_variation', function($variation_data, $product, $variation) {
    // Get the modem_monthly_fee value for this specific variation
    $modem_monthly_fee = get_field('modem_monthly_fee', $variation->get_id());
    
    if ($modem_monthly_fee) {
        // Add the modem monthly fee to the variation data
        $variation_data['modem_monthly_fee'] = $modem_monthly_fee;
        
        // Optionally add HTML to display it (this will be available in JavaScript)
        $variation_data['modem_monthly_fee_html'] = '<div class="variation-modem-monthly-fee">Modem Monthly Fee: ' . wc_price($modem_monthly_fee) . '</div>';
    }
    
    return $variation_data;
}, 10, 3);










/*--===========SHOW WOOCOMMERCE ADD ONS ON CUSTOM PROUCT TEMPLATE=======*/



/*===========ZACH SHORTCODE CREATION

=========*/


/*----InternetPlan Shorcode-----*/


function internet_plans_panel_shortcode() {
    // Start output buffering
    ob_start();
    ?>
    <div class="tabs-panel is-active panel-showinternetplans" id="panel-showinternetplans" style="display: block;">
        <p><br /></p>
        <?php 
        if (function_exists('RenderInternetPlans')) {
            echo RenderInternetPlans(); 
        } else {
            echo 'Internet plans are currently unavailable.';
        }
        ?>
    </div>
    <?php
    // Return the buffered content
    return ob_get_clean();
}

// Register the shortcode
add_shortcode('internet_plans_panel', 'internet_plans_panel_shortcode');


/*=================Changing Text Function================*/


function your_order_translation($translated){
    $text = array(
    'Your order' => 'Your Upfront Summary',
	'Cart totals' => 'Your Upfront Summary'
    );
    $translated = str_ireplace(  array_keys($text),  $text,  $translated );
    return $translated;
}

add_filter( 'gettext', 'your_order_translation', 20 );

/*Show Add to Cart Beneath Product in Loop*/

add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 20 );

/*==========Register monthly_fee shortcode========*/

/* function display_monthly_fee_shortcode( $atts ) {
    // Extract shortcode attributes (if any)
    $atts = shortcode_atts( array(
        'post_id' => get_the_ID(), // Default to current post ID
    ), $atts );

    // Get the 'monthly_fee' field value from the specified post
    $monthly_fee = get_field('monthly_fee', $atts['post_id']);

    // Check if there's a value for the 'monthly_fee' field
    if (!empty($monthly_fee)) {
        return esc_html($monthly_fee); // Return the value, escaped for security
    } else {
        return 'No monthly fee set'; // Return a default message or leave blank
    }
}
add_shortcode('display_monthly_fee', 'display_monthly_fee_shortcode'); */

// Function to display the monthly fee in the WooCommerce catalog view under the regular price
/* function display_monthly_fee_below_price_in_catalog() {
    global $product;
    
    // Get the 'monthly_fee' field value
    $monthly_fee = get_field('monthly_fee', $product->get_id());

    // Check if there's a value for the 'monthly_fee' field
    if (!empty($monthly_fee)) {
        // Display the monthly fee below the price
        echo '<p class="monthly-fee">Monthly Fee: ' . esc_html($monthly_fee) . '</p>';
    }
} */

// Hook the function into 'woocommerce_after_shop_loop_item_title' to display under the price
add_action('woocommerce_after_shop_loop_item_title', 'display_monthly_fee_below_price_in_catalog', 15);

/*========Make Selected Component With Pagination Appear At Topp=====*/

add_filter( 'woocommerce_component_option_details_relocation_mode', 'sw_cp_disable_relocation' );

function sw_cp_disable_relocation( $type ) {
	return 'off';
}

/*======Change # of Colunms in Thumbnail Available Selections Area======

add_filter( 'woocommerce_composite_component_loop_columns', 'wc_cp_component_loop_columns', 10, 3 );

function wc_cp_component_loop_columns( $cols, $component_id, $composite ) {
	return 4;
}
*/

/*=============
###############Zach Edits ########################
						*/

/*------ CALCULATION FUNCTIONS -----------   */

// Monthly Subtotal Calculation
function calculate_monthly_fees_subtotal() {
    error_log('calculate_monthly_fees_subtotal function started.');

    // Log WooCommerce session state
    $session_started = WC()->session->get_session_data();
    error_log('Session data: ' . print_r($session_started, true));

    // Log cart initialization state
    if (!is_object(WC()->cart)) {
        error_log('Cart object not initialized!');
    } else {
        error_log('Cart object is initialized.');
    }

    $cart_contents = WC()->cart->get_cart();
    $cart_count = WC()->cart->get_cart_contents_count();

    error_log('Cart contents count: ' . $cart_count);

    if (empty($cart_contents)) {
        error_log('Cart is empty or not initialized.');
    } else {
        error_log('Cart contents: ' . print_r($cart_contents, true)); // Log the entire cart array
    }

    $monthly_fee_subtotal = 0;

    foreach ($cart_contents as $cart_item) {
        error_log('Entering foreach loop.');
        
        $product_id = $cart_item['product_id'];
        error_log('Checking Product ID: ' . $product_id); // Log the product ID
        
        $monthly_fee = get_post_meta($product_id, 'monthly_fee', true);
        error_log('Monthly Fee Retrieved: ' . $monthly_fee); // Log the raw value of monthly_fee

        if ($monthly_fee) {
            error_log('Product ID: ' . $product_id . ' | Monthly Fee: ' . $monthly_fee . ' | Quantity: ' . $cart_item['quantity']);
            $monthly_fee_subtotal += $monthly_fee * $cart_item['quantity'];
        } else {
            error_log('Product ID: ' . $product_id . ' has no monthly_fee set.');
        }
    }

    error_log('Subtotal calculated: ' . $monthly_fee_subtotal);
    return $monthly_fee_subtotal;
}



// Monthly Tax Calculation 
function calculate_monthly_fees_tax() {
    error_log('calculate_monthly_fees_tax function started.');

    // Log the WooCommerce session state
    $session_started = WC()->session->get_session_data();
    error_log('Session data: ' . print_r($session_started, true));

    // Ensure the cart session is active
    WC()->session->set_customer_session_cookie(true);

    $tax_total = 0;
    $tax_rates = WC_Tax::get_rates();

    foreach (WC()->cart->get_cart() as $cart_item) {
        $product_id = $cart_item['product_id'];
        $monthly_fee = get_post_meta($product_id, 'monthly_fee', true);

        if ($monthly_fee) {
            $item_subtotal = $monthly_fee * $cart_item['quantity'];
            error_log('Product ID: ' . $product_id . ' | Monthly Fee: ' . $monthly_fee . ' | Quantity: ' . $cart_item['quantity'] . ' | Subtotal: ' . $item_subtotal);

            $item_taxes = WC_Tax::calc_tax($item_subtotal, $tax_rates, false);

            foreach ($item_taxes as $tax) {
                $tax_total += $tax;
                error_log('Tax added: ' . $tax);
            }
        } else {
            error_log('Product ID: ' . $product_id . ' has no monthly_fee set.');
        }
    }

    error_log('Tax total calculated: ' . $tax_total);
    return $tax_total;
}



// Monthly Total Calculation
function calculate_monthly_fees_total() {
    $subtotal = calculate_monthly_fees_subtotal();
    $tax = calculate_monthly_fees_tax();
    $total = $subtotal + $tax;
    error_log('Total calculated: ' . $total);
    return $total;
}


/* function recalculate_totals() {
    $subtotal = 0;
    $tax_total = 0;

    // Check if the session contains selected components
    if (isset($_SESSION['selected_components'])) {
        foreach ($_SESSION['selected_components'] as $component) {
            $price = $component['price'];
            $tax = calculate_tax($price); // Implement this function based on your tax settings
            $subtotal += $price;
            $tax_total += $tax;
        }
    }

    $total = $subtotal + $tax_total;

    return [
        'subtotal' => $subtotal,
        'tax_total' => $tax_total,
        'total' => $total,
    ];
} */ 


//==========Disabling Reveal buttons until products from corresponding categories added to cart

add_action('wp_ajax_check_cart_categories', 'check_cart_categories');
add_action('wp_ajax_nopriv_check_cart_categories', 'check_cart_categories');

function check_cart_categories() {
    $categories = array();
    
    foreach (WC()->cart->get_cart() as $cart_item) {
        $product_id = $cart_item['product_id'];
        $product_cats = wp_get_post_terms($product_id, 'product_cat');
        
        foreach ($product_cats as $cat) {
            $categories[] = $cat->slug;
        }
    }
    
    wp_send_json(array(
        'category1' => in_array('internet-plan', $categories),
        'category2' => in_array('fixed-fee', $categories),
        'category3' => in_array('modems', $categories),
    ));
}


//=============== Check if new product is from same category as existing cart item
add_action('wp_ajax_check_product_category', 'check_product_category');
add_action('wp_ajax_nopriv_check_product_category', 'check_product_category');

function check_product_category() {
    $new_product_id = $_POST['product_id'];
    $new_product_cats = wp_get_post_terms($new_product_id, 'product_cat');
    $has_same_category = false;
    $matching_category = '';

    foreach(WC()->cart->get_cart() as $cart_item) {
        $product_cats = wp_get_post_terms($cart_item['product_id'], 'product_cat');
        
        foreach($new_product_cats as $new_cat) {
            if(in_array($new_cat->term_id, wp_list_pluck($product_cats, 'term_id'))) {
                $has_same_category = true;
                $matching_category = $new_cat->slug;
                break 2;
            }
        }
    }

    wp_send_json([
        'has_same_category' => $has_same_category,
        'category' => $matching_category
    ]);
}

// Remove existing product from same category
add_action('wp_ajax_remove_category_product', 'remove_category_product');
add_action('wp_ajax_nopriv_remove_category_product', 'remove_category_product');

function remove_category_product() {
    $category = $_POST['category'];
    
    foreach(WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $product_cats = wp_get_post_terms($cart_item['product_id'], 'product_cat');
        
        if(in_array($category, wp_list_pluck($product_cats, 'slug'))) {
            WC()->cart->remove_cart_item($cart_item_key);
            break;
        }
    }
    
    wp_send_json_success();
}


// ------- MONTHLY SUMMARY SHORTCODE FUNCTION

/*function display_monthly_fees_summary_shortcode() {
    // Ensure WooCommerce cart is initialized
    if (!is_object(WC()->cart)) {
        WC()->cart = new WC_Cart();
    }

    $cart = WC()->cart;

    // Get the current product ID from the URL
    global $post;
    $main_product_id = $post->ID;
    $main_product = wc_get_product($main_product_id);
    $main_product_title = $main_product->get_name();
    $main_product_monthly_fee = get_post_meta($main_product_id, 'monthly_fee', true);

    // Get the product's category
    $terms = get_the_terms($main_product_id, 'product_cat');
    $main_product_category = '';
    if ($terms && !is_wp_error($terms)) {
        $main_product_category = $terms[0]->name;
    }

    // Calculate the monthly subtotal, including the main composite product's fee
    $total_monthly_fee_subtotal = $main_product_monthly_fee ? $main_product_monthly_fee : 0;
    $total_monthly_fee_subtotal += calculate_monthly_fees_subtotal();

    // Calculate the tax for the main product's monthly fee
    $main_product_tax = 0;
    if ($main_product_monthly_fee) {
        $tax_rates = WC_Tax::get_rates();
        $main_product_tax = array_sum(WC_Tax::calc_tax($main_product_monthly_fee, $tax_rates, false));
    }

    // Calculate the total tax, including the main composite product's tax
    $total_monthly_fee_tax = $main_product_tax + calculate_monthly_fees_tax();

    // Calculate the total monthly fee
    $total_monthly_fee = $total_monthly_fee_subtotal + $total_monthly_fee_tax;

    $output = '<h4>' . __('Your Monthly Summary ', 'woocommerce') . '</h4>';
    $output .= '<p style="margin-bottom: 10px; line-height: 1.2em;">This amount will be billed to you on a monthly basis starting next month</p>';
    $output .= '<table class="shop_table shop_table_responsive monthly_summary_table">';

    // Display the main composite product as the first item
    if ($main_product_monthly_fee) {
        $output .= '<tr class="individual-monthly-fee" data-product-id="' . esc_attr($main_product_id) . '">
            <th>' . esc_html($main_product_title) . '<br/><span class="product-categories">Category: ' . esc_html($main_product_category) . '</span></th>
            <td>' . wc_price($main_product_monthly_fee) . '</td>
        </tr>';
    } 

    // Display the selected components
    foreach ($cart->get_cart() as $cart_item) {
        $product = $cart_item['data'];
        $product_title = $product->get_name();
        $product_categories = wc_get_product_category_list($product->get_id(), ', ', '<span class="product-categories">', '</span>');
        $monthly_fee = isset($cart_item['monthly_fee']) ? $cart_item['monthly_fee'] : 0;
        $quantity = $cart_item['quantity'];
        $product_monthly_fee_total = $monthly_fee * $quantity;

        // Skip the main product if it's already added
        if ($cart_item['product_id'] == $main_product_id) {
            continue;
        }

        $output .= '<tr class="individual-monthly-fee">
            <th>' . $product_title . '<br/>' . $product_categories . '</th>
            <td>' . wc_price($product_monthly_fee_total) . '</td>
        </tr>';
    }

    // Always display subtotal, tax, and total rows
    $output .= '<tr class="total-monthly-fees-subtotal">
        <th>' . __('Monthly Subtotal', 'woocommerce') . '</th>
        <td>' . wc_price($total_monthly_fee_subtotal) . '</td>
    </tr>';
    $output .= '<tr class="total-monthly-fees-tax">
        <th>' . __('Monthly Tax', 'woocommerce') . '</th>
        <td class="monthly-fee-tax">' . wc_price($total_monthly_fee_tax) . '</td>
    </tr>';
    $output .= '<tr class="total-monthly-fees">
        <th>' . __('Total Monthly Fees', 'woocommerce') . '</th>
        <td>' . wc_price($total_monthly_fee) . '</td>
    </tr>';

    $output .= '</table>';

    return $output;
}

add_shortcode('monthly_fees_summary', 'display_monthly_fees_summary_shortcode');

*/



// Hide shipping costs in the checkout order review table
// add_filter('woocommerce_cart_totals_shipping_html', '__return_empty_string');

/*============Monthly Fee Summary AJAX Functions ============*/

// Enqueue JS File

function enqueue_custom_scripts() {
    wp_enqueue_script('custom-ajax-script', get_stylesheet_directory_uri() . '/js/custom-ajax.js', array('jquery'), null, true);

    wp_localize_script('custom-ajax-script', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce_subtotal' => wp_create_nonce('calculate_monthly_fee_subtotal_nonce'),
		'nonce_tax' => wp_create_nonce('calculate_monthly_fee_tax_nonce'),
		'nonce_total' => wp_create_nonce('calculate_monthly_fee_total_nonce'),
		'update_selected_product_summary_nonce' => wp_create_nonce('update_selected_product_summary_nonce'),
    ));
}

add_action('wp_enqueue_scripts', 'enqueue_custom_scripts');


// ===========Function to Handle Monthly Subtotal AJAX Request

function calculate_monthly_fee_subtotal() {
    check_ajax_referer('calculate_monthly_fee_subtotal_nonce', 'nonce');

    try {
        $monthly_fee_subtotal = calculate_monthly_fees_subtotal();
        $html = '<tr class="total-monthly-fees-subtotal">
            <th>' . __('Monthly Subtotal', 'woocommerce') . '</th>
            <td>' . wc_price($monthly_fee_subtotal) . '</td>
        </tr>';
        wp_send_json_success(array('html' => $html));
    } catch (Exception $e) {
        error_log('Exception: ' . $e->getMessage());
        wp_send_json_error(array('error' => $e->getMessage()));
    }
}

add_action('wp_ajax_calculate_monthly_fee_subtotal', 'calculate_monthly_fee_subtotal');
add_action('wp_ajax_nopriv_calculate_monthly_fee_subtotal', 'calculate_monthly_fee_subtotal');


// ====== Function to Handle Monthly Tax AJAX Request

function calculate_monthly_fee_tax() {
    check_ajax_referer('calculate_monthly_fee_tax_nonce', 'nonce');

    try {
        $tax_total = calculate_monthly_fees_tax();
        wp_send_json_success(array('monthly_fee_tax' => wc_price($tax_total)));
    } catch (Exception $e) {
        error_log('Exception: ' . $e->getMessage());
        wp_send_json_error(array('error' => $e->getMessage()));
    }
}

add_action('wp_ajax_calculate_monthly_fee_tax', 'calculate_monthly_fee_tax');
add_action('wp_ajax_nopriv_calculate_monthly_fee_tax', 'calculate_monthly_fee_tax');


// ============ Function to Handle Monthly Total AJAX Request 

function calculate_monthly_fee_total() {
    check_ajax_referer('calculate_monthly_fee_total_nonce', 'nonce');

    try {
        $monthly_fee_total = calculate_monthly_fees_total();
        $html = '<tr class="total-monthly-fees">
            <th>' . __('Total Monthly Fees', 'woocommerce') . '</th>
            <td>' . wc_price($monthly_fee_total) . '</td>
        </tr>';
        wp_send_json_success(array('html' => $html));
    } catch (Exception $e) {
        error_log('Exception: ' . $e->getMessage());
        wp_send_json_error(array('error' => $e->getMessage()));
    }
}

add_action('wp_ajax_calculate_monthly_fee_total', 'calculate_monthly_fee_total');
add_action('wp_ajax_nopriv_calculate_monthly_fee_total', 'calculate_monthly_fee_total');


// ============= Function to handle updated monthly summary table for selected products

function update_selected_product_summary() {
    check_ajax_referer('update_selected_product_summary_nonce', 'nonce');

	   if (!WC()->cart) {
        WC()->cart = new WC_Cart();
    }

    WC()->cart->get_cart();

    // Ensure the product_id is passed and valid
    if (!isset($_POST['product_id']) || empty($_POST['product_id'])) {
        error_log('Missing or invalid product ID in update_selected_product_summary.');
        wp_send_json_error(array('error' => 'Invalid product ID'));
        return;
    }

    $product_id = intval($_POST['product_id']);
    if (!$product_id) {
        error_log('Invalid product ID after intval in update_selected_product_summary.');
        wp_send_json_error(array('error' => 'Invalid product ID'));
        return;
    }

    // Safely retrieve the main product's ID, fallback to product_id if necessary
    global $post;
    $main_product_id = !empty($post) ? $post->ID : $product_id;

    // Retrieve the main product's monthly fee
    $main_product_monthly_fee = get_post_meta($main_product_id, 'monthly_fee', true);

    try {
        // Retrieve the selected product information
        $product_title = get_the_title($product_id);
        $monthly_fee = get_post_meta($product_id, 'monthly_fee', true);

        if ($monthly_fee === '') {
            throw new Exception('Monthly fee not set for product ID ' . $product_id);
        }

        // Get the selected product's category
        $terms = get_the_terms($product_id, 'product_cat');
        $category_slug = '';
        $product_category = '';
        if ($terms && !is_wp_error($terms)) {
            $category_slug = $terms[0]->slug;
            $product_category = $terms[0]->name;
        }

        // Calculate the updated subtotal, tax, and total, including the main product's fee
        $subtotal = $main_product_monthly_fee + calculate_monthly_fees_subtotal();
        $tax = calculate_monthly_fees_tax();
        $total = $subtotal + $tax;

        // Generate the HTML for the new selected product
        $html = '<tr class="individual-monthly-fee" data-category="' . esc_attr($category_slug) . '">
                    <td class="product-title">' . esc_html($product_title) . '<br/><span class="product-categories">Category: ' . esc_html($product_category) . '</span></td>
                    <td class="product-monthly-fee">' . wc_price($monthly_fee) . '</td>
                 </tr>';

        // Send the updated summary and totals back to the front-end
        wp_send_json_success(array(
            'html' => $html,
            'product_title' => $product_title,
            'product_category' => $product_category,
            'monthly_fee' => wc_price($monthly_fee),
            'category_slug' => $category_slug,
            'subtotal' => wc_price($subtotal),
            'tax' => wc_price($tax),
            'total' => wc_price($total),
            'main_product_id' => $main_product_id
        ));
    } catch (Exception $e) {
        error_log('Exception: ' . $e->getMessage());
        wp_send_json_error(array('error' => $e->getMessage()));
    }
}

add_action('wp_ajax_update_selected_product_summary', 'update_selected_product_summary');
add_action('wp_ajax_nopriv_update_selected_product_summary', 'update_selected_product_summary');


/*================================================
#Load custom Contact Form Module
================================================*/
function divi_custom_contact_form() {
	get_template_part( '/includes/ContactForm' );
	$dcfm = new Custom_ET_Builder_Module_Contact_Form();
	remove_shortcode( 'et_pb_contact_form' );
	add_shortcode( 'et_pb_contact_form', array( $dcfm, '_render' ) );
}
add_action( 'et_builder_ready', 'divi_custom_contact_form' );

function divi_custom_contact_form_class( $classlist ) { 
    // Contact Form Module 'classname' overwrite. 
    $classlist['et_pb_contact_form'] = array( 'classname' => 'Custom_ET_Builder_Module_Contact_Form',); 
    return $classlist; 
} 

add_filter( 'et_module_classes', 'divi_custom_contact_form_class' );

function AddressCheckLog($state) {

	/*** Sending information to be saved ****/
	$d["status"] = 0;
	$d["magic"] = "Sl2soDSpLAsHqetS";
	$d["api"] = "1.00";
	$d["method"] = "addresscheck";
	$d["state"] = $state;
	
	$user_data = dg_get_current_user_data();
	
	// FIX: Ensure user_data is an array before encoding
	if (!is_array($user_data)) {
		$user_data = array();
	}
	
	$info = json_encode($user_data);

	$d["user_data"] = base64_encode( $info );
	$payload = json_encode($d);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://207.167.88.7/signup.php");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLINFO_HEADER_OUT, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLINFO_HEADER_OUT, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

	// Set HTTP Header for POST request
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Content-Length: ' . strlen($payload))
	);

	$output = curl_exec($ch);
	$info = curl_getinfo($ch);
	curl_close($ch);

	// FIX: Convert info array to string for logging
	$info_string = is_array($info) ? print_r($info, true) : $info;
	error_log("Sending address check information to signup server, info: $info_string response: $output ");

	return $output;
}

// Previous Developer Functions

function SendInfoToSignupServer ( $state ) {

	/*** Sending information to be saved ****/

	$d["status"] = 0;
	$d["magic"] = "Sl2soDSpLAsHqetS";
	$d["api"] = "1.00";
	$d["method"] = "newsignup";
	$d["state"] = $state;
	
	$user_data = dg_get_current_user_data();

	if( isset( $user_data["higheststate"] ) ) {
		$higheststate = intval( $user_data["higheststate"] );
		$state = intval( $state );
		if( $higheststate  < $state ) {
			$higheststate = $state;
			dg_set_user_meta("higheststate", $higheststate);
			$user_data = dg_get_current_user_data();
		}
	} else {
		dg_set_user_meta("higheststate", $state);
	}


	$user_data["ip"] = $_SERVER['REMOTE_ADDR'];

	$info = json_encode($user_data);
	//error_log("Sending user_data : $info ");
	
	$d["user_data"] = base64_encode( $info );


	if( $state <= 1 ){
	} else {
		$d["cart"] = base64_encode( json_encode( WC()->cart->get_cart() ));
		$d["monthly_summary"] =base64_encode( json_encode(  get_monthly_fee_summary() ));
		$d["upfront_summary"] = base64_encode( json_encode(  get_upfront_fee_summary() ));
	}

	$payload = json_encode($d);

	//error_log("Sending payload : $payload");

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://207.167.88.7/signup.php");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLINFO_HEADER_OUT, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLINFO_HEADER_OUT, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

	// Set HTTP Header for POST request
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Content-Length: ' . strlen($payload))
	);

	$output = curl_exec($ch);
	$info = curl_getinfo($ch);
	curl_close($ch);

	error_log("Sending information to signup server, response $output ");

	return $output;
}


add_action( 'wp_ajax_nopriv_complete_order', 'ajax_complete_order' );
add_action( 'wp_ajax_complete_order', 'ajax_complete_order' );
function ajax_complete_order() {

	error_log( "In ajax_complete_order ");
	if (isset($_POST['data']) && !empty($_POST['data'])) {

		// process the payment for the upfront fees
		$payment_info['type'] = 'purchase';

		$summary = get_upfront_fee_summary();
		$amount = $summary['grand_total'][1];

		$cust_id = dg_get_user_meta( "signup_id" );

		error_log(" ajax_complete_order $cust_id ");
		error_log(" ajax_complete_order $amount ");
		error_log(" ajax_complete_order $data.up");

		$card_number == "";
		$expdate = "";
		$upfront_bill_payment_option = "";

		foreach( $_POST['data'] as $key=>$value ) {

			dg_set_user_meta(sanitize_text_field($key),sanitize_text_field($value));
			if( strcmp( $key, "upfront_billing_card_number" ) == 0 ) {
				$card_number = Cleanup_Number( base64_decode( $value) ); 
				error_log( " Card number $card_number " );
			}

			if( strcmp( $key, "upfront_billing_card_expiry" ) == 0 ) {
				$expdate = Cleanup_Number( $value ); 
				$expdate = substr($expdate, 2) . substr( $expdate, 0, 2);
				error_log( " Card expre $expdate " );
			}

			if( strcmp( $key, "upfront_bill_payment_option" ) == 0 ) {
				$upfront_bill_payment_option = strtolower( trim($value) );
			}

			if( strcmp( $key, "upfront_billing_postcode" ) == 0 ) {
				$postal_code= strtolower(trim ($value) );
			}

			if( strcmp( $key, "upfront_billing_card_cvv" ) == 0 ) {
				$cvd = strtolower(trim ($value) );
			}

			error_log( "ajax_complete_order : $key, $value " ) ;
		}

		// clear data in DB
		dg_set_user_meta("upfront_bill_payment_option", $upfront_bill_payment_option);
		dg_set_user_meta("order_complete_timestamp", time() );
		dg_set_user_meta("upfront_payment_msg", "");
		dg_set_user_meta("upfront_payment_code", "" );
		dg_set_user_meta("upfront_payment_ref", "" );
		dg_set_user_meta("upfront_payment_amount", "" );
		dg_set_user_meta("upfront_payment_date", "" );
		dg_set_user_meta("upfront_payment_code", "" ) ;

		if( strcmp( $upfront_bill_payment_option, "email-transfer") == 0 ) {
			dg_set_user_meta("upfront_payment", "email-transfer"); // set this to a value so we confirm the signup finished

			SendInfoToSignupServer( 100 );

			$response['status'] = "success";
			$response['upfront'] = "email-transfer";
			$response['summary'] = base64_encode( RenderUserSummary() );

			$data_response = json_encode( $response );
			error_log( $data_response );
			die( $data_response  );
		}

		error_log("Sending verify information: $cust_id, $amount, $card_number, $expdate ");
        	$payment_info['custid'] = $cust_id;
 	       	$payment_info['orderid'] = 'verify-'.date("dmy-G:i:s");
        	$payment_info['amount'] = $amount; //'1.00';
	        $payment_info['cardno'] = $card_number; //'4242424242424242';
        	$payment_info['expdate'] = $expdate; //'2011';
        	$payment_info['postal_code'] = $postal_code;
        	$payment_info['cvd'] = $cvd;

		// verify before processing a transaction
		verify_card_ex( $payment_info );

		error_log(" Sending payment information: $cust_id, $amount, $card_number, $expdate ");
 	       	$payment_info['orderid'] = 'ord-'.date("dmy-G:i:s");
		$mpg_response = ProcessPayment( $payment_info );
		error_log( "Got Response back " . $mpg_response->getComplete() );
		if( $mpg_response == false || 
			strcmp( $mpg_response->getComplete(), "true") ||
			$mpg_response->getResponseCode() == false || 
			$mpg_response->getResponseCode() == null ||
			$mpg_response->getResponseCode() >= 50 ) {

			$response['status'] = "failed";
			$response['msg'] = $mpg_response->getMessage();
			$response['code'] = $mpg_response->getResponseCode();
			$response['ref'] = $mpg_response->getReferenceNum();
	
			$data_response = json_encode( $response );
			error_log( $data_response );
			die( $data_response  );
		} else {
			dg_set_user_meta("upfront_payment", $mpg_response->getComplete());
			dg_set_user_meta("order_complete_timestamp", time() );
			dg_set_user_meta("upfront_payment_msg", $mpg_response->getMessage() );
			dg_set_user_meta("upfront_payment_code", $mpg_response->getResponseCode() );
			dg_set_user_meta("upfront_payment_ref", $mpg_response->getReferenceNum() );
			dg_set_user_meta("upfront_payment_amount", $mpg_response->getTransAmount() );
			dg_set_user_meta("upfront_payment_date", $mpg_response->getTransDate() );
			dg_set_user_meta("upfront_payment_code", $mpg_response->getResponseCode() );
		
			SendInfoToSignupServer( 100 );

			$response['status'] = "success";
			$response['summary'] = base64_encode( RenderUserSummary() );

			$data_response = json_encode( $response );
			error_log( $data_response );
			die( $data_response  );
		}
			
	} else {
	
		die("Error");
	
	}
	
	die();

}



function RenderUserSummary() {


	return "Hello World!!";

}

add_action( 'wp_ajax_nopriv_find_address_signup', 'ajax_find_address_signup' );
add_action( 'wp_ajax_find_address_signup', 'ajax_find_address_signup' );
function ajax_find_address_signup( $ccd = false ) {

	if( isset( $_POST["ccd_param"] ) ) {
		$ccd = $_POST["ccd_param"];
		if( strlen( $ccd ) > 0 ) {
			dg_set_user_meta( "ccd", $ccd );
		}
	}

	$response = ppget_internet_plans(0, $ccd);
	$save_status_json = trim( SendInfoToSignupServer(1) );

	if( strlen( $save_status_json ) > 0 ) {
		$save_status = json_decode( $save_status_json );
		$status = trim( $save_status->status );
		if( strcmp( $status, "success" ) == 0 ) {
			//error_log( "FindAddressSignup, return new signup " . $status );
			if( $save_status->sid == "success") {
				dg_set_user_meta( "signup_id", "" );
			} else {
				dg_set_user_meta( "signup_id", $save_status->sid );
			}
		}
	}

	wp_die($response);
}


/*==========Final Fixed AJAX Function - Provides data in expected format========*/

add_action( 'wp_ajax_nopriv_find_address_with_redirect', 'ajax_find_address_with_redirect_client_geocoded' );
add_action( 'wp_ajax_find_address_with_redirect', 'ajax_find_address_with_redirect_client_geocoded' );
function ajax_find_address_with_redirect_client_geocoded() {
    error_log("=== CLIENT GEOCODED: ajax_find_address_with_redirect called ===");
    error_log("POST data: " . print_r($_POST, true));
    
    $ccd = "";
    
    // getting the ccd parameters (referral to our website);
    if( isset( $_POST["ccd_param"] ) ) {
        $ccd = $_POST["ccd_param"];
        if( strlen( $ccd ) > 0 ) {
            dg_set_user_meta( "ccd", $ccd );
        }
    }
    
    // Check if this is a redirect request
    $should_redirect = isset($_POST["redirect_to_plans"]) && $_POST["redirect_to_plans"] === 'true';
    
    error_log("Should redirect: " . ($should_redirect ? 'yes' : 'no'));
    
    if ($should_redirect) {
        error_log("Processing redirect request...");
        
        // Get the address data
        $street_address = isset($_POST['streetAddress']) ? sanitize_text_field($_POST['streetAddress']) : '';
        $unit_number = isset($_POST['unitNumber']) ? sanitize_text_field($_POST['unitNumber']) : '';
        $buzzer_code = isset($_POST['buzzerCode']) ? sanitize_text_field($_POST['buzzerCode']) : '';
        
        // Get the geocoded address components from the client
        $geocoded_address = isset($_POST['geocoded_address']) ? $_POST['geocoded_address'] : null;
        
        error_log("Received street address: " . $street_address);
        error_log("Received geocoded address: " . print_r($geocoded_address, true));
        
        if (empty($street_address)) {
            wp_send_json_error(array(
                'message' => 'No street address provided.'
            ));
            return;
        }
        
        if (!$geocoded_address || !is_array($geocoded_address)) {
            wp_send_json_error(array(
                'message' => 'Address geocoding failed. Please try selecting a different address.'
            ));
            return;
        }
        
        // Ensure all required keys are present with defaults
        $searched_address = array_merge(array(
            'street_number' => '',
            'route' => '',
            'street_name' => '',
            'street_type' => '',
            'street_dir' => '',
            'sublocality_level_1' => '',
            'locality' => '',
            'administrative_area_level_2' => '',
            'administrative_area_level_1' => '',
            'country' => '',
            'postal_code' => '',
            'manual_search' => 0
        ), $geocoded_address);
        
        // Parse the route into street components if needed (same as availability_check.php does)
        if (!empty($searched_address['route']) && empty($searched_address['street_name'])) {
            if (function_exists('parse_street_components')) {
                $route_components = parse_street_components($searched_address['route']);
                if (isset($route_components['street_name'])) {
                    $searched_address['street_name'] = $route_components['street_name'];
                }
                if (isset($route_components['street_type'])) {
                    $searched_address['street_type'] = $route_components['street_type'];
                }
                if (isset($route_components['street_dir'])) {
                    $searched_address['street_dir'] = $route_components['street_dir'];
                }
            }
        }
        
        error_log("Final searched_address: " . print_r($searched_address, true));
        
        // Set up the user_data array
        $user_data = array(
            'full_name' => '',
            'email' => '',
            'lead_status' => '',
            'onboarding_stage' => 'address_search'
        );
        
        // Set up POST data exactly like the main page does
        $_POST['streetAddress'] = $street_address;
        $_POST['unitNumber'] = $unit_number ?: '';
        $_POST['buzzerCode'] = $buzzer_code ?: '';
        $_POST['unitType'] = ''; // Required to prevent undefined index in availability_check.php
        $_POST['searched_address'] = $searched_address;
        $_POST['user_data'] = $user_data;
        
        error_log("Set up POST data with client-geocoded address components");
        
        // Suppress PHP notices from availability_check.php temporarily
        $original_error_reporting = error_reporting();
        error_reporting(E_ERROR | E_PARSE);
        
        try {
            error_log("Calling ppget_internet_plans with client-geocoded data...");
            $response = ppget_internet_plans(1, $ccd);
            error_log("ppget_internet_plans returned, length: " . strlen($response));
            
            // Restore error reporting
            error_reporting($original_error_reporting);
            
            // Check the API response stored in user meta
            $apiResponse = dg_get_user_meta("_api_response");
            error_log("API Response from user meta: " . print_r($apiResponse, true));
            
            // Send the data to the server for logging
            AddressCheckLog( 0 );
            
            // Determine success/failure
            $success_found = false;
            
            // Check the stored API response for service availability
            if ($apiResponse && is_array($apiResponse)) {
                if (isset($apiResponse['error']) && $apiResponse['error'] === false && 
                    isset($apiResponse['success']) && $apiResponse['success'] === true) {
                    
                    // Check if any service provider is available
                    $providers = array('bell', 'telus', 'shaw', 'rogers', 'cogeco');
                    foreach ($providers as $provider) {
                        if (isset($apiResponse[$provider]) && $apiResponse[$provider] === true) {
                            $success_found = true;
                            error_log("SUCCESS: Found service provider: $provider");
                            break;
                        }
                    }
                }
            }
            
            // Fallback: Check HTML response for plan elements
            if (!$success_found) {
                $plan_indicators = array(
                    'plan_buy_now_btn',
                    'Select Plan',
                    'internet_plan_selector',
                    'radio_box_wrapper',
                    'InternetPlan'
                );
                
                foreach ($plan_indicators as $indicator) {
                    if (stripos($response, $indicator) !== false) {
                        $success_found = true;
                        error_log("SUCCESS: Found plan indicator in HTML response: $indicator");
                        break;
                    }
                }
            }
            
            // Check for explicit failure indicators
            $is_not_available = false;
            $not_available_indicators = array(
                "Check Service Availability in Your Area",
                "plan_check_availability_btn"
            );
            
            foreach ($not_available_indicators as $indicator) {
                if (stripos($response, $indicator) !== false) {
                    $is_not_available = true;
                    error_log("FAILURE: Found not available indicator: $indicator");
                    break;
                }
            }
            
            error_log("Final analysis: success_found=" . ($success_found ? 'true' : 'false') . 
                      ", is_not_available=" . ($is_not_available ? 'true' : 'false'));
            
            if ($success_found && !$is_not_available) {
                // Success - service is available at this address
                error_log("SUCCESS: Internet service available, sending redirect response");
                
                wp_send_json_success(array(
                    'redirect' => true,
                    'redirect_url' => home_url('/internet#internet-plan-section'),
                    'message' => 'Address updated successfully'
                ));
            } else {
                // No service available at this address
                error_log("ERROR: No internet service available at this address");
                
                $error_message = 'No internet service available at this address.';
                if ($apiResponse && isset($apiResponse['errorType']) && !empty($apiResponse['errorType'])) {
                    $error_message .= ' (' . $apiResponse['errorType'] . ')';
                }
                
                wp_send_json_error(array(
                    'message' => $error_message . ' Please try selecting a different address from the autocomplete suggestions.'
                ));
            }
            
        } catch (Exception $e) {
            error_reporting($original_error_reporting);
            error_log("Exception in ppget_internet_plans: " . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'An error occurred while checking address availability. Please try again.'
            ));
        }
        
    } else {
        // Original behavior for the main internet page
        error_log("Non-redirect request, using original behavior");
        $response = ppget_internet_plans(1, $ccd);
        AddressCheckLog( 0 );
        wp_die($response);
    }
}


// Add this temporary debug function to see what the API returns
add_action( 'wp_ajax_nopriv_debug_api_response', 'debug_api_response' );
add_action( 'wp_ajax_debug_api_response', 'debug_api_response' );
function debug_api_response() {
    $apiResponse = dg_get_user_meta("_api_response");
    
    wp_send_json_success(array(
        'api_response' => $apiResponse,
        'user_meta_keys' => array_keys(dg_get_current_user_data() ?: array())
    ));
}


/*==========Test function for debugging========*/
add_action( 'wp_ajax_nopriv_test_redirect_simple', 'test_redirect_simple' );
add_action( 'wp_ajax_test_redirect_simple', 'test_redirect_simple' );

function test_redirect_simple() {
    wp_send_json_success(array(
        'redirect' => true,
        'redirect_url' => home_url('/internet#internet-plan-section'),
        'message' => 'Test redirect successful'
    ));
}



/*==========Restore original find_address function if it was broken========*/

// Make sure the original find_address function still works
add_action( 'wp_ajax_nopriv_find_address', 'ajax_find_address' );
add_action( 'wp_ajax_find_address', 'ajax_find_address' );
function ajax_find_address( $ccd = false ) {
    $ccd = "";

    // getting the ccd parameters (referral to our website);
    if( isset( $_POST["ccd_param"] ) ) {
        $ccd = $_POST["ccd_param"];
        if( strlen( $ccd ) > 0 ) {
            dg_set_user_meta( "ccd", $ccd );
        }
    }

    error_log(" In ajax_find_address -> ppget_internet_plans $ccd ");
    $response = ppget_internet_plans(1,  $ccd);

    // commented out checking for email, to send the data anyways to the server for logging
    //$user_data = dg_get_current_user_data();
    //if( strlen( $user_data.email ) > 0 ) { 
        AddressCheckLog( 0 );
    //}

    wp_die($response);
}

// ============ Zach Note: Review This function ===========
// This functin facilitates address lookup

function ppget_internet_plans($MainWebPage, $ccd) {

	$prod_id_15_1 = 334;
	$prod_id_6 = 300;

	error_log("In ppget_internet_plans MainParam = $MainWebPage ");

	$ret_invald_address = "";
	$ret_diff_address = "";

	if( $MainWebPage == 1 ) {
		$ret_invald_address = "<a class=\"btn_mute plan_check_availability_btn\" href=\"#\" ";
		$ret_invald_address .= "data-target=\"#plan-building-wizard-modal\" data-toggle=\"modal\"";
		$ret_invald_address .= "rel=\"noopener noreferrer\">Check Service Availability in Your Area</a>";

		$ret_diff_address = "<a class=\"btn_mute plan_check_other_availability_btn\" href=\"#\" ";
		$ret_diff_address .= "data-target=\"#plan-building-wizard-modal\" data-toggle=\"modal\"";
		$ret_diff_address .= "rel=\"noopener noreferrer\">Search Different Address</a>";
	}

	if( isset( $_POST['streetAddress'] ) == true ) { // this is a new search
		error_log("This is a new address " .  $_POST['streetAddress']  );
		$apiResponse = find_address_availability_ex();
	} else {
		$apiResponse = dg_get_user_meta("_api_response");
	}

	if( $apiResponse == null || $apiResponse["error"] == true ) {
		error_log("find_address_availability did not find the address - sending button to check avail");
		return $ret_invald_address;
	}

	error_log("In ppget_internet_plans " . json_encode( $apiResponse ) );
	//$ret_title = "<div class=\"internet-packages-mobile-section\">";
	$ret_title = "<div class=\"internet-packages-check-availability\">";
	$ret_title .= "<div class=\"internet-packages-location-button-group\"><div class=\"internet-packages-check-availability-title\">"; 
	$ret_title .= "<i class=\"fa fa-map-marker internet-packages-location-icon\" aria-hidden=\"true\"></i>"; 
	$ret_title .= "<small class=\"internet-packages-location\">  " . $apiResponse["address"] .  "</small></div>"; 
	$ret_title .= $ret_diff_address;
  	$ret_title .= "</div>";

	$selected_internet_plan = dg_get_user_meta("selected_internet_plan");

	if ( $apiResponse == NULL ) {
		error_log("No availability check done, need it first to get the plans ");
		return $ret_invald_address;
	}

	$query = new WC_Product_Query( array(
		'limit' => -1,
		'post_type' => array( 'product', 'product_variation' ),
		'orderby' => 'menu_order',
		'order' => 'DESC',
		'category' => array('internet-plan'),
	) );

	$products = $query->get_products();

	$button_text = '<a class="btn_mute" href="#" data-target="#plan-building-wizard-modal" rel="noopener noreferrer">Select Service</a>';
	
	$disply_prod = $ret_title;

	if($apiResponse["bell"] == true) {
		$bell_plans_avail = explode(",", trim( $apiResponse["bell_max_down"] ));
	}
	if($apiResponse["telus"] == true) {
		$telus_plans_avail = explode(",", trim( $apiResponse["telus_services"] ));
	}

	$ccd_clear = "";
	if( strlen( trim( $ccd ) )  > 0 ) {
		$ccd_clear = base64_decode ( trim( $ccd ) );
		error_log(" In ppget_internet_plans ---- $ccd = $ccd_clear ");

	}

	$disply_prod .= '<div class="box-radio internet-redio_box internet_plan_selector">';
	$found_one_match = false;
	$bell_plans_found = false;
	foreach ($products as $prod) {

		if ($prod->is_in_stock() == false ) {
			continue;
		}

		$prod_id_only = $prod->get_id();

		/* 
		 * Finding if this product is a special case product 15/1 and 6
		 * these needs to hide if there are higher speeds available
		 */
		$prod_sku = $prod->get_sku();
		$skus = explode(",",$prod_sku);
		foreach( $skus as $s ) {
			$s = trim($s);
			if( strcmp( $s, "GASR006008N") == 0 ) {
				$prod_id_6 = $prod_id_only; // this is a DSL 6 product
				break;
			}
			if( strcmp( $s, "GASR01501N") == 0 ) {
				$prod_id_15_1 = $prod_id_only; // this is a DSL 6 product
				break;
			}
		}
		/****************************************************/

		if( $apiResponse["bell"] == true ) {
			if( $prod_id_only == $prod_id_6  && $bell_plans_found == true ) {
				if( sizeof( $bell_plans_avail ) > 1 )  {
					continue; // skip the Internet 6 plan DSL
				}
			}
			if( $prod_id_only == $prod_id_15_1 ) {
				$found_marching15 = false;
				for($i=0; $i < sizeof($bell_plans_avail); $i++) {
					$pblan = trim($bell_plans_avail[$i]);
					if( strcmp( $pblan, "GASR01510N") == 0 ) {
						$found_marching15 = true;
						break;
					}
				}
				if( $found_marching15 == true) {
					continue;
				}
			}
		}

		$found_match = false;
		//* repeated above the explode sku, can be optimized, but playing safe for now
		$prod_sku = $prod->get_sku();
		$skus = explode(",",$prod_sku);
		foreach( $skus as $s ) {

			$s = trim($s);
			if( strlen($s) == 0 ) {
				continue;
			}

			if( $apiResponse["bell"] == true ) {
				for($i=0; $i < sizeof($bell_plans_avail); $i++) { 
					$pblan = trim($bell_plans_avail[$i]);
					$sku_bell_speed = $s;
					if( strlen( $pblan ) <= 0 || strlen($sku_bell_speed) <= 0 ) {
						continue;
					}
					if( strcmp( $pblan, $sku_bell_speed ) == 0 ) {
						$found_match = true;
						$bell_plans_found = true;
						break;
					}
				}
			}

			if( $apiResponse["telus"] == true ) 
			{
				for($i=0; $i < sizeof($telus_plans_avail); $i++) 
				{ 
					$pblan = trim( $telus_plans_avail[$i] );
					if( strcmp( $pblan, $s ) == 0 ) 
					{
						$found_match = true;
						break; // good continue;
					}
				}
			}

			if( $apiResponse["cogeco"] == true ) {
				if( strcmp( $s, "cogeco") == 0 ) {
					$found_match = true;
					break;  // good continue;
				}
			}

			if( $apiResponse["shaw"] == true ) {
				if( strcmp( $s, "shaw") == 0 ) {
					$found_match = true;
					break;  // good continue;
				}
			}

			if( $apiResponse["rogers"] == true ) {
				if( strcmp( $s, "rogers") == 0 ) {
					$found_match = true;
					break;  // good continue;
				}
			}
		}

		if( $found_match == false ) {
			continue; // do not add this product because its not available in the address
		}

		$show_ccd = trim( $prod->get_attribute("CCD_show") );
		$hide_ccd = trim( $prod->get_attribute("CCD_hide") );
		error_log(" ppget_internet_plans ---- $ccd_clear, show $show_ccd, hide $hide_ccd ");

		if( strlen( $show_ccd ) > 0 ) {
			$show_prod = false;
			if( strlen( $ccd_clear ) > 0 ) {
				$show_ccds = explode( ",", $show_ccd );
				foreach( $show_ccds as $s ) {
					$s = strtolower( trim( $s ) );
					if( strcmp($ccd_clear , $s ) == 0 ) {
						$show_prod = true;
						break;
					}
				}
			}
			if( $show_prod == false ) {
				continue;
			}
		}
		if( strlen( $ccd_clear ) > 0 &&  strlen( $hide_ccd ) > 0 ) {
			$show_prod = true;
			$hide_ccds = explode( ",", $hide_ccd );
			foreach( $hide_ccds as $s ) {
				$s = strtolower( trim( $s ) );
				if( strcmp($ccd_clear , $s ) == 0 ) {
					$show_prod = false;
					break;
				}
			}
			if( $show_prod == false ) {
				continue;
			}
		}

		// $disply_prod .= "<div class=\"radio_box_wrapper\">";
		$disply_prod .= "<div class=\"radio_box_wrapper\">";

$prod_id = "Internet-" . $prod->get_id();
$prod_id_only = $prod->get_id(); // Ensure you have the actual product ID for comparison

// Zach Additional Edits
$product_url = get_permalink($prod_id_only);

// Append the ccde query parameter to the product URL
$product_url_with_ccd = $product_url . '?ccde=' . urlencode($ccd);

error_log("In render Internet plan, $prod_id_only");

 $disply_prod .= '<input type="radio" name="InternetPlan" ';
$disply_prod .= "id=\"$prod_id\" value=\"$prod_id_only\" ";

if (strcmp($selected_internet_plan, $prod_id_only) == 0) {
    $disply_prod .= ' checked >';
} else {
    $disply_prod .= '>';
}

if ($MainWebPage == 1) {
 $button_text = '<a class="btn_orange_grd plan_buy_now_btn" href="' . esc_url($product_url_with_ccd) . '" data-plan-id="' . esc_attr($prod_id_only) . '">Select Plan</a>';
} else {
    $button_text = '<button type="button" class="button small confirmation_button confirm_internet_plan" onclick="select_internet_plan(\'' . esc_attr($prod_id_only) . '\');"> <span aria-hidden="true">Confirm</span></button>';
}

			//$button_text = ''; 

		$prod_desc = str_replace( "<div class=\"checkout_buttons\"></div>",
			str_replace("{prod_id}",$prod->get_id(),$button_text),$prod->get_description());

		$disply_prod .= "<label for=\"$prod_id\">";
		$disply_prod .= "<span class=\"internet-sku\" style=\"display:none\">$prod_sku</span>";
		$disply_prod .= $prod_desc;
		$disply_prod .= '</label>';

		$disply_prod .= "</div>";
		$found_one_match = true;
	}

	$disply_prod .= "</div>";

	if( $found_one_match == false ) {
		return $ret_invald_address;
	}

	return $disply_prod;
}

add_action( 'wp_enqueue_scripts', 'diallog_theme_enqueue_styles',99);
function diallog_theme_enqueue_styles() {
	  //wp_enqueue_style( 'font-awesome', '//stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css' );
	  wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
	  wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array('parent-style'),"1.5.0.7","all");
	  wp_enqueue_style( 'custom-style', get_stylesheet_directory_uri() . '/css/custom.css', array('parent-style','child-style'),"0.9.1.6","all");
	  wp_dequeue_style('divi-style');
	  wp_enqueue_style( 'multisteps-style', get_stylesheet_directory_uri() . "/css/multistep.css", array() , "0.9.1","all");
	  
	  wp_enqueue_script( "bootstrap-main", get_stylesheet_directory_uri(). "/js/bootstrap.min.js" , array("jquery"), "3.3.7","all");
	  
	  wp_enqueue_script( "jquery-mask", get_stylesheet_directory_uri() . '/js/jquery.mask.min.js', array("jquery"), "1.14.16", true );

	  wp_enqueue_script( "diallog-main", get_stylesheet_directory_uri() . '/js/d-main.js', array("jquery"), "0.0.5", true );
	  
	  //wp_enqueue_script( "google-maps-api2","//maps.googleapis.com/maps/api/js?key=AIzaSyCVLq3DrRD2BizXm-yZ-WsD2qq0ofWN2VU&libraries=places" , array(), "1.0", true );
	  wp_enqueue_script( "google-maps-api2","//maps.googleapis.com/maps/api/js?key=AIzaSyAQ_uaqGJF-ALsSrkYEzKOHbI27WC-vEZg&libraries=places" , array(), "1.0", true );
	  //wp_enqueue_script( "google-maps-api2","https://maps.googleapis.com/maps/api/js?key=AIzaSyB1IAKntR9qg34Q-4eANCMqNoNQ1UE8j1M&libraries=places" , array(), "1.0", true );
	  wp_enqueue_script("jquery-ui","https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js",array("jquery"),"1.12.1",false);
	  wp_enqueue_script("jquery-ui-datepicker");
	  wp_enqueue_style('jquery-ui','https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/south-street/jquery-ui.css',false,'1.12.1',false);
}

add_action("wp_head","dg_wp_head",99);
function dg_wp_head() {
	
	if (is_page(2935) && sizeof( WC()->cart->get_cart() ) == 0 ) {
		
		wp_safe_redirect(get_the_permalink(2265)."?show_availability_checker");
		exit;
	}
	
	
	echo "<script> var dg_today = new Date('".date("m/d/Y")."'); var default_days = 6; </script>"."\r\n";
	//echo "<script> var dg_today = new Date('2019-04-16'); var default_days = 6; </script>"."\r\n";
	
	$invalid_service_dates['ON'] = array("01/01/2019","02/18/2019","04/19/2019","05/12/2019","05/20/2019","06/16/2019","07/01/2019","08/05/2019","09/02/2019","10/14/2019","12/25/2019","12/26/2019");
	
	$invalid_service_dates['QC'] = array("01/01/2019","04/19/2019","04/22/2019","05/12/2019","05/20/2019","06/16/2019","06/24/2019","07/01/2019","09/02/2019","10/14/2019","12/25/2019","12/31/2019");
	
	
	echo "<script>";
		
		echo "var invalid_service_dates = ["."\r\n";	
	
	$searched_address =  dg_get_user_meta ("searched_address");	
	
	if (!empty($searched_address['provinceOrState'])) {
		
		$st = $searched_address['provinceOrState'];
		
	} else {
		
		$st = "ON";
		
	}
	
	if (is_array($invalid_service_dates[$st]) && count($invalid_service_dates[$st])>0) {
		foreach ($invalid_service_dates[$st] as $isd) {
			echo "'$isd',"."\r\n";
		}
	} 
	echo "]"."\r\n";
	echo "</script>";
	
}

add_shortcode("dg_get_internet_plans","dg_get_internet_plans");
function dg_get_internet_plans( $atts ) {

	$atts = shortcode_atts( array(
		'ids' => 'all',
		'type'=> array('internet-plan'),
	), $atts, 'dg_get_internet_plans' );
	
	if ($atts['ids']=="all") {
		
		$query = new WC_Product_Query( array(
		    'limit' => -1,
		    'orderby' => 'date',
		    'order' => 'DESC',
		    'category' => $atts['type'],
		    
		) );
	
	} else {
		
		$query = new WC_Product_Query( array(
		    'limit' => 1,
		    'orderby' => 'date',
		    'order' => 'DESC',
		    'category' => $atts['type'],
		    'include' => explode(",",$ids)
		    
		));

	}
	
	$products = $query->get_products();
	//var_dump($products);
	$button_text = '<a class="btn_mute plan_check_availability_btn" href="#" data-target="#plan-building-wizard-modal" data-toggle="modal" rel="noopener noreferrer">Check Service</a> 
                    <a style="display:none;" class="btn_orange_grd plan_buy_now_btn" href="/residential/signup/?add-plan={prod_id}" data-plan-id="{prod_id}" >Select Plan</a>';

	$disply_prod = "";	
	foreach ($products as $prod) {
		
		if($prod->is_in_stock() == false) {
			continue;
		}

		$prod_desc = str_replace( "<div class=\"checkout_buttons\"></div>",
			str_replace("{prod_id}",$prod->get_id(),$button_text),$prod->get_description()); 
		
		$skus = explode(",",$prod->get_sku());
		
		$prod_class = "offer-".implode(" offer-",$skus);
		
		$disply_prod .= "<div class='internet-offer $prod_class' >". $prod_desc ."</div>";  	
		
	}
	
	return $disply_prod;

}

add_shortcode("dg_signup_page","dg_signup_page");
function dg_signup_page( $atts ) {
	
	ob_start();
	
	if (!is_admin()) {
		get_template_part("templates/page","signup");	
	}
	
	return ob_get_clean();	
	
}

function _add_product_tocart($plan_id, $type) {

	global $dg_order;
	global $woocommerce;

	error_log("In _add_product_tocart $plan_id");

	$product_id   = apply_filters('woocommerce_add_to_cart_product_id', absint( $plan_id ));
	$product_data = wc_get_product( $product_id );
	$product_cat_ids = $product_data->get_category_ids();
	$product_cat = get_term($product_cat_ids[0],'product_cat');
	$product_category = $product_cat->slug;
		
	$quantity = apply_filters( 'woocommerce_add_to_cart_quantity', 1, $product_id );

	if ( $quantity <= 0 || ! $product_data || 'trash' === $product_data->get_status() ) {
		return "Plan Not Available";        
	}

	$found = false;

	error_log("In _add_product_tocart - found plan id $product_id " );

	//check if product already in cart
	if ( sizeof( $woocommerce->cart->get_cart() ) > 0 ) {
		foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
			$_product = $values['data'];
			if ( $_product->id == $product_id ) {
				$found = true;
				break;
			}
		}
	}

	if (!$found) {
		
		//find a product in same category and remove it before adding new.
		foreach ( WC()->cart->get_cart() as $item_key => $value ) {
			$cart_product_data = wc_get_product( $value['product_id'] );							           
			$cart_product_cat_ids = $cart_product_data->get_category_ids();

			//if product is of same category then remove it; 
			if ($cart_product_cat_ids[0]==$product_cat_ids[0]) {  
				WC()->cart->remove_cart_item($item_key);
				break;
			}
		}
				
		error_log("In _add_product_tocart - adding it now $product_id, $quantity " );
		$ret = $woocommerce->cart->add_to_cart( $product_id, $quantity );
		error_log("In _add_product_tocart - adding it now ret = $ret " );

		dg_set_user_meta("selected_".$type."_plan",$product_id);
		dg_set_user_meta("selected_".$type."_plan_name",$product_data->get_title());

		error_log(" Cart_item_id $product_id, Cart_ItemCategory $product_category, Cart_ItemTitle " . $product_data->get_title() );

		dg_set_user_meta("cart_category_" . $product_id , $product_category);
		dg_set_user_meta("cart_title_" . $product_id , $product_data->get_title());

		dg_set_user_meta("category_" . $product_category, $product_id);

		if( strcmp( $product_category, "internet-plan" ) == 0 ) {
			$id = $product_data->get_attribute("id");
			dg_set_user_meta("category_" . $product_category, $id );
		}
		if ( sizeof( $woocommerce->cart->get_cart() ) > 0 ) {

			SendInfoToSignupServer(2);

			return "success";

		} else {

			return "plan could not be added";

		}

	} else {
		return "success";
	}
}

add_action("wp_ajax_add_plan","dg_add_product_to_cart");	
add_action("wp_ajax_nopriv_add_plan","dg_add_product_to_cart");
function dg_add_product_to_cart ($plan_id = false , $type =  false) {

	$ret = "";

	if ($plan_id) {

		$ajax = false;
		$type = $type ? $type : "internet";

	} elseif (isset($_POST['plan_id']) && !empty($_POST['plan_id'])) {

		$ajax = true;
		$plan_id = sanitize_text_field($_POST['plan_id']);
		$type = sanitize_text_field( ($_POST['type']!="" ? $_POST['type'] : "internet") );

	}

	error_log( " In dg_add_product_to_cart plan $plan_id, type $type " );

	// Add Installation Fees
	// 1. Check which Internet plan is selected
	// 2. Find the Fixed Fee, with SKU matching the sku of the internet plan
	// 3. Add it to cart
	if( $type == "Internet Plan" ) {
		// Check which plan is selected 
		// find the Fixed Fees with matching sku
		$internet_plan_id = wc_get_product( $plan_id );
		if( $internet_plan_id == false ) {
			goto error;
		}

		// add the internet plan
		$ret = _add_product_tocart($plan_id, $type);
		if( $ret != "success") {
			error_log("Added Internet Plan, $plan_id, but ret failed $ret");
			goto finish;
		}

		// add the installation fees
		$internet_plan_sku = $internet_plan_id->get_sku();
		$internet_plan_sku_list = explode(",",$internet_plan_sku);

		$query = new WC_Product_Query( array(
			'limit' => -1,
			'orderby' => 'date',
			'order' => 'DESC',
			'category' => "Fixed Fee",
		) );

		$products = $query->get_products();
		foreach($products as $prod) {
			$install_plan_sku = $prod->get_sku();
			$install_plan_sku_list = explode(",",$install_plan_sku);

			foreach($install_plan_sku_list as $install_sku) {
				foreach($internet_plan_sku_list as $internet_sku) {
					if( strcmp( $internet_sku, $install_sku) == 0 ) {
						// found match;
						$plan_id = $prod->get_id();
						$ret = _add_product_tocart($plan_id, $type);
						goto finish;
					}
				}
			}
		}

	}

	if ($plan_id) {
		//hubspot_set_user_data();
		$ret = _add_product_tocart($plan_id, $type);
		error_log(" dg_add_product_to_cart return $ret ") ;
	} 

finish:

	if ($ajax) {
		die($ret);		
	} else {
		return false;
	}

error:
	if ($ajax) {
		die("Error");		
	} else {
		return false;
	}
}


add_action("wp_ajax_remove_from_cart","dg_remove_from_cart");	
add_action("wp_ajax_nopriv_remove_from_cart","dg_remove_from_cart");
function dg_remove_from_cart ( $type = false , $category = false ) {
	
	global $woocommerce;
	
	if ($type && $category) {
		
		$ajax = false;
		
	
	} elseif ( isset($_POST['action']) && $_POST['action']=="remove_from_cart" ) {
		$ajax = true;

		error_log("Action = remove_from_cart" );
		if( isset( $_POST['plan_id'] ) == true ) {
			$plan_id = $_POST['plan_id'];
			error_log("Action = remove_from_cart $plan_id" );
			foreach ( WC()->cart->get_cart() as $item_key => $value ) {
				if( $value['product_id'] == $plan_id ) {
					WC()->cart->remove_cart_item($item_key);
				}	
			}
			die("success");		
		}

		$type = sanitize_text_field($_POST['type']);
		$category = sanitize_text_field($_POST['category']);
	} 

	if( strlen($type) > 0 && strcmp($type, "all-types") == 0 ) {
		WC()->cart->empty_cart();
		if ($ajax) {
			die("success");		
		} else {
			return true;
		}
	}

	if ($type && $category) {
		foreach ( WC()->cart->get_cart() as $item_key => $value ) {

			$cart_product_data = wc_get_product( $value['product_id'] );							           
			$cart_product_cat_ids = $cart_product_data->get_category_ids();
			$product_cat = get_term($cart_product_cat_ids[0],'product_cat');
			$product_category = $product_cat->slug;

			//if product is of same category then remove it; 
			if ($product_category==$category) {

				WC()->cart->remove_cart_item($item_key);
				dg_set_user_meta("selected_".$type."_plan","");
				dg_set_user_meta("selected_".$type."_plan_name","");

				if ($type=="internet") {
					dg_set_user_meta("order_step", 1);		
				} elseif ($type=="phone") {
					dg_set_user_meta("order_step",5);		
				} elseif ($type=="modems") {
					dg_set_user_meta("order_step",6);	
				}

				if ($ajax) {
					die("success");		
				} else {
					return true;
				}

				break;
			}
		}
	} else {
		if ($ajax) {
			die("error");		
		} else {
			return false;
		}
	}
    
	if ($ajax) {
		die();		
	} else {
		return false;
	}
}


add_action("wp_ajax_update_order_review","dg_update_order_review");	
add_action("wp_ajax_nopriv_update_order_review","dg_update_order_review");
function dg_update_order_review () {
	
	global $woocommerce;
	
	do_action( 'woocommerce_checkout_order_review' );
	
	die();

    
}

add_action("wp_ajax_get_order_summary_page","dg_get_order_summary_page");	
add_action("wp_ajax_nopriv_get_order_summary_page","dg_get_order_summary_page");
function dg_get_order_summary_page () {
		
	get_template_part("templates/page","signup-review-order");

	die();
    
}



add_action("wp_ajax_update_user_data","dg_update_user_data");	
add_action("wp_ajax_nopriv_update_user_data","dg_update_user_data");
function dg_update_user_data () {
	
	global $woocommerce;

	$check_credit_card = false;
	$card_number = "";
	$expdate = "";
	$postal_code = "";
	$cvd = "";
	
	$state = 3;

	if (isset($_POST['data']) && !empty($_POST['data'])) {
		
		foreach ($_POST['data'] as $key=>$value) {
			dg_set_user_meta(sanitize_text_field($key),sanitize_text_field($value));

			if( strcmp( $key , "order_step" ) == 0 ) {
				$state = trim($value);
				error_log("-------State = $state" );
			}

			if( strcmp( $key, "monthly_bill_payment_option" ) == 0 ) {
				$monthly_bill_payment_option = strtolower( trim($value) );
				if( strcmp( $monthly_bill_payment_option, "cc" ) == 0 ) {
					$check_credit_card = true;
				}
			}
			if( strcmp( $key, "cc_monthly_billing_card_number" ) == 0 ) {
				$card_number = Cleanup_Number( base64_decode( $value ) );
				error_log( "Monthly Card number $card_number " );
			}
			if( strcmp( $key, "cc_monthly_billing_card_expiry" ) == 0 ) {
				$expdate = Cleanup_Number( $value );
				$expdate = substr($expdate, 2) . substr( $expdate, 0, 2);
				error_log( "Monthly Card expre $expdate " );
			}
			if( strcmp( $key, "cc_monthly_billing_postcode" ) == 0 ) {
				$postal_code= strtolower(trim ($value) );
			}
			if( strcmp( $key, "cc_monthly_billing_card_cvv" ) == 0 ) {
				$cvd = strtolower(trim ($value) );
			}
		}

		if( $check_credit_card == true ) {

			error_log("Sending verification information: $cust_id, $amount, $card_number, $expdate, $cvd, $postal_code");

			$payment_info['custid'] = "";
			$payment_info['orderid'] = 'verify-'.date("dmy-G:i:s");
			$payment_info['amount'] = 0.01; 
			$payment_info['cardno'] = $card_number;
			$payment_info['expdate'] = $expdate; //'2011';
			$payment_info['postal_code'] = $postal_code;
			$payment_info['cvd'] = $cvd;

			verify_card_ex( $payment_info );
		}

		$user_data = dg_get_current_user_data();
		//if( strcmp( $user_data.lead_status, "Need manual check" ) == 0 ) {
		//	SendInfoToSignupServer(0);
		//} else {
			SendInfoToSignupServer($state);
		//}

		$response["status"] = "success";
		$res = json_encode( $response );
		die( $res );
			
	} else {
	
		die("Error");
	
	}
	
	die();
}


add_action( 'init', 'all_set_cookies_tasks' );
function all_set_cookies_tasks() {

	if (isset($_GET['src']) && $_GET['src']=="pbw") {
		setcookie("plan_building_complete", true, strtotime( '+14 days' ) ,"/");	
	}

}

	
/* Register Custom Post */
/* ----------------------------------------------------- */
add_action( 'init', 'wdg_create_post_type' );
function wdg_create_post_type() {  // clothes custom post type
    // set up labels
    $labels = array(
        'name' => 'Careers',
        'singular_name' => 'Career Item',
        'add_new' => 'Add New',
        'add_new_item' => 'Add New Career Item',
        'edit_item' => 'Edit Career Item',
        'new_item' => 'New Career Item',
        'all_items' => 'All Career',
        'view_item' => 'View Career Items',
        'search_items' => 'Search Career',
        'not_found' =>  'No Careers Found',
        'not_found_in_trash' => 'No Careers found in Trash',
        'parent_item_colon' => '',
        'menu_name' => 'Careers',
    );
    register_post_type(
        'careers',
        array(
            'labels' => $labels,
            'has_archive' => true,
            'public' => true,
            'hierarchical' => true,
            'supports' => array( 'title', 'editor', 'excerpt', 'custom-fields', 'thumbnail' ),
            'exclude_from_search' => true,
            'capability_type' => 'post',
        )
    );
    // set up labels
    $labels_feedback = array(
        'name' => 'Testimonials',
        'singular_name' => 'Testimonial Item',
        'add_new' => 'Add New',
        'add_new_item' => 'Add New Testimonial Item',
        'edit_item' => 'Edit Testimonial Item',
        'new_item' => 'New Testimonial Item',
        'all_items' => 'All Testimonial',
        'view_item' => 'View Testimonial Items',
        'search_items' => 'Search Testimonial',
        'not_found' =>  'No Testimonial Found',
        'not_found_in_trash' => 'No Testimonial found in Trash',
        'parent_item_colon' => '',
        'menu_name' => 'Testimonial',
    );
    register_post_type(
        'testimonial',
        array(
            'labels' => $labels_feedback,
            'has_archive' => true,
            'public' => true,
            'hierarchical' => true,
            'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
            'exclude_from_search' => true,
            'capability_type' => 'post',
        )
    );
}
 
// register two taxonomies to go with the post type
add_action( 'init', 'wdg_create_taxonomies', 0 );
function wdg_create_taxonomies() {
    // color taxonomy
    $labels = array(
        'name'              => _x( 'Jobs', 'taxonomy general name' ),
        'singular_name'     => _x( 'Job', 'taxonomy singular name' ),
        'search_items'      => __( 'Search Jobs' ),
        'all_items'         => __( 'All Jobs' ),
        'parent_item'       => __( 'Parent Job' ),
        'parent_item_colon' => __( 'Parent Job:' ),
        'edit_item'         => __( 'Edit Job' ),
        'update_item'       => __( 'Update Job' ),
        'add_new_item'      => __( 'Add New Job' ),
        'new_item_name'     => __( 'New Job' ),
        'menu_name'         => __( 'Jobs' ),
    );
    register_taxonomy(
        'jobs',
        'careers',
        array(
            'hierarchical' => true,
            'labels' => $labels,
            'query_var' => true,
            'rewrite' => true,
            'show_admin_column' => true
        )
    );
}


// create shortcode with parameters so that the user can define what's queried - default is to list all blog posts
add_shortcode( 'careersposts', 'wdg_careers_shortcode' );
function wdg_careers_shortcode( $atts ) {
    ob_start();
    // define attributes and their defaults
    extract( shortcode_atts( array (
        'type' => 'careers',
        'order' => 'date',
        'orderby' => 'title',
        'posts' => -1,
        'jobs' => ''
    ), $atts ) );
    // define query parameters based on attributes
    $options = array(
        'post_type' => $type,
        'order' => $order,
        'orderby' => $orderby,
        'posts_per_page' => $posts,
        'jobs' => $jobs
    );
    $string = '';
    $query = new WP_Query( $options );
    if( $query->have_posts() ){
        $string .= '<div class="career_main_area">';
        while( $query->have_posts() ){
            $query->the_post();
            $ttt = get_the_term_list( $post->ID, 'jobs', '', ', ' );
            $string .= '<div class="career_box_inner">';
            $string .= '<h4 class="career_box_category">' .  $ttt . '</h4>';
            $string .= '<div class="career_box_detail">';
            $string .= '<h2 class="carrer_box_title">'. get_the_title() .'</h2>';
            $string .= ' '. get_the_excerpt() .' ';
            $string .= '<a class="green-shade" href="'. get_the_permalink() .'">VIEW DETAIL</a>';
            $string .= '</div>';
            $string .= '</div>';
        }
        $string .= '</div>';
    }
    wp_reset_postdata();
    return $string;
}

add_filter( 'upload_mimes', 'my_myme_types', 99, 1 );
function my_myme_types( $mime_types ) {
  $mime_types['otf'] = 'font/otf';    
  $mime_types['ttf'] = 'font/ttf';
  
  return $mime_types;
}


add_shortcode( 'multi_form', 'wdg_multistep_form' );
function wdg_multistep_form( $atts ) {    
 	
 	// Attributes
    extract( shortcode_atts( array (
        'value' => 'Bring Your Friend'
    ), $atts ) ); 
    
    $return = "";
    
    if ($value != ' ') { 
    
    	$return = '<button type="button" class="green-shade btn-fix" data-toggle="modal" data-target=".bs-example-modal-lg"><?php echo $value; ?></button>';
    
    };
 
    return $return;
}


/* All Functions for plan building wizard */

function plan_building_wizard_modal() {
   
   
   include_once("plan-building-wizard-modal.php");
   include_once("basic-dialog-modal.php");
   include_once("abandon-cart-popup-modal.php");
   
   //WC()->cart->empty_cart();
   //var_dump( WC()->cart);
   
   
   
}

function add_phone_to_cart() {
	
	if (is_page(2265) && isset($_GET['addphn']) && !empty($_GET['addphn'])) {
		
		$the_slug = sanitize_text_field($_GET['addphn']);
		$args = array(
		  'name'        => $the_slug,
		  'post_type'   => 'product',
		  'post_status' => 'publish',
		  'numberposts' => 1
		);
		$my_posts = get_posts($args);
		
		if ($my_posts) {
			$pro_id =  $my_posts[0]->ID;	
			dg_add_product_to_cart ($pro_id , "phone");
		}
		
		wp_safe_redirect(get_the_permalink(2265));
		exit;
		
	}
	
}

function update_menu_cart_icon() {
	
	
	$onboarding_stage = dg_get_user_meta ("onboarding_stage");
	
	//not signup and checkout page
	if (!is_page(2935) && !is_page(2867) && !is_page(2265) && sizeof( WC()->cart->get_cart() ) > 0 ) {
		
		if ($onboarding_stage=="signup_initiated") {
			
			$display = "inline-block";
			$modal   = "show";
			$title = "Signup and Checkout";
			$color = "green";
			$link = "/residential/signup/";
			$text = "You have items left in your cart.";
			$btn_text = "Complete the signup";
		
		} elseif ($onboarding_stage=="signup_complete" || $onboarding_stage=="checkout_initiated" ) {
			
			$display = "inline-block";
			$modal   = "show";
			$title = "Complete your pending order";
			$color = "orange";
			$link = "/residential/checkout/";
			$text = "You are just a few steps away from completing your order!";
			$btn_text = "Checkout";
		
		} else {
			
			$display = "none";
			$modal   = "hide";
			$title = "";
			$color = "";
			$link = "#";
			$text = "";
			$btn_text = "";
			
					
		}
		
	} else {
		
		$display = "none";
		$modal   = "hide";
		$title = "";
		$color = "";
		$link = "#";
		$text = "";
		$btn_text = "";
		
	} ?>
	
	
	<script>
		
		jQuery(document).ready(function(){
			
			setTimeout(function(){ show_checkout_popup() },5000);
			
			
			jQuery(".et-cart-info").css("display","<?=$display?>");
		    jQuery(".et-cart-info").css("marginTop","10px");
		    jQuery(".et-cart-info").css("marginBottom","10px");
			jQuery(".et-cart-info").css("color","<?=$color?>");
			jQuery(".et-cart-info span").html("<?=$title?>&raquo;");
			jQuery(".et-cart-info").attr("href","<?=$link?>");
		    	
		
		});	
		
		function show_checkout_popup() {
		
			
			if (dg_getCookie('close_checkout_popup')) {
				
				return;
				
			}
	
			jQuery("#abandon-cart-popup-modal .quiz-title h3").html('<?=$title?>');
		    jQuery("#abandon-cart-popup-modal .modal-body .textsg").html('<?=$text?>');
		    jQuery("#abandon-cart-popup-modal #nextBtn span").html('<?=$btn_text?>');
		    jQuery("#abandon-cart-popup-modal #nextBtn").unbind("click").click(function(e){
		    	
		    	e.preventDefault;
		    	window.location.href= '<?=home_url($link)?>';
		    	
		    
		    });
		    
		    jQuery("#abandon-cart-popup-modal .close").unbind("click").click(function(e){
		    	
		    	//dg_setCookie("close_checkout_popup",1,1);	
		    
		    });
		    
		    jQuery("#abandon-cart-popup-modal").modal("<?=$modal?>");
		    
		    /*jQuery("#abandon-cart-popup-modal").on("hide.bs.modal",function(e){
		
			    dg_setCookie("close_checkout_popup",1,1);	
				
			});
		    */
		    
	    
	    }
				
	</script>
	
	
	<?php
	
	
}

function dg_show_availability_checker() {
	
	if (isset($_GET['show_availability_checker'])) { ?>
		
		<script>
			jQuery(document).ready(function(){
				jQuery("#plan-building-wizard-modal").modal("show");
			});
		</script>
		
	<?php }
}


function save_utm_parameters() {
	
	if (!empty($_GET['utm_source'])) {
		dg_set_user_meta ("utm_source",sanitize_text_field($_GET['utm_source']));	
	}
	if (!empty($_GET['utm_medium'])) {
		dg_set_user_meta ("utm_medium",sanitize_text_field($_GET['utm_medium']));
	}
	if (!empty($_GET['utm_campaign'])) {
		dg_set_user_meta ("utm_campaign",sanitize_text_field($_GET['utm_campaign']));
	}
	if (!empty($_GET['utm_term'])) {
		dg_set_user_meta ("utm_term",sanitize_text_field($_GET['utm_term']));
	}
	if (!empty($_GET['utm_content'])) {
		dg_set_user_meta ("utm_content",sanitize_text_field($_GET['utm_content']));
	}
	
}

function toc_modals_html() {
	
	//if is page checkout
	if (is_page(2867)) { 
		
		get_template_part("templates/page","checkout-toc-modals");
		 
	}
}

function load_phone_rates() {
	
	//if phone page then load rates
	if (is_page(251)) { ?>
	
	<script>
		load_phone_rates();
	</script>	
		
	<?php }
	
}


add_action( 'wp_footer', 'plan_building_wizard_modal',1);
//add_action( 'wp_footer', 'reload_bellapi_response',11);
add_action( 'wp_footer', 'add_phone_to_cart',12);
add_action( 'wp_footer', 'update_menu_cart_icon',13);
add_action( 'wp_footer', 'dg_show_availability_checker',99);
add_action( 'wp_footer', 'save_utm_parameters',100);
add_action( 'wp_footer', 'toc_modals_html',100);
add_action( 'wp_footer', 'load_phone_rates',100);


add_action( 'wp_ajax_nopriv_get_ld_rates', 'get_ld_rates' );
add_action( 'wp_ajax_get_ld_rates', 'get_ld_rates' );
function get_ld_rates() {
	
	global $wpdb;
	
	$data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}dg_ld_rates ORDER BY country_name ASC", ARRAY_A);
	$country = [];
	
	if ($data && is_array($data)) {
		
		
		$country[0]['rate'] = " ";
		$country[0]['name'] = " ";
		$country[0]['code'] = " ";
		
		$c = 1;

		foreach ($data as $row) {
			
			$country[$c]['rate'] = $row['rates'];
			$country[$c]['name'] = $row['country_name'];
			$country[$c]['code'] = $row['country_code'];
			
			$c++;				
		}
		 
	}
	
	wp_die(json_encode($country));
	
}



add_action( 'wp_ajax_nopriv_show_pbw', 'ajax_show_pbw' );
add_action( 'wp_ajax_show_pbw', 'ajax_show_pbw' );
function ajax_show_pbw() {
     $start_time = microtime(true); // ADD THIS LINE AT THE START

    include_once("plan-building-wizard-steps.php");

     $end_time = microtime(true); // ADD THIS LINE AT THE END
    $execution_time = ($end_time - $start_time) * 1000; // Convert to milliseconds
    error_log("Modal content generation took: {$execution_time}ms");

    wp_die();
}




// Show Payment Options after Billing fields. 

/*   ----- Zach Edit - restore default woocommerce checkout payment functionality
 remove_action( 'woocommerce_checkout_order_review','woocommerce_checkout_payment',20);
  add_action( 'woocommerce_before_customer_details','woocommerce_checkout_payment',10);
  */




function get_dg_user_id() {
	
	global $dg_user_id;
	
	if(isset($_COOKIE['dg_user_hash'])) {
		
		$user_id = $_COOKIE['dg_user_hash'];
		
	} elseif ($dg_user_id) {
		
		$user_id = $dg_user_id;
	
	} else {
		
		$user_id = false;
	}
	
	return $user_id;
}

function set_dg_user_id ($id) {
	
	global $dg_user_id;
	$dg_user_id = $id;
	setcookie("dg_user_hash", $dg_user_id, strtotime( '+30 days' ) ,"/");
	
}

function dg_get_current_user_data () {
	
	global $wpdb;
	
	$user_data = [];
	
	if (is_user_logged_in()) {
		
		$user_id = get_current_user_id();
		$user_info = get_userdata($user_id);
		
		$user_data['first_name'] = $user_info->first_name;
		$user_data['last_name'] = $user_info->last_name;
		$user_data['email'] = $user_info->user_email;
		$user_data['date_created'] = date("j M,Y H:i:s",strtotime($user_info->user_registered));
		
		foreach (get_user_meta($user_id,"",true) as $key=>$value) {
			
			$user_data[$key] = maybe_unserialize($value[0]);	
			
		}
		
		
	} elseif (get_dg_user_id()) {
		
		$dg_user_hash = get_dg_user_id();
		
		$data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}dg_user_data WHERE user_id ='$dg_user_hash' ", ARRAY_A);
		
		if ($data && is_array($data)) {
			
			foreach ($data as $row) {
				
				$user_data[$row['meta_key']] = maybe_unserialize($row['meta_value']);
			}
			 
		} else {
			
			$user_data = false;
		}
			
	} else {
		
		$user_data = false;
		
	}
	
	return $user_data;
	
} 


function dg_set_current_user_data ($user_data) {
	
	
	if (is_array($user_data))
	foreach ($user_data as $key => $value) { 
		dg_set_user_meta($key,$value);	
	}
	
	
}



function dg_get_user_meta ($key) {
	
	global $wpdb;
	global $dg_user_id;
	
	if (is_user_logged_in()) {
		
		$user_id = get_current_user_id();
		$value = get_user_meta($user_id,$key,true); 		
		
	} elseif (get_dg_user_id()) {
		
		$dg_user_hash = get_dg_user_id();
		
		$data = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}dg_user_data WHERE user_id = '$dg_user_hash' AND meta_key='$key' ", ARRAY_A );
		
		if ($data!==null) {
			$value = maybe_unserialize($data['meta_value']);	
		} else {
			$value = false;
		}
			
	} else {
		
		$value = false;
		
	}
	
	return $value;
	
}

function dg_set_user_meta ($key,$value = "") {
	
	global $wpdb;

	/*
	if ($key=="order_step" && is_numeric($value)) {
			$value = update_order_step($value);
	}
	*/

	if (is_user_logged_in()) {
		
		$user_id = get_current_user_id();
		update_user_meta($user_id,$key,$value);	
		
	} else {
	
		if (get_dg_user_id()) {
			
			$dg_user_hash = get_dg_user_id();
			
		} else {
			
			$dg_user_hash = md5(strtotime("now").rand(999999,10000000));
			set_dg_user_id ( $dg_user_hash );
			dg_set_user_meta("date_created",date("j M,Y H:i:s"));

		}
		
		//update
		if (dg_get_user_meta($key)!==false) {
			
			$insert = $wpdb->update( 
				$wpdb->prefix."dg_user_data", 
				array( 
					'meta_value' => maybe_serialize($value),	// string
				), 
				array( 'user_id' => $dg_user_hash, 
					   'meta_key' => $key
					 ), 
				array( 
					'%s'
				), 
				array( '%s' , '%s') 
			);
		
		//insert	
		} else {

			//insert 
			$insert = $wpdb->insert( 
				$wpdb->prefix."dg_user_data", 
				array( 
					'user_id' => $dg_user_hash, 
					'meta_key' => $key,
					'meta_value' => maybe_serialize($value), 
				), 
				array( 
					'%s', 
					'%s',
					'%s' 
				) 
			);
			
			return $insert_id;
		}
	}
}

function GetTaxRate() {
	$user_data = dg_get_current_user_data();
	if( $user_data == false ) {
		return 13;
	}

	$prov = $user_data['prov'];
	error_log( "GetTax Rate for prov $prov ");

        $tax_rate = 13;
        if( strcasecmp( $prov, "BC") == 0 ) {
                $tax_rate = 12;
        } else if( strcasecmp( $prov, "QC") == 0 ) {
                $tax_rate = 14.975;
        }
	return $tax_rate;
}

/*
function get_monthly_fee_summary() {

	$summary = array(
		'internet-plan'=>array('',0.0),
		'modems'=>array('',0.0),
		'phone-plan'=>array('',0.0),
		'subtotal'=>array('Subtotal',0.0),
		'taxes'=>array('Taxes',0.0),
		'grand_total'=>array('MONTHLY TOTAL',0.0));

	$show_included_taxes = wc_tax_enabled() && WC()->cart->display_prices_including_tax();
	$tax_rate = GetTaxRate();

	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {

		$_product     = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
		$product_cat_ids = $_product->get_category_ids();


		$product_cat = get_term($product_cat_ids[0],'product_cat');

		$product_category = $product_cat->slug;

		if ( array_key_exists($product_category, $summary) && $_product && 
		 		$_product->exists() && $cart_item['quantity'] > 0 && 
		 		apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) ) {

			if( $product_category == "modems") {

				if( $_product->get_attribute("Security Deposit") > 0 ) {

					$summary[$product_category][0] = $_product->get_title()." ".( $show_included_taxes ?"(inc taxes)":"")."" ;
					$summary[$product_category][1] = floatval($_product->get_price());
					$summary['taxes'][1] +=  round( floatval(( $_product->get_price() * $tax_rate ) / 100), 2 );  // floatval($_product->get_price_including_tax($cart_item['quantity'])-$_product->get_price());

				} else {

				}

			} else {
			
				if ($product_category!="modems" || ($product_category=="modems" && ($_product->get_id()!=887 && $_product->get_id()!=6931))) {
					$summary[$product_category][0] = $_product->get_title() . " " . ( $show_included_taxes ?"(inc taxes)":"")."" ;
					$summary[$product_category][1] = round( floatval($_product->get_price()), 2);
					//$summary['taxes'][1] += floatval($_product->get_price_including_tax($cart_item['quantity'])-$_product->get_price());
					$summary['taxes'][1] +=  round( floatval(( $_product->get_price() * $tax_rate ) / 100), 2 );  // floatval($_product->get_price_including_tax($cart_item['quantity'])-$_product->get_price());
					//$_product->get_price()*$cart_item['quantity'];	 
			 	}

			
/*
			if( $_product->get_id()==6931 ){
				$summary[$product_category][0] = $_product->get_title()." ".( $show_included_taxes ?"(inc taxes)":"")."" ;
				//$summary[$product_category][1] =  floatval($line_data['subtotal']);
				$summary[$product_category][1] =  floatval(2);
				
				$summary['taxes'][1] += floatval($line_data['total_tax']);
			}

			if( $_product->get_id()==887 ){
				$summary[$product_category][0] = $_product->get_title()." ".( $show_included_taxes ?"(inc taxes)":"")."" ;
				//$summary[$product_category][1] =  floatval($line_data['subtotal']);
				$summary[$product_category][1] =  floatval(0);
				
				$summary['taxes'][1] += floatval($line_data['total_tax']);
			}
*/
/*
			}
		}
	} 



	$summary['subtotal'][1] = $summary['internet-plan'][1] + $summary['modems'][1] + $summary['phone-plan'][1];

	if (wc_tax_enabled() && !$show_included_taxes ) {
		 $summary['taxes'][0] = esc_html( WC()->countries->tax_or_vat() );
		 $summary['grand_total'][1] = $summary['subtotal'][1] + $summary['taxes'][1];
	} elseif (!wc_tax_enabled()) {
		$summary['taxes'][0] = "Tax";
		$summary['taxes'][1] = 0.0;
		$summary['grand_total'][1] = $summary['subtotal'][1];
	}

	return $summary;	
}
*/


function get_monthly_order_summary($order) {
	
	$summary = array(
		'internet-plan'=>array('',0.0),
		'modems'=>array('',0.0),
		'phone-plan'=>array('',0.0),
		'subtotal'=>array('Subtotal',0.0),
		'taxes'=>array('Taxes',0.0),
		'grand_total'=>array('MONTHLY TOTAL',0.0));
	
	$show_included_taxes = false;
	$tax_rate = GetTaxRate();

	foreach ($order->get_items() as $cart_item_key => $cart_item ) {
		 
		 $_product     = $cart_item->get_product();
		 $line_data    = $cart_item->get_data();
		
		 $product_cat_ids = $_product->get_category_ids();
		 $product_cat = get_term($product_cat_ids[0],'product_cat');
		 $product_category = $product_cat->slug;
		 
		 if (array_key_exists($product_category,$summary) && $_product && $_product->exists() && $line_data['quantity'] > 0) {
			 
			 if ($product_category!="modems" || ($product_category=="modems" && ($_product->get_id()!=887 && $_product->get_id()!=6931)) ) {
				$summary[$product_category][0] = $_product->get_title()." ".( $show_included_taxes ?"(inc taxes)":"")."" ;
				$summary[$product_category][1] =  round( floatval($line_data['subtotal']), 2 );
				
				$summary['taxes'][1] += round( floatval( ($line_data['subtotal'] * $tax_rate) / 100 ), 2);  //floatval($line_data['total_tax']);
			 }
			 
			if( $_product->get_id()==6931 ){
				$summary[$product_category][0] = $_product->get_title()." ".( $show_included_taxes ?"(inc taxes)":"")."" ;
				//$summary[$product_category][1] =  floatval($line_data['subtotal']);
				$summary[$product_category][1] =  round( floatval(2), 2 );
				
				//$summary['taxes'][1] += floatval($line_data['total_tax']);
				$summary['taxes'][1] += round( floatval( ($summary[$product_category][1] * $tax_rate) / 100 ), 2);  //floatval($line_data['total_tax']);
			}

			if( $_product->get_id()==887 ){
				$summary[$product_category][0] = $_product->get_title()." ".( $show_included_taxes ?"(inc taxes)":"")."" ;
				//$summary[$product_category][1] =  floatval($line_data['subtotal']);
				$summary[$product_category][1] =  round ( floatval(0), 2 );
				
				//$summary['taxes'][1] += floatval($line_data['total_tax']);
				$summary['taxes'][1] += round( floatval( ($summary[$product_category][1] * $tax_rate) / 100 ), 2);  //floatval($line_data['total_tax']);
			}
			 
		 }
	}
	
	$summary['subtotal'][1] = $summary['internet-plan'][1] + $summary['modems'][1] + $summary['phone-plan'][1];
	if (wc_tax_enabled() && !$show_included_taxes ) {
		
		 $summary['taxes'][0] = esc_html( WC()->countries->tax_or_vat() );
		 $summary['grand_total'][1] = $summary['subtotal'][1] + $summary['taxes'][1];	
	
	} elseif (!wc_tax_enabled()) {
		
		$summary['taxes'][0] = "Tax";
		$summary['taxes'][1] = 0.0;
		$summary['grand_total'][1] = $summary['subtotal'][1];
		
	}
	
	return $summary;	
}


add_action( 'wp_ajax_nopriv_get_upfront_fee_json', 'get_upfront_fee_json' );
add_action( 'wp_ajax_get_upfront_fee_json', 'get_upfront_fee_json' );
function get_upfront_fee_json() {
	
	$summary = array ();

	$summary['upfront'] = get_upfront_fee_summary();
	$summary['monthly'] = get_monthly_fee_summary();

	$json_response = json_encode($summary);

	wp_die($json_response);
	
}


function get_upfront_fee_summary() {
	
	$summary = array(
		'ModemPurchaseOption'=>true,
		'internet-plan'=>array('',0.0),
		'modems'=>array('Modem Security Deposit',0.0),
		'fixed-fee'=>array('Installation Fee',0.0),
		'deposit'=>array('Pay-after Deposit',0.0),
		'subtotal'=>array('Subtotal',0.0),
		'taxes'=>array('Taxes',0.0),
		'grand_total'=>array('UPFRONT TOTAL',0.0));

	$tax_rate = GetTaxRate();
	$do_not_include_modem_deposit = false;
	$show_included_taxes = wc_tax_enabled() && WC()->cart->display_prices_including_tax();
	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
		 
		$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
		$product_cat_ids = $_product->get_category_ids();
		$product_cat = get_term($product_cat_ids[0],'product_cat');
		$product_category = $product_cat->slug;
		 
		if (array_key_exists($product_category,$summary) && $_product && $_product->exists() && 
			$cart_item['quantity'] > 0 && apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) ) {

			error_log( "...." . $product_category );
			//TODO: in an effort to remove the 1st month internet payment from the inital payment
			if( $product_category == "internet-plan" ) {
				continue;
			}

			if( $product_category == "deposit" ) {
				$summary[$product_category][0] = $_product->get_title();
				$summary[$product_category][1] =  round(floatval($_product->get_price()), 2);
				// no taxes here for category deposit
			} else {
			if( $product_category == "modems" && $_product->get_attribute("Security Deposit") > 0  ) {
				
					$security_deposit = $_product->get_attribute("Security Deposit");
					$summary[$product_category][0] = $_product->get_title()." ".( $show_included_taxes ?"(inc taxes)":"")."" ;
					$summary[$product_category][1] =  round(floatval($security_deposit), 2);
					// no tax on security deposit
					$summary['ModemPurchaseOption'] = false;
					$do_not_include_modem_deposit = true;

			} else {
				
				$summary[$product_category][0] = $_product->get_title()." ".( $show_included_taxes ?"(inc taxes)":"")."" ;
				$summary[$product_category][1] =  round(floatval($_product->get_price()), 2);
				$summary['taxes'][1] += round( floatval ( ($summary[$product_category][1] * $tax_rate ) / 100 ) , 2 );	
				
			}
			}
		}
	}

	if( $do_not_include_modem_deposit ) {
			$summary['subtotal'][1] = $summary['internet-plan'][1] + $summary['fixed-fee'][1];
			$summary['grand_total'][1] = $summary['subtotal'][1]+ $summary['deposit'][1] + $summary['taxes'][1] + $summary['modems'][1];	

			if (wc_tax_enabled() && !$show_included_taxes ) {
				$summary['taxes'][0] = esc_html( WC()->countries->tax_or_vat() );
			} elseif (!wc_tax_enabled()) {
				$summary['taxes'][0] = "Tax";
				$summary['grand_total'][1] = $summary['subtotal'][1] + $summary['deposit'][1] + $summary['modems'][1];
			}
	} else {
			$summary['subtotal'][1] = $summary['internet-plan'][1] + $summary['fixed-fee'][1] + $summary['modems'][1];
			$summary['grand_total'][1] = $summary['subtotal'][1]+ $summary['deposit'][1] + $summary['taxes'][1];
			if (wc_tax_enabled() && !$show_included_taxes ) {
				$summary['taxes'][0] = esc_html( WC()->countries->tax_or_vat() );
			} elseif (!wc_tax_enabled()) {
				$summary['taxes'][0] = "Tax";
				$summary['grand_total'][1] = $summary['subtotal'][1] + $summary['deposit'][1];
			}
	}

	return $summary;	
}

// this function is called from woocommerce-order system, require $order param
function get_upfront_order_summary($order) {
		
	$summary = array(
		'internet-plan'=>array('',0.0),
		'modems'=>array('',0.0),
		'fixed-fee'=>array('Installation Fee',0.0),
		'deposit'=>array('Pay-after Deposit',0.0),
		'subtotal'=>array('Subtotal',0.0),
		'taxes'=>array('Taxes',0.0),
		'grand_total'=>array('UPFRONT TOTAL',0.0));

	$show_included_taxes = false;
	$do_not_include_modem_deposit = false;

	foreach ( $order->get_items() as $cart_item_key => $cart_item ) {
		 
		$_product     = $cart_item->get_product();
		$line_data    = $cart_item->get_data();
		
		$product_cat_ids = $_product->get_category_ids();
		$product_cat = get_term($product_cat_ids[0],'product_cat');
		$product_category = $product_cat->slug;
		 
		if ( array_key_exists($product_category,$summary) && $_product && $_product->exists() && $line_data['quantity'] > 0 ) {

			//TODO: in an effort to remove the 1st month internet payment from the inital payment
			if( $product_category == "internet-plan" ) {
				continue;
			}

			if( $product_category == "deposit" ) {
				$summary[$product_category][0] = $_product->get_title();
				$summary[$product_category][1] =  round(floatval($_product->get_price()), 2);
				// no taxes here for category deposit
			} else {
			if ( $product_category=="modems" && $_product->get_attribute("Security Deposit") > 0  ) {

				$security_deposit = $_product->get_attribute("Security Deposit");
				$summary[$product_category][0] = $_product->get_title()." ".( $show_included_taxes ?"(inc taxes)":"")."" ;
				$summary[$product_category][1] =  round( floatval($security_deposit), 2);
				$do_not_include_modem_deposit = true;

			} else {

			 	$summary[$product_category][0] = $_product->get_title()." ".( $show_included_taxes ?"(inc taxes)":"")."" ;
				$summary[$product_category][1] =  round( floatval($line_data['subtotal']), 2 );
				$summary['taxes'][1] += floatval($line_data['total_tax']);
			
			}
			}

		}
		 
	}

	if( $do_not_include_modem_deposit ) {

		$summary['subtotal'][1] = $summary['internet-plan'][1] + $summary['fixed-fee'][1] ;	
		if (wc_tax_enabled() && !$show_included_taxes ) {
			$summary['taxes'][0] = esc_html( WC()->countries->tax_or_vat() );
			$summary['grand_total'][1] = $summary['subtotal'][1]+ $summary['deposit'][1]  + $summary['taxes'][1]+$summary['modems'][1];	
		} elseif (!wc_tax_enabled()) {
			$summary['taxes'][0] = "Tax";
			$summary['taxes'][1] = 0.0;
			$summary['grand_total'][1] = $summary['subtotal'][1]+ $summary['deposit'][1]  +$summary['modems'][1];	
		}

	} else {

		$summary['subtotal'][1] = $summary['internet-plan'][1] + $summary['fixed-fee'][1] + $summary['modems'][1];	
		if (wc_tax_enabled() && !$show_included_taxes ) {
			$summary['taxes'][0] = esc_html( WC()->countries->tax_or_vat() );
			$summary['grand_total'][1] = $summary['subtotal'][1]+ $summary['deposit'][1]  + $summary['taxes'][1];	
		} elseif (!wc_tax_enabled()) {
			$summary['taxes'][0] = "Tax";
			$summary['taxes'][1] = 0.0;
			$summary['grand_total'][1] = $summary['subtotal'][1]+ $summary['deposit'][1];	
		}
	}

	
	return $summary;	
}

/* ----- Zach Edit ---- Commented out to restore default Woocommerce Price Totals based on default price fields &
show default payment gateways on Checkout

add_filter( 'woocommerce_calculated_total', 'change_calculated_total', 10, 2 );
function change_calculated_total( $total, $cart ) {
    
	$summary = get_upfront_fee_summary();
		
	return $summary['grand_total'][1];
	
	//return 1;
}

add_action( 'woocommerce_calculate_totals', 'add_custom_price', 10, 1);
function add_custom_price( $cart_object ) {

    if ( is_admin() && ! defined( 'DOING_AJAX' ) )
        return;

    if ( did_action( 'woocommerce_calculate_totals' ) >= 2 )
        return;
        
    $summary = get_upfront_fee_summary();    

    $cart_object->subtotal = $summary['subtotal'][1];
    $cart_object->tax_total = $summary['tax'][1];
     
}

---- */



function GetModemsInfo(&$plan_name, &$fees, &$class_name, &$upfront_info ) {
	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			
		$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
		if( $_product == false || $_product->exists() == false || $cart_item['quantity'] <= 0 ||
			apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) == false ) {
			
				continue;
		}

		$product_cat_ids = $_product->get_category_ids();
		$product_cat = get_term($product_cat_ids[0],'product_cat');
		$product_category = $product_cat->slug;

		if ($product_category != 'modems') {
			continue;
		}
		
		$class_name = esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) );
		$plan_name = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) . '&nbsp;' . wc_get_formatted_cart_item_data( $cart_item );
		
		$ProdPrice = $_product->get_price();
		$fees = $ProdPrice;
	
		if( $_product->get_attribute( "Security Deposit" ) > 0 ) {
			$sec_depoist = $_product->get_attribute( "Security Deposit" );
			$upfront_info = "<p class=\"dg_ord_data display_modem150\"> " . 
				"<span class=\"plan_darta\">One-time upfront modem deposit</span> " . 
				"<span class=\"plan_pricing\"> $" . $sec_depoist . "</span>" . 
				"</p>";
		} else {
			$upfront_info = "";
		}

		return true;
	}

	return false;
}

function GetPhoneInfo(&$plan_name, &$fees, &$class_name) {
	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			
		$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
		if( $_product == false || $_product->exists() == false || $cart_item['quantity'] <= 0 ||
			apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) == false ) {
				continue;
		}

		$product_cat_ids = $_product->get_category_ids();
		$product_cat = get_term($product_cat_ids[0],'product_cat');
		$product_category = $product_cat->slug;

		if ($product_category != 'phone-plan') {
			continue;
		}
		
		$class_name = esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) );
		$plan_name = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) . '&nbsp;' . wc_get_formatted_cart_item_data( $cart_item );
		
		$ProdQuant = $cart_item['quantity'];
		$ProdPrice = $_product->get_price();
		$freq = $_product->get_attribute("Payment Frequency");
		if( strlen($freq) > 0) {
			$fees = $ProdPrice . " " . $freq ;
		} else {
			$fees = $ProdPrice;
		}

		return true;
	}

	return false;
}


function GetInternetPlanInfo( &$plan_name, &$fees, &$class_name  ) {


	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			
		$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
		if( $_product == false || $_product->exists() == false || $cart_item['quantity'] <= 0 ||
			apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) == false ) {
			
				continue;
		}

		$product_cat_ids = $_product->get_category_ids();
		$product_cat = get_term($product_cat_ids[0],'product_cat');
		$product_category = $product_cat->slug;

		if ($product_category != 'internet-plan') {
				 
			continue;
				 
		} 
		
		$class_name = esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) );
		$plan_name = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) . '&nbsp;' . wc_get_formatted_cart_item_data( $cart_item );
		
		$ProdQuant = $cart_item['quantity'];

		if( $_product->get_sale_price() > 0 ) {
			$ProdPrice = $_product->get_price();
			$fees_after = round($ProdPrice * $ProdQuant, 2);
			$fees_before = round($_product->get_regular_price() * $ProdQuant, 2);
			$fees = "<s>" . $fees_before . "</s> " . $fees_after;
		} else {
			$ProdPrice = $_product->get_price();
			$fees = round($ProdPrice * $ProdQuant, 2);
		}


		$freq = $_product->get_attribute("Payment Frequency");
		if( strlen($freq) > 0) {
			$fees .= $freq ;
		}

		return true;
	}

	return false;
}

function GetInstallationFees(&$fees, &$class_name, &$name) {

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			
			$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
			if( $_product == false || $_product->exists() == false || $cart_item['quantity'] <= 0 ||
				apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) == false ) {
				
					continue;
			}

			$product_cat_ids = $_product->get_category_ids();
			$product_cat = get_term($product_cat_ids[0],'product_cat');
			$product_category = $product_cat->slug;

			if ($product_category != 'fixed-fee') {
					 
				continue;
					 
			}
			
			$class_name = esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) );
			$name = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) . '&nbsp;' . wc_get_formatted_cart_item_data( $cart_item );

			$purchase_note = get_post_meta( $_product->get_id(), '_purchase_note', true );
			if( strlen( $purchase_note ) > 0 ) {
				$name = $name . " " . $purchase_note;
			}
			

			$ProdQuant = $cart_item['quantity'];
			if( $_product->get_sale_price() > 0 ) {
				$ProdPrice = $_product->get_price();
				$fees_after = round($ProdPrice * $ProdQuant, 2);
				$fees_before = round($_product->get_regular_price() * $ProdQuant, 2);
				$fees = "<s>" . $fees_before . "</s> " . $fees_after;
			} else {
				$ProdPrice = $_product->get_price();
				$fees = round($ProdPrice * $ProdQuant, 2);
			}	
			return true;
		}

	return false;
}



add_filter( 'default_checkout_billing_state', 'xa_set_default_checkout_state' );
function xa_set_default_checkout_state() {
  // Returns empty state by default.
  //   return null;
  // Returns California as default state.
     return 'ON';
}

add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields',99 );

// Our hooked in function - $fields is passed via the filter!

function custom_override_checkout_fields( $fields ) {
     
   /*  $ubpo = dg_get_user_meta ("upfront_bill_payment_option");
     
     //billing fields not required for email trasfer payment option.
     if ($ubpo=="email-transfer") {
	     
	     foreach($fields['billing'] as $key=>$val) {
		     
		     $fields['billing'][$key]['required'] = true;
	     }
     }
     
     $fields['billing']['billing_email']['default'] = dg_get_user_meta ('email');
     $fields['billing']['billing_first_name']['default'] = dg_get_user_meta ('first_name');
     $fields['billing']['billing_last_name']['default'] = dg_get_user_meta ('last_name');
     
     //var_dump("<h2>i am in</h2>");
     
     if (!empty($ubpo['searched_address']['provinceOrState'])) {
	 	 $fields['shipping']['shipping_state']['default'] = $searched_address['provinceOrState'];    
     }
     
     $fields['billing']['billing_first_name']['label'] = "Billing first name";
	 $fields['billing']['billing_first_name']['required'] = true;
     $fields['billing']['billing_last_name']['label'] = "Billing last name";
	 $fields['billing']['billing_last_name']['required'] = true;  
     $fields['billing']['billing_email']['label'] = "Billing email address";
	 $fields['billing']['billing_email']['required'] = true;  
     $fields['billing']['billing_city']['label'] = "City";
     $fields['billing']['billing_postcode']['label'] = "Postal Code";
     
     
     $fields['billing']['billing_city']['class'][0] = "form-row-first";
     $fields['billing']['billing_state']['class'][0] = "form-row-last";
     
     
     $fields['billing']['billing_postcode']['class'][0] = "form-row-first";
     $fields['billing']['billing_postcode']['class'][1] = "clear";
     $fields['billing']['billing_postcode']['class'][2] = "forceuppercase";
     $fields['billing']['billing_phone']['class'][0] = "form-row-last";
     $fields['billing']['billing_phone']['required'] = true; 
	*/
	
     unset($fields['billing']['billing_company']);
     unset($fields['billing']['billing_address_2']);
     // unset($fields['order']['order_comments']);
     
    /* 
     $fields['billing']['service_address'] = array(
	        'label'     => __('Service Address', 'woocommerce'),
		    'placeholder'   => _x('Service Address', 'placeholder', 'woocommerce'),
		    'required'  => true,
		    'class'     => array('checkout_hidden_fields'),
		    'clear'     => true,
		    'default' 	=> dg_get_user_meta ('searched_street_address')
		);
	
	$fields['billing']['date1'] = array(
	        'label'     => __('1st Preferred Installation Date and Time', 'woocommerce'),
		    'placeholder'   => _x('1st Preferred Installation Date and Time', 'placeholder', 'woocommerce'),
		    'required'  => true,
		    'class'     => array('checkout_hidden_fields'),
		    'clear'     => true,
		    'default' 	=> dg_get_user_meta ('preffered_installation_date_1')." ".dg_get_user_meta ('preffered_installation_time_1')
		);
	
	$fields['billing']['date2'] = array(
	        'label'     => __('2nd Preferred Installation Date and Time', 'woocommerce'),
		    'placeholder'   => _x('2nd Preferred Installation Date and Time', 'placeholder', 'woocommerce'),
		    'required'  => true,
		    'class'     => array('checkout_hidden_fields'),
		    'clear'     => true,
		    'default' 	=> dg_get_user_meta ('preffered_installation_date_2')." ".dg_get_user_meta ('preffered_installation_time_2')
		);
	
	$fields['billing']['date3'] = array(
	        'label'     => __('3rd Preferred Installation Date and Time', 'woocommerce'),
		    'placeholder'   => _x('2rd Preferred Installation Date and Time', 'placeholder', 'woocommerce'),
		    'required'  => true,
		    'class'     => array('checkout_hidden_fields'),
		    'clear'     => true,
		    'default' 	=> dg_get_user_meta ('preffered_installation_date_3')." ".dg_get_user_meta ('preffered_installation_time_3')
		);
	
	$fields['billing']['customer_first_name'] = array(
	        'label'     => __('Customer First Name', 'woocommerce'),
		    'placeholder'   => _x('Customer First Name', 'placeholder', 'woocommerce'),
		    'required'  => true,
		    'class'     => array('checkout_hidden_fields'),
		    'clear'     => true,
		    'default' 	=> dg_get_user_meta ('first_name')
		);
	
	$fields['billing']['customer_last_name'] = array(
	        'label'     => __('Customer Last Name', 'woocommerce'),
		    'placeholder'   => _x('Customer last Name', 'placeholder', 'woocommerce'),
		    'required'  => true,
		    'class'     => array('checkout_hidden_fields'),
		    'clear'     => true,
		    'default' 	=> dg_get_user_meta ('last_name')
		);
	
	$fields['billing']['customer_email'] = array(
	        'label'     => __('Customer email address', 'woocommerce'),
		    'placeholder'   => _x('Customer email address', 'placeholder', 'woocommerce'),
		    'required'  => true,
		    'class'     => array('checkout_hidden_fields'),
		    'clear'     => true,
		    'default' 	=> dg_get_user_meta ('email')
		);
	
	$fields['billing']['customer_phone'] = array(
	        'label'     => __('Customer phone', 'woocommerce'),
		    'placeholder'   => _x('Customer phone', 'placeholder', 'woocommerce'),
		    'required'  => true,
		    'class'     => array('checkout_hidden_fields'),
		    'clear'     => true,
		    'default' 	=> dg_get_user_meta ('phone')
		);
		*/

     /*--
	 	$fields['billing']['how_did_you_hear_about_us'] = array(
	        'label'     => __('How did you hear about us', 'woocommerce'),
		    'placeholder'   => _x('How did you hear about us', 'placeholder', 'woocommerce'),
		    'required'  => true,
		    'class'     => array('checkout_hidden_fields'),
		    'clear'     => true,
		    'default' 	=> dg_get_user_meta ('how_did_you_hear_about_us')
		);
      */ 
	/*$fields['billing']['referrer_name'] = array(
	        'label'     => __('Referrer name', 'woocommerce'),
		    'placeholder'   => _x('Referrer name', 'placeholder', 'woocommerce'),
		    'required'  => false,
		    'class'     => array('checkout_hidden_fields'),
		    'clear'     => true,
		    'default' 	=> dg_get_user_meta ('referrer_name')
		);
		*/
		
		
     
     
    return $fields;
}




add_action( 'woocommerce_checkout_update_order_meta', 'saving_checkout_cf_data');
function saving_checkout_cf_data( $order_id ) {
	
	//add checkout data to order meta
    if(isset($_POST['checkout']) && is_array($_POST['checkout'])) {
	    
		SendInfoToSignupServer(50);

	    foreach ($_POST['checkout'] as $key => $value) {


		    if ($key=="monthly_cc" || $key=="monthly_bank") {
			    
			    
			      $token = maybe_serialize($value);
			      
			      if (is_user_logged_in()) {
				      $encryption_key = md5(get_dg_user_id());
			      } else {
				      $encryption_key = get_dg_user_id();
			      }
				
				  $cryptor = new Cryptor($encryption_key);
				  $value = $cryptor->encrypt($token);
				  unset($token);
				  update_post_meta( $order_id, "dg_user_hash", $encryption_key );
			    
		    }
		    
		    
		    if (!is_array($value)) {
			    update_post_meta( $order_id, $key, sanitize_text_field( $value ) );
		    } else {
			    update_post_meta( $order_id, $key, maybe_serialize($value) );
		    }
		    
	    }
    }
    
    //add billing fields to user meta
    if (is_user_logged_in()) {
		
			    
	    
    }
     
}

add_action( 'woocommerce_order_status_on-hold', 'dg_on_order_processing');
add_action( 'woocommerce_order_status_processing', 'dg_on_order_processing');
function dg_on_order_processing($order_id) {
	
	$order = wc_get_order( $order_id );
	$user_email  = dg_get_user_meta ("email");
	$data['source'] = "Website purchase";
	$data['lead_status'] = "Customer signed up";
	
	//hubspot_api_update_contact_by_email($data,$user_email);
	
	dg_set_user_meta ("onboarding_stage","checkout_complete");
	
	
}


/*
function update_order_step($step_number = 0) {

	$values[0] = "0 - Availability";
	$values[1] = "1 - Selected Internet Plan";
	$values[2] = "2 - Customer Details";
	$values[3] = "3 - Service Address";
	$values[4] = "4 - Installation";
	$values[5] = "5 - Phone Plan";
	$values[6] = "6 - Modem Options";
	$values[7] = "7 - Review Order";
	$values[8] = "8 - Checkout";


	return $values[$step_number];

}
 */

function wporg_add_payment_box()
{
    $screens = ['shop_order'];
    foreach ($screens as $screen) {
        add_meta_box(
            'wporg_box_payment',           // Unique ID
            'Payment Details',  // Box title
            'wporg_payment_box_html',  // Content callback, must be of type callable
            $screen                   // Post type
        );
    }
}

add_action('add_meta_boxes', 'wporg_add_payment_box');
function wporg_payment_box_html($post)
{
	$order_meta = get_post_meta($post->ID,"",true);
	 if ( ($order_meta['monthly_bill_payment_option'][0]=="bank" || $order_meta['monthly_bill_payment_option'][0]=="cc")  ):
	 	$k=$order_meta['dg_user_hash'][0];
		$cryptor = new Cryptor($k);
		if($order_meta['monthly_bill_payment_option'][0]=="bank"){
			$field = $order_meta['monthly_bank'][0];
			$heading= "Bank Billing Details";
		}else {
			$field = $order_meta['monthly_cc'][0];
			$heading= "Credit Card Billing Details";
		}
		
		$value = $cryptor->decrypt($field);
		$data=maybe_unserialize($value);
	

    ?>
     	<div class="panel woocommerce-order-data" id="order_data">
			<h2 class="woocommerce-order-data__heading"><?php echo $heading;?></h2>
				<div class="inside">
					<div id="postcustomstuff">
				<?php //echo "<pre>";print_r($data);echo "</pre>";?>
				<?php if(!empty($data)){?>
					<table id="newmeta">
						<thead>
							<tr>
								<th class="left" >Name</th>
								<th>Value</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($data as $key => $value) { ?>
								
							
								<tr>
									<td class="left" id="newmetaleft">
									<label><?php echo $key;?></label>
									</td>
									<td>
									<label><?php echo $value;?></label>
									</td>

								</tr>	
						
							<?php
							}
							?>
							</tbody>
					</table>	
							
				<?php  } ?>
				</div>
			</div>
		
		</div>
    <?php
	endif;    
}

function Cleanup_Number($val) {
	return preg_replace('/[^0-9]/', '', $val);
}