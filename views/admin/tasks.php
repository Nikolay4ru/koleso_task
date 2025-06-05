<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление задачами - Администрирование</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .admin-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .admin-nav {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        .admin-nav .nav-link {
            color: #495057;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .admin-nav .nav-link:hover {
            background: #f8f9fa;
        }
        .admin-nav .nav-link.active {
            background: #667eea;
            color: white;
        }
        .content-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 2rem;
        }
        .stat-widget {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 1rem;
        }
        .stat-widget h4 {
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        .stat-widget p {
            margin-bottom: 0;
            color: #6c757d;
        }
        .status-chart {
            height: 200px;
        }
        .filter-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        .status-backlog { background: #e9ecef; color: #495057; }
        .status-todo { background: #cfe2ff; color: #084298; }
        .status-in_progress { background: #f8d7da; color: #842029; }
        .status-review { background: #fff3cd; color: #664d03; }
        .status-done { background: #d1e7dd; color: #0f5132; }
        
        .priority-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .priority-low { background: #d4edda; color: #155724; }
        .priority-medium { background: #d1ecf1; color: #0c5460; }
        .priority-high { background: #fff3cd; color: #856404; }
        .priority-urgent { background: #f8d7da; color: #721c24; }
        
        .overdue-indicator {
            color: #dc3545;
            font-weight: 600;
        }
        .task-actions {
            display: flex;
            gap: 0.5rem;
        }
        .bulk-actions {
            background: #e9ecef;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            display: none;
        }
        .bulk-actions.show {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layouts/main.php'; ?>
    
    <!-- Заголовок -->
    <div class="admin-header">
        <div class="container">
            <h2>Администрирование</h2>
            <p class="mb-0">Управление всеми задачами в системе</p>
        </div>
    </div>
    
    <div class="container">
        <!-- Навигация администратора -->
        <div class="admin-nav">
            <ul class="nav nav-pills">
                <li class="nav-item">
                    <a class="nav-link" href="/admin/dashboard">
                        <i class="bi bi-speedometer2 me-2"></i>
                        Дашборд
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/admin/users">
                        <i class="bi bi-people me-2"></i>
                        Пользователи
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="/admin/tasks">
                        <i class="bi bi-list-task me-2"></i>
                        Все задачи
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/admin/invitations">
                        <i class="bi bi-envelope me-2"></i>
                        Приглашения
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/admin/departments">
                        <i class="bi bi-building me-2"></i>
                        Отделы
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/admin/reports">
                        <i class="bi bi-graph-up me-2"></i>
                        Отчеты
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Статистика задач -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-widget">
                    <h4><?= count($tasks) ?></h4>
                    <p>Всего задач</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-widget">
                    <h4><?= $taskStats['overdue'] ?></h4>
                    <p class="text-danger">Просрочено</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-widget">
                    <canvas id="statusChart" class="status-chart"></canvas>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-widget">
                    <canvas id="priorityChart" class="status-chart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Основной контент -->
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>Список всех задач</h4>
                <div>
                    <button class="btn btn-outline-primary" id="exportBtn">
                        <i class="bi bi-download me-2"></i>
                        Экспорт
                    </button>
                    <a href="/tasks/create" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>
                        Создать задачу
                    </a>
                </div>
            </div>
            
            <!-- Фильтры -->
            <div class="filter-section">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Статус</label>
                        <select class="form-select form-select-sm" id="filterStatus">
                            <option value="">Все</option>
                            <option value="backlog">Бэклог</option>
                            <option value="todo">К выполнению</option>
                            <option value="in_progress">В работе</option>
                            <option value="review">На проверке</option>
                            <option value="done">Выполнено</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Приоритет</label>
                        <select class="form-select form-select-sm" id="filterPriority">
                            <option value="">Все</option>
                            <option value="low">Низкий</option>
                            <option value="medium">Средний</option>
                            <option value="high">Высокий</option>
                            <option value="urgent">Срочный</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Создатель</label>
                        <select class="form-select form-select-sm" id="filterCreator">
                            <option value="">Все</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Отдел</label>
                        <select class="form-select form-select-sm" id="filterDepartment">
                            <option value="">Все</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Состояние</label>
                        <select class="form-select form-select-sm" id="filterCondition">
                            <option value="">Все</option>
                            <option value="overdue">Просроченные</option>
                            <option value="today">Сегодня</option>
                            <option value="week">Эта неделя</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-sm btn-outline-secondary w-100" id="resetFilters">
                            <i class="bi bi-x-circle me-1"></i>
                            Сбросить
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Массовые действия -->
            <div class="bulk-actions" id="bulkActions">
                <div>
                    <span>Выбрано задач: <strong id="selectedCount">0</strong></span>
                </div>
                <div>
                    <select class="form-select form-select-sm d-inline-block w-auto me-2" id="bulkStatus">
                        <option value="">Изменить статус</option>
                        <option value="todo">К выполнению</option>
                        <option value="in_progress">В работе</option>
                        <option value="done">Выполнено</option>
                    </select>
                    <select class="form-select form-select-sm d-inline-block w-auto me-2" id="bulkAssignee">
                        <option value="">Назначить исполнителя</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-sm btn-danger" id="bulkDelete">
                        <i class="bi bi-trash me-1"></i>
                        Удалить
                    </button>
                </div>
            </div>
            
            <!-- Таблица задач -->
            <div class="table-responsive">
                <table id="tasksTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th width="3%">
                                <input type="checkbox" class="form-check-input" id="selectAll">
                            </th>
                            <th width="5%">ID</th>
                            <th width="25%">Задача</th>
                            <th width="10%">Статус</th>
                            <th width="10%">Приоритет</th>
                            <th width="15%">Создатель</th>
                            <th width="15%">Исполнители</th>
                            <th width="10%">Дедлайн</th>
                            <th width="7%">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): ?>
                            <tr data-task-id="<?= $task['id'] ?>"
                                data-status="<?= $task['status'] ?>"
                                data-priority="<?= $task['priority'] ?>"
                                data-creator="<?= $task['creator_id'] ?>"
                                data-overdue="<?= $task['is_overdue'] ?>">
                                <td>
                                    <input type="checkbox" class="form-check-input task-checkbox" value="<?= $task['id'] ?>">
                                </td>
                                <td>#<?= $task['id'] ?></td>
                                <td>
                                    <div>
                                        <a href="/tasks/view/<?= $task['id'] ?>" class="text-decoration-none fw-semibold">
                                            <?= htmlspecialchars($task['title']) ?>
                                        </a>
                                        <?php if ($task['comment_count'] > 0): ?>
                                            <small class="text-muted ms-2">
                                                <i class="bi bi-chat-dots"></i> <?= $task['comment_count'] ?>
                                            </small>
                                        <?php endif; ?>
                                        <?php if (!empty($task['description'])): ?>
                                            <div class="text-muted small text-truncate" style="max-width: 300px;">
                                                <?= htmlspecialchars($task['description']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $statusLabels = [
                                        'backlog' => 'Бэклог',
                                        'todo' => 'К выполнению',
                                        'in_progress' => 'В работе',
                                        'review' => 'На проверке',
                                        'done' => 'Выполнено'
                                    ];
                                    ?>
                                    <span class="status-badge status-<?= $task['status'] ?>">
                                        <?= $statusLabels[$task['status']] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $priorityLabels = [
                                        'low' => 'Низкий',
                                        'medium' => 'Средний',
                                        'high' => 'Высокий',
                                        'urgent' => 'Срочный'
                                    ];
                                    ?>
                                    <span class="priority-badge priority-<?= $task['priority'] ?>">
                                        <?= $priorityLabels[$task['priority']] ?>
                                    </span>
                                </td>
                                <td>
                                    <div>
                                        <div><?= htmlspecialchars($task['creator_name']) ?></div>
                                        <?php if ($task['creator_department']): ?>
                                            <small class="text-muted"><?= htmlspecialchars($task['creator_department']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($task['assignee_names']): ?>
                                        <small><?= htmlspecialchars($task['assignee_names']) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($task['deadline']): ?>
                                        <div class="<?= $task['is_overdue'] ? 'overdue-indicator' : '' ?>">
                                            <?= date('d.m.Y', strtotime($task['deadline'])) ?>
                                            <?php if ($task['is_overdue']): ?>
                                                <br><small>Просрочено</small>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="task-actions">
                                        <a href="/tasks/edit/<?= $task['id'] ?>" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="Редактировать">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="deleteTask(<?= $task['id'] ?>)"
                                                title="Удалить">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
    
    <script>
        // Инициализация DataTable
        const table = $('#tasksTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/ru.json'
            },
            pageLength: 25,
            order: [[1, 'desc']],
            columnDefs: [
                { orderable: false, targets: [0, 8] }
            ]
        });
        
        // Графики
        const statusStats = <?= json_encode($taskStats['by_status']) ?>;
        const priorityStats = <?= json_encode($taskStats['by_priority']) ?>;
        
        // График статусов
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: ['Бэклог', 'К выполнению', 'В работе', 'На проверке', 'Выполнено'],
                datasets: [{
                    data: [
                        statusStats.backlog || 0,
                        statusStats.todo || 0,
                        statusStats.in_progress || 0,
                        statusStats.review || 0,
                        statusStats.done || 0
                    ],
                    backgroundColor: ['#e9ecef', '#cfe2ff', '#f8d7da', '#fff3cd', '#d1e7dd']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        // График приоритетов
        new Chart(document.getElementById('priorityChart'), {
            type: 'doughnut',
            data: {
                labels: ['Низкий', 'Средний', 'Высокий', 'Срочный'],
                datasets: [{
                    data: [
                        priorityStats.low || 0,
                        priorityStats.medium || 0,
                        priorityStats.high || 0,
                        priorityStats.urgent || 0
                    ],
                    backgroundColor: ['#d4edda', '#d1ecf1', '#fff3cd', '#f8d7da']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        // Выбрать все
        $('#selectAll').change(function() {
            $('.task-checkbox').prop('checked', this.checked);
            updateBulkActions();
        });
        
        $('.task-checkbox').change(function() {
            updateBulkActions();
        });
        
        function updateBulkActions() {
            const selected = $('.task-checkbox:checked').length;
            $('#selectedCount').text(selected);
            
            if (selected > 0) {
                $('#bulkActions').addClass('show');
            } else {
                $('#bulkActions').removeClass('show');
            }
        }
        
        // Удаление задачи
        function deleteTask(taskId) {
            if (!confirm('Удалить эту задачу? Это действие нельзя отменить!')) return;
            
            fetch(`/tasks/delete/${taskId}`, {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Ошибка при удалении задачи');
                }
            });
        }
        
        // Фильтрация
        // Здесь должна быть логика фильтрации таблицы
    </script>
</body>
</html>