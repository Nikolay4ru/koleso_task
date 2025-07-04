<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Список задач - Система управления задачами</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .tasks-container {
            max-width: 1400px;
            margin: 2rem auto;
        }
        .page-header {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        .page-header h2 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        .task-status-waiting_approval {
    background: rgba(90, 55, 184, 0.1);
    color: #5a37b8;
    border: 1px solid rgba(90, 55, 184, 0.2);
}

        .filters-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        .filter-group {
            margin-bottom: 1rem;
        }
        .filter-group:last-child {
            margin-bottom: 0;
        }
        .filter-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .table-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .table-header {
            background: #f8f9fa;
            padding: 1.5rem;
            border-bottom: 1px solid #dee2e6;
        }
        .table-container {
            padding: 1.5rem;
        }
        .task-table {
            width: 100% !important;
        }
        .task-table thead th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #495057;
            padding: 1rem;
            white-space: nowrap;
        }
        .task-table tbody td {
            padding: 1rem;
            vertical-align: middle;
        }
        .task-title {
            font-weight: 600;
            color: #2c3e50;
            text-decoration: none;
            transition: color 0.2s;
        }
        .task-title:hover {
            color: #667eea;
        }
        .task-description {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 0.25rem;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 15px;
            font-weight: 500;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }
        .status-backlog { background: #e9ecef; color: #495057; }
        .status-todo { background: #cfe2ff; color: #084298; }
        .status-in_progress { background: #f8d7da; color: #842029; }
        .status-review { background: #fff3cd; color: #664d03; }
        .status-done { background: #d1e7dd; color: #0f5132; }
        
        .priority-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 15px;
            font-weight: 500;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        .priority-low { background: #d4edda; color: #155724; }
        .priority-medium { background: #d1ecf1; color: #0c5460; }
        .priority-high { background: #fff3cd; color: #856404; }
        .priority-urgent { background: #f8d7da; color: #721c24; }
        
        .user-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.75rem;
            margin-right: 0.25rem;
        }
        .user-list {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.25rem;
        }
        .user-more {
            background: #e9ecef;
            color: #6c757d;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 10px;
        }
        .deadline-text {
            font-size: 0.875rem;
        }
        .deadline-overdue {
            color: #dc3545;
            font-weight: 600;
        }
        .deadline-soon {
            color: #ffc107;
            font-weight: 600;
        }
        .deadline-normal {
            color: #6c757d;
        }
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        .btn-action {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            border: none;
            background: #f8f9fa;
            color: #6c757d;
            border-radius: 4px;
            transition: all 0.2s;
        }
        .btn-action:hover {
            background: #e9ecef;
            color: #495057;
        }
        .dataTables_wrapper .dataTables_length select {
            padding: 0.375rem 2rem 0.375rem 0.75rem;
        }
        .dataTables_wrapper .dataTables_filter input {
            border: 2px solid #e9ecef;
            border-radius: 20px;
            padding: 0.5rem 1rem;
            transition: all 0.3s;
        }
        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .export-buttons {
            display: flex;
            gap: 0.5rem;
        }
        .view-toggle {
            display: flex;
            gap: 0.5rem;
            background: #f8f9fa;
            padding: 0.25rem;
            border-radius: 8px;
        }
        .view-toggle .btn {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border: none;
            background: transparent;
            color: #6c757d;
            transition: all 0.2s;
        }
        .view-toggle .btn.active {
            background: white;
            color: #495057;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .quick-filters {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .quick-filter {
            padding: 0.375rem 0.75rem;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 20px;
            font-size: 0.875rem;
            color: #6c757d;
            cursor: pointer;
            transition: all 0.2s;
        }
        .quick-filter:hover {
            background: #e9ecef;
            border-color: #adb5bd;
        }
        .quick-filter.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        @media (max-width: 768px) {
            .table-header {
                flex-direction: column;
                gap: 1rem;
            }
            .export-buttons {
                width: 100%;
            }
            .filters-card {
                position: fixed;
                top: 0;
                left: -100%;
                width: 80%;
                height: 100vh;
                z-index: 1050;
                transition: left 0.3s;
                border-radius: 0;
            }
            .filters-card.show {
                left: 0;
            }
            .filters-backdrop {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 1049;
                display: none;
            }
            .filters-backdrop.show {
                display: block;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layouts/main.php'; ?>
    
    <div class="container-fluid tasks-container">
        <!-- Заголовок -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2>Список задач</h2>
                    <p class="text-muted mb-0">
                        Всего задач: <?= count($tasks) ?> • 
                        Активных: <?= count(array_filter($tasks, function($t) { return !in_array($t['status'], ['done', 'backlog']); })) ?>
                    </p>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <a href="/tasks/create" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>
                        Новая задача
                    </a>
                    <button class="btn btn-outline-secondary d-md-none" id="toggleFilters">
                        <i class="bi bi-funnel"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Фильтры (для десктопа - боковая панель, для мобильных - выезжающая) -->
            <div class="col-md-3">
                <div class="filters-card" id="filtersPanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Фильтры</h5>
                        <button class="btn btn-sm btn-outline-secondary" id="resetFilters">
                            <i class="bi bi-x-circle me-1"></i>
                            Сбросить
                        </button>
                    </div>
                    
                    <!-- Быстрые фильтры -->
                    <div class="filter-group">
                        <div class="filter-label">Быстрые фильтры</div>
                        <div class="quick-filters">
                            <div class="quick-filter" data-filter="my-tasks">
                                <i class="bi bi-person me-1"></i>
                                Мои задачи
                            </div>
                            <div class="quick-filter" data-filter="assigned-to-me">
                                <i class="bi bi-arrow-right-circle me-1"></i>
                                Назначены мне
                            </div>
                            <div class="quick-filter" data-filter="created-by-me">
                                <i class="bi bi-pencil me-1"></i>
                                Созданные мной
                            </div>
                            <div class="quick-filter" data-filter="overdue">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                Просроченные
                            </div>
                        </div>
                    </div>
                    
                    <!-- Статус -->
                    <div class="filter-group">
                        <div class="filter-label">Статус</div>
                        <select class="form-select form-select-sm" id="filterStatus" multiple>
    <option value="backlog">Очередь задач</option>
    <option value="todo">К выполнению</option>
    <option value="in_progress">В работе</option>
    <option value="waiting_approval">Ожидает проверки</option>
    <option value="done">Выполнено</option>
</select>
                    </div>
                    
                    <!-- Приоритет -->
                    <div class="filter-group">
                        <div class="filter-label">Приоритет</div>
                        <select class="form-select form-select-sm" id="filterPriority" multiple>
                            <option value="low">Низкий</option>
                            <option value="medium">Средний</option>
                            <option value="high">Высокий</option>
                            <option value="urgent">Срочный</option>
                        </select>
                    </div>
                    
                    <!-- Исполнитель -->
                    <div class="filter-group">
                        <div class="filter-label">Исполнитель</div>
                        <select class="form-select form-select-sm" id="filterAssignee">
                            <option value="">Все</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Отдел -->
                    <div class="filter-group">
                        <div class="filter-label">Отдел</div>
                        <select class="form-select form-select-sm" id="filterDepartment">
                            <option value="">Все</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Период -->
                    <div class="filter-group">
                        <div class="filter-label">Период</div>
                        <select class="form-select form-select-sm" id="filterPeriod">
                            <option value="">Все время</option>
                            <option value="today">Сегодня</option>
                            <option value="week">Эта неделя</option>
                            <option value="month">Этот месяц</option>
                            <option value="overdue">Просроченные</option>
                        </select>
                    </div>
                </div>
                <div class="filters-backdrop" id="filtersBackdrop"></div>
            </div>
            
            <!-- Таблица задач -->
            <div class="col-md-9">
                <div class="table-card">
                    <div class="table-header d-flex justify-content-between align-items-center">
                        <div class="view-toggle">
                            <button class="btn active" data-view="table">
                                <i class="bi bi-list"></i>
                            </button>
                            <button class="btn" data-view="grid">
                                <i class="bi bi-grid-3x3"></i>
                            </button>
                            <a href="/tasks/kanban" class="btn">
                                <i class="bi bi-kanban"></i>
                            </a>
                        </div>
                        
                        <div class="export-buttons">
                            <button class="btn btn-sm btn-outline-secondary" id="exportExcel">
                                <i class="bi bi-file-earmark-excel me-1"></i>
                                Excel
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" id="exportPDF">
                                <i class="bi bi-file-earmark-pdf me-1"></i>
                                PDF
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" id="printTable">
                                <i class="bi bi-printer me-1"></i>
                                Печать
                            </button>
                        </div>
                    </div>
                    
                    <div class="table-container">
                        <table id="tasksTable" class="table table-hover task-table">
                            <thead>
                                <tr>
                                    <th width="5%">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="selectAll">
                                        </div>
                                    </th>
                                    <th width="5%">ID</th>
                                    <th width="30%">Задача</th>
                                    <th width="10%">Статус</th>
                                    <th width="10%">Приоритет</th>
                                    <th width="15%">Исполнители</th>
                                    <th width="10%">Дедлайн</th>
                                    <th width="10%">Создана</th>
                                    <th width="10%">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tasks as $task): ?>
                                    <tr data-task-id="<?= $task['id'] ?>" 
                                        data-status="<?= $task['status'] ?>"
                                        data-priority="<?= $task['priority'] ?>">
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input task-checkbox" type="checkbox" value="<?= $task['id'] ?>">
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-muted">#<?= $task['id'] ?></span>
                                        </td>
                                        <td>
                                            <div>
                                                <a href="/tasks/view/<?= $task['id'] ?>" class="task-title">
                                                    <?= htmlspecialchars($task['title']) ?>
                                                </a>
                                                <?php if (!empty($task['description'])): ?>
                                                    <div class="task-description">
                                                        <?= htmlspecialchars($task['description']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $statusLabels = [
                                                'backlog' => 'Очередь задач',
                                                'todo' => 'К выполнению',
                                                'in_progress' => 'В работе',
                                                'review' => 'На проверке',
                                                'done' => 'Выполнено'
                                            ];
                                            ?>
                                            <span class="status-badge status-<?= $task['status'] ?>">
                                                <?= $statusLabels[$task['status']] ?? $task['status'] ?>
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
                                            $priorityIcons = [
                                                'low' => 'bi-arrow-down',
                                                'medium' => 'bi-dash',
                                                'high' => 'bi-arrow-up',
                                                'urgent' => 'bi-exclamation-circle-fill'
                                            ];
                                            ?>
                                            <span class="priority-badge priority-<?= $task['priority'] ?>">
                                                <i class="bi <?= $priorityIcons[$task['priority']] ?>"></i>
                                                <?= $priorityLabels[$task['priority']] ?? $task['priority'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="user-list">
                                                <?php 
                                                if (!empty($task['assignees'])):
                                                    $assignees = is_string($task['assignees']) ? explode(',', $task['assignees']) : $task['assignees'];
                                                    $visibleAssignees = array_slice($assignees, 0, 2);
                                                    foreach ($visibleAssignees as $assignee):
                                                        $name = is_array($assignee) ? $assignee['name'] : trim($assignee);
                                                        $initials = implode('', array_map(function($word) { 
                                                            return mb_substr($word, 0, 1); 
                                                        }, explode(' ', $name)));
                                                ?>
                                                    <div class="user-avatar" title="<?= htmlspecialchars($name) ?>">
                                                        <?= mb_strtoupper($initials) ?>
                                                    </div>
                                                <?php 
                                                    endforeach;
                                                    if (count($assignees) > 2):
                                                ?>
                                                    <span class="user-more">+<?= count($assignees) - 2 ?></span>
                                                <?php 
                                                    endif;
                                                else: 
                                                ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($task['deadline']): ?>
                                                <?php
                                                $deadline = strtotime($task['deadline']);
                                                $now = time();
                                                $daysLeft = ceil(($deadline - $now) / 86400);
                                                $isOverdue = $deadline < $now && $task['status'] != 'done';
                                                ?>
                                                <div class="deadline-text <?= $isOverdue ? 'deadline-overdue' : ($daysLeft <= 3 ? 'deadline-soon' : 'deadline-normal') ?>">
                                                    <i class="bi bi-calendar3 me-1"></i>
                                                    <?= date('d.m.Y', $deadline) ?>
                                                    <?php if ($isOverdue): ?>
                                                        <br><small>Просрочено</small>
                                                    <?php elseif ($daysLeft == 0): ?>
                                                        <br><small>Сегодня</small>
                                                    <?php elseif ($daysLeft == 1): ?>
                                                        <br><small>Завтра</small>
                                                    <?php elseif ($daysLeft <= 3): ?>
                                                        <br><small><?= $daysLeft ?> дн.</small>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="text-muted">
                                                <?= date('d.m.Y', strtotime($task['created_at'])) ?>
                                                <br>
                                                <small><?= htmlspecialchars($task['creator_name'] ?? '') ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="/tasks/view/<?= $task['id'] ?>" class="btn-action" title="Просмотр">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="/tasks/edit/<?= $task['id'] ?>" class="btn-action" title="Редактировать">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button class="btn-action" onclick="duplicateTask(<?= $task['id'] ?>)" title="Дублировать">
                                                    <i class="bi bi-files"></i>
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
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
    
    <script>
        // Инициализация DataTable
        const table = $('#tasksTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/ru.json'
            },
            pageLength: 25,
            order: [[7, 'desc']], // Сортировка по дате создания
            columnDefs: [
                { orderable: false, targets: [0, 8] }, // Отключаем сортировку для чекбоксов и действий
                { className: 'text-center', targets: [0, 1, 3, 4, 6, 7, 8] }
            ],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
            buttons: [
                {
                    extend: 'excel',
                    text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
                    className: 'btn btn-sm btn-outline-secondary',
                    exportOptions: {
                        columns: [1, 2, 3, 4, 5, 6, 7]
                    }
                },
                {
                    extend: 'pdf',
                    text: '<i class="bi bi-file-earmark-pdf me-1"></i> PDF',
                    className: 'btn btn-sm btn-outline-secondary',
                    exportOptions: {
                        columns: [1, 2, 3, 4, 5, 6, 7]
                    }
                },
                {
                    extend: 'print',
                    text: '<i class="bi bi-printer me-1"></i> Печать',
                    className: 'btn btn-sm btn-outline-secondary',
                    exportOptions: {
                        columns: [1, 2, 3, 4, 5, 6, 7]
                    }
                }
            ]
        });
        
        // Экспорт кнопки
        $('#exportExcel').click(function() {
            table.button('.buttons-excel').trigger();
        });
        
        $('#exportPDF').click(function() {
            table.button('.buttons-pdf').trigger();
        });
        
        $('#printTable').click(function() {
            table.button('.buttons-print').trigger();
        });
        
        // Мобильные фильтры
        $('#toggleFilters').click(function() {
            $('#filtersPanel').addClass('show');
            $('#filtersBackdrop').addClass('show');
        });
        
        $('#filtersBackdrop').click(function() {
            $('#filtersPanel').removeClass('show');
            $('#filtersBackdrop').removeClass('show');
        });
        
        // Выбрать все
        $('#selectAll').change(function() {
            $('.task-checkbox').prop('checked', this.checked);
        });
        
        // Быстрые фильтры
        $('.quick-filter').click(function() {
            $(this).toggleClass('active');
            applyFilters();
        });
        
        // Применение фильтров
        function applyFilters() {
            // Здесь должна быть логика фильтрации
            // Для примера - простая фильтрация по статусу
            const selectedStatuses = $('#filterStatus').val() || [];
            const selectedPriorities = $('#filterPriority').val() || [];
            
            table.rows().every(function() {
                const row = this.node();
                const status = $(row).data('status');
                const priority = $(row).data('priority');
                
                let show = true;
                
                if (selectedStatuses.length > 0 && !selectedStatuses.includes(status)) {
                    show = false;
                }
                
                if (selectedPriorities.length > 0 && !selectedPriorities.includes(priority)) {
                    show = false;
                }
                
                if (show) {
                    $(row).show();
                } else {
                    $(row).hide();
                }
            });
            
            table.draw();
        }
        
        // Обработчики фильтров
        $('#filterStatus, #filterPriority, #filterAssignee, #filterDepartment, #filterPeriod').change(function() {
            applyFilters();
        });
        
        // Сброс фильтров
        $('#resetFilters').click(function() {
            $('#filterStatus').val([]);
            $('#filterPriority').val([]);
            $('#filterAssignee').val('');
            $('#filterDepartment').val('');
            $('#filterPeriod').val('');
            $('.quick-filter').removeClass('active');
            
            // Показываем все строки
            table.rows().every(function() {
                $(this.node()).show();
            });
            table.draw();
        });
        
        // Дублирование задачи
        function duplicateTask(taskId) {
            if (confirm('Создать копию этой задачи?')) {
                fetch(`/tasks/duplicate/${taskId}`, {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = `/tasks/edit/${data.newTaskId}`;
                    } else {
                        alert('Ошибка при дублировании задачи');
                    }
                });
            }
        }
        
        // Переключение видов
        $('.view-toggle .btn').click(function() {
            if ($(this).data('view') === 'grid') {
                window.location.href = '/tasks/grid';
            }
        });
        
        // Применение настроек DataTable для мобильных
        if (window.innerWidth < 768) {
            table.columns([5, 7]).visible(false); // Скрываем некоторые колонки на мобильных
        }
        
        // Инициализация multiple select
        $('#filterStatus, #filterPriority').attr('size', 5);
    </script>
</body>
</html>