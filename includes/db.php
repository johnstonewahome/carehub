<?php
declare(strict_types=1);

function schema_path(): string
{
    return dirname(__DIR__) . '/database/schema.sql';
}

function database_path(): string
{
    $configured = (string) config('database_path', 'database/carehub.sqlite');
    if ($configured !== '' && ($configured[0] === '/' || preg_match('/^[A-Za-z]:[\\\\\\/]/', $configured) === 1)) {
        return $configured;
    }
    return dirname(__DIR__) . '/' . ltrim($configured, '/');
}

function run_sql_script(PDO $pdo, string $sql): void
{
    $sql = preg_replace('/--.*$/m', '', $sql) ?? $sql;
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
        if ($statement !== '') {
            $pdo->exec($statement);
        }
    }
}

function apply_schema(PDO $pdo): void
{
    $sql = file_get_contents(schema_path());
    if ($sql === false) {
        throw new RuntimeException('Could not read database/schema.sql');
    }
    run_sql_script($pdo, $sql);
}

function sqlite_connect(string $path): PDO
{
    $dir = dirname($path);
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Could not create the database folder.');
    }
    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA busy_timeout = 5000');
    return $pdo;
}

function db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $path = database_path();
    $fresh = !is_file($path) || filesize($path) === 0;
    $pdo = sqlite_connect($path);
    if ($fresh) {
        apply_schema($pdo);
    }
    return $pdo;
}
