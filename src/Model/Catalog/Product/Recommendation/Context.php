<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Tweakwise\Magento2Tweakwise\Model\Catalog\Product\Recommendation;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Tweakwise\Magento2Tweakwise\Model\Client;
use Tweakwise\Magento2Tweakwise\Model\Client\RequestPool;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\Recommendations\FeaturedRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\RequestFactory;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\RecommendationsResponse;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Config as CatalogConfig;
use Tweakwise\Magento2Tweakwise\Model\Config;

class Context
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var CatalogConfig
     */
    protected $catalogConfig;

    /**
     * @var Visibility
     */
    protected $visibility;

    /**
     * @var FeaturedRequest
     */
    protected $request;

    /**
     * @var RecommendationsResponse
     */
    protected $response;

    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var RequestPool
     */
    private readonly RequestPool $requestPool;

    /**
     * @var ProfileKeyApplier
     */
    private readonly ProfileKeyApplier $profileKeyApplier;

    /**
     * Context constructor.
     * @param Client $client
     * @param RequestFactory $requestFactory
     * @param CollectionFactory $collectionFactory
     * @param CatalogConfig $catalogConfig
     * @param Visibility $visibility
     * @param Config $config
     * @param CookieManagerInterface $cookieManager
     * @param RequestPool|null $requestPool
     * @param ProfileKeyApplier|null $profileKeyApplier
     */
    public function __construct(
        Client $client,
        RequestFactory $requestFactory,
        CollectionFactory $collectionFactory,
        CatalogConfig $catalogConfig,
        Visibility $visibility,
        Config $config,
        CookieManagerInterface $cookieManager,
        ?RequestPool $requestPool = null,
        ?ProfileKeyApplier $profileKeyApplier = null
    ) {
        $this->client = $client;
        $this->requestFactory = $requestFactory;
        $this->collectionFactory = $collectionFactory;
        $this->catalogConfig = $catalogConfig;
        $this->visibility = $visibility;
        $this->config = $config;
        $this->requestPool = $requestPool ?? ObjectManager::getInstance()->get(RequestPool::class);
        $this->profileKeyApplier = $profileKeyApplier ?? new ProfileKeyApplier($config, $cookieManager);
    }

    /**
     * @return FeaturedRequest
     */
    public function getRequest()
    {
        // @phpstan-ignore-next-line
        if (!$this->request) {
            // @phpstan-ignore-next-line
            $this->request = $this->requestFactory->create();
        }

        $this->profileKeyApplier->apply($this->request);

        return $this->request;
    }

    /**
     * Resolves the response through the request pool: every request queued before this point (e.g. the other
     * recommendation types prefetched for the same product) is sent to Tweakwise concurrently with this one.
     *
     * @return RecommendationsResponse
     */
    public function getResponse()
    {
        // @phpstan-ignore-next-line
        if (!$this->response) {
            // @phpstan-ignore-next-line
            $this->response = $this->requestPool->resolve($this->getRequest());
        }

        if (!is_numeric($this->request->getTemplate())) {
            //grouped item
            $limit = $this->config->getLimitGroupCodeItems();
            if (!empty($limit) && $limit > 0) {
                $items = $this->response->getItems();
                $items = array_slice($items, 0, $limit);
                $this->response->replaceItems($items);
            }
        }

        return $this->response;
    }

    /**
     * @return Collection
     */
    public function getCollection()
    {
        // @phpstan-ignore-next-line
        if (!$this->collection) {
            $collection = $this->collectionFactory->create(['response' => $this->getResponse()]);
            $this->prepareCollection($collection);
            $this->collection = $collection;
        }

        return $this->collection;
    }

    /**
     * @param Collection $collection
     * @return void
     */
    protected function prepareCollection(Collection $collection)
    {
        $collection->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addAttributeToSelect($this->catalogConfig->getProductAttributes())
            ->addUrlRewrite()
            ->setVisibility($this->visibility->getVisibleInCatalogIds())
            ->setFlag('do_not_use_category_id', true);
    }

    /**
     * @param FeaturedRequest $request
     * @return void
     */
    public function setRequest(FeaturedRequest $request)
    {
        // @phpstan-ignore-next-line
        $this->collection = null;
        // @phpstan-ignore-next-line
        $this->response = null;
        $this->request = $request;
    }
}
