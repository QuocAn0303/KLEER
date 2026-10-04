<?php

declare(strict_types=1);

/**
 * Smoke test: KleerCache phai noi duoc voi Redis that.
 *
 * Chay trong container php voi container redis dang chay:
 *   docker cp verify_cache.php kleer-php:/tmp/ && docker exec -e REDIS_HOST=redis kleer-php php /tmp/verify_cache.php
 *
 * Khong dung bo test CLI o tests/run_tests.php vi bo do khong can Redis.
 */

define('ABSPATH', '/var/www/html/');

// Chay duoc ca khi file nam trong repo, lan khi duoc copy sang container.
$pluginBootstrap = dirname(__DIR__) . '/kleer-plugin.php';

if (!is_file($pluginBootstrap)) {
    $pluginBootstrap = '/var/www/html/wp-content/plugins/kleer-plugin/kleer-plugin.php';
}

if (!is_file($pluginBootstrap)) {
    fwrite(STDERR, 'Khong tim thay file khoi dong plugin: ' . $pluginBootstrap . PHP_EOL);
    exit(1);
}

// Script nay chay ngoai WordPress, nen can khai bao ham WordPress toi thieu
// de file khoi dong nap duoc. Cac ham nay chi ghi nho, khong gui request nao.
if (!function_exists('add_action')) {
    function add_action(string $hook, callable $callback, int $priority = 10, int $accepted_args = 1): bool
    {
        return true;
    }
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook(string $file, callable $callback): void
    {
    }
}

if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook(string $file, callable $callback): void
    {
    }
}

require $pluginBootstrap;

use Kleer\Support\Cache\KleerCache;

$failures = [];

function check(string $name, bool $condition, string $detail = ''): void
{
    global $failures;

    if ($condition) {
        echo "  [PASS] {$name}\n";

        return;
    }

    $failures[] = $name . ($detail !== '' ? ' -> ' . $detail : '');
    echo "  [FAIL] {$name}" . ($detail !== '' ? ' -> ' . $detail : '') . "\n";
}

$cache = new KleerCache(
    getenv('REDIS_HOST') ?: 'redis',
    (int) (getenv('REDIS_PORT') ?: 6379),
    2.0,
    false,
    0,
    'smoke:'
);

$cache->flush(KleerCache::GROUP_PRODUCTS);
$cache->flush(KleerCache::GROUP_QUIZ);

$cache->set('alpha', 'one', KleerCache::GROUP_PRODUCTS, 60);
check('set/get round-trip qua Redis that', $cache->get('alpha', KleerCache::GROUP_PRODUCTS) === 'one', (string) $cache->unavailableReason());

$cache->resetStats();
$cache->get('alpha', KleerCache::GROUP_PRODUCTS);
$cache->get('khong-ton-tai', KleerCache::GROUP_PRODUCTS);
check('hit/miss dem dung', $cache->hits() === 1 && $cache->misses() === 1, sprintf('hits=%d misses=%d', $cache->hits(), $cache->misses()));

$cache->set('beta', 'two', KleerCache::GROUP_QUIZ, 60);
$cache->flush(KleerCache::GROUP_PRODUCTS);
check('xoa nhom san pham', $cache->get('alpha', KleerCache::GROUP_PRODUCTS) === false);
check('nhom quiz con nguyen', $cache->get('beta', KleerCache::GROUP_QUIZ) === 'two');

check('add() khong ghi de gia tri ton tai', $cache->add('beta', 'other', KleerCache::GROUP_QUIZ, 60) === false);
check('add() tao khoa moi', $cache->add('gamma', 'three', KleerCache::GROUP_QUIZ, 60) === true);
check('exists() phan biet ton tai/khong', $cache->exists('gamma', KleerCache::GROUP_QUIZ) && !$cache->exists('delta', KleerCache::GROUP_QUIZ));

$cache->set('counter', '0', KleerCache::GROUP_API, 60);
$cache->incr('counter', 5, KleerCache::GROUP_API);
check('incr() tang gia tri', $cache->get('counter', KleerCache::GROUP_API) === '5', (string) $cache->get('counter', KleerCache::GROUP_API));
$cache->decr('counter', 2, KleerCache::GROUP_API);
check('decr() giam gia tri', $cache->get('counter', KleerCache::GROUP_API) === '3');

$cache->addNonPersistentGroup('counts');
$cache->set('x', 'y', 'counts', 60);
check('nhom non-persistent bo qua qua du da gui', $cache->get('x', 'counts') === false);

$stats = $cache->stats();
check('stats() tra hit_rate', isset($stats['hit_rate']) && is_float($stats['hit_rate']));
check('cache bao available', $cache->isAvailable());

$cache->flushRuntime();
check('flushRuntime() xoa bo dem', $cache->get('beta', KleerCache::GROUP_QUIZ) === 'two');

$cache->flush(KleerCache::GROUP_PRODUCTS);
$cache->flush(KleerCache::GROUP_QUIZ);
$cache->flush(KleerCache::GROUP_API);

echo "\nSummary: " . count($failures) . " failures.\n";

if ($failures !== []) {
    exit(1);
}

echo "Redis cache smoke test PASSED!\n";
