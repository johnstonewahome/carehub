<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/helpers.php';

$failures = 0;
function expect_eq(mixed $actual, mixed $expected, string $message): void
{
    global $failures;
    if ($actual !== $expected) {
        $failures++;
        fwrite(STDERR, "FAIL: {$message} (got " . var_export($actual, true) . ")\n");
        return;
    }
    fwrite(STDOUT, "ok: {$message}\n");
}

expect_eq(format_qty(8.0), '8', 'float whole number');
expect_eq(format_qty(12.5), '12.5', 'float with decimal');
expect_eq(format_qty('40.00'), '40', 'string with trailing zeros');
expect_eq(format_qty(0), '0', 'zero');
expect_eq(format_qty(null), '0', 'null');

if ($failures > 0) {
    fwrite(STDERR, "{$failures} failure(s)\n");
    exit(1);
}
fwrite(STDOUT, "All format tests passed\n");
