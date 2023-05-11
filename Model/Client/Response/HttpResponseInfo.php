<?php

namespace Tweakwise\Magento2Tweakwise\Model\Client\Response;

class HttpResponseInfo
{
    /**
     * @var int $statusCode
     */
    private int $statusCode = 0;

    /**
     * @var bool $isTimedOut
     */
    private bool $isTimedOut = false;

    /**
     * @var bool $isNetworkError
     */
    private bool $isNetworkError = false;

    /**
     * @var string $error
     */
    private string $error;

    /**
     * @return bool
     */
    public function isSuccess() : bool
    {;
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * @return bool
     */
    public function isRetryable() : bool
    {
        if ($this->statusCode >= 200 && $this->statusCode < 300)
            return false;

        if ($this->statusCode >= 400 && $this->statusCode < 500)
            return false;

        if ($this->statusCode == 500)
            return false;

        return $this->isNetworkError;
    }

    /**
     * @param bool $isTimedOut
     */
    public function setIsTimedOut(bool $isTimedOut): void
    {
        $this->isTimedOut = $isTimedOut;
    }

    /**
     * @param bool $isNetworkError
     */
    public function setIsNetworkError(bool $isNetworkError): void
    {
        $this->isNetworkError = $isNetworkError;
    }

    /**
     * @param mixed $statusCode
     */
    public function setStatusCode(int $statusCode): void
    {
        $this->statusCode = $statusCode;
    }

    /**
     * @param mixed $error
     */
    public function setError(string $error): void
    {
        $this->error = $error;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return bool
     */
    public function isTimedOut(): bool
    {
        return $this->isTimedOut;
    }

    /**
     * @return bool
     */
    public function isNetworkError(): bool
    {
        return $this->isNetworkError;
    }

    /**
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }
}
