<?php
namespace App\Models;

use PDO;

class Department {
    private $db;
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    public function create($data) {
        $sql = "INSERT INTO departments (name, description) VALUES (:name, :description)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    public function getAll() {
        $sql = "SELECT d.*, COUNT(u.id) as user_count 
                FROM departments d
                LEFT JOIN users u ON d.id = u.department_id
                GROUP BY d.id
                ORDER BY d.name ASC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function findById($id) {
        $sql = "SELECT * FROM departments WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function update($id, $data) {
        $sql = "UPDATE departments SET name = :name, description = :description WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null
        ]);
    }
    
    public function delete($id) {
        // Сначала убираем привязку пользователей к отделу
        $sql = "UPDATE users SET department_id = NULL WHERE department_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        // Затем удаляем сам отдел
        $sql = "DELETE FROM departments WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    public function getUsersByDepartment($departmentId) {
        $sql = "SELECT * FROM users WHERE department_id = :department_id ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':department_id' => $departmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function search($searchTerm) {
        $sql = "SELECT d.*, COUNT(u.id) as user_count 
                FROM departments d
                LEFT JOIN users u ON d.id = u.department_id
                WHERE d.name LIKE :search OR d.description LIKE :search2
                GROUP BY d.id
                ORDER BY d.name ASC";
        
        $searchPattern = '%' . $searchTerm . '%';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':search' => $searchPattern,
            ':search2' => $searchPattern
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getStatistics($departmentId) {
        $sql = "SELECT 
                (SELECT COUNT(*) FROM users WHERE department_id = :dept_id) as total_users,
                (SELECT COUNT(DISTINCT t.id) 
                 FROM tasks t 
                 JOIN task_assignees ta ON t.id = ta.task_id
                 JOIN users u ON ta.user_id = u.id
                 WHERE u.department_id = :dept_id2) as total_tasks,
                (SELECT COUNT(DISTINCT t.id) 
                 FROM tasks t 
                 JOIN task_assignees ta ON t.id = ta.task_id
                 JOIN users u ON ta.user_id = u.id
                 WHERE u.department_id = :dept_id3 AND t.status = 'done') as completed_tasks,
                (SELECT COUNT(DISTINCT t.id) 
                 FROM tasks t 
                 JOIN task_assignees ta ON t.id = ta.task_id
                 JOIN users u ON ta.user_id = u.id
                 WHERE u.department_id = :dept_id4 AND t.status IN ('todo', 'in_progress')) as active_tasks";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':dept_id' => $departmentId,
            ':dept_id2' => $departmentId,
            ':dept_id3' => $departmentId,
            ':dept_id4' => $departmentId
        ]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function getCount() {
    $sql = "SELECT COUNT(*) as count FROM departments";
    $stmt = $this->db->query($sql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'];
}

public function getAllWithStats() {
    $sql = "SELECT d.*, 
            COUNT(DISTINCT u.id) as user_count,
            COUNT(DISTINCT t.id) as task_count,
            COUNT(DISTINCT CASE WHEN t.status = 'done' THEN t.id END) as completed_tasks
            FROM departments d
            LEFT JOIN users u ON d.id = u.department_id
            LEFT JOIN task_assignees ta ON u.id = ta.user_id
            LEFT JOIN tasks t ON ta.task_id = t.id
            GROUP BY d.id
            ORDER BY d.name ASC";
    
    $stmt = $this->db->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
}