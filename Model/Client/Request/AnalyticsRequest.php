<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Client\Request;

use Tweakwise\Magento2Tweakwise\Model\Client\Request;

class AnalyticsRequest extends Request
{
    /**
     * @var string
     */
    protected $path = '';

    /**
     * @var string
     */
    protected string $apiUrl = 'https://navigator-analytics.tweakwise.com/api';

    /**
     * @return bool
     */
    public function isPostRequest(): bool
    {
        return true;
    }

    /**
     * @return string
     */
    public function getApiurl(): string
    {
        return $this->apiUrl;
    }

    /**
     * @return void
     */
    public function setProfileKey(string $profileKey): void // @phpstan-ignore-line
    {
        $this->setParameter('ProfileKey', $profileKey);
    }

    /**
     * @return void
     */
    public function setPath($path): void // @phpstan-ignore-line
    {
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getProfileKey(): string
    {
        // @phpstan-ignore-next-line
        $profileKey = $this->getCookie($this->config->getPersonalMerchandisingCookieName());
        if (!$profileKey) {
            // @phpstan-ignore-next-line
            $profileKey = $this->generateProfileKey();
            // @phpstan-ignore-next-line
            $this->setCookie('profileKey', $profileKey);
        }

        return $profileKey;
    }
}
