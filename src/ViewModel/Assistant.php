<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\ViewModel;

use Exception;
use Magento\Catalog\Model\Category;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\JsonHexTag;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Tweakwise\Magento2Tweakwise\Exception\AssistantException;
use Tweakwise\Magento2Tweakwise\Model\Assistant\PageContextProvider;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext\CurrentContext;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductSearchRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\ProductNavigationResponse;
use Tweakwise\Magento2Tweakwise\Model\Config;

/**
 * Renders the AI Shopping Assistant above the layered navigation. Availability is derived from the navigation response
 * of the same page, so it is cached together with the filters.
 */
class Assistant implements ArgumentInterface
{
    public const QUOTA_REASONS = [
        AssistantException::REASON_QUOTA_REACHED,
        AssistantException::REASON_QUOTA_UNAVAILABLE,
    ];

    /**
     * @param Config $config
     * @param CurrentContext $currentContext
     * @param PageContextProvider $pageContextProvider
     * @param Registry $registry
     * @param StoreManagerInterface $storeManager
     * @param UrlInterface $url
     * @param JsonHexTag $jsonSerializer
     */
    public function __construct(
        private readonly Config $config,
        private readonly CurrentContext $currentContext,
        private readonly PageContextProvider $pageContextProvider,
        private readonly Registry $registry,
        private readonly StoreManagerInterface $storeManager,
        private readonly UrlInterface $url,
        private readonly JsonHexTag $jsonSerializer
    ) {
    }

    /**
     * @return bool
     */
    public function isAvailable(): bool
    {
        $response = $this->getNavigationResponse();
        if ($response === null) {
            return false;
        }

        return $response->isAssistantEnabled() || $this->getUnavailableReason() !== null;
    }

    /**
     * Quota reason reported by Tweakwise, the assistant is shown with a disabled composer in that case.
     *
     * @return string|null
     */
    public function getUnavailableReason(): ?string
    {
        $response = $this->getNavigationResponse();
        if ($response === null || $response->isAssistantEnabled()) {
            return null;
        }

        $reason = $response->getAssistantReason();

        return in_array($reason, self::QUOTA_REASONS, true) ? $reason : null;
    }

    /**
     * @param array $labels Translated texts used by the widget
     * @return string
     */
    public function getJsConfig(array $labels): string
    {
        $context = $this->pageContextProvider->getCurrentPageContext();
        $signedContext = $context->getSignedData();
        $signedContext['hash'] = $this->pageContextProvider->getHash($context);

        return (string)$this->jsonSerializer->serialize(
            [
                'Tweakwise_Magento2Tweakwise/js/assistant' => [
                    'endpoint' => $this->url->getUrl('tweakwise/ajax/assistant'),
                    'storageKey' => 'tw-assistant-' . $this->storeManager->getStore()->getCode(),
                    'context' => $signedContext,
                    'selectedFacets' => $this->pageContextProvider->getCurrentSelectedFacets(),
                    'searchTerm' => $context->getSearchTerm(),
                    'unavailableReason' => $this->getUnavailableReason(),
                    'labels' => $labels,
                ],
            ]
        );
    }

    /**
     * Response of the Tweakwise request of this page, null when Tweakwise does not handle the page.
     *
     * @return ProductNavigationResponse|null
     */
    private function getNavigationResponse(): ?ProductNavigationResponse
    {
        if (!$this->config->isAssistantEnabled() || $this->config->getTweakwiseExceptionThrown()) {
            return null;
        }

        try {
            $context = $this->currentContext->getContext();
            $isSearch = $context->getRequest() instanceof ProductSearchRequest;
            if (($isSearch && !$this->config->isSearchEnabled()) || (!$isSearch && !$this->config->isLayeredEnabled())) {
                return null;
            }

            if (!$isSearch && !$this->isTweakwiseCategory()) {
                return null;
            }

            $response = $context->getResponse();
        } catch (Exception $e) {
            return null;
        }

        return $this->config->getTweakwiseExceptionThrown() ? null : $response;
    }

    /**
     * @return bool
     */
    private function isTweakwiseCategory(): bool
    {
        $category = $this->registry->registry('current_category');
        if (!$category instanceof Category) {
            return true;
        }

        return (bool)$category->getIsAnchor() && $category->getDisplayMode() !== Category::DM_PAGE;
    }
}
