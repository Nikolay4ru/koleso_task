<?php
// src/Controllers/SignalingController.php

namespace App\Controllers;

use PDO;
use Exception;

class SignalingController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Polling для получения сигналов
     * GET /signaling или /signaling/poll
     */
    public function poll() {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        
        $roomId = $_GET['room_id'] ?? '';
        $lastId = (int)($_GET['last_id'] ?? 0);
        $userId = $_SESSION['user_id'] ?? 0;
        
        if (!$roomId) {
            http_response_code(400);
            echo json_encode(['error' => 'Room ID required']);
            return;
        }
        
        try {
            // Получаем новые сигналы
            $sql = "SELECT id, type, from_user_id, from_user_name, to_user_id, data, created_at
                    FROM conference_signals 
                    WHERE room_id = :room_id 
                    AND id > :last_id
                    AND (to_user_id IS NULL OR to_user_id = :user_id OR from_user_id = :user_id)
                    ORDER BY id ASC
                    LIMIT 100";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':room_id' => $roomId,
                ':last_id' => $lastId,
                ':user_id' => $userId
            ]);
            
            $signals = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Очищаем старые сигналы (старше 1 минуты)
            $this->cleanOldSignals();
            
            echo json_encode($signals);
            
        } catch (Exception $e) {
            error_log('SignalingController poll error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Server error']);
        }
    }
    
    /**
     * Отправка сигнала
     * POST /signaling или /signaling/send
     */
    public function send() {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(['success' => true]);
    exit;
}
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $type = $input['type'] ?? '';
        $fromUserId = $input['from_user_id'] ?? $_SESSION['user_id'] ?? 0;
        $fromUserName = $input['from_user_name'] ?? $_SESSION['user_name'] ?? '';
        $toUserId = $input['to_user_id'] ?? null;
        $roomId = $input['room_id'] ?? '';
        $data = $input['data'] ?? '';
        
        if (!$type || !$roomId || !$fromUserId) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Missing required fields',
                'received' => [
                    'type' => $type,
                    'room_id' => $roomId,
                    'from_user_id' => $fromUserId
                ]
            ]);
            return;
        }
        
        try {
            // Проверяем существование таблицы
            $checkTableSql = "SHOW TABLES LIKE 'conference_signals'";
            $checkStmt = $this->db->query($checkTableSql);
            if ($checkStmt->rowCount() == 0) {
                // Создаем таблицу если не существует
                $createTableSql = "CREATE TABLE IF NOT EXISTS `conference_signals` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `room_id` varchar(50) NOT NULL,
                    `type` varchar(50) NOT NULL,
                    `from_user_id` int(11) NOT NULL,
                    `from_user_name` varchar(255) DEFAULT NULL,
                    `to_user_id` int(11) DEFAULT NULL,
                    `data` text,
                    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_room_id` (`room_id`),
                    KEY `idx_created_at` (`created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                $this->db->exec($createTableSql);
            }
            
            // Вставляем сигнал
            $sql = "INSERT INTO conference_signals 
                    (type, from_user_id, from_user_name, to_user_id, room_id, data, created_at)
                    VALUES (:type, :from_user_id, :from_user_name, :to_user_id, :room_id, :data, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':type' => $type,
                ':from_user_id' => $fromUserId,
                ':from_user_name' => $fromUserName,
                ':to_user_id' => $toUserId,
                ':room_id' => $roomId,
                ':data' => $data
            ]);
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                throw new Exception('Database error: ' . implode(', ', $errorInfo));
            }
            
            $lastId = $this->db->lastInsertId();
            
            // Обновляем статус участника
            $this->updateParticipantStatus($type, $roomId, $fromUserId, $fromUserName);
            
            echo json_encode([
                'success' => true,
                'lastId' => $lastId
            ]);
            
        } catch (Exception $e) {
            error_log('SignalingController send error: ' . $e->getMessage());
            error_log('SQL Error Info: ' . print_r($this->db->errorInfo(), true));
            
            http_response_code(500);
            echo json_encode([
                'error' => 'Server error',
                'details' => $e->getMessage() // Только для отладки, удалите в продакшене
            ]);
        }
    }
    
    /**
     * Обновление статуса участника на основе сигнала
     */
    private function updateParticipantStatus($type, $roomId, $userId, $userName) {
        try {
            // Получаем ID конференции по room_id
            $sql = "SELECT id FROM video_conferences WHERE room_id = :room_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':room_id' => $roomId]);
            $conference = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$conference) {
                return;
            }
            
            $conferenceId = $conference['id'];
            
            switch ($type) {
                case 'join':
                    $this->handleJoinSignal($conferenceId, $userId, $userName);
                    break;
                    
                case 'leave':
                    $this->handleLeaveSignal($conferenceId, $userId);
                    break;
                    
                case 'heartbeat':
                    $this->updateHeartbeat($conferenceId, $userId);
                    break;
            }
            
        } catch (Exception $e) {
            error_log('updateParticipantStatus error: ' . $e->getMessage());
        }
    }
    
    /**
     * Обработка сигнала присоединения
     */
    private function handleJoinSignal($conferenceId, $userId, $userName) {
        try {
            // Проверяем существование таблицы conference_participants
            $checkTableSql = "SHOW TABLES LIKE 'conference_participants'";
            $checkStmt = $this->db->query($checkTableSql);
            if ($checkStmt->rowCount() == 0) {
                // Создаем таблицу если не существует
                $createTableSql = "CREATE TABLE IF NOT EXISTS `conference_participants` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `conference_id` int(11) NOT NULL,
                    `user_id` int(11) NOT NULL,
                    `role` enum('host','moderator','participant') DEFAULT 'participant',
                    `joined_at` datetime DEFAULT NULL,
                    `left_at` datetime DEFAULT NULL,
                    `is_active` tinyint(1) DEFAULT 0,
                    `last_seen` datetime DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `idx_conference_user` (`conference_id`, `user_id`),
                    KEY `idx_conference_id` (`conference_id`),
                    KEY `idx_user_id` (`user_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                $this->db->exec($createTableSql);
            }
            
            // Проверяем, есть ли участник
            $sql = "SELECT id FROM conference_participants 
                    WHERE conference_id = :conference_id AND user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':conference_id' => $conferenceId,
                ':user_id' => $userId
            ]);
            
            if ($stmt->fetch()) {
                // Обновляем статус
                $sql = "UPDATE conference_participants 
                        SET is_active = 1, last_seen = NOW(), joined_at = IFNULL(joined_at, NOW())
                        WHERE conference_id = :conference_id AND user_id = :user_id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':conference_id' => $conferenceId,
                    ':user_id' => $userId
                ]);
            } else {
                // Добавляем нового участника
                $sql = "INSERT INTO conference_participants 
                        (conference_id, user_id, role, joined_at, is_active, last_seen)
                        VALUES (:conference_id, :user_id, 'participant', NOW(), 1, NOW())";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':conference_id' => $conferenceId,
                    ':user_id' => $userId
                ]);
            }
            
            // Обновляем статус конференции если нужно
            $sql = "UPDATE video_conferences 
                    SET status = 'active', started_at = IFNULL(started_at, NOW())
                    WHERE id = :conference_id AND status != 'ended'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':conference_id' => $conferenceId]);
            
        } catch (Exception $e) {
            error_log('handleJoinSignal error: ' . $e->getMessage());
        }
    }
    
    /**
     * Обработка сигнала выхода
     */
    private function handleLeaveSignal($conferenceId, $userId) {
        $sql = "UPDATE conference_participants 
                SET is_active = 0, left_at = NOW()
                WHERE conference_id = :conference_id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':conference_id' => $conferenceId,
            ':user_id' => $userId
        ]);
    }
    
    /**
     * Обновление heartbeat
     */
    private function updateHeartbeat($conferenceId, $userId) {
        $sql = "UPDATE conference_participants 
                SET last_seen = NOW()
                WHERE conference_id = :conference_id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':conference_id' => $conferenceId,
            ':user_id' => $userId
        ]);
    }
    
    /**
     * Очистка старых сигналов
     */
    private function cleanOldSignals() {
        try {
            $sql = "DELETE FROM conference_signals 
                    WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 MINUTE)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        } catch (Exception $e) {
            error_log('cleanOldSignals error: ' . $e->getMessage());
        }
    }
}
?>