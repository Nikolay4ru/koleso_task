<?php
// src/Models/Chat.php
namespace App\Models;

use PDO;
use Exception;

class Chat {
    private $db;
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    /**
     * Создать новый чат
     */
    public function create($type, $name = null, $createdBy = null) {
        try {
            $sql = "INSERT INTO chats (type, name, created_by) VALUES (:type, :name, :created_by)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':type' => $type,
                ':name' => $name,
                ':created_by' => $createdBy
            ]);
            
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log('Chat create error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Получить или создать приватный чат между двумя пользователями
     */
    public function getOrCreatePrivateChat($user1Id, $user2Id) {
        // Проверяем существующий чат
        $sql = "SELECT c.* FROM chats c
                INNER JOIN chat_participants cp1 ON c.id = cp1.chat_id AND cp1.user_id = :user1
                INNER JOIN chat_participants cp2 ON c.id = cp2.chat_id AND cp2.user_id = :user2
                WHERE c.type = 'private'
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user1' => $user1Id, ':user2' => $user2Id]);
        $chat = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($chat) {
            return $chat;
        }
        
        // Создаем новый чат
        $this->db->beginTransaction();
        try {
            $chatId = $this->create('private', null, $user1Id);
            
            // Добавляем участников
            $this->addParticipant($chatId, $user1Id, 'member');
            $this->addParticipant($chatId, $user2Id, 'member');
            
            $this->db->commit();
            
            return $this->findById($chatId);
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Добавить участника в чат
     */
    public function addParticipant($chatId, $userId, $role = 'member') {
        $sql = "INSERT INTO chat_participants (chat_id, user_id, role) 
                VALUES (:chat_id, :user_id, :role)
                ON DUPLICATE KEY UPDATE role = :role";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':chat_id' => $chatId,
            ':user_id' => $userId,
            ':role' => $role
        ]);
    }
    
    /**
     * Получить все чаты пользователя
     */
    public function getUserChats($userId) {
        $sql = "SELECT c.*, 
                cp.last_read_message_id,
                cp.notifications_enabled,
                (SELECT COUNT(*) FROM messages m 
                 WHERE m.chat_id = c.id 
                 AND m.id > IFNULL(cp.last_read_message_id, 0)
                 AND m.user_id != :user_id) as unread_count,
                (SELECT m2.content FROM messages m2 
                 WHERE m2.chat_id = c.id 
                 AND m2.is_deleted = 0
                 ORDER BY m2.created_at DESC LIMIT 1) as last_message,
                (SELECT m3.created_at FROM messages m3 
                 WHERE m3.chat_id = c.id 
                 ORDER BY m3.created_at DESC LIMIT 1) as last_message_time
                FROM chats c
                INNER JOIN chat_participants cp ON c.id = cp.chat_id
                WHERE cp.user_id = :user_id
                ORDER BY last_message_time DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Получить информацию о чате
     */
    public function findById($chatId) {
        $sql = "SELECT * FROM chats WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $chatId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Получить участников чата
     */
    public function getParticipants($chatId) {
        $sql = "SELECT u.*, cp.role, cp.joined_at, us.status as online_status, us.last_seen
                FROM chat_participants cp
                INNER JOIN users u ON cp.user_id = u.id
                LEFT JOIN user_status us ON u.id = us.user_id
                WHERE cp.chat_id = :chat_id
                ORDER BY u.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':chat_id' => $chatId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Обновить последнее прочитанное сообщение
     */
    public function updateLastReadMessage($chatId, $userId, $messageId) {
        $sql = "UPDATE chat_participants 
                SET last_read_message_id = :message_id 
                WHERE chat_id = :chat_id AND user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':chat_id' => $chatId,
            ':user_id' => $userId,
            ':message_id' => $messageId
        ]);
    }
}

// src/Models/Message.php
namespace App\Models;

use PDO;
use Exception;

class Message {
    private $db;
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    /**
     * Отправить сообщение
     */
    public function send($data) {
        try {
            $sql = "INSERT INTO messages (chat_id, user_id, parent_message_id, type, content, file_path, file_name, file_size, metadata) 
                    VALUES (:chat_id, :user_id, :parent_message_id, :type, :content, :file_path, :file_name, :file_size, :metadata)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':chat_id' => $data['chat_id'],
                ':user_id' => $data['user_id'],
                ':parent_message_id' => $data['parent_message_id'] ?? null,
                ':type' => $data['type'] ?? 'text',
                ':content' => $data['content'] ?? null,
                ':file_path' => $data['file_path'] ?? null,
                ':file_name' => $data['file_name'] ?? null,
                ':file_size' => $data['file_size'] ?? null,
                ':metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null
            ]);
            
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log('Message send error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Получить сообщения чата
     */
    public function getChatMessages($chatId, $limit = 50, $beforeId = null) {
        $sql = "SELECT m.*, 
                u.name as user_name, 
               
                pm.content as parent_content,
                pu.name as parent_user_name
                FROM messages m
                INNER JOIN users u ON m.user_id = u.id
                LEFT JOIN messages pm ON m.parent_message_id = pm.id
                LEFT JOIN users pu ON pm.user_id = pu.id
                WHERE m.chat_id = :chat_id
                AND m.is_deleted = 0";
        
        if ($beforeId) {
            $sql .= " AND m.id < :before_id";
        }
        
        $sql .= " ORDER BY m.created_at DESC LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':chat_id', $chatId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        
        if ($beforeId) {
            $stmt->bindValue(':before_id', $beforeId, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $messages = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
        
        // Получаем реакции для каждого сообщения отдельным запросом
        foreach ($messages as &$message) {
            $message['reactions'] = $this->getMessageReactions($message['id']);
        }
        
        return $messages;
    }
    
    /**
     * Получить реакции на сообщение
     */
    private function getMessageReactions($messageId) {
        $sql = "SELECT emoji, COUNT(*) as count 
                FROM message_reactions 
                WHERE message_id = :message_id 
                GROUP BY emoji";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':message_id' => $messageId]);
        $reactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($reactions)) {
            return null;
        }
        
        // Форматируем как строку для совместимости с фронтендом
        $result = [];
        foreach ($reactions as $reaction) {
            $result[] = $reaction['emoji'] . ':' . $reaction['count'];
        }
        
        return implode(',', $result);
    }
    
    /**
     * Добавить реакцию на сообщение
     */
    public function addReaction($messageId, $userId, $emoji) {
        $sql = "INSERT INTO message_reactions (message_id, user_id, emoji) 
                VALUES (:message_id, :user_id, :emoji)
                ON DUPLICATE KEY UPDATE created_at = NOW()";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':message_id' => $messageId,
            ':user_id' => $userId,
            ':emoji' => $emoji
        ]);
    }
    
    /**
     * Удалить реакцию
     */
    public function removeReaction($messageId, $userId, $emoji) {
        $sql = "DELETE FROM message_reactions 
                WHERE message_id = :message_id AND user_id = :user_id AND emoji = :emoji";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':message_id' => $messageId,
            ':user_id' => $userId,
            ':emoji' => $emoji
        ]);
    }
    
    /**
     * Редактировать сообщение
     */
    public function edit($messageId, $userId, $newContent) {
        $sql = "UPDATE messages 
                SET content = :content, is_edited = 1, edited_at = NOW() 
                WHERE id = :id AND user_id = :user_id AND is_deleted = 0";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $messageId,
            ':user_id' => $userId,
            ':content' => $newContent
        ]);
    }
    
    /**
     * Удалить сообщение
     */
    public function delete($messageId, $userId) {
        $sql = "UPDATE messages 
                SET is_deleted = 1, deleted_at = NOW(), content = NULL 
                WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $messageId,
            ':user_id' => $userId
        ]);
    }
    
    /**
     * Отметить сообщения как прочитанные
     */
    public function markAsRead($messageIds, $userId) {
        if (empty($messageIds)) return true;
        
        $placeholders = str_repeat('?,', count($messageIds) - 1) . '?';
        $sql = "INSERT IGNORE INTO message_read_status (message_id, user_id) 
                SELECT id, ? FROM messages 
                WHERE id IN ($placeholders)";
        
        $params = array_merge([$userId], $messageIds);
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
}

// src/Models/Call.php
namespace App\Models;

use PDO;
use Exception;

class Call {
    private $db;
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    /**
     * Инициировать звонок
     */
    public function initiate($chatId, $initiatorId, $type = 'audio') {
        $this->db->beginTransaction();
        
        try {
            // Создаем запись о звонке
            $roomId = $this->generateRoomId();
            
            $sql = "INSERT INTO calls (chat_id, initiator_id, type, room_id, status) 
                    VALUES (:chat_id, :initiator_id, :type, :room_id, 'initiated')";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':chat_id' => $chatId,
                ':initiator_id' => $initiatorId,
                ':type' => $type,
                ':room_id' => $roomId
            ]);
            
            $callId = $this->db->lastInsertId();
            
            // Добавляем участников
            $chat = new Chat($this->db);
            $participants = $chat->getParticipants($chatId);
            
            foreach ($participants as $participant) {
                $status = $participant['id'] == $initiatorId ? 'joined' : 'invited';
                $this->addParticipant($callId, $participant['id'], $status);
            }
            
            // Создаем системное сообщение о начале звонка
            $message = new Message($this->db);
            $message->send([
                'chat_id' => $chatId,
                'user_id' => $initiatorId,
                'type' => 'call_started',
                'content' => $type === 'video' ? 'Начат видеозвонок' : 'Начат аудиозвонок',
                'metadata' => ['call_id' => $callId, 'room_id' => $roomId]
            ]);
            
            $this->db->commit();
            
            return [
                'call_id' => $callId,
                'room_id' => $roomId
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Добавить участника звонка
     */
    private function addParticipant($callId, $userId, $status) {
        $sql = "INSERT INTO call_participants (call_id, user_id, status) 
                VALUES (:call_id, :user_id, :status)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':call_id' => $callId,
            ':user_id' => $userId,
            ':status' => $status
        ]);
    }
    
    /**
     * Присоединиться к звонку
     */
    public function join($callId, $userId) {
        $sql = "UPDATE call_participants 
                SET status = 'joined', joined_at = NOW() 
                WHERE call_id = :call_id AND user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':call_id' => $callId,
            ':user_id' => $userId
        ]);
        
        // Обновляем статус звонка
        $this->updateCallStatus($callId, 'active');
        
        return $result;
    }
    
    /**
     * Покинуть звонок
     */
    public function leave($callId, $userId) {
        $sql = "UPDATE call_participants 
                SET status = 'left', left_at = NOW() 
                WHERE call_id = :call_id AND user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':call_id' => $callId,
            ':user_id' => $userId
        ]);
        
        // Проверяем, остались ли активные участники
        $this->checkAndEndCall($callId);
        
        return $result;
    }
    
    /**
     * Завершить звонок
     */
    public function end($callId) {
        $sql = "UPDATE calls 
                SET status = 'ended', ended_at = NOW(), 
                duration = TIMESTAMPDIFF(SECOND, started_at, NOW()) 
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([':id' => $callId]);
        
        // Обновляем статус всех участников
        $sql = "UPDATE call_participants 
                SET status = 'left', left_at = NOW() 
                WHERE call_id = :call_id AND status = 'joined'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':call_id' => $callId]);
        
        // Добавляем системное сообщение о завершении звонка
        $call = $this->findById($callId);
        $message = new Message($this->db);
        $message->send([
            'chat_id' => $call['chat_id'],
            'user_id' => $call['initiator_id'],
            'type' => 'call_ended',
            'content' => 'Звонок завершен',
            'metadata' => ['call_id' => $callId, 'duration' => $call['duration']]
        ]);
        
        return $result;
    }
    
    /**
     * Обновить статус звонка
     */
    private function updateCallStatus($callId, $status) {
        $sql = "UPDATE calls SET status = :status";
        
        if ($status === 'active') {
            $sql .= ", started_at = IFNULL(started_at, NOW())";
        }
        
        $sql .= " WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $callId,
            ':status' => $status
        ]);
    }
    
    /**
     * Проверить и завершить звонок если нет участников
     */
    private function checkAndEndCall($callId) {
        $sql = "SELECT COUNT(*) as active_count 
                FROM call_participants 
                WHERE call_id = :call_id AND status = 'joined'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':call_id' => $callId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['active_count'] == 0) {
            $this->end($callId);
        }
    }
    
    /**
     * Найти звонок по ID
     */
    public function findById($callId) {
        $sql = "SELECT * FROM calls WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $callId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Найти звонок по room_id
     */
    public function findByRoomId($roomId) {
        $sql = "SELECT * FROM calls WHERE room_id = :room_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':room_id' => $roomId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Генерировать уникальный ID комнаты
     */
    private function generateRoomId() {
        return uniqid('room_', true);
    }
    
    /**
     * Получить активные звонки пользователя
     */
    public function getUserActiveCalls($userId) {
        $sql = "SELECT c.*, ch.name as chat_name, ch.type as chat_type
                FROM calls c
                INNER JOIN call_participants cp ON c.id = cp.call_id
                INNER JOIN chats ch ON c.chat_id = ch.id
                WHERE cp.user_id = :user_id 
                AND c.status IN ('initiated', 'ringing', 'active')
                ORDER BY c.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// src/Models/UserStatus.php
namespace App\Models;

use PDO;

class UserStatus {
    private $db;
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    /**
     * Обновить статус пользователя
     */
    public function updateStatus($userId, $status, $statusMessage = null) {
        $sql = "INSERT INTO user_status (user_id, status, status_message, last_seen) 
                VALUES (:user_id, :status, :status_message, NOW())
                ON DUPLICATE KEY UPDATE 
                status = :status, 
                status_message = :status_message,
                last_seen = IF(:status = 'offline', NOW(), last_seen)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':status' => $status,
            ':status_message' => $statusMessage
        ]);
    }
    
    /**
     * Получить статус пользователя
     */
    public function getUserStatus($userId) {
        $sql = "SELECT * FROM user_status WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Обновить время последнего посещения
     */
    public function updateLastSeen($userId) {
        $sql = "UPDATE user_status SET last_seen = NOW() WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':user_id' => $userId]);
    }
    
    /**
     * Получить онлайн пользователей
     */
    public function getOnlineUsers() {
        $sql = "SELECT u.*, us.status, us.status_message, us.last_seen 
                FROM user_status us
                INNER JOIN users u ON us.user_id = u.id
                WHERE us.status IN ('online', 'away', 'busy')
                ORDER BY u.name";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}