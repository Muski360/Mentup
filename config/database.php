<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// Usa nomes prefixados para nao sobrescrever variaveis dos formularios que incluem este arquivo.
$dbHost = $_ENV['DB_HOST'] ?? null;
$dbPort = $_ENV['DB_PORT'] ?? '5432';
$dbName = $_ENV['DB_NAME'] ?? 'postgres';
$dbUser = $_ENV['DB_USER'] ?? null;
$dbPassword = $_ENV['DB_PASSWORD'] ?? null;
$dbSslmode = $_ENV['DB_SSLMODE'] ?? 'require';

if (!$dbHost || !$dbUser || !$dbPassword) {
    die('Error: Database .env is incomplete. Check .env variables.');
}

try {
    $pdo = new PDO(
        "pgsql:host={$dbHost};port={$dbPort};dbname={$dbName};sslmode={$dbSslmode}",
        $dbUser,
        $dbPassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die('Erro ao conectar com o banco: ' . $e->getMessage());
}
