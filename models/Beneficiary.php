<?php
// models/Beneficiary.php
// Model for Individual Beneficiaries

class Beneficiary {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create($data) {
        $sql = "INSERT INTO beneficiaries (
            last_name, first_name, middle_name, suffix, gender, barangay, municipality,
            contact_number, project_name, type_of_worker, amount_worth, noted_findings,
            date_complied_by_proponent, date_forwarded_to_ro6, rpmt_findings, date_approved,
            date_forwarded_to_nofo, date_turnover, date_monitoring, latitude, longitude,
            status, created_by, updated_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        try {
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                $this->logDatabaseError('Prepare failed', $this->db->errorInfo());
                return false;
            }
            
            $result = $stmt->execute([
                $data['last_name'], $data['first_name'], $data['middle_name'], $data['suffix'],
                $data['gender'], $data['barangay'], $data['municipality'], $data['contact_number'],
                $data['project_name'], $data['type_of_worker'], $data['amount_worth'],
                $data['noted_findings'], $data['date_complied_by_proponent'],
                $data['date_forwarded_to_ro6'], $data['rpmt_findings'], $data['date_approved'],
                $data['date_forwarded_to_nofo'], $data['date_turnover'], $data['date_monitoring'],
                $data['latitude'], $data['longitude'], $data['status'],
                $_SESSION['user_id'] ?? null, $_SESSION['user_id'] ?? null
            ]);
            
            if (!$result) {
                $this->logDatabaseError('Execute failed', $stmt->errorInfo());
                return false;
            }
            
            $insertId = $this->db->lastInsertId();
            $this->logActivity('create', $insertId, 'Created new beneficiary');
            return $insertId;
        } catch (PDOException $e) {
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
        $sql = "UPDATE beneficiaries SET
            last_name = ?, first_name = ?, middle_name = ?, suffix = ?, gender = ?,
            barangay = ?, municipality = ?, contact_number = ?, project_name = ?,
            type_of_worker = ?, amount_worth = ?, noted_findings = ?,
            date_complied_by_proponent = ?, date_forwarded_to_ro6 = ?, rpmt_findings = ?,
            date_approved = ?, date_forwarded_to_nofo = ?, date_turnover = ?,
            date_monitoring = ?, latitude = ?, longitude = ?, status = ?, updated_by = ?
        WHERE id = ?";
        
        try {
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                $this->logDatabaseError('Prepare failed in update', $this->db->errorInfo());
                return false;
            }
            
            $result = $stmt->execute([
                $data['last_name'], $data['first_name'], $data['middle_name'], $data['suffix'],
                $data['gender'], $data['barangay'], $data['municipality'], $data['contact_number'],
                $data['project_name'], $data['type_of_worker'], $data['amount_worth'],
                $data['noted_findings'], $data['date_complied_by_proponent'],
                $data['date_forwarded_to_ro6'], $data['rpmt_findings'], $data['date_approved'],
                $data['date_forwarded_to_nofo'], $data['date_turnover'], $data['date_monitoring'],
                $data['latitude'], $data['longitude'], $data['status'],
                $_SESSION['user_id'] ?? null, $id
            ]);
            
            if (!$result) {
                $this->logDatabaseError('Execute failed in update', $stmt->errorInfo());
                return false;
            }
            
            $this->logActivity('update', $id, 'Updated beneficiary');
            return $result;
        } catch (PDOException $e) {
            $this->logDatabaseError('PDOException in update', [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return false;
        }
    }
    
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM beneficiaries WHERE id = ?");
            if (!$stmt) {
                $this->logDatabaseError('Prepare failed in delete', $this->db->errorInfo());
                return false;
            }
            
            $result = $stmt->execute([$id]);
            
            if (!$result) {
                $this->logDatabaseError('Execute failed in delete', $stmt->errorInfo());
                return false;
            }
            
            if ($result) {
                $this->logActivity('delete', $id, 'Deleted beneficiary');
            }
            
            return $result;
        } catch (PDOException $e) {
            $this->logDatabaseError('PDOException in delete', [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return false;
        }
    }
    
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM beneficiaries WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getAll($filters = []) {
        $sql = "SELECT * FROM beneficiaries WHERE 1=1";
        $params = [];
        
        if (!empty($filters['municipality'])) {
            $sql .= " AND municipality = ?";
            $params[] = $filters['municipality'];
        }
        
        if (!empty($filters['barangay'])) {
            $sql .= " AND barangay = ?";
            $params[] = $filters['barangay'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND date_approved >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND date_approved <= ?";
            $params[] = $filters['date_to'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (CONCAT(first_name, ' ', last_name) LIKE ? OR project_name LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getMapData() {
        $sql = "SELECT id, CONCAT(first_name, ' ', last_name) as name, project_name, 
                barangay, municipality, amount_worth, 
                CAST(latitude AS DECIMAL(10,8)) as latitude, 
                CAST(longitude AS DECIMAL(11,8)) as longitude, 
                status, 'beneficiary' as type
                FROM beneficiaries 
                WHERE status IN ('approved', 'implemented', 'monitored') 
                AND latitude IS NOT NULL AND longitude IS NOT NULL
                AND latitude != 0 AND longitude != 0
                AND latitude BETWEEN 9.0 AND 12.0
                AND longitude BETWEEN 122.0 AND 124.0";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getStatistics() {
        $sql = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'implemented' THEN 1 ELSE 0 END) as implemented,
            SUM(CASE WHEN status = 'monitored' THEN 1 ELSE 0 END) as monitored,
            SUM(CASE WHEN gender = 'Male' THEN 1 ELSE 0 END) as male_count,
            SUM(CASE WHEN gender = 'Female' THEN 1 ELSE 0 END) as female_count,
            SUM(amount_worth) as total_amount
        FROM beneficiaries";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    private function logActivity($action, $recordId, $description) {
        $stmt = $this->db->prepare(
            "INSERT INTO activity_logs (user_id, action, table_name, record_id, description, ip_address) 
             VALUES (?, ?, 'beneficiaries', ?, ?, ?)"
        );
        $stmt->execute([
            $_SESSION['user_id'],
            $action,
            $recordId,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    }
    
    private function logDatabaseError($context, $errorInfo) {
        $logMessage = "[Beneficiary Model] " . $context . "\n";
        
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
