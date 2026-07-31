<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Analytics\Tag;

use Tweakwise\Magento2Tweakwise\Api\Data\TagInterface;

/**
 * Static marker tag for a page-impression event. Wired into any page's "data_layer" argument that
 * should fire one (see catalog_category_view.xml, catalogsearch_result_index.xml).
 */
class PageImpression implements TagInterface
{
    public function get(): string
    {
        return 'page_impression';
    }
}
