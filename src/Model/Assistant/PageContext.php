<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Assistant;

/**
 * Page the assistant is used on. Type, category and base URL are signed when rendered, so the proxy controller can
 * trust them.
 */
class PageContext
{
    public const TYPE_CATEGORY = 'category';
    public const TYPE_SEARCH = 'search';

    /**
     * @param string $type
     * @param int $categoryId Magento category id, the store root category on search pages
     * @param string $baseUrl Page URL without filters
     * @param string|null $searchTerm Current search term
     */
    public function __construct(
        private readonly string $type,
        private readonly int $categoryId,
        private readonly string $baseUrl,
        private readonly ?string $searchTerm = null
    ) {
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function isSearch(): bool
    {
        return $this->type === self::TYPE_SEARCH;
    }

    /**
     * @return int
     */
    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    /**
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * @return string|null
     */
    public function getSearchTerm(): ?string
    {
        return $this->searchTerm;
    }

    /**
     * Values covered by the context hash.
     *
     * @return array<string, int|string>
     */
    public function getSignedData(): array
    {
        return [
            'type' => $this->type,
            'categoryId' => $this->categoryId,
            'baseUrl' => $this->baseUrl,
        ];
    }
}
