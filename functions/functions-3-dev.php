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
    $tax_total = 0;

    // Retrieve the appropriate tax rates based on the base location or default to standard rates
    $tax_rates = WC_Tax::get_rates();

    foreach (WC()->cart->get_cart() as $cart_item) {
        if (isset($cart_item['monthly_fee'])) {
            $item_subtotal = $cart_item['monthly_fee'] * $cart_item['quantity'];
			// Calculate tax for each item
            $item_taxes = WC_Tax::calc_tax($item_subtotal, $tax_rates, false);

            // Sum up all tax components
            foreach ($item_taxes as $tax) {
                $tax_total += $tax;
            }
        }
    }

    return $tax_total;
}



function calculate_monthly_fees_total() {
    $subtotal = calculate_monthly_fees_subtotal();
    $tax = calculate_monthly_fees_tax();
    return $subtotal + $tax;
}

// --- Display Functions ---

function display_monthly_fees_summary_shortcode() {
    // Ensure WooCommerce cart is initialized
    if (!is_object(WC()->cart)) {
        WC()->cart = new WC_Cart();
    }

    $cart = WC()->cart;
    $total_monthly_fee_subtotal = $cart->get_cart_contents_count() > 0 ? calculate_monthly_fees_subtotal() : 0;
    $total_monthly_fee_tax = $cart->get_cart_contents_count() > 0 ? calculate_monthly_fees_tax() : 0;
    $total_monthly_fee = $total_monthly_fee_subtotal + $total_monthly_fee_tax;

    $output = '<h4>' . __('Your Monthly Summary ', 'woocommerce') . '</h4>';
    $output .= '<p style="margin-bottom: 10px;">This amount will be billed to you on a monthly basis starting next month</p>';
    $output .= '<table class="shop_table shop_table_responsive monthly_summary_table">';

    if ($cart->get_cart_contents_count() > 0) {
        foreach ($cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            $product_title = $product->get_name();
            $monthly_fee = isset($cart_item['monthly_fee']) ? $cart_item['monthly_fee'] : 0;
            $quantity = $cart_item['quantity'];
            $product_monthly_fee_total = $monthly_fee * $quantity;

            $output .= '<tr class="individual-monthly-fee">
            <th>' . $product_title . '</th>
            <td>' . wc_price($product_monthly_fee_total) . '</td>
            </tr>';
        }
    } else {
        // Display a placeholder row when cart is empty
        $output .= '<tr class="individual-monthly-fee">
        <th>Sample Product</th>
        <td>' . wc_price(0) . '</td>
        </tr>';
    }

    // Always display subtotal, tax, and total rows
    $output .= '<tr class="total-monthly-fees-subtotal">
    <th>' . __('Monthly Fees Subtotal', 'woocommerce') . '</th>
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
