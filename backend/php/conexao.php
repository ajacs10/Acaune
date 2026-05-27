<?php
declare(strict_types=1);

function carregarAmbiente(string $basePath): void
{
    $envFile = $basePath . '/.env';

    if (!is_file($envFile)) {
        return;
    }

    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");

        if (getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

function valorAmbiente(string $key, string $default = ''): string
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    return $value === false || $value === null || $value === '' ? $default : (string) $value;
}

function conexao(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
    carregarAmbiente($basePath);

    $host = valorAmbiente('DB_HOST', '127.0.0.1');
    $database = valorAmbiente('DB_NAME', 'AcaUni');
    $charset = valorAmbiente('DB_CHARSET', 'utf8mb4');
    $user = valorAmbiente('DB_USER', 'root');
    $password = valorAmbiente('DB_PASS', '');

    $dsn = "mysql:host={$host};dbname={$database};charset={$charset}";

    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
