define([
    'jquery',
    'Tweakwise_Magento2Tweakwise/js/analytics/push'
], function ($, pushEvent) {
    'use strict';

    /**
     * Reads productKey/price from the submitted product's own listing tile (its wrapper carries
     * "product-item-info_<productKey>" as its id, and a data-tw-price attribute - see
     * product/list/item.phtml), falling back to the current product view page's data when there's no
     * such tile (see analytics.phtml/analytics.js). Mirrors Yireo_GoogleTagManager2's
     * catalog-add-to-cart-mixin.js, which reads pre-rendered page data instead of asking the backend.
     */
    function getProductData(form) {
        const wrapper = $(form).closest('.product-item-info');

        if (wrapper.length && wrapper.attr('id')) {
            return {
                productKey: wrapper.attr('id').replace('product-item-info_', ''),
                price: parseFloat(wrapper.data('twPrice'))
            };
        }

        return window.tweakwiseCurrentProduct || null;
    }

    var mixin = {
        submitForm: function (form) {
            try {
                const formData = Object.fromEntries(new FormData(form[0]).entries());
                const qty = parseFloat(formData.qty) || 1;
                const productData = getProductData(form);

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
