<?php

declare(strict_types=1);

namespace Kleer\Support\Validation;

// Prevent direct file access (Security Case)
defined('ABSPATH') || exit;

/**
 * Bo kiem tra tin trung du lieu theo JSON Schema (khong phu thuoc thu vien ngoai).
 *
 * Layer: Support (Cross-cutting Infrastructure)
 * Responsibility: Kiem tra payload dau vao/ra khan API dung mot tai lieu JSON Schema
 *                 duy nhat, thu tai day du loi thay vi dung stop o loi dau tien.
 * Boundary: Khong doc/ghi database, khong phu thuoc WordPress, khong tao HTTP Response.
 *
 * Ho tro cac tu khoa: type, enum, const, required, properties, additionalProperties,
 * items, minItems, maxItems, minLength, maxLength, pattern, minimum, maximum,
 * oneOf, anyOf.
 *
 * Thiet ke "fail-closed": neu gap tu khoa ma bo kiem tra khong ho tro ($ref, allOf,
 * if/then/else, ...) thi nem RuntimeException thay vi bo qua, de khong bao gio
 * kiem tra sai (pass) mot payload thu sai luong.
 */
final class JsonSchema
{
    /** @var list<array{pointer: string, keyword: string, message: string}> */
    private array $errors = [];

    private function __construct()
    {
    }

    /**
     * Kiem tra du lieu $data co tuan thu $schema hay khong.
     *
     * @param array<string, mixed> $schema Noi dung tach tu file *.schema.json
     *
     * @throws \RuntimeException Khi schema chua ho tro hoac sai cu phap
     */
    public static function validate(mixed $data, array $schema): ValidationResult
    {
        $validator = new self();
        $validator->assertNoUnsupportedKeywords($schema, '#');
        $validator->check($data, $schema, '');

        return $validator->errors === []
            ? ValidationResult::valid()
            : ValidationResult::invalid($validator->errors);
    }

    /**
     * Nap schema tu file JSON trong thu muc schemas/ cua plugin.
     *
     * @throws \RuntimeException Khi file khong ton tai hoac JSON khong hop le
     */
    public static function fromFile(string $schemaFile): array
    {
        $absolutePath = self::schemaPath($schemaFile);

        if (!is_file($absolutePath)) {
            throw new \RuntimeException(sprintf('Khong tim thay file JSON Schema: %s', $absolutePath));
        }

        $raw = file_get_contents($absolutePath);
        if ($raw === false) {
            throw new \RuntimeException(sprintf('Khong doc duoc file JSON Schema: %s', $absolutePath));
        }

        try {
            /** @var mixed $decoded */
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException(
                sprintf('File JSON Schema khong hop le (%s): %s', $absolutePath, $exception->getMessage()),
                0,
                $exception
            );
        }

        if (!is_array($decoded)) {
            throw new \RuntimeException(sprintf('File JSON Schema phai chua mot object: %s', $absolutePath));
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    /**
     * Kiem tra du lieu bang schema nap tu file.
     *
     * @throws \RuntimeException Khi file khong ton tai hoac JSON khong hop le
     */
    public static function validateFile(mixed $data, string $schemaFile): ValidationResult
    {
        return self::validate($data, self::fromFile($schemaFile));
    }

    /**
     * Chuyen ten khoa thanh JSON Pointer de bao dam duong dan chi dinh mot khoang.
     */
    public static function escapePointerToken(string $token): string
    {
        return str_replace(['~', '/'], ['~0', '~1'], $token);
    }

    private static function schemaPath(string $schemaFile): string
    {
        $baseDir = defined('KLEER_PLUGIN_DIR')
            ? rtrim(str_replace('\\', '/', KLEER_PLUGIN_DIR), '/')
            : dirname(__DIR__, 2);

        return $baseDir . '/schemas/' . ltrim(str_replace('\\', '/', $schemaFile), '/');
    }

    /**
     * @param array<string, mixed> $schema
     */
    private function assertNoUnsupportedKeywords(array $schema, string $pointer): void
    {
        $supported = [
            '$schema', '$id', 'title', 'description', '$comment',
            'type', 'enum', 'const',
            'required', 'properties', 'additionalProperties',
            'items', 'minItems', 'maxItems',
            'minLength', 'maxLength', 'pattern',
            'minimum', 'maximum',
            'oneOf', 'anyOf',
        ];

        foreach ($schema as $keyword => $_) {
            if (!is_string($keyword)) {
                throw new \RuntimeException(sprintf('Ten tu khoa schema khong hop le tai %s.', $pointer));
            }

            if (!in_array($keyword, $supported, true)) {
                throw new \RuntimeException(sprintf(
                    'JSON Schema khong ho tro tu khoa "%s" tai %s. Hay bo sung vao JsonSchema::SUPPORTED.',
                    $keyword,
                    $pointer
                ));
            }
        }

        if (isset($schema['properties']) && is_array($schema['properties'])) {
            /** @var array<string, mixed> $properties */
            $properties = $schema['properties'];
            foreach ($properties as $name => $subSchema) {
                if (is_array($subSchema)) {
                    /** @var array<string, mixed> $subSchema */
                    $this->assertNoUnsupportedKeywords($subSchema, $pointer . '/properties/' . self::escapePointerToken((string) $name));
                }
            }
        }

        foreach (['oneOf', 'anyOf'] as $combinator) {
            if (!isset($schema[$combinator]) || !is_array($schema[$combinator])) {
                continue;
            }

            /** @var list<mixed> $branches */
            $branches = $schema[$combinator];
            foreach ($branches as $index => $branch) {
                if (is_array($branch)) {
                    /** @var array<string, mixed> $branch */
                    $this->assertNoUnsupportedKeywords($branch, $pointer . '/' . $combinator . '/' . $index);
                }
            }
        }

        if (isset($schema['items']) && is_array($schema['items'])) {
            /** @var array<string, mixed> $items */
            $items = $schema['items'];
            $this->assertNoUnsupportedKeywords($items, $pointer . '/items');
        }
    }

    /**
     * @param array<string, mixed> $schema
     */
    private function check(mixed $data, array $schema, string $pointer): void
    {
        if (isset($schema['oneOf']) || isset($schema['anyOf'])) {
            $this->checkCombinator($data, $schema, $pointer);

            return;
        }

        if (isset($schema['type']) && !$this->matchesType($data, $schema['type'])) {
            $this->addError($pointer, 'type', sprintf(
                'Kieu du lieu phai la %s, nhung nhan duoc %s.',
                $this->describeExpectedType($schema['type']),
                $this->describeActualType($data)
            ));

            return;
        }

        $this->checkEnum($data, $schema, $pointer);
        $this->checkString($data, $schema, $pointer);
        $this->checkNumber($data, $schema, $pointer);
        $this->checkArray($data, $schema, $pointer);
        $this->checkObject($data, $schema, $pointer);
    }

    /**
     * @param array<string, mixed> $schema
     */
    private function checkCombinator(mixed $data, array $schema, string $pointer): void
    {
        $isOneOf = isset($schema['oneOf']);
        $branches = $isOneOf ? $schema['oneOf'] : ($schema['anyOf'] ?? []);

        if (!is_array($branches) || $branches === []) {
            throw new \RuntimeException(sprintf('Schema tai %s phai co it nhat mot nhanh.', $pointer));
        }

        $matched = [];
        /** @var list<list<array{pointer: string, keyword: string, message: string}>> $branchErrors */
        $branchErrors = [];

        foreach (array_values($branches) as $index => $branch) {
            if (!is_array($branch)) {
                throw new \RuntimeException(sprintf('Nhanh schema tai %s phai la object.', $pointer));
            }

            /** @var array<string, mixed> $branch */
            $probe = new self();
            $probe->check($data, $branch, $pointer);

            if ($probe->errors === []) {
                $matched[] = $index;
            } else {
                $branchErrors[] = $probe->errors;
            }
        }

        if ($isOneOf && count($matched) === 1) {
            return;
        }

        if (!$isOneOf && $matched !== []) {
            return;
        }

        if ($isOneOf) {
            $this->addError($pointer, 'oneOf', count($matched) === 0
                ? 'Gia tri khong khop bat ky nhanh nao cua schema.'
                : sprintf('Gia tri khop %d nhanh cua schema, chi duoc phep khop dung 1 nhanh.', count($matched)));

            return;
        }

        foreach ($branchErrors as $errors) {
            foreach ($errors as $error) {
                $this->errors[] = $error;
            }
        }
    }

    /**
     * @param array<string, mixed> $schema
     */
    private function checkEnum(mixed $data, array $schema, string $pointer): void
    {
        if (array_key_exists('const', $schema)) {
            if ($data !== $schema['const']) {
                $this->addError($pointer, 'const', sprintf(
                    'Gia tri phai bang %s.',
                    $this->encode($schema['const'])
                ));
            }
        }

        if (!isset($schema['enum']) || !is_array($schema['enum'])) {
            return;
        }

        foreach ($schema['enum'] as $candidate) {
            if ($data === $candidate) {
                return;
            }
        }

        $allowed = array_map(fn (mixed $value): string => $this->encode($value), $schema['enum']);
        $this->addError($pointer, 'enum', sprintf(
            'Gia tri phai nam trong danh sach cho phep: %s.',
            implode(', ', $allowed)
        ));
    }

    /**
     * @param array<string, mixed> $schema
     */
    private function checkString(mixed $data, array $schema, string $pointer): void
    {
        if (!is_string($data)) {
            return;
        }

        $length = $this->stringLength($data);

        if (isset($schema['minLength']) && $length < (int) $schema['minLength']) {
            $this->addError($pointer, 'minLength', sprintf('Chuoi toi thieu %d ky tu.', (int) $schema['minLength']));
        }

        if (isset($schema['maxLength']) && $length > (int) $schema['maxLength']) {
            $this->addError($pointer, 'maxLength', sprintf('Chuoi toi da %d ky tu.', (int) $schema['maxLength']));
        }

        if (isset($schema['pattern'])) {
            $pattern = (string) $schema['pattern'];
            $result = preg_match('~' . str_replace('~', '\~', $pattern) . '~u', $data);

            if ($result === false) {
                throw new \RuntimeException(sprintf('Bieu thuc "pattern" khong hop le tai %s: %s', $pointer, $pattern));
            }

            if ($result === 0) {
                $this->addError($pointer, 'pattern', sprintf('Chuoi khong khop dinh dang yeu cau: %s.', $pattern));
            }
        }
    }

    /**
     * @param array<string, mixed> $schema
     */
    private function checkNumber(mixed $data, array $schema, string $pointer): void
    {
        if (!is_int($data) && !is_float($data)) {
            return;
        }

        if (isset($schema['minimum']) && $data < $schema['minimum']) {
            $this->addError($pointer, 'minimum', sprintf('Gia tri toi thieu la %s.', $this->encode($schema['minimum'])));
        }

        if (isset($schema['maximum']) && $data > $schema['maximum']) {
            $this->addError($pointer, 'maximum', sprintf('Gia toi da la %s.', $this->encode($schema['maximum'])));
        }
    }

    /**
     * @param array<string, mixed> $schema
     */
    private function checkArray(mixed $data, array $schema, string $pointer): void
    {
        if (!is_array($data) || ($data !== [] && !$this->isJsonList($data))) {
            return;
        }

        $count = count($data);

        if (isset($schema['minItems']) && $count < (int) $schema['minItems']) {
            $this->addError($pointer, 'minItems', sprintf('Mang phai co it nhat %d phan tu.', (int) $schema['minItems']));
        }

        if (isset($schema['maxItems']) && $count > (int) $schema['maxItems']) {
            $this->addError($pointer, 'maxItems', sprintf('Mang chi duoc co toi da %d phan tu.', (int) $schema['maxItems']));
        }

        if (!isset($schema['items']) || !is_array($schema['items'])) {
            return;
        }

        /** @var array<string, mixed> $itemSchema */
        $itemSchema = $schema['items'];

        foreach (array_values($data) as $index => $item) {
            $this->check($item, $itemSchema, $pointer . '/' . $index);
        }
    }

    /**
     * Mang rong ("[]") vua la list rong vua la object rong khi giai ma tu JSON.
     * Voi truong hop nay van phai chay quy tac object, neu thi payload rong se
     * bo qua "required" va duoc coi la hop le.
     *
     * @param array<string, mixed> $schema
     */
    private function checkObject(mixed $data, array $schema, string $pointer): void
    {
        if (!is_array($data) || ($data !== [] && $this->isJsonList($data))) {
            return;
        }

        /** @var array<string, mixed> $required */
        $required = isset($schema['required']) && is_array($schema['required']) ? $schema['required'] : [];

        foreach ($required as $name) {
            if (!array_key_exists((string) $name, $data)) {
                $this->addError($pointer, 'required', sprintf('Thieu truong bat buoc "%s".', (string) $name));
            }
        }

        /** @var array<string, mixed> $properties */
        $properties = isset($schema['properties']) && is_array($schema['properties']) ? $schema['properties'] : [];

        foreach ($data as $name => $value) {
            $name = (string) $name;
            $childPointer = $pointer . '/' . self::escapePointerToken($name);

            if (isset($properties[$name]) && is_array($properties[$name])) {
                /** @var array<string, mixed> $propertySchema */
                $propertySchema = $properties[$name];
                $this->check($value, $propertySchema, $childPointer);

                continue;
            }

            if (array_key_exists('additionalProperties', $schema) && $schema['additionalProperties'] === false) {
                $this->addError($pointer, 'additionalProperties', sprintf(
                    'Truong "%s" khong duoc phep xuat hien trong payload.',
                    $name
                ));
            }
        }
    }

    /**
     * Kiem tra kieu du lieu JSON. Chua ky tu pattern vi kieu "integer" va "number"
     * la duy nhat, phan con lai dung ham is_* cua PHP.
     *
     * @param mixed $expectedType
     */
    private function matchesType(mixed $data, mixed $expectedType): bool
    {
        $types = is_array($expectedType) ? $expectedType : [$expectedType];

        foreach ($types as $type) {
            if ($this->matchesSingleType($data, (string) $type)) {
                return true;
            }
        }

        return false;
    }

    private function matchesSingleType(mixed $data, string $type): bool
    {
        return match ($type) {
            'null' => $data === null,
            'boolean' => is_bool($data),
            'integer' => is_int($data),
            'number' => is_int($data) || is_float($data),
            'string' => is_string($data),
            'array' => is_array($data) && ($data === [] || $this->isJsonList($data)),
            'object' => is_array($data) && ($data === [] || !$this->isJsonList($data)),
            default => throw new \RuntimeException(sprintf('Kieu du lieu khong ho tro: "%s".', $type)),
        };
    }

    /**
     * @param array<mixed> $value
     */
    private function isJsonList(array $value): bool
    {
        return array_is_list($value);
    }

    private function stringLength(string $value): int
    {
        return function_exists('mb_strlen')
            ? (int) mb_strlen($value, 'UTF-8')
            : strlen($value);
    }

    /**
     * @param mixed $expectedType
     */
    private function describeExpectedType(mixed $expectedType): string
    {
        return is_array($expectedType)
            ? implode(' hoac ', array_map('strval', $expectedType))
            : (string) $expectedType;
    }

    private function describeActualType(mixed $data): string
    {
        return match (true) {
            $data === null => 'null',
            is_bool($data) => 'boolean',
            is_int($data) => 'integer',
            is_float($data) => 'number',
            is_string($data) => 'string',
            is_array($data) && $this->isJsonList($data) => 'array',
            is_array($data) => 'object',
            default => get_debug_type($data),
        };
    }

    private function encode(mixed $value): string
    {
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded === false ? get_debug_type($value) : $encoded;
    }

    private function addError(string $pointer, string $keyword, string $message): void
    {
        $this->errors[] = [
            'pointer' => $pointer === '' ? '/' : $pointer,
            'keyword' => $keyword,
            'message' => $message,
        ];
    }
}
