define('Tweakwise_Magento2Tweakwise/js/analytics', ['jquery'], function($) {
    'use strict';

    function handleItemClick(event, config) {
        try {
            if (!config.twRequestId) {
                return;
            }

            const product = $(event.target).closest(`${config.productSelector}`)[0];
            let productId;

            if (!product || !product.id) {
                const visual = $(event.target).closest('.visual');
                if (!visual.length) {
                    const link = $(event.target).closest('a');
                    if (link.length) {
                        visual = link.find('.visual');
                    }

                    if (!visual.length) {
                        return;
                    }
                }
                productId = visual.attr('id');
            } else {
                productId = product.id.replace(`${config.productSelector}_`, '');
            }

            if (!productId) {
                return;
            }

            // Send async AJAX request to the analytics endpoint
            $.ajax({
                url: config.analyticsEndpoint,
                type: 'POST',
                data: {
                    type: 'itemclick',
                    value: productId,
                    requestId: config.twRequestId
                },
                cache: false,
                success: function(response) {
                    // Do nothing
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Error sending analytics event', textStatus, errorThrown);
                }
            });
        } catch (error) {
            console.error('Error handling product click event', error);
        }
    }

    return function(config) {
        $(document).ready(function() {

            // send item or search view event
            if (config.type && config.value) {
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
            }

            // bindItemClickEvents
            if (config.bindItemClickEventsConfig) {
                const bindConfig = config.bindItemClickEventsConfig;
                const productList = $(bindConfig.productListSelector);

                if (!bindConfig.twRequestId || !productList.length) {
                    return;
                }

                productList.on('click', function(event) {
                    handleItemClick(event, bindConfig);
                });
            }
        });
    };
});
