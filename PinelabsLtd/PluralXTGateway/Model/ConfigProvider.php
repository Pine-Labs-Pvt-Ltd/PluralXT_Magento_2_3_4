<?php

namespace PinelabsLtd\PluralXTGateway\Model;

class ConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    protected $methodCode = \PinelabsLtd\PluralXTGateway\Model\PluralXTPaymentMethod::PAYMENT_RAPID_PAY_CODE;
    
    
    protected $method;
	

    public function __construct(\Magento\Payment\Helper\Data $paymenthelper){
        $this->method = $paymenthelper->getMethodInstance($this->methodCode);
    }

    public function getConfig(){

        return $this->method->isAvailable() ? [
            'payment'=>['pluralxt'=>[
                'redirectUrl'=>$this->method->getRedirectUrl()  
            ]
        ]
        ]:[];
    }
}
