<?php

declare(strict_types=1);

namespace Kleer\Support\Validation;

// Prevent direct file access (Security Case)
defined('ABSPATH') || exit;

/**
 * Ket qua kiem tra tin trung du lieu theo JSON Schema.
 *
 * Layer: Support (Cross-cutting Infrastructure)
 * Responsibility: Mang thong tin ket qua kiem tra duy nhat, tap hop loi theo
 *                 duong dan JSON Pointer de Frontend va Backend cung doc.
 * Boundary: Khong sua doi du lieu dau vao, khong phu thuoc WordPress.
 */
final class ValidationResult
{
    /**
     * @param list<array{pointer: string, keyword: string, message: string}> $errors
     */
    private function __construct(
        private readonly bool $valid,
        private readonly array $errors,
    ) {
    }

    /**
     * @param list<array{pointer: string, keyword: string, message: string}> $errors
     */
    public static function valid(array $errors = []): self
    {
        return new self($errors === [], $errors);
    }

    /**
     * @param list<array{pointer: string, keyword: string, message: string}> $errors
     */
    public static function invalid(array $errors): self
    {
        return new self(false, array_values($errors));
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function isInvalid(): bool
    {
        return !$this->valid;
    }

    /**
     * @return list<array{pointer: string, keyword: string, message: string}>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?array
    {
        return $this->errors[0] ?? null;
    }

    public function firstErrorPointer(): ?string
    {
        return $this->errors[0]['pointer'] ?? null;
    }

    /**
     * Kiem tra mot duong dan JSON Pointer co bi loi hay khong.
     *
     * @param string $pointer Duong dan duoi dang JSON Pointer, vi du: /answers/0/question_id
     */
    public function hasErrorAt(string $pointer): bool
    {
        foreach ($this->errors as $error) {
            if ($error['pointer'] === $pointer) {
                return true;
            }
        }

        return false;
    }

    public function count(): int
    {
        return count($this->errors);
    }
}
