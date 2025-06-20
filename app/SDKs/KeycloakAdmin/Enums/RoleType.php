<?php

namespace App\SDKs\KeycloakAdmin\Enums;

enum RoleType: string
{
    case REALM = 'realm';
    case CLIENT = 'client';

    /**
     * Get the display name
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::REALM => 'Realm Role',
            self::CLIENT => 'Client Role',
        };
    }

    /**
     * Get the description
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::REALM => 'Role that applies to the entire realm',
            self::CLIENT => 'Role that applies to a specific client',
        };
    }

    /**
     * Check if this is a realm role
     */
    public function isRealm(): bool
    {
        return $this === self::REALM;
    }

    /**
     * Check if this is a client role
     */
    public function isClient(): bool
    {
        return $this === self::CLIENT;
    }

    /**
     * Get the boolean value for the clientRole field
     */
    public function isClientRole(): bool
    {
        return $this === self::CLIENT;
    }

    /**
     * Create from boolean clientRole value
     */
    public static function fromClientRole(bool $clientRole): self
    {
        return $clientRole ? self::CLIENT : self::REALM;
    }

    /**
     * Create from role data
     */
    public static function fromRoleData(array $roleData): self
    {
        return self::fromClientRole($roleData['clientRole'] ?? false);
    }

    /**
     * Get all available types
     */
    public static function all(): array
    {
        return [
            self::REALM,
            self::CLIENT,
        ];
    }

    /**
     * Get all types as an array with labels
     */
    public static function toSelectArray(): array
    {
        return [
            self::REALM->value => self::REALM->getDisplayName(),
            self::CLIENT->value => self::CLIENT->getDisplayName(),
        ];
    }

    /**
     * Get icon for UI
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::REALM => 'shield',
            self::CLIENT => 'application',
        };
    }

    /**
     * Get color for UI
     */
    public function getColor(): string
    {
        return match ($this) {
            self::REALM => 'blue',
            self::CLIENT => 'green',
        };
    }

    /**
     * Get CSS class for styling
     */
    public function getCssClass(): string
    {
        return match ($this) {
            self::REALM => 'role-realm',
            self::CLIENT => 'role-client',
        };
    }

    /**
     * Get priority for sorting (lower = higher priority)
     */
    public function getPriority(): int
    {
        return match ($this) {
            self::REALM => 1,
            self::CLIENT => 2,
        };
    }

    /**
     * Get the API endpoint prefix
     */
    public function getEndpointPrefix(): string
    {
        return match ($this) {
            self::REALM => 'roles',
            self::CLIENT => 'clients',
        };
    }

    /**
     * Check if this type can be composite
     */
    public function canBeComposite(): bool
    {
        return match ($this) {
            self::REALM => true,
            self::CLIENT => true, // Both can be composite in Keycloak
        };
    }

    /**
     * Get validation rules specific to this type
     */
    public function getValidationRules(): array
    {
        return match ($this) {
            self::REALM => [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:500',
                'composite' => 'boolean',
            ],
            self::CLIENT => [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:500',
                'composite' => 'boolean',
                'containerId' => 'required|string',
            ],
        };
    }
}
