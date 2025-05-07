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
use Tweakwise\Magento2Tweakwise\Model\Config;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Store\Model\Store;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Math\Random;

class PersonalMerchandisingConfig extends Config
{
    /**
     * Constructor.
     *
     * @param ScopeConfigInterface   $config
     * @param Json                   $jsonSerializer
     * @param RequestInterface       $request
     * @param State                  $state
     * @param WriterInterface        $configWriter
     * @param TypeListInterface      $cacheTypeList
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory  $cookieMetadataFactory
     * @param Random                 $mathRandom
     *
     * @throws LocalizedException
     */
    public function __construct(
        ScopeConfigInterface $config,
        Json $jsonSerializer,
        RequestInterface $request,
        State $state,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList,
        private readonly CookieManagerInterface $cookieManager,
        private readonly CookieMetadataFactory $cookieMetadataFactory,
        private readonly Random $mathRandom
    ) {
        parent::__construct($config, $jsonSerializer, $request, $state, $configWriter, $cacheTypeList);
    }

    /**
     * @param Store|null $store
     * @return bool
     * @throws LocalizedException
     */
    public function isAnalyticsEnabled(Store $store = null): bool
    {
        return (bool)$this->getStoreConfig('tweakwise/personal_merchandising/analytics_enabled', $store);
    }

    /**
     * @return string|null
     */
    public function getProfileKey(): ?string
    {
        $profileKey = $this->cookieManager->getCookie(
            $this->getPersonalMerchandisingCookieName(),
            null
        );

        if ($this->isAnalyticsEnabled()) {
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

   /**
    * @return PublicCookieMetadata
    */
    private function getCookieMetadata(): PublicCookieMetadata
    {
        return $this->cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setDuration(86400)
            ->setPath('/')
            ->setSecure(true);
    }

    /**
     * @return string
     */
    private function generateProfileKey(): string
    {
        return $this->mathRandom->getUniqueHash();
    }
}
