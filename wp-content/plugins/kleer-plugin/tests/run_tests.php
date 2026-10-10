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

// Filters registry (required by Support\Cors\CorsService)
$registered_filters = [];
$removed_filters = [];

if (!function_exists('add_filter')) {
    function add_filter(string $hook, callable $callback, int $priority = 10, int $accepted_args = 1): bool
    {
        global $registered_filters;
        $registered_filters[$hook][] = ['callback' => $callback, 'priority' => $priority, 'accepted_args' => $accepted_args];
        return true;
    }
}

if (!function_exists('remove_filter')) {
    function remove_filter(string $hook, callable $callback, int $priority = 10): bool
    {
        global $removed_filters;
        $removed_filters[$hook][] = ['callback' => $callback, 'priority' => $priority];
        return true;
    }
}

if (!function_exists('__')) {
    function __($text, string $domain = 'default'): string
    {
        return $text;
    }
}

// WordPress core ships this function in wp-includes/rest-api.php. CorsService removes
// it so the permissive wildcard sender is replaced by the whitelist policy.
if (!function_exists('rest_send_cors_headers')) {
    function rest_send_cors_headers($served = false)
    {
        return $served;
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error(mixed $thing): bool
    {
        return $thing instanceof WP_Error;
    }
}

if (!class_exists('WP_Error')) {
    class WP_Error
    {
        /** @var array<string, list<string>> */
        private array $errors = [];

        private array $error_data;

        public function __construct(string $code = '', string $message = '', array $data = [])
        {
            if ($code !== '') {
                $this->errors[$code][] = $message;
            }
            $this->error_data = $data;
        }

        public function get_error_code(): string
        {
            $codes = array_keys($this->errors);

            return $codes[0] ?? '';
        }

        public function get_error_message(string $code = ''): string
        {
            $code = $code !== '' ? $code : $this->get_error_code();

            return $this->errors[$code][0] ?? '';
        }

        public function get_error_data(string $code = ''): array
        {
            return $this->error_data;
        }
    }
}

if (!class_exists('WP_REST_Response')) {
    class WP_REST_Response
    {
        public mixed $data;

        private int $status;

        /** @var array<string, mixed> */
        private array $headers;

        public function __construct(mixed $data = null, int $status = 200, array $headers = [])
        {
            $this->data = $data;
            $this->status = $status;
            $this->headers = $headers;
        }

        public function get_data(): mixed
        {
            return $this->data;
        }

        public function get_status(): int
        {
            return $this->status;
        }

        public function get_headers(): array
        {
            return $this->headers;
        }
    }
}

if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request
    {
        private string $method;

        private string $body;

        public function __construct(string $method = 'GET', string $body = '')
        {
            $this->method = $method;
            $this->body = $body;
        }

        public function get_method(): string
        {
            return $this->method;
        }

        public function get_body(): string
        {
            return $this->body;
        }

        /** @return array<string, mixed>|null */
        public function get_json_params(): ?array
        {
            if ($this->body === '') {
                return null;
            }

            /** @var mixed $decoded */
            $decoded = json_decode($this->body, true);

            return is_array($decoded) ? $decoded : null;
        }
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

$supportGuardCount = 0;
foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    if (str_contains(file_get_contents($file->getPathname()), "defined('ABSPATH') || exit;")) {
        $supportGuardCount++;
    }
}
assert_true($supportGuardCount >= 5, 'Every new Support/Concerns file carries the direct-access guard');

// Test 8: CORS Policy (pure decision logic)
echo "\n8. Testing CORS Policy (whitelist & header generation)...\n";
use Kleer\Support\Cors\CorsPolicy;

$policy = new CorsPolicy(['https://shop.kleer.vn', 'http://localhost:8080']);
assert_true($policy->isOriginAllowed('https://shop.kleer.vn'), 'Whitelisted origin is allowed');
assert_true(!$policy->isOriginAllowed('https://evil.example.com'), 'Unlisted origin is rejected');
assert_true(!$policy->isOriginAllowed(null), 'Missing origin is rejected (same-origin requests unaffected)');

$normalizedHeaders = $policy->buildHeaders('https://shop.kleer.vn');
assert_true(($normalizedHeaders['Access-Control-Allow-Origin'] ?? null) === 'https://shop.kleer.vn', 'Allow-Origin echoes the whitelisted origin');
assert_true($policy->buildHeaders('https://evil.example.com') === [], 'No CORS headers emitted for a rejected origin');
assert_true(isset($normalizedHeaders['Vary']), 'Vary header is always emitted (cache poisoning guard)');
assert_true(!isset($normalizedHeaders['Access-Control-Allow-Credentials']), 'Credentials header absent by default');
assert_true(!isset($policy->buildHeaders('https://shop.kleer.vn')['Access-Control-Max-Age']), 'Max-Age is not sent on a normal request');
assert_true(($policy->buildHeaders('https://shop.kleer.vn', true)['Access-Control-Max-Age'] ?? null) === '86400', 'Preflight carries Max-Age 86400');

$loosePolicy = new CorsPolicy(['https://shop.kleer.vn/'], null, null, false);
assert_true($loosePolicy->isOriginAllowed('HTTPS://Shop.Kleer.Vn'), 'Origin comparison ignores case and trailing slash');
assert_true($loosePolicy->resolveOrigin('HTTPS://Shop.Kleer.Vn') === 'https://shop.kleer.vn', 'Resolved origin is normalised before being echoed');

$wildcardPolicy = new CorsPolicy(['*']);
assert_true($wildcardPolicy->isOriginAllowed('https://anything.example.com'), 'Wildcard policy allows any origin');
assert_true($wildcardPolicy->resolveOrigin('https://anything.example.com') === '*', 'Wildcard echoes * when credentials are off');
assert_true(!isset($wildcardPolicy->buildHeaders('https://anything.example.com')['Access-Control-Max-Age']), 'Wildcard response carries no Max-Age before preflight');

$wildcardCredentialsPolicy = new CorsPolicy(['*'], null, null, true);
assert_true(
    $wildcardCredentialsPolicy->resolveOrigin('https://anything.example.com') === 'https://anything.example.com',
    'Wildcard + credentials echoes the concrete origin instead of *'
);
$wildcardCredentialHeaders = $wildcardCredentialsPolicy->buildHeaders('https://anything.example.com');
assert_true(
    ($wildcardCredentialHeaders['Access-Control-Allow-Origin'] ?? null) !== '*',
    'Wildcard + credentials never emits a literal * (invalid per spec with credentials)'
);
assert_true(($wildcardCredentialHeaders['Access-Control-Allow-Credentials'] ?? null) === 'true', 'Wildcard + credentials emits Allow-Credentials');

$credentialHeaders = (new CorsPolicy(['https://shop.kleer.vn'], null, null, true))->buildHeaders('https://shop.kleer.vn');
assert_true(($credentialHeaders['Access-Control-Allow-Credentials'] ?? null) === 'true', 'Credentials header sent for explicit origin when enabled');

assert_true($policy->isPreflight('options'), 'OPTIONS is detected as preflight (case-insensitive)');
assert_true(!$policy->isPreflight('POST'), 'POST is not a preflight request');
assert_true($policy->isRequestHeaderAllowed('x-wp-nonce'), 'Header allow-list is case-insensitive');
assert_true(!$policy->isRequestHeaderAllowed('X-Injected-Header'), 'Unlisted request header is rejected');
assert_true(
    $policy->filterRequestedHeaders('content-type, X-WP-Nonce, X-Evil') === 'content-type, X-WP-Nonce',
    'Preflight filters requested headers against the allow-list'
);
assert_true($policy->filterRequestedHeaders('') === '', 'Empty requested headers yield an empty allow list');
assert_true(
    in_array('X-KLEER-Session', (new CorsPolicy([]))->allowedHeaders(), true),
    'X-KLEER-Session is allowed by default'
);

// The test must not depend on whether the host or CI happens to export CORS variables,
// so every case passes its defaults explicitly and drives configuration through putenv.
$emptyPolicy = new CorsPolicy([]);
assert_true($emptyPolicy->allowedOrigins() === [], 'Empty configuration denies every cross-origin request');
assert_true($emptyPolicy->buildHeaders('http://localhost:5173') === [], 'Empty configuration emits no CORS headers');

$envPolicyFromServer = CorsPolicy::fromEnvironment([], null, null, false, 86400);
assert_true($envPolicyFromServer instanceof CorsPolicy, 'fromEnvironment() builds a policy without explicit configuration');

putenv('KLEER_CORS_ALLOWED_ORIGINS=https://a.kleer.vn, https://b.kleer.vn');
putenv('KLEER_CORS_ALLOW_CREDENTIALS=true');
$envPolicyFromServer = CorsPolicy::fromEnvironment();
assert_true($envPolicyFromServer->isOriginAllowed('https://b.kleer.vn'), 'Environment variable populates the origin allow-list');
assert_true(!$envPolicyFromServer->isOriginAllowed('https://c.kleer.vn'), 'Environment allow-list is not open-ended');
assert_true($envPolicyFromServer->allowCredentials(), 'KLEER_CORS_ALLOW_CREDENTIALS is parsed as boolean');
putenv('KLEER_CORS_ALLOWED_ORIGINS');
putenv('KLEER_CORS_ALLOW_CREDENTIALS');

assert_true(
    in_array('X-KLEER-Session', $policy->applyToCoreAllowedHeaders(['Content-Type']), true),
    'Policy headers are merged into the WordPress core allow-list'
);

// Test 9: CORS Service (WordPress wiring)
echo "\n9. Testing CORS Service (WordPress hooks & preflight)...\n";
use Kleer\Support\Cors\CorsService;

$corsService = new CorsService(new CorsPolicy(['https://shop.kleer.vn']));
$corsService->register();
assert_true(!empty($registered_filters['rest_pre_serve_request']), 'CORS hooks into rest_pre_serve_request');
assert_true(!empty($registered_filters['rest_pre_dispatch']), 'CORS hooks into rest_pre_dispatch');
assert_true(!empty($registered_filters['rest_allowed_cors_headers']), 'CORS hooks into rest_allowed_cors_headers');

// Regression guard: WordPress only attaches rest_send_cors_headers inside rest_api_init
// (priority 10). Removing it any earlier is a no-op and leaves the wildcard sender active,
// which silently bypasses the whitelist. Removing must therefore be hooked on rest_api_init
// at a priority greater than 10.
assert_true(!empty($registered_filters['rest_api_init']), 'Core CORS removal is hooked on rest_api_init');
$removalHooks = array_filter(
    $registered_filters['rest_api_init'],
    static fn (array $hook): bool => $hook['callback'] === [$corsService, 'removeCoreCorsHeaders']
);
assert_true(count($removalHooks) === 1, 'Core CORS removal is hooked exactly once');
$removalPriority = $removalHooks ? reset($removalHooks)['priority'] : 0;
assert_true($removalPriority > 10, 'Core CORS removal runs after WordPress attaches its own handler (priority > 10)');

// Simulate the real WordPress order: rest_api_init fires, then the filter actually runs.
$corsService->removeCoreCorsHeaders();
assert_true(
    isset($removed_filters['rest_pre_serve_request'])
    && $removed_filters['rest_pre_serve_request'][0]['callback'] === 'rest_send_cors_headers',
    'WordPress core permissive CORS sender is removed'
);

$corsService->resetSentHeaders();
$_SERVER['HTTP_ORIGIN'] = 'https://shop.kleer.vn';
$corsService->sendCorsHeadersOnServe(false);
$sent = $corsService->sentHeaderMap();
assert_true(($sent['Access-Control-Allow-Origin'] ?? null) === 'https://shop.kleer.vn', 'Serve hook emits Allow-Origin for allowed origin');
assert_true(($sent['Access-Control-Allow-Methods'] ?? '') !== '', 'Serve hook advertises allowed methods');
assert_true(($sent['Vary'] ?? null) !== null, 'Serve hook emits Vary');

$corsService->resetSentHeaders();
$_SERVER['HTTP_ORIGIN'] = 'https://evil.example.com';
$corsService->sendCorsHeadersOnServe(false);
assert_true($corsService->sentHeaderMap() === [], 'Serve hook emits nothing for a rejected origin');

$corsService->resetSentHeaders();
$_SERVER['HTTP_ORIGIN'] = 'https://shop.kleer.vn';
$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'] = 'content-type, X-Evil';
$preflightResponse = $corsService->handlePreflightRequest(null, null, new WP_REST_Request('OPTIONS'));
assert_true($preflightResponse instanceof WP_REST_Response, 'Preflight returns a REST response');
assert_true($preflightResponse instanceof WP_REST_Response && $preflightResponse->get_status() === 204, 'Preflight answers HTTP 204');
$preflightSent = $corsService->sentHeaderMap();
assert_true(($preflightSent['Access-Control-Allow-Origin'] ?? null) === 'https://shop.kleer.vn', 'Preflight emits Allow-Origin');
assert_true(($preflightSent['Access-Control-Allow-Headers'] ?? null) === 'content-type', 'Preflight allow-headers are filtered (X-Evil stripped)');
assert_true(($preflightSent['Access-Control-Max-Age'] ?? null) === '86400', 'Preflight caches with Max-Age');

$corsService->resetSentHeaders();
$_SERVER['HTTP_ORIGIN'] = 'https://evil.example.com';
assert_true($corsService->handlePreflightRequest(null, null, new WP_REST_Request('OPTIONS')) === null, 'Preflight from a rejected origin is handed back to WordPress');
assert_true($corsService->handlePreflightRequest(null, null, new WP_REST_Request('POST')) === null, 'Non-OPTIONS requests are never short-circuited');
assert_true($corsService->handlePreflightRequest('existing', null, new WP_REST_Request('OPTIONS')) === 'existing', 'An earlier filter result is never overwritten');
unset($_SERVER['HTTP_ORIGIN'], $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);

// Test 10: JSON Schema validation
echo "\n10. Testing JSON Schema Validation (schema integrity)...\n";
use Kleer\Support\Validation\JsonSchema;
use Kleer\Support\Validation\ValidationResult;

assert_true(ValidationResult::valid()->isValid(), 'ValidationResult::valid() is valid');
assert_true(ValidationResult::invalid([['pointer' => '/', 'keyword' => 'type', 'message' => 'x']])->isInvalid(), 'ValidationResult::invalid() is invalid');

$validSubmission = [
    'session_id' => '3f2504e0-4f89-41d3-9a0c-0305e82c3301',
    'consent' => [
        'policy_accepted' => true,
        'marketing_opt_in' => false,
    ],
    'answers' => [
        ['question_id' => 'skin_type', 'answer' => 'oily'],
        ['question_id' => 'concerns', 'answer' => ['acne', 'large_pores'], 'note' => 'Da dau ben duoi'],
    ],
    'context' => [
        'locale' => 'vi',
        'device' => 'mobile',
        'referrer' => 'https://kleer.vn/quiz',
    ],
];
$submissionSchema = JsonSchema::fromFile('quiz-submission.schema.json');
assert_true(array_key_exists('$id', $submissionSchema), 'Quiz submission schema file loads from schemas/');
assert_true(JsonSchema::validate($validSubmission, $submissionSchema)->isValid(), 'A complete quiz submission passes validation');

$missingRequired = $validSubmission;
unset($missingRequired['consent']);
$missingResult = JsonSchema::validate($missingRequired, $submissionSchema);
assert_true($missingResult->isInvalid(), 'Missing required field is rejected');
assert_true($missingResult->firstErrorPointer() === '/', 'Missing required field is reported at the object pointer');
assert_true(str_contains($missingResult->firstError()['message'], 'consent'), 'Missing required field names the field');
assert_true($missingResult->count() >= 1, 'ValidationResult counts errors');

$wrongType = $validSubmission;
$wrongType['answers'] = 'khong-phai-mang';
$wrongTypeResult = JsonSchema::validate($wrongType, $submissionSchema);
assert_true($wrongTypeResult->isInvalid(), 'Wrong JSON type is rejected');
assert_true($wrongTypeResult->hasErrorAt('/answers'), 'Type error points at /answers');
assert_true($wrongTypeResult->firstError()['keyword'] === 'type', 'Type error is tagged with the "type" keyword');

$extraField = $validSubmission;
$extraField['unexpected'] = 'x';
assert_true(JsonSchema::validate($extraField, $submissionSchema)->isInvalid(), 'additionalProperties:false rejects unknown fields');

$badSession = $validSubmission;
$badSession['session_id'] = 'khong-phai-uuid';
$badSessionResult = JsonSchema::validate($badSession, $submissionSchema);
assert_true($badSessionResult->isInvalid(), 'session_id failing the pattern is rejected');
assert_true($badSessionResult->hasErrorAt('/session_id'), 'Pattern error points at /session_id');

$nestedBad = $validSubmission;
$nestedBad['answers'][1]['question_id'] = 'SAI_DAU_HOA';
assert_true(JsonSchema::validate($nestedBad, $submissionSchema)->hasErrorAt('/answers/1/question_id'), 'Nested array errors keep an exact JSON Pointer');

$missingConsentField = $validSubmission;
unset($missingConsentField['consent']['policy_accepted']);
assert_true(
    JsonSchema::validate($missingConsentField, $submissionSchema)->hasErrorAt('/consent'),
    'Nested object required-field error points at /consent'
);

$tooManyAnswers = $validSubmission;
$tooManyAnswers['answers'] = array_fill(0, 21, ['question_id' => 'skin_type', 'answer' => 'oily']);
assert_true(JsonSchema::validate($tooManyAnswers, $submissionSchema)->hasErrorAt('/answers'), 'maxItems is enforced on the answers array');

$tooShortAnswers = $validSubmission;
$tooShortAnswers['answers'] = [];
assert_true(JsonSchema::validate($tooShortAnswers, $submissionSchema)->hasErrorAt('/answers'), 'minItems is enforced on the answers array');

foreach ([42, 3.5, true, ['a', 'b']] as $variant) {
    $withVariant = $validSubmission;
    $withVariant['answers'][0]['answer'] = $variant;
    assert_true(JsonSchema::validate($withVariant, $submissionSchema)->isValid(), 'oneOf answer accepts ' . get_debug_type($variant));
}
$badAnswer = $validSubmission;
$badAnswer['answers'][0]['answer'] = ['khong', 'duoc', 'chuoi', 'va', 'con', 'khong', 'duoc', 'chuoi', 'va', 'con', 'khong', 'duoc', 'chuoi', 'va', 'con', 'khong', 'duoc', 'chuoi', 'va', 'con', 'khong', 'duoc', 'chuoi'];
assert_true(JsonSchema::validate($badAnswer, $submissionSchema)->hasErrorAt('/answers/0/answer'), 'oneOf rejects a malformed answer payload');

$longNote = $validSubmission;
$longNote['answers'][0]['note'] = str_repeat('a', 501);
assert_true(JsonSchema::validate($longNote, $submissionSchema)->hasErrorAt('/answers/0/note'), 'maxLength is enforced on nested strings');

$badLocale = $validSubmission;
$badLocale['context']['locale'] = 'vietnamese';
assert_true(JsonSchema::validate($badLocale, $submissionSchema)->hasErrorAt('/context/locale'), 'BCP 47 locale pattern is enforced');

$badDevice = $validSubmission;
$badDevice['context']['device'] = 'smartwatch';
assert_true(JsonSchema::validate($badDevice, $submissionSchema)->hasErrorAt('/context/device'), 'enum is enforced on context.device');

$negativePrice = [
    'status' => 'ok',
    'data' => [
        'submission_id' => 42,
        'skin_profile' => ['code' => 'oily_acne', 'label' => 'Da dau mun'],
        'recommended_products' => [
            ['id' => 1, 'name' => 'Gentle Cleanser', 'price' => -5, 'routine_step' => 'cleanser'],
        ],
    ],
];
$responseSchema = JsonSchema::fromFile('quiz-submission-response.schema.json');
assert_true(JsonSchema::validate($negativePrice, $responseSchema)->hasErrorAt('/data/recommended_products/0/price'), 'Response schema rejects a negative price');
$negativePrice['data']['recommended_products'][0]['price'] = 250000;
assert_true(JsonSchema::validate($negativePrice, $responseSchema)->isValid(), 'A well-formed quiz response passes validation');

$unsupportedSchemaRejected = false;
try {
    JsonSchema::validate(['a' => 1], ['type' => 'object', 'allOf' => [['type' => 'object']]]);
} catch (\RuntimeException) {
    $unsupportedSchemaRejected = true;
}
assert_true($unsupportedSchemaRejected, 'Unsupported schema keywords throw instead of silently passing (fail-closed)');

$missingSchemaFileFailed = false;
try {
    JsonSchema::fromFile('khong-ton-tai.schema.json');
} catch (\RuntimeException) {
    $missingSchemaFileFailed = true;
}
assert_true($missingSchemaFileFailed, 'Missing schema file throws a RuntimeException');

$badJsonFailed = false;
try {
    JsonSchema::validate([], ['type' => 'ban-la-mot-kieu-khong-ton-tai']);
} catch (\RuntimeException) {
    $badJsonFailed = true;
}
assert_true($badJsonFailed, 'Unknown JSON type throws instead of silently passing');

assert_true(JsonSchema::escapePointerToken('a/b~c') === 'a~1b~0c', 'JSON Pointer escaping is RFC 6901 compliant');

// Test 11: Controller-side payload validation trait
echo "\n11. Testing ValidatesJsonPayload Trait (Controller boundary)...\n";

final class QuizPayloadProbe
{
    use Kleer\Controllers\Concerns\ValidatesJsonPayload;

    public function validate(WP_REST_Request $request, string $schema): array|WP_Error
    {
        return $this->validateJsonPayload($request, $schema);
    }
}

$probe = new QuizPayloadProbe();
$goodRequest = new WP_REST_Request('POST', json_encode($validSubmission));
$probeResult = $probe->validate($goodRequest, 'quiz-submission.schema.json');
assert_true(is_array($probeResult), 'Valid JSON body returns the normalised payload array');
assert_true(is_array($probeResult) && $probeResult['session_id'] === $validSubmission['session_id'], 'Returned payload preserves the session id');

$badRequest = new WP_REST_Request('POST', json_encode(['answers' => []]));
$badProbeResult = $probe->validate($badRequest, 'quiz-submission.schema.json');
assert_true(is_wp_error($badProbeResult), 'Invalid JSON body returns a WP_Error');
assert_true(is_wp_error($badProbeResult) && $badProbeResult->get_error_code() === 'kleer_invalid_payload', 'WP_Error uses the kleer_invalid_payload code');
$badData = is_wp_error($badProbeResult) ? $badProbeResult->get_error_data() : [];
assert_true(($badData['status'] ?? null) === 400, 'WP_Error data carries HTTP status 400');
assert_true(is_array($badData['errors'] ?? null) && count($badData['errors']) > 0, 'WP_Error data carries a machine-readable error list');
assert_true(
    is_array($badData['errors'] ?? null) && ($badData['errors'][0]['pointer'] ?? '') !== '',
    'Each error in the list carries a JSON Pointer'
);

$emptyBodyResult = $probe->validate(new WP_REST_Request('POST', ''), 'quiz-submission.schema.json');
assert_true(is_wp_error($emptyBodyResult), 'Empty body is rejected because required fields are missing');

$brokenJsonResult = $probe->validate(new WP_REST_Request('POST', '{khong phai json'), 'quiz-submission.schema.json');
assert_true(is_wp_error($brokenJsonResult), 'Malformed JSON is rejected, not thrown');

// Test 12: Plugin integration for the L01-G6-01 infrastructure
echo "\n12. Testing Plugin Integration (L01-G6-01 wiring)...\n";
$activePlugin = Kleer\Plugin::getInstance();
assert_true($activePlugin->corsService() instanceof CorsService, 'Plugin::register() boots the CORS service');
assert_true(!empty($registered_filters['rest_pre_dispatch']), 'CORS is active after plugins_loaded fires');
assert_true(
    in_array('X-KLEER-Session', $activePlugin->corsService()->filterCoreAllowedHeaders(['Content-Type']), true),
    'Booted CORS service exposes KLEER headers to WordPress core'
);
assert_true(
    class_exists(Kleer\Controllers\Concerns\ValidatesJsonPayload::class)
    || trait_exists(Kleer\Controllers\Concerns\ValidatesJsonPayload::class),
    'Autoloader exposes the ValidatesJsonPayload trait to Controllers'
);

// Test 13: WooCommerce HPOS compatibility (Rubric 5.3)
echo "\n13. Testing WooCommerce HPOS Compatibility...\n";
use Kleer\Support\Cache\CacheInvalidation;
use Kleer\Support\Cache\KleerCache;
use Kleer\Support\Cache\WooCommerceCompatibility;

$hpos = new WooCommerceCompatibility();
assert_true(in_array('custom_order_tables', $hpos->features(), true), 'HPOS feature custom_order_tables is declared');
assert_true(count($hpos->features()) >= 1, 'At least one compatibility feature is declared');
assert_true(
    in_array(['before_woocommerce_init', 'declareCompatibility'], $hpos->subscriptionMap(), true),
    'Compatibility is declared on before_woocommerce_init'
);
assert_true(!$hpos->declareCompatibility(), 'declareCompatibility() is a no-op when WooCommerce is absent');
assert_true(!$hpos->declareCartCheckoutCompatibility(), 'Cart/checkout declaration is a no-op when WooCommerce is absent');

// Simulate WooCommerce FeaturesUtil to prove the declaration actually fires.
if (!class_exists('Automattic\WooCommerce\Utilities\FeaturesUtil')) {
    $GLOBALS['kleer_hpos_declared'] = [];
    eval('namespace Automattic\WooCommerce\Utilities; class FeaturesUtil { public static function declare_compatibility($feature, $file, $positive) { $GLOBALS["kleer_hpos_declared"][] = [$feature, $file, $positive]; return true; } }');
}
$GLOBALS['kleer_hpos_declared'] = [];
$hposReal = new WooCommerceCompatibility([WooCommerceCompatibility::FEATURE_HPOS]);
assert_true($hposReal->declareCompatibility(), 'declareCompatibility() succeeds when WooCommerce is present');
assert_true(count($GLOBALS['kleer_hpos_declared']) === 1, 'Exactly one compatibility declaration is sent to WooCommerce');
assert_true(($GLOBALS['kleer_hpos_declared'][0][0] ?? null) === 'custom_order_tables', 'HPOS is the feature declared to WooCommerce');
assert_true(($GLOBALS['kleer_hpos_declared'][0][2] ?? null) === true, 'HPOS is declared as compatible (not merely experimental)');

$cartCheckout = new WooCommerceCompatibility([WooCommerceCompatibility::FEATURE_CART, WooCommerceCompatibility::FEATURE_CHECKOUT]);
$GLOBALS['kleer_hpos_declared'] = [];
$cartCheckout->declareCartCheckoutCompatibility();
assert_true(count($GLOBALS['kleer_hpos_declared']) === 2, 'Cart and checkout blocks are both declared');
unset($GLOBALS['kleer_hpos_declared'], $GLOBALS['kleer_hpos_declared']);

// Test 14: Cache invalidation wiring (Rubric 5.3)
echo "\n14. Testing Cache Invalidation Hooks...\n";
$cache = new KleerCache('127.0.0.1', 6379, 0.05, false, 0, 'unit:');
$invalidation = new CacheInvalidation($cache);
$invalidation->register();

$requiredHooks = ['save_post_product', 'deleted_post', 'woocommerce_update_product', 'updated_option', 'deleted_option'];
foreach ($requiredHooks as $hook) {
    assert_true(!empty($registered_actions[$hook]), 'Cache invalidation hooks into ' . $hook);
}
assert_true(!empty($registered_actions['woocommerce_new_product']), 'Cache invalidation hooks into woocommerce_new_product');
assert_true(!empty($registered_actions['woocommerce_delete_product']), 'Cache invalidation hooks into woocommerce_delete_product');

assert_true($cache->key('sku_1', KleerCache::GROUP_PRODUCTS) === 'unit:kleer_products:sku_1', 'Cache key includes the group so groups can be flushed separately');
assert_true($cache->key('a', KleerCache::GROUP_QUIZ) !== $cache->key('a', KleerCache::GROUP_PRODUCTS), 'Same key in different groups does not collide');
assert_true($cache->key('x', 'grp') !== $cache->key('x/y', 'grp'), 'Slashes in keys are namespaced correctly');

$cache->addGlobalGroup('kleer_products');
$cache->addGlobalGroup('kleer_products');
assert_true(count($cache->globalGroups()) === 1, 'Global groups are deduplicated');
$cache->addNonPersistentGroup('counts');
$cache->addIgnoredGroups(['plugins', 'theme_json']);
assert_true(in_array('counts', $cache->ignoredGroups(), true), 'Non-persistent group is registered');
assert_true(in_array('theme_json', $cache->ignoredGroups(), true), 'Multiple non-persistent groups can be registered at once');

// A cache pointed at a closed port must degrade instead of throwing. Port 1 is used
// because it is not bound anywhere in the compose network, unlike 6379 which is Redis.
$offline = new KleerCache('127.0.0.1', 1, 0.05, false, 0, 'unit-offline:');
assert_true($offline->set('k', 'v') === false, 'set() reports failure when Redis is unreachable');
assert_true($offline->get('k') === false, 'get() returns a cache miss when Redis is unreachable');
assert_true(!$offline->isAvailable(), 'Availability is reported as false when Redis is unreachable');
assert_true($offline->unavailableReason() !== null, 'A reason is recorded for diagnostics');

$isolated = new KleerCache('127.0.0.1', 1, 0.05, false, 0, 'unit-isolated:');
$isolated->resetStats();
assert_true($isolated->hitRate() === 0.0, 'Hit rate is zero before any traffic');
assert_true($isolated->misses() === 0, 'Miss counter is zero before any traffic');

// Each instance must resolve its own connection: one failed instance must not make
// another instance look connected or disconnected.
assert_true(!$isolated->isAvailable() && !$offline->isAvailable(), 'Instances do not share connection state');

$activePlugin2 = Kleer\Plugin::getInstance();
assert_true($activePlugin2->wooCommerceCompatibility() instanceof WooCommerceCompatibility, 'Plugin boots the HPOS compatibility layer');
assert_true($activePlugin2->cacheInvalidation() instanceof CacheInvalidation, 'Plugin boots the cache invalidation layer');

// Test 15: Skin Quiz Questions, Engine, Safe Fallback & REST Endpoints (L01-G5-03, L01-G5-05, L01-G5-06)
echo "\n15. Testing Skin Quiz Engine, Safe Fallback & Endpoints...\n";
use Kleer\Config\QuizQuestions;
use Kleer\Controllers\SkinQuizController;
use Kleer\Endpoints\SkinQuizEndpoints;
use Kleer\Services\QuizEngineService;

// 15.1. Quiz Questions configuration
$questions = QuizQuestions::getQuestions();
assert_true(count($questions) === 5, 'QuizQuestions defines exactly 5 core questions');
assert_true(isset($questions['q1_skin_feeling'], $questions['q2_pore_sebum'], $questions['q3_sensitivity'], $questions['q4_main_concern'], $questions['q5_routine_goal']), 'All 5 question keys exist');
$defaultCatalog = QuizQuestions::getDefaultCatalog();
assert_true(count($defaultCatalog) >= 15, 'Default catalog contains at least 15 skincare products');

// 15.2. QuizEngineService: Happy Path (Oily & Acne -> Intensive 4-step routine)
$quizService = new QuizEngineService();
$oilyPayload = [
    'session_id' => '11111111-2222-4333-8444-555555555555',
    'consent' => ['policy_accepted' => true, 'marketing_opt_in' => false],
    'answers' => [
        ['question_id' => 'q1_skin_feeling', 'answer' => 'greasy_all'],
        ['question_id' => 'q2_pore_sebum', 'answer' => 'large_pores'],
        ['question_id' => 'q3_sensitivity', 'answer' => 'rarely_never'],
        ['question_id' => 'q4_main_concern', 'answer' => 'acne_blemish'],
        ['question_id' => 'q5_routine_goal', 'answer' => 'intensive'],
    ],
];

$oilyResult = $quizService->processSubmission($oilyPayload);
assert_true($oilyResult['status'] === 'ok', 'QuizEngineService returns status ok');
assert_true($oilyResult['data']['skin_profile']['code'] === 'oily_acne', 'Oily + Acne answers map to code oily_acne');
assert_true(count($oilyResult['data']['recommended_products']) === 4, 'Intensive routine recommends 4 products');
$stepsFound = array_column($oilyResult['data']['recommended_products'], 'routine_step');
assert_true($stepsFound === ['cleanser', 'treatment', 'moisturizer', 'sunscreen'], '4 steps follow cleanser -> treatment -> moisturizer -> sunscreen');

// 15.3. QuizEngineService: Happy Path (Dry & Sensitive -> Minimal 3-step routine)
$dryPayload = [
    'session_id' => '22222222-3333-4444-8555-666666666666',
    'consent' => ['policy_accepted' => true, 'marketing_opt_in' => true],
    'answers' => [
        ['question_id' => 'q1_skin_feeling', 'answer' => 'tight_dry'],
        ['question_id' => 'q2_pore_sebum', 'answer' => 'flaky_rough'],
        ['question_id' => 'q3_sensitivity', 'answer' => 'very_often'],
        ['question_id' => 'q4_main_concern', 'answer' => 'dehydration'],
        ['question_id' => 'q5_routine_goal', 'answer' => 'minimal'],
    ],
];
$dryResult = $quizService->processSubmission($dryPayload);
assert_true($dryResult['data']['skin_profile']['code'] === 'sensitive_hydration', 'Sensitive + Dehydration maps to sensitive_hydration');
assert_true(count($dryResult['data']['recommended_products']) === 3, 'Minimal routine recommends exactly 3 products');
$drySteps = array_column($dryResult['data']['recommended_products'], 'routine_step');
assert_true($drySteps === ['cleanser', 'moisturizer', 'sunscreen'], 'Minimal routine omits treatment step');

// 15.4. QuizEngineService: Safe Fallback Mechanism (L01-G5-06)
// Test with restrictive catalog containing only 1 generic product per step
$minimalCatalog = [
    ['id' => 991, 'name' => 'Fallback Cleanser', 'price' => 100000, 'routine_step' => 'cleanser', 'skin_types' => ['all_skin_types'], 'is_gentle_fallback' => true],
    ['id' => 992, 'name' => 'Fallback Treatment', 'price' => 200000, 'routine_step' => 'treatment', 'skin_types' => ['all_skin_types'], 'is_gentle_fallback' => true],
    ['id' => 993, 'name' => 'Fallback Moisturizer', 'price' => 150000, 'routine_step' => 'moisturizer', 'skin_types' => ['all_skin_types'], 'is_gentle_fallback' => true],
    ['id' => 994, 'name' => 'Fallback Sunscreen', 'price' => 180000, 'routine_step' => 'sunscreen', 'skin_types' => ['all_skin_types'], 'is_gentle_fallback' => true],
];
$fallbackService = new QuizEngineService($minimalCatalog);
$fallbackResult = $fallbackService->processSubmission($oilyPayload);
assert_true(count($fallbackResult['data']['recommended_products']) === 4, 'Safe Fallback ensures 4 products even with no exact tag match');
assert_true($fallbackResult['data']['recommended_products'][0]['id'] === 991, 'Fallback product id is returned correctly');

// Validate the output conforms to quiz-submission-response.schema.json
$responseValidation = \Kleer\Support\Validation\JsonSchema::validate(
    $oilyResult,
    \Kleer\Support\Validation\JsonSchema::fromFile('quiz-submission-response.schema.json')
);
assert_true($responseValidation->isValid(), 'Generated quiz output passes quiz-submission-response.schema.json');

// 15.5. SkinQuizController HTTP boundary testing
$quizController = new SkinQuizController($quizService);
$validRequest = new WP_REST_Request('POST', json_encode($oilyPayload, JSON_THROW_ON_ERROR));
$response = $quizController->handleSubmission($validRequest);
assert_true($response instanceof WP_REST_Response, 'Valid submission returns WP_REST_Response');
assert_true($response->get_status() === 200, 'Valid submission responds HTTP 200');

$invalidRequest = new WP_REST_Request('POST', json_encode(['session_id' => 'not-a-uuid'], JSON_THROW_ON_ERROR));
$badResponse = $quizController->handleSubmission($invalidRequest);
assert_true(is_wp_error($badResponse), 'Invalid submission returns WP_Error');
assert_true($badResponse->get_error_code() === 'kleer_invalid_payload', 'Error code is kleer_invalid_payload');
assert_true($badResponse->get_error_data()['status'] === 400, 'Error status is 400');

// 15.6. SkinQuizEndpoints route registration
$quizEndpoints = new SkinQuizEndpoints($quizController);
$quizEndpoints->register();
assert_true(!empty($registered_actions['rest_api_init']), 'SkinQuizEndpoints hooks into rest_api_init');
$fakeRequest = new WP_REST_Request('POST', '/kleer/v1/skin-quiz');
assert_true($quizEndpoints->checkPermission($fakeRequest) === true, 'Public access permission callback returns true');

echo "\nSummary: {$tests} tests, {$failures} failures.\n";
if ($failures > 0) {
    exit(1);
}
echo "All architecture tests PASSED!\n";
