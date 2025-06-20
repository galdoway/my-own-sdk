<?php

namespace App\SDKs\KeycloakAdmin\Contracts;

use App\SDKs\KeycloakAdmin\Exceptions\KeycloakException;
use App\SDKs\KeycloakAdmin\Http\Client;
use App\SDKs\KeycloakAdmin\Resources\RoleResource;
//use App\SDKs\KeycloakAdmin\Resources\GroupResource;
//use App\SDKs\KeycloakAdmin\Resources\RoleMappingResource;
use App\SDKs\KeycloakAdmin\Services\RoleService;
//use App\SDKs\KeycloakAdmin\Services\GroupService;
//use App\SDKs\KeycloakAdmin\Services\RoleMappingService;
use Illuminate\Http\Request;

interface KeycloakAdminClientInterface
{
    /**
     * Create client instance from Laravel request
     *
     * @param Request $request HTTP request containing a bearer token
     * @return static
     * @throws KeycloakException When a token is missing or invalid
     */
    public static function fromRequest(Request $request): static;

    /**
     * Create client instance with specific token
     *
     * @param string $bearerToken Bearer token from user authentication
     * @param string|null $realm Optional realm override
     * @return static
     */
    public static function withToken(string $bearerToken, ?string $realm = null): static;

    /**
     * Get Roles resource for low-level API operations
     *
     * @return RoleResource
     */
    public function roles(): RoleResource;

//    /**
//     * Get Groups resource for low-level API operations
//     *
//     * @return GroupResource
//     */
//    public function groups(): GroupResource;
//
//    /**
//     * Get Role Mappings resource for low-level API operations
//     *
//     * @return RoleMappingResource
//     */
//    public function roleMappings(): RoleMappingResource;

    /**
     * Get Role service for business logic operations
     *
     * @return RoleService
     */
    public function roleService(): RoleService;
//
//    /**
//     * Get Group service for business logic operations
//     *
//     * @return GroupService
//     */
//    public function groupService(): GroupService;
//
//    /**
//     * Get Role Mapping service for business logic operations
//     *
//     * @return RoleMappingService
//     */
//    public function roleMappingService(): RoleMappingService;

    /**
     * Set or change the bearer token
     *
     * @param string $bearerToken New bearer token
     * @return self
     */
    public function withBearerToken(string $bearerToken): self;

    /**
     * Remove the bearer token
     *
     * @return self
     */
    public function withoutToken(): self;

    /**
     * Change the realm for operations
     *
     * @param string $realm New realm name
     * @return self
     */
    public function withRealm(string $realm): self;

    /**
     * Get current realm name
     *
     * @return string
     */
    public function getRealm(): string;

    /**
     * Get current bearer token
     *
     * @return string|null
     */
    public function getToken(): ?string;

    /**
     * Test connection to Keycloak server
     *
     * @return bool True if the connection is successful
     * @throws KeycloakException When connection fails
     */
    public function testConnection(): bool;

    /**
     * Get server information
     *
     * @return array Server info including a version, themes, etc.
     * @throws KeycloakException When request fails
     */
    public function getServerInfo(): array;

    /**
     * Get the current user's token info (introspection)
     *
     * @return array Token information
     * @throws KeycloakException When a token is invalid
     */
    public function getTokenInfo(): array;

    /**
     * Check if the current token has specific permissions
     *
     * @param array $permissions Required permissions
     * @return bool True if all permissions are present
     */
    public function hasPermissions(array $permissions): bool;

    /**
     * Check if the current token can manage roles
     *
     * @return bool
     */
    public function canManageRoles(): bool;

    /**
     * Check if the current token can manage groups
     *
     * @return bool
     */
    public function canManageGroups(): bool;

    /**
     * Check if the current token can manage users
     *
     * @return bool
     */
    public function canManageUsers(): bool;

    /**
     * Check if the current token can manage clients
     *
     * @return bool
     */
    public function canManageClients(): bool;

    /**
     * Set additional headers for requests
     *
     * @param array $headers Additional headers
     * @return self
     */
    public function withHeaders(array $headers): self;

    /**
     * Disable caching for subsequent requests
     *
     * @return self
     */
    public function withoutCache(): self;

    /**
     * Enable debug mode for detailed logging
     *
     * @param bool $enabled Debug mode enabled
     * @return self
     */
    public function debug(bool $enabled = true): self;

    /**
     * Set retry configuration
     *
     * @param int $times Number of retry attempts
     * @param int $sleepMilliseconds Delay between retries
     * @return self
     */
    public function retry(int $times, int $sleepMilliseconds = 100): self;

    /**
     * Get the underlying HTTP client for advanced usage
     *
     * @return Client
     */
    public function getHttpClient(): Client;

    /**
     * Get current configuration
     *
     * @return array Current client configuration
     */
    public function getConfig(): array;

    /**
     * Clear all cached responses
     *
     * @return bool True if cache was cleared successfully
     */
    public function clearCache(): bool;

    /**
     * Reset client to default configuration
     *
     * @return self
     */
    public function reset(): self;

    /**
     * Get client statistics
     *
     * @return array Usage statistics
     */
    public function getStats(): array;

    /**
     * Validate current token and permissions
     *
     * @return array Validation results
     * @throws KeycloakException When token is invalid
     */
    public function validateToken(): array;

    /**
     * Get available realms accessible to current token
     *
     * @return array List of accessible realms
     * @throws KeycloakException When request fails
     */
    public function getAccessibleRealms(): array;

    /**
     * Get current user information from token
     *
     * @return array User information
     * @throws KeycloakException When token is invalid
     */
    public function getCurrentUser(): array;

    /**
     * Get roles assigned to current user
     *
     * @return array Current user's roles
     * @throws KeycloakException When request fails
     */
    public function getCurrentUserRoles(): array;

    /**
     * Batch operations for multiple resources
     *
     * @param array $operations Array of operations to perform
     * @return array Results of each operation
     * @throws KeycloakException When operations fail
     */
    public function batchOperations(array $operations): array;

    /**
     * Export realm configuration
     *
     * @param bool $exportSecrets Include secrets in export
     * @param bool $exportGroups Include groups in export
     * @param bool $exportClients Include clients in export
     * @return array Realm configuration
     * @throws KeycloakException When export fails
     */
    public function exportRealm(
        bool $exportSecrets = false,
        bool $exportGroups = true,
        bool $exportClients = true
    ): array;

    /**
     * Health check for all services
     *
     * @return array Health status of all services
     */
    public function healthCheck(): array;
}
