<?php
session_start();
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/Auth.php';
require_once dirname(__DIR__) . '/models/Proponent.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Returns are edited from proponent-form.php, which is admin/encoder only.
if (!$auth->hasRole(['admin', 'encoder'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You do not have permission to change returns']);
    exit;
}

$proponentModel = new Proponent();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $proponentId = filter_var($input['proponent_id'] ?? 0, FILTER_VALIDATE_INT);
    $returnDate = $input['return_date'] ?? '';
    $reason = trim($input['reason'] ?? '');
    
    if (!$proponentId || !$returnDate) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Proponent ID and return date are required']);
        exit;
    }
    
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $returnDate)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid date format']);
        exit;
    }
    
    // findById() is province-scoped: a proponent outside the user's province is "not found".
    if (!$proponentModel->findById($proponentId)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Proponent not found']);
        exit;
    }

    $result = $proponentModel->addReturn($proponentId, $returnDate, $reason, $_SESSION['user_id']);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Return entry added successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to add return entry']);
    }
    
} elseif ($method === 'DELETE') {
    $returnId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if (!$returnId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Return ID is required']);
        exit;
    }
    
    // The return must belong to a proponent the user can see (province scope).
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT proponent_id FROM proponent_returns WHERE id = ?");
    $stmt->execute([$returnId]);
    $returnProponentId = $stmt->fetchColumn();
    if (!$returnProponentId || !$proponentModel->findById($returnProponentId)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Return entry not found']);
        exit;
    }

    $result = $proponentModel->deleteReturn($returnId);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Return entry deleted']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete return entry']);
    }
    
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
