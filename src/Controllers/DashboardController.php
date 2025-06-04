<?php
namespace App\Controllers;

use App\Models\Task;
use App\Models\User;
use App\Models\Notification;
use App\Models\Department;

class DashboardController {
    private $db;
    private $task;
    private $user;
    private $notification;
    private $department;
    
    public function __construct($db) {
        $this->db = $db;
        $this->task = new Task($db);
        $this->user = new User($db);
        $this->notification = new Notification($db);
        $this->department = new Department($db);
    }
    
    public function index() {
        $userId = $_SESSION['user_id'];
        
        // Получаем статистику пользователя
        $userStats = $this->getUserStatistics($userId);
        
        // Получаем последние задачи пользователя
        $recentTasks = $this->task->getUserRecentTasks($userId, 5);
        
        // Получаем задачи с приближающимися дедлайнами
        $upcomingDeadlines = $this->task->getUpcomingDeadlines($userId, 5);
        
        // Получаем последние уведомления
        $recentNotifications = $this->notification->getUserNotifications($userId, 5);
        $unreadNotificationsCount = $this->notification->getUnreadCount($userId);
        
        // Получаем статистику по статусам задач
        $tasksByStatus = $this->task->getTasksByStatusForUser($userId);
        
        // Получаем активность команды (если есть отдел)
        $teamActivity = $this->getTeamActivity($userId);
        
        // Получаем общую статистику системы (для админов или всех)
        $systemStats = $this->getSystemStatistics();
        
        // Не передаем $db в view, чтобы избежать проблем
        require_once __DIR__ . '/../../views/dashboard/index.php';
    }
    
    private function getUserStatistics($userId) {
        $stats = $this->user->getTaskStatistics($userId);
        
        // Добавляем процент выполнения
        $totalAssigned = $stats['assigned_tasks'];
        $completed = $stats['completed_tasks'];
        $stats['completion_rate'] = $totalAssigned > 0 
            ? round(($completed / $totalAssigned) * 100) 
            : 0;
        
        // Получаем задачи, требующие внимания
        $stats['overdue_tasks'] = $this->task->getOverdueTasksCount($userId);
        $stats['tasks_due_today'] = $this->task->getTasksDueTodayCount($userId);
        
        return $stats;
    }
    
    private function getTeamActivity($userId) {
        // Получаем отдел пользователя
        $user = $this->user->findById($userId);
        
        if (!$user['department_id']) {
            return null;
        }
        
        // Получаем последнюю активность в отделе
        $sql = "SELECT 
                    u.name as user_name,
                    t.title as task_title,
                    t.status,
                    t.updated_at,
                    'task_update' as activity_type
                FROM tasks t
                JOIN task_assignees ta ON t.id = ta.task_id
                JOIN users u ON ta.user_id = u.id
                WHERE u.department_id = :dept_id
                    AND t.updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
                ORDER BY t.updated_at DESC
                LIMIT 10";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':dept_id' => $user['department_id']]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    private function getSystemStatistics() {
        $sql = "SELECT 
                (SELECT COUNT(*) FROM users) as total_users,
                (SELECT COUNT(*) FROM tasks) as total_tasks,
                (SELECT COUNT(*) FROM tasks WHERE status = 'done') as completed_tasks,
                (SELECT COUNT(*) FROM tasks WHERE status IN ('in_progress', 'review')) as active_tasks,
                (SELECT COUNT(*) FROM departments) as total_departments";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
    
    public function getChartData() {
        $userId = $_SESSION['user_id'];
        
        // Данные для графика выполнения задач за последние 7 дней
        $sql = "SELECT 
                    DATE(t.updated_at) as date,
                    COUNT(*) as completed_count
                FROM tasks t
                JOIN task_assignees ta ON t.id = ta.task_id
                WHERE ta.user_id = :user_id 
                    AND t.status = 'done'
                    AND t.updated_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                GROUP BY DATE(t.updated_at)
                ORDER BY date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $completionData = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Данные для круговой диаграммы по приоритетам
        $sql = "SELECT 
                    t.priority,
                    COUNT(*) as count
                FROM tasks t
                JOIN task_assignees ta ON t.id = ta.task_id
                WHERE ta.user_id = :user_id 
                    AND t.status != 'done'
                GROUP BY t.priority";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $priorityData = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode([
            'completion' => $completionData,
            'priorities' => $priorityData
        ]);
        exit;
    }
}