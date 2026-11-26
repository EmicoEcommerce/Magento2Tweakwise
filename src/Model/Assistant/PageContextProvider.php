<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Assistant;

use Magento\Framework\App\Request\Http as MagentoHttpRequest;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext\CurrentContext;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductSearchRequest;
use Tweakwise\Magento2Tweakwise\Model\FilterFormInputProvider\HashInputProvider;

/**
 * Creates the signed page context that is rendered with the assistant and validated by the proxy controller.
 */
class PageContextProvider
{
    private const ATTRIBUTE_FILTER_PREFIX = 'tn_fk_';

    /**
     * @param CurrentContext $currentContext
     * @param Registry $registry
     * @param StoreManagerInterface $storeManager
     * @param UrlInterface $url
     * @param Url $layerUrl
     * @param MagentoHttpRequest $request
     * @param HashInputProvider $hashInputProvider
     */
    public function __construct(
        private readonly CurrentContext $currentContext,
        private readonly Registry $registry,
        private readonly StoreManagerInterface $storeManager,
        private readonly UrlInterface $url,
        private readonly Url $layerUrl,
        private readonly MagentoHttpRequest $request,
        private readonly HashInputProvider $hashInputProvider
    ) {
    }

    /**
     * @return PageContext
     */
    public function getCurrentPageContext(): PageContext
    {
        $navigationRequest = $this->currentContext->getRequest();
        $searchTerm = $navigationRequest->getParameter('tn_q');
        $searchTerm = is_string($searchTerm) && $searchTerm !== '' ? $searchTerm : null;

        if ($navigationRequest instanceof ProductSearchRequest) {
            return new PageContext(
                PageContext::TYPE_SEARCH,
                $this->getRootCategoryId(),
                $this->url->getUrl('catalogsearch/result'),
                $searchTerm
            );
        }

        $category = $this->registry->registry('current_category');
        $categoryId = $category ? (int)$category->getId() : $this->getRootCategoryId();

        return new PageContext(PageContext::TYPE_CATEGORY, $categoryId, $this->getOriginalUrl(), $searchTerm);
    }

    /**
     * Facets selected on the current page, in the format of the assistant request.
     *
     * @return array<int, array{attribute: string, value: string}>
     */
    public function getCurrentSelectedFacets(): array
    {
        $selectedFacets = [];
        foreach ($this->currentContext->getRequest()->getParameters() as $name => $value) {
            if (!is_string($name) || !str_starts_with($name, self::ATTRIBUTE_FILTER_PREFIX) || !is_scalar($value)) {
                continue;
            }

            $selectedFacets[] = [
                'attribute' => substr($name, strlen(self::ATTRIBUTE_FILTER_PREFIX)),
                'value' => (string)$value,
            ];
        }

        return $selectedFacets;
    }

    /**
     * @param PageContext $context
     * @return string
     */
    public function getHash(PageContext $context): string
    {
        return $this->hashInputProvider->getHash(['__tw_assistant_context' => $context->getSignedData()]);
    }

    /**
     * Restores a signed context posted by the storefront, null when it was modified.
     *
     * @param mixed $data
     * @param string|null $searchTerm
     * @return PageContext|null
     */
    public function fromSignedData($data, ?string $searchTerm = null): ?PageContext
    {
        if (
            !is_array($data)
            || !in_array($data['type'] ?? null, [PageContext::TYPE_CATEGORY, PageContext::TYPE_SEARCH], true)
            || !is_numeric($data['categoryId'] ?? null)
            || !is_string($data['baseUrl'] ?? null)
            || !is_string($data['hash'] ?? null)
        ) {
            return null;
        }

        $context = new PageContext((string)$data['type'], (int)$data['categoryId'], $data['baseUrl'], $searchTerm);

        return hash_equals($this->getHash($context), $data['hash']) ? $context : null;
    }

    /**
     * @return int
     */
    private function getRootCategoryId(): int
    {
        // @phpstan-ignore-next-line
        return (int)$this->storeManager->getStore()->getRootCategoryId();
    }

    /**
     * Current page URL without filters, sort or paging.
     *
     * @return string
     */
    private function getOriginalUrl(): string
    {
        $url = (string)$this->layerUrl->getUrlStrategy()->getOriginalUrl($this->request);
        $url = (string)strtok($url, '?');
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }

        return $this->url->getDirectUrl(ltrim($url, '/'));
    }
}
