<?php

declare(strict_types=1);

namespace Kleer\Contracts;

interface ProductRepositoryInterface
{
    /** @return array<int, array{id: int, name: string}> */
    public function findFeatured(int $limit = 5): array;
}
