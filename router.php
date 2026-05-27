<?php
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$file = __DIR__ . $path;

if ($path === '/') {
    header('Location: /frontend/html/login.html');
    return true;
}

if (str_starts_with($path, '/api')) {
    require __DIR__ . '/public/api/index.php';
    return true;
}

if ($path === '/testar-conexao') {
    require __DIR__ . '/testar-conexao.php';
    return true;
}

if (is_file($file)) {
    return false;
}

http_response_code(404);
echo '404 - Ficheiro nao encontrado';
return true;
