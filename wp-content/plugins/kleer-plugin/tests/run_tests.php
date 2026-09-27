<?php

declare(strict_types=1);

/**
 * Lightweight test suite to verify KLEER Plugin Architecture & Layers.
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

// 1. Mock minimal WordPress functions if not defined
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/../../../../');
}

$registered_actions = [];
$registered_routes = [];

if (!function_exists('add_action')) {
    function add_action(string $hook, callable $callback): void
    {
        global $registered_actions;
        $registered_actions[$hook][] = $callback;
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

echo "Running KLEER Plugin Architecture Tests...\n\n";

// 2. Load plugin classes
require_once __DIR__ . '/../src/Contracts/ProductRepositoryInterface.php';
require_once __DIR__ . '/../src/Models/Product.php';
require_once __DIR__ . '/../src/Services/ProductService.php';
require_once __DIR__ . '/../src/Controllers/HealthController.php';
require_once __DIR__ . '/../src/Endpoints/HealthEndpoints.php';
require_once __DIR__ . '/../src/Plugin.php';

// Test 1: Model layer
echo "1. Testing Model Layer (Product)...\n";
$product = new Kleer\Models\Product(101, 'Hydrating Gentle Cleanser', 250000);
assert_true($product->id === 101, 'Product id is 101');
assert_true($product->name === 'Hydrating Gentle Cleanser', 'Product name matches');
assert_true($product->price === 250000, 'Product price is 250000');
$array = $product->toArray();
assert_true($array['id'] === 101 && $array['name'] === 'Hydrating Gentle Cleanser', 'Product::toArray() returns valid array');

// Test 2: Controller layer
echo "\n2. Testing Controller Layer (HealthController)...\n";
$controller = new Kleer\Controllers\HealthController();
$response = $controller->health();
assert_true(isset($response['status']) && $response['status'] === 'ok', 'Health status is ok');
assert_true(isset($response['service']) && $response['service'] === 'kleer-plugin', 'Health service is kleer-plugin');

// Test 3: Service layer with Contract abstraction
echo "\n3. Testing Service & Contract Layer (ProductService)...\n";
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

// Test 5: Plugin bootstrap layer
echo "\n5. Testing Plugin Bootstrap Layer (Plugin)...\n";
$plugin = new Kleer\Plugin();
$plugin->register();
assert_true(true, 'Plugin::register() completes without error');

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

echo "\nSummary: {$tests} tests, {$failures} failures.\n";
if ($failures > 0) {
    exit(1);
}
echo "All architecture tests PASSED!\n";
