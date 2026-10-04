<?php

declare(strict_types=1);

namespace Kleer\Support\Cors;

// Prevent direct file access (Security Case)
defined('ABSPATH') || exit;

/**
 * Ghep CORS policy vao REST API cua WordPress.
 *
 * Layer: Support (Cross-cutting Infrastructure)
 * Responsibility: Hook vao WordPress de moi route trong namespace KLEER deu duoc
 *                 CORS dung chung, va tra loi preflight OPTIONS ma khong can auth.
 * Boundary: Khong chua route nao, khong co business logic, khong duoc Controller
 *           hay Service nao goi truc tiep.
 */
final class CorsService
{
    private CorsPolicy $policy;

    /** @var list<string> Header da gui trong request hien tai (phuc vu kiem thu). */
    private array $sentHeaders = [];

    public function __construct(?CorsPolicy $policy = null)
    {
        $this->policy = $policy ?? CorsPolicy::fromEnvironment();
    }

    public function policy(): CorsPolicy
    {
        return $this->policy;
    }

    /**
     * @return list<string> Header da gui trong request hien tai.
     */
    public function sentHeaders(): array
    {
        return $this->sentHeaders;
    }

    public function resetSentHeaders(): void
    {
        $this->sentHeaders = [];
    }

    /**
     * Đăng ký hook CORS. Chỉ nên gọi từ Plugin::register().
     *
     * Lưu ý thứ tự thời gian: WordPress đăng ký `rest_send_cors_headers` KHÔNG phải
     * ngay ở bootstrap mà trong action `rest_api_init` (priority 10, thông qua
     * `rest_api_default_filters`). Vì vậy phải gỡ filter trong `rest_api_init` với
     * priority > 10. Nếu gỡ ngay tại register() thì sẽ vô hiệu, và core vẫn phát
     * `Access-Control-Allow-Origin` cho mọi origin — tức là bypass whitelist.
     */
    public function register(): void
    {
        if (function_exists('add_filter')) {
            add_filter('rest_allowed_cors_headers', [$this, 'filterCoreAllowedHeaders']);

            add_filter('rest_pre_serve_request', [$this, 'sendCorsHeadersOnServe'], 15);

            add_filter('rest_api_init', [$this, 'removeCoreCorsHeaders'], 20);
            add_filter('rest_pre_dispatch', [$this, 'handlePreflightRequest'], 10, 3);
        }
    }

    /**
     * Gỡ bộ gửi CORS mặc định (permissive) của WordPress core.
     */
    public function removeCoreCorsHeaders(): void
    {
        if (function_exists('remove_filter') && function_exists('rest_send_cors_headers')) {
            remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
        }
    }

    /**
     * @param list<string> $defaultHeaders
     *
     * @return list<string>
     */
    public function filterCoreAllowedHeaders(array $defaultHeaders = []): array
    {
        return $this->policy->applyToCoreAllowedHeaders($defaultHeaders);
    }

    /**
     * Gui header CORS ngay truoc khi WordPress serialize response.
     */
    public function sendCorsHeadersOnServe(mixed $served = null): mixed
    {
        $origin = $this->currentOrigin();
        $this->emit($this->policy->buildHeaders($origin), $origin);

        return $served;
    }

    /**
     * Tra loi preflight OPTIONS voi HTTP 204, khong chay qua permission_callback.
     *
     * @param mixed $result  Ket qua cua cac filter truoc do.
     * @param mixed $server  WP_REST_Server hien tai.
     * @param mixed $request WP_REST_Request hien tai.
     */
    public function handlePreflightRequest(mixed $result = null, mixed $server = null, mixed $request = null): mixed
    {
        if ($result !== null || !is_object($request) || !method_exists($request, 'get_method')) {
            return $result;
        }

        if (!$this->policy->isPreflight((string) $request->get_method())) {
            return $result;
        }

        $origin = $this->currentOrigin();
        $headers = $this->policy->buildHeaders($origin, true);

        if ($headers === []) {
            // Origin khong nam trong whitelist: giao viec tra loi cho WordPress.
            return $result;
        }

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
            /** @var mixed $requested */
            $requested = $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'];
            $filtered = $this->policy->filterRequestedHeaders(is_string($requested) ? $requested : null);

            if ($filtered !== '') {
                $headers['Access-Control-Allow-Headers'] = $filtered;
            }
        }

        $this->emit($headers, $origin);

        return class_exists('\WP_REST_Response') ? new \WP_REST_Response(null, 204) : null;
    }

    /**
     * Lay Origin cua request hien tai.
     */
    private function currentOrigin(): ?string
    {
        if (function_exists('get_http_origin')) {
            /** @var mixed $origin */
            $origin = get_http_origin();

            return is_string($origin) && $origin !== '' ? $origin : null;
        }

        /** @var mixed $origin */
        $origin = $_SERVER['HTTP_ORIGIN'] ?? null;

        return is_string($origin) && $origin !== '' ? $origin : null;
    }

    /**
     * @param array<string, string> $headers
     */
    private function emit(array $headers, ?string $origin): void
    {
        if ($headers === []) {
            return;
        }

        foreach ($headers as $name => $value) {
            $this->sendHeader($name, $value);
        }
    }

    protected function sendHeader(string $name, string $value): void
    {
        $this->sentHeaders[] = $name . ': ' . $value;

        if (!headers_sent()) {
            header($name . ': ' . $value, true);
        }
    }

    /**
     * @internal Dung cho kiem thu - xem cac header da gui.
     *
     * @return array<string, string>
     */
    public function sentHeaderMap(): array
    {
        $map = [];
        foreach ($this->sentHeaders as $header) {
            $separator = strpos($header, ':');
            if ($separator === false) {
                continue;
            }
            $map[substr($header, 0, $separator)] = trim(substr($header, $separator + 1));
        }

        return $map;
    }
}
