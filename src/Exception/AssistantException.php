<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Exception;

use Throwable;

/**
 * Failure of an AI Shopping Assistant request. Kept separate from regular API failures so it never disables
 * Tweakwise navigation for the rest of the page request.
 */
class AssistantException extends ApiException
{
    public const REASON_QUOTA_REACHED = 'quota-reached';
    public const REASON_QUOTA_UNAVAILABLE = 'quota-unavailable';

    /**
     * @param string $message
     * @param int $statusCode HTTP status code of the assistant response, 0 when no response was received
     * @param string $reference Error reference that is logged and returned to the shopper
     * @param string|null $reason One of the REASON_* constants, when the failure has a known reason
     * @param Throwable|null $previous
     */
    public function __construct(
        string $message,
        int $statusCode,
        private readonly string $reference,
        private readonly ?string $reason = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    /**
     * @return string
     */
    public function getReference(): string
    {
        return $this->reference;
    }

    /**
     * @return string|null
     */
    public function getReason(): ?string
    {
        return $this->reason;
    }
}
