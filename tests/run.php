<?php
declare(strict_types=1);

$files = [__DIR__ . '/StockTest.php', __DIR__ . '/VisitTest.php', __DIR__ . '/FormatTest.php', __DIR__ . '/ExcelTest.php'];
$status = 0;
foreach ($files as $testFile) {
    passthru('php ' . escapeshellarg($testFile), $code);
    if ($code !== 0) {
        $status = $code;
    }
}
exit($status);
