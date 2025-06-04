<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Уведомления - Система управления задачами</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .notifications-container {
            max-width: 900px;
            margin: 2rem auto;
        }
        .notifications-header {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        .notifications-header h2 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        .filter-tabs {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
        }
        .nav-pills .nav-link {
            color: #6c757d;
            border-radius: 20px;
            padding: 0.5rem 1.5rem;
            margin-right: 0.5rem;
            transition: all 0.3s;
        }
        .nav-pills .nav-link:hover {
            background: #f8f9fa;
            color: #495057;
        }
        .nav-pills .nav-link.active {
            background: #667eea;
            color: white;
        }
        .notification-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 1rem;
            transition: all 0.3s;
            overflow: hidden;
        }
        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.12);
        }
        .notification-card.unread {
            border-left: 4px solid #667eea;
        }
        .notification-card.unread .notification-body {
            background: #f8f9ff;
        }
        .notification-body {
            padding: 1.5rem;
        }
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 0.75rem;
        }
        .notification-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.25rem;
            font-size: 1.1rem;
        }
        .notification-time {
            font-size: 0.875rem;
            color: #6c757d;
            white-space: nowrap;
        }
        .notification-message {
            color: #495057;
            margin-bottom: 0.75rem;
            line-height: 1.6;
        }
        .notification-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.875rem;
        }
        .notification-type {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-weight: 500;
        }
        .type-task_created {
            background: #d1ecf1;
            color: #0c5460;
        }
        .type-task_assigned {
            background: #cfe2ff;
            color: #084298;
        }
        .type-status_changed {
            background: #f8d7da;
            color: #842029;
        }
        .type-comment_added, .type-new_comment {
            background: #d4edda;
            color: #155724;
        }
        .type-deadline_reminder {
            background: #fff3cd;
            color: #664d03;
        }
        .type-task_completed {
            background: #d1e7dd;
            color: #0f5132;
        }
        .type-mentioned {
            background: #e2e3e5;
            color: #41464b;
        }
        .type-task_updated {
            background: #e0cffc;
            color: #432874;
        }
        .type-test {
            background: #f8d7da;
            color: #842029;
        }
        .notification-actions {
            display: flex;
            gap: 0.5rem;
        }
        .btn-notification {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            border: none;
            background: none;
            color: #6c757d;
            transition: all 0.2s;
        }
        .btn-notification:hover {
            color: #495057;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .empty-state-icon {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }
        .empty-state h5 {
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        .date-divider {
            position: relative;
            text-align: center;
            margin: 2rem 0;
        }
        .date-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #dee2e6;
        }
        .date-divider span {
            background: #f8f9fa;
            padding: 0 1rem;
            position: relative;
            color: #6c757d;
            font-weight: 500;
            font-size: 0.875rem;
        }
        .bulk-actions {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
            display: none;
        }
        .bulk-actions.show {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .search-box {
            position: relative;
            max-width: 400px;
        }
        .search-box input {
            padding-left: 2.5rem;
            border: 2px solid #e9ecef;
            border-radius: 20px;
            transition: all 0.3s;
        }
        .search-box input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        @media (max-width: 768px) {
            .notification-header {
                flex-direction: column;
                gap: 0.5rem;
            }
            .notification-meta {
                flex-direction: column;
                align-items: start;
                gap: 0.5rem;
            }
            .filter-tabs {
                overflow-x: auto;
            }
            .nav-pills {
                flex-wrap: nowrap;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layouts/main.php'; ?>
    
    <div class="container notifications-container">
        <!-- Заголовок -->
        <div class="notifications-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2>Уведомления</h2>
                    <p class="text-muted mb-0">
                        Всего уведомлений: <?= count($notifications) ?>
                        <?php 
                        $unreadCount = count(array_filter($notifications, function($n) { return !$n['is_read']; }));
                        if ($unreadCount > 0): 
                        ?>
                            • <span class="text-primary"><?= $unreadCount ?> непрочитанных</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <div class="search-box d-inline-block">
                        <i class="bi bi-search"></i>
                        <input type="text" 
                               class="form-control" 
                               id="searchNotifications" 
                               placeholder="Поиск уведомлений...">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Фильтры -->
        <div class="filter-tabs">
            <div class="d-flex justify-content-between align-items-center">
                <ul class="nav nav-pills mb-0" id="notificationFilters">
                    <li class="nav-item">
                        <a class="nav-link active" href="#" data-filter="all">
                            Все
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-filter="unread">
                            <i class="bi bi-circle-fill text-primary me-1" style="font-size: 0.5rem;"></i>
                            Непрочитанные
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-filter="tasks">
                            <i class="bi bi-list-task me-1"></i>
                            Задачи
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-filter="comments">
                            <i class="bi bi-chat me-1"></i>
                            Комментарии
                        </a>
                    </li>
                </ul>
                
                <?php if ($unreadCount > 0): ?>
                <button class="btn btn-sm btn-outline-primary" id="markAllRead">
                    <i class="bi bi-check2-all me-1"></i>
                    Отметить все как прочитанные
                </button>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Массовые действия -->
        <div class="bulk-actions" id="bulkActions">
            <div>
                <span class="text-muted">Выбрано: <strong id="selectedCount">0</strong></span>
            </div>
            <div>
                <button class="btn btn-sm btn-outline-primary" id="bulkMarkRead">
                    <i class="bi bi-check2 me-1"></i>
                    Прочитано
                </button>
                <button class="btn btn-sm btn-outline-danger" id="bulkDelete">
                    <i class="bi bi-trash me-1"></i>
                    Удалить
                </button>
            </div>
        </div>
        
        <!-- Список уведомлений -->
        <div id="notificationsList">
            <?php if (empty($groupedNotifications)): ?>
                <div class="empty-state">
                    <i class="bi bi-bell-slash empty-state-icon"></i>
                    <h5>Нет уведомлений</h5>
                    <p class="text-muted">Здесь будут отображаться все ваши уведомления</p>
                </div>
            <?php else: ?>
                <?php foreach ($groupedNotifications as $date => $dateNotifications): ?>
                    <div class="date-divider">
                        <span>
                            <?php
                            if ($date === date('Y-m-d')) {
                                echo 'Сегодня';
                            } elseif ($date === date('Y-m-d', strtotime('-1 day'))) {
                                echo 'Вчера';
                            } else {
                                echo date('d F Y', strtotime($date));
                            }
                            ?>
                        </span>
                    </div>
                    
                    <?php foreach ($dateNotifications as $notification): ?>
                        <div class="notification-card <?= !$notification['is_read'] ? 'unread' : '' ?>" 
                             data-id="<?= $notification['id'] ?>"
                             data-type="<?= $notification['type'] ?>">
                            <div class="notification-body">
                                <div class="notification-header">
                                    <div class="flex-grow-1">
                                        <h5 class="notification-title">
                                            <?= htmlspecialchars($notification['title']) ?>
                                        </h5>
                                        <div class="notification-time">
                                            <i class="bi bi-clock me-1"></i>
                                            <?= date('H:i', strtotime($notification['created_at'])) ?>
                                        </div>
                                    </div>
                                    <div class="notification-actions">
                                        <?php if (!$notification['is_read']): ?>
                                        <button class="btn-notification" 
                                                onclick="markAsRead(<?= $notification['id'] ?>)"
                                                title="Отметить как прочитанное">
                                            <i class="bi bi-check2"></i>
                                        </button>
                                        <?php endif; ?>
                                        <button class="btn-notification" 
                                                onclick="deleteNotification(<?= $notification['id'] ?>)"
                                                title="Удалить">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <p class="notification-message">
                                    <?= htmlspecialchars($notification['message']) ?>
                                </p>
                                
                                <div class="notification-meta">
                                    <?php
                                    $typeLabels = [
                                        'task_created' => 'Новая задача',
                                        'task_assigned' => 'Назначение',
                                        'status_changed' => 'Изменение статуса',
                                        'comment_added' => 'Комментарий',
                                        'new_comment' => 'Новый комментарий',
                                        'deadline_reminder' => 'Напоминание',
                                        'task_completed' => 'Завершено',
                                        'mentioned' => 'Упоминание',
                                        'task_updated' => 'Обновление',
                                        'test' => 'Тест'
                                    ];
                                    
                                    $typeIcons = [
                                        'task_created' => 'bi-plus-circle',
                                        'task_assigned' => 'bi-person-plus',
                                        'status_changed' => 'bi-arrow-repeat',
                                        'comment_added' => 'bi-chat',
                                        'new_comment' => 'bi-chat-dots',
                                        'deadline_reminder' => 'bi-clock',
                                        'task_completed' => 'bi-check-circle',
                                        'mentioned' => 'bi-at',
                                        'task_updated' => 'bi-pencil',
                                        'test' => 'bi-gear'
                                    ];
                                    ?>
                                    <span class="notification-type type-<?= $notification['type'] ?>">
                                        <i class="bi <?= $typeIcons[$notification['type']] ?? 'bi-bell' ?>"></i>
                                        <?= $typeLabels[$notification['type']] ?? $notification['type'] ?>
                                    </span>
                                    
                                    <?php if ($notification['task_id']): ?>
                                    <a href="/tasks/view/<?= $notification['task_id'] ?>" class="text-decoration-none">
                                        <i class="bi bi-box-arrow-up-right me-1"></i>
                                        Перейти к задаче
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Фильтрация уведомлений
        document.querySelectorAll('#notificationFilters .nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Активная вкладка
                document.querySelectorAll('#notificationFilters .nav-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                const cards = document.querySelectorAll('.notification-card');
                
                cards.forEach(card => {
                    let show = true;
                    
                    if (filter === 'unread') {
                        show = card.classList.contains('unread');
                    } else if (filter === 'tasks') {
                        const type = card.dataset.type;
                        show = ['task_created', 'task_assigned', 'status_changed', 'task_completed', 'task_updated'].includes(type);
                    } else if (filter === 'comments') {
                        const type = card.dataset.type;
                        show = ['comment_added', 'new_comment', 'mentioned'].includes(type);
                    }
                    
                    card.style.display = show ? 'block' : 'none';
                });
                
                // Скрываем разделители дат если нет видимых уведомлений
                document.querySelectorAll('.date-divider').forEach(divider => {
                    const nextElement = divider.nextElementSibling;
                    let hasVisibleNotifications = false;
                    let element = nextElement;
                    
                    while (element && !element.classList.contains('date-divider')) {
                        if (element.classList.contains('notification-card') && element.style.display !== 'none') {
                            hasVisibleNotifications = true;
                            break;
                        }
                        element = element.nextElementSibling;
                    }
                    
                    divider.style.display = hasVisibleNotifications ? 'block' : 'none';
                });
            });
        });
        
        // Поиск
        document.getElementById('searchNotifications').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const cards = document.querySelectorAll('.notification-card');
            
            cards.forEach(card => {
                const title = card.querySelector('.notification-title').textContent.toLowerCase();
                const message = card.querySelector('.notification-message').textContent.toLowerCase();
                const show = title.includes(searchTerm) || message.includes(searchTerm);
                card.style.display = show ? 'block' : 'none';
            });
        });
        
        // Отметить как прочитанное
        function markAsRead(notificationId) {
            fetch(`/notifications/mark-read/${notificationId}`, {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const card = document.querySelector(`[data-id="${notificationId}"]`);
                    card.classList.remove('unread');
                    card.querySelector('.btn-notification[onclick^="markAsRead"]')?.remove();
                    
                    // Обновляем счетчик в навигации
                    updateNotificationBadge();
                }
            });
        }
        
        // Удалить уведомление
        function deleteNotification(notificationId) {
            if (!confirm('Удалить это уведомление?')) return;
            
            fetch(`/notifications/delete/${notificationId}`, {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const card = document.querySelector(`[data-id="${notificationId}"]`);
                    card.style.transition = 'all 0.3s';
                    card.style.opacity = '0';
                    card.style.transform = 'translateX(20px)';
                    setTimeout(() => card.remove(), 300);
                }
            });
        }
        
        // Отметить все как прочитанные
        document.getElementById('markAllRead')?.addEventListener('click', function() {
            fetch('/notifications/mark-all-read', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelectorAll('.notification-card.unread').forEach(card => {
                        card.classList.remove('unread');
                        card.querySelector('.btn-notification[onclick^="markAsRead"]')?.remove();
                    });
                    this.remove();
                    updateNotificationBadge();
                }
            });
        });
        
        // Форматирование даты для русской локали
        const months = [
            'января', 'февраля', 'марта', 'апреля', 'мая', 'июня',
            'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'
        ];
        
        document.querySelectorAll('.date-divider span').forEach(span => {
            const text = span.textContent.trim();
            if (text.includes('F')) {
                const date = new Date(text.replace(/(\d+) F (\d+)/, '$2-$1-01'));
                const day = date.getDate();
                const month = months[date.getMonth()];
                const year = date.getFullYear();
                span.textContent = `${day} ${month} ${year}`;
            }
        });
    </script>
</body>
</html>