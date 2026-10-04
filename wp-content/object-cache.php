<?php

/**
 * Object Cache drop-in cho KLEER (dung ext-redis).
 *
 * Day la "drop-in" cua WordPress: file nay nam o wp-content/object-cache.php se duoc
 * WordPress nap AUTOMATIC, khong can kich hoat trong admin. Neu khong ton tai,
 * WordPress fallback sang cache trong bo nho.
 *
 * Cac ham day phai ton tai theo dung chu ky cua WordPress Object Cache API.
 *
 * @package KLEER
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

if (!defined('KLEER_REDIS_AVAILABLE')) {
    // Redis bat buoc phai co san truoc khi nap drop-in, neu khong thi bo qua de
    // WordPress dung cache mac dinh thay vi sinh loi fatal.
    if (!extension_loaded('redis')) {
        return;
    }

    define('KLEER_REDIS_AVAILABLE', true);
    define('KLEER_REDIS_DROPIN_FILE', __FILE__);
}

/**
 * Adapter dua KleerCache len WordPress Object Cache API.
 *
 * @see KleerCache Trai nhiem chuan hoa duoc trong src/Support/Cache/KleerCache.php
 */
final class KleerObjectCache
{
    private KleerCache $cache;

    public function __construct(?KleerCache $cache = null)
    {
        $this->cache = $cache ?? KleerCache::fromEnvironment();

        // Cac nhom WordPress mac dinh khong nen luu lau: chi doc va phai luon moi.
        $this->cache->addIgnoredGroups(['counts', 'plugins', 'theme_json']);
    }

    public function cache(): KleerCache
    {
        return $this->cache;
    }

    /**
     * @return array{hits: int, misses: int, hit_rate: float, prefix: string}
     */
    public function stats(): array
    {
        return $this->cache->stats();
    }

    /**
     * @param string $key
     * @param string $group
     * @param bool $force
     * @param bool &$found
     *
     * @return mixed
     */
    public function get($key, $group = 'default', $force = false, &$found = null)
    {
        $value = $this->cache->get((string) $key, (string) $group);

        if ($value === false) {
            $found = false;

            return false;
        }

        $found = true;

        return $this->unserialize((string) $value);
    }

    /**
     * @param string $key
     * @param mixed $data
     * @param string $group
     * @param int|false $expire
     *
     * @return bool
     */
    public function set($key, $data, $group = 'default', $expire = false)
    {
        return $this->cache->set((string) $key, $this->serialize($data), (string) $group, is_int($expire) ? $expire : false);
    }

    /**
     * @param string $key
     * @param mixed $data
     * @param string $group
     * @param int|false $expire
     *
     * @return bool
     */
    public function add($key, $data, $group = 'default', $expire = false)
    {
        return $this->cache->add((string) $key, $this->serialize($data), (string) $group, is_int($expire) ? $expire : false);
    }

    /**
     * @param string $key
     * @param string $group
     *
     * @return bool
     */
    public function delete($key, $group = 'default')
    {
        return $this->cache->delete((string) $key, (string) $group);
    }

    /**
     * @param string $key
     * @param string $group
     *
     * @return bool
     */
    public function exists($key, $group = 'default')
    {
        return $this->cache->exists((string) $key, (string) $group);
    }

    /**
     * @param string $key
     * @param int $offset
     * @param string $group
     *
     * @return int|false
     */
    public function incr($key, $offset = 1, $group = 'default')
    {
        return $this->cache->incr((string) $key, (int) $offset, (string) $group);
    }

    /**
     * @param string $key
     * @param int $offset
     * @param string $group
     *
     * @return int|false
     */
    public function decr($key, $offset = 1, $group = 'default')
    {
        return $this->cache->decr((string) $key, (int) $offset, (string) $group);
    }

    /**
     * @param string $group
     *
     * @return bool
     */
    public function flush($group = 'default')
    {
        $this->cache->flushRuntime();

        return $this->cache->flush((string) $group);
    }

    /**
     * @param string $group
     *
     * @return array<int, string>
     */
    public function addGlobalGroups($group)
    {
        $groups = is_array($group) ? $group : [$group];

        foreach ($groups as $single) {
            $this->cache->addGlobalGroup((string) $single);
        }

        return $this->cache->globalGroups();
    }

    /**
     * @param string|string[] $group
     *
     * @return array<int, string>
     */
    public function addNonPersistentGroups($group)
    {
        $groups = is_array($group) ? $group : [$group];
        $this->cache->addIgnoredGroups(array_map('strval', $groups));

        return $this->cache->ignoredGroups();
    }

    /**
     * @param string|string[] $groups
     *
     * @return array<int, string>
     */
    public function addIgnoredGroups($groups)
    {
        return $this->addNonPersistentGroups($groups);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function get_multiple($keys, $group = 'default')
    {
        $values = [];

        foreach ((array) $keys as $single) {
            $value = $this->get((string) $single, (string) $group);
            if ($value !== false) {
                $values[(string) $single] = $value;
            }
        }

        return $values;
    }

    /**
     * @param array<string, mixed> $data
     * @param string $group
     * @param int|false $expire
     *
     * @return array<string, bool>
     */
    public function set_multiple($data, $group = 'default', $expire = false)
    {
        $results = [];

        foreach ((array) $data as $key => $value) {
            $results[(string) $key] = $this->set((string) $key, $value, (string) $group, $expire);
        }

        return $results;
    }

    /**
     * @param array<string, mixed> $data
     * @param string $group
     * @param int|false $expire
     *
     * @return array<string, bool>
     */
    public function add_multiple($data, $group = 'default', $expire = false)
    {
        $results = [];

        foreach ((array) $data as $key => $value) {
            $results[(string) $key] = $this->add((string) $key, $value, (string) $group, $expire);
        }

        return $results;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, bool>
     */
    public function delete_multiple($data, $group = 'default')
    {
        $results = [];

        foreach ((array) $data as $key => $value) {
            $results[(string) $key] = $this->delete((string) $value, (string) $group);
        }

        return $results;
    }

    /**
     * WordPress goi ham nay de biet cache co ho tro flush nhom hay khong.
     *
     * @param string $group
     *
     * @return bool
     */
    public function flush_group($group)
    {
        return $this->flush($group);
    }

    /**
     * Serialize du dung PHP serialize de giu nguyen kieu du lieu cua WordPress.
     *
     * @param mixed $data
     */
    private function serialize($data): string
    {
        return serialize($data);
    }

    /**
     * @return mixed
     */
    private function unserialize(string $value)
    {
        $result = @unserialize($value);

        // unserialize tra false khi gia tri cu la false, nen can phan biet.
        if ($result === false && $value !== serialize(false)) {
            return $value;
        }

        return $result;
    }
}

// Khoi tao global $wp_object_cache theo quy uoc cua WordPress.
/** @var KleerObjectCache $wp_object_cache */
$wp_object_cache = new KleerObjectCache();
