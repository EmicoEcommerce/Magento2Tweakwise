<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\ViewModel;

use Magento\Bundle\Model\Product\Type as Bundle;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class PersonalMerchandisingAnalytics
 *
 * ViewModel for personal merchandising analytics.
 */
class PersonalMerchandisingAnalytics implements ArgumentInterface
{
    /**
     * @param Config $tweakwiseConfig
     * @param Helper $helper
     * @param StoreManagerInterface $storeManager
     * @param RequestInterface $request
     * @param Json $jsonSerializer
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        private readonly Config $tweakwiseConfig,
        private readonly Helper $helper,
        public readonly StoreManagerInterface $storeManager,
        private readonly RequestInterface $request,
        private readonly Json $jsonSerializer,
        private readonly ProductRepositoryInterface $productRepository,
    ) {
    }

    /**
     * Get the product key.
     *
     * @return string
     */
    public function getProductKey(): string
    {
        $productId = $this->request->getParam('id');

        if (!$productId) {
            return '0';
        }

        if (!$this->tweakwiseConfig->isGroupedProductsEnabled()) {
            return $productId;
        }

        return (string)$this->getGroupedProductId((int)$productId);
    }

    /**
     * @param int $productId
     * @return int|string
     */
    public function getGroupedProductId(int $productId): int|string
    {
        try {
            /** @var Product $product */
            $product = $this->productRepository->getById($productId);
        } catch (NoSuchEntityException $e) {
            return $productId;
        }

        if ($product->getTypeId() === Type::TYPE_SIMPLE) {
            return $productId;
        }

        $associatedProducts = $this->getAssociatedProducts($product);
        if (empty($associatedProducts)) {
            return $productId;
        }

        $firstAssociatedProduct = reset($associatedProducts);
        $simpleId = $firstAssociatedProduct->getId();
        if ($simpleId === 0 || $simpleId === $productId) {
            return $productId;
        }

        return $simpleId . Helper::GROUP_CODE_DELIMITER . $productId;
    }

    /**
     * Get the API URL.
     *
     * @return string
     */
    public function getApiUrl(): string
    {
        return 'https://navigator-analytics.tweakwise.com/api/';
    }

    /**
     * Get the instance key.
     *
     * @return string
     */
    public function getInstanceKey(): string
    {
        return $this->tweakwiseConfig->getGeneralAuthenticationKey();
    }

    /**
     * Get the cookie name.
     *
     * @return string
     */
    public function getCookieName(): string
    {
        return $this->tweakwiseConfig->getPersonalMerchandisingCookieName();
    }

    /**
     * Get the search query.
     *
     * @return string
     */
    public function getSearchQuery(): string
    {
        return $this->request->getParam('q') ?? '';
    }

    /**
     * Get the Tweakwise request ID.
     *
     * @return string
     */
    public function getTwRequestId(): string
    {
        return $this->request->getParam('tw_request_id') ?? '';
    }

    /**
     * @param array $analyticsTypes
     * @param string $requestId
     * @return string
     */
    public function getEventsData(array $analyticsTypes, string $requestId): string
    {
        $map = [
            'product'       => fn() => $this->getProductKey(),
            'search'        => fn() => $this->getSearchQuery(),
            'session_start' => fn() => 'session_start',
            'page_impression' => fn() => 'page_impression',
        ];

        $eventsData = array_map(
            fn($type) => [
                'type'  => $type,
                'value' => ($map[$type] ?? fn() => '')(),
                'requestId'  => $requestId,
            ],
            $analyticsTypes
        );

        return $this->jsonSerializer->serialize($eventsData);
    }

    /**
     * @param Product $product
     * @return array
     */
    protected function getAssociatedProducts(Product $product): array
    {
        $typeInstance = $product->getTypeInstance();
        return match (true) {
            $typeInstance instanceof Configurable => $typeInstance->getUsedProducts($product),
            $typeInstance instanceof Grouped => $typeInstance->getAssociatedProducts($product),
            $typeInstance instanceof Bundle => $typeInstance->getSelectionsCollection(
                $typeInstance->getOptionsIds($product),
                $product
            )->getItems(),
            default => [],
        };
    }
}
