<?php

declare(strict_types=1);

/**
 * The coverage gate of `.github/workflows/ci.yml`: reads a Clover report and fails when the
 * line coverage of the two `src/` trees is below the minimum.
 *
 *   php scripts/coverage-check.php build/coverage.xml 90
 *
 * PHPUnit measures coverage but has no threshold of its own, so the gate lives here instead of in
 * another dev dependency.
 */

require __DIR__ . '/lib/cli.php';

$args = cliArgs();
$path = $args[1] ?? 'build/coverage.xml';
$minimum = (float) ($args[2] ?? '90');

if (!is_file($path)) {
    fwrite(STDERR, "no Clover report at {$path}\n");
    exit(1);
}

$xml = simplexml_load_file($path);
if ($xml === false) {
    fwrite(STDERR, "cannot parse the Clover report at {$path}\n");
    exit(1);
}

$project = $xml->project;
if (!$project instanceof SimpleXMLElement) {
    fwrite(STDERR, "{$path} has no project element\n");
    exit(1);
}

$metrics = $project->metrics;
if (!$metrics instanceof SimpleXMLElement) {
    fwrite(STDERR, "{$path} has no project metrics\n");
    exit(1);
}

$statements = (int) $metrics['statements'];
$covered = (int) $metrics['coveredstatements'];

if ($statements === 0) {
    fwrite(STDERR, "{$path} reports no statements; the report is empty\n");
    exit(1);
}

$percent = 100 * $covered / $statements;
printf("line coverage: %.2f%% (%d of %d statements), gate %.2f%%\n", $percent, $covered, $statements, $minimum);

$uncovered = [];
$files = $project->file;
if ($files !== null) {
    foreach ($files as $file) {
        $fileMetrics = $file->metrics;
        if (!$fileMetrics instanceof SimpleXMLElement) {
            continue;
        }
        $fileStatements = (int) $fileMetrics['statements'];
        $fileCovered = (int) $fileMetrics['coveredstatements'];
        if ($fileStatements > 0 && $fileCovered === 0) {
            $uncovered[] = (string) $file['name'];
        }
    }
}

if ($uncovered !== []) {
    fwrite(STDERR, "files with no covered statement:\n  - " . implode("\n  - ", $uncovered) . "\n");
}

if ($percent + 1e-9 < $minimum) {
    fwrite(STDERR, "coverage is below the gate\n");
    exit(1);
}
