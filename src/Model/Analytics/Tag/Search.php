<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Analytics\Tag;

use Magento\Framework\App\RequestInterface;
use Tweakwise\Magento2Tweakwise\Api\Data\TagInterface;

/**
 * The current search query. Wired only into catalogsearch_result_index.xml's "data_layer" argument.
 */
class Search implements TagInterface
{
    public function __construct(
        private readonly RequestInterface $request,
    ) {
    }

    public function get(): string
    {
        return $this->request->getParam('q') ?? '';
    }
}
