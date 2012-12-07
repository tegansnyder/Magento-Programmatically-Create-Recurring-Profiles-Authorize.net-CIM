<?php #!/usr/bin/env /usr/bin/php

CONST BASE_DIR 		= '/sites/magento/';
CONST SUCCESS_LOG 	= 'log/success.log';
CONST OUTPUT_LOG 	= 'log/output.log';
CONST ERR_LOG		= 'log/errors.log';
CONST CSV_FILE		= 'cust-cards.txt';

// Lets read a CSV of customer data into an associative array

$csv = array();
$f = @fopen($csv_file, "r");
$keys = fgetcsv($f);
while (!feof($f)) {
    $csv[] = array_combine($keys, fgetcsv($f));
}

// Lets read the customer data array

foreach ($csv as $cust) {

	$cc_type = $cust['cc_type'];
	$cc = $cust['cc'];
	$month = $cust['month'];
	$year = $cust['year'];
	$email = $cust['email'];
	
	// Our file has a CST timestamp - I'm removing it
	
	$start_date = str_replace(' CST', '', $cust['bill_ends_date']);
	$start_date = strtotime($start_date);

	try {
	
		exec("php " . BASE_DIR . "worker.php \"$email\" \"$cc\" \"$cc_type\" \"$month\" \"$year\" \"$start_date\"");
	
	} catch(Exception $e){
	
		// If we encounter an error write the email to the log
	
		$fh = fopen(BASE_DIR . ERR_LOG, 'a') or die("can't open file");
		fwrite($fh, $email . ' ' . $e->getMessage() . PHP_EOL);
		fclose($fh);
	
	}

}
?>