<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require dirname(__DIR__) . '/includes/Excel.php';
require dirname(__DIR__) . '/includes/ClinicExport.php';

$failures = 0;
function expect_true(bool $cond, string $message): void
{
    global $failures;
    if (!$cond) {
        $failures++;
        fwrite(STDERR, "FAIL: {$message}\n");
        return;
    }
    fwrite(STDOUT, "ok: {$message}\n");
}

expect_true(ExcelWorkbook::columnLetter(0) === 'A', 'column A');
expect_true(ExcelWorkbook::columnLetter(25) === 'Z', 'column Z');
expect_true(ExcelWorkbook::columnLetter(26) === 'AA', 'column AA');

$book = new ExcelWorkbook();
$book->addSheet('Patients', ['Name', 'Age'], [['Ama', 38], ['Daniel', 50]]);
$bytes = $book->bytes();
expect_true(str_starts_with($bytes, 'PK'), 'xlsx is a zip');

$tmp = tempnam(sys_get_temp_dir(), 'xlsx-test');
file_put_contents($tmp, $bytes);
$zip = new ZipArchive();
expect_true($zip->open($tmp) === true, 'xlsx opens as zip');
$sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
$zip->close();
unlink($tmp);
expect_true(is_string($sheet) && str_contains($sheet, 'Ama'), 'sheet contains row value');
expect_true(is_string($sheet) && str_contains($sheet, 'Name'), 'sheet contains header');

$pdo = test_pdo();
$export = new ClinicExport($pdo);
$clinicBytes = $export->xlsxBytes();
$clinicTmp = tempnam(sys_get_temp_dir(), 'xlsx-clinic');
file_put_contents($clinicTmp, $clinicBytes);
$clinicZip = new ZipArchive();
expect_true($clinicZip->open($clinicTmp) === true, 'clinic workbook opens');
$workbook = $clinicZip->getFromName('xl/workbook.xml');
$patientsSheet = $clinicZip->getFromName('xl/worksheets/sheet1.xml');
$allXml = '';
for ($i = 0; $i < $clinicZip->numFiles; $i++) {
    $name = $clinicZip->getNameIndex($i);
    if (is_string($name) && str_ends_with($name, '.xml')) {
        $allXml .= (string) $clinicZip->getFromIndex($i);
    }
}
$clinicZip->close();
unlink($clinicTmp);

expect_true(is_string($workbook) && str_contains($workbook, 'Patients'), 'workbook has Patients sheet');
expect_true(is_string($workbook) && str_contains($workbook, 'Visits'), 'workbook has Visits sheet');
expect_true(is_string($workbook) && str_contains($workbook, 'Medicines'), 'workbook has Medicines sheet');
expect_true(is_string($patientsSheet) && str_contains($patientsSheet, 'PMSHX'), 'patients sheet uses PMSHX header');
expect_true(is_string($patientsSheet) && str_contains($patientsSheet, 'Boateng'), 'patients sheet includes seed patient');
expect_true(!str_contains($allXml, 'password_hash'), 'export does not include password hashes');

if ($failures > 0) {
    fwrite(STDERR, "{$failures} failure(s)\n");
    exit(1);
}
fwrite(STDOUT, "All excel tests passed\n");
