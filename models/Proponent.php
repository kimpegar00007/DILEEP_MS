<?php
// models/Proponent.php
// Model for Group Proponents

class Proponent {
    private $db;
    private $lastError = '';
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getLastError() {
        return $this->lastError;
    }
    
    public function create($data) {
        $this->lastError = '';
        
        try {
            $data['liquidation_deadline'] = $this->calculateLiquidationDeadline(
                $data['date_turnover'],
                $data['proponent_type']
            );
            
            $sql = "INSERT INTO proponents (
                proponent_type, date_received, noted_findings, control_number, number_of_copies,
                date_copies_received, district, proponent_name, project_title, amount,
                number_of_associations, total_beneficiaries, male_beneficiaries, female_beneficiaries,
                type_of_beneficiaries, category, recipient_barangays, letter_of_intent_date,
                date_forwarded_to_ro6, rpmt_findings, date_complied_by_proponent,
                date_complied_by_proponent_nofo, date_forwarded_to_nofo, date_approved,
                date_check_release, check_number, check_date_issued, or_number, or_date_issued,
                date_turnover, date_implemented, date_liquidated, liquidation_deadline, date_monitoring,
                source_of_funds, latitude, longitude, status, created_by, updated_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                $this->lastError = 'Prepare failed: ' . implode(' | ', $this->db->errorInfo());
                $this->logDatabaseError('Prepare failed', $this->db->errorInfo());
                return false;
            }
            
            $params = [
                $data['proponent_type'], $data['date_received'], $data['noted_findings'],
                $data['control_number'], $data['number_of_copies'], $data['date_copies_received'],
                $data['district'], $data['proponent_name'], $data['project_title'], $data['amount'],
                $data['number_of_associations'], $data['total_beneficiaries'],
                $data['male_beneficiaries'], $data['female_beneficiaries'],
                $data['type_of_beneficiaries'], $data['category'], $data['recipient_barangays'],
                $data['letter_of_intent_date'], $data['date_forwarded_to_ro6'], $data['rpmt_findings'],
                $data['date_complied_by_proponent'], $data['date_complied_by_proponent_nofo'],
                $data['date_forwarded_to_nofo'], $data['date_approved'], $data['date_check_release'],
                $data['check_number'], $data['check_date_issued'], $data['or_number'],
                $data['or_date_issued'], $data['date_turnover'], $data['date_implemented'],
                $data['date_liquidated'], $data['liquidation_deadline'], $data['date_monitoring'],
                $data['source_of_funds'], $data['latitude'], $data['longitude'], $data['status'],
                $_SESSION['user_id'] ?? null, $_SESSION['user_id'] ?? null
            ];
            
            $result = $stmt->execute($params);
            
            if (!$result) {
                $this->lastError = 'Execute failed: ' . implode(' | ', $stmt->errorInfo());
                $this->logDatabaseError('Execute failed', $stmt->errorInfo());
                return false;
            }
            
            $insertId = (int) $this->db->lastInsertId();
            
            if ($insertId <= 0) {
                $findStmt = $this->db->prepare(
                    "SELECT id FROM proponents 
                     WHERE proponent_name = ? AND project_title = ? 
                     ORDER BY id DESC LIMIT 1"
                );
                $findStmt->execute([$data['proponent_name'], $data['project_title']]);
                $row = $findStmt->fetch();
                $insertId = $row ? (int)$row['id'] : 0;
                
                if ($insertId <= 0) {
                    $this->lastError = 'Insert succeeded but could not retrieve the new record ID (MariaDB lastInsertId compatibility issue)';
                    return false;
                }
            }
            
            $this->logActivity('create', $insertId, 'Created new proponent');
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
        } catch (Exception $e) {
            $this->lastError = 'Error: ' . $e->getMessage();
            error_log('[Proponent Model] Exception in create: ' . $e->getMessage());
            return false;
        }
    }
    
    public function update($id, $data) {
        $this->lastError = '';
        
        try {
            $data['liquidation_deadline'] = $this->calculateLiquidationDeadline(
                $data['date_turnover'],
                $data['proponent_type']
            );
            
            $sql = "UPDATE proponents SET
                proponent_type = ?, date_received = ?, noted_findings = ?, control_number = ?,
                number_of_copies = ?, date_copies_received = ?, district = ?, proponent_name = ?,
                project_title = ?, amount = ?, number_of_associations = ?, total_beneficiaries = ?,
                male_beneficiaries = ?, female_beneficiaries = ?, type_of_beneficiaries = ?,
                category = ?, recipient_barangays = ?, letter_of_intent_date = ?,
                date_forwarded_to_ro6 = ?, rpmt_findings = ?, date_complied_by_proponent = ?,
                date_complied_by_proponent_nofo = ?, date_forwarded_to_nofo = ?, date_approved = ?,
                date_check_release = ?, check_number = ?, check_date_issued = ?, or_number = ?,
                or_date_issued = ?, date_turnover = ?, date_implemented = ?, date_liquidated = ?,
                liquidation_deadline = ?, date_monitoring = ?, source_of_funds = ?, latitude = ?, longitude = ?, status = ?,
                updated_by = ?
            WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                $this->lastError = 'Prepare failed: ' . implode(' | ', $this->db->errorInfo());
                $this->logDatabaseError('Prepare failed in update', $this->db->errorInfo());
                return false;
            }
            
            $result = $stmt->execute([
                $data['proponent_type'], $data['date_received'], $data['noted_findings'],
                $data['control_number'], $data['number_of_copies'], $data['date_copies_received'],
                $data['district'], $data['proponent_name'], $data['project_title'], $data['amount'],
                $data['number_of_associations'], $data['total_beneficiaries'],
                $data['male_beneficiaries'], $data['female_beneficiaries'],
                $data['type_of_beneficiaries'], $data['category'], $data['recipient_barangays'],
                $data['letter_of_intent_date'], $data['date_forwarded_to_ro6'], $data['rpmt_findings'],
                $data['date_complied_by_proponent'], $data['date_complied_by_proponent_nofo'],
                $data['date_forwarded_to_nofo'], $data['date_approved'], $data['date_check_release'],
                $data['check_number'], $data['check_date_issued'], $data['or_number'],
                $data['or_date_issued'], $data['date_turnover'], $data['date_implemented'],
                $data['date_liquidated'], $data['liquidation_deadline'], $data['date_monitoring'],
                $data['source_of_funds'], $data['latitude'], $data['longitude'], $data['status'],
                $_SESSION['user_id'] ?? null, $id
            ]);
            
            if (!$result) {
                $this->lastError = 'Execute failed: ' . implode(' | ', $stmt->errorInfo());
                $this->logDatabaseError('Execute failed in update', $stmt->errorInfo());
                return false;
            }
            
            $this->logActivity('update', $id, 'Updated proponent');
            return $result;
        } catch (PDOException $e) {
            $this->lastError = 'DB Error: ' . $e->getMessage();
            $this->logDatabaseError('PDOException in update', [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return false;
        } catch (Exception $e) {
            $this->lastError = 'Error: ' . $e->getMessage();
            error_log('[Proponent Model] Exception in update: ' . $e->getMessage());
            return false;
        }
    }
    
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM proponents WHERE id = ?");
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
                $this->logActivity('delete', $id, 'Deleted proponent');
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
        $stmt = $this->db->prepare("SELECT * FROM proponents WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getAll($filters = []) {
        $sql = "SELECT * FROM proponents WHERE 1=1";
        $params = [];
        
        if (!empty($filters['proponent_type'])) {
            $sql .= " AND proponent_type = ?";
            $params[] = $filters['proponent_type'];
        }
        
        if (!empty($filters['district'])) {
            $sql .= " AND district = ?";
            $params[] = $filters['district'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['category'])) {
            $sql .= " AND category = ?";
            $params[] = $filters['category'];
        }
        
        // Support filtering by a specific date field (whitelisted)
        $allowedDateFields = [
            'letter_of_intent_date',
            'date_forwarded_to_ro6',
            'date_complied_by_proponent',
            'date_complied_by_proponent_nofo',
            'date_forwarded_to_nofo',
            'date_approved',
            'date_check_release',
            'check_date_issued',
            'or_date_issued',
            'date_turnover',
            'date_implemented',
            'date_liquidated',
            'date_monitoring',
            // 'source_of_funds' is treated specially (non-date)
        ];

        $dateField = 'date_approved';
        $isSourceOfFundsFilter = false;

        if (!empty($filters['date_field'])) {
            if ($filters['date_field'] === 'source_of_funds') {
                $isSourceOfFundsFilter = true;
            } elseif (in_array($filters['date_field'], $allowedDateFields, true)) {
                $dateField = $filters['date_field'];
            }
        }

        if ($isSourceOfFundsFilter) {
            // Filter records with non-empty source_of_funds. Optionally apply date range to created_at if provided.
            $sql .= " AND (source_of_funds IS NOT NULL AND source_of_funds != '')";

            if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
                $sql .= " AND (DATE(created_at) BETWEEN ? AND ?)";
                $params[] = $filters['date_from'];
                $params[] = $filters['date_to'];
            } elseif (!empty($filters['date_from'])) {
                $sql .= " AND (DATE(created_at) >= ?)";
                $params[] = $filters['date_from'];
            } elseif (!empty($filters['date_to'])) {
                $sql .= " AND (DATE(created_at) <= ?)";
                $params[] = $filters['date_to'];
            }
        } else {
            if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
                $sql .= " AND ((" . $dateField . " IS NOT NULL AND " . $dateField . " BETWEEN ? AND ?) OR (" . $dateField . " IS NULL AND DATE(created_at) BETWEEN ? AND ?))";
                $params[] = $filters['date_from'];
                $params[] = $filters['date_to'];
                $params[] = $filters['date_from'];
                $params[] = $filters['date_to'];
            } elseif (!empty($filters['date_from'])) {
                $sql .= " AND ((" . $dateField . " IS NOT NULL AND " . $dateField . " >= ?) OR (" . $dateField . " IS NULL AND DATE(created_at) >= ?))";
                $params[] = $filters['date_from'];
                $params[] = $filters['date_from'];
            } elseif (!empty($filters['date_to'])) {
                $sql .= " AND ((" . $dateField . " IS NOT NULL AND " . $dateField . " <= ?) OR (" . $dateField . " IS NULL AND DATE(created_at) <= ?))";
                $params[] = $filters['date_to'];
                $params[] = $filters['date_to'];
            }
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (proponent_name LIKE ? OR project_title LIKE ? OR control_number LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getMapData() {
        $sql = "SELECT id, proponent_name as name, project_title, district, amount, 
                CAST(latitude AS DECIMAL(10,8)) as latitude, 
                CAST(longitude AS DECIMAL(11,8)) as longitude, 
                status, proponent_type, 'proponent' as type,
                total_beneficiaries
                FROM proponents 
                WHERE status IN ('approved', 'implemented', 'liquidated', 'monitored') 
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
            SUM(CASE WHEN proponent_type = 'LGU-associated' THEN 1 ELSE 0 END) as lgu_count,
            SUM(CASE WHEN proponent_type = 'Non-LGU-associated' THEN 1 ELSE 0 END) as non_lgu_count,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'implemented' THEN 1 ELSE 0 END) as implemented,
            SUM(CASE WHEN status = 'liquidated' THEN 1 ELSE 0 END) as liquidated,
            SUM(CASE WHEN status = 'monitored' THEN 1 ELSE 0 END) as monitored,
            SUM(total_beneficiaries) as total_beneficiaries,
            SUM(male_beneficiaries) as total_male,
            SUM(female_beneficiaries) as total_female,
            SUM(amount) as total_amount
        FROM proponents";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    public function getOverdueLiquidations() {
        $sql = "SELECT * FROM proponents 
                WHERE date_liquidated IS NULL 
                AND liquidation_deadline < CURDATE() 
                AND status != 'liquidated'
                ORDER BY liquidation_deadline ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function saveAssociations($proponentId, $names, $addresses) {
        try {
            try {
                $this->db->exec("
                    CREATE TABLE IF NOT EXISTS proponent_associations (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        proponent_id INT NOT NULL,
                        association_name VARCHAR(255) NOT NULL,
                        association_address VARCHAR(500) DEFAULT NULL,
                        sort_order INT DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (proponent_id) REFERENCES proponents(id) ON DELETE CASCADE
                    )
                ");
            } catch (PDOException $e) {
                $this->db->exec("
                    CREATE TABLE IF NOT EXISTS proponent_associations (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        proponent_id INT NOT NULL,
                        association_name VARCHAR(255) NOT NULL,
                        association_address VARCHAR(500) DEFAULT NULL,
                        sort_order INT DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )
                ");
                error_log("[Proponent Model] proponent_associations created without FK constraint: " . $e->getMessage());
            }
            
            $stmt = $this->db->prepare("DELETE FROM proponent_associations WHERE proponent_id = ?");
            $stmt->execute([$proponentId]);
            
            if (!empty($names) && is_array($names)) {
                $insertStmt = $this->db->prepare(
                    "INSERT INTO proponent_associations (proponent_id, association_name, association_address, sort_order) VALUES (?, ?, ?, ?)"
                );
                
                foreach ($names as $index => $name) {
                    $name = trim($name);
                    if (empty($name)) continue;
                    
                    $address = isset($addresses[$index]) ? trim($addresses[$index]) : '';
                    $insertStmt->execute([$proponentId, $name, $address, $index]);
                }
            }
            
            return true;
        } catch (PDOException $e) {
            $this->logDatabaseError('Failed to save associations', [
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    public function getAssociations($proponentId) {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM proponent_associations WHERE proponent_id = ? ORDER BY sort_order ASC"
            );
            $stmt->execute([$proponentId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    
    private function calculateLiquidationDeadline($dateTurnover, $proponentType) {
        if (empty($dateTurnover)) {
            return null;
        }
        
        $turnoverDate = new DateTime($dateTurnover);
        $interval = ($proponentType === 'LGU-associated') ? 10 : 60;
        $turnoverDate->add(new DateInterval('P' . $interval . 'D'));
        
        return $turnoverDate->format('Y-m-d');
    }
    
    private function logActivity($action, $recordId, $description) {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO activity_logs (user_id, action, table_name, record_id, description, ip_address) 
                 VALUES (?, ?, 'proponents', ?, ?, ?)"
            );
            $stmt->execute([
                $_SESSION['user_id'] ?? null,
                $action,
                $recordId,
                $description,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        } catch (PDOException $e) {
            error_log("[Proponent Model] Failed to log activity: " . $e->getMessage());
        }
    }
    
    public function getDistrictDistribution() {
        $sql = "SELECT district, COUNT(*) as count, SUM(amount) as total_amount 
                FROM proponents 
                WHERE district IS NOT NULL AND district != ''
                GROUP BY district 
                ORDER BY count DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getCategoryDistribution() {
        $sql = "SELECT category, COUNT(*) as count 
                FROM proponents 
                WHERE category IS NOT NULL AND category != ''
                GROUP BY category 
                ORDER BY count DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getFundingSourceBreakdown() {
        $sql = "SELECT 
                CASE 
                    WHEN source_of_funds IS NULL OR source_of_funds = '' THEN 'Not Specified'
                    ELSE source_of_funds
                END as source_of_funds,
                COUNT(*) as count, 
                SUM(amount) as total_amount 
                FROM proponents 
                GROUP BY 
                    CASE 
                        WHEN source_of_funds IS NULL OR source_of_funds = '' THEN 'Not Specified'
                        ELSE source_of_funds
                    END
                ORDER BY total_amount DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getMonthlyTrends() {
        $sql = "SELECT 
                DATE_FORMAT(date_approved, '%Y-%m') as month,
                COUNT(*) as count,
                SUM(amount) as total_amount,
                SUM(total_beneficiaries) as total_beneficiaries
                FROM proponents 
                WHERE date_approved IS NOT NULL
                AND date_approved >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(date_approved, '%Y-%m')
                ORDER BY month ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    private function logDatabaseError($context, $errorInfo) {
        $logMessage = "[Proponent Model] " . $context . "\n";
        
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
