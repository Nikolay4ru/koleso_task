<?php
namespace App\Controllers;
use App\Models\Task;
use App\Models\User;
use App\Models\File;
use App\Models\Department;
use App\Services\NotificationService;
use PDO;

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
        // Получаем id пользователя и его отдела
        $userId = $_SESSION['user_id'];
        // Получаем department_id из users
        $userModel = new User($this->db);
        $user = $userModel->getById($userId);
        $departmentId = $user['department_id'];

        // Получаем только задачи своего отдела или свои собственные
        $tasks = $this->task->getKanbanTasks($userId, $departmentId);
        $users = $userModel->getAll();
        require_once __DIR__ . '/../../views/tasks/kanban.php';
    }

    public function create() {
        $userModel = new User($this->db);
        $users = $userModel->getAll();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

                    if (!empty($_POST['uploaded_files'])) {
                        $fileModel = new File($this->db);
                        $fileIds = json_decode($_POST['uploaded_files'], true);
                        foreach ($fileIds as $fileId) {
                            $fileModel->attachToTask($fileId, $taskId);
                        }
                    }
                    
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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Метод не поддерживается']);
            exit;
        }
        
        $taskId = $_POST['task_id'] ?? null;
        $oldStatus = $_POST['old_status'] ?? '';
        $newStatus = $_POST['new_status'] ?? null;
        $comment = $_POST['comment'] ?? '';
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$taskId || !$newStatus || !$userId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Недостаточно данных']);
            exit;
        }
        
        try {
            $task = $this->task->getTaskDetails($taskId);
            if (!$task) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Задача не найдена']);
                exit;
            }
            
            $isCreator = $task['creator_id'] == $userId;
            $isAssignee = in_array($userId, array_column($task['assignees'] ?? [], 'id'));
            
            if (!$this->canChangeStatus($oldStatus, $newStatus, $isCreator, $isAssignee)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Недостаточно прав для изменения статуса']);
                exit;
            }
            
            $statusUpdated = $this->task->updateStatus($taskId, $newStatus);
            
            if (!$statusUpdated) {
                throw new \Exception('Не удалось обновить статус в базе данных');
            }
            
            if (!empty($comment)) {
                $statusChangeComment = $this->getStatusChangeMessage($oldStatus, $newStatus) . "\n\n" . $comment;
                $this->task->addComment($taskId, $userId, $statusChangeComment);
            } else {
                $statusChangeComment = $this->getStatusChangeMessage($oldStatus, $newStatus);
                $this->task->addSystemComment($taskId, $statusChangeComment);
            }
            
            $this->notificationService->notifyStatusChanged(
                $taskId, $oldStatus, $newStatus, $userId
            );
            
            if ($newStatus === 'waiting_approval') {
                $this->notificationService->notifyTaskReadyForApproval($taskId, $userId);
            } elseif ($newStatus === 'done') {
                $this->notificationService->notifyTaskCompleted($taskId, $userId);
            } elseif ($oldStatus === 'waiting_approval' && $newStatus === 'in_progress') {
                $this->notificationService->notifyTaskRejected($taskId, $userId, $comment);
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Статус успешно обновлен',
                'new_status' => $newStatus
            ]);
            
        } catch (\Exception $e) {
            error_log('Status update error: ' . $e->getMessage());
            error_log('Error details: ' . print_r([
                'task_id' => $taskId,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'user_id' => $userId
            ], true));
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'error' => 'Ошибка при обновлении статуса: ' . $e->getMessage()
            ]);
        }
        
        exit;
    }

    private function canChangeStatus($oldStatus, $newStatus, $isCreator, $isAssignee) {
        if ($isCreator) {
            if ($oldStatus === 'waiting_approval' && in_array($newStatus, ['done', 'in_progress'])) {
                return true;
            }
            if ($newStatus === 'done' && $oldStatus !== 'waiting_approval') {
                return true;
            }
        }
        if ($isAssignee) {
            $allowedTransitions = [
                'backlog' => ['in_progress'],
                'todo' => ['in_progress'],
                'in_progress' => ['waiting_approval'],
                'waiting_approval' => [],
                'done' => []
            ];
            return isset($allowedTransitions[$oldStatus]) &&
                in_array($newStatus, $allowedTransitions[$oldStatus]);
        }
        return false;
    }

    private function getStatusChangeMessage($oldStatus, $newStatus) {
        $statusLabels = [
            'backlog' => 'Очередь задач',
            'todo' => 'К выполнению',
            'in_progress' => 'В работе',
            'waiting_approval' => 'Ожидает проверки',
            'done' => 'Выполнено'
        ];
        $oldLabel = $statusLabels[$oldStatus] ?? $oldStatus;
        $newLabel = $statusLabels[$newStatus] ?? $newStatus;
        return "Статус изменен с '{$oldLabel}' на '{$newLabel}'";
    }
    
public function view($taskId) {
    try {
        // Получаем данные задачи
        $task = $this->task->getTaskDetails($taskId);
        if (!$task) {
            http_response_code(404);
            require_once __DIR__ . '/../../views/errors/404.php';
            return;
        }
        
        // Получаем комментарии к задаче
        $comments = $this->task->getTaskComments($taskId);
        
        // Создаем единственный экземпляр File модели
        $fileModel = new File($this->db);
        
        // Получаем файлы задачи
        $taskFiles = $fileModel->getTaskFiles($taskId);
        
        // Добавляем файлы к каждому комментарию используя передачу по ссылке
        foreach ($comments as &$comment) {
            $comment['files'] = $fileModel->getCommentFiles($comment['id']);
        }
        unset($comment);
        // ОТЛАДКА: Проверяем финальный массив комментариев
        error_log('Final comments before view: ' . print_r($comments, true));
        
        // Проверяем права пользователя
        $userId = $_SESSION['user_id'] ?? null;
        $isAssignee = $userId && in_array($userId, array_column($task['assignees'] ?? [], 'id'));
        $isWatcher = $userId && in_array($userId, array_column($task['watchers'] ?? [], 'id'));
        $isCreator = $userId && $task['creator_id'] == $userId;
        $canEdit = $isCreator;
        
        // Передаем $db для возможного использования в представлении
        $db = $this->db;
        
        // Передаем данные в представление
        require_once __DIR__ . '/../../views/tasks/view.php';
    } catch (Exception $e) {
        error_log('Task view error: ' . $e->getMessage());
        http_response_code(500);
        require_once __DIR__ . '/../../views/errors/500.php';
    }
}
    
    public function edit($taskId) {
        $task = $this->task->getTaskDetails($taskId);
        if (!$task) {
            http_response_code(404);
            require_once __DIR__ . '/../../views/errors/404.php';
            return;
        }
        $isCreator = $task['creator_id'] == $_SESSION['user_id'];
        if (!$isCreator) {
            header('Location: /tasks/view/' . $taskId);
            exit;
        }
        $userModel = new User($this->db);
        $users = $userModel->getAll();
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
                if (!empty($_POST['uploaded_files'])) {
                    $fileIds = json_decode($_POST['uploaded_files'], true);
                    foreach ($fileIds as $fileId) {
                        $fileModel->attachToTask($fileId, $taskId);
                    }
                }
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
        $uploadedFiles = [];
        if (!empty($_POST['uploaded_files'])) {
            $uploadedFiles = json_decode($_POST['uploaded_files'], true);
        }
        if (empty($comment) && empty($uploadedFiles)) {
            header('Location: /tasks/view/' . $taskId);
            exit;
        }
        try {
            $commentId = $this->task->addComment($taskId, $_SESSION['user_id'], $comment);
            if (!empty($uploadedFiles)) {
                $fileModel = new File($this->db);
                foreach ($uploadedFiles as $fileId) {
                    $fileModel->attachToComment($fileId, $commentId);
                }
            }
            $this->notificationService->notifyNewComment($taskId, $_SESSION['user_id'], $comment);
            header('Location: /tasks/view/' . $taskId . '#comments');
        } catch (\Exception $e) {
            error_log('Add comment error: ' . $e->getMessage());
            header('Location: /tasks/view/' . $taskId);
        }
    }

    public function list() {
        $tasks = $this->task->getAllTasksWithDetails();
        $userModel = new User($this->db);
        $users = $userModel->getAll();
        $departmentModel = new Department($this->db);
        $departments = $departmentModel->getAll();
        require_once __DIR__ . '/../../views/tasks/list.php';
    }

    public function duplicate($taskId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }
        $task = $this->task->getTaskDetails($taskId);
        if (!$task) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Task not found']);
            exit;
        }
        $newTaskData = [
            'title' => $task['title'] . ' (копия)',
            'description' => $task['description'],
            'status' => 'backlog',
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

    // Метод для отображения задач в виде сетки
// Добавьте этот метод в ваш TaskController.php

public function grid() {
    try {
        // Получение параметров фильтрации и пагинации
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 12; // Количество задач на странице для grid view
        $offset = ($page - 1) * $perPage;
        
        // Сбор фильтров
        $filters = [
            'status' => $_GET['status'] ?? '',
            'priority' => $_GET['priority'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];
        
        // Построение SQL запроса с фильтрами
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['status'])) {
            $where[] = 'status = :status';
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['priority'])) {
            $where[] = 'priority = :priority';
            $params[':priority'] = $filters['priority'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = '(title LIKE :search OR description LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Получение общего количества задач
        $countSql = "SELECT COUNT(*) as total FROM tasks WHERE $whereClause";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $totalTasks = $stmt->fetch()['total'];
        $totalPages = ceil($totalTasks / $perPage);
        
        // Получение задач для текущей страницы
        $sql = "SELECT * FROM tasks 
                WHERE $whereClause 
                ORDER BY created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Передача данных в представление
        $currentPage = $page;
        
        // Подключение представления
        require_once __DIR__ . '/../../views/tasks/grid.php';
        
    } catch (Exception $e) {
        // Логирование ошибки
        error_log('Error in TaskController::grid(): ' . $e->getMessage());
        
        // Отображение страницы с ошибкой
        header('HTTP/1.1 500 Internal Server Error');
        echo '<div class="alert alert-danger">Произошла ошибка при загрузке задач. Пожалуйста, попробуйте позже.</div>';
    }
}

}