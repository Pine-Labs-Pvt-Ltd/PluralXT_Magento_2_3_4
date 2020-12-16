<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace PinelabsLtd\PluralXTGateway\Model;

use Magento\Sales\Api\Data\TransactionInterface;

/**
 * Pay In Store payment method model
 */
class PluralXTPaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod
{

    /**
     * Payment code
     *
     * @var string
     */
	const PAYMENT_RAPID_PAY_CODE = 'pluralxtpaymentmethod';
    protected $_code = self::PAYMENT_RAPID_PAY_CODE;


    protected $_isOffline = true;

	private $checkoutSession;
	public  $logger;
    /**
     * 
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
      public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \PinelabsLtd\PluralXTGateway\Helper\PluralXT $helper,
       
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory,
        \Magento\Checkout\Model\Session $checkoutSession   ,
        \Magento\Checkout\Model\Cart $cart		
              
    ) {
        $this->helper = $helper;
        $this->httpClientFactory = $httpClientFactory;
        $this->checkoutSession = $checkoutSession;
        $this->cart = $cart;
		$this->_countryHelper = \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Directory\Model\Country');
    
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger
        );

    }
	
	public function getRedirectUrl() {
        return $this->helper->getUrl($this->getConfigData('redirect_url'));
    }

    public function getReturnUrl() {
        return $this->helper->getUrl($this->getConfigData('return_url'));
    }

    public function getCancelUrl() {
        return $this->helper->getUrl($this->getConfigData('cancel_url'));
    }

    /**
     * Return url according to environment
     * @return string
     */
    public function getCgiUrl() {
        $env = $this->getConfigData('PayEnvironment');
        if ($env === 'LIVE') {
            return $this->getConfigData('production_url');
        }
        return $this->getConfigData('sandbox_url');
    }
	  public function Hex2String($hex){
            $string='';
            for ($i=0; $i < strlen($hex)-1; $i+=2){
                $string .= chr(hexdec($hex[$i].$hex[$i+1]));
            }
            return $string;
        }
		
    public function buildCheckoutRequest() {
		
		$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/PluralXT/'.date("Y-m-d").'.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
		$logger->info('Enter Method[buildCheckoutRequest]');
		
        $order = $this->checkoutSession->getLastRealOrder();
        
        $billing_address = $order->getBillingAddress();
		
		$billAddress1 = '';
        $billAddress2 = '';
        $billAddress3 = '';

        if (!empty($billing_address))
        {
	        $billStreet = $billing_address->getStreet();

	        if (gettype($billStreet) == 'string')
	        {
	        	$billStreet = explode("\n", $billStreet);
			}

	        if (!empty($billStreet))
	        {
	        	if (array_key_exists(0, $billStreet) && !empty($billStreet[0]))
	        	{
		        	$billAddress1 = $billStreet[0];
				}

				if (array_key_exists(1, $billStreet) && !empty($billStreet[1]))
	        	{
		        	$billAddress2 = $billStreet[1];
				}

				if (array_key_exists(2, $billStreet) && !empty($billStreet[2]))
	        	{
		        	$billAddress3 = $billStreet[2];
				}
	        }
		}

		$shipping_address = $order->getShippingAddress();

        $shipAddress1 = '';
        $shipAddress2 = '';
        $shipAddress3 = '';

		if (!empty($shipping_address))
        {
	        $shipStreet = $shipping_address->getStreet();
	        // $shipStreet = explode("\n", $shipStreet);

	        if (gettype($shipStreet) == 'string')
	        {
	        	$shipStreet = explode("\n", $shipStreet);
			}

	        if (!empty($shipStreet))
	        {
	        	if (array_key_exists(0, $shipStreet) && !empty($shipStreet[0]))
	        	{
		        	$shipAddress1 = $shipStreet[0];
				}

				if (array_key_exists(1, $shipStreet) && !empty($shipStreet[1]))
	        	{
		        	$shipAddress2 = $shipStreet[1];
				}

				if (array_key_exists(2, $shipStreet) && !empty($shipStreet[2]))
	        	{
		        	$shipAddress3 = $shipStreet[2];
				}
	        }
		}

		$params = array();

        $secret_key = $this -> Hex2String($this->getConfigData("MerchantSecretKey"));
        $params["ppc_PayModeOnLandingPage"] = $this->getConfigData("MerchantPaymentMode");

		$om = \Magento\Framework\App\ObjectManager::getInstance();  
		$customerSession = $om->get('Magento\Customer\Model\Session');  
		
		if (!empty($customerSession))
		{
			$customerID = $customerSession->getCustomer()->getId();
		}
		else 
		{
		    $customerID = '0';
		}

		$ppc_MerchantProductInfo = '';

		$product_info_data = new \stdClass();

		$k = 0;

		foreach ($order->getAllVisibleItems() as $product) 
		{
			//iterate the cart_quantity of a particular product 
			for ($j = 0; $j < $product->getQtyOrdered(); $j++)
			{
				$ppc_MerchantProductInfo .= $product->getName() . '|';

				$product_details = new \stdClass();
				$product_details->product_code = $product->getSku();
				$product_details->product_amount = round($product->getPrice(), 2)*100;
				
				$product_info_data->product_details[$k++] = $product_details;
			} 
        }

		$ppc_MerchantProductInfo = substr($ppc_MerchantProductInfo, 0, -1);

		$merchant_data = new \stdClass();		
		$merchant_data->merchant_return_url = $this->getReturnUrl();
		$merchant_data->merchant_access_code =$this->getConfigData("MerchantAccessCode");
		$merchant_data->order_id = $this->checkoutSession->getLastRealOrderId() . '_' . date("ymdHis");
		$merchant_data->merchant_id = $this->getConfigData("MerchantId");

		$payment_info_data = new \stdClass();
		$payment_info_data->amount = round($order->getBaseGrandTotal(), 2)*100;
		$payment_info_data->currency_code = "INR";
		$payment_info_data->preferred_gateway = $this->getConfigData('PreferredPG');
		$payment_info_data->order_desc = $ppc_MerchantProductInfo;

		$customer_data = new \stdClass();
		// $customer_data->customer_id = $customerID;
		$customer_data->customer_ref_no = $customerID;
		// $customer_data->mobile_no = preg_replace('/\D/', '', $billing_address->getData('telephone'));
		$customer_data->mobile_number = preg_replace('/\D/', '', $billing_address->getData('telephone'));
		$customer_data->email_id = $billing_address->getData('email');
		$customer_data->first_name = $billing_address->getData('firstname');
		$customer_data->last_name = $billing_address->getData('lastname');
		$customer_data->country_code = "91";

		$billing_address_data = new \stdClass();
		$billing_address_data->first_name = $billing_address->getData('firstname');
		$billing_address_data->last_name = $billing_address->getData('lastname');
		$billing_address_data->address1 = $billAddress1;
		$billing_address_data->address2 = $billAddress2;
		$billing_address_data->address3 = $billAddress3;
		$billing_address_data->pincode = preg_replace('/\D/', '', $billing_address->getData('postcode'));
		$billing_address_data->city = $billing_address->getData('city');
		$billing_address_data->state = $billing_address->getData('region');

		$billingCountryObj = $this->_countryHelper->loadByCode($billing_address->getData('country_id'));
		
		$billing_address_data->country = $billingCountryObj->getName();

		$shipping_address_data = new \stdClass();
		$shipping_address_data->first_name = $shipping_address->getData('firstname');
		$shipping_address_data->last_name = $shipping_address->getData('lastname');
		$shipping_address_data->address1 = $shipAddress1;
		$shipping_address_data->address2 = $shipAddress2;
		$shipping_address_data->address3 = $shipAddress3;
		$shipping_address_data->pincode = preg_replace('/\D/', '', $shipping_address->getData('postcode'));
		$shipping_address_data->city = $shipping_address->getData('city');
		$shipping_address_data->state = $shipping_address->getData('region');
		
		$shippingCountryObj = $this->_countryHelper->loadByCode($shipping_address->getData('country_id'));
		
		$shipping_address_data->country = $shippingCountryObj->getName();

		$additional_info_data = new \stdClass();
		$additional_info_data->rfu1 = '';

		$orderData = new \stdClass();

		$orderData->merchant_data = $merchant_data;
		$orderData->payment_info_data = $payment_info_data;
		$orderData->customer_data = $customer_data;
		$orderData->billing_address_data = $billing_address_data;
		$orderData->shipping_address_data = $shipping_address_data;
		$orderData->product_info_data = $product_info_data;
		$orderData->additional_info_data = $additional_info_data;

		$orderData = json_encode($orderData);

		$requestData = new \stdClass();
		$requestData->request = base64_encode($orderData);

		$logger->info('Encoded Request for order creation: ' . $requestData->request);

		$x_verify = strtoupper(hash_hmac("sha256", $requestData->request, $this->Hex2String($this->getConfigData("MerchantSecretKey"))));

		$requestData = json_encode($requestData);

		$pluralxtHostUrl = $this->getCgiUrl();

		$orderCreationUrl = $pluralxtHostUrl . '/api/v2/order/create';
		$logger->info('Order Request Sent:');
		$order_creation = $this->callOrderCreationAPI($orderCreationUrl, $requestData, $x_verify);
        $logger->info('Response Received:');
		$response = json_decode($order_creation, true);

		$response_code = null;
		$token = null;

		if (!empty($response))
		{	
			$logger->info('Response:');
		if (array_key_exists('response_code', $response))
			{	
				$response_code = $response['response_code'];
				$logger->info('Order Response Code:'.$response_code);
			}

			if (array_key_exists('token', $response))
			{
				$token = $response['token'];
			}	
		}else
		{
			$logger->info('NULL Response Received from order Creation API');
		}
        
		$logger->info('Order creation response code: ' . $response_code);

        $params['response_code'] = $response_code;
        $params['token'] = $token;

        return $params;
    }

    function callOrderCreationAPI($url, $data, $x_verify)
	{
	   	$curl = curl_init();
		
		curl_setopt($curl, CURLOPT_POST, 1);
		
		if ($data)
		{
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		}
		// OPTIONS:
		curl_setopt($curl, CURLOPT_URL, $url);

		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
		  'X-VERIFY: ' . $x_verify,
		  'Content-Type: application/json',
		));

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

		// EXECUTE:
		$result = curl_exec($curl);

		if (!$result) {
			die("Connection Failure");
		}

		curl_close($curl);

		return $result;
	}
	
	  //validate response
    public function validateResponse($returnParams) {
		
		$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/PluralXT/'.date("Y-m-d").'.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
		$logger->info('Enter Method[validateResponse]');
		
		$order_id=0;
		// if (isset($returnParams['unique_merchant_txn_id'])) 
		// {
		//   $order_id = trim(($returnParams['unique_merchant_txn_id']));
		if (isset($returnParams['order_id'])) 
		{
		  $order_id = trim(($returnParams['order_id']));
		  $logger->info('Enter Method[validateResponse] validate response for order id:'.$order_id);
		} 
		else 
		{
		 $logger->info('Enter Method[validateResponse] Received order id is null');
		  die('Illegal Access ORDER ID NOR PASSED');
		}
		
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order_info = $objectManager->create('Magento\Sales\Model\Order')->load($order_id);
		
		
	   if ($order_info) 
		{
			if ( !empty($returnParams) ) 
			{
					
				$DiaSecretType='';
				$DiaSecret='';
				
				if (isset($returnParams['dia_secret_type'])) {
					$DiaSecretType = $returnParams['dia_secret_type'];
				} 
				if (isset($returnParams['dia_secret'])) {
					$DiaSecret = $returnParams['dia_secret'];
				} 

				$strString="";
				ksort($returnParams);
				foreach ($returnParams as $key => $value)
				{
					$strString.=$key."=".$value."&";
				}

				$logger->info('Method[validateResponse] [Order ID]:' . $order_id.' Received parameters : '.$strString);
				unset($returnParams['dia_secret_type']);
				unset($returnParams['dia_secret']);
				$strString="";
				$secret_key   =   $this -> Hex2String($this->getConfigData("MerchantSecretKey"));
				ksort($returnParams);
				foreach ($returnParams as $key => $value)
				{
					$strString.=$key."=".$value."&";
				}			
				$strString = substr($strString, 0, -1);
				$SecretHashCode = strtoupper(hash_hmac('sha256', $strString, $secret_key));
			
				if("" == trim($DiaSecret))
				{	
					$logger->info('Method[validateResponse] [Order ID]:' . $order_id.' Transaction failed.Pine PG Secure hash is empty');
					return false;
				}   
				else
				{
					if(trim($DiaSecret)==trim($SecretHashCode))
					{	
						if ($returnParams['payment_status'] == 'CAPTURED' && $returnParams['payment_response_code'] == '1') 
						{		
							$logger->info('Method[validateResponse] [Order ID]:' . $order_id.' Payment Transation is successful');
							return true;
						}
						else if($returnParams['payment_status'] == 'CANCELLED')
						{
							$logger->info('Method[validateResponse] [Order ID]:' . $order_id.' Transaction cancelled by user ');
							return false;
						}
						else if($returnParams['payment_status'] == 'REJECTED')
						{ 
							$logger->info('Method[validateResponse] [Order ID]:' . $order_id.' Transaction rejected by system ');
							return false;
						}
						else
						{
							$logger->info('Method[validateResponse] [Order ID]:' . $order_id.'  Transaction failed ');
							return false;
						}
					}
					else
					{
						$logger->info('Method[validateResponse] [Order ID]:' . $order_id.'  Transaction failed.Secure_Hash not matched with Pine PG Secure Hash');
						return false;
					}
				}
			}
			else
			{ 	    
					$logger->info('Method[validateResponse] Post parameters received is empty');	
					die('Illegal Access POST REQUEST IS EMPTY');
					return false;
			}
		}
		else 
		{	
		 $logger->info('Method[validateResponse] Received order id is null:');
		  die('Illegal Access ORDER ID NOR PASSED');
		}
			
     return false;
    }


    public function postProcessing(\Magento\Sales\Model\Order $order,
            \Magento\Framework\DataObject $payment, $response) {
				
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/PluralXT/'.date("Y-m-d").'.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
		$logger->info('Enter Method[postProcessing]');

		//$transactionId = explode('_', $response['ppc_UniqueMerchantTxnID']);
		//$transactionId = $transactionId[0]; //get order_id part
		//$payment->setTransactionId($transactionId[0]);
		//$payment->setParentTransactionId($transactionId[0].'parent');
		//error_log('pine transactionId'.$response['ppc_PluralXTTransactionID']);
		$payment->setTransactionId($response['ppc_PluralXTTransactionID']);
		//$payment->setTransactionAdditionalInfo('ppc_PluralXTTransactionID',$response['ppc_PluralXTTransactionID']);
        $payment->setAdditionalInformation('ppc_Amount_in_paise', $response['ppc_Amount']);
		//$payment->setAdditionalInformation('Order_No', $transactionId[0]);
		if (isset($response['ppc_Is_BrandEMITransaction']) ) 
		{
			if($response['ppc_Is_BrandEMITransaction']=="1")
			{
				 $payment->setAdditionalInformation('ppc_Is_BrandEMITransaction', $response['ppc_Is_BrandEMITransaction']);			
			}
		}
		if (isset($response['ppc_Is_BankEMITransaction']) ||isset($response['ppc_Is_BankEMITransaction'])) 
		{	
			if($response['ppc_Is_BankEMITransaction']=="1")
			{
				 $payment->setAdditionalInformation('ppc_Is_BankEMITransaction', $response['ppc_Is_BankEMITransaction']);			
			}
		}
		//if (isset($response['ppc_PluralXTTransactionID']) ) 
		//{
		//	$payment->setAdditionalInformation('ppc_PluralXTTransactionID', $response['ppc_PluralXTTransactionID']);
		//}
	    
		if (isset($response['ppc_IssuerName']) ) 
		{
			$payment->setAdditionalInformation('ppc_IssuerName', $response['ppc_IssuerName']);
		}
		
		if (isset($response['ppc_EMIInterestRatePercent']) ) 
		{
			$payment->setAdditionalInformation('ppc_EMIInterestRatePercent', $response['ppc_EMIInterestRatePercent']);
		}
		if (isset($response['ppc_EMIAmountPayableEachMonth']) ) 
		{
			$payment->setAdditionalInformation('ppc_EMIAmountPayableEachMonth', $response['ppc_EMIAmountPayableEachMonth']);
		}
		
		if (isset($response['ppc_EMITotalDiscCashBackPercent']) ) 
		{
			$payment->setAdditionalInformation('ppc_EMITotalDiscCashBackPercent', $response['ppc_EMITotalDiscCashBackPercent']);
		}
	    if (isset($response['ppc_EMITotalDiscCashBackAmt']) ) 
		{
			 $payment->setAdditionalInformation('ppc_EMITotalDiscCashBackAmt', $response['ppc_EMITotalDiscCashBackAmt']);
		}
	   
		if (isset($response['ppc_EMITenureMonth']) ) 
		{
		  $payment->setAdditionalInformation('ppc_EMITenureMonth', $response['ppc_EMITenureMonth']);
		}
	    if (isset($response['ppc_EMICashBackType']) ) 
		{
			 $payment->setAdditionalInformation('ppc_EMICashBackType', $response['ppc_EMICashBackType']);
		}
	    if (isset($response['ppc_EMIAdditionalCashBack']) ) 
		{
			 $payment->setAdditionalInformation('ppc_EMIAdditionalCashBack', $response['ppc_EMIAdditionalCashBack']);
		}
				
        $payment->addTransaction("order");
        $payment->setIsTransactionClosed(0);
        $payment->place();
        $order->setStatus('processing');
        $order->save();
		$logger->info('Enter Method[postProcessing] Save the order after successful response from Pine PG for order id:'.$response['ppc_UniqueMerchantTxnID'].'and Pine PG Txn ID:'.$response['ppc_PluralXTTransactionID'] );
    }
}