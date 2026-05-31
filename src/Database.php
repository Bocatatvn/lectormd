<?php

namespace App;

class Database
{
    private static ?Database $instance = null;
    private \PDO $pdo;

    private function __construct()
    {
        $host = $_ENV['DB_HOST'] ?? 'lectorimd-sql';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $dbName = $_ENV['MYSQL_DATABASE'] ?? 'lectormd';
        $user = $_ENV['MYSQL_USER'] ?? 'root';
        $pass = $_ENV['MYSQL_ROOT_PASSWORD'] ?? 'test';

        $this->pdo = new \PDO(
            "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4",
            $user,
            $pass,
            [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]
        );
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): \PDO
    {
        return $this->pdo;
    }
}
