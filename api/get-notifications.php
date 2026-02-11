<?php
session_start();
require_once '../config/database.php';
require_once '../includes/Auth.php';

$auth = new Auth();
$auth->requireLogin();

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();
    
    $notifications = [];
    
    // Get overdue liquidations
    $sql = "SELECT 
                id,
                proponent_name,
                project_title,
                proponent_type,
                date_turnover,
                liquidation_deadline,
                DATEDIFF(CURDATE(), liquidation_deadline) as days_overdue
            FROM proponents 
            WHERE liquidation_deadline IS NOT NULL 
                AND date_liquidated IS NULL 
                AND liquidation_deadline < CURDATE()
            ORDER BY liquidation_deadline ASC";
    
    $stmt = $db->query($sql);
    $overdueLiquidations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($overdueLiquidations as $item) {
        $notifications[] = [
            'id' => 'liquidation_' . $item['id'],
            'type' => 'liquidation',
            'title' => 'Overdue Liquidation',
            'message' => $item['proponent_name'] . ' - Liquidation deadline passed',
            'details' => 'Deadline: ' . date('M d, Y', strtotime($item['liquidation_deadline'])) . ' (' . $item['days_overdue'] . ' days overdue)',
            'proponent_id' => $item['id'],
            'proponent_name' => $item['proponent_name'],
            'project_title' => $item['project_title'],
            'deadline' => $item['liquidation_deadline'],
            'days_overdue' => $item['days_overdue'],
            'severity' => 'danger',
            'timestamp' => strtotime($item['liquidation_deadline'])
        ];
    }
    
    // Get upcoming liquidation deadlines (within 14 days)
    $sql = "SELECT 
                id,
                proponent_name,
                project_title,
                proponent_type,
                date_turnover,
                liquidation_deadline,
                DATEDIFF(liquidation_deadline, CURDATE()) as days_remaining
            FROM proponents 
            WHERE liquidation_deadline IS NOT NULL 
                AND date_liquidated IS NULL 
                AND liquidation_deadline >= CURDATE()
                AND liquidation_deadline <= DATE_ADD(CURDATE(), INTERVAL 14 DAY)
            ORDER BY liquidation_deadline ASC";
    
    $stmt = $db->query($sql);
    $upcomingLiquidations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($upcomingLiquidations as $item) {
        $notifications[] = [
            'id' => 'upcoming_liquidation_' . $item['id'],
            'type' => 'upcoming_liquidation',
            'title' => 'Upcoming Liquidation Deadline',
            'message' => $item['proponent_name'] . ' - Liquidation due soon',
            'details' => 'Deadline: ' . date('M d, Y', strtotime($item['liquidation_deadline'])) . ' (' . $item['days_remaining'] . ' day' . ($item['days_remaining'] != 1 ? 's' : '') . ' remaining)',
            'proponent_id' => $item['id'],
            'proponent_name' => $item['proponent_name'],
            'project_title' => $item['project_title'],
            'deadline' => $item['liquidation_deadline'],
            'days_remaining' => $item['days_remaining'],
            'severity' => 'warning',
            'timestamp' => strtotime($item['liquidation_deadline'])
        ];
    }
    
    // Get beneficiaries with upcoming monitoring (within 7 days)
    $sql = "SELECT 
                id,
                CONCAT(first_name, ' ', last_name) as name,
                project_name,
                date_turnover,
                DATE_ADD(date_turnover, INTERVAL 30 DAY) as monitoring_due_date,
                DATEDIFF(DATE_ADD(date_turnover, INTERVAL 30 DAY), CURDATE()) as days_remaining
            FROM beneficiaries 
            WHERE date_turnover IS NOT NULL 
                AND date_monitoring IS NULL 
                AND DATE_ADD(date_turnover, INTERVAL 30 DAY) >= CURDATE()
                AND DATE_ADD(date_turnover, INTERVAL 30 DAY) <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            ORDER BY date_turnover ASC
            LIMIT 20";
    
    $stmt = $db->query($sql);
    $upcomingBeneficiaryMonitoring = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($upcomingBeneficiaryMonitoring as $item) {
        $notifications[] = [
            'id' => 'upcoming_monitoring_beneficiary_' . $item['id'],
            'type' => 'upcoming_monitoring',
            'title' => 'Upcoming Monitoring (Beneficiary)',
            'message' => $item['name'] . ' - Monitoring due soon',
            'details' => 'Expected: ' . date('M d, Y', strtotime($item['monitoring_due_date'])) . ' (' . $item['days_remaining'] . ' day' . ($item['days_remaining'] != 1 ? 's' : '') . ' remaining)',
            'beneficiary_id' => $item['id'],
            'beneficiary_name' => $item['name'],
            'project_name' => $item['project_name'],
            'deadline' => $item['monitoring_due_date'],
            'days_remaining' => $item['days_remaining'],
            'severity' => 'info',
            'timestamp' => strtotime($item['monitoring_due_date'])
        ];
    }
    
    // Get beneficiaries with overdue monitoring (turnover date + 30 days, no monitoring date yet)
    $sql = "SELECT 
                id,
                CONCAT(first_name, ' ', last_name) as name,
                project_name,
                date_turnover,
                DATE_ADD(date_turnover, INTERVAL 30 DAY) as monitoring_due_date,
                DATEDIFF(CURDATE(), DATE_ADD(date_turnover, INTERVAL 30 DAY)) as days_overdue
            FROM beneficiaries 
            WHERE date_turnover IS NOT NULL 
                AND date_monitoring IS NULL 
                AND DATE_ADD(date_turnover, INTERVAL 30 DAY) < CURDATE()
            ORDER BY date_turnover ASC
            LIMIT 20";
    
    $stmt = $db->query($sql);
    $overdueMonitoring = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($overdueMonitoring as $item) {
        $notifications[] = [
            'id' => 'overdue_monitoring_beneficiary_' . $item['id'],
            'type' => 'monitoring',
            'title' => 'Overdue Monitoring (Beneficiary)',
            'message' => $item['name'] . ' - Monitoring overdue',
            'details' => 'Expected: ' . date('M d, Y', strtotime($item['monitoring_due_date'])) . ' (' . $item['days_overdue'] . ' day' . ($item['days_overdue'] != 1 ? 's' : '') . ' overdue)',
            'beneficiary_id' => $item['id'],
            'beneficiary_name' => $item['name'],
            'project_name' => $item['project_name'],
            'deadline' => $item['monitoring_due_date'],
            'days_overdue' => $item['days_overdue'],
            'severity' => 'warning',
            'timestamp' => strtotime($item['monitoring_due_date'])
        ];
    }
    
    // Get proponents with upcoming monitoring (within 14 days of expected monitoring date)
    $sql = "SELECT 
                id,
                proponent_name,
                project_title,
                date_turnover,
                date_monitoring as expected_monitoring_date,
                DATEDIFF(date_monitoring, CURDATE()) as days_remaining
            FROM proponents 
            WHERE date_monitoring IS NOT NULL 
                AND date_monitoring >= CURDATE()
                AND date_monitoring <= DATE_ADD(CURDATE(), INTERVAL 14 DAY)
                AND status != 'monitored'
            ORDER BY date_monitoring ASC
            LIMIT 20";
    
    $stmt = $db->query($sql);
    $upcomingProponentMonitoring = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($upcomingProponentMonitoring as $item) {
        $notifications[] = [
            'id' => 'upcoming_monitoring_proponent_' . $item['id'],
            'type' => 'upcoming_monitoring',
            'title' => 'Upcoming Monitoring (Proponent)',
            'message' => $item['proponent_name'] . ' - Monitoring scheduled soon',
            'details' => 'Scheduled: ' . date('M d, Y', strtotime($item['expected_monitoring_date'])) . ' (' . $item['days_remaining'] . ' day' . ($item['days_remaining'] != 1 ? 's' : '') . ' remaining)',
            'proponent_id' => $item['id'],
            'proponent_name' => $item['proponent_name'],
            'project_title' => $item['project_title'],
            'deadline' => $item['expected_monitoring_date'],
            'days_remaining' => $item['days_remaining'],
            'severity' => 'info',
            'timestamp' => strtotime($item['expected_monitoring_date'])
        ];
    }
    
    // Get proponents with overdue monitoring (date_monitoring passed but status not monitored)
    $sql = "SELECT 
                id,
                proponent_name,
                project_title,
                date_monitoring as expected_monitoring_date,
                DATEDIFF(CURDATE(), date_monitoring) as days_overdue
            FROM proponents 
            WHERE date_monitoring IS NOT NULL 
                AND date_monitoring < CURDATE()
                AND status != 'monitored'
            ORDER BY date_monitoring ASC
            LIMIT 20";
    
    $stmt = $db->query($sql);
    $overdueProponentMonitoring = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($overdueProponentMonitoring as $item) {
        $notifications[] = [
            'id' => 'overdue_monitoring_proponent_' . $item['id'],
            'type' => 'monitoring',
            'title' => 'Overdue Monitoring (Proponent)',
            'message' => $item['proponent_name'] . ' - Monitoring overdue',
            'details' => 'Scheduled: ' . date('M d, Y', strtotime($item['expected_monitoring_date'])) . ' (' . $item['days_overdue'] . ' day' . ($item['days_overdue'] != 1 ? 's' : '') . ' overdue)',
            'proponent_id' => $item['id'],
            'proponent_name' => $item['proponent_name'],
            'project_title' => $item['project_title'],
            'deadline' => $item['expected_monitoring_date'],
            'days_overdue' => $item['days_overdue'],
            'severity' => 'warning',
            'timestamp' => strtotime($item['expected_monitoring_date'])
        ];
    }
    
    // Sort notifications by severity and timestamp
    usort($notifications, function($a, $b) {
        $severityOrder = ['danger' => 0, 'warning' => 1, 'info' => 2];
        $severityCompare = $severityOrder[$a['severity']] - $severityOrder[$b['severity']];
        if ($severityCompare !== 0) {
            return $severityCompare;
        }
        return $a['timestamp'] - $b['timestamp'];
    });
    
    echo json_encode([
        'success' => true,
        'count' => count($notifications),
        'notifications' => $notifications
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch notifications',
        'message' => $e->getMessage()
    ]);
}
