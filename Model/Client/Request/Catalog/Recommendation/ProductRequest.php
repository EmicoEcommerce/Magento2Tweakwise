<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Client\Request\Catalog\Recommendation;

use Tweakwise\Magento2Tweakwise\Model\Client\Request;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\Catalog\Recommendation\OptionsResponse;

class ProductRequest extends Request
{
    /**
     * {@inheritDoc}
     */
    protected $path = 'catalog/recommendation/product';

    /**
     * @return string
     */
    public function getResponseType()
    {
        return OptionsResponse::class;
    }
}
