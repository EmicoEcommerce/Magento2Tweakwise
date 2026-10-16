<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Catalog\Product\Recommendation;

use Tweakwise\Magento2Tweakwise\Model\Catalog\Product\AbstractCollection;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Product\VisualBuilder;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\RecommendationsResponse;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Model\Enum\ItemType;
use Magento\Catalog\Model\Indexer\Product\Flat\State;
use Magento\Catalog\Model\Product\OptionFactory;
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
     * @var RecommendationsResponse
     */
    protected $response;

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
     * @param RecommendationsResponse $response
     * @param VisualBuilder $visualBuilder
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
        RecommendationsResponse $response,
        private readonly VisualBuilder $visualBuilder,
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
        $this->response = $response;
    }

    /**
     * {@inheritdoc}
     */
    protected function getProductIds()
    {
        return $this->response->getProductIds();
    }

    /**
     * @return $this|AbstractCollection|Collection
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();

        $this->addVisuals();

        return $this;
    }

    /**
     * @return void
     */
    protected function addVisuals(): void
    {
        if (!$this->config->isVisualsEnabled()) {
            return;
        }

        $position = 0;
        foreach ($this->response->getItems() as $item) {
            if ($item->getType() === ItemType::VISUAL->value) {
                $visual = $this->visualBuilder->build($item);

                // Not array_splice, because that renumbers the product id keys of the collection.
                $this->_items = array_slice($this->_items, 0, $position, true)
                    + ['visual_' . $visual->getId() => $visual]
                    + array_slice($this->_items, $position, null, true);
            }

            $position++;
        }
    }
}
