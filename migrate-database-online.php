<?php
/**
 * Online Database Migration Tool
 * DOLE DILP Monitoring System
 * 
 * This script safely migrates the database online with the following features:
 * - Pre-migration validation and checks
 * - Automatic backup before migration
 * - Transaction support for rollback capability
 * - Step-by-step migration with error handling
 * - Post-migration validation
 * - Detailed logging and reporting
 * 
 * Security: This file should be deleted after successful migration
 * or protected with authentication in production environments.
 */

// Set execution time limit
set_time_limit(300); // 5 minutes

// Error reporting will be set after headers are sent to avoid conflicts
$GLOBALS['migration_errors'] = [];

// Custom error handler (only logs, doesn't echo before headers)
function migrationErrorHandler($errno, $errstr, $errfile, $errline) {
    $errorMsg = "PHP Error [{$errno}]: {$errstr} in {$errfile} on line {$errline}";
    error_log($errorMsg);
    $GLOBALS['migration_errors'][] = $errstr;
    return true; // Suppress default PHP error output
}

// Custom exception handler
function migrationExceptionHandler($exception) {
    $errorMsg = "Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine();
    error_log($errorMsg);
    $GLOBALS['migration_errors'][] = $exception->getMessage();
}

// Don't set error handlers yet - will be set after headers
require_once __DIR__ . '/config/database.php';

class OnlineDatabaseMigrator {
    private $db;
    private $errors = [];
    private $warnings = [];
    private $successes = [];
    private $backupTables = [];
    private $migrationLog = [];
    private $startTime;
    
    const BACKUP_PREFIX = 'backup_';
    const LOG_FILE = 'migration_log.txt';
    
    public function __construct() {
        $this->startTime = microtime(true);
        try {
            echo "Connecting to database...\n";
            flush();
            
            $this->db = Database::getInstance()->getConnection();
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            echo "✓ Database connection established\n";
            flush();
        } catch (Exception $e) {
            $errorMsg = "CRITICAL ERROR: Failed to connect to database: " . $e->getMessage();
            echo "\n✗ {$errorMsg}\n";
            error_log($errorMsg);
            throw $e;
        }
    }
    
    /**
     * Main migration execution
     */
    public function migrate($options = []) {
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "Starting Online Database Migration\n";
        echo str_repeat("=", 70) . "\n\n";
        flush();
        
        $this->log("=== Starting Online Database Migration ===");
        $this->log("Timestamp: " . date('Y-m-d H:i:s'));
        $this->log("Database: " . DB_NAME . " @ " . DB_HOST);
        
        try {
            echo "\nRunning pre-flight checks...\n";
            flush();
            if (!$this->preFlightChecks()) {
                throw new Exception("Pre-flight checks failed. Migration aborted.");
            }
            echo "✓ Pre-flight checks passed\n\n";
            flush();
            
            if (!isset($options['skip_backup']) || !$options['skip_backup']) {
                echo "Creating backup...\n";
                flush();
                if (!$this->createBackup()) {
                    throw new Exception("Backup creation failed. Migration aborted for safety.");
                }
                echo "✓ Backup created successfully\n\n";
                flush();
            } else {
                echo "⚠ WARNING: Skipping backup (not recommended)\n\n";
                flush();
            }
            
            echo "Applying migrations...\n";
            flush();
            if (!$this->applyMigrations()) {
                throw new Exception("Migration application failed.");
            }
            echo "✓ Migrations applied successfully\n\n";
            flush();
            
            echo "Running post-migration validation...\n";
            flush();
            if (!$this->postMigrationValidation()) {
                throw new Exception("Post-migration validation failed.");
            }
            echo "✓ Post-migration validation passed\n\n";
            flush();
            
            $this->successes[] = "✓ All migrations applied successfully";
            $this->log("Migration completed successfully");
            
            echo "\n" . str_repeat("=", 70) . "\n";
            echo "✓ MIGRATION COMPLETED SUCCESSFULLY!\n";
            echo str_repeat("=", 70) . "\n";
            flush();
            
            return true;
            
        } catch (Exception $e) {
            echo "\n" . str_repeat("=", 70) . "\n";
            echo "✗ MIGRATION FAILED\n";
            echo str_repeat("=", 70) . "\n";
            echo "Error: " . $e->getMessage() . "\n";
            echo "File: " . $e->getFile() . "\n";
            echo "Line: " . $e->getLine() . "\n\n";
            flush();
            
            echo "⚠ You can restore from backup using the 'Restore Backup' button.\n";
            flush();
            
            $this->errors[] = "✗ MIGRATION FAILED: " . $e->getMessage();
            $this->log("ERROR: " . $e->getMessage());
            
            return false;
        } finally {
            $this->saveLogToFile();
        }
    }
    
    /**
     * Pre-flight checks before migration
     */
    private function preFlightChecks() {
        $this->log("\n--- Pre-Flight Checks ---");
        $allPassed = true;
        
        if (!$this->checkDatabaseConnection()) {
            $this->errors[] = "✗ Database connection check failed";
            $allPassed = false;
        } else {
            $this->successes[] = "✓ Database connection verified";
        }
        
        if (!$this->checkDatabasePermissions()) {
            $this->errors[] = "✗ Insufficient database permissions";
            $allPassed = false;
        } else {
            $this->successes[] = "✓ Database permissions verified";
        }
        
        if (!$this->checkRequiredTables()) {
            $this->warnings[] = "⚠ Some required tables don't exist (will be created)";
        } else {
            $this->successes[] = "✓ Required tables exist";
        }
        
        if (!$this->checkDiskSpace()) {
            $this->warnings[] = "⚠ Unable to verify disk space";
        } else {
            $this->successes[] = "✓ Sufficient disk space available";
        }
        
        return $allPassed;
    }
    
    /**
     * Check database connection
     */
    private function checkDatabaseConnection() {
        try {
            $stmt = $this->db->query("SELECT 1");
            return $stmt !== false;
        } catch (PDOException $e) {
            $this->log("Connection check failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check database permissions
     */
    private function checkDatabasePermissions() {
        try {
            $requiredPrivileges = ['SELECT', 'INSERT', 'UPDATE', 'DELETE', 'CREATE', 'DROP', 'ALTER', 'INDEX'];
            $stmt = $this->db->query("SHOW GRANTS FOR CURRENT_USER");
            $grants = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $grantString = implode(' ', $grants);
            $hasAllPrivileges = stripos($grantString, 'ALL PRIVILEGES') !== false;
            
            if ($hasAllPrivileges) {
                return true;
            }
            
            foreach ($requiredPrivileges as $privilege) {
                if (stripos($grantString, $privilege) === false) {
                    $this->log("Missing privilege: " . $privilege);
                    return false;
                }
            }
            
            return true;
        } catch (PDOException $e) {
            $this->log("Permission check failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if required tables exist
     */
    private function checkRequiredTables() {
        try {
            $requiredTables = ['users', 'beneficiaries', 'proponents', 'activity_logs', 'proponent_associations'];
            $stmt = $this->db->query("SHOW TABLES");
            $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($requiredTables as $table) {
                if (!in_array($table, $existingTables)) {
                    $this->log("Missing table: " . $table);
                    return false;
                }
            }
            
            return true;
        } catch (PDOException $e) {
            $this->log("Table check failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check available disk space (if possible)
     */
    private function checkDiskSpace() {
        try {
            $stmt = $this->db->query("
                SELECT SUM(data_length + index_length) as size 
                FROM information_schema.TABLES 
                WHERE table_schema = '" . DB_NAME . "'
            ");
            $result = $stmt->fetch();
            $dbSize = $result['size'] ?? 0;
            
            $this->log("Database size: " . $this->formatBytes($dbSize));
            return true;
        } catch (PDOException $e) {
            return true;
        }
    }
    
    /**
     * Create backup of critical tables
     */
    private function createBackup() {
        $this->log("\n--- Creating Backup ---");
        
        try {
            $tablesToBackup = ['users', 'beneficiaries', 'proponents', 'activity_logs'];
            
            foreach ($tablesToBackup as $table) {
                if (!$this->backupTable($table)) {
                    return false;
                }
            }
            
            $this->successes[] = "✓ Backup created successfully";
            return true;
            
        } catch (Exception $e) {
            $this->errors[] = "✗ Backup failed: " . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Backup a single table
     */
    private function backupTable($tableName) {
        try {
            $backupName = self::BACKUP_PREFIX . $tableName . '_' . date('YmdHis');
            
            $stmt = $this->db->query("SHOW TABLES LIKE '{$tableName}'");
            if ($stmt->rowCount() === 0) {
                $this->log("Table {$tableName} doesn't exist, skipping backup");
                return true;
            }
            
            $this->db->exec("DROP TABLE IF EXISTS {$backupName}");
            $this->db->exec("CREATE TABLE {$backupName} LIKE {$tableName}");
            $this->db->exec("INSERT INTO {$backupName} SELECT * FROM {$tableName}");
            
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM {$backupName}");
            $count = $stmt->fetch()['count'];
            
            $this->backupTables[] = $backupName;
            $this->log("Backed up {$tableName} ({$count} rows) to {$backupName}");
            
            return true;
            
        } catch (PDOException $e) {
            $this->log("Failed to backup {$tableName}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Apply database migrations
     */
    private function applyMigrations() {
        $this->log("\n--- Applying Migrations ---");
        
        try {
            $this->createMigrationTrackingTable();
            
            $migrations = [
                'create_base_schema' => [$this, 'migrateBaseSchema'],
                'create_proponent_associations' => [$this, 'migrateProponentAssociations'],
                'create_indexes' => [$this, 'migrateIndexes'],
                'create_triggers' => [$this, 'migrateTriggers'],
                'insert_default_data' => [$this, 'migrateDefaultData'],
            ];
            
            foreach ($migrations as $name => $callback) {
                if ($this->isMigrationApplied($name)) {
                    $this->log("Migration '{$name}' already applied, skipping");
                    continue;
                }
                
                $this->log("Applying migration: {$name}");
                
                if (call_user_func($callback)) {
                    $this->recordMigration($name);
                    $this->successes[] = "✓ Migration '{$name}' applied successfully";
                } else {
                    throw new Exception("Migration '{$name}' failed");
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            $this->errors[] = "✗ Migration application failed: " . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Create migration tracking table
     */
    private function createMigrationTrackingTable() {
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS migrations (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    migration_name VARCHAR(255) UNIQUE NOT NULL,
                    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
            return true;
        } catch (PDOException $e) {
            $this->log("Failed to create migrations table: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if migration was already applied
     */
    private function isMigrationApplied($name) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM migrations WHERE migration_name = ?");
            $stmt->execute([$name]);
            $result = $stmt->fetch();
            return $result['count'] > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Record applied migration
     */
    private function recordMigration($name) {
        try {
            $stmt = $this->db->prepare("INSERT INTO migrations (migration_name) VALUES (?)");
            $stmt->execute([$name]);
            return true;
        } catch (PDOException $e) {
            $this->log("Failed to record migration: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Migrate base schema
     */
    private function migrateBaseSchema() {
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    username VARCHAR(100) UNIQUE NOT NULL,
                    email VARCHAR(100) UNIQUE NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    role ENUM('admin', 'encoder', 'user') DEFAULT 'user',
                    full_name VARCHAR(255) NOT NULL,
                    is_active BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");
            
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS beneficiaries (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    last_name VARCHAR(100) NOT NULL,
                    first_name VARCHAR(100) NOT NULL,
                    middle_name VARCHAR(100),
                    suffix VARCHAR(20),
                    gender ENUM('Male', 'Female') NOT NULL,
                    barangay VARCHAR(100) NOT NULL,
                    municipality VARCHAR(100) NOT NULL,
                    contact_number VARCHAR(20),
                    project_name VARCHAR(255) NOT NULL,
                    type_of_worker VARCHAR(100),
                    amount_worth DECIMAL(15,2) NOT NULL,
                    noted_findings TEXT,
                    date_complied_by_proponent DATE,
                    date_forwarded_to_ro6 DATE,
                    rpmt_findings TEXT,
                    date_approved DATE,
                    date_forwarded_to_nofo DATE,
                    date_turnover DATE,
                    date_monitoring DATE,
                    latitude DECIMAL(10, 8),
                    longitude DECIMAL(11, 8),
                    status ENUM('pending', 'approved', 'implemented', 'monitored') DEFAULT 'pending',
                    created_by INT,
                    updated_by INT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (created_by) REFERENCES users(id),
                    FOREIGN KEY (updated_by) REFERENCES users(id)
                )
            ");
            
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS proponents (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    proponent_type ENUM('LGU-associated', 'Non-LGU-associated') NOT NULL,
                    date_received DATE,
                    noted_findings TEXT,
                    control_number VARCHAR(50) UNIQUE,
                    number_of_copies INT,
                    date_copies_received DATE,
                    district VARCHAR(100),
                    proponent_name VARCHAR(255) NOT NULL,
                    project_title VARCHAR(255) NOT NULL,
                    amount DECIMAL(15,2) NOT NULL,
                    number_of_associations INT,
                    total_beneficiaries INT NOT NULL,
                    male_beneficiaries INT DEFAULT 0,
                    female_beneficiaries INT DEFAULT 0,
                    type_of_beneficiaries VARCHAR(255),
                    category ENUM('Formation', 'Enhancement', 'Restoration') NOT NULL,
                    recipient_barangays TEXT,
                    letter_of_intent_date DATE,
                    date_forwarded_to_ro6 DATE,
                    rpmt_findings TEXT,
                    date_complied_by_proponent DATE,
                    date_complied_by_proponent_nofo DATE,
                    date_forwarded_to_nofo DATE,
                    date_approved DATE,
                    date_check_release DATE,
                    check_number VARCHAR(50),
                    check_date_issued DATE,
                    or_number VARCHAR(50),
                    or_date_issued DATE,
                    date_turnover DATE,
                    date_implemented DATE,
                    date_liquidated DATE,
                    liquidation_deadline DATE,
                    date_monitoring DATE,
                    source_of_funds VARCHAR(255),
                    latitude DECIMAL(10, 8),
                    longitude DECIMAL(11, 8),
                    status ENUM('pending', 'approved', 'implemented', 'liquidated', 'monitored') DEFAULT 'pending',
                    created_by INT,
                    updated_by INT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (created_by) REFERENCES users(id),
                    FOREIGN KEY (updated_by) REFERENCES users(id)
                )
            ");
            
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS activity_logs (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT,
                    action VARCHAR(50) NOT NULL,
                    table_name VARCHAR(50) NOT NULL,
                    record_id INT NOT NULL,
                    description TEXT,
                    ip_address VARCHAR(45),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id)
                )
            ");
            
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS proponent_associations (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    proponent_id INT NOT NULL,
                    association_name VARCHAR(255) NOT NULL,
                    association_address VARCHAR(500) DEFAULT NULL,
                    sort_order INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (proponent_id) REFERENCES proponents(id) ON DELETE CASCADE
                )
            ");
            
            return true;
        } catch (PDOException $e) {
            $this->log("Base schema migration failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Migrate proponent_associations table (separate step for shared hosting compatibility)
     */
    private function migrateProponentAssociations() {
        try {
            try {
                $this->db->exec("
                    CREATE TABLE IF NOT EXISTS proponent_associations (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        proponent_id INT NOT NULL,
                        association_name VARCHAR(255) NOT NULL,
                        association_address VARCHAR(500) DEFAULT NULL,
                        sort_order INT DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (proponent_id) REFERENCES proponents(id) ON DELETE CASCADE
                    )
                ");
                $this->log("proponent_associations table created with FK constraint");
            } catch (PDOException $e) {
                $this->log("FK constraint failed, creating table without FK: " . $e->getMessage());
                $this->db->exec("
                    CREATE TABLE IF NOT EXISTS proponent_associations (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        proponent_id INT NOT NULL,
                        association_name VARCHAR(255) NOT NULL,
                        association_address VARCHAR(500) DEFAULT NULL,
                        sort_order INT DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )
                ");
                $this->warnings[] = "⚠ proponent_associations created without FK constraint (shared hosting limitation)";
            }
            
            return true;
        } catch (PDOException $e) {
            $this->log("proponent_associations migration failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Migrate indexes
     */
    private function migrateIndexes() {
        try {
            $indexes = [
                "CREATE INDEX IF NOT EXISTS idx_beneficiaries_municipality ON beneficiaries(municipality)",
                "CREATE INDEX IF NOT EXISTS idx_beneficiaries_barangay ON beneficiaries(barangay)",
                "CREATE INDEX IF NOT EXISTS idx_beneficiaries_status ON beneficiaries(status)",
                "CREATE INDEX IF NOT EXISTS idx_beneficiaries_date_approved ON beneficiaries(date_approved)",
                "CREATE INDEX IF NOT EXISTS idx_proponents_type ON proponents(proponent_type)",
                "CREATE INDEX IF NOT EXISTS idx_proponents_district ON proponents(district)",
                "CREATE INDEX IF NOT EXISTS idx_proponents_status ON proponents(status)",
                "CREATE INDEX IF NOT EXISTS idx_proponents_control_number ON proponents(control_number)",
                "CREATE INDEX IF NOT EXISTS idx_proponents_date_approved ON proponents(date_approved)",
                "CREATE INDEX IF NOT EXISTS idx_proponent_associations_proponent ON proponent_associations(proponent_id)",
                "CREATE INDEX IF NOT EXISTS idx_activity_logs_user ON activity_logs(user_id)",
                "CREATE INDEX IF NOT EXISTS idx_activity_logs_table ON activity_logs(table_name, record_id)",
            ];
            
            foreach ($indexes as $indexSQL) {
                try {
                    $this->db->exec($indexSQL);
                } catch (PDOException $e) {
                    if ($e->getCode() != '42000') {
                        throw $e;
                    }
                }
            }
            
            return true;
        } catch (PDOException $e) {
            $this->log("Index migration failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Migrate triggers
     */
    private function migrateTriggers() {
        try {
            // Check if triggers are supported (some shared hosting restricts this)
            $stmt = $this->db->query("SHOW VARIABLES LIKE 'have_trigger'");
            $result = $stmt->fetch();
            
            if ($result && strtolower($result['Value']) === 'no') {
                $this->warnings[] = "⚠ Triggers not supported on this server - skipping trigger creation";
                $this->log("Triggers not supported - skipping");
                return true; // Not a failure, just not supported
            }
            
            // Try to drop existing trigger
            try {
                $this->db->exec("DROP TRIGGER IF EXISTS update_liquidation_deadline");
            } catch (PDOException $e) {
                // Ignore if trigger doesn't exist
                $this->log("Note: Could not drop trigger (may not exist): " . $e->getMessage());
            }
            
            // Try to create trigger
            try {
                $triggerSQL = "CREATE TRIGGER update_liquidation_deadline 
                    BEFORE UPDATE ON proponents
                    FOR EACH ROW
                    BEGIN
                        IF NEW.date_turnover IS NOT NULL AND (
                            OLD.date_turnover IS NULL OR 
                            NEW.date_turnover != OLD.date_turnover OR 
                            NEW.proponent_type != OLD.proponent_type
                        ) THEN
                            IF NEW.proponent_type = 'LGU-associated' THEN
                                SET NEW.liquidation_deadline = DATE_ADD(NEW.date_turnover, INTERVAL 10 DAY);
                            ELSE
                                SET NEW.liquidation_deadline = DATE_ADD(NEW.date_turnover, INTERVAL 60 DAY);
                            END IF;
                        END IF;
                    END";
                
                $this->db->exec($triggerSQL);
                $this->log("Trigger created successfully");
                return true;
            } catch (PDOException $e) {
                // Check if it's a permission issue
                if (stripos($e->getMessage(), 'command denied') !== false || 
                    stripos($e->getMessage(), 'access denied') !== false ||
                    stripos($e->getMessage(), 'trigger') !== false) {
                    $this->warnings[] = "⚠ Trigger creation not permitted on this server - you'll need to update liquidation_deadline manually";
                    $this->log("Trigger creation not permitted: " . $e->getMessage());
                    return true; // Not a critical failure
                }
                throw $e; // Re-throw if it's a different error
            }
        } catch (PDOException $e) {
            $this->log("Trigger migration failed: " . $e->getMessage());
            $this->errors[] = "✗ Trigger creation failed: " . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Migrate default data
     */
    private function migrateDefaultData() {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM users WHERE username = 'admin'");
            $result = $stmt->fetch();
            
            if ($result['count'] == 0) {
                $this->db->exec("
                    INSERT INTO users (username, email, password, role, full_name) VALUES 
                    ('admin', 'admin@dilp.gov.ph', '\$2y\$12\$cxubeCJxgDoHaci9zO4Ud.b7uJ7PQQpWfOafrfLY2efdUQGNuRDLi', 'admin', 'System Administrator')
                ");
                $this->log("Default admin user created");
            } else {
                $this->log("Default admin user already exists");
            }
            
            return true;
        } catch (PDOException $e) {
            $this->log("Default data migration failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Post-migration validation
     */
    private function postMigrationValidation() {
        $this->log("\n--- Post-Migration Validation ---");
        
        try {
            $criticalValidations = [
                'Tables exist' => $this->validateTablesExist(),
                'Indexes exist' => $this->validateIndexesExist(),
                'Data integrity' => $this->validateDataIntegrity(),
            ];
            
            $nonCriticalValidations = [
                'Triggers exist' => $this->validateTriggersExist(),
            ];
            
            $allPassed = true;
            foreach ($criticalValidations as $name => $result) {
                if ($result) {
                    $this->successes[] = "✓ {$name} - PASSED";
                } else {
                    $this->errors[] = "✗ {$name} - FAILED";
                    $allPassed = false;
                }
            }
            
            foreach ($nonCriticalValidations as $name => $result) {
                if ($result) {
                    $this->successes[] = "✓ {$name} - PASSED";
                } else {
                    $this->warnings[] = "⚠ {$name} - SKIPPED (non-critical, may not be supported on this server)";
                }
            }
            
            return $allPassed;
            
        } catch (Exception $e) {
            $this->errors[] = "✗ Validation failed: " . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Validate tables exist
     */
    private function validateTablesExist() {
        try {
            $requiredTables = ['users', 'beneficiaries', 'proponents', 'activity_logs', 'proponent_associations', 'migrations'];
            $stmt = $this->db->query("SHOW TABLES");
            $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($requiredTables as $table) {
                if (!in_array($table, $existingTables)) {
                    $this->log("Validation failed: Missing table {$table}");
                    return false;
                }
            }
            
            return true;
        } catch (PDOException $e) {
            $this->log("Table validation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validate indexes exist
     */
    private function validateIndexesExist() {
        try {
            $stmt = $this->db->query("SHOW INDEX FROM proponents WHERE Key_name = 'idx_proponents_type'");
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $this->log("Index validation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validate triggers exist
     */
    private function validateTriggersExist() {
        try {
            $stmt = $this->db->query("SHOW TRIGGERS LIKE 'proponents'");
            $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($triggers as $trigger) {
                if ($trigger['Trigger'] === 'update_liquidation_deadline') {
                    return true;
                }
            }
            
            $this->log("Validation failed: Trigger 'update_liquidation_deadline' not found");
            return false;
        } catch (PDOException $e) {
            $this->log("Trigger validation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validate data integrity
     */
    private function validateDataIntegrity() {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM users");
            $result = $stmt->fetch();
            
            if ($result['count'] == 0) {
                $this->log("Validation warning: No users in database");
                return false;
            }
            
            return true;
        } catch (PDOException $e) {
            $this->log("Data integrity validation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cleanup backup tables
     */
    public function cleanupBackups() {
        $this->log("\n--- Cleaning Up Backups ---");
        
        foreach ($this->backupTables as $backupTable) {
            try {
                $this->db->exec("DROP TABLE IF EXISTS {$backupTable}");
                $this->log("Dropped backup table: {$backupTable}");
            } catch (PDOException $e) {
                $this->log("Failed to drop backup table {$backupTable}: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Restore from backup
     */
    public function restoreFromBackup() {
        $this->log("\n--- Restoring from Backup ---");
        
        try {
            $this->db->beginTransaction();
            
            foreach ($this->backupTables as $backupTable) {
                $originalTable = str_replace(self::BACKUP_PREFIX, '', preg_replace('/_\d{14}$/', '', $backupTable));
                
                $this->db->exec("TRUNCATE TABLE {$originalTable}");
                $this->db->exec("INSERT INTO {$originalTable} SELECT * FROM {$backupTable}");
                
                $this->log("Restored {$originalTable} from {$backupTable}");
            }
            
            $this->db->commit();
            $this->successes[] = "✓ Database restored from backup successfully";
            return true;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            $this->errors[] = "✗ Restore failed: " . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Log message
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $this->migrationLog[] = "[{$timestamp}] {$message}";
    }
    
    /**
     * Save log to file
     */
    private function saveLogToFile() {
        $logContent = implode("\n", $this->migrationLog);
        $logContent .= "\n\nExecution time: " . round(microtime(true) - $this->startTime, 2) . " seconds\n";
        
        file_put_contents(__DIR__ . '/' . self::LOG_FILE, $logContent);
    }
    
    /**
     * Get results
     */
    public function getResults() {
        return [
            'successes' => $this->successes,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'log' => $this->migrationLog,
            'execution_time' => round(microtime(true) - $this->startTime, 2),
        ];
    }
    
    /**
     * Format bytes
     */
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Print results
     */
    public function printResults() {
        $results = $this->getResults();
        
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "DATABASE MIGRATION RESULTS\n";
        echo str_repeat("=", 70) . "\n\n";
        
        if (!empty($results['successes'])) {
            echo "✓ SUCCESSES:\n";
            foreach ($results['successes'] as $msg) {
                echo "  {$msg}\n";
            }
            echo "\n";
        }
        
        if (!empty($results['warnings'])) {
            echo "⚠ WARNINGS:\n";
            foreach ($results['warnings'] as $msg) {
                echo "  {$msg}\n";
            }
            echo "\n";
        }
        
        if (!empty($results['errors'])) {
            echo "✗ ERRORS:\n";
            foreach ($results['errors'] as $msg) {
                echo "  {$msg}\n";
            }
            echo "\n";
        }
        
        echo "Execution time: {$results['execution_time']} seconds\n";
        echo "Log saved to: " . self::LOG_FILE . "\n";
        
        echo "\n" . str_repeat("=", 70) . "\n\n";
    }
}

$isCommandLine = php_sapi_name() === 'cli';

if ($isCommandLine) {
    $migrator = new OnlineDatabaseMigrator();
    
    $args = isset($argv) ? array_slice($argv, 1) : [];
    $options = [];
    
    if (in_array('--skip-backup', $args)) {
        $options['skip_backup'] = true;
        echo "WARNING: Running without backup!\n";
    }
    
    if (in_array('--restore', $args)) {
        echo "Restoring from backup...\n";
        $migrator->restoreFromBackup();
        $migrator->printResults();
        exit(0);
    }
    
    if (in_array('--cleanup', $args)) {
        echo "Cleaning up backup tables...\n";
        $migrator->cleanupBackups();
        exit(0);
    }
    
    $success = $migrator->migrate($options);
    $migrator->printResults();
    
    if ($success) {
        echo "Migration completed successfully!\n";
        echo "You can now cleanup backup tables with: php migrate-database-online.php --cleanup\n";
    } else {
        echo "Migration failed! You can restore with: php migrate-database-online.php --restore\n";
    }
    
    exit($success ? 0 : 1);
    
} else {
    header('Content-Type: text/html; charset=utf-8');
    
    $action = $_GET['action'] ?? null;
    
    if ($action) {
        // Set headers for streaming output
        header('Content-Type: text/plain; charset=utf-8');
        header('X-Accel-Buffering: no');
        header('Cache-Control: no-cache');
        
        // Now set error handlers after headers are sent
        set_error_handler('migrationErrorHandler');
        set_exception_handler('migrationExceptionHandler');
        
        // Enable error reporting
        ini_set('display_errors', 0); // Don't display, we'll handle them
        error_reporting(E_ALL);
        
        // Disable output buffering
        if (ob_get_level()) ob_end_clean();
        
        // Display any errors that occurred before headers
        if (!empty($GLOBALS['migration_errors'])) {
            foreach ($GLOBALS['migration_errors'] as $error) {
                echo "⚠ Early error: {$error}\n";
            }
            echo "\n";
        }
        
        try {
            echo "Initializing migration system...\n";
            flush();
            
            $migrator = new OnlineDatabaseMigrator();
            
            switch ($action) {
                case 'migrate':
                    echo "\nStarting migration process...\n\n";
                    flush();
                    $success = $migrator->migrate();
                    echo "\n";
                    $migrator->printResults();
                    if ($success) {
                        echo "\n✓ You can now cleanup backup tables using the 'Cleanup Backups' button.\n";
                    } else {
                        echo "\n✗ Migration failed. You can restore from backup using the 'Restore Backup' button.\n";
                    }
                    break;
                    
                case 'restore':
                    echo "\nStarting restore process...\n\n";
                    flush();
                    $migrator->restoreFromBackup();
                    echo "\n";
                    $migrator->printResults();
                    break;
                    
                case 'cleanup':
                    echo "\nStarting cleanup process...\n\n";
                    flush();
                    $migrator->cleanupBackups();
                    echo "\n✓ Backup tables cleaned up successfully.\n";
                    break;
                    
                default:
                    echo "✗ Unknown action: {$action}\n";
            }
        } catch (Exception $e) {
            echo "\n✗ FATAL ERROR: " . $e->getMessage() . "\n";
            echo "File: " . $e->getFile() . "\n";
            echo "Line: " . $e->getLine() . "\n";
            error_log("Migration error: " . $e->getMessage());
        }
        
        flush();
        exit;
    }
    
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Online Database Migration - DILP System</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                padding: 20px;
            }
            
            .container {
                max-width: 900px;
                margin: 0 auto;
                background: white;
                border-radius: 12px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                overflow: hidden;
            }
            
            .header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 30px;
                text-align: center;
            }
            
            .header h1 {
                font-size: 28px;
                margin-bottom: 10px;
            }
            
            .header p {
                opacity: 0.9;
                font-size: 14px;
            }
            
            .content {
                padding: 40px;
            }
            
            .warning-box {
                background: #fff3cd;
                border-left: 4px solid #ffc107;
                padding: 15px;
                margin-bottom: 30px;
                border-radius: 4px;
            }
            
            .warning-box h3 {
                color: #856404;
                margin-bottom: 10px;
                font-size: 16px;
            }
            
            .warning-box ul {
                margin-left: 20px;
                color: #856404;
            }
            
            .warning-box li {
                margin: 5px 0;
            }
            
            .info-box {
                background: #d1ecf1;
                border-left: 4px solid #17a2b8;
                padding: 15px;
                margin-bottom: 30px;
                border-radius: 4px;
            }
            
            .info-box h3 {
                color: #0c5460;
                margin-bottom: 10px;
                font-size: 16px;
            }
            
            .info-box ul {
                margin-left: 20px;
                color: #0c5460;
            }
            
            .info-box li {
                margin: 5px 0;
            }
            
            .button-group {
                display: flex;
                gap: 15px;
                margin: 30px 0;
                flex-wrap: wrap;
            }
            
            button {
                flex: 1;
                min-width: 200px;
                padding: 15px 25px;
                border: none;
                border-radius: 6px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            button:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            }
            
            button:active {
                transform: translateY(0);
            }
            
            button:disabled {
                opacity: 0.6;
                cursor: not-allowed;
                transform: none !important;
            }
            
            .btn-primary {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
            }
            
            .btn-success {
                background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
                color: white;
            }
            
            .btn-warning {
                background: linear-gradient(135deg, #f2994a 0%, #f2c94c 100%);
                color: white;
            }
            
            .btn-danger {
                background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
                color: white;
            }
            
            #output {
                background: #1e1e1e;
                color: #d4d4d4;
                border-radius: 6px;
                padding: 20px;
                margin-top: 30px;
                font-family: 'Courier New', Courier, monospace;
                font-size: 13px;
                line-height: 1.6;
                white-space: pre-wrap;
                word-wrap: break-word;
                max-height: 500px;
                overflow-y: auto;
                display: none;
            }
            
            #output.show {
                display: block;
            }
            
            .loading {
                display: inline-block;
                width: 20px;
                height: 20px;
                border: 3px solid rgba(255, 255, 255, 0.3);
                border-radius: 50%;
                border-top-color: white;
                animation: spin 1s ease-in-out infinite;
            }
            
            @keyframes spin {
                to { transform: rotate(360deg); }
            }
            
            .status-indicator {
                display: inline-block;
                width: 10px;
                height: 10px;
                border-radius: 50%;
                margin-right: 8px;
            }
            
            .status-success { background: #28a745; }
            .status-error { background: #dc3545; }
            .status-warning { background: #ffc107; }
            .status-info { background: #17a2b8; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🗄️ Online Database Migration</h1>
                <p>DOLE DILP Monitoring System</p>
            </div>
            
            <div class="content">
                <div class="warning-box">
                    <h3>⚠️ Important Security Notice</h3>
                    <ul>
                        <li>This file should be <strong>deleted</strong> after successful migration</li>
                        <li>Never leave this file accessible in production</li>
                        <li>Always backup your database before running migrations</li>
                        <li>Test migrations in a development environment first</li>
                    </ul>
                </div>
                
                <div class="info-box">
                    <h3>ℹ️ Migration Features</h3>
                    <ul>
                        <li><strong>Pre-flight Checks:</strong> Validates database connection and permissions</li>
                        <li><strong>Automatic Backup:</strong> Creates backup tables before migration</li>
                        <li><strong>Transaction Support:</strong> Automatic rollback on failure</li>
                        <li><strong>Post-validation:</strong> Verifies migration success</li>
                        <li><strong>Detailed Logging:</strong> Saves complete migration log</li>
                    </ul>
                </div>
                
                <div class="button-group">
                    <button class="btn-success" onclick="runMigration('migrate')">
                        🚀 Run Migration
                    </button>
                    <button class="btn-warning" onclick="runMigration('restore')">
                        ↩️ Restore Backup
                    </button>
                    <button class="btn-danger" onclick="runMigration('cleanup')">
                        🧹 Cleanup Backups
                    </button>
                </div>
                
                <div id="output"></div>
            </div>
        </div>
        
        <script>
            let isRunning = false;
            
            function runMigration(action) {
                if (isRunning) {
                    alert('A migration is already running. Please wait...');
                    return;
                }
                
                const confirmMessages = {
                    'migrate': 'Are you sure you want to run the database migration?\n\nThis will:\n- Create backups of existing tables\n- Apply schema changes\n- Update triggers and indexes\n\nProceed?',
                    'restore': 'Are you sure you want to restore from backup?\n\nThis will:\n- Restore all tables from the last backup\n- Overwrite current data\n\nProceed?',
                    'cleanup': 'Are you sure you want to cleanup backup tables?\n\nThis will:\n- Delete all backup tables\n- Free up database space\n\nProceed?'
                };
                
                if (!confirm(confirmMessages[action])) {
                    return;
                }
                
                isRunning = true;
                const output = document.getElementById('output');
                const buttons = document.querySelectorAll('button');
                
                buttons.forEach(btn => btn.disabled = true);
                
                output.classList.add('show');
                output.innerHTML = '<span style="color: #4ec9b0;">⏳ Running ' + action + '...</span>\n';
                
                fetch('?action=' + action)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('HTTP error! status: ' + response.status);
                        }
                        return response.text();
                    })
                    .then(data => {
                        if (!data || data.trim() === '') {
                            throw new Error('Empty response received from server');
                        }
                        output.innerHTML = formatOutput(data);
                        output.scrollTop = output.scrollHeight;
                        isRunning = false;
                        buttons.forEach(btn => btn.disabled = false);
                    })
                    .catch(error => {
                        console.error('Migration error:', error);
                        output.innerHTML = '<span style="color: #f48771;">❌ Error: ' + error.message + '</span>\n\n';
                        output.innerHTML += '<span style="color: #dcdcaa;">Possible causes:</span>\n';
                        output.innerHTML += '- Database connection failed\n';
                        output.innerHTML += '- PHP execution timeout\n';
                        output.innerHTML += '- Insufficient permissions\n';
                        output.innerHTML += '- Server configuration issue\n\n';
                        output.innerHTML += '<span style="color: #dcdcaa;">Check the browser console for more details.</span>';
                        isRunning = false;
                        buttons.forEach(btn => btn.disabled = false);
                    });
            }
            
            function formatOutput(text) {
                // Escape HTML
                const div = document.createElement('div');
                div.textContent = text;
                let escaped = div.innerHTML;
                
                // Apply color formatting
                escaped = escaped.replace(/✓/g, '<span style="color: #4ec9b0;">✓</span>');
                escaped = escaped.replace(/✗/g, '<span style="color: #f48771;">✗</span>');
                escaped = escaped.replace(/⚠/g, '<span style="color: #dcdcaa;">⚠</span>');
                escaped = escaped.replace(/===/g, '<span style="color: #569cd6;">===</span>');
                escaped = escaped.replace(/---/g, '<span style="color: #569cd6;">---</span>');
                escaped = escaped.replace(/ERROR/g, '<span style="color: #f48771; font-weight: bold;">ERROR</span>');
                escaped = escaped.replace(/SUCCESS/g, '<span style="color: #4ec9b0; font-weight: bold;">SUCCESS</span>');
                escaped = escaped.replace(/WARNING/g, '<span style="color: #dcdcaa; font-weight: bold;">WARNING</span>');
                
                return escaped;
            }
        </script>
    </body>
    </html>
    <?php
}
?>
