<?php

namespace App\SDKs\DummyJson\Http;

use App\SDKs\DummyJson\Exceptions\ApiException;
use App\SDKs\DummyJson\Exceptions\DummyJsonException;
use App\SDKs\DummyJson\Exceptions\RateLimitException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response as LaravelResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Client
{
    private PendingRequest $httpClient;
    private array $config;
    private ?string $authToken = null;

    public function __construct()
    {
        $this->config = config('dummyjson');
        $this->setupHttpClient();
    }

    private function setupHttpClient(): void
    {
        $this->httpClient = Http::baseUrl($this->config['base_url'])
            ->timeout($this->config['timeout'])
            ->retry(
                times: $this->config['retry']['times'],
                sleepMilliseconds: $this->config['retry']['sleep']
            )
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'User-Agent' => 'Laravel-DummyJson-SDK/1.0',
            ])
            ->throw(function (LaravelResponse $response) {
                $this->handleHttpException($response);
            });
    }

    /**
     * @throws ConnectionException
     */
    public function get(string $endpoint, array $query = []): Response
    {
        $cacheKey = $this->generateCacheKey($endpoint, $query);

        if ($this->config['cache']['enabled'] && Cache::has($cacheKey)) {
            $data = Cache::get($cacheKey);
            return new Response($data, 200, true);
        }

        $this->logRequest('GET', $endpoint, $query);

        $response = $this->httpClient
            ->when($this->authToken, fn($http) => $http->withToken($this->authToken))
            ->get($endpoint, $query);

        $wrappedResponse = new Response($response->json(), $response->status());

        if ($this->config['cache']['enabled'] && $response->successful()) {
            Cache::put($cacheKey, $response->json(), $this->config['cache']['ttl']);
        }

        $this->logResponse($response);

        return $wrappedResponse;
    }

    /**
     * @throws ConnectionException
     */
    public function post(string $endpoint, array $data = []): Response
    {
        $this->logRequest('POST', $endpoint, $data);

        $response = $this->httpClient
            ->when($this->authToken, fn($http) => $http->withToken($this->authToken))
            ->post($endpoint, $data);

        $this->logResponse($response);

        return new Response($response->json(), $response->status());
    }

    /**
     * @throws ConnectionException
     */
    public function put(string $endpoint, array $data = []): Response
    {
        $this->logRequest('PUT', $endpoint, $data);

        $response = $this->httpClient
            ->when($this->authToken, fn($http) => $http->withToken($this->authToken))
            ->put($endpoint, $data);

        $this->logResponse($response);

        return new Response($response->json(), $response->status());
    }

    /**
     * @throws ConnectionException
     */
    public function patch(string $endpoint, array $data = []): Response
    {
        $this->logRequest('PATCH', $endpoint, $data);

        $response = $this->httpClient
            ->when($this->authToken, fn($http) => $http->withToken($this->authToken))
            ->patch($endpoint, $data);

        $this->logResponse($response);

        return new Response($response->json(), $response->status());
    }

    /**
     * @throws ConnectionException
     */
    public function delete(string $endpoint): Response
    {
        $this->logRequest('DELETE', $endpoint);

        $response = $this->httpClient
            ->when($this->authToken, fn($http) => $http->withToken($this->authToken))
            ->delete($endpoint);

        $this->logResponse($response);

        return new Response($response->json(), $response->status());
    }

    public function withAuth(string $token): self
    {
        $this->authToken = $token;
        return $this;
    }

    public function withoutAuth(): self
    {
        $this->authToken = null;
        return $this;
    }

    public function withHeaders(array $headers): self
    {
        $this->httpClient = $this->httpClient->withHeaders($headers);
        return $this;
    }

    public function withoutCache(): self
    {
        $client = clone $this;
        $client->config['cache']['enabled'] = false;
        return $client;
    }

    /**
     * @throws DummyJsonException
     * @throws ApiException
     * @throws RateLimitException
     */
    private function handleHttpException(LaravelResponse $response): void
    {
        $statusCode = $response->status();
        $body = $response->json();

        match (true) {
            $statusCode === 429 => throw new RateLimitException(
                'Rate limit exceeded',
                $statusCode,
                $response->header('Retry-After')
            ),
            $statusCode >= 400 && $statusCode < 500 => throw new ApiException(
                $body['message'] ?? 'Client error occurred',
                $statusCode,
                $body
            ),
            $statusCode >= 500 => throw new ApiException(
                'Server error occurred',
                $statusCode,
                $body
            ),
            default => throw new DummyJsonException(
                'Unexpected error occurred',
                $statusCode
            ),
        };
    }

    private function generateCacheKey(string $endpoint, array $params = []): string
    {
        $key = sprintf('dummyjson:%s:%s', 'GET', $endpoint);

        if (!empty($params)) {
            $key .= ':' . md5(serialize($params));
        }

        if ($this->authToken) {
            $key .= ':auth:' . md5($this->authToken);
        }

        return $key;
    }

    private function logRequest(string $method, string $endpoint, array $data = []): void
    {
        Log::channel('dummyjson')->info('API Request', [
            'method' => $method,
            'endpoint' => $endpoint,
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ]);
    }

    private function logResponse(LaravelResponse $response): void
    {
        Log::channel('dummyjson')->info('API Response', [
            'status' => $response->status(),
            'response_time' => $response->transferStats?->getTransferTime(),
            'timestamp' => now()->toISOString(),
        ]);
    }
}
