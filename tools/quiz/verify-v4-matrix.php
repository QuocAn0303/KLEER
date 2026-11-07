<?php

declare(strict_types=1);

/**
 * Isolated verification against a frozen Google Sheets v4 source, not production config.
 * No WordPress bootstrap, database, HTTP, plugin hooks, or file writes.
 * Run: php tools/quiz/verify-v4-matrix.php
 * Optional first argument: another snapshot with the same raw-sheet layout.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

$root = dirname(__DIR__, 2);
define('ABSPATH', $root . DIRECTORY_SEPARATOR);
define('KLEER_PLUGIN_DIR', $root . '/wp-content/plugins/kleer-plugin/');
spl_autoload_register(static function (string $class) use ($root): void {
    if (str_starts_with($class, 'Kleer\\')) {
        $file = $root . '/wp-content/plugins/kleer-plugin/src/'
            . str_replace('\\', '/', substr($class, 6)) . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
});
set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

use Kleer\Config\QuizQuestions;
use Kleer\Services\QuizEngineService;
use Kleer\Support\Validation\JsonSchema;

$fixturePath = $argv[1] ?? __DIR__ . '/fixtures/nhan-v4-source-2026-10-31.json';
$source = json_decode(file_get_contents($fixturePath), true, 512, JSON_THROW_ON_ERROR);
$skinTypes = ['sensitive', 'dry', 'combination', 'oily', 'normal'];
$skinNames = array_combine(['Nhạy cảm', 'Khô', 'Hỗn hợp', 'Dầu', 'Thường'], $skinTypes);
$concernNames = ['Mụn' => 'acne', 'Thâm/nám' => 'dark_spots', 'Lão hóa' => 'aging', 'Lỗ chân lông' => 'pores', 'Không' => 'none'];
$stepNames = ['Tẩy trang' => 'makeup_remover', 'Sữa rửa mặt' => 'cleanser', 'Toner' => 'toner', 'Serum' => 'serum', 'Dưỡng ẩm' => 'moisturizer', 'Chống nắng' => 'sunscreen'];
$displaySteps = array_values($stepNames);
$failures = [];
$checks = 0;
$failureCount = 0;
$check = static function (bool $ok, string $context) use (&$checks, &$failureCount, &$failures): void {
    $checks++;
    if (!$ok) {
        $failureCount++;
        if (count($failures) < 20) {
            $failures[] = $context;
        }
    }
};

// Source values are parsed from the captured sheet, never from QuizQuestions.
$matrix = [];
$currentQuestion = '';
foreach (array_slice($source['sheets']['Ma trận điểm']['values'], 4) as $row) {
    if (!empty($row[0])) {
        $currentQuestion = strtolower($row[0]);
    }
    $option = $row[2];
    $matrix[$currentQuestion][$option] = [
        'scores' => array_combine($skinTypes, array_map('intval', array_slice($row, 4, 5))),
        'concern' => $concernNames[$row[9] ?? 'Không'] ?? 'none',
        'count' => (int) ($row[10] ?? 0),
        'sun' => ($row[11] ?? '') === 'Có',
    ];
}
$ruleRows = $source['sheets']['Quy tắc']['values'];
$threshold = (int) $ruleRows[3][1];
$concernWeight = (int) $ruleRows[4][1];
$skinWeight = (int) $ruleRows[5][1];
$minimumProducts = (int) $ruleRows[6][1];
$boosts = [];
$keepSun = [];
foreach (array_slice($ruleRows, 24, 5) as $row) {
    $concern = $concernNames[$row[0]];
    $boosts[$concern] = [$stepNames[$row[1]] ?? null, $stepNames[$row[2]] ?? null];
    if ($row[3] === 'Có') {
        $keepSun[] = $concern;
    }
}

$catalog = [];
foreach (array_slice($source['sheets']['SKU mẫu']['values'], 4) as $row) {
    $types = [];
    foreach ($skinTypes as $i => $type) {
        if ((int) $row[4 + $i] === 1) {
            $types[] = $type;
        }
    }
    $concerns = [];
    foreach (['acne', 'dark_spots', 'aging', 'pores'] as $i => $concern) {
        if ((int) $row[9 + $i] === 1) {
            $concerns[] = $concern;
        }
    }
    $catalog[] = [
        'id' => (int) $row[0],
        'name' => $row[1],
        'routine_step' => $stepNames[$row[3]],
        'skin_types' => count($types) === 5 ? [...$types, 'all_skin_types'] : $types,
        'concerns' => $concerns,
        'has_strong_actives' => $row[13] === 'Có',
        'price' => (int) preg_replace('/\D/', '', $row[14]),
    ];
}

$config = QuizQuestions::getV4Questions();
$check(count($matrix) === 6 && array_sum(array_map('count', $matrix)) === 27, 'Snapshot must have 6 questions and 27 options.');
$check(array_keys($config) === array_keys($matrix), 'Production question IDs must match source.');
foreach ($matrix as $question => $options) {
    $check(array_keys($config[$question]['options']) === array_keys($options), "$question option IDs");
    foreach ($options as $option => $expected) {
        $actual = $config[$question]['options'][$option];
        $scores = array_replace(array_fill_keys($skinTypes, 0), $actual['scores'] ?? []);
        $check($scores === $expected['scores'], "$question/$option score vector");
        if ($question === 'q4') {
            $check($actual['concern'] === $expected['concern'], "$option concern");
        } elseif ($question === 'q5') {
            $check($actual['routine_steps'] === $expected['count'], "$option requested count");
        } elseif ($question === 'q6') {
            $check($actual['uses_sunscreen_makeup'] === $expected['sun'], "$option sun/makeup flag");
        }
    }
}

// An independent model: ordinal priority lists instead of the service's numeric ranks.
$reference = static function (array $options, array $inventory) use ($matrix, $skinTypes, $threshold, $boosts, $keepSun, $displaySteps, $concernWeight, $skinWeight): array {
    $scores = array_fill_keys($skinTypes, 0);
    foreach ($options as $i => $option) {
        foreach ($matrix['q' . ($i + 1)][$option]['scores'] as $type => $points) {
            $scores[$type] += $points;
        }
    }
    $type = $skinTypes[array_search(max($scores), array_values($scores), true)];
    $sensitive = $scores['sensitive'] >= $threshold;
    $concern = $matrix['q4'][$options[3]]['concern'];
    $count = $matrix['q5'][$options[4]]['count'];
    $sun = $matrix['q6'][$options[5]]['sun'];
    $priority = ['cleanser'];
    if ($sun || in_array($concern, $keepSun, true)) {
        $priority = [...$priority, 'sunscreen', 'makeup_remover'];
    }
    $boost = $boosts[$concern][(int) $sensitive];
    if ($boost !== null) {
        $priority[] = $boost;
    }
    $priority = array_values(array_unique([...$priority, 'serum', 'toner', 'moisturizer']));
    $chosen = array_slice($priority, 0, $count);
    $steps = array_values(array_intersect($displaySteps, $chosen));
    $ids = [];
    $missing = [];
    foreach ($steps as $step) {
        $best = null;
        $bestKey = null;
        foreach ($inventory as $product) {
            // Explicit model assumption A-01 from QA: eligibility uses skin tags first.
            if ($product['routine_step'] !== $step || !in_array($type, $product['skin_types'], true)
                || ($sensitive && $product['has_strong_actives']) || in_array($product['id'], $ids, true)) {
                continue;
            }
            $specificType = count(array_intersect($skinTypes, $product['skin_types'])) < 5;
            $score = (in_array($concern, $product['concerns'], true) ? $concernWeight : 0)
                + ($specificType ? $skinWeight : 0);
            // Lower ID is an implementation tie-break when both score and price are equal.
            $key = [-$score, $product['price'], $product['id']];
            if ($bestKey === null || $key < $bestKey) {
                $best = $product['id'];
                $bestKey = $key;
            }
        }
        if ($best === null) {
            $missing[] = $step;
        } else {
            $ids[] = $best;
        }
    }
    return compact('scores', 'type', 'sensitive', 'concern', 'count', 'sun', 'steps', 'ids', 'missing');
};

$engine = new QuizEngineService($catalog);
$requestSchema = JsonSchema::fromFile('quiz-submission.schema.json');
$responseSchema = JsonSchema::fromFile('quiz-submission-response.schema.json');
$payloadFor = static fn(array $options): array => [
    'session_id' => '11111111-2222-4333-8444-555555555555',
    'consent' => ['policy_accepted' => true, 'marketing_opt_in' => false],
    'answers' => array_map(static fn(int $i, string $option): array => ['question_id' => 'q' . ($i + 1), 'answer' => $option], array_keys($options), $options),
];

$combinations = [[]];
foreach ($matrix as $options) {
    $next = [];
    foreach ($combinations as $combination) {
        foreach (array_keys($options) as $option) {
            $next[] = [...$combination, $option];
        }
    }
    $combinations = $next;
}
$combinationsPassed = 0;
$routinesWithMissingSteps = 0;
$routinesBelowTwo = 0;
foreach ($combinations as $options) {
    $before = $failureCount;
    $expected = $reference($options, $catalog);
    $payload = $payloadFor($options);
    $profile = $engine->calculateSkinProfileV4($payload['answers']);
    $result = $engine->processSubmission($payload, 'v4');
    $data = $result['data'];
    $context = implode('/', $options);
    $check($profile['scores'] === $expected['scores'], "$context scores");
    $check($profile['skin_type'] === $expected['type'] && $profile['is_sensitive'] === $expected['sensitive'], "$context profile/tie/sensitivity");
    $check($profile['primary_concern'] === $expected['concern'] && $profile['routine_steps_requested'] === $expected['count'] && $profile['uses_sunscreen_makeup'] === $expected['sun'], "$context Q4/Q5/Q6");
    $check($data['routine_steps_selected'] === $expected['steps'], "$context step priority/display order");
    $check(array_column($data['recommended_products'], 'id') === $expected['ids'], "$context product ranking");
    $check($data['missing_steps'] === $expected['missing'], "$context missing steps");
    $check(($data['warnings'] !== []) === ($expected['missing'] !== [] || count($expected['ids']) < $minimumProducts), "$context warning presence");
    $check(JsonSchema::validate($payload, $requestSchema)->isValid(), "$context request schema");
    $check(JsonSchema::validate($result, $responseSchema)->isValid(), "$context response schema");
    $combinationsPassed += (int) ($before === $failureCount);
    $routinesWithMissingSteps += (int) ($expected['missing'] !== []);
    $routinesBelowTwo += (int) (count($expected['ids']) < $minimumProducts);
}
$check(count($combinations) === 7500, 'Expected all 7,500 combinations.');

$anchorCasesPassed = 0;
foreach (array_slice($source['sheets']['Ca thử']['values'], 4) as $case) {
    $before = $failureCount;
    $options = array_slice($case, 2, 6);
    $data = $engine->processSubmission($payloadFor($options), 'v4')['data'];
    preg_match_all('/#(\d+)\b/u', $case[32], $matches);
    $check(array_column($data['recommended_products'], 'id') === array_map('intval', $matches[1]), $case[0] . ' manually specified product IDs from source AG');
    $check($data['skin_profile']['primary_type'] === $skinNames[$case[13]], $case[0] . ' expected skin type');
    $check($data['skin_profile']['is_sensitive'] === ($case[14] === 'Có'), $case[0] . ' sensitivity flag');
    $check($data['skin_profile']['primary_concern'] === $concernNames[$case[15]], $case[0] . ' primary concern');
    $anchorCasesPassed += (int) ($before === $failureCount);
}

$invalidCasesPassed = 0;
$valid = $payloadFor($combinations[0]);
foreach (['missing', 'duplicate', 'unknown_option', 'unknown_question', 'multiple', 'empty'] as $name) {
    $bad = $valid;
    switch ($name) {
        case 'missing': array_pop($bad['answers']); break;
        case 'duplicate': $bad['answers'][1] = $bad['answers'][0]; break;
        case 'unknown_option': $bad['answers'][0]['answer'] = '1Z'; break;
        case 'unknown_question': $bad['answers'][0]['question_id'] = 'q7'; break;
        case 'multiple': $bad['answers'][0]['answer'] = ['1A', '1B']; break;
        case 'empty': $bad['answers'] = []; break;
    }
    $rejected = false;
    try {
        $engine->processSubmission($bad, 'v4');
    } catch (InvalidArgumentException) {
        $rejected = true;
    }
    $check($rejected, "v4 rejects $name answers");
    $invalidCasesPassed += (int) $rejected;
}
$emptyResult = (new QuizEngineService([]))->processSubmission($valid, 'v4')['data'];
$check($emptyResult['recommended_products'] === [] && $emptyResult['missing_steps'] !== [] && count($emptyResult['warnings']) >= 2, 'Empty fixture catalog must expose shortage and below-minimum warnings; this is not a successful complete routine.');

$report = [
    'checked_at_utc' => gmdate('c'),
    'source_url' => $source['source_url'],
    'source_captured_on' => $source['captured_on'],
    'source_sha256' => hash_file('sha256', $fixturePath),
    'verifier_sha256' => hash_file('sha256', __FILE__),
    'implementation_sha256' => [
        'QuizQuestions.php' => hash_file('sha256', KLEER_PLUGIN_DIR . 'src/Config/QuizQuestions.php'),
        'QuizEngineService.php' => hash_file('sha256', KLEER_PLUGIN_DIR . 'src/Services/QuizEngineService.php'),
    ],
    'mode' => 'isolated_cli_fixture_only',
    'php_version' => PHP_VERSION,
    'version' => 'v4',
    'questions' => count($matrix),
    'options' => array_sum(array_map('count', $matrix)),
    'combinations_checked' => count($combinations),
    'combinations_passed' => $combinationsPassed,
    'workbook_anchor_cases_passed' => $anchorCasesPassed,
    'invalid_answer_cases_passed' => $invalidCasesPassed,
    'assertions' => $checks,
    'failures' => $failureCount,
    'first_failures' => $failures,
    'sample_catalog_products' => count($catalog),
    'routines_with_missing_steps_on_sample_catalog' => $routinesWithMissingSteps,
    'routines_below_two_products_on_sample_catalog' => $routinesBelowTwo,
    'assumptions' => [
        'Eligibility filters by skin type before product scoring (QA A-01; consistent with the eleven source cases).',
        'Specific skin type means fewer than all five types; all-five source rows map to all_skin_types.',
        'Equal score and price are resolved by lower ID; source states only the price tie-break.',
    ],
    'limits' => [
        'No WordPress, WooCommerce database, HTTP, menu, cart, or performance acceptance.',
        'Source IDs 1..30 are fixture row IDs, never asserted to be live WooCommerce IDs.',
        'Passing logic with missing-step warnings is not proof that every answer has a complete purchasable routine.',
        'User selected v4; PO/SM G3 approval and acceptance of the explicit assumptions remain separate.',
    ],
];
echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), PHP_EOL;
exit($failureCount > 0 ? 1 : 0);
