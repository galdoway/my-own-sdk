<?php


namespace App\SDKs\DummyJson\Exceptions;

use Exception;
use Throwable;

class DummyJsonException extends Exception
{
    protected array $context;

    public function __construct(
        string    $message = '',
        int       $code = 0,
        array     $context = [],
        Throwable $previous = null
    )
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get additional context data
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Set additional context data
     */
    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Add context data
     */
    public function addContext(string $key, $value): self
    {
        $this->context[$key] = $value;
        return $this;
    }

    /**
     * Convert to array for logging
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'context' => $this->context,
        ];
    }

    /**
     * Convert to JSON for API responses
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Get user-friendly error message
     */
    public function getUserMessage(): string
    {
        return 'An error occurred while communicating with DummyJSON API.';
    }
}
