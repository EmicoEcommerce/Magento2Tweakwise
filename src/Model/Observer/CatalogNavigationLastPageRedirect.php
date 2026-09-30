<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Tweakwise\Magento2Tweakwise\Model\Observer;

use Magento\Catalog\Model\Category;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Event\Observer;
use Magento\Framework\Registry;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext;
use Tweakwise\Magento2Tweakwise\Model\Config;

class CatalogNavigationLastPageRedirect extends CatalogLastPageRedirect
{
    /**
     * @param Config $config
     * @param NavigationContext $context
     * @param Context $actionContext
     * @param Registry $registry
     */
    public function __construct(
        Config $config,
        NavigationContext $context,
        Context $actionContext,
        private readonly Registry $registry
    ) {
        parent::__construct($config, $context, $actionContext);
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if (!$this->config->isLayeredEnabled()) {
            return;
        }

        $request = $this->context->getRequest();
        $category = $this->registry->registry('current_category');
        if ($category instanceof Category && !$request->hasParameter('tn_cid')) {
            $request->addCategoryFilter($category);
        }

        parent::execute($observer);
    }
}
