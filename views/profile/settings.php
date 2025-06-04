<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки профиля - Система управления задачами</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .settings-container {
            max-width: 1000px;
            margin: 2rem auto;
        }
        .settings-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px 10px 0 0;
            margin-bottom: -1px;
        }
        .settings-nav {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 0 0 10px 10px;
            padding: 0;
            overflow: hidden;
        }
        .nav-pills .nav-link {
            color: #495057;
            border-radius: 0;
            padding: 1rem 1.5rem;
            border-right: 1px solid #dee2e6;
            transition: all 0.3s;
        }
        .nav-pills .nav-link:hover {
            background: #f8f9fa;
            color: #667eea;
        }
        .nav-pills .nav-link.active {
            background: #667eea;
            color: white;
        }
        .nav-pills .nav-link i {
            margin-right: 0.5rem;
        }
        .settings-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 2rem;
            margin-bottom: 1.5rem;
        }
        .settings-section {
            margin-bottom: 2rem;
        }
        .settings-section h5 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #e9ecef;
        }
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            transition: all 0.3s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .form-check-input {
            width: 1.25rem;
            height: 1.25rem;
            margin-top: 0.125rem;
            border: 2px solid #dee2e6;
        }
        .form-check-input:checked {
            background-color: #667eea;
            border-color: #667eea;
        }
        .btn-save {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .notification-preview {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }
        .telegram-bot-info {
            background: #e3f2fd;
            border: 1px solid #90caf9;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .telegram-bot-info code {
            background: #1976d2;
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
        }
        .password-requirements {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }
        .password-requirements li {
            margin-bottom: 0.25rem;
        }
        .alert-custom {
            border-radius: 8px;
            border: none;
            padding: 1rem 1.5rem;
        }
        .user-avatar-upload {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 1.5rem;
        }
        .user-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: 600;
            position: relative;
            overflow: hidden;
        }
        .avatar-upload-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #2c3e50;
            color: white;
            border: 3px solid white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .avatar-upload-btn:hover {
            background: #34495e;
            transform: scale(1.1);
        }
        .danger-zone {
            border: 2px solid #dc3545;
            border-radius: 8px;
            padding: 1.5rem;
            background: #fff5f5;
        }
        .tab-pane {
            padding-top: 2rem;
        }
        @media (max-width: 768px) {
            .nav-pills .nav-link {
                border-right: none;
                border-bottom: 1px solid #dee2e6;
            }
            .settings-header {
                padding: 1.5rem;
            }
            .settings-card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layouts/main.php'; ?>
    
    <div class="container settings-container">
        <!-- Заголовок -->
        <div class="settings-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-1">Настройки профиля</h2>
                    <p class="mb-0 opacity-75">Управляйте вашими персональными настройками и предпочтениями</p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <a href="/profile" class="btn btn-light">
                        <i class="bi bi-person-circle me-2"></i>
                        Мой профиль
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Навигация по вкладкам -->
        <div class="settings-nav">
            <ul class="nav nav-pills nav-fill" id="settingsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button">
                        <i class="bi bi-gear"></i>
                        Основные
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications" type="button">
                        <i class="bi bi-bell"></i>
                        Уведомления
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button">
                        <i class="bi bi-shield-lock"></i>
                        Безопасность
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="advanced-tab" data-bs-toggle="tab" data-bs-target="#advanced" type="button">
                        <i class="bi bi-sliders"></i>
                        Дополнительно
                    </button>
                </li>
            </ul>
        </div>
        
        <!-- Контент вкладок -->
        <div class="tab-content" id="settingsTabContent">
            <!-- Основные настройки -->
            <div class="tab-pane fade show active" id="general" role="tabpanel">
                <form method="POST" action="/profile/update" enctype="multipart/form-data">
                    <input type="hidden" name="section" value="general">
                    
                    <div class="settings-card">
                        <h5>Личная информация</h5>
                        
                        <?php if (isset($_SESSION['success']) && $_SESSION['section'] == 'general'): ?>
                            <div class="alert alert-success alert-custom alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle me-2"></i>
                                <?= $_SESSION['success'] ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['success'], $_SESSION['section']); ?>
                        <?php endif; ?>
                        
                        <?php if (isset($error) && $_POST['section'] == 'general'): ?>
                            <div class="alert alert-danger alert-custom alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <?= htmlspecialchars($error) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Аватар -->
                        <div class="user-avatar-upload">
                            <?php 
                            $initials = implode('', array_map(function($word) { 
                                return mb_substr($word, 0, 1); 
                            }, explode(' ', $user['name'])));
                            ?>
                            <div class="user-avatar">
                                <?= mb_strtoupper($initials) ?>
                            </div>
                            <label for="avatar" class="avatar-upload-btn">
                                <i class="bi bi-camera"></i>
                                <input type="file" id="avatar" name="avatar" accept="image/*" style="display: none;">
                            </label>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Полное имя</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="name" 
                                       name="name" 
                                       value="<?= htmlspecialchars($user['name']) ?>" 
                                       required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" 
                                       class="form-control" 
                                       value="<?= htmlspecialchars($user['email']) ?>" 
                                       disabled>
                                <small class="text-muted">Email изменить нельзя</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Телефон</label>
                                <input type="tel" 
                                       class="form-control" 
                                       id="phone" 
                                       name="phone" 
                                       value="<?= htmlspecialchars($user['phone'] ?? '') ?>" 
                                       placeholder="+7 (999) 123-45-67">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="department_id" class="form-label">Отдел</label>
                                <select class="form-select" id="department_id" name="department_id">
                                    <option value="">Не указан</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?= $dept['id'] ?>" 
                                                <?= $user['department_id'] == $dept['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($dept['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="bio" class="form-label">О себе</label>
                                <textarea class="form-control" 
                                          id="bio" 
                                          name="bio" 
                                          rows="3" 
                                          placeholder="Расскажите немного о себе..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-save">
                                <i class="bi bi-check-lg me-2"></i>
                                Сохранить изменения
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Настройки уведомлений -->
            <div class="tab-pane fade" id="notifications" role="tabpanel">
                <form method="POST" action="/profile/update">
                    <input type="hidden" name="section" value="notifications">
                    
                    <div class="settings-card">
                        <h5>Настройки уведомлений</h5>
                        
                        <?php if (isset($_SESSION['success']) && $_SESSION['section'] == 'notifications'): ?>
                            <div class="alert alert-success alert-custom alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle me-2"></i>
                                <?= $_SESSION['success'] ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['success'], $_SESSION['section']); ?>
                        <?php endif; ?>
                        
                        <!-- Email уведомления -->
                        <div class="settings-section">
                            <h6 class="mb-3">
                                <i class="bi bi-envelope me-2"></i>
                                Email уведомления
                            </h6>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="email_notifications" 
                                       name="email_notifications"
                                       <?= $user['email_notifications'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="email_notifications">
                                    Получать уведомления по Email
                                </label>
                            </div>
                            
                            <div class="notification-preview">
                                <small class="text-muted d-block mb-2">Вы будете получать уведомления на:</small>
                                <strong><?= htmlspecialchars($user['email']) ?></strong>
                            </div>
                        </div>
                        
                        <!-- Telegram уведомления -->
                        <div class="settings-section">
                            <h6 class="mb-3">
                                <i class="bi bi-telegram me-2"></i>
                                Telegram уведомления
                            </h6>
                            
                            <?php if (empty($user['telegram_chat_id'])): ?>
                                <div class="telegram-bot-info">
                                    <h6 class="mb-2">Как подключить Telegram уведомления:</h6>
                                    <ol class="mb-0">
                                        <li>Найдите бота <code>@task_koleso_bot</code> в Telegram</li>
                                        <li>Нажмите кнопку "Start" или отправьте команду <code>/start</code></li>
                                        <li>Скопируйте полученный Chat ID и вставьте его ниже</li>
                                    </ol>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label for="telegram_chat_id" class="form-label">Telegram Chat ID</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="telegram_chat_id" 
                                       name="telegram_chat_id" 
                                       value="<?= htmlspecialchars($user['telegram_chat_id'] ?? '') ?>"
                                       placeholder="Например: 123456789">
                                <small class="text-muted">
                                    <?php if (!empty($user['telegram_chat_id'])): ?>
                                        <i class="bi bi-check-circle text-success me-1"></i>
                                        Telegram подключен
                                    <?php else: ?>
                                        Получите Chat ID у бота @TaskManagementBot
                                    <?php endif; ?>
                                </small>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="telegram_notifications" 
                                       name="telegram_notifications"
                                       <?= $user['telegram_notifications'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="telegram_notifications">
                                    Получать уведомления в Telegram
                                </label>
                            </div>
                        </div>
                        
                        <!-- Типы уведомлений -->
                        <div class="settings-section">
                            <h6 class="mb-3">Типы уведомлений</h6>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="notify_task_assigned" name="notify_task_assigned" checked>
                                        <label class="form-check-label" for="notify_task_assigned">
                                            Назначение на задачу
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="notify_task_completed" name="notify_task_completed" checked>
                                        <label class="form-check-label" for="notify_task_completed">
                                            Завершение задачи
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="notify_comment" name="notify_comment" checked>
                                        <label class="form-check-label" for="notify_comment">
                                            Новые комментарии
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="notify_status_change" name="notify_status_change" checked>
                                        <label class="form-check-label" for="notify_status_change">
                                            Изменение статуса задачи
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="notify_deadline" name="notify_deadline" checked>
                                        <label class="form-check-label" for="notify_deadline">
                                            Напоминания о дедлайнах
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="notify_mentions" name="notify_mentions" checked>
                                        <label class="form-check-label" for="notify_mentions">
                                            Упоминания в комментариях
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-primary" id="testNotification">
                                <i class="bi bi-send me-2"></i>
                                Тестовое уведомление
                            </button>
                            <button type="submit" class="btn btn-save">
                                <i class="bi bi-check-lg me-2"></i>
                                Сохранить настройки
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Безопасность -->
            <div class="tab-pane fade" id="security" role="tabpanel">
                <form method="POST" action="/profile/update">
                    <input type="hidden" name="section" value="security">
                    
                    <div class="settings-card">
                        <h5>Изменение пароля</h5>
                        
                        <?php if (isset($_SESSION['success']) && $_SESSION['section'] == 'security'): ?>
                            <div class="alert alert-success alert-custom alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle me-2"></i>
                                <?= $_SESSION['success'] ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['success'], $_SESSION['section']); ?>
                        <?php endif; ?>
                        
                        <?php if (isset($error) && $_POST['section'] == 'security'): ?>
                            <div class="alert alert-danger alert-custom alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <?= htmlspecialchars($error) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="current_password" class="form-label">Текущий пароль</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>
                            
                            <div class="col-md-6"></div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="new_password" class="form-label">Новый пароль</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                                <ul class="password-requirements">
                                    <li>Минимум 8 символов</li>
                                    <li>Хотя бы одна заглавная буква</li>
                                    <li>Хотя бы одна цифра</li>
                                </ul>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="new_password_confirm" class="form-label">Подтвердите новый пароль</label>
                                <input type="password" class="form-control" id="new_password_confirm" name="new_password_confirm">
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-save">
                                <i class="bi bi-shield-lock me-2"></i>
                                Изменить пароль
                            </button>
                        </div>
                    </div>
                    
                    <div class="settings-card">
                        <h5>Активные сессии</h5>
                        <p class="text-muted mb-4">Управляйте устройствами, с которых выполнен вход в вашу учетную запись</p>
                        
                        <div class="list-group">
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">
                                            <i class="bi bi-laptop me-2"></i>
                                            Текущая сессия
                                        </h6>
                                        <p class="mb-0 text-muted">
                                            <?= $_SERVER['HTTP_USER_AGENT'] ?>
                                        </p>
                                        <small class="text-muted">IP: <?= $_SERVER['REMOTE_ADDR'] ?></small>
                                    </div>
                                    <span class="badge bg-success">Активна</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button type="button" class="btn btn-outline-danger">
                                <i class="bi bi-box-arrow-right me-2"></i>
                                Завершить все другие сессии
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Дополнительные настройки -->
            <div class="tab-pane fade" id="advanced" role="tabpanel">
                <div class="settings-card">
                    <h5>Экспорт данных</h5>
                    <p class="text-muted mb-4">Скачайте копию всех ваших данных из системы</p>
                    
                    <button type="button" class="btn btn-outline-primary">
                        <i class="bi bi-download me-2"></i>
                        Экспортировать мои данные
                    </button>
                </div>
                
                <div class="settings-card">
                    <h5>Удаление аккаунта</h5>
                    
                    <div class="danger-zone">
                        <h6 class="text-danger mb-3">Опасная зона</h6>
                        <p class="mb-3">
                            После удаления аккаунта восстановление будет невозможно. 
                            Все ваши данные, включая задачи и комментарии, будут удалены навсегда.
                        </p>
                        
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                            <i class="bi bi-trash me-2"></i>
                            Удалить мой аккаунт
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Модальное окно удаления аккаунта -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Удаление аккаунта</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Вы уверены, что хотите удалить свой аккаунт?</p>
                    <p class="text-danger mb-3">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Это действие нельзя отменить! Все ваши данные будут удалены навсегда.
                    </p>
                    
                    <form id="deleteAccountForm" method="POST" action="/profile/delete">
                        <div class="mb-3">
                            <label for="delete_password" class="form-label">Введите ваш пароль для подтверждения:</label>
                            <input type="password" class="form-control" id="delete_password" name="password" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" form="deleteAccountForm" class="btn btn-danger">
                        <i class="bi bi-trash me-2"></i>
                        Удалить навсегда
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Активация вкладки из URL
        document.addEventListener('DOMContentLoaded', function() {
            const hash = window.location.hash;
            if (hash) {
                const tab = document.querySelector(`button[data-bs-target="${hash}"]`);
                if (tab) {
                    const bsTab = new bootstrap.Tab(tab);
                    bsTab.show();
                }
            }
        });
        
        // Сохранение активной вкладки в URL
        document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(button => {
            button.addEventListener('shown.bs.tab', function (e) {
                const target = e.target.getAttribute('data-bs-target');
                window.location.hash = target;
            });
        });
        
        // Предпросмотр аватара
        document.getElementById('avatar').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const avatar = document.querySelector('.user-avatar');
                    avatar.style.backgroundImage = `url(${e.target.result})`;
                    avatar.style.backgroundSize = 'cover';
                    avatar.style.backgroundPosition = 'center';
                    avatar.innerHTML = '';
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Тестовое уведомление
        document.getElementById('testNotification').addEventListener('click', function() {
            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Отправка...';
            
            fetch('/profile/test-notification', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Тестовое уведомление отправлено! Проверьте вашу почту и Telegram.');
                } else {
                    alert('Ошибка отправки: ' + (data.error || 'Неизвестная ошибка'));
                }
            })
            .catch(error => {
                alert('Произошла ошибка при отправке');
                console.error('Error:', error);
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-send me-2"></i>Тестовое уведомление';
            });
        });
        
        // Валидация пароля
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('new_password_confirm');
        
        function validatePassword() {
            const password = newPassword.value;
            const requirements = document.querySelector('.password-requirements');
            const items = requirements.querySelectorAll('li');
            
            // Минимум 8 символов
            if (password.length >= 8) {
                items[0].classList.add('text-success');
                items[0].innerHTML = '<i class="bi bi-check-circle me-1"></i>' + items[0].textContent;
            } else {
                items[0].classList.remove('text-success');
                items[0].innerHTML = items[0].textContent;
            }
            
            // Заглавная буква
            if (/[A-Z]/.test(password)) {
                items[1].classList.add('text-success');
                items[1].innerHTML = '<i class="bi bi-check-circle me-1"></i>' + items[1].textContent;
            } else {
                items[1].classList.remove('text-success');
                items[1].innerHTML = items[1].textContent;
            }
            
            // Цифра
            if (/[0-9]/.test(password)) {
                items[2].classList.add('text-success');
                items[2].innerHTML = '<i class="bi bi-check-circle me-1"></i>' + items[2].textContent;
            } else {
                items[2].classList.remove('text-success');
                items[2].innerHTML = items[2].textContent;
            }
        }
        
        if (newPassword) {
            newPassword.addEventListener('input', validatePassword);
        }
        
        // Проверка совпадения паролей
        if (confirmPassword) {
            confirmPassword.addEventListener('input', function() {
                if (this.value !== newPassword.value) {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                }
            });
        }
        
        // Маска телефона
        const phoneInput = document.getElementById('phone');
        if (phoneInput) {
            phoneInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                let formattedValue = '';
                
                if (value.length > 0) {
                    if (value[0] === '7') {
                        formattedValue = '+7';
                        value = value.substring(1);
                    } else if (value[0] === '8') {
                        formattedValue = '+7';
                        value = value.substring(1);
                    } else {
                        formattedValue = '+7';
                    }
                    
                    if (value.length > 0) {
                        formattedValue += ' (' + value.substring(0, 3);
                    }
                    if (value.length >= 3) {
                        formattedValue += ')';
                    }
                    if (value.length > 3) {
                        formattedValue += ' ' + value.substring(3, 6);
                    }
                    if (value.length > 6) {
                        formattedValue += '-' + value.substring(6, 8);
                    }
                    if (value.length > 8) {
                        formattedValue += '-' + value.substring(8, 10);
                    }
                }
                
                e.target.value = formattedValue;
            });
        }
        
        // Автосохранение черновика
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            const inputs = form.querySelectorAll('input:not([type="password"]), textarea, select');
            inputs.forEach(input => {
                input.addEventListener('change', function() {
                    localStorage.setItem('settings_' + this.name, this.type === 'checkbox' ? this.checked : this.value);
                });
                
                // Восстановление значений
                const savedValue = localStorage.getItem('settings_' + input.name);
                if (savedValue !== null) {
                    if (input.type === 'checkbox') {
                        input.checked = savedValue === 'true';
                    } else {
                        input.value = savedValue;
                    }
                }
            });
            
            // Очистка при успешной отправке
            form.addEventListener('submit', function() {
                inputs.forEach(input => {
                    localStorage.removeItem('settings_' + input.name);
                });
            });
        });
    </script>
</body>
</html>