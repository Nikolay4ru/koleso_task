<?php
namespace App\Models;

use PDO;

class Invitation {
    private $db;
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    public function create($data) {
        $sql = "INSERT INTO invitations (email, name, token, department_id, is_admin, invited_by, expires_at) 
                VALUES (:email, :name, :token, :department_id, :is_admin, :invited_by, :expires_at)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':email' => $data['email'],
            ':name' => $data['name'],
            ':token' => $data['token'],
            ':department_id' => $data['department_id'],
            ':is_admin' => $data['is_admin'],
            ':invited_by' => $data['invited_by'],
            ':expires_at' => $data['expires_at']
        ]);
        
        return $this->db->lastInsertId();
    }
    
    public function findById($id) {
        $sql = "SELECT i.*, u.name as invited_by_name 
                FROM invitations i
                LEFT JOIN users u ON i.invited_by = u.id
                WHERE i.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function findByToken($token) {
        $sql = "SELECT * FROM invitations 
                WHERE token = :token 
                AND status = 'pending' 
                AND expires_at > NOW()";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':token' => $token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getAll() {
        $sql = "SELECT i.*, u.name as invited_by_name, d.name as department_name
                FROM invitations i
                LEFT JOIN users u ON i.invited_by = u.id
                LEFT JOIN departments d ON i.department_id = d.id
                ORDER BY i.created_at DESC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getPendingCount() {
        $sql = "SELECT COUNT(*) as count 
                FROM invitations 
                WHERE status = 'pending' 
                AND expires_at > NOW()";
        
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
    
    public function existsPending($email) {
        $sql = "SELECT COUNT(*) as count 
                FROM invitations 
                WHERE email = :email 
                AND status = 'pending' 
                AND expires_at > NOW()";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }
    
    public function updateToken($id, $newToken) {
        $sql = "UPDATE invitations 
                SET token = :token, 
                    expires_at = DATE_ADD(NOW(), INTERVAL 7 DAY),
                    updated_at = NOW()
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':token' => $newToken
        ]);
    }
    
    public function markAsUsed($token) {
        $sql = "UPDATE invitations 
                SET status = 'used', 
                    used_at = NOW() 
                WHERE token = :token";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':token' => $token]);
    }
    
    public function cancel($id) {
        $sql = "UPDATE invitations 
                SET status = 'cancelled' 
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    public function deleteExpired() {
        $sql = "DELETE FROM invitations 
                WHERE status = 'pending' 
                AND expires_at < NOW()";
        
        return $this->db->exec($sql);
    }
}