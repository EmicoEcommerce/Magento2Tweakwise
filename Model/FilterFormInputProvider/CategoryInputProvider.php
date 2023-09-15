<?php

namespace Tweakwise\Magento2Tweakwise\Model\FilterFormInputProvider;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\Data\CategoryInterfaceFactory;
use Magento\Catalog\Model\Category;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url;
use Magento\Framework\App\Request\Http as MagentoHttpRequest;


class CategoryInputProvider implements FilterFormInputProviderInterface
{
    public const TYPE = 'category';

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var CategoryInterfaceFactory
     */
    protected $categoryFactory;

    /**
     * @var UrlInterface
     */
    protected $url;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ToolbarInputProvider
     */
    protected $toolbarInputProvider;

    /**
     * @var HashInputProvider
     */
    protected $hashInputProvider;

    /**
     * @var Url
     */
    protected $layerUrl;

    /**
     * @var MagentoHttpRequest
     */
    protected $request;

    /**
     * CategoryParameterProvider constructor.
     * @param UrlInterface $url
     * @param Registry $registry
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     * @param CategoryRepositoryInterface $categoryRepository
     * @param CategoryInterfaceFactory $categoryFactory
     * @param ToolbarInputProvider $toolbarInputProvider
     */
    public function __construct(
        UrlInterface $url,
        Registry $registry,
        Config $config,
        StoreManagerInterface $storeManager,
        CategoryRepositoryInterface $categoryRepository,
        CategoryInterfaceFactory $categoryFactory,
        ToolbarInputProvider $toolbarInputProvider,
        HashInputProvider $hashInputProvider,
        Url $layerUrl,
        MagentoHttpRequest $request
    ) {
        $this->url = $url;
        $this->registry = $registry;
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->categoryRepository = $categoryRepository;
        $this->categoryFactory = $categoryFactory;
        $this->toolbarInputProvider = $toolbarInputProvider;
        $this->hashInputProvider = $hashInputProvider;
        $this->layerUrl = $layerUrl;
        $this->request = $request;
    }

    /**
     * @inheritDoc
     */
    public function getFilterFormInput(): array
    {
        if (!$this->config->isAjaxFilters()) {
            return [];
        }

        $input = [
            '__tw_ajax_type' => self::TYPE,
            '__tw_object_id' => $this->getCategoryId(),
            '__tw_original_url' => $this->getOriginalUrl(),
        ];

        $input['__tw_hash'] = $this->hashInputProvider->getHash($input);

        return array_merge($input, $this->toolbarInputProvider->getFilterFormInput());
    }

    /**
     * @return string
     */
    public function getOriginalUrl() : string
    {
        return $this->layerUrl->getUrlStrategy()->getOriginalUrl($this->request);
    }

    /**
     * @return int|null
     */
    public function getCategoryId()
    {
        return (int)$this->getCategory()->getId() ?: null;
    }

    /**
     * @return CategoryInterface|Category
     */
    protected function getCategory()
    {
        if ($currentCategory = $this->registry->registry('current_category')) {
            return $currentCategory;
        }

        try {
            $rootCategory = $this->storeManager->getStore()->getRootCategoryId();
        } catch (NoSuchEntityException $exception) {
            $rootCategory = 2;
        }

        try {
            return $this->categoryRepository->get($rootCategory);
        } catch (NoSuchEntityException $exception) {
            return $this->categoryFactory->create();
        }
    }
}
