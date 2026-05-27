<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'success' => true,
    'message' => 'Backend ativo. Use /api para os endpoints e /frontend/html para as páginas.',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
