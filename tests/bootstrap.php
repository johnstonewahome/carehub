<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';

function test_pdo(): PDO
{
    $pdo = new PDO('sqlite::memory:', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    apply_schema($pdo);
    return $pdo;
}

function test_seed_visit(PDO $pdo, int $patientId): int
{
    $stmt = $pdo->prepare(
        "INSERT INTO visits (patient_id, visited_at, chief_complaint) VALUES (?, datetime('now'), ?)"
    );
    $stmt->execute([$patientId, 'Test complaint']);
    return (int) $pdo->lastInsertId();
}
