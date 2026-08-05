define([
    'jquery',
    'Tweakwise_Magento2Tweakwise/js/analytics/push'
], function ($, pushEvent) {
    'use strict';

    /**
     * Reads productKey/price for the submitted Magento product id from window.tweakwiseListingProductData
     * (populated per tile by Plugin\ProductList\AddProductAnalyticsData, keyed by Magento product id -
     * not by any Tweakwise-specific markup, so this survives listing template customisation), falling
     * back to the current product view page's data when there's no matching entry (see
     * analytics.phtml/analytics.js), reading pre-rendered page data instead of asking the backend.
     */
    function getProductData(magentoProductId) {
        const listingData = window.tweakwiseListingProductData || {};

        return listingData[magentoProductId] || window.tweakwiseCurrentProduct || null;
    }

    var mixin = {
        submitForm: function (form) {
            try {
                const formData = Object.fromEntries(new FormData(form[0]).entries());
                const qty = parseFloat(formData.qty) || 1;
                const productData = getProductData(formData.product);

                if (productData && productData.productKey && !isNaN(productData.price)) {
                    pushEvent('addtocart', {
                        productKey: productData.productKey,
                        quantity: qty,
                        totalAmount: qty * productData.price
                    });
                }
            } catch (error) {
                console.error('[Tweakwise] Could not track add to cart', error);
            }

            return this._super(form);
        }
    };

    return function (targetWidget) {
        $.widget('mage.catalogAddToCart', targetWidget, mixin);
        return $.mage.catalogAddToCart;
    };
});
