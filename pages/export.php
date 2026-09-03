<?php
declare(strict_types=1);

$bytes = (new ClinicExport($svc['db']))->xlsxBytes();
$filename = 'care-center-' . date('Y-m-d') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . (string) strlen($bytes));
header('Cache-Control: no-store');
echo $bytes;
exit;
