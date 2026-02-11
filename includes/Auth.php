<?php
// includes/Auth.php
// Authentication and Authorization Handler

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function login($username, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            
            $this->logActivity($user['id'], 'login', 'users', $user['id'], 'User logged in');
            return true;
        }
        
        return false;
    }
    
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logActivity($_SESSION['user_id'], 'logout', 'users', $_SESSION['user_id'], 'User logged out');
        }
        
        session_destroy();
        return true;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function getUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role'],
            'full_name' => $_SESSION['full_name']
        ];
    }
    
    public function hasRole($roles) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        
        return in_array($_SESSION['role'], $roles);
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }
    
    public function requireRole($roles) {
        $this->requireLogin();
        
        if (!$this->hasRole($roles)) {
            header('Location: unauthorized.php');
            exit;
        }
    }
    
    public function changePassword($userId, $oldPassword, $newPassword) {
        $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($oldPassword, $user['password'])) {
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $this->db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);
            
            $this->logActivity($userId, 'update', 'users', $userId, 'Password changed');
            return true;
        }
        
        return false;
    }
    
    private function logActivity($userId, $action, $tableName, $recordId, $description) {
        $stmt = $this->db->prepare(
            "INSERT INTO activity_logs (user_id, action, table_name, record_id, description, ip_address) 
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $userId,
            $action,
            $tableName,
            $recordId,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    }
}
