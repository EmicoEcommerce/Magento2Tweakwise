<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Analytics\Tag;

use Tweakwise\Magento2Tweakwise\Api\Data\TagInterface;

class PageImpression implements TagInterface
{
    public function get(): string
    {
        return 'page_impression';
    }
}
