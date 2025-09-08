<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Главная - Система управления задачами</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
    <style>
        :root {
            --main-bg: #f7f7fa;
            --card-bg: #ffffffdd;
            --primary: #007aff;
            --primary-fade: #eaf4fd;
            --danger: #ff453a;
            --success: #34c759;
            --warning: #ff9f0a;
            --info: #5ac8fa;
            --border-radius: 22px;
            --shadow: 0 4px 32px 0 rgba(0,0,0,0.06);
            --font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        html, body {
            background: var(--main-bg);
            font-family: var(--font-family);
            color: #1c1c1e;
            box-sizing: border-box;
        }
        *, *:before, *:after { box-sizing: border-box; }
        .dashboard-header {
            background: linear-gradient(253deg, var(--primary), #10cfc9 90%);
            color: white;
            padding: 2.7rem 0 2rem 0;
            margin-bottom: 2.5rem;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            box-shadow: 0 4px 24px 0 #007aff18;
        }
        .dashboard-header .welcome-message {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -.01em;
            margin-bottom: .2rem;
        }
        .dashboard-header .date-time {
            font-size: 1.1rem;
            opacity: 0.85;
        }
        /* Обновления для дашборда */
.dashboard-stat.waiting_approval {
    border-left: 4px solid #6f42c1;
}

.dashboard-stat.waiting_approval .stat-icon {
    background: rgba(111, 66, 193, 0.1);
    color: #6f42c1;
}

/* Фильтры в списке задач */
.filter-chip[data-status="waiting_approval"] {
    background: rgba(111, 66, 193, 0.1);
    color: #6f42c1;
    border: 1px solid rgba(111, 66, 193, 0.2);
}

.filter-chip[data-status="waiting_approval"].active {
    background: #6f42c1;
    color: white;
}
        .btn-ios {
            border-radius: 14px;
            font-weight: 500;
            box-shadow: 0 2px 12px 0 #007aff15;
            background: #fff;
            color: var(--primary);
            border: none;
            transition: box-shadow 0.15s;
        }
        .btn-ios:active, .btn-ios:focus {
            background: var(--primary-fade);
            color: var(--primary);
            box-shadow: 0 4px 24px 0 #007aff1a;
        }
        .stat-card {
            display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 1.8rem 1.3rem 1.2rem 1.3rem;
            text-align: center;
            border: none;
            transition: box-shadow .18s, transform .18s;
            min-height: unset;
            height: auto;
        }

        
        .stat-card:hover {
            box-shadow: 0 8px 40px 0 #007aff22;
            transform: translateY(-2px) scale(1.01);
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 1rem;
            background: var(--primary-fade);
            color: var(--primary);
        }
        .stat-icon.success { background: #eafcf6; color: var(--success);}
        .stat-icon.danger { background: #fff2f2; color: var(--danger);}
        .stat-icon.warning { background: #fff7ec; color: var(--warning);}
        .stat-icon.info { background: #eaf7fd; color: var(--info);}
        .stat-label {
            font-weight: 500;
            font-size: 1rem;
            color: #8e8e93;
            margin-bottom: 0.2rem;
            letter-spacing: .01em;
        }
        .stat-value {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 0.1rem;
        }
        .stat-extra {
            font-size: .95rem;
            color: var(--success);
            font-weight: 500;
        }
        .chart-container {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 1.8rem 1.5rem 1.5rem 1.5rem;
            margin-bottom: 2rem;
            height: 340px;
            min-height: unset;
            max-height: 380px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }
        .chart-container canvas {
            /* Chart.js по умолчанию canvas width/height из атрибута, а не css.
               Чтобы контролировать высоту, задаём !important */
            height: 260px !important;
            max-height: 260px;
        }
        .card {
            border: none;
            border-radius: var(--border-radius);
            background: var(--card-bg);
            box-shadow: var(--shadow);
            margin-bottom: 1.9rem;
            min-height: unset;
            height: auto;
        }
        .card-header {
            background: transparent;
            border: none;
            font-weight: 600;
            font-size: 1.13rem;
            letter-spacing: -.01em;
        }
        .task-item {
            padding: 1rem 1.1rem;
            border-bottom: 1px solid #f2f2f7;
            transition: background 0.17s;
            border-radius: 0;
        }
        .task-item:hover {
            background: #f8f9fa;
        }
        .priority-badge {
            border-radius: 12px;
            padding: 0.23rem 0.9rem;
            font-size: .81rem;
            font-weight: 500;
            background: #f2f2f7;
            color: #8e8e93;
            margin-left: 0.7rem;
        }
        .priority-urgent { background: #fff2f2; color: var(--danger);}
        .priority-high { background: #fff7ec; color: var(--warning);}
        .priority-medium { background: #eaf7fd; color: var(--info);}
        .priority-low { background: #eafcf6; color: var(--success);}
        .notification-item {
            padding: 1rem 1.2rem;
            border-bottom: 1px solid #f2f2f7;
            background: transparent;
            transition: background 0.17s;
            border-radius: 0;
        }
        .notification-item.unread {
            background: #f7faff;
            border-left: 3px solid var(--primary);
        }
        .notification-item:last-child,
        .task-item:last-child {
            border-bottom: none;
        }
        .badge {
            border-radius: 10px;
            font-size: .8rem;
            font-weight: 500;
        }
        .list-group-item {
            background: transparent;
            border: none;
            border-radius: 0;
        }
        .activity-item {
            padding: 1rem 0 1rem 1.3rem;
            border-left: 2.5px solid #e5e5ea;
            margin-left: .5rem;
            position: relative;
        }
        .activity-item::before {
            content: '';
            position: absolute;
            left: -9px;
            top: 1.45rem;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary);
            border: 2px solid #fff;
        }
        .activity-item h6 {
            font-size: .98rem;
            font-weight: 600;
            margin-bottom: 0.12rem;
            color: var(--primary);
        }
        .activity-item p {
            color: #8e8e93;
            font-size: .96rem;
            margin-bottom: 0.1rem;
        }
        @media (max-width: 992px) {
            .dashboard-header { padding: 2.2rem 0 1.4rem 0;}
        }
        @media (max-width: 767px) {
            .dashboard-header { border-radius: 0 0 16px 16px;}
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layouts/main.php'; ?>
    <!-- Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="row align-items-center gy-3">
                <div class="col-md-8">
                    <div class="welcome-message">Добро пожаловать, <?= htmlspecialchars($_SESSION['user_name']) ?>!</div>
                    <div class="date-time">
                        <i class="bi bi-calendar3 me-2"></i>
                        <?php $date = new IntlDateFormatter(
                            'ru_RU',
                            IntlDateFormatter::LONG,
                            IntlDateFormatter::NONE,
                            'Europe/Moscow',
                            IntlDateFormatter::GREGORIAN,
                            'd MMMM y, EEEE'
                        );
                        echo $date->format(new DateTime()); ?>
                    </div>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="/tasks/create" class="btn btn-ios btn-lg">
                        <i class="bi bi-plus-circle me-2"></i>Новая задача
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="container">
        <!-- Статистика -->
        <div class="row g-4 mb-4 align-items-start">
    <div class="col-6 col-md-3">
        <div class="stat-card d-flex flex-column h-100">
            <div class="stat-icon"><i class="bi bi-list-task"></i></div>
            <div class="stat-label">Всего задач</div>
            <div class="stat-value"><?= $userStats['assigned_tasks'] ?></div>
            <div class="stat-extra" style="visibility: hidden;">.</div> <!-- Пустое место для выравнивания -->
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card d-flex flex-column h-100">
            <div class="stat-icon success"><i class="bi bi-check-circle"></i></div>
            <div class="stat-label">Выполнено</div>
            <div class="stat-value"><?= $userStats['completed_tasks'] ?></div>
            <div class="stat-extra">
                <i class="bi bi-graph-up"></i> <?= $userStats['completion_rate'] ?>%
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card d-flex flex-column h-100">
            <div class="stat-icon warning"><i class="bi bi-clock"></i></div>
            <div class="stat-label">Сегодня</div>
            <div class="stat-value"><?= $userStats['tasks_due_today'] ?></div>
            <div class="stat-extra" style="visibility: hidden;">.</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card d-flex flex-column h-100">
            <div class="stat-icon danger"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="stat-label">Просрочено</div>
            <div class="stat-value"><?= $userStats['overdue_tasks'] ?></div>
            <div class="stat-extra" style="visibility: hidden;">.</div>
        </div>
    </div>
</div>
        <div class="row g-4 align-items-start">
            <!-- Левая колонка -->
            <div class="col-lg-8">
                <!-- График выполнения задач -->
                <div class="chart-container mb-4">
                    <h5 class="fw-bold mb-3" style="font-size:1.1rem;">Выполнение задач за последние 7 дней</h5>
                    <canvas id="completionChart"></canvas>
                </div>
                <!-- Последние задачи -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Мои последние задачи</span>
                        <a href="/tasks/kanban" class="btn btn-ios btn-sm px-3">Все задачи</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recentTasks)): ?>
                            <p class="text-muted text-center py-4">Нет активных задач</p>
                        <?php else: ?>
                            <?php
                            $priorityLabels = [
                                'urgent' => 'срочный',
                                'high' => 'высокий',
                                'medium' => 'средний',
                                'low' => 'низкий'
                            ];
                            foreach ($recentTasks as $task): ?>
                                <div class="task-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-semibold" style="font-size:1.04rem;">
                                            <a href="/tasks/view/<?= $task['id'] ?>" class="text-decoration-none text-dark">
                                                <?= htmlspecialchars($task['title']) ?>
                                            </a>
                                        </div>
                                        <div class="text-muted small">
                                            <?= htmlspecialchars($task['description'] ?? 'Без описания') ?>
                                        </div>
                                    </div>
                                    <span class="priority-badge priority-<?= $task['priority'] ?>">
                                        <?= $priorityLabels[$task['priority']] ?? htmlspecialchars($task['priority']) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Приближающиеся дедлайны -->
                <?php if (!empty($upcomingDeadlines)): ?>
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-calendar-event text-warning me-2"></i>
                        Приближающиеся дедлайны
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <?php foreach ($upcomingDeadlines as $task): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <div class="fw-semibold"><?= htmlspecialchars($task['title']) ?></div>
                                        <small class="text-muted">
                                            <i class="bi bi-clock me-1"></i>
                                            <?= date('d.m.Y H:i', strtotime($task['deadline'])) ?>
                                        </small>
                                    </div>
                                    <?php 
                                        $daysLeft = ceil((strtotime($task['deadline']) - time()) / 86400);
                                        $badgeClass = $daysLeft <= 1 ? 'danger' : ($daysLeft <= 3 ? 'warning' : 'info');
                                    ?>
                                    <span class="badge bg-<?= $badgeClass ?>">
                                        <?= $daysLeft ?> дн.
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <!-- Правая колонка -->
            <div class="col-lg-4">
                <!-- Уведомления -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>
                            Уведомления
                            <span id="dashboard-notification-count" class="badge bg-danger" style="display: none;">0</span>
                        </span>
                        <a href="/notifications" class="btn btn-ios btn-sm px-3">Все</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recentNotifications)): ?>
                            <p class="text-muted text-center py-4">Нет новых уведомлений</p>
                        <?php else: ?>
                            <?php foreach ($recentNotifications as $notification): ?>
                                <div class="notification-item <?= !$notification['is_read'] ? 'unread' : '' ?>">
                                    <div class="fw-semibold small mb-1"><?= htmlspecialchars($notification['title']) ?></div>
                                    <div class="text-muted small mb-1"><?= htmlspecialchars($notification['message']) ?></div>
                                    <small class="text-muted">
                                        <i class="bi bi-clock me-1"></i>
                                        <?= date('d.m H:i', strtotime($notification['created_at'])) ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Распределение по приоритетам -->
                <div class="chart-container mb-4">
                    <h5 class="fw-bold mb-3" style="font-size:1.1rem;">Задачи по приоритетам</h5>
                    <canvas id="priorityChart"></canvas>
                </div>
                <!-- Активность команды -->
                <?php if ($teamActivity): ?>
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-people me-2"></i>
                        Активность команды
                    </div>
                    <div class="card-body">
                        <?php foreach ($teamActivity as $activity): ?>
                            <div class="activity-item">
                                <h6 class="mb-1"><?= htmlspecialchars($activity['user_name']) ?></h6>
                                <p class="mb-0">
                                    Обновил задачу "<?= htmlspecialchars($activity['task_title']) ?>"
                                </p>
                                <small class="text-muted">
                                    <?= date('d.m H:i', strtotime($activity['updated_at'])) ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            fetch('/notifications/recent')
                .then(response => response.json())
                .then(data => {
                    const badge = document.getElementById('dashboard-notification-count');
                    if (data.unread_count > 0) {
                        badge.textContent = data.unread_count;
                        badge.style.display = 'inline-block';
                    }
                });
        });
        fetch('/dashboard/chart-data')
            .then(response => response.json())
            .then(data => {
                // График линейный
                const ctx1 = document.getElementById('completionChart').getContext('2d');
                new Chart(ctx1, {
                    type: 'line',
                    data: {
                        labels: data.completion.map(item => item.date),
                        datasets: [{
                            label: 'Выполнено задач',
                            data: data.completion.map(item => item.completed_count),
                            borderColor: '#007aff',
                            backgroundColor: 'rgba(0, 122, 255, 0.07)',
                            tension: 0.38,
                            pointRadius: 4,
                            pointBackgroundColor: '#fff',
                            pointBorderWidth: 2,
                            pointBorderColor: '#007aff',
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            x: {
                                grid: { display: false }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: { stepSize: 1, font: { size: 14 } },
                                grid: { color: "#f2f2f7" }
                            }
                        }
                    }
                });
                // Круговая диаграмма приоритетов
                const ctx2 = document.getElementById('priorityChart').getContext('2d');
                const priorityLabels = {
                    'urgent': 'срочный',
                    'high': 'высокий',
                    'medium': 'средний',
                    'low': 'низкий'
                };
                const priorityColors = {
                    'urgent': '#ff453a',
                    'high': '#ff9f0a',
                    'medium': '#5ac8fa',
                    'low': '#34c759'
                };
                new Chart(ctx2, {
                    type: 'doughnut',
                    data: {
                        labels: data.priorities.map(item => priorityLabels[item.priority] || item.priority),
                        datasets: [{
                            data: data.priorities.map(item => item.count),
                            backgroundColor: data.priorities.map(item => priorityColors[item.priority] || '#ced4da'),
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: "70%",
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { font: { size: 15 } }
                            }
                        }
                    }
                });
            });
    </script>



</body>
</html>