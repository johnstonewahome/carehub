<?php
declare(strict_types=1);

function test_pdo(): PDO
{
    $host = getenv('CAREHUB_TEST_HOST') ?: '127.0.0.1';
    $name = getenv('CAREHUB_TEST_DB') ?: 'carehub_test';
    $user = getenv('CAREHUB_TEST_USER') ?: 'carehub';
    $pass = getenv('CAREHUB_TEST_PASS') ?: 'carehub_dev';

    $pdo = new PDO(
        "mysql:host={$host};dbname={$name};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach (['visit_medications', 'stock_movements', 'visits', 'medicines', 'patients', 'users'] as $table) {
        $pdo->exec("DROP TABLE IF EXISTS {$table}");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

    $sql = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
    if ($sql === false) {
        throw new RuntimeException('Could not read schema.sql');
    }
    $sql = preg_replace('/--.*$/m', '', $sql) ?? $sql;
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
        if ($statement !== '') {
            $pdo->exec($statement);
        }
    }

    return $pdo;
}

function test_seed_visit(PDO $pdo, int $patientId): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO visits (patient_id, visited_at, chief_complaint) VALUES (?, NOW(), ?)'
    );
    $stmt->execute([$patientId, 'Test complaint']);
    return (int) $pdo->lastInsertId();
}
