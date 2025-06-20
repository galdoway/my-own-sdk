<?php

namespace App\SDKs\KeycloakAdmin;

use AllowDynamicProperties;
use App\SDKs\KeycloakAdmin\Contracts\KeycloakAdminClientInterface;
use App\SDKs\KeycloakAdmin\Exceptions\KeycloakException;
use App\SDKs\KeycloakAdmin\Http\Client;
use App\SDKs\KeycloakAdmin\Resources\RoleResource;
use App\SDKs\KeycloakAdmin\Services\RoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
//use App\SDKs\KeycloakAdmin\Resources\GroupResource;
//use App\SDKs\KeycloakAdmin\Resources\RoleMappingResource;
//use App\SDKs\KeycloakAdmin\Services\GroupService;
//use App\SDKs\KeycloakAdmin\Services\RoleMappingService;

#[AllowDynamicProperties]
class KeycloakAdminClient implements KeycloakAdminClientInterface
{
    private Client $httpClient;
    private array $config;
    private ?string $bearerToken = 'eyJhbGciOiJSUzI1NiIsInR5cCIgOiAiSldUIiwia2lkIiA6ICJqUGJjYkdmd0F4OFFua0FKUzdveVBlOElqbDNSWG1WV1RSb1JfY1dVOE5RIn0.eyJleHAiOjE3NTA0MDExMDUsImlhdCI6MTc1MDQwMDgwNSwiYXV0aF90aW1lIjoxNzUwNDAwODA1LCJqdGkiOiJvbnJ0YWM6Y2M0NDc5ZmQtMmJiYS00YWNmLWFkNDEtMTk3YzJmYzY3ZDdiIiwiaXNzIjoiaHR0cHM6Ly9rZXljbG9hay5hbmVwaGVtZXJhbGFwcC54eXovcmVhbG1zL2xvY2FsaG9zdCIsImF1ZCI6WyJyZWFsbS1tYW5hZ2VtZW50IiwiYWNjb3VudCJdLCJzdWIiOiI4OWRkMGQzNC01ZmUwLTRiZmItYWE2OS1mMTBiYmFhOTU2MGEiLCJ0eXAiOiJCZWFyZXIiLCJhenAiOiJzcGEiLCJzaWQiOiJjZDA0YjZiNy1kOGMwLTQ4NDctODFmNS0wNTE4MGIzNTRkYTgiLCJhY3IiOiIxIiwiYWxsb3dlZC1vcmlnaW5zIjpbImh0dHA6Ly9sb2NhbGhvc3Q6NTE3MyJdLCJyZWFsbV9hY2Nlc3MiOnsicm9sZXMiOlsiZGVmYXVsdC1yb2xlcy1sb2NhbGhvc3QiLCJvZmZsaW5lX2FjY2VzcyIsInVtYV9hdXRob3JpemF0aW9uIl19LCJyZXNvdXJjZV9hY2Nlc3MiOnsicmVhbG0tbWFuYWdlbWVudCI6eyJyb2xlcyI6WyJ2aWV3LXJlYWxtIiwidmlldy1pZGVudGl0eS1wcm92aWRlcnMiLCJtYW5hZ2UtaWRlbnRpdHktcHJvdmlkZXJzIiwiaW1wZXJzb25hdGlvbiIsInJlYWxtLWFkbWluIiwiY3JlYXRlLWNsaWVudCIsIm1hbmFnZS11c2VycyIsInF1ZXJ5LXJlYWxtcyIsInZpZXctYXV0aG9yaXphdGlvbiIsInF1ZXJ5LWNsaWVudHMiLCJxdWVyeS11c2VycyIsIm1hbmFnZS1ldmVudHMiLCJtYW5hZ2UtcmVhbG0iLCJ2aWV3LWV2ZW50cyIsInZpZXctdXNlcnMiLCJ2aWV3LWNsaWVudHMiLCJtYW5hZ2UtYXV0aG9yaXphdGlvbiIsIm1hbmFnZS1jbGllbnRzIiwicXVlcnktZ3JvdXBzIl19LCJzcGEiOnsicm9sZXMiOlsicG9zdHM6dXBkYXRlIiwicG9zdHM6aW5kZXgiLCJwb3N0czpyZWFkIiwicG9zdHM6ZGVsZXRlIiwicG9zdHM6Y3JlYXRlIl19LCJhY2NvdW50Ijp7InJvbGVzIjpbIm1hbmFnZS1hY2NvdW50IiwibWFuYWdlLWFjY291bnQtbGlua3MiLCJ2aWV3LXByb2ZpbGUiXX19LCJzY29wZSI6Im9wZW5pZCBlbWFpbCBwcm9maWxlIiwiZW1haWxfdmVyaWZpZWQiOnRydWUsIm5hbWUiOiJBbGJlcnRvIEdhbGRhbWV6IiwicHJlZmVycmVkX3VzZXJuYW1lIjoiYWxiZXJ0by5nYWxkYW1lekBlbGFuaWluLmNvbSIsImdpdmVuX25hbWUiOiJBbGJlcnRvIiwiZmFtaWx5X25hbWUiOiJHYWxkYW1leiIsImVtYWlsIjoiYWxiZXJ0by5nYWxkYW1lekBlbGFuaWluLmNvbSJ9.NjqnKCuWjsln8DH-1-Zd929LQn-QRRAwa2_bprOjMJs0ZCu2EGqAZTMH-6oidm63TrjgnBV5AVnaHLiFOOcVjqRQDdWd3Qp5eUvkQJd28URolwPsqJuAButJ7SPRewA_Oc8_RBaK6pZMAB_s1Ch4RxOq01xZJeJJGD00PrVeHOLjDn1TCnUmhuaWeP83Av_vJg5_ho8dFffHJzNc9w5ADJP_mFbxM0BUwPB6vqQJfl9WkDwiZZeu891VNU3m2zqJ9-hXrI9mXSECd_XLEcT1zM2QkYOaeB7FfQQlfh6HzUdBZDmLyvMACPEHoQGImucgvl7d0YvrSTEk4TXgfaQobg';
    private string $realm;
    private array $stats = [];
    private bool $debugMode = false;
    private array $additionalHeaders = [];

    // Resource instances
    private ?RoleResource $roleResource = null;
//    private ?GroupResource $groupResource = null;
//    private ?RoleMappingResource $roleMappingResource = null;
//
//    // Service instances
//    private ?RoleService $roleService = null;
//    private ?GroupService $groupService = null;
//    private ?RoleMappingService $roleMappingService = null;

    public function __construct(?string $bearerToken = null, ?string $realm = null)
    {
        $this->config = config('keycloak-admin');
        $this->realm = $realm ?? $this->config['realm'];
        $this->bearerToken = $bearerToken;
        $this->httpClient = new Client();

        if ($this->bearerToken) {
            $this->setupAuthentication();
        }

        $this->initializeStats();
    }

    public static function fromRequest(Request $request): static
    {
        $token = $request->bearerToken();

        if (!$token) {
            throw new KeycloakException('No bearer token found in request');
        }

        return new static($token);
    }

    public static function withToken(string $bearerToken, ?string $realm = null): static
    {
        return new static($bearerToken, $realm);
    }

    public function roles(): RoleResource
    {
        if ($this->roleResource === null) {
            $this->roleResource = new RoleResource($this->httpClient, $this->realm);
        }

        return $this->roleResource;
    }

//    public function groups(): GroupResource
//    {
//        if ($this->groupResource === null) {
//            $this->groupResource = new GroupResource($this->httpClient, $this->realm);
//        }
//
//        return $this->groupResource;
//    }

//    public function roleMappings(): RoleMappingResource
//    {
//        if ($this->roleMappingResource === null) {
//            $this->roleMappingResource = new RoleMappingResource($this->httpClient, $this->realm);
//        }
//
//        return $this->roleMappingResource;
//    }

    public function roleService(): RoleService
    {
        if ($this->roleService === null) {
            $this->roleService = new RoleService($this->roles());
        }

        return $this->roleService;
    }

//    public function groupService(): GroupService
//    {
//        if ($this->groupService === null) {
//            $this->groupService = new GroupService($this->groups());
//        }
//
//        return $this->groupService;
//    }

//    public function roleMappingService(): RoleMappingService
//    {
//        if ($this->roleMappingService === null) {
//            $this->roleMappingService = new RoleMappingService($this->roleMappings());
//        }
//
//        return $this->roleMappingService;
//    }

    public function withBearerToken(string $bearerToken): self
    {
        $this->bearerToken = $bearerToken;
        $this->setupAuthentication();
        $this->resetResourceInstances();

        return $this;
    }

    public function withoutToken(): self
    {
        $this->bearerToken = null;
        $this->httpClient->withoutAuth();
        $this->resetResourceInstances();

        return $this;
    }

    public function withRealm(string $realm): self
    {
        $this->realm = $realm;
        $this->resetResourceInstances();

        return $this;
    }

    public function getRealm(): string
    {
        return $this->realm;
    }

    public function getToken(): ?string
    {
        return $this->bearerToken;
    }

    public function testConnection(): bool
    {
        try {
            $response = $this->httpClient->get('/admin/serverinfo');
            $this->incrementStats('connection_tests');

            return $response->successful();
        } catch (\Exception $e) {
            $this->logError('Connection test failed', $e);
            throw new KeycloakException('Connection test failed: ' . $e->getMessage());
        }
    }

    public function getServerInfo(): array
    {
        try {
            $response = $this->httpClient->get('/admin/serverinfo');
            $this->incrementStats('server_info_requests');

            return $response->json();
        } catch (\Exception $e) {
            $this->logError('Failed to get server info', $e);
            throw new KeycloakException('Failed to get server info: ' . $e->getMessage());
        }
    }

    public function getTokenInfo(): array
    {
        if (!$this->bearerToken) {
            throw new KeycloakException('No token available for introspection');
        }

        try {
            // This is a simplified version - in real implementation you'd call the introspection endpoint
            $response = $this->httpClient->get("/admin/realms/{$this->realm}/");
            $this->incrementStats('token_introspections');

            return $response->json();
        } catch (\Exception $e) {
            $this->logError('Token introspection failed', $e);
            throw new KeycloakException('Token introspection failed: ' . $e->getMessage());
        }
    }

    public function hasPermissions(array $permissions): bool
    {
        // Simplified permission check - in real implementation you'd validate against token claims
        try {
            foreach ($permissions as $permission) {
                if (!$this->checkSinglePermission($permission)) {
                    return false;
                }
            }

            return true;
        } catch (\Exception $e) {
            $this->logError('Permission check failed', $e);
            return false;
        }
    }

    public function canManageRoles(): bool
    {
        return $this->hasPermissions(['manage-realm', 'manage-roles']);
    }

    public function canManageGroups(): bool
    {
        return $this->hasPermissions(['manage-realm', 'manage-groups']);
    }

    public function canManageUsers(): bool
    {
        return $this->hasPermissions(['manage-realm', 'manage-users']);
    }

    public function canManageClients(): bool
    {
        return $this->hasPermissions(['manage-realm', 'manage-clients']);
    }

    public function withHeaders(array $headers): self
    {
        $this->additionalHeaders = array_merge($this->additionalHeaders, $headers);
        $this->httpClient->withHeaders($this->additionalHeaders);

        return $this;
    }

    public function withoutCache(): self
    {
        $this->httpClient->withoutCache();

        return $this;
    }

    public function debug(bool $enabled = true): self
    {
        $this->debugMode = $enabled;
        $this->httpClient->debug($enabled);

        return $this;
    }

    public function retry(int $times, int $sleepMilliseconds = 100): self
    {
        $this->httpClient->retry($times, $sleepMilliseconds);

        return $this;
    }

    public function getHttpClient(): Client
    {
        return $this->httpClient;
    }

    public function getConfig(): array
    {
        return [
            'server_url' => $this->config['server_url'],
            'realm' => $this->realm,
            'timeout' => $this->config['timeout'] ?? 30,
            'has_token' => !empty($this->bearerToken),
            'debug_mode' => $this->debugMode,
            'additional_headers' => $this->additionalHeaders,
        ];
    }

    public function clearCache(): bool
    {
        try {
            $this->httpClient->clearCache();
            $this->incrementStats('cache_clears');

            return true;
        } catch (\Exception $e) {
            $this->logError('Cache clear failed', $e);
            return false;
        }
    }

    public function reset(): self
    {
        $this->bearerToken = null;
        $this->realm = $this->config['default_realm'];
        $this->additionalHeaders = [];
        $this->debugMode = false;
        $this->resetResourceInstances();
        $this->httpClient->reset();

        return $this;
    }

    public function getStats(): array
    {
        return $this->stats;
    }

    public function validateToken(): array
    {
        if (!$this->bearerToken) {
            throw new KeycloakException('No token to validate');
        }

        try {
            $tokenInfo = $this->getTokenInfo();
            $permissions = $this->getCurrentUserRoles();

            return [
                'valid' => true,
                'token_info' => $tokenInfo,
                'permissions' => $permissions,
                'can_manage_roles' => $this->canManageRoles(),
                'can_manage_groups' => $this->canManageGroups(),
                'can_manage_users' => $this->canManageUsers(),
            ];
        } catch (\Exception $e) {
            throw new KeycloakException('Token validation failed: ' . $e->getMessage());
        }
    }

    public function getAccessibleRealms(): array
    {
        try {
            $response = $this->httpClient->get('/admin/realms');
            $this->incrementStats('realm_requests');

            return $response->json();
        } catch (\Exception $e) {
            $this->logError('Failed to get accessible realms', $e);
            throw new KeycloakException('Failed to get accessible realms: ' . $e->getMessage());
        }
    }

    public function getCurrentUser(): array
    {
        // This would typically decode the JWT token or call a user info endpoint
        try {
            $tokenInfo = $this->getTokenInfo();
            return $tokenInfo['user'] ?? [];
        } catch (\Exception $e) {
            $this->logError('Failed to get current user', $e);
            throw new KeycloakException('Failed to get current user: ' . $e->getMessage());
        }
    }

    public function getCurrentUserRoles(): array
    {
        try {
            // This would extract roles from token claims
            $tokenInfo = $this->getTokenInfo();
            return $tokenInfo['roles'] ?? [];
        } catch (\Exception $e) {
            $this->logError('Failed to get current user roles', $e);
            throw new KeycloakException('Failed to get current user roles: ' . $e->getMessage());
        }
    }

    public function batchOperations(array $operations): array
    {
        $results = [];

        foreach ($operations as $index => $operation) {
            try {
                $result = $this->executeSingleOperation($operation);
                $results[$index] = ['success' => true, 'data' => $result];
            } catch (\Exception $e) {
                $results[$index] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        $this->incrementStats('batch_operations');

        return $results;
    }

    public function exportRealm(
        bool $exportSecrets = false,
        bool $exportGroups = true,
        bool $exportClients = true
    ): array {
        try {
            $queryParams = [
                'exportSecrets' => $exportSecrets,
                'exportGroups' => $exportGroups,
                'exportClients' => $exportClients,
            ];

            $response = $this->httpClient->get("/admin/realms/{$this->realm}/partial-export", $queryParams);
            $this->incrementStats('realm_exports');

            return $response->json();
        } catch (\Exception $e) {
            $this->logError('Realm export failed', $e);
            throw new KeycloakException('Realm export failed: ' . $e->getMessage());
        }
    }

    public function healthCheck(): array
    {
        $status = [
            'overall' => 'healthy',
            'services' => [],
            'timestamp' => now()->toISOString(),
        ];

        // Test basic connection
        try {
            $this->testConnection();
            $status['services']['connection'] = 'healthy';
        } catch (\Exception $e) {
            $status['services']['connection'] = 'unhealthy';
            $status['overall'] = 'unhealthy';
        }

        // Test roles service
        try {
            $this->roles()->getAll(['max' => 1]);
            $status['services']['roles'] = 'healthy';
        } catch (\Exception $e) {
            $status['services']['roles'] = 'unhealthy';
            $status['overall'] = 'degraded';
        }

        // Test groups service
        try {
            $this->groups()->getAll(['max' => 1]);
            $status['services']['groups'] = 'healthy';
        } catch (\Exception $e) {
            $status['services']['groups'] = 'unhealthy';
            $status['overall'] = 'degraded';
        }

        $this->incrementStats('health_checks');

        return $status;
    }

    // Private helper methods
    private function setupAuthentication(): void
    {
        if ($this->bearerToken) {
            $this->httpClient->withAuth($this->bearerToken);
        }
    }

    private function resetResourceInstances(): void
    {
        $this->roleResource = null;
        $this->groupResource = null;
        $this->roleMappingResource = null;
        $this->roleService = null;
        $this->groupService = null;
        $this->roleMappingService = null;
    }

    private function checkSinglePermission(string $permission): bool
    {
        // Simplified permission check - in real implementation you'd check token claims
        return true; // This would contain actual logic to validate permissions
    }

    private function executeSingleOperation(array $operation): mixed
    {
        $type = $operation['type'] ?? throw new KeycloakException('Operation type is required');
        $params = $operation['params'] ?? [];

        return match ($type) {
            'get_roles' => $this->roles()->getAll($params),
            'get_groups' => $this->groups()->getAll($params),
            'create_role' => $this->roles()->create($params),
            'create_group' => $this->groups()->create($params),
            default => throw new KeycloakException("Unknown operation type: {$type}")
        };
    }

    private function initializeStats(): void
    {
        $this->stats = [
            'connection_tests' => 0,
            'server_info_requests' => 0,
            'token_introspections' => 0,
            'cache_clears' => 0,
            'realm_requests' => 0,
            'batch_operations' => 0,
            'realm_exports' => 0,
            'health_checks' => 0,
            'created_at' => now()->toISOString(),
        ];
    }

    private function incrementStats(string $key): void
    {
        if (isset($this->stats[$key])) {
            $this->stats[$key]++;
        }
    }

    private function logError(string $message, \Exception $e): void
    {
        if ($this->debugMode) {
            Log::error("KeycloakAdminClient: {$message}", [
                'exception' => $e->getMessage(),
                'realm' => $this->realm,
                'has_token' => !empty($this->bearerToken),
            ]);
        }
    }
}
