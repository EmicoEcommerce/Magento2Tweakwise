<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Block\Navigation\SearchBanner;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Psr\Log\LoggerInterface;
use Tweakwise\Magento2Tweakwise\Exception\TweakwiseExceptionInterface;
use Tweakwise\Magento2Tweakwise\Model\Config;

class SearchBannerRenderer extends Template
{
    private readonly Config $config;

    private readonly LoggerInterface $logger;

    /**
     * @param TemplateContext $context
     * @param array $data
     * @param Config|null $config
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        TemplateContext $context,
        array $data = [],
        ?Config $config = null,
        ?LoggerInterface $logger = null
    ) {
        $this->config = $config ?? ObjectManager::getInstance()->get(Config::class);
        $this->logger = $logger ?? ObjectManager::getInstance()->get(LoggerInterface::class);
        parent::__construct($context, $data);
    }

    /**
     * @return array|mixed
     */
    public function getContainerTopBanner()
    {
        $banners = $this->getSearchBanners();

        if (!empty($banners['ContainerTop'])) {
            return $banners['ContainerTop'];
        }

        return [];
    }

    /**
     * @return array|mixed
     */
    public function getListTopBanner()
    {
        $banners = $this->getSearchBanners();

        if (!empty($banners['ListTop'])) {
            return $banners['ListTop'];
        }

        return [];
    }

    /**
     * @return array|mixed
     */
    public function getProductsTopBanner()
    {
        $banners = $this->getSearchBanners();

        if (!empty($banners['ProductsTop'])) {
            return $banners['ProductsTop'];
        }

        return [];
    }

    /**
     * @return array
     */
    private function getSearchBanners()
    {
        if ($this->config->getTweakwiseExceptionThrown()) {
            return [];
        }

        $navigationContext = $this->getData('tweakwise_navigation_context')->getNavigationContext()->getContext();
        $result = [];
        $banners = [];

        if ($navigationContext->showSearchBanners()) {
            try {
                $response = $navigationContext->getResponse();
            } catch (TweakwiseExceptionInterface $e) {
                $this->logger->critical($e->getMessage());
                $this->config->setTweakwiseExceptionThrown();

                return [];
            }

            if (!empty($response)) {
                $banners = $response->getValue('searchbanners');
            }

            if (isset($banners['searchbanner'])) {
                $banners = $banners['searchbanner'];

                $result = [];
                //group by location
                if (is_array($banners)) {
                    foreach ($banners as $banner) {
                        $result[$banner['location']][] = $banner;
                    }
                }
            }
        }

        return $result;
    }
}
