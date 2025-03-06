<?php

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Catalog\Product;

use Exception;
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
        AdapterInterface $connection = null
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
        $this->addVisuals();

        return $this;
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

        foreach ($response->getItems() as $item) {
            if ($item->getValue('type') !== ItemType::VISUAL->value) {
                continue;
            }

            /** @var VisualInterface $visual */
            $visual = $this->visualFactory->create();
            $visual->setId($item->getValue('itemno'));
            $visual->setImageUrl($item->getImage());
            $visual->setUrl($item->getUrl());
            $itemPosition = array_search($item, $response->getItems());

            array_splice($this->_items, $itemPosition, 0, [$visual]);
        }
    }

    public function overwriteImage(): void
    {
        try {
            $response = $this->navigationContext->getResponse();
        } catch (Exception $e) {
            return;
        }

        $counter = 0;
        foreach ($response->getItems() as $item) {
            if ($item->getValue('type') !== ItemType::PRODUCT->value) {
                $counter++;
                continue;
            }

            if (!empty($item->getImage())) {
                $this->_items[$counter]->setData('image', $item->getImage());
                $this->_items[$counter]->setData('small_image',
                    $item->getImage());
                $this->_items[$counter]->setData('thumbnail',
                    $item->getImage());
                $counter++;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getProductIds()
    {
        $response = $this->navigationContext->getResponse();
        return $response->getProductIds() ?? [];
    }
}
