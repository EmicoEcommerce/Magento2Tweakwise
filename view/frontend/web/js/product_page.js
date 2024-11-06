define(['jquery'], function($) {
    'use strict';

    return function(config) {
        $(document).ready(function() {
            var apiUrl = config.apiUrl;
            var profileKey = config.profileKey;
            var productKey = config.productKey;

            $.ajax({
                url: apiUrl,
                method: 'POST',
                data: {
                    profileKey: profileKey,
                    productKey: productKey
                },
                success: function(response) {
                    console.log(response);
                },
                error: function(error) {
                    console.error('Tweakwise API call failed:', error);
                }
            });
        });
    };
});
