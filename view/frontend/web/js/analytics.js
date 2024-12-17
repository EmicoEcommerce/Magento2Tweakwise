define('Tweakwise_Magento2Tweakwise/js/analytics', ['jquery'], function($) {
    'use strict';

    return function(config) {
        $(document).ready(function() {
            var requestData = {
                type: config.type,
                value: config.value
            };

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
