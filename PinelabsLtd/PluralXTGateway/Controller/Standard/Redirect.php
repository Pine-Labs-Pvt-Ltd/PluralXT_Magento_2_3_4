<?php

namespace PinelabsLtd\PluralXTGateway\Controller\Standard;

class Redirect extends \PinelabsLtd\PluralXTGateway\Controller\PluralXTAbstract {

    public function execute() 
	{
		$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/PluralXT/'.date("Y-m-d").'.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
		$logger->info('Enter Method[Redirct:execute]');
		
			if (!$this->getRequest()->isAjax()) {
				$this->_cancelPayment();
				$this->_checkoutSession->restoreQuote();
				$this->getResponse()->setRedirect(
						$this->getCheckoutHelper()->getUrl('checkout')
				);
			} 
		
			$order = $this->getOrder();
			$order->setState('pending')->setStatus('pending');
			$order->save();
			$quote = $this->getQuote();
			$email = $this->getRequest()->getParam('email');
			if ($this->getCustomerSession()->isLoggedIn()) {
				$this->getCheckoutSession()->loadCustomerQuote();
				$quote->updateCustomerData($this->getQuote()->getCustomer());
			} else {
				$quote->setCustomerEmail($email);
			}

			if ($this->getCustomerSession()->isLoggedIn()) {
				$quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_CUSTOMER);
			} else {
				$quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_GUEST);
			}
			
			
			$quote->setCustomerEmail($email);
			$quote->save();
		$logger->info('Transaction Processing started');
		
        $params = [];
		
        $params["fields"] = $this->getPaymentMethod()->buildCheckoutRequest();
        $params["url"] = $this->getPaymentMethod()->getCgiUrl();
        return $this->resultJsonFactory->create()->setData($params);
    }

}
