<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\AjaxResultInitializer;

use Magento\Framework\App\Request\Http as MagentoHttpRequest;
use Magento\Framework\App\RequestInterface;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext;

abstract class AbstractCountInitializer implements CountInitializerInterface
{
    private const IGNORED_PARAMS = [
        '__tw_ajax_type',
        '__tw_object_id',
        '__tw_original_url',
        '__tw_hash',
        'p',
        'product_list_order',
        'product_list_limit',
        'product_list_mode',
        'product_list_dir',
        'q',
        '_',
        'categorie',
    ];

    protected function applyFilterParams(RequestInterface $request, NavigationContext $navigationContext): void
    {
        if (!$request instanceof MagentoHttpRequest) {
            return;
        }

        $navigationRequest = $navigationContext->getRequest();

        foreach ($request->getQuery() as $attribute => $value) {
            if (in_array(strtolower((string) $attribute), self::IGNORED_PARAMS, true)) {
                continue;
            }

            $values = is_array($value) ? $value : [$value];
            foreach ($values as $singleValue) {
                if ($singleValue === '' || $singleValue === null) {
                    continue;
                }

                $navigationRequest->addAttributeFilter((string) $attribute, $singleValue);
            }
        }
    }
}
