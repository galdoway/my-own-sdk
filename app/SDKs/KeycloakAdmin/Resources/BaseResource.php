<?php

namespace App\SDKs\KeycloakAdmin\Resources;

use App\SDKs\KeycloakAdmin\Http\Client;

abstract class BaseResource
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Get the HTTP client instance
     */
    protected function getClient(): Client
    {
        return $this->client;
    }

    /**
     * Get the current realm
     */
    protected function getRealm(): string
    {
        return $this->client->getRealm();
    }

    /**
     * Check if a client has a valid token
     */
    protected function hasToken(): bool
    {
        return !is_null($this->client->getToken());
    }

    /**
     * Log resource activity
     */
    protected function logActivity(string $action, array $context = []): void
    {
        $logContext = array_merge([
            'resource' => static::class,
            'realm' => $this->getRealm(),
            'action' => $action,
            'timestamp' => now()->toISOString(),
        ], $context);

        logger()->info('Keycloak Resource Activity', $logContext);
    }
}
