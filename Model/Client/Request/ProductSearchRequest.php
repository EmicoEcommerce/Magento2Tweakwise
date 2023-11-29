<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Client\Request;

use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;

/**
 * Class ProductSearchRequest
 * @package Tweakwise\Magento2Tweakwise\Model\Client\Request
 */
class ProductSearchRequest extends ProductNavigationRequest implements SearchRequestInterface
{
    use SearchRequestTrait;

    /**
     * {@inheritDoc}
     */
    protected $path = 'navigation-search';
}
