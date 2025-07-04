<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Канбан доска - Система управления задачами</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --ios-bg: #f2f2f7;
            --ios-card: #ffffff;
            --ios-primary: #007aff;
            --ios-secondary: #5856d6;
            --ios-success: #34c759;
            --ios-warning: #ff9500;
            --ios-danger: #ff3b30;
            --ios-gray: #8e8e93;
            --ios-gray-2: #aeaeb2;
            --ios-gray-3: #c7c7cc;
            --ios-gray-4: #d1d1d6;
            --ios-gray-5: #e5e5ea;
            --ios-gray-6: #f2f2f7;
            --ios-shadow: 0 2px 15px rgba(0,0,0,0.1);
            --ios-radius: 20px;
            --ios-radius-sm: 12px;
        }

        .status-icon {
    font-size: 0.875rem;
}
        body {
            background: var(--ios-bg);
            font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', sans-serif;
            color: #1c1c1e;
            overflow-x: hidden;
        }

        /* Заголовок */
        .kanban-header {
            background: rgba(255,255,255,0.8);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            border-bottom: 0.5px solid var(--ios-gray-5);
            position: sticky;
            top: 56px;
            z-index: 100;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin: 0;
        }

        /* Поиск */
        .search-container {
            position: relative;
            max-width: 340px;
        }

        .search-input {
            background: var(--ios-gray-6);
            border: none;
            border-radius: 10px;
            padding: 10px 40px 10px 16px;
            font-size: 16px;
            width: 100%;
            transition: all 0.2s;
        }

        .search-input:focus {
            background: var(--ios-card);
            box-shadow: 0 0 0 4px rgba(0,122,255,0.1);
            outline: none;
        }

        .search-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--ios-gray-2);
            pointer-events: none;
        }

        /* Кнопки */
        .btn-ios {
            background: var(--ios-primary);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 10px 20px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-ios:hover {
            background: #0051d5;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(0,122,255,0.3);
        }

        /* Статистика */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255,255,255,0.8);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            transition: all 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--ios-primary);
            line-height: 1;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 14px;
            color: var(--ios-gray);
            font-weight: 500;
        }

        /* Канбан доска */
        .kanban-board {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            padding-bottom: 2rem;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
        }

        .kanban-column {
            flex: 0 0 320px;
            background: rgba(255,255,255,0.6);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: var(--ios-radius);
            padding: 20px;
            min-height: 600px;
            transition: all 0.3s;
        }

        .kanban-column.drag-over {
            background: rgba(0,122,255,0.05);
            box-shadow: 0 0 0 2px var(--ios-primary);
        }

        .column-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--ios-gray-5);
        }

        .column-title {
            font-size: 16px;
            font-weight: 600;
            color: #1c1c1e;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .column-count {
            background: var(--ios-gray-6);
            color: var(--ios-gray);
            font-size: 12px;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 10px;
        }

        .task-card.needs-attention {
    box-shadow: 0 0 0 2px #ffc107;
    animation: pulse 2s infinite;
}

        /* Карточки задач */
        .task-card {
            background: var(--ios-card);
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 12px;
            cursor: move;
            transition: all 0.2s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            position: relative;
            overflow: hidden;
        }

        .task-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--ios-gray-4);
            transition: all 0.2s;
        }

        .task-card.priority-urgent::before { background: var(--ios-danger); }
        .task-card.priority-high::before { background: var(--ios-warning); }
        .task-card.priority-medium::before { background: var(--ios-primary); }
        .task-card.priority-low::before { background: var(--ios-success); }

        .task-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .task-card.dragging {
            opacity: 0.5;
            transform: rotate(3deg);
            cursor: grabbing;
        }

        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 12px;
        }

        .task-title {
            font-size: 15px;
            font-weight: 600;
            color: #1c1c1e;
            line-height: 1.3;
            margin: 0;
            flex: 1;
        }

        .task-actions {
            display: flex;
            gap: 4px;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .task-card:hover .task-actions {
            opacity: 1;
        }

        .task-action-btn {
            background: var(--ios-gray-6);
            border: none;
            border-radius: 8px;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--ios-gray);
            cursor: pointer;
            transition: all 0.2s;
        }

        .task-action-btn:hover {
            background: var(--ios-gray-5);
            color: var(--ios-primary);
        }

        .task-description {
            font-size: 13px;
            color: var(--ios-gray);
            margin-bottom: 12px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .task-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .task-assignees {
            display: flex;
            margin-right: auto;
        }

        .assignee-avatar {
            width: 24px;
            height: 24px;
            border-radius: 12px;
            background: var(--ios-primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 600;
            margin-right: -6px;
            border: 2px solid white;
            position: relative;
            z-index: 1;
        }

        .assignee-avatar:hover {
            z-index: 2;
            transform: scale(1.1);
        }

        .assignee-more {
            background: var(--ios-gray-5);
            color: var(--ios-gray);
        }

        .task-deadline {
            background: var(--ios-gray-6);
            color: var(--ios-gray);
            font-size: 11px;
            font-weight: 500;
            padding: 4px 8px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .task-deadline.overdue {
            background: rgba(255,59,48,0.1);
            color: var(--ios-danger);
        }

        .task-deadline.soon {
            background: rgba(255,149,0,0.1);
            color: var(--ios-warning);
        }

        /* Быстрые статусы для задач */
        .task-status-actions {
            display: flex;
            gap: 4px;
            margin-top: 8px;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .task-card:hover .task-status-actions {
            opacity: 1;
        }

        .task-status-btn {
            background: var(--ios-gray-6);
            border: none;
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 10px;
            font-weight: 500;
            color: var(--ios-gray);
            cursor: pointer;
            transition: all 0.2s;
        }

        .task-status-btn:hover {
            background: var(--ios-primary);
            color: white;
        }

        .task-status-btn.btn-start {
            background: rgba(0,122,255,0.1);
            color: var(--ios-primary);
        }

        .task-status-btn.btn-complete {
            background: rgba(88,86,214,0.1);
            color: var(--ios-secondary);
        }

        .task-status-btn.btn-approve {
            background: rgba(52,199,89,0.1);
            color: var(--ios-success);
        }

        .task-status-btn.btn-reject {
            background: rgba(255,59,48,0.1);
            color: var(--ios-danger);
        }

        /* Адаптивность */
        @media (max-width: 768px) {
            .kanban-board {
                padding: 0 16px 16px;
            }
            
            .kanban-column {
                flex: 0 0 280px;
            }
            
            .kanban-header {
                padding: 1rem 16px;
            }
            
            .search-container {
                max-width: 100%;
            }

            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Уведомление */
        .notification-toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: rgba(0,0,0,0.9);
            color: white;
            padding: 16px 24px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s;
            z-index: 1050;
        }

        .notification-toast.show {
            transform: translateY(0);
            opacity: 1;
        }

        /* Загрузка */
        .loading-spinner {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 2px solid var(--ios-gray-5);
            border-radius: 50%;
            border-top-color: var(--ios-primary);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layouts/main.php'; ?>

    <!-- Заголовок -->
    <div class="kanban-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="page-title">Канбан доска</h1>
                </div>
                <div class="col-md-6">
                    <div class="d-flex gap-3 justify-content-md-end">
                        <div class="search-container flex-grow-1">
                            <input type="text" class="search-input" id="searchTasks" placeholder="Поиск задач...">
                            <i class="bi bi-search search-icon"></i>
                        </div>
                        <a href="/tasks/create" class="btn-ios">
                            <i class="bi bi-plus-circle"></i>
                            <span class="d-none d-sm-inline">Новая задача</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <!-- Статистика -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-value" id="totalTasks">0</div>
                <div class="stat-label">Всего задач</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="completedTasks">0</div>
                <div class="stat-label">Выполнено</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="inProgressTasks">0</div>
                <div class="stat-label">В работе</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="awaitingApproval">0</div>
                <div class="stat-label">Ожидает проверки</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="overdueTasks">0</div>
                <div class="stat-label">Просрочено</div>
            </div>
        </div>

        <!-- Канбан доска -->
        <div class="kanban-board" id="kanbanBoard">
            <?php
            $columns = [
                'backlog' => ['title' => 'Очередь задач', 'icon' => 'bi-inbox'],
                'todo' => ['title' => 'К выполнению', 'icon' => 'bi-list-check'],
                'in_progress' => ['title' => 'В работе', 'icon' => 'bi-gear'],
                'review' => ['title' => 'На проверке', 'icon' => 'bi-eye'],
                'waiting_approval' => ['title' => 'Ожидает проверки', 'icon' => 'bi-clock-history'],
                'done' => ['title' => 'Выполнено', 'icon' => 'bi-check-circle']
            ];
            
            foreach ($columns as $status => $column): ?>
                <div class="kanban-column" data-status="<?= $status ?>">
                    <div class="column-header">
                        <h3 class="column-title">
                            <i class="bi <?= $column['icon'] ?>"></i>
                            <?= $column['title'] ?>
                        </h3>
                        <span class="column-count" data-status="<?= $status ?>">0</span>
                    </div>
                    <div class="tasks-container" data-status="<?= $status ?>">
                        <?php if (isset($tasks[$status])): ?>
                            <?php foreach ($tasks[$status] as $task): ?>
                                <div class="task-card priority-<?= $task['priority'] ?>" 
                                     data-task-id="<?= $task['id'] ?>"
                                     data-status="<?= $status ?>"
                                     data-priority="<?= $task['priority'] ?>"
                                     data-creator-id="<?= $task['creator_id'] ?>"
                                     data-assignees="<?= htmlspecialchars($task['assignee_names'] ?? '') ?>"
                                     draggable="true">
                                    <div class="task-header">
                                        <h4 class="task-title"><?= htmlspecialchars($task['title']) ?></h4>
                                        <div class="task-actions">
                                            <button class="task-action-btn" onclick="viewTask(<?= $task['id'] ?>)" title="Просмотр">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="task-action-btn" onclick="editTask(<?= $task['id'] ?>)" title="Редактировать">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($task['description'])): ?>
                                        <p class="task-description"><?= htmlspecialchars($task['description']) ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="task-meta">
                                        <?php if (!empty($task['assignee_names'])): ?>
                                            <div class="task-assignees">
                                                <?php 
                                                $assignees = explode(',', $task['assignee_names']);
                                                $visibleAssignees = array_slice($assignees, 0, 3);
                                                foreach ($visibleAssignees as $assignee): 
                                                    $initials = implode('', array_map(function($word) { 
                                                        return mb_substr(trim($word), 0, 1); 
                                                    }, explode(' ', trim($assignee))));
                                                ?>
                                                    <div class="assignee-avatar" title="<?= htmlspecialchars(trim($assignee)) ?>">
                                                        <?= mb_strtoupper($initials) ?>
                                                    </div>
                                                <?php endforeach; ?>
                                                <?php if (count($assignees) > 3): ?>
                                                    <div class="assignee-avatar assignee-more">
                                                        +<?= count($assignees) - 3 ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($task['deadline'])): ?>
                                            <?php
                                            $deadline = strtotime($task['deadline']);
                                            $now = time();
                                            $daysLeft = ceil(($deadline - $now) / 86400);
                                            $deadlineClass = '';
                                            if ($daysLeft < 0 && $task['status'] != 'done') {
                                                $deadlineClass = 'overdue';
                                            } elseif ($daysLeft <= 3 && $task['status'] != 'done') {
                                                $deadlineClass = 'soon';
                                            }
                                            ?>
                                            <div class="task-deadline <?= $deadlineClass ?>">
                                                <i class="bi bi-calendar3"></i>
                                                <?= date('d.m', $deadline) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Быстрые действия со статусом -->
                                    <div class="task-status-actions">
                                        <?php
                                        $currentUserId = $_SESSION['user_id'];
                                        $isCreator = ($task['creator_id'] == $currentUserId);
                                        $isAssignee = !empty($task['assignee_names']) && strpos($task['assignee_names'], $currentUserId) !== false;
                                        
                                        // Показываем кнопки в зависимости от статуса и роли пользователя
                                        if ($isAssignee || $isCreator):
                                            switch ($status):
                                                case 'backlog':
                                                case 'todo':
                                                    if ($isAssignee): ?>
                                                        <button class="task-status-btn btn-start" onclick="quickChangeStatus(<?= $task['id'] ?>, 'in_progress')">
                                                            <i class="bi bi-play-fill"></i> Начать
                                                        </button>
                                                    <?php endif;
                                                    break;
                                                
                                                case 'in_progress':
                                                    if ($isAssignee): ?>
                                                        <button class="task-status-btn btn-complete" onclick="quickChangeStatus(<?= $task['id'] ?>, 'waiting_approval')">
                                                            <i class="bi bi-check"></i> Готово
                                                        </button>
                                                    <?php endif;
                                                    break;
                                                
                                                case 'review':
                                                    if ($isAssignee): ?>
                                                        <button class="task-status-btn btn-complete" onclick="quickChangeStatus(<?= $task['id'] ?>, 'waiting_approval')">
                                                            <i class="bi bi-check"></i> Готово
                                                        </button>
                                                    <?php endif;
                                                    break;
                                                
                                                case 'waiting_approval':
                                                    if ($isCreator): ?>
                                                        <button class="task-status-btn btn-approve" onclick="quickChangeStatus(<?= $task['id'] ?>, 'done')">
                                                            <i class="bi bi-check-all"></i> Принять
                                                        </button>
                                                        <button class="task-status-btn btn-reject" onclick="quickChangeStatus(<?= $task['id'] ?>, 'in_progress')">
                                                            <i class="bi bi-arrow-left"></i> Доработка
                                                        </button>
                                                    <?php endif;
                                                    break;
                                                
                                                case 'done':
                                                    if ($isCreator || $isAssignee): ?>
                                                        <button class="task-status-btn" onclick="quickChangeStatus(<?= $task['id'] ?>, 'in_progress')">
                                                            <i class="bi bi-arrow-counterclockwise"></i> Переоткрыть
                                                        </button>
                                                    <?php endif;
                                                    break;
                                            endswitch;
                                        endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Уведомление -->
    <div class="notification-toast" id="notificationToast">
        <i class="bi bi-check-circle"></i>
        <span id="notificationText">Статус задачи обновлен</span>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Глобальные переменные
        let draggedElement = null;
        let placeholder = null;
        const columns = document.querySelectorAll('.kanban-column');

        // Инициализация
        document.addEventListener('DOMContentLoaded', function() {
            initializeDragAndDrop();
            initializeSearch();
            updateStatistics();
            updateColumnCounts();
        });

        

        // Drag and Drop
        function initializeDragAndDrop() {
            document.querySelectorAll('.task-card').forEach(card => {
                card.addEventListener('dragstart', handleDragStart);
                card.addEventListener('dragend', handleDragEnd);
            });

            columns.forEach(column => {
                const container = column.querySelector('.tasks-container');
                container.addEventListener('dragover', handleDragOver);
                container.addEventListener('drop', handleDrop);
                container.addEventListener('dragenter', handleDragEnter);
                container.addEventListener('dragleave', handleDragLeave);
            });
        }

        function handleDragStart(e) {
            draggedElement = this;
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            
            // Создаем placeholder
            placeholder = document.createElement('div');
            placeholder.className = 'task-card-placeholder';
            placeholder.style.height = this.offsetHeight + 'px';
            placeholder.style.background = 'rgba(0,122,255,0.1)';
            placeholder.style.border = '2px dashed var(--ios-primary)';
            placeholder.style.borderRadius = '16px';
            placeholder.style.marginBottom = '12px';
        }

        function handleDragEnd(e) {
            this.classList.remove('dragging');
            columns.forEach(col => col.classList.remove('drag-over'));
            if (placeholder && placeholder.parentNode) {
                placeholder.parentNode.removeChild(placeholder);
            }
            placeholder = null;
        }

        function handleDragOver(e) {
            if (e.preventDefault) {
                e.preventDefault();
            }
            e.dataTransfer.dropEffect = 'move';

            const afterElement = getDragAfterElement(this, e.clientY);
            if (afterElement == null) {
                this.appendChild(placeholder);
            } else {
                this.insertBefore(placeholder, afterElement);
            }
            
            return false;
        }

        function handleDrop(e) {
            if (e.stopPropagation) {
                e.stopPropagation();
            }

            const column = this.closest('.kanban-column');
            const newStatus = column.dataset.status;
            const oldStatus = draggedElement.dataset.status;
            const taskId = draggedElement.dataset.taskId;

            if (placeholder && placeholder.parentNode) {
                placeholder.parentNode.replaceChild(draggedElement, placeholder);
            }

            draggedElement.dataset.status = newStatus;

            if (oldStatus !== newStatus) {
                updateTaskStatus(taskId, oldStatus, newStatus);
                showNotification('Задача перемещена');
            }

            updateColumnCounts();
            updateStatistics();

            return false;
        }

        function handleDragEnter(e) {
            this.closest('.kanban-column').classList.add('drag-over');
        }

        function handleDragLeave(e) {
            if (e.target === this) {
                this.closest('.kanban-column').classList.remove('drag-over');
            }
        }

        function getDragAfterElement(container, y) {
            const draggableElements = [...container.querySelectorAll('.task-card:not(.dragging)')];

            return draggableElements.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;

                if (offset < 0 && offset > closest.offset) {
                    return { offset: offset, element: child };
                } else {
                    return closest;
                }
            }, { offset: Number.NEGATIVE_INFINITY }).element;
        }

        // Быстрая смена статуса
        function quickChangeStatus(taskId, newStatus) {
    window.currentTaskId = taskId; // Устанавливаем глобальную переменную
    
    const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
    const oldStatus = taskCard ? taskCard.dataset.status : 'backlog';
    
    // Показываем индикатор загрузки
    const statusActions = taskCard ? taskCard.querySelector('.task-status-actions') : null;
    if (statusActions) {
        statusActions.innerHTML = '<div class="loading-spinner"></div>';
    }
    
    performStatusChange(newStatus);
}


// Вспомогательная функция для экранирования HTML
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

        // Обновление статуса задачи
        function updateTaskStatus(taskId, oldStatus, newStatus) {
            const formData = new FormData();
            formData.append('task_id', taskId);
            formData.append('old_status', oldStatus);
            formData.append('new_status', newStatus);

            fetch('/tasks/update-status', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Перемещаем карточку в новую колонку если статус изменился
                    if (oldStatus !== newStatus) {
                        moveTaskToColumn(taskId, newStatus);
                    }
                    
                    showNotification('Статус успешно обновлен');
                    updateColumnCounts();
                    updateStatistics();
                } else {
                    showNotification('Ошибка при обновлении статуса', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Произошла ошибка', 'error');
            });
        }

        // Перемещение задачи в новую колонку
        function moveTaskToColumn(taskId, newStatus) {
            const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
            const newColumn = document.querySelector(`[data-status="${newStatus}"] .tasks-container`);
            
            if (taskCard && newColumn) {
                taskCard.dataset.status = newStatus;
                newColumn.appendChild(taskCard);
                
                // Обновляем кнопки статуса
                setTimeout(() => {
                    location.reload(); // Простое решение для обновления кнопок
                }, 1000);
            }
        }

        // Поиск
        function initializeSearch() {
            const searchInput = document.getElementById('searchTasks');
            searchInput.addEventListener('input', function() {
                const query = this.value.toLowerCase();
                const cards = document.querySelectorAll('.task-card');

                cards.forEach(card => {
                    const title = card.querySelector('.task-title').textContent.toLowerCase();
                    const description = card.querySelector('.task-description')?.textContent.toLowerCase() || '';
                    const show = title.includes(query) || description.includes(query);
                    card.style.display = show ? 'block' : 'none';
                });

                updateColumnCounts();
            });
        }

        // Статистика
        function updateStatistics() {
            const allCards = document.querySelectorAll('.task-card');
            const visibleCards = Array.from(allCards).filter(card => card.style.display !== 'none');
            
            document.getElementById('totalTasks').textContent = visibleCards.length;
            document.getElementById('completedTasks').textContent = 
                visibleCards.filter(card => card.dataset.status === 'done').length;
            document.getElementById('inProgressTasks').textContent = 
                visibleCards.filter(card => card.dataset.status === 'in_progress').length;
            document.getElementById('awaitingApproval').textContent = 
                visibleCards.filter(card => card.dataset.status === 'waiting_approval').length;
            document.getElementById('overdueTasks').textContent = 
                visibleCards.filter(card => card.querySelector('.task-deadline.overdue')).length;
        }

        // Обновление счетчиков колонок
        
function updateColumnCounts() {
    const columns = document.querySelectorAll('.kanban-column');
    columns.forEach(column => {
        const status = column.dataset.status;
        const count = column.querySelectorAll('.task-card:not([style*="display: none"])').length;
        const countElement = column.querySelector('.column-count');
        if (countElement) {
            countElement.textContent = count;
        }
    });
}

        // Уведомления
        function showNotification(message, type = 'success') {
            const toast = document.getElementById('notificationToast');
            const icon = toast.querySelector('i');
            const text = document.getElementById('notificationText');
            
            // Устанавливаем иконку и цвет в зависимости от типа
            if (type === 'error') {
                icon.className = 'bi bi-exclamation-circle';
                toast.style.background = 'rgba(255,59,48,0.9)';
            } else {
                icon.className = 'bi bi-check-circle';
                toast.style.background = 'rgba(0,0,0,0.9)';
            }
            
            text.textContent = message;
            toast.classList.add('show');
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        // Вспомогательные функции
        function viewTask(taskId) {
            window.location.href = `/tasks/view/${taskId}`;
        }

        function editTask(taskId) {
            window.location.href = `/tasks/edit/${taskId}`;
        }

        // Инициализация счетчиков при загрузке
        updateColumnCounts();

        // Функция для подсветки задач, требующих внимания
function highlightTasksNeedingAttention() {
    const userId = <?= $_SESSION['user_id'] ?? 'null' ?>;
    const taskCards = document.querySelectorAll('.task-card');
    
    taskCards.forEach(card => {
        const status = card.dataset.status;
        const creatorId = card.dataset.creatorId;
        const assignees = card.dataset.assignees;
        
        let needsAttention = false;
        
        // Если пользователь - создатель и задача ожидает проверки
        if (creatorId == userId && status === 'waiting_approval') {
            needsAttention = true;
        }
        
        // Если пользователь - исполнитель и задача в работе
        if (assignees.includes(userId) && status === 'in_progress') {
            needsAttention = true;
        }
        
        if (needsAttention) {
            card.classList.add('needs-attention');
        }
    });
    
    // Обновляем счетчики в заголовках
    updateColumnAttentionIndicators();
}

// Обновление индикаторов внимания в заголовках колонок
function updateColumnAttentionIndicators() {
    const columns = document.querySelectorAll('.kanban-column');
    
    columns.forEach(column => {
        const status = column.dataset.status;
        const needsAttentionCount = column.querySelectorAll('.task-card.needs-attention').length;
        const countElement = column.querySelector('.column-count');
        
        if (needsAttentionCount > 0) {
            countElement.classList.add('has-attention');
            countElement.title = `${needsAttentionCount} задач требуют вашего внимания`;
        } else {
            countElement.classList.remove('has-attention');
            countElement.title = '';
        }
    });
}

// Функция для отправки уведомления об изменении статуса
function notifyStatusChange(taskId, oldStatus, newStatus, comment = '') {
    // Отправляем дополнительные данные о смене статуса
    const eventData = {
        taskId: taskId,
        oldStatus: oldStatus,
        newStatus: newStatus,
        comment: comment,
        timestamp: new Date().toISOString(),
        userId: <?= $_SESSION['user_id'] ?? 'null' ?>
    };
    
    // Можно добавить отправку в аналитику или другие системы
    console.log('Status change event:', eventData);
}

// Обновленная функция для быстрой смены статуса с подтверждением
function quickChangeStatusWithConfirmation(taskId, newStatus) {
    const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
    const taskTitle = taskCard.querySelector('.task-title').textContent;
    const oldStatus = taskCard.dataset.status;
    
const statusLabels = {
    'backlog': 'Очередь задач',
    'todo': 'К выполнению',
    'in_progress': 'В работе',
    'waiting_approval': 'Ожидает проверки',
    'done': 'Выполнено'
};
    
    const confirmMessage = `Изменить статус задачи "${taskTitle}" с "${statusLabels[oldStatus]}" на "${statusLabels[newStatus]}"?`;
    
    if (confirm(confirmMessage)) {
        quickChangeStatus(taskId, newStatus);
    }
}

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    // Подсвечиваем задачи, требующие внимания
    setTimeout(highlightTasksNeedingAttention, 500);
    
    // Обновляем подсветку каждые 30 секунд
    setInterval(highlightTasksNeedingAttention, 30000);
});

    </script>
</body>
</html>