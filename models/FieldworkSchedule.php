<?php
// models/FieldworkSchedule.php
// Model for Schedule of Activities / Fieldwork

class FieldworkSchedule {
    private $db;
    private $lastError = '';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->ensureTable();
    }

    public function getLastError() {
        return $this->lastError;
    }

    private function ensureTable() {
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS fieldwork_schedule (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    location VARCHAR(500),
                    assigned_user_id INT NOT NULL,
                    start_date DATE NOT NULL,
                    end_date DATE,
                    status ENUM('pending', 'ongoing', 'completed', 'missed') DEFAULT 'pending',
                    created_by INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (assigned_user_id) REFERENCES users(id),
                    FOREIGN KEY (created_by) REFERENCES users(id)
                )
            ");
        } catch (PDOException $e) {
            error_log("[FieldworkSchedule Model] Table check failed: " . $e->getMessage());
        }
    }

    public function create($data) {
        $this->lastError = '';

        try {
            $sql = "INSERT INTO fieldwork_schedule (
                title, description, location, assigned_user_id,
                start_date, end_date, status, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                $this->lastError = 'Prepare failed: ' . implode(' | ', $this->db->errorInfo());
                $this->logDatabaseError('Prepare failed', $this->db->errorInfo());
                return false;
            }

            $status = $this->computeStatus($data['start_date'], $data['end_date'] ?? null);

            $result = $stmt->execute([
                trim($data['title']),
                trim($data['description'] ?? ''),
                trim($data['location'] ?? ''),
                (int) $data['assigned_user_id'],
                $data['start_date'],
                !empty($data['end_date']) ? $data['end_date'] : null,
                $status,
                $_SESSION['user_id'] ?? null
            ]);

            if (!$result) {
                $this->lastError = 'Execute failed: ' . implode(' | ', $stmt->errorInfo());
                $this->logDatabaseError('Execute failed', $stmt->errorInfo());
                return false;
            }

            $insertId = (int) $this->db->lastInsertId();

            if ($insertId <= 0) {
                $findStmt = $this->db->prepare(
                    "SELECT id FROM fieldwork_schedule WHERE title = ? AND start_date = ? AND created_by = ? ORDER BY id DESC LIMIT 1"
                );
                $findStmt->execute([trim($data['title']), $data['start_date'], $_SESSION['user_id'] ?? null]);
                $row = $findStmt->fetch();
                $insertId = $row ? (int) $row['id'] : 0;

                if ($insertId <= 0) {
                    $this->lastError = 'Insert succeeded but could not retrieve the new record ID';
                    return false;
                }
            }

            $this->logActivity('create', $insertId, 'Created fieldwork schedule: ' . trim($data['title']));
            return $insertId;
        } catch (PDOException $e) {
            $this->lastError = 'DB Error: ' . $e->getMessage();
            $this->logDatabaseError('PDOException in create', [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return false;
        }
    }

    public function update($id, $data) {
        $this->lastError = '';

        try {
            $sql = "UPDATE fieldwork_schedule SET
                title = ?, description = ?, location = ?, assigned_user_id = ?,
                start_date = ?, end_date = ?, status = ?
            WHERE id = ?";

            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                $this->lastError = 'Prepare failed: ' . implode(' | ', $this->db->errorInfo());
                $this->logDatabaseError('Prepare failed in update', $this->db->errorInfo());
                return false;
            }

            $status = $data['status'] ?? $this->computeStatus($data['start_date'], $data['end_date'] ?? null);

            $result = $stmt->execute([
                trim($data['title']),
                trim($data['description'] ?? ''),
                trim($data['location'] ?? ''),
                (int) $data['assigned_user_id'],
                $data['start_date'],
                !empty($data['end_date']) ? $data['end_date'] : null,
                $status,
                (int) $id
            ]);

            if (!$result) {
                $this->lastError = 'Execute failed: ' . implode(' | ', $stmt->errorInfo());
                $this->logDatabaseError('Execute failed in update', $stmt->errorInfo());
                return false;
            }

            $this->logActivity('update', $id, 'Updated fieldwork schedule: ' . trim($data['title']));
            return true;
        } catch (PDOException $e) {
            $this->lastError = 'DB Error: ' . $e->getMessage();
            $this->logDatabaseError('PDOException in update', [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return false;
        }
    }

    public function updateStatus($id, $status) {
        $this->lastError = '';

        $validStatuses = ['pending', 'ongoing', 'completed', 'missed'];
        if (!in_array($status, $validStatuses)) {
            $this->lastError = 'Invalid status: ' . $status;
            return false;
        }

        try {
            $stmt = $this->db->prepare("UPDATE fieldwork_schedule SET status = ? WHERE id = ?");
            $result = $stmt->execute([$status, (int) $id]);

            if ($result) {
                $this->logActivity('update', $id, 'Updated fieldwork status to: ' . $status);
            }

            return $result;
        } catch (PDOException $e) {
            $this->lastError = 'DB Error: ' . $e->getMessage();
            $this->logDatabaseError('PDOException in updateStatus', [
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function delete($id) {
        $this->lastError = '';

        try {
            $activity = $this->findById($id);
            $stmt = $this->db->prepare("DELETE FROM fieldwork_schedule WHERE id = ?");
            $result = $stmt->execute([(int) $id]);

            if (!$result) {
                $this->lastError = 'Execute failed: ' . implode(' | ', $stmt->errorInfo());
                $this->logDatabaseError('Execute failed in delete', $stmt->errorInfo());
                return false;
            }

            $title = $activity ? $activity['title'] : 'ID ' . $id;
            $this->logActivity('delete', $id, 'Deleted fieldwork schedule: ' . $title);
            return true;
        } catch (PDOException $e) {
            $this->lastError = 'DB Error: ' . $e->getMessage();
            $this->logDatabaseError('PDOException in delete', [
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function findById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT fs.*, u.full_name as assigned_user_name, c.full_name as created_by_name
                FROM fieldwork_schedule fs
                LEFT JOIN users u ON fs.assigned_user_id = u.id
                LEFT JOIN users c ON fs.created_by = c.id
                WHERE fs.id = ?
            ");
            $stmt->execute([(int) $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->logDatabaseError('PDOException in findById', ['message' => $e->getMessage()]);
            return false;
        }
    }

    public function getAll($filters = []) {
        try {
            $sql = "SELECT fs.*, u.full_name as assigned_user_name, c.full_name as created_by_name
                    FROM fieldwork_schedule fs
                    LEFT JOIN users u ON fs.assigned_user_id = u.id
                    LEFT JOIN users c ON fs.created_by = c.id
                    WHERE 1=1";
            $params = [];

            if (!empty($filters['status'])) {
                $sql .= " AND fs.status = ?";
                $params[] = $filters['status'];
            }

            if (!empty($filters['assigned_user_id'])) {
                $sql .= " AND fs.assigned_user_id = ?";
                $params[] = (int) $filters['assigned_user_id'];
            }

            if (!empty($filters['date_from'])) {
                $sql .= " AND fs.start_date >= ?";
                $params[] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $sql .= " AND fs.start_date <= ?";
                $params[] = $filters['date_to'];
            }

            if (!empty($filters['search'])) {
                $sql .= " AND (fs.title LIKE ? OR fs.description LIKE ? OR fs.location LIKE ?)";
                $term = '%' . $filters['search'] . '%';
                $params[] = $term;
                $params[] = $term;
                $params[] = $term;
            }

            $sql .= " ORDER BY fs.start_date ASC, fs.created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logDatabaseError('PDOException in getAll', ['message' => $e->getMessage()]);
            return [];
        }
    }

    public function getCalendarEvents($startDate, $endDate) {
        try {
            $sql = "SELECT fs.*, u.full_name as assigned_user_name
                    FROM fieldwork_schedule fs
                    LEFT JOIN users u ON fs.assigned_user_id = u.id
                    WHERE (fs.start_date <= ? AND (fs.end_date >= ? OR (fs.end_date IS NULL AND fs.start_date >= ?)))
                    ORDER BY fs.start_date ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$endDate, $startDate, $startDate]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logDatabaseError('PDOException in getCalendarEvents', ['message' => $e->getMessage()]);
            return [];
        }
    }

    public function getUpcomingActivities($daysAhead = 3) {
        try {
            $sql = "SELECT fs.*, u.full_name as assigned_user_name
                    FROM fieldwork_schedule fs
                    LEFT JOIN users u ON fs.assigned_user_id = u.id
                    WHERE fs.status IN ('pending', 'ongoing')
                    AND fs.start_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                    ORDER BY fs.start_date ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$daysAhead]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logDatabaseError('PDOException in getUpcomingActivities', ['message' => $e->getMessage()]);
            return [];
        }
    }

    public function getOverdueActivities() {
        try {
            $sql = "SELECT fs.*, u.full_name as assigned_user_name
                    FROM fieldwork_schedule fs
                    LEFT JOIN users u ON fs.assigned_user_id = u.id
                    WHERE fs.status NOT IN ('completed', 'missed')
                    AND (fs.end_date IS NOT NULL AND fs.end_date < CURDATE())
                       OR (fs.end_date IS NULL AND fs.start_date < CURDATE() AND fs.status NOT IN ('completed', 'missed'))
                    ORDER BY fs.start_date ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logDatabaseError('PDOException in getOverdueActivities', ['message' => $e->getMessage()]);
            return [];
        }
    }

    public function autoUpdateStatuses() {
        try {
            $today = date('Y-m-d');

            // Mark as ongoing: start_date <= today AND (end_date >= today OR end_date IS NULL) AND status = pending
            $this->db->prepare("
                UPDATE fieldwork_schedule
                SET status = 'ongoing'
                WHERE status = 'pending'
                AND start_date <= ?
                AND (end_date >= ? OR end_date IS NULL)
            ")->execute([$today, $today]);

            // Mark as missed: end_date < today AND status NOT IN (completed, missed)
            $this->db->prepare("
                UPDATE fieldwork_schedule
                SET status = 'missed'
                WHERE status NOT IN ('completed', 'missed')
                AND end_date IS NOT NULL AND end_date < ?
            ")->execute([$today]);

            // Mark single-day activities as missed if date passed and not completed
            $this->db->prepare("
                UPDATE fieldwork_schedule
                SET status = 'missed'
                WHERE status NOT IN ('completed', 'missed')
                AND end_date IS NULL AND start_date < ?
            ")->execute([$today]);

            return true;
        } catch (PDOException $e) {
            $this->logDatabaseError('PDOException in autoUpdateStatuses', ['message' => $e->getMessage()]);
            return false;
        }
    }

    public function getStatistics() {
        try {
            $sql = "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'ongoing' THEN 1 ELSE 0 END) as ongoing,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'missed' THEN 1 ELSE 0 END) as missed
            FROM fieldwork_schedule";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->logDatabaseError('PDOException in getStatistics', ['message' => $e->getMessage()]);
            return ['total' => 0, 'pending' => 0, 'ongoing' => 0, 'completed' => 0, 'missed' => 0];
        }
    }

    public function getAllUsers() {
        try {
            $stmt = $this->db->prepare("SELECT id, full_name, role FROM users WHERE is_active = 1 ORDER BY full_name ASC");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function computeStatus($startDate, $endDate = null) {
        $today = date('Y-m-d');

        if (!empty($endDate) && $endDate < $today) {
            return 'missed';
        }

        if ($startDate <= $today && (empty($endDate) || $endDate >= $today)) {
            if ($startDate === $today) {
                return 'ongoing';
            }
            if ($startDate < $today) {
                return empty($endDate) ? 'missed' : 'ongoing';
            }
        }

        return 'pending';
    }

    private function logActivity($action, $recordId, $description) {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO activity_logs (user_id, action, table_name, record_id, description, ip_address)
                 VALUES (?, ?, 'fieldwork_schedule', ?, ?, ?)"
            );
            $stmt->execute([
                $_SESSION['user_id'] ?? null,
                $action,
                $recordId,
                $description,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        } catch (PDOException $e) {
            error_log("[FieldworkSchedule Model] Failed to log activity: " . $e->getMessage());
        }
    }

    private function logDatabaseError($context, $errorInfo) {
        $logMessage = "[FieldworkSchedule Model] " . $context . "\n";

        if (is_array($errorInfo)) {
            if (isset($errorInfo['code'])) {
                $logMessage .= "Code: " . $errorInfo['code'] . "\n";
            }
            if (isset($errorInfo['message'])) {
                $logMessage .= "Message: " . $errorInfo['message'] . "\n";
            }
            if (isset($errorInfo['file'])) {
                $logMessage .= "File: " . $errorInfo['file'] . "\n";
            }
            if (isset($errorInfo['line'])) {
                $logMessage .= "Line: " . $errorInfo['line'] . "\n";
            }
            if (isset($errorInfo[0]) && isset($errorInfo[1]) && isset($errorInfo[2])) {
                $logMessage .= "SQLSTATE: " . $errorInfo[0] . "\n";
                $logMessage .= "Driver Code: " . $errorInfo[1] . "\n";
                $logMessage .= "Driver Message: " . $errorInfo[2] . "\n";
            }
        }

        $logMessage .= "Timestamp: " . date('Y-m-d H:i:s') . "\n";
        $logMessage .= "User ID: " . ($_SESSION['user_id'] ?? 'unknown') . "\n";
        $logMessage .= "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";

        error_log($logMessage);
    }
}
