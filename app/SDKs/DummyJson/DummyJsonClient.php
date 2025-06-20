<?php

namespace App\SDKs\DummyJson;

use App\SDKs\DummyJson\Contracts\DummyJsonClientInterface;
use App\SDKs\DummyJson\Exceptions\ApiException;
use App\SDKs\DummyJson\Exceptions\DummyJsonException;
use App\SDKs\DummyJson\Http\Client;
use App\SDKs\DummyJson\Resources\Product;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

//use App\DummyJson\Resources\User;
//use App\DummyJson\Resources\Post;
//use App\DummyJson\Resources\Cart;
//use App\DummyJson\Resources\Auth;

class DummyJsonClient implements DummyJsonClientInterface
{
    private Client $httpClient;
    private ?string $authToken = null;
    private array $config;

    // Resource instances (lazy loaded)
    private ?Product $productResource = null;
//    private ?User $userResource = null;
//    private ?Post $postResource = null;
//    private ?Cart $cartResource = null;
//    private ?Auth $authResource = null;

    public function __construct()
    {
        $this->config = config('dummyjson', [
            'base_url' => 'https://dummyjson.com',
            'timeout' => 30,
            'retry' => ['times' => 3, 'sleep' => 100],
            'cache' => ['enabled' => true, 'ttl' => 300],
            'debug' => false,
        ]);

        $this->httpClient = new Client();
    }

    /**
     * Get Products resource
     */
    public function products(): Product
    {
        if ($this->productResource === null) {
            $this->productResource = new Product($this->httpClient);
        }

        return $this->productResource;
    }

    /**
     * Get Users resource
     */
//    public function users(): User
//    {
//        if ($this->userResource === null) {
//            $this->userResource = new User($this->httpClient);
//        }
//
//        return $this->userResource;
//    }

    /**
     * Get Posts resource
     */
//    public function posts(): Post
//    {
//        if ($this->postResource === null) {
//            $this->postResource = new Post($this->httpClient);
//        }
//
//        return $this->postResource;
//    }

    /**
     * Get Carts resource
     */
//    public function carts(): Cart
//    {
//        if ($this->cartResource === null) {
//            $this->cartResource = new Cart($this->httpClient);
//        }
//
//        return $this->cartResource;
//    }

    /**
     * Get Auth resource
     */
//    public function auth(): Auth
//    {
//        if ($this->authResource === null) {
//            $this->authResource = new Auth($this->httpClient);
//        }
//
//        return $this->authResource;
//    }

    /**
     * Authenticate with username and password
     * @throws ApiException
     */
    public function login(string $username, string $password, int $expiresInMins = 30): array
    {
        try {
            $response = $this->auth()->login($username, $password, $expiresInMins);

            if ($response->successful()) {
                $this->authToken = $response->token();
                $this->httpClient->withAuth($this->authToken);

                $result = [
                    'token' => $response->token(),
                    'refresh_token' => $response->refreshToken(),
                    'user' => $response->get('user'),
                    'expires_in' => $expiresInMins * 60, // Convert to seconds
                ];

                $this->logActivity('login_success', [
                    'username' => $username,
                    'expires_in_mins' => $expiresInMins,
                ]);

                return $result;
            }

            throw new ApiException('Authentication failed', $response->status(), $response->data());

        } catch (ApiException $e) {
            $this->logActivity('login_failed', [
                'username' => $username,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Set authentication token manually
     */
    public function withAuth(string $token): self
    {
        $this->authToken = $token;
        $this->httpClient->withAuth($token);

        $this->logActivity('auth_token_set');

        return $this;
    }

    /**
     * Remove authentication
     */
    public function withoutAuth(): self
    {
        $this->authToken = null;
        $this->httpClient->withoutAuth();

        $this->logActivity('auth_removed');

        return $this;
    }

    /**
     * Get a current authenticated user
     * @throws ApiException
     */
    public function me(): ?array
    {
        if (!$this->authToken) {
            return null;
        }

        try {
            $response = $this->auth()->me();
            return $response->successful() ? $response->data() : null;
        } catch (ApiException $e) {
            $this->logActivity('me_request_failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Refresh authentication token
     * @throws ApiException
     */
    public function refreshToken(string $refreshToken, int $expiresInMins = 30): array
    {
        try {
            $response = $this->auth()->refresh($refreshToken, $expiresInMins);

            if ($response->successful()) {
                $this->authToken = $response->token();
                $this->httpClient->withAuth($this->authToken);

                $result = [
                    'token' => $response->token(),
                    'refresh_token' => $response->refreshToken(),
                    'expires_in' => $expiresInMins * 60,
                ];

                $this->logActivity('token_refreshed', [
                    'expires_in_mins' => $expiresInMins,
                ]);

                return $result;
            }

            throw new ApiException('Token refresh failed', $response->status(), $response->data());

        } catch (ApiException $e) {
            $this->logActivity('token_refresh_failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Check if a client is authenticated
     */
    public function isAuthenticated(): bool
    {
        return $this->authToken !== null;
    }

    /**
     * Get the current auth token
     */
    public function getAuthToken(): ?string
    {
        return $this->authToken;
    }

    /**
     * Disable cache for next request
     */
    public function withoutCache(): self
    {
        $this->httpClient = $this->httpClient->withoutCache();
        return $this;
    }

    /**
     * Enable cache for requests (default behavior)
     */
    public function withCache(): self
    {
        // Reset the HTTP client to restore cache functionality
        $this->httpClient = new Client();
        if ($this->authToken) {
            $this->httpClient->withAuth($this->authToken);
        }
        return $this;
    }

    /**
     * Add custom headers for next requests
     */
    public function withHeaders(array $headers): self
    {
        $this->httpClient->withHeaders($headers);
        return $this;
    }

    /**
     * Set request timeout
     */
    public function timeout(int $seconds): self
    {
        $this->config['timeout'] = $seconds;
        $this->httpClient = $this->createNewHttpClient();
        return $this;
    }

    /**
     * Set retry configuration
     */
    public function retry(int $times, int $sleepMilliseconds = 100): self
    {
        $this->config['retry'] = [
            'times' => $times,
            'sleep' => $sleepMilliseconds,
        ];
        $this->httpClient = $this->createNewHttpClient();
        return $this;
    }

    /**
     * Enable debug mode for detailed logging
     */
    public function debug(bool $enabled = true): self
    {
        $this->config['debug'] = $enabled;
        return $this;
    }

    /**
     * Set base URL (useful for testing)
     */
    public function baseUrl(string $baseUrl): self
    {
        $this->config['base_url'] = $baseUrl;
        $this->httpClient = $this->createNewHttpClient();
        return $this;
    }

    /**
     * Get the underlying HTTP client for advanced usage
     */
    public function getHttpClient(): Client
    {
        return $this->httpClient;
    }

    /**
     * Test the connection to DummyJSON API
     */
    public function testConnection(): bool
    {
        try {
            $response = $this->httpClient->get('/test');
            $isSuccessful = $response->successful();

            $this->logActivity('connection_test', [
                'success' => $isSuccessful,
                'status_code' => $response->status(),
            ]);

            return $isSuccessful;
        } catch (Exception $e) {
            $this->logActivity('connection_test_failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get API status and information
     */
    public function getApiStatus(): array
    {
        try {
            // DummyJSON doesn't have a status endpoint, so we'll create our own status check
            $startTime = microtime(true);
            $response = $this->products()->paginate(1, 0);
            $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

            $status = [
                'status' => 'operational',
                'response_time_ms' => round($responseTime, 2),
                'api_version' => '1.0',
                'authenticated' => $this->isAuthenticated(),
                'base_url' => $this->config['base_url'],
                'timestamp' => now()->toISOString(),
            ];

            if ($response->successful()) {
                $status['endpoints'] = [
                    'products' => 'operational',
                    'users' => 'operational',
                    'posts' => 'operational',
                    'carts' => 'operational',
                    'auth' => 'operational',
                ];
            }

            $this->logActivity('api_status_check', $status);

            return $status;

        } catch (Exception $e) {
            $errorStatus = [
                'status' => 'error',
                'error_message' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ];

            $this->logActivity('api_status_check_failed', $errorStatus);

            return $errorStatus;
        }
    }

    /**
     * Clear all cached responses
     */
    public function clearCache(): bool
    {
        try {
            $cachePrefix = 'dummyjson:';
            $cleared = Cache::flush(); // This clears all cache, for specific prefix clearing you'd need a custom implementation

            $this->logActivity('cache_cleared', ['success' => $cleared]);

            return $cleared;
        } catch (Exception $e) {
            $this->logActivity('cache_clear_failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get current configuration
     */
    public function getConfig(): array
    {
        return [
            'base_url' => $this->config['base_url'],
            'timeout' => $this->config['timeout'],
            'retry' => $this->config['retry'],
            'cache_enabled' => $this->config['cache']['enabled'] ?? true,
            'cache_ttl' => $this->config['cache']['ttl'] ?? 300,
            'debug' => $this->config['debug'] ?? false,
            'authenticated' => $this->isAuthenticated(),
        ];
    }

    /**
     * Reset client to default configuration
     */
    public function reset(): self
    {
        $this->config = config('dummyjson', [
            'base_url' => 'https://dummyjson.com',
            'timeout' => 30,
            'retry' => ['times' => 3, 'sleep' => 100],
            'cache' => ['enabled' => true, 'ttl' => 300],
            'debug' => false,
        ]);

        $this->authToken = null;
        $this->httpClient = new Client();

        // Reset all resource instances
        $this->productResource = null;
//        $this->userResource = null;
//        $this->postResource = null;
//        $this->cartResource = null;
//        $this->authResource = null;

        $this->logActivity('client_reset');

        return $this;
    }

    /**
     * Create a new HTTP client with the current configuration
     */
    private function createNewHttpClient(): Client
    {
        $newClient = new Client();

        if ($this->authToken) {
            $newClient->withAuth($this->authToken);
        }

        return $newClient;
    }

    /**
     * Log client activity
     */
    private function logActivity(string $action, array $context = []): void
    {
        if (!$this->config['debug']) {
            return;
        }

        $logData = array_merge([
            'action' => $action,
            'timestamp' => now()->toISOString(),
            'client_id' => spl_object_hash($this),
        ], $context);

        Log::channel('dummyjson')->info("DummyJSON Client: $action", $logData);
    }

    /**
     * Magic method to handle dynamic calls for quick access
     * @throws DummyJsonException
     */
    public function __call(string $method, array $arguments)
    {
        // Allow quick access like $client->findProduct(1) instead of $client->products()->find(1)
        $quickMethods = [
            'findProduct' => fn($id) => $this->products()->find($id),
//            'findUser' => fn($id) => $this->users()->find($id),
//            'findPost' => fn($id) => $this->posts()->find($id),
//            'findCart' => fn($id) => $this->carts()->find($id),
            'searchProducts' => fn($query, $limit = 30, $skip = 0) => $this->products()->search($query, $limit, $skip),
            'getAllProducts' => fn($params = []) => $this->products()->all($params),
//            'getAllUsers' => fn($params = []) => $this->users()->all($params),
            'getProductCategories' => fn() => $this->products()->categories(),
        ];

        if (isset($quickMethods[$method])) {
            return $quickMethods[$method](...$arguments);
        }

        throw new DummyJsonException("Method $method does not exist on DummyJsonClient");
    }
}
