<?php
/**
 * SnackApp v1 - Database Connection
 * Singleton PDO wrapper avec gestion d'erreurs
 */

class Database {
    private static ?PDO $instance = null;
    private static array $config = [];

    /**
     * Initialise la configuration (à appeler une fois au boot)
     */
    public static function init(array $config): void {
        self::$config = $config;
    }

    /**
     * Retourne l'instance PDO (singleton)
     */
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            self::connect();
        }
        return self::$instance;
    }

    /**
     * Connexion à la base de données
     */
    private static function connect(): void {
        $host = self::$config['host'] ?? '127.0.0.1';
        $dbname = self::$config['name'] ?? self::$config['dbname'] ?? 'snackapp';  // Support 'name' et 'dbname'
        $username = self::$config['user'] ?? self::$config['username'] ?? 'root';  // Support 'user' et 'username'
        $password = self::$config['password'] ?? '';
        $charset = self::$config['charset'] ?? 'utf8mb4';
        $port = self::$config['port'] ?? 3306;

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}"
        ];

        try {
            self::$instance = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Connexion à la base de données impossible");
        }
    }

    /**
     * Raccourci pour préparer et exécuter une requête
     */
    public static function query(string $sql, array $params = []): PDOStatement {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Récupère une seule ligne
     */
    public static function fetchOne(string $sql, array $params = []): ?array {
        $result = self::query($sql, $params)->fetch();
        return $result ?: null;
    }

    /**
     * Récupère toutes les lignes
     */
    public static function fetchAll(string $sql, array $params = []): array {
        return self::query($sql, $params)->fetchAll();
    }

    /**
     * Insert et retourne l'ID
     */
    public static function insert(string $table, array $data): int {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

        // 🐛 DEBUG: Log the SQL query
        $debugFile = dirname(__DIR__) . '/admin/debug_orders.log';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($debugFile, "[$timestamp] [DB INSERT] Table: $table\n", FILE_APPEND);
        file_put_contents($debugFile, "[$timestamp] [DB INSERT] Columns: $columns\n", FILE_APPEND);
        file_put_contents($debugFile, "[$timestamp] [DB INSERT] SQL: $sql\n", FILE_APPEND);

        try {
            self::query($sql, array_values($data));
            file_put_contents($debugFile, "[$timestamp] [DB INSERT] SUCCESS!\n", FILE_APPEND);
        } catch (Exception $e) {
            file_put_contents($debugFile, "[$timestamp] [DB INSERT] ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
            throw $e;
        }

        return (int) self::getInstance()->lastInsertId();
    }

    /**
     * Update avec conditions
     */
    public static function update(string $table, array $data, array $where): int {
        $set = implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($data)));
        $whereClause = implode(' AND ', array_map(fn($k) => "{$k} = ?", array_keys($where)));

        $sql = "UPDATE {$table} SET {$set} WHERE {$whereClause}";
        $params = array_merge(array_values($data), array_values($where));

        return self::query($sql, $params)->rowCount();
    }

    /**
     * Delete avec conditions
     */
    public static function delete(string $table, array $where): int {
        $whereClause = implode(' AND ', array_map(fn($k) => "{$k} = ?", array_keys($where)));
        $sql = "DELETE FROM {$table} WHERE {$whereClause}";

        return self::query($sql, array_values($where))->rowCount();
    }

    /**
     * Transaction helper
     */
    public static function transaction(callable $callback) {
        $pdo = self::getInstance();
        $pdo->beginTransaction();

        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
