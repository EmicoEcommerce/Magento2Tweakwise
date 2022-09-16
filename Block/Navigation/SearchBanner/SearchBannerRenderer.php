<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Block\Navigation\SearchBanner;

use Magento\Framework\View\Element\Template;

class SearchBannerRenderer extends Template
{
    public function getContainerTopBanner()
    {
        $banners = $this->getSearchBanners();
        return $banners['ContainerTop'];
    }

    public function getListTopBanner()
    {
        $banners = $this->getSearchBanners();
        return $banners['ListTop'];
    }

    public function getProductsTopBanner()
    {
        $banners = $this->getSearchBanners();
        return $banners['ProductsTop'];
    }

    private function getSearchBanners()
    {
        $navigationContext = $this->getData('tweakwise_navigation_context')->getNavigationContext();
        $response = $navigationContext->getResponse();
        $banners = $response->getValue('searchbanners');
        $banners = $banners['searchbanner'];

        $result = [];
        //group by location
        if (is_array($banners)) {
            foreach ($banners as $banner) {
                $result[$banner['location']][] = $banner;
            }
        }

        return $result;
    }
}
