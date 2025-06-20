<?php

namespace App\SDKs\KeycloakAdmin\Http;

use App\SDKs\KeycloakAdmin\Exceptions\KeycloakException;
use App\SDKs\KeycloakAdmin\Exceptions\InvalidTokenException;
use App\SDKs\KeycloakAdmin\Exceptions\InsufficientPermissionsException;
use App\SDKs\KeycloakAdmin\Exceptions\ResourceNotFoundException;
use App\SDKs\KeycloakAdmin\Exceptions\RateLimitException;
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
    private ?string $bearerToken = null;
    private string $realm;

    public function __construct(?string $bearerToken = null)
    {
        $this->config = config('keycloak-admin');
        $this->bearerToken = $bearerToken;
        $this->realm = $this->config['realm'];
        $this->setupHttpClient();
    }

    private function setupHttpClient(): void
    {
        $baseUrl = rtrim($this->config['server_url'], '/');

        $this->httpClient = Http::baseUrl($baseUrl)
            ->timeout($this->config['timeout'])
            ->retry(
                times: $this->config['retry']['times'],
                sleepMilliseconds: $this->config['retry']['sleep']
            )
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->when($this->bearerToken, function (PendingRequest $http) {
                return $http->withToken($this->bearerToken);
            })
            ->throw(function (LaravelResponse $response) {
                $this->handleHttpException($response);
            });
    }

    /**
     * @throws ConnectionException
     */
    public function get(string $endpoint, array $query = []): Response
    {
        $fullEndpoint = $this->buildEndpoint($endpoint);
        $cacheKey = $this->generateCacheKey('GET', $fullEndpoint, $query);

        if ($this->config['cache']['enabled'] && Cache::has($cacheKey)) {
            $data = Cache::get($cacheKey);
            return new Response($data, 200, true);
        }

        $this->logRequest('GET', $fullEndpoint, ['query' => $query]);

        $response = $this->httpClient->get($fullEndpoint, $query);

        $wrappedResponse = new Response(
            $response->json() ?? [],
            $response->status()
        );

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
        $fullEndpoint = $this->buildEndpoint($endpoint);

        $this->logRequest('POST', $fullEndpoint, ['data' => $data]);

        $response = $this->httpClient->post($fullEndpoint, $data);

        $this->logResponse($response);

        return new Response(
            $response->json() ?? [],
            $response->status()
        );
    }

    /**
     * @throws ConnectionException
     */
    public function put(string $endpoint, array $data = []): Response
    {
        $fullEndpoint = $this->buildEndpoint($endpoint);

        $this->logRequest('PUT', $fullEndpoint, ['data' => $data]);

        $response = $this->httpClient->put($fullEndpoint, $data);

        $this->logResponse($response);

        return new Response(
            $response->json() ?? [],
            $response->status()
        );
    }

    /**
     * @throws ConnectionException
     */
    public function patch(string $endpoint, array $data = []): Response
    {
        $fullEndpoint = $this->buildEndpoint($endpoint);

        $this->logRequest('PATCH', $fullEndpoint, ['data' => $data]);

        $response = $this->httpClient->patch($fullEndpoint, $data);

        $this->logResponse($response);

        return new Response(
            $response->json() ?? [],
            $response->status()
        );
    }

    /**
     * @throws ConnectionException
     */
    public function delete(string $endpoint): Response
    {
        $fullEndpoint = $this->buildEndpoint($endpoint);

        $this->logRequest('DELETE', $fullEndpoint);

        $response = $this->httpClient->delete($fullEndpoint);

        $this->logResponse($response);

        return new Response(
            $response->json() ?? [],
            $response->status()
        );
    }

    public function withToken(string $token): self
    {
        $clone = clone $this;
        $clone->bearerToken = $token;
        $clone->setupHttpClient();
        return $clone;
    }

    public function withoutToken(): self
    {
        $clone = clone $this;
        $clone->bearerToken = null;
        $clone->setupHttpClient();
        return $clone;
    }

    public function withHeaders(array $headers): self
    {
        $clone = clone $this;
        $clone->httpClient = $clone->httpClient->withHeaders($headers);
        return $clone;
    }

    public function withoutCache(): self
    {
        $clone = clone $this;
        $clone->config['cache']['enabled'] = false;
        return $clone;
    }

    public function withRealm(string $realm): self
    {
        $clone = clone $this;
        $clone->realm = $realm;
        return $clone;
    }

    public function getRealm(): string
    {
        return $this->realm;
    }

    public function getToken(): ?string
    {
        return $this->bearerToken;
    }

    /**
     * Test connection to Keycloak server
     */
    public function testConnection(): bool
    {
        try {
            $response = $this->get('/admin/serverinfo');
            return $response->successful();
        } catch (\Exception) {
            return false;
        }
    }

    private function buildEndpoint(string $endpoint): string
    {
        // Si ya incluye /admin/realms, no lo duplicamos
        if (str_starts_with($endpoint, '/admin/realms/')) {
            return $endpoint;
        }

        // Si es un endpoint de realm específico
        if (str_starts_with($endpoint, '/admin/realms/{realm}')) {
            return str_replace('{realm}', $this->realm, $endpoint);
        }

        // Si es un endpoint administrativo general
        if (str_starts_with($endpoint, '/admin/')) {
            return $endpoint;
        }

        // Si es un endpoint de realm sin /admin
        if (str_starts_with($endpoint, 'realms/')) {
            return "/admin/{$endpoint}";
        }

        // Por defecto, asumimos que es para el realm actual
        return "/admin/realms/{$this->realm}{$endpoint}";
    }

    /**
     * @throws KeycloakException
     * @throws InvalidTokenException
     * @throws InsufficientPermissionsException
     * @throws ResourceNotFoundException
     * @throws RateLimitException
     */
    private function handleHttpException(LaravelResponse $response): void
    {
        $statusCode = $response->status();
        $body = $response->json() ?? [];
        $errorMessage = $body['error_description'] ?? $body['message'] ?? $body['error'] ?? 'Unknown error';

        match (true) {
            $statusCode === 401 => throw new InvalidTokenException(
                $errorMessage,
                $statusCode,
                $body
            ),
            $statusCode === 403 => throw new InsufficientPermissionsException(
                $errorMessage,
                $statusCode,
                $body
            ),
            $statusCode === 404 => throw new ResourceNotFoundException(
                $errorMessage,
                $statusCode,
                $body
            ),
            $statusCode === 429 => throw new RateLimitException(
                'Rate limit exceeded',
                $statusCode,
                $response->header('Retry-After')
            ),
            $statusCode >= 400 && $statusCode < 500 => throw new KeycloakException(
                $errorMessage,
                $statusCode,
                $body
            ),
            $statusCode >= 500 => throw new KeycloakException(
                'Keycloak server error occurred',
                $statusCode,
                $body
            ),
            default => throw new KeycloakException(
                'Unexpected error occurred',
                $statusCode,
                $body
            ),
        };
    }

    private function generateCacheKey(string $method, string $endpoint, array $params = []): string
    {
        $key = sprintf('keycloak:%s:%s:%s', $this->realm, $method, $endpoint);

        if (!empty($params)) {
            $key .= ':' . md5(serialize($params));
        }

        if ($this->bearerToken) {
            // Solo usamos los primeros 8 caracteres del token para el cache
            $key .= ':token:' . substr(md5($this->bearerToken), 0, 8);
        }

        return $key;
    }

    private function logRequest(string $method, string $endpoint, array $context = []): void
    {
        if (!$this->config['logging']['enabled'] ?? true) {
            return;
        }

        Log::channel('keycloak')->info('Keycloak API Request', [
            'method' => $method,
            'endpoint' => $endpoint,
            'realm' => $this->realm,
            'has_token' => !is_null($this->bearerToken),
            'timestamp' => now()->toISOString(),
            ...$context
        ]);
    }

    private function logResponse(LaravelResponse $response): void
    {
        if (!$this->config['logging']['enabled'] ?? true) {
            return;
        }

        Log::channel('keycloak')->info('Keycloak API Response', [
            'status' => $response->status(),
            'successful' => $response->successful(),
            'response_time' => $response->transferStats?->getTransferTime(),
            'timestamp' => now()->toISOString(),
        ]);
    }
}
