<?php

namespace App\SDKs\KeycloakAdmin\Enums;

enum MappingType: string
{
    case USER = 'user';
    case GROUP = 'group';

    /**
     * Get the display name
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::USER => 'User Mapping',
            self::GROUP => 'Group Mapping',
        };
    }

    /**
     * Get the description
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::USER => 'Role assigned directly to a user',
            self::GROUP => 'Role assigned to a group (inherited by group members)',
        };
    }

    /**
     * Check if this is a user mapping
     */
    public function isUser(): bool
    {
        return $this === self::USER;
    }

    /**
     * Check if this is a group mapping
     */
    public function isGroup(): bool
    {
        return $this === self::GROUP;
    }

    /**
     * Get the API endpoint segment
     */
    public function getEndpointSegment(): string
    {
        return match ($this) {
            self::USER => 'users',
            self::GROUP => 'groups',
        };
    }

    /**
     * Get the resource ID field name
     */
    public function getIdFieldName(): string
    {
        return match ($this) {
            self::USER => 'userId',
            self::GROUP => 'groupId',
        };
    }

    /**
     * Get the resource name field
     */
    public function getNameField(): string
    {
        return match ($this) {
            self::USER => 'username',
            self::GROUP => 'name',
        };
    }

    /**
     * Get all available types
     */
    public static function all(): array
    {
        return [
            self::USER,
            self::GROUP,
        ];
    }

    /**
     * Get all types as an array with labels
     */
    public static function toSelectArray(): array
    {
        return [
            self::USER->value => self::USER->getDisplayName(),
            self::GROUP->value => self::GROUP->getDisplayName(),
        ];
    }

    /**
     * Get icon for UI
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::USER => 'user',
            self::GROUP => 'users',
        };
    }

    /**
     * Get color for UI
     */
    public function getColor(): string
    {
        return match ($this) {
            self::USER => 'indigo',
            self::GROUP => 'purple',
        };
    }

    /**
     * Get CSS class for styling
     */
    public function getCssClass(): string
    {
        return match ($this) {
            self::USER => 'mapping-user',
            self::GROUP => 'mapping-group',
        };
    }

    /**
     * Get priority for sorting (lower = higher priority)
     */
    public function getPriority(): int
    {
        return match ($this) {
            self::USER => 1, // Users have higher priority
            self::GROUP => 2,
        };
    }

    /**
     * Check if this mapping type supports inheritance
     */
    public function supportsInheritance(): bool
    {
        return match ($this) {
            self::USER => false, // Direct assignment only
            self::GROUP => true,  // Members inherit group roles
        };
    }

    /**
     * Get the mapping scope description
     */
    public function getScopeDescription(): string
    {
        return match ($this) {
            self::USER => 'Applies only to the specific user',
            self::GROUP => 'Applies to all members of the group',
        };
    }

    /**
     * Build role mapping endpoint
     */
    public function buildRoleMappingEndpoint(string $entityId, RoleType $roleType, ?string $clientId = null): string
    {
        $base = "/{$this->getEndpointSegment()}/{$entityId}/role-mappings";

        return match ($roleType) {
            RoleType::REALM => "{$base}/realm",
            RoleType::CLIENT => "{$base}/clients/{$clientId}",
        };
    }

    /**
     * Get validation rules for mapping operations
     */
    public function getValidationRules(): array
    {
        $baseRules = [
            'roles' => 'required|array|min:1',
            'roles.*' => 'required|array',
            'roles.*.id' => 'required|string',
            'roles.*.name' => 'required|string',
        ];

        return match ($this) {
            self::USER => array_merge($baseRules, [
                'userId' => 'required|string',
            ]),
            self::GROUP => array_merge($baseRules, [
                'groupId' => 'required|string',
            ]),
        };
    }

    /**
     * Get the effective mapping description
     */
    public function getEffectiveDescription(): string
    {
        return match ($this) {
            self::USER => 'User has these roles directly assigned',
            self::GROUP => 'User has these roles through group membership',
        };
    }

    /**
     * Check if mapping can be removed
     */
    public function canBeRemoved(): bool
    {
        return match ($this) {
            self::USER => true,  // Direct assignments can always be removed
            self::GROUP => false, // Group mappings are removed by leaving the group
        };
    }

    /**
     * Get management actions available
     */
    public function getAvailableActions(): array
    {
        return match ($this) {
            self::USER => ['assign', 'remove', 'view'],
            self::GROUP => ['view'], // Groups are managed separately
        };
    }

    /**
     * Get audit log action prefix
     */
    public function getAuditActionPrefix(): string
    {
        return match ($this) {
            self::USER => 'USER_ROLE',
            self::GROUP => 'GROUP_ROLE',
        };
    }
}
