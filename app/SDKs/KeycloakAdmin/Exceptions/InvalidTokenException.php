<?php

namespace App\SDKs\KeycloakAdmin\Exceptions;

use Throwable;

class InvalidTokenException extends KeycloakException
{
    private ?string $tokenHint;

    public function __construct(
        string    $message = 'Invalid or expired token',
        int       $code = 401,
        array     $context = [],
        ?array    $responseBody = null,
        ?string   $tokenHint = null,
        Throwable $previous = null
    ) {
        parent::__construct($message, $code, $context, $responseBody, $previous);
        $this->tokenHint = $tokenHint;
    }

    /**
     * Get hint about the token issue
     */
    public function getTokenHint(): ?string
    {
        return $this->tokenHint;
    }

    /**
     * Create exception for expired token
     */
    public static function expired(array $responseBody = []): self
    {
        return new self(
            message: 'Token has expired',
            responseBody: $responseBody,
            tokenHint: 'expired'
        );
    }

    /**
     * Create exception for malformed token
     */
    public static function malformed(array $responseBody = []): self
    {
        return new self(
            message: 'Token is malformed or invalid',
            responseBody: $responseBody,
            tokenHint: 'malformed'
        );
    }

    /**
     * Create exception for missing token
     */
    public static function missing(): self
    {
        return new self(
            message: 'Bearer token is required but not provided',
            tokenHint: 'missing'
        );
    }

    /**
     * Create exception for invalid signature
     */
    public static function invalidSignature(array $responseBody = []): self
    {
        return new self(
            message: 'Token signature is invalid',
            responseBody: $responseBody,
            tokenHint: 'invalid_signature'
        );
    }

    /**
     * Create exception for invalid audience
     */
    public static function invalidAudience(array $responseBody = []): self
    {
        return new self(
            message: 'Token audience is invalid for this request',
            responseBody: $responseBody,
            tokenHint: 'invalid_audience'
        );
    }

    /**
     * Create exception for invalid issuer
     */
    public static function invalidIssuer(array $responseBody = []): self
    {
        return new self(
            message: 'Token issuer is invalid',
            responseBody: $responseBody,
            tokenHint: 'invalid_issuer'
        );
    }

    public function getUserMessage(): string
    {
        return match ($this->tokenHint) {
            'expired' => 'Your session has expired. Please log in again.',
            'malformed' => 'Invalid authentication token. Please log in again.',
            'missing' => 'Authentication required. Please log in.',
            'invalid_signature' => 'Invalid authentication token. Please log in again.',
            'invalid_audience' => 'Authentication token is not valid for this service.',
            'invalid_issuer' => 'Authentication token is from an untrusted source.',
            default => 'Authentication failed. Please log in again.',
        };
    }

    public function toArray(): array
    {
        $array = parent::toArray();
        $array['token_hint'] = $this->tokenHint;
        return $array;
    }
}
