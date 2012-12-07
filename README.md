Creating Recurring Authorize.net CIM Profiles Programmatically in Magento

 Author: Tegan Snyder (BuluBox.com)
  Authorize.Net CIM - Payment model by Paradox Labs (Ryan Hoerr)
=======================================================================================


When I took the job at Bulu Box. My goal was to move us into a move versatile ecommerce platform. For months we had been successfully using Shopify for product sales and Recurly for subscription billing, but as we wanted to add value to our services we needed to be able to control our own code base. That is why we choose to move towards the Magento platform.

The process of Migrating customers from Recurly into Magento recurring profiles using Authroize.net CIM was very daunting. This code was written to take a list of customers and their credit card data (Thank you Recurly) and import them into Magento as recurring profiles.


---------------------------------------------------------------------------------------
 HOW DO I RUN THIS?
---------------------------------------------------------------------------------------

Requirements: 
Authorize.Net CIM extension from Paradox Labs
http://www.magentocommerce.com/magento-connect/authorize-net-cim-payment-module-7207.html#tab:reviews


To run upload the folder structure to you Magento root directory
CHMOD the appropriate folder for log reporting and set the CONST variables in each file for the logging and CSV data source (I have included an example CSV)

Edit the Authorize.NET constructor settings in HandleOrderCreate.php (app/code/local/Bulubox/Ordermaker/Model/HandleOrderCreate.php)


---------------------------------------------------------------------------------------
 After you have purchased it one modification needed.
---------------------------------------------------------------------------------------

	File: app/community/ParadoxLabs/AuthorizeNetCim/Model/Payment.php file
	Locate the: [submitRecurringProfile] function
	
		Right after it checks for a $payment_id
		Before the "Do we need to bill?" comment 
		
			
		Add (code below):
		

		[code]
		
			try {
			
	
				if (Mage::registry('recurly_move')) {
	
					$startDate = Mage::registry('start_date');
		
					$dateFormat = Mage::app()->getLocale()->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
					$localeCode = Mage::app()->getLocale()->getLocaleCode();
					
					if (!Zend_Date::isDate($startDate, $dateFormat, $localeCode)) {
					    Mage::throwException(Mage::helper('payment')->__('Recurring profile start date has invalid format.'));
					}
					
					$utcTime = Mage::app()->getLocale()->utcDate(Mage::app()->getStore(), $startDate, true, $dateFormat)->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);
					
					$profile->setStartDatetime($utcTime)->setImportedStartDatetime($startDate);
	
				}
			
			} catch (Exception $e) {
			
				}
		
		[/code]		


> NEXT--------------------------------------------------------------------------------
	
	In the same file search for:	$adtl
			 Before it sets the:	$adtl = array( 


		Add this:
	
		[code]
		
			if (Mage::registry('recurly_move')) {
				
				$next_cycle = $start;
				
			}
			
		[/code]
	

---------------------------------------------------------------------------------------
 FINALLY TO RUN
---------------------------------------------------------------------------------------		

Open up a bash session in SSH and run:

	php initProcess.php
	