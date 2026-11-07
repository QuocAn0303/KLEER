<?php

declare(strict_types=1);

namespace Kleer\Contracts;

// Prevent direct file access
defined('ABSPATH') || exit;

/**
 * Specialized Repository Contract for Skin Quiz Routine Recommendation.
 * Extends ProductRepositoryInterface without breaking the findFeatured() contract.
 */
interface QuizProductRepositoryInterface extends ProductRepositoryInterface
{
    /**
     * Lấy danh mục sản phẩm phục vụ thuật toán Skin Quiz (kèm taxonomy terms và flags).
     *
     * @param array<string, mixed> $criteria Bộ lọc tùy chọn
     * @return list<array<string, mixed>>
     */
    public function getQuizCatalog(array $criteria = []): array;
}
