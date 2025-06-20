<?php

namespace App\SDKs\KeycloakAdmin\Contracts;

use App\SDKs\KeycloakAdmin\Data\RoleData;
use App\SDKs\KeycloakAdmin\Enums\RoleType;
use App\SDKs\KeycloakAdmin\Exceptions\KeycloakException;
use App\SDKs\KeycloakAdmin\Exceptions\ResourceNotFoundException;
use Illuminate\Support\Collection;

interface RoleServiceInterface
{
    /**
     * Get all roles with optional filtering and caching
     *
     * @param RoleType|null $type Filter by role type (realm/client)
     * @param string|null $clientId Required for client roles
     * @param bool $useCache Use cached results if available
     * @param int $cacheTtl Cache TTL in seconds
     * @return Collection<RoleData> Collection of role data objects
     * @throws KeycloakException When request fails
     */
    public function getAllRoles(
        ?RoleType $type = null,
        ?string $clientId = null,
        bool $useCache = true,
        int $cacheTtl = 300
    ): Collection;

    /**
     * Create a new role with business logic validation
     *
     * @param string $name Role name (must be unique within scope)
     * @param RoleType $type Role type (realm or client)
     * @param string|null $clientId Required for client roles
     * @param string|null $description Optional role description
     * @param array $attributes Custom attributes for the role
     * @param bool $composite Whether the role is composite
     * @return RoleData Created role data
     * @throws KeycloakException When creation fails or validation errors occur
     */
    public function createRole(
        string $name,
        RoleType $type,
        ?string $clientId = null,
        ?string $description = null,
        array $attributes = [],
        bool $composite = false
    ): RoleData;

    /**
     * Update an existing role with optimistic updates
     *
     * @param string $roleName Current role name
     * @param RoleType $type Role type (realm or client)
     * @param array $updates Fields to update (name, description, composite, attributes)
     * @param string|null $clientId Required for client roles
     * @return RoleData Updated role data
     * @throws ResourceNotFoundException When a role doesn't exist
     * @throws KeycloakException When update fails or validation errors occur
     */
    public function updateRole(
        string $roleName,
        RoleType $type,
        array $updates,
        ?string $clientId = null
    ): RoleData;

    /**
     * Delete a role with dependency checking
     *
     * @param string $roleName Role name to delete
     * @param RoleType $type Role type (realm or client)
     * @param string|null $clientId Required for client roles
     * @param bool $force Force deletion even if a role has dependencies
     * @return bool True if deletion was successful
     * @throws ResourceNotFoundException When a role doesn't exist
     * @throws KeycloakException When deletion fails or role has dependencies
     */
    public function deleteRole(
        string $roleName,
        RoleType $type,
        ?string $clientId = null,
        bool $force = false
    ): bool;

    /**
     * Search roles with advanced filtering and relevance ranking
     *
     * @param string $query Search query (searches name, description, attributes)
     * @param RoleType|null $type Filter by role type
     * @param string|null $clientId Required for client role search
     * @param array $filters Additional filters (composite, has_description, created_after)
     * @return Collection<RoleData> Filtered and ranked collection of roles
     * @throws KeycloakException When search fails
     */
    public function searchRoles(
        string $query,
        ?RoleType $type = null,
        ?string $clientId = null,
        array $filters = []
    ): Collection;

    /**
     * Manage composite role relationships
     *
     * @param string $parentRoleName Parent role name
     * @param RoleType $parentType Parent role type
     * @param Collection|array $childRoles Child roles to add/remove
     * @param string $action Action to perform ('add' or 'remove')
     * @param string|null $clientId Required for client roles
     * @return bool True if operation was successful
     * @throws ResourceNotFoundException When parent role doesn't exist
     * @throws KeycloakException When operation fails or invalid action
     */
    public function manageCompositeRoles(
        string $parentRoleName,
        RoleType $parentType,
        Collection|array $childRoles,
        string $action,
        ?string $clientId = null
    ): bool;

    /**
     * Get role hierarchy and dependencies
     *
     * @param string $roleName Role name to analyze
     * @param RoleType $type Role type
     * @param string|null $clientId Required for client roles
     * @return array Hierarchy information with role, children, parents, depth
     * @throws ResourceNotFoundException When role doesn't exist
     * @throws KeycloakException When request fails
     */
    public function getRoleHierarchy(
        string $roleName,
        RoleType $type,
        ?string $clientId = null
    ): array;

    /**
     * Bulk operations for multiple roles
     *
     * @param array $operations Array of operations with action, name, type, etc.
     * @return array Results with success/failure status and summary
     * @throws KeycloakException When bulk operation setup fails
     */
    public function bulkOperations(array $operations): array;

    /**
     * Get roles by specific criteria
     *
     * @param RoleType $type Role type to filter
     * @param string|null $clientId Required for client roles
     * @param bool $compositesOnly Only return composite roles
     * @param bool $simpleOnly Only return simple (non-composite) roles
     * @return Collection<RoleData> Filtered collection of roles
     * @throws KeycloakException When request fails
     */
    public function getRolesByType(
        RoleType $type,
        ?string $clientId = null,
        bool $compositesOnly = false,
        bool $simpleOnly = false
    ): Collection;

    /**
     * Check if a role exists
     *
     * @param string $roleName Role name to check
     * @param RoleType $type Role type
     * @param string|null $clientId Required for client roles
     * @return bool True if role exists
     * @throws KeycloakException When request fails
     */
    public function roleExists(
        string $roleName,
        RoleType $type,
        ?string $clientId = null
    ): bool;

    /**
     * Get roles with usage statistics
     *
     * @param RoleType $type Role type to analyze
     * @param string|null $clientId Required for client roles
     * @return Collection Usage statistics for each role
     * @throws KeycloakException When request fails
     */
    public function getRolesWithUsageStats(
        RoleType $type,
        ?string $clientId = null
    ): Collection;

    /**
     * Find role by name with type detection
     *
     * @param string $roleName Role name to find
     * @param RoleType $type Role type
     * @param string|null $clientId Required for client roles
     * @return RoleData Found role data
     * @throws ResourceNotFoundException When role doesn't exist
     * @throws KeycloakException When request fails
     */
    public function findRole(
        string $roleName,
        RoleType $type,
        ?string $clientId = null
    ): RoleData;

    /**
     * Get composite children of a role
     *
     * @param string $roleName Parent role name
     * @param RoleType $type Role type
     * @param string|null $clientId Required for client roles
     * @param bool $recursive Get all descendants recursively
     * @return Collection<RoleData> Child roles
     * @throws ResourceNotFoundException When role doesn't exist
     * @throws KeycloakException When request fails
     */
    public function getCompositeChildren(
        string $roleName,
        RoleType $type,
        ?string $clientId = null,
        bool $recursive = false
    ): Collection;

    /**
     * Add multiple child roles to a composite role
     *
     * @param string $parentRoleName Parent role name
     * @param RoleType $parentType Parent role type
     * @param Collection|array $childRoles Child roles to add
     * @param string|null $clientId Required for client roles
     * @return bool True if successful
     * @throws ResourceNotFoundException When parent role doesn't exist
     * @throws KeycloakException When operation fails
     */
    public function addCompositeChildren(
        string $parentRoleName,
        RoleType $parentType,
        Collection|array $childRoles,
        ?string $clientId = null
    ): bool;

    /**
     * Remove multiple child roles from a composite role
     *
     * @param string $parentRoleName Parent role name
     * @param RoleType $parentType Parent role type
     * @param Collection|array $childRoles Child roles to remove
     * @param string|null $clientId Required for client roles
     * @return bool True if successful
     * @throws ResourceNotFoundException When parent role doesn't exist
     * @throws KeycloakException When operation fails
     */
    public function removeCompositeChildren(
        string $parentRoleName,
        RoleType $parentType,
        Collection|array $childRoles,
        ?string $clientId = null
    ): bool;

    /**
     * Validate role data before operations
     *
     * @param RoleData|array $roleData Role data to validate
     * @param string $operation Operation type (create, update, delete)
     * @return array Validation errors (empty if valid)
     */
    public function validateRoleData(
        RoleData|array $roleData,
        string $operation = 'create'
    ): array;

    /**
     * Get role suggestions based on partial name
     *
     * @param string $partialName Partial role name
     * @param RoleType|null $type Filter by role type
     * @param string|null $clientId Required for client role suggestions
     * @param int $limit Maximum suggestions to return
     * @return Collection<RoleData> Suggested roles
     * @throws KeycloakException When request fails
     */
    public function getRoleSuggestions(
        string $partialName,
        ?RoleType $type = null,
        ?string $clientId = null,
        int $limit = 10
    ): Collection;

    /**
     * Clone a role with new name
     *
     * @param string $sourceRoleName Source role name
     * @param string $newRoleName New role name
     * @param RoleType $type Role type
     * @param string|null $clientId Required for client roles
     * @param bool $cloneComposites Also clone composite relationships
     * @return RoleData Cloned role data
     * @throws ResourceNotFoundException When source role doesn't exist
     * @throws KeycloakException When cloning fails
     */
    public function cloneRole(
        string $sourceRoleName,
        string $newRoleName,
        RoleType $type,
        ?string $clientId = null,
        bool $cloneComposites = false
    ): RoleData;

    /**
     * Get role audit log/history
     *
     * @param string $roleName Role name
     * @param RoleType $type Role type
     * @param string|null $clientId Required for client roles
     * @param int $limit Maximum entries to return
     * @return array Audit log entries
     * @throws ResourceNotFoundException When a role doesn't exist
     * @throws KeycloakException When request fails
     */
    public function getRoleAuditLog(
        string $roleName,
        RoleType $type,
        ?string $clientId = null,
        int $limit = 50
    ): array;
}
