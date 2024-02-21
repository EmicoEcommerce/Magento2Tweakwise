<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Tweakwise\Magento2Tweakwise\Block\Catalog\Product\ProductList\Toolbar;


use Closure;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext\CurrentContext;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductSearchRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\SortFieldType;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Magento\Catalog\Block\Product\ProductList\Toolbar;

class Plugin
{
    /**
     * @var CurrentContext
     */
    protected $context;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Plugin constructor.
     *
     * @param Config $config
     * @param CurrentContext $context
     * @param StockConfigurationInterface $stockConfiguration
     * @param LoggerInterface $logger
     */
    public function __construct(
        Config $config,
        CurrentContext $context,
        StockConfigurationInterface $stockConfiguration,
        LoggerInterface $logger
    ) {
        $this->context = $context;
        $this->config = $config;
        $this->stockConfiguration = $stockConfiguration;
        $this->logger = $logger;
    }

    /**
     * @param Toolbar $subject
     * @param Closure $proceed
     * @return array
     */
    public function aroundGetAvailableOrders(Toolbar $subject, Closure $proceed)
    {
        if (!$this->config->isLayeredEnabled()) {
            if (!$this->config->isSearchEnabled() || !($this->context->getRequest() instanceof ProductSearchRequest)) {
                return $proceed();
            }
        }

        //page is search and search is not enabled
        if ((!$this->config->isSearchEnabled()) && ($this->context->getRequest() instanceof ProductSearchRequest)) {
            return $proceed();
        }

        if (!$this->context->getResponse()) {
            return $proceed();
        }

        /** @var SortFieldType[] $sortFields */
        $sortFields = $this->context->getResponse()->getProperties()->getSortFields();

        $result = [];
        foreach ($sortFields as $field) {
            $result[$field->getUrlValue()] = $field->getDisplayTitle();
        }
        return $result;
    }

    /**
     * @param Toolbar $subject
     * @param string $result
     * @return false|string
     */
    public function afterGetWidgetOptionsJson(Toolbar $subject, string $result)
    {
        if (!$this->config->isAjaxFilters()) {
            return $result;
        }

        $options = json_decode($result, true);
        $options['productListToolbarForm']['ajaxFilters'] = true;

        return json_encode($options);
    }
    /**
     * Update toolbar count if store is in single source mode
     * Used in the commerce version
     *
     * @param Toolbar $subject
     * @param int $result
     * @return int
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws LocalizedException
     */
    public function aroundGetTotalNum(Toolbar $subject, callable $proceed): int
    {
        if (!$this->config->isLayeredEnabled()) {
            return $proceed();
        }

        if ($this->stockConfiguration->isShowOutOfStock()) {
            try {
                $response = $this->context->getResponse();
                $result = $response->getProperties()->getNumberOfItems();
            } catch (\Exception $e) {
                $this->logger->critical($e->getMessage());
            }
        }

        if (!isset($result))
        {
            return $proceed();
        }

        return $result;
    }
}
