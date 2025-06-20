<?php

namespace App\SDKs\KeycloakAdmin\Resources;

use App\SDKs\KeycloakAdmin\Data\RoleData;
use App\SDKs\KeycloakAdmin\Enums\RoleType;
use App\SDKs\KeycloakAdmin\Exceptions\ResourceNotFoundException;
use App\SDKs\KeycloakAdmin\Http\Client;
use App\SDKs\KeycloakAdmin\Http\Response;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;

class RoleResource extends BaseResource
{
    public function __construct(Client $client)
    {
        parent::__construct($client);
    }

    /**
     * Get all realm roles
     *
     * @param bool $briefRepresentation Only return basic info
     * @return Collection<RoleData>
     * @throws ConnectionException
     */
    public function getAllRealmRoles(bool $briefRepresentation = true): Collection
    {
        $response = $this->client->get('/roles', [
            'briefRepresentation' => $briefRepresentation
        ]);

        return $response->roles()->map(fn($role) => RoleData::from($role));
    }

    /**
     * Get all client roles for a specific client
     *
     * @param string $clientId Client UUID (not client-id)
     * @param bool $briefRepresentation Only return basic info
     * @return Collection<RoleData>
     * @throws ConnectionException
     */
    public function getAllClientRoles(string $clientId, bool $briefRepresentation = true): Collection
    {
        $response = $this->client->get("/clients/{$clientId}/roles", [
            'briefRepresentation' => $briefRepresentation
        ]);

        return $response->roles()->map(fn($role) => RoleData::from($role));
    }

    /**
     * Find a realm role by name
     *
     * @param string $roleName Role name
     * @return RoleData
     * @throws ResourceNotFoundException
     * @throws ConnectionException
     */
    public function findRealmRole(string $roleName): RoleData
    {
        try {
            $response = $this->client->get("/roles/{$roleName}");
            return RoleData::from($response->item());
        } catch (ResourceNotFoundException) {
            throw ResourceNotFoundException::roleByName($roleName);
        }
    }

    /**
     * Find a client role by name
     *
     * @param string $clientId Client UUID
     * @param string $roleName Role name
     * @return RoleData
     * @throws ResourceNotFoundException
     * @throws ConnectionException
     */
    public function findClientRole(string $clientId, string $roleName): RoleData
    {
        try {
            $response = $this->client->get("/clients/{$clientId}/roles/{$roleName}");
            return RoleData::from($response->item());
        } catch (ResourceNotFoundException) {
            throw ResourceNotFoundException::roleByName($roleName);
        }
    }

    /**
     * Find a role by ID (works for both realm and client roles)
     *
     * @param string $roleId Role ID
     * @return RoleData
     * @throws ResourceNotFoundException
     * @throws ConnectionException
     */
    public function findById(string $roleId): RoleData
    {
        try {
            $response = $this->client->get("/roles-by-id/{$roleId}");
            return RoleData::from($response->item());
        } catch (ResourceNotFoundException) {
            throw ResourceNotFoundException::role($roleId);
        }
    }

    /**
     * Create a new realm role
     *
     * @param RoleData|array $roleData Role data
     * @return bool
     * @throws ConnectionException
     */
    public function createRealmRole(RoleData|array $roleData): bool
    {
        $data = $roleData instanceof RoleData ? $roleData->toKeycloakArray() : $roleData;

        // Ensure it's a realm role
        $data['clientRole'] = false;
        unset($data['id']); // Remove ID for creation

        $response = $this->client->post('/roles', $data);
        return $response->isCreated() || $response->isNoContent();
    }

    /**
     * Create a new client role
     *
     * @param string $clientId Client UUID
     * @param RoleData|array $roleData Role data
     * @return bool
     * @throws ConnectionException
     */
    public function createClientRole(string $clientId, RoleData|array $roleData): bool
    {
        $data = $roleData instanceof RoleData ? $roleData->toKeycloakArray() : $roleData;

        // Ensure it's a client role
        $data['clientRole'] = true;
        $data['containerId'] = $clientId;
        unset($data['id']); // Remove ID for creation

        $response = $this->client->post("/clients/{$clientId}/roles", $data);
        return $response->isCreated() || $response->isNoContent();
    }

    /**
     * Update a realm role
     *
     * @param string $roleName Current role name
     * @param RoleData|array $roleData Updated role data
     * @return bool
     * @throws ConnectionException
     */
    public function updateRealmRole(string $roleName, RoleData|array $roleData): bool
    {
        $data = $roleData instanceof RoleData ? $roleData->toKeycloakArray() : $roleData;

        $response = $this->client->put("/roles/{$roleName}", $data);
        return $response->isNoContent();
    }

    /**
     * Update a client role
     *
     * @param string $clientId Client UUID
     * @param string $roleName Current role name
     * @param RoleData|array $roleData Updated role data
     * @return bool
     * @throws ConnectionException
     */
    public function updateClientRole(string $clientId, string $roleName, RoleData|array $roleData): bool
    {
        $data = $roleData instanceof RoleData ? $roleData->toKeycloakArray() : $roleData;

        $response = $this->client->put("/clients/{$clientId}/roles/{$roleName}", $data);
        return $response->isNoContent();
    }

    /**
     * Update a role by ID
     *
     * @param string $roleId Role ID
     * @param RoleData|array $roleData Updated role data
     * @return bool
     * @throws ConnectionException
     */
    public function updateById(string $roleId, RoleData|array $roleData): bool
    {
        $data = $roleData instanceof RoleData ? $roleData->toKeycloakArray() : $roleData;

        $response = $this->client->put("/roles-by-id/{$roleId}", $data);
        return $response->isNoContent();
    }

    /**
     * Delete a realm role
     *
     * @param string $roleName Role name
     * @return bool
     * @throws ConnectionException
     */
    public function deleteRealmRole(string $roleName): bool
    {
        $response = $this->client->delete("/roles/{$roleName}");
        return $response->isNoContent();
    }

    /**
     * Delete a client role
     *
     * @param string $clientId Client UUID
     * @param string $roleName Role name
     * @return bool
     * @throws ConnectionException
     */
    public function deleteClientRole(string $clientId, string $roleName): bool
    {
        $response = $this->client->delete("/clients/{$clientId}/roles/{$roleName}");
        return $response->isNoContent();
    }

    /**
     * Delete a role by ID
     *
     * @param string $roleId Role ID
     * @return bool
     * @throws ConnectionException
     */
    public function deleteById(string $roleId): bool
    {
        $response = $this->client->delete("/roles-by-id/{$roleId}");
        return $response->isNoContent();
    }

    // Composite Roles Methods

    /**
     * Get composite roles for a realm role
     *
     * @param string $roleName Role name
     * @param bool $briefRepresentation Only return basic info
     * @return Collection<RoleData>
     * @throws ConnectionException
     */
    public function getRealmRoleComposites(string $roleName, bool $briefRepresentation = true): Collection
    {
        $response = $this->client->get("/roles/{$roleName}/composites", [
            'briefRepresentation' => $briefRepresentation
        ]);

        return $response->roles()->map(fn($role) => RoleData::from($role));
    }

    /**
     * Get composite roles for a client role
     *
     * @param string $clientId Client UUID
     * @param string $roleName Role name
     * @param bool $briefRepresentation Only return basic info
     * @return Collection<RoleData>
     * @throws ConnectionException
     */
    public function getClientRoleComposites(string $clientId, string $roleName, bool $briefRepresentation = true): Collection
    {
        $response = $this->client->get("/clients/{$clientId}/roles/{$roleName}/composites", [
            'briefRepresentation' => $briefRepresentation
        ]);

        return $response->roles()->map(fn($role) => RoleData::from($role));
    }

    /**
     * Add composite roles to a realm role
     *
     * @param string $roleName Parent role name
     * @param Collection|array $childRoles Child roles to add
     * @return bool
     * @throws ConnectionException
     */
    public function addRealmRoleComposites(string $roleName, Collection|array $childRoles): bool
    {
        $roles = $this->prepareRolesData($childRoles);

        $response = $this->client->post("/roles/{$roleName}/composites", $roles);
        return $response->isNoContent();
    }

    /**
     * Add composite roles to a client role
     *
     * @param string $clientId Client UUID
     * @param string $roleName Parent role name
     * @param Collection|array $childRoles Child roles to add
     * @return bool
     * @throws ConnectionException
     */
    public function addClientRoleComposites(string $clientId, string $roleName, Collection|array $childRoles): bool
    {
        $roles = $this->prepareRolesData($childRoles);

        $response = $this->client->post("/clients/{$clientId}/roles/{$roleName}/composites", $roles);
        return $response->isNoContent();
    }

    /**
     * Remove composite roles from a realm role
     *
     * @param string $roleName Parent role name
     * @param Collection|array $childRoles Child roles to remove
     * @return bool
     * @throws ConnectionException
     */
    public function removeRealmRoleComposites(string $roleName, Collection|array $childRoles): bool
    {
        $roles = $this->prepareRolesData($childRoles);

        $response = $this->client->delete("/roles/{$roleName}/composites", $roles);
        return $response->isNoContent();
    }

    /**
     * Remove composite roles from a client role
     *
     * @param string $clientId Client UUID
     * @param string $roleName Parent role name
     * @param Collection|array $childRoles Child roles to remove
     * @return bool
     * @throws ConnectionException
     */
    public function removeClientRoleComposites(string $clientId, string $roleName, Collection|array $childRoles): bool
    {
        $roles = $this->prepareRolesData($childRoles);

        $response = $this->client->delete("/clients/{$clientId}/roles/{$roleName}/composites", $roles);
        return $response->isNoContent();
    }

    // Utility Methods

    /**
     * Search roles by name (supports wildcards)
     *
     * @param string $search Search term
     * @param RoleType|null $type Role type filter
     * @param string|null $clientId Client ID for client roles
     * @return Collection<RoleData>
     * @throws ConnectionException
     */
    public function search(string $search, ?RoleType $type = null, ?string $clientId = null): Collection
    {
        $roles = collect();

        // Search realm roles
        if (!$type || $type === RoleType::REALM) {
            $realmRoles = $this->getAllRealmRoles(false)
                ->filter(fn(RoleData $role) => str_contains(
                    strtolower($role->name),
                    strtolower($search)
                ));
            $roles = $roles->merge($realmRoles);
        }

        // Search client roles
        if ((!$type || $type === RoleType::CLIENT) && $clientId) {
            $clientRoles = $this->getAllClientRoles($clientId, false)
                ->filter(fn(RoleData $role) => str_contains(
                    strtolower($role->name),
                    strtolower($search)
                ));
            $roles = $roles->merge($clientRoles);
        }

        return $roles;
    }

    /**
     * Get roles by type with optional filtering
     *
     * @param RoleType $type Role type
     * @param string|null $clientId Required for client roles
     * @param bool $compositesOnly Only composite roles
     * @param bool $simpleOnly Only simple (non-composite) roles
     * @return Collection<RoleData>
     * @throws ConnectionException
     */
    public function getByType(
        RoleType $type,
        ?string $clientId = null,
        bool $compositesOnly = false,
        bool $simpleOnly = false
    ): Collection {
        $roles = match ($type) {
            RoleType::REALM => $this->getAllRealmRoles(false),
            RoleType::CLIENT => $this->getAllClientRoles($clientId ?? '', false),
        };

        if ($compositesOnly) {
            $roles = $roles->filter(fn(RoleData $role) => $role->isComposite());
        }

        if ($simpleOnly) {
            $roles = $roles->filter(fn(RoleData $role) => $role->isSimple());
        }

        return $roles;
    }

    /**
     * Check if a role exists
     *
     * @param string $roleName Role name
     * @param RoleType $type Role type
     * @param string|null $clientId Client ID for client roles
     * @return bool
     */
    public function exists(string $roleName, RoleType $type, ?string $clientId = null): bool
    {
        try {
            match ($type) {
                RoleType::REALM => $this->findRealmRole($roleName),
                RoleType::CLIENT => $this->findClientRole($clientId ?? '', $roleName),
            };
            return true;
        } catch (ResourceNotFoundException) {
            return false;
        }
    }

    /**
     * Get roles with their usage statistics
     *
     * @param RoleType $type Role type
     * @param string|null $clientId Client ID for client roles
     * @return Collection
     * @throws ConnectionException
     */
    public function getWithUsageStats(RoleType $type, ?string $clientId = null): Collection
    {
        $roles = $this->getByType($type, $clientId, false, false);

        return $roles->map(function (RoleData $role) use ($type, $clientId) {
            // Note: This would require additional API calls to get actual usage
            // For now, we return the role with placeholder stats
            return [
                'role' => $role,
                'user_count' => 0, // Would need API call to get actual count
                'group_count' => 0, // Would need API call to get actual count
                'is_composite' => $role->isComposite(),
                'composite_count' => 0, // Would need API call if composite
            ];
        });
    }

    /**
     * Prepare roles data for API requests
     *
     * @param Collection|array $roles Roles collection or array
     * @return array
     */
    private function prepareRolesData(Collection|array $roles): array
    {
        if ($roles instanceof Collection) {
            $roles = $roles->toArray();
        }

        return array_map(function ($role) {
            if ($role instanceof RoleData) {
                return $role->toKeycloakArray();
            }
            return $role;
        }, $roles);
    }

    /**
     * Validate role data before operations
     *
     * @param RoleData|array $roleData Role data to validate
     * @return array Validation errors
     */
    private function validateRoleData(RoleData|array $roleData): array
    {
        if ($roleData instanceof RoleData) {
            return $roleData->validateRole();
        }

        $errors = [];

        if (empty($roleData['name'])) {
            $errors[] = 'Role name is required';
        }

        return $errors;
    }
}
