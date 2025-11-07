<?php

global $user_data;	
$user_data = dg_get_current_user_data();

$ins_number_of_days = 3;
$ins_time = array ( "Morning: 8AM - 12PM"=>"Morning: 8AM - 12PM",
	"Afternoon: 12PM - 5PM"=>"Afternoon: 12PM - 5PM",
	"Evening: 5PM - 9PM"=>"Evening: 5PM - 9PM");

$ins_day_label = array("","<strong>1</strong><sup>st</sup> choice","<strong>2</strong><sup>nd</sup> choice","<strong>3</strong><sup>rd</sup> choice");
$select_hdyhau = array("Friend or family","Word of mouth","Google","Facebook","Yelp","CanadianISP","Planhub","Compare My Rates","Kijiji","Bing","Online reviews","Prefer not to say","Retail store","Other");

if( isset($_GET['add-plan'])) {
	$selected_plan = $_GET['add-plan'];
	$selected_plan = Cleanup_Number( trim($selected_plan) );
	dg_set_user_meta("selected_internet_plan", $selected_plan);
}

if( isset($_GET['ccd']) ) {
	$ccd = $_GET['ccd'];
	dg_set_user_meta("ccd", $ccd);
}

function Render_order_summary( $user_data ) {

		$res = '<div class="dg_cart dg_checkout">';
		$res .= '<div class="et_pb_section et_pb_section_1 et_section_regular" style="width:100%;padding:0;">';
		$res .= '<div class="et_pb_row et_pb_row_1">';
		$res .= '<div class="et_pb_column et_pb_column_2_3 et_pb_column_1    et_pb_css_mix_blend_mode_passthrough">';
		$res .= '<div class="et_pb_module et_pb_code et_pb_code_1">';
		$res .= '<div class="dg_review_order_details">';

		$res .= '<h1 align="center"> Order is Complete </h1>';

		/* Customer info */
		$res .= "<br><div class='review_order_internet'>";

		$res .= "<h2 class='dg_ord_heading'>Customer Details:</h2>";
                $res .= "<p class='dg_ord_data upfront_total' style=\"font-size:20px;\">";
                $res .= "<span class='plan_name'>Name</span>";
                $res .= "<span class='plan_pricing'>" . $user_data['first_name'] . " " . $user_data['last_name'] . "</span>";
                $res .= "</p>";

                $res .= "<p class='dg_ord_data upfront_total' style=\"font-size:20px;\">";
                $res .= "<span class='plan_name'>Email</span>";
                $res .= "<span class='plan_pricing'>" . $user_data['email'] . "</span>";
                $res .= "</p>";
                $res .= "<p class='dg_ord_data upfront_total' style=\"font-size:20px;\">";
                $res .= "<span class='plan_name'>Phone</span>";
                $res .= "<span class='plan_pricing'>" . $user_data['phone'] . "</span>";
		$res .= "</p>";

		$addr = "";
		if( strlen( $user_data["unit_num"] ) > 0 ) {
			$addr = $user_data["unit_num"] . "<br>";
		}
		$addr .= $user_data['street_number']." ".$user_data['street_name']." ".$user_data['street_dir']." ".$user_data['street_type'] . " " .
			$user_data['city'] ." " . $user_data['prov'] . " " . $user_data['postal_code']; 

                $res .= "<p class='dg_ord_data upfront_total' style=\"font-size:20px;\">";
                $res .= "<span class='plan_name'>Service Address</span>";
                $res .= "<span class='plan_pricing'>" . $addr . "</span>";
		$res .= "</p>";

                $res .= "</div>";

		/* *** Upfront Summary ***/
		$upfront_summary = get_upfront_fee_summary();

                $res .= '<h3 style="color: #868686; font-family: \'barlow-regular\';font-size:26px;border-bottom: 1px dashed #CCC;margin-bottom: 20px;">Upfront Payment Summary</h3>';
		$res .= "<p class='dg_ord_data'>";
		$res .= "<span class='plan_name'> Internet - " . $upfront_summary['internet-plan'][0] . "</span>";
                $res .= "<span class='plan_pricing'>$" . number_format($upfront_summary['internet-plan'][1],2) . "</span>";
		$res .= "</p>";

		if ($upfront_summary['phone-plan'][1]>0) {
			$res .= "<p class='dg_ord_data'>";
			$res .= "<span class='plan_name'>" . "Phone - " . $upfront_summary['phone-plan'][0] . "</span>";
			$res .= "<span class='plan_pricing'>$" . number_format($upfront_summary['phone-plan'][1],2) . "</span>";
			$res .= "</p>";
                } 

		if ( $upfront_summary['ModemPurchaseOption'] == true && $upfront_summary['modems'][1]>0) { 
			$res .=	 "<p class='dg_ord_data'>";
			$res .=	 "<span class='plan_name'>" . $upfront_summary['modems'][0] . "</span>";
			$res .=	 "<span class='plan_pricing'>$"  . number_format($upfront_summary['modems'][1],2) . "</span>";
			$res .=	 "</p>";
		} 

		if ($upfront_summary['fixed-fee'][1]>0) { 
			$res .= "<p class='dg_ord_data'>";
			$res .= "<span class='plan_name'>" . $upfront_summary['fixed-fee'][0] . "</span>";
			$res .= "<span class='plan_pricing'>$" . number_format($upfront_summary['fixed-fee'][1],2) . "</span>";
			$res .= "</p>";
		} 

                $res .= "<p class='dg_ord_data'>";
                $res .= "<span class='plan_name'>" . $upfront_summary['subtotal'][0] . "</span>";
                $res .= "<span class='plan_pricing'>" . wc_price($upfront_summary['subtotal'][1]) . "</span>";
                $res .= "</p>";

                $res .= "<p class='dg_ord_data'>";
                $res .= "<span class='plan_name'>" . $upfront_summary['taxes'][0] . "</span>";
                $res .= "<span class='plan_pricing'>$" . number_format($upfront_summary['taxes'][1],2) . "</span>";
                $res .= "</p>";

		if ( $upfront_summary['ModemPurchaseOption'] == false && $upfront_summary['modems'][1]>0) { 
			$res .= "<p class='dg_ord_data' style='padding-bottom:0;'>";
			$res .= "<span class='plan_name'>" . $upfront_summary['modems'][0] . "</span>";
			$res .= "<span class='plan_pricing'>$" . number_format($upfront_summary['modems'][1],2) . "</span>";
			$res .= "</p>";
		} 

                if ( $upfront_summary['deposit'][1]>0) {
			$res .= "<p class='dg_ord_data display_deposit' style='padding-bottom:0;'>";
			$res .= "<span class='plan_name'>" . $upfront_summary['deposit'][0] . "</span>";
			$res .= "<span class='plan_pricing'>$" . number_format($upfront_summary['deposit'][1],2) . "</span>";
			$res .= "</p>";
		} 

                $res .= "<p class='dg_ord_data upfront_total' style=\"font-size:20px;\">";
                $res .= "<span class='plan_name'>" . $upfront_summary['grand_total'][0] . "</span>";
                $res .= "<span class='plan_pricing'>$" . number_format($upfront_summary['grand_total'][1],2) . "</span>";
                $res .= "</p><br>";

		/*** Payment Summary ****/
		$res .= "<br><div class='review_order_internet'>";
		$res .= "<h2 class='dg_ord_heading'>Payment Summary</h2>";

		if( strcmp( $user_data['upfront_payment'], "email-transfer") == 0 ) {
			$res .= "<p class='dg_ord_data upfront_total' style=\"font-size:20px;\">";
                        $res .= "<span class='plan_name'> Upfront Payment Method </span>";
                        $res .= "<span class='plan_pricing'> Email Transfer to etransfers@diallog.com </span>";
                        $res .= "</p>";
		} else {
			$res .= "<p class='dg_ord_data upfront_total' style=\"font-size:20px;\">";
			$res .= "<span class='plan_name'>Credit Card Payment </span>";
			$res .= "<span class='plan_pricing'>" . $user_data['upfront_payment'] . "</span>";
			$res .= "</p>";
			$res .= "<p class='dg_ord_data upfront_total' style=\"font-size:20px;\">";
			$res .= "<span class='plan_name'>Credit Card Transaction </span>";
			$res .= "<span class='plan_pricing'>" . $user_data['upfront_payment_ref'] . "</span>";
			$res .= "</p>";
			$res .= "<p class='dg_ord_data upfront_total' style=\"font-size:20px;\">";
			$res .= "<span class='plan_name'>Credit Card Auth Code </span>";
			$res .= "<span class='plan_pricing'>" . $user_data['upfront_payment_code'] . "</span>";
			$res .= "</p>";
			$res .= "<p class='dg_ord_data upfront_total' style=\"font-size:20px;\">";
			$res .= "<span class='plan_name'>Credit Card Transaction Date</span>";
			$res .= "<span class='plan_pricing'>" . $user_data['upfront_payment_date'] . "</span>";
			$res .= "</p>";
			$res .= "<p class='dg_ord_data upfront_total' style=\"font-size:20px;\">";
			$res .= "<span class='plan_name'>Credit Card Transaction Amount </span>";
			$res .= "<span class='plan_pricing'>$" . $user_data['upfront_payment_amount'] . "</span>";
			$res .= "</p>";
		}

                $res .= "</div>";


		/*** Internet Plan ***/
		$res .= "<br><div class='review_order_internet'>";
		$res .= "<h2 class='dg_ord_heading'>Your Internet Plan</h2>";
		$internet_plan_name = "";
		$internet_plan_initfees = "";
		$internet_plan_monthly = "";
		if( GetInternetPlanInfo( $internet_plan_name, $internet_plan_monthly, $internet_plan_initfees ) ) {
			$res .= "<p class='dg_ord_data display_internet_plan'><span class='plan_name'>$internet_plan_name</span>";
			$res .= "<span class=\"woocommerce-Price-amount amount\"><span class=\"woocommerce-Price-currencySymbol\">$</span>$internet_plan_monthly</span></span>";
		}

		$res .= "</div>";
		$res .= "<div class='review_order_date'>";
		$res .= "<h2 class='dg_ord_heading'>Preffered Installation Dates</h2>";

		$res .= "<p class='dg_ord_data display_date_1'>";
		$res .= $user_data["preffered_installation_date_1"] . " " . $user_data["preffered_installation_time_1"]; 
		$res .= "</p>";

		$res .= "<p class='dg_ord_data display_date_2'>";
		$res .= $user_data["preffered_installation_date_2"] . " " . $user_data["preffered_installation_time_2"]; 
		$res .= "</p>";

		$res .= "</div>";

		// installation
		$installation_class_name = "";
		$installation_fees = "";
		$installation_fees_product_name = "";

		if( GetInstallationFees($installation_fees, $installation_class_name, $installation_fees_product_name ) ) {
			$res .= "<div class='review_order_installation'>";
			$res .= "<h2 class='dg_ord_heading'>Installation</h2>";
			$res .= "<p class=\"dg_ord_data\">";
			$res .= "<span class=\"plan_name\">$installation_fees_product_name</span> <span class=\"plan_pricing\">";
			$res .= "<span class=\"woocommerce-Price-amount amount\"><span class=\"woocommerce-Price-currencySymbol\">$</span>$installation_fees</span></span>";
			$res .= "</p>";
			$res .= "</div>";

			error_log("In signup review order page, $installation_class_name, $installation_fees, $installation_fees_product_name");
		}

		// modems
		$modem_class_name = "";
		$modem_fees = "";
		$modem_fees_product_name = "";
		$modem_upfront_info = "";
		if( GetModemsInfo($modem_fees_product_name, $modem_fees, $modem_class_name, $modem_upfront_info ) ) {
			$res .= "<div class='review_order_modem'>";
			$res .= "<h2 class='dg_ord_heading'>Modem Option</h2>";

			$res .= "<p class='dg_ord_data upfront_total' style=\"font-size:20px;\">";
			$res .= "<span class='plan_name'>$modem_fees_product_name</span>";
			$res .= "<span class='plan_pricing'>$modem_fees</span>";
			$res .= "</p>";

			$res .= "$modem_upfront_info";
			$res .= "</div>";
		}

		// delivery address
		if( strlen($user_data['modem_delivery']) > 0 ) {

			$res .= "<div class='review_order_service_address'>";
			$res .= "<h2 class='dg_ord_heading'>Ship to Address </h2>";
			$res .= "<p class='dg_ord_data upfront_total' style=\"font-size:20px;\">";

			if( strcmp( "Ship to service address", $user_data['modem_delivery'] ) == 0 ) {
				$res .= $user_data['modem_delivery'];
				$res .= "</p>";
			} else {
				$res .= $user_data['modem_shipping_street'] . "<br>".$user_data['modem_shipping_city'] ." " . $user_data['modem_shipping_state'] .
					" " . $user_data['modem_shipping_postal_code'];
				$res .= "</p>";
				$res .= "<p class='dg_ord_data upfront_total' style=\"font-size:20px;\">";
				if( strlen( $user_data["modem_shipping_unit_no"] ) > 0 ) {
					$res .= "Unit " . $user_data["modem_shipping_unit_no"] . "<br>";
				}
				$res .= "</p>";
			}

			$res .= "</div>";
		}

		// phone
		$phone_class_name = "";
		$phone_fees = "";
		$phone_fees_product_name = "";
		if( GetPhoneInfo($phone_fees_product_name, $phone_fees, $phone_class_name ) ) {
			$res .= "<div class='review_order_phone'>";
			$res .= "<h2 class='dg_ord_heading'>Phone</h2>";
			$res .= "<p class='dg_ord_data upfront_total' style=\"font-size:20px;\">";
			$res .= "<span class='plan_name'>$phone_fees_product_name</span>";
			$res .= "<span class='plan_pricing'>$phone_fees</span>";
			$res .= "</p>";
			$res .= "</div>";
		}

		$res .= "</div>";
		$res .= "</div>";
		$res .= "</div>";
		$res .= "</div>";
		$res .= "</div>";
		$res .= "</div>";

		return $res;
}

function send_email_confirmation_of_order( $email, $ret ) {

	$headers='';
	$headers.="From:residential.orders@diallog.com \r\n";
	$headers.="BCC:residential.orders@diallog.com \r\n";
	$headers.="MIME-Version: 1.0 \r\n";
	$headers.="Content-type: text/html; charset=\"UTF-8\" \r\n";

	$pre = "<html>";
	$pre .= ' 
		<head>
		<style>
	ul.signup_steps_nav li {
                /*width: 100%;*/
        }
        .form-res-internet .form-group.two-fields-group {
                grid-template-columns: 1fr;
        }
        .dg_cart .confirmation_button, .woocommerce #payment #place_order, .woocommerce-page #payment #place_order {
                text-align: center;
                float: none;
                width: 100%;
                clear: both;
                margin: 10px auto;
        }
        ul.signup_steps_nav li {
                width: 30px;
        }
        .signup_steps_nav li span {
            height: 15px;
            width: 15px;
            line-height: 5px;
        }
        .change_address_checkout_btn, .change_plan_button {
            text-align: center;
            margin: 20px auto 0;
            float: none !important;
            width: 100%;
        }
        .dg_ord_data .plan_name {
            width: 70%;
            display: inline-block;
        }
        .inbox_new_column ul li {
                width: 100%;
                margin-left: 10%;
	}
</style>
</head>
<body>
<br>
<p class="dg_ord_data upfront_total" style="font-size:20px;" >
Thank you for submitting your order and trusting us with your Internet needs. Our customer onboarding team will submit your order shortly and reach out with next steps.
</p>
<br>
<br>
';
	
	$ret = $pre . "" . $ret . "</body></html>";

	wp_mail( "$email", "Your Diallog order confirmation", $ret, $headers); 
	//wp_mail( "ihab.khalil@gmail.com", "Your Diallog order confirmation", $ret, $headers); 
}

if( isset( $_POST['dg-checkout'] )) {

	/* This is the final summary of the order which appear at the end of the signup
	 */
	if( isset( $user_data['upfront_payment'] )  && 
		( strcmp( $user_data['upfront_payment'], "true") == 0 || strcmp( $user_data['upfront_payment'], "email-transfer") == 0 ) ) {

		dg_set_user_meta("onboarding_stage","signup_complete");

		$ret = Render_order_summary( $user_data );
		echo $ret;

		$to = $user_data['email'];
		send_email_confirmation_of_order( $to, $ret );

		exit(0);
	}

	/*
	if (!empty($_POST['first_name']) && !empty($_POST['last_name']) && !empty($_POST['email']) && !empty($_POST['phone'])) {
		
		$data['lead_status'] = "Interested waiting for PAP";  
		//hubspot_api_update_contact_by_email( $data , $_POST['email'] );
		
		dg_set_user_meta("onboarding_stage","signup_complete");
		dg_set_user_meta("order_step",7);
		
		header("location: ".home_url("/residential/checkout/"));
		exit;
		
	} 
	 */

	return;
}



if( isset( $user_data['upfront_payment'] )  && 
	( strcmp( $user_data['upfront_payment'], "true") == 0 || strcmp( $user_data['upfront_payment'], "email-transfer") == 0 )  ) {


	if( isset( $user_data['order_complete_timestamp'] ) ) {

		$timestamp_order = $user_data['order_complete_timestamp'];

		if(  time() < ( $user_data['order_complete_timestamp'] + (60 ) ) )  {

			$upfront_payment = $user_data['upfront_payment'];
			$timestamp_order = $user_data['order_complete_timestamp'];
			error_log(" Upfront Payment $upfront_payment , $timestamp_order, " . time() );

			$ret = Render_order_summary( $user_data );
			echo $ret;

			send_email_confirmation_of_order( $ret );

			exit(0);
		}
	}
}


/*
 * TODO: IHAB COMMENTED OUT when introduced the drop down options
if (date("N")==7) {
	
	//$prfd = date("m/d/Y",strtotime("+5 days"));

} else {
	
	//$prfd = date("m/d/Y",strtotime("+6 days"));
}

if( isset( $user_data['preffered_installation_date_1'] ) ) {
	$pref_date_1 = $user_data['preffered_installation_date_1']!=""? $user_data['preffered_installation_date_1']:$prfd;
	$pref_date_2 = $user_data['preffered_installation_date_2'];
	$pref_date_3 = $user_data['preffered_installation_date_3'];
}
 */


function render_toc() {

	echo '<div class="form-row place-order">
		<p class="form-row form-row-wide validate-required" id="upfront_billing_toc_field" style="text-align:center;">
		<a style="color:red;font-size:16px;border:1px solid red;padding: 10px 20px;background: #ECECEC;border-radius: 6px;margin: 10px;display: inline-block;"
				href="#" onclick="agree_terms();" rel="noopener noreferrer"> Click this button to read our Terms and Conditions before placing the order.
		</a>
		<br>
		</div>

		<div id="upfront_toc_modal" class="modal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
		<div class="modal-dialog modal-lg" >
		<div class="modal-content">
		<div class="modal-header">
		<button type="button" onclick="dismiss_modal_toc();" ><span aria-hidden="true">&times;</span></button>
		</div>
		<div class="modal-body">
		<h2>Payment Terms and Conditions</h2>
		<p>&nbsp;</p>
		<div id="et-boc" class="et-boc">
		<div class="et_builder_inner_content et_pb_gutters3">
		<h3>Installation Appointment Policy</h3>
		<p>I agree to attend the installation appointment on selected date and time and provide technician with access to the property telephone room. I understand that I can only cancel my installation appointment with *48 hours notice. I agree to pay a *$100 Appointment fee** if I miss the appointment or technician is denied access or I cancel without notice.</p>
		<div class="lto-dynamic">
		<h3>Modem Usage Policy* (applicable only if you use a Diallog modem)</h3>
		<p>I agree to return the Diallog supplied modem within 2 weeks of cancelling my service. If I don’t return the modem within 2 weeks, I agree to pay a *$150 Replacement Fee*</p>
		<p>&nbsp;</p>
		</div>
		<h3>*Cancellation and Payment Terms*</h3>
		<p>I understand that my service will be suspended if my account is past due, and I agree to pay the full balance in order to restore service. I understand that while service is suspended, the monthly fee is still applicable and continues to accrue. I agree to pay a *$25 NSF Fee* every time my payment is declined for any reason.</p>
		<h3>*Disconnection Charge*</h3>
		<p>I agree to pay a *$100 Disconnect Fee* if my account is overdue more than 30 days and Diallog cancels my account for non-payment.</p>
		<h3>*Cancellation*</h3>
		<p>I understand that I can cancel my account at any time, with a minimum of *30 days* notice.</p>
		<p>&nbsp;</p>
		<p>
		<label id="terms_label" class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox" style="display: visible; font-size: 18px;" for="popup_terms">
			<input type="checkbox" id="popup_terms" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="popup_terms" /> 
				I have read and accepted the terms and conditions
			<br />
		</label>
		</p>
		<br>
		<p class="TermAndConditionsEr" style="color: #ff0000;font-size: 15px; display: none;">Please read and Check terms and conditions</p>
		<button class="confirmation_button terms_button orange-button" id="terms_button" style="border-radius: 50px;">Agree Terms</button>
		<br>
		<br>
		</div>
		</div> 
		</div>
		</div>
		</div>
		</div>';
	return;
}

function RenderReviewOrder() {
	$disply_prod = "";

	$disply_prod .= "<div class=\"dg_review_order_details\">";
	$disply_prod .= "<div class='review_order_internet'>";
	$disply_prod .= "<h2 class='dg_ord_heading'>Your Internet Plan</h2>";
	$disply_prod .= "<p class='dg_ord_data display_internet_plan'><span class='plan_name'></span> ";
	$disply_prod .= "<span class='plan_pricing'>00.00 /mo</span></p>";
	$disply_prod .= "</div>";

	$disply_prod .= "<div class='review_order_service_address'>";
	$disply_prod .= "<h2 class='dg_ord_heading'>Service Address</h2>";
	$disply_prod .= "<p class='dg_ord_data display_service_address'></p>";
	$disply_prod .= "<p class='dg_ord_data display_unit_number'></p>";
	$disply_prod .= "<p class='dg_ord_data display_access_instructions'></p>";
	$disply_prod .= "</div>";

	$disply_prod .= "<div class='review_order_date'>";
	$disply_prod .= "<h2 class='dg_ord_heading'>Preffered Installation Dates</h2>";
	$disply_prod .= "<p class='dg_ord_data display_date_1'></p>";
	$disply_prod .= "<p class='dg_ord_data display_date_2'></p>";
	$disply_prod .= "<p class='dg_ord_data display_date_3'></p>";
	$disply_prod .= "</div>";

	$disply_prod .= "<div class='review_order_installation'>";
	$disply_prod .= "<h2 class='dg_ord_heading'>Installation</h2>";
	$disply_prod .= "<p class=\"dg_ord_data display_modem\">";
	$disply_prod .= "<span class=\"plan_darta\">One-time upfront installation fee</span>";
	$disply_prod .= "<span class=\"plan_pricing\">";
	$disply_prod .= "<span class=\"woocommerce-Price-amount amount\">";
	$disply_prod .= "<span class=\"woocommerce-Price-currencySymbol\">$</span>";
	$disply_prod .= "</span></span></p></div>";

	$disply_prod .= "<div class='review_order_modem'>";
	$disply_prod .= "<h2 class='dg_ord_heading'>Modem Option</h2>";
	$disply_prod .= "<p class=\"dg_ord_data display_modem\">";
	$disply_prod .= "<span class=\"plan_darta\"></span>";
	$disply_prod .= "<span class=\"plan_pricing1\">";
	$disply_prod .= "<span class=\"woocommerce-Price-amount amount\">";
	$disply_prod .= "<span class=\"woocommerce-Price-currencySymbol\">$</span>0/mo</span>";
	$disply_prod .= "</span></p>";
	$disply_prod .= "<p class=\"dg_ord_data display_modem150\">";
	$disply_prod .= "<span class=\"plan_darta\">One-time upfront modem deposit fee</span>";
	$disply_prod .= "<span class=\"plan_pricing\">";
	$disply_prod .= "<span class=\"woocommerce-Price-amount amount\">";
	$disply_prod .= "<span class=\"woocommerce-Price-currencySymbol\">$</span>";
	$disply_prod .= "</span></span></p></div>";	


	$disply_prod .= "<div class='review_order_phone' >";
	$disply_prod .= "<h2 class='dg_ord_heading'>Phone</h2>";
	$disply_prod .= "<p class='dg_ord_data display_phone'><span class='plan_name'>";
	$disply_prod .= "</span> <span class='plan_pricing'>00.00 /mo</span></p></div>";
	$disply_prod .= "</div>";
	
	return $disply_prod;
}

function RenderInternetPlans() {

	error_log( "In RenderInternetPlans ");

	$apiResponse = dg_get_user_meta("_api_response");
	$selected_internet_plan = dg_get_user_meta("selected_internet_plan");

	if ( $apiResponse == NULL ) {
		error_log("No availability check done, need it first to get the plans ");
		echo "Check Availabilty"; // todo 
		return;
	}

	$atts = shortcode_atts( array(
		'ids' => 'all',
		'orderby' => 'price',
		'order' => 'ASC',
		'type'=> array('internet-plan'),
	), $atts, 'dg_get_internet_plans' );
	
	if ($atts['ids']=="all") {
		
		$query = new WC_Product_Query( array(
		    'limit' => -1,
		    'orderby' => 'price',
		    'order' => 'ASC',
		    'category' => $atts['type'],
		) );

	} else {

		$query = new WC_Product_Query( array(
		    'limit' => 1,
		    'orderby' => 'price',
		    'order' => 'ASC',
		    'category' => $atts['type'],
		    'include' => explode(",",$ids)
		));

	}

	$products = $query->get_products();

	$button_text = '<a class="btn_mute" href="#" data-target="#plan-building-wizard-modal" ' . 
		'rel="noopener noreferrer">Select Service</a>';

	$disply_prod = "";	
	if($apiResponse["bell"] == true) {
		error_log("Bell MAX Down: " . $apiResponse["bell_max_down"]);
		$bell_plans_avail = explode(",", trim( $apiResponse["bell_max_down"] ));
		var_dump( $bell_plans);
	}

	$disply_prod .= '<div class="box-radio internet-redio_box internet_plan_selector">';

	foreach ($products as $prod) {
		
		if ( $prod->is_in_stock() == false ) {
			continue;
		}

		$found_match = false;
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
						break;
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
		
		$disply_prod .= "<div class=\"radio_box_wrapper\">";
		
		$prod_id_only = $prod->get_id();
		$prod_id = "Internet-" . $prod->get_id();
		
		$disply_prod .= '<input type="radio" name="InternetPlan" ';
		$disply_prod .= "id=\"$prod_id\" value=\"$prod_id_only\" ";
		
		if( strcmp( $selected_internet_plan, $prod_id_only ) == 0 ) {
			$disply_prod .= ' checked >';
		} else {
			$disply_prod .= '>';
		}

		$disply_prod .= "<label for=\"$prod_id\">";
		$disply_prod .= "<span class=\"internet-sku\" style=\"display:none\">$prod_sku</span>";
		$disply_prod .= $prod->get_description();
		$disply_prod .= '</label>';

		$disply_prod .= "</div>";
	}

	$disply_prod .= "</div>";
	return $disply_prod;
}

function RenderModems() {

	error_log( "In RenderModems ");

	$selected_internet_plan = dg_get_user_meta("selected_internet_plan");
	$selected_phone_plan = dg_get_user_meta("selected_phone_plan");

	error_log("RenderModems: Selected Internet Plan = $selected_internet_plan");
	error_log("RenderModems: Selected Phone Plan = $selected_phone_plan");
	$query = new WC_Product_Query( array(
		    'limit' => -1,
		    'orderby' => 'price',
		    'order' => 'ASC',
		    'category' => "modems",
	));

	$mymodem = "";
	$products = $query->get_products();
	$disply_prod = "";	
	$disply_prod .= '<div class="box-radio modem-redio_box" >';

	foreach( $products as $prod ) {
		
		error_log("RenderModems: Iterate Product ". $prod->get_name() );

		if ($prod->is_in_stock() == false ) {
			continue;
		}

		$found_match = false;
		$skus = explode(",",$prod->get_sku());
		$prod_class = "modemsku-all modemsku-".implode(" modemsku-",$skus);

		$provider = "";
		$found = false;
		$foundownmodem = false;
		foreach( $skus as $s ) {
			$s = trim($s);
			if( strlen($s) == 0 ) {
				continue;
			}
			if( strcmp("ownmodem", $s) == 0) {
				$foundownmodem = true;
			}
			if( strcmp("bell", $s) == 0) {
				$found = true;
				break;
			}
			if( strcmp("cogeco", $s) == 0) {
				$found = true;
				break;
			}
			if( strcmp("rogers", $s) == 0) {
				$found = true;
				break;
			}
			if( strcmp("shaw", $s) == 0) {
				$found = true;
				break;
			}
		}

		if( $found == false ) {
			continue;
		}

		if( $foundownmodem == true && $found == true) {
			$ownmodemid = $prod->get_id();
			$mymodem .= "<div class=\"radio_box_wrapper $prod_class\">";
			$mymodem .= '<p class=\'ex_radio_p  byo\'>
					<input type="radio" name="ModemPlan" id="ihavemymodem' .$ownmodemid . '" value="' . $ownmodemid . '" />
					<label for="ihavemymodem">
						<span class=\'purchase-title\'>I WILL USE MY OWN MODEM</span>
						<span  class="modem_hidden_text" style="font-size:18px;">
							<input type="text" name="BYO_ModemName" id="BYO_ModemName" value="" 
								placeholder="Enter modem name and model" style="width:300px;font-size: 16px;padding: 10px;margin: 10px;border-radius: 6px;">
							<br>
						</span>
					</label> ';
			$mymodem .= "</div>";
			continue;
		}
		
		$prod_id = $prod->get_id();
		$prod_name = $prod->get_name();
		$prod_desc = $prod->get_description();
		$image_id  = $prod->get_image_id();
		$image_url = wp_get_attachment_image_url( $image_id, 'full' );

		if( $prod->get_attribute( 'Security Deposit' ) > 0 ) {

			$security_deposit = $prod->get_attribute( 'Security Deposit' );
			$monthly_fee = $prod->get_price();

			$disply_prod .= "<div class=\"radio_box_wrapper $prod_class\">";
			$disply_prod .= "<input type=\"radio\" name=\"ModemPlan\" id=\"modem_$prod_id\" value=\"$prod_id\">";
			$disply_prod .= "<label for=\"modem_$prod_id\">";
			$disply_prod .= "<span class=\"modem-top-box\">";
			$disply_prod .= "<span class=\"purchase-title\">$prod_name</span>";
			$disply_prod .= "<span class=\"purchase-text\">$prod_desc</span>";
			$disply_prod .= "<img src=\"$image_url\" style=\"max-width:160px;width:100%;margin:10px auto;height:auto;\" />";
			$disply_prod .= "</span>";
			$disply_prod .= "<span class=\"modem-bottom-box\">";
			$disply_prod .= "<span class=\"modem-price\" id=\"mprice\"><span class=\"modem-price-symble\">$</span>$monthly_fee/month</span>";
			$disply_prod .= "<span class=\"modem-bottom-box-text\">$$security_deposit refundable deposit required</span>";
			$disply_prod .= "</span>";
			$disply_prod .= "</label>";
			$disply_prod .= "<p class=\"modem_hidden_text\" ></p>";
			$disply_prod .= "</div>";

		} else {

			$purchase_price = $prod->get_price();

			$disply_prod .= "<div class=\"radio_box_wrapper $prod_class\">";
			$disply_prod .= "<input type=\"radio\" name=\"ModemPlan\" id=\"modem_$prod_id\" value=\"$prod_id\">";
			$disply_prod .= "<label for=\"modem_$prod_id\">";
			$disply_prod .= "<span class=\"modem-top-box\">";
			$disply_prod .= "<span class=\"purchase-title\">$prod_name</span>";
			$disply_prod .= "<span class=\"purchase-text\">$prod_desc</span>";
			$disply_prod .= "<img src=\"$image_url\" style=\"max-width:160px;width:100%;margin:10px auto;height:auto;\" />";
			$disply_prod .= "</span>";
			$disply_prod .= "<span class=\"modem-bottom-box\">";
			$disply_prod .= "<span class=\"modem-price\" id=\"mprice\"><span class=\"modem-price-symble\">$</span>$purchase_price</span>";
			$disply_prod .= "<span class=\"modem-bottom-box-text\">Purchase Modem</span>";
			$disply_prod .= "</span>";
			$disply_prod .= "</label>";
			$disply_prod .= "<p class=\"modem_hidden_text\" ></p>";
			$disply_prod .= "</div>";
		}
	}

	$disply_prod .= "</div>";

	$disply_prod .= $mymodem;

//		'<p class=\'ex_radio_p byo\'>
//	<input type="radio" name="ModemPlan" id="ihavemymodem" value="'. $ownmodemid .'" />
//	<label for="ihavemymodem">
//		<span class=\'purchase-title\'>I WILL USE MY OWN MODEM</span>
//		<span  class="modem_hidden_text" style="font-size:18px;">
//			<input type="text" name="BYO_ModemName" id="BYO_ModemName" value="" placeholder="Enter modem name and model" style="width:300px;font-size: 16px;padding: 10px;margin: 10px;border-radius: 6px;">
//			<br>
//		</span>
//	</label>
	
	$disply_prod .= '</p>
<div class="modem_delivery_wrapper" >
	<hr style="color: #EFEFEF;">
	<h2 style="margin-top: 20px;margin-bottom: 20px;font-family: barlow-regular;">Modem delivery options</h2>
	
	<p class=\'ex_radio_p\'>
		
		<input type="radio" name="modem_delivery" id="modem_delivery1" value="Ship to service address" checked />
		<label for="modem_delivery1">
			<span class=\'purchase-title\'>&nbsp;Ship to service address</span>
			<span  class="modem_hidden_text" style="font-size:18px;">
			</span>
		</label>
	</p>
	
	<p class=\'ex_radio_p\'>
		<input type="radio" name="modem_delivery" id="modem_delivery2" value="Ship to a different address" />
		<label for="modem_delivery2">
			<span class=\'purchase-title\'>&nbsp;Ship to a different address</span>
		</label>
	</p>';

	$shiptodifferentaddress = "";
	$modem_ship_street = $user_data['modem_shipping_street'];
	$modem_ship_unit = $user_data['modem_shipping_unit_no'];
	$modem_ship_city = $user_data['modem_shipping_city'];
	$modem_ship_state = $user_data['modem_shipping_state'];
	$modem_ship_postal = $user_data['modem_shipping_postal_code'];
	$modem_ship_phone = $user_data['modem_shipping_phone'];

	$disply_prod .= '<div class="form-res-internet modem_shipping_fields" >
		<div class="form-group two-fields-group">
			<div class="form-fields">
				<input type="text" class="form-control modem_shippping_field required" 
				name="modem_shipping_street" id="modem_shipping_street" placeholder="Street Address" 
				value="' . $modem_ship_street . '" >
			</div>
			<div class="form-fields">
				<input type="text" class="form-control modem_shippping_field" 
				name="modem_shipping_unit_no" id="modem_shipping_unit_no" placeholder="Unit/Apt Number (optional)" 
				value="' . $modem_ship_unit . '">
			</div>
	    </div>
	    <div class="form-group two-fields-group">
			<div class="form-fields">
				<input type="text" class="form-control modem_shippping_field required" 
				name="modem_shipping_city" id="modem_shipping_city" placeholder="City" 
				value="' . $modem_ship_city . '" >
			</div>
			<div class="form-fields">
				<input type="text" class="form-control modem_shippping_field required" 
				name="modem_shipping_state" id="modem_shipping_state" placeholder="Province" 
				value="' . $modem_ship_state . '">
			</div>
	    </div>
	    <div class="form-group two-fields-group">
			<div class="form-fields">
				<input type="text" class="form-control modem_shippping_field required forceuppercase" 
				name="modem_shipping_postal_code" id="modem_shipping_postal_code" placeholder="Postal Code" 
				value="' . $modem_ship_postal . '">
			</div>
			<div class="form-fields">
				<input type="text" class="form-control modem_shippping_field required" 
				name="modem_shipping_phone" id="modem_shipping_phone" placeholder="Phone" 
				value="' . $modem_ship_phone . '">
			</div>
	    </div>
	<p> NOTE: we cannot ship to PO Boxes. Please provide a physical street address for shipping.</p>
	</div>

</div>';

	return $disply_prod;
}

function RenderFullAddress() {

		$ret = '<div class="form-group two-fields-group">
			<div  class="form-fields">
				<input  class="form-control" mask="000000" name="unitNumber" placeholder="Unit Number (Optional)"
					id="add_unitNumber"  type="text" value="' . GetAddr_UnitNumber() . '" required>
			</div>
			<div  class="form-fields">
				<input  class="form-control" name="buzzerCode" placeholder="Buzzer Code (Optional)"
					id="add_buzzerCode"  type="text" value="" required>
			</div>
		</div>

		<div class="form-group two-fields-group">
			<div class="form-fields">
				<input  class="form-control" name="streetNumber" placeholder="Street Number*"
					id="add_streetNumber" required type="text" value="' . GetAddr_StNum() . '">
			</div>
				
			<div class="form-fields">
				<input  class="form-control" name="streetName" placeholder="Street Name*"
					id="add_streetName" required type="text" value="' . GetAddr_StName() . '">
			</div>

			<div class="form-fields">
				<select  class="form-control" name="streetType" placeholder="Street Type"
					id="add_streetType" required>
					<option value="" selected disabled>Select Street Type...</option>
					' . GetAddr_StType() . '
				</select>
			</div>

			<div class="form-fields">
				<select  class="form-control" name="streetDirection" placeholder="Street Direction (Optional)"
					id="add_streetDirection" required>
					<option value= "" selected disabled>Select Street Direction...</option>
					' . GetAddr_StDir() . '
				</select>
			</div>

		</div>
			
		<div class="form-group two-fields-group">
			<div class="form-fields">
				<input  class="form-control" name="municipalityCity" placeholder="City*"
					id="add_municipalityCity" required type="text" value="' . GetAddr_City() .'">
			</div>

			<div  class="form-fields">
				<select   class="form-control" name="provinceOrState" placeholder="Province*"
					id="add_provinceOrState" required>
						' . GetAddr_Prov() . '
				</select>
			</div>
		
			<div  class="form-fields">
				<input  class="form-control" mask="S0S0S0" name="postalCode" placeholder="Postal Code*"
						id="add_postalCode"  pattern="[A-Za-z][0-9][A-Za-z][0-9][A-Za-z][0-9]" 
						value="' . GetAddr_Postal() . '" required type="text">
			</div>
		</div>
	';
	
	return $ret;
}

function RenderMonthlyBankInfo($user_data) {

	$dis = 'style="display:none;"';
	if( $user_data['monthly_bill_payment_option']=="bank") {
		$dis = "";
	}

	echo '<div class="form-res-internet monthly_payment_options monthly_bank_info"' . $dis . '>';
	echo '<label style="color: black; border-bottom: 1px solid green;">Bank Account Pre-Authorized Payment</label>';
	echo '<br><label for="bank_monthly_billing_account_type" class="">Account Type</label>';
	echo '<input type="radio" class="input-checkbox" name="checkout[monthly_bank][account_type]" id="bank_monthly_billing_account_type_chequing" value="Chequing" />';
	echo '&nbsp;Chequing&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	echo '<input type="radio" class="input-checkbox" name="checkout[monthly_bank][account_type]" id="bank_monthly_billing_account_type_savings" value="Savings" />';
	echo ' &nbsp;Savings';
	echo '	<div class="form-group">';
        echo '		<div class="form-fields">';
        echo '			<input type="text" class="form-control" name="checkout[monthly_bank][billing_first_name]" id="bank_monthly_billing_first_name"';
        echo '				placeholder="Last Name" value="' . $user_data['bank_monthly_billing_first_name'] . '">';
        echo '		</div>';
        echo '		<div class="form-fields">';
        echo '			<input type="text" class="form-control" name="checkout[monthly_bank][billing_last_name]" id="bank_monthly_billing_last_name"';
        echo '				placeholder="Last Name" value="' . $user_data['bank_monthly_billing_last_name'] . '">';
        echo '		</div>';
        echo '		<div class="form-fields">';
        echo '			<input type="text" class="form-control" name="checkout[monthly_bank][financial_institution]" id="bank_monthly_billing_financial_institution"';
        echo '				placeholder="Enter Financial Institution name" value="' . $user_data['bank_monthly_billing_financial_institution'] . '">';
        echo '		</div>';
        echo '		<div class="form-fields">';
        echo '			<input type="text" class="form-control" name="checkout[monthly_bank][transit_number]" id="bank_monthly_billing_transit_number"';
        echo '				placeholder="Enter Transit Number (5 digits)" value="' . $user_data['bank_monthly_billing_transit_number'] . '">';
        echo '		</div>';
        echo '		<div class="form-fields">';
        echo '			<input type="text" class="form-control" name="checkout[monthly_bank][institution_number]" id="bank_monthly_billing_institution_number"';
        echo '				placeholder="Enter Institution Number (3 digits)" value="' . $user_data['bank_monthly_billing_institution_number'] . '">';
        echo '		</div>';
        echo '		<div class="form-fields">';
        echo '			<input type="text" class="form-control" name="checkout[monthly_bank][account_number]" id="bank_monthly_billing_account_number"';
        echo '				placeholder="Enter Account Number (7 digits or more)" value="' . $user_data['bank_monthly_billing_account_number'] . '">';
        echo '		</div>';
        echo '	</div>';
        echo '<button type="button" class="button small confirmation_button confirm_bank_details"> <span aria-hidden="true">Confirm</span></button>';
	echo '</div>';
}

function RenderUpfrontBankTransfer($user_data) {

	$dis = 'style="display:none;"';
	if( $user_data['upfront_bill_payment_option'] == "cc") {
		$dis = "";
	}

	echo '<div class="form-res-internet upfront_payment_options upfront_email-transfer_info"' . $dis .  '>';
	echo '<p class="payment_option_sub_text" ><small>Send us an e-transfer via online banking to etransfers@diallog.com for the total order amount</small></p>';
	echo '<button type="button" class="button small confirmation_button confirm_upfront_email-transfer_details" >';
	echo '<span aria-hidden="true">Order Now</span></button>';
	echo '</div>';

}

function RenderUpfrontCreditCardInfo($user_data) {

	$dis = 'style="display:none;"';
	if( $user_data['upfront_bill_payment_option'] == "cc") {
		$dis = "";
	}

	echo '<div class="form-res-internet upfront_payment_options upfront_cc_info"' . $dis .  '>';
	echo '<label style="color: black; border-bottom: 1px solid green;">Credit Card </label>';
        echo '	<div class="form-group">';
        echo '		<div class="form-fields">';
        echo '			<input type="text" class="form-control" name="checkout[upfront_cc][billing_full_name]" id="cc_upfront_billing_full_name"';
        echo '				placeholder="Cardholder Name" value="' . $user_data['cc_upfront_billing_full_name'] . '">';
        echo '		</div>';
        echo '		<div class="form-fields">';
        echo '			<input type="text" class="form-control" name="checkout[upfront_cc][billing_card_number]" id="cc_upfront_billing_card_number"';
        echo '				placeholder="•••• •••• •••• ••••" value="' . $user_data['cc_upfront_billing_card_number_masked'] . '">';
        echo '		</div>';
        echo '		<div class="form-fields">';
        echo '			<input type="text" class="form-control" name="checkout[upfront_cc][billing_card_expiry]" id="cc_upfront_billing_card_expiry"';
        echo '				placeholder="MM/YY" value="' . $user_data['cc_upfront_billing_card_expiry'] . '">';
        echo '		</div>';
        echo '		<div class="form-fields">';
        echo '			<input type="text" class="form-control" name="checkout[upfront_cc][billing_card_cvv]" id="cc_upfront_billing_card_cvv"';
        echo '				placeholder="CVV2" value="' . $user_data['cc_upfront_billing_card_cvv'] . '">';
        echo '		</div>';
        echo '		<div class="form-fields">';
        echo '			<input type="text" class="form-control" name="checkout[upfront_cc][billing_postcode]" id="cc_upfront_billing_postcode"';
        echo '				placeholder="Billing Postal Code" value="' . $user_data['cc_upfront_billing_postcode'] . '">';
        echo '		</div>';
        echo '	</div>';
	echo '<button type="button" class="button small confirmation_button confirm_upfront_cc_details"  >';
	echo '<span aria-hidden="true">Order Now</span></button>';
	echo '</div>';

}

function RenderMonthlyCreditCardInfo($user_data) {

	$dis = 'style="display:none;"';
	if( $user_data['monthly_bill_payment_option']=="cc") {
		$dis = "";
	}
	echo '<div class="form-res-internet monthly_payment_options monthly_cc_info"' . $dis .  '>';
	echo '<label style="color: black; border-bottom: 1px solid green;">Credit Card Pre-Authorized Payment</label>';
        echo '<p style="font-size:14px;">Note: If your credit card is from outside of Canada, call us at 1-844-DIALLOG(342-5564) to process your order</p>';
        echo '	<div class="form-group">';
        echo '		<div class="form-fields">';
        echo '			<input type="text" class="form-control" name="checkout[monthly_cc][billing_full_name]" id="cc_monthly_billing_full_name"';
        echo '				placeholder="Cardholder Name" value="' . $user_data['cc_monthly_billing_full_name'] . '">';
        echo '		</div>';
        echo '		<div class="form-fields">';
        echo '			<input type="text" class="form-control" name="checkout[monthly_cc][billing_card_number]" id="cc_monthly_billing_card_number"';
        echo '				placeholder="•••• •••• •••• ••••" value="' . $user_data['cc_monthly_billing_card_number_masked'] . '">';
        echo '		</div>';
        echo '		<div class="form-fields">';
        echo '			<input type="text" class="form-control" name="checkout[monthly_cc][billing_card_expiry]" id="cc_monthly_billing_card_expiry"';
        echo '				placeholder="MM/YY" value="' . $user_data['cc_monthly_billing_card_expiry'] . '">';
        echo '		</div>';
        echo '		<div class="form-fields">';
        echo '			<input type="text" class="form-control" name="checkout[monthly_cc][billing_card_cvv]" id="cc_monthly_billing_card_cvv"';
        echo '				placeholder="CVV2" value="' . $user_data['cc_monthly_billing_card_cvv'] . '">';
        echo '		</div>';
        echo '		<div class="form-fields">';
        echo '			<input type="text" class="form-control" name="checkout[monthly_cc][billing_postcode]" id="cc_monthly_billing_postcode"';
        echo '				placeholder="Billing Postal Code" value="' . $user_data['cc_monthly_billing_postcode'] . '">';
        echo '		</div>';
        echo '	</div>';
        echo '	<div class="form-group monthly_cc_service_address_fields">';
	echo '	</div>'; 
	echo '<button type="button" class="button small confirmation_button confirm_cc_details"> <span aria-hidden="true">Confirm</span></button>';
	echo '</div>';

}

function GetAddr_City() {
	$searched_address = dg_get_user_meta ("searched_address");
	if($searched_address != null ) {
		return trim( $searched_address["locality"] );
	}
	return "";
}

function GetAddr_StNum() {

	$searched_address = dg_get_user_meta ("searched_address");
	if($searched_address != null ) {
		return trim( $searched_address["street_number"] );
	}
	return "";
}

function GetAddr_UnitNumber() {
	$searched_address = dg_get_user_meta ("searched_address");
	if($searched_address != null ) {
		return trim( $searched_address["unit_number"] );
	}

	return "";
}

function GetAddr_Postal() {
	
	$searched_address = dg_get_user_meta ("searched_address");
	if($searched_address != null ) {
		return trim( $searched_address["postal_code"] );
	}

	return "";
}

function GetAddr_StName() {

	$searched_address = dg_get_user_meta ("searched_address");
	if($searched_address != null ) {
		return trim( $searched_address["street_name"] );
	}

	return "";

}

function GetAddr_UnitType() {

	$ret = "";
	$selected_sttype = "";

	$searched_address = dg_get_user_meta ("searched_address");
	if( $searched_address != null) {
		$selected_sttype = strtoupper( trim( $searched_address["unit_type"] ) );
	}

	/*if( strlen($selected_sttype) == 0 ) {
		$ret .= "<option selected value=\"\"></option>";
	}else {
		$ret .= "<option value=\"\"></option>";
	}*/

	$street_types = get_unit_types();
	foreach($street_types as $abr => $sttype) {
		if(strcmp($abr, $selected_sttype) == 0 || strcmp($sttype, $selected_sttype) == 0 ) {
			$ret .= "<option selected value=\"$abr\">$sttype</option>";
		} else {
			$ret .= "<option value=\"$abr\">$sttype</option>";
		}
	}

	return $ret;
}

function GetAddr_Prov() {

	$ret = "";
	$selected_sttype = "";
	$searched_address = dg_get_user_meta ("searched_address");
	if( $searched_address != null) {
		$selected_sttype = strtoupper( trim( $searched_address["administrative_area_level_1"] ) );
	}

	$street_types = get_list_provinces();
	foreach($street_types as $abr => $sttype) {
		if(strcmp($abr, $selected_sttype) == 0 || strcmp($sttype, $selected_sttype) == 0 ) {
			$ret .= "<option selected value=\"$abr\">$sttype</option>";
		} else {
			$ret .= "<option value=\"$abr\">$sttype</option>";
		}
	}

	return $ret;
}

function GetAddr_StType() {

	$ret = "";
	$selected_sttype = "";
	$searched_address = dg_get_user_meta ("searched_address");
	if( $searched_address != null) {
		$selected_sttype = strtoupper( trim( $searched_address["street_type"] ) );
	}

	/*if( strlen($selected_sttype) == 0 ) {
		$ret .= "<option selected value=\"\"></option>";
	}else {
		$ret .= "<option value=\"\"></option>";
	}*/

	$street_types = get_street_types();
	foreach($street_types as $abr => $sttype) {
		if(strcmp($abr, $selected_sttype) == 0 || strcmp($sttype, $selected_sttype) == 0 ) {
			$ret .= "<option selected value=\"$abr\">$sttype</option>";
		} else {
			$ret .= "<option value=\"$abr\">$sttype</option>";
		}
		
	}

	return $ret;
}

function GetAddr_StDir() {

	$ret = "";
	$selected_sttype = "";
	$searched_address = dg_get_user_meta ("searched_address");
	if( $searched_address != null) {
		$selected_sttype = strtoupper( trim( $searched_address["street_dir"] ) );
	}

	/*if( strlen($selected_sttype) == 0 ) {
		$ret .= "<option selected value=\"\"></option>";
	}else {
		$ret .= "<option value=\"\"></option>";
	}*/

	$street_types = get_street_directions();
	foreach($street_types as $abr => $sttype) {
		if(strcmp($abr, $selected_sttype) == 0 || strcmp($sttype, $selected_sttype) == 0 ) {
			$ret .= "<option selected value=\"$abr\">$sttype</option>";
		} else {
			$ret .= "<option value=\"$abr\">$sttype</option>";
		}
		
	}

	return $ret;
}

?>

<form name="signup" method="POST" action="#" enctype="application/x-www-form-urlencoded">

	<ul class="signup_steps_nav">
		<li class="active"><a href="#stepaddress"><span>1</span> Customer Details</a></li>
		<li class="disabled"><a href="#stepinternet"><span>2</span> Internet Plan</a></li>
		<li class="disabled"><a href="#stepdates"><span>3</span> Installation</a></li>
		<li class="disabled"><a href="#stepphone"><span>4</span> Phone Plan</a></li>
		<li class="disabled"><a href="#stepmodem"><span>5</span> Modem Options</a></li>
		<li class="disabled"><a href="#stepmonthly"><span>6</span> Review Monthly </a></li>
		<li class="disabled"><a href="#stepupfront"><span>7</span> Review Upfront </a></li>
		<li class="disabled"><a href="#review"><span>8</span> Complete </a></li>
	</ul>
	
	<div class="dg_cart dg_checkout">
		<div class="et_pb_section et_pb_section_1 et_section_regular" style="padding:0;">
		    <div class="et_pb_row et_pb_row_1">
		        <div class="et_pb_column et_pb_column_2_3 et_pb_column_1    et_pb_css_mix_blend_mode_passthrough">
		            <div class="et_pb_module et_pb_code et_pb_code_1">
		            	
		            							
				<!-- Contact Details -->
				<div class="dg_cart_module selected_customer_details active">
					<h3 class="signin-title">Customer Details</h3>


				<div class="dg_cart_module_content">
						<div class="form-res-internet billing">
							<div class="form-group two-fields-group">
								<div class="form-fields">
									<input type="text" class="form-control" name="first_name" id="first_name" placeholder="First Name*" value="<?=$user_data['first_name']?>">
								</div>
								<div class="form-fields">
									<input type="text" class="form-control" name="last_name" id="last_name" placeholder="Last Name*" value="<?=$user_data['last_name']?>">
								</div>
							</div>

							<div class="form-group two-fields-group">
								<div class="form-fields">
									    <input type="tel" class="form-control" name="phone" id="phone" placeholder="Phone number*" value="<?=$user_data['phone']?>">
								</div>
								<div class="form-fields">
									<input type="email" class="form-control" name="email" id="email" placeholder="Email*" value="<?=$user_data['email']?>">
								</div>
							</div>

							<div class="form-group two-fields-group">
                                                                <p style="margin: 20px 0px;">Please enter account no. of the customer who referred you to us:</p>
								<div class="form-fields">
									<input type="text" class="form-control" name="referrer_ecn" id="referrer_ecn" placeholder="Referrer Acct No" value="<?=$user_data['referrer_ecn']?>">
								</div>
                                                        </div>


							<div class="form-res-internet billing">	
								<p style="margin: 20px 0px;">This is where your new internet service will be installed.
								<br>Please provide your Unit Number and Buzzer Code if applicable.</p> 
								<?php echo RenderFullAddress(); ?>
							</div>
						</div>

						<button type="button" class="button small confirmation_button confirm_customer_details_button" >
							<span aria-hidden="true">Confirm</span> </button>
					</div>	
				</div>

				<!-- Your Internet Plan -->
				<div class="dg_cart_module selected_internet_plan ">
					<h3 class="signin-title done">Please confirm your selected Internet plan</h3>
					<button type="button" class="button small confirmation_button confirm_internet_plan" style="display: block; float: none; margin-left: auto; margin-bottom: 30px;"> <span aria-hidden="true">Confirm</span></button>
					<div class="dg_cart_module_content">
						<div class="tabs-panel is-active panel-showinternetplans" id="panel-showinternetplans" style="display: block;">
					<p><br /></p>
							<?php echo RenderInternetPlans(); ?>
						</div>
					<p><br /></p>
					<button type="button" class="button small confirmation_button confirm_internet_plan"> <span aria-hidden="true">Confirm</span></button>
					</div>
				</div>

				<!-- Installation Dates -->
				<div class="dg_cart_module pid_mod">
					<h3 class="signin-title">Preferred Installation Date</h3>
					<div class="dg_cart_module_content">
					 <p>It takes between 4 to 7 days for the service to be installed, excluding Sundays and Holidays. Please indicate your preferred appointment day and time, and our team will schedule your installation accordingly. You will receive email confirmations throughout the onboarding process.</p>
						<p>
						</p>
					

<?php 

echo '<div class="form-res-internet">';
echo '<h3 style="color: #868686; font-family: \'barlow-regular\';font-size:26px;border-bottom: 1px dashed #CCC; margin-bottom: 20px;">1st choice installation time slot</h3>';
echo '<div class="form-group">';
echo '<div class="form-fields">';
	echo "<select id='time1' name=\"preffered_installation_time_1\" class=\"first-available\">";
	echo '<option value="" disabled selected>Please select your first choice time slot</option>';
	echo "<option value=\"Soonest available anytime\">First available</option>";
	echo "<option value=\"Weekdays 8AM-12PM\">Weekdays 8AM-12PM</option>";
	echo "<option value=\"Weekdays 12PM-4PM\">Weekdays 12PM-4PM</option>";
	echo "<option value=\"Weekdays 4PM-8PM\">Weekdays 4PM-8PM</option>";
	echo "<option value=\"Saturday 8AM-12PM\">Saturday 8AM-12PM</option>";
	echo "<option value=\"Saturday 12PM-4PM\">Saturday 12PM-4PM</option>";
	echo "<option value=\"Saturday 4PM-8PM\">Saturday 4PM-8PM</option>";
	echo "</select>";
echo "</div><br>";

echo '<h3 style="color: #868686; font-family: \'barlow-regular\';font-size:26px;border-bottom: 1px dashed #CCC; margin-bottom: 20px;">2nd choice installation time slot</h3>';
echo '<div class="form-fields">';
	echo "<select id='time2' name=\"preffered_installation_time_2\" class=\"first-available\">";
	echo '<option value="" disabled selected>Please select your second choice time slot</option>';
	echo "<option value=\"Soonest available anytime\">First available</option>";
	echo "<option value=\"Weekdays 8AM-12PM\">Weekdays 8AM-12PM</option>";
	echo "<option value=\"Weekdays 12PM-4PM\">Weekdays 12PM-4PM</option>";
	echo "<option value=\"Weekdays 4PM-8PM\">Weekdays 4PM-8PM</option>";
	echo "<option value=\"Saturday 8AM-12PM\">Saturday 8AM-12PM</option>";
	echo "<option value=\"Saturday 12PM-4PM\">Saturday 12PM-4PM</option>";
	echo "<option value=\"Saturday 4PM-8PM\">Saturday 4PM-8PM</option>";
	echo "</select>";
echo "</div><br>";
echo '<h3 style="color: #868686; font-family: \'barlow-regular\';font-size:26px;border-bottom: 1px dashed #CCC; margin-bottom: 20px;">Other order notes and instructions</h3>';
echo '<div class="form-fields">';
	echo '<input type="text" id="install_instructions" placeholder="Specific installation date or other instructions" maxlength="1024"> ';
echo "</div>";
echo "</div></div><br>";

//for( $i=1; $i <= $ins_number_of_days; $i++ ) {
//
//	echo '<div class="form-res-internet">';
//	echo '<div class="form-group two-fields-group">';
//	echo '<div class="form-fields">';
//	echo '<span class="pinsda">' . $ins_day_label[$i];
//	echo "</span> &nbsp;<input type="text" id=\"date$i\"";
//	echo 'class="form-control" name="preffered_installation_date[' . $i . ']" placeholder="MM/DD/YYYY*" value="';
//	echo '${"pref_date_"' . $i . '}">';
//	echo '</div>';
//	echo '<div class="form-fields">';
//	echo "<select id='time$i' name=\"preffered_installation_time[$i]\" class=\"first-available\">";
//	foreach ($ins_time as $key => $val) {
//		$selected = "";
//		if($key==$user_data['preffered_installation_time_'.$i]) {
//			$selected = "selected";	
//		}
//		echo '<option '.$selected.' value="'.$key.'">'.$val.'</option>';
//		echo "</select>";
//		echo "</div>";
//		echo "</div>";
//		echo "<p id='date" . $i . "_error' style=\"color:red;font-size: 12px;text-indent: 43px;margin-bottom:10px;\"></p>";
//		echo "</div>";
//	} 
//}

?>

				<button type="button"  class="button small confirmation_button confirm_dates_button">
					<span aria-hidden="true">Confirm</span>
				</button>
			</div>
			</div> 

			<!-- Phone Selection -->
			<div class="dg_cart_module">
				<h3 class="signin-title">Select a Phone Plan</h3>
				<div class="dg_cart_module_content">
					<?php get_template_part("templates/page","signup-phone"); ?>
				<button type="button" class="button small confirmation_button select_phone_button"> <span aria-hidden="true">Confirm</span> 
				</button>
				<button type="button" data-toggle="modal" class="button small confirmation_button skip_phone_button" 
					style="text-decoration:underline;"> <span aria-hidden="true">I don't need a phone plan</span></button>
				</div>	
			</div>

			<!-- Modem Selection -->
			<div class="dg_cart_module">
				<h3 class="signin-title">Select Modem Option</h3>
				<div class="dg_cart_module_content">
					<?php echo RenderModems();?>
					<button type="button" class="button small confirmation_button select_modem_button">
						<span aria-hidden="true">Confirm</span> 
					</button>
				</div>
			</div>

			<!-- Review Monthly Payment -->
			<div class="dg_cart_module">
				<!--
				<h2 style="margin-top: 20px;margin-bottom: 20px;font-family: barlow-regular;">
					How would you like to pay your recurring monthly ?
				</h2>
				-->
				<div class="dg_cart_module_content">
					<div class="et_pb_column et_pb_column_4_4 et_pb_column_2">
					<h3 style="color: #868686; font-family: 'barlow-regular';font-size:26px;border-bottom: 1px dashed #CCC;
							margin-bottom: 20px;">Monthly service fee payment options</h3>
					<p class='dg_ord_data' style='padding-bottom:0;'>
						<label for="monthly_bill_payment_option_cc">
						<input type='radio' id="monthly_bill_payment_option_cc" class="monthly_bill_payment_option"
							name="checkout[monthly_bill_payment_option]" value="cc" 
							onclick="UpdateViewMonthlyPaymentDetails(0);"
							<?=($user_data['monthly_bill_payment_option']=="cc")?"checked":"";?> />
							Credit Card Pre-Authorized Payment
						</label>
					</p>
					<p class='payment_option_sub_text' >
						<small>Setup automated monthly credit card payments and never worry about missing a bill again
						</small>
					</p>
					<p class='dg_ord_data' style='padding-bottom:0;'>
						<label for="monthly_bill_payment_option_bank">
						<input type='radio' id="monthly_bill_payment_option_bank" class="monthly_bill_payment_option"
							name="checkout[monthly_bill_payment_option]" value="bank"  
							onclick="UpdateViewMonthlyPaymentDetails(1);"
						<?=($user_data['monthly_bill_payment_option']=="bank")?"checked":"";?> />
						Bank Account Pre-Authorized Payment</label>
					</p>
					<p class='payment_option_sub_text'>
						<small>Setup automated monthly bank account payments and never worry about missing a bill again
						</small>
					</p>
					<p class='dg_ord_data' style='padding-bottom:0;'>
						<label for="monthly_bill_payment_option_payafter">
						<input id="monthly_bill_payment_option_payafter" type='radio' class="monthly_bill_payment_option"
							name="checkout[monthly_bill_payment_option]" value="payafter" 
							onclick="UpdateViewMonthlyPaymentDetails(2);"
							<?=($user_data['monthly_bill_payment_option']=="payafter")?"checked":"";?> />
						Pay After/Non Pre-Authorized</label>
					</p>
					<p class='payment_option_sub_text' >
						<small>Pay after you receive your monthly bill. An upfront deposit of $100 will be required</small>
					</p>
					</div>

					<?php RenderMonthlyCreditCardInfo($user_data); ?>
					<?php RenderMonthlyBankInfo($user_data); ?>
					<?php get_template_part("templates/page","checkout-monthly-billing-details-payafter"); ?>
				</div>
			</div>

			<!-- Review Upfront Payment-->
			<div class="dg_cart_module">

		<?PHP render_toc(); ?>


				<div class="et_pb_column et_pb_column_4_4 et_pb_column_2">
				<h3 style="color: #868686; font-family: 'barlow-regular';font-size:26px;
					border-bottom: 1px dashed #CCC;margin-bottom: 20px;">
					Payment options for Due Now amount
				</h3>
				<p class='dg_ord_data' style='padding-bottom:0;' class="upfront_bill_payment_option_cc">
				<label for='upfront_bill_payment_option_cc'>
				<input type='radio' id='upfront_bill_payment_option_cc' 
					class="upfront_bill_payment_option" 
					name="checkout[upfront_bill_payment_option]" value="cc" 
					onclick="UpdateViewUpfrontPaymentDetails(0);"
					<?=($user_data['upfront_bill_payment_option']=="cc")?"checked":"";?> /> 
					Credit Card
				</label>
				</p>
				<p class="payment_option_sub_text" >
					<small>Pay your one-time upfront fees with a credit card</small>
				</p>
				<?php RenderUpfrontCreditCardInfo($user_data); ?>
				<p></p>
					<p>Don't have access to a credit card? Call us at <a href="tel:+18884433876" title="1.888.443.3876">1.888.443.3876</a> or email us at <a href=" mailto:join@diallog.com?subject=I%20need%20help%20signing%20up%20without%20a%20credit%20card" target="_blank" rel="noopener">join@diallog.com</a>.
					</p>
<!-- Eugene commented out to eliminate e-Transfer option and force an email or phone call for customers without credit cards
				<p class='dg_ord_data' style='padding-bottom:0;' class="upfront_bill_payment_option_cc">
				<label for="upfront_bill_payment_option_email-transfer">
				<input id='upfront_bill_payment_option_email-transfer' type='radio' 
					class="upfront_bill_payment_option" 
					name="checkout[upfront_bill_payment_option]" value="email-transfer" 
					onclick="UpdateViewUpfrontPaymentDetails(1);"
					<!--?=($user_data['upfront_bill_payment_option']=="email-transfer")?"checked":"";?>/> 
					Email Money Transfer
				</label>
				</p>
-->
				<!--?php RenderUpfrontBankTransfer($user_data); ?>-->
				</div>
			</div>
		</div>
	</div>

	<!-- .et_pb_column -->
	<div class="et_pb_column et_pb_column_1_3 et_pb_column_2    et_pb_css_mix_blend_mode_passthrough">
		<div class="et_pb_module et_pb_text et_pb_text_0 et_pb_bg_layout_light  et_pb_text_align_left">
			<div class="et_pb_text_inner">
				<div class="create_order_box">
				<h5>Your Order</h5>
				<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
				<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
				<div id="order_review" class="woocommerce-checkout-review-order">
				<?php do_action( 'woocommerce_checkout_order_review' ); ?>
				</div>
				<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
		               </div>
				
		              <input type="hidden" name='dg-checkout' value="1" />
		            <!--  <button type="submit" disabled="" onclick="SubmitSignup();" class="orange-button cart_checkout_button"><span aria-hidden="true">Checkout</span>
		              </button> -->
		            <!-- .et_pb_text -->
			</div>
		</div>
		        </div>
		        <!-- .et_pb_column -->
		
		    </div>
		    <!-- .et_pb_row -->
	</div>
	</div>
</form>




<script>
    jQuery(".woocommerce-monthly-billing-fields__field-wrapper").hide();
    jQuery(".woocommerce-monthly-billing-fields__field-wrapper.cc_details").show();
</script>

