<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Catalog\Product\Recommendation;

use Magento\Framework\Stdlib\CookieManagerInterface;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\Recommendations\FeaturedRequest;
use Tweakwise\Magento2Tweakwise\Model\Config;

/**
 * Adds the personal merchandising profile key of the visitor to a recommendation request when it is active.
 */
class ProfileKeyApplier
{
    /**
     * @param Config $config
     * @param CookieManagerInterface $cookieManager
     */
    public function __construct(
        private readonly Config $config,
        private readonly CookieManagerInterface $cookieManager
    ) {
    }

    /**
     * @param FeaturedRequest $request
     * @return void
     */
    public function apply(FeaturedRequest $request): void
    {
        if (!$this->config->isPersonalMerchandisingActive()) {
            return;
        }

        $profileKey = $this->cookieManager->getCookie(
            $this->config->getPersonalMerchandisingCookieName(),
            null
        );

        if (!$profileKey) {
            return;
        }

        $request->setProfileKey($profileKey);
    }
}
