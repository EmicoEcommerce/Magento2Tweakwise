<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\ViewModel;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\StoreManagerInterface;
use Tweakwise\Magento2Tweakwise\Api\Data\EventInterface;
use Tweakwise\Magento2Tweakwise\Api\Data\TagInterface;
use Tweakwise\Magento2Tweakwise\Model\Analytics\RecommendationImpressionCollector;
use Tweakwise\Magento2Tweakwise\Model\Config;

/**
 * Class PersonalMerchandisingAnalytics
 *
 * ViewModel for personal merchandising analytics.
 */
class PersonalMerchandisingAnalytics implements ArgumentInterface
{
    private const BLOCK_NAME = 'tweakwise.analytics';

    public function __construct(
        private readonly Config $tweakwiseConfig,
        private readonly StoreManagerInterface $storeManager,
        private readonly RequestInterface $request,
        private readonly Json $jsonSerializer,
        private readonly LayoutInterface $layout,
        private readonly RecommendationImpressionCollector $impressionCollector,
    ) {
    }

    /**
     * Get the store manager.
     *
     * @return StoreManagerInterface
     */
    public function getStoreManager(): StoreManagerInterface
    {
        return $this->storeManager;
    }

    /**
     * Get the instance key.
     *
     * @return string
     */
    public function getInstanceKey(): string
    {
        return $this->tweakwiseConfig->getGeneralAuthenticationKey();
    }

    /**
     * Get the cookie name.
     *
     * @return string
     */
    public function getCookieName(): string
    {
        return $this->tweakwiseConfig->getPersonalMerchandisingCookieName();
    }

    /**
     * Get the Tweakwise request ID.
     *
     * @return string
     */
    public function getTwRequestId(): string
    {
        return $this->request->getParam('tw_request_id') ?? '';
    }

    /**
     * The Tag and Event objects composed here come from the "tweakwise.analytics" block's "data_layer"
     * and "data_layer_events" arguments, which each layout XML handle populates with whatever's
     * relevant to that page (see catalog_product_view.xml, catalogsearch_result_index.xml,
     * checkout_onepage_success.xml, ...). Adding a new page's analytics tag/event is therefore a layout
     * change, never a change to this class.
     */
    public function getEventsData(string $requestId): string
    {
        $block = $this->layout->getBlock(self::BLOCK_NAME);
        $eventsData = [];

        if ($block instanceof AbstractBlock) {
            /** @var array<string, TagInterface> $tags */
            $tags = (array)$block->getData('data_layer');
            foreach ($tags as $type => $tag) {
                $eventsData[] = ['type' => $type, 'value' => $tag->get(), 'requestId' => $requestId];
            }

            // addtocart/addtowishlist/purchase are never wired here: this block renders on every page,
            // including pages Magento's full-page cache serves identically to every visitor. Flushing
            // session-specific pending events into that shared HTML would leak one customer's cart/
            // wishlist/order data to every other visitor of the same cached URL. addtocart/addtowishlist
            // are delivered exclusively via the customer-data section AJAX endpoint
            // (Plugin\CustomerData\AddPendingEventsToCartSection/AddPendingEventsToCustomerSection),
            // which is never cached; purchase is only
            // ever wired into checkout_onepage_success.xml, which Magento core already marks
            // cacheable="false".
            /** @var array<string, EventInterface> $events */
            $events = (array)$block->getData('data_layer_events');
            foreach ($events as $type => $event) {
                foreach ($event->get() as $value) {
                    $eventsData[] = ['type' => $type, 'value' => $value, 'requestId' => ''];
                }
            }
        }

        // One additional page_impression per recommendation widget (upsell/related/featured/
        // crosssell) that rendered with items during this request, each carrying that widget's
        // own Tweakwise request-id (see RecommendationImpressionCollector).
        foreach ($this->impressionCollector->getRequestIds() as $widgetRequestId) {
            $eventsData[] = ['type' => 'page_impression', 'value' => 'page_impression', 'requestId' => $widgetRequestId];
        }

        return $this->jsonSerializer->serialize($eventsData);
    }
}
