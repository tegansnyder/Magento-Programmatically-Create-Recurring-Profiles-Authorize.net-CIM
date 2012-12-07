<?php #!/usr/bin/php

// This is what we used to create the customer payment profiles intially

require_once BASE_DIR . 'app/Mage.php';
Varien_Profiler::enable();
umask(0);
Mage::app('default'); 



CONST BASE_DIR 		= '/sites/magento/';
CONST SUCCESS_LOG 	= 'log/success.log';
CONST OUTPUT_LOG 	= 'log/output.log';
CONST ERR_LOG		= 'log/errors.log';
CONST CSV_FILE		= 'custs.txt';


$csv = array();
$f = @fopen($csv_file, "r");
$keys = fgetcsv($f);
while (!feof($f)) {
    $csv[] = array_combine($keys, fgetcsv($f));
}


foreach ($csv as $cust) {


	try {

		$email = $cust['email'];
		$customer = Mage::getModel('customer/customer')->setWebsiteId(1)->loadByEmail($email);
		
		
		if(!empty($customer)) {
		
			$address = Mage::getModel('customer/address')->load($customer->getDefaultBilling());
			$customer_id = $customer->getId();
		
			$d['firstname'] = $customer->getFirstname();
			$d['lastname'] = $customer->getLastname();
			$d['address1'] = str_replace(array("\r\n", "\r", "\n"), " ", $address->getData('street'));	
			$d['city'] = $address->getData('city');
			$d['state'] = $address->getData('region');
			$d['zip'] = $address->getData('postcode');
			$d['country'] = $address->getData('country_id');
			$d['cc_type'] = $cust['cc_type'];
			$d['cc_number'] = $cust['cc'];
			$d['cc_exp_month'] = $cust['month'];
			$d['cc_exp_year'] = $cust['year'];
	
			$fh = fopen(BASE_DIR . SUCCESS_LOG, 'a') or die("can't open file");
			fwrite($fh, $email . PHP_EOL);
			fclose($fh);
		
			$fh = fopen(OUTPUT_LOG . SUCCESS_LOG, 'a') or die("can't open file");
			fwrite($fh, print_r($d, ture) . PHP_EOL);
			fclose($fh);
			
			Mage::getModel('authnetcim/payment')->createCustomerPaymentProfileFromRecurly($d, $customer_id);
		
		} else {
		
			$fh = fopen(OUTPUT_LOG . ERR_LOG, 'a') or die("can't open file");
			fwrite($fh, 'not found: ' . $email . PHP_EOL);
			fclose($fh);
		
		}
		
	
	} catch(Exception $e){
	
		$fh = fopen(OUTPUT_LOG . ERR_LOG, 'a') or die("can't open file");
		fwrite($fh, $email . ' ' . $e->getMessage() . PHP_EOL);
		fclose($fh);
	
	}

}

?>