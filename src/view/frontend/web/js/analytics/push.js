define('Tweakwise_Magento2Tweakwise/js/analytics/push', [], function() {
    'use strict';

    return function pushEvent(event, data) {
        window.tweakwiseLayer = window.tweakwiseLayer || [];

        // Prevent the same event (e.g. addtocart/addtowishlist/purchase) from being pushed twice when
        // it arrives via more than one delivery path (customer-data section AND a later full page render).
        window.TWEAKWISE_PAST_EVENTS = window.TWEAKWISE_PAST_EVENTS || [];
        const eventHash = btoa(encodeURIComponent(JSON.stringify({ event: event, data: data })));
        if (window.TWEAKWISE_PAST_EVENTS.indexOf(eventHash) !== -1) {
            return;
        }
        window.TWEAKWISE_PAST_EVENTS.push(eventHash);

        window.tweakwiseLayer.push({ event: event, data: data });
    };
});
