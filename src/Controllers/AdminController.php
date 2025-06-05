<?php
namespace App\Controllers;

use App\Models\User;
use App\Models\Task;
use App\Models\Department;
use App\Models\Invitation;
use App\Services\EmailService;
use App\Services\NotificationService;

class AdminController {
    private $db;
    private $user;
    private $task;
    private $department;
    private $invitation;
    private $emailService;
    
    public function __construct($db, EmailService $emailService) {
        $this->db = $db;
        $this->user = new User($db);
        $this->task = new Task($db);
        $this->department = new Department($db);
        $this->invitation = new Invitation($db);
        $this->emailService = $emailService;
        
        // Проверка прав администратора
        $this->checkAdminAccess();
    }
    
    private function checkAdminAccess() {
        if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            header('Location: /dashboard');
            exit;
        }
    }
    
    public function dashboard() {
        // Статистика системы
        $stats = [
            'total_users' => $this->user->getCount(),
            'active_users' => $this->user->getActiveCount(30), // Активные за последние 30 дней
            'total_tasks' => $this->task->getCount(),
            'completed_tasks' => $this->task->getCompletedCount(),
            'total_departments' => $this->department->getCount(),
            'pending_invitations' => $this->invitation->getPendingCount()
        ];
        
        // Графики активности
        $activityData = $this->getActivityData();
        
        // Последние действия в системе
        $recentActivity = $this->getRecentActivity();
        
        require_once __DIR__ . '/../../views/admin/dashboard.php';
    }
    
    public function users() {
        $users = $this->user->getAllWithStats();
        $departments = $this->department->getAll();
        
        require_once __DIR__ . '/../../views/admin/users.php';
    }
    
    public function editUser($userId) {
        $user = $this->user->findById($userId);
        
        if (!$user) {
            header('Location: /admin/users');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => trim($_POST['name']),
                'email' => trim($_POST['email']),
                'department_id' => $_POST['department_id'] ?: null,
                'is_admin' => isset($_POST['is_admin']) ? 1 : 0,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'phone' => $_POST['phone'] ?: null,
                'bio' => $_POST['bio'] ?: null
            ];
            
            // Если изменяется email, проверяем уникальность
            if ($data['email'] !== $user['email']) {
                if ($this->user->exists($data['email'])) {
                    $error = 'Пользователь с таким email уже существует';
                    $departments = $this->department->getAll();
                    require_once __DIR__ . '/../../views/admin/edit-user.php';
                    return;
                }
            }
            
            try {
                $this->user->updateByAdmin($userId, $data);
                
                // Если меняется пароль
                if (!empty($_POST['new_password'])) {
                    $this->user->updatePassword($userId, $_POST['new_password']);
                }
                
                $_SESSION['success'] = 'Пользователь успешно обновлен';
                header('Location: /admin/users');
                exit;
            } catch (\Exception $e) {
                $error = 'Ошибка при обновлении пользователя';
            }
        }
        
        $departments = $this->department->getAll();
        $userStats = $this->user->getTaskStatistics($userId);
        
        require_once __DIR__ . '/../../views/admin/edit-user.php';
    }
    
    public function deleteUser($userId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }
        
        // Нельзя удалить самого себя
        if ($userId == $_SESSION['user_id']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Нельзя удалить свой аккаунт']);
            exit;
        }
        
        try {
            $this->user->delete($userId);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Ошибка при удалении']);
        }
    }
    
    public function tasks() {
        // Получаем все задачи с расширенной информацией
        $tasks = $this->task->getAllTasksForAdmin();
        $users = $this->user->getAll();
        $departments = $this->department->getAll();
        
        // Статистика по задачам
        $taskStats = [
            'by_status' => $this->task->getStatsByStatus(),
            'by_priority' => $this->task->getStatsByPriority(),
            'overdue' => $this->task->getOverdueCount()
        ];
        
        require_once __DIR__ . '/../../views/admin/tasks.php';
    }
    
    public function invitations() {
        $invitations = $this->invitation->getAll();
        $departments = $this->department->getAll();
        
        require_once __DIR__ . '/../../views/admin/invitations.php';
    }
    
    public function sendInvitation() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/invitations');
            exit;
        }
        
        $email = trim($_POST['email']);
        $name = trim($_POST['name']);
        $departmentId = $_POST['department_id'] ?: null;
        $isAdmin = isset($_POST['is_admin']) ? 1 : 0;
        $message = $_POST['message'] ?: '';
        
        // Валидация
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Неверный формат email';
            header('Location: /admin/invitations');
            exit;
        }
        
        // Проверяем, не существует ли уже пользователь
        if ($this->user->exists($email)) {
            $_SESSION['error'] = 'Пользователь с таким email уже существует';
            header('Location: /admin/invitations');
            exit;
        }
        
        // Проверяем, не отправлено ли уже приглашение
        if ($this->invitation->existsPending($email)) {
            $_SESSION['error'] = 'Приглашение уже отправлено на этот email';
            header('Location: /admin/invitations');
            exit;
        }
        
        try {
            // Создаем приглашение
            $token = $this->generateInvitationToken();
            $invitationId = $this->invitation->create([
                'email' => $email,
                'name' => $name,
                'token' => $token,
                'department_id' => $departmentId,
                'is_admin' => $isAdmin,
                'invited_by' => $_SESSION['user_id'],
                'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days'))
            ]);
            
            // Отправляем email
            $inviteUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/register?token=' . $token;
            $emailBody = $this->getInvitationEmailBody($name, $message, $inviteUrl);
            
            $this->emailService->send(
                $email,
                'Приглашение в систему управления задачами',
                $emailBody
            );
            
            $_SESSION['success'] = 'Приглашение успешно отправлено';
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Ошибка при отправке приглашения';
            error_log('Invitation error: ' . $e->getMessage());
        }
        
        header('Location: /admin/invitations');
        exit;
    }
    
    public function resendInvitation($invitationId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }
        
        $invitation = $this->invitation->findById($invitationId);
        
        if (!$invitation || $invitation['status'] !== 'pending') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Приглашение не найдено']);
            exit;
        }
        
        try {
            // Обновляем токен и срок действия
            $newToken = $this->generateInvitationToken();
            $this->invitation->updateToken($invitationId, $newToken);
            
            // Отправляем email
            $inviteUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/register?token=' . $newToken;
            $emailBody = $this->getInvitationEmailBody($invitation['name'], '', $inviteUrl);
            
            $this->emailService->send(
                $invitation['email'],
                'Повторное приглашение в систему управления задачами',
                $emailBody
            );
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Ошибка при отправке']);
        }
    }
    
    public function cancelInvitation($invitationId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }
        
        try {
            $this->invitation->cancel($invitationId);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Ошибка при отмене']);
        }
    }
    

    
    public function reports() {
        // Различные отчеты
        $reports = [
            'user_activity' => $this->getUserActivityReport(),
            'task_completion' => $this->getTaskCompletionReport(),
            'department_performance' => $this->getDepartmentPerformanceReport()
        ];
        
        require_once __DIR__ . '/../../views/admin/reports.php';
    }
    
    private function generateInvitationToken() {
        return bin2hex(random_bytes(32));
    }
    
    private function getInvitationEmailBody($name, $customMessage, $inviteUrl) {
        $message = "
        <h2>Здравствуйте, {$name}!</h2>
        <p>Вы приглашены присоединиться к системе управления задачами.</p>
        ";
        
        if (!empty($customMessage)) {
            $message .= "<p><em>{$customMessage}</em></p>";
        }
        
        $message .= "
        <p>Для регистрации перейдите по ссылке:</p>
        <p><a href='{$inviteUrl}' style='display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>Принять приглашение</a></p>
        <p>Приглашение действительно в течение 7 дней.</p>
        <p>Если вы не ожидали этого письма, просто проигнорируйте его.</p>
        ";
        
        return $message;
    }
    
    private function getActivityData() {
        // Данные для графиков активности
        $sql = "SELECT 
                DATE(created_at) as date,
                COUNT(*) as tasks_created
                FROM tasks
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date ASC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    private function getRecentActivity() {
        // Последние действия в системе
        $sql = "SELECT 
                'task_created' as type,
                t.title as description,
                u.name as user_name,
                t.created_at as timestamp
                FROM tasks t
                JOIN users u ON t.creator_id = u.id
                ORDER BY t.created_at DESC
                LIMIT 10";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    private function getUserActivityReport() {
        $sql = "SELECT 
                u.name,
                COUNT(DISTINCT t.id) as created_tasks,
                COUNT(DISTINCT ta.task_id) as assigned_tasks,
                COUNT(DISTINCT tc.id) as comments_made,
                MAX(GREATEST(
                    COALESCE(t.created_at, '1970-01-01'),
                    COALESCE(tc.created_at, '1970-01-01')
                )) as last_activity
                FROM users u
                LEFT JOIN tasks t ON u.id = t.creator_id
                LEFT JOIN task_assignees ta ON u.id = ta.user_id
                LEFT JOIN task_comments tc ON u.id = tc.user_id
                GROUP BY u.id
                ORDER BY last_activity DESC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    private function getTaskCompletionReport() {
        $sql = "SELECT 
                DATE_FORMAT(updated_at, '%Y-%m') as month,
                COUNT(*) as completed_tasks,
                AVG(DATEDIFF(updated_at, created_at)) as avg_completion_days
                FROM tasks
                WHERE status = 'done'
                AND updated_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(updated_at, '%Y-%m')
                ORDER BY month ASC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    private function getDepartmentPerformanceReport() {
        $sql = "SELECT 
                d.name as department,
                COUNT(DISTINCT u.id) as user_count,
                COUNT(DISTINCT t.id) as total_tasks,
                COUNT(DISTINCT CASE WHEN t.status = 'done' THEN t.id END) as completed_tasks,
                AVG(CASE WHEN t.status = 'done' THEN DATEDIFF(t.updated_at, t.created_at) END) as avg_completion_days
                FROM departments d
                LEFT JOIN users u ON d.id = u.department_id
                LEFT JOIN task_assignees ta ON u.id = ta.user_id
                LEFT JOIN tasks t ON ta.task_id = t.id
                GROUP BY d.id
                ORDER BY completed_tasks DESC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

public function createDepartment() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /admin/departments');
        exit;
    }
    
    $name = trim($_POST['name']);
    $description = trim($_POST['description'] ?? '');
    $headUserId = $_POST['head_user_id'] ?: null;
    
    if (empty($name)) {
        $_SESSION['error'] = 'Название отдела обязательно';
        header('Location: /admin/departments');
        exit;
    }
    
    try {
        $this->department->create([
            'name' => $name,
            'description' => $description,
            'head_user_id' => $headUserId
        ]);
        
        $_SESSION['success'] = 'Отдел успешно создан';
    } catch (\Exception $e) {
        $_SESSION['error'] = 'Ошибка при создании отдела';
        error_log('Department creation error: ' . $e->getMessage());
    }
    
    header('Location: /admin/departments');
    exit;
}


public function departments() {
        $departments = $this->department->getAllWithStats();
        
        // Получаем всех пользователей для формы
        $allUsers = $this->user->getAll();
        
        // Добавляем пользователей к каждому отделу
        foreach ($departments as &$dept) {
            $dept['users'] = $this->department->getUsersByDepartment($dept['id']);
        }
        
        require_once __DIR__ . '/../../views/admin/departments.php';
    }

public function editDepartment($departmentId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }
    
    $name = trim($_POST['name']);
    $description = trim($_POST['description'] ?? '');
    
    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Название отдела обязательно']);
        exit;
    }
    
    try {
        $this->department->update($departmentId, [
            'name' => $name,
            'description' => $description
        ]);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Ошибка при обновлении отдела']);
    }
}

public function deleteDepartment($departmentId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }
    
    // Проверяем, есть ли пользователи в отделе
    $users = $this->department->getUsersByDepartment($departmentId);
    
    if (!empty($users)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Нельзя удалить отдел с сотрудниками']);
        exit;
    }
    
    try {
        $this->department->delete($departmentId);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Ошибка при удалении отдела']);
    }
}
}