<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Catalog\Layer;

use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Filter\Item;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext\CurrentContext;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\CategoryUrlInterface;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\FilterApplierInterface;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\Strategy\UrlStrategyFactory;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\UrlInterface;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\UrlModel;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductNavigationRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductSearchRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\FacetType\SettingsType;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2TweakwiseExport\Model\Helper as ExportHelper;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\App\Request\Http as MagentoHttpRequest;
use Magento\Framework\Exception\NoSuchEntityException;
use Exception;

/**
 * Class Url will later implement logic to use implementation selected in configuration.
 */
class Url
{
    /**
     * @var UrlInterface
     */
    protected $urlStrategy;

    /**
     * @var FilterApplierInterface
     */
    protected $filterApplier;

    /**
     * @var CategoryUrlInterface
     */
    protected $categoryUrlStrategy;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var ExportHelper
     */
    protected $exportHelper;

    /**
     * @var MagentoHttpRequest
     */
    protected $request;

    /**
     * @var UrlStrategyFactory
     */
    protected $urlStrategyFactory;

    protected $magentoUrl; // @phpstan-ignore-line

    /**
     * Builder constructor.
     *
     * @param UrlStrategyFactory $urlStrategyFactory
     * @param MagentoHttpRequest $request
     * @param CategoryRepositoryInterface $categoryRepository
     * @param ExportHelper $exportHelper
     * @param UrlModel $magentoUrl
     * @param Config $config
     * @param CurrentContext $currentContext
     */
    public function __construct(
        UrlStrategyFactory $urlStrategyFactory,
        MagentoHttpRequest $request,
        CategoryRepositoryInterface $categoryRepository,
        ExportHelper $exportHelper,
        UrlModel $magentoUrl,
        protected readonly Config $config,
        protected readonly CurrentContext $currentContext
    ) {
        $this->urlStrategyFactory = $urlStrategyFactory;
        $this->categoryRepository = $categoryRepository;
        $this->exportHelper = $exportHelper;
        $this->request = $request;
        $this->magentoUrl = $magentoUrl;
    }

    /**
     * @return UrlInterface
     */
    public function getUrlStrategy()
    {
        // @phpstan-ignore-next-line
        if (!$this->urlStrategy) {
            // @phpstan-ignore-next-line
            $this->urlStrategy = $this->urlStrategyFactory->create();
        }

        return $this->urlStrategy;
    }

    /**
     * @return FilterApplierInterface
     */
    protected function getFilterApplier()
    {
        // @phpstan-ignore-next-line
        if (!$this->filterApplier) {
            // @phpstan-ignore-next-line
            $this->filterApplier = $this->urlStrategyFactory
                ->create(FilterApplierInterface::class);
        }

        return $this->filterApplier;
    }

    /**
     * @return CategoryUrlInterface
     */
    protected function getCategoryUrlStrategy()
    {
        // @phpstan-ignore-next-line
        if (!$this->categoryUrlStrategy) {
            // @phpstan-ignore-next-line
            $this->categoryUrlStrategy = $this->urlStrategyFactory
                ->create(CategoryUrlInterface::class);
        }

        return $this->categoryUrlStrategy;
    }

    /**
     * @param Item $item
     * @return string
     */
    public function getSelectFilter(Item $item): string
    {
        $settings = $item
            ->getFilter()
            ->getFacet()
            ->getFacetSettings();

        if ($settings->getSource() === SettingsType::SOURCE_CATEGORY) {
            if ($this->shouldUseTweakwiseCategoryUrl($item)) {
                return $item->getAttribute()->getLink();
            }

            return $this->getCategoryUrlStrategy()
                ->getCategoryFilterSelectUrl($this->request, $item);
        }

        return $this->addBaseUrl($this->getUrlStrategy()->getAttributeSelectUrl($this->request, $item));
    }

    /**
     * Determine whether the Tweakwise-provided category URL should be used for a filter item.
     * Returns true when:
     * - the "Use category URL from Tweakwise" setting is enabled
     * - the item carries a non-empty link from Tweakwise
     * - the current request is not a search request
     *
     * @param Item $item
     * @return bool
     */
    protected function shouldUseTweakwiseCategoryUrl(Item $item): bool
    {
        if (!$this->config->isCategoryUrlFromTweakwiseEnabled()) {
            return false;
        }

        $tweakwiseUrl = $item->getAttribute()->getLink();
        if (empty($tweakwiseUrl)) {
            return false;
        }

        try {
            $navigationRequest = $this->currentContext->getRequest();
        } catch (Exception $e) {
            return false;
        }

        return !($navigationRequest instanceof ProductSearchRequest);
    }

    /**
     * {@inheritdoc}
     */
    public function getRemoveFilter(Item $item): string
    {
        $settings = $item->getFilter()
            ->getFacet()
            ->getFacetSettings();

        if ($settings->getSource() === SettingsType::SOURCE_CATEGORY) {
            return $this->getCategoryUrlStrategy()
                ->getCategoryFilterRemoveUrl($this->request, $item);
        }

        return $this->addBaseUrl($this->getUrlStrategy()->getAttributeRemoveUrl($this->request, $item));
    }

    /**
     * @param Item[] $activeFilterItems
     * @return string
     */
    public function getClearUrl(array $activeFilterItems)
    {
        return $this->addBaseUrl(
            $this->getUrlStrategy()
            ->getClearUrl($this->request, $activeFilterItems)
        );
    }

    /**
     * @param array $activeFilterItems
     * @return string
     */
    public function getFilterUrl(array $activeFilterItems)
    {
        return $this->addBaseUrl(
            $this->getUrlStrategy()
            ->buildFilterUrl($this->request, $activeFilterItems)
        );
    }

    /**
     * @param ProductNavigationRequest $navigationRequest
     * @return void
     */
    public function apply(ProductNavigationRequest $navigationRequest)
    {
        $this->getFilterApplier()->apply($this->request, $navigationRequest);
    }

    /**
     * @param Item $item
     * @return string
     */
    public function getSliderUrl(Item $item)
    {
        return $this->addBaseUrl($this->getUrlStrategy()->getSliderUrl($this->request, $item));
    }

    public function addBaseUrl($url) // @phpstan-ignore-line
    {

        $baseUrl = $this->magentoUrl->getBaseUrl();
        //prevent double base urls
        $url = str_replace($baseUrl, '', $url);
        //remove slashes to prevent double slashes
        $baseUrl = rtrim($baseUrl, '/');
        $url = ltrim($url, '/');

        return $baseUrl . '/' . $url;
    }

    /**
     * @param Item $item
     * @return CategoryInterface
     * @throws NoSuchEntityException
     */
    protected function getCategoryFromItem(Item $item): CategoryInterface
    {
        $tweakwiseCategoryId = $item->getAttribute()->getAttributeId();
        $categoryId = $this->exportHelper->getStoreId($tweakwiseCategoryId);

        return $this->categoryRepository->get($categoryId);
    }
}
