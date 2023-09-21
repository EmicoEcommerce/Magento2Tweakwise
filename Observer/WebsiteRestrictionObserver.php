<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Observer;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Allow Tweakwise feed to be accessed when website restrctions apply
 */
class WebsiteRestrictionObserver implements ObserverInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param RequestInterface $request
     */
    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        if ($this->request->getFullActionName() == 'tweakwise_feed_export') {
            $result = $observer->getResult();
            $result->setData('should_proceed', false);
        }
    }
}
