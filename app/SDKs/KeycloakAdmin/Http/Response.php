<?php

namespace App\SDKs\KeycloakAdmin\Http;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Collection;
use JsonSerializable;

class Response implements Arrayable, Jsonable, JsonSerializable
{
    private array $data;
    private int $statusCode;
    private bool $fromCache;

    public function __construct(array $data, int $statusCode, bool $fromCache = false)
    {
        $this->data = $data;
        $this->statusCode = $statusCode;
        $this->fromCache = $fromCache;
    }

    public function data(): array
    {
        return $this->data;
    }

    public function status(): int
    {
        return $this->statusCode;
    }

    public function successful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    public function failed(): bool
    {
        return !$this->successful();
    }

    public function fromCache(): bool
    {
        return $this->fromCache;
    }

    public function get(string $key, $default = null)
    {
        return data_get($this->data, $key, $default);
    }

    public function has(string $key): bool
    {
        return data_get($this->data, $key) !== null;
    }

    // Métodos específicos para respuestas de Keycloak

    /**
     * Para respuestas que devuelven arrays (listas de roles, grupos, etc.)
     */
    public function items(): Collection
    {
        // Si la respuesta es directamente un array
        if (array_is_list($this->data)) {
            return collect($this->data);
        }

        // Si hay una key específica que contiene los items
        $items = $this->get('data', $this->get('items', $this->get('results', [])));

        return collect($items);
    }

    /**
     * Para respuestas de un solo item (rol, grupo, usuario específico)
     */
    public function item(): ?array
    {
        // Si la respuesta es directamente un objeto con ID
        if ($this->has('id')) {
            return $this->data;
        }

        // Si es un array con un solo elemento
        $items = $this->items();
        return $items->first();
    }

    /**
     * Para respuestas de conteo
     */
    public function count(): int
    {
        // Si la respuesta es un array directo
        if (array_is_list($this->data)) {
            return count($this->data);
        }

        // Si hay una key específica de conteo
        return $this->get('count', $this->get('total', $this->items()->count()));
    }

    /**
     * Verificar si la respuesta está vacía
     */
    public function isEmpty(): bool
    {
        return $this->items()->isEmpty();
    }

    /**
     * Verificar si la respuesta tiene items
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    // Métodos específicos para diferentes tipos de respuestas de Keycloak

    /**
     * Para respuestas de roles
     */
    public function roles(): Collection
    {
        return $this->items();
    }

    /**
     * Para respuestas de grupos
     */
    public function groups(): Collection
    {
        return $this->items();
    }

    /**
     * Para respuestas de usuarios
     */
    public function users(): Collection
    {
        return $this->items();
    }

    /**
     * Para respuestas de clientes
     */
    public function clients(): Collection
    {
        return $this->items();
    }

    /**
     * Para respuestas de mapeos de roles
     */
    public function roleMappings(): array
    {
        return [
            'realm' => collect($this->get('realmMappings', [])),
            'client' => collect($this->get('clientMappings', [])),
        ];
    }

    /**
     * Filtrar items por un criterio
     */
    public function filter(callable $callback): Collection
    {
        return $this->items()->filter($callback);
    }

    /**
     * Buscar un item por ID
     */
    public function findById(string $id): ?array
    {
        return $this->items()->firstWhere('id', $id);
    }

    /**
     * Buscar un item por nombre
     */
    public function findByName(string $name): ?array
    {
        return $this->items()->firstWhere('name', $name);
    }

    /**
     * Pluck una propiedad específica de todos los items
     */
    public function pluck(string $key): Collection
    {
        return $this->items()->pluck($key);
    }

    /**
     * Obtener solo los IDs de los items
     */
    public function ids(): Collection
    {
        return $this->pluck('id');
    }

    /**
     * Obtener solo los nombres de los items
     */
    public function names(): Collection
    {
        return $this->pluck('name');
    }

    // Métodos de transformación heredados

    public function toCollection(): Collection
    {
        return collect($this->data);
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->data, $options);
    }

    public function jsonSerialize(): array
    {
        return $this->data;
    }

    // Métodos mágicos para acceso directo

    public function __get(string $key)
    {
        return $this->get($key);
    }

    public function __isset(string $key): bool
    {
        return $this->has($key);
    }

    public function __toString(): string
    {
        return $this->toJson();
    }

    // Métodos para debugging

    public function dd(): never
    {
        dd([
            'status' => $this->statusCode,
            'successful' => $this->successful(),
            'from_cache' => $this->fromCache,
            'data' => $this->data,
            'item_count' => $this->count(),
        ]);
    }

    public function dump(): self
    {
        dump([
            'status' => $this->statusCode,
            'successful' => $this->successful(),
            'from_cache' => $this->fromCache,
            'data' => $this->data,
            'item_count' => $this->count(),
        ]);

        return $this;
    }

    // Métodos de conveniencia para verificar tipos de respuesta

    /**
     * Verificar si es una respuesta de error de Keycloak
     */
    public function isKeycloakError(): bool
    {
        return $this->has('error') || $this->has('errorMessage');
    }

    /**
     * Obtener mensaje de error de Keycloak
     */
    public function getErrorMessage(): ?string
    {
        return $this->get('error_description')
            ?? $this->get('errorMessage')
            ?? $this->get('error')
            ?? $this->get('message');
    }

    /**
     * Verificar si la respuesta representa una operación exitosa sin contenido
     */
    public function isNoContent(): bool
    {
        return $this->statusCode === 204;
    }

    /**
     * Verificar si la respuesta es de creación exitosa
     */
    public function isCreated(): bool
    {
        return $this->statusCode === 201;
    }
}
