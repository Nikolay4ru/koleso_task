<?php
namespace App\Services;

use App\Models\Notification;
use PDO;

class NotificationService {
    private $db;
    private $notification;
    private $emailService;
    private $telegramService;
    
    public function __construct(PDO $db, EmailService $emailService, TelegramService $telegramService) {
        $this->db = $db;
        $this->notification = new Notification($db);
        $this->emailService = $emailService;
        $this->telegramService = $telegramService;
    }
    
    public function notifyTaskCreated($taskId, $creatorId) {
        // Получаем информацию о задаче
        $task = $this->getTaskInfo($taskId);
        
        // Получаем всех исполнителей и наблюдателей
        $recipients = $this->getTaskRecipients($taskId);
        
        foreach ($recipients as $recipient) {
            if ($recipient['id'] == $creatorId) continue;
            
            $title = "Новая задача: {$task['title']}";
            $message = "Вы назначены на задачу '{$task['title']}'";
            
            // Создаем уведомление в БД
            $this->notification->create([
                'user_id' => $recipient['id'],
                'type' => 'task_created',
                'title' => $title,
                'message' => $message,
                'task_id' => $taskId
            ]);
            
            // Отправляем email
            if ($recipient['email_notifications']) {
                $this->emailService->send($recipient['email'], $title, $message);
            }
            
            // Отправляем в Telegram
            if ($recipient['telegram_notifications'] && $recipient['telegram_chat_id']) {
                $this->telegramService->sendMessage($recipient['telegram_chat_id'], $message);
            }
        }
    }
    
    public function notifyStatusChanged($taskId, $oldStatus, $newStatus, $changedBy) {
        $task = $this->getTaskInfo($taskId);
        $recipients = $this->getTaskRecipients($taskId);
        
        $statusLabels = [
            'backlog' => 'Бэклог',
            'todo' => 'К выполнению',
            'in_progress' => 'В работе',
            'review' => 'На проверке',
            'done' => 'Выполнено'
        ];
        
        foreach ($recipients as $recipient) {
            if ($recipient['id'] == $changedBy) continue;
            
            $title = "Изменен статус задачи: {$task['title']}";
            $message = "Статус изменен с '{$statusLabels[$oldStatus]}' на '{$statusLabels[$newStatus]}'";
            
            $this->notification->create([
                'user_id' => $recipient['id'],
                'type' => 'status_changed',
                'title' => $title,
                'message' => $message,
                'task_id' => $taskId
            ]);
            
            if ($recipient['email_notifications']) {
                $this->emailService->send($recipient['email'], $title, $message);
            }
            
            if ($recipient['telegram_notifications'] && $recipient['telegram_chat_id']) {
                $this->telegramService->sendMessage($recipient['telegram_chat_id'], $message);
            }
        }
    }

    public function notifyTaskUpdated($taskId, $updatedBy) {
    $task = $this->getTaskInfo($taskId);
    $recipients = $this->getTaskRecipients($taskId);
    
    foreach ($recipients as $recipient) {
        if ($recipient['id'] == $updatedBy) continue;
        
        $title = "Задача обновлена: {$task['title']}";
        $message = "Задача '{$task['title']}' была изменена";
        
        $this->notification->create([
            'user_id' => $recipient['id'],
            'type' => 'task_updated',
            'title' => $title,
            'message' => $message,
            'task_id' => $taskId
        ]);
        
        if ($recipient['email_notifications']) {
            $this->emailService->send($recipient['email'], $title, $message);
        }
        
        if ($recipient['telegram_notifications'] && $recipient['telegram_chat_id']) {
            $this->telegramService->sendMessage($recipient['telegram_chat_id'], $message);
        }
    }
}

public function notifyNewComment($taskId, $commentAuthorId, $commentText) {
    $task = $this->getTaskInfo($taskId);
    $author = $this->getUserInfo($commentAuthorId);
    $recipients = $this->getTaskRecipients($taskId);
    
    // Добавляем создателя задачи в список получателей
    $creator = $this->getUserInfo($task['creator_id']);
    if ($creator && $creator['id'] != $commentAuthorId) {
        $recipients[] = $creator;
    }
    
    // Убираем дубликаты
    $uniqueRecipients = [];
    $seenIds = [];
    foreach ($recipients as $recipient) {
        if (!in_array($recipient['id'], $seenIds) && $recipient['id'] != $commentAuthorId) {
            $uniqueRecipients[] = $recipient;
            $seenIds[] = $recipient['id'];
        }
    }
    
    foreach ($uniqueRecipients as $recipient) {
        $title = "Новый комментарий к задаче: {$task['title']}";
        $message = "{$author['name']} прокомментировал: " . mb_substr($commentText, 0, 100) . 
                   (mb_strlen($commentText) > 100 ? '...' : '');
        
        $this->notification->create([
            'user_id' => $recipient['id'],
            'type' => 'new_comment',
            'title' => $title,
            'message' => $message,
            'task_id' => $taskId
        ]);
        
        if ($recipient['email_notifications']) {
            $fullMessage = "{$author['name']} оставил комментарий к задаче '{$task['title']}':\n\n" . 
                          $commentText . "\n\n" .
                          "Посмотреть задачу: https://task.koleso.app/tasks/view/{$taskId}#comments";
            
            $this->emailService->send($recipient['email'], $title, $fullMessage);
        }
        
        if ($recipient['telegram_notifications'] && $recipient['telegram_chat_id']) {
            $telegramMessage = "💬 <b>Новый комментарий</b>\n\n" .
                              "Задача: {$task['title']}\n" .
                              "От: {$author['name']}\n\n" .
                              "<i>" . htmlspecialchars($commentText) . "</i>";
            
            $this->telegramService->sendMessage($recipient['telegram_chat_id'], $telegramMessage);
        }
    }
}

private function getUserInfo($userId) {
    $sql = "SELECT * FROM users WHERE id = :id";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([':id' => $userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
    
    private function getTaskInfo($taskId) {
        $sql = "SELECT * FROM tasks WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $taskId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getTaskRecipients($taskId) {
        $sql = "SELECT DISTINCT u.* FROM users u
                LEFT JOIN task_assignees ta ON u.id = ta.user_id
                LEFT JOIN task_watchers tw ON u.id = tw.user_id
                WHERE ta.task_id = :task_id OR tw.task_id = :task_id2";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':task_id' => $taskId, ':task_id2' => $taskId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}