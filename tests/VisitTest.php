<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require dirname(__DIR__) . '/includes/Stock.php';
require dirname(__DIR__) . '/includes/Visits.php';
require dirname(__DIR__) . '/includes/Patients.php';

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

$pdo = test_pdo();
$stock = new Stock($pdo);
$visits = new Visits($pdo, $stock);
$patients = new Patients($pdo);

$id = $patients->create([
    'first_name' => 'Kwame',
    'last_name' => 'Asante',
    'sex' => 'male',
    'dob' => '1990-01-01',
    'phone' => '0200000000',
    'address' => '',
    'allergies' => '',
    'medical_history' => '',
]);
$created = $patients->get($id);
expect_true(($created['chart_no'] ?? '') === sprintf('CH-%04d', $id), 'new patient gets CH-padded chart number');

$medicineId = (int) $pdo->query("SELECT id FROM medicines WHERE name = 'Amoxil capsules'")->fetchColumn();
$before = $stock->quantityOnHand($medicineId);
$visitCountBefore = (int) $pdo->query('SELECT COUNT(*) FROM visits')->fetchColumn();

$blocked = false;
try {
    $visits->create($id, [
        'visited_at' => '2026-08-24 10:00',
        'chief_complaint' => 'Cough',
        'examination' => '',
        'diagnosis' => '',
        'treatment' => '',
        'bp_systolic' => '',
        'bp_diastolic' => '',
        'pulse' => '',
        'temp_c' => '',
        'weight_kg' => '',
        'notes' => '',
    ], [[
        'medicine_id' => $medicineId,
        'quantity' => 9999,
        'dose_instructions' => 'too much',
    ]]);
} catch (StockException $e) {
    $blocked = true;
}
expect_true($blocked, 'visit create blocks when dispense exceeds stock');
$visitCountAfter = (int) $pdo->query('SELECT COUNT(*) FROM visits')->fetchColumn();
expect_true($visitCountAfter === $visitCountBefore, 'failed visit does not leave a visit row');
expect_true($stock->quantityOnHand($medicineId) === $before, 'failed visit does not change stock');

$visitId = $visits->create($id, [
    'visited_at' => '2026-08-24 11:00',
    'chief_complaint' => 'Cough',
    'examination' => 'Chest clear',
    'diagnosis' => 'Viral URTI',
    'treatment' => 'Rest',
    'bp_systolic' => '120',
    'bp_diastolic' => '80',
    'pulse' => '72',
    'temp_c' => '36.8',
    'weight_kg' => '70',
    'notes' => '',
], [[
    'medicine_id' => $medicineId,
    'quantity' => 3,
    'dose_instructions' => '1 capsule three times daily',
]]);
$saved = $visits->get($visitId);
expect_true($saved !== null && $saved['chief_complaint'] === 'Cough', 'visit is saved');
expect_true(count($saved['medications']) === 1, 'visit stores dispensed medicine');
expect_true($stock->quantityOnHand($medicineId) === $before - 3, 'successful visit deducts stock');

if ($failures > 0) {
    fwrite(STDERR, "{$failures} failure(s)\n");
    exit(1);
}
fwrite(STDOUT, "All visit tests passed\n");
