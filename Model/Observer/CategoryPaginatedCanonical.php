<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Observer;

use Magento\Catalog\Block\Category\View as CategoryView;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Asset\GroupedCollection;
use Magento\Framework\View\Page\Config as PageConfig;
use Tweakwise\Magento2Tweakwise\Model\Config;

class CategoryPaginatedCanonical implements ObserverInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly PageConfig $pageConfig,
        private readonly RequestInterface $request
    ) {
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer): void
    {
        if (!$this->config->isPaginatedCanonicalEnabled()) {
            return;
        }

        $block = $observer->getData('block');
        if (!$block instanceof CategoryView) {
            return;
        }

        $page = (int) $this->request->getParam('p');
        if ($page < 2) {
            return;
        }

        $assetCollection = $this->pageConfig->getAssetCollection();
        $canonicalGroup = $assetCollection->getGroupByContentType('canonical');
        if (!$canonicalGroup) {
            return;
        }

        $canonicals = $canonicalGroup->getAll();
        $existingCanonical = array_key_first($canonicals);
        if ($existingCanonical === null) {
            return;
        }

        $canonicalUrl = $this->appendPageParam($existingCanonical, $page);
        $this->removeCanonicals($assetCollection, $canonicals);
        $this->pageConfig->addRemotePageAsset(
            $canonicalUrl,
            'canonical',
            ['attributes' => ['rel' => 'canonical']]
        );
    }

    /**
     * @param string $url
     * @param int $page
     * @return string
     */
    private function appendPageParam(string $url, int $page): string
    {
        $urlParts = parse_url($url);
        $query = [];
        if (isset($urlParts['query'])) {
            parse_str($urlParts['query'], $query);
        }

        $query['p'] = $page;

        return (isset($urlParts['scheme']) ? $urlParts['scheme'] . '://' : '')
            . ($urlParts['host'] ?? '')
            . ($urlParts['path'] ?? '')
            . '?' . http_build_query($query);
    }

    /**
     * @param GroupedCollection $assetCollection
     * @param array $canonicals
     */
    private function removeCanonicals(GroupedCollection $assetCollection, array $canonicals): void
    {
        foreach (array_keys($canonicals) as $canonicalUrl) {
            $assetCollection->remove($canonicalUrl);
        }
    }
}
