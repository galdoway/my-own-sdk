<?php

namespace App\SDKs\DummyJson\Exceptions;

use Throwable;

class ApiException extends DummyJsonException
{
    protected int $statusCode;
    protected array $responseBody;
    protected ?string $endpoint;

    public function __construct(
        string    $message = '',
        int       $statusCode = 0,
        array     $responseBody = [],
        string    $endpoint = null,
        Throwable $previous = null
    )
    {
        parent::__construct($message, $statusCode, [
            'status_code' => $statusCode,
            'response_body' => $responseBody,
            'endpoint' => $endpoint,
        ], $previous);

        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;
        $this->endpoint = $endpoint;
    }

    /**
     * Get the HTTP status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get the response body
     */
    public function getResponseBody(): array
    {
        return $this->responseBody;
    }

    /**
     * Get the endpoint that caused the error
     */
    public function getEndpoint(): ?string
    {
        return $this->endpoint;
    }

    /**
     * Check if this is a client error (4xx)
     */
    public function isClientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * Check if this is a server error (5xx)
     */
    public function isServerError(): bool
    {
        return $this->statusCode >= 500;
    }

    /**
     * Check if this is a validation error
     */
    public function isValidationError(): bool
    {
        return $this->statusCode === 422;
    }

    /**
     * Check if this is an authentication error
     */
    public function isAuthenticationError(): bool
    {
        return $this->statusCode === 401;
    }

    /**
     * Check if this is an authorization error
     */
    public function isAuthorizationError(): bool
    {
        return $this->statusCode === 403;
    }

    /**
     * Check if this is a not found error
     */
    public function isNotFoundError(): bool
    {
        return $this->statusCode === 404;
    }

    /**
     * Get validation errors if available
     */
    public function getValidationErrors(): array
    {
        return $this->responseBody['errors'] ?? [];
    }

    /**
     * Get error details from response
     */
    public function getErrorDetails(): array
    {
        return [
            'message' => $this->getMessage(),
            'status_code' => $this->statusCode,
            'endpoint' => $this->endpoint,
            'response_body' => $this->responseBody,
            'type' => $this->getErrorType(),
        ];
    }

    /**
     * Get error type based on status code
     */
    public function getErrorType(): string
    {
        return match (true) {
            $this->isValidationError() => 'validation_error',
            $this->isAuthenticationError() => 'authentication_error',
            $this->isAuthorizationError() => 'authorization_error',
            $this->isNotFoundError() => 'not_found_error',
            $this->isClientError() => 'client_error',
            $this->isServerError() => 'server_error',
            default => 'unknown_error',
        };
    }

    /**
     * Get user-friendly error message
     */
    public function getUserMessage(): string
    {
        return match (true) {
            $this->isValidationError() => 'The provided data is invalid. Please check your input and try again.',
            $this->isAuthenticationError() => 'Authentication failed. Please check your credentials.',
            $this->isAuthorizationError() => 'You do not have permission to perform this action.',
            $this->isNotFoundError() => 'The requested resource was not found.',
            $this->statusCode === 429 => 'Too many requests. Please wait a moment and try again.',
            $this->isServerError() => 'A server error occurred. Please try again later.',
            default => 'An unexpected error occurred. Please try again.',
        };
    }

    /**
     * Should this error be retried?
     */
    public function shouldRetry(): bool
    {
        return match (true) {
            $this->statusCode === 429 => true, // Rate limit
            $this->statusCode === 502 => true, // Bad Gateway
            $this->statusCode === 503 => true, // Service Unavailable
            $this->statusCode === 504 => true, // Gateway Timeout
            $this->statusCode >= 500 => true,  // Other server errors
            default => false,
        };
    }

    /**
     * Get retry delay in seconds
     */
    public function getRetryDelay(): int
    {
        return match (true) {
            $this->statusCode === 429 => 60,  // 1 minute for rate limit
            $this->statusCode >= 500 => 30,   // 30 seconds for server errors
            default => 5,                     // 5 seconds default
        };
    }

    /**
     * Convert to array for logging
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'status_code' => $this->statusCode,
            'endpoint' => $this->endpoint,
            'response_body' => $this->responseBody,
            'error_type' => $this->getErrorType(),
        ]);
    }
}
