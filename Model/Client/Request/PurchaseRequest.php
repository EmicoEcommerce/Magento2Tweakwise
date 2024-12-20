<?php

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Client\Request;

use Tweakwise\Magento2Tweakwise\Model\Client\Request;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\FacetResponse;

class PurchaseRequest extends Request
{
    /**
     * @var string
     */
    protected $path = 'purchase';

    /**
     * @var array
     */
    protected $productKeys;

    protected $apiUrl = 'https://navigator-analytics.tweakwise.com/api';

    /**
     * @return true
     */
    public function isPostRequest(): bool
    {
        return true;
    }

    /**
     * @return mixed|string
     */
    public function getApiurl(): string
    {
        return $this->apiUrl;
    }

    /**
     * @param string $profileKey
     *
     * @return void
     */
    public function setProfileKey(string $profileKey): void
    {
        $this->setParameter('ProfileKey', $profileKey);
    }
}
