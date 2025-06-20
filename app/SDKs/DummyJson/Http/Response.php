<?php

namespace App\SDKs\DummyJson\Http;

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

    // Para respuestas paginadas
    public function items(): Collection
    {
        $items = $this->get('products', $this->get('users', $this->get('posts', [])));
        return collect($items);
    }

    public function pagination(): ?array
    {
        if (!$this->has('total')) {
            return null;
        }

        return [
            'total' => $this->get('total'),
            'skip' => $this->get('skip', 0),
            'limit' => $this->get('limit', 30),
            'current_page' => intval($this->get('skip', 0) / $this->get('limit', 30)) + 1,
            'per_page' => $this->get('limit', 30),
            'last_page' => ceil($this->get('total', 0) / $this->get('limit', 30)),
        ];
    }

    public function hasNextPage(): bool
    {
        $pagination = $this->pagination();
        return $pagination && $pagination['current_page'] < $pagination['last_page'];
    }

    public function hasPrevPage(): bool
    {
        $pagination = $this->pagination();
        return $pagination && $pagination['current_page'] > 1;
    }

    // Para respuestas de un solo item
    public function item(): ?array
    {
        // Si tiene ID, probablemente es un item único
        if ($this->has('id')) {
            return $this->data;
        }

        // Si tiene items array, tomar el primero
        $items = $this->items();
        return $items->first();
    }

    // Para respuestas de autenticación
    public function token(): ?string
    {
        return $this->get('token');
    }

    public function refreshToken(): ?string
    {
        return $this->get('refreshToken');
    }

    // Métodos de transformación
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

    // Método para debugging
    public function dd(): never
    {
        dd([
            'status' => $this->statusCode,
            'from_cache' => $this->fromCache,
            'data' => $this->data,
        ]);
    }

    public function dump(): self
    {
        dump([
            'status' => $this->statusCode,
            'from_cache' => $this->fromCache,
            'data' => $this->data,
        ]);

        return $this;
    }
}
