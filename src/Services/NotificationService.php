<?php
namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationQueue;
use PDO;

class NotificationService {
    private $db;
    private $notification;
    private $queue;
    private $emailService;
    private $telegramService;
    private $useQueue = true; // Флаг для включения/выключения очереди
    
    public function __construct(PDO $db, EmailService $emailService, TelegramService $telegramService) {
        $this->db = $db;
        $this->notification = new Notification($db);
        $this->queue = new NotificationQueue($db);
        $this->emailService = $emailService;
        $this->telegramService = $telegramService;
    }
    
    /**
     * Включить/выключить использование очереди
     */
    public function setUseQueue($useQueue) {
        $this->useQueue = $useQueue;
    }
    
    public function notifyTaskCreated($taskId, $creatorId) {
        $task = $this->getTaskInfo($taskId);
        $recipients = $this->getTaskRecipients($taskId);
        
        $notifications = [];
        
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
            
            // Подготавливаем уведомления для очереди
            if ($recipient['email_notifications']) {
                $notifications[] = [
                    'user_id' => $recipient['id'],
                    'type' => 'task_created',
                    'channel' => 'email',
                    'recipient' => $recipient['email'],
                    'subject' => $title,
                    'message' => $message,
                    'data' => ['task_id' => $taskId],
                    'priority' => 5
                ];
            }
            
            if ($recipient['telegram_notifications'] && $recipient['telegram_chat_id']) {
                $notifications[] = [
                    'user_id' => $recipient['id'],
                    'type' => 'task_created',
                    'channel' => 'telegram',
                    'recipient' => $recipient['telegram_chat_id'],
                    'message' => $message,
                    'data' => ['task_id' => $taskId],
                    'priority' => 5
                ];
            }
        }
        
        // Отправляем уведомления через очередь или напрямую
        if ($this->useQueue && !empty($notifications)) {
            $this->queue->addBatch($notifications);
        } else {
            $this->sendImmediately($notifications);
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
        
        $notifications = [];
        
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
                $notifications[] = [
                    'user_id' => $recipient['id'],
                    'type' => 'status_changed',
                    'channel' => 'email',
                    'recipient' => $recipient['email'],
                    'subject' => $title,
                    'message' => $message,
                    'data' => [
                        'task_id' => $taskId,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus
                    ],
                    'priority' => 5
                ];
            }
            
            if ($recipient['telegram_notifications'] && $recipient['telegram_chat_id']) {
                $notifications[] = [
                    'user_id' => $recipient['id'],
                    'type' => 'status_changed',
                    'channel' => 'telegram',
                    'recipient' => $recipient['telegram_chat_id'],
                    'message' => $message,
                    'data' => [
                        'task_id' => $taskId,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus
                    ],
                    'priority' => 5
                ];
            }
        }
        
        if ($this->useQueue && !empty($notifications)) {
            $this->queue->addBatch($notifications);
        } else {
            $this->sendImmediately($notifications);
        }
    }
    
    public function notifyTaskUpdated($taskId, $updatedBy) {
        $task = $this->getTaskInfo($taskId);
        $recipients = $this->getTaskRecipients($taskId);
        
        $notifications = [];
        
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
                $notifications[] = [
                    'user_id' => $recipient['id'],
                    'type' => 'task_updated',
                    'channel' => 'email',
                    'recipient' => $recipient['email'],
                    'subject' => $title,
                    'message' => $message,
                    'data' => ['task_id' => $taskId],
                    'priority' => 6
                ];
            }
            
            if ($recipient['telegram_notifications'] && $recipient['telegram_chat_id']) {
                $notifications[] = [
                    'user_id' => $recipient['id'],
                    'type' => 'task_updated',
                    'channel' => 'telegram',
                    'recipient' => $recipient['telegram_chat_id'],
                    'message' => $message,
                    'data' => ['task_id' => $taskId],
                    'priority' => 6
                ];
            }
        }
        
        if ($this->useQueue && !empty($notifications)) {
            $this->queue->addBatch($notifications);
        } else {
            $this->sendImmediately($notifications);
        }
    }
    
 public function notifyNewComment($taskId, $commentAuthorId, $commentText) {
    $task = $this->getTaskInfo($taskId);
    $author = $this->getUserInfo($commentAuthorId);

    if (!$task || !$author) {
        // Нет задачи или автора — ничего не делаем (или логируем ошибку)
        return;
    }

    $recipients = $this->getTaskRecipients($taskId);
    // Добавляем создателя задачи в список получателей, если он не автор комментария
    $creator = $this->getUserInfo($task['creator_id']);
    if ($creator && $creator['id'] != $commentAuthorId) {
        $recipients[] = $creator;
    }

    // Убираем дубликаты по id
    $uniqueRecipients = [];
    $seenIds = [];
    foreach ($recipients as $recipient) {
        if (!in_array($recipient['id'], $seenIds)) {
            $uniqueRecipients[] = $recipient;
            $seenIds[] = $recipient['id'];
        }
    }
    $recipients = $uniqueRecipients;

    $notifications = [];
    
    foreach ($recipients as $recipient) {
        if ($recipient['id'] == $commentAuthorId) continue;

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

        if (!empty($recipient['email_notifications']) && !empty($recipient['email'])) {
            $fullMessage = "{$author['name']} оставил комментарий к задаче '{$task['title']}':\n\n" .
                          $commentText . "\n\n" .
                          "Посмотреть задачу: https://task.koleso.app/tasks/view/{$taskId}#comments";

            $notifications[] = [
                'user_id' => $recipient['id'],
                'type' => 'new_comment',
                'channel' => 'email',
                'recipient' => $recipient['email'],
                'subject' => $title,
                'message' => $fullMessage,
                'data' => [
                    'task_id' => $taskId,
                    'comment' => $commentText,
                    'author' => $author['name']
                ],
                'priority' => 4
            ];
        }

        if (!empty($recipient['telegram_notifications']) && !empty($recipient['telegram_chat_id'])) {
            $telegramMessage = "<b>Новый комментарий</b>\n\n" .
                              "Задача: {$task['title']}\n" .
                              "От: {$author['name']}\n\n" .
                              "<i>" . htmlspecialchars($commentText) . "</i>";

            $notifications[] = [
                'user_id' => $recipient['id'],
                'type' => 'new_comment',
                'channel' => 'telegram',
                'recipient' => $recipient['telegram_chat_id'],
                'message' => $telegramMessage,
                'data' => [
                    'task_id' => $taskId,
                    'comment' => $commentText,
                    'author' => $author['name']
                ],
                'priority' => 4
            ];
        }
    }

    if ($this->useQueue && !empty($notifications)) {
        $this->queue->addBatch($notifications);
    } else if (!empty($notifications)) {
        $this->sendImmediately($notifications);
    }
}
    
    /**
     * Отправить уведомления немедленно (для критичных уведомлений)
     */
    private function sendImmediately($notifications) {
        foreach ($notifications as $notification) {
            try {
                if ($notification['channel'] === 'email') {
                    $this->emailService->send(
                        $notification['recipient'],
                        $notification['subject'],
                        $notification['message']
                    );
                } elseif ($notification['channel'] === 'telegram') {
                    $this->telegramService->sendMessage(
                        $notification['recipient'],
                        $notification['message']
                    );
                }
            } catch (\Exception $e) {
                error_log("Failed to send notification: " . $e->getMessage());
            }
        }
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
    
    private function getUserInfo($userId) {
        $sql = "SELECT * FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}