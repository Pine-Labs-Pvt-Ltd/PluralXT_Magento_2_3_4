define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/customer-data',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/full-screen-loader',
        'PinelabsLtd_PluralXTGateway/js/form/form-builder',
        'Magento_Ui/js/modal/alert'
    ],
    function ($, quote, customerData,customer, fullScreenLoader, formBuilder,alert) {
        'use strict';

        return function (messageContainer) {

            var serviceUrl,
                email,
                form;

            if (!customer.isLoggedIn()) {
                email = quote.guestEmail;
            } else {
                email = customer.customerData.email;
            }

              
         
			 serviceUrl = window.checkoutConfig.payment.pluralxt.redirectUrl+'?email='+email;
            fullScreenLoader.startLoader();
            
            $.ajax({
                url: serviceUrl,
                type: 'post',
                context: this,
                data: {isAjax: 1},
               
                success: function (response) {
					
                    if ($.type(response) === 'object' && !$.isEmptyObject(response) 
                        && response.fields != null && response.fields.response_code == 1)
                    {
                        var token = response.fields.token;
                        var ppc_PayModeOnLandingPage = response.fields.ppc_PayModeOnLandingPage;
                        var pluralxtHostUrl = response.url;

                        customerData.invalidate(['cart']);

                        window.location = pluralxtHostUrl + '/pinepg/v2/process/payment/redirect?orderToken=' + token + '&paymentmodecsv=' + ppc_PayModeOnLandingPage;
                    } else {
                        fullScreenLoader.stopLoader();
                        alert({
                            content: $.mage.__('Sorry, something went wrong. Please try again.')
                        });
                    }
                },
                error: function (response) {
                    fullScreenLoader.stopLoader();
                    alert({
                        content: $.mage.__('Sorry, something went wrong. Please try again later.')
                    });
                }
            });
        };
    }
);


