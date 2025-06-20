<?php

namespace App\SDKs\KeycloakAdmin\Exceptions;

use Throwable;

class ResourceNotFoundException extends KeycloakException
{
    private ?string $resourceType;
    private ?string $resourceId;

    public function __construct(
        string    $message = 'Resource not found',
        int       $code = 404,
        array     $context = [],
        ?array    $responseBody = null,
        ?string   $resourceType = null,
        ?string   $resourceId = null,
        Throwable $previous = null
    ) {
        parent::__construct($message, $code, $context, $responseBody, $previous);
        $this->resourceType = $resourceType;
        $this->resourceId = $resourceId;
    }

    /**
     * Get the resource type
     */
    public function getResourceType(): ?string
    {
        return $this->resourceType;
    }

    /**
     * Get the resource ID
     */
    public function getResourceId(): ?string
    {
        return $this->resourceId;
    }

    /**
     * Create exception for role not found
     */
    public static function role(string $roleId, array $responseBody = []): self
    {
        return new self(
            message: "Role with ID '{$roleId}' not found",
            responseBody: $responseBody,
            resourceType: 'role',
            resourceId: $roleId
        );
    }

    /**
     * Create exception for role not found by name
     */
    public static function roleByName(string $roleName, array $responseBody = []): self
    {
        return new self(
            message: "Role with name '{$roleName}' not found",
            responseBody: $responseBody,
            resourceType: 'role',
            resourceId: $roleName
        );
    }

    /**
     * Create exception for group not found
     */
    public static function group(string $groupId, array $responseBody = []): self
    {
        return new self(
            message: "Group with ID '{$groupId}' not found",
            responseBody: $responseBody,
            resourceType: 'group',
            resourceId: $groupId
        );
    }

    /**
     * Create exception for group not found by name
     */
    public static function groupByName(string $groupName, array $responseBody = []): self
    {
        return new self(
            message: "Group with name '{$groupName}' not found",
            responseBody: $responseBody,
            resourceType: 'group',
            resourceId: $groupName
        );
    }

    /**
     * Create exception for user not found
     */
    public static function user(string $userId, array $responseBody = []): self
    {
        return new self(
            message: "User with ID '{$userId}' not found",
            responseBody: $responseBody,
            resourceType: 'user',
            resourceId: $userId
        );
    }

    /**
     * Create exception for user not found by username
     */
    public static function userByUsername(string $username, array $responseBody = []): self
    {
        return new self(
            message: "User with username '{$username}' not found",
            responseBody: $responseBody,
            resourceType: 'user',
            resourceId: $username
        );
    }

    /**
     * Create exception for client not found
     */
    public static function client(string $clientId, array $responseBody = []): self
    {
        return new self(
            message: "Client with ID '{$clientId}' not found",
            responseBody: $responseBody,
            resourceType: 'client',
            resourceId: $clientId
        );
    }

    /**
     * Create exception for client not found by client ID
     */
    public static function clientByClientId(string $clientId, array $responseBody = []): self
    {
        return new self(
            message: "Client with client-id '{$clientId}' not found",
            responseBody: $responseBody,
            resourceType: 'client',
            resourceId: $clientId
        );
    }

    /**
     * Create exception for realm not found
     */
    public static function realm(string $realmName, array $responseBody = []): self
    {
        return new self(
            message: "Realm '{$realmName}' not found",
            responseBody: $responseBody,
            resourceType: 'realm',
            resourceId: $realmName
        );
    }

    /**
     * Create exception for role mapping not found
     */
    public static function roleMapping(string $userId, string $roleId, array $responseBody = []): self
    {
        return new self(
            message: "Role mapping not found for user '{$userId}' and role '{$roleId}'",
            responseBody: $responseBody,
            resourceType: 'role-mapping',
            resourceId: "{$userId}:{$roleId}"
        );
    }

    /**
     * Create exception for generic resource
     */
    public static function resource(string $resourceType, string $resourceId, array $responseBody = []): self
    {
        return new self(
            message: ucfirst($resourceType) . " with ID '{$resourceId}' not found",
            responseBody: $responseBody,
            resourceType: $resourceType,
            resourceId: $resourceId
        );
    }

    public function getUserMessage(): string
    {
        if ($this->resourceType) {
            return match ($this->resourceType) {
                'role' => 'The requested role does not exist.',
                'group' => 'The requested group does not exist.',
                'user' => 'The requested user does not exist.',
                'client' => 'The requested client does not exist.',
                'realm' => 'The requested realm does not exist.',
                'role-mapping' => 'The requested role assignment does not exist.',
                default => "The requested {$this->resourceType} does not exist.",
            };
        }

        return $this->getKeycloakErrorDescription()
            ?? 'The requested resource does not exist.';
    }

    public function toArray(): array
    {
        $array = parent::toArray();
        $array['resource_type'] = $this->resourceType;
        $array['resource_id'] = $this->resourceId;
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
     * Get a suggestion for the user
     */
    public function getSuggestion(): ?string
    {
        if ($this->resourceType) {
            return match ($this->resourceType) {
                'role' => 'Check if the role name is correct or if the role exists in the current realm.',
                'group' => 'Verify the group path or check if the group exists in the current realm.',
                'user' => 'Ensure the user ID or username is correct and the user exists.',
                'client' => 'Verify the client ID is correct and the client is configured in this realm.',
                'realm' => 'Check if the realm name is spelled correctly and accessible.',
                default => 'Verify the resource identifier and try again.',
            };
        }

        return null;
    }
}
