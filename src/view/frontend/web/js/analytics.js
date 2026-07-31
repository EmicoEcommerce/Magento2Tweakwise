define('Tweakwise_Magento2Tweakwise/js/analytics', [
    'jquery',
    'Magento_Customer/js/customer-data',
    'Tweakwise_Magento2Tweakwise/js/analytics/push'
], function($, customerData, pushEvent) {
    'use strict';

    function pushEventsData(eventsData) {
        (eventsData || []).forEach(function(eventData) {
            switch (eventData.type) {
                case 'product':
                    pushEvent('productView', { productKey: eventData.value });
                    break;
                case 'search':
                    pushEvent('search', { searchTerm: eventData.value });
                    break;
                case 'page_impression':
                    pushEvent('pageImpression', { requestId: eventData.requestId });
                    break;
                case 'addtocart_event':
                    pushEvent('addtocart', eventData.value);
                    break;
                case 'addtowishlist_event':
                    pushEvent('addtowishlist', eventData.value);
                    break;
                case 'purchase_event':
                    pushEvent('purchase', eventData.value);
                    break;
                default:
                    break;
            }
        });
    }

    function handleItemClick(event, config) {
        try {
            if (!config.twRequestId) {
                return;
            }

            const product = $(event.target).closest(`.${config.productSelector}`)[0];
            let productId;

            if (!product || !product.id) {
                let visual = $(event.target).closest('.visual');
                if (!visual.length) {
                    const link = $(event.target).closest('a');
                    if (link.length) {
                        visual = link.find('.visual');
                    }

                    if (!visual.length) {
                        console.warn(
                            '[Tweakwise] Could not track item click: no product element with selector ".' +
                            config.productSelector +
                            '" (with an id attribute) or ".visual" element found near the clicked element.',
                            event.target
                        );
                        return;
                    }
                }
                productId = visual.attr('id');
            } else {
                productId = product.id.replace(`${config.productSelector}_`, '');
            }

            if (!productId) {
                console.warn(
                    '[Tweakwise] Could not track item click: product element found but id attribute is empty.',
                    product || event.target
                );
                return;
            }

            pushEvent('itemClick', { itemId: productId, requestId: config.twRequestId });
        } catch (error) {
            console.error('Error handling product click event', error);
        }
    }

    return function(config) {
        // Read by js/mixins/catalog-add-to-cart-mixin.js as a fallback on product view pages, where
        // there's no per-tile DOM wrapper to read a productKey/price from.
        window.tweakwiseCurrentProduct = config.currentProduct || null;

        $(document).ready(function() {

            if (config.eventsData) {
                pushEventsData(config.eventsData);
            }

            // Pending addtocart/addtowishlist events get attached to the "cart"/generic "customer"
            // customer-data sections respectively (see Plugin\CustomerData\AddPendingEventsToCartSection
            // /AddPendingEventsToCustomerSection), never rendered into page HTML directly, since that
            // HTML can be full-page-cached and shared across visitors. Magento's own private-content
            // invalidation already reloads a section on the next page load whenever the action that
            // stashed an event marked it stale (see etc/frontend/sections.xml for wishlist; cart is
            // invalidated by core by default), so no manual forced reload is needed here - just
            // subscribe for delivery.
            ['cart', 'customer'].forEach(function(sectionName) {
                customerData.get(sectionName).subscribe(function(sectionData) {
                    if (!sectionData || !sectionData.tweakwise_events) {
                        return;
                    }

                    pushEventsData(sectionData.tweakwise_events);

                    // Remove the consumed events from customer-data's own (localStorage-backed) cache,
                    // so a later page load reading this same cached section can't push them again.
                    // Mirrors Yireo_GoogleTagManager2's generic.js.
                    delete sectionData.tweakwise_events;
                    customerData.set(sectionName, sectionData);
                });
            });

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
