define([
    'jquery',
    'ko',
    'Magento_Checkout/js/model/quote',
    'mage/storage',
    'Magento_Checkout/js/action/redirect-on-success',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/full-screen-loader'
], function ($, ko, quote, storage, redirectOnSuccessAction, checkoutData, fullScreenLoader) {
    'use strict';

    function optInCall() {
        fullScreenLoader.startLoader();
        storage.post(
            'deco/checkout/optIn',
            JSON.stringify({
                quote_id: quote.getQuoteId(),
                payment_method: checkoutData.getSelectedPaymentMethod()
            }),
            true
        ).done(function (result) {
            if (result.status === 'opt_in') {
                redirectOnSuccessAction.execute();
            }
        });
        fullScreenLoader.stopLoader();
    }

    return {
        paymentFail: function(buttonColor, buttonTextColor, logoUrl) {
            fullScreenLoader.startLoader();

            if ($('.payment-methods input[type="radio"]:checked').val() === 'authorizenet_directpost') {
                storage.post(
                    'deco/checkout/checkoutDenied',
                    JSON.stringify({
                        quote_id: quote.getQuoteId()
                    }),
                    true
                );
            }

            storage.post(
                'deco/checkout/isEligible',
                JSON.stringify({
                    quote_id: quote.getQuoteId()
                }),
                true
            ).done(function (result) {
                if (result.status === 'eligible') {
                    $('.payment-method._active #deco-container').html("<div id='deco-widget'></div>");
                    window.drawDecoWidget(() => {
                        return optInCall();
                    }, {
                        buttonColor: buttonColor,
                        buttonText: buttonTextColor,
                        logoUrl: logoUrl
                    });
                    $("#deco-main-button").click(function(e){e.preventDefault();});
                }
            });
            fullScreenLoader.stopLoader();
        }
    }
});
