<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки профиля - Система управления задачами</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        /* iOS-inspired design overrides and general improvements */
        body {
            background: #f4f5f7;
            font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;
            color: #222;
        }
        .settings-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .settings-header {
            background: linear-gradient(135deg, #f7f8fa 0%, #e1e7ef 100%);
            color: #222;
            padding: 2rem 1.5rem;
            border-radius: 28px 28px 0 0;
            margin-bottom: -1px;
            box-shadow: 0 6px 24px 0 rgba(0,0,0,0.04);
        }
        .settings-header h2 {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        .settings-nav {
            background: #fff;
            border: 0;
            border-radius: 0 0 28px 28px;
            padding: 0;
            overflow: hidden;
            box-shadow: 0 4px 24px 0 rgba(0,0,0,0.03);
            margin-bottom: 2rem;
        }
        .nav-pills .nav-link {
            color: #555;
            font-size: 1.1rem;
            font-weight: 500;
            border-radius: 0;
            padding: 1rem 1.5rem;
            border-right: 1px solid #f1f1f1;
            background: none;
            transition: background 0.2s, color 0.2s;
        }
        .nav-pills .nav-link:last-child { border-right: none; }
        .nav-pills .nav-link:hover,
        .nav-pills .nav-link:focus {
            background: #f4f5f7;
            color: #007aff;
        }
        .nav-pills .nav-link.active {
            background: #e3e8f1;
            color: #007aff;
            box-shadow: 0 2px 8px 0 rgba(0,122,255,.07);
        }
        .nav-pills .nav-link i {
            margin-right: 0.5rem;
        }
        .settings-card {
            background: #fff;
            border-radius: 22px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.07);
            padding: 2rem 1.5rem;
            margin-bottom: 1.5rem;
            border: none;
        }
        .settings-section {
            margin-bottom: 2rem;
        }
        .settings-section h5,
        .settings-section h6 {
            color: #111;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1.5px solid #f1f4fa;
            font-size: 1.2rem;
            letter-spacing: -0.01em;
        }
        .form-label {
            font-weight: 500;
            color: #222;
            margin-bottom: 0.5rem;
        }
        .form-control, .form-select {
            border: 2px solid #e7eaf1;
            border-radius: 14px;
            padding: 0.85rem 1rem;
            font-size: 1rem;
            background: #f9fafb;
            transition: all 0.3s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #007aff;
            box-shadow: 0 0 0 0.18rem rgba(0,122,255,0.13);
        }
        
        .form-check-input {
            width: 1.35rem;
            height: 1.35rem;
            margin-top: 0.125rem;
            border: 2px solid #e7eaf1;
            border-radius: 8px;
            background: #f4f6fa;
        }
        .form-check-input:checked {
            background-color: #007aff;
            border-color: #007aff;
        }
        .btn-save {
            background: linear-gradient(90deg, #007aff 0%, #00c6fb 100%);
            border: none;
            color: white;
            padding: 0.75rem 2.2rem;
            font-weight: 600;
            border-radius: 14px;
            font-size: 1.1rem;
            transition: all 0.24s;
            box-shadow: 0 4px 18px 0 rgba(0,122,255,0.09);
        }
        .btn-save:hover {
            transform: translateY(-2px) scale(1.025);
            box-shadow: 0 5px 20px rgba(0,122,255, 0.18);
            color: white;
        }
        .notification-preview {
            background: #f4f6fa;
            border: 2px solid #e7eaf1;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
        }
        .telegram-bot-info {
            background: #e6f0fd;
            border: 1px solid #b4d7fa;
            border-radius: 11px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .telegram-bot-info code {
            background: #007aff;
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
        }
        .password-requirements {
            font-size: 0.9rem;
            color: #7c8592;
            margin-top: 0.5rem;
        }
        .password-requirements li {
            margin-bottom: 0.25rem;
        }
        .alert-custom {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.5rem;
            font-size: 1rem;
        }
        .user-avatar-upload {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 2rem;
        }
        .user-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg,#007aff,#00c6fb);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.2rem;
            font-weight: 600;
            position: relative;
            overflow: hidden;
            box-shadow: 0 2px 16px 0 rgba(0,122,255,0.08);
        }
        .avatar-upload-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #fff;
            color: #007aff;
            border: 2.5px solid #fff;
            border-radius: 50%;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 8px 0 rgba(0,122,255,0.08);
            transition: all 0.3s;
        }
        .avatar-upload-btn:hover {
            background: #f4f6fb;
            transform: scale(1.08);
        }
        .danger-zone {
            border: 2px solid #dc3545;
            border-radius: 12px;
            padding: 1.5rem;
            background: #fff5f5;
        }
        .tab-pane {
            padding-top: 2rem;
        }
        .list-group-item {
            border-radius: 12px !important;
            margin-bottom: 0.5rem;
            border: 1.2px solid #e7eaf1;
            background: #f9fafb;
        }
        .modal-content {
            border-radius: 16px;
        }
        /* Responsive: iOS-like paddings and stacking */
        @media (max-width: 768px) {
            .settings-header, .settings-card {
                padding: 1.3rem 0.8rem;
            }
            .nav-pills .nav-link {
                border-right: none;
                border-bottom: 1px solid #f1f1f1;
                padding: 0.9rem 0.8rem;
                font-size: 1rem;
            }
            .settings-header {
                padding: 1.2rem 0.8rem;
            }
            .settings-container {
                padding: 0 0.2rem;
            }
        }
        @media (max-width: 475px) {
            .settings-header {
                border-radius: 0;
            }
            .settings-nav {
                border-radius: 0 0 18px 18px;
            }
            .settings-card {
                border-radius: 10px;
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
                    <a href="/profile" class="btn btn-light border-0 px-3 py-2 rounded-12 shadow-sm">
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
                            <label for="avatar" class="avatar-upload-btn" title="Загрузить фото">
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
                                        Получите Chat ID у бота @task_koleso_bot
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
                        
                        <div class="d-flex justify-content-between flex-column flex-md-row gap-2">
                            <button type="button" class="btn btn-outline-primary rounded-12 px-4" id="testNotification">
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
                                    <span class="badge bg-success px-3 py-1 rounded-pill" style="font-size:0.95em;">Активна</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button type="button" class="btn btn-outline-danger rounded-12 px-4">
                                <i class="bi bi-box-arrow-right me-2"></i>
                                Завершить все другие сессии
                            </button>
                        </div>
                    </div>
                </form>
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
                    <button type="button" class="btn btn-secondary rounded-12" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" form="deleteAccountForm" class="btn btn-danger rounded-12">
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
            if (/[A-ZА-ЯЁ]/.test(password)) {
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