<?php

/* Hupspot API Functions */

function hubspot_api_contact_properties ( ) {
	
	return array(
		
		"source" => "source",
		"first_name" => "firstname",
		"last_name" => "lastname",
		"phone" => "phone",
		"email"		=> 'email',
		"street_address" => "address",
		"unit_number" => "unit_number",
		"building_access_instructions" => "buzzer_code",
		"city" => "city",
		"state" => "province",
		"postcode" => "zip",
		"monthly_bill_payment_option" => "payment_method",
		"selected_internet_plan" => "package_selected",
		"selected_modem_plan" => "modem_selected",
		"selected_phone_plan" => "phone_selected",
		"BYO_ModemName" => "byo_modem_model",
		"install_timeslot_1" => "install_timeslot_1",
		"install_timeslot_2" => "install_timeslot_2",
		"install_timeslot_3" => "install_timeslot_3",
		"available_speeds" => "available_speeds",
		"lead_status" => "status",
		"how_did_you_hear_about_us" => "coupon_code",
		"referrer_name" => "referring_ecn",
		"alternate_modem_delivery_address" => "alternate_modem_delivery_address",
		"lifecyclestage" => "lifecyclestage",
		"hubspot_owner_id" => "hubspot_owner_id",
		"order_step" => "order_step",

		
		
	);
	
	
	
	/* 
	"date_created" => "hs_lifecyclestage_lead_date"
		
	=> ecn_number
		 => package
		 => 
		 => 
		 => 
		 
		 => coupon_code
		 */
	
	
	
}


function hubspot_set_user_data () {
	
	
	$data = dg_get_current_user_data();
	
	
	
	$data['hubspot_owner_id'] = 33417648; //Diallog
	$data['source'] = "Website availability checker"; 
	
	
	if (empty($data['first_name'])) {
		
		$data['last_name'] = "NONAME";
		
	} 
	
	if ($data['onboarding_stage']=="address_search" && !empty($data['lead_status'])) {
		
		$data['lead_status'] = $data['lead_status'];
		$data['lifecyclestage'] = "lead"; 
			
	} elseif (empty($data['available_plans']) || $data['available_plans']=="PLANS_NOT_AVAILABLE" || $data['available_plans']=="ADDRESS_NOT_FOUND" ) {
		
		$data['lead_status'] = "No service or Speeds too low";
		$data['available_speeds'] = "No Service";
		$data['lifecyclestage'] = "lead";
		
	} else {
		
		$data['lead_status'] = "Active lead";
		$data['lifecyclestage'] = "lead";
		
		$available_speeds = array("GASR05010N"=>"Ultimate 50Mbps","GASR02510N"=>"Plus 25Mbps","GASR01501N"=>"Starter 15Mbps","GASR01510N"=>"Starter 15Mbps","GASR006008N"=>"Basic 6Mbps");
		
		$available_plans = explode(",",$data['available_plans']);
	
		if (count($available_plans)) {
			
			foreach($available_speeds as $key=>$pl) {
			
				if (in_array($key,$available_plans)) {
					
					$data['available_speeds'] = $pl;
					break;
				}
			}
		}		
	}
	
	
	$state = array("ON"=>"ON - Ontario","QC"=>"QC - Quebec");
	
	$hdyhau = array("Friend or family"=>"Referral","Compare My Rates"=>"comparemyrates","Planhub"=>"planhub","Other"=>"Other, see notes","Prefer not to say"=>"");
	
	$monthly_bpo = array("cc"=>"Credit Card PAP","bank"=>"Bank Account PAP","payafter"=>'$100 Deposit');
	
	
	
	
	$package_selected = array(2622=>"Ultimate 50Mbps",2624=>"Plus 25Mbps",2625=>"Starter 15Mbps",3499=>"Starter 15Mbps",2626=>"Basic 6Mbps");
	
	$modem_selected = array(2631=>"Lease to own SmartRG 516ac",887=>"Purchase SmartRG 516ac",2632=>"Use my own DSL modem *CAUTION - ADMIN ACCESS REQUIRED*",3259=>"Lease to own SmartRG 616ac *VOIP ONLY*" ,3260=>"Purchase SmartRG 616ac *VOIP ONLY*");
	
	$phone_selected = array(0=>"Do not need VoIP home phone",2633=>'Local calling for $12.95/mo',304=>'Canada calling for $14.95/mo',305=>'Canada and USA calling for $19.95/mo');
	
	
	$data['selected_internet_plan'] = $package_selected[intval($data['selected_internet_plan'])];
	$data['selected_modem_plan'] = $modem_selected[intval($data['selected_modem_plan'])];
	$data['selected_phone_plan'] = $phone_selected[intval($data['selected_phone_plan'])];
	 
	
	
	$data['install_timeslot_1'] = $data['preffered_installation_date_1']." ".$data['preffered_installation_time_1'];
	$data['install_timeslot_2'] = $data['preffered_installation_date_2']." ".$data['preffered_installation_time_2'];
	$data['install_timeslot_3'] = $data['preffered_installation_date_3']." ".$data['preffered_installation_time_3'];
	
	$data['city'] = $data['searched_address']['municipalityCity'];
	
	$data['state'] = array_key_exists($data['searched_address']['provinceOrState'],$state) ? $state[$data['searched_address']['provinceOrState']] : $data['searched_address']['provinceOrState']; 
	
	$data['postcode'] = $data['searched_address']['postalCode'];
	
	
	$data['how_did_you_hear_about_us'] = array_key_exists($data['how_did_you_hear_about_us'],$hdyhau) ? $hdyhau[$data['how_did_you_hear_about_us']] : $data['how_did_you_hear_about_us']; 
	
	$data['monthly_bill_payment_option'] = array_key_exists($data['monthly_bill_payment_option'],$monthly_bpo) ? $monthly_bpo[$data['monthly_bill_payment_option']] : $data['monthly_bill_payment_option']; 
	
	
	
	
	
	hubspot_api_update_contact_by_email($data,$data['email']);
	
	
}


function hubspot_api_update_contact_by_email ( $data , $email ) {

	return;
	
	
	$url = HUPSPOT_CONTACT_API_ENDPOINT."createOrUpdate/email/".$email."/?hapikey=".HUBSPOT_API_KEY;
	
	
	//$data_array['properties'][] = array("property"=>"lifecyclestage","value"=>"lead");
	//$data_array['properties'][] = array("property"=>"registered_at","value"=>"Website");  
	
	foreach ($data as $key=>$value) {
		if ($ky = hubspot_api_contact_properties()[$key]) {
			$data_array['properties'][] = array("property"=>$ky,"value"=>$value);	
		}
	}
	
	dg_set_user_meta("hubspot_api_request",$data_array);
	
	$payload = json_encode($data_array);
	
	$res = wp_remote_post( $url, array(
		'method' => 'POST',
		'timeout' => 100,
		'redirection' => 5,
		'httpversion' => '1.0',
		'blocking' => true,
		/*'headers' => array(),*/
		'body' => $payload,
		'data_format' => 'body'
		
	    )
	);

	if ( is_wp_error( $res ) ) {
		
		$response['error'] = true;
		$response['msg'] = $res->get_error_message();
	
	} elseif ($res['response']['code']==200) {
	
		$response['success'] = true;
		$response['body'] = $res['body'];	
		
	} else {
			
		$response['error'] = true;
		$response['body'] = $res['body'];	
		
	}
	
	dg_set_user_meta("hubspot_api_response",$response);
	
	
	return $response;
	
}
