<?PHP

function SpecialChar($string) {
	$string = str_replace('À', 'A', $string);
	$string = str_replace('Á', 'A', $string);
	$string = str_replace('Â', 'A', $string);
	$string = str_replace('Ä', 'A', $string);
	$string = str_replace('Å', 'A', $string);
	$string = str_replace('Ç', 'C', $string);
	$string = str_replace('È', 'E', $string);
	$string = str_replace('É', 'E', $string);
	$string = str_replace('Ê', 'E', $string);
	$string = str_replace('Ë', 'E', $string);
	$string = str_replace('Ò', 'O', $string);
	$string = str_replace('Ó', 'O', $string);
	$string = str_replace('Ô', 'O', $string);
	$string = str_replace('Ô', 'O', $string);
	$string = str_replace('Õ', 'O', $string);
	$string = str_replace('Ö', 'O', $string);
	$string = str_replace('è', 'e', $string);
	$string = str_replace('é', 'e', $string);
	$string = str_replace('ê', 'e', $string);
	$string = str_replace('ë', 'e', $string);
	$string = str_replace('à', 'a', $string);
	$string = str_replace('á', 'a', $string);
	$string = str_replace('â', 'a', $string);
	$string = str_replace('ã', 'a', $string);
	$string = str_replace('ä', 'a', $string);
	$string = str_replace('å', 'a', $string);
	return $string;
}

function QueryDB( $data ) {
	$url = "https://38.88.6.91/www/Query.php";

	$data = "json=$data\n";

	// Initialize session and set URL.
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);

	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_VERBOSE, false);
 	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLINFO_HEADER_OUT, true);

	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Content-Length: ' . strlen($data))
	);

	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

	// Set so curl_exec returns the result instead of outputting it.
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 
	// Get the response and close the channel.
	$response = curl_exec($ch);
	curl_close($ch);
 
	return $response;
}

function get_list_provinces() {
	
	$provinces_list = array(
		"AB" => "Alberta",
		"BC" => "British Columbia", 
		"MB" => "Manitoba",
		"NB" => "New Brunswick", 
		"NL" => "Newfoundland and Labrador",
		"NS" => "Nova Scotia", 
		"ON" => "Ontario",
		"PE" => "Prince Edward Island",
		"QC" => "Quebec",
		"SK" => "Saskatchawan",
		"NT" => "Northwest Territories",
		"NU" => "Nunavut", 
		"YT" => "Yukon"
	);

	return $provinces_list;
}

function get_street_types () {
	
	$street_types = array(	
		"ALLÉE" => "ALLÉE", 
		"AV" => "Avenue",
		"AVE" => "AVE",
		"BD" => "Boulevard",
		"BL" => "Boulevard", 
		"CARRÉ" => "CAR",  
		"CERCLE" => "CERCLE",
		"CH" => "Chemin",
		"CIR" => "Circle",
		"CÔTE" => "CÔTE",
		"COUR" => "COUR",
		"CR" => "Cresent",
		"CR" => "Crescent",
		"CRT" => "Court",
		"DR" => "Drive",
		"GATE" => "GATE",
		"GDNS" => "Gardens",
		"GRV" => "Grove",
		"HWY" => "Highway",
		"LIGNE" => "LIGNE",
		"LINE" => "LINE",
		"LN" => "LANE",
		"MTÉE" => "Montée",
		"PARK" => "Park",
		"PKWY" => "Parkway",
		"PL" => "Place",
		"PRIV" => "Private",
		"RANGÉE" => "RANGÉE",
		"RD" => "Road",
		"RDWY" => "Roadway",
		"RG" => "Range",
		"ROW" => "ROW",
		"RR" => "RR",
		"RTE" => "Route",
		"RUE" => "Rue",
		"RUELLE" => "RLE",
		"SD" => "SD",
		"SDRD" => "SDRD",
		"SNTIER" => "SNTIER",
		"SQ" => "Square",
		"ST" => "Street",
		"TERR" => "Terrace",
		"TRL" => "Trail",
		"V" => "View", 
		"WAY" => "Way"
	);
	
	return $street_types; 
}

function get_unit_types () {

	$unit_types = array(
		"APT" => "Apartment",
		"BSMT" => "Basement",	
		"BSMTE" => "Basement East",
		"BSMTW" => "Basement West",
		"MAIN" => "Main",
		"LWR" => "Lower",
		"U" => "Unit", 
		"UPR" => "Upper"
	);
	
	return $unit_types; 
}

function get_street_directions () {
	
	$street_directions = array(	
		
		"E" => "East", 
		"N" => "North",
		"NE" => "Northeast",
		"NW" => "Northwest",
		"S" => "South", 
		"SE" => "Southeast",  
		"SW" => "Southwest",
		"W" => "West",
		"O" => "Ouest",
		"NO" => "Nord-Ouest",
		"SO" => "Sud-Ouest"
		
	);
	
	return $street_directions; 
}

function parse_street_components($route) {
	
	$street_types = get_street_types();
	$street_dir = get_street_directions();
	$routes_comp = explode(" ",$route);
	$street_components = array();
	
	foreach ($street_types as $abr => $sttype) {
		
		$fk=FALSE;
		$fk2=FALSE;
		
		$fk = array_search($sttype,$routes_comp);
		if ( $fk!==FALSE ){
			unset($routes_comp[$fk]);
			$street_components['street_type'] = $abr; 
		}
		
		$fk2 = array_search($abr,$routes_comp);
		if ( $fk2!==FALSE ){
			unset($routes_comp[$fk2]);
			$street_components['street_type'] = $abr; 
		}
	}
	
	foreach ($street_dir as $dabr => $dir) {
		
		$fk=FALSE;
		$fk2=FALSE;
		
		$fk = array_search($dir,$routes_comp);
		if ( $fk!==FALSE ){
			unset($routes_comp[$fk]);
			$street_components['street_dir'] = $dabr; 
		}
		
		$fk2 = array_search($dabr,$routes_comp);
		if ( $fk2!==FALSE ){
			unset($routes_comp[$fk2]);
			$street_components['street_dir'] = $dabr; 
		}
	}
	
	$street_components['street_name'] = implode(" ",$routes_comp);
	
	return $street_components;
	
}

function find_address_availability_ex() {

		$ip = $_SERVER['REMOTE_ADDR'];
		$user = $_POST['user_data']['email'];
	
		///error_log( "Error in $ip, $user " );
		//TODO RATE LIMIT
	
		$retries = 0;
	
		$fullAddress = $_POST['streetAddress'];
		$unitType = $_POST['unitType'];
		$unitNumber = $_POST['unitNumber'];
		$searched_address = $_POST['searched_address'];

		// var_dump the returned info 
		 ob_start(); 
		 var_dump( $searched_address); 
		$ob = ob_get_clean(); 
		error_log( "In find_address_availability_ex: fulladdress: $fullAddress, $unitType , $unitNumber, $ob " );

		$searched_address['unit_number'] = $unitNumber;
		$searched_address['unit_type'] = $unitType;

		if ($searched_address['manual_search']==0) {
		
			$street_components = parse_street_components($searched_address['route']); 	
			$searched_address['street_name'] = $street_components['street_name'];
			$searched_address['street_type'] = $street_components['street_type'];
			$searched_address['street_dir'] = $street_components['street_dir'];
	
		} elseif ($searched_address['manual_search']==1) {
			
		}
		
		$detailed_address['magic'] = "42kmn4oigt20201212";
		$detailed_address['cmd'] = "query";

		/* When finding availability do not search by unit number */
		/*
		if( $unitType != null ) {
			$detailed_address['utype'] = strtoupper( $unitType );
		}
		if( $unitNumber != null ) {
			$detailed_address['unit'] = strtoupper( $unitNumber );
		} else {
			$detailed_address['unit'] = "";
		}
		*/

		$detailed_address['stnum'] = strtoupper( trim( $searched_address['street_number'] ) );
		$detailed_address['stname'] = SpecialChar( strtoupper( trim( $searched_address['street_name'] ) ) );
		if( $searched_address['street_type'] != null ) {
			$detailed_address['sttype'] = SpecialChar( strtoupper( trim( $searched_address['street_type'] ) ) );
		}
		if( $street_components['street_dir'] != null ) {
			$detailed_address['stdir'] = SpecialChar( strtoupper( trim( $searched_address['street_dir'] ) ) );
		}
		
		$detailed_address['city'] = SpecialChar( strtoupper( trim( $searched_address['locality'] ) ) );
		$detailed_address['postal'] = strtoupper( str_replace(" ","",$searched_address['postal_code']) );
		$detailed_address['prov'] = SpecialChar( strtoupper( trim( $searched_address['administrative_area_level_1'] ) ) );
			
		$data = json_encode($detailed_address);
	
	tryagain:
	
		$res = QueryDB($data);
	
		error_log( "Sent request for address checking $data" );
	
		$shaw_avail = false;
		$bell_avail = false;
		$rogers_avail = false;
		$cogeco_avail = false;
		$telus_avail = false;
	
		if( $res != null ) {
			$res = trim($res);
	
			//error_log( "Got Response for address checking $res" );
	
			$options = json_decode($res);
	
			if( strcmp($options->status, "success" ) == 0  && strcmp( $options->msg, "Entry Added to DB") == 0 ) {
				// this is a new entry
				// we need to request again after giving sometime to the server to find the availability.
				if($retries > 3) { // avoid infinite loop
					$apiResponse['error'] = true;
					$apiResponse['errorType'] = "ADDRESS_NOT_FOUND";
					goto finish;
				}
				$retries = $retries + 1;
				sleep(1);
				
				goto tryagain;
			}
	
			if(strcmp( $options->status, "Entry Exists" ) == 0 ) {
	
				// initalize info
				$apiResponse['error'] = false;
				$apiResponse['errorType'] = "";
				$apiResponse['success'] = true;
				$apiResponse['shaw'] = false;
				$apiResponse['bell'] = false;
				$apiResponse['cogeco'] = false;
				$apiResponse['rogers'] = false;
				$apiResponse['telus'] = false;
				$apiResponse['address'] = "";
	
				for( $i=0; $i< count($options->values) ; $i++) {
					$v = $options->values[$i];
					
					$apiResponse['address'] = "$v->stnum $v->stname $v->sttype $v->stdir $v->city $v->prov $v->postal";
					if( strcmp( $v->bell, "Available") == 0 ) {
						$apiResponse['bell'] = true;
						$apiResponse['bell_max_down'] = $v->bell_max_down;
					}
	
					if( strcmp( $v->telus, "Available") == 0 ) {
						$apiResponse['telus'] = true;
						$apiResponse['telus_services'] = $v->telus_services;
					}

					if( strcmp( $v->shaw, "Available") == 0 ) {
						$apiResponse['shaw'] = true;
					}
	
					if( strcmp( $v->cogeco, "Available") == 0 ) {
						$apiResponse['cogeco'] = true;
					}
	
					if( strcmp( $v->rogers, "Available") == 0 ) {
						$apiResponse['rogers'] = true;
					}
				}
				
			} else {
	
				$apiResponse['error'] = true;
				$apiResponse['errorType'] = "ADDRESS_NOT_FOUND";
	
			}
		}
	
	finish:
		$full_name = explode(" ",$_POST['user_data']['full_name']);
		$email = $_POST['user_data']['email'];
		$lead_status = $_POST['user_data']['lead_status'];
		$onboarding_stage = $_POST['user_data']['onboarding_stage'];	
		
		dg_set_user_meta ("searched_address", $searched_address);
		dg_set_user_meta ("searched_street_address",$fullAddress);
		dg_set_user_meta ("street_address",$street_address);
		dg_set_user_meta ("email",$email);
		dg_set_user_meta ("first_name",isset($full_name[0]) ? $full_name[0] : "" );
		dg_set_user_meta ("last_name",isset($full_name[1]) ? $full_name[1] : "" );
		dg_set_user_meta ("lead_status",$lead_status);
		dg_set_user_meta ("onboarding_stage",$onboarding_stage);
		dg_set_user_meta ("_api_response",$apiResponse);
		
		return $apiResponse;

}


?>
