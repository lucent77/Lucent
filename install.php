<?php
/**
 * CREODENT CADCAM Work Management System
 * Installation Script
 *
 * Usage: php install.php
 */

echo "\n";
echo "========================================\n";
echo "CREODENT CADCAM Work Management System\n";
echo "Installation Script\n";
echo "========================================\n\n";

// Load configuration
$configFile = __DIR__ . '/config/config.php';

if (!file_exists($configFile)) {
    echo "ERROR: Configuration file not found!\n";
    echo "Please copy config/config.example.php to config/config.php and configure it.\n";
    exit(1);
}

$config = require $configFile;

echo "Step 1: Testing database connection...\n";

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%d;charset=%s',
        $config['database']['host'],
        $config['database']['port'],
        $config['database']['charset']
    );

    $pdo = new PDO(
        $dsn,
        $config['database']['username'],
        $config['database']['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "✓ Database connection successful!\n\n";

} catch (PDOException $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    echo "Please check your database credentials in config/config.php\n";
    exit(1);
}

// Check if database exists
echo "Step 2: Checking database...\n";

try {
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$config['database']['name']}'");
    $dbExists = $stmt->fetch();

    if (!$dbExists) {
        echo "Database '{$config['database']['name']}' does not exist.\n";
        echo "Creating database...\n";

        $pdo->exec("CREATE DATABASE `{$config['database']['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✓ Database created!\n\n";
    } else {
        echo "✓ Database exists\n\n";
    }

    // Connect to the database
    $pdo->exec("USE `{$config['database']['name']}`");

} catch (PDOException $e) {
    echo "✗ Database check/creation failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Check if tables exist
echo "Step 3: Checking database schema...\n";

try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tables)) {
        echo "No tables found. Installing schema...\n\n";

        $schemaFile = __DIR__ . '/database/schema.sql';

        if (!file_exists($schemaFile)) {
            throw new RuntimeException('Schema file not found: ' . $schemaFile);
        }

        $schema = file_get_contents($schemaFile);

        // Split by semicolon and execute each statement
        $statements = array_filter(array_map('trim', explode(';', $schema)));

        $count = 0;
        foreach ($statements as $statement) {
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue;
            }

            try {
                $pdo->exec($statement);
                $count++;
            } catch (PDOException $e) {
                // Ignore some errors
                if (strpos($e->getMessage(), 'already exists') === false) {
                    echo "Warning: " . $e->getMessage() . "\n";
                }
            }
        }

        echo "✓ Executed $count SQL statements\n\n";

    } else {
        echo "✓ Found " . count($tables) . " tables\n";
        echo "Tables: " . implode(', ', $tables) . "\n\n";
    }

} catch (Exception $e) {
    echo "✗ Schema installation failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Create required directories
echo "Step 4: Creating required directories...\n";

$directories = [
    __DIR__ . '/logs',
    __DIR__ . '/uploads',
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "✓ Created: $dir\n";
        } else {
            echo "✗ Failed to create: $dir\n";
        }
    } else {
        echo "✓ Exists: $dir\n";
    }
}

echo "\n";

// Set permissions
echo "Step 5: Setting permissions...\n";

$writeableDirs = [
    __DIR__ . '/logs',
    __DIR__ . '/uploads',
];

foreach ($writeableDirs as $dir) {
    if (is_writable($dir)) {
        echo "✓ Writable: $dir\n";
    } else {
        echo "⚠ Warning: Not writable: $dir\n";
        echo "  Please run: chmod 755 $dir\n";
    }
}

echo "\n";

// Test default admin login
echo "Step 6: Verifying default admin user...\n";

try {
    $stmt = $pdo->query("SELECT username, role FROM users WHERE username = 'admin'");
    $admin = $stmt->fetch();

    if ($admin) {
        echo "✓ Default admin user exists\n";
        echo "  Username: admin\n";
        echo "  Password: admin123\n";
        echo "  Role: " . $admin['role'] . "\n";
        echo "\n";
        echo "  ⚠ IMPORTANT: Please change the default password after first login!\n";
    } else {
        echo "⚠ Warning: Default admin user not found\n";
        echo "  You may need to create an admin user manually.\n";
    }

} catch (PDOException $e) {
    echo "✗ Error checking admin user: " . $e->getMessage() . "\n";
}

echo "\n";
echo "========================================\n";
echo "Installation Complete!\n";
echo "========================================\n\n";

echo "Next Steps:\n";
echo "1. Configure your web server to point to the 'public' directory\n";
echo "2. Update config/config.php with your Evolution Portal credentials (if using)\n";
echo "3. Visit your website and login with:\n";
echo "   Username: admin\n";
echo "   Password: admin123\n";
echo "4. Change the default password immediately!\n";
echo "5. Set up cron job for automatic syncing:\n";
echo "   0 * * * * php " . __DIR__ . "/cron/sync_evolution.php\n\n";

echo "For more information, see README.md\n\n";
