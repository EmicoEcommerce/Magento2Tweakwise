<?php

namespace Tweakwise\Magento2Tweakwise\Model\Autocomplete;

use Tweakwise\Magento2Tweakwise\Model\Autocomplete\DataProvider\ProductItemFactory;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\AutocompleteProductResponseInterface;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Layer\Category\CollectionFilter;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Search\Model\Autocomplete\ItemInterface;
use Magento\Search\Model\Query;
use Magento\Search\Model\QueryFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Config as CatalogConfig;

class DataProviderHelper
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var QueryFactory
     */
    protected $queryFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var HttpRequest
     */
    protected $request;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var CollectionFilter
     */
    protected $collectionFilter;

    /**
     * @var ProductItemFactory
     */
    protected $productItemFactory;

    /**
     * @var CatalogConfig
     */
    protected $catalogConfig;

    /**
     * AutocompleteDataProvider constructor
     * @param Config $config
     * @param QueryFactory $queryFactory
     * @param StoreManagerInterface $storeManager
     * @param CategoryRepositoryInterface $categoryRepository
     * @param HttpRequest $request
     * @param ProductCollectionFactory $productCollectionFactory
     * @param CollectionFilter $collectionFilter
     * @param ProductItemFactory $productItemFactory
     */
    public function __construct(
        Config $config,
        QueryFactory $queryFactory,
        StoreManagerInterface $storeManager,
        CategoryRepositoryInterface $categoryRepository,
        HttpRequest $request,
        ProductCollectionFactory $productCollectionFactory,
        CollectionFilter $collectionFilter,
        ProductItemFactory $productItemFactory,
        CatalogConfig $catalogConfig
    ) {
        $this->config = $config;
        $this->queryFactory = $queryFactory;
        $this->storeManager = $storeManager;
        $this->categoryRepository = $categoryRepository;
        $this->request = $request;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->collectionFilter = $collectionFilter;
        $this->productItemFactory = $productItemFactory;
        $this->catalogConfig = $catalogConfig;
    }

    /**
     * @return Query|mixed|string|null
     */
    public function getQuery()
    {
        /** @var Query $query */
        $query = $this->queryFactory->get();

        return $query->getQueryText();
    }

    /**
     * @return Category
     * @noinspection PhpIncompatibleReturnTypeInspection
     * phpcs:disable Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
     * phpcs:disable Generic.CodeAnalysis.EmptyStatement.DetectedCatch
     * @SuppressWarnings(PHPMD.EmptyCatchBlock)
     */
    public function getCategory()
    {
        $categoryId = (int)$this->request->getParam('cid');
        if ($categoryId && $this->config->isAutocompleteStayInCategory()) {
            try {
                return $this->categoryRepository->get($categoryId);
            } catch (NoSuchEntityException $e) {
            }
        }

        $store = $this->storeManager->getStore();
        $categoryId = $store->getRootCategoryId();
        return $this->categoryRepository->get($categoryId);
    }

    /**
     * @param AutocompleteProductResponseInterface $response
     * @return ItemInterface[]
     * @throws LocalizedException
     */
    public function getProductItems(AutocompleteProductResponseInterface $response)
    {
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->setStore($this->storeManager->getStore());
        $productCollection->addAttributeToFilter('entity_id', ['in' => $response->getProductIds()]);
        $productCollection->addFieldToFilter(
            'visibility',
            [
            'in' => [
            Visibility::VISIBILITY_BOTH,
            Visibility::VISIBILITY_IN_SEARCH
            ]
            ]
        );

        $category = $this->getCategory();

        $productCollection->addAttributeToSelect($this->catalogConfig->getProductAttributes())
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addUrlRewrite($category->getId());

        $productCollection->addCategoryFilter($category);

        $result = [];
        foreach ($response->getProductData() as $item) {
            $product = $productCollection->getItemById($item['id']);

            if (!$product) {
                continue;
            }

            $product->setData('tweakwise_price', $item['tweakwise_price']);
            $product->setData('tweakwise_final_price', $item['tweakwise_final_price']);

            $result[] = $this->productItemFactory->create(['product' => $product]);
        }

        return $result;
    }
}
