<?php

##
## Example php -q TestPurchase.php store1
##

function VerifyCard($data) {

	$store_id='monca07419';
	$api_token='G5F7TE7rzrM21OVzkCQD';
	
	$type= 'card_verification';
	$cust_id= trim( $data['custid'] ); //'cust id';
	$order_id= trim( $data['orderid'] ); //'ord-'.date("dmy-G:i:s");
	$amount=number_format( $data['amount'], 2); //'1.00';
	$pan=trim( $data['cardno'] ); //'4242424242424242';
	$expiry_date=trim( $data['expdate'] ); //'2011';
	$address = trim( $data["address"]);
	$city = trim( $data["city"] );
	$province = trim( $data["province"] );
	$postal_code = trim( $data["postal_code"] );
	$postal_code = str_replace(' ', '', $postal_code);
	$cvd_value = trim ( $data["cvd"] );
	$crypt='7';
	$dynamic_descriptor='Purchase plan online Diallog.com';
	$status_check = 'false';

	error_log( "Parameters for process payment: $type, $cust_id, $order_id, $amount, $pan, $expiry_date, $crypt, $address, $city, $province, $postal_code ");

	$txnArray=array('type'=>$type,
		'order_id'=>$order_id,
		'cust_id'=>$cust_id,
		'amount'=>$amount,
		'pan'=>$pan,
		'expdate'=>$expiry_date,
		'crypt_type'=>$crypt
		//'dynamic_descriptor'=>$dynamic_descriptor
		//,'wallet_indicator' => '' //Refer to documentation for details
		//,'cm_id' => '8nAK8712sGaAkls56' //set only for usage with Offlinx - Unique max 50 alphanumeric characters transaction id generated by merchant
	);

	/********************** Set Customer Information **********************/
	$avsTemplate = array(
		'avs_zipcode' => $postal_code
	);
	$mpgAvsInfo = new mpgAvsInfo ($avsTemplate);

	$cvdTemplate = array(
		'cvd_indicator' => 1,
		'cvd_value' => $cvd_value
	);
	$mpgCvdInfo = new mpgCvdInfo ($cvdTemplate);

	$mpgTxn = new mpgTransaction($txnArray);

	$mpgTxn->setCvdInfo($mpgCvdInfo);
	$mpgTxn->setAvsInfo($mpgAvsInfo);

	/****************************** Request Object *******************************/

	$mpgRequest = new mpgRequest($mpgTxn);
	$mpgRequest->setProcCountryCode("CA"); //"US" for sending transaction to US environment
	$mpgRequest->setTestMode(false); //false or comment out this line for production transactions

	/***************************** HTTPS Post Object *****************************/
	$mpgHttpPost  =new mpgHttpsPostStatus($store_id,$api_token,$status_check,$mpgRequest);

	/******************************* Response ************************************/

	$mpgResponse=$mpgHttpPost->getMpgResponse();

	error_log("\nCardType = " . $mpgResponse->getCardType());
	error_log("\nTransAmount = " . $mpgResponse->getTransAmount());
	error_log("\nTxnNumber = " . $mpgResponse->getTxnNumber());
	error_log("\nReceiptId = " . $mpgResponse->getReceiptId());
	error_log("\nTransType = " . $mpgResponse->getTransType());
	error_log("\nReferenceNum = " . $mpgResponse->getReferenceNum());
	error_log("\nResponseCode = " . $mpgResponse->getResponseCode());
	error_log("\nISO = " . $mpgResponse->getISO());
	error_log("\nMessage = " . $mpgResponse->getMessage());
	error_log("\nIsVisaDebit = " . $mpgResponse->getIsVisaDebit());
	error_log("\nAuthCode = " . $mpgResponse->getAuthCode());
	error_log("\nComplete = " . $mpgResponse->getComplete());
	error_log("\nTransDate = " . $mpgResponse->getTransDate());
	error_log("\nTransTime = " . $mpgResponse->getTransTime());
	error_log("\nTicket = " . $mpgResponse->getTicket());
	error_log("\nTimedOut = " . $mpgResponse->getTimedOut());
	error_log("\nStatusCode = " . $mpgResponse->getStatusCode());
	error_log("\nStatusMessage = " . $mpgResponse->getStatusMessage());
	error_log("\nHostId = " . $mpgResponse->getHostId());
	error_log("\nIssuerId = " . $mpgResponse->getIssuerId());
	error_log("\nAVS = " .  $mpgResponse->getAvsResultCode());

	return $mpgResponse;
}

function ProcessPayment($data) {

	$store_id='monca07419';
	$api_token='G5F7TE7rzrM21OVzkCQD';
	
	$type= trim( $data['type'] );//'purchase';
	$cust_id= trim( $data['custid'] ); //'cust id';
	$order_id= trim( $data['orderid'] ); //'ord-'.date("dmy-G:i:s");
	$amount=number_format( $data['amount'], 2); //'1.00';
	$pan=trim( $data['cardno'] ); //'4242424242424242';
	$expiry_date=trim( $data['expdate'] ); //'2011';
	$postal_code = trim( $data["postal_code"] );
	$cvd_value = trim ( $data["cvd"] );
	$crypt='7';
	$dynamic_descriptor='Purchase plan online Diallog.com';
	$status_check = 'false';

	error_log( "Parameters for process payment: $type, $cust_id, $order_id, $amount, $pan, $expiry_date, $crypt, $address, $city, $province, $postal_code ");

	$txnArray=array('type'=>$type,
		'order_id'=>$order_id,
		'cust_id'=>$cust_id,
		'amount'=>$amount,
		'pan'=>$pan,
		'expdate'=>$expiry_date,
		'crypt_type'=>$crypt
		//'dynamic_descriptor'=>$dynamic_descriptor
		//,'wallet_indicator' => '' //Refer to documentation for details
		//,'cm_id' => '8nAK8712sGaAkls56' //set only for usage with Offlinx - Unique max 50 alphanumeric characters transaction id generated by merchant
	);

	/********************** Set Customer Information **********************/
	$avsTemplate = array(
		'avs_zipcode' => $postal_code
	);
	$mpgAvsInfo = new mpgAvsInfo ($avsTemplate);

	$cvdTemplate = array(
		'cvd_indicator' => 1,
		'cvd_value' => $cvd_value
	);
	$mpgCvdInfo = new mpgCvdInfo ($cvdTemplate);

	$mpgTxn = new mpgTransaction($txnArray);

	$mpgTxn->setCvdInfo($mpgCvdInfo);
	$mpgTxn->setAvsInfo($mpgAvsInfo);

	/****************************** Request Object *******************************/

	$mpgRequest = new mpgRequest($mpgTxn);
	$mpgRequest->setProcCountryCode("CA"); //"US" for sending transaction to US environment
	$mpgRequest->setTestMode(false); //false or comment out this line for production transactions

	/***************************** HTTPS Post Object *****************************/
	$mpgHttpPost  =new mpgHttpsPostStatus($store_id,$api_token,$status_check,$mpgRequest);

	/******************************* Response ************************************/

	$mpgResponse=$mpgHttpPost->getMpgResponse();

	error_log("\nCardType = " . $mpgResponse->getCardType());
	error_log("\nTransAmount = " . $mpgResponse->getTransAmount());
	error_log("\nTxnNumber = " . $mpgResponse->getTxnNumber());
	error_log("\nReceiptId = " . $mpgResponse->getReceiptId());
	error_log("\nTransType = " . $mpgResponse->getTransType());
	error_log("\nReferenceNum = " . $mpgResponse->getReferenceNum());
	error_log("\nResponseCode = " . $mpgResponse->getResponseCode());
	error_log("\nISO = " . $mpgResponse->getISO());
	error_log("\nMessage = " . $mpgResponse->getMessage());
	error_log("\nIsVisaDebit = " . $mpgResponse->getIsVisaDebit());
	error_log("\nAuthCode = " . $mpgResponse->getAuthCode());
	error_log("\nComplete = " . $mpgResponse->getComplete());
	error_log("\nTransDate = " . $mpgResponse->getTransDate());
	error_log("\nTransTime = " . $mpgResponse->getTransTime());
	error_log("\nTicket = " . $mpgResponse->getTicket());
	error_log("\nTimedOut = " . $mpgResponse->getTimedOut());
	error_log("\nStatusCode = " . $mpgResponse->getStatusCode());
	error_log("\nStatusMessage = " . $mpgResponse->getStatusMessage());
	error_log("\nHostId = " . $mpgResponse->getHostId());
	error_log("\nIssuerId = " . $mpgResponse->getIssuerId());

	return $mpgResponse;
}

