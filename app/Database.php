<?php
/**
 * CREODENT Integrated Web Operations System
 * Database Connection Manager
 *
 * Manages PDO connections with proper error handling and connection pooling
 */

declare(strict_types=1);

class Database {
    private static ?PDO $connection = null;

    /**
     * Get PDO database connection (singleton pattern)
     *
     * @return PDO
     * @throws PDOException
     */
    public static function getConnection(): PDO {
        if (self::$connection === null) {
            $config = config('database');

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );

            try {
                self::$connection = new PDO(
                    $dsn,
                    $config['username'],
                    $config['password'],
                    $config['options']
                );
            } catch (PDOException $e) {
                // Log the error (implement your logging here)
                error_log('Database connection failed: ' . $e->getMessage());
                throw new PDOException('Database connection failed. Please contact administrator.');
            }
        }

        return self::$connection;
    }

    /**
     * Close the database connection
     */
    public static function closeConnection(): void {
        self::$connection = null;
    }

    /**
     * Begin a database transaction
     *
     * @return bool
     */
    public static function beginTransaction(): bool {
        return self::getConnection()->beginTransaction();
    }

    /**
     * Commit a database transaction
     *
     * @return bool
     */
    public static function commit(): bool {
        return self::getConnection()->commit();
    }

    /**
     * Rollback a database transaction
     *
     * @return bool
     */
    public static function rollback(): bool {
        return self::getConnection()->rollBack();
    }

    /**
     * Execute a query and return the statement
     *
     * @param string $sql
     * @param array $params
     * @return PDOStatement
     */
    public static function query(string $sql, array $params = []): PDOStatement {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch a single row
     *
     * @param string $sql
     * @param array $params
     * @return array|null
     */
    public static function fetchOne(string $sql, array $params = []): ?array {
        $stmt = self::query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Fetch all rows
     *
     * @param string $sql
     * @param array $params
     * @return array
     */
    public static function fetchAll(string $sql, array $params = []): array {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Insert a record and return the last insert ID
     *
     * @param string $sql
     * @param array $params
     * @return int
     */
    public static function insert(string $sql, array $params = []): int {
        self::query($sql, $params);
        return (int) self::getConnection()->lastInsertId();
    }

    /**
     * Update records and return affected rows count
     *
     * @param string $sql
     * @param array $params
     * @return int
     */
    public static function update(string $sql, array $params = []): int {
        $stmt = self::query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Delete records and return affected rows count
     *
     * @param string $sql
     * @param array $params
     * @return int
     */
    public static function delete(string $sql, array $params = []): int {
        $stmt = self::query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Check if a record exists
     *
     * @param string $sql
     * @param array $params
     * @return bool
     */
    public static function exists(string $sql, array $params = []): bool {
        $stmt = self::query($sql, $params);
        return $stmt->rowCount() > 0;
    }
}
