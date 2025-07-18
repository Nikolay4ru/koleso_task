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
    try {
        $task = $this->getTaskInfo($taskId);
        if (!$task) {
            error_log("Task not found for notification: $taskId");
            return false;
        }
        
        $recipients = $this->getTaskRecipients($taskId);
        
        $statusLabels = [
    'backlog' => 'Очередь задач',
    'todo' => 'К выполнению',
    'in_progress' => 'В работе',
    'waiting_approval' => 'Ожидает проверки',
    'done' => 'Выполнено'
];
        
        $notifications = [];
        
        foreach ($recipients as $recipient) {
            if ($recipient['id'] == $changedBy) continue;
            
            $title = "Изменен статус задачи: {$task['title']}";
            $oldLabel = $statusLabels[$oldStatus] ?? $oldStatus;
            $newLabel = $statusLabels[$newStatus] ?? $newStatus;
            $message = "Статус изменен с '{$oldLabel}' на '{$newLabel}'";
            
            // Создаем уведомление в БД
            $this->notification->create([
                'user_id' => $recipient['id'],
                'type' => 'status_changed',
                'title' => $title,
                'message' => $message,
                'task_id' => $taskId
            ]);
            
            // Email уведомление
            if (!empty($recipient['email_notifications']) && !empty($recipient['email'])) {
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
            
            // Telegram уведомление
            if (!empty($recipient['telegram_notifications']) && !empty($recipient['telegram_chat_id'])) {
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
        
        // Отправляем уведомления
        if ($this->useQueue && !empty($notifications)) {
            $this->queue->addBatch($notifications);
        } else {
            $this->sendImmediately($notifications);
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log('NotificationService::notifyStatusChanged error: ' . $e->getMessage());
        return false;
    }
}


     /**
     * Уведомление о том, что задача готова к проверке
     */
    public function notifyTaskReadyForApproval($taskId, $completedBy) {
        $task = $this->getTaskInfo($taskId);
        $creator = $this->getUserInfo($task['creator_id']);
        $completer = $this->getUserInfo($completedBy);
        
        if (!$creator || !$completer || $creator['id'] == $completedBy) {
            return;
        }
        
        $title = "Задача готова к проверке: {$task['title']}";
        $message = "{$completer['name']} выполнил задачу '{$task['title']}' и ждет вашей проверки";
        
        // Создаем уведомление в БД
        $this->notification->create([
            'user_id' => $creator['id'],
            'type' => 'task_ready_approval',
            'title' => $title,
            'message' => $message,
            'task_id' => $taskId
        ]);
        
        $notifications = [];
        
        // Email уведомление
        if ($creator['email_notifications']) {
            $emailMessage = "{$completer['name']} выполнил задачу '{$task['title']}' и ожидает вашей проверки.\n\n" .
                           "Описание задачи: {$task['description']}\n\n" .
                           "Перейти к задаче: https://task.koleso.app/tasks/view/{$taskId}";
            
            $notifications[] = [
                'user_id' => $creator['id'],
                'type' => 'task_ready_approval',
                'channel' => 'email',
                'recipient' => $creator['email'],
                'subject' => $title,
                'message' => $emailMessage,
                'data' => ['task_id' => $taskId],
                'priority' => 3 // Высокий приоритет
            ];
        }
        
        // Telegram уведомление
        if ($creator['telegram_notifications'] && $creator['telegram_chat_id']) {
            $telegramMessage = " <b>Задача готова к проверке</b>\n\n" .
                              "Задача: {$task['title']}\n" .
                              "Исполнитель: {$completer['name']}\n\n" .
                              "<i>Задача выполнена и ожидает вашего подтверждения</i>";
            
            $notifications[] = [
                'user_id' => $creator['id'],
                'type' => 'task_ready_approval',
                'channel' => 'telegram',
                'recipient' => $creator['telegram_chat_id'],
                'message' => $telegramMessage,
                'data' => ['task_id' => $taskId],
                'priority' => 3
            ];
        }
        
        if ($this->useQueue && !empty($notifications)) {
            $this->queue->addBatch($notifications);
        } else {
            $this->sendImmediately($notifications);
        }
    }


    /**
     * Уведомление о том, что задача окончательно выполнена
     */
    public function notifyTaskCompleted($taskId, $approvedBy) {
        $task = $this->getTaskInfo($taskId);
        $recipients = $this->getTaskRecipients($taskId);
        $approver = $this->getUserInfo($approvedBy);
        
        $notifications = [];
        
        foreach ($recipients as $recipient) {
            if ($recipient['id'] == $approvedBy) continue;
            
            $title = "Задача завершена: {$task['title']}";
            $message = "Задача '{$task['title']}' была завершена";
            
            if ($approver && $approvedBy == $task['creator_id']) {
                $message = "Задача '{$task['title']}' была принята и закрыта";
            }
            
            $this->notification->create([
                'user_id' => $recipient['id'],
                'type' => 'task_completed',
                'title' => $title,
                'message' => $message,
                'task_id' => $taskId
            ]);
            
            if ($recipient['email_notifications']) {
                $notifications[] = [
                    'user_id' => $recipient['id'],
                    'type' => 'task_completed',
                    'channel' => 'email',
                    'recipient' => $recipient['email'],
                    'subject' => $title,
                    'message' => $message,
                    'data' => ['task_id' => $taskId],
                    'priority' => 6
                ];
            }
            
            if ($recipient['telegram_notifications'] && $recipient['telegram_chat_id']) {
                $telegramMessage = " <b>Задача завершена</b>\n\n" .
                                  "Задача: {$task['title']}\n\n" .
                                  "<i>Задача успешно выполнена и закрыта</i>";
                
                $notifications[] = [
                    'user_id' => $recipient['id'],
                    'type' => 'task_completed',
                    'channel' => 'telegram',
                    'recipient' => $recipient['telegram_chat_id'],
                    'message' => $telegramMessage,
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

    public function getTaskAssignees($taskId) {
    $sql = "SELECT u.* FROM task_assignees ta
            JOIN users u ON ta.user_id = u.id
            WHERE ta.task_id = :task_id";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([':task_id' => $taskId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    
    /**
     * Уведомление об отклонении задачи
     */
    public function notifyTaskRejected($taskId, $rejectedBy, $reason = '') {
        $task = $this->getTaskInfo($taskId);
        $assignees = $this->getTaskAssignees($taskId);
        $rejector = $this->getUserInfo($rejectedBy);
        
        $notifications = [];
        
        foreach ($assignees as $assignee) {
            if ($assignee['id'] == $rejectedBy) continue;
            
            $title = "Задача возвращена на доработку: {$task['title']}";
            $message = "Задача '{$task['title']}' требует доработки";
            
            if ($reason) {
                $message .= "\n\nПричина: {$reason}";
            }
            
            $this->notification->create([
                'user_id' => $assignee['id'],
                'type' => 'task_rejected',
                'title' => $title,
                'message' => $message,
                'task_id' => $taskId
            ]);
            
            if ($assignee['email_notifications']) {
                $emailMessage = "Задача '{$task['title']}' была возвращена на доработку.\n\n";
                if ($reason) {
                    $emailMessage .= "Причина: {$reason}\n\n";
                }
                $emailMessage .= "Перейти к задаче: https://task.koleso.app/tasks/view/{$taskId}";
                
                $notifications[] = [
                    'user_id' => $assignee['id'],
                    'type' => 'task_rejected',
                    'channel' => 'email',
                    'recipient' => $assignee['email'],
                    'subject' => $title,
                    'message' => $emailMessage,
                    'data' => ['task_id' => $taskId, 'reason' => $reason],
                    'priority' => 4
                ];
            }
            
            if ($assignee['telegram_notifications'] && $assignee['telegram_chat_id']) {
                $telegramMessage = " <b>Задача возвращена на доработку</b>\n\n" .
                                  "Задача: {$task['title']}\n";
                if ($reason) {
                    $telegramMessage .= "Причина: <i>" . htmlspecialchars($reason) . "</i>\n";
                }
                $telegramMessage .= "\n <i>Необходимо внести исправления</i>";
                
                $notifications[] = [
                    'user_id' => $assignee['id'],
                    'type' => 'task_rejected',
                    'channel' => 'telegram',
                    'recipient' => $assignee['telegram_chat_id'],
                    'message' => $telegramMessage,
                    'data' => ['task_id' => $taskId, 'reason' => $reason],
                    'priority' => 4
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
 * Отправляет уведомления немедленно с обработкой ошибок
 */
private function sendImmediately($notifications) {
    foreach ($notifications as $notification) {
        try {
            if ($notification['channel'] === 'email' && isset($this->emailService)) {
                $this->emailService->send(
                    $notification['recipient'],
                    $notification['subject'],
                    $notification['message']
                );
            } elseif ($notification['channel'] === 'telegram' && isset($this->telegramService)) {
                $this->telegramService->sendMessage(
                    $notification['recipient'],
                    $notification['message']
                );
            }
        } catch (Exception $e) {
            error_log("Failed to send {$notification['channel']} notification: " . $e->getMessage());
        }
    }
}
    
/**
 * Безопасно получает информацию о задаче
 */
private function getTaskInfo($taskId) {
    try {
        $sql = "SELECT * FROM tasks WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $taskId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('getTaskInfo error: ' . $e->getMessage());
        return false;
    }
}
    
/**
 * Безопасно получает получателей уведомлений для задачи
 */
private function getTaskRecipients($taskId) {
    try {
        $sql = "SELECT DISTINCT u.id, u.name, u.email, 
                COALESCE(u.email_notifications, 1) as email_notifications,
                COALESCE(u.telegram_notifications, 0) as telegram_notifications,
                u.telegram_chat_id
                FROM users u
                LEFT JOIN task_assignees ta ON u.id = ta.user_id
                LEFT JOIN task_watchers tw ON u.id = tw.user_id
                LEFT JOIN tasks t ON u.id = t.creator_id
                WHERE ta.task_id = :task_id OR tw.task_id = :task_id2 OR t.id = :task_id3";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':task_id' => $taskId, 
            ':task_id2' => $taskId,
            ':task_id3' => $taskId
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('getTaskRecipients error: ' . $e->getMessage());
        return [];
    }
}
    
    private function getUserInfo($userId) {
        $sql = "SELECT * FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}