<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Tweakwise\Magento2Tweakwise\Model\Observer;

use Magento\Framework\Event\Observer;

class CatalogSearchLastPageRedirect extends CatalogLastPageRedirect
{
    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if (!$this->config->isSearchEnabled()) {
            return;
        }

        parent::execute($observer);
    }
}
