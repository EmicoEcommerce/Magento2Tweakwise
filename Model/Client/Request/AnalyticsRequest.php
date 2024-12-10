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

class AnalyticsRequest extends Request
{
    /**
     * @var string
     */
    protected $path = '';

    protected $apiUrl = 'https://navigator-analytics.tweakwise.com/api';

    /**
     * @return string
     */
    public function isPostRequest()
    {
        return true;
    }

    /**
     * @return string
     */
    public function getApiurl()
    {
        return $this->apiUrl;
    }

    /**
     * @return string
     */
    public function setProfileKey(string $profileKey)
    {
        $this->setParameter('ProfileKey', $profileKey);
    }

    /**
     * @return string
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getProfileKey()
    {
        $profileKey = $this->getCookie($this->config->getPersonalMerchandisingCookieName());
        if (!$profileKey) {
            $profileKey = $this->generateProfileKey();
            $this->setCookie('profileKey', $profileKey);
        }

        return $profileKey;
    }
}
