<?php

namespace App\SDKs\DummyJson\Exceptions;

use Throwable;

class RateLimitException extends ApiException
{
    protected ?int $retryAfter;
    protected ?int $remainingRequests;
    protected ?int $resetTime;

    public function __construct(
        string $message = 'Rate limit exceeded',
        int $statusCode = 429,
        ?int $retryAfter = null,
        ?int $remainingRequests = null,
        ?int $resetTime = null,
        Throwable $previous = null
    ) {
        $this->retryAfter = $retryAfter;
        $this->remainingRequests = $remainingRequests;
        $this->resetTime = $resetTime;

        $responseBody = [
            'error' => 'rate_limit_exceeded',
            'retry_after' => $retryAfter,
            'remaining_requests' => $remainingRequests,
            'reset_time' => $resetTime,
        ];

        parent::__construct($message, $statusCode, $responseBody, null, $previous);
    }

    /**
     * Get to retry after seconds
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }

    /**
     * Get remaining requests in the current window
     */
    public function getRemainingRequests(): ?int
    {
        return $this->remainingRequests;
    }

    /**
     * Get a reset time timestamp
     */
    public function getResetTime(): ?int
    {
        return $this->resetTime;
    }

    /**
     * Get reset time as DateTime
     */
    public function getResetDateTime(): ?\DateTime
    {
        if ($this->resetTime === null) {
            return null;
        }

        return new \DateTime('@' . $this->resetTime);
    }

    /**
     * Get human-readable reset time
     */
    public function getResetTimeForHumans(): ?string
    {
        $resetDateTime = $this->getResetDateTime();

        if ($resetDateTime === null) {
            return null;
        }

        $now = new \DateTime();
        $diff = $now->diff($resetDateTime);

        if ($diff->h > 0) {
            return $diff->h . ' hour(s) and ' . $diff->i . ' minute(s)';
        }

        if ($diff->i > 0) {
            return $diff->i . ' minute(s) and ' . $diff->s . ' second(s)';
        }

        return $diff->s . ' second(s)';
    }

    /**
     * Check if retry after time has passed
     */
    public function canRetryNow(): bool
    {
        if ($this->retryAfter === null) {
            return true;
        }

        // Simple check - in real implementation you'd want to track when the exception was created
        return false;
    }

    /**
     * Get the suggested wait time in seconds
     */
    public function getSuggestedWaitTime(): int
    {
        if ($this->retryAfter !== null) {
            return $this->retryAfter;
        }

        // Default to 60 seconds if no retry-after header
        return 60;
    }

    /**
     * Get user-friendly error message
     */
    public function getUserMessage(): string
    {
        $baseMessage = 'Too many requests have been made. Please wait before trying again.';

        if ($this->retryAfter !== null) {
            $minutes = ceil($this->retryAfter / 60);
            return $baseMessage . " Try again in {$minutes} minute(s).";
        }

        if ($this->resetTime !== null) {
            $resetTime = $this->getResetTimeForHumans();
            return $baseMessage . " Rate limit resets in {$resetTime}.";
        }

        return $baseMessage;
    }

    /**
     * Always should retry for rate limit
     */
    public function shouldRetry(): bool
    {
        return true;
    }

    /**
     * Get retry delay for rate limit
     */
    public function getRetryDelay(): int
    {
        return $this->getSuggestedWaitTime();
    }

    /**
     * Convert to array for logging
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'retry_after' => $this->retryAfter,
            'remaining_requests' => $this->remainingRequests,
            'reset_time' => $this->resetTime,
            'reset_time_human' => $this->getResetTimeForHumans(),
            'can_retry_now' => $this->canRetryNow(),
        ]);
    }

    /**
     * Create from HTTP headers
     */
    public static function fromHeaders(array $headers, string $message = 'Rate limit exceeded'): self
    {
        $retryAfter = isset($headers['Retry-After']) ? (int) $headers['Retry-After'] : null;
        $remaining = isset($headers['X-RateLimit-Remaining']) ? (int) $headers['X-RateLimit-Remaining'] : null;
        $resetTime = isset($headers['X-RateLimit-Reset']) ? (int) $headers['X-RateLimit-Reset'] : null;

        return new self($message, 429, $retryAfter, $remaining, $resetTime);
    }
}
