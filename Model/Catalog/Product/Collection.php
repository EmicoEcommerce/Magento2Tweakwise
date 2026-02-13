<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Catalog\Product;

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\LocalizedException;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\ItemType as ClientItemType;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Model\Enum\ItemType;
use Tweakwise\Magento2Tweakwise\Api\Data\VisualInterface;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductSearchRequest;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Indexer\Product\Flat\State;
use Magento\Catalog\Model\Product\OptionFactory;
use Tweakwise\Magento2Tweakwise\Model\VisualFactory;
use Magento\Catalog\Model\ResourceModel\Helper as CatalogResourceHelper;
use Magento\Catalog\Model\ResourceModel\Url;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Customer\Model\Session;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\EntityFactory as EavEntityFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactory as CollectionEntityFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Module\Manager;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Validator\UniversalFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Collection extends AbstractCollection
{
    /**
     * @var NavigationContext
     */
    protected $navigationContext;

    /**
     * @param CollectionEntityFactory $entityFactory
     * @param LoggerInterface $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param ManagerInterface $eventManager
     * @param EavConfig $eavConfig
     * @param ResourceConnection $resource
     * @param EavEntityFactory $eavEntityFactory
     * @param CatalogResourceHelper $resourceHelper
     * @param UniversalFactory $universalFactory
     * @param StoreManagerInterface $storeManager
     * @param Manager $moduleManager
     * @param State $catalogProductFlatState
     * @param ScopeConfigInterface $scopeConfig
     * @param OptionFactory $productOptionFactory
     * @param Url $catalogUrl
     * @param TimezoneInterface $localeDate
     * @param Session $customerSession
     * @param DateTime $dateTime
     * @param GroupManagementInterface $groupManagement
     * @param NavigationContext $navigationContext
     * @param VisualFactory $visualFactory
     * @param Config $config
     * @param AdapterInterface|null $connection
     * @SuppressWarnings("PHPMD.ExcessiveParameterList")
     */
    public function __construct(
        CollectionEntityFactory $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        EavConfig $eavConfig,
        ResourceConnection $resource,
        EavEntityFactory $eavEntityFactory,
        CatalogResourceHelper $resourceHelper,
        UniversalFactory $universalFactory,
        StoreManagerInterface $storeManager,
        Manager $moduleManager,
        State $catalogProductFlatState,
        ScopeConfigInterface $scopeConfig,
        OptionFactory $productOptionFactory,
        Url $catalogUrl,
        TimezoneInterface $localeDate,
        Session $customerSession,
        DateTime $dateTime,
        GroupManagementInterface $groupManagement,
        NavigationContext $navigationContext,
        private readonly VisualFactory $visualFactory,
        private readonly Config $config,
        ?AdapterInterface $connection = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $eavConfig,
            $resource,
            $eavEntityFactory,
            $resourceHelper,
            $universalFactory,
            $storeManager,
            $moduleManager,
            $catalogProductFlatState,
            $scopeConfig,
            $productOptionFactory,
            $catalogUrl,
            $localeDate,
            $customerSession,
            $dateTime,
            $groupManagement,
            $connection
        );

        $this->navigationContext = $navigationContext;
    }

    /**
     * @param Category $category
     * @return $this
     */
    public function addCategoryFilter(Category $category)
    {
        $this->navigationContext->getRequest()->addCategoryFilter($category);
        return $this;
    }

    /**
     * @param string $query
     * @return $this
     */
    public function addSearchFilter(string $query)
    {
        $request = $this->navigationContext->getRequest();
        if ($request instanceof ProductSearchRequest) {
            $request->setSearch($query);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function applyCollectionSizeValues()
    {
        $response = $this->navigationContext->getResponse();
        // @phpstan-ignore-next-line
        $properties = $response->getProperties();

        $this->_pageSize = $properties->getPageSize();
        $this->_curPage = $properties->getCurrentPage();
        $this->_totalRecords = $properties->getNumberOfItems();
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();

        $this->applyCollectionSizeValues();
        $this->enrichProducts();
        $this->addVisuals();

        return $this;
    }

    /**
     * @return AbstractCollection
     * @throws LocalizedException
     */
    protected function enrichProducts(): AbstractCollection
    {
        $productData = $this->getProductData();
        if (!$productData) {
            return $this;
        }

        $isGroupedProductsEnabled = $this->config->isGroupedProductsEnabled();
        foreach ($productData as $productId => $data) {
            if (!isset($this->_items[$productId])) {
                continue;
            }

            $product = $this->_items[$productId];
            if (!$product instanceof ProductInterface) {
                continue;
            }

            $this->applyVisualProperties($product, $data);

            if (
                !$isGroupedProductsEnabled ||
                $product->getTypeId() === Type::TYPE_SIMPLE
            ) {
                continue;
            }

            $this->applyTweakwiseId($product, $data);

            if (empty($data[ClientItemType::IMAGE]) || $product->getTypeId() !== Configurable::TYPE_CODE) {
                continue;
            }

            $this->applyProductImages($product, $data);
        }

        return $this;
    }

    /**
     * @param ProductInterface $product
     * @param array $extraProductData
     * @return void
     */
    protected function applyVisualProperties(ProductInterface $product, array $extraProductData): void
    {
        if (!$product instanceof Product) {
            return;
        }

        $product->setData(ClientItemType::COLSPAN, $extraProductData[ClientItemType::COLSPAN]);
        $product->setData(ClientItemType::ROWSPAN, $extraProductData[ClientItemType::ROWSPAN]);
    }

    /**
     * @param ProductInterface $product
     * @param array $extraProductData
     * @return void
     */
    protected function applyTweakwiseId(ProductInterface $product, array $extraProductData): void
    {
        if (!$product instanceof Product || empty($extraProductData[ClientItemType::TWEAKWISE_ID])) {
            return;
        }

        $product->setData(ClientItemType::TWEAKWISE_ID, $extraProductData[ClientItemType::TWEAKWISE_ID]);
    }

    /**
     * @param ProductInterface $product
     * @param array $extraProductData
     * @return void
     */
    protected function applyProductImages(ProductInterface $product, array $extraProductData): void
    {
        if (!$product instanceof Product) {
            return;
        }

        $product->setData('image', $extraProductData[ClientItemType::IMAGE]);
        $product->setData('small_image', $extraProductData[ClientItemType::IMAGE]);
        $product->setData('thumbnail', $extraProductData[ClientItemType::IMAGE]);
    }

    /**
     * @return void
     */
    protected function addVisuals(): void
    {
        try {
            $response = $this->navigationContext->getResponse();
        } catch (Exception $e) {
            return;
        }

        // @phpstan-ignore-next-line
        foreach ($response->getItems() as $item) {
            if ($item->getType() !== ItemType::VISUAL->value) {
                continue;
            }

            /** @var VisualInterface $visual */
            $visual = $this->visualFactory->create();
            // @phpstan-ignore-next-line
            $visual->setId($item->getId());
            $visual->setImageUrl($item->getImage());
            $visual->setUrl($item->getUrl());

            $colspan = $item->getColspan();
            if ($colspan) {
                $visual->setColspan($colspan);
            }
            $rowspan = $item->getRowspan();
            if ($rowspan) {
                $visual->setRowspan($rowspan);
            }

            // @phpstan-ignore-next-line
            $itemPosition = array_search($item, $response->getItems());

            // @phpstan-ignore-next-line
            array_splice($this->_items, $itemPosition, 0, [$visual]);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getProductIds()
    {
        $response = $this->navigationContext->getResponse();
        // @phpstan-ignore-next-line
        return $response->getProductIds() ?? [];
    }

    /**
     * @return array
     */
    protected function getProductData(): array
    {
        try {
            $response = $this->navigationContext->getResponse();
        } catch (Exception $e) {
            return [];
        }

        // @phpstan-ignore-next-line
        return $response->getProductData() ?? [];
    }
}
