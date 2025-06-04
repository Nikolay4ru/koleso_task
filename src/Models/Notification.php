<?php
namespace App\Models;

use PDO;

class Notification {
    private $db;
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    /**
     * Создать новое уведомление
     */
    public function create($data) {
        $sql = "INSERT INTO notifications (user_id, type, title, message, task_id) 
                VALUES (:user_id, :type, :title, :message, :task_id)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $data['user_id'],
            ':type' => $data['type'],
            ':title' => $data['title'],
            ':message' => $data['message'],
            ':task_id' => $data['task_id'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Получить все уведомления пользователя
     */
    public function getUserNotifications($userId, $limit = 50) {
        $sql = "SELECT n.*, t.title as task_title 
                FROM notifications n
                LEFT JOIN tasks t ON n.task_id = t.id
                WHERE n.user_id = :user_id
                ORDER BY n.created_at DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Получить непрочитанные уведомления
     */
    public function getUnreadNotifications($userId) {
        $sql = "SELECT n.*, t.title as task_title 
                FROM notifications n
                LEFT JOIN tasks t ON n.task_id = t.id
                WHERE n.user_id = :user_id AND n.is_read = 0
                ORDER BY n.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Получить количество непрочитанных уведомлений
     */
    public function getUnreadCount($userId) {
        $sql = "SELECT COUNT(*) as count 
                FROM notifications 
                WHERE user_id = :user_id AND is_read = 0";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
    
    /**
     * Отметить уведомление как прочитанное
     */
    public function markAsRead($notificationId, $userId) {
        $sql = "UPDATE notifications 
                SET is_read = 1 
                WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $notificationId,
            ':user_id' => $userId
        ]);
    }
    
    /**
     * Отметить все уведомления пользователя как прочитанные
     */
    public function markAllAsRead($userId) {
        $sql = "UPDATE notifications 
                SET is_read = 1 
                WHERE user_id = :user_id AND is_read = 0";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':user_id' => $userId]);
    }
    
    /**
     * Удалить уведомление
     */
    public function delete($notificationId, $userId) {
        $sql = "DELETE FROM notifications 
                WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $notificationId,
            ':user_id' => $userId
        ]);
    }
    
    /**
     * Удалить старые уведомления (старше 30 дней)
     */
    public function deleteOldNotifications($days = 30) {
        $sql = "DELETE FROM notifications 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':days' => $days]);
    }
    
    /**
     * Создать массовые уведомления для нескольких пользователей
     */
    public function createBulk($userIds, $data) {
        $sql = "INSERT INTO notifications (user_id, type, title, message, task_id) 
                VALUES (:user_id, :type, :title, :message, :task_id)";
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($userIds as $userId) {
            $stmt->execute([
                ':user_id' => $userId,
                ':type' => $data['type'],
                ':title' => $data['title'],
                ':message' => $data['message'],
                ':task_id' => $data['task_id'] ?? null
            ]);
        }
        
        return true;
    }
    
    /**
     * Получить уведомления по типу
     */
    public function getByType($userId, $type, $limit = 20) {
        $sql = "SELECT n.*, t.title as task_title 
                FROM notifications n
                LEFT JOIN tasks t ON n.task_id = t.id
                WHERE n.user_id = :user_id AND n.type = :type
                ORDER BY n.created_at DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':type', $type, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Поиск уведомлений
     */
    public function search($userId, $searchTerm) {
        $sql = "SELECT n.*, t.title as task_title 
                FROM notifications n
                LEFT JOIN tasks t ON n.task_id = t.id
                WHERE n.user_id = :user_id 
                AND (n.title LIKE :search OR n.message LIKE :search2)
                ORDER BY n.created_at DESC
                LIMIT 50";
        
        $searchPattern = '%' . $searchTerm . '%';
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':search' => $searchPattern,
            ':search2' => $searchPattern
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Получить статистику уведомлений пользователя
     */
    public function getUserStats($userId) {
        $sql = "SELECT 
                    type,
                    COUNT(*) as total,
                    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread
                FROM notifications
                WHERE user_id = :user_id
                GROUP BY type";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}