<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        try 
        {
            require_once BASE_PATH . '/backend/php/conexao.php';
            self::$connection = \conexao();
        } catch (PDOException $exception) {
            throw new PDOException('Database connection error: ' . $exception->getMessage(), (int) $exception->getCode());
        }

        return self::$connection;
    }
}
