<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Client\Request\Recommendations;

use Magento\Framework\Registry;
use Magento\Store\Model\StoreManager;
use Tweakwise\Magento2Tweakwise\Exception\ApiException;
use Tweakwise\Magento2Tweakwise\Model\Client\Request;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\RecommendationsResponse;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;
use Tweakwise\Magento2Tweakwise\Model\Config;

class FeaturedRequest extends Request
{
    /**
     * {@inheritDoc}
     */
    protected $path = 'recommendations/featured';

    /**
     * @var int
     */
    protected $templateId;

    protected $registery;

    public function __construct(Helper $helper, StoreManager $storeManager, Registry $registry, Config $config)
    {
        $this->registery = $registry;
        parent::__construct($helper, $storeManager, $config);
    }

    /**
     * {@inheritDoc}
     */
    public function getResponseType()
    {
        return RecommendationsResponse::class;
    }

    /**
     * @return int|string
     */
    public function getTemplate()
    {
        return $this->templateId;
    }

    /**
     * @param int|string $templateId
     * @return $this
     */
    public function setTemplate($templateId)
    {
        if (!is_string($templateId)) {
            $templateId = (int) $templateId;
        }

        $this->templateId = $templateId;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPathSuffix()
    {
        if (!$this->templateId) {
            throw new ApiException('Featured products without template ID was requested.');
        }

        return  '/' . $this->templateId;
    }

    /**
     * @return int|null
     */
    public function getCurrentCategoryId()
    {
        $category = $this->registery->registry('current_category');

        if (!empty($category)) {
            return $category->getId();
        }

        return null;
    }

    /**
     * @return void
     */
    public function setCategory()
    {
        $categoryId = $this->getCurrentCategoryId();
        if (!empty($categoryId)) {
            $this->addCategoryFilter($categoryId);
        }
    }
}
