<?php
// src/Controllers/MessengerController.php
namespace App\Controllers;

use App\Models\Chat;
use App\Models\Message;
use App\Models\Call;
use App\Models\UserStatus;
use App\Models\User;
use PDO;

class MessengerController {
    private $db;
    private $chat;
    private $message;
    private $call;
    private $userStatus;
    private $user;
    
    public function __construct(PDO $db) {
        $this->db = $db;
        $this->chat = new Chat($db);
        $this->message = new Message($db);
        $this->call = new Call($db);
        $this->userStatus = new UserStatus($db);
        $this->user = new User($db);
        
        // Обновляем статус пользователя при каждом запросе
        if (isset($_SESSION['user_id'])) {
            $this->userStatus->updateStatus($_SESSION['user_id'], 'online');
        }
    }
    
    /**
     * Главная страница мессенджера
     */
    public function index() {
        $this->requireAuth();
        
        $userId = $_SESSION['user_id'];
        $chats = $this->chat->getUserChats($userId);
        $users = $this->user->getAll();
        $onlineUsers = $this->userStatus->getOnlineUsers();
        
        // Форматируем данные чатов
        foreach ($chats as &$chat) {
            if ($chat['type'] === 'private') {
                // Для приватных чатов получаем информацию о собеседнике
                $participants = $this->chat->getParticipants($chat['id']);
                foreach ($participants as $participant) {
                    if ($participant['id'] != $userId) {
                        $chat['name'] = $participant['name'];
                      //  $chat['avatar'] = $participant['avatar'];
                        $chat['opponent_id'] = $participant['id'];
                        $chat['opponent_status'] = $participant['online_status'] ?? 'offline';
                        break;
                    }
                }
            }
        }
        
        require_once __DIR__ . '/../../views/messenger/index.php';
    }
    
    /**
     * Открыть чат
     */
    public function openChat($chatId = null) {
        $this->requireAuth();
        
        $userId = $_SESSION['user_id'];
        
        // Если chatId не указан, пытаемся получить из POST (для приватного чата)
        if (!$chatId && isset($_POST['user_id'])) {
            $opponentId = (int)$_POST['user_id'];
            $chat = $this->chat->getOrCreatePrivateChat($userId, $opponentId);
            $chatId = $chat['id'];
        }
        
        if (!$chatId) {
            $this->jsonResponse(['error' => 'Chat ID required']);
            return;
        }
        
        // Проверяем доступ к чату
        $participants = $this->chat->getParticipants($chatId);
        $hasAccess = false;
        foreach ($participants as $participant) {
            if ($participant['id'] == $userId) {
                $hasAccess = true;
                break;
            }
        }
        
        if (!$hasAccess) {
            $this->jsonResponse(['error' => 'Access denied']);
            return;
        }
        
        // Получаем сообщения
        $messages = $this->message->getChatMessages($chatId, 50);
        
        // Отмечаем сообщения как прочитанные
        $messageIds = array_column($messages, 'id');
        $this->message->markAsRead($messageIds, $userId);
        
        // Обновляем последнее прочитанное сообщение
        if (!empty($messages)) {
            $lastMessageId = end($messages)['id'];
            $this->chat->updateLastReadMessage($chatId, $userId, $lastMessageId);
        }
        
        $this->jsonResponse([
            'success' => true,
            'chat_id' => $chatId,
            'messages' => $messages,
            'participants' => $participants
        ]);
    }
    
    /**
     * Отправить сообщение
     */
    public function sendMessage() {
        $this->requireAuth();
        
        $userId = $_SESSION['user_id'];
        $chatId = (int)($_POST['chat_id'] ?? 0);
        $content = trim($_POST['content'] ?? '');
        $parentMessageId = $_POST['parent_message_id'] ?? null;
        $type = $_POST['type'] ?? 'text';
        
        if (!$chatId || (!$content && $type === 'text')) {
            $this->jsonResponse(['error' => 'Invalid data']);
            return;
        }
        
        // Проверяем доступ к чату
        $participants = $this->chat->getParticipants($chatId);
        $hasAccess = false;
        $participantIds = [];
        foreach ($participants as $participant) {
            $participantIds[] = $participant['id'];
            if ($participant['id'] == $userId) {
                $hasAccess = true;
            }
        }
        
        if (!$hasAccess) {
            $this->jsonResponse(['error' => 'Access denied']);
            return;
        }
        
        // Обработка загруженных файлов
        $fileData = null;
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $fileData = $this->handleFileUpload($_FILES['file']);
            if (!$fileData) {
                $this->jsonResponse(['error' => 'File upload failed']);
                return;
            }
            $type = $this->getFileType($fileData['file_path']);
        }
        
        // Отправляем сообщение
        $messageId = $this->message->send([
            'chat_id' => $chatId,
            'user_id' => $userId,
            'parent_message_id' => $parentMessageId,
            'type' => $type,
            'content' => $content,
            'file_path' => $fileData['file_path'] ?? null,
            'file_name' => $fileData['file_name'] ?? null,
            'file_size' => $fileData['file_size'] ?? null
        ]);
        
        if (!$messageId) {
            $this->jsonResponse(['error' => 'Failed to send message']);
            return;
        }
        
        // Отправляем уведомления другим участникам
        $this->sendNotifications($chatId, $userId, $participantIds, $content);
        
        // Возвращаем отправленное сообщение
        $messages = $this->message->getChatMessages($chatId, 1);
        
        $this->jsonResponse([
            'success' => true,
            'message' => $messages[0] ?? null
        ]);
    }
    
    /**
     * Добавить реакцию
     */
    public function addReaction() {
        $this->requireAuth();
        
        $userId = $_SESSION['user_id'];
        $messageId = (int)($_POST['message_id'] ?? 0);
        $emoji = $_POST['emoji'] ?? '';
        
        if (!$messageId || !$emoji) {
            $this->jsonResponse(['error' => 'Invalid data']);
            return;
        }
        
        $result = $this->message->addReaction($messageId, $userId, $emoji);
        
        $this->jsonResponse(['success' => $result]);
    }
    
    /**
     * Удалить реакцию
     */
    public function removeReaction() {
        $this->requireAuth();
        
        $userId = $_SESSION['user_id'];
        $messageId = (int)($_POST['message_id'] ?? 0);
        $emoji = $_POST['emoji'] ?? '';
        
        if (!$messageId || !$emoji) {
            $this->jsonResponse(['error' => 'Invalid data']);
            return;
        }
        
        $result = $this->message->removeReaction($messageId, $userId, $emoji);
        
        $this->jsonResponse(['success' => $result]);
    }
    
    /**
     * Редактировать сообщение
     */
    public function editMessage() {
        $this->requireAuth();
        
        $userId = $_SESSION['user_id'];
        $messageId = (int)($_POST['message_id'] ?? 0);
        $newContent = trim($_POST['content'] ?? '');
        
        if (!$messageId || !$newContent) {
            $this->jsonResponse(['error' => 'Invalid data']);
            return;
        }
        
        $result = $this->message->edit($messageId, $userId, $newContent);
        
        $this->jsonResponse(['success' => $result]);
    }
    
    /**
     * Удалить сообщение
     */
    public function deleteMessage() {
        $this->requireAuth();
        
        $userId = $_SESSION['user_id'];
        $messageId = (int)($_POST['message_id'] ?? 0);
        
        if (!$messageId) {
            $this->jsonResponse(['error' => 'Invalid data']);
            return;
        }
        
        $result = $this->message->delete($messageId, $userId);
        
        $this->jsonResponse(['success' => $result]);
    }
    
    /**
     * Инициировать звонок
     */
    public function initiateCall() {
        $this->requireAuth();
        
        $userId = $_SESSION['user_id'];
        $chatId = (int)($_POST['chat_id'] ?? 0);
        $type = $_POST['type'] ?? 'audio';
        
        if (!$chatId || !in_array($type, ['audio', 'video'])) {
            $this->jsonResponse(['error' => 'Invalid data']);
            return;
        }
        
        try {
            $result = $this->call->initiate($chatId, $userId, $type);
            
            // Отправляем уведомления участникам
            $participants = $this->chat->getParticipants($chatId);
            foreach ($participants as $participant) {
                if ($participant['id'] != $userId) {
                    // Здесь можно отправить push-уведомление или WebSocket событие
                    $this->sendCallNotification($participant['id'], $result['call_id'], $type);
                }
            }
            
            $this->jsonResponse([
                'success' => true,
                'call_id' => $result['call_id'],
                'room_id' => $result['room_id']
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Присоединиться к звонку
     */
    public function joinCall() {
        $this->requireAuth();
        
        $userId = $_SESSION['user_id'];
        $callId = (int)($_POST['call_id'] ?? 0);
        
        if (!$callId) {
            $this->jsonResponse(['error' => 'Invalid call ID']);
            return;
        }
        
        $result = $this->call->join($callId, $userId);
        $call = $this->call->findById($callId);
        
        $this->jsonResponse([
            'success' => $result,
            'room_id' => $call['room_id'] ?? null
        ]);
    }
    
    /**
     * Покинуть звонок
     */
    public function leaveCall() {
        $this->requireAuth();
        
        $userId = $_SESSION['user_id'];
        $callId = (int)($_POST['call_id'] ?? 0);
        
        if (!$callId) {
            $this->jsonResponse(['error' => 'Invalid call ID']);
            return;
        }
        
        $result = $this->call->leave($callId, $userId);
        
        $this->jsonResponse(['success' => $result]);
    }
    
    /**
     * Получить новые сообщения
     */
    public function getNewMessages() {
        $this->requireAuth();
        
        $userId = $_SESSION['user_id'];
        $chatId = (int)($_GET['chat_id'] ?? 0);
        $lastMessageId = (int)($_GET['last_message_id'] ?? 0);
        
        if (!$chatId) {
            $this->jsonResponse(['error' => 'Invalid chat ID']);
            return;
        }
        
        // Получаем новые сообщения
        $sql = "SELECT m.*, u.name as user_name
                FROM messages m
                INNER JOIN users u ON m.user_id = u.id
                WHERE m.chat_id = :chat_id 
                AND m.id > :last_id
                AND m.is_deleted = 0
                ORDER BY m.created_at ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':chat_id' => $chatId,
            ':last_id' => $lastMessageId
        ]);
        
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Отмечаем как прочитанные
        if (!empty($messages)) {
            $messageIds = array_column($messages, 'id');
            $this->message->markAsRead($messageIds, $userId);
            
            $lastId = end($messages)['id'];
            $this->chat->updateLastReadMessage($chatId, $userId, $lastId);
        }
        
        $this->jsonResponse([
            'success' => true,
            'messages' => $messages
        ]);
    }
    
    /**
     * Обновить статус пользователя
     */
    public function updateStatus() {
        $this->requireAuth();
        
        $userId = $_SESSION['user_id'];
        $status = $_POST['status'] ?? 'online';
        $statusMessage = $_POST['status_message'] ?? null;
        
        if (!in_array($status, ['online', 'away', 'busy', 'offline'])) {
            $this->jsonResponse(['error' => 'Invalid status']);
            return;
        }
        
        $result = $this->userStatus->updateStatus($userId, $status, $statusMessage);
        
        $this->jsonResponse(['success' => $result]);
    }
    
    /**
     * Поиск сообщений
     */
    public function searchMessages() {
        $this->requireAuth();
        
        $userId = $_SESSION['user_id'];
        $query = trim($_GET['q'] ?? '');
        $chatId = $_GET['chat_id'] ?? null;
        
        if (!$query) {
            $this->jsonResponse(['error' => 'Query required']);
            return;
        }
        
        $sql = "SELECT m.*, c.name as chat_name, u.name as user_name
                FROM messages m
                INNER JOIN chats c ON m.chat_id = c.id
                INNER JOIN chat_participants cp ON c.id = cp.chat_id
                INNER JOIN users u ON m.user_id = u.id
                WHERE cp.user_id = :user_id
                AND m.content LIKE :query
                AND m.is_deleted = 0";
        
        if ($chatId) {
            $sql .= " AND m.chat_id = :chat_id";
        }
        
        $sql .= " ORDER BY m.created_at DESC LIMIT 50";
        
        $stmt = $this->db->prepare($sql);
        $params = [
            ':user_id' => $userId,
            ':query' => '%' . $query . '%'
        ];
        
        if ($chatId) {
            $params[':chat_id'] = $chatId;
        }
        
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->jsonResponse([
            'success' => true,
            'results' => $results
        ]);
    }
    
    /**
     * Получить количество непрочитанных сообщений
     */
    public function getUnreadCount($userId = null) {
        if (!$userId) {
            $userId = $_SESSION['user_id'] ?? 0;
        }
        
        $sql = "SELECT COUNT(DISTINCT m.chat_id) as unread_chats,
                COUNT(m.id) as unread_messages
                FROM messages m
                INNER JOIN chat_participants cp ON m.chat_id = cp.chat_id
                WHERE cp.user_id = :user_id
                AND m.user_id != :user_id2
                AND (cp.last_read_message_id IS NULL OR m.id > cp.last_read_message_id)
                AND m.is_deleted = 0";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':user_id2' => $userId
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['unread_messages'] ?? 0;
    }
    
    // === Вспомогательные методы ===
    
    /**
     * Обработка загрузки файла
     */
    private function handleFileUpload($file) {
        $uploadDir = __DIR__ . '/../../uploads/messages/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = uniqid() . '_' . basename($file['name']);
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            return [
                'file_path' => '/uploads/messages/' . $fileName,
                'file_name' => $file['name'],
                'file_size' => $file['size']
            ];
        }
        
        return false;
    }
    
    /**
     * Определить тип файла
     */
    private function getFileType($filePath) {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $videoExtensions = ['mp4', 'webm', 'ogg', 'mov'];
        $audioExtensions = ['mp3', 'wav', 'ogg', 'm4a'];
        
        if (in_array($extension, $imageExtensions)) {
            return 'image';
        } elseif (in_array($extension, $videoExtensions)) {
            return 'video';
        } elseif (in_array($extension, $audioExtensions)) {
            return 'audio';
        }
        
        return 'file';
    }
    
    /**
     * Отправить уведомления
     */
    private function sendNotifications($chatId, $senderId, $participantIds, $content) {
        // Здесь можно интегрировать с существующей системой уведомлений
        // Отправка через WebSocket, Push-уведомления, Email, Telegram и т.д.
    }
    
    /**
     * Отправить уведомление о звонке
     */
    private function sendCallNotification($userId, $callId, $type) {
        // Интеграция с системой уведомлений для звонков
    }
    
    /**
     * Проверка авторизации
     */
    private function requireAuth() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
    }
    
    /**
     * JSON ответ
     */
    private function jsonResponse($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}