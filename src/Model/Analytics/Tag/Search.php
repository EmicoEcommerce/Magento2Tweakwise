<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Analytics\Tag;

use Magento\Framework\App\RequestInterface;
use Tweakwise\Magento2Tweakwise\Api\Data\TagInterface;

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
