/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'Magento_Checkout/js/view/payment/default',
		'PinelabsLtd_PluralXTGateway/js/action/set-payment-method',
    ],
      function(Component,setPaymentMethod){
    'use strict';

    return Component.extend({
        defaults:{
            'template':'PinelabsLtd_PluralXTGateway/payment/pluralxtpaymentmethod'
        },
		  redirectAfterPlaceOrder: false,
        
        afterPlaceOrder: function () {
            setPaymentMethod();    
        }
       

    });
});
