<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Дашборд администратора - Система управления задачами</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
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
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: transform 0.2s, box-shadow 0.2s;
            height: 100%;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.12);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        .stat-icon.users { background: rgba(102, 126, 234, 0.1); color: #667eea; }
        .stat-icon.tasks { background: rgba(40, 167, 69, 0.1); color: #28a745; }
        .stat-icon.completed { background: rgba(23, 162, 184, 0.1); color: #17a2b8; }
        .stat-icon.departments { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
        .stat-icon.invitations { background: rgba(220, 53, 69, 0.1); color: #dc3545; }
        .stat-icon.active { background: rgba(32, 201, 151, 0.1); color: #20c997; }
        
        .chart-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        .activity-feed {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            max-height: 500px;
            overflow-y: auto;
        }
        .activity-item {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e9ecef;
            transition: background 0.2s;
        }
        .activity-item:hover {
            background: #f8f9fa;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }
        .activity-icon.task { background: #e3f2fd; color: #1976d2; }
        .activity-icon.user { background: #f3e5f5; color: #7b1fa2; }
        .activity-icon.comment { background: #e8f5e9; color: #388e3c; }
        
        .quick-action {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
            height: 100%;
        }
        .quick-action:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.12);
            color: inherit;
        }
        .quick-action-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
         #activityChart {
        max-height: 400px; /* Установите фиксированную максимальную высоту */
    }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layouts/main.php'; ?>
    
    <!-- Заголовок -->
    <div class="admin-header">
        <div class="container">
            <h2>Панель администратора</h2>
            <p class="mb-0">Обзор системы и статистика</p>
        </div>
    </div>
    
    <div class="container">
        <!-- Навигация администратора -->
        <div class="admin-nav">
            <ul class="nav nav-pills">
                <li class="nav-item">
                    <a class="nav-link active" href="/admin/dashboard">
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
                    <a class="nav-link" href="/admin/tasks">
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
        
        <!-- Статистика -->
        <div class="row g-4 mb-4">
            <div class="col-md-4 col-lg-2">
                <div class="stat-card">
                    <div class="stat-icon users">
                        <i class="bi bi-people"></i>
                    </div>
                    <h3 class="h5 text-muted mb-2">Пользователи</h3>
                    <p class="h3 mb-0"><?= $stats['total_users'] ?></p>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="stat-card">
                    <div class="stat-icon active">
                        <i class="bi bi-person-check"></i>
                    </div>
                    <h3 class="h5 text-muted mb-2">Активные</h3>
                    <p class="h3 mb-0"><?= $stats['active_users'] ?></p>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="stat-card">
                    <div class="stat-icon tasks">
                        <i class="bi bi-list-task"></i>
                    </div>
                    <h3 class="h5 text-muted mb-2">Задачи</h3>
                    <p class="h3 mb-0"><?= $stats['total_tasks'] ?></p>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="stat-card">
                    <div class="stat-icon completed">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <h3 class="h5 text-muted mb-2">Выполнено</h3>
                    <p class="h3 mb-0"><?= $stats['completed_tasks'] ?></p>
                    <small class="text-success">
                        <?= $stats['total_tasks'] > 0 ? round(($stats['completed_tasks'] / $stats['total_tasks']) * 100) : 0 ?>%
                    </small>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="stat-card">
                    <div class="stat-icon departments">
                        <i class="bi bi-building"></i>
                    </div>
                    <h3 class="h5 text-muted mb-2">Отделы</h3>
                    <p class="h3 mb-0"><?= $stats['total_departments'] ?></p>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="stat-card">
                    <div class="stat-icon invitations">
                        <i class="bi bi-envelope"></i>
                    </div>
                    <h3 class="h5 text-muted mb-2">Приглашения</h3>
                    <p class="h3 mb-0"><?= $stats['pending_invitations'] ?></p>
                    <small class="text-muted">Ожидают</small>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- График активности -->
            <div class="col-lg-8">
                <div class="chart-card">
                    <h5 class="mb-3">Активность за последние 30 дней</h5>
                    <canvas id="activityChart" height="100"></canvas>
                </div>
                
                <!-- Быстрые действия -->
                <div class="row g-3">
                    <div class="col-md-3">
                        <a href="/admin/invitations" class="quick-action">
                            <i class="bi bi-person-plus quick-action-icon text-primary"></i>
                            <h6>Пригласить пользователя</h6>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="/tasks/create" class="quick-action">
                            <i class="bi bi-plus-circle quick-action-icon text-success"></i>
                            <h6>Создать задачу</h6>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="/admin/reports" class="quick-action">
                            <i class="bi bi-file-earmark-bar-graph quick-action-icon text-warning"></i>
                            <h6>Просмотр отчетов</h6>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="/admin/departments" class="quick-action">
                            <i class="bi bi-diagram-3 quick-action-icon text-info"></i>
                            <h6>Управление отделами</h6>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Лента активности -->
            <div class="col-lg-4">
                <div class="activity-feed">
                    <div class="p-3 border-bottom">
                        <h5 class="mb-0">Последняя активность</h5>
                    </div>
                    
                    <?php if (empty($recentActivity)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-1"></i>
                            <p>Нет активности</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentActivity as $activity): ?>
                            <div class="activity-item">
                                <div class="d-flex gap-3">
                                    <div class="activity-icon <?= $activity['type'] ?>">
                                        <?php
                                        $icons = [
                                            'task_created' => 'bi-plus-circle',
                                            'user_registered' => 'bi-person-plus',
                                            'comment_added' => 'bi-chat-dots'
                                        ];
                                        ?>
                                        <i class="bi <?= $icons[$activity['type']] ?? 'bi-circle' ?>"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <p class="mb-1">
                                            <strong><?= htmlspecialchars($activity['user_name']) ?></strong>
                                            <?php
                                            $actions = [
                                                'task_created' => 'создал задачу',
                                                'user_registered' => 'зарегистрировался',
                                                'comment_added' => 'добавил комментарий'
                                            ];
                                            echo $actions[$activity['type']] ?? $activity['type'];
                                            ?>
                                        </p>
                                        <p class="text-muted small mb-0">
                                            <?= htmlspecialchars($activity['description']) ?>
                                        </p>
                                        <small class="text-muted">
                                            <?= date('d.m.Y H:i', strtotime($activity['timestamp'])) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // График активности
        const activityData = <?= json_encode($activityData) ?>;
        
        const ctx = document.getElementById('activityChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: activityData.map(item => {
                    const date = new Date(item.date);
                    return date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' });
                }),
                datasets: [{
                    label: 'Создано задач',
                    data: activityData.map(item => item.tasks_created),
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    </script>
</body>
</html>