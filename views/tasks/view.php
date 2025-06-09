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
        
        /* Стили для файлов */
        .files-section {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e9ecef;
        }
        
        .files-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .file-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }
        
        .file-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            background: white;
        }
        
        .file-icon {
            font-size: 2.5rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        
        .file-thumbnail {
            width: 100%;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 0.5rem;
        }
        
        .file-name {
            font-size: 0.875rem;
            color: #495057;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            margin-bottom: 0.25rem;
            font-weight: 500;
        }
        
        .file-info {
            font-size: 0.75rem;
            color: #6c757d;
        }
        
        .comment-files {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }
        
        .comment-file {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            color: #495057;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }
        
        .comment-file:hover {
            background: white;
            border-color: #667eea;
            color: #667eea;
            text-decoration: none;
        }
        
        /* Компонент загрузки файлов для комментариев */
        .file-uploader {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s;
            cursor: pointer;
            margin-bottom: 1rem;
        }
        
        .file-uploader:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }
        
        .file-uploader.drag-over {
            border-color: #667eea;
            background: #e8f0ff;
            transform: scale(1.02);
        }
        
        .file-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .file-preview-item {
            background: #e9ecef;
            border-radius: 6px;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            position: relative;
        }
        
        .file-preview-item.uploading {
            opacity: 0.6;
        }
        
        .file-preview-item .file-name {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .file-preview-item .remove-file {
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            padding: 0;
            margin-left: 0.5rem;
            display: flex;
            align-items: center;
        }
        
        .file-preview-item .remove-file:hover {
            color: #dc3545;
        }
        
        /* Индикатор загрузки */
        .upload-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: #667eea;
            transform-origin: left;
            transform: scaleX(0);
            transition: transform 0.3s;
        }
        /* Быстрые кнопки смены статуса */
        .status-actions {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .status-action-btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            border: 2px solid transparent;
            background: white;
            color: #495057;
            font-weight: 500;
            transition: all 0.3s;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .status-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .status-action-btn.btn-start {
            border-color: #0d6efd;
            color: #0d6efd;
        }
        .status-action-btn.btn-start:hover {
            background: #0d6efd;
            color: white;
        }
        
        .status-action-btn.btn-complete {
            border-color: #6f42c1;
            color: #6f42c1;
        }
        .status-action-btn.btn-complete:hover {
            background: #6f42c1;
            color: white;
        }
        
        .status-action-btn.btn-approve {
            border-color: #198754;
            color: #198754;
        }
        .status-action-btn.btn-approve:hover {
            background: #198754;
            color: white;
        }
        
        .status-action-btn.btn-reject {
            border-color: #dc3545;
            color: #dc3545;
        }
        .status-action-btn.btn-reject:hover {
            background: #dc3545;
            color: white;
        }
        
        .status-action-btn.btn-review {
            border-color: #fd7e14;
            color: #fd7e14;
        }
        .status-action-btn.btn-review:hover {
            background: #fd7e14;
            color: white;
        }
        
        .status-action-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Уведомление о смене статуса */
        .status-change-notification {
            position: fixed;
            top: 80px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateX(400px);
            opacity: 0;
            transition: all 0.3s;
            z-index: 1050;
        }
        
        .status-change-notification.show {
            transform: translateX(0);
            opacity: 1;
        }
        
        .status-change-notification.error {
            background: #dc3545;
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
                <!-- Быстрые действия со статусом -->
                <?php if ($canEdit || $isCreator): ?>
                <div class="status-actions">
                    <h6 class="mb-3">
                        <i class="bi bi-lightning me-2"></i>
                        Быстрые действия
                    </h6>
                    
                    <div class="status-buttons">
                        <?php
                        $currentStatus = $task['status'];
                        $userId = $_SESSION['user_id'];
                        
                        // Определяем доступные действия в зависимости от статуса и роли
                        if ($isAssignee || $canEdit): ?>
                        
                            <?php if (in_array($currentStatus, ['backlog', 'todo'])): ?>
                                <button class="status-action-btn btn-start" onclick="changeStatus('in_progress')">
                                    <i class="bi bi-play-fill"></i>
                                    Начать работу
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($currentStatus === 'in_progress'): ?>
                                <button class="status-action-btn btn-complete" onclick="changeStatus('waiting_approval')">
                                    <i class="bi bi-check-circle"></i>
                                    Выполнено (на проверку)
                                </button>
                                <button class="status-action-btn btn-review" onclick="changeStatus('review')">
                                    <i class="bi bi-eye"></i>
                                    На внутреннюю проверку
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($currentStatus === 'review'): ?>
                                <button class="status-action-btn btn-complete" onclick="changeStatus('waiting_approval')">
                                    <i class="bi bi-check-circle"></i>
                                    Выполнено (на проверку)
                                </button>
                            <?php endif; ?>
                            
                        <?php endif; ?>
                        
                        <?php if ($isCreator): ?>
                        
                            <?php if ($currentStatus === 'waiting_approval'): ?>
                                <button class="status-action-btn btn-approve" onclick="changeStatus('done')">
                                    <i class="bi bi-check-all"></i>
                                    Принять и закрыть
                                </button>
                                <button class="status-action-btn btn-reject" onclick="changeStatus('in_progress', 'Требуется доработка')">
                                    <i class="bi bi-arrow-left-circle"></i>
                                    Вернуть на доработку
                                </button>
                            <?php endif; ?>
                            
                            <?php if (in_array($currentStatus, ['backlog', 'todo', 'in_progress', 'review'])): ?>
                                <button class="status-action-btn btn-approve" onclick="changeStatus('done')">
                                    <i class="bi bi-check-all"></i>
                                    Закрыть как выполненную
                                </button>
                            <?php endif; ?>
                            
                        <?php endif; ?>
                        
                        <?php if ($currentStatus === 'done' && ($isCreator || $isAssignee)): ?>
                            <button class="status-action-btn btn-reject" onclick="changeStatus('in_progress')">
                                <i class="bi bi-arrow-counterclockwise"></i>
                                Переоткрыть задачу
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
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
                    
                    <!-- Файлы задачи -->
                    <?php if (!empty($taskFiles)): ?>
                    <div class="files-section">
                        <h6 class="mb-3">
                            <i class="bi bi-paperclip me-1"></i>
                            Прикрепленные файлы (<?= count($taskFiles) ?>)
                        </h6>
                        <div class="files-grid">
                            <?php 
                            $fileModel = new \App\Models\File($this->db);
                            foreach ($taskFiles as $file): 
                            ?>
                                <div class="file-card" onclick="viewFile(<?= htmlspecialchars(json_encode([
                                    'id' => $file['id'],
                                    'name' => $file['original_name'],
                                    'is_image' => $file['is_image'],
                                    'mime_type' => $file['mime_type'],
                                    'preview_url' => '/file/preview/' . $file['id'],
                                    'download_url' => '/file/download/' . $file['id']
                                ])) ?>)">
                                    <?php if ($file['is_image'] && $file['thumbnail_path']): ?>
                                        <img src="<?= $fileModel->getThumbnailUrl($file) ?>" class="file-thumbnail" alt="<?= htmlspecialchars($file['original_name']) ?>">
                                    <?php else: ?>
                                        <i class="file-icon bi <?= $fileModel->getFileIcon($file['mime_type']) ?>"></i>
                                    <?php endif; ?>
                                    <div class="file-name" title="<?= htmlspecialchars($file['original_name']) ?>">
                                        <?= htmlspecialchars($file['original_name']) ?>
                                    </div>
                                    <div class="file-info">
                                        <?= $fileModel->formatFileSize($file['size']) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
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
                    <form id="commentForm" action="/tasks/<?= $task['id'] ?>/comment" method="POST">
                        <div class="mb-3">
                            <textarea class="form-control" 
                                      name="comment" 
                                      placeholder="Напишите комментарий..." 
                                      required></textarea>
                        </div>
                        
                        <!-- Загрузка файлов для комментария -->
                        <div class="file-uploader" id="commentFileUploader">
                            <input type="file" class="d-none" id="commentFileInput" multiple accept="*/*">
                            <i class="bi bi-paperclip" style="font-size: 1.5rem; color: #6c757d;"></i>
                            <p class="mb-0 mt-1 small">Прикрепить файлы (макс. 10 MB)</p>
                        </div>
                        
                        <div class="file-preview" id="commentFilePreview"></div>
                        <input type="hidden" name="uploaded_files" id="commentUploadedFiles" value="[]">
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-2"></i>Отправить
                        </button>
                    </form>
                    
                    <!-- Список комментариев -->
                    <div class="comments-list mt-4">
                        <?php if (empty($comments)): ?>
                            <p class="text-center text-muted py-4">Пока нет комментариев</p>
                        <?php else: ?>
                            <?php 
                            $fileModel = new \App\Models\File($this->db);
                            foreach ($comments as $comment): 
                            ?>
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
                                    <?php var_dump($comment['id']) ?>
                                    <?php if (!empty($comment['files'])): ?>
                                    <div class="comment-files">
                                        <?php foreach ($comment['files'] as $file): ?>
                                            <a href="#" onclick="viewFile(<?= htmlspecialchars(json_encode([
                                                'id' => $file['id'],
                                                'name' => $file['original_name'],
                                                'is_image' => $file['is_image'],
                                                'mime_type' => $file['mime_type'],
                                                'preview_url' => '/file/preview/' . $file['id'],
                                                'download_url' => '/file/download/' . $file['id']
                                            ])) ?>); return false;" class="comment-file">
                                                <i class="bi <?= $fileModel->getFileIcon($file['mime_type']) ?>"></i>
                                                <?= htmlspecialchars($file['original_name']) ?>
                                                <small>(<?= $fileModel->formatFileSize($file['size']) ?>)</small>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
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
    
    <!-- Модальное окно просмотра файлов -->
    <div class="modal fade" id="fileViewerModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="fileViewerTitle">Просмотр файла</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="fileViewerBody" style="max-height: 80vh; overflow: auto;">
                    <!-- Содержимое будет загружено динамически -->
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-primary" id="fileDownloadBtn">
                        <i class="bi bi-download me-2"></i>Скачать
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                </div>
            </div>
        </div>
    </div>

     <!-- Уведомление о смене статуса -->
    <div class="status-change-notification" id="statusNotification">
        <i class="bi bi-check-circle me-2"></i>
        <span id="statusNotificationText">Статус задачи изменен</span>
    </div>
    
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
        
        // Загрузка файлов для комментариев
        let commentUploadedFiles = [];
        const commentFileUploader = document.getElementById('commentFileUploader');
        const commentFileInput = document.getElementById('commentFileInput');
        const commentFilePreview = document.getElementById('commentFilePreview');
        const commentUploadedFilesInput = document.getElementById('commentUploadedFiles');
        
        commentFileUploader.addEventListener('click', () => commentFileInput.click());
        
        commentFileUploader.addEventListener('dragover', (e) => {
            e.preventDefault();
            commentFileUploader.classList.add('drag-over');
        });
        
        commentFileUploader.addEventListener('dragleave', () => {
            commentFileUploader.classList.remove('drag-over');
        });
        
        commentFileUploader.addEventListener('drop', (e) => {
            e.preventDefault();
            commentFileUploader.classList.remove('drag-over');
            handleCommentFiles(e.dataTransfer.files);
        });
        
        commentFileInput.addEventListener('change', (e) => {
            handleCommentFiles(e.target.files);
        });
        
        function handleCommentFiles(files) {
            Array.from(files).forEach(file => {
                if (file.size > 10 * 1024 * 1024) {
                    alert(`Файл ${file.name} слишком большой. Максимальный размер: 10 MB`);
                    return;
                }
                uploadCommentFile(file);
            });
        }
        
        function uploadCommentFile(file) {
            const tempId = 'temp_' + Date.now() + '_' + Math.random();
            const previewItem = createFilePreviewItem(file.name, tempId);
            commentFilePreview.appendChild(previewItem);
            
            const formData = new FormData();
            formData.append('files[]', file);
            formData.append('type', 'comment');
            
            fetch('/file/upload', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.files.length > 0) {
                    const uploadedFile = data.files[0];
                    commentUploadedFiles.push(uploadedFile.id);
                    commentUploadedFilesInput.value = JSON.stringify(commentUploadedFiles);
                    
                    // Обновляем элемент предпросмотра
                    previewItem.classList.remove('uploading');
                    previewItem.dataset.fileId = uploadedFile.id;
                    const removeBtn = previewItem.querySelector('.remove-file');
                    removeBtn.onclick = () => removeCommentFile(uploadedFile.id, previewItem);
                } else {
                    previewItem.remove();
                    alert(data.errors ? data.errors.join('\n') : 'Ошибка при загрузке файла');
                }
            })
            .catch(error => {
                previewItem.remove();
                alert('Ошибка при загрузке файла');
                console.error('Upload error:', error);
            });
        }
        
        function createFilePreviewItem(fileName, tempId) {
            const div = document.createElement('div');
            div.className = 'file-preview-item uploading';
            div.id = `file-preview-${tempId}`;
            div.innerHTML = `
                <i class="bi bi-file-earmark"></i>
                <span class="file-name">${fileName}</span>
                <button type="button" class="remove-file">
                    <i class="bi bi-x"></i>
                </button>
                <div class="upload-progress"></div>
            `;
            return div;
        }
        
        function removeCommentFile(fileId, element) {
            commentUploadedFiles = commentUploadedFiles.filter(id => id !== fileId);
            commentUploadedFilesInput.value = JSON.stringify(commentUploadedFiles);
            element.remove();
            
            // Отправляем запрос на удаление файла с сервера
            fetch(`/file/delete/${fileId}`, {
                method: 'POST'
            }).catch(error => console.error('Delete error:', error));
        }
        
        // Просмотр файлов
        function viewFile(file) {
            const modal = new bootstrap.Modal(document.getElementById('fileViewerModal'));
            const title = document.getElementById('fileViewerTitle');
            const body = document.getElementById('fileViewerBody');
            const downloadBtn = document.getElementById('fileDownloadBtn');
            
            title.textContent = file.name;
            downloadBtn.href = file.download_url;
            
            if (file.is_image) {
                body.innerHTML = `<img src="${file.preview_url}" class="img-fluid" alt="${file.name}">`;
            } else if (file.mime_type === 'application/pdf') {
                body.innerHTML = `<iframe src="${file.preview_url}" style="width: 100%; height: 70vh; border: none;"></iframe>`;
            } else {
                body.innerHTML = `
                    <div class="text-center py-5">
                        <i class="bi ${getFileIcon(file.mime_type)}" style="font-size: 5rem; color: #6c757d;"></i>
                        <h4 class="mt-3">${file.name}</h4>
                        <p class="text-muted">Предпросмотр недоступен для этого типа файла</p>
                    </div>
                `;
            }
            
            modal.show();
        }
        
        function getFileIcon(mimeType) {
            if (mimeType.startsWith('image/')) return 'bi-file-earmark-image';
            if (mimeType === 'application/pdf') return 'bi-file-earmark-pdf';
            if (mimeType.includes('word')) return 'bi-file-earmark-word';
            if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) return 'bi-file-earmark-excel';
            if (mimeType.includes('powerpoint') || mimeType.includes('presentation')) return 'bi-file-earmark-ppt';
            if (mimeType.startsWith('text/')) return 'bi-file-earmark-text';
            if (mimeType.startsWith('video/')) return 'bi-file-earmark-play';
            if (mimeType.startsWith('audio/')) return 'bi-file-earmark-music';
            if (mimeType.includes('zip') || mimeType.includes('rar') || mimeType.includes('7z')) return 'bi-file-earmark-zip';
            return 'bi-file-earmark';
        }
    </script>



<script>
        
        
        // Переменные для работы с модальным окном отклонения
        let rejectModal = null;
        let pendingRejectComment = '';
        
        document.addEventListener('DOMContentLoaded', function() {
            rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
            
            // Обработчик подтверждения отклонения
            document.getElementById('confirmReject').addEventListener('click', function() {
                const comment = document.getElementById('rejectComment').value;
                rejectModal.hide();
                
                // Выполняем отклонение с комментарием
                performStatusChange('in_progress', comment);
            });
        });
        
        // Функция смены статуса
        function changeStatus(newStatus, comment = '') {
            // Если это отклонение и нет комментария, показываем модальное окно
            if (newStatus === 'in_progress' && comment === 'Требуется доработка') {
                rejectModal.show();
                return;
            }
            
            performStatusChange(newStatus, comment);
        }
        
        function performStatusChange(newStatus, comment = '') {
            // Отключаем все кнопки статуса
            const statusButtons = document.querySelectorAll('.status-action-btn');
            statusButtons.forEach(btn => {
                btn.disabled = true;
                const originalText = btn.innerHTML;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Обновление...';
                btn.dataset.originalText = originalText;
            });
            
            const formData = new FormData();
            formData.append('task_id', <?= $task['id'] ?>);
            formData.append('old_status', '<?= $task['status'] ?>');
            formData.append('new_status', newStatus);
            if (comment) {
                formData.append('comment', comment);
            }
            
            fetch('/tasks/update-status', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Обновляем статус на странице
                    updateStatusDisplay(newStatus);
                    
                    // Если есть комментарий, добавляем его
                    if (comment) {
                        addStatusChangeComment(comment);
                    }
                    
                    showStatusNotification('Статус задачи успешно изменен', 'success');
                    
                    // Перезагружаем страницу через 2 секунды для обновления кнопок
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showStatusNotification('Ошибка при изменении статуса', 'error');
                    restoreStatusButtons();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showStatusNotification('Произошла ошибка при изменении статуса', 'error');
                restoreStatusButtons();
            });
        }
        
        function updateStatusDisplay(newStatus) {
            const statusLabels = {
                'backlog': 'Бэклог',
                'todo': 'К выполнению', 
                'in_progress': 'В работе',
                'review': 'На проверке',
                'waiting_approval': 'Ожидает проверки',
                'done': 'Выполнено'
            };
            
            const statusElement = document.getElementById('currentStatus');
            statusElement.className = `status-badge status-${newStatus}`;
            statusElement.textContent = statusLabels[newStatus] || newStatus;
        }
        
        function addStatusChangeComment(comment) {
            // Добавляем системный комментарий об изменении статуса
            const commentsContainer = document.querySelector('.comments-list');
            const newComment = document.createElement('div');
            newComment.className = 'comment-item';
            newComment.innerHTML = `
                <div class="comment-header">
                    <div class="d-flex align-items-center">
                        <div class="user-avatar" style="background: #6c757d;">
                            <i class="bi bi-gear"></i>
                        </div>
                        <span class="comment-author">Система</span>
                    </div>
                    <span class="comment-time">
                        ${new Date().toLocaleDateString('ru-RU')} ${new Date().toLocaleTimeString('ru-RU', {hour: '2-digit', minute: '2-digit'})}
                    </span>
                </div>
                <div class="comment-text">
                    <strong>Статус изменен</strong><br>
                    ${comment}
                </div>
            `;
            
            if (commentsContainer.querySelector('.text-muted')) {
                commentsContainer.innerHTML = '';
            }
            commentsContainer.insertBefore(newComment, commentsContainer.firstChild);
        }
        
        function restoreStatusButtons() {
            const statusButtons = document.querySelectorAll('.status-action-btn');
            statusButtons.forEach(btn => {
                btn.disabled = false;
                if (btn.dataset.originalText) {
                    btn.innerHTML = btn.dataset.originalText;
                    delete btn.dataset.originalText;
                }
            });
        }
        
        function showStatusNotification(message, type = 'success') {
            const notification = document.getElementById('statusNotification');
            const text = document.getElementById('statusNotificationText');
            const icon = notification.querySelector('i');
            
            text.textContent = message;
            
            if (type === 'error') {
                notification.classList.add('error');
                icon.className = 'bi bi-exclamation-circle me-2';
            } else {
                notification.classList.remove('error');
                icon.className = 'bi bi-check-circle me-2';
            }
            
            notification.classList.add('show');
            
            setTimeout(() => {
                notification.classList.remove('show');
            }, 4000);
        }
    
    </script>
</body>
</html>