<?php

namespace App\SDKs\KeycloakAdmin\Exceptions;

use Throwable;

class InsufficientPermissionsException extends KeycloakException
{
    private ?string $requiredPermission;
    private ?string $resourceType;

    public function __construct(
        string    $message = 'Insufficient permissions to perform this action',
        int       $code = 403,
        array     $context = [],
        ?array    $responseBody = null,
        ?string   $requiredPermission = null,
        ?string   $resourceType = null,
        Throwable $previous = null
    ) {
        parent::__construct($message, $code, $context, $responseBody, $previous);
        $this->requiredPermission = $requiredPermission;
        $this->resourceType = $resourceType;
    }

    /**
     * Get the required permission
     */
    public function getRequiredPermission(): ?string
    {
        return $this->requiredPermission;
    }

    /**
     * Get the resource type
     */
    public function getResourceType(): ?string
    {
        return $this->resourceType;
    }

    /**
     * Create exception for role management permissions
     */
    public static function forRoleManagement(array $responseBody = []): self
    {
        return new self(
            message: 'Insufficient permissions to manage roles',
            responseBody: $responseBody,
            requiredPermission: 'manage-realm',
            resourceType: 'roles'
        );
    }

    /**
     * Create exception for group management permissions
     */
    public static function forGroupManagement(array $responseBody = []): self
    {
        return new self(
            message: 'Insufficient permissions to manage groups',
            responseBody: $responseBody,
            requiredPermission: 'manage-users',
            resourceType: 'groups'
        );
    }

    /**
     * Create exception for user management permissions
     */
    public static function forUserManagement(array $responseBody = []): self
    {
        return new self(
            message: 'Insufficient permissions to manage users',
            responseBody: $responseBody,
            requiredPermission: 'manage-users',
            resourceType: 'users'
        );
    }

    /**
     * Create exception for client management permissions
     */
    public static function forClientManagement(array $responseBody = []): self
    {
        return new self(
            message: 'Insufficient permissions to manage clients',
            responseBody: $responseBody,
            requiredPermission: 'manage-clients',
            resourceType: 'clients'
        );
    }

    /**
     * Create exception for realm administration permissions
     */
    public static function forRealmAdministration(array $responseBody = []): self
    {
        return new self(
            message: 'Insufficient permissions for realm administration',
            responseBody: $responseBody,
            requiredPermission: 'realm-admin',
            resourceType: 'realm'
        );
    }

    /**
     * Create exception for viewing permissions
     */
    public static function forViewing(string $resourceType, array $responseBody = []): self
    {
        return new self(
            message: "Insufficient permissions to view {$resourceType}",
            responseBody: $responseBody,
            requiredPermission: "view-{$resourceType}",
            resourceType: $resourceType
        );
    }

    /**
     * Create exception for role mapping permissions
     */
    public static function forRoleMapping(array $responseBody = []): self
    {
        return new self(
            message: 'Insufficient permissions to manage role mappings',
            responseBody: $responseBody,
            requiredPermission: 'manage-users',
            resourceType: 'role-mappings'
        );
    }

    public function getUserMessage(): string
    {
        if ($this->resourceType && $this->requiredPermission) {
            return match ($this->resourceType) {
                'roles' => 'You do not have permission to manage roles in this realm.',
                'groups' => 'You do not have permission to manage groups in this realm.',
                'users' => 'You do not have permission to manage users in this realm.',
                'clients' => 'You do not have permission to manage clients in this realm.',
                'realm' => 'You do not have permission to administer this realm.',
                'role-mappings' => 'You do not have permission to manage role assignments.',
                default => "You do not have permission to access {$this->resourceType}.",
            };
        }

        return $this->getKeycloakErrorDescription()
            ?? 'You do not have sufficient permissions to perform this action.';
    }

    public function toArray(): array
    {
        $array = parent::toArray();
        $array['required_permission'] = $this->requiredPermission;
        $array['resource_type'] = $this->resourceType;
        return $array;
    }

    /**
     * Check if this exception is for a specific resource type
     */
    public function isForResourceType(string $resourceType): bool
    {
        return $this->resourceType === $resourceType;
    }

    /**
     * Check if this exception requires a specific permission
     */
    public function requiresPermission(string $permission): bool
    {
        return $this->requiredPermission === $permission;
    }
}
