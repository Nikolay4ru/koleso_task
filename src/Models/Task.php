<?php
namespace App\Models;

use PDO;

class Task {
    private $db;
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    public function create($data) {
        $this->db->beginTransaction();
        
        try {
            // Создаем задачу
            $sql = "INSERT INTO tasks (title, description, status, priority, creator_id, deadline) 
                    VALUES (:title, :description, :status, :priority, :creator_id, :deadline)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':title' => $data['title'],
                ':description' => $data['description'],
                ':status' => $data['status'] ?? 'backlog',
                ':priority' => $data['priority'] ?? 'medium',
                ':creator_id' => $data['creator_id'],
                ':deadline' => $data['deadline'] ?? null
            ]);
            
            $taskId = $this->db->lastInsertId();
            
            // Добавляем исполнителей
            if (!empty($data['assignees'])) {
                $this->addAssignees($taskId, $data['assignees']);
            }
            
            // Добавляем наблюдателей
            if (!empty($data['watchers'])) {
                $this->addWatchers($taskId, $data['watchers']);
            }
            
            $this->db->commit();
            return $taskId;
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function addAssignees($taskId, $userIds) {
        $sql = "INSERT INTO task_assignees (task_id, user_id) VALUES (:task_id, :user_id)";
        $stmt = $this->db->prepare($sql);
        
        foreach ($userIds as $userId) {
            $stmt->execute([
                ':task_id' => $taskId,
                ':user_id' => $userId
            ]);
        }
    }
    
    public function addWatchers($taskId, $userIds) {
        $sql = "INSERT INTO task_watchers (task_id, user_id) VALUES (:task_id, :user_id)";
        $stmt = $this->db->prepare($sql);
        
        foreach ($userIds as $userId) {
            $stmt->execute([
                ':task_id' => $taskId,
                ':user_id' => $userId
            ]);
        }
    }
    
    public function getKanbanTasks() {
        $sql = "SELECT t.*, u.name as creator_name,
                GROUP_CONCAT(DISTINCT au.name) as assignee_names
                FROM tasks t
                JOIN users u ON t.creator_id = u.id
                LEFT JOIN task_assignees ta ON t.id = ta.task_id
                LEFT JOIN users au ON ta.user_id = au.id
                GROUP BY t.id
                ORDER BY t.priority DESC, t.created_at DESC";
        
        $stmt = $this->db->query($sql);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Группируем по статусам
        $kanban = [
            'backlog' => [],
            'todo' => [],
            'in_progress' => [],
            'review' => [],
            'done' => []
        ];
        
        foreach ($tasks as $task) {
            $kanban[$task['status']][] = $task;
        }
        
        return $kanban;
    }
    
    public function updateStatus($taskId, $status) {
        $sql = "UPDATE tasks SET status = :status WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $taskId,
            ':status' => $status
        ]);
    }

    public function getUserRecentTasks($userId, $limit = 10) {
    $sql = "SELECT t.*, 
            GROUP_CONCAT(DISTINCT u.name) as assignee_names
            FROM tasks t
            LEFT JOIN task_assignees ta ON t.id = ta.task_id
            LEFT JOIN users u ON ta.user_id = u.id
            WHERE (ta.user_id = :user_id OR t.creator_id = :user_id2)
                AND t.status != 'done'
            GROUP BY t.id
            ORDER BY t.updated_at DESC
            LIMIT :limit";
    
    $stmt = $this->db->prepare($sql);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':user_id2', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getUpcomingDeadlines($userId, $days = 7) {
    $sql = "SELECT t.* 
            FROM tasks t
            JOIN task_assignees ta ON t.id = ta.task_id
            WHERE ta.user_id = :user_id
                AND t.deadline IS NOT NULL
                AND t.deadline BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL :days DAY)
                AND t.status != 'done'
            ORDER BY t.deadline ASC";
    
    $stmt = $this->db->prepare($sql);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':days', $days, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getTasksByStatusForUser($userId) {
    $sql = "SELECT 
            t.status,
            COUNT(*) as count
            FROM tasks t
            JOIN task_assignees ta ON t.id = ta.task_id
            WHERE ta.user_id = :user_id
            GROUP BY t.status";
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute([':user_id' => $userId]);
    
    $result = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $result[$row['status']] = $row['count'];
    }
    
    return $result;
}

public function getOverdueTasksCount($userId) {
    $sql = "SELECT COUNT(*) as count
            FROM tasks t
            JOIN task_assignees ta ON t.id = ta.task_id
            WHERE ta.user_id = :user_id
                AND t.deadline < NOW()
                AND t.status != 'done'";
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute([':user_id' => $userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['count'];
}

public function getTasksDueTodayCount($userId) {
    $sql = "SELECT COUNT(*) as count
            FROM tasks t
            JOIN task_assignees ta ON t.id = ta.task_id
            WHERE ta.user_id = :user_id
                AND DATE(t.deadline) = CURDATE()
                AND t.status != 'done'";
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute([':user_id' => $userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['count'];
}

public function getTaskDetails($taskId) {
    // Получаем основную информацию о задаче
    $sql = "SELECT t.*, 
            u.name as creator_name,
            u.email as creator_email
            FROM tasks t
            JOIN users u ON t.creator_id = u.id
            WHERE t.id = :task_id";
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute([':task_id' => $taskId]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$task) {
        return null;
    }
    
    // Получаем исполнителей
    $sql = "SELECT u.id, u.name, u.email, d.name as department_name
            FROM task_assignees ta
            JOIN users u ON ta.user_id = u.id
            LEFT JOIN departments d ON u.department_id = d.id
            WHERE ta.task_id = :task_id";
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute([':task_id' => $taskId]);
    $task['assignees'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Получаем наблюдателей
    $sql = "SELECT u.id, u.name, u.email, d.name as department_name
            FROM task_watchers tw
            JOIN users u ON tw.user_id = u.id
            LEFT JOIN departments d ON u.department_id = d.id
            WHERE tw.task_id = :task_id";
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute([':task_id' => $taskId]);
    $task['watchers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $task;
}

public function update($taskId, $data) {
    $this->db->beginTransaction();
    
    try {
        // Обновляем основную информацию
        $sql = "UPDATE tasks SET 
                title = :title,
                description = :description,
                status = :status,
                priority = :priority,
                deadline = :deadline,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $taskId,
            ':title' => $data['title'],
            ':description' => $data['description'],
            ':status' => $data['status'],
            ':priority' => $data['priority'],
            ':deadline' => $data['deadline']
        ]);
        
        // Обновляем исполнителей
        $sql = "DELETE FROM task_assignees WHERE task_id = :task_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':task_id' => $taskId]);
        
        if (!empty($data['assignees'])) {
            $this->addAssignees($taskId, $data['assignees']);
        }
        
        // Обновляем наблюдателей
        $sql = "DELETE FROM task_watchers WHERE task_id = :task_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':task_id' => $taskId]);
        
        if (!empty($data['watchers'])) {
            $this->addWatchers($taskId, $data['watchers']);
        }
        
        $this->db->commit();
        return true;
        
    } catch (\Exception $e) {
        $this->db->rollBack();
        throw $e;
    }
}

public function delete($taskId) {
    $sql = "DELETE FROM tasks WHERE id = :id";
    $stmt = $this->db->prepare($sql);
    return $stmt->execute([':id' => $taskId]);
}

public function getTaskComments($taskId) {
    $sql = "SELECT tc.*, u.name as user_name
            FROM task_comments tc
            JOIN users u ON tc.user_id = u.id
            WHERE tc.task_id = :task_id
            ORDER BY tc.created_at DESC";
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute([':task_id' => $taskId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function addComment($taskId, $userId, $comment) {
    $sql = "INSERT INTO task_comments (task_id, user_id, comment) 
            VALUES (:task_id, :user_id, :comment)";
    
    $stmt = $this->db->prepare($sql);
    return $stmt->execute([
        ':task_id' => $taskId,
        ':user_id' => $userId,
        ':comment' => $comment
    ]);
}

public function getActiveTasksForUser($userId) {
    $sql = "SELECT t.*, 
            GROUP_CONCAT(DISTINCT u.name) as assignee_names
            FROM tasks t
            LEFT JOIN task_assignees ta ON t.id = ta.task_id
            LEFT JOIN users u ON ta.user_id = u.id
            WHERE (ta.user_id = :user_id OR t.creator_id = :user_id2)
                AND t.status IN ('todo', 'in_progress', 'review')
            GROUP BY t.id
            ORDER BY 
                CASE t.priority 
                    WHEN 'urgent' THEN 1 
                    WHEN 'high' THEN 2 
                    WHEN 'medium' THEN 3 
                    WHEN 'low' THEN 4 
                END,
                t.deadline ASC";
    
    $stmt = $this->db->prepare($sql);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':user_id2', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


public function getAllTasksWithDetails() {
    $sql = "SELECT 
            t.*,
            u.name as creator_name,
            GROUP_CONCAT(DISTINCT CONCAT(au.id, ':', au.name) SEPARATOR '|') as assignees_data,
            GROUP_CONCAT(DISTINCT wu.name SEPARATOR ', ') as watcher_names,
            COUNT(DISTINCT ta.user_id) as assignee_count,
            COUNT(DISTINCT tw.user_id) as watcher_count,
            COUNT(DISTINCT tc.id) as comment_count
        FROM tasks t
        LEFT JOIN users u ON t.creator_id = u.id
        LEFT JOIN task_assignees ta ON t.id = ta.task_id
        LEFT JOIN users au ON ta.user_id = au.id
        LEFT JOIN task_watchers tw ON t.id = tw.task_id
        LEFT JOIN users wu ON tw.user_id = wu.id
        LEFT JOIN task_comments tc ON t.id = tc.task_id
        GROUP BY t.id
        ORDER BY t.created_at DESC";
    
    $stmt = $this->db->query($sql);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Обработка данных исполнителей
    foreach ($tasks as &$task) {
        $task['assignees'] = [];
        if (!empty($task['assignees_data'])) {
            $assigneePairs = explode('|', $task['assignees_data']);
            foreach ($assigneePairs as $pair) {
                list($id, $name) = explode(':', $pair);
                $task['assignees'][] = ['id' => $id, 'name' => $name];
            }
        }
    }
    
    return $tasks;
}

public function getTasksForUser($userId, $filters = []) {
    $sql = "SELECT 
            t.*,
            u.name as creator_name,
            GROUP_CONCAT(DISTINCT au.name SEPARATOR ', ') as assignee_names
        FROM tasks t
        LEFT JOIN users u ON t.creator_id = u.id
        LEFT JOIN task_assignees ta ON t.id = ta.task_id
        LEFT JOIN users au ON ta.user_id = au.id
        WHERE (ta.user_id = :user_id OR t.creator_id = :user_id2)";
    
    $params = [
        ':user_id' => $userId,
        ':user_id2' => $userId
    ];
    
    // Применяем фильтры
    if (!empty($filters['status'])) {
        $sql .= " AND t.status IN (" . implode(',', array_fill(0, count($filters['status']), '?')) . ")";
    }
    
    if (!empty($filters['priority'])) {
        $sql .= " AND t.priority IN (" . implode(',', array_fill(0, count($filters['priority']), '?')) . ")";
    }
    
    if (!empty($filters['period'])) {
        switch ($filters['period']) {
            case 'today':
                $sql .= " AND DATE(t.created_at) = CURDATE()";
                break;
            case 'week':
                $sql .= " AND t.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $sql .= " AND t.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                break;
            case 'overdue':
                $sql .= " AND t.deadline < NOW() AND t.status != 'done'";
                break;
        }
    }
    
    $sql .= " GROUP BY t.id ORDER BY t.created_at DESC";
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getAllTasksForAdmin() {
    $sql = "SELECT 
            t.*,
            u.name as creator_name,
            d.name as creator_department,
            GROUP_CONCAT(DISTINCT au.name SEPARATOR ', ') as assignee_names,
            COUNT(DISTINCT tc.id) as comment_count,
            CASE 
                WHEN t.deadline < NOW() AND t.status != 'done' THEN 1
                ELSE 0
            END as is_overdue
        FROM tasks t
        LEFT JOIN users u ON t.creator_id = u.id
        LEFT JOIN departments d ON u.department_id = d.id
        LEFT JOIN task_assignees ta ON t.id = ta.task_id
        LEFT JOIN users au ON ta.user_id = au.id
        LEFT JOIN task_comments tc ON t.id = tc.task_id
        GROUP BY t.id
        ORDER BY t.created_at DESC";
    
    $stmt = $this->db->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getCount() {
    $sql = "SELECT COUNT(*) as count FROM tasks";
    $stmt = $this->db->query($sql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'];
}

public function getCompletedCount() {
    $sql = "SELECT COUNT(*) as count FROM tasks WHERE status = 'done'";
    $stmt = $this->db->query($sql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'];
}

public function getOverdueCount() {
    $sql = "SELECT COUNT(*) as count FROM tasks 
            WHERE deadline < NOW() AND status != 'done'";
    $stmt = $this->db->query($sql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'];
}

public function getStatsByStatus() {
    $sql = "SELECT status, COUNT(*) as count 
            FROM tasks 
            GROUP BY status";
    $stmt = $this->db->query($sql);
    
    $stats = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stats[$row['status']] = $row['count'];
    }
    return $stats;
}

public function getStatsByPriority() {
    $sql = "SELECT priority, COUNT(*) as count 
            FROM tasks 
            GROUP BY priority";
    $stmt = $this->db->query($sql);
    
    $stats = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stats[$row['priority']] = $row['count'];
    }
    return $stats;
}


}