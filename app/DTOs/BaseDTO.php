<?php

namespace App\DTOs;

abstract class BaseDTO
{
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public static function fromArray(array $data): self
    {
        $reflection = new \ReflectionClass(static::class);
        return $reflection->newInstanceArgs($data);
    }
}