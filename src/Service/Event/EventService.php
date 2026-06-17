<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Service\Event;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Math\Random;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Psr\Log\LoggerInterface;

class EventService
{
    private const TWEAKWISE_SESSION_KEY_COOKIE_NAME = 'tw_session_key';

    /**
     * @var string|null
     */
    private ?string $sessionKey = null;

    /**
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param LoggerInterface $logger
     * @param Random $mathRandom
     */
    public function __construct(
        private readonly CookieManagerInterface $cookieManager,
        private readonly CookieMetadataFactory $cookieMetadataFactory,
        private readonly LoggerInterface $logger,
        private readonly Random $mathRandom,
    ) {
    }

    /**
     * @return string
     */
    public function getSessionKey(): string
    {
        if ($this->sessionKey === null) {
            $this->sessionKey = $this->cookieManager->getCookie(self::TWEAKWISE_SESSION_KEY_COOKIE_NAME);
        }

        return $this->sessionKey ?? '';
    }

    /**
     * @return void
     */
    protected function setTweakwiseSessionKeyCookie(): void
    {
        try {
            $this->sessionKey = $this->mathRandom->getUniqueHash();
            $this->cookieManager->setPublicCookie(
                self::TWEAKWISE_SESSION_KEY_COOKIE_NAME,
                $this->sessionKey,
                $this->getCookieMetaData()
            );
        } catch (InputException | CookieSizeLimitReachedException | FailureToSendException | LocalizedException $e) {
            $this->logger->error(
                sprintf('Could not set %s cookie', self::TWEAKWISE_SESSION_KEY_COOKIE_NAME),
                ['message' => $e->getMessage()]
            );
        }
    }

    /**
     * @return PublicCookieMetadata
     */
    protected function getCookieMetaData(): PublicCookieMetadata
    {
        return $this->cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setPath('/')
            ->setSecure(true);
    }
}
