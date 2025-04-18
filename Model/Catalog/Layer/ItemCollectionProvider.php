<?php

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Catalog\Layer;

use Tweakwise\Magento2Tweakwise\Exception\TweakwiseExceptionInterface;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Product\CollectionFactory;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductSearchRequest;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2TweakwiseExport\Model\Logger;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Layer\ItemCollectionProviderInterface;

class ItemCollectionProvider implements ItemCollectionProviderInterface
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Logger
     */
    protected $log;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var ItemCollectionProviderInterface
     */
    protected $originalProvider;

    /**
     * @var NavigationContext
     */
    protected $navigationContext;

    /**
     * Proxy constructor.
     *
     * @param Config $config
     * @param Logger $log
     * @param ItemCollectionProviderInterface $originalProvider
     * @param CollectionFactory $collectionFactory
     * @param NavigationContext $navigationContext
     */
    public function __construct(
        Config $config,
        Logger $log,
        ItemCollectionProviderInterface $originalProvider,
        CollectionFactory $collectionFactory,
        NavigationContext $navigationContext
    ) {
        $this->config = $config;
        $this->log = $log;
        $this->collectionFactory = $collectionFactory;
        $this->originalProvider = $originalProvider;
        $this->navigationContext = $navigationContext;
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(Category $category)
    {
        if (!$this->config->isLayeredEnabled()) {
            if (
                !$this->config->isSearchEnabled() ||
                !($this->navigationContext->getRequest() instanceof ProductSearchRequest)
            ) {
                return $this->originalProvider->getCollection($category);
            }
        }

        if (
            !$this->config->isSearchEnabled() &&
            ($this->navigationContext->getRequest() instanceof ProductSearchRequest)
        ) {
            return $this->originalProvider->getCollection($category);
        }

        //no api response
        if ($this->config->getTweakwiseExceptionTrown()) {
            return $this->originalProvider->getCollection($category);
        }

        try {
            if (empty($this->navigationContext->getResponse()->getProductIds())) {
                $collection = $this->collectionFactory
                    ->create(['navigationContext' => $this->navigationContext->resetPagination()])
                ;
            } else {
                $collection = $this->collectionFactory->create(['navigationContext' => $this->navigationContext]);
            }

            return $collection;
        } catch (TweakwiseExceptionInterface $e) {
            $this->log->critical($e);
            $this->config->setTweakwiseExceptionThrown();

            return $this->originalProvider->getCollection($category);
        }
    }
}
