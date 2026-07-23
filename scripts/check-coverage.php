<?php

declare(strict_types=1);

/**
 * Assert statement coverage from a PHPUnit Clover report meets a threshold.
 *
 * Usage:
 *   php scripts/check-coverage.php [clover-path] [threshold]
 *
 * Defaults:
 *   clover-path: build/coverage/clover.xml
 *   threshold:   COVERAGE_THRESHOLD env or 90
 */
$cloverPath = $argv[1] ?? 'build/coverage/clover.xml';
$threshold = isset($argv[2])
    ? (float) $argv[2]
    : (float) (getenv('COVERAGE_THRESHOLD') !== false ? getenv('COVERAGE_THRESHOLD') : 90);

if (! is_file($cloverPath)) {
    fwrite(STDERR, sprintf("Coverage report not found: %s\n", $cloverPath));
    exit(1);
}

$xml = simplexml_load_file($cloverPath);
if ($xml === false) {
    fwrite(STDERR, sprintf("Unable to parse coverage report: %s\n", $cloverPath));
    exit(1);
}

$metrics = $xml->project->metrics ?? null;
if ($metrics === null) {
    fwrite(STDERR, "Clover report is missing project metrics.\n");
    exit(1);
}

$statements = (int) $metrics['statements'];
$covered = (int) $metrics['coveredstatements'];
$percent = $statements > 0 ? round(($covered / $statements) * 100, 2) : 0.0;

echo sprintf("Code coverage: %s%% (threshold: %s%%)\n", $percent, $threshold);

if ($percent < $threshold) {
    fwrite(STDERR, sprintf(
        "Code coverage %s%% is below minimum threshold of %s%%\n",
        $percent,
        $threshold,
    ));
    exit(1);
}

exit(0);
