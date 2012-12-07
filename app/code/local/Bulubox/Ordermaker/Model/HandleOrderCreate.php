<?php

// Lets load the AuthorizeNetCim class.
// This is part of the extension we are using for CIM by
// Paradox Labs, Inc.
// http://www.paradoxlabs.com
// http://www.magentocommerce.com/magento-connect/authorize-net-cim-payment-module-7207.html#tab:reviews


require_once('app/code/community/ParadoxLabs/AuthorizeNetCim/Model/AuthnetCIM.class.php');


class Bulubox_Observer_Model_HandleOrderCreate extends Mage_Core_Model_Abstract
{
    private $_storeId = '1';
    private $_groupId = '1';
    private $_sendConfirmation = '0';
    
    private $orderData = array();
    private $_product;
    
    private $_sourceCustomer;
    private $_sourceOrder;
    
    protected $cim;
 
    
    public function __construct() {
    
    	// Instantiate CIM with your credentials from Authorize.net
    	// loginKey, transKey, operatingMode [prod => 1, dev => 0], validationMode
    
    	$this->cim = new AuthnetCIM('9hv5eK4cRUB','8p76wD5utWbLt38a',0,'liveMode');

    }
    
    
    public function setOrderInfo(Varien_Object $sourceOrder, Mage_Customer_Model_Customer $sourceCustomer)
    {
        $this->_sourceOrder    = $sourceOrder;
        $this->_sourceCustomer = $sourceCustomer;
        
        
        // You can extract/refactor this if you have more than one product, etc.
        $this->_product = Mage::getModel('catalog/product')->getCollection()->addAttributeToFilter('sku', $this->_sourceOrder->getSku())->addAttributeToSelect('*')->getFirstItem();
        
        
        //Load full product data to product object
        $this->_product->load($this->_product->getId());
        
        $billingAddress = Mage::getModel('customer/address')->load($this->_sourceCustomer->getDefaultBilling())->getData();
        $shippingAddress = Mage::getModel('customer/address')->load($this->_sourceCustomer->getDefaultShipping())->getData();
    
    
    
		$cust_email = Mage::registry('cust_email');
    	
    	
    	// lets grab the profile_id from the customer attributes in magento
    	
        $profile_id = $this->_sourceCustomer->getAuthnetcimProfileId();

		if( !empty($profile_id) ) {
        
	        $this->cim->setParameter( 'customerProfileId', $profile_id );
			$this->cim->getCustomerProfile();
			
			
	        $info = array();
	        if( count($this->cim->raw->profile->paymentProfiles) ) {
	        	foreach( $this->cim->raw->profile->paymentProfiles as $payment ) {
	        		$a = new Varien_Object();
	        		$a->setPaymentId( $payment->customerPaymentProfileId );
	        		$a->setCardNumber( $payment->payment->creditCard->cardNumber );
	        		$info[] = $a;
		
	        	}
	        }
	        
	        
	        $cc_type = Mage::registry('cc_type');
	        $cc_number = Mage::registry('cc_number');
	        $cc_month = Mage::registry('cc_exp_month');
	        $cc_year = Mage::registry('cc_exp_year');
	        
	
	        $this->orderData = array(
	            'session' => array(
	                'customer_id' => $this->_sourceCustomer->getId(),
	                'store_id' => $this->_storeId
	            ),
	            'payment' => array(
	                'method' => 'authnetcim',
	                'payment_id' =>  $profile_id,
	                'cc_type' => $cc_type,
	                'cc_number' => $cc_number,
	                'cc_exp_month' => $cc_month,
	                'cc_exp_year' => $cc_year
	            ),
	            'add_products' => array(
	                $this->_product->getId() => array(
	                    'qty' => 1
	                )
	            ),
	            'order' => array(
	                'currency' => 'USD',
	                'account' => array(
	                    'group_id' => $this->_groupId,
	                    'email' => $this->_sourceCustomer->getEmail()
	                ),
	                'billing_address' => array(
	                    'customer_address_id' => $this->_sourceCustomer->getDefaultBilling(),
	                    'prefix' => '',
	                    'firstname' => $this->_sourceCustomer->getFirstname(),
	                    'middlename' => '',
	                    'lastname' => $this->_sourceCustomer->getLastname(),
	                    'suffix' => '',
	                    'company' => '',
	                    'street' => array($billingAddress['street'], ''),
	                    'city' => $billingAddress['city'],
	                    'country_id' => $billingAddress['country_id'],
	                    'region' => $billingAddress['region'],
	                    'region_id' => $billingAddress['region_id'],
	                    'postcode' => $billingAddress['postcode'],
	                    'telephone' => '',
	                    'fax' => ''
	                ),
	//                'shipping_address' => array(
	//                    'customer_address_id' => $this->_sourceCustomer->getDefaultShipping(),
	//                    'prefix' => '',
	//                    'firstname' => $this->_sourceCustomer->getFirstname(),
	//                    'middlename' => '',
	//                    'lastname' => $this->_sourceCustomer->getLastname(),
	//                    'suffix' => '',
	//                    'company' => '',
	//                    'street' => array($shippingAddress['street'],''),
	//                    'city' => $shippingAddress['city'],
	//                    'country_id' => $shippingAddress['country_id'],
	//                    'region' => $shippingAddress['region'],
	//                    'region_id' => $shippingAddress['region_id'],
	//                    'postcode' => $shippingAddress['postcode'],
	//                    'telephone' => $shippingAddress['telephone'],
	//                    'fax' => ''
	//                ),
	                'shipping_method' => 'flatrate_flatrate',
	                'comment' => array(
	                    'customer_note' => 'This order has been programmatically created via import script.'
	                ),
	                'send_confirmation' => $this->_sendConfirmation
	            )
	        );
	        
	        
	        return true;
    
	                
		} else {
		
			$fh = fopen('/log/errors.log', 'a') or die("can't open file");
			fwrite($fh, 'profileId not found ' . $cust_email . PHP_EOL);
			fclose($fh);
			
			return false;
		
		}
 
        
    }
    
    /**
     * Retrieve order create model
     *
     * @return  Mage_Adminhtml_Model_Sales_Order_Create
     */
    protected function _getOrderCreateModel()
    {
        return Mage::getSingleton('adminhtml/sales_order_create');
    }
    
    /**
     * Retrieve session object
     *
     * @return Mage_Adminhtml_Model_Session_Quote
     */
    protected function _getSession()
    {
        return Mage::getSingleton('adminhtml/session_quote');
    }
    
    /**
     * Initialize order creation session data
     *
     * @param array $data
     * @return Mage_Adminhtml_Sales_Order_CreateController
     */
    protected function _initSession($data)
    {
        /* Get/identify customer */
        if (!empty($data['customer_id'])) {
            $this->_getSession()->setCustomerId((int) $data['customer_id']);
        }
        
        /* Get/identify store */
        if (!empty($data['store_id'])) {
            $this->_getSession()->setStoreId((int) $data['store_id']);
        }
        
        return $this;
    }
    
    /**
     * Creates order
     */
    public function create()
    {
        $orderData = $this->orderData;
        
/*
        echo '<pre>';
        print_r($this->orderData);
        echo '</pre>';
        
*/
        
        if (!empty($orderData)) {
            $this->_initSession($orderData['session']);
            
            try {
                
                $this->_processQuote($orderData);

                if (!empty($orderData['payment'])) {
                    $this->_getOrderCreateModel()->setPaymentData($orderData['payment']);
                    $this->_getOrderCreateModel()->getQuote()->getPayment()->addData($orderData['payment']);
                    
                }
                
                $item = $this->_getOrderCreateModel()->getQuote()->getItemByProduct($this->_product);
                
                Mage::app()->getStore()->setConfig(Mage_Sales_Model_Order::XML_PATH_EMAIL_ENABLED, "0");
                
                $_order = $this->_getOrderCreateModel()
                    ->setIsValidate(true)
                    ->importPostData($orderData['order'])
                    ->setSendConfirmation(false) // email triggers non-object error
                    ->createOrder();
                    
                
                $this->_getSession()->clear();
                
                Mage::unregister('rule_data');
                
                
                Mage::unregister('recurly_move');
                Mage::unregister('start_date');
                Mage::unregister('cc_type');
                Mage::unregister('cc_number');
                Mage::unregister('cc_exp_month');
                Mage::unregister('cc_exp_year');
                Mage::unregister('cust_email');
                
                
                
                return $_order;
                

            } catch (Exception $e){
            
            	$message = $e->getMessage();
            	
              	if( !empty($message) ) {
               
               		echo $message;
                   
               	}
                
            }

        }
        
        return null;
    }
    
    protected function _processQuote($data = array())
    {
        /* Saving order data */
        if (!empty($data['order'])) {
            $this->_getOrderCreateModel()->importPostData($data['order']);
        }
        
        $this->_getOrderCreateModel()->getBillingAddress();
        $this->_getOrderCreateModel()->setShippingAsBilling(true);
        
        /* Just like adding products from Magento admin grid */
        if (!empty($data['add_products'])) {
            $this->_getOrderCreateModel()->addProducts($data['add_products']);
        }
        
        /* Collect shipping rates */
        $this->_getOrderCreateModel()->collectShippingRates();
        
        /* Add payment data */
        if (!empty($data['payment'])) {
            $this->_getOrderCreateModel()->getQuote()->getPayment()->addData($data['payment']);
        }
        
        $this->_getOrderCreateModel()->initRuleData()->saveQuote();
        
        if (!empty($data['payment'])) {
            $this->_getOrderCreateModel()->getQuote()->getPayment()->addData($data['payment']);
        }
        
        return $this;
    }
}
?>