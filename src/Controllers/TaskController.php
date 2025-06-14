<?php
namespace App\Controllers;

use App\Models\Task;
use App\Models\User;
use App\Models\File;
use App\Services\NotificationService;

class TaskController {
    private $db;
    private $task;
    private $notificationService;
    
    public function __construct($db, NotificationService $notificationService) {
        $this->db = $db;
        $this->task = new Task($db);
        $this->notificationService = $notificationService;
    }
    
    public function kanban() {
        $tasks = $this->task->getKanbanTasks();
        $userModel = new User($this->db);
        $users = $userModel->getAll();
        require_once __DIR__ . '/../../views/tasks/kanban.php';
    }
    
    public function create() {
        // Получаем список пользователей для формы
        $userModel = new User($this->db);
        $users = $userModel->getAll();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Валидация данных
            $errors = [];
            
            $title = trim($_POST['title'] ?? '');
            if (empty($title)) {
                $errors[] = 'Название задачи обязательно для заполнения';
            }
            
            if (!empty($errors)) {
                $error = implode('<br>', $errors);
            } else {
                $data = [
                    'title' => $title,
                    'description' => $_POST['description'] ?? '',
                    'status' => $_POST['status'] ?? 'backlog',
                    'priority' => $_POST['priority'] ?? 'medium',
                    'creator_id' => $_SESSION['user_id'],
                    'deadline' => !empty($_POST['deadline']) ? $_POST['deadline'] : null,
                    'assignees' => $_POST['assignees'] ?? [],
                    'watchers' => $_POST['watchers'] ?? []
                ];
                
                try {
                    $taskId = $this->task->create($data);


                     // Обрабатываем загруженные файлы
                    if (!empty($_POST['uploaded_files'])) {
                        $fileModel = new File($this->db);
                        $fileIds = json_decode($_POST['uploaded_files'], true);
                        foreach ($fileIds as $fileId) {
                            $fileModel->attachToTask($fileId, $taskId);
                        }
                    }
                    
                    // Отправляем уведомления
                    $this->notificationService->notifyTaskCreated($taskId, $_SESSION['user_id']);
                    
                    header('Location: /tasks/kanban');
                    exit;
                } catch (\Exception $e) {
                    $error = 'Ошибка при создании задачи. Попробуйте еще раз.';
                    error_log('Task creation error: ' . $e->getMessage());
                }
            }
        }
        
        require_once __DIR__ . '/../../views/tasks/create.php';
    }
    
        public function updateStatus() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $taskId = $_POST['task_id'];
            $oldStatus = $_POST['old_status'] ?? '';
            $newStatus = $_POST['new_status'];
            $comment = $_POST['comment'] ?? '';
            $userId = $_SESSION['user_id'];
            
            // Получаем информацию о задаче для проверки прав
            $task = $this->task->getTaskDetails($taskId);
            
            if (!$task) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Задача не найдена']);
                exit;
            }
            
            // Проверяем права на изменение статуса
            $isCreator = $task['creator_id'] == $userId;
            $isAssignee = in_array($userId, array_column($task['assignees'], 'id'));
            
            if (!$this->canChangeStatus($oldStatus, $newStatus, $isCreator, $isAssignee)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Недостаточно прав для изменения статуса']);
                exit;
            }
            
            try {
                // Обновляем статус задачи
                $this->task->updateStatus($taskId, $newStatus);
                
                // Добавляем комментарий о смене статуса если есть
                if (!empty($comment)) {
                    $statusChangeComment = $this->getStatusChangeMessage($oldStatus, $newStatus) . "\n\n" . $comment;
                    $this->task->addComment($taskId, $userId, $statusChangeComment);
                } else {
                    // Добавляем системный комментарий о смене статуса
                    $statusChangeComment = $this->getStatusChangeMessage($oldStatus, $newStatus);
                    $this->task->addSystemComment($taskId, $statusChangeComment);
                }
                
                // Отправляем уведомления
                $this->notificationService->notifyStatusChanged(
                    $taskId, 
                    $oldStatus, 
                    $newStatus, 
                    $userId
                );
                
                // Дополнительные уведомления для специальных случаев
                if ($newStatus === 'waiting_approval') {
                    $this->notificationService->notifyTaskReadyForApproval($taskId, $userId);
                } elseif ($newStatus === 'done') {
                    $this->notificationService->notifyTaskCompleted($taskId, $userId);
                } elseif ($oldStatus === 'waiting_approval' && $newStatus === 'in_progress') {
                    $this->notificationService->notifyTaskRejected($taskId, $userId, $comment);
                }
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
            } catch (\Exception $e) {
                error_log('Status update error: ' . $e->getMessage());
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Ошибка при обновлении статуса']);
            }
            exit;
        }
    }


     /**
     * Проверяет, может ли пользователь изменить статус задачи
     */
    private function canChangeStatus($oldStatus, $newStatus, $isCreator, $isAssignee) {
        // Создатель может всегда изменить статус
        if ($isCreator) {
            return true;
        }
        
        // Исполнители могут изменять статусы в рамках своей работы
        if ($isAssignee) {
            $allowedTransitions = [
                'backlog' => ['todo', 'in_progress'],
                'todo' => ['in_progress', 'backlog'],
                'in_progress' => ['review', 'waiting_approval', 'todo'],
                'review' => ['in_progress', 'waiting_approval'],
                'waiting_approval' => [], // Только создатель может изменять этот статус
                'done' => ['in_progress'] // Переоткрытие задачи
            ];
            
            return isset($allowedTransitions[$oldStatus]) && 
                   in_array($newStatus, $allowedTransitions[$oldStatus]);
        }
        
        return false;
    }
    
    /**
     * Возвращает сообщение о смене статуса
     */
    private function getStatusChangeMessage($oldStatus, $newStatus) {
        $statusLabels = [
            'backlog' => 'Бэклог',
            'todo' => 'К выполнению',
            'in_progress' => 'В работе',
            'review' => 'На проверке',
            'waiting_approval' => 'Ожидает проверки',
            'done' => 'Выполнено'
        ];
        
        $oldLabel = $statusLabels[$oldStatus] ?? $oldStatus;
        $newLabel = $statusLabels[$newStatus] ?? $newStatus;
        
        return "Статус изменен с '{$oldLabel}' на '{$newLabel}'";
    }
    
    public function view($taskId) {
        $task = $this->task->getTaskDetails($taskId);
        
        if (!$task) {
            http_response_code(404);
            require_once __DIR__ . '/../../views/errors/404.php';
            return;
        }
        
        // Получаем комментарии к задаче
        $comments = $this->task->getTaskComments($taskId);


         // Получаем файлы задачи
        $fileModel = new File($this->db);
        $taskFiles = $fileModel->getTaskFiles($taskId);


         // Получаем файлы для каждого комментария
        foreach ($comments as &$comment) {
            $comment['files'] = $fileModel->getCommentFiles($comment['id']);
        }
        
        // Проверяем права доступа
        $isAssignee = in_array($_SESSION['user_id'], array_column($task['assignees'], 'id'));
        $isWatcher = in_array($_SESSION['user_id'], array_column($task['watchers'], 'id'));
        $isCreator = $task['creator_id'] == $_SESSION['user_id'];
        $canEdit = $isCreator || $isAssignee;
        
        require_once __DIR__ . '/../../views/tasks/view.php';
    }
    
    public function edit($taskId) {
        $task = $this->task->getTaskDetails($taskId);
        
        if (!$task) {
            http_response_code(404);
            require_once __DIR__ . '/../../views/errors/404.php';
            return;
        }
        
        // Проверяем права на редактирование
        $isAssignee = in_array($_SESSION['user_id'], array_column($task['assignees'], 'id'));
        $isCreator = $task['creator_id'] == $_SESSION['user_id'];
        
        if (!$isCreator && !$isAssignee) {
            header('Location: /tasks/view/' . $taskId);
            exit;
        }
        
        // Получаем список пользователей
        $userModel = new User($this->db);
        $users = $userModel->getAll();


         // Получаем файлы задачи
        $fileModel = new File($this->db);
        $taskFiles = $fileModel->getTaskFiles($taskId);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'title' => trim($_POST['title'] ?? ''),
                'description' => $_POST['description'] ?? '',
                'status' => $_POST['status'] ?? 'backlog',
                'priority' => $_POST['priority'] ?? 'medium',
                'deadline' => !empty($_POST['deadline']) ? $_POST['deadline'] : null,
                'assignees' => $_POST['assignees'] ?? [],
                'watchers' => $_POST['watchers'] ?? []
            ];
            
            try {
                $this->task->update($taskId, $data);


                // Обрабатываем новые загруженные файлы
                if (!empty($_POST['uploaded_files'])) {
                    $fileIds = json_decode($_POST['uploaded_files'], true);
                    foreach ($fileIds as $fileId) {
                        $fileModel->attachToTask($fileId, $taskId);
                    }
                }
                
                // Отправляем уведомления об изменении
                $this->notificationService->notifyTaskUpdated($taskId, $_SESSION['user_id']);
                
                header('Location: /tasks/view/' . $taskId);
                exit;
            } catch (\Exception $e) {
                $error = 'Ошибка при обновлении задачи.';
                error_log('Task update error: ' . $e->getMessage());
            }
        }
        
        require_once __DIR__ . '/../../views/tasks/edit.php';
    }
    
    public function delete($taskId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /tasks/kanban');
            exit;
        }
        
        $task = $this->task->getTaskDetails($taskId);
        
        if (!$task) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Задача не найдена']);
            exit;
        }
        
        // Проверяем права на удаление (только создатель)
        if ($task['creator_id'] != $_SESSION['user_id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Недостаточно прав']);
            exit;
        }
        
        try {
            $this->task->delete($taskId);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Ошибка при удалении']);
        }
    }
    
    public function addComment($taskId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /tasks/view/' . $taskId);
            exit;
        }
        
        $comment = trim($_POST['comment'] ?? '');
        
        if (empty($comment)) {
            header('Location: /tasks/view/' . $taskId);
            exit;
        }
        
        try {
             $commentId = $this->task->addComment($taskId, $_SESSION['user_id'], $comment);
             
            
            // Обрабатываем загруженные файлы
            if (!empty($_POST['uploaded_files'])) {
                $fileModel = new File($this->db);
                $fileIds = json_decode($_POST['uploaded_files'], true);
                foreach ($fileIds as $fileId) {
                    $fileModel->attachToComment($fileId, $commentId['id']);
                }
            }
            
            // Отправляем уведомления о новом комментарии
            $this->notificationService->notifyNewComment($taskId, $_SESSION['user_id'], $comment);
            
            header('Location: /tasks/view/' . $taskId . '#comments');
        } catch (\Exception $e) {
            error_log('Add comment error: ' . $e->getMessage());
            header('Location: /tasks/view/' . $taskId);
        }
    }


public function list() {
    // Получаем все задачи с дополнительной информацией
    $tasks = $this->task->getAllTasksWithDetails();
    
    // Получаем списки для фильтров
    $userModel = new User($this->db);
    $users = $userModel->getAll();
    
    $departmentModel = new \App\Models\Department($this->db);
    $departments = $departmentModel->getAll();
    
    require_once __DIR__ . '/../../views/tasks/list.php';
}

public function duplicate($taskId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }
    
    // Получаем информацию о задаче
    $task = $this->task->getTaskDetails($taskId);
    
    if (!$task) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Task not found']);
        exit;
    }
    
    // Создаем копию
    $newTaskData = [
        'title' => $task['title'] . ' (копия)',
        'description' => $task['description'],
        'status' => 'backlog', // Новая задача всегда в бэклоге
        'priority' => $task['priority'],
        'creator_id' => $_SESSION['user_id'],
        'deadline' => $task['deadline'],
        'assignees' => array_column($task['assignees'], 'id'),
        'watchers' => array_column($task['watchers'], 'id')
    ];
    
    try {
        $newTaskId = $this->task->create($newTaskData);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'newTaskId' => $newTaskId]);
    } catch (\Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Failed to duplicate task']);
    }
}

public function grid() {
    // Получаем все задачи для grid view
    $tasks = $this->task->getAllTasksWithDetails();
    
    // Получаем списки для фильтров
    $userModel = new User($this->db);
    $users = $userModel->getAll();
    
    $departmentModel = new \App\Models\Department($this->db);
    $departments = $departmentModel->getAll();
    
    require_once __DIR__ . '/../../views/tasks/grid.php';
}


}