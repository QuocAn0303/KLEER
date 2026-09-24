<?php

declare(strict_types=1);

namespace Kleer\Services;

use Kleer\Contracts\ProductRepositoryInterface;

final class ProductService
{
    public function __construct(private ProductRepositoryInterface $repository)
    {
    }

    /** @return array<int, array{id: int, name: string}> */
    public function featuredProducts(int $limit = 5): array
    {
        return $this->repository->findFeatured(max(1, min($limit, 20)));
    }
}
