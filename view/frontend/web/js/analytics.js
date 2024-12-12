define('Tweakwise_Magento2Tweakwise/js/analytics', ['jquery'], function($) {
    'use strict';

    return function(config) {
        $(document).ready(function() {
            var requestData = {
                type: config.type
            };

            if (config.type === 'search') {
                requestData.searchTerm = config.searchQuery;
            } else if (config.type === 'product') {
                requestData.productKey = config.productKey;
            }

            $.ajax({
                url: '/tweakwise/ajax/analytics',
                method: 'POST',
                data: requestData,
                error: function(error) {
                    console.error('Tweakwise API call failed:', error);
                }
            });
        });
    };
});
