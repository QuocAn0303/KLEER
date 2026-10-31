<?php

declare(strict_types=1);

namespace Kleer\Exceptions;

defined('ABSPATH') || exit;

final class RoutineUnavailableException extends \RuntimeException
{
    /** @param list<string> $missingSteps */
    public function __construct(
        public readonly array $missingSteps = [],
        public readonly string $reason = 'catalog_incomplete'
    ) {
        parent::__construct('Chưa có đủ sản phẩm phù hợp để tạo routine an toàn.');
    }
}
