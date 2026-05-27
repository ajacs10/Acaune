<?php
declare(strict_types=1);

define('BASE_PATH', __DIR__);
require __DIR__ . '/backend/php/conexao.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = conexao();
    $database = $pdo->query('SELECT DATABASE()')->fetchColumn();

    echo "Conexão efetuada com sucesso.\n";
    echo "Base de dados ativa: {$database}\n";
} catch (Throwable $exception) {
    http_response_code(500);
    echo "Erro ao ligar à base de dados.\n";
    echo $exception->getMessage() . "\n\n";
    echo "Verifique:\n";
    echo "1. Se o MySQL está iniciado no XAMPP/WAMP.\n";
    echo "2. Se a base AcaUni foi importada no phpMyAdmin.\n";
    echo "3. Se o ficheiro .env tem DB_HOST, DB_NAME, DB_USER e DB_PASS corretos.\n";
}
