define([
    'jquery',
    'ko',
    'Magento_Checkout/js/model/quote',
    'mage/storage',
    'Magento_Checkout/js/model/url-builder',
    'Magento_Checkout/js/action/redirect-on-success',
    'Magento_Checkout/js/checkout-data',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/place-order',
    'Magento_Checkout/js/model/full-screen-loader'
], function ($, ko, quote, storage, urlBuilder, redirectOnSuccessAction, checkoutData, customer, placeOrderService, fullScreenLoader) {
    'use strict';

    function placeOrder() {
        var serviceUrl, payload;
        debugger;
        payload = {
            cartId: quote.getQuoteId(),
            billingAddress: quote.billingAddress(),
            paymentMethod: {
                additional_data : null,
                method : "deco",
                po_number : null
            }
        };

        if (customer.isLoggedIn()) {
            serviceUrl = urlBuilder.createUrl('/carts/mine/payment-information', {});
        } else {
            serviceUrl = urlBuilder.createUrl('/guest-carts/:quoteId/payment-information', {
                quoteId: quote.getQuoteId()
            });

            payload.email = quote.guestEmail;
        }

        return placeOrderService(serviceUrl, payload);
    }

    function optInCall() {
        fullScreenLoader.startLoader();
        storage.post(
            'deco/checkout/optIn',
            JSON.stringify({
                quote_id: quote.getQuoteId(),
                email: quote.shippingAddress._latestValue.email ? quote.shippingAddress._latestValue.email : quote.guestEmail,
                payment_method: checkoutData.getSelectedPaymentMethod()
            }),
            true
        ).done(function (result) {
            if (result.status) {
                $('<input type="radio" name="payment[method]" class="radio js-deco-payment-method" value="deco">')
                    .appendTo($(".payment-group"));

                $('.js-deco-payment-method').click();
                $('.checkout').attr('disabled', 'disabled');

                return placeOrder();
            }
        });
        fullScreenLoader.stopLoader();
    };

    function triggerIsEligible(buttonColor, buttonTextColor, logoUrl)
    {
        storage.post(
            'deco/checkout/isEligible',
            JSON.stringify({
                quote_id: quote.getQuoteId(),
                email: quote.shippingAddress._latestValue.email ? quote.shippingAddress._latestValue.email : quote.guestEmail
            }),
            true
        ).done(function (result) {
            if (result.status === 'eligible') {
                composeDecoPaymentForm(buttonColor, buttonTextColor, logoUrl);
            }
        });
    };
    function composeDecoPaymentForm(buttonColor, buttonTextColor, logoUrl)
    {
        $('.payment-method._active #deco-container').html("<div id='deco-widget'></div>");
        window.drawDecoWidget(() => {
            return optInCall();
        }, {
            buttonColor: buttonColor,
            buttonText: buttonTextColor,
            logoUrl: logoUrl
        });

        $("#deco-main-button").click(function(e){
            e.preventDefault();
        });
    };

    return {
        paymentFail: function(buttonColor, buttonTextColor, logoUrl) {
            fullScreenLoader.startLoader();
            triggerIsEligible(buttonColor, buttonTextColor, logoUrl);
            fullScreenLoader.stopLoader();
        }
    }
});
