<?php
namespace App\Models;

use PDO;

class User {
    private $db;
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    public function create($data) {
        $sql = "INSERT INTO users (email, password, name, department_id, is_admin, is_active) 
                VALUES (:email, :password, :name, :department_id, :is_admin, :is_active)";
        
        $stmt = $this->db->prepare($sql);
        
        // Обработка department_id - если пустая строка, то NULL
        $departmentId = (!empty($data['department_id'])) ? $data['department_id'] : null;
        
        $stmt->execute([
            ':email' => $data['email'],
            ':password' => password_hash($data['password'], PASSWORD_DEFAULT),
            ':name' => $data['name'],
            ':department_id' => $departmentId,
            ':is_admin' => $data['is_admin'] ?? 0,
            ':is_active' => 1
        ]);
        
        return $this->db->lastInsertId();
    }

    public function getAllWithStats() {
    $sql = "SELECT u.*, 
            d.name as department_name,
            (SELECT COUNT(*) FROM tasks WHERE creator_id = u.id) as created_tasks,
            (SELECT COUNT(*) FROM task_assignees WHERE user_id = u.id) as assigned_tasks
            FROM users u
            LEFT JOIN departments d ON u.department_id = d.id
            ORDER BY u.created_at DESC";
    
    $stmt = $this->db->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


public function getById($userId) {
    $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    return $stmt->fetch(\PDO::FETCH_ASSOC);
}


public function getCount() {
    $sql = "SELECT COUNT(*) as count FROM users";
    $stmt = $this->db->query($sql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'];
}

public function getActiveCount($days = 30) {
    $sql = "SELECT COUNT(*) as count FROM users 
            WHERE last_login >= DATE_SUB(NOW(), INTERVAL :days DAY)";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([':days' => $days]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'];
}
    
    public function findByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function findById($id) {
        $sql = "SELECT u.*, d.name as department_name 
                FROM users u 
                LEFT JOIN departments d ON u.department_id = d.id 
                WHERE u.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function update($userId, $data) {
        $sql = "UPDATE users SET 
                name = :name,
                department_id = :department_id,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";
        
        // Обработка department_id
        $departmentId = (!empty($data['department_id'])) ? $data['department_id'] : null;
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $userId,
            ':name' => $data['name'],
            ':department_id' => $departmentId
        ]);
    }
    
    public function updatePassword($userId, $newPassword) {
        $sql = "UPDATE users SET password = :password WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $userId,
            ':password' => password_hash($newPassword, PASSWORD_DEFAULT)
        ]);
    }
    
    public function updateSettings($userId, $data) {
        
        $sql = "UPDATE users SET 
                telegram_chat_id = :telegram_chat_id,
                email_notifications = :email_notifications,
                telegram_notifications = :telegram_notifications
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $userId,
            ':telegram_chat_id' => $data['telegram_chat_id'] ?? null,
            ':email_notifications' => $data['email_notifications'],
            ':telegram_notifications' => $data['telegram_notifications']
        ]);
    }
    
    public function getAll() {
        $sql = "SELECT u.*, d.name as department_name 
                FROM users u 
                LEFT JOIN departments d ON u.department_id = d.id 
                ORDER BY u.name ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getByDepartment($departmentId) {
        $sql = "SELECT * FROM users WHERE department_id = :department_id ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':department_id' => $departmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function search($searchTerm) {
        $sql = "SELECT u.*, d.name as department_name 
                FROM users u 
                LEFT JOIN departments d ON u.department_id = d.id 
                WHERE u.name LIKE :search OR u.email LIKE :search2
                ORDER BY u.name ASC";
        
        $searchPattern = '%' . $searchTerm . '%';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':search' => $searchPattern,
            ':search2' => $searchPattern
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function exists($email) {
        $sql = "SELECT COUNT(*) FROM users WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetchColumn() > 0;
    }
    
    public function delete($userId) {
        $sql = "DELETE FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $userId]);
    }
    
    public function getTaskStatistics($userId) {
        $sql = "SELECT 
                (SELECT COUNT(*) FROM tasks WHERE creator_id = :user_id) as created_tasks,
                (SELECT COUNT(*) FROM task_assignees WHERE user_id = :user_id2) as assigned_tasks,
                (SELECT COUNT(*) FROM task_watchers WHERE user_id = :user_id3) as watching_tasks,
                (SELECT COUNT(*) FROM tasks t 
                 JOIN task_assignees ta ON t.id = ta.task_id 
                 WHERE ta.user_id = :user_id4 AND t.status = 'done') as completed_tasks";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':user_id2' => $userId,
            ':user_id3' => $userId,
            ':user_id4' => $userId
        ]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateByAdmin($userId, $data) {
    $sql = "UPDATE users SET 
            name = :name,
            email = :email,
            department_id = :department_id,
            is_admin = :is_admin,
            is_active = :is_active,
            phone = :phone,
            bio = :bio,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = :id";
    
    $departmentId = (!empty($data['department_id'])) ? $data['department_id'] : null;
    
    $stmt = $this->db->prepare($sql);
    return $stmt->execute([
        ':id' => $userId,
        ':name' => $data['name'],
        ':email' => $data['email'],
        ':department_id' => $departmentId,
        ':is_admin' => $data['is_admin'],
        ':is_active' => $data['is_active'],
        ':phone' => $data['phone'],
        ':bio' => $data['bio']
    ]);
}

public function updateLastLogin($userId) {
    $sql = "UPDATE users SET last_login = NOW() WHERE id = :id";
    $stmt = $this->db->prepare($sql);
    return $stmt->execute([':id' => $userId]);
}


}