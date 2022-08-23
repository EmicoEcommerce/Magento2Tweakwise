<?php

namespace Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url;

use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Filter\Item;
use Tweakwise\Magento2TweakwiseExport\Model\Helper as ExportHelper;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class StrategyHelper
{
    /**
     * @var ExportHelper
     */
    private $exportHelper;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * StrategyHelper constructor.
     * @param ExportHelper $exportHelper
     * @param CategoryRepositoryInterface $categoryRepository
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ExportHelper $exportHelper,
        CategoryRepositoryInterface $categoryRepository,
        StoreManagerInterface $storeManager
    ) {
        $this->exportHelper = $exportHelper;
        $this->categoryRepository = $categoryRepository;
        $this->storeManager = $storeManager;
    }

    /**
     * @param Item $item
     * @return CategoryInterface
     * @throws NoSuchEntityException
     */
    public function getCategoryFromItem(Item $item): CategoryInterface
    {
        $tweakwiseCategoryId = $item->getAttribute()->getAttributeId();
        $categoryId = $this->exportHelper->getStoreId($tweakwiseCategoryId);

        try {
            $storeId = $this->storeManager->getStore()->getId();
        } catch (NoSuchEntityException $exception) {
            $storeId = null;
        }

        return $this->categoryRepository->get($categoryId, $storeId);
    }
}
