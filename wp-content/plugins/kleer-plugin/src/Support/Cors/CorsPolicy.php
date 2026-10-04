<?php

declare(strict_types=1);

namespace Kleer\Support\Cors;

// Prevent direct file access (Security Case)
defined('ABSPATH') || exit;

/**
 * Chinh sach CORS thuan tuy cho REST API KLEER.
 *
 * Layer: Support (Cross-cutting Infrastructure)
 * Responsibility: Quyet dinh origin nao duoc phep, phai nhung header nao duoc
 *                 gui lai, va preflight co duoc chap nhan hay khong.
 * Boundary: Khong chua HTTP header cua WordPress, khong tao Response, khong doc
 *           du lieu nguon. Toan bo logic o day de test duoc khong can server.
 *
 * Nguyen tac an toan:
 * - KHONG bao gio "reflect" mot origin bat ky khong nam trong whitelist.
 * - Khi bat credentials, KHONG duoc echo "*"; chi echo origin cu the nao duoc
 *   whitelist. Cau hinh wildcard se bi bo qua co bao.
 * - Luon gui "Vary: Origin" de CDN/proxy khong phuc vu cach header CORS cho
 *   nguoi gui khac.
 */
final class CorsPolicy
{
    public const WILDCARD = '*';

    private const DEFAULT_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];

    private const DEFAULT_HEADERS = [
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'X-WP-Nonce',
        'X-KLEER-Session',
    ];

    /** @var list<string> */
    private array $allowedOrigins;

    /** @var list<string> */
    private array $allowedMethods;

    /** @var list<string> */
    private array $allowedHeaders;

    private bool $allowCredentials;

    private int $maxAge;

    /**
     * @param list<string> $allowedOrigins Danh sach origin duoc phep. Ho tro "*".
     * @param list<string>|null $allowedMethods
     * @param list<string>|null $allowedHeaders
     */
    public function __construct(
        array $allowedOrigins,
        ?array $allowedMethods = null,
        ?array $allowedHeaders = null,
        bool $allowCredentials = false,
        int $maxAge = 86400
    ) {
        $this->allowedOrigins = self::normalizeOrigins($allowedOrigins);
        $this->allowedMethods = self::normalizeList($allowedMethods ?? self::DEFAULT_METHODS);
        $this->allowedHeaders = self::normalizeList($allowedHeaders ?? self::DEFAULT_HEADERS);
        $this->allowCredentials = $allowCredentials;
        $this->maxAge = max(0, $maxAge);
    }

    /**
     * Doc cau hinh tu moi truong va khoi tao policy.
     *
     * Thu tu uu tien: filter > WordPress constant > bien moi truong > danh sach mac dinh.
     *
     * @param list<string> $defaultOrigins
     * @param list<string> $defaultMethods
     * @param list<string> $defaultHeaders
     */
    public static function fromEnvironment(
        array $defaultOrigins = [],
        ?array $defaultMethods = null,
        ?array $defaultHeaders = null,
        bool $allowCredentials = false,
        int $maxAge = 86400
    ): self {
        $origins = $defaultOrigins;
        $configured = self::readConfigValue('KLEER_CORS_ALLOWED_ORIGINS');

        if ($configured !== null) {
            $origins = self::splitList($configured);
        }

        $methods = $defaultMethods;
        $configuredMethods = self::readConfigValue('KLEER_CORS_ALLOWED_METHODS');
        if ($configuredMethods !== null) {
            $methods = self::splitList($configuredMethods);
        }

        $headers = $defaultHeaders;
        $configuredHeaders = self::readConfigValue('KLEER_CORS_ALLOWED_HEADERS');
        if ($configuredHeaders !== null) {
            $headers = self::splitList($configuredHeaders);
        }

        $credentials = $allowCredentials;
        $configuredCredentials = self::readConfigValue('KLEER_CORS_ALLOW_CREDENTIALS');
        if ($configuredCredentials !== null) {
            $credentials = self::toBool($configuredCredentials);
        }

        $age = $maxAge;
        $configuredAge = self::readConfigValue('KLEER_CORS_MAX_AGE');
        if ($configuredAge !== null && is_numeric($configuredAge)) {
            $age = (int) $configuredAge;
        }

        return new self($origins, $methods, $headers, $credentials, $age);
    }

    /**
     * @return list<string>
     */
    public function allowedOrigins(): array
    {
        return $this->allowedOrigins;
    }

    public function allowCredentials(): bool
    {
        return $this->allowCredentials;
    }

    public function isWildcardConfigured(): bool
    {
        return in_array(self::WILDCARD, $this->allowedOrigins, true);
    }

    public function isOriginAllowed(?string $origin): bool
    {
        if ($origin === null || trim($origin) === '') {
            return false;
        }

        if ($this->isWildcardConfigured()) {
            return true;
        }

        return in_array($this->normalizeOrigin($origin), $this->allowedOrigins, true);
    }

    /**
     * Tra ve origin se echo vao header, hoac null khi khong duoc phep.
     *
     * Khi cau hinh wildcard:
     * - khong bat credentials: echo "*";
     * - bat credentials: echo origin cu the, vi "*" khong hop le theo spec
     *   khi request co dung cookie/Authorization.
     */
    public function resolveOrigin(?string $origin): ?string
    {
        if (!$this->isOriginAllowed($origin)) {
            return null;
        }

        $normalized = $this->normalizeOrigin((string) $origin);

        if ($this->isWildcardConfigured()) {
            return $this->allowCredentials ? $normalized : self::WILDCARD;
        }

        return $normalized;
    }

    public function isPreflight(?string $method): bool
    {
        return $method !== null && strtoupper(trim($method)) === 'OPTIONS';
    }

    /**
     * Kiem tra mot request header co nam trong danh sach cho phep hay khong.
     * So sanh khong phan biet hoa chu hoa.
     */
    public function isRequestHeaderAllowed(string $header): bool
    {
        $needle = strtolower(trim($header));

        foreach ($this->allowedHeaders as $allowed) {
            if (strtolower($allowed) === $needle) {
                return true;
            }
        }

        return false;
    }

    public function isMethodAllowed(string $method): bool
    {
        return in_array(strtoupper(trim($method)), $this->allowedMethods, true);
    }

    /**
     * Chi lay phan "Access-Control-Request-Headers" ma client xin, loc theo whitelist.
     * Tra ve chuoi rong neu client xin header khong duoc phep nao.
     */
    public function filterRequestedHeaders(?string $requestedHeaders): string
    {
        if ($requestedHeaders === null || trim($requestedHeaders) === '') {
            return '';
        }

        $accepted = [];
        foreach (preg_split('/\s*,\s*/', trim($requestedHeaders)) ?: [] as $header) {
            if ($header === '') {
                continue;
            }

            if ($this->isRequestHeaderAllowed($header)) {
                $accepted[] = $header;
            }
        }

        return implode(', ', $accepted);
    }

    /**
     * Tap header CORS cho mot request da duoc xac thuc origin.
     *
     * @return array<string, string> Rong khi origin khong duoc phep.
     */
    public function buildHeaders(?string $origin, bool $isPreflight = false): array
    {
        $resolved = $this->resolveOrigin($origin);

        if ($resolved === null) {
            return [];
        }

        $headers = [
            'Access-Control-Allow-Origin' => $resolved,
            'Vary' => 'Origin',
            'Access-Control-Allow-Methods' => implode(', ', $this->allowedMethods),
            'Access-Control-Allow-Headers' => implode(', ', $this->allowedHeaders),
        ];

        if ($resolved !== self::WILDCARD) {
            $headers['Vary'] = 'Origin, Access-Control-Request-Headers';
        }

        if ($this->allowCredentials && $resolved !== self::WILDCARD) {
            $headers['Access-Control-Allow-Credentials'] = 'true';
        }

        if ($isPreflight) {
            $headers['Access-Control-Max-Age'] = (string) $this->maxAge;
        }

        return $headers;
    }

    /**
     * @return list<string>
     */
    public function allowedHeaders(): array
    {
        return $this->allowedHeaders;
    }

    /**
     * @return list<string>
     */
    public function allowedMethods(): array
    {
        return $this->allowedMethods;
    }

    public function maxAge(): int
    {
        return $this->maxAge;
    }

    /**
     * Loc cac header xin phep cua WordPress core sang danh sach cua policy.
     *
     * @param list<string> $defaultHeaders
     *
     * @return list<string>
     */
    public function applyToCoreAllowedHeaders(array $defaultHeaders = []): array
    {
        return array_values(array_unique(array_merge($defaultHeaders, $this->allowedHeaders)));
    }

    private function normalizeOrigin(string $origin): string
    {
        $lowered = strtolower(trim($origin));
        $withoutTrailingSlash = rtrim($lowered, '/');

        return $withoutTrailingSlash === '' ? self::WILDCARD : $withoutTrailingSlash;
    }

    /**
     * Chuẩn hóa danh sách origin được cấu hình để so sánh được với origin đến từ
     * request (bỏ dấu "/" cuối, không phân biệt hoa thường).
     *
     * @param list<string> $values
     *
     * @return list<string>
     */
    private static function normalizeOrigins(array $values): array
    {
        $normalized = [];

        foreach ($values as $value) {
            $trimmed = trim((string) $value);

            if ($trimmed === '') {
                continue;
            }

            $normalized[] = rtrim(strtolower($trimmed), '/') ?: self::WILDCARD;
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @param list<string> $values
     *
     * @return list<string>
     */
    private static function normalizeList(array $values): array
    {
        $normalized = [];

        foreach ($values as $value) {
            $trimmed = trim((string) $value);

            if ($trimmed === '') {
                continue;
            }

            $normalized[] = $trimmed;
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @return list<string>
     */
    private static function splitList(string $raw): array
    {
        return self::normalizeList(preg_split('/[,\s]+/', trim($raw)) ?: []);
    }

    private static function readConfigValue(string $name): ?string
    {
        if (defined($name)) {
            /** @var mixed $constant */
            $constant = constant($name);
            if (is_string($constant) && trim($constant) !== '') {
                return $constant;
            }
        }

        /** @var mixed $env */
        $env = getenv($name);
        if (is_string($env) && trim($env) !== '') {
            return $env;
        }

        /** @var mixed $server */
        $server = $_SERVER[$name] ?? null;
        if (is_string($server) && trim($server) !== '') {
            return $server;
        }

        return null;
    }

    private static function toBool(string $value): bool
    {
        return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
    }
}
