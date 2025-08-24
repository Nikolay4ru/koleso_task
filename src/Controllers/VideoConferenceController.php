<?php
// src/Controllers/VideoConferenceController.php

namespace App\Controllers;

use App\Models\VideoConference;
use App\Models\Task;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\WebSocketService;

class VideoConferenceController {
    private $db;
    private $conferenceModel;
    private $taskModel;
    private $userModel;
    private $notificationService;
    
    public function __construct($db, $notificationService = null) {
        $this->db = $db;
        $this->conferenceModel = new VideoConference($db);
        $this->taskModel = new Task($db);
        $this->userModel = new User($db);
        $this->notificationService = $notificationService;
    }


    public function getConferenceModel() {
    return $this->conferenceModel;
}
    
    /**
     * Главная страница видеоконференций
     */
    public function index() {
         $userId = $_SESSION['user_id'];
        
        // Получаем активные конференции
        $activeConferences = $this->conferenceModel->getActiveConferences();
        
        // Получаем запланированные конференции пользователя
        $scheduledConferences = $this->conferenceModel->getScheduledForUser($userId);
        
        // Получаем историю конференций
        $recentConferences = $this->conferenceModel->getHistoryForUser($userId, 10);
        
        require_once __DIR__ . '/../../views/conference/index.php';
    }
    
    /**
     * Создание новой конференции
     */
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            
            $taskId = $data['task_id'] ?? null;
            $title = $data['title'] ?? 'Видеоконференция';
            $scheduledAt = $data['scheduled_at'] ?? null;
            $invitedUsers = $data['invited_users'] ?? [];
            
            // Генерация уникального ID комнаты
            $roomId = $this->generateRoomId();
            
            $conferenceData = [
                'room_id' => $roomId,
                'task_id' => $taskId,
                'creator_id' => $_SESSION['user_id'],
                'title' => $title,
                'scheduled_at' => $scheduledAt,
                'status' => $scheduledAt ? 'scheduled' : 'active'
            ];
            
            $conferenceId = $this->conferenceModel->create($conferenceData);
            
            if ($conferenceId) {
                // Добавляем создателя как хоста
                $this->conferenceModel->addParticipant($conferenceId, $_SESSION['user_id'], 'host');
                
                // Добавляем приглашенных участников
                foreach ($invitedUsers as $userId) {
                    $this->conferenceModel->inviteUser($conferenceId, $userId);
                    
                    // Отправляем уведомление
                    if ($this->notificationService) {
                        $this->notificationService->notify($userId, [
                            'type' => 'conference_invitation',
                            'title' => 'Приглашение на видеоконференцию',
                            'message' => "Вы приглашены на конференцию: {$title}",
                            'link' => "/conference/join/{$roomId}",
                            'conference_id' => $conferenceId
                        ]);
                    }
                }
                
                // Если конференция связана с задачей
                if ($taskId) {
                    $this->notifyTaskParticipants($taskId, $roomId, $title);
                    
                    // Добавляем системный комментарий к задаче
                    $this->taskModel->addSystemComment($taskId, 
                        "Создана видеоконференция: {$title}\nКод комнаты: {$roomId}"
                    );
                }
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'room_id' => $roomId,
                    'conference_id' => $conferenceId,
                    'join_url' => "/conference/join/{$roomId}"
                ]);
                return;
            }
            
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Не удалось создать конференцию']);
            return;
        }
        
        // GET запрос - показываем форму создания
        $users = $this->userModel->getAllActive();
        $tasks = $this->taskModel->getActiveTasksForUser($_SESSION['user_id']);
        
        require_once __DIR__ . '/../../views/conference/create.php';
    }
    
    /**
     * Быстрое создание конференции (для кнопки в задаче)
     */
    public function quickCreate() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $taskId = $_POST['task_id'] ?? null;
            
            if (!$taskId) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Task ID required']);
                return;
            }
            
            $task = $this->taskModel->getById($taskId);
            if (!$task) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Task not found']);
                return;
            }
            
            // Создаем конференцию
            $roomId = $this->generateRoomId();
            
            $conferenceData = [
                'room_id' => $roomId,
                'task_id' => $taskId,
                'creator_id' => $_SESSION['user_id'],
                'title' => "Обсуждение: {$task['title']}",
                'status' => 'active'
            ];
            
            $conferenceId = $this->conferenceModel->create($conferenceData);
            
            if ($conferenceId) {
                // Добавляем создателя
                $this->conferenceModel->addParticipant($conferenceId, $_SESSION['user_id'], 'host');
                
                // Приглашаем всех участников задачи
                $assignees = $this->taskModel->getAssignees($taskId);
                foreach ($assignees as $assignee) {
                    if ($assignee['user_id'] != $_SESSION['user_id']) {
                        $this->conferenceModel->inviteUser($conferenceId, $assignee['user_id']);
                    }
                }
                
                // Уведомляем участников
                $this->notifyTaskParticipants($taskId, $roomId, $conferenceData['title']);
                
                // Добавляем комментарий к задаче
                $this->taskModel->addSystemComment($taskId, 
                    "Начата видеоконференция по задаче\nКод комнаты: {$roomId}"
                );
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'room_id' => $roomId,
                    'join_url' => "/conference/join/{$roomId}"
                ]);
                return;
            }
            
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Failed to create conference']);
        }
    }
    
    /**
     * Присоединение к конференции
     */
    public function join($roomId = null) {
        if (!$roomId) {
            // Получаем из URL
            $uri = $_SERVER['REQUEST_URI'];
            if (preg_match('/\/conference\/join\/([0-9-]+)/', $uri, $matches)) {
                $roomId = $matches[1];
               // print_r($roomId);
            } else {
                header('Location: /conference');
                return;
            }
        }
        
        $conference = $this->conferenceModel->getByRoomId($roomId);
        
        if (!$conference) {
            $_SESSION['error'] = 'Конференция не найдена';
            header('Location: /conference');
            return;
        }
        
        // Проверяем права доступа
        if (!$this->canJoinConference($conference, $_SESSION['user_id'])) {
            $_SESSION['error'] = 'У вас нет доступа к этой конференции';
            header('Location: /conference');
            return;
        }
        
        // Добавляем участника если его еще нет
        $participant = $this->conferenceModel->getParticipant($conference['id'], $_SESSION['user_id']);
        if (!$participant) {
            $role = $conference['creator_id'] == $_SESSION['user_id'] ? 'host' : 'participant';
            $this->conferenceModel->addParticipant($conference['id'], $_SESSION['user_id'], $role);
        }
        
        // Обновляем статус конференции если нужно
        if ($conference['status'] === 'scheduled') {
            $this->conferenceModel->updateStatus($conference['id'], 'active');
        }
        
        // Получаем данные для конференции
        $participants = $this->conferenceModel->getParticipants($conference['id']);
        $messages = $this->conferenceModel->getMessages($conference['id']);
        $task = $conference['task_id'] ? $this->taskModel->getById($conference['task_id']) : null;
        
        // Генерируем токен для WebRTC
        $webrtcToken = $this->generateWebRTCToken($conference['id'], $_SESSION['user_id']);
        
        // Текущий пользователь
        $currentUser = $this->userModel->findById($_SESSION['user_id']);
        
        require_once __DIR__ . '/../../views/conference/room.php';
    }
    
    /**
     * Запланированные конференции
     */
    public function scheduled() {
        $userId = $_SESSION['user_id'];
        
        $conferences = $this->conferenceModel->getScheduledForUser($userId);
        
        // Группируем по датам
        $groupedConferences = [];
        foreach ($conferences as $conf) {
            $date = date('Y-m-d', strtotime($conf['scheduled_at']));
            $groupedConferences[$date][] = $conf;
        }
        
        require_once __DIR__ . '/../../views/conference/scheduled.php';
    }
    
    /**
     * История конференций
     */
    public function history() {
        $userId = $_SESSION['user_id'];
        
        $conferences = $this->conferenceModel->getHistoryForUser($userId);
        
        require_once __DIR__ . '/../../views/conference/history.php';
    }
    
    /**
     * API: Отправка сообщения в чат
     */
    public function sendMessage() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $conferenceId = $_POST['conference_id'];
            $message = $_POST['message'];
            
            $messageId = $this->conferenceModel->addMessage(
                $conferenceId,
                $_SESSION['user_id'],
                $message
            );
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message_id' => $messageId,
                'timestamp' => date('H:i')
            ]);
        }
    }
    
    /**
     * API: Начало записи
     */
    public function startRecording() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $conferenceId = $_POST['conference_id'];
            
            // Проверяем права
            if (!$this->isHost($conferenceId, $_SESSION['user_id'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Only host can start recording']);
                return;
            }
            
            $recordingId = $this->conferenceModel->startRecording($conferenceId);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'recording_id' => $recordingId
            ]);
        }
    }
    
    /**
     * API: Остановка записи
     */
    public function stopRecording() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $recordingId = $_POST['recording_id'];
            $fileData = $_POST['file_data'] ?? null;
            
            if ($fileData) {
                // Сохраняем файл
                $fileName = 'conference_' . time() . '.webm';
                $filePath = __DIR__ . '/../../public/uploads/recordings/' . $fileName;
                
                // Декодируем base64 если нужно
                if (strpos($fileData, 'base64,') !== false) {
                    $fileData = explode('base64,', $fileData)[1];
                    $fileData = base64_decode($fileData);
                }
                
                file_put_contents($filePath, $fileData);
                
                $this->conferenceModel->saveRecording($recordingId, '/uploads/recordings/' . $fileName);
            }
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        }
    }
    
    /**
     * API: Завершение конференции
     */
    public function end() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $conferenceId = $_POST['conference_id'];
            
            // Проверяем права
            if (!$this->isHost($conferenceId, $_SESSION['user_id'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Only host can end conference']);
                return;
            }
            
            $this->conferenceModel->endConference($conferenceId);
            
            // Создаем сводку
            $this->createConferenceSummary($conferenceId);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        }
    }
    
    /**
     * Скачивание записи
     */
    public function downloadRecording($recordingId = null) {
        if (!$recordingId) {
            $uri = $_SERVER['REQUEST_URI'];
            if (preg_match('/\/conference\/recording\/(\d+)/', $uri, $matches)) {
                $recordingId = $matches[1];
            }
        }
        
        $recording = $this->conferenceModel->getRecording($recordingId);
        
        if (!$recording || !$this->canAccessRecording($recording, $_SESSION['user_id'])) {
            header('HTTP/1.0 403 Forbidden');
            return;
        }
        
        $filePath = __DIR__ . '/../../public' . $recording['file_path'];
        
        if (file_exists($filePath)) {
            header('Content-Type: video/webm');
            header('Content-Disposition: attachment; filename="conference_' . $recording['conference_id'] . '.webm"');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
        } else {
            header('HTTP/1.0 404 Not Found');
        }
    }
    
    // === Вспомогательные методы ===
    
    private function generateRoomId() {
        return sprintf('%03d-%03d-%03d', 
            rand(100, 999), 
            rand(100, 999), 
            rand(100, 999)
        );
    }
    
    private function generateWebRTCToken($conferenceId, $userId) {
        // Простой токен для демо
        $data = [
            'conference_id' => $conferenceId,
            'user_id' => $userId,
            'exp' => time() + 14400 // 4 часа
        ];
        
        return base64_encode(json_encode($data));
    }
    
    private function canJoinConference($conference, $userId) {
        // Создатель всегда может присоединиться
        if ($conference['creator_id'] == $userId) {
            return true;
        }
        
        // Проверяем приглашения
        if ($this->conferenceModel->isUserInvited($conference['id'], $userId)) {
            return true;
        }
        
        // Если конференция связана с задачей
        if ($conference['task_id']) {
            $assignees = $this->taskModel->getAssignees($conference['task_id']);
            foreach ($assignees as $assignee) {
                if ($assignee['user_id'] == $userId) {
                    return true;
                }
            }
        }
        
        return true;
    }
    
    private function isHost($conferenceId, $userId) {
        $participant = $this->conferenceModel->getParticipant($conferenceId, $userId);
        return $participant && $participant['role'] === 'host';
    }
    
    private function canAccessRecording($recording, $userId) {
        $conference = $this->conferenceModel->getById($recording['conference_id']);
        return $this->canJoinConference($conference, $userId);
    }
    
    private function notifyTaskParticipants($taskId, $roomId, $title) {
        if (!$this->notificationService) return;
        
        $task = $this->taskModel->getById($taskId);
        $assignees = $this->taskModel->getAssignees($taskId);
        
        foreach ($assignees as $assignee) {
            if ($assignee['user_id'] != $_SESSION['user_id']) {
                $this->notificationService->notify($assignee['user_id'], [
                    'type' => 'conference_started',
                    'title' => 'Видеоконференция по задаче',
                    'message' => "Начата конференция: {$title}",
                    'link' => "/conference/join/{$roomId}",
                    'task_id' => $taskId
                ]);
            }
        }
    }
    
    private function createConferenceSummary($conferenceId) {
        $conference = $this->conferenceModel->getById($conferenceId);
        $participants = $this->conferenceModel->getParticipants($conferenceId);
        $messagesCount = $this->conferenceModel->getMessagesCount($conferenceId);
        
        $duration = 0;
        if ($conference['started_at'] && $conference['ended_at']) {
            $duration = strtotime($conference['ended_at']) - strtotime($conference['started_at']);
        }
        
        // Если конференция связана с задачей
        if ($conference['task_id']) {
            $summary = "Завершена видеоконференция\n";
            $summary .= "Длительность: " . gmdate('H:i:s', $duration) . "\n";
            $summary .= "Участников: " . count($participants) . "\n";
            $summary .= "Сообщений в чате: " . $messagesCount;
            
            if ($this->conferenceModel->hasRecording($conferenceId)) {
                $summary .= "\nДоступна запись конференции";
            }
            
            $this->taskModel->addSystemComment($conference['task_id'], $summary);
        }
        
        // Отправляем уведомления участникам
        if ($this->notificationService) {
            foreach ($participants as $participant) {
                $this->notificationService->notify($participant['user_id'], [
                    'type' => 'conference_ended',
                    'title' => 'Конференция завершена',
                    'message' => "Конференция '{$conference['title']}' завершена",
                    'conference_id' => $conferenceId
                ]);
            }
        }
    }
}


?>