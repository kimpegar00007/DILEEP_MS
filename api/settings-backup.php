<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Auth.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requireRole('admin');

$action = $_GET['action'] ?? '';

if ($action !== 'download') {
    http_response_code(400);
    echo 'Invalid action.';
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $dbName = DB_NAME;
    $filename = $dbName . '_backup_' . date('Y-m-d_His') . '.sql';

    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    // Write header
    fwrite($output, "-- Database Backup: $dbName\n");
    fwrite($output, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
    fwrite($output, "-- Server: " . DB_HOST . "\n");
    fwrite($output, "-- PHP Version: " . phpversion() . "\n");
    fwrite($output, "-- --------------------------------------------------------\n\n");
    fwrite($output, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
    fwrite($output, "START TRANSACTION;\n");
    fwrite($output, "SET time_zone = \"+00:00\";\n\n");
    fwrite($output, "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n");
    fwrite($output, "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n");
    fwrite($output, "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n");
    fwrite($output, "/*!40101 SET NAMES utf8mb4 */;\n\n");

    // Get all tables
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $tableName) {
        fwrite($output, "-- --------------------------------------------------------\n");
        fwrite($output, "-- Table structure for table `$tableName`\n");
        fwrite($output, "-- --------------------------------------------------------\n\n");

        // Drop table if exists
        fwrite($output, "DROP TABLE IF EXISTS `$tableName`;\n");

        // Get CREATE TABLE statement
        $createStmt = $db->query("SHOW CREATE TABLE `$tableName`")->fetch();
        fwrite($output, $createStmt['Create Table'] . ";\n\n");

        // Get table data
        $rowCount = $db->query("SELECT COUNT(*) FROM `$tableName`")->fetchColumn();

        if ($rowCount > 0) {
            fwrite($output, "-- Dumping data for table `$tableName` ($rowCount rows)\n\n");

            // Get column info for proper escaping
            $columns = $db->query("SHOW COLUMNS FROM `$tableName`")->fetchAll();
            $colNames = array_map(function($col) { return '`' . $col['Field'] . '`'; }, $columns);
            $colNameStr = implode(', ', $colNames);

            // Fetch data in chunks to handle large tables
            $chunkSize = 500;
            $offset = 0;

            while ($offset < $rowCount) {
                $dataStmt = $db->query("SELECT * FROM `$tableName` LIMIT $chunkSize OFFSET $offset");
                $dataRows = $dataStmt->fetchAll(PDO::FETCH_NUM);

                if (empty($dataRows)) break;

                fwrite($output, "INSERT INTO `$tableName` ($colNameStr) VALUES\n");

                $rowStrings = [];
                foreach ($dataRows as $row) {
                    $values = array_map(function($val) use ($db) {
                        if ($val === null) return 'NULL';
                        return $db->quote($val);
                    }, $row);
                    $rowStrings[] = '(' . implode(', ', $values) . ')';
                }

                fwrite($output, implode(",\n", $rowStrings) . ";\n\n");
                $offset += $chunkSize;
            }
        }
    }

    // Get triggers
    $triggers = $db->query("SHOW TRIGGERS")->fetchAll();
    if (!empty($triggers)) {
        fwrite($output, "-- --------------------------------------------------------\n");
        fwrite($output, "-- Triggers\n");
        fwrite($output, "-- --------------------------------------------------------\n\n");
        fwrite($output, "DELIMITER \$\$\n");

        foreach ($triggers as $trigger) {
            $triggerName = $trigger['Trigger'];
            $createTrigger = $db->query("SHOW CREATE TRIGGER `$triggerName`")->fetch();
            $triggerSql = $createTrigger['SQL Original Statement'] ?? '';
            if ($triggerSql) {
                fwrite($output, "DROP TRIGGER IF EXISTS `$triggerName`\$\$\n");
                fwrite($output, $triggerSql . "\$\$\n\n");
            }
        }

        fwrite($output, "DELIMITER ;\n\n");
    }

    fwrite($output, "COMMIT;\n\n");
    fwrite($output, "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n");
    fwrite($output, "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n");
    fwrite($output, "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n");

    fclose($output);

    // Log the backup activity (separate connection since output is already sent)
    try {
        $logDb = Database::getInstance()->getConnection();
        $logStmt = $logDb->prepare(
            "INSERT INTO activity_logs (user_id, action, table_name, record_id, description, ip_address) 
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $logStmt->execute([
            $_SESSION['user_id'],
            'backup',
            'system',
            0,
            'Downloaded full database backup (' . count($tables) . ' tables)',
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    } catch (PDOException $e) {
        error_log('[Settings Backup] Failed to log activity: ' . $e->getMessage());
    }

} catch (PDOException $e) {
    error_log('[Settings Backup] Database error: ' . $e->getMessage());
    http_response_code(500);
    echo 'An error occurred while generating the backup.';
}
