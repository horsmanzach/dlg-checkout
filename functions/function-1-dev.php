<?php 

/*====================Show Monthly Fee Custom Fields on Cart Template =====================*/


// Add monthly fee to cart item data

add_filter('woocommerce_add_cart_item_data', 'add_monthly_fee_to_cart_item', 10, 2);

function add_monthly_fee_to_cart_item($cart_item_data, $product_id) {
    $monthly_fee = get_field('monthly_fee', $product_id);
    if ($monthly_fee) {
        $cart_item_data['monthly_fee'] = $monthly_fee;
    }
    return $cart_item_data;
}

// Add monthly_fee_tax to cart item data

add_filter('woocommerce_add_cart_item_data', 'add_monthly_fee_tax_to_cart_item', 10, 2);
function add_monthly_fee_tax_to_cart_item($cart_item_data, $product_id) {
    $product = wc_get_product($product_id);
    $monthly_fee_tax = get_field('monthly_fee_tax', $product_id); // Retrieve the ACF field value
    if (!empty($monthly_fee_tax)) {
        $cart_item_data['monthly_fee_tax'] = $monthly_fee_tax;
    }
    return $cart_item_data;
}



// Calculate monthly fee subtotal

function calculate_monthly_fees_subtotal() {
    $total_monthly_fee_subtotal = 0;
    foreach ( WC()->cart->get_cart() as $cart_item ) {
        if ( isset( $cart_item['monthly_fee'] ) ) {
            $total_monthly_fee_subtotal += floatval( $cart_item['monthly_fee'] );
        }
    }
    return $total_monthly_fee_subtotal;
}

// Calculate monthly fee tax 

function calculate_monthly_fees_tax() {
    $total_monthly_fee_tax = 0;
    foreach ( WC()->cart->get_cart() as $cart_item ) {
        if ( isset( $cart_item['monthly_fee_tax'] ) ) {
            $total_monthly_fee_tax += floatval( $cart_item['monthly_fee_tax'] );
        }
    }
    return $total_monthly_fee_tax;
}

// Calculate monthly fee total

function calculate_monthly_fees_total() {
    $total_monthly_fee = 0;

    foreach (WC()->cart->get_cart() as $cart_item) {
        $monthly_fee = isset($cart_item['monthly_fee']) ? $cart_item['monthly_fee'] : 0;
        $monthly_fee_tax = isset($cart_item['monthly_fee_tax']) ? $cart_item['monthly_fee_tax'] : 0;
        $total_monthly_fee += ($monthly_fee + $monthly_fee_tax) * $cart_item['quantity'];
    }
    return $total_monthly_fee;
}


// Display monthly fees subtotal on cart page
add_action('woocommerce_proceed_to_checkout', 'display_monthly_fees_subtotal_on_cart', 20);

function display_monthly_fees_subtotal_on_cart() {
    $total_monthly_fee_subtotal = calculate_monthly_fees_subtotal();
    if ($total_monthly_fee_subtotal > 0) {
        echo '<tr class="total-monthly-fees-subtotal">
        <th>' . __('Monthly Fees Subtotal', 'woocommerce') . '</th>
        <td>' . wc_price($total_monthly_fee_subtotal) . '</td>
        </tr>';

        // Display individual products and their monthly fees
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            $product_title = $product->get_name();
            $monthly_fee = isset($cart_item['monthly_fee']) ? $cart_item['monthly_fee'] : 0;
            $quantity = $cart_item['quantity'];
            $total_monthly_fee = $monthly_fee * $quantity;

            if ($total_monthly_fee > 0) {
                echo '<tr class="individual-monthly-fee">
                <th>' . $product_title . ' (' . $quantity . ' x ' . wc_price($monthly_fee) . ')</th>
                <td>' . wc_price($total_monthly_fee) . '</td>
                </tr>';
            }
        }
    }
}

// Display monthly fees tax on cart


 add_action('woocommerce_proceed_to_checkout', 'display_total_monthly_fees_tax_on_cart', 40);

function display_total_monthly_fees_tax_on_cart() {
    $total_monthly_fee_tax = calculate_monthly_fees_tax();
    if ($total_monthly_fee_tax > 0) {
        echo '<tr class="total-monthly-fees-tax">
        <th>' . __('Monthly Fees Tax', 'woocommerce') . '</th>
    	<td>' . wc_price($total_monthly_fee_tax) . '</td>
    	</tr>';
    }
}


// Display total monthly fees (subtotal + tax) on cart

 add_action('woocommerce_proceed_to_checkout', 'display_total_monthly_fees_on_cart', 60);

function display_total_monthly_fees_on_cart() {
    $total_monthly_fee = calculate_monthly_fees_total();
    if ($total_monthly_fee > 0) {
        echo '<tr class="total-monthly-fees">
        <th>' . __('Total Monthly Fees', 'woocommerce') . '</th>
    	<td>' . wc_price($total_monthly_fee) . '</td>
    	</tr>';
    }
}

/*============Show Monthly Fee Custom Fields on Product page Template============*/

// Enqueue JS File

function enqueue_custom_scripts() {
    wp_enqueue_script('custom-ajax-script', get_stylesheet_directory_uri() . '/js/custom-ajax.js', array('jquery'), null, true);

    wp_localize_script('custom-ajax-script', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce_subtotal' => wp_create_nonce('calculate_monthly_fee_subtotal_nonce'),
		'nonce_tax' => wp_create_nonce('calculate_monthly_fee_tax_nonce'),
		'nonce_total' => wp_create_nonce('calculate_monthly_fee_total_nonce')
    ));
}

add_action('wp_enqueue_scripts', 'enqueue_custom_scripts');



// Function to get the monthly_fee subtotal

function get_monthly_fee_subtotal() {
    $monthly_fee_subtotal = 0;

    foreach (WC()->cart->get_cart() as $cart_item) {
        if (isset($cart_item['monthly_fee'])) {
            $monthly_fee_subtotal += $cart_item['monthly_fee'] * $cart_item['quantity'];
        }
    }
	 return $monthly_fee_subtotal;
}


// Function to get monthly fee tax
function get_monthly_fee_tax() {
	$monthly_fee_tax = 0;

 foreach (WC()->cart->get_cart() as $cart_item) {
        if (isset($cart_item['monthly_fee_tax'])) {
            $monthly_fee_tax += $cart_item['monthly_fee_tax'] * $cart_item['quantity'];
        }
    }
	 return $monthly_fee_tax;
}

// Function to get monthly fee total (subtotal + tax)

function get_monthly_fee_total() {
    $monthly_fee_total = 0;

    foreach (WC()->cart->get_cart() as $cart_item) {
        $monthly_fee = isset($cart_item['monthly_fee']) ? $cart_item['monthly_fee'] : 0;
        $monthly_fee_tax = isset($cart_item['monthly_fee_tax']) ? $cart_item['monthly_fee_tax'] : 0;
        $monthly_fee_total += ($monthly_fee + $monthly_fee_tax) * $cart_item['quantity'];
    }
    return $monthly_fee_total;
}


// Function to handle AJAX request for Monthly Fee Subtotal

function calculate_monthly_fee_subtotal() {
    check_ajax_referer('calculate_monthly_fee_subtotal_nonce', 'nonce');

    if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
        error_log('Not an AJAX request');
        wp_send_json_error(array('error' => 'Invalid request'));
        return;
    }

    try {
        $monthly_fee_subtotal = get_monthly_fee_subtotal();
        wp_send_json_success(array('monthly_fee_subtotal' => wc_price($monthly_fee_subtotal)));
    } catch (Exception $e) {
        error_log('Exception: ' . $e->getMessage());
        wp_send_json_error(array('error' => $e->getMessage()));
    }
}

add_action('wp_ajax_calculate_monthly_fee_subtotal', 'calculate_monthly_fee_subtotal');
add_action('wp_ajax_nopriv_calculate_monthly_fee_subtotal', 'calculate_monthly_fee_subtotal');


// Function to handle AJAX request for Monthly Fee Tax

function calculate_monthly_fee_tax() {
    check_ajax_referer('calculate_monthly_fee_tax_nonce', 'nonce');

    if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
        error_log('Not an AJAX request');
        wp_send_json_error(array('error' => 'Invalid request'));
        return;
    }

    try {
        $monthly_fee_tax = get_monthly_fee_tax();
        wp_send_json_success(array('monthly_fee_tax' => wc_price($monthly_fee_tax)));
    } catch (Exception $e) {
        error_log('Exception: ' . $e->getMessage());
        wp_send_json_error(array('error' => $e->getMessage()));
    }
}

add_action('wp_ajax_calculate_monthly_fee_tax', 'calculate_monthly_fee_tax');
add_action('wp_ajax_nopriv_calculate_monthly_fee_tax', 'calculate_monthly_fee_tax');


// Function to handle AJAX request for Monthly Fee Total

function calculate_monthly_fee_total() {
    check_ajax_referer('calculate_monthly_fee_total_nonce', 'nonce');

    if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
        error_log('Not an AJAX request');
        wp_send_json_error(array('error' => 'Invalid request'));
        return;
    }

    try {
        $monthly_fee_total = get_monthly_fee_total();
        wp_send_json_success(array('monthly_fee_total' => wc_price($monthly_fee_total)));
    } catch (Exception $e) {
        error_log('Exception: ' . $e->getMessage());
        wp_send_json_error(array('error' => $e->getMessage()));
    }
}

add_action('wp_ajax_calculate_monthly_fee_total', 'calculate_monthly_fee_total');
add_action('wp_ajax_nopriv_calculate_monthly_fee_total', 'calculate_monthly_fee_total');



// Display total monthly fee subtotal on product page 

function display_monthly_fee_subtotal_on_product_page() {
    if (is_product()) {
        $monthly_fee_subtotal = get_monthly_fee_subtotal();
        echo '<p class="monthly-fee-subtotal">Monthly Fees Subtotal: ' . wc_price($monthly_fee_subtotal) . '</p>';
    }
}

add_action('woocommerce_before_add_to_cart_form', 'display_monthly_fee_subtotal_on_product_page', 25);


// Display total monthly fee tax on product page 


function display_monthly_fee_tax_on_product_page() {
    if (is_product()) {
        $monthly_fee_tax = get_monthly_fee_tax();
        echo '<p class="monthly-fee-tax">Total Monthly Fees Tax: ' . wc_price($monthly_fee_tax) . '</p>';
    }
}

add_action('woocommerce_before_add_to_cart_form', 'display_monthly_fee_tax_on_product_page', 45);

// Display total monthly fee total on product page 

function display_monthly_fee_total_on_product_page() {
    if (is_product()) {
        $monthly_fee_total = get_monthly_fee_total();
        echo '<p class="monthly-fee-total">Total Monthly Fees: ' . wc_price($monthly_fee_total) . '</p>';
    }
}

add_action('woocommerce_before_add_to_cart_form', 'display_monthly_fee_total_on_product_page', 65);


// Ensure the cart is initialized on product pages

function initialize_cart_on_product_page() {
    if (is_product()) {
        WC()->cart->calculate_totals();
    }
}

add_action('template_redirect', 'initialize_cart_on_product_page');
