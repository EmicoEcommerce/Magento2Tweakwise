<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Block\Cart\Crosssell;

use Closure;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Block\Cart\Crosssell;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Tweakwise\Magento2Tweakwise\Block\Catalog\Product\ProductList\AbstractRecommendationPlugin;
use Tweakwise\Magento2Tweakwise\Exception\ApiException;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Product\Recommendation\Context;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\Recommendations\ProductRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\RequestFactory;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Magento\Checkout\Model\Session;
use Tweakwise\Magento2Tweakwise\Model\Config\TemplateFinder;

class Plugin extends AbstractRecommendationPlugin
{
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var ProductRepositoryInterface|null
     */
    private $productRepository;


    private $lastAddedProduct;

    public function __construct(
        Config $config,
        Registry $registry,
        Context $context,
        TemplateFinder $templateFinder,
        Session $checkoutSession,
        ?ProductRepositoryInterface $productRepository = null
    )
    {
        $this->productRepository = $productRepository;
        $this->checkoutSession  = $checkoutSession;
        $this->productRepository = $productRepository
            ?? ObjectManager::getInstance()->get(ProductRepositoryInterface::class);

        parent::__construct($config, $registry, $context, $templateFinder);
    }

    /**
     * @return string
     */
    protected function getType()
    {
        return Config::RECCOMENDATION_TYPE_SHOPPINGCART;
    }

    /**
     * Get the last added product before running the default getItems because the last added products gets deleted from the session
     *
     * @param Closure $proceed
     * @return array
     */
    public function aroundGetItems(Crosssell $crosssell, Closure $proceed)
    {
        if (!$this->config->isRecommendationsEnabled(Config::RECCOMENDATION_TYPE_SHOPPINGCART)) {
            return $proceed();
        }

        $this->lastAddedProduct = $this->getLastAddedProduct();

        return $proceed();
    }

    public function afterGetItems(Crosssell $crosssell, $result)
    {
        if (!$this->config->isRecommendationsEnabled(Config::RECCOMENDATION_TYPE_SHOPPINGCART)) {
            return $result;
        }

        $ninProductIds = $crosssell->getData('_cart_product_ids');

        if ($ninProductIds) {

            if ($this->lastAddedProduct) {

                $requestFactory = new RequestFactory(ObjectManager::getInstance(), ProductRequest::class);
                $request = $requestFactory->create();
                $request->setProduct($this->lastAddedProduct);

                if (!$this->templateFinder->forProduct($this->lastAddedProduct, $this->getType())) {
                    return $result;
                }

                $request->setTemplate($this->templateFinder->forProduct($this->lastAddedProduct, $this->getType()));
                $this->context->setRequest($request);

                try {
                    $collection = $this->getCollection();
                } catch (ApiException $e) {
                    return $result;
                }

                if (!empty($ninProductIds)) {
                    $collection = $this->removeCartItems($collection, $ninProductIds);
                }

                foreach ($collection as $item) {
                    $ninProductIds[] = $item->getId();
                    $items[] = $item;
                }
            }

            if (count($items) < 4) {

            }
        }
        $crosssell->setData('items', $items);
        return $items;
    }

    /**
     * Retrieve just added to cart product object
     *
     * @return ProductInterface|null
     */
    private function getLastAddedProduct(): ?ProductInterface
    {
        $product = null;
        $productId = $this->_getLastAddedProductId();
        if ($productId) {
            try {
                $product = $this->productRepository->getById($productId);
            } catch (NoSuchEntityException $e) {
                $product = null;
            }
        }
        return $product;
    }

    /**
     * Get last product ID that was added to cart and remove this information from session
     *
     * @return int
     * @codeCoverageIgnore
     */
    protected function _getLastAddedProductId()
    {
        return $this->checkoutSession->getLastAddedProductId(false);
    }

    /**
     * @param $collection
     * @param $filteredProducts
     * @return void
     */
    protected function removeCartItems($collection, $cartItems)
    {
            $items = $collection->getItems();

            if(!empty($cartItems)) {
                foreach ($cartItems as $cartItem) {
                    unset($items[$cartItem]);
                }
            }
            return $items;
    }
}
