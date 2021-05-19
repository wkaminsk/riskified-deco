var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/view/payment/default': {
                'Riskified_Deco/js/payment': true
            },
        }
    },
    map: {
        '*': {
            deco: 'Riskified_Deco/js/deco',
            eligible: 'Riskified_Deco/js/eligible'
        }
    }
};
