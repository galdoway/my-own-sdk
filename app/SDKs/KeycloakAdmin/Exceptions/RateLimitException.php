<?php

namespace App\SDKs\KeycloakAdmin\Exceptions;

use Carbon\Carbon;
use Throwable;

class RateLimitException extends KeycloakException
{
    private ?int $retryAfter;
    private ?Carbon $retryAfterTime;

    public function __construct(
        string    $message = 'Rate limit exceeded',
        int       $code = 429,
        ?int      $retryAfter = null,
        array     $context = [],
        ?array    $responseBody = null,
        Throwable $previous = null
    ) {
        parent::__construct($message, $code, $context, $responseBody, $previous);
        $this->retryAfter = $retryAfter;
        $this->retryAfterTime = $retryAfter ? Carbon::now()->addSeconds($retryAfter) : null;
    }

    /**
     * Get retry after seconds
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }

    /**
     * Get retry after time as Carbon instance
     */
    public function getRetryAfterTime(): ?Carbon
    {
        return $this->retryAfterTime;
    }

    /**
     * Get human readable retry after time
     */
    public function getRetryAfterHuman(): ?string
    {
        return $this->retryAfterTime?->diffForHumans();
    }

    /**
     * Check if retry is allowed now
     */
    public function canRetryNow(): bool
    {
        if (!$this->retryAfterTime) {
            return true;
        }

        return Carbon::now()->isAfter($this->retryAfterTime);
    }

    /**
     * Get seconds until retry is allowed
     */
    public function getSecondsUntilRetry(): int
    {
        if (!$this->retryAfterTime) {
            return 0;
        }

        $diff = Carbon::now()->diffInSeconds($this->retryAfterTime, false);
        return max(0, $diff);
    }

    /**
     * Create exception with retry after header
     */
    public static function withRetryAfter(string|int $retryAfter, array $responseBody = []): self
    {
        $retryAfterInt = is_string($retryAfter) ? (int) $retryAfter : $retryAfter;

        return new self(
            message: "Rate limit exceeded. Retry after {$retryAfterInt} seconds.",
            retryAfter: $retryAfterInt,
            responseBody: $responseBody
        );
    }

    /**
     * Create exception for admin API rate limit
     */
    public static function adminApiLimit(array $responseBody = []): self
    {
        return new self(
            message: 'Keycloak Admin API rate limit exceeded',
            retryAfter: 60, // Default 1 minute
            responseBody: $responseBody
        );
    }

    /**
     * Create exception for authentication rate limit
     */
    public static function authenticationLimit(array $responseBody = []): self
    {
        return new self(
            message: 'Authentication rate limit exceeded',
            retryAfter: 300, // Default 5 minutes
            responseBody: $responseBody
        );
    }

    /**
     * Create exception for general server overload
     */
    public static function serverOverload(array $responseBody = []): self
    {
        return new self(
            message: 'Keycloak server is temporarily overloaded',
            retryAfter: 30, // Default 30 seconds
            responseBody: $responseBody
        );
    }

    public function getUserMessage(): string
    {
        if ($this->retryAfter) {
            $waitTime = $this->getRetryAfterHuman() ?? "{$this->retryAfter} seconds";
            return "Too many requests. Please wait {$waitTime} before trying again.";
        }

        return $this->getKeycloakErrorDescription()
            ?? 'Too many requests. Please wait a moment before trying again.';
    }

    public function toArray(): array
    {
        $array = parent::toArray();
        $array['retry_after'] = $this->retryAfter;
        $array['retry_after_time'] = $this->retryAfterTime?->toISOString();
        $array['can_retry_now'] = $this->canRetryNow();
        $array['seconds_until_retry'] = $this->getSecondsUntilRetry();
        return $array;
    }

    public function isRetriable(): bool
    {
        return true; // Rate limit exceptions are always retriable
    }

    /**
     * Sleep until retry is allowed
     */
    public function waitUntilRetry(): void
    {
        $seconds = $this->getSecondsUntilRetry();
        if ($seconds > 0) {
            sleep($seconds);
        }
    }

    /**
     * Get suggested backoff strategy
     */
    public function getBackoffStrategy(): array
    {
        $baseDelay = $this->retryAfter ?? 60;

        return [
            'strategy' => 'exponential',
            'base_delay' => $baseDelay,
            'max_delay' => min($baseDelay * 8, 300), // Max 5 minutes
            'jitter' => true,
        ];
    }
}
