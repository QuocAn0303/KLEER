<?php

declare(strict_types=1);

namespace Kleer\Models;

/**
 * Minimal domain entity representing a skincare product in the KLEER store.
 *
 * Layer: Models
 * Responsibility: Hold domain state and attributes.
 * Boundary: Must not perform database I/O or handle HTTP requests.
 */
final class Product
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly int $price = 0,
    ) {
    }

    /**
     * Convert domain model to primitive array representation.
     *
     * @return array{id: int, name: string, price: int}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
        ];
    }
}
