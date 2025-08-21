<?php
// src/Models/VideoConference.php

namespace App\Models;

use PDO;
use Exception;

class VideoConference {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Создание новой конференции
     */
    public function create($data) {
        try {
            $sql = "INSERT INTO video_conferences 
                    (room_id, task_id, creator_id, title, status, scheduled_at, created_at) 
                    VALUES 
                    (:room_id, :task_id, :creator_id, :title, :status, :scheduled_at, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':room_id' => $data['room_id'],
                ':task_id' => $data['task_id'],
                ':creator_id' => $data['creator_id'],
                ':title' => $data['title'],
                ':status' => $data['status'] ?? 'scheduled',
                ':scheduled_at' => $data['scheduled_at'] ?? null
            ]);
            
            return $result ? $this->db->lastInsertId() : false;
            
        } catch (Exception $e) {
            error_log('VideoConference create error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Добавление сообщения в чат
     */
    public function addMessage($conferenceId, $userId, $message) {
        try {
            $sql = "INSERT INTO conference_messages 
                    (conference_id, user_id, message, sent_at) 
                    VALUES 
                    (:conference_id, :user_id, :message, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':conference_id' => $conferenceId,
                ':user_id' => $userId,
                ':message' => $message
            ]);
            
            return $result ? $this->db->lastInsertId() : false;
            
        } catch (Exception $e) {
            error_log('VideoConference addMessage error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Получение сообщений
     */
    public function getMessages($conferenceId, $limit = 100) {
        $sql = "SELECT cm.*, u.name as user_name, u.avatar_url
                FROM conference_messages cm
                LEFT JOIN users u ON cm.user_id = u.id
                WHERE cm.conference_id = :conference_id
                ORDER BY cm.sent_at DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':conference_id', $conferenceId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    /**
     * Получение количества сообщений
     */
    public function getMessagesCount($conferenceId) {
        $sql = "SELECT COUNT(*) FROM conference_messages 
                WHERE conference_id = :conference_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':conference_id' => $conferenceId]);
        
        return $stmt->fetchColumn();
    }
    
    /**
     * Начало записи
     */
    public function startRecording($conferenceId) {
        try {
            $sql = "INSERT INTO conference_recordings 
                    (conference_id, started_at) 
                    VALUES 
                    (:conference_id, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([':conference_id' => $conferenceId]);
            
            return $result ? $this->db->lastInsertId() : false;
            
        } catch (Exception $e) {
            error_log('VideoConference startRecording error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Сохранение записи
     */
    public function saveRecording($recordingId, $filePath) {
        try {
            $sql = "UPDATE conference_recordings 
                    SET file_path = :file_path, 
                        ended_at = NOW(),
                        file_size = :file_size
                    WHERE id = :id";
            
            $fileSize = file_exists(__DIR__ . '/../../public' . $filePath) 
                ? filesize(__DIR__ . '/../../public' . $filePath) 
                : 0;
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $recordingId,
                ':file_path' => $filePath,
                ':file_size' => $fileSize
            ]);
            
        } catch (Exception $e) {
            error_log('VideoConference saveRecording error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Получение записи
     */
    public function getRecording($recordingId) {
        $sql = "SELECT * FROM conference_recordings WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $recordingId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Проверка наличия записи
     */
    public function hasRecording($conferenceId) {
        $sql = "SELECT COUNT(*) FROM conference_recordings 
                WHERE conference_id = :conference_id AND file_path IS NOT NULL";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':conference_id' => $conferenceId]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Обновление статуса конференции
     */
    public function updateStatus($conferenceId, $status) {
        try {
            $sql = "UPDATE video_conferences SET status = :status";
            
            if ($status === 'active') {
                $sql .= ", started_at = NOW()";
            } elseif ($status === 'ended') {
                $sql .= ", ended_at = NOW()";
            }
            
            $sql .= " WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $conferenceId,
                ':status' => $status
            ]);
            
        } catch (Exception $e) {
            error_log('VideoConference updateStatus error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Завершение конференции
     */
    public function endConference($conferenceId) {
        try {
            $this->updateStatus($conferenceId, 'ended');
            
            $sql = "UPDATE conference_participants 
                    SET left_at = NOW() 
                    WHERE conference_id = :conference_id AND left_at IS NULL";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':conference_id' => $conferenceId]);
            
            return true;
            
        } catch (Exception $e) {
            error_log('VideoConference endConference error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Обновление времени выхода участника
     */
    public function updateParticipantLeftTime($conferenceId, $userId) {
        try {
            $sql = "UPDATE conference_participants 
                    SET left_at = NOW() 
                    WHERE conference_id = :conference_id AND user_id = :user_id";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':conference_id' => $conferenceId,
                ':user_id' => $userId
            ]);
            
        } catch (Exception $e) {
            error_log('VideoConference updateParticipantLeftTime error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Получение статистики конференции
     */
    public function getConferenceStats($conferenceId) {
        $conference = $this->getById($conferenceId);
        
        if (!$conference) {
            return null;
        }
        
        $stats = [
            'duration' => 0,
            'participants_count' => 0,
            'messages_count' => 0,
            'has_recording' => false
        ];
        
        // Длительность
        if ($conference['started_at'] && $conference['ended_at']) {
            $stats['duration'] = strtotime($conference['ended_at']) - strtotime($conference['started_at']);
        }
        
        // Количество участников
        $sql = "SELECT COUNT(DISTINCT user_id) FROM conference_participants 
                WHERE conference_id = :conference_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':conference_id' => $conferenceId]);
        $stats['participants_count'] = $stmt->fetchColumn();
        
        // Количество сообщений
        $stats['messages_count'] = $this->getMessagesCount($conferenceId);
        
        // Наличие записи
        $stats['has_recording'] = $this->hasRecording($conferenceId);
        
        return $stats;
    }
    
    /**
     * Получение конференции по ID
     */
    public function getById($id) {
        $sql = "SELECT vc.*, u.name as creator_name 
                FROM video_conferences vc
                LEFT JOIN users u ON vc.creator_id = u.id
                WHERE vc.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Получение конференции по room_id
     */
    public function getByRoomId($roomId) {
        $sql = "SELECT vc.*, u.name as creator_name 
                FROM video_conferences vc
                LEFT JOIN users u ON vc.creator_id = u.id
                WHERE vc.room_id = :room_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':room_id' => $roomId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Получение конференций по задаче
     */
    public function getByTaskId($taskId) {
        $sql = "SELECT vc.*, u.name as creator_name
                FROM video_conferences vc
                LEFT JOIN users u ON vc.creator_id = u.id
                WHERE vc.task_id = :task_id
                ORDER BY vc.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':task_id' => $taskId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Получение активных конференций
     */
    public function getActiveConferences() {
        $sql = "SELECT vc.*, u.name as creator_name,
                (SELECT COUNT(*) FROM conference_participants 
                 WHERE conference_id = vc.id AND left_at IS NULL) as participant_count
                FROM video_conferences vc
                LEFT JOIN users u ON vc.creator_id = u.id
                WHERE vc.status = 'active'
                ORDER BY vc.started_at DESC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Получение запланированных конференций пользователя
     */
    public function getScheduledForUser($userId) {
        $sql = "SELECT DISTINCT vc.*, u.name as creator_name
                FROM video_conferences vc
                LEFT JOIN users u ON vc.creator_id = u.id
                LEFT JOIN conference_participants cp ON vc.id = cp.conference_id
                LEFT JOIN conference_invitations ci ON vc.id = ci.conference_id
                WHERE vc.status = 'scheduled'
                AND (vc.creator_id = :user_id 
                     OR cp.user_id = :user_id2
                     OR ci.user_id = :user_id3)
                AND (vc.scheduled_at IS NULL OR vc.scheduled_at > NOW())
                ORDER BY vc.scheduled_at ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':user_id2' => $userId,
            ':user_id3' => $userId
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Получение истории конференций пользователя
     */
    public function getHistoryForUser($userId, $limit = 50) {
        $sql = "SELECT DISTINCT vc.*, u.name as creator_name,
                cp.joined_at, cp.left_at, cp.role,
                (SELECT COUNT(*) FROM conference_messages 
                 WHERE conference_id = vc.id) as message_count,
                (SELECT COUNT(*) FROM conference_recordings 
                 WHERE conference_id = vc.id) as recording_count
                FROM video_conferences vc
                LEFT JOIN users u ON vc.creator_id = u.id
                LEFT JOIN conference_participants cp ON vc.id = cp.conference_id AND cp.user_id = :user_id
                WHERE vc.status = 'ended'
                AND (vc.creator_id = :user_id2 OR cp.user_id IS NOT NULL)
                ORDER BY vc.ended_at DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id2', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Добавление участника
     */
    public function addParticipant($conferenceId, $userId, $role = 'participant') {
        try {
            $checkSql = "SELECT id FROM conference_participants 
                        WHERE conference_id = :conference_id AND user_id = :user_id";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([
                ':conference_id' => $conferenceId,
                ':user_id' => $userId
            ]);
            
            if ($checkStmt->fetch()) {
                $updateSql = "UPDATE conference_participants 
                             SET joined_at = NOW(), left_at = NULL 
                             WHERE conference_id = :conference_id AND user_id = :user_id";
                $updateStmt = $this->db->prepare($updateSql);
                return $updateStmt->execute([
                    ':conference_id' => $conferenceId,
                    ':user_id' => $userId
                ]);
            }
            
            $sql = "INSERT INTO conference_participants 
                    (conference_id, user_id, role, joined_at) 
                    VALUES 
                    (:conference_id, :user_id, :role, NOW())";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':conference_id' => $conferenceId,
                ':user_id' => $userId,
                ':role' => $role
            ]);
            
        } catch (Exception $e) {
            error_log('VideoConference addParticipant error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Получение участника
     */
    public function getParticipant($conferenceId, $userId) {
        $sql = "SELECT * FROM conference_participants 
                WHERE conference_id = :conference_id AND user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':conference_id' => $conferenceId,
            ':user_id' => $userId
        ]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Получение списка участников
     */
    public function getParticipants($conferenceId) {
        $sql = "SELECT cp.*, u.name, u.email, u.avatar_url
                FROM conference_participants cp
                LEFT JOIN users u ON cp.user_id = u.id
                WHERE cp.conference_id = :conference_id
                ORDER BY cp.role DESC, cp.joined_at ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':conference_id' => $conferenceId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Приглашение пользователя
     */
    public function inviteUser($conferenceId, $userId) {
        try {
            $sql = "INSERT IGNORE INTO conference_invitations 
                    (conference_id, user_id, invited_at) 
                    VALUES 
                    (:conference_id, :user_id, NOW())";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':conference_id' => $conferenceId,
                ':user_id' => $userId
            ]);
            
        } catch (Exception $e) {
            error_log('VideoConference inviteUser error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Проверка приглашения
     */
    public function isUserInvited($conferenceId, $userId) {
        $sql = "SELECT id FROM conference_invitations 
                WHERE conference_id = :conference_id AND user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':conference_id' => $conferenceId,
            ':user_id' => $userId
        ]);
        
        return (bool) $stmt->fetchColumn();
    }
}
