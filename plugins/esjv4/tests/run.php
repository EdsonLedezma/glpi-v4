<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$src = $root . '/src';

if (is_dir($src)) {
    foreach (glob($src . '/*.php') ?: [] as $file) {
        require_once $file;
    }
}

$tests = [
    __DIR__ . '/PluginTest.php',
    __DIR__ . '/MenuTest.php',
    __DIR__ . '/AccessProvisioningServiceTest.php',
    __DIR__ . '/CatalogTest.php',
    __DIR__ . '/SapMailSampleTest.php',
    __DIR__ . '/SapMailParserTest.php',
    __DIR__ . '/SapIntakeServiceTest.php',
    __DIR__ . '/SchemaTest.php',
    __DIR__ . '/TemplateServiceTest.php',
    __DIR__ . '/ProjectPlanBuilderTest.php',
    __DIR__ . '/PlanningInputTest.php',
    __DIR__ . '/PlanningServiceTest.php',
    __DIR__ . '/PlanningRepositoryTest.php',
    __DIR__ . '/ProjectOverviewServiceTest.php',
    __DIR__ . '/ProjectActionServiceTest.php',
    __DIR__ . '/KanbanServiceTest.php',
    __DIR__ . '/ActivityActionServiceTest.php',
    __DIR__ . '/RfiLinkServiceTest.php',
    __DIR__ . '/TimeReportServiceTest.php',
    __DIR__ . '/GateReleaseServiceTest.php',
    __DIR__ . '/EventLogTest.php',
    __DIR__ . '/GateServiceTest.php',
];

$failures = 0;

function esjv4_assert_true(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function esjv4_assert_same(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            $message . ' Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true)
        );
    }
}

foreach ($tests as $test) {
    $name = basename($test, '.php');

    try {
        require $test;
        echo 'PASS ' . $name . PHP_EOL;
    } catch (Throwable $e) {
        $failures++;
        echo 'FAIL ' . $name . ': ' . $e->getMessage() . PHP_EOL;
    }
}

if ($failures > 0) {
    echo $failures . ' test file(s) failed' . PHP_EOL;
    exit(1);
}

echo 'All tests passed' . PHP_EOL;
