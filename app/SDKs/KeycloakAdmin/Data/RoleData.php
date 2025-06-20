<?php

namespace App\SDKs\KeycloakAdmin\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

class RoleData extends Data
{
    public function __construct(
        public readonly ?string $id,
        public readonly string $name,
        public readonly ?string $description,
        public readonly bool $composite,
        public readonly bool $clientRole,
        public readonly ?string $containerId,
        public readonly ?array $attributes,

        #[MapName('createdTimestamp')]
        public readonly ?int $createdTimestamp,

        #[MapName('updatedTimestamp')]
        public readonly ?int $updatedTimestamp,
    ) {}

    /**
     * Get the role type
     */
    public function getType(): string
    {
        return $this->clientRole ? 'client' : 'realm';
    }

    /**
     * Check if this is a realm role
     */
    public function isRealmRole(): bool
    {
        return !$this->clientRole;
    }

    /**
     * Check if this is a client role
     */
    public function isClientRole(): bool
    {
        return $this->clientRole;
    }

    /**
     * Check if this is a composite role
     */
    public function isComposite(): bool
    {
        return $this->composite;
    }

    /**
     * Check if this is a simple role (not composite)
     */
    public function isSimple(): bool
    {
        return !$this->composite;
    }

    /**
     * Get the display name (name with description if available)
     */
    public function getDisplayName(): string
    {
        if ($this->description) {
            return "{$this->name} - {$this->description}";
        }

        return $this->name;
    }

    /**
     * Get created date as a Carbon instance
     */
    public function getCreatedAt(): ?Carbon
    {
        return $this->createdTimestamp
            ? Carbon::createFromTimestampMs($this->createdTimestamp)
            : null;
    }

    /**
     * Get updated date as Carbon instance
     */
    public function getUpdatedAt(): ?Carbon
    {
        return $this->updatedTimestamp
            ? Carbon::createFromTimestampMs($this->updatedTimestamp)
            : null;
    }

    /**
     * Check if the role was recently created
     */
    public function isRecentlyCreated(int $daysThreshold = 7): bool
    {
        $createdAt = $this->getCreatedAt();
        return $createdAt && $createdAt->diffInDays(now()) <= $daysThreshold;
    }

    /**
     * Check if the role was recently updated
     */
    public function isRecentlyUpdated(int $daysThreshold = 7): bool
    {
        $updatedAt = $this->getUpdatedAt();
        return $updatedAt && $updatedAt->diffInDays(now()) <= $daysThreshold;
    }

    /**
     * Get a specific attribute value
     */
    public function getAttribute(string $key, $default = null)
    {
        if (!$this->attributes || !isset($this->attributes[$key])) {
            return $default;
        }

        $value = $this->attributes[$key];

        // Keycloak stores attributes as arrays
        if (is_array($value)) {
            return count($value) === 1 ? $value[0] : $value;
        }

        return $value;
    }

    /**
     * Check if the role has a specific attribute
     */
    public function hasAttribute(string $key): bool
    {
        return $this->attributes && isset($this->attributes[$key]);
    }

    /**
     * Get all attribute keys
     */
    public function getAttributeKeys(): array
    {
        return $this->attributes ? array_keys($this->attributes) : [];
    }

    /**
     * Get role scope (realm name or client id)
     */
    public function getScope(): ?string
    {
        return $this->containerId;
    }

    /**
     * Check if this role has the same name as another
     */
    public function hasSameName(RoleData $other): bool
    {
        return strtolower($this->name) === strtolower($other->name);
    }

    /**
     * Check if this role is in the same scope as another
     */
    public function isInSameScope(RoleData $other): bool
    {
        return $this->containerId === $other->containerId
            && $this->clientRole === $other->clientRole;
    }

    /**
     * Get a role as a simple array for API responses
     */
    public function toSimpleArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->getType(),
            'composite' => $this->composite,
        ];
    }

    /**
     * Get a role for API responses with additional metadata
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'display_name' => $this->getDisplayName(),
            'type' => $this->getType(),
            'composite' => $this->composite,
            'client_role' => $this->clientRole,
            'container_id' => $this->containerId,
            'scope' => $this->getScope(),
            'attributes' => $this->attributes,
            'created_at' => $this->getCreatedAt()?->toISOString(),
            'updated_at' => $this->getUpdatedAt()?->toISOString(),
            'is_recently_created' => $this->isRecentlyCreated(),
            'is_recently_updated' => $this->isRecentlyUpdated(),
            'age_in_days' => $this->getCreatedAt()?->diffInDays(now()),
        ];
    }

    /**
     * Get a role for Keycloak API requests
     */
    public function toKeycloakArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'composite' => $this->composite,
            'clientRole' => $this->clientRole,
            'containerId' => $this->containerId,
            'attributes' => $this->attributes,
        ], fn($value) => $value !== null);
    }

    /**
     * Create a new RoleData for creating a role (without timestamps and id)
     */
    public static function forCreation(
        string $name,
        ?string $description = null,
        bool $composite = false,
        bool $clientRole = false,
        ?string $containerId = null,
        ?array $attributes = null
    ): self {
        return new self(
            id: null,
            name: $name,
            description: $description,
            composite: $composite,
            clientRole: $clientRole,
            containerId: $containerId,
            attributes: $attributes,
            createdTimestamp: null,
            updatedTimestamp: null,
        );
    }

    /**
     * Create a new RoleData for updating (preserving id and timestamps)
     */
    public function forUpdate(
        ?string $name = null,
        ?string $description = null,
        ?bool $composite = null,
        ?array $attributes = null
    ): self {
        return new self(
            id: $this->id,
            name: $name ?? $this->name,
            description: $description ?? $this->description,
            composite: $composite ?? $this->composite,
            clientRole: $this->clientRole,
            containerId: $this->containerId,
            attributes: $attributes ?? $this->attributes,
            createdTimestamp: $this->createdTimestamp,
            updatedTimestamp: $this->updatedTimestamp,
        );
    }

    /**
     * Create a copy as a realm role
     */
    public function asRealmRole(?string $containerId = null): self
    {
        return new self(
            id: null, // New role, no ID
            name: $this->name,
            description: $this->description,
            composite: $this->composite,
            clientRole: false,
            containerId: $containerId,
            attributes: $this->attributes,
            createdTimestamp: null,
            updatedTimestamp: null,
        );
    }

    /**
     * Create a copy as a client role
     */
    public function asClientRole(string $containerId): self
    {
        return new self(
            id: null, // New role, no ID
            name: $this->name,
            description: $this->description,
            composite: $this->composite,
            clientRole: true,
            containerId: $containerId,
            attributes: $this->attributes,
            createdTimestamp: null,
            updatedTimestamp: null,
        );
    }

    /**
     * Get role summary for logging
     */
    public function getSummary(): string
    {
        $type = $this->getType();
        $composite = $this->composite ? ' (composite)' : '';
        $scope = $this->containerId ? " in {$this->containerId}" : '';

        return "{$type} role '{$this->name}'{$composite}{$scope}";
    }

    /**
     * Validate role data
     */
    public function validateRole(): array
    {
        $errors = [];

        if (empty($this->name)) {
            $errors[] = 'Role name is required';
        }

        if (strlen($this->name) > 255) {
            $errors[] = 'Role name cannot exceed 255 characters';
        }

        if ($this->description && strlen($this->description) > 500) {
            $errors[] = 'Role description cannot exceed 500 characters';
        }

        if ($this->clientRole && !$this->containerId) {
            $errors[] = 'Client roles must have a containerId';
        }

        return $errors;
    }

    /**
     * Check if the role data is valid
     */
    public function isValid(): bool
    {
        return empty($this->validateRole());
    }
}
