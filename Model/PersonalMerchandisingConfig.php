<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Tweakwise\Magento2Tweakwise\Model;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Store\Model\Store;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Math\Random;

class PersonalMerchandisingConfig extends Config
{
    /**
     * @var string|null
     */
    private ?string $profileKey = null;

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
     */
    public function isAnalyticsEnabled(?Store $store = null): bool
    {
        try {
            return (bool)$this->getStoreConfig(
                'tweakwise/general/analytics_enabled',
                $store
            );
        } catch (LocalizedException $e) {
            return false;
        }
    }

    /**
     * @return string|null
     */
    public function getProfileKey(): ?string
    {
        if (!$this->isAnalyticsEnabled()) {
            return null;
        }

        if ($this->profileKey) {
            return $this->profileKey;
        }

        $profileKey = $this->cookieManager->getCookie($this->getPersonalMerchandisingCookieName());

        if ($profileKey) {
            $this->profileKey = $profileKey;
            return $this->profileKey;
        }

        $this->profileKey = $this->generateProfileKey();
        try {
            $this->cookieManager->setPublicCookie(
                $this->getPersonalMerchandisingCookieName(),
                $this->profileKey,
                $this->getCookieMetadata()
            );
        } catch (InputException | CookieSizeLimitReachedException | FailureToSendException $e) {
            return null;
        }

        return $this->profileKey;
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
