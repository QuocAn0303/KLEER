<?php

declare(strict_types=1);

namespace Kleer\Controllers\Concerns;

use Kleer\Support\Validation\JsonSchema;
use Kleer\Support\Validation\ValidationResult;

// Prevent direct file access (Security Case)
defined('ABSPATH') || exit;

use WP_Error;
use WP_REST_Request;

/**
 * Tien ich dung chung cho moi Controller de kiem tra tin trung payload JSON.
 *
 * Layer: Controllers (Concerns)
 * Responsibility: Kiem tra JSON Schema o ranh giahi HTTP va chuyen ValidationResult
 *                 thanh WP_Error theo dung dinh dang loi thong nhat cua he thong.
 * Boundary: Khong chua business logic, khong truy van database.
 *
 * Cach dung trong Controller:
 *   $params = $this->validateJsonPayload($request, 'quiz-submission.schema.json');
 *   if (is_wp_error($params)) {
 *       return $params;
 *   }
 */
trait ValidatesJsonPayload
{
    /**
     * Kiem tra JSON body cua request theo file schema.
     *
     * @return array<string, mixed>|WP_Error Mang da chuan hoa neu hop le, WP_Error neu khong.
     *
     * @throws \RuntimeException Khi file schema khong ton tai hoac dung tu khoa chua ho tro.
     */
    protected function validateJsonPayload(WP_REST_Request $request, string $schemaFile): array|WP_Error
    {
        $payload = $this->readJsonPayload($request);
        $result = JsonSchema::validate($payload, JsonSchema::fromFile($schemaFile));

        if ($result->isInvalid()) {
            return $this->payloadValidationError($result);
        }

        /** @var array<string, mixed> $payload */
        return $payload;
    }

    /**
     * Chuyen ket qua kiem tra thanh WP_Error theo chuan response loi cua he thong.
     */
    protected function payloadValidationError(ValidationResult $result): WP_Error
    {
        $errors = [];
        foreach ($result->errors() as $error) {
            $errors[] = [
                'pointer' => $error['pointer'],
                'keyword' => $error['keyword'],
                'message' => $error['message'],
            ];
        }

        return new WP_Error(
            'kleer_invalid_payload',
            __('Dữ liệu gửi lên không hợp lệ theo schema của API.', 'kleer-plugin'),
            [
                'status' => 400,
                'errors' => $errors,
            ]
        );
    }

    /**
     * Doc JSON body cua request, tra ve mang rong khi body khong phai object.
     *
     * @return array<string, mixed>
     */
    protected function readJsonPayload(WP_REST_Request $request): array
    {
        $body = $request->get_body();

        if (!is_string($body) || trim($body) === '') {
            return [];
        }

        try {
            /** @var mixed $decoded */
            $decoded = json_decode($body, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }
}
