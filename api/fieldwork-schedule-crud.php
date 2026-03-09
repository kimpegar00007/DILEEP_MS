<?php
// api/fieldwork-schedule-crud.php
// CRUD API for Schedule of Activities / Fieldwork module

session_start();
require_once '../config/database.php';
require_once '../includes/Auth.php';
require_once '../models/FieldworkSchedule.php';

$auth = new Auth();
$auth->requireLogin();

header('Content-Type: application/json');

$model = new FieldworkSchedule();
$action = $_REQUEST['action'] ?? '';
$userRole = $_SESSION['role'] ?? 'user';
$userId = $_SESSION['user_id'] ?? null;

// Auto-update statuses on every API call
$model->autoUpdateStatuses();

try {
    switch ($action) {

        case 'list':
            $filters = [];
            if (!empty($_GET['status'])) {
                $filters['status'] = $_GET['status'];
            }
            if (!empty($_GET['assigned_user_id'])) {
                $filters['assigned_user_id'] = $_GET['assigned_user_id'];
            }
            if (!empty($_GET['date_from'])) {
                $filters['date_from'] = $_GET['date_from'];
            }
            if (!empty($_GET['date_to'])) {
                $filters['date_to'] = $_GET['date_to'];
            }
            if (!empty($_GET['search'])) {
                $filters['search'] = $_GET['search'];
            }

            $activities = $model->getAll($filters);

            echo json_encode([
                'success' => true,
                'data' => $activities,
                'count' => count($activities)
            ]);
            break;

        case 'get':
            $id = (int) ($_GET['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid activity ID']);
                break;
            }

            $activity = $model->findById($id);
            if (!$activity) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Activity not found']);
                break;
            }

            echo json_encode(['success' => true, 'data' => $activity]);
            break;

        case 'calendar':
            $start = $_GET['start'] ?? date('Y-m-01');
            $end = $_GET['end'] ?? date('Y-m-t');

            $events = $model->getCalendarEvents($start, $end);

            $calendarEvents = [];
            foreach ($events as $event) {
                $colorMap = [
                    'pending'   => '#ffc107',
                    'ongoing'   => '#0d6efd',
                    'completed' => '#198754',
                    'missed'    => '#dc3545'
                ];

                $endDate = $event['end_date'] ?? $event['start_date'];
                // FullCalendar end date is exclusive, so add 1 day
                $endDateObj = new DateTime($endDate);
                $endDateObj->add(new DateInterval('P1D'));

                $calendarEvents[] = [
                    'id'              => $event['id'],
                    'title'           => $event['title'],
                    'start'           => $event['start_date'],
                    'end'             => $endDateObj->format('Y-m-d'),
                    'backgroundColor' => $colorMap[$event['status']] ?? '#6c757d',
                    'borderColor'     => $colorMap[$event['status']] ?? '#6c757d',
                    'textColor'       => $event['status'] === 'pending' ? '#212529' : '#ffffff',
                    'extendedProps'   => [
                        'description'   => $event['description'],
                        'location'      => $event['location'],
                        'status'        => $event['status'],
                        'assigned_user' => $event['assigned_user_name'],
                        'start_date'    => $event['start_date'],
                        'end_date'      => $event['end_date']
                    ]
                ];
            }

            echo json_encode($calendarEvents);
            break;

        case 'create':
            requireEditPermission($userRole);

            $data = getPostData();
            $errors = validateActivityData($data);
            if (!empty($errors)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'errors' => $errors]);
                break;
            }

            $result = $model->create($data);
            if ($result) {
                echo json_encode(['success' => true, 'id' => $result, 'message' => 'Activity created successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $model->getLastError() ?: 'Failed to create activity']);
            }
            break;

        case 'update':
            requireEditPermission($userRole);

            $id = (int) ($_POST['id'] ?? ($_REQUEST['id'] ?? 0));
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid activity ID']);
                break;
            }

            $data = getPostData();
            $errors = validateActivityData($data);
            if (!empty($errors)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'errors' => $errors]);
                break;
            }

            $result = $model->update($id, $data);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Activity updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $model->getLastError() ?: 'Failed to update activity']);
            }
            break;

        case 'update_status':
            requireEditPermission($userRole);

            $id = (int) ($_POST['id'] ?? ($_REQUEST['id'] ?? 0));
            $status = $_POST['status'] ?? ($_REQUEST['status'] ?? '');

            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid activity ID']);
                break;
            }

            $result = $model->updateStatus($id, $status);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Status updated to ' . $status]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $model->getLastError() ?: 'Failed to update status']);
            }
            break;

        case 'delete':
            requireEditPermission($userRole);

            $id = (int) ($_POST['id'] ?? ($_REQUEST['id'] ?? 0));
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid activity ID']);
                break;
            }

            $result = $model->delete($id);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Activity deleted successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $model->getLastError() ?: 'Failed to delete activity']);
            }
            break;

        case 'statistics':
            $stats = $model->getStatistics();
            echo json_encode(['success' => true, 'data' => $stats]);
            break;

        case 'overdue':
            $overdue = $model->getOverdueActivities();
            echo json_encode(['success' => true, 'data' => $overdue, 'count' => count($overdue)]);
            break;

        case 'users':
            $users = $model->getAllUsers();
            echo json_encode(['success' => true, 'data' => $users]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action: ' . htmlspecialchars($action)]);
            break;
    }
} catch (Exception $e) {
    error_log("[Fieldwork API] Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An unexpected error occurred',
        'message' => $e->getMessage()
    ]);
}

/**
 * Enforce edit permissions (admin or encoder only)
 */
function requireEditPermission($role) {
    if (!in_array($role, ['admin', 'encoder'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'You do not have permission to perform this action']);
        exit;
    }
}

/**
 * Extract and sanitize POST data for activity creation/update
 */
function getPostData() {
    return [
        'title'            => trim($_POST['title'] ?? ''),
        'description'      => trim($_POST['description'] ?? ''),
        'location'         => trim($_POST['location'] ?? ''),
        'assigned_user_id' => (int) ($_POST['assigned_user_id'] ?? 0),
        'start_date'       => trim($_POST['start_date'] ?? ''),
        'end_date'         => trim($_POST['end_date'] ?? ''),
        'status'           => trim($_POST['status'] ?? '')
    ];
}

/**
 * Validate activity data and return an array of errors
 */
function validateActivityData($data) {
    $errors = [];

    if (empty($data['title'])) {
        $errors[] = 'Activity title is required';
    } elseif (strlen($data['title']) > 255) {
        $errors[] = 'Activity title must not exceed 255 characters';
    }

    if (empty($data['assigned_user_id']) || $data['assigned_user_id'] <= 0) {
        $errors[] = 'Assigned user is required';
    }

    if (empty($data['start_date'])) {
        $errors[] = 'Start date is required';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['start_date'])) {
        $errors[] = 'Invalid start date format';
    }

    if (!empty($data['end_date'])) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['end_date'])) {
            $errors[] = 'Invalid end date format';
        } elseif ($data['end_date'] < $data['start_date']) {
            $errors[] = 'End date must be on or after start date';
        }
    }

    if (!empty($data['location']) && strlen($data['location']) > 500) {
        $errors[] = 'Location must not exceed 500 characters';
    }

    return $errors;
}
