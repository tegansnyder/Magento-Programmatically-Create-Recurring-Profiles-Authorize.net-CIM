<?php #!/usr/bin/env /usr/bin/php

CONST BASE_DIR 		= '/sites/magento/';
CONST SUCCESS_LOG 	= 'log/success.log';
CONST OUTPUT_LOG 	= 'log/output.log';
CONST ERR_LOG		= 'log/errors.log';
CONST CSV_FILE		= 'cust-cards.txt';

// Check for arguments

if (!isset($argv[1],$argv[2],$argv[3],$argv[4],$argv[5],$argv[6])) {
	exit();
}

// Lets grab the arguments from the call

$email = $argv[1];
$cc = $argv[2];
$cc_type = $argv[3];
$month = $argv[4];
$year = $argv[5];
$start_date = date("n/d/y g:i a", $argv[6]);


// Load Magento core

require_once BASE_DIR . 'app/Mage.php';
Varien_Profiler::enable();
umask(0);
Mage::app('default'); 


try {

	// Get customer details by email address

	$customer = Mage::getModel('customer/customer')->setWebsiteId(1)->loadByEmail($email);
		
	if(!empty($customer)) {
	
	
		// Setup some global vars that we can pass around Magento (dirty hack)
	
		Mage::register('recurly_move', true);
		Mage::register('start_date', $start_date);
		Mage::register('cc_type', $cc_type);
		Mage::register('cc_number', $cc);
		Mage::register('cc_exp_month', $month);
		Mage::register('cc_exp_year', $year);
		Mage::register('cust_email', $email);
		
		
		// Init the order object
		
		$flatOrderDataObject = new Varien_Object();
		$flatOrderDataObject->setSku('BULU_MONTHLY');
		
		// setup the order via the Ordermaker extension
		// code for this is in app/code/local/Bulubox/Ordermaker/Model/HandleOrderCreate.php
		
		$order = new Bulubox_Ordermaker_Model_HandleOrderCreate();
		
		$isOrder = $order->setOrderInfo($flatOrderDataObject, $customer);
		
		
		if ($isOrder) {
		
			$order->create();
		
			$fh = fopen(BASE_DIR . SUCCESS_LOG, 'a') or die("can't open file");
			fwrite($fh, $email . PHP_EOL);
			fclose($fh);
		
		}

	
	} else {
	
		$fh = fopen(BASE_DIR . ERR_LOG, 'a') or die("can't open file");
		fwrite($fh, $email . ' does not exist' . PHP_EOL);
		fclose($fh);
	
	}
	

} catch(Exception $e){

	$fh = fopen(BASE_DIR . ERR_LOG, 'a') or die("can't open file");
	fwrite($fh, $email . ' ' . $e->getMessage() . PHP_EOL);
	fclose($fh);

}
?>