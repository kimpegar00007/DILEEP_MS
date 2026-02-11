<?php
/**
 * Migration Runner for Non-LGU Fix
 * 
 * This script applies database migrations without requiring mysql command-line tool.
 * It can be run from the command line or accessed via web browser.
 * 
 * Usage: php run-migrations.php [--fix-trigger] [--fix-records] [--all]
 */

require_once __DIR__ . '/config/database.php';

class MigrationRunner {
    private $db;
    private $errors = [];
    private $successes = [];
    
    public function __construct() {
        try {
            $this->db = Database::getInstance()->getConnection();
        } catch (Exception $e) {
            die("Failed to connect to database: " . $e->getMessage());
        }
    }
    
    /**
     * Apply the trigger fix for Non-LGU proponent type changes
     */
    public function fixTrigger() {
        try {
            // Drop existing trigger
            $this->db->exec("DROP TRIGGER IF EXISTS update_liquidation_deadline");
            
            // Create new trigger with proponent_type condition
            $triggerSQL = <<<SQL
CREATE TRIGGER update_liquidation_deadline 
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
END
SQL;
            
            $this->db->exec($triggerSQL);
            $this->successes[] = "✓ Trigger 'update_liquidation_deadline' successfully updated";
            return true;
        } catch (PDOException $e) {
            $this->errors[] = "✗ Failed to update trigger: " . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Fix existing liquidation deadlines for all proponents
     */
    public function fixExistingRecords() {
        try {
            $updateSQL = <<<SQL
UPDATE proponents 
SET liquidation_deadline = CASE 
    WHEN proponent_type = 'LGU-associated' THEN DATE_ADD(date_turnover, INTERVAL 10 DAY)
    ELSE DATE_ADD(date_turnover, INTERVAL 60 DAY)
END
WHERE date_turnover IS NOT NULL
SQL;
            
            $stmt = $this->db->prepare($updateSQL);
            $stmt->execute();
            $rowCount = $stmt->rowCount();
            
            $this->successes[] = "✓ Fixed liquidation deadlines for {$rowCount} existing proponent records";
            return true;
        } catch (PDOException $e) {
            $this->errors[] = "✗ Failed to fix existing records: " . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Run all migrations
     */
    public function runAll() {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "Non-LGU Proponent Fix - Database Migration Runner\n";
        echo str_repeat("=", 60) . "\n\n";
        
        echo "Step 1: Updating trigger...\n";
        $triggerSuccess = $this->fixTrigger();
        
        echo "Step 2: Fixing existing records...\n";
        $recordsSuccess = $this->fixExistingRecords();
        
        $this->printResults();
        
        return $triggerSuccess && $recordsSuccess;
    }
    
    /**
     * Print migration results
     */
    private function printResults() {
        echo "\n" . str_repeat("-", 60) . "\n";
        echo "MIGRATION RESULTS\n";
        echo str_repeat("-", 60) . "\n\n";
        
        if (!empty($this->successes)) {
            echo "Successes:\n";
            foreach ($this->successes as $msg) {
                echo "  {$msg}\n";
            }
            echo "\n";
        }
        
        if (!empty($this->errors)) {
            echo "Errors:\n";
            foreach ($this->errors as $msg) {
                echo "  {$msg}\n";
            }
            echo "\n";
        }
        
        if (empty($this->errors)) {
            echo "✓ All migrations completed successfully!\n\n";
            echo "Next Steps:\n";
            echo "1. Log into the system\n";
            echo "2. Create a new Non-LGU-associated proponent with a turnover date\n";
            echo "3. Verify the liquidation deadline is set to 60 days from turnover\n";
            echo "4. Edit an existing LGU proponent and change it to Non-LGU\n";
            echo "5. Verify the deadline updates to 60 days\n";
        } else {
            echo "✗ Some migrations failed. Please review the errors above.\n";
        }
        
        echo "\n" . str_repeat("=", 60) . "\n\n";
    }
}

// Determine execution context
$isCommandLine = php_sapi_name() === 'cli';

if ($isCommandLine) {
    // Command-line execution
    $runner = new MigrationRunner();
    
    // Parse command-line arguments
    $args = isset($argv) ? array_slice($argv, 1) : [];
    
    if (empty($args) || in_array('--all', $args)) {
        $success = $runner->runAll();
        exit($success ? 0 : 1);
    } else {
        if (in_array('--fix-trigger', $args)) {
            echo "Applying trigger fix...\n";
            $runner->fixTrigger();
        }
        if (in_array('--fix-records', $args)) {
            echo "Fixing existing records...\n";
            $runner->fixExistingRecords();
        }
        $runner->printResults();
    }
} else {
    // Web browser execution
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Non-LGU Fix - Database Migration</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                max-width: 800px;
                margin: 40px auto;
                padding: 20px;
                background: #f5f5f5;
            }
            .container {
                background: white;
                border-radius: 8px;
                padding: 30px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            h1 {
                color: #333;
                margin-top: 0;
            }
            .button-group {
                display: flex;
                gap: 10px;
                margin: 20px 0;
            }
            button {
                padding: 10px 20px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 500;
                transition: all 0.3s;
            }
            .btn-primary {
                background: #007bff;
                color: white;
            }
            .btn-primary:hover {
                background: #0056b3;
            }
            .btn-success {
                background: #28a745;
                color: white;
            }
            .btn-success:hover {
                background: #218838;
            }
            #output {
                background: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 4px;
                padding: 15px;
                margin-top: 20px;
                font-family: 'Courier New', monospace;
                font-size: 13px;
                line-height: 1.6;
                white-space: pre-wrap;
                word-wrap: break-word;
                display: none;
            }
            .success {
                color: #28a745;
            }
            .error {
                color: #dc3545;
            }
            .info {
                color: #17a2b8;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Non-LGU Proponent Fix - Database Migration</h1>
            <p>This tool applies database migrations to fix the Non-LGU proponent liquidation deadline calculation.</p>
            
            <div class="button-group">
                <button class="btn-success" onclick="runMigration('all')">Run All Migrations</button>
                <button class="btn-primary" onclick="runMigration('trigger')">Fix Trigger Only</button>
                <button class="btn-primary" onclick="runMigration('records')">Fix Records Only</button>
            </div>
            
            <div id="output"></div>
        </div>
        
        <script>
            function runMigration(type) {
                const output = document.getElementById('output');
                output.style.display = 'block';
                output.innerHTML = '<span class="info">Running migration...</span>';
                
                fetch('run-migrations.php?action=' + type)
                    .then(response => response.text())
                    .then(data => {
                        output.innerHTML = data;
                    })
                    .catch(error => {
                        output.innerHTML = '<span class="error">Error: ' + error + '</span>';
                    });
            }
        </script>
    </body>
    </html>
    <?php
}
?>
