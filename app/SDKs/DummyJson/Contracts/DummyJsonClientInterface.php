<?php

namespace App\SDKs\DummyJson\Contracts;

use App\SDKs\DummyJson\Exceptions\ApiException;
use App\SDKs\DummyJson\Http\Client;
use App\SDKs\DummyJson\Resources\Product;

//use App\DummyJson\Resources\User;
//use App\DummyJson\Resources\Post;
//use App\DummyJson\Resources\Cart;
//use App\DummyJson\Resources\Auth;

interface DummyJsonClientInterface
{
    /**
     * Get Products resource
     *
     * @return Product
     */
    public function products(): Product;

//    /**
//     * Get Users resource
//     *
//     * @return User
//     */
//    public function users(): User;
//
//    /**
//     * Get Posts resource
//     *
//     * @return Post
//     */
//    public function posts(): Post;
//
//    /**
//     * Get Carts resource
//     *
//     * @return Cart
//     */
//    public function carts(): Cart;
//
//    /**
//     * Get Auth resource
//     *
//     * @return Auth
//     */
//    public function auth(): Auth;

    /**
     * Authenticate with username and password
     *
     * @param string $username
     * @param string $password
     * @param int $expiresInMins Token expiration time in minutes
     * @return array Contains token, refresh_token, and user data
     * @throws ApiException When authentication fails
     */
    public function login(string $username, string $password, int $expiresInMins = 30): array;

    /**
     * Set authentication token manually
     *
     * @param string $token JWT token
     * @return self
     */
    public function withAuth(string $token): self;

    /**
     * Remove authentication
     *
     * @return self
     */
    public function withoutAuth(): self;

    /**
     * Get a current authenticated user
     *
     * @return array|null User data or null if not authenticated
     * @throws ApiException When request fails
     */
    public function me(): ?array;

    /**
     * Refresh authentication token
     *
     * @param string $refreshToken
     * @param int $expiresInMins Token expiration time in minutes
     * @return array Contains a new token and refresh_token
     * @throws ApiException When refresh fails
     */
    public function refreshToken(string $refreshToken, int $expiresInMins = 30): array;

    /**
     * Check if a client is authenticated
     *
     * @return bool
     */
    public function isAuthenticated(): bool;

    /**
     * Get the current auth token
     *
     * @return string|null
     */
    public function getAuthToken(): ?string;

    /**
     * Disable cache for next request
     *
     * @return self
     */
    public function withoutCache(): self;

    /**
     * Enable cache for requests (default behavior)
     *
     * @return self
     */
    public function withCache(): self;

    /**
     * Add custom headers for next requests
     *
     * @param array $headers
     * @return self
     */
    public function withHeaders(array $headers): self;

    /**
     * Set request timeout
     *
     * @param int $seconds
     * @return self
     */
    public function timeout(int $seconds): self;

    /**
     * Set retry configuration
     *
     * @param int $times Number of retry attempts
     * @param int $sleepMilliseconds Delay between retries
     * @return self
     */
    public function retry(int $times, int $sleepMilliseconds = 100): self;

    /**
     * Enable debug mode for detailed logging
     *
     * @param bool $enabled
     * @return self
     */
    public function debug(bool $enabled = true): self;

    /**
     * Set base URL (useful for testing)
     *
     * @param string $baseUrl
     * @return self
     */
    public function baseUrl(string $baseUrl): self;

    /**
     * Get the underlying HTTP client for advanced usage
     *
     * @return Client
     */
    public function getHttpClient(): Client;

    /**
     * Test the connection to DummyJSON API
     *
     * @return bool True if the connection is successful
     * @throws ApiException When connection fails
     */
    public function testConnection(): bool;

    /**
     * Get API status and information
     *
     * @return array API status information
     * @throws ApiException When request fails
     */
    public function getApiStatus(): array;

    /**
     * Clear all cached responses
     *
     * @return bool True if the cache was cleared successfully
     */
    public function clearCache(): bool;

    /**
     * Get current configuration
     *
     * @return array Current client configuration
     */
    public function getConfig(): array;

    /**
     * Reset client to default configuration
     *
     * @return self
     */
    public function reset(): self;
}
