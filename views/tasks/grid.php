<?php
/**
 * Grid view для отображения задач
 * Путь: /views/tasks/grid.php
 */

// Проверка наличия данных
$tasks = $tasks ?? [];
$currentPage = $currentPage ?? 1;
$totalPages = $totalPages ?? 1;
$filters = $filters ?? [];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Задачи - Сетка</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .task-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        
        .task-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            background: #fff;
            transition: all 0.3s ease;
            cursor: pointer;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .task-card:hover {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .task-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
            flex: 1;
            word-break: break-word;
        }
        
        .task-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            white-space: nowrap;
            margin-left: 10px;
        }
        
        .status-new { background: #e3f2fd; color: #1976d2; }
        .status-in-progress { background: #fff3e0; color: #f57c00; }
        .status-completed { background: #e8f5e9; color: #388e3c; }
        .status-cancelled { background: #ffebee; color: #d32f2f; }
        
        .task-description {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 15px;
            flex: 1;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }
        
        .task-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            color: #6c757d;
            margin-top: auto;
        }
        
        .task-priority {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .priority-high { color: #dc3545; }
        .priority-medium { color: #ffc107; }
        .priority-low { color: #28a745; }
        
        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .view-toggle {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 64px;
            color: #dee2e6;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Заголовок и переключатель видов -->
        <div class="d-flex justify-content-between align-items-center py-3">
            <h1>Задачи</h1>
            <div class="view-toggle">
                <a href="?view=list" class="btn btn-outline-secondary">
                    <i class="bi bi-list"></i> Список
                </a>
                <button class="btn btn-secondary" disabled>
                    <i class="bi bi-grid-3x3-gap-fill"></i> Сетка
                </button>
            </div>
        </div>
        
        <!-- Фильтры -->
        <div class="filter-section">
            <form method="GET" action="" class="row g-3">
                <input type="hidden" name="view" value="grid">
                
                <div class="col-md-3">
                    <label for="status" class="form-label">Статус</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Все статусы</option>
                        <option value="new" <?= ($filters['status'] ?? '') === 'new' ? 'selected' : '' ?>>Новая</option>
                        <option value="in_progress" <?= ($filters['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>В работе</option>
                        <option value="completed" <?= ($filters['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Завершена</option>
                        <option value="cancelled" <?= ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Отменена</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="priority" class="form-label">Приоритет</label>
                    <select class="form-select" id="priority" name="priority">
                        <option value="">Все приоритеты</option>
                        <option value="low" <?= ($filters['priority'] ?? '') === 'low' ? 'selected' : '' ?>>Низкий</option>
                        <option value="medium" <?= ($filters['priority'] ?? '') === 'medium' ? 'selected' : '' ?>>Средний</option>
                        <option value="high" <?= ($filters['priority'] ?? '') === 'high' ? 'selected' : '' ?>>Высокий</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label for="search" class="form-label">Поиск</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Поиск по названию или описанию" 
                           value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Найти
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Сетка задач -->
        <?php if (empty($tasks)): ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h3>Задачи не найдены</h3>
                <p>Попробуйте изменить параметры фильтра или создайте новую задачу</p>
                <a href="/tasks/create" class="btn btn-primary mt-3">
                    <i class="bi bi-plus-circle"></i> Создать задачу
                </a>
            </div>
        <?php else: ?>
            <div class="task-grid">
                <?php foreach ($tasks as $task): ?>
                    <div class="task-card" onclick="window.location.href='/tasks/<?= $task['id'] ?>'">
                        <div class="task-header">
                            <h3 class="task-title"><?= htmlspecialchars($task['title']) ?></h3>
                            <span class="task-status status-<?= $task['status'] ?>">
                                <?= getStatusLabel($task['status']) ?>
                            </span>
                        </div>
                        
                        <?php if (!empty($task['description'])): ?>
                            <p class="task-description">
                                <?= htmlspecialchars($task['description']) ?>
                            </p>
                        <?php endif; ?>
                        
                        <div class="task-meta">
                            <div class="task-priority priority-<?= $task['priority'] ?>">
                                <i class="bi bi-flag-fill"></i>
                                <?= getPriorityLabel($task['priority']) ?>
                            </div>
                            <div class="task-date">
                                <i class="bi bi-calendar"></i>
                                <?= date('d.m.Y', strtotime($task['created_at'])) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Пагинация -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Навигация по страницам" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?view=grid&page=<?= $currentPage - 1 ?><?= buildFilterQuery($filters) ?>">
                                Предыдущая
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                <a class="page-link" href="?view=grid&page=<?= $i ?><?= buildFilterQuery($filters) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?view=grid&page=<?= $currentPage + 1 ?><?= buildFilterQuery($filters) ?>">
                                Следующая
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Вспомогательные функции
function getStatusLabel($status) {
    $labels = [
        'new' => 'Новая',
        'in_progress' => 'В работе',
        'completed' => 'Завершена',
        'cancelled' => 'Отменена'
    ];
    return $labels[$status] ?? $status;
}

function getPriorityLabel($priority) {
    $labels = [
        'low' => 'Низкий',
        'medium' => 'Средний',
        'high' => 'Высокий'
    ];
    return $labels[$priority] ?? $priority;
}

function buildFilterQuery($filters) {
    $query = '';
    foreach ($filters as $key => $value) {
        if (!empty($value) && $key !== 'page') {
            $query .= '&' . urlencode($key) . '=' . urlencode($value);
        }
    }
    return $query;
}
?>