<?php 

// Completely disable magnific_popup.css
add_action('init', 'completely_disable_magnific_popup', 1);
function completely_disable_magnific_popup() {
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    
    // Block ALL magnific popup CSS requests site-wide
    if (strpos($request_uri, 'magnific_popup.css') !== false) {
        header('Content-Type: text/css');
        header('Cache-Control: max-age=86400'); // Cache for 24 hours
        echo '/* Magnific popup disabled site-wide */';
        exit;
    }
}


include_once("includes/crypt.php");
include_once("includes/hubspot.php");
include_once("includes/availability_check.php");
include_once("mpgClasses.php");
include_once("includes/ProcessPayment.php");
require_once(get_stylesheet_directory() . '/templates/customer-email.php');



/* add_action( 'wp_enqueue_scripts', 'add_step8_script' );
function add_step8_script() {
	wp_enqueue_script( 'step8-script', get_stylesheet_directory_uri() . '/js/step8-script.js', array( 'jquery' ), '1.0', true );
}
*/ 


/*Fix Encoding Issue*/

// Fix for apostrophes and special characters in Divi modules
add_filter('do_shortcode_tag', 'fix_divi_special_characters', 10, 4);
function fix_divi_special_characters($output, $tag, $attr, $m) {
    // Check for various Divi modules that might have text with apostrophes
    if ('et_pb_text' === $tag || 'et_pb_button' === $tag || 'et_pb_toggle' === $tag 
        || 'et_pb_accordion_item' === $tag || 'et_pb_blurb' === $tag) {
        // Decode HTML entities to proper characters
        $output = html_entity_decode($output, ENT_QUOTES, 'UTF-8');
    }
    return $output;
}


/*==============
================
================ NEW FUNCTIONS CREATED FOR REVAMPED VERSION OF SITE JANUARY 2026 ================
===============
================ */


/*
 * Get the primary product category, ignoring provider categories
 * Provider categories (bell, bell-fttp, cogeco, rogers, shaw, telus) are used for filtering
 * but should not be used as the primary category for processing
 */
function dg_get_primary_product_category($product_cat_ids) {
    $provider_slugs = array('bell', 'bell-fttp', 'cogeco', 'rogers', 'shaw', 'telus');

    // If product has modems-new, skip the old 'modems' category
    $has_modems_new = false;
    foreach ($product_cat_ids as $cat_id) {
        $term = get_term($cat_id, 'product_cat');
        if (!is_wp_error($term) && $term->slug === 'modems-new') {
            $has_modems_new = true;
            break;
        }
    }
    if ($has_modems_new) {
        $provider_slugs[] = 'modems';
    }

    // Loop through all categories and find the first non-provider category
    foreach ($product_cat_ids as $cat_id) {
        $product_cat = get_term($cat_id, 'product_cat');
        if (is_wp_error($product_cat)) {
            continue;
        }
        if (!in_array($product_cat->slug, $provider_slugs)) {
            return $product_cat->slug;
        }
    }

    // If all categories were provider categories, fall back to first one
    if (!empty($product_cat_ids)) {
        $product_cat = get_term($product_cat_ids[0], 'product_cat');
        if (!is_wp_error($product_cat)) {
            return $product_cat->slug;
        }
    }

    return null;
}


/**
 * Enqueue exit intent scripts and SweetAlert2 library
 */
function dg_enqueue_exit_intent_scripts() {
    // Only load on product pages (where checkout-screen exists)
    if (is_product()) {
        // Enqueue SweetAlert2 from CDN
        wp_enqueue_script(
            'sweetalert2',
            'https://cdn.jsdelivr.net/npm/sweetalert2@11',
            array(),
            '11.0.0',
            true
        );
        
        // Enqueue custom exit intent script
        wp_enqueue_script(
            'dg-exit-intent',
            get_stylesheet_directory_uri() . '/js/exit-intent.js',
            array('jquery'),  // Remove 'sweetalert2' from dependencies
            '1.0.2',  // Increment version to force refresh
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'dg_enqueue_exit_intent_scripts');

/**
 * Filter modems based on internet plan provider category
 * Hides modems that don't match the provider of the internet plan in monthly summary
 */

function dg_filter_modems_by_provider() {
    global $post;
    
    // Only run if we have a valid post object
    if (!$post) {
        return;
    }
    
    // Only run on product post types
    if ($post->post_type !== 'product') {
        return;
    }
    
    $current_product_id = $post->ID;
    
    // Get the internet plan category
    $internet_plan_category = get_term_by('slug', 'internet-plan', 'product_cat');
    $internet_plan_category_id = $internet_plan_category ? $internet_plan_category->term_id : 19;
    
    // Get all categories for the current product
    $product_cats = wp_get_post_terms($current_product_id, 'product_cat', array('fields' => 'ids'));
    
    // Check if this is an internet plan
    $is_internet_plan = in_array($internet_plan_category_id, $product_cats);
    
    if (!$is_internet_plan) {
        return;
    }
    
    // Define provider category slugs
    $provider_slugs = array('bell', 'bell-fttp', 'cogeco', 'rogers', 'shaw', 'telus');
    
    // Get all category objects for the current product
    $product_categories = wp_get_post_terms($current_product_id, 'product_cat', array('fields' => 'all'));
    
    // Find the provider category for this internet plan
    $internet_plan_provider = null;
    foreach ($product_categories as $category) {
        if (in_array($category->slug, $provider_slugs)) {
            $internet_plan_provider = $category->slug;
            break;
        }
    }
    
    // If no provider found, show all modems (fallback)
    if (!$internet_plan_provider) {
        return;
    }
    
    // Map modem CSS classes to product IDs
    $modem_map = array(
        'modem-0' => 267980,
        'modem-1' => 267981,
        'modem-2' => 267983,
        'modem-3' => 267984,
        'modem-4' => 267979,
        'modem-5' => 268259,
        'modem-6' => 268266,
        'modem-7' => 268258,
        'modem-8' => 268265,
        'modem-9' => 268264,
        'modem-10' => 268260,
        'modem-11' => 268267,
		'modem-12' => 268466,
		'modem-13' => 268465,
		'modem-14' => 268464,
		'modem-15' => 268462
    );
    
    // Array to store modems that should be hidden
    $modems_to_hide = array();
    
    // Check each modem's provider categories
    foreach ($modem_map as $css_class => $modem_product_id) {
        // Get all categories for this modem
        $modem_categories = wp_get_post_terms($modem_product_id, 'product_cat', array('fields' => 'all'));
        
        // Get provider categories for this modem
        $modem_providers = array();
        foreach ($modem_categories as $category) {
            if (in_array($category->slug, $provider_slugs)) {
                $modem_providers[] = $category->slug;
            }
        }
        
        // If modem has no provider categories, show it (fallback)
        if (empty($modem_providers)) {
            continue;
        }
        
        // Check if internet plan provider matches any of this modem's providers
        if (!in_array($internet_plan_provider, $modem_providers)) {
            // No match - hide this modem
            $modems_to_hide[] = $css_class;
        }
    }
    
    // Output CSS to hide non-matching modems
    if (!empty($modems_to_hide)) {
        ?>
        <style type="text/css" id="modem-provider-filter">
            <?php foreach ($modems_to_hide as $css_class): ?>
            .checkout-screen .<?php echo $css_class; ?> {
                display: none !important;
                visibility: hidden !important;
            }
            <?php endforeach; ?>
        </style>
        <?php
    }
}
add_action('wp_head', 'dg_filter_modems_by_provider', 999);


// AJAX handler to update current product in session
add_action('wp_ajax_update_current_product_session', 'update_current_product_session');
add_action('wp_ajax_nopriv_update_current_product_session', 'update_current_product_session');

function update_current_product_session() {
    if (!isset($_POST['product_id'])) {
        wp_send_json_error(array('message' => 'No product ID provided'));
        return;
    }
    
    $product_identifier = sanitize_text_field($_POST['product_id']);
    $product_id = null;
    
    // Check if it's a numeric ID or a slug
    if (is_numeric($product_identifier)) {
        $product_id = intval($product_identifier);
    } else {
        // It's a slug, find the product
        $product_query = new WP_Query(array(
            'post_type' => 'product',
            'name' => $product_identifier,
            'posts_per_page' => 1
        ));
        
        if ($product_query->have_posts()) {
            $product_id = $product_query->posts[0]->ID;
        }
    }
    
    if (!$product_id) {
        error_log("Could not find product with identifier: " . $product_identifier);
        wp_send_json_error(array('message' => 'Product not found'));
        return;
    }
    
    // Initialize WC session
    if (!WC()->session) {
        WC()->session->init();
    }
    
    if (!WC()->session->has_session()) {
        WC()->session->set_customer_session_cookie(true);
    }
    
    $old_id = WC()->session->get('current_viewing_product_id');
    WC()->session->set('current_viewing_product_id', $product_id);
    WC()->session->save_data();
    
    error_log("=== JS TRIGGERED SESSION UPDATE ===");
    error_log("Old product ID: " . ($old_id ? $old_id : 'none'));
    error_log("New product ID: " . $product_id);
    error_log("✓ Session updated and saved");
    
    wp_send_json_success(array('product_id' => $product_id));
}


// Get customer IP address
function dg_get_customer_ip() {
    return $_SERVER['REMOTE_ADDR'] ?? '';
}

// Get order timestamp (formatted) with WordPress timezone
function dg_get_order_timestamp($format = 'Y-m-d H:i:s T') {
    // Get WordPress timezone
    $wp_timezone_string = wp_timezone_string();
    
    // Check if there's a saved order timestamp in the session
    if (WC()->session) {
        $saved_timestamp = WC()->session->get('order_timestamp');
        
        if ($saved_timestamp) {
            // Convert Unix timestamp to DateTime with WordPress timezone
            $order_datetime = new DateTime('@' . $saved_timestamp);
            $order_datetime->setTimezone(new DateTimeZone($wp_timezone_string));
            return $order_datetime->format($format);
        }
    }
    
    // Fallback: Use current time with WordPress timezone
    $order_datetime = new DateTime('now', new DateTimeZone($wp_timezone_string));
    return $order_datetime->format($format);
}


/**
 * AJAX handler to store terms timestamp in WooCommerce session
 */
add_action('wp_ajax_store_terms_timestamp', 'ajax_store_terms_timestamp');
add_action('wp_ajax_nopriv_store_terms_timestamp', 'ajax_store_terms_timestamp');

function ajax_store_terms_timestamp() {
    // Verify nonce
    check_ajax_referer('checkout_nonce', 'nonce');
    
    if (!isset($_POST['timestamp'])) {
        wp_send_json_error(array('message' => 'No timestamp provided'));
        return;
    }
    
    $timestamp = sanitize_text_field($_POST['timestamp']);
    
    // Store in WooCommerce session
    if (WC()->session) {
        WC()->session->set('terms_timestamp', $timestamp);
        error_log('Terms timestamp stored in session: ' . $timestamp);
        wp_send_json_success(array('message' => 'Timestamp stored successfully'));
    } else {
        wp_send_json_error(array('message' => 'WooCommerce session not available'));
    }
}




/**
 * Get customer info from WooCommerce checkout and stored address data
 */


function dg_get_customer_info() {
    // Get the searched address from user meta (the Google Maps address - this is the SERVICE address)
    $searched_address = dg_get_user_meta("searched_address");
    
    // Build the service address from searched_address components
    $service_address_full = ''; // Full address with all parts on one line
    
    if ($searched_address && is_array($searched_address)) {
        // Build full street address
        $address_parts = array();
        
        // Unit number - check both formats
        if (!empty($searched_address['unit_number'])) {
            $address_parts[] = 'Unit ' . $searched_address['unit_number'];
        } elseif (!empty($searched_address['unitNumber'])) {
            $address_parts[] = 'Unit ' . $searched_address['unitNumber'];
        }
        
        // Street number - PRIMARY key is 'street_number' (from Google Maps)
        if (!empty($searched_address['street_number'])) {
            $address_parts[] = $searched_address['street_number'];
        } elseif (!empty($searched_address['streetNumber'])) {
            $address_parts[] = $searched_address['streetNumber'];
        }
        
        // Street name - check both formats
        if (!empty($searched_address['street_name'])) {
            $address_parts[] = $searched_address['street_name'];
        } elseif (!empty($searched_address['streetName'])) {
            $address_parts[] = $searched_address['streetName'];
        }
        
        // Street type - check both formats
        if (!empty($searched_address['street_type'])) {
            $address_parts[] = $searched_address['street_type'];
        } elseif (!empty($searched_address['streetType'])) {
            $address_parts[] = $searched_address['streetType'];
        }
        
        // Street direction - check both formats
        if (!empty($searched_address['street_dir'])) {
            $address_parts[] = $searched_address['street_dir'];
        } elseif (!empty($searched_address['streetDirection'])) {
            $address_parts[] = $searched_address['streetDirection'];
        }
        
        $street_address = implode(' ', array_filter($address_parts));
        
        // Get city - PRIMARY keys are 'locality' and 'municipalityCity'
        $service_city = '';
        if (!empty($searched_address['locality'])) {
            $service_city = $searched_address['locality'];
        } elseif (!empty($searched_address['municipalityCity'])) {
            $service_city = $searched_address['municipalityCity'];
        }
        
        // Get province - PRIMARY key is 'administrative_area_level_1'
        $service_province = '';
        if (!empty($searched_address['administrative_area_level_1'])) {
            $service_province = $searched_address['administrative_area_level_1'];
        } elseif (!empty($searched_address['provinceOrState'])) {
            $service_province = $searched_address['provinceOrState'];
        }
        
        // Get postal code - PRIMARY key is 'postal_code'
        $service_postal = '';
        if (!empty($searched_address['postal_code'])) {
            $service_postal = $searched_address['postal_code'];
        } elseif (!empty($searched_address['postalCode'])) {
            $service_postal = $searched_address['postalCode'];
        }
        
        // Combine all address parts on one line with commas
        $address_components = array_filter(array($street_address, $service_city, $service_province, $service_postal));
        $service_address_full = implode(', ', $address_components);
    }
    
    // Get shipping address from WooCommerce SESSION (not customer)
    // By default, shipping address is same as service address
    $shipping_address_full = $service_address_full;
    
    // Check if customer used "Ship to Different Address" checkbox
    if (WC()->session) {
        // Check if shipping address was set to something different
        $ship_to_different = WC()->session->get('ship_to_different_address');
        
        // Get the custom shipping address we stored during checkout
        $custom_shipping = WC()->session->get('custom_shipping_address_full');
        
        error_log('=== RETRIEVING SHIPPING ADDRESS ===');
        error_log('ship_to_different: ' . ($ship_to_different ? 'true' : 'false'));
        error_log('custom_shipping from session: ' . $custom_shipping);
        
        if ($ship_to_different && !empty($custom_shipping)) {
            $shipping_address_full = $custom_shipping;
            error_log('Using custom shipping address: ' . $shipping_address_full);
        } else {
            error_log('Using service address for shipping: ' . $shipping_address_full);
        }
    }
    
    // Get first and last name with fallback to user meta for logged-out users
    $first_name = WC()->customer->get_billing_first_name();
    if (empty($first_name)) {
        $first_name = dg_get_user_meta('billing_first_name');
    }
    
    $last_name = WC()->customer->get_billing_last_name();
    if (empty($last_name)) {
        $last_name = dg_get_user_meta('billing_last_name');
    }
    
    // Get email with fallback to user meta for logged-out users
    $email = WC()->customer->get_billing_email();
    if (empty($email)) {
        $email = dg_get_user_meta('billing_email');
        error_log('Email retrieved from user meta: ' . $email);
    } else {
        error_log('Email retrieved from WC customer: ' . $email);
    }
    
    // Get phone with fallback to user meta for logged-out users
    $phone = WC()->customer->get_billing_phone();
    if (empty($phone)) {
        $phone = dg_get_user_meta('billing_phone');
    }
    
    // Combine first and last name
    $full_name = trim($first_name . ' ' . $last_name);
    
    error_log('=== FINAL CUSTOMER INFO ===');
    error_log('First Name: ' . $first_name);
    error_log('Last Name: ' . $last_name);
    error_log('Email: ' . $email);
    error_log('Phone: ' . $phone);
    error_log('========================');
    
    return array(
        'first_name'                    => $first_name,
        'last_name'                     => $last_name,
        'full_name'                     => $full_name,
        'email'                         => $email,
        'phone'                         => dg_format_phone_for_display($phone),
        'unit_number'                   => dg_get_user_meta('billing_unit_number') ?: '',
        'buzzer_code'                   => dg_get_user_meta('billing_buzzer_code') ?: '',
        'special_shipping_instructions' => dg_get_user_meta('special_shipping_instructions') ?: '',
        'service_address_full'          => $service_address_full,
        'shipping_address_full'         => $shipping_address_full,
        // Keep legacy fields for backward compatibility
        'address' => $service_address_full,
        'city' => '',
        'province' => '',
        'postal_code' => ''
    );
}


/**
 * Get the invoice number from the Moneris payment session
 * The invoice number is the order_id that was generated during payment
 */
function dg_get_invoice_number() {
    // Check if we have the order_id stored in WooCommerce session
    if (WC()->session) {
        $invoice_number = WC()->session->get('payment_order_id');
        
        if ($invoice_number) {
            return $invoice_number;
        }
    }
    
    // Fallback: If not in session, generate a new one (shouldn't normally happen)
    // This uses the same format as the Moneris payment processing
    $moneris_config = get_moneris_config();
    $invoice_number = ($moneris_config['test_mode'] ? 'test-' : 'web-') . time() . '-' . rand(1000, 9999);
    
    return $invoice_number;
}


/*Save Monthly Billing Method to Woocommerce session so it can be displayed on Thank You page*/

function save_monthly_payment_method_to_session($order_id) {
    error_log('=== Saving monthly payment method to session ===');
    
    // Check if monthly payment method was submitted (check both possible POST structures)
    if (isset($_POST['checkout']['monthly_bill_payment_option']) || isset($_POST['monthly_bill_payment_option'])) {
        $monthly_method = isset($_POST['checkout']['monthly_bill_payment_option']) 
            ? sanitize_text_field($_POST['checkout']['monthly_bill_payment_option'])
            : sanitize_text_field($_POST['monthly_bill_payment_option']);
        
        if (WC()->session) {
            WC()->session->set('monthly_payment_method', $monthly_method);
            error_log('✓ Monthly payment method saved to session: ' . $monthly_method);
            
            // If using credit card for monthly billing, save the card details
            if ($monthly_method === 'cc') {
                // Check if they're using the same card as upfront
                if (isset($_POST['use_same_card_for_monthly']) && $_POST['use_same_card_for_monthly'] === 'yes') {
                    // Copy the upfront card last 4 to monthly
                    $upfront_card_last_4 = WC()->session->get('payment_card_last_4');
                    if ($upfront_card_last_4) {
                        WC()->session->set('monthly_card_last_4', $upfront_card_last_4);
                        error_log('✓ Copied upfront card last 4 to monthly: ' . $upfront_card_last_4);
                    }
                } else {
                    // Using different card - should have been saved separately
                    // Check if monthly card last 4 was saved
                    if (isset($_POST['monthly_card_last_4'])) {
                        $monthly_card_last_4 = sanitize_text_field($_POST['monthly_card_last_4']);
                        WC()->session->set('monthly_card_last_4', $monthly_card_last_4);
                        error_log('✓ Monthly card last 4 saved: ' . $monthly_card_last_4);
                    }
                }
            }
        } else {
            error_log('✗ WooCommerce session not available');
        }
    } else {
        error_log('✗ No monthly payment method in POST data');
    }
}
add_action('woocommerce_checkout_update_order_meta', 'save_monthly_payment_method_to_session', 10, 1);



/**
 * Get all Thank You page data in one function call
 * Returns an array with all the information needed for the Thank You page
 * UPDATED: Added monthly card last 4 digits
 */


function dg_get_thank_you_page_data() {
    $data = array();
    
    // Invoice number
    $data['invoice_number'] = dg_get_invoice_number();
    
  // Order timestamp (formatted for display WITH TIMEZONE)
    $data['order_timestamp'] = dg_get_order_timestamp('F j, Y \a\t g:i A T'); // Added T for timezone
    $data['order_timestamp_raw'] = dg_get_order_timestamp('Y-m-d H:i:s T'); // Added T for timezone
    
    // Customer IP address
    $data['customer_ip'] = dg_get_customer_ip();
    
    // Customer information
    $customer_info = dg_get_customer_info();
    $data['customer'] = $customer_info;

    
    // Get last 4 digits of UPFRONT credit card (if available in session)
    $data['card_last_4'] = '';
    if (WC()->session) {
        $card_last_4 = WC()->session->get('payment_card_last_4');
        if ($card_last_4) {
            $data['card_last_4'] = $card_last_4;
        }
    }
    
    // Get terms acceptance timestamp from session
    $data['terms_timestamp'] = '';
    if (WC()->session) {
        $terms_timestamp = WC()->session->get('terms_timestamp');
        if ($terms_timestamp) {
            $data['terms_timestamp'] = $terms_timestamp;
            error_log('Terms timestamp retrieved for thank you page: ' . $terms_timestamp);
        }
    }
    
    // ============================================================
    // Get CCD (Coupon Code) value from user meta
    // ============================================================
    $data['ccd'] = '';
    $ccd_encoded = dg_get_user_meta("ccd");
    
    if (!empty($ccd_encoded)) {
        // The CCD is stored base64 encoded, so decode it for display
        $ccd_decoded = base64_decode($ccd_encoded);
        
        // Additional safety check - only set if decode was successful
        if ($ccd_decoded !== false && !empty($ccd_decoded)) {
            $data['ccd'] = strtoupper(trim($ccd_decoded)); // Convert to uppercase for display
            error_log('CCD retrieved for thank you page: ' . $data['ccd']);
        }
    }
    // ============================================================
    
    // Get payment transaction details from session
    if (WC()->session) {
        $data['transaction_id'] = WC()->session->get('payment_transaction_id');
        $data['receipt_id'] = WC()->session->get('payment_receipt_id');
        $data['auth_code'] = WC()->session->get('payment_auth_code');       
        $data['reference_num'] = WC()->session->get('payment_reference_num');  
        $data['payment_amount'] = WC()->session->get('payment_amount');
        $data['payment_date'] = WC()->session->get('payment_date');
    }
    
    // Get upfront fee summary from stored session data (cart was emptied after payment)
    if (WC()->session) {
        $stored_upfront = WC()->session->get('stored_upfront_summary');
        $data['upfront_summary'] = $stored_upfront ? $stored_upfront : array();
    } else {
        $data['upfront_summary'] = array();
    }

    // Get monthly fee summary from stored session data (cart was emptied after payment)
    if (WC()->session) {
        $stored_monthly = WC()->session->get('stored_monthly_summary');
        $data['monthly_summary'] = $stored_monthly ? $stored_monthly : array();
    } else {
        $data['monthly_summary'] = array();
    }
    
    // ============================================================
    // CORRECTED: Get monthly payment method from USER META (not session)
    // The payment method itself doesn't have encoding issues - only the card number does
    // ============================================================
    $data['monthly_payment_method'] = dg_get_user_meta('monthly_bill_payment_option');
    
    if ($data['monthly_payment_method']) {
        error_log('Monthly payment method retrieved from user meta: ' . $data['monthly_payment_method']);
    } else {
        error_log('No monthly payment method found in user meta');
    }
    // ============================================================
    
    // ============================================================
    // FIXED: Get last 4 digits of MONTHLY credit card from SESSION (not user meta)
    // The card number IS base64 encoded and corrupted, so we use session instead
    // ============================================================
    $data['monthly_card_last_4'] = '';
    
    if ($data['monthly_payment_method'] && strtolower($data['monthly_payment_method']) === 'cc') {
        // Get from session (stored during validation)
        if (WC()->session) {
            $monthly_card_last_4 = WC()->session->get('monthly_payment_card_last_4');
            if ($monthly_card_last_4) {
                $data['monthly_card_last_4'] = $monthly_card_last_4;
                error_log('Monthly card last 4 retrieved from session: ' . $monthly_card_last_4);
            } else {
                error_log('Warning: Monthly payment is CC but no card last 4 in session');
            }
        }
    }
    // ============================================================
    
  // Format monthly payment method for display
    if ($data['monthly_payment_method']) {
        switch(strtolower($data['monthly_payment_method'])) {
            case 'cc':
                $data['monthly_payment_method_display'] = 'Credit Card';
                break;
            case 'bank':
            case 'pad':
                $data['monthly_payment_method_display'] = 'Pre-Authorized Debit (PAD)';
                break;
            case 'payafter':
                $data['monthly_payment_method_display'] = 'Pay After (Non Pre-Authorized)';
                break;
            default:
                $data['monthly_payment_method_display'] = ucfirst($data['monthly_payment_method']);
        }
    } else {
        $data['monthly_payment_method_display'] = 'Not specified';
    }
    
    return $data;
}

/**
 * Prevent WooCommerce from caching product purchasability
 * This ensures products always reflect their current state
 */
add_filter('woocommerce_product_is_purchasable', 'force_product_purchasability_check', 10, 2);
function force_product_purchasability_check($is_purchasable, $product) {
    // Don't use cached value - always recalculate
    // This is safe because the calculation is lightweight
    return $product->is_type('simple') && $product->get_price() !== null;
}

// Also clear product transients more frequently
add_action('woocommerce_update_product', 'clear_product_transients_on_update');
function clear_product_transients_on_update($product_id) {
    delete_transient('wc_product_' . $product_id);
    wc_delete_product_transients($product_id);
}

/**
 * Ensure products with ACF dynamic pricing are always purchasable
 */
add_filter('woocommerce_product_is_purchasable', 'ensure_dynamic_price_products_purchasable', 20, 2);
function ensure_dynamic_price_products_purchasable($is_purchasable, $product) {
    // If product has a dynamic sale price ACF field, it's purchasable
    $dynamic_sale_price = get_field('dynamic_sale_price', $product->get_id());
    
    if ($dynamic_sale_price !== false && $dynamic_sale_price !== null) {
        return true;
    }
    
    return $is_purchasable;
}

// Debug Internet plan products - NO conditionals
add_action('wp', 'debug_internet_plan_product', 999); // High priority to run late
function debug_internet_plan_product() {
    global $post;
    
    error_log('=== INTERNET PLAN TEMPLATE DEBUG (FORCED) ===');
    error_log('Current URL: ' . $_SERVER['REQUEST_URI']);
    error_log('is_singular(product): ' . (is_singular('product') ? 'YES' : 'NO'));
    error_log('Global Post exists: ' . (isset($post) ? 'YES' : 'NO'));
    
    if (isset($post)) {
        error_log('Post ID: ' . $post->ID);
        error_log('Post Type: ' . $post->post_type);
        error_log('Post Slug: ' . $post->post_name);
        
        if ($post->post_type === 'product') {
            $product = wc_get_product($post->ID);
            
            if ($product) {
                error_log('Product loaded successfully!');
                error_log('Is Purchasable: ' . ($product->is_purchasable() ? 'YES' : 'NO'));
                error_log('Regular Price: "' . $product->get_regular_price() . '"');
                error_log('Sale Price: "' . $product->get_sale_price() . '"');
                error_log('Price: "' . $product->get_price() . '"');
                
                $monthly_fee = get_field('monthly_fee', $product->get_id());
                $dynamic_sale = get_field('dynamic_sale_price', $product->get_id());
                error_log('ACF Monthly Fee: "' . $monthly_fee . '"');
                error_log('ACF Dynamic Sale: "' . $dynamic_sale . '"');
            }
        }
    }
    error_log('=== END DEBUG ===');
}

/**
 * AJAX Handler: Confirm Customer Info and Send to Diallog
 * Called when "Confirm Customer Info" button is clicked
 * Saves data to WC session and sends to Diallog with state 50
 */


 function confirm_customer_info_handler() {
    
    // **TEST MODE TOGGLE** - Set to false to enable Diallog submission
    $skip_diallog_submission = false; // Change to false when ready to send to Diallog
    
    try {
        error_log('');
        error_log('================================================================================');
        error_log('============== CUSTOMER INFO CONFIRMATION STARTED (STATE 50) ==================');
        error_log('Test Mode (Skip Diallog): ' . ($skip_diallog_submission ? 'YES' : 'NO'));
        error_log('================================================================================');
        error_log('');
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'confirm_customer_info_nonce')) {
            throw new Exception('Security verification failed');
        }

        // Initialize WC session
        if (!WC()->session || !WC()->session->has_session()) {
            WC()->session->set_customer_session_cookie(true);
        }

 // Save customer name and email to WooCommerce customer AND user meta
if (isset($_POST['first_name']) && !empty($_POST['first_name'])) {
    WC()->customer->set_billing_first_name(sanitize_text_field($_POST['first_name']));
    dg_set_user_meta('billing_first_name', sanitize_text_field($_POST['first_name']));
    error_log('First name saved to WC customer and user meta: ' . $_POST['first_name']);
}

if (isset($_POST['last_name']) && !empty($_POST['last_name'])) {
    WC()->customer->set_billing_last_name(sanitize_text_field($_POST['last_name']));
    dg_set_user_meta('billing_last_name', sanitize_text_field($_POST['last_name']));
    error_log('Last name saved to WC customer and user meta: ' . $_POST['last_name']);
}

if (isset($_POST['email']) && !empty($_POST['email'])) {
    WC()->customer->set_billing_email(sanitize_email($_POST['email']));
    dg_set_user_meta('billing_email', sanitize_email($_POST['email']));
    error_log('Email saved to WC customer and user meta: ' . $_POST['email']);
}

// Save phone to WooCommerce customer AND user meta
if (isset($_POST['phone']) && !empty($_POST['phone'])) {
    $phone_digits = preg_replace('/\D/', '', $_POST['phone']);
    WC()->customer->set_billing_phone($phone_digits);
    dg_set_user_meta('billing_phone', $phone_digits);
    error_log('Phone saved to WC customer and user meta: ' . $phone_digits);
}

		// 3 Additonal Customer Fields Added June 4th, 2026
		
if (isset($_POST['unit_number'])) {
    $unit_number = sanitize_text_field($_POST['unit_number']);
    dg_set_user_meta('billing_unit_number', $unit_number);
    error_log('Unit number saved to user meta: ' . $unit_number);
}

if (isset($_POST['buzzer_code'])) {
    $buzzer_code = sanitize_text_field($_POST['buzzer_code']);
    dg_set_user_meta('billing_buzzer_code', $buzzer_code);
    error_log('Buzzer code saved to user meta: ' . $buzzer_code);
}

if (isset($_POST['special_shipping_instructions'])) {
    $shipping_instructions = sanitize_textarea_field($_POST['special_shipping_instructions']);
    dg_set_user_meta('special_shipping_instructions', $shipping_instructions);
    if (WC()->session) {
        WC()->session->set('special_shipping_instructions', $shipping_instructions);
    }
    error_log('Special shipping instructions saved: ' . $shipping_instructions);
}

		// Save shipping address to session from POST data
if (WC()->session) {
    $ship_to_different = isset($_POST['ship_to_different']) && $_POST['ship_to_different'] === 'true';
    WC()->session->set('ship_to_different_address', $ship_to_different);
    
    if ($ship_to_different && !empty($_POST['shipping_address'])) {
        WC()->session->set('custom_shipping_address_full', sanitize_text_field($_POST['shipping_address']));
        error_log('Shipping address saved to session from POST: ' . $_POST['shipping_address']);
    } else {
        WC()->session->set('custom_shipping_address_full', '');
    }
}

        // Get customer data using the SAME method as prepare_diallog_order_data
        $customer_info = dg_get_customer_info();

        error_log('--- 1) CUSTOMER INFO RETRIEVED ---');
        error_log(json_encode($customer_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        error_log('');

        // Get CCD from user meta
        $ccd = '';
        $ccd_encoded = dg_get_user_meta("ccd");
        if (!empty($ccd_encoded)) {
            $ccd_decoded = base64_decode($ccd_encoded);
            if ($ccd_decoded !== false && !empty($ccd_decoded)) {
                $ccd = strtoupper(trim($ccd_decoded));
                error_log('--- CCD RETRIEVED FROM USER META ---');
                error_log('CCD: ' . $ccd);
                error_log('');
            }
        } else {
            error_log('--- NO CCD FOUND ---');
            error_log('');
        }

        // Build customer_data EXACTLY like prepare_diallog_order_data does
       $customer_data = array(
    'billing_first_name'             => $customer_info['first_name'],
    'billing_last_name'              => $customer_info['last_name'],
    'billing_email'                  => $customer_info['email'],
    'billing_phone'                  => $customer_info['phone'],
    'customer_service_address'       => $customer_info['service_address_full'],
    'customer_shipping_address'      => $customer_info['shipping_address_full'],
    'customer_ip'                    => $_SERVER['REMOTE_ADDR'] ?? '',
    'ccd'                            => $ccd,
    'unit_number'                    => $customer_info['unit_number'],
    'buzzer_code'                    => $customer_info['buzzer_code'],
    'special_shipping_instructions'  => $customer_info['special_shipping_instructions'],
);

        error_log('--- 2) CUSTOMER DATA FORMATTED ---');
        error_log(json_encode($customer_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        error_log('');

        // Save to WooCommerce session
        WC()->session->set('confirmed_customer_data', $customer_data);
        error_log('✓ Customer data saved to WC session');
        error_log('');
        
        // Get FORMATTED summaries (same formatting as state 100 call)
        error_log('--- 3) FORMATTING SUMMARIES USING SHARED HELPER FUNCTION ---');
        $formatted_summaries = format_summaries_for_diallog();
        error_log('✓ Summaries formatted successfully');
        error_log('');
        
        error_log('--- 4) UPFRONT SUMMARY (FORMATTED) ---');
        error_log(json_encode($formatted_summaries['upfront_summary'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        error_log('');
        
        error_log('--- 5) MONTHLY SUMMARY (FORMATTED) ---');
        error_log(json_encode($formatted_summaries['monthly_summary'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        error_log('');
        
        // Prepare data for Diallog API - order_state FIRST
        $diallog_data = array(
            'order_state' => 'not_completed', // First field in the data package
            'customer_data' => $customer_data,
            'upfront_summary' => $formatted_summaries['upfront_summary'],
            'monthly_summary' => $formatted_summaries['monthly_summary']
        );

        error_log('--- 6) COMPLETE DATA PACKAGE FOR DIALLOG (STATE 50) ---');
        error_log(json_encode($diallog_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        error_log('');

        // Call send_customer_info_to_diallog with test mode toggle
        error_log('--- 7) SENDING TO DIALLOG SERVER ---');
        
        $diallog_response = 'skipped';
        if (!$skip_diallog_submission) {
            $diallog_response = send_customer_info_to_diallog($diallog_data);
            error_log('✓ Order sent to Diallog');
        } else {
            error_log('⊗ Test mode: Skipping Diallog submission');
        }
        error_log('');

        error_log('================================================================================');
        error_log('========== CUSTOMER INFO CONFIRMATION COMPLETED SUCCESSFULLY ==================');
        error_log('================================================================================');
        error_log('');

        // Return success
        wp_send_json_success(array(
            'message' => 'Customer information confirmed and saved',
            'customer_data' => $customer_data,
            'diallog_response' => $diallog_response,
            'ccd_included' => !empty($ccd) ? 'yes' : 'no',
            'test_mode' => $skip_diallog_submission
        ));

    } catch (Exception $e) {
        error_log('');
        error_log('================================================================================');
        error_log('ERROR IN CUSTOMER INFO CONFIRMATION');
        error_log('================================================================================');
        error_log('Error Message: ' . $e->getMessage());
        error_log('Stack Trace: ' . $e->getTraceAsString());
        error_log('================================================================================');
        error_log('');
        
        wp_send_json_error(array(
            'message' => $e->getMessage()
        ));
    }
}

add_action('wp_ajax_confirm_customer_info', 'confirm_customer_info_handler');
add_action('wp_ajax_nopriv_confirm_customer_info', 'confirm_customer_info_handler');


/**
 * Get the tax rate percentage based on customer's state/province
 */
function get_customer_tax_rate_percentage() {
    // Get customer's state from stored address data
    $searched_address = dg_get_user_meta("searched_address");
    
    error_log('=== TAX RATE DEBUG ===');
    error_log('Searched address: ' . print_r($searched_address, true));
    
    // FIXED: Check for both possible key names
    $state = '';
    if (isset($searched_address['provinceOrState'])) {
        $state = $searched_address['provinceOrState'];
    } elseif (isset($searched_address['administrative_area_level_1'])) {
        $state = $searched_address['administrative_area_level_1'];
    }
    
    error_log('State extracted: ' . $state);
    
    if (empty($state)) {
        error_log('State is empty, returning 0');
        return 0;
    }
    
    // Get WooCommerce tax rates for this state
    $tax_rates = WC_Tax::find_rates(array(
        'country'   => 'CA',
        'state'     => $state,
        'city'      => '',
        'postcode'  => ''
    ));
    
    error_log('WooCommerce tax rates found: ' . print_r($tax_rates, true));
    
    // Calculate total tax rate percentage
    $tax_percentage = 0;
    if (!empty($tax_rates)) {
        foreach ($tax_rates as $rate) {
            $tax_percentage += floatval($rate['rate']);
        }
    }
    
    error_log('Final tax percentage: ' . $tax_percentage);
    
    return $tax_percentage;
}

/**
 * AJAX handler to get Thank You page data
 */
add_action('wp_ajax_get_thank_you_data', 'ajax_get_thank_you_data');
add_action('wp_ajax_nopriv_get_thank_you_data', 'ajax_get_thank_you_data');

function ajax_get_thank_you_data() {
    // Get all the Thank You page data
    $data = dg_get_thank_you_page_data();


    $data['tax_rate'] = get_customer_tax_rate_percentage();
    
    // Return the data as JSON
    wp_send_json_success($data);
}

/**
 * Enqueue Product Session Update Script
 */
function enqueue_product_session_update_script() {
    error_log('=== ENQUEUE PRODUCT SESSION UPDATE SCRIPT RUNNING ===');
    
    wp_enqueue_script(
        'product-session-update',
        get_stylesheet_directory_uri() . '/js/product-session-update.js',
        array('jquery'),
        '1.0.5',
        true
    );
    
    error_log('Script enqueued: product-session-update');
    
    // Localize with AJAX data
    wp_localize_script('product-session-update', 'diallog_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('diallog_ajax_nonce'),
        'confirm_customer_info_nonce' => wp_create_nonce('confirm_customer_info_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_product_session_update_script');

function enqueue_customer_info_confirm_script() {
    // Only load on checkout pagef
    if (is_checkout() || is_page(267950)) {
        
        wp_enqueue_script(
            'customer-info-confirm-js',
            get_stylesheet_directory_uri() . '/js/customer-info-confirm.js',
            array('jquery'),
            '1.0.2', // Bump version
            true
        );
        
        // Localize with AJAX data
        wp_localize_script('customer-info-confirm-js', 'diallog_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('diallog_ajax_nonce'),
            'confirm_customer_info_nonce' => wp_create_nonce('confirm_customer_info_nonce')
        ));
    }
}
add_action('wp_enqueue_scripts', 'enqueue_customer_info_confirm_script', 14);


/**
 * Enqueue Thank You page JavaScript
 */
function enqueue_thank_you_scripts() {
    // Only load on the Thank You page 
    if (is_page(267294)) { // Thank You page ID
        wp_enqueue_script(
            'thank-you-js',
            get_stylesheet_directory_uri() . '/js/thank-you.js',
            array('jquery'),
            '1.0.2',
            true
        );
        
        // Make sure ajaxurl is available
        wp_localize_script('thank-you-js', 'thankYouData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'timezone' => wp_timezone_string(),
        ));
    }
}

add_action('wp_enqueue_scripts', 'enqueue_thank_you_scripts');


// Enqueue billing fields formatting JavaScript
function enqueue_billing_fields_formatting() {
    if (is_checkout()) {
        wp_enqueue_script(
            'billing-fields-formatting',
            get_stylesheet_directory_uri() . '/js/billing-fields-formatting.js',
            array('jquery'),
            '1.2.0',
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_billing_fields_formatting');


// Enqueue CustoomAJAX JS File - contains preloader functionality when dynamically updating upfront total shortcode

function enqueue_custom_scripts() {
    wp_enqueue_script(
        'custom-ajax-script', 
        get_stylesheet_directory_uri() . '/js/custom-ajax.js', 
        array('jquery'), 
        '1.0.4',  // Bump version to clear cache
        true
    );
    
    // No need to localize - already done by card-selection.js enqueue
}
add_action('wp_enqueue_scripts', 'enqueue_custom_scripts');


/**
 * AJAX Handler - Store Customer Info Confirmation (for future API integration)
 * This will be used when you integrate the Diallog API call
 */
function ajax_store_customer_info_confirmation() {
    // Verify nonce
    check_ajax_referer('customer_info_nonce', 'nonce');
    
    // Get customer info data
    $customer_data = array(
        'first_name' => isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '',
        'last_name' => isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '',
        'email' => isset($_POST['email']) ? sanitize_email($_POST['email']) : '',
        'phone' => isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '',
        'service_address' => isset($_POST['service_address']) ? sanitize_text_field($_POST['service_address']) : '',
        'shipping_address' => isset($_POST['shipping_address']) ? sanitize_text_field($_POST['shipping_address']) : '',
        'timestamp' => isset($_POST['timestamp']) ? sanitize_text_field($_POST['timestamp']) : current_time('mysql'),
    );
    
    // Store in session for now (you'll integrate Diallog API here later)
    if (!session_id()) {
        session_start();
    }
    $_SESSION['customer_info_confirmed'] = $customer_data;
    
    // TODO: Send to Diallog API here
    // $diallog_response = send_to_diallog_api($customer_data);
    
    wp_send_json_success(array(
        'message' => 'Customer info stored successfully',
        'timestamp' => $customer_data['timestamp']
    ));
}
add_action('wp_ajax_store_customer_info_confirmation', 'ajax_store_customer_info_confirmation');
add_action('wp_ajax_nopriv_store_customer_info_confirmation', 'ajax_store_customer_info_confirmation');


/************
 ---------------Billing Field Formatting---------------
 ***********/

function dg_enqueue_phone_formatting_js() {
    if (is_checkout()) {
        $js_file_path = get_stylesheet_directory() . '/js/.js';
        
        if (file_exists($js_file_path)) {
            wp_enqueue_script(
                'dg-billing-phone-formatting',
                get_stylesheet_directory_uri() . '/js/.js',
                array('jquery'),
                '1.0.2',
                true
            );
        } else {
            error_log('Phone formatting JS file not found at: ' . $js_file_path);
        }
    }
}
add_action('wp_enqueue_scripts', 'dg_enqueue_phone_formatting_js');


// ============================================
// PART 2: Add custom CSS to update the label
// ============================================
function dg_phone_label_css() {
    if (is_checkout()) {
        ?>
        <style>
            #billing_phone_field label::after {
                content: "(10 digits)";
            }
        </style>
        <?php
    }
}
add_action('wp_head', 'dg_phone_label_css');

// ============================================
// PART 3: Validate phone (exactly 10 digits)
// ============================================
function dg_validate_phone_10_digits() {
    if (isset($_POST['billing_phone'])) {
        $phone = $_POST['billing_phone'];
        $digits_only = preg_replace('/\D/', '', $phone);
        
        if (strlen($digits_only) !== 10) {
            wc_add_notice('Phone number must contain exactly 10 digits.', 'error');
        }
    }
}
add_action('woocommerce_checkout_process', 'dg_validate_phone_10_digits');

// ============================================
// PART 4: Strip formatting before saving (digits only)
// ============================================
function dg_strip_phone_formatting_on_save() {
    if (isset($_POST['billing_phone'])) {
        $phone = $_POST['billing_phone'];
        
        // Remove all non-digit characters
        $digits_only = preg_replace('/\D/', '', $phone);
        
        // Update the POST data with digits only
        $_POST['billing_phone'] = $digits_only;
    }
}
// Hook early in checkout process to strip before anything else processes it
add_action('woocommerce_checkout_process', 'dg_strip_phone_formatting_on_save', 5);

// ============================================
// PART 5: Helper function to format phone for display
// ============================================
function dg_format_phone_for_display($phone) {
    if (empty($phone)) {
        return '';
    }
    
    // Remove all non-digit characters (in case it's already formatted)
    $digits_only = preg_replace('/\D/', '', $phone);
    
    // Only format if we have exactly 10 digits
    if (strlen($digits_only) === 10) {
        return '(' . substr($digits_only, 0, 3) . ') ' . substr($digits_only, 3, 3) . '-' . substr($digits_only, 6, 4);
    }
    
    // Return original if not 10 digits
    return $phone;
}



// ========== Zach's AJAX handlers for monthly billing section on checkout====

add_action('wp_ajax_remove_monthly_billing_deposits', 'ajax_remove_monthly_billing_deposits');
add_action('wp_ajax_nopriv_remove_monthly_billing_deposits', 'ajax_remove_monthly_billing_deposits');

function ajax_remove_monthly_billing_deposits() {
    check_ajax_referer('checkout_nonce', 'nonce');
    
    $keep_option = isset($_POST['keep_option']) ? sanitize_text_field($_POST['keep_option']) : '';
    
    // If keep_option is empty, clear ALL monthly billing selections
    if (empty($keep_option)) {
        // Remove all deposit products from cart
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $product_id = $cart_item['product_id'];
            
            // Remove the specific Pay After deposit product
            if ($product_id == 267989) {
                WC()->cart->remove_cart_item($cart_item_key);
                continue;
            }
            
            // Also remove any product in the "deposit" category
            $product_cats = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'slugs'));
            if (in_array('deposit', $product_cats)) {
                WC()->cart->remove_cart_item($cart_item_key);
            }
        }
        
        // Clear all saved monthly billing user meta
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            
            // Clear all monthly billing related meta
            delete_user_meta($user_id, 'monthly_bill_payment_option');
            delete_user_meta($user_id, 'cc_monthly_billing_card_number');
            delete_user_meta($user_id, 'cc_monthly_billing_card_expiry');
            delete_user_meta($user_id, 'cc_monthly_billing_card_cvv');
            delete_user_meta($user_id, 'cc_monthly_billing_full_name');
            delete_user_meta($user_id, 'cc_monthly_billing_postcode');
            delete_user_meta($user_id, 'bank_monthly_billing_first_name');
            delete_user_meta($user_id, 'bank_monthly_billing_last_name');
            delete_user_meta($user_id, 'bank_monthly_billing_account_type');
            delete_user_meta($user_id, 'bank_monthly_billing_financial_institution');
            delete_user_meta($user_id, 'bank_monthly_billing_transit_number');
            delete_user_meta($user_id, 'bank_monthly_billing_institution_number');
            delete_user_meta($user_id, 'bank_monthly_billing_account_number');
        }
        
        // Clear session data if you're using sessions
        if (isset($_SESSION)) {
            unset($_SESSION['monthly_billing_data']);
        }
        
        // Recalculate cart totals
        WC()->cart->calculate_totals();
        
        wp_send_json_success(array(
            'message' => 'All monthly billing options cleared',
            'kept_option' => '',
            'action' => 'cleared_all'
        ));
        
        return;
    }
    
    // If we're not keeping the payafter option, remove all deposit products
    if ($keep_option !== 'payafter') {
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $product_id = $cart_item['product_id'];
            
            // Remove the specific Pay After deposit product
            if ($product_id == 267989) {
                WC()->cart->remove_cart_item($cart_item_key);
                continue;
            }
            
            // Also remove any product in the "deposit" category
            $product_cats = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'slugs'));
            if (in_array('deposit', $product_cats)) {
                WC()->cart->remove_cart_item($cart_item_key);
            }
        }
        
        // Clear Pay After related user meta if switching away from it
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            
            // Only clear pay after if we're keeping CC or bank
            if (in_array($keep_option, ['cc', 'bank'])) {
                // Don't clear the CC or bank data, just ensure pay after is not set
                if (dg_get_user_meta('monthly_bill_payment_option') === 'payafter') {
                    dg_update_user_meta('monthly_bill_payment_option', $keep_option);
                }
            }
        }
    }
    
    // If we're not keeping CC option, clear CC data
    if ($keep_option !== 'cc' && is_user_logged_in()) {
        $user_id = get_current_user_id();
        delete_user_meta($user_id, 'cc_monthly_billing_card_number');
        delete_user_meta($user_id, 'cc_monthly_billing_card_expiry');
        delete_user_meta($user_id, 'cc_monthly_billing_card_cvv');
        delete_user_meta($user_id, 'cc_monthly_billing_full_name');
        delete_user_meta($user_id, 'cc_monthly_billing_postcode');
    }
    
    // If we're not keeping bank option, clear bank data  
    if ($keep_option !== 'bank' && is_user_logged_in()) {
        $user_id = get_current_user_id();
        delete_user_meta($user_id, 'bank_monthly_billing_first_name');
        delete_user_meta($user_id, 'bank_monthly_billing_last_name');
        delete_user_meta($user_id, 'bank_monthly_billing_account_type');
        delete_user_meta($user_id, 'bank_monthly_billing_financial_institution');
        delete_user_meta($user_id, 'bank_monthly_billing_transit_number');
        delete_user_meta($user_id, 'bank_monthly_billing_institution_number');
        delete_user_meta($user_id, 'bank_monthly_billing_account_number');
    }
    
    // Recalculate cart totals
    WC()->cart->calculate_totals();
    
    wp_send_json_success(array(
        'message' => 'Other monthly billing options cleared',
        'kept_option' => $keep_option,
        'action' => 'cleared_others'
    ));
}


// ----  Simple validation state check - used in confirm-terms.js for both monthly billing and terms and condition validation check


add_action('wp_ajax_simple_validation_check', 'simple_validation_check');
add_action('wp_ajax_nopriv_simple_validation_check', 'simple_validation_check');

function simple_validation_check() {
    // Check if any monthly billing method is validated
    $has_payafter = false;
    $has_monthly_option = false;
    
    // Check for Pay After deposit in cart
    if (WC()->cart) {
        foreach (WC()->cart->get_cart() as $cart_item) {
            if ($cart_item['product_id'] == 267989) { // Pay After product ID
                $has_payafter = true;
                break;
            }
        }
    }
    
    // Check user meta for saved monthly billing option
    $monthly_option = dg_get_user_meta('monthly_bill_payment_option');
    if (!empty($monthly_option) && in_array(strtolower($monthly_option), ['cc', 'bank'])) {
        $has_monthly_option = true;
    }
    
    $is_validated = $has_payafter || $has_monthly_option;
    
    wp_send_json_success(array(
        'validated' => $is_validated,
        'method' => $has_payafter ? 'payafter' : ($has_monthly_option ? $monthly_option : 'none')
    ));
}

// AJAX Handler to refresh upfront summary code directly after adding Pay Later Depsosut

add_action('wp_ajax_refresh_upfront_summary_shortcode', 'ajax_refresh_upfront_summary_shortcode');
add_action('wp_ajax_nopriv_refresh_upfront_summary_shortcode', 'ajax_refresh_upfront_summary_shortcode');

function ajax_refresh_upfront_summary_shortcode() {
    check_ajax_referer('checkout_nonce', 'nonce');
    
    // Get fresh upfront table HTML
    $upfront_table = upfront_fee_summary_shortcode();
    
    wp_send_json_success(array(
        'upfront_table' => $upfront_table,
        'message' => 'Upfront summary refreshed successfully'
    ));
}

// AJAX handler to update Moneris payment amount
add_action('wp_ajax_update_moneris_payment_amount', 'ajax_update_moneris_payment_amount');
add_action('wp_ajax_nopriv_update_moneris_payment_amount', 'ajax_update_moneris_payment_amount');

function ajax_update_moneris_payment_amount() {
    check_ajax_referer('checkout_nonce', 'nonce');
    
    error_log('=== AJAX UPDATE MONERIS PAYMENT AMOUNT CALLED ===');
    
    // Get updated total amount (same logic as moneris_payment_form_shortcode)
    $total_amount = 0;
    if (function_exists('get_upfront_fee_summary')) {
        $summary = get_upfront_fee_summary();
        $total_amount = $summary['grand_total'][1];
        $total_display = wc_price($total_amount);
        
        error_log('AJAX - Upfront summary grand total: ' . $total_amount);
        error_log('AJAX - Installation price in summary: ' . $summary['installation'][1]);
    } else if (function_exists('WC') && WC()->cart) {
        $total_amount = WC()->cart->get_total('edit');
        $total_display = wc_price($total_amount);
    }
    
    // Build the complete HTML to match the original shortcode
    $payment_amount_html = '<h3>Amount Due Today: ' . $total_display . '</h3>';
    $payment_amount_html .= '<p>If you do not have access to a credit card and would like to send a payment via e-transfer please email us @ <a href="mailto:customercare@diallog.com">customercare@diallog.com</a> and we will assist in completing your order.</p>';
    
    error_log('=== END AJAX UPDATE ===');
    
    wp_send_json_success(array(
        'total_amount' => $total_amount,
        'total_display' => $total_display,
        'payment_amount_html' => $payment_amount_html
    ));
}

// AJAX handler to check which monthly billing method was previously selected & return saved billing field data alongside the method

add_action('wp_ajax_get_selected_monthly_billing_method', 'ajax_get_selected_monthly_billing_method');
add_action('wp_ajax_nopriv_get_selected_monthly_billing_method', 'ajax_get_selected_monthly_billing_method');

function ajax_get_selected_monthly_billing_method() {
    check_ajax_referer('checkout_nonce', 'nonce');
    
    $selected_method = '';
    
    // Check cart for payafter deposit
    if (WC()->cart && !WC()->cart->is_empty()) {
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $product_id = $cart_item['product_id'];
            if ($product_id == 267989) {
                $selected_method = 'payafter';
                break;
            }
            $product_cats = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'slugs'));
            if (in_array('deposit', $product_cats)) {
                $selected_method = 'payafter';
                break;
            }
        }
    }
    
    if (empty($selected_method)) {
        $monthly_payment_option = dg_get_user_meta('monthly_bill_payment_option');
        if (!empty($monthly_payment_option)) {
            if (strtolower($monthly_payment_option) == 'cc') {
                $selected_method = 'cc';
            } elseif (strtolower($monthly_payment_option) == 'bank') {
                $selected_method = 'bank';
            }
        }
    }

    // NEW: Return saved field values so JS can repopulate inputs
    $saved_fields = array();
    if ($selected_method === 'cc') {
        $saved_fields = array(
            'cc_cardholder_name' => dg_get_user_meta('cc_monthly_billing_full_name'),
            'cc_card_number'     => dg_get_user_meta('cc_monthly_billing_card_number'),
            'cc_expiry'          => dg_get_user_meta('cc_monthly_billing_card_expiry'),
            'cc_cvv'             => dg_get_user_meta('cc_monthly_billing_card_cvv'),
            'cc_postal_code'     => dg_get_user_meta('cc_monthly_billing_postcode'),
        );
    } elseif ($selected_method === 'bank') {
        $saved_fields = array(
            'bank_first_name'        => dg_get_user_meta('bank_monthly_billing_first_name'),
            'bank_last_name'         => dg_get_user_meta('bank_monthly_billing_last_name'),
            'bank_account_type'      => dg_get_user_meta('bank_monthly_billing_account_type'),
            'bank_institution'       => dg_get_user_meta('bank_monthly_billing_financial_institution'),
            'bank_transit'           => dg_get_user_meta('bank_monthly_billing_transit_number'),
            'bank_institution_number'=> dg_get_user_meta('bank_monthly_billing_institution_number'),
            'bank_account_number'    => dg_get_user_meta('bank_monthly_billing_account_number'),
        );
    }
    
    wp_send_json_success(array(
        'selected_method' => $selected_method,
        'saved_fields'    => $saved_fields,
        'message'         => 'Monthly billing method check completed'
    ));
}

// AJAX handler to save monthly billing selection to user meta
add_action('wp_ajax_save_monthly_billing_selection', 'ajax_save_monthly_billing_selection');
add_action('wp_ajax_nopriv_save_monthly_billing_selection', 'ajax_save_monthly_billing_selection');

function ajax_save_monthly_billing_selection() {
    check_ajax_referer('checkout_nonce', 'nonce');
    
    $method = sanitize_text_field($_POST['method']);
    $billing_data = $_POST['billing_data'];
    
    // Save each piece of billing data to user meta (same as the old UpdateUserData function)
    if (is_array($billing_data)) {
        foreach ($billing_data as $key => $value) {
            dg_set_user_meta(sanitize_text_field($key), sanitize_text_field($value));
        }
    }
    
    // Also set the order step to indicate monthly billing is configured
    dg_set_user_meta('order_step', 6);
    
    wp_send_json_success(array(
        'method' => $method,
        'message' => 'Monthly billing selection saved successfully'
    ));
}



// MINIMAL UPDATE: Replace your existing ajax_validate_credit_card function with this

add_action('wp_ajax_validate_credit_card', 'ajax_validate_credit_card');
add_action('wp_ajax_nopriv_validate_credit_card', 'ajax_validate_credit_card');

function ajax_validate_credit_card() {
    check_ajax_referer('checkout_nonce', 'nonce');
    
    $card_data = $_POST['card_data'];
    
    // ============================================================
    // Store last 4 digits in session BEFORE encoding
    // This prevents the base64 + sanitize_text_field corruption issue
    // ============================================================
    $clean_card_number = preg_replace('/\s+/', '', $card_data['card_number']);
    if (strlen($clean_card_number) >= 4) {
        $monthly_card_last_4 = substr($clean_card_number, -4);
        if (WC()->session) {
            WC()->session->set('monthly_payment_card_last_4', $monthly_card_last_4);
            error_log('Monthly card last 4 stored in session: ' . $monthly_card_last_4);
        }
    }
    // ============================================================
    
    // Basic postal code validation
    if (empty($card_data['postal_code'])) {
        wp_send_json_error(array('message' => 'Billing postal code is required', 'field' => 'postal_code'));
        return;
    }
    
    // Clean and validate Canadian postal code format  
    $postal_code = strtoupper(preg_replace('/[^A-Z0-9]/', '', $card_data['postal_code']));
    if (!preg_match('/^[A-Z]\d[A-Z]\d[A-Z]\d$/', $postal_code)) {
        wp_send_json_error(array('message' => 'Please enter a valid Canadian postal code (A1A 1A1)', 'field' => 'postal_code'));
        return;
    }
    
    // Convert MM/YY to YYMM format for Moneris
    $expiry = $card_data['expiry'];
    if (strpos($expiry, '/') !== false) {
        list($month, $year) = explode('/', $expiry);
        $formatted_expiry = $year . $month;
    } else {
        $formatted_expiry = $expiry;
    }
    
    $payment_info = array(
        'custid'      => '',
        'orderid'     => 'validate-' . time(),
        'amount'      => '0.01',
        'cardno'      => $clean_card_number,
        'expdate'     => $formatted_expiry,
        'cvd'         => $card_data['cvv'],
        'postal_code' => $postal_code
    );
    
    // Call verify_card_ex and use its return value
    $verify_result = verify_card_ex($payment_info);
    
    if ($verify_result['status'] === 'failed') {
        wp_send_json_error(array(
            'message' => $verify_result['msg'],
            'field'   => $verify_result['field'],
            'code'    => isset($verify_result['code']) ? $verify_result['code'] : '',
        ));
        return;
    }
    
    // Verification passed
    wp_send_json_success(array('message' => 'Card is valid'));
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
    $payafter_product_id = 267989; // Your Pay After product ID
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


/**
 * Enqueue shipping address JavaScript 
 */
function enqueue_shipping_address_scripts() {
    // Only load on checkout page
    if (is_checkout()) {
        // Enqueue JavaScript
        wp_enqueue_script(
            'shipping-address',
            get_stylesheet_directory_uri() . '/js/shipping-address.js',
            array('jquery'),
            '1.0.0',
            true
        );

        // Pass necessary data to JavaScript
        wp_localize_script('shipping-address', 'shippingAddressVars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('shipping_address_nonce')
        ));
    }
}
add_action('wp_enqueue_scripts', 'enqueue_shipping_address_scripts');


/**
 * Shipping Address Checkbox Shortcode
 * Usage: [shipping_address_checkbox]
 */
function shipping_address_checkbox_shortcode() {
    ob_start();
    ?>
    <div class="shipping-address-checkbox-container">
        <label class="shipping-address-checkbox-label">
            <input type="checkbox" id="ship-to-different-checkbox" name="ship_to_different_address">
            <span>Ship to a Different Address?</span>
        </label>
        
        <div class="shipping-address-wrapper" style="display: none;">
            <div class="shipping-address-field">
                <label for="shipping-address-input">Shipping Address</label>
                <input 
                    type="text" 
                    id="shipping-address-input" 
                    name="shipping_address_input"
                    placeholder="Start typing your address..."
                    autocomplete="off"
                >
                 <input type="hidden" id="shipping-address-full" name="shipping_address_full">
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('shipping_address_checkbox', 'shipping_address_checkbox_shortcode');


/**
 * Special Shipping Instructions Textarea Shortcode
 * Usage: [special_shipping_instructions]
 */
function special_shipping_instructions_shortcode() {
    ob_start();
    ?>
    <div class="special-shipping-instructions-container">
        <div class="special-shipping-instructions-field">
            <label for="special-shipping-instructions-input">
                Special Shipping Instructions
                <span class="optional" style="font-weight: normal; color: #666;"> (optional)</span>
            </label>
            <textarea
                id="special-shipping-instructions-input"
                name="special_shipping_instructions"
                placeholder="Any specific instructions for your order delivery or installation..."
                rows="4"
                maxlength="500"
                style="width: 100%; box-sizing: border-box; resize: vertical;"
            ></textarea>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('special_shipping_instructions', 'special_shipping_instructions_shortcode');


// ----- 4 Updated Enqueueing Functions for monthly_billing_assets, confirm_terms_script, mneris_payment_assets and checkout_cc_copy_script

// ===== UPDATED: Enqueue monthly-billing.js file with proper versioning
function enqueue_monthly_billing_assets() {
    // Only load on checkout page or pages with monthly billing functionality
    if (is_checkout() || 
        is_page(267950) || // Your checkout page ID
        (is_singular() && has_shortcode(get_post()->post_content, 'monthly_billing_section'))) {
        
        wp_enqueue_script(
            'monthly-billing-js',
            get_stylesheet_directory_uri() . '/js/monthly-billing.js',
            array('jquery'),
            '1.1.0', // Updated version for cache busting
            true
        );
        
        // Localize script with AJAX data
        wp_localize_script('monthly-billing-js', 'monthlyBilling', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('checkout_nonce'),
            'checkoutNonce' => wp_create_nonce('checkout_nonce')
        ));
    }
}
add_action('wp_enqueue_scripts', 'enqueue_monthly_billing_assets');

// ===== UPDATED: Enqueue confirm-terms.js file with proper dependencies

function enqueue_confirm_terms_script() {
    // Only load on checkout page or pages with Moneris payment shortcodes
    if (is_checkout() || 
        is_page(267950) || // Your checkout page ID
        (is_singular() && (
            has_shortcode(get_post()->post_content, 'moneris_payment_form') ||
            has_shortcode(get_post()->post_content, 'moneris_complete_payment_button')
        ))) {
        
        wp_enqueue_script(
            'confirm-terms-js',
            get_stylesheet_directory_uri() . '/js/confirm-terms.js',
            array('jquery', 'monthly-billing-js', 'customer-info-confirm-js'), // UPDATED: Add monthly-billing-js as dependency
            '1.2.1', // Updated version for cache busting
            true
        );
        
        // Localize script with validation data
        wp_localize_script('confirm-terms-js', 'confirmTerms', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('checkout_nonce'),
            'checkoutPageId' => 267950,
            'validationEnabled' => true
        ));
    }
}
add_action('wp_enqueue_scripts', 'enqueue_confirm_terms_script');

/**
 * UPDATED: Enqueue payment gateway scripts with proper dependencies
 */
function enqueue_moneris_payment_assets() {
    if (is_checkout() || 
        has_shortcode(get_post()->post_content, 'moneris_payment_form') ||
        has_shortcode(get_post()->post_content, 'moneris_complete_payment_button')) {
        
        wp_enqueue_script(
            'moneris-payment-js',
            get_stylesheet_directory_uri() . '/js/moneris-payment.js',
            array('jquery', 'confirm-terms-js'), // UPDATED: Add confirm-terms-js as dependency
            '1.1.1', // Updated version for cache busting
            true
        );
        
        wp_localize_script('moneris-payment-js', 'monerisPayment', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('moneris_payment_nonce')
        ));
    }
}
// NOTE: Make sure this runs after the confirm-terms script is enqueued
add_action('wp_enqueue_scripts', 'enqueue_moneris_payment_assets', 15);

// ===== UPDATED: Enqueue checkout-cc-copy.js file to copy credit card credentials
function enqueue_checkout_cc_copy_script() {
    if (is_checkout() || is_page()) {
        wp_enqueue_script(
            'checkout-cc-copy',
            get_stylesheet_directory_uri() . '/js/checkout-cc-copy.js',
            array('jquery'),
            '1.0.1', // Updated version
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_checkout_cc_copy_script');


/**
 * Dynamic Terms and Conditions - PHP Logic Only
 */
/**
 * Enqueue provider terms script and pass data to JavaScript
 */
function enqueue_provider_terms_script() {
    // Only load on checkout page
    if (!is_checkout()) {
        return;
    }
    
    // Enqueue the JavaScript file
    wp_enqueue_script(
        'provider-terms-js',
        get_stylesheet_directory_uri() . '/js/provider-terms.js',
        array('jquery'),
        '1.0.0',
        true
    );
    
    // Get the provider class to show and pass it to JavaScript
    $provider_class = get_current_provider_terms_class();
    
    // Localize script to pass PHP data to JavaScript
    wp_localize_script('provider-terms-js', 'providerTermsData', array(
        'providerClass' => $provider_class,
        'isCheckout' => is_checkout()
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_provider_terms_script');



/**
 * Get the CSS class for the current provider terms module
 */
function get_current_provider_terms_class() {
    // Check if cart is empty
    if (WC()->cart->is_empty()) {
        return '';
    }
    
    // Internet plan category ID
    $internet_plan_category_id = 19;
    
    // Provider category IDs mapping to CSS classes
    $provider_categories = array(
        62 => 'bell-terms-module', // Bell
        67 => 'bell-fttp-terms-module',  // Bell-FTTP
        64 => 'rogers-terms-module',    // Rogers
        63 => 'cogeco-terms-module',    // Cogeco
        66 => 'telus-terms-module',     // Telus
        65 => 'shaw-terms-module'       // Shaw
    );
    
    // Loop through cart items to find internet plan
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $_product = $cart_item['data'];
        
        // Skip if product doesn't exist
        if (!$_product || !$_product->exists()) {
            continue;
        }
        
        // Get product category IDs
        $product_cat_ids = $_product->get_category_ids();
        
        // Check if this product is an internet plan (has category ID 19)
        if (in_array($internet_plan_category_id, $product_cat_ids)) {
            
            // Check which provider category this internet plan has
            foreach ($provider_categories as $provider_cat_id => $css_class) {
                if (in_array($provider_cat_id, $product_cat_ids)) {
                    return $css_class;
                }
            }
        }
    }
    
    return ''; // No provider found
}


/**
 * Optional: AJAX handler for dynamic updates if cart changes
 * You can call this via AJAX if you need real-time updates
 */
function ajax_get_provider_terms_class() {
    $provider_class = get_current_provider_terms_class();
    
    wp_send_json_success(array(
        'providerClass' => $provider_class
    ));
}
add_action('wp_ajax_get_provider_terms_class', 'ajax_get_provider_terms_class');
add_action('wp_ajax_nopriv_get_provider_terms_class', 'ajax_get_provider_terms_class');


/**
 * AJAX HANDLER - Save shipping address to session BEFORE payment
 * This saves the shipping address to the session when the user submits checkout,
 * BEFORE the payment is processed, so it's available on the Thank You page.
 */

// AJAX handler for saving shipping address to session
add_action('wp_ajax_save_shipping_to_session', 'ajax_save_shipping_to_session');
add_action('wp_ajax_nopriv_save_shipping_to_session', 'ajax_save_shipping_to_session');

function ajax_save_shipping_to_session() {
    error_log('');
    error_log('========================================');
    error_log('=== AJAX: SAVE SHIPPING TO SESSION ===');
    error_log('========================================');
    error_log('Time: ' . date('Y-m-d H:i:s'));
    
    // Check nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'shipping_address_nonce')) {
        error_log('✗ Security check failed');
        wp_send_json_error(array('message' => 'Security check failed'));
        return;
    }
    
    if (!WC()->session) {
        error_log('✗ WooCommerce session not available');
        wp_send_json_error(array('message' => 'Session not available'));
        return;
    }
    
    // Get data from AJAX request
    $ship_to_different = isset($_POST['ship_to_different']) && $_POST['ship_to_different'] === 'true';
    $shipping_address = isset($_POST['shipping_address']) ? sanitize_text_field($_POST['shipping_address']) : '';
    
    error_log('Ship to different: ' . ($ship_to_different ? 'TRUE' : 'FALSE'));
    error_log('Shipping address: "' . $shipping_address . '"');
    
    // Save to session
    WC()->session->set('ship_to_different_address', $ship_to_different);
    
    if ($ship_to_different && !empty($shipping_address)) {
        WC()->session->set('custom_shipping_address_full', $shipping_address);
        error_log('✓ Saved custom shipping to session: "' . $shipping_address . '"');
    } else {
        WC()->session->set('custom_shipping_address_full', '');
        error_log('✓ Cleared custom shipping (using service address)');
    }
    
    // Verify it was saved
    $saved_checkbox = WC()->session->get('ship_to_different_address');
    $saved_address = WC()->session->get('custom_shipping_address_full');
    
    error_log('');
    error_log('--- VERIFICATION ---');
    error_log('Retrieved ship_to_different: ' . ($saved_checkbox ? 'TRUE' : 'FALSE'));
    error_log('Retrieved custom_shipping: "' . $saved_address . '"');
    
    error_log('========================================');
    error_log('=== END AJAX SAVE ===');
    error_log('========================================');
    error_log('');
    
    wp_send_json_success(array(
        'message' => 'Shipping address saved to session',
        'ship_to_different' => $saved_checkbox,
        'address' => $saved_address
    ));
}


// Add Filter to Exclude Pay Later Product From Being Added to Monthly Summary

add_filter('woocommerce_cart_item_visible', 'exclude_payafter_from_monthly_summary', 10, 3);

function exclude_payafter_from_monthly_summary($visible, $cart_item, $cart_item_key) {
    // Check if we're in the context of monthly fee calculation
    if (did_action('monthly_fee_summary_calculation')) {
        $product_id = $cart_item['product_id'];
        
        // Exclude the Pay After deposit product from monthly calculations
        if ($product_id == 267989) {
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



/*========Auto Fill Checkout Fields with previously collected order data======*/

// Auto-fill checkout fields with previously collected address data
add_filter('woocommerce_checkout_get_value', 'auto_fill_checkout_from_address_lookup', 10, 2);
function auto_fill_checkout_from_address_lookup($value, $key) {
    
    // Get the stored address data from your initial lookup
    $apiResponse = dg_get_user_meta("_api_response");
    $searched_address = dg_get_user_meta("searched_address");
    
    // If no stored address data, return the original value
    if (empty($searched_address) && empty($apiResponse)) {
        return $value;
    }
    
    // Map the checkout fields to your stored address data
    switch($key) {
        case 'billing_address_1':
            // Build full street address from components - UPDATED TO NEW KEY NAMES
            $street_num = isset($searched_address['street_number']) ? $searched_address['street_number'] : '';
            $street_name = isset($searched_address['street_name']) ? $searched_address['street_name'] : '';
            $street_dir = isset($searched_address['street_dir']) ? $searched_address['street_dir'] : '';
            $street_type = isset($searched_address['street_type']) ? $searched_address['street_type'] : '';
            $unit_num = isset($searched_address['unit_number']) ? $searched_address['unit_number'] : '';
            
            $address = trim($street_num . ' ' . $street_name . ' ' . $street_dir . ' ' . $street_type);
            if (!empty($unit_num)) {
                $address = 'Unit ' . $unit_num . ', ' . $address;
            }
            return !empty($address) ? $address : $value;
            
        case 'billing_city':
            return isset($searched_address['locality']) ? $searched_address['locality'] : $value;
    
        case 'billing_postcode':
            return isset($searched_address['postal_code']) ? $searched_address['postal_code'] : $value;
    
        case 'billing_state':
            return isset($searched_address['administrative_area_level_1']) ? $searched_address['administrative_area_level_1'] : $value;
            
        // Also fill shipping fields if they exist - UPDATED TO NEW KEY NAMES
        case 'shipping_address_1':
            $street_num = isset($searched_address['street_number']) ? $searched_address['street_number'] : '';
            $street_name = isset($searched_address['street_name']) ? $searched_address['street_name'] : '';
            $street_dir = isset($searched_address['street_dir']) ? $searched_address['street_dir'] : '';
            $street_type = isset($searched_address['street_type']) ? $searched_address['street_type'] : '';
            $unit_num = isset($searched_address['unit_number']) ? $searched_address['unit_number'] : '';
            
            $address = trim($street_num . ' ' . $street_name . ' ' . $street_dir . ' ' . $street_type);
            if (!empty($unit_num)) {
                $address = 'Unit ' . $unit_num . ', ' . $address;
            }
            return !empty($address) ? $address : $value;
            
        case 'shipping_city':
            return isset($searched_address['locality']) ? $searched_address['locality'] : $value;
            
        case 'shipping_postcode':
            return isset($searched_address['postal_code']) ? $searched_address['postal_code'] : $value;
            
        case 'shipping_state':
            return isset($searched_address['administrative_area_level_1']) ? $searched_address['administrative_area_level_1'] : $value;
    }
    
    return $value;
}


// Hide the address fields since they're now auto-populated

add_filter('woocommerce_checkout_fields', 'hide_auto_filled_address_fields', 99);
function hide_auto_filled_address_fields($fields) {

    // Always strip form-row-wide and hide auto-filled address fields
    // regardless of whether searched_address is populated.
    if (isset($fields['billing']['billing_address_1'])) {
        $fields['billing']['billing_address_1']['class'] = array('auto-filled-field');
        $fields['billing']['billing_address_1']['custom_attributes']['style'] = 'display:none;';
    }
    if (isset($fields['billing']['billing_city'])) {
        $fields['billing']['billing_city']['class'] = array('auto-filled-field');
        $fields['billing']['billing_city']['custom_attributes']['style'] = 'displafy:none;';
    }
    if (isset($fields['billing']['billing_postcode'])) {
        $fields['billing']['billing_postcode']['class'] = array('auto-filled-field');
        $fields['billing']['billing_postcode']['custom_attributes']['style'] = 'display:none;';
    }
    if (isset($fields['billing']['billing_state'])) {
        $fields['billing']['billing_state']['class'] = array('auto-filled-field');
        $fields['billing']['billing_state']['custom_attributes']['style'] = 'display:none;';
    }

	if (isset($fields['billing']['billing_country'])) {
    $fields['billing']['billing_country']['class'] = array('auto-filled-field');
    $fields['billing']['billing_country']['custom_attributes']['style'] = 'display:none;';
}

    if (isset($fields['shipping']['shipping_address_1'])) {
        $fields['shipping']['shipping_address_1']['class'] = array('auto-filled-field');
        $fields['shipping']['shipping_address_1']['custom_attributes']['style'] = 'display:none;';
    }
    if (isset($fields['shipping']['shipping_city'])) {
        $fields['shipping']['shipping_city']['class'] = array('auto-filled-field');
        $fields['shipping']['shipping_city']['custom_attributes']['style'] = 'display:none;';
    }
    if (isset($fields['shipping']['shipping_postcode'])) {
        $fields['shipping']['shipping_postcode']['class'] = array('auto-filled-field');
        $fields['shipping']['shipping_postcode']['custom_attributes']['style'] = 'display:none;';
    }
    if (isset($fields['shipping']['shipping_state'])) {
        $fields['shipping']['shipping_state']['class'] = array('auto-filled-field');
        $fields['shipping']['shipping_state']['custom_attributes']['style'] = 'display:none;';
    }

    return $fields;
}

/**
 * Moneris Payment Gateway Functions for functions.php
 */

// Moneris Test Mode Control - Change this to switch between test and production
function is_moneris_test_mode() {
    return false; // Set to true for test mode, false for live transactions
}

// Moneris Account Configuration
function get_moneris_config() {
    if (is_moneris_test_mode()) {
        // TEST ENVIRONMENT CREDENTIALS
        return array(
            'store_id' => 'store5',     // test store ID 
            'api_token' => 'yesguy',   // test API token
            'test_mode' => true
        );
    } else {
        // PRODUCTION ENVIRONMENT CREDENTIALS (from existing processpayment.php)
        return array(
             'store_id' => 'monca07419',             
            'api_token' => 'G5F7TE7rzrM21OVzkCQD',  
            'test_mode' => false
        );
    }
}


/**
 *================================== UPDATED: Moneris Payment Form Shortcode (WITHOUT submit button)
 */

function moneris_payment_form_shortcode($atts) {

	// Guard: WC cart is not available in admin/REST context (e.g. Divi backend editor)
    if ( is_null( WC()->cart ) ) {
        return '';
    }
	
    $atts = shortcode_atts(array(
        'show_amount' => 'true',
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
                <h3>Amount Due Today: <?php echo $total_display; ?></h3>
                <p>If you do not have access to a credit card and would like to send a payment via e-transfer please email us @ <a href="mailto:customercare@diallog.com">customercare@diallog.com</a> and we will assist in completing your order.</p>
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
                <label for="moneris_postal_code">Billing Postal Code <span style="color:red;">*</span></label>
                <input type="text" id="moneris_postal_code" name="postal_code" 
                       placeholder="A1A 1A1" required maxlength="7">
                       <small class="field-help-text">Enter the postal code from your credit card billing statement</small>
            </div>
            
            <div class="moneris-loading" style="display: none;">
                <p>Processing payment, please wait...</p>
            </div>
            
            <!-- REMOVED: Submit button is now in separate shortcode -->
            
            <input type="hidden" name="action" value="process_moneris_payment">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('moneris_payment_nonce'); ?>">
            <input type="hidden" name="success_message" value="<?php echo esc_attr($atts['success_message']); ?>">
        </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('moneris_payment_form', 'moneris_payment_form_shortcode');


/**
 * ===================== NEW: Moneris Complete Payment Button Shortcode
 */
 
function moneris_complete_payment_button_shortcode($atts) {
    $atts = shortcode_atts(array(
        'button_text' => 'Complete Payment',
        'redirect_url' => '',
        'button_class' => 'moneris-submit-btn',
        'show_validation' => 'true', // Control validation message display
        'button_id' => 'moneris-complete-payment-btn' // NEW: Allow custom button ID
    ), $atts);
    
    // Convert show_validation to boolean
    $show_validation = filter_var($atts['show_validation'], FILTER_VALIDATE_BOOLEAN);
    
    ob_start();
    ?>
    <div class="moneris-complete-payment-container" data-show-validation="<?php echo $show_validation ? 'true' : 'false'; ?>">
        <button type="button" id="<?php echo esc_attr($atts['button_id']); ?>" 
                class="<?php echo esc_attr($atts['button_class']); ?> moneris-payment-button" 
                data-redirect-url="<?php echo esc_url($atts['redirect_url']); ?>">
            <?php echo esc_html($atts['button_text']); ?>
        </button>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('moneris_complete_payment_button', 'moneris_complete_payment_button_shortcode');


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
    $timestamp = substr(time(), -6);
    $order_id = ($moneris_config['test_mode'] ? 'test-' : 'web-') . $timestamp . '-' . wp_rand(100, 999);

    // Store order_id in session for Thank You page
    if (WC()->session) {
        WC()->session->set('payment_order_id', $order_id);
    }
    
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
            $verify_data['amount'] = '0.01';
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
                
                // ========== CRITICAL FIX STARTS HERE ==========
                error_log('=== PAYMENT SUCCESS - STORING ALL DATA BEFORE CART IS EMPTIED ===');
                
                // STEP 1: Store detailed cart items FIRST (CRITICAL - must be before cart is emptied)
                error_log('Step 1: Storing detailed cart summaries for thank you page...');

                // Initialize variables that will be used later
                $stored_upfront = array();
                $stored_monthly = array();
                
                // Use new helper functions to get itemized cart data
                if (function_exists('get_upfront_summary_for_thank_you')) {
                    $stored_upfront = get_upfront_summary_for_thank_you();
                    WC()->session->set('stored_upfront_summary', $stored_upfront);
                    error_log('Upfront summary stored (itemized): ' . json_encode($stored_upfront));
                } elseif (function_exists('get_upfront_fee_summary_with_deposits')) {
                    $stored_upfront = get_upfront_fee_summary_with_deposits();
                    WC()->session->set('stored_upfront_summary', $stored_upfront);
                    error_log('Upfront summary stored with deposits: ' . json_encode($stored_upfront));
                } elseif (function_exists('get_upfront_fee_summary')) {
                    $stored_upfront = get_upfront_fee_summary();
                    WC()->session->set('stored_upfront_summary', $stored_upfront);
                    error_log('Upfront summary stored (fallback): ' . json_encode($stored_upfront));
                } else {
                    error_log('WARNING: No upfront summary function available!');
                }
                
                // Store monthly summary with new helper function
                if (function_exists('get_monthly_summary_for_thank_you')) {
                    $stored_monthly = get_monthly_summary_for_thank_you();
                    WC()->session->set('stored_monthly_summary', $stored_monthly);
                    error_log('Monthly summary stored (itemized): ' . json_encode($stored_monthly));
                } elseif (function_exists('get_monthly_fee_summary')) {
                    $stored_monthly = get_monthly_fee_summary();
                    WC()->session->set('stored_monthly_summary', $stored_monthly);
                    error_log('Monthly summary stored: ' . json_encode($stored_monthly));
                } else {
                    error_log('WARNING: No monthly summary function available!');
                }
                
                // STEP 2: Store payment details in session
                error_log('Step 2: Storing payment details...');
                
                if (WC()->session) {
                    WC()->session->set('payment_status', 'completed');
                    WC()->session->set('payment_transaction_id', $payment_response->getTxnNumber());
                    WC()->session->set('payment_auth_code', $payment_response->getAuthCode());
                    WC()->session->set('payment_reference_num', $payment_response->getReferenceNum());
                    WC()->session->set('payment_receipt_id', $payment_response->getReceiptId());
                    WC()->session->set('payment_amount', $payment_response->getTransAmount());
                    WC()->session->set('payment_date', $payment_response->getTransDate());
                    WC()->session->set('order_complete_timestamp', time());
                    WC()->session->set('cardholder_name', $cardholder_name);
                    WC()->session->set('payment_test_mode', $moneris_config['test_mode']);
                    WC()->session->set('payment_card_last_4', substr($clean_card_number, -4));
                    error_log('Payment details stored in session');
                }
                

                // STEP 3: Get terms timestamp - read directly from POST data to avoid guest session isolation issues
error_log('Step 3: Getting terms timestamp...');

$terms_timestamp = '';
if (!empty($_POST['terms_timestamp'])) {
    $terms_timestamp = sanitize_text_field($_POST['terms_timestamp']);
    error_log('Terms timestamp from POST: ' . $terms_timestamp);
    // Also write to WC session so dg_get_thank_you_page_data() can read it
    if (WC()->session) {
        WC()->session->set('terms_timestamp', $terms_timestamp);
        error_log('Terms timestamp saved to WC session for thank you page');
    }
} else {
    // Fallback to session in case POST value is missing
    if (WC()->session) {
        $terms_timestamp = WC()->session->get('terms_timestamp');
    }
    if ($terms_timestamp) {
        error_log('Terms timestamp from WC session (fallback): ' . $terms_timestamp);
    } else {
        error_log('WARNING: No terms timestamp in POST or session');
    }
}

                // STEP 4: Prepare order data for Diallog
                error_log('Step 4: Preparing order data for Diallog...');
                
                $order_data = prepare_diallog_order_data($payment_response, $cardholder_name, $moneris_config);
                error_log('Order data prepared');
                
                // STEP 5: Send to Diallog database
error_log('Step 5: Sending to Diallog database...');

$diallog_response = 'skipped';

    // NEW: Allow Diallog submission in Moneris test mode
// Set this to true when you want to send test orders to Diallog
$send_to_diallog_in_test_mode = true; // Change to false to skip Diallog in test mode

if (!$moneris_config['test_mode'] || $send_to_diallog_in_test_mode) {
    $diallog_response = send_order_to_diallog($order_data);
    
    if ($moneris_config['test_mode']) {
        error_log('TEST MODE: Order sent to Diallog (test transaction): ' . $diallog_response);
    } else {
        error_log('PRODUCTION: Order sent to Diallog: ' . $diallog_response);
    }
} else {
    error_log('Test mode: Skipping Diallog submission (send_to_diallog_in_test_mode = false)');
}
                
               // STEP 6: Send custom order confirmation email
error_log('Step 6: Sending customer order confirmation email...');

// Build the order data structure that transform_order_data_for_email expects
$diallog_order_data = array(
    'upfront_summary' => isset($stored_upfront) ? $stored_upfront : array(),
    'monthly_summary' => isset($stored_monthly) ? $stored_monthly : array(),
    'order_timestamp' => time(),
    'terms_acceptance_timestamp' => $terms_timestamp
);

// Transform the order data to the format expected by email template
$email_order_data = transform_order_data_for_email($diallog_order_data);

// Extract customer email
$customer_email = isset($email_order_data['customer_email']) ? $email_order_data['customer_email'] : '';
error_log('CUSTOMER EMAIL FROM ORDER DATA: "' . $customer_email . '"');

if (!empty($customer_email)) {
    // Handle multiple emails (comma-separated) or single email
    $email_addresses = array_map('trim', explode(',', $customer_email));
    $valid_emails = array_filter($email_addresses, 'is_email');
    
    if (!empty($valid_emails)) {
        // Rejoin valid emails back into comma-separated string for wp_mail
        $customer_email = implode(',', $valid_emails);
        
        // Send the order confirmation email using our custom template
        $email_sent = send_customer_order_confirmation_email($customer_email, $email_order_data);
        
        if ($email_sent) {
            error_log('Order confirmation email sent successfully to: ' . $customer_email);
        } else {
            error_log('Failed to send order confirmation email to: ' . $customer_email);
        }
    } else {
        error_log('No valid email addresses found. Cannot send confirmation email.');
    }
} else {
    error_log('Invalid or missing customer email. Cannot send confirmation email.');
}
                
                // STEP 7: Empty cart ONLY AFTER all data has been captured
                error_log('Step 7: Emptying cart...');
                
                if (function_exists('WC') && WC()->cart) {
                    WC()->cart->empty_cart();
                    error_log('Cart emptied successfully');
                }
                
                error_log('=== ALL DATA STORED AND CART EMPTIED SUCCESSFULLY ===');
                // ========== CRITICAL FIX ENDS HERE ==========
                
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
 * Format upfront and monthly summaries for Diallog
 * Shared formatting logic used by both state 50 and state 100 API calls
 * Returns array with formatted upfront_summary and monthly_summary
 */

function format_summaries_for_diallog() {
    error_log('FORMAT SUMMARIES - UPDATED VERSION WITH HELPER FUNCTION');
    // Get raw summaries
    $upfront_summary = function_exists('get_upfront_fee_summary') ? get_upfront_fee_summary() : array();
    $monthly_summary = function_exists('get_monthly_fee_summary') ? get_monthly_fee_summary() : array();

    // TRANSFORM UPFRONT SUMMARY
    // Remove unnecessary fields
    unset($upfront_summary['ModemPurchaseOption']);
    unset($upfront_summary['internet-plan']);
    unset($upfront_summary['deposit']); // Remove total deposits line

    // Get installation dates from cart
    $installation_dates = array(
        'preferred' => '',
        'secondary' => ''
    );
    
    // NEW: Also get internet plan ID for dynamic install pricing
    $internet_plan_id = null;
    
    foreach (WC()->cart->get_cart() as $cart_item) {
        $product = $cart_item['data'];
        
        // Check if this is THE installation variation (ID: 267988)
        if ($product->get_id() == 267988) {
            error_log('Found installation variation 267988 in cart');
            
            // Get dates from variation attributes
            if (isset($cart_item['variation']) && is_array($cart_item['variation'])) {
                $installation_dates['preferred'] = isset($cart_item['variation']['attribute_preferred-date']) ? 
                    $cart_item['variation']['attribute_preferred-date'] : '';
                $installation_dates['secondary'] = isset($cart_item['variation']['attribute_secondary-date']) ? 
                    $cart_item['variation']['attribute_secondary-date'] : '';
                
                error_log('Extracted dates - Preferred: ' . $installation_dates['preferred'] . ', Secondary: ' . $installation_dates['secondary']);
            }
        }
        
        // NEW: Also check for internet plan to get dynamic install price
        $product_cat_ids = $product->get_category_ids();
        if (!empty($product_cat_ids) && in_array(19, $product_cat_ids)) {
            $internet_plan_id = $product->get_id();
            error_log('Found internet plan in cart for Diallog: ' . $internet_plan_id);
        }
    }
    
    // Process installation with dynamic pricing
    if (isset($upfront_summary['installation'])) {
        // Check if we have promotional pricing info from get_upfront_fee_summary
        $has_dynamic_pricing = isset($upfront_summary['installation'][3]) && isset($upfront_summary['installation'][4]);
        
        if ($has_dynamic_pricing) {
            // Has dynamic pricing - show original price as "Price" and dynamic as "Dynamic Sale Price"
            $original_price = $upfront_summary['installation'][3];
            $sale_price = $upfront_summary['installation'][4];
            
            $upfront_summary['installation'] = array(
                'Title' => $upfront_summary['installation'][0],
                'Price' => $original_price  // Original price ($88.50)
            );
            
            // Add installation dates
            if (!empty($installation_dates['preferred'])) {
                $upfront_summary['installation']['Preferred Installation Date'] = $installation_dates['preferred'];
            }
            if (!empty($installation_dates['secondary'])) {
                $upfront_summary['installation']['Secondary Installation Date'] = $installation_dates['secondary'];
            }
            
            // Add dynamic sale price at the end
            $upfront_summary['installation']['Dynamic Sale Price'] = $sale_price;
            
            error_log('Diallog API - Installation with dynamic pricing: Original=' . $original_price . ', Sale=' . $sale_price);
            
        } else {
            // No dynamic pricing - regular format
            $upfront_summary['installation'] = array(
                'Title' => $upfront_summary['installation'][0],
                'Price' => $upfront_summary['installation'][1]
            );
            
            // Add installation dates
            if (!empty($installation_dates['preferred'])) {
                $upfront_summary['installation']['Preferred Installation Date'] = $installation_dates['preferred'];
            }
            if (!empty($installation_dates['secondary'])) {
                $upfront_summary['installation']['Secondary Installation Date'] = $installation_dates['secondary'];
            }
            
            error_log('Diallog API - Installation without dynamic pricing: Price=' . $upfront_summary['installation']['Price']);
        }
    }

// Convert upfront items to objects and add ACF deposit info
foreach ($upfront_summary as $key => $value) {
    error_log("FORMAT DIALLOG - Processing upfront key: " . $key);
    
    // Skip totals/subtotals and installation (already processed above)
    if (in_array($key, array('subtotal', 'taxes', 'grand_total', 'total-deposits', 'installation'))) {
        error_log("FORMAT DIALLOG - Skipping key: " . $key);
        continue;
    }

    error_log("FORMAT DIALLOG - Looking for cart product matching: " . $key);
    
    // Get product from cart for this category
    foreach (WC()->cart->get_cart() as $cart_item) {
        $product = $cart_item['data'];
        $product_cat_ids = $product->get_category_ids();
        
        error_log("FORMAT DIALLOG - Checking product: " . $product->get_name() . " (ID: " . $product->get_id() . ")");
        
        if (empty($product_cat_ids)) {
            error_log("FORMAT DIALLOG - Product has no categories, skipping");
            continue;
        }
        
        // Find matching category by iterating all category IDs
        $product_category = null;
        foreach ($product_cat_ids as $cat_id) {
            $product_cat = get_term($cat_id, 'product_cat');
            if (is_wp_error($product_cat)) {
                continue;
            }
            $cat_slug = $product_cat->slug;
            if ($cat_slug == 'modems-new') {
                $cat_slug = 'modems';
            }
            if ($cat_slug === $key) {
                $product_category = $cat_slug;
                break;
            }
        }
        
        error_log("FORMAT DIALLOG - Primary category detected: " . ($product_category ? $product_category : 'NULL'));
        
        if ($product_category === null) {
            error_log("FORMAT DIALLOG - Primary category is null, skipping product");
            continue;
        }
        
        // If this product matches current summary key
        if ($product_category === $key) {
            error_log("FORMAT DIALLOG - MATCH FOUND! Converting $key to object format");
            
            $product_id = $product->get_id();
            
            // Convert to object
            $original_name = $upfront_summary[$key][0];
            $original_price = ($key === 'modems') ? round(floatval($product->get_regular_price()), 2) : $upfront_summary[$key][1];
            
            $upfront_summary[$key] = array(
                'Title' => $original_name,
                'Price' => $original_price
            );
            
            error_log("FORMAT DIALLOG - Converted structure: " . json_encode($upfront_summary[$key]));
            
           // Add ACF deposit fields
            if (function_exists('get_field')) {
                $deposit_title = get_field('deposit-title', $product_id);
                $deposit_fee = get_field('deposit-fee', $product_id);
                
                if (!empty($deposit_title) || !empty($deposit_fee)) {
                    $upfront_summary[$key]['Deposit Title'] = $deposit_title ? $deposit_title : '';
                    $upfront_summary[$key]['Deposit Amount'] = $deposit_fee ? floatval($deposit_fee) : 0;
                    error_log("FORMAT DIALLOG - Added deposit fields: Title=" . $deposit_title . ", Amount=" . $deposit_fee);
                }
				
            }

            // Add modem make & model for "I Have My Own Modem"
            if ($key === 'modems' && $product_id == 267979) {
                if (isset($cart_item['modem_details']) && !empty($cart_item['modem_details'])) {
                    $upfront_summary[$key]['Modem Make & Model'] = $cart_item['modem_details'];
                }
            }
            break;
        } else {
            error_log("FORMAT DIALLOG - No match: $product_category !== $key");
        }
    }
}

    // Add Pay After Deposit if selected
    $monthly_method = dg_get_user_meta('monthly_bill_payment_option');
    if ($monthly_method === 'payafter') {
        $payafter_product_id = 267989;
        
        $deposit_title = '';
        $deposit_fee = 0;
        
        if (function_exists('get_field')) {
            $deposit_title = get_field('deposit-title', $payafter_product_id);
            $deposit_fee = get_field('deposit-fee', $payafter_product_id);
        }
        
        if (empty($deposit_title)) {
            $deposit_title = 'Pay After Deposit';
        }
        if (empty($deposit_fee)) {
            $deposit_fee = 200;
        }
        
        $reordered = array();
        foreach ($upfront_summary as $key => $value) {
            if ($key === 'subtotal') {
                $reordered['deposit'] = array(
                    'Title' => $deposit_title,
                    'Amount' => floatval($deposit_fee)
                );
            }
            $reordered[$key] = $value;
        }
        $upfront_summary = $reordered;
    }

    // TRANSFORM MONTHLY SUMMARY
    $tax_rate = function_exists('GetTaxRate') ? GetTaxRate() : 0;
    $monthly_subtotal = 0;

    foreach (WC()->cart->get_cart() as $cart_item) {
        $product = $cart_item['data'];
        $product_cat_ids = $product->get_category_ids();

        if (empty($product_cat_ids)) {
            continue;
        }
        
        // Find matching category
        $product_category = null;
        foreach ($product_cat_ids as $cat_id) {
            $product_cat = get_term($cat_id, 'product_cat');
            if (is_wp_error($product_cat)) {
                continue;
            }
            
            $cat_slug = $product_cat->slug;
            if ($cat_slug == 'modems-new') {
                $cat_slug = 'modems';
            }
            
            if (isset($monthly_summary[$cat_slug])) {
                $product_category = $cat_slug;
                break;
            }
        }

        if ($product_category === null) {
            continue;
        }

        $product_id = $product->get_id();

        // Get monthly_fee from ACF
        $monthly_fee = 0;
        if (function_exists('get_field')) {
            $monthly_fee = get_field('monthly_fee', $product_id);
            $monthly_fee = is_numeric($monthly_fee) ? floatval($monthly_fee) : 0;
        }
        
        // Update summary as object
        $monthly_summary[$product_category] = array(
            'Title' => $product->get_name(),
            'Monthly Fee' => round($monthly_fee, 2)
        );

        // Add ID attribute for certain categories
        if (in_array($product_category, array('internet-plan', 'phone-plan', 'modems', 'tv-plan'))) {
            $plan_id = $product->get_attribute('id');
            if (!empty($plan_id)) {
                $monthly_summary[$product_category]['ID Attribute'] = $plan_id;
            }
        }

        // Special handling for "I Have My Own Modem"
        if ($product_category === 'modems' && $product_id == 267979) {
            if (isset($cart_item['modem_details']) && !empty($cart_item['modem_details'])) {
                $monthly_summary[$product_category]['Modem Make & Model'] = $cart_item['modem_details'];
            }
        }

        // Add promotional pricing
        if (function_exists('get_field')) {
            $promo_blurb = get_field('monthly_promo_blurb', $product_id);
            $promo_fee_raw = get_field('monthly_promo_fee', $product_id);
            $promo_is_set = is_numeric($promo_fee_raw);
            $promo_fee = $promo_is_set ? floatval($promo_fee_raw) : null;
            
            if (!empty($promo_blurb) || $promo_is_set) {
                $monthly_summary[$product_category]['Promotional Blurb'] = $promo_blurb ? $promo_blurb : '';
                $monthly_summary[$product_category]['Promotional Price'] = $promo_is_set ? round($promo_fee, 2) : 0;
                $monthly_subtotal += $promo_is_set ? $promo_fee : $monthly_fee;
            } else {
                $monthly_subtotal += $monthly_fee;
            }
        } else {
            $monthly_subtotal += $monthly_fee;
        }
    }

    // Add tv-plan if not in summary but exists in cart
    if (!isset($monthly_summary['tv-plan'])) {
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            $product_cat_ids = $product->get_category_ids();
            
            if (!empty($product_cat_ids)) {
                $product_cat = get_term($product_cat_ids[0], 'product_cat');
                if (!is_wp_error($product_cat) && $product_cat->slug === 'tv-plan') {
                    $product_id = $product->get_id();
                    $monthly_fee = 0;
                    
                    if (function_exists('get_field')) {
                        $monthly_fee = get_field('monthly_fee', $product_id);
                        $monthly_fee = is_numeric($monthly_fee) ? floatval($monthly_fee) : 0;
                    }
                    
                    $tv_plan_object = array(
                        'Title' => $product->get_name(),
                        'Monthly Fee' => round($monthly_fee, 2)
                    );
                    
                    $tv_id = $product->get_attribute('id');
                    if (!empty($tv_id)) {
                        $tv_plan_object['ID Attribute'] = $tv_id;
                    }
                    
                    $promo_blurb = '';
                    $promo_fee_raw = null;
                    if (function_exists('get_field')) {
                        $promo_blurb = get_field('monthly_promo_blurb', $product_id);
                        $promo_fee_raw = get_field('monthly_promo_fee', $product_id);
                    }
                    $promo_is_set = is_numeric($promo_fee_raw);
                    $promo_fee = $promo_is_set ? floatval($promo_fee_raw) : null;
                    
                    if (!empty($promo_blurb) || $promo_is_set) {
                        $tv_plan_object['Promotional Blurb'] = $promo_blurb ? $promo_blurb : '';
                        $tv_plan_object['Promotional Price'] = $promo_is_set ? round($promo_fee, 2) : 0;
                        $monthly_subtotal += $promo_is_set ? $promo_fee : $monthly_fee;
                    } else {
                        $monthly_subtotal += $monthly_fee;
                    }
                    
                    // Insert before subtotal
                    $reordered = array();
                    foreach ($monthly_summary as $key => $value) {
                        if ($key === 'subtotal') {
                            $reordered['tv-plan'] = $tv_plan_object;
                        }
                        $reordered[$key] = $value;
                    }
                    $monthly_summary = $reordered;
                    break;
                }
            }
        }
    }

    // Recalculate monthly totals
    $monthly_summary['subtotal'][1] = round($monthly_subtotal, 2);
    $monthly_summary['taxes'][1] = round(($monthly_subtotal * $tax_rate) / 100, 2);
    $monthly_summary['grand_total'][1] = round($monthly_subtotal + $monthly_summary['taxes'][1], 2);
    
    // Calculate total deposits
    $total_deposits = 0;
    foreach ($upfront_summary as $key => $value) {
        if (in_array($key, array('subtotal', 'taxes', 'grand_total'))) {
            continue;
        }
        
        if (isset($value['Deposit Amount']) && is_numeric($value['Deposit Amount'])) {
            $total_deposits += floatval($value['Deposit Amount']);
        }
    }
    
    if (isset($upfront_summary['deposit']) && isset($upfront_summary['deposit']['Amount'])) {
        $total_deposits += floatval($upfront_summary['deposit']['Amount']);
    }
    
    // Insert total-deposits
    if ($total_deposits > 0) {
        $reordered_upfront = array();
        foreach ($upfront_summary as $key => $value) {
            $reordered_upfront[$key] = $value;
            if ($key === 'taxes') {
                $reordered_upfront['total-deposits'] = array(
                    'Total Deposits',
                    round($total_deposits, 2)
                );
            }
        }
        $upfront_summary = $reordered_upfront;
    }
    
    // Convert totals to objects
    $monthly_summary['subtotal'] = array('Subtotal' => $monthly_summary['subtotal'][1]);
    $monthly_summary['taxes'] = array('Tax' => $monthly_summary['taxes'][1]);
    $monthly_summary['grand_total'] = array('MONTHLY TOTAL' => $monthly_summary['grand_total'][1]);
    
    $upfront_summary['subtotal'] = array('Subtotal' => $upfront_summary['subtotal'][1]);
    $upfront_summary['taxes'] = array('Tax' => $upfront_summary['taxes'][1]);
    $upfront_summary['grand_total'] = array('UPFRONT TOTAL' => $upfront_summary['grand_total'][1]);
    
    if (isset($upfront_summary['total-deposits'])) {
        $upfront_summary['total-deposits'] = array('Total Deposits' => $upfront_summary['total-deposits'][1]);
    }
    
    return array(
        'upfront_summary' => $upfront_summary,
        'monthly_summary' => $monthly_summary
    );
}


/**
 * Prepare order data for Diallog database
 * INCLUDES COMPREHENSIVE LOGGING FOR DIALLOG DEVELOPER
 */

function prepare_diallog_order_data($payment_response, $cardholder_name, $moneris_config) {
 
    // Get formatted summaries using shared helper function
    $formatted_summaries = format_summaries_for_diallog();
    $upfront_summary = $formatted_summaries['upfront_summary'];
    $monthly_summary = $formatted_summaries['monthly_summary'];
    
    // Get customer data using the SAME method as thank you page
    $customer_info = dg_get_customer_info();

    // Get CCD (coupon code)
    $ccd = '';
    $ccd_encoded = dg_get_user_meta("ccd");
    if (!empty($ccd_encoded)) {
        $ccd_decoded = base64_decode($ccd_encoded);
        if ($ccd_decoded !== false && !empty($ccd_decoded)) {
            $ccd = strtoupper(trim($ccd_decoded));
        }
    }

    $customer_data = array(
    'billing_first_name'             => $customer_info['first_name'],
    'billing_last_name'              => $customer_info['last_name'],
    'billing_email'                  => $customer_info['email'],
    'billing_phone'                  => $customer_info['phone'],
    'customer_service_address'       => $customer_info['service_address_full'],
    'customer_shipping_address'      => $customer_info['shipping_address_full'],
    'customer_ip'                    => $_SERVER['REMOTE_ADDR'] ?? '',
    'ccd'                            => $ccd,
    'unit_number'                    => $customer_info['unit_number'],
    'buzzer_code'                    => $customer_info['buzzer_code'],
    'special_shipping_instructions'  => $customer_info['special_shipping_instructions'],
);
    
    // Get terms acceptance timestamp from session
    $terms_timestamp = '';
    if (WC()->session) {
        $terms_timestamp = WC()->session->get('terms_timestamp');
        if ($terms_timestamp) {
            error_log('Including terms timestamp in order data: ' . $terms_timestamp);
        }
    }

    // Format timestamps for display
    $order_timestamp_formatted = '';
    $terms_timestamp_formatted = '';

    if (function_exists('wp_timezone')) {
        $timezone = wp_timezone();
    
        $order_date = new DateTime('now', $timezone);
        $order_timestamp_formatted = $order_date->format('F j, Y \a\t g:i A T');
    
        if (!empty($terms_timestamp)) {
            try {
                $terms_date = new DateTime($terms_timestamp, new DateTimeZone('UTC'));
                $terms_date->setTimezone($timezone);
                $terms_timestamp_formatted = $terms_date->format('F j, Y \a\t g:i A T');
            } catch (Exception $e) {
                $terms_timestamp_formatted = $terms_timestamp;
            }
        }
    }

    // Build complete order data structure
    $order_data = array(
        'order_state' => 'completed',
        'payment_info' => array(
            'transaction_id' => $payment_response->getTxnNumber(),
            'receipt_id' => $payment_response->getReceiptId(),
            'amount' => $payment_response->getTransAmount(),
            'date' => $payment_response->getTransDate(),
            'time' => $payment_response->getTransTime(),
            'card_type' => $payment_response->getCardType(),
            'auth_code' => $payment_response->getAuthCode(),
            'reference_num' => $payment_response->getReferenceNum(),
            'test_mode' => $moneris_config['test_mode'],
            'order_timestamp' => $order_timestamp_formatted,
            'order_source' => 'website_checkout',
            'terms_acceptance_timestamp' => $terms_timestamp_formatted
        ),
        'customer_data' => $customer_data,
        'upfront_summary' => $upfront_summary,
        'monthly_summary' => $monthly_summary,
		'upfront_payment_method'  => 'Credit Card ending in ' . ( WC()->session ? WC()->session->get('payment_card_last_4') : '' )
    );
    
    // ========== ADD ENCRYPTED MONTHLY BILLING DATA ==========
    
    $monthly_method = dg_get_user_meta('monthly_bill_payment_option');
    
    if ($monthly_method === 'cc' || $monthly_method === 'bank') {
        if (is_user_logged_in()) {
            $encryption_key = md5(get_current_user_id());
        } else {
            $encryption_key = get_dg_user_id();
        }
        
        $crypt_path = get_stylesheet_directory() . '/includes/crypt.php';

        if (!file_exists($crypt_path)) {
            error_log('CRITICAL ERROR: crypt.php not found at: ' . $crypt_path);
            return false;
        }

        require_once $crypt_path;  
        
        if ($monthly_method === 'cc') {
            $cc_data = array(
                'billing_full_name' => dg_get_user_meta('cc_monthly_billing_full_name'),
                'billing_card_number' => dg_get_user_meta('cc_monthly_billing_card_number'),
                'billing_card_expiry' => dg_get_user_meta('cc_monthly_billing_card_expiry'),
                'billing_card_cvv' => dg_get_user_meta('cc_monthly_billing_card_cvv'),
                'billing_postcode' => dg_get_user_meta('cc_monthly_billing_postcode')
            );
            
            error_log('========================================');
            error_log('MONTHLY CC DATA (UNENCRYPTED - FOR DIALLOG DEVELOPER REFERENCE)');
            error_log('========================================');
            error_log(json_encode($cc_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            error_log('========================================');
            error_log('NOTE: This data will be serialized, encrypted with Cryptor class (AES-128-CTR),');
            error_log('and stored in the "monthly_cc" field in the order_data payload.');
            error_log('Decrypt using: $cryptor = new Cryptor($dg_user_hash); $decrypted = $cryptor->decrypt($monthly_cc);');
            error_log('Then unserialize: $data = maybe_unserialize($decrypted);');
            error_log('========================================');
            
            $cryptor = new Cryptor($encryption_key);
            $order_data['monthly_cc'] = $cryptor->encrypt(maybe_serialize($cc_data));
            $order_data['dg_user_hash'] = $encryption_key;
            
            $payment_method_display = 'Credit Card';
            
            if (WC()->session) {
                $monthly_card_last_4 = WC()->session->get('monthly_payment_card_last_4');
                if ($monthly_card_last_4) {
                    $payment_method_display .= ' ending in ' . $monthly_card_last_4;
                    error_log('Monthly card last 4 retrieved from session: ' . $monthly_card_last_4);
                }
            }
            
            $order_data['monthly_bill_payment_option'] = $payment_method_display;
            
        } elseif ($monthly_method === 'bank') {
            $bank_data = array(
                'billing_first_name' => dg_get_user_meta('bank_monthly_billing_first_name'),
                'billing_last_name' => dg_get_user_meta('bank_monthly_billing_last_name'),
                'account_type' => dg_get_user_meta('bank_monthly_billing_account_type'),
                'financial_institution' => dg_get_user_meta('bank_monthly_billing_financial_institution'),
                'transit_number' => dg_get_user_meta('bank_monthly_billing_transit_number'),
                'institution_number' => dg_get_user_meta('bank_monthly_billing_institution_number'),
                'account_number' => dg_get_user_meta('bank_monthly_billing_account_number')
            );
            
            error_log('========================================');
            error_log('MONTHLY BANK DATA (UNENCRYPTED - FOR DIALLOG DEVELOPER REFERENCE)');
            error_log('========================================');
            error_log(json_encode($bank_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            error_log('========================================');
            error_log('NOTE: This data will be serialized, encrypted with Cryptor class (AES-128-CTR),');
            error_log('and stored in the "monthly_bank" field in the order_data payload.');
            error_log('Decrypt using: $cryptor = new Cryptor($dg_user_hash); $decrypted = $cryptor->decrypt($monthly_bank);');
            error_log('Then unserialize: $data = maybe_unserialize($decrypted);');
            error_log('========================================');
            
            $cryptor = new Cryptor($encryption_key);
            $order_data['monthly_bank'] = $cryptor->encrypt(maybe_serialize($bank_data));
            $order_data['dg_user_hash'] = $encryption_key;
            
            $order_data['monthly_bill_payment_option'] = 'Bank Account - Pre-Authorized Debit (PAD)';
        }
        
    } elseif ($monthly_method === 'payafter') {
        error_log('========================================');
        error_log('MONTHLY BILLING METHOD: PAY AFTER (NO ENCRYPTED DATA)');
        error_log('========================================');
        error_log('Customer selected Pay After / Non Pre-Authorized billing.');
        error_log('A $100 deposit was added to upfront_summary.');
        error_log('No encrypted monthly billing data is included for this method.');
        error_log('========================================');
        
        $order_data['monthly_bill_payment_option'] = 'Pay After (Non Pre-Authorized)';
    }
    
    // ========== COMPREHENSIVE LOGGING FOR DIALLOG DEVELOPER ==========
    error_log('');
    error_log('================================================================================');
    error_log('==================== COMPLETE ORDER DATA FOR DIALLOG ==========================');
    error_log('================================================================================');
    error_log('');
    error_log('--- 1) UPFRONT BILLING SUMMARY (UNENCRYPTED) ---');
    error_log(json_encode($order_data['upfront_summary'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    error_log('');
    error_log('--- 2) MONTHLY BILLING SUMMARY (UNENCRYPTED) ---');
    error_log(json_encode($order_data['monthly_summary'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    error_log('');
    error_log('--- 3) MONTHLY BILLING METHOD ---');
    error_log('Selected Method: ' . ($monthly_method ? $monthly_method : 'NOT SET'));
    if ($monthly_method === 'cc') {
        error_log('monthly_cc field: [ENCRYPTED STRING - see separate log above for unencrypted structure]');
        error_log('dg_user_hash: ' . $encryption_key);
    } elseif ($monthly_method === 'bank') {
        error_log('monthly_bank field: [ENCRYPTED STRING - see separate log above for unencrypted structure]');
        error_log('dg_user_hash: ' . $encryption_key);
    } elseif ($monthly_method === 'payafter') {
        error_log('Pay After selected - No encrypted billing data');
    }
    error_log('');
    error_log('--- COMPLETE ORDER DATA STRUCTURE (WITH ENCRYPTED FIELDS) ---');
    error_log(json_encode($order_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    error_log('');
    error_log('================================================================================');
    error_log('========================== END DIALLOG ORDER DATA =============================');
    error_log('================================================================================');
    error_log('');
    
    return $order_data;
}



// ======================Transform order data to flat keys rather than nested objects specifically for email templates =========

function transform_order_data_for_email($diallog_order_data) {
    
    // Get customer data from WooCommerce
    $customer_info = dg_get_customer_info();

    // NEW: Get tax rate percentage
    $tax_rate = get_customer_tax_rate_percentage();

   // Get CCD from session
   /* $ccd = '';
    if (WC()->session) {
        $ccd = WC()->session->get('ccd');
    } */
    
    // Get CCD from user meta (same way as thank you page)
    $ccd = '';
    $ccd_encoded = dg_get_user_meta("ccd");

    if (!empty($ccd_encoded)) {
        // The CCD is stored base64 encoded, so decode it for display
        $ccd_decoded = base64_decode($ccd_encoded);
        
        if ($ccd_decoded !== false && !empty($ccd_decoded)) {
            $ccd = strtoupper(trim($ccd_decoded));
        }
    }
    
    // Get customer IP
    $customer_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    // Transform upfront summary to email format
    $upfront_items_for_email = array();
    $upfront_deposit_items = array(); // Separate array for deposits
    $upfront_subtotal = 0;
    $upfront_tax = 0;
    $upfront_total = 0;

    if (isset($diallog_order_data['upfront_summary']) && is_array($diallog_order_data['upfront_summary'])) {
        $upfront = $diallog_order_data['upfront_summary'];
        
        // Process each item in upfront summary
        foreach ($upfront as $key => $item) {
            // Handle both old array format [0], [1] and new object format
            if (is_array($item)) {
                // Check if it's numeric array format (old style) or associative array (new object style)
                $is_object_format = isset($item['Title']) || isset($item['Subtotal']) || isset($item['Tax']) || isset($item['UPFRONT TOTAL']);
                
                if ($is_object_format) {
                    // NEW OBJECT FORMAT from format_summaries_for_diallog
                    
                    // Handle special summary rows (subtotal, tax, total)
                    if (isset($item['Subtotal'])) {
                        $upfront_subtotal = $item['Subtotal'];
                    } elseif (isset($item['Tax'])) {
                        $upfront_tax = $item['Tax'];
                    } elseif (isset($item['UPFRONT TOTAL'])) {
                        $upfront_total = $item['UPFRONT TOTAL'];
                    } elseif (isset($item['Total Deposits'])) {
                        // Skip total deposits line - we show individual deposits
                    } else {
                        // Regular item (installation, modem, etc.)
                        $name = isset($item['Title']) ? $item['Title'] : '';
                        $amount = isset($item['Price']) ? $item['Price'] : 0;
                        
                        // NEW: Check for deposit items
                        $is_deposit = isset($item['Deposit Title']) || isset($item['Deposit Amount']) || 
                                     (isset($item['Amount']) && stripos($name, 'deposit') !== false);
                        
                        if ($is_deposit) {
                            // This is a deposit - add to separate array
                            $deposit_amount = isset($item['Amount']) ? $item['Amount'] : (isset($item['Deposit Amount']) ? $item['Deposit Amount'] : $amount);
                            
                            $upfront_deposit_items[] = array(
                                'name' => $name,
                                'total' => $deposit_amount
                            );
                        } else {
                            // Regular item (installation, etc.)
                            $item_data = array(
                                'name' => $name,
                                'total' => $amount
                            );
                            
                            // NEW: Extract installation dates if available
                            $installation_dates_parts = array();
                            if (isset($item['Preferred Installation Date']) && !empty($item['Preferred Installation Date'])) {
                                $installation_dates_parts[] = $item['Preferred Installation Date'];
                            }
                            if (isset($item['Secondary Installation Date']) && !empty($item['Secondary Installation Date'])) {
                                $installation_dates_parts[] = $item['Secondary Installation Date'];
                            }
                            if (!empty($installation_dates_parts)) {
                                $item_data['installation_dates'] = implode(', ', $installation_dates_parts);
                            }
                            
                            // NEW: Extract dynamic sale price if available
                            if (isset($item['Dynamic Sale Price']) && $item['Dynamic Sale Price'] > 0) {
                                $item_data['original_price'] = $amount; // 'Price' is original when Dynamic Sale Price exists
                                $item_data['promo_price'] = $item['Dynamic Sale Price'];
                            }
                            
                            $upfront_items_for_email[] = $item_data;
                        }
                    }
                    
                } else {
                    // OLD NUMERIC ARRAY FORMAT (fallback for compatibility)
                    $name = $item[0];
                    $amount = $item[1];
                    
                    // Get installation dates if available (index [2] for Installation items)
                    $installation_dates = isset($item[2]) ? $item[2] : '';
                    
                    // Skip subtotal, tax, and grand_total - we'll handle those separately
                    if ($key === 'subtotal') {
                        $upfront_subtotal = $amount;
                    } elseif ($key === 'taxes') {
                        $upfront_tax = $amount;
                    } elseif ($key === 'grand_total') {
                        $upfront_total = $amount;
                    } else {
                        // Check if this is a deposit item
                        if ($key === 'deposit' || stripos($name, 'deposit') !== false) {
                            // This is a deposit - add to separate array
                            $clean_name = $name;
                            if (stripos($name, 'pay-after') !== false || stripos($name, 'payafter') !== false) {
                                $clean_name = 'Pay After Deposit';
                            }
                            
                            $upfront_deposit_items[] = array(
                                'name' => $clean_name,
                                'total' => $amount
                            );
                        } else {
                            // Regular item (installation, etc.)
                            $item_data = array(
                                'name' => $name,
                                'total' => $amount
                            );
                            
                            // Add installation dates if they exist
                            if (!empty($installation_dates)) {
                                $item_data['installation_dates'] = $installation_dates;
                            }
                            
                            // Check for promotional pricing (indexes [3] and [4])
                            if (isset($item[3]) && isset($item[4]) && $item[3] > 0) {
                                $item_data['original_price'] = $item[3];
                                $item_data['promo_price'] = $item[4];
                            }
                            
                            $upfront_items_for_email[] = $item_data;
                        }
                    }
                }
            }
        }
    }
    
    // UPDATED: Transform monthly summary to email format WITH PROMOTIONAL INFO
    $monthly_items_for_email = array();
    $monthly_subtotal = 0;
    $monthly_tax = 0;
    $monthly_total = 0;

    if (isset($diallog_order_data['monthly_summary']) && is_array($diallog_order_data['monthly_summary'])) {
        $monthly = $diallog_order_data['monthly_summary'];
        
        // Process each item in monthly summary
        foreach ($monthly as $key => $item) {
            if (is_array($item)) {
                // Check if object format or numeric array format
                $is_object_format = isset($item['Title']) || isset($item['Subtotal']) || isset($item['Tax']) || isset($item['MONTHLY TOTAL']);
                
                if ($is_object_format) {
                    // NEW OBJECT FORMAT
                    if (isset($item['Subtotal'])) {
                        $monthly_subtotal = $item['Subtotal'];
                    } elseif (isset($item['Tax'])) {
                        $monthly_tax = $item['Tax'];
                    } elseif (isset($item['MONTHLY TOTAL'])) {
                        $monthly_total = $item['MONTHLY TOTAL'];
                    } else {
                        // Regular monthly item
                        $name = isset($item['Title']) ? $item['Title'] : '';
                        $final_price = isset($item['Monthly Fee']) ? $item['Monthly Fee'] : 0;
                        
                        $monthly_items_for_email[] = array(
                            'name' => $name,
                            'total' => $final_price,
                            'original_price' => $final_price, // Use final price as original if no promo
                            'promo_price' => isset($item['Promotional Price']) ? $item['Promotional Price'] : 0,
                            'promo_blurb' => isset($item['Promotional Blurb']) ? $item['Promotional Blurb'] : '',
                            'modem_details' => isset($item['Modem Make & Model']) ? $item['Modem Make & Model'] : ''
                        );
                    }
                    
                } else {
                    // OLD NUMERIC ARRAY FORMAT (fallback)
                    if (isset($item[0], $item[1])) {
                        $name = $item[0];
                        $final_price = $item[1];
                        
                        // Extract promotional pricing info if available
                        $original_price = isset($item[2]) ? $item[2] : 0;
                        $promo_price = isset($item[3]) ? $item[3] : 0;
                        $promo_blurb = isset($item[4]) ? $item[4] : '';
                        $modem_details = isset($item[5]) ? $item[5] : '';
                        
                        // Skip subtotal, tax, and grand_total
                        if ($key === 'subtotal') {
                            $monthly_subtotal = $final_price;
                        } elseif ($key === 'taxes') {
                            $monthly_tax = $final_price;
                        } elseif ($key === 'grand_total') {
                            $monthly_total = $final_price;
                        } else {
                            $monthly_items_for_email[] = array(
                                'name' => $name,
                                'total' => $final_price,
                                'original_price' => $original_price,
                                'promo_price' => $promo_price,
                                'promo_blurb' => $promo_blurb,
                                'modem_details' => $modem_details
                            );
                        }
                    }
                }
            }
        }
    }
    
    // Get monthly payment method from user meta (same way as thank you page)
    $monthly_payment_method = 'Not specified';
    $monthly_method_code = dg_get_user_meta('monthly_bill_payment_option');

    if ($monthly_method_code) {
        if (strtolower($monthly_method_code) === 'cc') {
            $monthly_payment_method = 'Credit Card';
            
            // Get last 4 from session with correct key
            if (WC()->session) {
                $monthly_card_last_4 = WC()->session->get('monthly_payment_card_last_4');
                if ($monthly_card_last_4) {
                    $monthly_payment_method .= ' ending in ' . $monthly_card_last_4;
                }
            }
        } elseif (in_array(strtolower($monthly_method_code), array('bank', 'pad'))) {
            $monthly_payment_method = 'Bank Account - Pre-Authorized Debit (PAD)';
        } elseif (strtolower($monthly_method_code) === 'payafter') {
            $monthly_payment_method = 'Pay After (Non Pre-Authorized)';
        }
    }
    
    // Get payment details from session
    $auth_code = '';
    $transaction_number = '';
    $card_last_4 = '';
    
    if (WC()->session) {
        $auth_code = WC()->session->get('payment_auth_code');
        $transaction_number = WC()->session->get('payment_reference_num'); 
        $card_last_4 = WC()->session->get('payment_card_last_4');
    }
    
    // ============================================================
    // FORMAT TIMESTAMPS - MUST BE BEFORE $email_order_data ARRAY!
    // ============================================================
    
    // Format timestamps using WordPress timezone
    // Get WordPress timezone string (e.g., 'America/New_York' for EST)
    $wp_timezone_string = wp_timezone_string(); // This gets the timezone from WordPress settings
    
    // Format order timestamp in WordPress timezone with timezone label
    $order_timestamp_formatted = '';
    if (isset($diallog_order_data['order_timestamp']) && !empty($diallog_order_data['order_timestamp'])) {
        // Convert Unix timestamp to DateTime with WordPress timezone
        $order_datetime = new DateTime('@' . $diallog_order_data['order_timestamp']);
        $order_datetime->setTimezone(new DateTimeZone($wp_timezone_string));
     // Format: "F j, Y at g:i A T" - Example: "November 12, 2025 at 4:50 PM EST"
        $order_timestamp_formatted = $order_datetime->format('F j, Y \a\t g:i A T');
    } else {
        // Fallback to current time in WordPress timezone
        $order_datetime = new DateTime('now', new DateTimeZone($wp_timezone_string));
        $order_timestamp_formatted = $order_datetime->format('F j, Y \a\t g:i A T');
    }
    
    // Format terms timestamp in WordPress timezone with timezone label
    $terms_timestamp_formatted = '';
    if (isset($diallog_order_data['terms_acceptance_timestamp']) && !empty($diallog_order_data['terms_acceptance_timestamp'])) {
        // The terms timestamp is already a formatted datetime string from JavaScript
        // Parse it and convert to WordPress timezone
        try {
            $terms_datetime = new DateTime($diallog_order_data['terms_acceptance_timestamp']);
            $terms_datetime->setTimezone(new DateTimeZone($wp_timezone_string));
            // Format: "F j, Y at g:i A T" - Example: "November 12, 2025 at 4:50 PM EST"
            $terms_timestamp_formatted = $terms_datetime->format('F j, Y \a\t g:i A T');
        } catch (Exception $e) {
            error_log('Error parsing terms timestamp: ' . $e->getMessage());
            $terms_timestamp_formatted = '';
        }
    }
    
    // ============================================================
    // NOW BUILD THE EMAIL ORDER DATA ARRAY
    // ============================================================
    
    // Build the transformed order data for email
    $email_order_data = array(
        // Customer information
    'customer_name' => $customer_info['full_name'],
    'customer_email' => $customer_info['email'],
    'customer_phone' => $customer_info['phone'],
    'service_address' => $customer_info['service_address_full'],
    'shipping_address' => $customer_info['shipping_address_full'],
    'unit_number' => $customer_info['unit_number'],
    'buzzer_code' => $customer_info['buzzer_code'],
    'special_shipping_instructions' => $customer_info['special_shipping_instructions'],
    'customer_ip' => $customer_ip,
    'ccd' => $ccd,
        
        // Order timestamps (formatted with timezone)
        'order_timestamp' => $order_timestamp_formatted,
        'terms_timestamp' => $terms_timestamp_formatted,

        // Tax information
        'tax_rate' => $tax_rate,
        
        // Payment information
        'authorization_code' => $auth_code,
        'transaction_number' => $transaction_number,
        'card_last_4' => $card_last_4,
        
        // Upfront payment summary
        'upfront_summary' => array(
            'items' => $upfront_items_for_email,
            'deposits' => $upfront_deposit_items, // NEW: Add deposits separately
            'subtotal' => $upfront_subtotal,
            'tax' => $upfront_tax,
            'total' => $upfront_total
        ),
        
        // Monthly billing summary
        'monthly_summary' => array(
            'items' => $monthly_items_for_email,
            'subtotal' => $monthly_subtotal,
            'tax' => $monthly_tax,
            'total' => $monthly_total,
            'payment_method' => $monthly_payment_method
        )
    );
    
    return $email_order_data;
}


/**
 * Send customer info to Diallog (state 50 - info confirmed but not completed)
 */
 
function send_customer_info_to_diallog($data) {
    error_log('--- PREPARING API PAYLOAD (STATE 50) ---');
    
    // Generate unique transaction ID and receipt ID for this state 50 submission
    $unique_transaction_id = 'PENDING-' . time() . '-' . wp_rand(1000, 9999);
    $unique_receipt_id = 'PENDING-' . wp_rand(10000, 99999);
    
    error_log('Generated unique IDs for state 50:');
    error_log('- Transaction ID: ' . $unique_transaction_id);
    error_log('- Receipt ID: ' . $unique_receipt_id);
    error_log('');
    
    // Build the order_data object with placeholder payment info for state 50
    $order_data = array(
        'order_state' => 'not_completed',
        'payment_info' => array(
            'transaction_id' => $unique_transaction_id, // CHANGED: unique ID instead of "pending"
            'receipt_id' => $unique_receipt_id, // CHANGED: unique ID instead of "pending"
            'amount' => '0.00',
            'date' => date('Ymd'),
            'time' => date('H:i:s'),
            'card_type' => 'pending',
            'auth_code' => 'pending',
            'reference_num' => 'pending',
            'test_mode' => true,
            'order_timestamp' => date('F j, Y \a\t g:i A T'),
            'order_source' => 'website_checkout',
            'terms_acceptance_timestamp' => 'pending'
        ),
        'customer_data' => $data['customer_data'],
        'upfront_summary' => $data['upfront_summary'],
        'monthly_summary' => $data['monthly_summary'],
        'monthly_bill_payment_option' => 'not_selected'
    );
    
    // Prepare payload with single encoded order_data field (SAME as state 100)
    $payload_data = array(
        'status' => 0,
        'magic' => 'Sl2soDSpLAsHqetS',
        'api' => '1.00',
        'method' => 'newsignup',
        'state' => 50,
        'order_data' => base64_encode(json_encode($order_data)),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
    );

    error_log('Payload structure prepared:');
    error_log('- method: newsignup');
    error_log('- state: 50');
    error_log('- order_state (inside order_data): not_completed');
    error_log('- payment_info: unique transaction/receipt IDs generated');
    error_log('- monthly_bill_payment_option: not_selected');
    error_log('');

    $payload = json_encode($payload_data);
    
    error_log('--- RAW JSON PAYLOAD (WITH BASE64 ENCODED order_data) ---');
    error_log($payload);
    error_log('');
    error_log('Payload size: ' . strlen($payload) . ' bytes');
    error_log('');

    error_log('--- DECODED PREVIEW (FOR VERIFICATION) ---');
    error_log('Order Data (decoded): ' . json_encode($order_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    error_log('');

    // Initialize cURL
    error_log('--- INITIATING CURL REQUEST ---');
    error_log('Target URL: https://sg.diallog.com/signup');
    error_log('Method: POST');
    error_log('SSL Verification: ENABLED');
    error_log('');
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://sg.diallog.com/signup");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload)
    ));

    // Execute request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    error_log('--- CURL REQUEST COMPLETED ---');
    error_log('HTTP Status Code: ' . $http_code);
    
    if ($curl_error) {
        error_log('CURL Error: ' . $curl_error);
    } else {
        error_log('✓ No CURL errors');
    }
    
    error_log('');
    error_log('--- DIALLOG SERVER RESPONSE ---');
    error_log('Raw Response: ' . $response);
    error_log('');
    
    // Try to decode response if it's JSON
    $decoded_response = json_decode($response, true);
    if ($decoded_response !== null) {
        error_log('Decoded Response (JSON):');
        error_log(json_encode($decoded_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    } else {
        error_log('Response is not JSON or could not be decoded');
    }
    error_log('');

    return $response;
}

/**
 * Send complete order to Diallog (state 100 - order completed with payment)
 */
/**
 * Send complete order to Diallog (state 100 - order completed with payment)
    */
    function send_order_to_diallog($order_data) {
        $payload_data = array(
            'order_state' => 'completed',
            'status' => 0,
            'magic' => 'Sl2soDSpLAsHqetS',
            'api' => '1.00',
            'method' => 'complete_order',
            'state' => 100,
            'order_data' => base64_encode(json_encode($order_data)),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
        );
        
        $payload = json_encode($payload_data);
        
        error_log("Sending complete order to Diallog (state 100 - COMPLETED): " . $payload);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://sg.diallog.com/signup"); // NEW URL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // ENABLE SSL verification for production
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // ENABLE SSL verification for production
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload)
        ));
        
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        
        error_log("Diallog API response (state 100 - COMPLETED): " . $response);
        
        return $response;
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


/*==========Updated display_monthly_fee shortcode with promotional pricing support========*/

function display_monthly_fee_shortcode($atts) {
    $atts = shortcode_atts(array(
        'product_id' => '',
    ), $atts);

    if (empty($atts['product_id'])) {
        return 'Product ID required';
    }

    $monthly_fee = get_field('monthly_fee', $atts['product_id']);
    if ($monthly_fee === null && function_exists('get_field')) {
        $monthly_fee = get_field('monthly_fee', 'product_' . $atts['product_id']);
    }

    $monthly_promo_fee_raw = get_field('monthly_promo_fee', $atts['product_id']);
    if ($monthly_promo_fee_raw === null && function_exists('get_field')) {
        $monthly_promo_fee_raw = get_field('monthly_promo_fee', 'product_' . $atts['product_id']);
    }

    if (is_numeric($monthly_fee) || is_string($monthly_fee)) {
        $promo_is_set = is_numeric($monthly_promo_fee_raw);
        $monthly_promo_fee = $promo_is_set ? floatval($monthly_promo_fee_raw) : null;

        if ($promo_is_set) {
            $output  = '<span style="text-decoration: line-through; color: grey; font-size: 0.8em;">$' . esc_html((string)$monthly_fee) . '</span> ';
            $output .= '<span style="color: green; font-weight: bold;">$' . esc_html((string)$monthly_promo_fee) . '</span>';
            return $output;
        } else {
            return '$' . esc_html((string)$monthly_fee);
        }
    } else {
        return '$0';
    }
}
add_shortcode('display_monthly_fee', 'display_monthly_fee_shortcode');


/*==========New shortcode for displaying promotional fee blurb========*/


function display_promo_fee_blurb_shortcode($atts) {
    // Extract shortcode attributes, requiring product_id
    $atts = shortcode_atts(array(
        'product_id' => '', // No default - must be specified
    ), $atts);
    
    // Check if product_id is provided
    if (empty($atts['product_id'])) {
        return 'Product ID required'; // Or just return empty: return '';
    }
    
    // Get the 'monthly_promo_blurb' field value from the specified product
    $monthly_promo_blurb = get_field('monthly_promo_blurb', $atts['product_id']);
    
    // Alternative way to get the field if the above doesn't work
    if ($monthly_promo_blurb === null && function_exists('get_field')) {
        $monthly_promo_blurb = get_field('monthly_promo_blurb', 'product_' . $atts['product_id']);
    }
    
    // Return the blurb content or empty string if not populated
    if (!empty($monthly_promo_blurb)) {
        return '<span style="color: green; font-style: italic;">' . esc_html($monthly_promo_blurb) . '</span>';
    } else {
        return ''; // Return empty if no promotional blurb set
    }
}
add_shortcode('display_promo_fee_blurb', 'display_promo_fee_blurb_shortcode');


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
        'variation_id' => 267988, // Default to the installation variation ID
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
        $output .= 'data-source="shortcode" ';  
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

/*==========Final Fixed AJAX Function - Provides data in expected format========*/

add_action( 'wp_ajax_nopriv_find_address_with_redirect', 'ajax_find_address_with_redirect_client_geocoded' );
add_action( 'wp_ajax_find_address_with_redirect', 'ajax_find_address_with_redirect_client_geocoded' );

function ajax_find_address_with_redirect_client_geocoded() {
    error_log("=== CLIENT GEOCODED: ajax_find_address_with_redirect called ===");
    error_log("POST data: " . print_r($_POST, true));
    
    $ccd = "";
    
    // Handle CCD parameter
    if (isset($_POST["ccd_param"])) {
        $ccd = $_POST["ccd_param"];
        if (strlen($ccd) > 0) {
            dg_set_user_meta("ccd", $ccd);
        }
    }
    
    // Check if this is a redirect request
    $redirect_to_plans = isset($_POST['redirect_to_plans']) && $_POST['redirect_to_plans'] === 'true';
    error_log("Redirect to plans: " . ($redirect_to_plans ? 'yes' : 'no'));
    
    if ($redirect_to_plans) {
        error_log("Processing redirect request...");
        
        // CRITICAL: Get the address data from the AJAX request
        $street_address = isset($_POST['streetAddress']) ? sanitize_text_field($_POST['streetAddress']) : '';
        $unit_number = isset($_POST['unitNumber']) ? sanitize_text_field($_POST['unitNumber']) : '';
        $buzzer_code = isset($_POST['buzzerCode']) ? sanitize_text_field($_POST['buzzerCode']) : '';
        $geocoded_address = isset($_POST['geocoded_address']) ? $_POST['geocoded_address'] : null;
        
        error_log("Street Address: " . $street_address);
        error_log("Geocoded Address: " . print_r($geocoded_address, true));
        
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
        
        // Parse the route into street components if needed
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
        
        // CRITICAL: Set up POST data that ppget_internet_plans expects
        $_POST['streetAddress'] = $street_address;
        $_POST['unitNumber'] = $unit_number;
        $_POST['buzzerCode'] = $buzzer_code;
        $_POST['unitType'] = ''; // Required to prevent undefined index
        $_POST['searched_address'] = $searched_address;
        $_POST['user_data'] = $user_data;
        
        error_log("POST data set up, now calling ppget_internet_plans...");
        
        // Suppress WordPress errors for cleaner JSON response
        $original_error_reporting = error_reporting();
        error_reporting(E_ERROR | E_PARSE);
        
        try {
            // Call the main internet plans function
            $response = ppget_internet_plans(1, $ccd);
            error_log("ppget_internet_plans response length: " . strlen($response));
            error_log("Response preview: " . substr($response, 0, 200));
            
            // Restore error reporting
            error_reporting($original_error_reporting);
            
            // Log address data after the function runs
            AddressCheckLog(0);
            
            // Get the API response to check if service is available
            $apiResponse = dg_get_user_meta("_api_response");
            error_log("API Response after ppget_internet_plans: " . print_r($apiResponse, true));
            
            // Check if we found plans - the response contains product HTML if successful
            $no_service_found = (strpos($response, 'Check Service Availability in Your Area') !== false);
            $is_not_available = (strpos($response, 'not available') !== false || 
                                 strpos($response, 'No service') !== false);
            
            error_log("Response analysis: no_service_found=" . 
                      ($no_service_found ? 'true' : 'false') . 
                      ", is_not_available=" . ($is_not_available ? 'true' : 'false'));
            
            if (!$no_service_found && !$is_not_available) {
                // Success - service is available at this address
                error_log("SUCCESS: Internet service available, sending redirect response");
                
                wp_send_json_success(array(
                    'redirect' => true,
                    'redirect_url' => home_url('/residential/internet#panel-showinternetplans'),
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
        AddressCheckLog(0);
        wp_die($response);
    }
}


/*======Add Modem Details to 'I have my own Modem' product=====*/

function save_modem_details_to_cart() {
    check_ajax_referer('modem_selection_nonce', 'nonce');

       // DEBUG: Check what PHP receives
    error_log("========== MODEM DETAILS DEBUG ==========");
    error_log("RAW _POST data: " . print_r($_POST, true));
    error_log("RAW modem_details: '" . ($_POST['modem_details'] ?? 'NOT SET') . "'");
    error_log("RAW length: " . strlen($_POST['modem_details'] ?? ''));
    
    $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
    $modem_details = isset($_POST['modem_details']) ? sanitize_textarea_field($_POST['modem_details']) : '';
    
    if ($product_id !== 267979 || strlen($modem_details) < 5 || strlen($modem_details) > 100) {
        error_log("VALIDATION FAILED - product_id: $product_id, length: " . strlen($modem_details));
        wp_send_json_error(array('message' => 'Invalid modem details'));
        return;
    }
    
    // Remove any existing modems from cart
    $modem_category_id = 59;
    
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
    
    error_log("Cart item data being added: " . print_r($cart_item_data, true));
    
    $added = WC()->cart->add_to_cart($product_id, 1, 0, array(), $cart_item_data);
    
    if ($added) {
        // DEBUG: Check what was actually saved to cart
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if ($cart_item['product_id'] == 267979) {
                error_log("SAVED TO CART - modem_details: " . ($cart_item['modem_details'] ?? 'NOT SET'));
                error_log("SAVED TO CART - LENGTH: " . strlen($cart_item['modem_details'] ?? ''));
            }
        }
        
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


// Save modem details to order (NO SHIPPING DATA)
function save_modem_details_to_order($item, $cart_item_key, $values, $order) {
    if (isset($values['modem_details'])) {
        $item->add_meta_data('Modem Make & Model', $values['modem_details']);
    }
}
add_action('woocommerce_checkout_create_order_line_item', 'save_modem_details_to_order', 10, 4);


/**
 * Clickable Modem Rows - Add to Cart Functionality
 */

// Enqueue the JavaScript
function modem_selection_scripts() {
    // Load on product pages and custom checkout pages
    if (is_product() || is_page(array('checkout', 'internet-plans'))) { // Add your custom page slugs here
        wp_enqueue_script('card-selection', get_stylesheet_directory_uri() . '/js/card-selection.js', array('jquery'), '1.1', true);
        
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
    $modem_details = '';
    
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $items[] = $cart_item['product_id'];
        
        // Check if this is the "I Have My Own Modem" product and get the details
        if ($cart_item['product_id'] == 267979 && isset($cart_item['modem_details'])) {
            $modem_details = $cart_item['modem_details'];
        }
    }
    
    wp_send_json_success(array(
        'items' => $items,
        'modem_details' => $modem_details
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


/**
 * Apply dynamic installation price based on internet plan in cart
 * This overrides the installation product price when a promotional price exists
 */

function apply_dynamic_install_price($cart) {
    // Prevent this from running multiple times during a single page load
    if (did_action('woocommerce_before_calculate_totals') >= 2) {
        return;
    }
    
    // Don't run in admin
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }
    
    error_log('=== APPLY DYNAMIC INSTALL PRICE ===');
    
    // Step 1: Find internet plan in cart and get dynamic_install_price
    $internet_plan_cat_id = 19;
    $dynamic_install_price = null;
    $internet_plan_found = false;
    
    foreach ($cart->get_cart() as $cart_item) {
        $product = $cart_item['data'];
        $product_id = $product->get_id();
        $product_cat_ids = $product->get_category_ids();
        
        // Check if this product has internet-plan category
        if (in_array($internet_plan_cat_id, $product_cat_ids)) {
            $internet_plan_found = true;
            
            // Get dynamic_install_price from this internet plan
            if (function_exists('get_field')) {
                $dynamic_install_price = get_field('dynamic_install_price', $product_id);
                
                if ($dynamic_install_price === '' || $dynamic_install_price === null || $dynamic_install_price === false) {
                    $dynamic_install_price = get_field('dynamic_install_price', 'product_' . $product_id);
                }
                
                // Convert to float if we have a value (including 0)
                if ($dynamic_install_price !== '' && $dynamic_install_price !== null && $dynamic_install_price !== false) {
                    $dynamic_install_price = floatval($dynamic_install_price);
                }
            }
            
            error_log("Found internet plan (ID: $product_id) with dynamic_install_price: " . ($dynamic_install_price !== null ? $dynamic_install_price : 'not set'));
            break; // Found internet plan, stop looking
        }
    }
    
    // If no dynamic price set or no internet plan, exit
    if (!$internet_plan_found || $dynamic_install_price === null) {
        error_log("No dynamic install price to apply (internet plan found: " . ($internet_plan_found ? 'yes' : 'no') . ", price: " . ($dynamic_install_price !== null ? $dynamic_install_price : 'not set') . ")");
        return;
    }
    
    // Step 2: Find installation product and override its price
    $installation_parent_id = 267986;
    $installation_variation_id = 267988;
    
    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        $product_id = $product->get_id();
        $parent_id = $product->get_parent_id();
        
        // Check if this is the installation product
        $is_installation = ($product_id == $installation_parent_id || 
                           $product_id == $installation_variation_id || 
                           $parent_id == $installation_parent_id);
        
        if ($is_installation) {
            $original_price = $product->get_price();
            
            // Set the new price (can now be 0)
            $product->set_price($dynamic_install_price);
            
            error_log("✓ Applied dynamic install price to product $product_id: $$original_price → $$dynamic_install_price");
        }
    }
    
    error_log('=== END APPLY DYNAMIC INSTALL PRICE ===');
}
add_action('woocommerce_before_calculate_totals', 'apply_dynamic_install_price', 10, 1);

/*======Upfront Fee Summary Table - 
Removed 'Installation' & 'Extras' row headers =========*/

function upfront_fee_summary_shortcode() {

	// Guard: WC cart is not available in admin/REST context (e.g. Divi backend editor)
    if ( is_null( WC()->cart ) ) {
        return '';
    }
	
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
   $original_installation_price = 0;
   $dynamic_install_price = 0;
   $deposits = array(); // Store deposit information
   
   // NEW: Get dynamic install price from internet plan in cart
   $internet_plan_cat_id = 19;
   foreach ($cart->get_cart() as $cart_item) {
       $product = $cart_item['data'];
       $product_id = $product->get_id();
       $product_cat_ids = $product->get_category_ids();
       
      // Check if this product has internet-plan category (check ALL categories)
    if (in_array($internet_plan_cat_id, $product_cat_ids)) {
        if (function_exists('get_field')) {
            $dynamic_install_price = get_field('dynamic_install_price', $product_id);
        
        if ($dynamic_install_price === '' || $dynamic_install_price === null || $dynamic_install_price === false) {
            $dynamic_install_price = get_field('dynamic_install_price', 'product_' . $product_id);
        }
        
        // Convert to float, but only if we actually have a value (including 0)
        if ($dynamic_install_price !== '' && $dynamic_install_price !== null && $dynamic_install_price !== false) {
            $dynamic_install_price = floatval($dynamic_install_price);
        } else {
            $dynamic_install_price = null; // No promotional price set
        }
    }
    
    error_log('Upfront summary shortcode - Found internet plan: ' . $product_id . ', dynamic price: ' . $dynamic_install_price);
    break;
}
   }
   
   // First pass: Check for installation and store its details
   foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
       $product = $cart_item['data'];
       $product_id = $product->get_id();
       $parent_id = $product->get_parent_id();
       
       // Check if this is an installation product
       $installation_category_id = 60; // Installation category ID
       $installation_product_id = 267986; // Parent product ID
       $product_cats = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
       $is_installation = in_array($installation_category_id, $product_cats) || $parent_id == $installation_product_id;
       
       if ($is_installation) {
    $installation_found = true;
    
    // NEW: Get ORIGINAL price from database, not cart (cart may be modified by hook)
    $installation_product_db = wc_get_product($product_id);
    $original_installation_price = $installation_product_db ? floatval($installation_product_db->get_regular_price()) : floatval($product->get_price());
    
    // Apply dynamic install price if available
   // Apply dynamic install price if available
    if ($dynamic_install_price !== null) {
        $installation_price = $dynamic_install_price;
    } else {
        $installation_price = $original_installation_price;
    }
    
    // Check for variation attributes
    if (isset($cart_item['variation']) && is_array($cart_item['variation'])) {
        $installation_dates = array(
            'preferred-date' => isset($cart_item['variation']['attribute_preferred-date']) ? 
                $cart_item['variation']['attribute_preferred-date'] : '',
            'secondary-date' => isset($cart_item['variation']['attribute_secondary-date']) ? 
                $cart_item['variation']['attribute_secondary-date'] : ''
        );
    }
    break; // Only need to find one installation product
}
   }
   
   // Display installation first if found
   if ($installation_found) {
       // Build pricing display for installation
       $install_pricing_display = '';
       if ($dynamic_install_price !== null && $dynamic_install_price != $original_installation_price) {
           // Show strikethrough original price and green bold sale price
           $install_pricing_display = '<span style="text-decoration: line-through;">' . wc_price($original_installation_price) . '</span> ';
           $install_pricing_display .= '<span style="color: green; font-weight: bold;">' . wc_price($dynamic_install_price) . '</span>';
       } else {
           // Show regular price
           $install_pricing_display = wc_price($installation_price);
       }
       
       $output .= '<tr>';
       $output .= '<td>Installation';
       
       // Add dates if available
       if (!empty($installation_dates['preferred-date'])) {
           $output .= '<br><span style="font-size: 0.75em; color: #666;"><b>Preferred:</b> ' . 
                     esc_html($installation_dates['preferred-date']) . '</span>';
       }
       if (!empty($installation_dates['secondary-date'])) {
           $output .= '<br><span style="font-size: 0.75em; color: #666;"><b>Secondary:</b> ' . 
                     esc_html($installation_dates['secondary-date']) . '</span>';
       }
       
       $output .= '</td>';
       $output .= '<td>Installation</td>';
       $output .= '<td>' . $install_pricing_display . '</td>';
       $output .= '</tr>';
       
       $subtotal += $installation_price; // Use final price (sale or regular) for calculations
   }
   
   // Get internet plan category for sorting
   $internet_plan_category_id = 19;
   
   // Second pass: Get all other products (excluding installation)
   $other_products = array();
   
   foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
       $product = $cart_item['data'];
       $product_id = $product->get_id();
       $parent_id = $product->get_parent_id();
       $product_price = $product->get_price();
       $product_name = $product->get_name();
       
       // Get product categories
       $product_cats = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
       
       // Skip installation (already displayed)
       $installation_category_id = 60;
       $installation_product_id = 267986;
       $is_installation = in_array($installation_category_id, $product_cats) || $parent_id == $installation_product_id;
       
       if ($is_installation) {
           continue;
       }
       
       // Get category name for display
       $category_name = 'Product';
       
       // Get terms from parent if variation, otherwise from product
       if ($parent_id > 0) {
           $terms = get_the_terms($parent_id, 'product_cat');
       } else {
           $terms = get_the_terms($product_id, 'product_cat');
       }
       
       if (!empty($terms) && !is_wp_error($terms)) {
           // Check if this product is an internet plan (has category ID 19)
           $is_internet_plan = in_array($internet_plan_category_id, $product_cats);
           
           if ($is_internet_plan) {
               // For internet plans, always show "Internet Plan" category
               $internet_plan_term = get_term($internet_plan_category_id, 'product_cat');
               if ($internet_plan_term && !is_wp_error($internet_plan_term)) {
                   $category_name = $internet_plan_term->name;
               } else {
                   // Fallback if term lookup fails
                   $category_name = 'Internet Plan';
               }
           } else {
               // For non-internet plans, use the first category as before
               $category_name = $terms[0]->name;
           }
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
       
       // Add product to array for sorting (only if price > 0)
       if ($product_price > 0) {
           $other_products[] = array(
               'name' => $product_name,
               'category' => $category_name,
               'price' => $product_price
           );
       }
       
       // Always add to subtotal regardless of display
       $subtotal += $product_price;
   }
   
   // Sort products: Internet plans first, then others
   usort($other_products, function($a, $b) {
       if ($a['category'] === 'Internet Plan' && $b['category'] !== 'Internet Plan') {
           return -1;
       }
       if ($a['category'] !== 'Internet Plan' && $b['category'] === 'Internet Plan') {
           return 1;
       }
       return 0;
   });
   
   // Display sorted products
   foreach ($other_products as $product_data) {
       $output .= '<tr>';
       $output .= '<td>' . esc_html($product_data['name']) . '</td>';
       $output .= '<td>' . esc_html($product_data['category']) . '</td>';
       $output .= '<td>' . wc_price($product_data['price']) . '</td>';
       $output .= '</tr>';
   }
   
   // Calculate tax using province-specific rates (excluding deposits)
   $tax_total = 0;
   if (wc_tax_enabled()) {
       // Get province from address lookup
       $searched_address = dg_get_user_meta("searched_address");
       
       error_log('Upfront summary - searched address: ' . print_r($searched_address, true));
       
       $state = '';
       if (isset($searched_address['administrative_area_level_1'])) {
           $state = $searched_address['administrative_area_level_1'];
       } elseif (isset($searched_address['provinceOrState'])) {
           $state = $searched_address['provinceOrState'];
       }
       
       error_log('Upfront summary - province: ' . $state);
       
       // Get province-specific tax rates
       $tax_rates = WC_Tax::find_rates(array(
           'country'   => 'CA',
           'state'     => $state,
           'city'      => '',
           'postcode'  => ''
       ));
       
       error_log('Upfront summary - tax rates: ' . print_r($tax_rates, true));
       
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


/*--------------DEBUG CART CONTENTS: SEPT. 30TH --------*/

add_action('wp_ajax_debug_cart_contents', 'debug_cart_contents');
add_action('wp_ajax_nopriv_debug_cart_contents', 'debug_cart_contents');

function debug_cart_contents() {
    $cart_items = array();
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $product_id = $cart_item['product_id'];
        $product_name = $cart_item['data']->get_name();
        $cart_items[] = array(
            'id' => $product_id,
            'name' => $product_name,
            'is_payafter' => ($product_id == 267989)
        );
    }
    
    wp_send_json_success(array(
        'cart_items' => $cart_items,
        'payafter_in_cart' => in_array(267989, wp_list_pluck($cart_items, 'id'))
    ));
}



// --------------------Monthly Fee Summary Table Shortcode with Internet Plan and Promotional Pricing

// --------------------Monthly Fee Summary Table Shortcode with Internet Plan and Promotional Pricing

function monthly_fee_summary_shortcode() {
    global $post;

    // Guard: WC cart is not available in admin/REST context (e.g. Divi backend editor)
    if ( is_null( WC()->cart ) ) {
        return '';
    }
    
    $current_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    error_log("Current URL: " . $current_url);
    
    if (strpos($current_url, 'admin-ajax.php') !== false) {
        error_log("AJAX context detected - loading from session");
        if (WC()->session) {
            $stored_product_id = WC()->session->get('current_viewing_product_id');
            if ($stored_product_id) {
                $post = get_post($stored_product_id);
                error_log("✓ Product loaded from session: " . $stored_product_id);
            } else {
                error_log("✗ No product ID in session");
            }
        }
    } else {
        error_log("Normal page context");
        if (!$post && strpos($current_url, '/product/') !== false) {
            $slug = basename(trim(parse_url($current_url, PHP_URL_PATH), '/'));
            $product_query = new WP_Query(array(
                'post_type' => 'product',
                'name' => $slug,
                'posts_per_page' => 1
            ));
            if ($product_query->have_posts()) {
                $post = $product_query->posts[0];
                error_log("✓ Product loaded from URL: " . $post->ID);
            }
        }
    }
    
    if ($post && isset($post->ID) && strpos($current_url, 'admin-ajax.php') === false) {
        if (WC()->session) {
            $old_id = WC()->session->get('current_viewing_product_id');
            if ($old_id != $post->ID) {
                error_log("Updating stored product ID in session: $old_id → " . $post->ID);
            }
            WC()->session->set('current_viewing_product_id', $post->ID);
            error_log("Session updated with current product: " . $post->ID);
        }
    }
    
    error_log("Final product status: " . ($post && isset($post->post_type) && $post->post_type === 'product' ? 'ID=' . $post->ID : 'NO PRODUCT'));
    
    do_action('monthly_fee_summary_calculation');
    
    $cart = WC()->cart;
    $subtotal = 0;
    $internet_plan_in_cart = false;
    $current_product_id = 0;
    
    $installation_category_id = 60;
    $installation_product_id = 267986;
    $deposit_category_id = get_term_by('slug', 'deposit', 'product_cat');
    $deposit_category_id = $deposit_category_id ? $deposit_category_id->term_id : null;
    
    $internet_plan_category = get_term_by('slug', 'internet-plan', 'product_cat');
    $internet_plan_category_id = $internet_plan_category ? $internet_plan_category->term_id : 19;
    
    error_log("=== MONTHLY FEE SUMMARY DEBUG ===");
    error_log("Internet plan category ID: " . $internet_plan_category_id);
    error_log("Is product page: " . ($post && $post->post_type === 'product' ? 'YES' : 'NO'));
    error_log("Post ID: " . ($post ? $post->ID : 'NO POST'));
    error_log("Post Type: " . ($post ? $post->post_type : 'N/A'));

    if ($post && $post->post_type === 'product' && !is_checkout() && !is_cart()) {
        $current_product_id = $post->ID;
        error_log("Current product ID: " . $current_product_id);
        
        if ($current_product_id != $installation_product_id) {
            $current_product = wc_get_product($current_product_id);
            
            if ($current_product && $current_product->get_type() === 'simple') {
                $product_cats = wp_get_post_terms($current_product_id, 'product_cat', array('fields' => 'ids'));
                $is_internet_plan = in_array($internet_plan_category_id, $product_cats);
                
                error_log("Product categories: " . print_r($product_cats, true));
                error_log("Is internet plan: " . ($is_internet_plan ? 'YES' : 'NO'));
                
                if ($is_internet_plan) {
                    error_log("=== CHECKING FOR DIFFERENT INTERNET PLAN ===");
                    error_log("Current product ID: " . $current_product_id);
                    
                    $different_plan_found = false;
                    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
                        $cart_product_cats = wp_get_post_terms($cart_item['product_id'], 'product_cat', array('fields' => 'ids'));
                        $cart_item_is_internet_plan = in_array($internet_plan_category_id, $cart_product_cats);
                        if ($cart_item_is_internet_plan && $cart_item['product_id'] != $current_product_id) {
                            $different_plan_found = true;
                            error_log("✓ Found different internet plan in cart: " . $cart_item['product_id']);
                            break;
                        }
                    }
                    
                    if ($different_plan_found) {
                        error_log("CLEARING ENTIRE CART - Switching from different internet plan");
                        WC()->cart->empty_cart();
                        if (WC()->session) { WC()->session->set('cart', array()); }
                        WC()->cart->set_cart_contents(array());
                        error_log("✓ Cart cleared - Item count after clearing: " . WC()->cart->get_cart_contents_count());
                    } else {
                        error_log("No different internet plan found - keeping existing cart items");
                    }
                    
                    error_log("=== END DIFFERENT PLAN CHECK ===");
                    
                    $current_plan_in_cart = false;
                    foreach ($cart->get_cart() as $cart_item) {
                        if ($cart_item['product_id'] == $current_product_id) {
                            $current_plan_in_cart = true;
                            $internet_plan_in_cart = true;
                            error_log("Current internet plan already in cart");
                            break;
                        }
                    }
                    
                    if (!$current_plan_in_cart) {
                        $added = $cart->add_to_cart($current_product_id, 1);
                        if ($added) {
                            $internet_plan_in_cart = true;
                            error_log("Added current internet plan to cart: " . $current_product_id);
                        }
                    }
                }
            }
        }
    }

    // ── Canonical display order ──────────────────────────────────────────
    $category_order = array(
        'internet-plan' => 1,
        'installation'  => 2,
        'modems-new'    => 3,
        'tv-plan'       => 4,
        'phone-plan'    => 5,
    );

    // Collect rows into buckets keyed by category slug, render in order at end
    $row_buckets = array(); // [ slug => [ 'html' => '', 'price' => 0.0 ] ]

    // ── Internet plan row (product page context) ─────────────────────────
    if ($post && $post->post_type === 'product' && $current_product_id && $internet_plan_in_cart) {
        $monthly_fee = 0;
        if (function_exists('get_field')) {
            $monthly_fee = get_field('monthly_fee', $current_product_id);
            if (empty($monthly_fee) && $monthly_fee !== '0') {
                $monthly_fee = get_field('monthly_fee', 'product_' . $current_product_id);
            }
            $monthly_fee = is_numeric($monthly_fee) ? floatval($monthly_fee) : 0;
        }
        
        $monthly_promo_fee_raw = null;
        if (function_exists('get_field')) {
            $monthly_promo_fee_raw = get_field('monthly_promo_fee', $current_product_id);
            if ($monthly_promo_fee_raw === null) {
                $monthly_promo_fee_raw = get_field('monthly_promo_fee', 'product_' . $current_product_id);
            }
        }
        $promo_is_set = is_numeric($monthly_promo_fee_raw);
        $monthly_promo_fee = $promo_is_set ? floatval($monthly_promo_fee_raw) : null;
        
        $monthly_promo_blurb = '';
        if (function_exists('get_field')) {
            $monthly_promo_blurb = get_field('monthly_promo_blurb', $current_product_id);
            if (empty($monthly_promo_blurb)) {
                $monthly_promo_blurb = get_field('monthly_promo_blurb', 'product_' . $current_product_id);
            }
        }
        
        $final_monthly_fee = $promo_is_set ? $monthly_promo_fee : $monthly_fee;
        $current_product   = wc_get_product($current_product_id);
        $product_name_display = esc_html($current_product->get_name());
        if (!empty($monthly_promo_blurb)) {
            $product_name_display .= '<br><span class="promo-blurb" style="color: green; font-style: italic; font-size: 0.9em;">' . esc_html($monthly_promo_blurb) . '</span>';
        }
        
        if ($promo_is_set && $monthly_promo_fee != $monthly_fee) {
            $pricing_display = '<span class="monthly-fee-original-price" style="text-decoration: line-through;">' . wc_price($monthly_fee) . '</span><br><span class="monthly-fee-sale-price">' . wc_price($monthly_promo_fee) . '</span>';
        } else {
            $pricing_display = wc_price($monthly_fee);
        }
        
        $row  = '<tr>';
        $row .= '<td>' . $product_name_display . '</td>';
        $row .= '<td>Internet Plan</td>';
        $row .= '<td>' . $pricing_display . '</td>';
        $row .= '</tr>';
        
        $row_buckets['internet-plan'] = array( 'html' => $row, 'price' => $final_monthly_fee );
    }
    
    // ── Cart item rows ───────────────────────────────────────────────────
    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        $product    = $cart_item['data'];
        $product_id = $product->get_id();
        $parent_id  = $product->get_parent_id();
        
        $product_cats = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
        
        $is_installation = in_array($installation_category_id, $product_cats) || $parent_id == $installation_product_id;
        if ($is_installation) { continue; }
        
        $is_deposit = $deposit_category_id && in_array($deposit_category_id, $product_cats);
        if ($is_deposit) { continue; }
        
        if ($product_id == 267989) { continue; }
        
        $is_internet_plan_item = in_array($internet_plan_category_id, $product_cats);
        if ($is_internet_plan_item && $post && $post->post_type === 'product' && $current_product_id == $product_id) {
            continue; // Already added above
        }
        
        // Monthly fee
        $monthly_fee = 0;
        if (function_exists('get_field')) {
            $monthly_fee = get_field('monthly_fee', $product_id);
            if (empty($monthly_fee) && $monthly_fee !== '0') {
                $monthly_fee = get_field('monthly_fee', 'product_' . $product_id);
            }
            $monthly_fee = is_numeric($monthly_fee) ? floatval($monthly_fee) : 0;
        }
        
        // Promo pricing
        $monthly_promo_fee_raw = null;
        if (function_exists('get_field')) {
            $monthly_promo_fee_raw = get_field('monthly_promo_fee', $product_id);
            if ($monthly_promo_fee_raw === null) {
                $monthly_promo_fee_raw = get_field('monthly_promo_fee', 'product_' . $product_id);
            }
        }
        $promo_is_set      = is_numeric($monthly_promo_fee_raw);
        $monthly_promo_fee = $promo_is_set ? floatval($monthly_promo_fee_raw) : null;
        
        $monthly_promo_blurb = '';
        if (function_exists('get_field')) {
            $monthly_promo_blurb = get_field('monthly_promo_blurb', $product_id);
            if (empty($monthly_promo_blurb)) {
                $monthly_promo_blurb = get_field('monthly_promo_blurb', 'product_' . $product_id);
            }
        }
        
        $final_monthly_fee = $promo_is_set ? $monthly_promo_fee : $monthly_fee;
        
        $product_name         = $product->get_name();
        $product_name_display = esc_html($product_name);
        if (!empty($monthly_promo_blurb)) {
            $product_name_display .= '<br><span class="promo-blurb" style="color: green; font-style: italic; font-size: 0.9em;">' . esc_html($monthly_promo_blurb) . '</span>';
        }
        
        // ── Category resolution (identical to old version) ───────────────
        $category_name = 'Product';
        $category_slug = '';
        
        if ($parent_id > 0) {
            $terms = get_the_terms($parent_id, 'product_cat');
        } else {
            $terms = get_the_terms($product_id, 'product_cat');
        }
        
        if (!empty($terms) && !is_wp_error($terms)) {
            if ($is_internet_plan_item) {
                $internet_plan_term = get_term($internet_plan_category_id, 'product_cat');
                if ($internet_plan_term && !is_wp_error($internet_plan_term)) {
                    $category_name = $internet_plan_term->name;
                    $category_slug = $internet_plan_term->slug;
                } else {
                    $category_name = 'Internet Plan';
                    $category_slug = 'internet-plan';
                }
            } else {
                $primary_category_slug = dg_get_primary_product_category($product_cats);
                if ($primary_category_slug) {
                    $primary_term = get_term_by('slug', $primary_category_slug, 'product_cat');
                    if ($primary_term && !is_wp_error($primary_term)) {
                        $category_name = $primary_term->name;
                        $category_slug = $primary_term->slug;
                    } else {
                        $category_name = ucfirst(str_replace('-', ' ', $primary_category_slug));
                        $category_slug = $primary_category_slug;
                    }
                } else {
                    $category_name = $terms[0]->name;
                    $category_slug = $terms[0]->slug;
                }
            }
        }
        
        // Pricing display
        if ($promo_is_set && $monthly_promo_fee != $monthly_fee) {
            $pricing_display = '<span class="monthly-fee-original-price" style="text-decoration: line-through;">' . wc_price($monthly_fee) . '</span><br><span class="monthly-fee-sale-price">' . wc_price($monthly_promo_fee) . '</span>';
        } else {
            $pricing_display = wc_price($monthly_fee);
        }
        
        $row  = '<tr>';
        $row .= '<td>' . $product_name_display . '</td>';
        $row .= '<td>' . esc_html($category_name) . '</td>';
        $row .= '<td>' . $pricing_display . '</td>';
        $row .= '</tr>';

        // ── Determine bucket key from $category_slug ─────────────────────
        // Normalise modems slug (dg_get_primary_product_category returns 'modems', order uses 'modems-new')
        $bucket_key = $category_slug;
        if ( $bucket_key === 'modems' ) {
            $bucket_key = 'modems-new';
        }
        // Internet plan items not on product page land here
        if ( $is_internet_plan_item ) {
            $bucket_key = 'internet-plan';
        }

        if ( isset( $category_order[ $bucket_key ] ) ) {
            $row_buckets[ $bucket_key ] = array( 'html' => $row, 'price' => $final_monthly_fee );
        } else {
            // Unknown category — append at end outside buckets (shouldn't happen normally)
            $row_buckets[ 'zzz_' . $product_id ] = array( 'html' => $row, 'price' => $final_monthly_fee );
        }
    }

    // ── Build output in canonical order ──────────────────────────────────
    $output  = '<table class="fee-summary-table monthly-fee-table">';
    $output .= '<thead><tr><th>Product</th><th>Category</th><th>Monthly Fee</th></tr></thead>';
    $output .= '<tbody>';

    foreach ( $category_order as $slug => $position ) {
        if ( isset( $row_buckets[ $slug ] ) ) {
            $output   .= $row_buckets[ $slug ]['html'];
            $subtotal += $row_buckets[ $slug ]['price'];
        }
    }
    // Append any unknown-category items
    foreach ( $row_buckets as $key => $bucket ) {
        if ( strpos( $key, 'zzz_' ) === 0 ) {
            $output   .= $bucket['html'];
            $subtotal += $bucket['price'];
        }
    }
    
    // ── Tax ──────────────────────────────────────────────────────────────
    $tax_total = 0;
    if (wc_tax_enabled()) {
        $searched_address = dg_get_user_meta("searched_address");
        error_log('Monthly summary - searched address: ' . print_r($searched_address, true));
        
        $state = '';
        if (isset($searched_address['administrative_area_level_1'])) {
            $state = $searched_address['administrative_area_level_1'];
        } elseif (isset($searched_address['provinceOrState'])) {
            $state = $searched_address['provinceOrState'];
        }
        error_log('Monthly summary - province: ' . $state);
        
        $tax_rates = WC_Tax::find_rates(array(
            'country'   => 'CA',
            'state'     => $state,
            'city'      => '',
            'postcode'  => ''
        ));
        error_log('Monthly summary - tax rates: ' . print_r($tax_rates, true));
        
        if (!empty($tax_rates)) {
            $taxes     = WC_Tax::calc_tax($subtotal, $tax_rates);
            $tax_total = array_sum($taxes);
        }
    }
    
    $output .= '<tr class="subtotal-row"><td colspan="2">Subtotal</td><td>' . wc_price($subtotal) . '</td></tr>';
    $output .= '<tr class="tax-row"><td colspan="2">Tax</td><td>' . wc_price($tax_total) . '</td></tr>';
    $output .= '<tr class="total-row"><td colspan="2">Total Monthly</td><td>' . wc_price($subtotal + $tax_total) . '</td></tr>';
    
    $output .= '</tbody></table>';
    
    error_log("Final internet_plan_in_cart status: " . ($internet_plan_in_cart ? 'TRUE' : 'FALSE'));
    error_log("=== END MONTHLY FEE SUMMARY DEBUG ===");
    
    return $output;
}
add_shortcode('monthly_fee_summary', 'monthly_fee_summary_shortcode');


// -------- Edit Order popup Shortcode

function edit_order_popup_shortcode() {

	// Guard: WC cart is not available in admin/REST context (e.g. Divi backend editor)
    if ( is_null( WC()->cart ) ) {
        return '';
    }


    $cart = WC()->cart;
    
    if ($cart->is_empty()) {
        return '<table class="edit-order-table"><thead><tr><th>Product</th><th>Category</th><th>Edit</th></tr></thead><tbody><tr><td colspan="3">No products in cart</td></tr></tbody></table>';
    }
    
    $deposit_category_id = get_term_by('slug', 'deposit', 'product_cat');
    $deposit_category_id = $deposit_category_id ? $deposit_category_id->term_id : null;
    $installation_category_id = 60;
    $installation_parent_product_id = 267986;
    $pay_after_deposit_id = 267989;
    
    $internet_plan_slug = '';
    $internet_plan_category_id = 19;
    
    foreach ($cart->get_cart() as $cart_item) {
        $product_id = $cart_item['product_id'];
        $product_cats = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
        
        if (in_array($internet_plan_category_id, $product_cats)) {
            $internet_plan_product = wc_get_product($product_id);
            if ($internet_plan_product) {
                $internet_plan_url = get_permalink($product_id);
                $internet_plan_slug = basename(parse_url($internet_plan_url, PHP_URL_PATH));
            }
            break;
        }
    }
    
    $category_slides = array(
        'installation' => 'screen1',
        'modems-new' => 'screen2',
        'tv-plan' => 'screen3',
        'phone-plan' => 'screen4'
    );
    
    $category_order = array(
        'internet-plan' => 1,
        'installation' => 2,
        'modems-new' => 3,
        'tv-plan' => 4,
        'phone-plan' => 5
    );
    
    $organized_items = array();
    
    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        $product_id = $product->get_id();
        $parent_id = $product->get_parent_id();
        
        $product_cats = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
        
        if ($product_id == $pay_after_deposit_id) {
            continue;
        }
        
        if ($deposit_category_id && in_array($deposit_category_id, $product_cats)) {
            continue;
        }
        
        $product_name = $product->get_name();
        
        $is_installation = in_array($installation_category_id, $product_cats) || $parent_id == $installation_parent_product_id;
        
        $category_name = '';
        $category_slug = '';
        
        if ($is_installation && $parent_id) {
            $terms = get_the_terms($parent_id, 'product_cat');
        } else {
            $terms = get_the_terms($product_id, 'product_cat');
        }
        
        if (!empty($terms) && !is_wp_error($terms)) {
            $is_internet_plan = in_array($internet_plan_category_id, $product_cats);
            
            if ($is_internet_plan) {
                $internet_plan_term = get_term($internet_plan_category_id, 'product_cat');
                if ($internet_plan_term && !is_wp_error($internet_plan_term)) {
                    $category_name = $internet_plan_term->name;
                    $category_slug = $internet_plan_term->slug;
                } else {
                    $category_name = 'Internet Plan';
                    $category_slug = 'internet-plan';
                }
            } else {
                $functional_slugs = array('modems-new', 'tv-plan', 'phone-plan', 'installation');
                $category_name = $terms[0]->name;
                $category_slug = $terms[0]->slug;
                $is_modem = in_array('modems-new', array_column((array)$terms, 'slug'));
                
                foreach ($terms as $term) {
                    if (in_array($term->slug, $functional_slugs)) {
                        $category_slug = $term->slug;
                        if (!$is_modem) {
                            $category_name = $term->name;
                        }
                        break;
                    }
                }
                
                    if ($is_modem) {
    $modems_term = get_term_by('slug', 'modems-new', 'product_cat');
    $category_name = $modems_term ? $modems_term->name : 'Modems';
				}
            }
        }
        
        $monthly_fee = 0;
        if (function_exists('get_field')) {
            $monthly_fee = get_field('monthly_fee', $product_id);
            
            if (empty($monthly_fee) && $monthly_fee !== '0') {
                $monthly_fee = get_field('monthlyfee', 'product' . $product_id);
            }
            
            $monthly_fee = is_numeric($monthly_fee) ? floatval($monthly_fee) : 0;
        }
        
        $product_attributes = '';
        
        if ($is_installation) {
            $installation_dates = array();
            if (isset($cart_item['variation']) && is_array($cart_item['variation'])) {
                $installation_dates = array(
                    'preferred-date' => isset($cart_item['variation']['attribute_preferred-date']) ?
                        $cart_item['variation']['attribute_preferred-date'] : '',
                    'secondary-date' => isset($cart_item['variation']['attribute_secondary-date']) ?
                        $cart_item['variation']['attribute_secondary-date'] : ''
                );
            }
            
            if (!empty($installation_dates['preferred-date']) && !empty($installation_dates['secondary-date'])) {
                $product_attributes = '<br><small style="color: #666;">' .
                    esc_html($installation_dates['preferred-date'] . ', ' . $installation_dates['secondary-date']) .
                    '</small>';
            } elseif (!empty($installation_dates['preferred-date'])) {
                $product_attributes = '<br><small style="color: #666;">' .
                    esc_html($installation_dates['preferred-date']) .
                    '</small>';
            }
        }
        
        $item_data = array(
            'product_id' => $product_id,
            'product_name' => $product_name,
            'category_name' => $category_name,
            'category_slug' => $category_slug,
            'monthly_fee' => $monthly_fee,
            'product_cats' => $product_cats,
            'cart_item_key' => $cart_item_key,
            'product_attributes' => $product_attributes,
            'is_installation' => $is_installation,
            'parent_id' => $parent_id,
            'order' => $is_installation ? 2 : (isset($category_order[$category_slug]) ? $category_order[$category_slug] : 999)
        );
        
        $organized_items[] = $item_data;
    }
    
    usort($organized_items, function($a, $b) {
        return $a['order'] - $b['order'];
    });
    
    $output = '<table class="edit-order-table">';
    $output .= '<thead><tr><th>Product</th><th>Category</th><th>Edit Product</th></tr></thead>';
    $output .= '<tbody>';
    
    $has_items = false;
    
    foreach ($organized_items as $item) {
        $edit_url = '';
        $edit_button = '';
        $internet_plan_warning = '';
        
        if (in_array($internet_plan_category_id, $item['product_cats'])) {
            $edit_url = 'https://staging.diallog.com/residential/internet/';
            $edit_button = '<a href="' . esc_url($edit_url) . '" class="edit-product-btn btn-primary">Edit</a>';
            
            $internet_plan_warning = '<br><div class="internet-plan-warning" style="display: flex; align-items: center; margin-top: 5px; color: #e74c3c; font-size: 12px;">' .
                '<i class="fa fa-exclamation-triangle" style="margin-right: 5px; font-size: 14px;" aria-hidden="true"></i>' .
                '<span>Changing internet plans will clear all previous selections</span>' .
                '</div>';
        }
        elseif ($item['is_installation']) {
            if (!empty($internet_plan_slug)) {
                $edit_url = 'https://staging.diallog.com/product/' . $internet_plan_slug . '/#screen1';
                $edit_button = '<a href="' . esc_url($edit_url) . '" class="edit-product-btn btn-primary">Edit</a>';
            } else {
                $edit_button = '<span class="edit-product-btn btn-disabled">Edit</span>';
            }
        }
        elseif (!empty($internet_plan_slug) && isset($category_slides[$item['category_slug']])) {
            $slide_anchor = $category_slides[$item['category_slug']];
            $edit_url = 'https://staging.diallog.com/product/' . $internet_plan_slug . '/#' . $slide_anchor;
            $edit_button = '<a href="' . esc_url($edit_url) . '" class="edit-product-btn btn-primary">Edit</a>';
        }
        elseif (!empty($internet_plan_slug)) {
            $edit_url = 'https://staging.diallog.com/product/' . $internet_plan_slug . '/';
            $edit_button = '<a href="' . esc_url($edit_url) . '" class="edit-product-btn btn-primary">Edit</a>';
        }
        else {
            $edit_button = '<span class="edit-product-btn btn-disabled">Edit</span>';
        }
        
        $output .= '<tr class="edit-order-row" data-product-id="' . esc_attr($item['product_id']) . '" data-category="' . esc_attr($item['category_slug']) . '">';
        $output .= '<td class="product-name">' . esc_html($item['product_name']) . $item['product_attributes'] . $internet_plan_warning . '</td>';
        $output .= '<td class="product-category">' . esc_html($item['category_name']) . '</td>';
        $output .= '<td class="product-edit">' . $edit_button . '</td>';
        $output .= '</tr>';
        
        $has_items = true;
    }
    
    if (!$has_items) {
        $output .= '<tr><td colspan="3">No editable products in cart</td></tr>';
    }
    
    $output .= '</tbody></table>';
    
    return $output;
}
add_shortcode('edit_order_popup', 'edit_order_popup_shortcode');


/*========Total Monthly Fee Shortcode=======*/

function monthly_fee_total_shortcode($atts) {
    global $post;

    if ( is_null( WC()->cart ) ) {
        return '$0.00';
    }

    $cart = WC()->cart;

    if ($cart->is_empty() && (!is_product() || !$post)) {
        return '$0.00';
    }

    $subtotal = 0;
    $current_product_id = 0;

    $installation_category_id = 60;
    $installation_product_id = 267986;
    $deposit_category_id = get_term_by('slug', 'deposit', 'product_cat');
    $deposit_category_id = $deposit_category_id ? $deposit_category_id->term_id : null;

    if (is_product() && $post) {
        $current_product_id = $post->ID;

        if ($current_product_id != $installation_product_id) {
            $current_product = wc_get_product($current_product_id);

            if ($current_product && $current_product->get_type() === 'simple') {
                $product_cats = wp_get_post_terms($current_product_id, 'product_cat', array('fields' => 'ids'));
                $is_internet_plan = in_array(19, $product_cats);

                if ($is_internet_plan) {
                    $monthly_fee = 0;
                    if (function_exists('get_field')) {
                        $monthly_fee = get_field('monthly_fee', $current_product_id);
                        if (empty($monthly_fee) && $monthly_fee !== '0') {
                            $monthly_fee = get_field('monthly_fee', 'product_' . $current_product_id);
                        }
                        $monthly_fee = is_numeric($monthly_fee) ? floatval($monthly_fee) : 0;
                    }

                    $monthly_promo_fee_raw = null;
                    if (function_exists('get_field')) {
                        $monthly_promo_fee_raw = get_field('monthly_promo_fee', $current_product_id);
                        if ($monthly_promo_fee_raw === null) {
                            $monthly_promo_fee_raw = get_field('monthly_promo_fee', 'product_' . $current_product_id);
                        }
                    }
                    $promo_is_set = is_numeric($monthly_promo_fee_raw);
                    $monthly_promo_fee = $promo_is_set ? floatval($monthly_promo_fee_raw) : null;

                    $final_monthly_fee = $promo_is_set ? $monthly_promo_fee : $monthly_fee;
                    $subtotal += $final_monthly_fee;
                }
            }
        }
    }

    foreach ($cart->get_cart() as $cart_item) {
        $product_id = $cart_item['product_id'];

        $product_cats = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
        $is_installation = in_array($installation_category_id, $product_cats);

        if ($is_installation || $product_id == $installation_product_id) {
            continue;
        }

        $is_deposit = $deposit_category_id && in_array($deposit_category_id, $product_cats);
        if ($is_deposit) {
            continue;
        }

        if ($product_id == 267989) {
            continue;
        }

        if (is_product() && $post && $product_id == $current_product_id) {
            continue;
        }

        $monthly_fee = 0;
        if (function_exists('get_field')) {
            $monthly_fee = get_field('monthly_fee', $product_id);
            if (empty($monthly_fee) && $monthly_fee !== '0') {
                $monthly_fee = get_field('monthly_fee', 'product_' . $product_id);
            }
            $monthly_fee = is_numeric($monthly_fee) ? floatval($monthly_fee) : 0;
        }

        if ($monthly_fee <= 0) {
            continue;
        }

        $monthly_promo_fee_raw = null;
        if (function_exists('get_field')) {
            $monthly_promo_fee_raw = get_field('monthly_promo_fee', $product_id);
            if ($monthly_promo_fee_raw === null) {
                $monthly_promo_fee_raw = get_field('monthly_promo_fee', 'product_' . $product_id);
            }
        }
        $promo_is_set = is_numeric($monthly_promo_fee_raw);
        $monthly_promo_fee = $promo_is_set ? floatval($monthly_promo_fee_raw) : null;

        $final_monthly_fee = $promo_is_set ? $monthly_promo_fee : $monthly_fee;
        $subtotal += $final_monthly_fee * $cart_item['quantity'];
    }

    $tax_total = 0;
    if (wc_tax_enabled()) {
        $searched_address = dg_get_user_meta("searched_address");
        $state = '';
        if (isset($searched_address['administrative_area_level_1'])) {
            $state = $searched_address['administrative_area_level_1'];
        } elseif (isset($searched_address['provinceOrState'])) {
            $state = $searched_address['provinceOrState'];
        }
        $tax_rates = WC_Tax::find_rates(array(
            'country'   => 'CA',
            'state'     => $state,
            'city'      => '',
            'postcode'  => ''
        ));
        if (!empty($tax_rates)) {
            $taxes = WC_Tax::calc_tax($subtotal, $tax_rates);
            $tax_total = array_sum($taxes);
        }
    }

    return wc_price($subtotal + $tax_total);
}
add_shortcode('monthly_fee_total', 'monthly_fee_total_shortcode');


/*====Modem Add to Cart AJAX====*/

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
                $product_cats = wp_get_post_terms($cart_product_id, 'product_cat', array('fields' => 'ids'));
                $is_cart_item_internet_plan = in_array(19, $product_cats);
                if ($is_cart_item_internet_plan) {
                    WC()->cart->remove_cart_item($cart_item_key);
                }
            }
            $added = WC()->cart->add_to_cart($product_id, 1);
        }
        // If this is a phone plan
        elseif ($product_type === 'phone') {
            $phone_category_id = 22;
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $cart_product_id = $cart_item['product_id'];
                $product_cats = wp_get_post_terms($cart_product_id, 'product_cat', array('fields' => 'ids'));
                $is_phone_plan = in_array($phone_category_id, $product_cats);
                if ($is_phone_plan) {
                    WC()->cart->remove_cart_item($cart_item_key);
                }
            }
            $added = WC()->cart->add_to_cart($product_id, 1);
        }
        // If this is a TV plan
        elseif ($product_type === 'tv') {
            $tv_category_id = 61;
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $cart_product_id = $cart_item['product_id'];
                $product_cats = wp_get_post_terms($cart_product_id, 'product_cat', array('fields' => 'ids'));
                $is_tv_plan = in_array($tv_category_id, $product_cats);
                if ($is_tv_plan) {
                    WC()->cart->remove_cart_item($cart_item_key);
                }
            }
            $added = WC()->cart->add_to_cart($product_id, 1);
        }
        // If this is a modem
        elseif ($product_type === 'modem') {
            $modem_category_id = 59;
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $cart_product_id = $cart_item['product_id'];
                $product_cats = wp_get_post_terms($cart_product_id, 'product_cat', array('fields' => 'ids'));
                $is_modem = in_array($modem_category_id, $product_cats);
                if ($is_modem) {
                    WC()->cart->remove_cart_item($cart_item_key);
                }
            }
            $added = WC()->cart->add_to_cart($product_id, 1);
        }
        // For any other product type
        else {
            $added = WC()->cart->add_to_cart($product_id, 1);
        }

        if ($added) {
            $upfront_total_display = '$0.00';
            if (function_exists('get_upfront_fee_summary')) {
                $summary = get_upfront_fee_summary();
                $upfront_total_display = wc_price($summary['grand_total'][1]);
            }
            wp_send_json_success(array(
                'message' => 'Product added to cart',
                'product_id' => $product_id,
                'upfront_total' => $upfront_total_display
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'Failed to add product to cart'
            ));
        }
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

	// Guard: WC cart is not available in admin/REST context (e.g. Divi backend editor)
    if ( is_null( WC()->cart ) ) {
        return '';
    }
    // Get cart items
    $cart = WC()->cart;
    
    if ($cart->is_empty()) {
        $total_display = '$0.00';
    } else {
        // NEW: Get dynamic install price from internet plan in cart
        $internet_plan_cat_id = 19;
        $dynamic_install_price = 0;
        
        foreach ($cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            $product_id = $product->get_id();
            $product_cat_ids = $product->get_category_ids();
            
            // Check if this product has internet-plan category (check ALL categories)
            if (in_array($internet_plan_cat_id, $product_cat_ids)) {
                if (function_exists('get_field')) {
                    $dynamic_install_price = get_field('dynamic_install_price', $product_id);
                    
                    if ($dynamic_install_price === '' || $dynamic_install_price === null || $dynamic_install_price === false) {
                        $dynamic_install_price = get_field('dynamic_install_price', 'product_' . $product_id);
                    }
                    
                    if ($dynamic_install_price !== '' && $dynamic_install_price !== null && $dynamic_install_price !== false) {
                        $dynamic_install_price = floatval($dynamic_install_price);
                    } else {
                        $dynamic_install_price = null;
                    }
                }
                
                error_log('Upfront total shortcode - Found internet plan: ' . $product_id . ', dynamic price: ' . $dynamic_install_price);
                break;
            }
        }
        
        $subtotal = 0;
        $deposit_total = 0;
        
        // Loop through cart items
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $product_id = $product->get_id();
            $parent_id = $product->get_parent_id();
            $product_price = $product->get_price();
            
            $installation_category_id = 60;
            $installation_parent_product_id = 267986;
            $product_cats = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
            $is_installation = in_array($installation_category_id, $product_cats) || 
                   $parent_id == $installation_parent_product_id || 
                   $product_id == $installation_parent_product_id;
            
            if ($is_installation && $dynamic_install_price !== null) {
                $product_price = $dynamic_install_price;
                error_log('Upfront total shortcode - Using dynamic install price: ' . $product_price);
            }
            
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
        $total_display = wc_price($total);
    }
    
      // Use span instead of div to avoid Divi stripping it out
    return '<div class="upfront-fee-total-container" data-shortcode="upfront_fee_total">' .
           '<span class="upfront-fee-content" style="display:block;">' . $total_display . '</span>' .
           '</div>';
}
add_shortcode('upfront_fee_total', 'upfront_fee_total_shortcode');


/**
 * AJAX handler to get upfront fee total
 * Used for dynamic updates
 */
function get_upfront_fee_total_ajax() {
    check_ajax_referer('modem_selection_nonce', 'nonce');
    
    $cart = WC()->cart;
    
    if ($cart->is_empty()) {
        $total_display = '$0.00';
    } else {
        $internet_plan_cat_id = 19;
        $dynamic_install_price = 0;
        
        foreach ($cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            $product_id = $product->get_id();
            $product_cat_ids = $product->get_category_ids();
            
            if (in_array($internet_plan_cat_id, $product_cat_ids)) {
                if (function_exists('get_field')) {
                    $dynamic_install_price = get_field('dynamic_install_price', $product_id);
        
                    if ($dynamic_install_price === '' || $dynamic_install_price === null || $dynamic_install_price === false) {
                        $dynamic_install_price = get_field('dynamic_install_price', 'product_' . $product_id);
                    }
        
                    if ($dynamic_install_price !== '' && $dynamic_install_price !== null && $dynamic_install_price !== false) {
                        $dynamic_install_price = floatval($dynamic_install_price);
                    } else {
                        $dynamic_install_price = null;
                    }
                }
                break;
            }
        }
        
        $subtotal = 0;
        $deposit_total = 0;
        
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $product_id = $product->get_id();
            $parent_id = $product->get_parent_id();
            $product_price = $product->get_price();
            
            $installation_category_id = 60;
            $installation_parent_product_id = 267986;
            $product_cats = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
            $is_installation = in_array($installation_category_id, $product_cats) || 
                               $parent_id == $installation_parent_product_id || 
                               $product_id == $installation_parent_product_id;
            
            if ($is_installation && $dynamic_install_price !== null) {
                $product_price = $dynamic_install_price;
            }
            
            $subtotal += $product_price;
            
            $deposit_fee = 0;
            if (function_exists('get_field')) {
                $deposit_fee = get_field('deposit-fee', $product_id);
                
                if (empty($deposit_fee) && $deposit_fee !== '0') {
                    $deposit_fee = get_field('deposit-fee', 'product_' . $product_id);
                }
                
                $deposit_fee = is_numeric($deposit_fee) ? floatval($deposit_fee) : 0;
                $deposit_total += $deposit_fee;
            }
        }
        
        $tax_total = 0;
        if (wc_tax_enabled()) {
            $searched_address = dg_get_user_meta("searched_address");
            
            $state = '';
            if (isset($searched_address['administrative_area_level_1'])) {
                $state = $searched_address['administrative_area_level_1'];
            } elseif (isset($searched_address['provinceOrState'])) {
                $state = $searched_address['provinceOrState'];
            }
            
            $tax_rates = WC_Tax::find_rates(array(
                'country'   => 'CA',
                'state'     => $state,
                'city'      => '',
                'postcode'  => ''
            ));
            
            if (!empty($tax_rates)) {
                $taxes = WC_Tax::calc_tax($subtotal, $tax_rates);
                $tax_total = array_sum($taxes);
            }
        }
        
        $total = $subtotal + $tax_total + $deposit_total;
        $total_display = wc_price($total);
    }
    
    wp_send_json_success(array(
        'total' => $total_display
    ));
    
    wp_die();
}
add_action('wp_ajax_get_upfront_fee_total', 'get_upfront_fee_total_ajax');
add_action('wp_ajax_nopriv_get_upfront_fee_total', 'get_upfront_fee_total_ajax');


/*Update Fee Summary Tables*/

function update_fee_summary_tables() {
    check_ajax_referer('modem_selection_nonce', 'nonce');
    
    // Get current product ID from AJAX request
    $current_product_id = isset($_POST['current_product_id']) ? absint($_POST['current_product_id']) : 0;
    
    // Store current product ID in session for use in shortcodes
    WC()->session->set('current_product_id', $current_product_id);
    
    $upfront_table = upfront_fee_summary_shortcode();
    $monthly_table = monthly_fee_summary_shortcode();

	  // Compute the total while we're already here — no extra AJAX needed
    $upfront_total_display = '$0.00';
    if (function_exists('get_upfront_fee_summary')) {
        $summary = get_upfront_fee_summary();
        $upfront_total_display = wc_price($summary['grand_total'][1]);
    }
    
    wp_send_json_success(array(
        'upfront_table' => $upfront_table,
        'monthly_table' => $monthly_table,
		'upfront_total' => $upfront_total_display
    ));
    
    wp_die();
}
add_action('wp_ajax_update_fee_tables', 'update_fee_summary_tables');
add_action('wp_ajax_nopriv_update_fee_tables', 'update_fee_summary_tables');

// ========= AJAX handler to remove product from cart

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

			$upfront_total_display = '$0.00';
		if (function_exists('get_upfront_fee_summary')) {
    		$summary = get_upfront_fee_summary();
    		$upfront_total_display = wc_price($summary['grand_total'][1]);
		}
        
            wp_send_json_success(array(
                'message' => 'Product removed from cart',
                'product_id' => $product_id,
				'upfront_total' => $upfront_total_display
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
    
    $installation_category_id = 60; // Installation date category ID
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
    $installation_category_id = 60;
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


/*=======Upfront Fee Summary ======*/


function get_upfront_fee_summary() {
    
    $summary = array(
        'ModemPurchaseOption'=>true,
        'internet-plan'=>array('',0.0),
        'modems'=>array('Modem Deposit',0.0),
        'installation'=>array('Installation Fee',0.0),
        'deposit'=>array('Pay-after Deposit',0.0),
        'phone-plan'=>array('Phone Plan',0.0),
        'tv-plan'=>array('TV Plan',0.0),
        'subtotal'=>array('Subtotal',0.0),
        'taxes'=>array('Taxes',0.0),
        'grand_total'=>array('UPFRONT TOTAL',0.0));

    $tax_rate = function_exists('GetTaxRate') ? GetTaxRate() : 0;
    $do_not_include_modem_deposit = false;
    $show_included_taxes = wc_tax_enabled() && WC()->cart->display_prices_including_tax();
    $total_deposits = 0;
    $tv_deposit = 0;
    $phone_deposit = 0;
    $modem_deposit = 0;
    
    // NEW: Get dynamic install price from internet plan in cart
    $internet_plan_cat_id = 19;
    $dynamic_install_price = 0;
    
    foreach (WC()->cart->get_cart() as $cart_item) {
    $product = $cart_item['data'];
    $product_id = $product->get_id();
    $product_cat_ids = $product->get_category_ids();
    
    // Check if this product has internet-plan category (check ALL categories)
    if (in_array($internet_plan_cat_id, $product_cat_ids)) {
        if (function_exists('get_field')) {
            $dynamic_install_price = get_field('dynamic_install_price', $product_id);
            
            if ($dynamic_install_price === '' || $dynamic_install_price === null || $dynamic_install_price === false) {
                $dynamic_install_price = get_field('dynamic_install_price', 'product_' . $product_id);
            }
            
            // Convert to float if we have a value (including 0)
            if ($dynamic_install_price !== '' && $dynamic_install_price !== null && $dynamic_install_price !== false) {
                $dynamic_install_price = floatval($dynamic_install_price);
            } else {
                $dynamic_install_price = null;
            }
        }
        
        break;
        }
    }
    
    
    foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
         
        $_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
        if( $_product == false || $_product->exists() == false || $cart_item['quantity'] <= 0 ||
            apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) == false ) {
            continue;
        }
        
        $product_cat_ids = $_product->get_category_ids();
        
        // FIX: Handle product variations - check parent product categories if variation has none
        if (empty($product_cat_ids) && $_product->get_parent_id() > 0) {
            $parent_product = wc_get_product($_product->get_parent_id());
            if ($parent_product) {
                $product_cat_ids = $parent_product->get_category_ids();
            }
        }
        
        // FIX: Handle empty category array to prevent PHP notices
        if (empty($product_cat_ids)) {
            continue;
        }
        
        // Get primary category (ignoring provider categories)
        $product_category = dg_get_primary_product_category($product_cat_ids);

        if ($product_category === null) {
            continue;
        
        }
        $product_id = $_product->get_id();
        
        
        // FIX: Handle 'modems-new' category - treat it as 'modems'
        if ($product_category == 'modems-new') {
            $product_category = 'modems';
        }
         
        if (array_key_exists($product_category,$summary) && $product_category != 'installation') {

            // Handle specific product exclusions
            if( $_product->get_id() == 264 ) {
                $summary[$product_category][0] = $_product->get_title()." ".( $show_included_taxes ?"(inc taxes)":"")."" ;
                $summary[$product_category][1] = round( floatval(2), 2 );
                $summary['taxes'][1] += round( floatval( ($summary[$product_category][1] * $tax_rate) / 100 ), 2);
                continue;
            }

            if( $_product->get_id() == 887 ){
                $summary[$product_category][0] = $_product->get_title()." ".( $show_included_taxes ?"(inc taxes)":"")."" ;
                $summary[$product_category][1] = round ( floatval(0), 2 );
                $summary['taxes'][1] += round( floatval( ($summary[$product_category][1] * $tax_rate) / 100 ), 2);
                continue;
            }
            
            // Check for ACF deposit fields first (NEW METHOD)
            $deposit_fee = 0;
            if (function_exists('get_field')) {
                $deposit_fee = get_field('deposit-fee', $product_id);
                if (empty($deposit_fee) && $deposit_fee !== '0') {
                    $deposit_fee = get_field('deposit-fee', 'product_' . $product_id);
                }
                $deposit_fee = is_numeric($deposit_fee) ? floatval($deposit_fee) : 0;
            }
            
            // Check for legacy product attribute method (OLD METHOD)
            $security_deposit_attr = 0;
            if ($deposit_fee == 0 && $product_category == "modems") {
                $security_deposit_attr = $_product->get_attribute("Security Deposit");
                $security_deposit_attr = is_numeric($security_deposit_attr) ? floatval($security_deposit_attr) : 0;
            }
            
            // Use whichever deposit method has a value
            $final_deposit = max($deposit_fee, $security_deposit_attr);
            
            
            
            // Handle modem with deposit
            if ( $product_category == "modems" && $final_deposit > 0 ) {
                $modem_price = floatval($_product->get_regular_price());
                $summary[$product_category][0] = $_product->get_title()." ".( $show_included_taxes ?"(inc taxes)":"")."" ;
                $summary[$product_category][1] = round($modem_price, 2);
                $summary['taxes'][1] += round(floatval(($modem_price * $tax_rate) / 100), 2);
                $do_not_include_modem_deposit = true;
                $modem_deposit = $final_deposit;
            } else {
                // Handle regular products
                $product_price = $_product->get_price();
                
                // SPECIAL CASE: For deposit category products, don't add regular price or tax
                if ($product_category == 'deposit') {
                    // Deposit products are handled separately via ACF fields below
                    // Don't set summary or add tax for the $0 product price
                } else {
                    // Regular non-deposit products
                    $summary[$product_category][0] = $_product->get_title()." ".( $show_included_taxes ?"(inc taxes)":"")."" ;
                    $summary[$product_category][1] = round(floatval($product_price), 2);
                    $summary['taxes'][1] += round( floatval ( ($summary[$product_category][1] * $tax_rate ) / 100 ) , 2 );
                }
            }
            
            // Handle additional ACF deposits for any product (not just modems)
            if ($deposit_fee > 0 && $product_category != "modems") {
                $total_deposits += $deposit_fee;
                
                // Store the product name for deposits
                if ($product_category == 'deposit') {
                    // For Pay After deposits, use the actual product title
                } elseif ($product_category == "tv-plan") {
                    $tv_deposit = $deposit_fee;
                } elseif ($product_category == "phone-plan") {
                    $phone_deposit = $deposit_fee;
                } else {
                }
            }
            
        } else {
    // FIX: Handle installation category specifically
    if ($product_category == 'installation' || $product_id == 267986 || in_array(60, $product_cat_ids)) {
        // NEW: Get ORIGINAL price from database, not cart (cart may already be modified by hook)
        $installation_product_db = wc_get_product($product_id);
        $original_install_price = $installation_product_db ? floatval($installation_product_db->get_regular_price()) : floatval($_product->get_price());
        
        // Determine final price to use
        $final_install_price = ($dynamic_install_price !== null) ? $dynamic_install_price : $original_install_price;
        
        $summary['installation'][0] = $_product->get_title()." ".( $show_included_taxes ?"(inc taxes)":"")."" ;
        $summary['installation'][1] = round($final_install_price, 2);
        
        // NEW: Add promotional pricing info if dynamic price exists
        if ($dynamic_install_price !== null && $dynamic_install_price != $original_install_price) {
            $summary['installation'][2] = ''; // Placeholder for dates (added later)
            $summary['installation'][3] = round($original_install_price, 2); // Original price
            $summary['installation'][4] = round($dynamic_install_price, 2); // Sale price
        }
        
        $summary['taxes'][1] += round( floatval ( ($summary['installation'][1] * $tax_rate ) / 100 ) , 2 );
    } else {
    }
    }
   }

    // Add deposits to summary if any exist
    if ($total_deposits > 0) {
        $summary['deposit'][0] = 'Deposits';
        $summary['deposit'][1] = $total_deposits;
    }

    // Calculate totals
    if( $do_not_include_modem_deposit ) {
        $summary['subtotal'][1] = $summary['internet-plan'][1] + $summary['installation'][1] + $summary['modems'][1] + $summary['phone-plan'][1] + $summary['tv-plan'][1];
        $summary['grand_total'][1] = $summary['subtotal'][1] + $summary['deposit'][1] + $summary['taxes'][1] + $modem_deposit;

        if (wc_tax_enabled() && !$show_included_taxes ) {
            $summary['taxes'][0] = esc_html( WC()->countries->tax_or_vat() );
        } elseif (!wc_tax_enabled()) {
            $summary['taxes'][0] = "Tax";
            $summary['grand_total'][1] = $summary['subtotal'][1] + $summary['deposit'][1] + $modem_deposit;
        }
    } else {
        $summary['subtotal'][1] = $summary['internet-plan'][1] + $summary['installation'][1] + $summary['modems'][1] + $summary['phone-plan'][1] + $summary['tv-plan'][1];
        $summary['grand_total'][1] = $summary['subtotal'][1] + $summary['deposit'][1] + $summary['taxes'][1];
        
        if (wc_tax_enabled() && !$show_included_taxes ) {
            $summary['taxes'][0] = esc_html( WC()->countries->tax_or_vat() );
        } elseif (!wc_tax_enabled()) {
            $summary['taxes'][0] = "Tax";
            $summary['grand_total'][1] = $summary['subtotal'][1] + $summary['deposit'][1];
        }
    }
    
    return $summary;    
}

/**
 * Get detailed upfront cart items for thank you page
 * Returns individual cart items with their prices for display
 * UPDATED: Handles deposits with category-based naming only, Install Dates special handling
 */

function get_upfront_cart_items_for_thank_you() {
    $items = array();
    $tax_rate = function_exists('GetTaxRate') ? GetTaxRate() : 13;
    
    if (!WC()->cart || WC()->cart->is_empty()) {
        return $items;
    }
    
    error_log('=== GETTING UPFRONT CART ITEMS FOR THANK YOU PAGE ===');
    
    // NEW: Get dynamic install price from internet plan in cart
    $internet_plan_cat_id = 19;
    $dynamic_install_price = null;
    
    foreach (WC()->cart->get_cart() as $cart_item) {
        $product = $cart_item['data'];
        $product_id = $product->get_id();
        $product_cat_ids = $product->get_category_ids();
        
        // Check if this product has internet-plan category (check ALL categories)
        if (in_array($internet_plan_cat_id, $product_cat_ids)) {
            if (function_exists('get_field')) {
                $dynamic_install_price = get_field('dynamic_install_price', $product_id);
                
                if ($dynamic_install_price === '' || $dynamic_install_price === null || $dynamic_install_price === false) {
                    $dynamic_install_price = get_field('dynamic_install_price', 'product_' . $product_id);
                }
                
                // Convert to float if we have a value (including 0)
                if ($dynamic_install_price !== '' && $dynamic_install_price !== null && $dynamic_install_price !== false) {
                    $dynamic_install_price = floatval($dynamic_install_price);
                } else {
                    $dynamic_install_price = null;
                }
            }
            
            error_log('Thank you page - Found internet plan: ' . $product_id . ', dynamic price: ' . ($dynamic_install_price !== null ? $dynamic_install_price : 'not set'));
            break;
        }
    }
    
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        $product_id = $product->get_id();
        $parent_id = $product->get_parent_id();
        $product_name = $product->get_name();
        $product_price = $product->get_price();
        
        // Get product categories
        $product_cats = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'slugs'));
        $primary_category = !empty($product_cats) ? $product_cats[0] : 'uncategorized';
        
        
        // SPECIAL HANDLING: Install Dates product (267986)
        if ($product_id == 267988 || $parent_id == 267986) {
            // Get the installation dates from variation attributes
            $installation_dates = '';
            if (isset($cart_item['variation']) && is_array($cart_item['variation'])) {
                $preferred = isset($cart_item['variation']['attribute_preferred-date']) ? 
                    $cart_item['variation']['attribute_preferred-date'] : '';
                $secondary = isset($cart_item['variation']['attribute_secondary-date']) ? 
                    $cart_item['variation']['attribute_secondary-date'] : '';
                
                if (!empty($preferred) && !empty($secondary)) {
                    $installation_dates = $preferred . ', ' . $secondary;
                } elseif (!empty($preferred)) {
                    $installation_dates = $preferred;
                } elseif (!empty($secondary)) {
                    $installation_dates = $secondary;
                }
            }
            
            // NEW: Get original price from database (cart price may be modified by hook)
            $installation_product_db = wc_get_product($product_id);
            $original_install_price = $installation_product_db ? floatval($installation_product_db->get_regular_price()) : floatval($product_price);
            
            // Determine final price (use dynamic if available, otherwise use cart price)
            $final_install_price = ($dynamic_install_price !== null) ? $dynamic_install_price : $product_price;

            // Add installation item
            $install_item = array(
                'name' => 'Installation Fee',
                'price' => $final_install_price,
                'type' => 'installation',
                'category' => $primary_category,
                'dates' => $installation_dates // Store dates for display
            );
            
            // NEW: Add promotional pricing if dynamic price exists
            if ($dynamic_install_price !== null && $dynamic_install_price != $original_install_price) {
                $install_item['original_price'] = $original_install_price;
                $install_item['promo_price'] = $dynamic_install_price;
                error_log("Added installation with promo: Installation Fee = $$final_install_price (original: $$original_install_price, promo: $$dynamic_install_price) with dates: $installation_dates");
            } else {
                error_log("Added installation: Installation Fee = $$final_install_price with dates: $installation_dates");
            }
            
            $items[] = $install_item;
            continue; // Skip to next item
        }
        
        // Add main product if it has a price (skip deposit category products with $0 price)
        if ($product_price > 0 && !in_array('deposit', $product_cats)) {
            $items[] = array(
                'name' => $product_name,
                'price' => $product_price,
                'type' => 'product',
                'category' => $primary_category
            );
            error_log("Added product: $product_name = $$product_price");
        }
        
        // Check for deposit fee using ACF
        $deposit_fee = 0;
        if (function_exists('get_field')) {
            $deposit_fee = get_field('deposit-fee', $product_id);
            
            if (empty($deposit_fee) && $deposit_fee !== '0') {
                $deposit_fee = get_field('deposit-fee', 'product_' . $product_id);
            }
            
            $deposit_fee = is_numeric($deposit_fee) ? floatval($deposit_fee) : 0;
        }
        
        // Add deposit if exists - USE CATEGORY-BASED NAMING
        if ($deposit_fee > 0) {
            // Determine deposit title based on category
            $deposit_title = 'Deposit'; // Default
            
            if (in_array('modems', $product_cats) || in_array('modems-new', $product_cats)) {
                $deposit_title = 'Modem Deposit';
            } elseif (in_array('tv-plan', $product_cats)) {
                $deposit_title = 'TV Deposit';
            } elseif (in_array('phone-plan', $product_cats)) {
                $deposit_title = 'Phone Deposit';
            } elseif (in_array('deposit', $product_cats)) {
                // FIXED: Handle Pay After deposit category
                $deposit_title = 'Pay After Deposit';
            }
            
            $items[] = array(
                'name' => $deposit_title,
                'price' => $deposit_fee,
                'type' => 'deposit',
                'category' => $primary_category
            );
            error_log("Added deposit: $deposit_title = $$deposit_fee (category: $primary_category)");
        }
    }
    
    error_log('Total items for thank you page: ' . count($items));
    return $items;
}

/**
 * Get formatted upfront summary for thank you page
 * This replaces the category-based summary with item-based summary
 */

function get_upfront_summary_for_thank_you() {
    $items = get_upfront_cart_items_for_thank_you();
    $tax_rate = function_exists('GetTaxRate') ? GetTaxRate() : 13;
    
    $summary = array();
    $subtotal = 0;
    $deposits_total = 0;
    $deposits = array(); // Store deposits separately
    
    // Separate products/installation from deposits
    foreach ($items as $item) {
        if ($item['type'] === 'deposit') {
            // Store deposits separately
            $deposits[] = $item;
            $deposits_total += $item['price'];
        } else {
            // Add products and installation to summary
            $key = sanitize_key($item['name']);
            $summary[$key] = array(
                $item['name'], 
                $item['price'],
                isset($item['dates']) ? $item['dates'] : '' // Pass dates if present
            );
            
            // NEW: Add optional original_price and promo_price fields if they exist
            if (isset($item['original_price'])) {
                $summary[$key][3] = $item['original_price'];
            }
            if (isset($item['promo_price'])) {
                $summary[$key][4] = $item['promo_price'];
            }
            
            $subtotal += $item['price'];
        }
    }
    
    // Calculate tax on subtotal (not including deposits)
    $tax = 0;
    if (wc_tax_enabled()) {
        $tax = round(($subtotal * $tax_rate) / 100, 2);
    }
    
    // Add subtotal and tax BEFORE deposits
    $summary['subtotal'] = array('Subtotal', $subtotal, '');
    $summary['taxes'] = array('Tax', $tax, '');
    
    // NOW add deposits after subtotal and tax
    foreach ($deposits as $deposit) {
        $key = sanitize_key($deposit['name']);
        $summary[$key] = array($deposit['name'], $deposit['price'], '');
    }
    
    // Add grand_total - JavaScript needs this to populate the #upfront-total in tfoot
    // It's in skipKeys so it won't be rendered in tbody, only used for the total
    $summary['grand_total'] = array('Total Paid Today', $subtotal + $tax + $deposits_total, '');
    
    error_log('Upfront summary for thank you: ' . json_encode($summary));
    return $summary;
}



/*====Monthly Fee Summary=====*/

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

        // Get primary category (ignoring provider categories)
        $product_category = dg_get_primary_product_category($product_cat_ids);

        if ($product_category === null) {
            error_log("Product " . $_product->get_name() . " has no valid categories");
            continue;
    }

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

/**
 * UPDATED: Get detailed monthly cart items for thank you page
 * Now includes promotional pricing fields (monthly_promo_fee and monthly_promo_blurb)
 */

function get_monthly_cart_items_for_thank_you() {
    $items = array();
    
    if (!WC()->cart || WC()->cart->is_empty()) {
        return $items;
    }
    
    error_log('=== GETTING MONTHLY CART ITEMS FOR THANK YOU PAGE (WITH PROMO SUPPORT) ===');

    // Canonical category order for display sorting
    $category_order = array(
        'internet-plan' => 1,
        'installation'  => 2,
        'modems-new'    => 3,
        'tv-plan'       => 4,
        'phone-plan'    => 5,
    );
    
    // Categories that should NOT be in monthly billing
    $exclude_categories = array('deposit', 'installation');
    
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        $product_id = $product->get_id();
        $product_name = $product->get_name();
        
        // Get product categories
        $product_cats = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'slugs'));
        $primary_category = !empty($product_cats) ? $product_cats[0] : 'uncategorized';
        
        // Skip excluded categories
        if (in_array($primary_category, $exclude_categories)) {
            continue;
        }
        
        // Skip Pay After deposit
        if ($product_id == 267989) {
            continue;
        }
        
        // Get monthly fee from ACF
        $monthly_fee = 0;
        if (function_exists('get_field')) {
            $monthly_fee = get_field('monthly_fee', $product_id);
            
            if (empty($monthly_fee) && $monthly_fee !== '0') {
                $monthly_fee = get_field('monthly_fee', 'product_' . $product_id);
            }
            
            $monthly_fee = is_numeric($monthly_fee) ? floatval($monthly_fee) : 0;
        }
        
        // NEW: Get promotional pricing fields
        $monthly_promo_fee_raw = null;
	$monthly_promo_fee = null;
		$monthly_promo_blurb = '';
        if (function_exists('get_field')) {
           // Get promo fee
$monthly_promo_fee_raw = get_field('monthly_promo_fee', $product_id);
if ($monthly_promo_fee_raw === null) {
    $monthly_promo_fee_raw = get_field('monthly_promo_fee', 'product_' . $product_id);
}
$promo_is_set = is_numeric($monthly_promo_fee_raw);
$monthly_promo_fee = $promo_is_set ? floatval($monthly_promo_fee_raw) : null;
            
            // Get promo blurb
            $monthly_promo_blurb = get_field('monthly_promo_blurb', $product_id);
            if (empty($monthly_promo_blurb)) {
                $monthly_promo_blurb = get_field('monthly_promo_blurb', 'product_' . $product_id);
            }
        }

        // NEW: Get modem details if this is "I Have My Own Modem" product (ID: 267979)
            $modem_details = '';
            if ($product_id == 267979 && isset($cart_item['modem_details']) && !empty($cart_item['modem_details'])) {
                $modem_details = $cart_item['modem_details'];
                error_log("Found modem details for product $product_id: " . $modem_details);
            }

        // Determine the final price to use (promo takes precedence if exists)
        $final_price = $promo_is_set ? $monthly_promo_fee : $monthly_fee;

        // FIXED: Add to items if monthly fee exists OR if it's the "I Have My Own Modem" product
        if ($monthly_fee > 0 || $promo_is_set || $product_id == 267979) {

            // Determine sort position from category slugs
            $sort_order = PHP_INT_MAX;
            foreach ($product_cats as $slug) {
                if (isset($category_order[$slug])) {
                    $sort_order = $category_order[$slug];
                    break;
                }
            }

            $items[] = array(
                 'name'           => $product_name,
    	'price'          => $final_price,
    	'original_price' => $monthly_fee,
    	'promo_price'    => $monthly_promo_fee,  // null if not set, 0 if explicitly free
    	'promo_blurb'    => $monthly_promo_blurb,
    	'modem_details'  => $modem_details,
    	'category'       => $primary_category,
    	'sort_order'     => $sort_order,
            );
            error_log("Added monthly item: $product_name = $$final_price/month (original: $$monthly_fee, promo: $$monthly_promo_fee, modem_details: $modem_details)");
        }
    }

    // Sort into canonical category order
    usort($items, function($a, $b) {
        return $a['sort_order'] - $b['sort_order'];
    });
    
    error_log('Total monthly items for thank you page: ' . count($items));
    return $items;
}

/**
 * Get formatted monthly summary for thank you page
 */
/**
 * UPDATED: Get formatted monthly summary for thank you page
 * Now preserves promotional pricing information in the summary array
 */
function get_monthly_summary_for_thank_you() {
    $items = get_monthly_cart_items_for_thank_you();
    $tax_rate = function_exists('GetTaxRate') ? GetTaxRate() : 13;
    
    $summary = array();
    $subtotal = 0;
    
    // Add each item to summary WITH promotional info
    foreach ($items as $item) {
        $key = sanitize_key($item['name']);
        // NEW: Add additional fields for promotional display
        $summary[$key] = array(
            $item['name'],                  // [0] = name
            $item['price'],                 // [1] = final price (used for calculations)
            $item['original_price'],        // [2] = original price (for strikethrough)
            $item['promo_price'],           // [3] = promo price
            $item['promo_blurb'],          // [4] = promo blurb
             $item['modem_details']         // [5] = modem details for 'I have my own modem' product
        );
        $subtotal += $item['price'];
    }
    
    // Calculate tax
    $tax = 0;
    if (wc_tax_enabled()) {
        $tax = round(($subtotal * $tax_rate) / 100, 2);
    }
    
    // Add totals (maintain original structure for these)
    $summary['subtotal'] = array('Subtotal', $subtotal);
    $summary['taxes'] = array('Tax', $tax);
    $summary['grand_total'] = array('Total', $subtotal + $tax);
    
    error_log('Monthly summary for thank you (with promo): ' . json_encode($summary));
    return $summary;
}

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
    
    // Get province from address lookup
    $searched_address = dg_get_user_meta("searched_address");
    
    error_log('Searched address for tax: ' . print_r($searched_address, true));
    
    $state = '';
    if (isset($searched_address['administrative_area_level_1'])) {
        $state = $searched_address['administrative_area_level_1'];
    } elseif (isset($searched_address['provinceOrState'])) {
        $state = $searched_address['provinceOrState'];
    }
    
    error_log('Province extracted for tax: ' . $state);
    
    // Get province-specific tax rates
    $tax_rates = WC_Tax::find_rates(array(
        'country'   => 'CA',
        'state'     => $state,
        'city'      => '',
        'postcode'  => ''
    ));
    
    error_log('Tax rates found: ' . print_r($tax_rates, true));

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




/*==============================END OF NEW FUNCTIONS ADDED FOR SITE REVAMPED January 2026==================================================*/


function verify_card_ex($payment_info) {
    $mpg_response = VerifyCard($payment_info);
    error_log("Got verify response back " . $mpg_response->getComplete());

    // Card number / general decline
    if ($mpg_response == false ||
        $mpg_response->getComplete() !== "true" ||
        $mpg_response->getResponseCode() == false ||
        $mpg_response->getResponseCode() == null ||
        $mpg_response->getResponseCode() >= 50) {

        $msg = "The credit card details entered are invalid. Please ensure the credit card number, expiry date and CVV are all correct.";
        $response = array(
            'status' => 'failed',
            'msg'    => $msg,
            'field'  => 'card_number',
            'code'   => $mpg_response->getResponseCode(),
            'ref'    => $mpg_response->getReferenceNum(),
        );
        error_log(json_encode($response));
        return $response;
    }

    // CVD (CVV) check — result code "1M" means match
    $cvd_result = $mpg_response->getCvdResultCode();
    error_log("Got verify cvd result code " . $cvd_result);

    if ($cvd_result !== "1M") {
        // First character is the CVD response code digit:
        //   1 = CVD present and processed
        //   2 = CVD present but not processed
        //   3 = No CVD present
        //   4 = CVD present but illegible
        // Second character is the actual result: M=match, N=no match, P=not processed, S=suspicious
        $cvd_digit = $cvd_result ? substr($cvd_result, 0, 1) : '';
        $cvd_match = $cvd_result ? substr($cvd_result, 1, 1) : '';

        // If the card was processed but expiry caused the decline (response code 54)
        $response_code = $mpg_response->getResponseCode();
        if ($response_code == '54') {
            $msg = "Invalid or expired expiry date. Please double check the expiry date entered.";
            $field = 'expiry';
        } elseif ($cvd_match === 'N') {
            $msg = "Invalid CVV. The security code entered does not match. Please double check the CVV on the back of your card.";
            $field = 'cvv';
        } else {
            // Generic CVD failure — most likely CVV
            $msg = "Invalid CVV or expiry date. Please double check the information entered. " . $mpg_response->getMessage();
            $field = 'cvv';
        }

        $response = array(
            'status' => 'failed',
            'msg'    => $msg,
            'field'  => $field,
            'code'   => $response_code,
            'ref'    => $mpg_response->getReferenceNum(),
        );
        error_log(json_encode($response));
        return $response;
    }

    // AVS (postal code) check
    $avs_result = $mpg_response->getAvsResultCode();
    error_log("Got verify avs result code " . $avs_result);

    if ($avs_result === "N") {
        $msg = "Invalid postal code. Please provide the billing postal code from your recent credit card statement (may be different from the service address postal code).";
        $response = array(
            'status' => 'failed',
            'msg'    => $msg,
            'field'  => 'postal_code',
            'code'   => $mpg_response->getResponseCode(),
            'ref'    => $mpg_response->getReferenceNum(),
        );
        error_log(json_encode($response));
        return $response;
    }

    error_log("Got response back " . $mpg_response->getComplete());
    return array('status' => 'success');
}

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

	error_log("Sending address check information to signup server, $info response $output ");

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

	error_log("Sending to server payload : $payload");

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
			dg_set_user_meta("upfront_payment_code", $mpg_response->getAuthCode() );
			dg_set_user_meta("upfront_payment_ref", $mpg_response->getReferenceNum() );
			dg_set_user_meta("upfront_payment_amount", $mpg_response->getTransAmount() );
			dg_set_user_meta("upfront_payment_date", $mpg_response->getTransDate() );
			dg_set_user_meta("upfront_payment_response_code", $mpg_response->getResponseCode() );
		
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
		//AddressCheckLog( 0 );
	//}

	wp_die($response);
}

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

	  wp_enqueue_script( "diallog-main", get_stylesheet_directory_uri() . '/js/d-main.js', array("jquery"), "0.0.7", true );
	
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

//echo ("In dg_signup_page " . time() . "<br>"); //Eugene
	
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

	error_log( " 1- In dg_add_product_to_cart plan $plan_id, type $type " );

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

	error_log( " 2- In dg_add_product_to_cart plan $plan_id, type $type " );

		// add the installation fees
		$internet_plan_sku = $internet_plan_id->get_sku();
		$internet_plan_sku_list = explode(",",$internet_plan_sku);

		$query = new WC_Product_Query( array(
			'limit' => -1,
			'orderby' => 'date',
			'order' => 'DESC',
			'category' => "Fixed Fee",
		) );

	    error_log( " 3- In dg_add_product_to_cart plan $plan_id, type $type " );

		$products = $query->get_products();
		foreach($products as $prod) {
			$install_plan_sku = $prod->get_sku();
			$install_plan_sku_list = explode(",",$install_plan_sku);

            error_log( "In dg_add_product_to_cart in the product loop, install plan sku = $install_plan_sku, $plan_id, type $type " );

			foreach($install_plan_sku_list as $install_sku) {
				//foreach($internet_plan_sku_list as $internet_sku) {
					if( strcmp( $internet_plan_sku_list[0], $install_sku) == 0 ) {
						// found match;
						$plan_id = $prod->get_id();
						$ret = _add_product_tocart($plan_id, "fixedfee");
						goto finish;
					}
				//}
			}


		}

	    error_log( " 4- In dg_add_product_to_cart plan $plan_id, type $type " );

	}

	if ($plan_id) {
		//hubspot_set_user_data();
		$ret = _add_product_tocart($plan_id, $type);
		error_log(" dg_add_product_to_cart return $ret ") ;
	} 
	    

finish:

    error_log( " 5- In dg_add_product_to_cart plan $plan_id, type $type " );

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
    include_once("plan-building-wizard-steps.php");
    wp_die();
}



// Show Payment Options after Billing fields. 
remove_action( 'woocommerce_checkout_order_review','woocommerce_checkout_payment',20);
add_action( 'woocommerce_checkout_before_customer_details','woocommerce_checkout_payment',10);			 

// Optimized by Eugene with help from ChatGPT
function get_dg_user_id() {
	global $dg_user_id;

	if (isset($_COOKIE['dg_user_hash'])) {
		$dg_user_id = $_COOKIE['dg_user_hash'];
	} elseif (!empty($dg_user_id)) {
		// already set
	} else {
		$dg_user_id = false;
	}

	return $dg_user_id;
}

function set_dg_user_id($id) {
	global $dg_user_id;
	$dg_user_id = $id;
	setcookie("dg_user_hash", $dg_user_id, strtotime('+30 days'), "/");
}

function dg_get_user_meta($key) {
	global $wpdb;

	$user_id = get_dg_user_id();
	if (!$user_id) return false;

    $main_key = "full_user_data";
	$data = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT meta_value FROM {$wpdb->prefix}dg_user_data WHERE user_id = %s AND meta_key = %s",
			$user_id, $main_key
		),
		ARRAY_A
	);

    if( $data )
    {
        $main_value_d = json_decode( base64_decode ( $data['meta_value'] ) , true );
        if( isset( $main_value_d[$key] ) && $main_value_d[$key] ) {
            return maybe_unserialize( base64_decode( $main_value_d[$key] ) );
        }
    }
    
	return false;//$data ? maybe_unserialize($data['meta_value']) : false;
}

function dg_set_user_meta($key, $value = "") {
	global $wpdb;

	$user_id = get_dg_user_id();
	if (!$user_id) {
		$user_id = md5(microtime(true) . rand());
		set_dg_user_id($user_id);
		dg_set_user_meta("date_created", date("j M,Y H:i:s"));
	}

    $existing = true;
    $user_data = dg_get_current_user_data(true);//dg_get_user_meta($key);
    if( $user_data == false )
        $existing = false;
    

    $value = base64_encode( maybe_serialize( $value ) );
    $main_key = "full_user_data";


	if ($existing !== false) {
        $main_value_d = dg_get_current_user_data(true);
        $main_value_d[$key] = $value;
        $main_value = base64_encode( json_encode( $main_value_d ));

		return $wpdb->update(
			$wpdb->prefix . "dg_user_data",
			['meta_value' => $main_value],
			['user_id' => $user_id, 'meta_key' => $main_key],
			['%s'], ['%s', '%s']
		);

	} else {
		$main_value_d = [];
        $main_value_d[$key] = $value;
        $main_value = base64_encode( json_encode( $main_value_d ));

		return $wpdb->insert(
			$wpdb->prefix . "dg_user_data",
			[
				'user_id'    => $user_id,
				'meta_key'   => $main_key,
				'meta_value' => $main_value,
        			'timestamp' => current_time('mysql'), // set TIMESTAMP column
			],
			['%s', '%s', '%s']
		);
	}
}

function dg_get_current_user_data($unserialized = false) {
	global $wpdb;

	$user_id = get_dg_user_id();
	if (!$user_id) return false;

    $main_key = "full_user_data";
	$data = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT meta_value FROM {$wpdb->prefix}dg_user_data WHERE user_id = %s AND meta_key = %s",
			$user_id, $main_key
		),
		ARRAY_A
	);

	if (!$data || isset( $data['meta_value']) == false ) return false;

    $user_data = json_decode( base64_decode( $data['meta_value'] ), true );
    if( $unserialized == false ) {
        $ret_data = [];

        foreach( $user_data as $key => $value ) { // decode all items
            $ret_data[ $key ] = maybe_unserialize ( base64_decode( $value ) );
        }

        return $ret_data;
    } else {
        return $user_data;
    }

	return false;    
}

function dg_set_current_user_data($user_data) {
	if (!is_array($user_data)) return;

    $user_id = get_dg_user_id();
	if (!$user_id) return false;

    $existing = true;
    $current_data = dg_get_current_user_data(true);
    if( $current_data == false ) {
        $existing = false;
        $current_data = [];
    }

    foreach ($user_data as $key => $value) {
        $current_data[$key] = base64_encode ( maybe_serialize( $value ));
	}

    $main_value = base64_encode( json_encode( $data ));
    $main_key = "full_user_data";

    if ($existing !== false) {
		return $wpdb->update(
			$wpdb->prefix . "dg_user_data",
			['meta_value' => $main_value],
			['user_id' => $user_id, 'meta_key' => $main_key],
			['%s'], ['%s', '%s']
		);
	} else {
		return $wpdb->insert(
			$wpdb->prefix . "dg_user_data",
			[
				'user_id'    => $user_id,
				'meta_key'   => $main_key,
				'meta_value' => $main_value,
			],
			['%s', '%s', '%s']
		);
	}

}
// End optimized by Eugene with help from ChatGPT

function GetTaxRate() {
	$user_data = dg_get_current_user_data();
	if( $user_data == false ) {
		return 13;
	}

	$prov = $user_data['prov'];
        $tax_rate = 13;
        if( strcasecmp( $prov, "BC") == 0 ) {
                $tax_rate = 12;
        } else if( strcasecmp( $prov, "QC") == 0 ) {
                $tax_rate = 14.975;
        }
	return $tax_rate;
}


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
				$summary[$product_category][0] = $_product->get_title();
				$summary[$product_category][1] = 0.00; // Eugene explicitly exclude cost, show plan name
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

/*add_filter( 'woocommerce_calculated_total', 'change_calculated_total', 10, 2 );
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
     
} */

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
		$fees = "$" . number_format($ProdPrice, 2) . "/mo";
	
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

//Eugene updated to correctly show the promo price and regular price plus Free 3 months on the Email and Thank You page summaries
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
		$regular = round($_product->get_regular_price() * $ProdQuant, 2);
		$sale = round($_product->get_sale_price() * $ProdQuant, 2);
		$freq = $_product->get_attribute("Payment Frequency");
		$fees = "";

		// Eugene updated to show proper pricing for Phone section of Email and Thank You summaries
		if ($regular > 0 && $sale == 0) {
			$fees = "<s>$$regular/mo</s> Free for 3 months";
		} elseif ($sale > 0 && $sale < $regular) {
			$fees = "<s>$$regular/mo</s> $$sale/mo";
		} else {
			$fees = "$" . round($_product->get_price() * $ProdQuant, 2) . "/mo";
		}

		if (strlen($freq) > 0 && strpos($fees, $freq) === false) {
			$fees .= " " . $freq;
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
			$fees .= " " . $freq;
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

// Updated by Zach June 4th to include additional fields: Unit Number and Buzz Code & reorder fields on checkout template */

add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields',99 );

function custom_override_checkout_fields( $fields ) {
     
     $ubpo = dg_get_user_meta ("upfront_bill_payment_option");
     
     //billing fields not required for email transfer payment option.
     if ($ubpo=="email-transfer") {
	     foreach($fields['billing'] as $key=>$val) {
		     $fields['billing'][$key]['required'] = false;
	     }
     }
     
     $fields['billing']['billing_email']['default']      = dg_get_user_meta('email');
     $fields['billing']['billing_first_name']['default'] = dg_get_user_meta('first_name');
     $fields['billing']['billing_last_name']['default']  = dg_get_user_meta('last_name');
     
     if (!empty($ubpo['searched_address']['provinceOrState'])) {
	 	 $fields['shipping']['shipping_state']['default'] = $searched_address['provinceOrState'];    
     }
     
     unset($fields['billing']['billing_company']);
     unset($fields['billing']['billing_address_2']);
     unset($fields['order']['order_comments']);

     // Row 1: First Name / Last Name
     $fields['billing']['billing_first_name']['label']    = "First Name";
     $fields['billing']['billing_first_name']['priority'] = 10;
     $fields['billing']['billing_first_name']['class']    = array('form-row-first');
	
     $fields['billing']['billing_last_name']['label']     = "Last Name";
     $fields['billing']['billing_last_name']['priority']  = 20;
     $fields['billing']['billing_last_name']['class']     = array('form-row-last');
	
     // Row 2: Email / Phone
     $fields['billing']['billing_email']['label']         = "Email Address";
     $fields['billing']['billing_email']['priority']      = 30;
     $fields['billing']['billing_email']['class']         = array('form-row-first');

     $fields['billing']['billing_phone']['required']      = true;
     $fields['billing']['billing_phone']['priority']      = 40;
     $fields['billing']['billing_phone']['class']         = array('form-row-last');

     // Row 3: Unit Number / Buzzer Code
     $fields['billing']['billing_unit_number'] = array(
          'label'       => __('Unit Number', 'woocommerce'),
          'placeholder' => _x('', 'placeholder', 'woocommerce'),
          'required'    => false,
          'class'       => array('form-row-first'),
          'clear'       => false,
          'priority'    => 50,
          'default'     => dg_get_user_meta('billing_unit_number') ?: ''
     );

     $fields['billing']['billing_buzzer_code'] = array(
          'label'       => __('Buzzer Code', 'woocommerce'),
          'placeholder' => _x('', 'placeholder', 'woocommerce'),
          'required'    => false,
          'class'       => array('form-row-last'),
          'clear'       => true,
          'priority'    => 60,
          'default'     => dg_get_user_meta('billing_buzzer_code') ?: ''
     );

     // Address fields pushed to 70+ to avoid collision with unit/buzzer above
     $fields['billing']['billing_address_1']['priority']  = 70;

     $fields['billing']['billing_city']['label']          = "City";
     $fields['billing']['billing_city']['priority']       = 80;
     $fields['billing']['billing_city']['class']          = array('form-row-first');

     $fields['billing']['billing_state']['priority']      = 90;
     $fields['billing']['billing_state']['class']         = array('form-row-last');

     $fields['billing']['billing_postcode']['label']      = "Postal Code";
     $fields['billing']['billing_postcode']['priority']   = 100;
     $fields['billing']['billing_postcode']['class']      = array('form-row-first', 'clear', 'forceuppercase');

	$fields['billing']['billing_country']['priority'] = 110;

     // Hidden fields (no priority needed — class hides them)
   $fields['billing']['service_address'] = array(
    'label'       => __('Service Address', 'woocommerce'),
    'placeholder' => _x('Service Address', 'placeholder', 'woocommerce'),
    'required'    => true,
    'class'       => array('checkout_hidden_fields'),
    'clear'       => true,
    'priority'    => 200,
    'default'     => dg_get_user_meta('searched_street_address')
);

$fields['billing']['date1'] = array(
    'label'       => __('1st Preferred Installation Date and Time', 'woocommerce'),
    'placeholder' => _x('1st Preferred Installation Date and Time', 'placeholder', 'woocommerce'),
    'required'    => true,
    'class'       => array('checkout_hidden_fields'),
    'clear'       => true,
    'priority'    => 210,
    'default'     => dg_get_user_meta('preffered_installation_date_1')." ".dg_get_user_meta('preffered_installation_time_1')
);

$fields['billing']['date2'] = array(
    'label'       => __('2nd Preferred Installation Date and Time', 'woocommerce'),
    'placeholder' => _x('2nd Preferred Installation Date and Time', 'placeholder', 'woocommerce'),
    'required'    => true,
    'class'       => array('checkout_hidden_fields'),
    'clear'       => true,
    'priority'    => 220,
    'default'     => dg_get_user_meta('preffered_installation_date_2')." ".dg_get_user_meta('preffered_installation_time_2')
);

$fields['billing']['date3'] = array(
    'label'       => __('3rd Preferred Installation Date and Time', 'woocommerce'),
    'placeholder' => _x('2rd Preferred Installation Date and Time', 'placeholder', 'woocommerce'),
    'required'    => true,
    'class'       => array('checkout_hidden_fields'),
    'clear'       => true,
    'priority'    => 230,
    'default'     => dg_get_user_meta('preffered_installation_date_3')." ".dg_get_user_meta('preffered_installation_time_3')
);

$fields['billing']['customer_first_name'] = array(
    'label'       => __('Customer First Name', 'woocommerce'),
    'placeholder' => _x('Customer First Name', 'placeholder', 'woocommerce'),
    'required'    => true,
    'class'       => array('checkout_hidden_fields'),
    'clear'       => true,
    'priority'    => 240,
    'default'     => dg_get_user_meta('first_name')
);

$fields['billing']['customer_last_name'] = array(
    'label'       => __('Customer Last Name', 'woocommerce'),
    'placeholder' => _x('Customer last Name', 'placeholder', 'woocommerce'),
    'required'    => true,
    'class'       => array('checkout_hidden_fields'),
    'clear'       => true,
    'priority'    => 250,
    'default'     => dg_get_user_meta('last_name')
);

$fields['billing']['customer_email'] = array(
    'label'       => __('Customer email address', 'woocommerce'),
    'placeholder' => _x('Customer email address', 'placeholder', 'woocommerce'),
    'required'    => true,
    'class'       => array('checkout_hidden_fields'),
    'clear'       => true,
    'priority'    => 260,
    'default'     => dg_get_user_meta('email')
);

$fields['billing']['customer_phone'] = array(
    'label'       => __('Customer phone', 'woocommerce'),
    'placeholder' => _x('Customer phone', 'placeholder', 'woocommerce'),
    'required'    => true,
    'class'       => array('checkout_hidden_fields'),
    'clear'       => true,
    'priority'    => 270,
    'default'     => dg_get_user_meta('phone')
);

$fields['billing']['referrer_name'] = array(
    'label'       => __('Referrer name', 'woocommerce'),
    'placeholder' => _x('Referrer name', 'placeholder', 'woocommerce'),
    'required'    => false,
    'class'       => array('checkout_hidden_fields'),
    'clear'       => true,
    'priority'    => 280,
    'default'     => dg_get_user_meta('referrer_name')
);
     
     return $fields;
}


// Optimized by Eugene with ChatGPT help to update logic that was trying to encrypt with WordPress ID. Remove is_user_logged_in() check and always use get_dg_user_id()
add_action('woocommerce_checkout_update_order_meta', 'saving_checkout_cf_data');
function saving_checkout_cf_data($order_id) {
	if (isset($_POST['checkout']) && is_array($_POST['checkout'])) {
		SendInfoToSignupServer(50);

		foreach ($_POST['checkout'] as $key => $value) {
			if ($key == "monthly_cc" || $key == "monthly_bank") {
				$token = maybe_serialize($value);
				$encryption_key = get_dg_user_id(); // Always use cookie ID
				$cryptor = new Cryptor($encryption_key);
				$value = $cryptor->encrypt($token);
				unset($token);
				update_post_meta($order_id, "dg_user_hash", $encryption_key);
			}

			if (!is_array($value)) {
				update_post_meta($order_id, $key, sanitize_text_field($value));
			} else {
				update_post_meta($order_id, $key, maybe_serialize($value));
			}
		}
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

// Eugene define shortcode to render order summary, which will be used on thank-you page
function dg_order_summary_shortcode() {
    $summary = dg_get_user_meta("order_summary_html");
    if (!$summary) return "<p>We couldn’t find your order details. Please contact support.</p>";
    return $summary;
}
add_shortcode('dg_order_summary', 'dg_order_summary_shortcode');


// auto delete the old entries from the user_data
function myplugin_schedule_daily_cleanup() {
    if (! wp_next_scheduled('myplugin_daily_cleanup')) {
        wp_schedule_event(strtotime('00:00:00'), 'daily', 'myplugin_daily_cleanup');
    }
}
add_action('wp', 'myplugin_schedule_daily_cleanup');

add_action('myplugin_daily_cleanup', 'myplugin_delete_old_entries');
function myplugin_delete_old_entries() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'dg_user_data';

    $one_week_ago = date('Y-m-d H:i:s', strtotime('-1 week'));

    $deleted = $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM $table_name WHERE timestamp < %s",
            $one_week_ago
        )
    );

    if ($deleted !== false) {
        error_log("Deleted $deleted old entries from $table_name."); // Optional logging
    }
}

function myplugin_clear_cron() {
    $timestamp = wp_next_scheduled('myplugin_daily_cleanup');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'myplugin_daily_cleanup');
    }
}
register_deactivation_hook(__FILE__, 'myplugin_clear_cron');