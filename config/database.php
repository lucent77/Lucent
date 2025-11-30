<?php
/**
 * Database Connection Handler
 * Voice Prescription System
 *
 * Provides PDO connection with proper error handling
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

class Database
{
    private static ?PDO $instance = null;
    private static array $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];

    /**
     * Get database connection instance (Singleton pattern)
     *
     * @return PDO
     * @throws PDOException
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            try {
                $dsn = sprintf(
                    "mysql:host=%s;dbname=%s;charset=%s",
                    DB_HOST,
                    DB_NAME,
                    DB_CHARSET
                );

                self::$instance = new PDO($dsn, DB_USER, DB_PASS, self::$options);
            } catch (PDOException $e) {
                if (APP_DEBUG) {
                    throw new PDOException("Database connection failed: " . $e->getMessage());
                }
                throw new PDOException("Database connection failed. Please try again later.");
            }
        }

        return self::$instance;
    }

    /**
     * Close database connection
     */
    public static function closeConnection(): void
    {
        self::$instance = null;
    }

    /**
     * Begin transaction
     */
    public static function beginTransaction(): bool
    {
        return self::getConnection()->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public static function commit(): bool
    {
        return self::getConnection()->commit();
    }

    /**
     * Rollback transaction
     */
    public static function rollback(): bool
    {
        return self::getConnection()->rollBack();
    }

    /**
     * Execute a prepared statement with parameters
     *
     * @param string $sql SQL query
     * @param array $params Parameters to bind
     * @return PDOStatement
     */
    public static function execute(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Get last inserted ID
     *
     * @return string
     */
    public static function lastInsertId(): string
    {
        return self::getConnection()->lastInsertId();
    }

    /**
     * Check if connection is alive
     *
     * @return bool
     */
    public static function isConnected(): bool
    {
        try {
            self::getConnection()->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}
