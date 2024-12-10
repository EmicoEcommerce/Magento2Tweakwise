<?php

namespace Tweakwise\Magento2Tweakwise\Model;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;

class PersonalMerchandisingConfig extends Config
{
    public function __construct(
        ScopeConfigInterface $config,
        Json $jsonSerializer,
        RequestInterface $request,
        State $state,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList,
        private CookieManagerInterface $cookieManager,
        private CookieMetadataFactory $cookieMetadataFactory
    ) {
        parent::__construct($config, $jsonSerializer, $request, $state,
            $configWriter, $cacheTypeList);
    }

    /**
     * @param Store|null $store
     * @return bool
     */
    public function isAnalyticsEnabled(Store $store = null)
    {
        return (bool)$this->getStoreConfig('tweakwise/personal_merchandising/analytics_enabled', $store);
    }

    public function getProfileKey()
    {
        $profileKey = $this->cookieManager->getCookie(
            $this->getPersonalMerchandisingCookieName(),
            null
        );

        if($this->isAnalyticsEnabled()) {
            if ($profileKey === null) {
                $profileKey = $this->generateProfileKey();
                $this->cookieManager->setPublicCookie(
                    $this->getPersonalMerchandisingCookieName(),
                    $profileKey,
                    $this->getCookieMetadata()
                );
            }
        }
        return $profileKey;
    }

    private function getCookieMetadata()
    {
        return $this->cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setDuration(86400)
            ->setPath('/')
            ->setSecure(true);
    }

    private function generateProfileKey()
    {
        return uniqid('', true);
    }
}
