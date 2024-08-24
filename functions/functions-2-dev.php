<?php 

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

/*==================== Monthly Summary Shortcode Display & Calculation Functions =====================*/


// Add monthly_fee to cart item data

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

// --- Calculation Functions  ---

function calculate_monthly_fees_subtotal() {
    $monthly_fee_subtotal = 0;
    foreach (WC()->cart->get_cart() as $cart_item) {
        if (isset($cart_item['monthly_fee'])) {
            $monthly_fee_subtotal += $cart_item['monthly_fee'] * $cart_item['quantity'];
        }
    }
    return $monthly_fee_subtotal;
}

function calculate_monthly_fees_tax() {
    $monthly_fee_tax = 0;
    foreach (WC()->cart->get_cart() as $cart_item) {
        if (isset($cart_item['monthly_fee_tax'])) {
            $monthly_fee_tax += $cart_item['monthly_fee_tax'] * $cart_item['quantity'];
        }
    }
    return $monthly_fee_tax;
}

function calculate_monthly_fees_total() {
    $subtotal = calculate_monthly_fees_subtotal();
    $tax = calculate_monthly_fees_tax();
    return $subtotal + $tax;
}

// --- Display Functions ---

function display_monthly_fees_summary_shortcode() {
    $total_monthly_fee_subtotal = calculate_monthly_fees_subtotal();
    $total_monthly_fee_tax = calculate_monthly_fees_tax();
    $total_monthly_fee = $total_monthly_fee_subtotal + $total_monthly_fee_tax;

    $output = '<h4>' . __('Your Monthly Summary ', 'woocommerce') . '</h4>';
	$output .= '<p style="margin-bottom: 10px;">This amount will be billed to you on a monthly basis starting next month</p>';
     $output .= '<table class="shop_table shop_table_responsive monthly_summary_table">';

    // Display individual products and their monthly fees
    foreach (WC()->cart->get_cart() as $cart_item) {
        $product = $cart_item['data'];
        $product_title = $product->get_name();
        $monthly_fee = isset($cart_item['monthly_fee']) ? $cart_item['monthly_fee'] : 0;
        $quantity = $cart_item['quantity'];
        $product_monthly_fee_total = $monthly_fee * $quantity;

        if ($total_monthly_fee > 0) {
            $output .= '<tr class="individual-monthly-fee">
            <th>' . $product_title . '</th>
            <td>' . wc_price($product_monthly_fee_total) . '</td>
            </tr>';
        }
    }

    // Display monthly fees subtotal
    if ($total_monthly_fee_subtotal > 0) {
        $output .= '<tr class="total-monthly-fees-subtotal">
        <th>' . __('Monthly Fees Subtotal', 'woocommerce') . '</th>
        <td>' . wc_price($total_monthly_fee_subtotal) . '</td>
        </tr>';
    }

    // Display monthly fees tax
    if ($total_monthly_fee_tax > 0) {
        $output .= '<tr class="total-monthly-fees-tax">
        <th>' . __('Monthly Tax', 'woocommerce') . '</th>
        <td>' . wc_price($total_monthly_fee_tax) . '</td>
        </tr>';
    }

    // Display total monthly fees
    if ($total_monthly_fee > 0) {
        $output .= '<tr class="total-monthly-fees">
        <th>' . __('Total Monthly Fees', 'woocommerce') . '</th>
        <td>' . wc_price($total_monthly_fee) . '</td>
        </tr>';
    }

    $output .= '</table>';

    return $output;
}
add_shortcode('monthly_fees_summary', 'display_monthly_fees_summary_shortcode');


// Hide shipping costs in the checkout order review table
// add_filter('woocommerce_cart_totals_shipping_html', '__return_empty_string');

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


// Function to handle AJAX request for Monthly Fee Subtotal

function calculate_monthly_fee_subtotal() {
    check_ajax_referer('calculate_monthly_fee_subtotal_nonce', 'nonce');

    if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
        error_log('Not an AJAX request');
        wp_send_json_error(array('error' => 'Invalid request'));
        return;
    }

    try {
        $monthly_fee_subtotal = calculate_monthly_fees_subtotal();
		$individual_fees_html = '';

		foreach (WC()->cart->get_cart() as $cart_item) {
        $product = $cart_item['data'];
        $product_title = $product->get_name();
        $monthly_fee = isset($cart_item['monthly_fee']) ? $cart_item['monthly_fee'] : 0;
        $quantity = $cart_item['quantity'];
        $product_monthly_fee_total = $monthly_fee * $quantity;

		if ($monthly_fee > 0) {
			$individual_fees_html .= '<tr class="individual-monthly-fee">
			<th>' . $product_title . '</th>
			<td>' . wc_price($product_monthly_fee_total) . '</td>
			</tr>';
		}
		}

		$html = '<tr class="total-monthly-fees-subtotal">
		<th>' . __('Monthly Fees Subtotal', 'woocommerce') . '</th>
		<td>' . wc_price($monthly_fee_subtotal) . '</td>
		</tr>';
        wp_send_json_success(array('html' => $html, 'individual_fees_html' => $individual_fees_html));
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
        $monthly_fee_tax = calculate_monthly_fees_tax();
		$html = '<tr class="total-monthly-fees-tax">
		<th>' . __('Monthly Tax', 'woocommerce') . '</th>
		<td>' . wc_price($monthly_fee_tax) . '</td>
		</tr>';
        wp_send_json_success(array('html' => $html));
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


// Ensure the cart is initialized on product pages

function initialize_cart_on_product_page() {
    if (is_product()) {
        WC()->cart->calculate_totals();
    }
}

add_action('template_redirect', 'initialize_cart_on_product_page');
