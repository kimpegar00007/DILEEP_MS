<?php
/**
 * API: Get proponents for dropdown/search in beneficiary form.
 * Returns JSON array of { id, label } objects.
 * Supports optional ?search= query parameter for filtering.
 * Province-scoped: returns only proponents accessible to the logged-in user.
 */
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance()->getConnection();

$role     = $_SESSION['role']     ?? '';
$province = $_SESSION['province'] ?? null;

// NOTE: `proponents` has no `municipality` column — do not select it.
$sql    = "SELECT id, proponent_name, project_title, province
           FROM proponents WHERE 1=1";
$params = [];

// Province scope
$crossProvinceRoles = ['super_admin', 'regional_director'];
if (!in_array($role, $crossProvinceRoles, true)) {
    if ($province === null) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }
    $sql     .= " AND province = ?";
    $params[] = $province;
}

// Optional search filter
$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $sql     .= " AND (proponent_name LIKE ? OR project_title LIKE ?)";
    $term     = '%' . $search . '%';
    $params[] = $term;
    $params[] = $term;
}

$sql .= " ORDER BY proponent_name ASC LIMIT 100";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = array_map(function ($row) {
        $label = $row['proponent_name'] . ' — ' . $row['project_title'];
        if (!empty($row['province'])) {
            $label .= ' (' . $row['province'] . ')';
        }
        return [
            'id'    => (int) $row['id'],
            'label' => $label,
        ];
    }, $rows);

    echo json_encode(['success' => true, 'data' => $data]);
} catch (PDOException $e) {
    error_log('[get-proponents API] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
