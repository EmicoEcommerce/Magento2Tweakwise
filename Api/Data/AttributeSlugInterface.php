<?php

namespace Tweakwise\Magento2Tweakwise\Api\Data;

interface AttributeSlugInterface
{
    public const ATTRIBUTE = 'attribute';
    public const SLUG = 'slug';

    /**
     * @return string|null
     */
    public function getAttribute(): ?string;

    /**
     * @return string
     */
    public function getSlug(): string;

    /**
     * @return void
     */
    public function setSlug(string $slug): void;
}
