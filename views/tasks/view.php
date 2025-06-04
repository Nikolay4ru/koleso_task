<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($task['title']) ?> - Система управления задачами</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .task-header {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        .task-title {
            font-size: 2rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        .task-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            color: #6c757d;
            font-size: 0.95rem;
        }
        .task-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.875rem;
        }
        .status-backlog { background: #e9ecef; color: #495057; }
        .status-todo { background: #cfe2ff; color: #084298; }
        .status-in_progress { background: #f8d7da; color: #842029; }
        .status-review { background: #fff3cd; color: #664d03; }
        .status-done { background: #d1e7dd; color: #0f5132; }
        
        .priority-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.875rem;
        }
        .priority-low { background: #d4edda; color: #155724; }
        .priority-medium { background: #d1ecf1; color: #0c5460; }
        .priority-high { background: #fff3cd; color: #856404; }
        .priority-urgent { background: #f8d7da; color: #721c24; }
        
        .content-card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .sidebar-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
        }
        
        .user-item {
            display: flex;
            align-items: center;
            padding: 0.5rem 0;
        }
        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 0.75rem;
            font-size: 0.875rem;
        }
        
        .comment-item {
            border-bottom: 1px solid #e9ecef;
            padding: 1.5rem 0;
        }
        .comment-item:last-child {
            border-bottom: none;
        }
        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        .comment-author {
            font-weight: 600;
            color: #2c3e50;
        }
        .comment-time {
            font-size: 0.875rem;
            color: #6c757d;
        }
        .comment-text {
            color: #495057;
            line-height: 1.6;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .deadline-alert {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .deadline-alert.overdue {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        
        .activity-log {
            font-size: 0.875rem;
        }
        .activity-item {
            padding: 0.5rem 0;
            border-left: 2px solid #e9ecef;
            padding-left: 1rem;
            margin-left: 0.5rem;
            position: relative;
        }
        .activity-item::before {
            content: '';
            position: absolute;
            left: -5px;
            top: 0.75rem;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #667eea;
        }
        
        #commentForm textarea {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 0.75rem;
            resize: vertical;
            min-height: 100px;
        }
        #commentForm textarea:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layouts/main.php'; ?>
    
    <div class="container mt-4">
        <!-- Хлебные крошки -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/dashboard">Дашборд</a></li>
                <li class="breadcrumb-item"><a href="/tasks/kanban">Задачи</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($task['title']) ?></li>
            </ol>
        </nav>
        
        <!-- Заголовок задачи -->
        <div class="task-header">
            <div class="row">
                <div class="col-lg-8">
                    <h1 class="task-title"><?= htmlspecialchars($task['title']) ?></h1>
                    <div class="task-meta">
                        <div class="task-meta-item">
                            <i class="bi bi-person-circle"></i>
                            <span>Создал: <strong><?= htmlspecialchars($task['creator_name']) ?></strong></span>
                        </div>
                        <div class="task-meta-item">
                            <i class="bi bi-calendar3"></i>
                            <span><?= date('d.m.Y H:i', strtotime($task['created_at'])) ?></span>
                        </div>
                        <?php if ($task['updated_at'] != $task['created_at']): ?>
                        <div class="task-meta-item">
                            <i class="bi bi-pencil"></i>
                            <span>Изменено: <?= date('d.m.Y H:i', strtotime($task['updated_at'])) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                    <div class="action-buttons justify-content-lg-end">
                        <?php if ($canEdit): ?>
                        <a href="/tasks/edit/<?= $task['id'] ?>" class="btn btn-primary">
                            <i class="bi bi-pencil me-2"></i>Редактировать
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($isCreator): ?>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="bi bi-trash me-2"></i>Удалить
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Основной контент -->
            <div class="col-lg-8">
                <!-- Описание -->
                <div class="content-card">
                    <h5 class="mb-3">Описание</h5>
                    <?php if (!empty($task['description'])): ?>
                        <div class="task-description">
                            <?= nl2br(htmlspecialchars($task['description'])) ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Описание не указано</p>
                    <?php endif; ?>
                </div>
                
                <!-- Комментарии -->
                <div class="content-card" id="comments">
                    <h5 class="mb-4">
                        Комментарии 
                        <?php if (!empty($comments)): ?>
                            <span class="badge bg-secondary"><?= count($comments) ?></span>
                        <?php endif; ?>
                    </h5>
                    
                    <!-- Форма добавления комментария -->
                    <form id="commentForm" action="/tasks/<?= $task['id'] ?>/comment" method="POST" class="mb-4">
                        <div class="mb-3">
                            <textarea class="form-control" 
                                      name="comment" 
                                      placeholder="Напишите комментарий..." 
                                      required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-2"></i>Отправить
                        </button>
                    </form>
                    
                    <!-- Список комментариев -->
                    <div class="comments-list">
                        <?php if (empty($comments)): ?>
                            <p class="text-muted text-center py-4">Пока нет комментариев</p>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                                <div class="comment-item">
                                    <div class="comment-header">
                                        <div class="d-flex align-items-center">
                                            <?php 
                                            $initials = implode('', array_map(function($word) { 
                                                return mb_substr($word, 0, 1); 
                                            }, explode(' ', $comment['user_name'])));
                                            ?>
                                            <div class="user-avatar"><?= mb_strtoupper($initials) ?></div>
                                            <span class="comment-author"><?= htmlspecialchars($comment['user_name']) ?></span>
                                        </div>
                                        <span class="comment-time">
                                            <?= date('d.m.Y H:i', strtotime($comment['created_at'])) ?>
                                        </span>
                                    </div>
                                    <div class="comment-text">
                                        <?= nl2br(htmlspecialchars($comment['comment'])) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Боковая панель -->
            <div class="col-lg-4">
                <!-- Статус и приоритет -->
                <div class="sidebar-card">
                    <h6 class="mb-3">Статус и приоритет</h6>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block mb-2">Статус</small>
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
                            <?= $statusLabels[$task['status']] ?? $task['status'] ?>
                        </span>
                    </div>
                    
                    <div>
                        <small class="text-muted d-block mb-2">Приоритет</small>
                        <?php
                        $priorityLabels = [
                            'low' => 'Низкий',
                            'medium' => 'Средний',
                            'high' => 'Высокий',
                            'urgent' => 'Срочный'
                        ];
                        ?>
                        <span class="priority-badge priority-<?= $task['priority'] ?>">
                            <?= $priorityLabels[$task['priority']] ?? $task['priority'] ?>
                        </span>
                    </div>
                </div>
                
                <!-- Дедлайн -->
                <?php if ($task['deadline']): ?>
                <div class="sidebar-card">
                    <h6 class="mb-3">
                        <i class="bi bi-calendar-event me-2"></i>Дедлайн
                    </h6>
                    <?php
                    $deadline = strtotime($task['deadline']);
                    $now = time();
                    $isOverdue = $deadline < $now && $task['status'] != 'done';
                    $daysLeft = ceil(($deadline - $now) / 86400);
                    ?>
                    
                    <?php if ($isOverdue): ?>
                    <div class="deadline-alert overdue">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Просрочено на <?= abs($daysLeft) ?> дн.
                    </div>
                    <?php elseif ($daysLeft <= 3 && $task['status'] != 'done'): ?>
                    <div class="deadline-alert">
                        <i class="bi bi-clock me-2"></i>
                        Осталось <?= $daysLeft ?> дн.
                    </div>
                    <?php endif; ?>
                    
                    <p class="mb-0">
                        <strong><?= date('d.m.Y', $deadline) ?></strong><br>
                        <small class="text-muted"><?= date('H:i', $deadline) ?></small>
                    </p>
                </div>
                <?php endif; ?>
                
                <!-- Исполнители -->
                <div class="sidebar-card">
                    <h6 class="mb-3">
                        <i class="bi bi-people-fill me-2"></i>Исполнители
                    </h6>
                    <?php if (empty($task['assignees'])): ?>
                        <p class="text-muted mb-0">Не назначены</p>
                    <?php else: ?>
                        <?php foreach ($task['assignees'] as $assignee): ?>
                            <div class="user-item">
                                <?php 
                                $initials = implode('', array_map(function($word) { 
                                    return mb_substr($word, 0, 1); 
                                }, explode(' ', $assignee['name'])));
                                ?>
                                <div class="user-avatar"><?= mb_strtoupper($initials) ?></div>
                                <div>
                                    <div><?= htmlspecialchars($assignee['name']) ?></div>
                                    <?php if ($assignee['department_name']): ?>
                                        <small class="text-muted"><?= htmlspecialchars($assignee['department_name']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Наблюдатели -->
                <div class="sidebar-card">
                    <h6 class="mb-3">
                        <i class="bi bi-eye-fill me-2"></i>Наблюдатели
                    </h6>
                    <?php if (empty($task['watchers'])): ?>
                        <p class="text-muted mb-0">Не назначены</p>
                    <?php else: ?>
                        <?php foreach ($task['watchers'] as $watcher): ?>
                            <div class="user-item">
                                <?php 
                                $initials = implode('', array_map(function($word) { 
                                    return mb_substr($word, 0, 1); 
                                }, explode(' ', $watcher['name'])));
                                ?>
                                <div class="user-avatar" style="background: #6c757d;">
                                    <?= mb_strtoupper($initials) ?>
                                </div>
                                <div>
                                    <div><?= htmlspecialchars($watcher['name']) ?></div>
                                    <?php if ($watcher['department_name']): ?>
                                        <small class="text-muted"><?= htmlspecialchars($watcher['department_name']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Модальное окно удаления -->
    <?php if ($isCreator): ?>
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Подтверждение удаления</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Вы уверены, что хотите удалить эту задачу?</p>
                    <p class="text-danger mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Это действие нельзя отменить!
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">
                        <i class="bi bi-trash me-2"></i>Удалить
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Удаление задачи
        <?php if ($isCreator): ?>
        document.getElementById('confirmDelete').addEventListener('click', function() {
            fetch('/tasks/delete/<?= $task['id'] ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '/tasks/kanban';
                } else {
                    alert('Ошибка при удалении задачи: ' + (data.error || 'Неизвестная ошибка'));
                }
            })
            .catch(error => {
                alert('Произошла ошибка при удалении задачи');
                console.error('Error:', error);
            });
        });
        <?php endif; ?>
        
        // Автоматическая высота textarea при вводе
        const textarea = document.querySelector('#commentForm textarea');
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
        
        // Прокрутка к комментариям если есть хеш
        if (window.location.hash === '#comments') {
            document.getElementById('comments').scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html>