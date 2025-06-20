<?php

namespace App\SDKs\KeycloakAdmin\Exceptions;

use Exception;
use Throwable;

class KeycloakException extends Exception
{
    protected array $context;
    protected ?array $responseBody;

    public function __construct(
        string    $message = '',
        int       $code = 0,
        array     $context = [],
        ?array    $responseBody = null,
        Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
        $this->responseBody = $responseBody;
    }

    /**
     * Get additional context data
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get the response body from Keycloak
     */
    public function getResponseBody(): ?array
    {
        return $this->responseBody;
    }

    /**
     * Get Keycloak error code if available
     */
    public function getKeycloakError(): ?string
    {
        return $this->responseBody['error'] ?? null;
    }

    /**
     * Get Keycloak error description if available
     */
    public function getKeycloakErrorDescription(): ?string
    {
        return $this->responseBody['error_description']
            ?? $this->responseBody['errorMessage']
            ?? null;
    }

    /**
     * Get Keycloak error parameters if available
     */
    public function getKeycloakErrorParams(): ?array
    {
        return $this->responseBody['params'] ?? null;
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
            'keycloak_error' => $this->getKeycloakError(),
            'keycloak_error_description' => $this->getKeycloakErrorDescription(),
            'response_body' => $this->responseBody,
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
        return $this->getKeycloakErrorDescription()
            ?? $this->getMessage()
            ?? 'An error occurred while communicating with Keycloak.';
    }

    /**
     * Check if this is a specific Keycloak error
     */
    public function isKeycloakError(string $errorCode): bool
    {
        return $this->getKeycloakError() === $errorCode;
    }

    /**
     * Get HTTP status code
     */
    public function getHttpStatus(): int
    {
        return $this->getCode();
    }

    /**
     * Check if error is retriable (5xx errors, rate limits, etc.)
     */
    public function isRetriable(): bool
    {
        return $this->getCode() >= 500 || $this->getCode() === 429;
    }

    /**
     * Check if the error is client-side (4xx errors)
     */
    public function isClientError(): bool
    {
        return $this->getCode() >= 400 && $this->getCode() < 500;
    }

    /**
     * Check if the error is server-side (5xx errors)
     */
    public function isServerError(): bool
    {
        return $this->getCode() >= 500;
    }
}
