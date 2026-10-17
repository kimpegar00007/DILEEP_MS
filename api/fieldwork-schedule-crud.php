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
            $statusFilter = (string) ($_GET['status'] ?? '');
            if ($statusFilter !== '') {
                if (!in_array($statusFilter, array_merge(FieldworkSchedule::STATUSES, ['overdue']), true)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Invalid status filter']);
                    break;
                }
                $filters['status'] = $statusFilter;
            }
            if (!empty($_GET['assigned_user_id'])) {
                $filters['assigned_user_id'] = $_GET['assigned_user_id'];
            }
            foreach (['date_from', 'date_to'] as $dateKey) {
                if (!empty($_GET[$dateKey]) && isValidDate((string) $_GET[$dateKey])) {
                    $filters[$dateKey] = $_GET[$dateKey];
                }
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
            // FullCalendar sends ISO strings (2026-10-01T00:00:00+08:00); only the date part matters.
            $start = substr((string) ($_GET['start'] ?? ''), 0, 10);
            $end   = substr((string) ($_GET['end'] ?? ''), 0, 10);
            if (!isValidDate($start) || !isValidDate($end)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid calendar range']);
                break;
            }

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
                    'classNames'      => $event['is_overdue'] ? ['fieldwork-overdue'] : [],
                    'extendedProps'   => [
                        'is_overdue'    => $event['is_overdue'],
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
                http_response_code($model->getLastErrorStatus());
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
                http_response_code($model->getLastErrorStatus());
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
                echo json_encode(['success' => true, 'message' => 'Status updated to ' . ucfirst($status)]);
            } else {
                http_response_code($model->getLastErrorStatus());
                echo json_encode(['success' => false, 'error' => $model->getLastError() ?: 'Failed to update status']);
            }
            break;

        case 'delete':
            requireEditPermission($userRole);
            // Deletes are admin-only, like every other delete in the system.
            // hasRole() also passes super_admin and regional_director.
            if (!$auth->hasRole(['admin'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Only administrators can delete activities']);
                break;
            }

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
                http_response_code($model->getLastErrorStatus());
                echo json_encode(['success' => false, 'error' => $model->getLastError() ?: 'Failed to delete activity']);
            }
            break;

        case 'statistics':
            $stats = $model->getStatistics();
            echo json_encode(['success' => true, 'data' => $stats]);
            break;

        case 'overdue':
            // The reminder asks people to close activities, so only editors get it.
            if (!canEdit($userRole)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'You do not have permission to perform this action']);
                break;
            }
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
    echo json_encode(['success' => false, 'error' => 'An unexpected error occurred']);
}

/**
 * Enforce edit permissions (admin, regional_director, super_admin, or encoder only).
 * super_admin and regional_director are explicitly included because this function
 * receives the raw $_SESSION['role'] string, bypassing Auth::hasRole().
 */
function canEdit($role) {
    return in_array($role, ['admin', 'encoder', 'super_admin', 'regional_director'], true);
}

function requireEditPermission($role) {
    if (!canEdit($role)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'You do not have permission to perform this action']);
        exit;
    }
    // Writes only over POST, so a plain link or <img> cannot change or delete activities.
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'This action requires POST']);
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

/** True for a real calendar date in YYYY-MM-DD form. */
function isValidDate($value) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return false;
    }
    [$y, $m, $d] = array_map('intval', explode('-', $value));
    return checkdate($m, $d, $y);
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
    } elseif (!isValidDate($data['start_date'])) {
        $errors[] = 'Invalid start date';
    }

    if (!empty($data['end_date'])) {
        if (!isValidDate($data['end_date'])) {
            $errors[] = 'Invalid end date';
        } elseif ($data['end_date'] < $data['start_date']) {
            $errors[] = 'End date must be on or after start date';
        }
    }

    if ($data['status'] !== '' && !in_array($data['status'], FieldworkSchedule::STATUSES, true)) {
        $errors[] = 'Invalid status';
    }

    if (!empty($data['location']) && strlen($data['location']) > 500) {
        $errors[] = 'Location must not exceed 500 characters';
    }

    return $errors;
}
