<?php

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Client;

use Tweakwise\Magento2TweakwiseExport\Model\Helper;
use Magento\Catalog\Model\Category;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManager;
use Tweakwise\Magento2Tweakwise\Model\Config;

class Request
{
    /**
     * A list of parameters that should be ignored
     * when adding a separator to the value
     */
    private const IGNORE_SEPARATOR_PARAMETERS = ['tn_fk_final_price', 'tn_fk_p'];

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $groupedPath;

    /**
     * @var array
     */
    protected $parameters = [];

    /**
     * @var StoreManager
     */
    protected $storeManager;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var Config
     */
    protected $config;

    /**
     * Request constructor.
     *
     * @param Helper $helper
     * @param StoreManager $storeManager
     * @param Config $config
     */
    public function __construct(Helper $helper, StoreManager $storeManager, Config $config)
    {
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        $this->config = $config;
    }

    /**
     * @return string
     */
    public function getResponseType()
    {
        return Response::class;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        if ($this->config->isGroupedProductsEnabled($this->storeManager->getStore()) && !empty($this->groupedPath)) {
            return $this->groupedPath;
        }

        return $this->path;
    }

    /**
     * @param string $path
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = (string) $path;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPathSuffix()
    {
        return null;
    }

    /**
     * @param string $parameter
     * @param string $value
     * @param string $separator
     * @return $this
     */
    public function addParameter($parameter, $value, $separator = '|')
    {
        if (isset($this->parameters[$parameter])) {
            if ($value == null) {
                unset($this->parameters[$parameter]);
            } else {
                if (
                    (!in_array($parameter, self::IGNORE_SEPARATOR_PARAMETERS)) &&
                    ($this->parameters[$parameter] !== $value)
                ) {
                    $this->parameters[$parameter] = $this->parameters[$parameter] . $separator . $value;
                }
            }
        } elseif ($value !== null) {
            $this->parameters[$parameter] = (string) $value;
        }

        return $this;
    }

    /**
     * @param array $parameters
     * @return $this
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * @param string $parameter
     * @param string|null $value
     * @return $this
     */
    public function setParameter(string $parameter, string $value = null)
    {
        if ($value === null) {
            unset($this->parameters[$parameter]);
        } else {
            $value = strip_tags($value);
            $this->parameters[$parameter] = (string) $value;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param string $parameter
     * @return mixed|null
     */
    public function getParameter(string $parameter)
    {
        if (isset($this->parameters[$parameter])) {
            return $this->parameters[$parameter];
        }

        return null;
    }

    /**
     * @param string $parameter
     * @return bool
     */
    public function hasParameter($parameter)
    {
        return isset($this->parameters[$parameter]);
    }

    /**
     * @param Category|int $category
     * @return $this
     */
    public function addCategoryFilter($category)
    {
        $ids = [];
        if (is_numeric($category)) {
            $ids[] = $category;
            return $this->addCategoryPathFilter($ids);
        }

        if ($this->config->isCategoryViewDefault()) {
            /** @var Category $category */
            $parentIsRoot = $category;
            while ($this->isCategoryRoot($parentIsRoot) === false) {
                $ids[] = (int)$parentIsRoot->getParentId();
                $parentIsRoot = $parentIsRoot->getParentCategory();
            }

            $ids[] = (int)$parentIsRoot->getParentId();

            $ids = array_reverse($ids);
        } else {
            /** @var Category $category */
            $parentIsRoot = $this->isCategoryRoot($category);

            if (!$parentIsRoot) {
                // Parent category is added so that category menu is retained on the deepest category level
                $ids[] = (int) $category->getParentId();
            }
        }

        $ids[] = (int) $category->getId();

        return $this->addCategoryPathFilter($ids);
    }

    private function isCategoryRoot($category)
    {
        return  in_array(
            (int) $category->getParentId(),
            [
                0,
                1,
                (int) $category->getStore()->getRootCategoryId()
            ],
            true
        );
    }

    /**
     * @param array $categoryIds
     * @return $this
     */
    public function addCategoryPathFilter(array $categoryIds)
    {
        $categoryIds = array_map('intval', $categoryIds);
        $storeId = (int) $this->getStoreId();
        $tweakwiseIdMapper = function (int $categoryId) use ($storeId) {
            //don't add prefix for root category 1.
            if ($categoryId === 1) {
                return '';
            }

            return $this->helper->getTweakwiseId($storeId, $categoryId);
        };
        $tweakwiseIds = array_map($tweakwiseIdMapper, $categoryIds);
        $this->setParameter('tn_cid', implode('-', $tweakwiseIds));
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCategoryPathFilter()
    {
        $categoryPath = $this->getParameter('tn_cid');
        if (!$categoryPath) {
            return null;
        }

        if (!is_string($categoryPath)) {
            return null;
        }

        $magentoIdMapper = function (int $tweakwiseCategoryId) {
            return $this->helper->getStoreId($tweakwiseCategoryId);
        };

        $categoryPath = array_map($magentoIdMapper, explode('-', $categoryPath));
        return implode('-', $categoryPath);
    }

    /**
     * @param string|int $storeId
     * @return void
     */
    public function setStore($storeId)
    {
        $this->storeManager->setCurrentStore((int) $storeId);
    }

    /**
     * @return array|StoreInterface[]
     */
    public function getStores()
    {
        return $this->storeManager->getStores();
    }

    /**
     * @return StoreInterface|null
     */
    protected function getStore()
    {
        try {
            return $this->storeManager->getStore();
        } catch (NoSuchEntityException $e) {
            // Chose to not implement a good catch as this will not happen in practice.
            return null;
        }
    }

    /**
     * @return int
     */
    protected function getStoreId()
    {
        $store = $this->getStore();
        if ($store instanceof StoreInterface) {
            return $store->getId();
        }

        return null;
    }

    /**
     * @param string $profileKey
     * @return $this
     */
    public function setProfileKey(string $profileKey)
    {
        $this->setParameter('tn_profilekey', $profileKey);
        return $this;
    }

    /**
     * @return string|null
     */
    public function setParameterArray(string $parameter, array $value): Request
    {
        $this->parameters[$parameter] = $value;
        return $this;
    }

    /**
     * @return string|null
     */
    public function isPostRequest(): bool
    {
        return false;
    }
}
