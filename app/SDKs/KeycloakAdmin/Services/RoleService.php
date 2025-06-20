<?php

namespace App\SDKs\KeycloakAdmin\Services;

use App\SDKs\KeycloakAdmin\Data\RoleData;
use App\SDKs\KeycloakAdmin\Enums\RoleType;
use App\SDKs\KeycloakAdmin\Exceptions\KeycloakException;
use App\SDKs\KeycloakAdmin\Exceptions\ResourceNotFoundException;
use App\SDKs\KeycloakAdmin\Resources\RoleResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RoleService
{
    public function __construct(
        private readonly RoleResource $roleResource
    ) {}

    /**
     * Get all roles with optional filtering and caching
     *
     * @param RoleType|null $type Filter by role type
     * @param string|null $clientId Required for client roles
     * @param bool $useCache Use cached results
     * @param int $cacheTtl Cache TTL in seconds
     * @return Collection<RoleData>
     */
    public function getAllRoles(
        ?RoleType $type = null,
        ?string $clientId = null,
        bool $useCache = true,
        int $cacheTtl = 300
    ): Collection {
        $cacheKey = "keycloak.roles.all.{$type?->value}.{$clientId}";

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $roles = collect();

        try {
            // Get realm roles
            if (!$type || $type === RoleType::REALM) {
                $realmRoles = $this->roleResource->getAllRealmRoles(false);
                $roles = $roles->merge($realmRoles);
            }

            // Get client roles
            if ((!$type || $type === RoleType::CLIENT) && $clientId) {
                $clientRoles = $this->roleResource->getAllClientRoles($clientId, false);
                $roles = $roles->merge($clientRoles);
            }

            if ($useCache) {
                Cache::put($cacheKey, $roles, $cacheTtl);
            }

            $this->logActivity('roles_retrieved', [
                'count' => $roles->count(),
                'type' => $type?->value,
                'client_id' => $clientId,
            ]);

            return $roles;
        } catch (KeycloakException $e) {
            $this->logError('failed_to_retrieve_roles', $e, [
                'type' => $type?->value,
                'client_id' => $clientId,
            ]);
            throw $e;
        }
    }

    /**
     * Create a new role with business logic validation
     *
     * @param string $name Role name
     * @param RoleType $type Role type
     * @param string|null $clientId Required for client roles
     * @param string|null $description Role description
     * @param array $attributes Custom attributes
     * @param bool $composite Whether role is composite
     * @return RoleData
     * @throws KeycloakException
     */
    public function createRole(
        string $name,
        RoleType $type,
        ?string $clientId = null,
        ?string $description = null,
        array $attributes = [],
        bool $composite = false
    ): RoleData {
        // Business logic validation
        $this->validateRoleCreation($name, $type, $clientId);

        $roleData = RoleData::forCreation(
            name: $name,
            description: $description,
            composite: $composite,
            clientRole: $type->isClientRole(),
            containerId: $clientId,
            attributes: $attributes
        );

        try {
            $success = match ($type) {
                RoleType::REALM => $this->roleResource->createRealmRole($roleData),
                RoleType::CLIENT => $this->roleResource->createClientRole($clientId, $roleData),
            };

            if (!$success) {
                throw new KeycloakException("Failed to create {$type->value} role '{$name}'");
            }

            // Clear relevant caches
            $this->clearRolesCaches($type, $clientId);

            // Retrieve the created role
            $createdRole = match ($type) {
                RoleType::REALM => $this->roleResource->findRealmRole($name),
                RoleType::CLIENT => $this->roleResource->findClientRole($clientId, $name),
            };

            $this->logActivity('role_created', [
                'role_name' => $name,
                'role_type' => $type->value,
                'client_id' => $clientId,
                'composite' => $composite,
            ]);

            return $createdRole;
        } catch (KeycloakException $e) {
            $this->logError('failed_to_create_role', $e, [
                'role_name' => $name,
                'role_type' => $type->value,
                'client_id' => $clientId,
            ]);
            throw $e;
        }
    }

    /**
     * Update a role with optimistic updates
     *
     * @param string $roleName Current role name
     * @param RoleType $type Role type
     * @param array $updates Fields to update
     * @param string|null $clientId Required for client roles
     * @return RoleData
     * @throws KeycloakException
     */
    public function updateRole(
        string $roleName,
        RoleType $type,
        array $updates,
        ?string $clientId = null
    ): RoleData {
        try {
            // Get current role data
            $currentRole = match ($type) {
                RoleType::REALM => $this->roleResource->findRealmRole($roleName),
                RoleType::CLIENT => $this->roleResource->findClientRole($clientId, $roleName),
            };

            // Create updated role data
            $updatedRole = $currentRole->forUpdate(
                name: $updates['name'] ?? null,
                description: $updates['description'] ?? null,
                composite: $updates['composite'] ?? null,
                attributes: $updates['attributes'] ?? null
            );

            // Validate the update
            $errors = $updatedRole->validateRole();
            if (!empty($errors)) {
                throw new KeycloakException('Role validation failed: ' . implode(', ', $errors));
            }

            // Perform the update
            $success = match ($type) {
                RoleType::REALM => $this->roleResource->updateRealmRole($roleName, $updatedRole),
                RoleType::CLIENT => $this->roleResource->updateClientRole($clientId, $roleName, $updatedRole),
            };

            if (!$success) {
                throw new KeycloakException("Failed to update {$type->value} role '{$roleName}'");
            }

            // Clear relevant caches
            $this->clearRolesCaches($type, $clientId);

            // Return updated role
            $finalRoleName = $updates['name'] ?? $roleName;
            $finalRole = match ($type) {
                RoleType::REALM => $this->roleResource->findRealmRole($finalRoleName),
                RoleType::CLIENT => $this->roleResource->findClientRole($clientId, $finalRoleName),
            };

            $this->logActivity('role_updated', [
                'role_name' => $roleName,
                'new_name' => $finalRoleName,
                'role_type' => $type->value,
                'client_id' => $clientId,
                'updates' => array_keys($updates),
            ]);

            return $finalRole;
        } catch (KeycloakException $e) {
            $this->logError('failed_to_update_role', $e, [
                'role_name' => $roleName,
                'role_type' => $type->value,
                'client_id' => $clientId,
                'updates' => $updates,
            ]);
            throw $e;
        }
    }

    /**
     * Delete a role with dependency checking
     *
     * @param string $roleName Role name
     * @param RoleType $type Role type
     * @param string|null $clientId Required for client roles
     * @param bool $force Force deletion even if role has dependencies
     * @return bool
     * @throws KeycloakException
     */
    public function deleteRole(
        string $roleName,
        RoleType $type,
        ?string $clientId = null,
        bool $force = false
    ): bool {
        try {
            // Check if role exists
            $role = match ($type) {
                RoleType::REALM => $this->roleResource->findRealmRole($roleName),
                RoleType::CLIENT => $this->roleResource->findClientRole($clientId, $roleName),
            };

            // Check for dependencies if not forcing
            if (!$force) {
                $this->validateRoleDeletion($role, $type, $clientId);
            }

            // Perform deletion
            $success = match ($type) {
                RoleType::REALM => $this->roleResource->deleteRealmRole($roleName),
                RoleType::CLIENT => $this->roleResource->deleteClientRole($clientId, $roleName),
            };

            if ($success) {
                // Clear relevant caches
                $this->clearRolesCaches($type, $clientId);

                $this->logActivity('role_deleted', [
                    'role_name' => $roleName,
                    'role_type' => $type->value,
                    'client_id' => $clientId,
                    'forced' => $force,
                ]);
            }

            return $success;
        } catch (KeycloakException $e) {
            $this->logError('failed_to_delete_role', $e, [
                'role_name' => $roleName,
                'role_type' => $type->value,
                'client_id' => $clientId,
            ]);
            throw $e;
        }
    }

    /**
     * Search roles with advanced filtering and ranking
     *
     * @param string $query Search query
     * @param RoleType|null $type Filter by role type
     * @param string|null $clientId Required for client role search
     * @param array $filters Additional filters
     * @return Collection<RoleData>
     */
    public function searchRoles(
        string $query,
        ?RoleType $type = null,
        ?string $clientId = null,
        array $filters = []
    ): Collection {
        try {
            $roles = $this->getAllRoles($type, $clientId);

            // Apply search query
            $filtered = $roles->filter(function (RoleData $role) use ($query) {
                $searchableText = strtolower(implode(' ', [
                    $role->name,
                    $role->description ?? '',
                    implode(' ', array_keys($role->attributes ?? [])),
                ]));

                return str_contains($searchableText, strtolower($query));
            });

            // Apply additional filters
            if (isset($filters['composite'])) {
                $filtered = $filtered->filter(fn(RoleData $role) =>
                    $role->composite === $filters['composite']
                );
            }

            if (isset($filters['has_description'])) {
                $filtered = $filtered->filter(fn(RoleData $role) =>
                $filters['has_description'] ? !empty($role->description) : empty($role->description)
                );
            }

            if (isset($filters['created_after'])) {
                $filtered = $filtered->filter(fn(RoleData $role) =>
                $role->getCreatedAt()?->isAfter($filters['created_after'])
                );
            }

            // Sort by relevance (exact matches first, then partial matches)
            $sorted = $filtered->sort(function (RoleData $a, RoleData $b) use ($query) {
                $queryLower = strtolower($query);

                $aExact = strtolower($a->name) === $queryLower ? 0 : 1;
                $bExact = strtolower($b->name) === $queryLower ? 0 : 1;

                if ($aExact !== $bExact) {
                    return $aExact <=> $bExact;
                }

                return strcmp($a->name, $b->name);
            });

            $this->logActivity('roles_searched', [
                'query' => $query,
                'results_count' => $sorted->count(),
                'type' => $type?->value,
                'client_id' => $clientId,
                'filters' => array_keys($filters),
            ]);

            return $sorted->values();
        } catch (KeycloakException $e) {
            $this->logError('failed_to_search_roles', $e, [
                'query' => $query,
                'type' => $type?->value,
                'client_id' => $clientId,
            ]);
            throw $e;
        }
    }

    /**
     * Manage composite role relationships
     *
     * @param string $parentRoleName Parent role name
     * @param RoleType $parentType Parent role type
     * @param Collection|array $childRoles Child roles to add/remove
     * @param string $action 'add' or 'remove'
     * @param string|null $clientId Required for client roles
     * @return bool
     * @throws KeycloakException
     */
    public function manageCompositeRoles(
        string $parentRoleName,
        RoleType $parentType,
        Collection|array $childRoles,
        string $action,
        ?string $clientId = null
    ): bool {
        if (!in_array($action, ['add', 'remove'])) {
            throw new KeycloakException("Invalid action '{$action}'. Must be 'add' or 'remove'.");
        }

        try {
            // Validate parent role exists and is composite
            $parentRole = match ($parentType) {
                RoleType::REALM => $this->roleResource->findRealmRole($parentRoleName),
                RoleType::CLIENT => $this->roleResource->findClientRole($clientId, $parentRoleName),
            };

            if (!$parentRole->composite && $action === 'add') {
                throw new KeycloakException("Role '{$parentRoleName}' must be composite to add child roles");
            }

            // Perform the operation
            $success = match ([$parentType, $action]) {
                [RoleType::REALM, 'add'] => $this->roleResource->addRealmRoleComposites($parentRoleName, $childRoles),
                [RoleType::REALM, 'remove'] => $this->roleResource->removeRealmRoleComposites($parentRoleName, $childRoles),
                [RoleType::CLIENT, 'add'] => $this->roleResource->addClientRoleComposites($clientId, $parentRoleName, $childRoles),
                [RoleType::CLIENT, 'remove'] => $this->roleResource->removeClientRoleComposites($clientId, $parentRoleName, $childRoles),
            };

            if ($success) {
                $this->clearRolesCaches($parentType, $clientId);

                $this->logActivity('composite_roles_managed', [
                    'parent_role' => $parentRoleName,
                    'parent_type' => $parentType->value,
                    'action' => $action,
                    'child_count' => is_array($childRoles) ? count($childRoles) : $childRoles->count(),
                    'client_id' => $clientId,
                ]);
            }

            return $success;
        } catch (KeycloakException $e) {
            $this->logError('failed_to_manage_composite_roles', $e, [
                'parent_role' => $parentRoleName,
                'parent_type' => $parentType->value,
                'action' => $action,
                'client_id' => $clientId,
            ]);
            throw $e;
        }
    }

    /**
     * Get role hierarchy and dependencies
     *
     * @param string $roleName Role name
     * @param RoleType $type Role type
     * @param string|null $clientId Required for client roles
     * @return array
     */
    public function getRoleHierarchy(
        string $roleName,
        RoleType $type,
        ?string $clientId = null
    ): array {
        try {
            $role = match ($type) {
                RoleType::REALM => $this->roleResource->findRealmRole($roleName),
                RoleType::CLIENT => $this->roleResource->findClientRole($clientId, $roleName),
            };

            $hierarchy = [
                'role' => $role,
                'children' => collect(),
                'parents' => collect(),
                'depth' => 0,
            ];

            // Get child roles if composite
            if ($role->composite) {
                $hierarchy['children'] = match ($type) {
                    RoleType::REALM => $this->roleResource->getRealmRoleComposites($roleName, false),
                    RoleType::CLIENT => $this->roleResource->getClientRoleComposites($clientId, $roleName, false),
                };
            }

            // TODO: Get parent roles (would need additional API calls)
            // This would require searching all composite roles to find which ones include this role

            return $hierarchy;
        } catch (KeycloakException $e) {
            $this->logError('failed_to_get_role_hierarchy', $e, [
                'role_name' => $roleName,
                'role_type' => $type->value,
                'client_id' => $clientId,
            ]);
            throw $e;
        }
    }

    /**
     * Bulk operations for multiple roles
     *
     * @param array $operations Array of operations to perform
     * @return array Results of each operation
     */
    public function bulkOperations(array $operations): array
    {
        $results = [];
        $successCount = 0;
        $errorCount = 0;

        foreach ($operations as $index => $operation) {
            try {
                $result = match ($operation['action']) {
                    'create' => $this->createRole(
                        $operation['name'],
                        RoleType::from($operation['type']),
                        $operation['client_id'] ?? null,
                        $operation['description'] ?? null,
                        $operation['attributes'] ?? [],
                        $operation['composite'] ?? false
                    ),
                    'update' => $this->updateRole(
                        $operation['name'],
                        RoleType::from($operation['type']),
                        $operation['updates'],
                        $operation['client_id'] ?? null
                    ),
                    'delete' => $this->deleteRole(
                        $operation['name'],
                        RoleType::from($operation['type']),
                        $operation['client_id'] ?? null,
                        $operation['force'] ?? false
                    ),
                    default => throw new KeycloakException("Unknown action: {$operation['action']}")
                };

                $results[$index] = ['success' => true, 'result' => $result];
                $successCount++;
            } catch (KeycloakException $e) {
                $results[$index] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'operation' => $operation
                ];
                $errorCount++;
            }
        }

        $this->logActivity('bulk_operations_completed', [
            'total_operations' => count($operations),
            'successful' => $successCount,
            'failed' => $errorCount,
        ]);

        return [
            'results' => $results,
            'summary' => [
                'total' => count($operations),
                'successful' => $successCount,
                'failed' => $errorCount,
            ]
        ];
    }

    /**
     * Validate role creation business rules
     *
     * @param string $name Role name
     * @param RoleType $type Role type
     * @param string|null $clientId Client ID for client roles
     * @throws KeycloakException
     */
    private function validateRoleCreation(string $name, RoleType $type, ?string $clientId): void
    {
        // Check for reserved role names
        $reservedNames = ['admin', 'default-roles-realm', 'offline_access', 'uma_authorization'];
        if (in_array(strtolower($name), $reservedNames)) {
            throw new KeycloakException("Role name '{$name}' is reserved and cannot be used");
        }

        // Validate client ID for client roles
        if ($type === RoleType::CLIENT && empty($clientId)) {
            throw new KeycloakException('Client ID is required for client roles');
        }

        // Check if role already exists
        if ($this->roleResource->exists($name, $type, $clientId)) {
            throw new KeycloakException("Role '{$name}' already exists");
        }
    }

    /**
     * Validate role deletion business rules
     *
     * @param RoleData $role Role to delete
     * @param RoleType $type Role type
     * @param string|null $clientId Client ID
     * @throws KeycloakException
     */
    private function validateRoleDeletion(RoleData $role, RoleType $type, ?string $clientId): void
    {
        // Check for system roles that shouldn't be deleted
        $protectedRoles = ['admin', 'create-realm', 'default-roles-realm'];
        if (in_array($role->name, $protectedRoles)) {
            throw new KeycloakException("Role '{$role->name}' is protected and cannot be deleted");
        }

        // TODO: Check for role usage (users, groups, composites)
        // This would require additional API calls to determine if the role is in use
    }

    /**
     * Clear role-related caches
     *
     * @param RoleType $type Role type
     * @param string|null $clientId Client ID
     */
    private function clearRolesCaches(RoleType $type, ?string $clientId = null): void
    {
        $patterns = [
            "keycloak.roles.all.{$type->value}.{$clientId}",
            "keycloak.roles.all..{$clientId}",
            "keycloak.roles.all.{$type->value}.",
            "keycloak.roles.all..",
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    /**
     * Log service activity
     *
     * @param string $action Action performed
     * @param array $context Additional context
     */
    private function logActivity(string $action, array $context = []): void
    {
        Log::info('Keycloak Role Service Activity', [
            'service' => 'RoleService',
            'action' => $action,
            'timestamp' => now()->toISOString(),
            ...$context
        ]);
    }

    /**
     * Log service errors
     *
     * @param string $action Action that failed
     * @param KeycloakException $exception Exception that occurred
     * @param array $context Additional context
     */
    private function logError(string $action, KeycloakException $exception, array $context = []): void
    {
        Log::error('Keycloak Role Service Error', [
            'service' => 'RoleService',
            'action' => $action,
            'error' => $exception->getMessage(),
            'exception_class' => get_class($exception),
            'timestamp' => now()->toISOString(),
            ...$context
        ]);
    }
}
