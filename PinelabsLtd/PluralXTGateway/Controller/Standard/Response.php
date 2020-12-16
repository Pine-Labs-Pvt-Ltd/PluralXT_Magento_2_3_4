<?php

namespace PinelabsLtd\PluralXTGateway\Controller\Standard;

use Magento\Sales\Model\Order\Email\Sender\OrderSender;

class Response extends \PinelabsLtd\PluralXTGateway\Controller\PluralXTAbstract {
  
 
    
    public function execute() {
		
		$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/PluralXT/'.date("Y-m-d").'.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
		
		$logger->info('Payment Response Received');
        
		$returnUrl = $this->getCheckoutHelper()->getUrl('checkout');
        try {

            $paymentMethod = $this->getPaymentMethod();
            $params = $this->getRequest()->getParams();

            if ($paymentMethod->validateResponse($params)) {
				
				$logger->info('Payment Response Parameter validated successfully');
				
                $returnUrl = $this->getCheckoutHelper()->getUrl('checkout/onepage/success');
				
				$merchantTxnID = $params['order_id'];
				//$merchantTxnID = $params['unique_merchant_txn_id'];
				$order_id = explode('_', $merchantTxnID);
				$order_id = $order_id[0]; //get order_id part
				
				$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			  
             	$order = $objectManager->create('Magento\Sales\Api\Data\OrderInterface')->loadByIncrementId($order_id);
				
				$order->setState('processing')->setStatus('processing');
					
				$order->save();
				
				$logger->info('Order Information saved successfully');
                //$order = $this->getOrder();
                $payment = $order->getPayment();
				
                // $paymentMethod->postProcessing($order, $payment, $params);
				
				$returnUrl = $this->getCheckoutHelper()->getUrl('checkout/onepage/success');
				
				try {
					$orderSender=$objectManager->create('Magento\Sales\Model\Order\Email\Sender\OrderSender');
					$orderSender->send($order);
				    } 			
				catch (\Exception $e) 
					{
					$this->logger->critical($e);
					}
            } 
			else {
					$returnUrl = $this->getCheckoutHelper()->getUrl('checkout/onepage/failure');
					$this->_cancelPayment('Payment fails');
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
			$logger->info('Order updation failed with Exception message'.$e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('We can\'t place your the order.'));
			$logger->info('Order updation failed with Exception message'.$e);
			$logger->info('Order updation failed with Exception message'.$e->getMessage());
        }
        $this->getResponse()->setRedirect($returnUrl);
    }
}
