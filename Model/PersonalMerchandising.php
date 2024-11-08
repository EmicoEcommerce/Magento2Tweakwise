<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model;

use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Tweakwise\Magento2Tweakwise\Model\Config;

class PersonalMerchandising
{
    public function __construct(
        public CookieManagerInterface $cookieManager,
        public CookieMetadataFactory $cookieMetadataFactory,
        public Config $config,
    ) {
    }

    public function setOrGetProfileKey()
    {
        $cookieName = $this->config->getPersonalMerchandisingCookieName();

        if (empty($cookieName)) {
            $cookieName = 'tw_analytics';
        }

        $profileKey = $this->cookieManager->getCookie(
            $cookieName,
            null
        );

        //if there is no profile key, generate an profileKey and save it in the cookie
        if (!$profileKey) {
            $profileKey = $this->generateProfileKey();

            $metadata = $this->cookieMetadataFactory
                ->createPublicCookieMetadata()
                ->setDuration(86400) // Cookie will expire after one day (86400 seconds)
                ->setSecure(true) //the cookie is only available under HTTPS
                ->setPath('/')// The cookie will be available to all pages and subdirectories within the /subfolder path
                ->setHttpOnly(false);

            $this->cookieManager->setPublicCookie(
                $cookieName,
                $profileKey,
                $metadata
            );
        }

        return $profileKey;
    }

    private function generateProfileKey(): string
    {
        return sha1(random_bytes(18));
    }
}
