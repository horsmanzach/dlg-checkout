<?php

/*=================ZACH NEW EDITS AS OF MARCH 31=================*/

/*==========Register monthly_fee shortcode========*/
function display_monthly_fee_shortcode($atts) {
    // Extract shortcode attributes, requiring product_id
    $atts = shortcode_atts(array(
        'product_id' => '', // No default - must be specified
    ), $atts);
    
    // Check if product_id is providedgit   
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

/*Create Clickable Rows Instead of Add to Cart Buttons*/

/**
 * Clickable Modem Rows - Add to Cart Functionality
 * Add this to your theme's functions.php or a custom plugin
 */

// Enqueue the JavaScript
function modem_selection_scripts() {
    // Only load on the product template page
	if (is_product()) { // Checks if current page is a WooCommerce single product
        wp_enqueue_script('card-selection', get_stylesheet_directory_uri() . '/js/card-selection.js', array('jquery'), '1.0', true);
        
        // Pass AJAX URL and nonce to JavaScript
        wp_localize_script('card-selection', 'modem_selection_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('modem_selection_nonce'),
        ));
    }
}
add_action('wp_enqueue_scripts', 'modem_selection_scripts');


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




/* Creation of UPFRONT FEE TABLE / SHORTCODE and MONTHLY FEE TABLET / SHORTCODE */

/**
 * Fee Summary Tables - Shortcodes for upfront and monthly fees
 * Add this to your theme's functions.php file
 */

// Upfront Fee Summary Table Shortcode
function upfront_fee_summary_shortcode() {
    // Get cart items
    $cart = WC()->cart;
    
    if ($cart->is_empty()) {
        return '<p>No products selected.</p>';
    }
    
    $output = '<table class="fee-summary-table upfront-fee-table">';
    $output .= '<thead><tr><th>Product</th><th>Price</th></tr></thead>';
    $output .= '<tbody>';
    
    $subtotal = 0;
    
    // Loop through cart items
    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        $product_id = $product->get_id();
        $product_name = $product->get_name();
        $product_price = $product->get_price();
        
        $output .= '<tr>';
        $output .= '<td>' . esc_html($product_name) . '</td>';
        $output .= '<td>' . wc_price($product_price) . '</td>';
        $output .= '</tr>';
        
        $subtotal += $product_price;
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
    $output .= '<tr class="subtotal-row"><td>Subtotal</td><td>' . wc_price($subtotal) . '</td></tr>';
    $output .= '<tr class="tax-row"><td>Tax</td><td>' . wc_price($tax_total) . '</td></tr>';
    $output .= '<tr class="total-row"><td>Total Upfront</td><td>' . wc_price($subtotal + $tax_total) . '</td></tr>';
    
    $output .= '</tbody></table>';
    
    return $output;
}
add_shortcode('upfront_fee_summary', 'upfront_fee_summary_shortcode');


/*-------*/




// Monthly Fee Summary Table Shortcode

// Monthly Fee Summary Table Shortcode with Internet Plan
function monthly_fee_summary_shortcode() {
    global $post;
    // Get cart items
    $cart = WC()->cart;
    
    $output = '<table class="fee-summary-table monthly-fee-table">';
    $output .= '<thead><tr><th>Product</th><th>Monthly Fee</th></tr></thead>';
    $output .= '<tbody>';
    
    $subtotal = 0;
    $internet_plan_in_cart = false;
    $current_product_id = 0;
    
    // Check if we're on a product page
    if (is_product() && $post) {
        $current_product_id = $post->ID;
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
                
                // Add current plan to table
                $output .= '<tr class="internet-plan-row">';
                $output .= '<td>' . esc_html($current_product->get_name()) . '</td>';
                $output .= '<td>' . wc_price($monthly_fee) . '</td>';
                $output .= '</tr>';
                
                $subtotal += $monthly_fee;
            }
        }
    }
    
    // Check if cart is empty
    if (!$cart->is_empty()) {
        // Loop through cart items
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $product_id = $product->get_id();
            
            // Skip if this is an internet plan and we're on its product page
            if ($product_id == $current_product_id) {
                $internet_plan_in_cart = true;
                continue;
            }
            
            // Check if this is an internet plan but not the current one
            $product_cats = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
            $is_internet_plan = in_array(19, $product_cats); // Replace with actual category ID
            
            // Remove other internet plans from cart
            if ($is_internet_plan && $current_product_id > 0) {
                WC()->cart->remove_cart_item($cart_item_key);
                continue;
            }
            
            $product_name = $product->get_name();
            
            // Get ACF monthly fee field
            $monthly_fee = 0;
            if (function_exists('get_field')) {
                $monthly_fee = get_field('monthly_fee', $product_id);
                
                // If direct approach fails, try with product_ prefix
                if (empty($monthly_fee) && $monthly_fee !== '0') {
                    $monthly_fee = get_field('monthly_fee', 'product_' . $product_id);
                }
                
                // Convert to numeric value
                $monthly_fee = is_numeric($monthly_fee) ? floatval($monthly_fee) : 0;
            }
            
            $output .= '<tr>';
            $output .= '<td>' . esc_html($product_name) . '</td>';
            $output .= '<td>' . wc_price($monthly_fee) . '</td>';
            $output .= '</tr>';
            
            $subtotal += $monthly_fee;
        }
    }
    
    // Add current internet plan to cart if it's not there already
    if ($current_product_id > 0 && !$internet_plan_in_cart) {
        // Check if it's an internet plan
        $product_cats = wp_get_post_terms($current_product_id, 'product_cat', array('fields' => 'ids'));
        $is_internet_plan = in_array(19, $product_cats); // Replace with your category ID
        
        if ($is_internet_plan) {
            // Add to cart silently (no redirect)
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
    $output .= '<tr class="subtotal-row"><td>Subtotal</td><td>' . wc_price($subtotal) . '</td></tr>';
    $output .= '<tr class="tax-row"><td>Tax</td><td>' . wc_price($tax_total) . '</td></tr>';
    $output .= '<tr class="total-row"><td>Total Monthly</td><td>' . wc_price($subtotal + $tax_total) . '</td></tr>';
    
    $output .= '</tbody></table>';
    
    return $output;
}
add_shortcode('monthly_fee_summary', 'monthly_fee_summary_shortcode');


// AJAX handler for updating tables
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

// Modified AJAX handler to add modem to cart
function modem_add_to_cart_ajax() {
    // Check nonce for security
    check_ajax_referer('modem_selection_nonce', 'nonce');
    
    // Get the product ID
    $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
    $is_internet_plan = isset($_POST['is_internet_plan']) && $_POST['is_internet_plan'] === 'true';
    
    if ($product_id > 0) {
        // If this is NOT an internet plan, we need to check if there are any in the cart already
        if (!$is_internet_plan) {
            $internet_plan_found = false;
            $internet_plan_cart_key = '';
            
            // Check for internet plans in cart
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $cart_product_id = $cart_item['product_id'];
                
                // Check if this is an internet plan
                $product_cats = wp_get_post_terms($cart_product_id, 'product_cat', array('fields' => 'ids'));
                $is_cart_item_internet_plan = in_array(19, $product_cats); // Replace with your category ID
                
                if ($is_cart_item_internet_plan) {
                    $internet_plan_found = true;
                    $internet_plan_cart_key = $cart_item_key;
                    break;
                }
            }
            
            // Remove any internet plans in cart
            if ($internet_plan_found && $internet_plan_cart_key) {
                // Keep the internet plan, just remove other items
                $cart_items_to_keep = array($internet_plan_cart_key);
                remove_all_except_items($cart_items_to_keep);
            } else {
                // No internet plan, clear the entire cart
                WC()->cart->empty_cart();
            }
            
            // Add the new product to cart
            $added = WC()->cart->add_to_cart($product_id, 1);
        } else {
            // This is an internet plan - remove any existing internet plans
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
    
    // Loop through cart items
    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        $product_price = $product->get_price();
        $subtotal += $product_price;
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
    
    // Calculate total
    $total = $subtotal + $tax_total;
    
    // Format with wc_price for consistency
    return wc_price($total);
}
add_shortcode('upfront_fee_total', 'upfront_fee_total_shortcode');

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