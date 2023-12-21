<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Controller\Adminhtml\Export\Trigger;

use Exception;
use InvalidArgumentException;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

class Trigger extends Action
{
    /**
     * @var Scheduler
     */
    protected $scheduler;

    /**
     * Trigger constructor.
     * @param Action\Context $context
     */
    public function __construct(Action\Context $context)
    {
        parent::__construct($context);
    }

    /**
     * Schedule new export
     *
     * @return ResultInterface
     * @throws InvalidArgumentException
     */
    public function execute()
    {
        try {
            $this->exportTweakwiseSettings();
            $this->messageManager->addSuccessMessage('Export completed');
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage('Failed to export');
        }

        return $this->createRefererRedirect();
    }

    private function exportTweakwiseSettings()
    {
        var_dump($this->getTweakwiseSettings());
    }

    private function getTweakwiseSettings()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource      = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection    = $resource->getConnection();

        $sql = "SELECT `scope`, `scope_id`, `path`, `value`, `updated_at`
            FROM core_config_data
            WHERE path IN (
                'tweakwise/autocomplete/enabled',
                'tweakwise/autocomplete/in_current_category',
                'tweakwise/autocomplete/max_results',
                'tweakwise/autocomplete/show_parent_category',
                'tweakwise/autocomplete/show_products',
                'tweakwise/autocomplete/show_suggestions',
                'tweakwise/autocomplete/use_suggestions',
                'tweakwise/export/allow_cache_flush',
                'tweakwise/export/enabled',
                'tweakwise/export/exclude_child_attributes',
                'tweakwise/export/out_of_stock_children',
                'tweakwise/export/price_field',
                'tweakwise/export/real_time',
                'tweakwise/export/store_level_export_enabled',
                'tweakwise/export/validate',
                'tweakwise/layered/ajax_filters',
                'tweakwise/layered/default_link_renderer',
                'tweakwise/layered/enabled',
                'tweakwise/layered/form_filters',
                'tweakwise/layered/hide_single_option',
                'tweakwise/layered/query_filter_arguments',
                'tweakwise/layered/query_filter_type',
                'tweakwise/layered/url_strategy',
                'tweakwise/personal_merchandising/enabled',
                'tweakwise/recommendations/crosssell_enabled',
                'tweakwise/recommendations/crosssell_template',
                'tweakwise/recommendations/featured_enabled',
                'tweakwise/recommendations/limit_group_code_items',
                'tweakwise/recommendations/shoppingcart_crosssell_enabled',
                'tweakwise/recommendations/upsell_enabled',
                'tweakwise/recommendations/upsell_template',
                'tweakwise/search/enabled',
                'tweakwise/search/language',
                'tweakwise/search/searchbanner',
                'tweakwise/search/template',
                'tweakwise/seo/enabled',
                'tweakwise/seo/filter_values_whitelist',
                'tweakwise/seo/filter_whitelist',
                'tweakwise/seo/max_allowed_facets'
            )";

        $result = $connection->fetchAll($sql);

        return $result;
    }

    /**
     * @return ResultInterface
     * @throws InvalidArgumentException
     */
    protected function createRefererRedirect()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        $redirectUrl = $this->_redirect->getRefererUrl();
        if (!$redirectUrl) {
            $redirectUrl = $this->_url->getUrl('adminhtml');
        }
        $resultRedirect->setUrl($redirectUrl);

        return $resultRedirect;
    }
}
