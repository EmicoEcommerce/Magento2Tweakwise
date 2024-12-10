define('Tweakwise_Magento2Tweakwise/js/search-page', ['jquery'], function($) {
    'use strict';

    return function(config) {
        $(document).ready(function() {
            var searchQuery = config.searchQuery;

            $.ajax({
                url: '/tweakwise/ajax/analytics',
                method: 'POST',
                data: {
                    type: 'search',
                    searchTerm: searchQuery
                },
                error: function(error) {
                    console.error('Tweakwise API call failed:', error);
                }
            });
        });
    };
});
