<?php
declare(strict_types=1);

namespace App;

/**
 * Database Singleton & Connection Manager
 */
class Database
{
    private static ?self $instance = null;
    private ?\PDO $pdo = null;
    private string $driver = 'sqlite';

    private function __construct()
    {
        $dbConfig = require __DIR__ . '/../config/database.php';
        $driver = $dbConfig['default'] ?? 'sqlite';
        $this->driver = $driver;

        if ($driver === 'mysql') {
            $config = $dbConfig['connections']['mysql'];
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
            try {
                $this->pdo = new \PDO($dsn, $config['username'], $config['password'], $config['options']);
            } catch (\PDOException $e) {
                // If MySQL fails and app is in local/debug mode, graceful fallback or rethrow
                throw new \RuntimeException("MySQL Connection Failed: " . $e->getMessage(), (int)$e->getCode(), $e);
            }
        } else {
            $config = $dbConfig['connections']['sqlite'];
            $dbPath = $config['database'];
            
            // Ensure directory exists
            $dir = dirname($dbPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $dsn = "sqlite:{$dbPath}";
            $this->pdo = new \PDO($dsn, null, null, $config['options']);
            // Enable WAL mode & foreign keys for SQLite
            $this->pdo->exec('PRAGMA foreign_keys = ON;');
            $this->pdo->exec('PRAGMA journal_mode = WAL;');
        }
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

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function fetchColumn(string $sql, array $params = []): mixed
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function prepare(string $sql): \PDOStatement
    {
        return $this->pdo->prepare($sql);
    }

    public function query(string $sql): \PDOStatement
    {
        return $this->pdo->query($sql);
    }

    public function exec(string $sql): int
    {
        return $this->pdo->exec($sql);
    }

    // Prevent cloning and unserialization
    private function __clone() {}
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}

