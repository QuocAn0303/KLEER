<?php

declare(strict_types=1);

/**
 * Lightweight test suite to verify KLEER Plugin Architecture, Autoloader, Lifecycle & Layers.
 *
 * Runs without requiring an active WordPress database or web server.
 * Can be executed via CLI: php wp-content/plugins/kleer-plugin/tests/run_tests.php
 */

$failures = 0;
$tests = 0;

function assert_true(bool $condition, string $message): void
{
    global $failures, $tests;
    $tests++;
    if ($condition) {
        echo "  [PASS] {$message}\n";
    } else {
        echo "  [FAIL] {$message}\n";
        $failures++;
    }
}

// 1. Mock minimal WordPress functions and environment if not defined
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/../../../../');
}

$registered_actions = [];
$registered_routes = [];
$registered_activation_hooks = [];
$registered_deactivation_hooks = [];
$flushed_rewrite_rules_count = 0;
$deleted_transients = [];

if (!function_exists('add_action')) {
    function add_action(string $hook, callable $callback): void
    {
        global $registered_actions;
        $registered_actions[$hook][] = $callback;
    }
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook(string $file, callable $callback): void
    {
        global $registered_activation_hooks;
        $registered_activation_hooks[$file] = $callback;
    }
}

if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook(string $file, callable $callback): void
    {
        global $registered_deactivation_hooks;
        $registered_deactivation_hooks[$file] = $callback;
    }
}

if (!function_exists('register_rest_route')) {
    function register_rest_route(string $namespace, string $route, array $args): void
    {
        global $registered_routes;
        $registered_routes[] = [
            'namespace' => $namespace,
            'route' => $route,
            'args' => $args,
        ];
    }
}

if (!function_exists('__return_true')) {
    function __return_true(): bool
    {
        return true;
    }
}

if (!function_exists('flush_rewrite_rules')) {
    function flush_rewrite_rules(): void
    {
        global $flushed_rewrite_rules_count;
        $flushed_rewrite_rules_count++;
    }
}

if (!function_exists('delete_transient')) {
    function delete_transient(string $transient): bool
    {
        global $deleted_transients;
        $deleted_transients[] = $transient;
        return true;
    }
}

echo "Running KLEER Plugin Architecture Tests...\n\n";

// 2. Load plugin bootstrap file (tests constants, autoloader, and hook registrations)
require_once __DIR__ . '/../kleer-plugin.php';

// Test 0: Plugin Constants & Hooks Registration
echo "0. Testing Plugin Bootstrap, Constants & Lifecycle Hooks...\n";
assert_true(defined('KLEER_PLUGIN_VERSION') && KLEER_PLUGIN_VERSION === '0.1.0', 'KLEER_PLUGIN_VERSION is 0.1.0');
assert_true(defined('KLEER_MIN_PHP_VERSION') && KLEER_MIN_PHP_VERSION === '8.2', 'KLEER_MIN_PHP_VERSION is 8.2');
assert_true(defined('KLEER_PLUGIN_FILE'), 'KLEER_PLUGIN_FILE is defined');
assert_true(defined('KLEER_PLUGIN_DIR'), 'KLEER_PLUGIN_DIR is defined');
assert_true(isset($registered_activation_hooks[KLEER_PLUGIN_FILE]), 'Activation hook is registered for plugin file');
assert_true(isset($registered_deactivation_hooks[KLEER_PLUGIN_FILE]), 'Deactivation hook is registered for plugin file');
assert_true(!empty($registered_actions['plugins_loaded']), 'plugins_loaded hook is registered');

// Test 1: Autoloader & Model layer
echo "\n1. Testing Autoloader & Model Layer (Product)...\n";
assert_true(class_exists(Kleer\Models\Product::class), 'Autoloader successfully loads Kleer\Models\Product');
$product = new Kleer\Models\Product(101, 'Hydrating Gentle Cleanser', 250000);
assert_true($product->id === 101, 'Product id is 101');
assert_true($product->name === 'Hydrating Gentle Cleanser', 'Product name matches');
assert_true($product->price === 250000, 'Product price is 250000');
$array = $product->toArray();
assert_true($array['id'] === 101 && $array['name'] === 'Hydrating Gentle Cleanser', 'Product::toArray() returns valid array');

// Test 2: Controller layer
echo "\n2. Testing Controller Layer (HealthController)...\n";
assert_true(class_exists(Kleer\Controllers\HealthController::class), 'Autoloader successfully loads Kleer\Controllers\HealthController');
$controller = new Kleer\Controllers\HealthController();
$response = $controller->health();
assert_true(isset($response['status']) && $response['status'] === 'ok', 'Health status is ok');
assert_true(isset($response['service']) && $response['service'] === 'kleer-plugin', 'Health service is kleer-plugin');

// Test 3: Service layer with Contract abstraction
echo "\n3. Testing Service & Contract Layer (ProductService)...\n";
assert_true(interface_exists(Kleer\Contracts\ProductRepositoryInterface::class), 'Autoloader successfully loads ProductRepositoryInterface');
assert_true(class_exists(Kleer\Services\ProductService::class), 'Autoloader successfully loads ProductService');

$mockRepo = new class implements Kleer\Contracts\ProductRepositoryInterface {
    public function findFeatured(int $limit = 5): array
    {
        $items = [];
        for ($i = 1; $i <= $limit; $i++) {
            $items[] = ['id' => $i, 'name' => "Featured Product {$i}"];
        }
        return $items;
    }
};
$service = new Kleer\Services\ProductService($mockRepo);
$featured = $service->featuredProducts(3);
assert_true(count($featured) === 3, 'ProductService returns 3 items when requested 3');
assert_true($featured[0]['name'] === 'Featured Product 1', 'Item name matches mock repository');
$clamped = $service->featuredProducts(50);
assert_true(count($clamped) === 20, 'ProductService clamps limit to maximum 20');
$clampedZero = $service->featuredProducts(0);
assert_true(count($clampedZero) === 1, 'ProductService clamps limit to minimum 1');

// Test 4: Endpoints layer
echo "\n4. Testing Endpoints Layer (HealthEndpoints)...\n";
assert_true(class_exists(Kleer\Endpoints\HealthEndpoints::class), 'Autoloader successfully loads HealthEndpoints');
$endpoints = new Kleer\Endpoints\HealthEndpoints($controller);
$endpoints->register();
assert_true(!empty($registered_actions['rest_api_init']), 'HealthEndpoints hooks into rest_api_init');

// Trigger rest_api_init callback to simulate WordPress REST initialization
foreach ($registered_actions['rest_api_init'] as $cb) {
    $cb();
}
assert_true(!empty($registered_routes), 'register_rest_route was called');
$healthRoute = null;
foreach ($registered_routes as $r) {
    if ($r['namespace'] === 'kleer/v1' && $r['route'] === '/health') {
        $healthRoute = $r;
        break;
    }
}
assert_true($healthRoute !== null, 'Route kleer/v1 /health is registered');
assert_true($healthRoute['args']['methods'] === 'GET', 'HTTP method is GET');
assert_true($healthRoute['args']['permission_callback'] === '__return_true', 'Permission callback is __return_true');
$endpointOutput = call_user_func($healthRoute['args']['callback']);
assert_true($endpointOutput['status'] === 'ok', 'Endpoint callback returns status ok');

// Test 5: Plugin Singleton & Lifecycle Methods
echo "\n5. Testing Plugin Singleton & Lifecycle Methods...\n";
assert_true(class_exists(Kleer\Plugin::class), 'Autoloader successfully loads Kleer\Plugin');
$pluginInstance1 = Kleer\Plugin::getInstance();
$pluginInstance2 = Kleer\Plugin::getInstance();
assert_true($pluginInstance1 === $pluginInstance2, 'Plugin::getInstance() implements Singleton pattern');

$reflection = new ReflectionClass(Kleer\Plugin::class);
$constructor = $reflection->getConstructor();
assert_true($constructor !== null && $constructor->isPrivate(), 'Plugin constructor is private (enforces singleton)');

// Test activation hook execution
$flushedBefore = $flushed_rewrite_rules_count;
Kleer\Plugin::activate();
assert_true($flushed_rewrite_rules_count === $flushedBefore + 1, 'Plugin::activate() flushes rewrite rules');

// Test deactivation hook execution
$flushedBeforeDeact = $flushed_rewrite_rules_count;
Kleer\Plugin::deactivate();
assert_true($flushed_rewrite_rules_count === $flushedBeforeDeact + 1, 'Plugin::deactivate() flushes rewrite rules');
assert_true(in_array('kleer_plugin_cache', $deleted_transients, true), 'Plugin::deactivate() cleans temporary transient cache');

// Test plugins_loaded execution
foreach ($registered_actions['plugins_loaded'] as $cb) {
    $cb();
}
assert_true(true, 'plugins_loaded callback registers Plugin without error');

// Test 6: Theme independence
echo "\n6. Testing Theme Independence...\n";
$pluginDir = dirname(__DIR__);
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pluginDir . '/src'));
$themeFound = false;
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        if (preg_match('/wp-content\/themes|kleer-theme/i', $content)) {
            $themeFound = true;
            break;
        }
    }
}
$entryContent = file_get_contents($pluginDir . '/kleer-plugin.php');
if (preg_match('/wp-content\/themes|kleer-theme/i', $entryContent)) {
    $themeFound = true;
}
assert_true(!$themeFound, 'Plugin production code has zero references to theme');

// Test 7: Direct File Access Security Check
echo "\n7. Testing Security Case (defined ABSPATH || exit)...\n";
assert_true(strpos($entryContent, "defined('ABSPATH') || exit;") !== false, 'kleer-plugin.php contains defined ABSPATH || exit;');
$pluginClassContent = file_get_contents($pluginDir . '/src/Plugin.php');
assert_true(strpos($pluginClassContent, "defined('ABSPATH') || exit;") !== false, 'src/Plugin.php contains defined ABSPATH || exit;');

echo "\nSummary: {$tests} tests, {$failures} failures.\n";
if ($failures > 0) {
    exit(1);
}
echo "All architecture tests PASSED!\n";
