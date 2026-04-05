<?php
declare(strict_types=1);

namespace MareDiSakai\Inventory\Support;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $config = app_config();
        $dbConfig = $config['db'] ?? [];

        $host = getenv('DB_HOST') ?: (string) ($dbConfig['host'] ?? 'localhost');
        $port = (int) (getenv('DB_PORT') ?: ($dbConfig['port'] ?? 3306));
        $database = getenv('DB_NAME') ?: (string) ($dbConfig['database'] ?? '');
        $username = getenv('DB_USER') ?: (string) ($dbConfig['username'] ?? '');
        $password = getenv('DB_PASSWORD') ?: (string) ($dbConfig['password'] ?? '');

        if ($database === '' || $username === '') {
            throw new RuntimeException('データベース設定が不足しています。config/app.php を確認してください。');
        }

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $database);

        try {
            self::$pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException('データベース接続に失敗しました: ' . $exception->getMessage(), 0, $exception);
        }

        return self::$pdo;
    }
}
