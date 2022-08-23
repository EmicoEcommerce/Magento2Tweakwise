<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext;

use Tweakwise\Magento2Tweakwise\Exception\RuntimeException;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductNavigationRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\ProductNavigationResponse;

class CurrentContext
{
    /**
     * @var NavigationContext
     */
    protected $context;

    /**
     * @param NavigationContext $context
     */
    public function setContext(NavigationContext $context)
    {
        $this->context = $context;
    }

    /**
     * @return NavigationContext
     */
    public function getContext()
    {
        if (!$this->context) {
            throw new RuntimeException(sprintf('Navigation context not set, initialize a version of %s first.', NavigationContext::class));
        }
        return $this->context;
    }

    /**
     * @return ProductNavigationRequest
     */
    public function getRequest()
    {
        return $this->getContext()->getRequest();
    }

    /**
     * @return ProductNavigationResponse
     */
    public function getResponse()
    {
        return $this->getContext()->getResponse();
    }
}
