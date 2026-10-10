<?php

declare(strict_types=1);

namespace Kleer\Support\Cache;

// Prevent direct file access
defined('ABSPATH') || exit;

/**
 * Standardized object cache client backed by ext-redis with in-memory caching and fallback degradation.
 *
 * Layer: Support / Cache
 */
class KleerCache
{
    public const GROUP_PRODUCTS = 'kleer_products';
    public const GROUP_QUIZ = 'kleer_quiz';
    public const GROUP_API = 'kleer_api';

    private string $host;
    private int $port;
    private float $timeout;
    private bool $persistent;
    private int $database;
    private string $prefix;

    /** @var array<string, mixed> */
    private array $runtime = [];

    /** @var list<string> */
    private array $globalGroups = [];

    /** @var list<string> */
    private array $ignoredGroups = [];

    private ?\Redis $redis = null;
    private bool $connected = false;
    private ?string $unavailableReason = null;

    private int $hits = 0;
    private int $misses = 0;

    public function __construct(
        string $host = '127.0.0.1',
        int $port = 6379,
        float $timeout = 1.0,
        bool $persistent = false,
        int $database = 0,
        string $prefix = 'kleer:'
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->timeout = $timeout;
        $this->persistent = $persistent;
        $this->database = $database;
        $this->prefix = $prefix;

        $this->connect();
    }

    public static function fromEnvironment(): self
    {
        $host = getenv('REDIS_HOST') ?: '127.0.0.1';
        $port = (int) (getenv('REDIS_PORT') ?: 6379);
        $timeout = (float) (getenv('REDIS_TIMEOUT') ?: 1.0);
        $prefix = (string) (getenv('REDIS_PREFIX') ?: 'kleer:');

        return new self($host, $port, $timeout, false, 0, $prefix);
    }

    private function connect(): void
    {
        if (!extension_loaded('redis')) {
            $this->connected = false;
            $this->unavailableReason = 'Redis extension not loaded';
            return;
        }

        try {
            $this->redis = new \Redis();
            $success = $this->redis->connect($this->host, $this->port, $this->timeout);

            if ($success) {
                if ($this->database > 0) {
                    $this->redis->select($this->database);
                }
                $this->connected = true;
                $this->unavailableReason = null;
            } else {
                $this->connected = false;
                $this->unavailableReason = "Cannot connect to Redis at {$this->host}:{$this->port}";
            }
        } catch (\Throwable $e) {
            $this->connected = false;
            $this->unavailableReason = $e->getMessage();
        }
    }

    public function key(string $key, string $group = 'default'): string
    {
        return $this->prefix . $group . ':' . $key;
    }

    public function addGlobalGroup(string $group): void
    {
        if (!in_array($group, $this->globalGroups, true)) {
            $this->globalGroups[] = $group;
        }
    }

    /**
     * @return list<string>
     */
    public function globalGroups(): array
    {
        return $this->globalGroups;
    }

    public function addNonPersistentGroup(string $group): void
    {
        if (!in_array($group, $this->ignoredGroups, true)) {
            $this->ignoredGroups[] = $group;
        }
    }

    /**
     * @param list<string> $groups
     */
    public function addIgnoredGroups(array $groups): void
    {
        foreach ($groups as $g) {
            $this->addNonPersistentGroup($g);
        }
    }

    /**
     * @return list<string>
     */
    public function ignoredGroups(): array
    {
        return $this->ignoredGroups;
    }

    public function get(string $key, string $group = 'default'): mixed
    {
        if (in_array($group, $this->ignoredGroups, true)) {
            return false;
        }

        $internalKey = $this->key($key, $group);

        if (array_key_exists($internalKey, $this->runtime)) {
            $this->hits++;
            return $this->runtime[$internalKey];
        }

        if (!$this->connected || $this->redis === null) {
            $this->misses++;
            return false;
        }

        try {
            $val = $this->redis->get($internalKey);
            if ($val !== false) {
                $this->hits++;
                $this->runtime[$internalKey] = $val;
                return $val;
            }
        } catch (\Throwable) {
            $this->connected = false;
        }

        $this->misses++;
        return false;
    }

    public function set(string $key, mixed $value, string $group = 'default', int|false $expire = false): bool
    {
        if (in_array($group, $this->ignoredGroups, true)) {
            return true;
        }

        if (!$this->connected || $this->redis === null) {
            return false;
        }

        $internalKey = $this->key($key, $group);
        $this->runtime[$internalKey] = $value;

        try {
            $ttl = is_int($expire) && $expire > 0 ? $expire : null;
            if ($ttl !== null) {
                return (bool) $this->redis->setEx($internalKey, $ttl, (string) $value);
            }
            return (bool) $this->redis->set($internalKey, (string) $value);
        } catch (\Throwable) {
            $this->connected = false;
            return false;
        }
    }

    public function add(string $key, mixed $value, string $group = 'default', int|false $expire = false): bool
    {
        if ($this->exists($key, $group)) {
            return false;
        }

        return $this->set($key, $value, $group, $expire);
    }

    public function delete(string $key, string $group = 'default'): bool
    {
        $internalKey = $this->key($key, $group);
        unset($this->runtime[$internalKey]);

        if (!$this->connected || $this->redis === null) {
            return false;
        }

        try {
            return (bool) $this->redis->del($internalKey);
        } catch (\Throwable) {
            return false;
        }
    }

    public function exists(string $key, string $group = 'default'): bool
    {
        if (in_array($group, $this->ignoredGroups, true)) {
            return false;
        }

        $internalKey = $this->key($key, $group);
        if (array_key_exists($internalKey, $this->runtime)) {
            return true;
        }

        if (!$this->connected || $this->redis === null) {
            return false;
        }

        try {
            return (bool) $this->redis->exists($internalKey);
        } catch (\Throwable) {
            return false;
        }
    }

    public function incr(string $key, int $offset = 1, string $group = 'default'): int|false
    {
        if (!$this->connected || $this->redis === null) {
            return false;
        }

        $internalKey = $this->key($key, $group);
        try {
            $res = $this->redis->incrBy($internalKey, $offset);
            if ($res !== false) {
                $this->runtime[$internalKey] = (string) $res;
                return $res;
            }
        } catch (\Throwable) {
            return false;
        }

        return false;
    }

    public function decr(string $key, int $offset = 1, string $group = 'default'): int|false
    {
        if (!$this->connected || $this->redis === null) {
            return false;
        }

        $internalKey = $this->key($key, $group);
        try {
            $res = $this->redis->decrBy($internalKey, $offset);
            if ($res !== false) {
                $this->runtime[$internalKey] = (string) $res;
                return $res;
            }
        } catch (\Throwable) {
            return false;
        }

        return false;
    }

    public function flush(string $group): bool
    {
        $pattern = $this->prefix . $group . ':*';

        foreach (array_keys($this->runtime) as $k) {
            if (str_starts_with($k, $this->prefix . $group . ':')) {
                unset($this->runtime[$k]);
            }
        }

        if (!$this->connected || $this->redis === null) {
            return false;
        }

        try {
            $keys = $this->redis->keys($pattern);
            if (!empty($keys)) {
                $this->redis->del($keys);
            }
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function flushRuntime(): void
    {
        $this->runtime = [];
    }

    public function isAvailable(): bool
    {
        return $this->connected;
    }

    public function unavailableReason(): ?string
    {
        return $this->unavailableReason;
    }

    public function hits(): int
    {
        return $this->hits;
    }

    public function misses(): int
    {
        return $this->misses;
    }

    public function hitRate(): float
    {
        $total = $this->hits + $this->misses;
        if ($total === 0) {
            return 0.0;
        }

        return round($this->hits / $total, 4);
    }

    public function resetStats(): void
    {
        $this->hits = 0;
        $this->misses = 0;
    }

    /**
     * @return array{hits: int, misses: int, hit_rate: float, prefix: string}
     */
    public function stats(): array
    {
        return [
            'hits' => $this->hits,
            'misses' => $this->misses,
            'hit_rate' => $this->hitRate(),
            'prefix' => $this->prefix,
        ];
    }
}
