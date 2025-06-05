<?php
namespace App\Models;

use PDO;

class NotificationQueue {
    private $db;
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    /**
     * Добавить уведомление в очередь
     */
    public function add($data) {
        $sql = "INSERT INTO notification_queue 
                (user_id, type, channel, recipient, subject, message, data, priority, scheduled_at) 
                VALUES 
                (:user_id, :type, :channel, :recipient, :subject, :message, :data, :priority, :scheduled_at)";
        
        $stmt = $this->db->prepare($sql);
        
        $params = [
            ':user_id' => $data['user_id'],
            ':type' => $data['type'],
            ':channel' => $data['channel'],
            ':recipient' => $data['recipient'],
            ':subject' => $data['subject'] ?? null,
            ':message' => $data['message'],
            ':data' => isset($data['data']) ? json_encode($data['data']) : null,
            ':priority' => $data['priority'] ?? 5,
            ':scheduled_at' => $data['scheduled_at'] ?? date('Y-m-d H:i:s')
        ];
        
        return $stmt->execute($params);
    }
    
    /**
     * Добавить несколько уведомлений одновременно
     */
    public function addBatch($notifications) {
        $sql = "INSERT INTO notification_queue 
                (user_id, type, channel, recipient, subject, message, data, priority, scheduled_at) 
                VALUES 
                (:user_id, :type, :channel, :recipient, :subject, :message, :data, :priority, :scheduled_at)";
        
        $stmt = $this->db->prepare($sql);
        
        $this->db->beginTransaction();
        try {
            foreach ($notifications as $notification) {
                $stmt->execute([
                    ':user_id' => $notification['user_id'],
                    ':type' => $notification['type'],
                    ':channel' => $notification['channel'],
                    ':recipient' => $notification['recipient'],
                    ':subject' => $notification['subject'] ?? null,
                    ':message' => $notification['message'],
                    ':data' => isset($notification['data']) ? json_encode($notification['data']) : null,
                    ':priority' => $notification['priority'] ?? 5,
                    ':scheduled_at' => $notification['scheduled_at'] ?? date('Y-m-d H:i:s')
                ]);
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Получить следующие уведомления для обработки
     */
    public function getNextBatch($limit = 10) {
        $sql = "UPDATE notification_queue
        SET status = 'processing'
        WHERE status = 'pending'
        AND scheduled_at <= NOW()
        AND attempts < 3
        ORDER BY priority ASC, created_at ASC
        LIMIT :limit";
$stmt = $this->db->prepare($sql);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();

$sql = "SELECT * FROM notification_queue
        WHERE status = 'processing'
        AND attempts < 3
        ORDER BY priority ASC, created_at ASC
        LIMIT :limit";
$stmt = $this->db->prepare($sql);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Отметить уведомление как обрабатываемое
     */
    public function markAsProcessing($id) {
        $sql = "UPDATE notification_queue 
                SET status = 'processing', 
                    attempts = attempts + 1 
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Отметить уведомление как отправленное
     */
    public function markAsSent($id) {
        $sql = "UPDATE notification_queue 
                SET status = 'sent', 
                    processed_at = NOW() 
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Отметить уведомление как неудачное
     */
    public function markAsFailed($id, $errorMessage) {
        $sql = "UPDATE notification_queue 
                SET status = CASE 
                    WHEN attempts >= 3 THEN 'failed' 
                    ELSE 'pending' 
                END,
                error_message = :error_message,
                scheduled_at = DATE_ADD(NOW(), INTERVAL POW(2, attempts) MINUTE)
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':error_message' => $errorMessage
        ]);
    }
    
    /**
     * Получить статистику очереди
     */
    public function getStatistics() {
        $sql = "SELECT 
                status,
                COUNT(*) as count,
                MIN(created_at) as oldest
                FROM notification_queue
                GROUP BY status";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Очистить старые обработанные уведомления
     */
    public function cleanOld($daysToKeep = 7) {
        $sql = "DELETE FROM notification_queue 
                WHERE status IN ('sent', 'failed') 
                AND processed_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':days' => $daysToKeep]);
        
        return $stmt->rowCount();
    }
    
    /**
     * Получить неудачные уведомления для повторной отправки
     */
    public function getFailedNotifications($userId = null) {
        $sql = "SELECT * FROM notification_queue 
                WHERE status = 'failed'";
        
        if ($userId) {
            $sql .= " AND user_id = :user_id";
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        if ($userId) {
            $stmt->execute([':user_id' => $userId]);
        } else {
            $stmt->execute();
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}