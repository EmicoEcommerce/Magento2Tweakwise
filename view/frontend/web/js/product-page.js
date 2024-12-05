define('Tweakwise_Magento2Tweakwise/js/product-page', ['jquery'], function($) {
    'use strict';

    return function(config) {
        $(document).ready(function() {
            var productKey = config.productKey;

            $.ajax({
                url: '/tweakwise/ajax/analytics',
                method: 'POST',
                data: {
                    type: 'product',
                    productKey: productKey
                },
                error: function(error) {
                    console.error('Tweakwise API call failed:', error);
                }
            });
        });
    };
});
