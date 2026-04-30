<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Tweakwise\Magento2Tweakwise\Api\Data;

interface VisualInterface
{
    public const IMAGE_URL = 'image';
    public const URL = 'url';
    public const COLSPAN = 'colspan';
    public const ROWSPAN = 'rowspan';

    /**
     * @return string
     */
    public function getImageUrl(): string;

    /**
     * @param string $imageUrl
     * @return self
     */
    public function setImageUrl(string $imageUrl): self;

    /**
     * @return string
     */
    public function getUrl(): string;

    /**
     * @param string $url
     * @return self
     */
    public function setUrl(string $url): self;

    /**
     * @return int|null
     */
    public function getColspan(): ?int;

    /**
     * @param int $colspan
     * @return self
     */
    public function setColspan(int $colspan): self;

    /**
     * @return int|null
     */
    public function getRowspan(): ?int;

    /**
     * @param int $rowspan
     * @return self
     */
    public function setRowspan(int $rowspan): self;

    /**
     * Returns an inline CSS style string for grid-column/grid-row spanning,
     * e.g. "grid-column: span 2;grid-row: span 2;" — empty string when not applicable.
     *
     * @return string
     */
    public function getGridStyle(): string;
}
