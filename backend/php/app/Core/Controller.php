<?php
declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Content-Type');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function input(): array
    {
        $payload = file_get_contents('php://input') ?: '';
        $json = json_decode($payload, true);

        if (is_array($json)) {
            return $json;
        }

        return $_POST;
    }
}
