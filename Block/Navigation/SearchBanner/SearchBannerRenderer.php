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

        if (!empty($banners['ContainerTop'])) {
            return $banners['ContainerTop'];
        }

        return [];
    }

    public function getListTopBanner()
    {
        $banners = $this->getSearchBanners();

        if (!empty($banners['ListTop'])) {
            return $banners['ListTop'];
        }

        return [];
    }

    public function getProductsTopBanner()
    {
        $banners = $this->getSearchBanners();

        if (!empty($banners['ProductsTop'])) {
            return $banners['ProductsTop'];
        }

        return [];
    }

    private function getSearchBanners()
    {
        $navigationContext = $this->getData('tweakwise_navigation_context')->getNavigationContext()->getContext();
        $result = [];

        if($navigationContext->showSearchBanners()) {
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
        }


        return $result;
    }
}
