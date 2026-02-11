<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$table = $_POST['table'] ?? '';
$allowedTables = ['beneficiaries', 'proponents'];

if (!in_array($table, $allowedTables, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid target table.']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit.',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds form upload limit.',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Server temporary folder missing.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION  => 'Upload blocked by server extension.',
    ];
    $errorCode = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
    $msg = $uploadErrors[$errorCode] ?? 'Unknown upload error.';
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

$file = $_FILES['file'];

// Validate file extension
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if ($ext !== 'csv') {
    echo json_encode(['success' => false, 'message' => 'Invalid file format. Only CSV files are accepted.']);
    exit;
}

// Validate file size (5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 5MB.']);
    exit;
}

// Validate MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);
$allowedMimes = ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'];
if (!in_array($mimeType, $allowedMimes, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type detected (' . $mimeType . '). Please upload a valid CSV file.']);
    exit;
}

// Define expected columns per table
$columnDefs = [
    'beneficiaries' => [
        'required' => ['last_name', 'first_name', 'gender', 'barangay', 'municipality', 'project_name', 'amount_worth'],
        'optional' => ['middle_name', 'suffix', 'contact_number', 'type_of_worker', 'noted_findings',
                       'date_complied_by_proponent', 'date_forwarded_to_ro6', 'rpmt_findings', 'date_approved',
                       'date_forwarded_to_nofo', 'date_turnover', 'date_monitoring', 'latitude', 'longitude', 'status'],
        'defaults' => ['status' => 'pending'],
        'date_columns' => ['date_complied_by_proponent', 'date_forwarded_to_ro6', 'date_approved',
                           'date_forwarded_to_nofo', 'date_turnover', 'date_monitoring'],
        'numeric_columns' => ['amount_worth', 'latitude', 'longitude'],
        'enum_columns' => [
            'gender' => ['Male', 'Female'],
            'status' => ['pending', 'approved', 'implemented', 'monitored']
        ]
    ],
    'proponents' => [
        'required' => ['proponent_type', 'proponent_name', 'project_title', 'amount', 'total_beneficiaries', 'category'],
        'optional' => ['date_received', 'noted_findings', 'control_number', 'number_of_copies', 'date_copies_received',
                       'district', 'number_of_associations', 'male_beneficiaries', 'female_beneficiaries',
                       'type_of_beneficiaries', 'recipient_barangays', 'letter_of_intent_date',
                       'date_forwarded_to_ro6', 'rpmt_findings', 'date_complied_by_proponent',
                       'date_complied_by_proponent_nofo', 'date_forwarded_to_nofo', 'date_approved',
                       'date_check_release', 'check_number', 'check_date_issued', 'or_number', 'or_date_issued',
                       'date_turnover', 'date_implemented', 'date_liquidated', 'liquidation_deadline',
                       'date_monitoring', 'source_of_funds', 'latitude', 'longitude', 'status'],
        'defaults' => ['status' => 'pending'],
        'date_columns' => ['date_received', 'date_copies_received', 'letter_of_intent_date', 'date_forwarded_to_ro6',
                           'date_complied_by_proponent', 'date_complied_by_proponent_nofo', 'date_forwarded_to_nofo',
                           'date_approved', 'date_check_release', 'check_date_issued', 'or_date_issued',
                           'date_turnover', 'date_implemented', 'date_liquidated', 'liquidation_deadline', 'date_monitoring'],
        'numeric_columns' => ['amount', 'number_of_copies', 'number_of_associations', 'total_beneficiaries',
                              'male_beneficiaries', 'female_beneficiaries', 'latitude', 'longitude'],
        'enum_columns' => [
            'proponent_type' => ['LGU-associated', 'Non-LGU-associated'],
            'category' => ['Formation', 'Enhancement', 'Restoration'],
            'status' => ['pending', 'approved', 'implemented', 'liquidated', 'monitored']
        ]
    ]
];

$def = $columnDefs[$table];

// Parse CSV
$handle = fopen($file['tmp_name'], 'r');
if (!$handle) {
    echo json_encode(['success' => false, 'message' => 'Failed to read the uploaded file.']);
    exit;
}

// Read header row
$headers = fgetcsv($handle);
if (!$headers) {
    fclose($handle);
    echo json_encode(['success' => false, 'message' => 'Could not read CSV headers. The file may be empty or malformed.']);
    exit;
}

// Strip BOM from first header if present
$headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);

// Normalize headers (trim, lowercase)
$headers = array_map(function($h) { return strtolower(trim($h)); }, $headers);

// Validate required columns exist
$missingCols = [];
foreach ($def['required'] as $reqCol) {
    if (!in_array($reqCol, $headers, true)) {
        $missingCols[] = $reqCol;
    }
}

if (!empty($missingCols)) {
    fclose($handle);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required columns: ' . implode(', ', $missingCols),
        'errors' => ['Expected columns: ' . implode(', ', $def['required'])]
    ]);
    exit;
}

// Determine which columns to import (intersection of CSV headers and known columns)
$allKnownCols = array_merge($def['required'], $def['optional']);
$importCols = array_values(array_intersect($headers, $allKnownCols));

// Read and validate all rows
$rows = [];
$errors = [];
$lineNum = 1; // header is line 1

while (($csvRow = fgetcsv($handle)) !== false) {
    $lineNum++;

    // Skip completely empty rows
    if (count($csvRow) === 1 && trim($csvRow[0]) === '') continue;
    if (empty(array_filter($csvRow, function($v) { return trim($v) !== ''; }))) continue;

    // Map CSV columns to associative array
    $rowData = [];
    foreach ($headers as $idx => $colName) {
        $rowData[$colName] = isset($csvRow[$idx]) ? trim($csvRow[$idx]) : '';
    }

    $rowErrors = [];

    // Validate required fields
    foreach ($def['required'] as $reqCol) {
        if (empty($rowData[$reqCol])) {
            $rowErrors[] = "Row $lineNum: '$reqCol' is required but empty.";
        }
    }

    // Validate enum columns
    foreach ($def['enum_columns'] as $col => $allowed) {
        if (!empty($rowData[$col]) && !in_array($rowData[$col], $allowed, true)) {
            $rowErrors[] = "Row $lineNum: '$col' value '" . $rowData[$col] . "' is invalid. Allowed: " . implode(', ', $allowed);
        }
    }

    // Validate numeric columns
    foreach ($def['numeric_columns'] as $col) {
        if (!empty($rowData[$col]) && !is_numeric($rowData[$col])) {
            $rowErrors[] = "Row $lineNum: '$col' must be a number, got '" . $rowData[$col] . "'.";
        }
    }

    // Validate date columns
    foreach ($def['date_columns'] as $col) {
        if (!empty($rowData[$col])) {
            $dateVal = $rowData[$col];
            $parsed = date_create($dateVal);
            if (!$parsed) {
                $rowErrors[] = "Row $lineNum: '$col' has invalid date format '" . $dateVal . "'.";
            } else {
                $rowData[$col] = $parsed->format('Y-m-d');
            }
        }
    }

    if (!empty($rowErrors)) {
        $errors = array_merge($errors, $rowErrors);
        if (count($errors) > 50) {
            $errors[] = '... additional errors truncated. Please fix the above issues first.';
            break;
        }
        continue;
    }

    // Apply defaults
    foreach ($def['defaults'] as $col => $defaultVal) {
        if (empty($rowData[$col])) {
            $rowData[$col] = $defaultVal;
        }
    }

    // Sanitize: convert empty strings to null for optional fields
    foreach ($def['optional'] as $col) {
        if (isset($rowData[$col]) && $rowData[$col] === '') {
            $rowData[$col] = null;
        }
    }

    $rows[] = $rowData;
}

fclose($handle);

if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => 'Validation failed for ' . count($errors) . ' issue(s). No records were imported.',
        'errors' => array_slice($errors, 0, 20)
    ]);
    exit;
}

if (empty($rows)) {
    echo json_encode(['success' => false, 'message' => 'No valid data rows found in the CSV file.']);
    exit;
}

// Build INSERT query
try {
    $db = Database::getInstance()->getConnection();
    $db->beginTransaction();

    // Add created_by, updated_by to import columns
    $insertCols = $importCols;
    $insertCols[] = 'created_by';
    $insertCols[] = 'updated_by';

    $placeholders = implode(', ', array_fill(0, count($insertCols), '?'));
    $colNames = implode(', ', array_map(function($c) { return '`' . $c . '`'; }, $insertCols));

    $sql = "INSERT INTO `$table` ($colNames) VALUES ($placeholders)";
    $stmt = $db->prepare($sql);

    $insertedCount = 0;
    $userId = $_SESSION['user_id'] ?? null;

    foreach ($rows as $row) {
        $params = [];
        foreach ($importCols as $col) {
            $params[] = $row[$col] ?? null;
        }
        $params[] = $userId; // created_by
        $params[] = $userId; // updated_by

        $stmt->execute($params);
        $insertedCount++;
    }

    // Log the import activity
    $logStmt = $db->prepare(
        "INSERT INTO activity_logs (user_id, action, table_name, record_id, description, ip_address) 
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $logStmt->execute([
        $userId,
        'import',
        $table,
        0,
        'Imported ' . $insertedCount . ' records into ' . $table . ' from CSV',
        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Successfully imported ' . $insertedCount . ' records into ' . $table . '.'
    ]);

} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[Settings Import] Database error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error during import. No records were saved.',
        'errors' => ['Please check the CSV data matches the expected format and try again.']
    ]);
}
