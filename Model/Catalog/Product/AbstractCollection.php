<?php

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Catalog\Product;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\DB\Select;

abstract class AbstractCollection extends ProductCollection
{
    /**
     * @return int[]
     */
    abstract protected function getProductIds();

    /**
     * @return $this
     */
    protected function applyEntityIdFilter()
    {
        $productIds = $this->getProductIds();
        if (count($productIds) === 0) {
            // Result should be none make sure we dont load any products
            $this->addFieldToFilter('entity_id', ['null' => true]);
        } else {
            $this->addFieldToFilter('entity_id', ['in' => $productIds]);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function clearFilters()
    {
        $select = $this->getSelect();
        $select->setPart(Select::WHERE, []);
        $this->_pageSize = null;
        $this->_curPage = null;
        return $this;
    }

    /**
     * @return $this
     */
    protected function fixProductOrder()
    {
        $productIds = $this->getProductIds();

        $result = [];
        foreach ($productIds as $productId) {
            if (isset($this->_items[$productId])) {
                $result[$productId] = $this->_items[$productId];
            }
        }

        $this->_items = $result;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function _beforeLoad()
    {
        parent::_beforeLoad();

        $this->clearFilters();
        $this->applyEntityIdFilter();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();

        $this->fixProductOrder();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize()
    {
        $this->load();
        return parent::getSize();
    }
}
