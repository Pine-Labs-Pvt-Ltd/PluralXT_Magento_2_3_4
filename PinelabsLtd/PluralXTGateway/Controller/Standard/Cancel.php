<?php

namespace PinelabsLtd\PluralXTGateway\Controller\Standard;

class Cancel extends \PinelabsLtd\PluralXTGateway\Controller\PluralXTAbstract {

    public function execute() {
        $this->getOrder()->cancel()->save();
        
        $this->messageManager->addErrorMessage(__('Your order has been can cancelled'));
        $this->getResponse()->setRedirect(
                $this->getCheckoutHelper()->getUrl('checkout')
        );
    }
}
