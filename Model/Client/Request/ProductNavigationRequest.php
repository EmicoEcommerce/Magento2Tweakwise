<?php

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Client\Request;

use Tweakwise\Magento2Tweakwise\Model\Client\Request;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\ProductNavigationResponse;

class ProductNavigationRequest extends Request
{
    /**
     * Maximum number of products returned for one request
     */
    private const MAX_PRODUCTS = 1000;

    /**
     * Sort order directions
     */
    private const SORT_ASC = 'ASC';
    private const SORT_DESC = 'DESC';

    /**
     * @var string
     */
    protected $path = 'navigation';

    /**
     * @var array
     */
    protected $hiddenParameters = [];

    /**
     * {@inheritdoc}
     */
    public function getResponseType()
    {
        return ProductNavigationResponse::class;
    }

    /**
     * @param string $attribute
     * @param string $value
     * @return $this
     */
    public function addAttributeFilter(string $attribute, $value)
    {
        $this->addParameter('tn_fk_' . $attribute, trim($value));
        return $this;
    }

    /**
     * @param string $attribute
     * @param int $value
     */
    public function addHiddenParameter(string $attribute, $value)
    {
        $this->hiddenParameters[] = sprintf('%s=%s', $attribute, $value);
        $this->setParameter('tn_parameters', implode('&', $this->hiddenParameters));
    }

    /**
     * @param string $sort
     * @return $this
     */
    public function setOrder($sort)
    {
        $this->setParameter('tn_sort', $sort);
        return $this;
    }

    /**
     * @param int $page
     * @return $this
     */
    public function setPage($page)
    {
        $page = (int) $page;
        $page = max(1, $page);

        $this->setParameter('tn_p', $page);
        return $this;
    }

    /**
     * @param int $limit
     * @return $this
     */
    public function setLimit($limit)
    {
        if ($limit === 'all') {
            $limit = self::MAX_PRODUCTS;
        }

        $limit = min($limit, self::MAX_PRODUCTS);
        $this->setParameter('tn_ps', $limit);
        return $this;
    }

    /**
     * @param int|null $templateId
     * @return $this
     */
    public function setTemplateId($templateId)
    {
        $this->setParameter('tn_ft', $templateId);
        return $this;
    }

    /**
     * @param int|null $templateId
     * @return $this
     */
    public function setSortTemplateId($templateId)
    {
        $this->setParameter('tn_st', $templateId);
        return $this;
    }

    public function setBuilderTemplateId($templateId)
    {
        $this->setParameter('tn_b', $templateId);
        return $this;
    }
}
