<?php

namespace Tweakwise\Magento2Tweakwise\Api\Data;

interface AttributeSlugInterface
{
    public const ATTRIBUTE = 'attribute';
    public const SLUG = 'slug';
    public const STORE_ID = 'store_id';

    /**
     * @return string|null
     */
    public function getAttribute(): ?string;

    /**
     * @return string
     */
    public function getSlug(): string;

    /**
     * @param string $slug
     * @return void
     */
    public function setSlug(string $slug): void;

    /**
     * @return int
     */
    public function getStoreId(): int;

    /**
     * @param int $storeId
     * @return void
     */
    public function setStoreId(int $storeId): void;
}
