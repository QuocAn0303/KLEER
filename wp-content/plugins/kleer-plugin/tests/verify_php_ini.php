<?php

declare(strict_types=1);

/**
 * CI gate: xac nhan php.ini that su duoc nap trong container.
 *
 * Mot file .ini sai cu phap bi PHP bo qua im lang, nen ton tai ma moi dung
 * la mot loi hanh dong. Gate nay lam no that lai thay vi tin vao viec doc file.
 *
 * Usage: php tests/verify_php_ini.php
 */

// Only assert directives that CLI actually reports from php.ini.
// max_execution_time is intentionally excluded: PHP forces it to 0 for every CLI
// run, so asserting 300 here would fail on a correctly loaded php.ini.
$expectations = [
    'memory_limit' => '512M',
    'upload_max_filesize' => '100M',
    'post_max_size' => '100M',
    'display_errors' => '',
    'log_errors' => '1',
    'opcache.enable' => '1',
    'expose_php' => '',
];

$failures = [];

foreach ($expectations as $key => $expected) {
    $actual = (string) ini_get($key);

    if ($actual !== $expected) {
        $failures[] = sprintf('%-22s expected %-8s actual %s', $key, $expected === '' ? '(off)' : $expected, $actual === '' ? '(off)' : $actual);
    }
}

$requiredExtensions = ['mbstring', 'intl', 'gd', 'zip', 'mysqli', 'pdo_mysql', 'redis'];
foreach ($requiredExtensions as $extension) {
    if (!extension_loaded($extension)) {
        $failures[] = sprintf('missing PHP extension: %s', $extension);
    }
}

if ($failures !== []) {
    fwrite(STDERR, "php.ini / extensions FAIL:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '  - ' . $failure . PHP_EOL);
    }
    exit(1);
}

echo 'php.ini applied and required extensions present' . PHP_EOL;
