<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require dirname(__DIR__) . '/includes/Stock.php';

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

function expect_eq(mixed $actual, mixed $expected, string $message): void
{
    expect_true($actual == $expected, $message . " (got " . var_export($actual, true) . ")");
}

$pdo = test_pdo();
$stock = new Stock($pdo);

$medicineId = (int) $pdo->query("SELECT id FROM medicines WHERE name = 'Amoxil capsules'")->fetchColumn();
$patientId = (int) $pdo->query("SELECT id FROM patients ORDER BY id ASC LIMIT 1")->fetchColumn();
$start = $stock->quantityOnHand($medicineId);
expect_eq($start, 40, 'seeded Amoxil starts at 40');

$stock->receive($medicineId, 10, 'New box from supplier');
expect_eq($stock->quantityOnHand($medicineId), 50, 'receive adds to on-hand');
$inCount = (int) $pdo->query("SELECT COUNT(*) FROM stock_movements WHERE medicine_id = {$medicineId} AND type = 'in'")->fetchColumn();
expect_eq($inCount, 1, 'receive writes an in movement');

$visitId = test_seed_visit($pdo, $patientId);
$stock->dispense($visitId, $medicineId, 6, '500 mg three times daily for 2 days');
expect_eq($stock->quantityOnHand($medicineId), 44, 'dispense subtracts from on-hand');
$out = $pdo->query("SELECT quantity, visit_id FROM stock_movements WHERE type = 'out' AND visit_id = {$visitId}")->fetch();
expect_eq($out['quantity'] ?? null, 6, 'dispense writes out quantity');
$line = $pdo->query("SELECT quantity, dose_instructions FROM visit_medications WHERE visit_id = {$visitId}")->fetch();
expect_eq($line['quantity'] ?? null, 6, 'dispense writes visit medication');
expect_eq($line['dose_instructions'] ?? null, '500 mg three times daily for 2 days', 'dose instructions stored');

$blocked = false;
try {
    $stock->dispense($visitId, $medicineId, 1000, 'too many');
} catch (StockException $e) {
    $blocked = true;
    expect_true(str_contains($e->getMessage(), 'on hand'), 'over-dispense message mentions on hand');
}
expect_true($blocked, 'dispense more than on-hand is blocked');
expect_eq($stock->quantityOnHand($medicineId), 44, 'failed dispense does not change stock');

$paraId = (int) $pdo->query("SELECT id FROM medicines WHERE name = 'Paracetamol tablets'")->fetchColumn();
expect_true($stock->isLowStock($paraId), 'paracetamol is low stock at seed levels');
expect_true(!$stock->isLowStock($medicineId), 'amoxil is not low stock after receive');

$stock->adjust($medicineId, -4, 'Broken blister counted out');
expect_eq($stock->quantityOnHand($medicineId), 40, 'adjust applies a signed delta');

$adjustBlocked = false;
try {
    $stock->adjust($medicineId, -999, 'wipe out');
} catch (StockException $e) {
    $adjustBlocked = true;
}
expect_true($adjustBlocked, 'adjust cannot take stock below zero');
expect_eq($stock->quantityOnHand($medicineId), 40, 'failed adjust does not change stock');

$qtyBlocked = false;
try {
    $stock->receive($medicineId, 0, 'noop');
} catch (StockException $e) {
    $qtyBlocked = true;
}
expect_true($qtyBlocked, 'receive rejects non-positive quantity');

$negDispense = false;
try {
    $stock->dispense($visitId, $medicineId, -1, 'bad');
} catch (StockException $e) {
    $negDispense = true;
}
expect_true($negDispense, 'dispense rejects non-positive quantity');

if ($failures > 0) {
    fwrite(STDERR, "{$failures} failure(s)\n");
    exit(1);
}

fwrite(STDOUT, "All stock tests passed\n");
exit(0);
