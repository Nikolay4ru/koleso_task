<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Канбан доска - Система управления задачами</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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

        * {
            box-sizing: border-box;
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

        .btn-ios:active {
            transform: translateY(0);
        }

        /* Быстрые кнопки дедлайна */
        .quick-deadline-btn {
            border-radius: 20px;
            font-size: 13px;
            padding: 6px 16px;
            border: 1px solid var(--ios-primary);
            background: transparent;
            color: var(--ios-primary);
            transition: all 0.2s;
        }

        .quick-deadline-btn:hover {
            background: var(--ios-primary);
            color: white;
            border-color: var(--ios-primary);
        }

        .quick-deadline-btn:active {
            transform: scale(0.95);
        }

        /* Select2 iOS стиль */
        .select2-container--bootstrap-5 .select2-selection {
            background: var(--ios-gray-6) !important;
            border: none !important;
            border-radius: 12px !important;
            min-height: 44px !important;
        }

        .select2-container--bootstrap-5.select2-container--focus .select2-selection {
            background: white !important;
            box-shadow: 0 0 0 4px rgba(0,122,255,0.1) !important;
        }

        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice {
            background: var(--ios-primary);
            border: none;
            color: white;
            border-radius: 8px;
            padding: 4px 10px;
            font-size: 14px;
        }

        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice__remove {
            color: white;
            margin-right: 4px;
        }

        /* Фильтры */
        .filters-container {
            background: rgba(255,255,255,0.8);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: var(--ios-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--ios-shadow);
        }

        .filter-chips {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .filter-chip {
            background: var(--ios-gray-6);
            border: none;
            border-radius: 20px;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 500;
            color: var(--ios-gray);
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .filter-chip:hover {
            background: var(--ios-gray-5);
        }

        .filter-chip.active {
            background: var(--ios-primary);
            color: white;
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
            flex: 0 0 340px;
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
            font-size: 18px;
            font-weight: 600;
            color: #1c1c1e;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .column-count {
            background: var(--ios-gray-6);
            color: var(--ios-gray);
            font-size: 14px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 12px;
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
            font-size: 16px;
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
            font-size: 14px;
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
            width: 28px;
            height: 28px;
            border-radius: 14px;
            background: var(--ios-primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            margin-right: -8px;
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
            font-size: 12px;
            font-weight: 500;
            padding: 4px 10px;
            border-radius: 8px;
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

        /* Быстрое добавление задачи */
        .quick-add-task {
            margin-top: 12px;
            padding: 12px;
            background: var(--ios-gray-6);
            border-radius: 12px;
            cursor: pointer;
            text-align: center;
            color: var(--ios-primary);
            font-weight: 500;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .quick-add-task:hover {
            background: var(--ios-gray-5);
        }

        /* Модальные окна */
        .modal-content {
            border: none;
            border-radius: var(--ios-radius);
            overflow: hidden;
        }

        .modal-header {
            background: var(--ios-gray-6);
            border-bottom: 1px solid var(--ios-gray-5);
            padding: 20px;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 700;
            color: #1c1c1e;
        }

        .modal-body {
            padding: 24px;
        }

        .form-label {
            font-size: 14px;
            font-weight: 600;
            color: var(--ios-gray);
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            background: var(--ios-gray-6);
            border: none;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 16px;
            transition: all 0.2s;
        }

        .form-control:focus, .form-select:focus {
            background: white;
            box-shadow: 0 0 0 4px rgba(0,122,255,0.1);
            outline: none;
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

        /* Прогресс бар */
        .progress-bar-container {
            background: var(--ios-gray-6);
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 20px;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--ios-primary), var(--ios-secondary));
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        /* Анимации */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .task-card {
            animation: slideIn 0.3s ease;
        }

        /* Адаптивность */
        @media (max-width: 768px) {
            .kanban-board {
                padding: 0 16px 16px;
            }
            
            .kanban-column {
                flex: 0 0 300px;
            }
            
            .filters-container {
                margin: 0 16px 16px;
            }
            
            .kanban-header {
                padding: 1rem 16px;
            }
            
            .search-container {
                max-width: 100%;
            }
        }

        /* Приоритет селектор */
        .priority-selector {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
        }

        .priority-option {
            flex: 1;
            position: relative;
        }

        .priority-option input {
            position: absolute;
            opacity: 0;
        }

        .priority-label {
            display: block;
            padding: 8px;
            border-radius: 8px;
            text-align: center;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            background: var(--ios-gray-6);
            color: var(--ios-gray);
        }

        .priority-option input:checked + .priority-label {
            color: white;
        }

        .priority-low input:checked + .priority-label { background: var(--ios-success); }
        .priority-medium input:checked + .priority-label { background: var(--ios-primary); }
        .priority-high input:checked + .priority-label { background: var(--ios-warning); }
        .priority-urgent input:checked + .priority-label { background: var(--ios-danger); }

        /* Loading skeleton */
        .skeleton {
            background: linear-gradient(90deg, var(--ios-gray-6) 25%, var(--ios-gray-5) 50%, var(--ios-gray-6) 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
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
                        <button class="btn-ios" data-bs-toggle="modal" data-bs-target="#createTaskModal">
                            <i class="bi bi-plus-circle"></i>
                            <span class="d-none d-sm-inline">Новая задача</span>
                        </button>
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
                <div class="stat-value" id="overdueTasks">0</div>
                <div class="stat-label">Просрочено</div>
            </div>
        </div>

        <!-- Фильтры -->
        <div class="filters-container">
            <h5 class="mb-3">Быстрые фильтры</h5>
            <div class="filter-chips">
                <button class="filter-chip active" data-filter="all">
                    <i class="bi bi-grid-3x3-gap"></i> Все задачи
                </button>
                <button class="filter-chip" data-filter="my-tasks">
                    <i class="bi bi-person"></i> Мои задачи
                </button>
                <button class="filter-chip" data-filter="urgent">
                    <i class="bi bi-exclamation-circle"></i> Срочные
                </button>
                <button class="filter-chip" data-filter="today">
                    <i class="bi bi-calendar-event"></i> Сегодня
                </button>
                <button class="filter-chip" data-filter="overdue">
                    <i class="bi bi-clock-history"></i> Просроченные
                </button>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar-fill" id="progressBar" style="width: 0%"></div>
            </div>
        </div>

        <!-- Канбан доска -->
        <div class="kanban-board" id="kanbanBoard">
            <?php
            $columns = [
                'backlog' => ['title' => 'К Выполнению', 'icon' => 'bi-inbox'],
               // 'todo' => ['title' => 'К выполнению', 'icon' => 'bi-list-check'],
                'in_progress' => ['title' => 'В работе', 'icon' => 'bi-gear'],
                'review' => ['title' => 'На проверке', 'icon' => 'bi-eye'],
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
                                     data-assignees="<?= htmlspecialchars($task['assignee_names'] ?? '') ?>"
                                     data-assignees-ids="<?= htmlspecialchars($task['assignee_ids'] ?? '') ?>"
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
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($status == "backlog"): ?>
                    <div class="quick-add-task" data-status="<?= $status ?>">
                        <i class="bi bi-plus"></i>
                        Добавить задачу
                    </div>
                     <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Модальное окно создания задачи -->
    <div class="modal fade" id="createTaskModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Новая задача</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="createTaskForm" method="POST" action="/tasks/create">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Название задачи</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Описание</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Статус</label>
                                <select class="form-select" name="status">
                                    <option value="backlog" selected>Очередь задач</option>
                                    
                                    <option value="in_progress">В работе</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Дедлайн</label>
                                <input type="text" class="form-control" name="deadline" id="deadlinePicker">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Приоритет</label>
                            <div class="priority-selector">
                                <div class="priority-option priority-low">
                                    <input type="radio" name="priority" value="low" id="priority-low">
                                    <label class="priority-label" for="priority-low">Низкий</label>
                                </div>
                                <div class="priority-option priority-medium">
                                    <input type="radio" name="priority" value="medium" id="priority-medium" checked>
                                    <label class="priority-label" for="priority-medium">Средний</label>
                                </div>
                                <div class="priority-option priority-high">
                                    <input type="radio" name="priority" value="high" id="priority-high">
                                    <label class="priority-label" for="priority-high">Высокий</label>
                                </div>
                                <div class="priority-option priority-urgent">
                                    <input type="radio" name="priority" value="urgent" id="priority-urgent">
                                    <label class="priority-label" for="priority-urgent">Срочный</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Исполнители</label>
                                <select class="form-select" name="assignees[]" id="assigneesSelect" multiple>
                                    <!-- Заполняется через PHP -->
                                    <?php if(isset($users)): ?>
                                        <?php foreach($users as $user): ?>
                                            <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Наблюдатели</label>
                                <select class="form-select" name="watchers[]" id="watchersSelect" multiple>
                                    <?php if(isset($users)): ?>
                                        <?php foreach($users as $user): ?>
                                            <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn-ios">
                            <i class="bi bi-check-circle"></i>
                            Создать задачу
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальное окно быстрого создания -->
    <div class="modal fade" id="quickAddModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Быстрое добавление задачи</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="quickAddForm">
                    <div class="modal-body">
                        <input type="hidden" name="status" id="quickAddStatus">
                        
                        <!-- Название задачи -->
                        <div class="mb-3">
                            <input type="text" class="form-control" name="title" placeholder="Название задачи" required autofocus>
                        </div>
                        
                        <!-- Приоритет -->
                        <div class="mb-3">
                            <label class="form-label">Приоритет</label>
                            <div class="priority-selector">
                                <div class="priority-option priority-low">
                                    <input type="radio" name="quick_priority" value="low" id="quick-priority-low">
                                    <label class="priority-label" for="quick-priority-low">Низкий</label>
                                </div>
                                <div class="priority-option priority-medium">
                                    <input type="radio" name="quick_priority" value="medium" id="quick-priority-medium" checked>
                                    <label class="priority-label" for="quick-priority-medium">Средний</label>
                                </div>
                                <div class="priority-option priority-high">
                                    <input type="radio" name="quick_priority" value="high" id="quick-priority-high">
                                    <label class="priority-label" for="quick-priority-high">Высокий</label>
                                </div>
                                <div class="priority-option priority-urgent">
                                    <input type="radio" name="quick_priority" value="urgent" id="quick-priority-urgent">
                                    <label class="priority-label" for="quick-priority-urgent">Срочный</label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Исполнители и наблюдатели -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-people-fill me-1"></i>
                                    Исполнители
                                </label>
                                <select class="form-select" name="assignees[]" id="quickAssigneesSelect" multiple>
                                    <?php if(isset($users)): ?>
                                        <?php foreach($users as $user): ?>
                                            <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-eye-fill me-1"></i>
                                    Наблюдатели
                                </label>
                                <select class="form-select" name="watchers[]" id="quickWatchersSelect" multiple>
                                    <?php if(isset($users)): ?>
                                        <?php foreach($users as $user): ?>
                                            <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Срок выполнения -->
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-calendar3 me-1"></i>
                                Срок выполнения
                            </label>
                            <input type="text" class="form-control" name="deadline" id="quickDeadlinePicker" placeholder="Выберите дату и время">
                        </div>
                        
                        <!-- Быстрые шаблоны сроков -->
                        <div class="d-flex gap-2 mb-3">
                            <button type="button" class="btn btn-sm btn-outline-primary quick-deadline-btn" data-deadline="today">Сегодня</button>
                            <button type="button" class="btn btn-sm btn-outline-primary quick-deadline-btn" data-deadline="tomorrow">Завтра</button>
                            <button type="button" class="btn btn-sm btn-outline-primary quick-deadline-btn" data-deadline="week">Через неделю</button>
                            <button type="button" class="btn btn-sm btn-outline-primary quick-deadline-btn" data-deadline="month">Через месяц</button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn-ios">
                            <i class="bi bi-plus"></i>
                            Добавить задачу
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Уведомление -->
    <div class="notification-toast" id="notificationToast">
        <i class="bi bi-check-circle"></i>
        <span id="notificationText">Задача успешно обновлена</span>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/ru.js"></script>
    <script>
        // Глобальные переменные
        let draggedElement = null;
        let placeholder = null;
        const columns = document.querySelectorAll('.kanban-column');
        const quickAddModal = new bootstrap.Modal(document.getElementById('quickAddModal'));

        // Инициализация
        document.addEventListener('DOMContentLoaded', function() {
            initializeDragAndDrop();
            initializeFilters();
            initializeSearch();
            initializeQuickAdd();
            initializeSelects();
            initializeDatePicker();
            updateStatistics();
            updateProgress();
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
                updateTaskStatus(taskId, newStatus);
                showNotification('Задача перемещена');
            }

            updateColumnCounts();
            updateStatistics();
            updateProgress();

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

        // Обновление статуса задачи
        function updateTaskStatus(taskId, newStatus) {
            fetch('/tasks/update-status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `task_id=${taskId}&new_status=${newStatus}`
            });
        }

        // Фильтры
        function initializeFilters() {
            document.querySelectorAll('.filter-chip').forEach(chip => {
                chip.addEventListener('click', function() {
                    document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
                    this.classList.add('active');
                    
                    const filter = this.dataset.filter;
                    applyFilter(filter);
                });
            });
        }

        function applyFilter(filter) {
            const cards = document.querySelectorAll('.task-card');
            const userId = <?= $_SESSION['user_id'] ?? 'null' ?>;
            
            cards.forEach(card => {
                let show = true;
                console.log(card);
                switch(filter) {
                    case 'my-tasks':
                        const assigneesIds = card.dataset.assigneesIds || '';
                        console.log(card.dataset);
                        show = assigneesIds.includes(userId);
                        break;
                    case 'urgent':
                        show = card.dataset.priority === 'urgent';
                        break;
                    case 'today':
                        const deadline = card.querySelector('.task-deadline');
                        if (deadline) {
                            const today = new Date().toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit' });
                            show = deadline.textContent.includes(today);
                        } else {
                            show = false;
                        }
                        break;
                    case 'overdue':
                        show = card.querySelector('.task-deadline.overdue') !== null;
                        break;
                }

                card.style.display = show ? 'block' : 'none';
            });

            updateColumnCounts();
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

        // Быстрое добавление
        function initializeQuickAdd() {
            document.querySelectorAll('.quick-add-task').forEach(btn => {
                btn.addEventListener('click', function() {
                    const status = this.dataset.status;
                    document.getElementById('quickAddStatus').value = status;
                    
                    // Сбрасываем форму
                    document.getElementById('quickAddForm').reset();
                    $('#quickAssigneesSelect, #quickWatchersSelect').val(null).trigger('change');
                    
                    // Устанавливаем фокус на поле названия при открытии
                    quickAddModal.show();
                    setTimeout(() => {
                        document.querySelector('#quickAddModal input[name="title"]').focus();
                    }, 500);
                });
            });

            document.getElementById('quickAddForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                
                // Переименовываем приоритет
                formData.append('priority', formData.get('quick_priority'));
                formData.delete('quick_priority');
                
                // Показываем индикатор загрузки
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Добавление...';
                
                fetch('/tasks/create', {
                    method: 'POST',
                    body: formData
                }).then(response => {
                    if (response.ok) {
                        quickAddModal.hide();
                        showNotification('Задача успешно создана');
                        
                        // Перезагружаем страницу через секунду
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showNotification('Ошибка при создании задачи', 'error');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }
                }).catch(error => {
                    showNotification('Ошибка при создании задачи', 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
            });
        }

        // Select2
        function initializeSelects() {
            // Для основной формы
            $('#assigneesSelect, #watchersSelect').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#createTaskModal'),
                placeholder: 'Выберите пользователей',
                allowClear: true
            });
            
            // Для быстрого добавления
            $('#quickAssigneesSelect, #quickWatchersSelect').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#quickAddModal'),
                placeholder: 'Выберите пользователей',
                allowClear: true,
                width: '100%'
            });
        }

        // Flatpickr
        function initializeDatePicker() {
            // Для основной формы
            flatpickr("#deadlinePicker", {
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                minDate: "today",
                locale: "ru"
            });
            
            // Для быстрого добавления
            const quickPicker = flatpickr("#quickDeadlinePicker", {
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                minDate: "today",
                locale: "ru",
                time_24hr: true
            });
            
            // Быстрые кнопки для установки дедлайна
            document.querySelectorAll('.quick-deadline-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const deadline = this.dataset.deadline;
                    const date = new Date();
                    
                    switch(deadline) {
                        case 'today':
                            date.setHours(23, 59, 0, 0);
                            break;
                        case 'tomorrow':
                            date.setDate(date.getDate() + 1);
                            date.setHours(18, 0, 0, 0);
                            break;
                        case 'week':
                            date.setDate(date.getDate() + 7);
                            date.setHours(18, 0, 0, 0);
                            break;
                        case 'month':
                            date.setMonth(date.getMonth() + 1);
                            date.setHours(18, 0, 0, 0);
                            break;
                    }
                    
                    quickPicker.setDate(date);
                });
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
            document.getElementById('overdueTasks').textContent = 
                visibleCards.filter(card => card.querySelector('.task-deadline.overdue')).length;
        }

        // Обновление счетчиков колонок
        function updateColumnCounts() {
            columns.forEach(column => {
                const status = column.dataset.status;
                const count = column.querySelectorAll('.task-card:not([style*="display: none"])').length;
                column.querySelector('.column-count').textContent = count;
            });
        }

        // Прогресс
        function updateProgress() {
            const total = document.querySelectorAll('.task-card').length;
            const completed = document.querySelectorAll('.task-card[data-status="done"]').length;
            const progress = total > 0 ? (completed / total * 100) : 0;
            document.getElementById('progressBar').style.width = progress + '%';
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
    </script>
</body>
</html>