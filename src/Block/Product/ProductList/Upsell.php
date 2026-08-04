<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Block\Product\ProductList;

use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Block\Product\ProductList\Upsell as MagentoUpsell;
use Magento\Catalog\Model\Product\Visibility as ProductVisibility;
use Magento\Checkout\Model\ResourceModel\Cart as CartResourceModel;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\Manager;

class Upsell extends MagentoUpsell
{
    /**
     * @param Context $context
     * @param CartResourceModel $checkoutCart
     * @param ProductVisibility $catalogProductVisibility
     * @param CheckoutSession $checkoutSession
     * @param Manager $moduleManager
     * @param Linked $linked
     * @param array $data
     */
    public function __construct(
        Context $context,
        CartResourceModel $checkoutCart,
        ProductVisibility $catalogProductVisibility,
        CheckoutSession $checkoutSession,
        Manager $moduleManager,
        private readonly Linked $linked,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $checkoutCart,
            $catalogProductVisibility,
            $checkoutSession,
            $moduleManager,
            $data
        );
    }

    /**
     * @return int|bool|null
     */
    protected function getCacheLifetime()
    {
        $linkedResult = $this->linked->getCacheLifetime($this);
        if ($linkedResult) {
            return $linkedResult;
        }

        return parent::getCacheLifetime();
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        $linkedResult = $this->linked->getTemplate();
        if ($linkedResult) {
            return $linkedResult;
        }

        return parent::getTemplate();
    }

    /**
     * @return Upsell
     * @throws NoSuchEntityException
     */
    protected function _prepareData()
    {
        $this->linked->prepareData($this);

        return parent::_prepareData();
    }

    /**
     * @param string $route
     * @param array $params
     * @return string
     */
    public function getUrl($route = '', $params = [])
    {
        $linkedResult = $this->linked->getUrl($this, $route, $params);
        if ($linkedResult) {
            $params = $linkedResult;
        }

        return parent::getUrl($route, $params);
    }
}
