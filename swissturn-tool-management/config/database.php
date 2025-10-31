<?php
/**
 * Swissturn Tool Management System
 * Database Configuration
 *
 * This file contains database connection settings and provides
 * a singleton database connection instance.
 */

// Database configuration constants
define('DB_SERVER', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_DATABASE', 'u359033001_TOOL');
define('DB_USERNAME', 'u359033001_TOOL');
define('DB_PASSWORD', 'Creo$10001');
define('DB_CHARSET', 'utf8mb4');

/**
 * Database Connection Class
 * Implements singleton pattern for database connectivity
 */
class Database {
    private static $instance = null;
    private $connection;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_SERVER . ";port=" . DB_PORT . ";dbname=" . DB_DATABASE . ";charset=" . DB_CHARSET;

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];

            $this->connection = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);

        } catch(PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            die("Database connection failed. Please contact system administrator.");
        }
    }

    /**
     * Get singleton instance of Database
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get PDO connection
     * @return PDO
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}

    /**
     * Prevent unserializing of the instance
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Get database connection
 * Convenience function to get PDO connection
 *
 * @return PDO
 */
function getDB() {
    return Database::getInstance()->getConnection();
}

/**
 * Execute a prepared statement and return results
 *
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters to bind
 * @return array Query results
 */
function dbQuery($sql, $params = []) {
    try {
        $db = getDB();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Query Error: " . $e->getMessage() . " | SQL: " . $sql);
        throw $e;
    }
}

/**
 * Execute a prepared statement (INSERT, UPDATE, DELETE)
 *
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters to bind
 * @return int Number of affected rows
 */
function dbExecute($sql, $params = []) {
    try {
        $db = getDB();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    } catch(PDOException $e) {
        error_log("Execute Error: " . $e->getMessage() . " | SQL: " . $sql);
        throw $e;
    }
}

/**
 * Get the last inserted ID
 *
 * @return int Last insert ID
 */
function dbLastInsertId() {
    return getDB()->lastInsertId();
}

/**
 * Begin database transaction
 */
function dbBeginTransaction() {
    return getDB()->beginTransaction();
}

/**
 * Commit database transaction
 */
function dbCommit() {
    return getDB()->commit();
}

/**
 * Rollback database transaction
 */
function dbRollback() {
    return getDB()->rollBack();
}

/**
 * Check if we're in a transaction
 *
 * @return bool
 */
function dbInTransaction() {
    return getDB()->inTransaction();
}
