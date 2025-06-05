<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мой профиль - Система управления задачами</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
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

        body {
            background: var(--ios-bg);
            font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', sans-serif;
            color: #1c1c1e;
        }

        /* Заголовок профиля */
        .profile-header {
            background: linear-gradient(135deg, var(--ios-primary) 0%, var(--ios-secondary) 100%);
            padding: 3rem 0 4rem;
            margin-bottom: -3rem;
            position: relative;
        }

        .profile-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3rem;
            background: var(--ios-bg);
            border-radius: var(--ios-radius) var(--ios-radius) 0 0;
        }

        /* Карточка профиля */
        .profile-card {
            background: var(--ios-card);
            border-radius: var(--ios-radius);
            box-shadow: var(--ios-shadow);
            padding: 2rem;
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 1;
        }

        /* Аватар */
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 60px;
            background: linear-gradient(135deg, var(--ios-primary), var(--ios-secondary));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: 700;
            margin: -60px auto 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border: 4px solid white;
        }

        .profile-name {
            font-size: 1.75rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 0.5rem;
            color: #1c1c1e;
        }

        .profile-info {
            text-align: center;
            color: var(--ios-gray);
            margin-bottom: 2rem;
        }

        /* Статистика */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--ios-gray-6);
            border-radius: var(--ios-radius-sm);
            padding: 1.5rem;
            text-align: center;
            transition: all 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            background: var(--ios-gray-5);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--ios-primary);
            line-height: 1;
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--ios-gray);
            margin-top: 0.5rem;
        }

        /* Информационные секции */
        .info-section {
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1c1c1e;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: var(--ios-gray-6);
            border-radius: var(--ios-radius-sm);
            margin-bottom: 0.75rem;
            transition: all 0.2s;
        }

        .info-item:hover {
            background: var(--ios-gray-5);
        }

        .info-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: var(--ios-primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .info-content {
            flex: 1;
        }

        .info-label {
            font-size: 0.875rem;
            color: var(--ios-gray);
        }

        .info-value {
            font-size: 1rem;
            color: #1c1c1e;
            font-weight: 500;
        }

        /* Задачи */
        .task-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .task-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: var(--ios-gray-6);
            border-radius: var(--ios-radius-sm);
            margin-bottom: 0.75rem;
            transition: all 0.2s;
            text-decoration: none;
            color: inherit;
        }

        .task-item:hover {
            background: var(--ios-gray-5);
            transform: translateX(4px);
            text-decoration: none;
            color: inherit;
        }

        .task-priority {
            width: 4px;
            height: 40px;
            border-radius: 2px;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .priority-urgent { background: var(--ios-danger); }
        .priority-high { background: var(--ios-warning); }
        .priority-medium { background: var(--ios-primary); }
        .priority-low { background: var(--ios-success); }

        .task-content {
            flex: 1;
        }

        .task-title {
            font-weight: 500;
            color: #1c1c1e;
            margin-bottom: 0.25rem;
        }

        .task-meta {
            font-size: 0.875rem;
            color: var(--ios-gray);
        }

        .task-status {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
            background: var(--ios-gray-5);
            color: var(--ios-gray);
        }

        /* Кнопки действий */
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn-ios {
            background: var(--ios-primary);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            flex: 1;
            justify-content: center;
        }

        .btn-ios:hover {
            background: #0051d5;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(0,122,255,0.3);
        }

        .btn-ios.btn-secondary {
            background: var(--ios-gray-5);
            color: #1c1c1e;
        }

        .btn-ios.btn-secondary:hover {
            background: var(--ios-gray-4);
        }

        /* График активности */
        .activity-chart {
            height: 250px;
            background: var(--ios-gray-6);
            border-radius: var(--ios-radius-sm);
            padding: 1rem;
            margin-bottom: 1rem;
            position: relative;
        }

        .activity-chart canvas {
            max-height: 220px !important;
        }

        /* Статистика активности */
        .stat-value.text-success {
            color: var(--ios-success) !important;
        }

        .stat-value.text-primary {
            color: var(--ios-primary) !important;
        }

        .stat-value.text-warning {
            color: var(--ios-warning) !important;
        }

        /* Пустое состояние */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--ios-gray);
        }

        .empty-state-icon {
            font-size: 3rem;
            color: var(--ios-gray-3);
            margin-bottom: 1rem;
        }

        /* Адаптивность */
        @media (max-width: 768px) {
            .profile-header {
                padding: 2rem 0 3rem;
            }

            .profile-card {
                padding: 1.5rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .action-buttons {
                flex-direction: column;
            }
        }

        /* Скроллбар */
        .task-list::-webkit-scrollbar {
            width: 6px;
        }

        .task-list::-webkit-scrollbar-track {
            background: var(--ios-gray-6);
            border-radius: 3px;
        }

        .task-list::-webkit-scrollbar-thumb {
            background: var(--ios-gray-3);
            border-radius: 3px;
        }

        .task-list::-webkit-scrollbar-thumb:hover {
            background: var(--ios-gray-2);
        }

        /* Бейджи */
        .badge-ios {
            background: var(--ios-primary);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .badge-ios.badge-success {
            background: var(--ios-success);
        }

        .badge-ios.badge-warning {
            background: var(--ios-warning);
        }

        .badge-ios.badge-danger {
            background: var(--ios-danger);
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layouts/main.php'; ?>

    <!-- Заголовок профиля -->
    <div class="profile-header">
        <div class="container">
            <h1 class="text-white text-center mb-0">Мой профиль</h1>
        </div>
    </div>

    <div class="container">
        <!-- Основная информация -->
        <div class="profile-card">
            <?php 
            $initials = implode('', array_map(function($word) { 
                return mb_substr($word, 0, 1); 
            }, explode(' ', $user['name'])));
            ?>
            <div class="profile-avatar">
                <?= mb_strtoupper($initials) ?>
            </div>

            <h2 class="profile-name"><?= htmlspecialchars($user['name']) ?></h2>
            <div class="profile-info">
                <p class="mb-1"><?= htmlspecialchars($user['email']) ?></p>
                <?php if ($user['department_name']): ?>
                    <p class="mb-0">
                        <span class="badge-ios"><?= htmlspecialchars($user['department_name']) ?></span>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Статистика -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['created_tasks'] ?></div>
                    <div class="stat-label">Создано задач</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['assigned_tasks'] ?></div>
                    <div class="stat-label">Назначено</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['completed_tasks'] ?></div>
                    <div class="stat-label">Выполнено</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['watching_tasks'] ?></div>
                    <div class="stat-label">Наблюдаю</div>
                </div>
            </div>

            <!-- Кнопки действий -->
            <div class="action-buttons">
                <a href="/profile/settings" class="btn-ios">
                    <i class="bi bi-gear"></i>
                    Настройки
                </a>
                <a href="/tasks/create" class="btn-ios btn-secondary">
                    <i class="bi bi-plus-circle"></i>
                    Новая задача
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Информация о пользователе -->
            <div class="col-lg-6">
                <div class="profile-card">
                    <h3 class="section-title">
                        <i class="bi bi-person-circle"></i>
                        Информация
                    </h3>

                    <div class="info-section">
                        <?php if ($user['phone']): ?>
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="bi bi-telephone"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Телефон</div>
                                <div class="info-value"><?= htmlspecialchars($user['phone']) ?></div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="info-item">
                            <div class="info-icon">
                                <i class="bi bi-calendar3"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Дата регистрации</div>
                                <div class="info-value"><?= date('d.m.Y', strtotime($user['created_at'])) ?></div>
                            </div>
                        </div>

                        <?php if ($user['last_login']): ?>
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Последний вход</div>
                                <div class="info-value"><?= date('d.m.Y H:i', strtotime($user['last_login'])) ?></div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="info-item">
                            <div class="info-icon">
                                <i class="bi bi-bell"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Уведомления</div>
                                <div class="info-value">
                                    <?php if ($user['email_notifications']): ?>
                                        <span class="badge-ios badge-success me-1">Email</span>
                                    <?php endif; ?>
                                    <?php if ($user['telegram_notifications']): ?>
                                        <span class="badge-ios">Telegram</span>
                                    <?php endif; ?>
                                    <?php if (!$user['email_notifications'] && !$user['telegram_notifications']): ?>
                                        <span class="text-muted">Отключены</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($user['bio']): ?>
                    <div class="info-section">
                        <h4 class="section-title">
                            <i class="bi bi-card-text"></i>
                            О себе
                        </h4>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($user['bio'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Активные задачи -->
            <div class="col-lg-6">
                <div class="profile-card">
                    <h3 class="section-title">
                        <i class="bi bi-list-task"></i>
                        Активные задачи
                    </h3>

                    <?php if (empty($activeTasks)): ?>
                        <div class="empty-state">
                            <i class="bi bi-check2-circle empty-state-icon"></i>
                            <p>Нет активных задач</p>
                        </div>
                    <?php else: ?>
                        <div class="task-list">
                            <?php foreach ($activeTasks as $task): ?>
                                <a href="/tasks/view/<?= $task['id'] ?>" class="task-item">
                                    <div class="task-priority priority-<?= $task['priority'] ?>"></div>
                                    <div class="task-content">
                                        <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                                        <div class="task-meta">
                                            <?php if ($task['deadline']): ?>
                                                <i class="bi bi-calendar3 me-1"></i>
                                                <?= date('d.m.Y', strtotime($task['deadline'])) ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="task-status">
                                        <?php
                                        $statusLabels = [
                                            'todo' => 'К выполнению',
                                            'in_progress' => 'В работе',
                                            'review' => 'На проверке'
                                        ];
                                        echo $statusLabels[$task['status']] ?? $task['status'];
                                        ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- График активности -->
                <div class="profile-card">
                    <h3 class="section-title">
                        <i class="bi bi-graph-up"></i>
                        Активность за последние 7 дней
                    </h3>
                    <div class="activity-chart">
                        <canvas id="activityChart"></canvas>
                    </div>
                    
                    <?php if (isset($activityData)): ?>
                    <!-- Статистика активности -->
                    <div class="row mt-3">
                        <div class="col-4 text-center">
                            <div class="stat-value text-success"><?= array_sum($activityData['completed']) ?></div>
                            <div class="stat-label">Выполнено</div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="stat-value text-primary"><?= array_sum($activityData['created']) ?></div>
                            <div class="stat-label">Создано</div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="stat-value text-warning"><?= array_sum($activityData['comments']) ?></div>
                            <div class="stat-label">Комментариев</div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Последние задачи -->
        <div class="profile-card">
            <h3 class="section-title">
                <i class="bi bi-clock-history"></i>
                Последние задачи
            </h3>

            <?php if (empty($recentTasks)): ?>
                <div class="empty-state">
                    <i class="bi bi-inbox empty-state-icon"></i>
                    <p>Нет задач</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach (array_slice($recentTasks, 0, 6) as $task): ?>
                        <div class="col-md-6 col-lg-4">
                            <a href="/tasks/view/<?= $task['id'] ?>" class="task-item">
                                <div class="task-priority priority-<?= $task['priority'] ?>"></div>
                                <div class="task-content">
                                    <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                                    <div class="task-meta">
                                        <?php if ($task['assignee_names']): ?>
                                            <i class="bi bi-person me-1"></i>
                                            <?= htmlspecialchars($task['assignee_names']) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // График активности с реальными данными
        const ctx = document.getElementById('activityChart');
        if (ctx) {
            <?php if (isset($activityData)): ?>
                const activityData = <?= json_encode($activityData) ?>;
                
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: activityData.labels,
                        datasets: [
                            {
                                label: 'Выполнено задач',
                                data: activityData.completed,
                                borderColor: '#34c759',
                                backgroundColor: 'rgba(52, 199, 89, 0.1)',
                                tension: 0.4,
                                fill: true,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                pointBackgroundColor: '#34c759',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2
                            },
                            {
                                label: 'Создано задач',
                                data: activityData.created,
                                borderColor: '#007aff',
                                backgroundColor: 'rgba(0, 122, 255, 0.1)',
                                tension: 0.4,
                                fill: true,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                pointBackgroundColor: '#007aff',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2
                            },
                            {
                                label: 'Комментарии',
                                data: activityData.comments,
                                borderColor: '#ff9500',
                                backgroundColor: 'rgba(255, 149, 0, 0.1)',
                                tension: 0.4,
                                fill: true,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                pointBackgroundColor: '#ff9500',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom',
                                labels: {
                                    usePointStyle: true,
                                    padding: 15,
                                    font: {
                                        size: 12
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                padding: 12,
                                titleFont: {
                                    size: 14
                                },
                                bodyFont: {
                                    size: 13
                                },
                                cornerRadius: 8,
                                callbacks: {
                                    title: function(context) {
                                        const index = context[0].dataIndex;
                                        const date = activityData.dates[index];
                                        const dateObj = new Date(date);
                                        const options = { 
                                            day: 'numeric', 
                                            month: 'long',
                                            weekday: 'long'
                                        };
                                        return dateObj.toLocaleDateString('ru-RU', options);
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1,
                                    font: {
                                        size: 12
                                    }
                                },
                                grid: {
                                    color: '#f2f2f7',
                                    drawBorder: false
                                }
                            },
                            x: {
                                grid: {
                                    display: false,
                                    drawBorder: false
                                },
                                ticks: {
                                    font: {
                                        size: 12
                                    }
                                }
                            }
                        }
                    }
                });
            <?php else: ?>
                // Если нет данных, показываем пустой график
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'],
                        datasets: [{
                            label: 'Нет данных',
                            data: [0, 0, 0, 0, 0, 0, 0],
                            borderColor: '#8e8e93',
                            backgroundColor: 'rgba(142, 142, 147, 0.1)',
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
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 5,
                                grid: {
                                    color: '#f2f2f7'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            <?php endif; ?>
        }
    </script>
</body>
</html>