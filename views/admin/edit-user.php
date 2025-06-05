<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование пользователя - Администрирование</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
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
        .content-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .user-avatar-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 3rem;
            margin: 0 auto 1.5rem;
        }
        .stats-box {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            margin-bottom: 1rem;
        }
        .stats-box h5 {
            color: #667eea;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .stats-box p {
            color: #6c757d;
            margin-bottom: 0;
            font-size: 0.875rem;
        }
        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #e9ecef;
        }
        .form-section:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        .form-section h5 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .switch-group {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
        }
        .activity-timeline {
            position: relative;
            padding-left: 2rem;
        }
        .activity-timeline::before {
            content: '';
            position: absolute;
            left: 0.5rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e9ecef;
        }
        .activity-item {
            position: relative;
            margin-bottom: 1rem;
        }
        .activity-item::before {
            content: '';
            position: absolute;
            left: -1.5rem;
            top: 0.5rem;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #667eea;
            border: 2px solid white;
        }
        .btn-save {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            font-weight: 600;
            border-radius: 8px;
        }
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layouts/main.php'; ?>
    
    <!-- Заголовок -->
    <div class="admin-header">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/admin/dashboard" class="text-white-50">Администрирование</a></li>
                    <li class="breadcrumb-item"><a href="/admin/users" class="text-white-50">Пользователи</a></li>
                    <li class="breadcrumb-item active text-white">Редактирование</li>
                </ol>
            </nav>
            <h2 class="mt-2">Редактирование пользователя</h2>
        </div>
    </div>
    
    <div class="container">
        <div class="row">
            <!-- Левая колонка - информация о пользователе -->
            <div class="col-md-4">
                <div class="content-card text-center">
                    <?php 
                    $initials = implode('', array_map(function($word) { 
                        return mb_substr($word, 0, 1); 
                    }, explode(' ', $user['name'])));
                    ?>
                    <div class="user-avatar-large"><?= mb_strtoupper($initials) ?></div>
                    
                    <h4><?= htmlspecialchars($user['name']) ?></h4>
                    <p class="text-muted"><?= htmlspecialchars($user['email']) ?></p>
                    
                    <?php if ($user['department_name']): ?>
                        <p>
                            <span class="badge bg-secondary">
                                <i class="bi bi-building me-1"></i>
                                <?= htmlspecialchars($user['department_name']) ?>
                            </span>
                        </p>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <!-- Статистика -->
                    <div class="row">
                        <div class="col-6">
                            <div class="stats-box">
                                <h5><?= $userStats['created_tasks'] ?></h5>
                                <p>Создано задач</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stats-box">
                                <h5><?= $userStats['assigned_tasks'] ?></h5>
                                <p>Назначено</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stats-box">
                                <h5><?= $userStats['completed_tasks'] ?></h5>
                                <p>Выполнено</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stats-box">
                                <h5><?= $userStats['watching_tasks'] ?></h5>
                                <p>Наблюдает</p>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Информация о регистрации -->
                    <div class="text-start">
                        <p class="mb-2">
                            <strong>Зарегистрирован:</strong><br>
                            <?= date('d.m.Y H:i', strtotime($user['created_at'])) ?>
                        </p>
                        <?php if ($user['last_login']): ?>
                        <p class="mb-2">
                            <strong>Последний вход:</strong><br>
                            <?= date('d.m.Y H:i', strtotime($user['last_login'])) ?>
                        </p>
                        <?php endif; ?>
                        <p class="mb-0">
                            <strong>ID пользователя:</strong> #<?= $user['id'] ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Правая колонка - форма редактирования -->
            <div class="col-md-8">
                <div class="content-card">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="/admin/users/edit/<?= $user['id'] ?>">
                        <!-- Основная информация -->
                        <div class="form-section">
                            <h5>
                                <i class="bi bi-person-circle"></i>
                                Основная информация
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Полное имя</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?= htmlspecialchars($user['name']) ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= htmlspecialchars($user['email']) ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Телефон</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
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
                                    <textarea class="form-control" id="bio" name="bio" rows="3" 
                                              placeholder="Краткая информация о пользователе..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Права и статус -->
                        <div class="form-section">
                            <h5>
                                <i class="bi bi-shield-check"></i>
                                Права и статус
                            </h5>
                            
                            <div class="switch-group">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                           <?= $user['is_active'] ? 'checked' : '' ?>
                                           <?= $user['id'] == $_SESSION['user_id'] ? 'disabled' : '' ?>>
                                    <label class="form-check-label" for="is_active">
                                        <strong>Активный пользователь</strong><br>
                                        <small class="text-muted">Неактивные пользователи не могут войти в систему</small>
                                    </label>
                                </div>
                                
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_admin" name="is_admin" 
                                           <?= $user['is_admin'] ? 'checked' : '' ?>
                                           <?= $user['id'] == $_SESSION['user_id'] ? 'disabled' : '' ?>>
                                    <label class="form-check-label" for="is_admin">
                                        <strong>Права администратора</strong><br>
                                        <small class="text-muted">Администраторы имеют полный доступ к системе</small>
                                    </label>
                                </div>
                                
                                <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                    <input type="hidden" name="is_active" value="1">
                                    <input type="hidden" name="is_admin" value="1">
                                    <div class="mt-2">
                                        <small class="text-warning">
                                            <i class="bi bi-info-circle me-1"></i>
                                            Вы не можете изменить свои права и статус
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Смена пароля -->
                        <div class="form-section">
                            <h5>
                                <i class="bi bi-key"></i>
                                Смена пароля
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="new_password" class="form-label">Новый пароль</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password">
                                    <div class="form-text">Оставьте пустым, если не хотите менять пароль</div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="new_password_confirm" class="form-label">Подтвердите пароль</label>
                                    <input type="password" class="form-control" id="new_password_confirm" name="new_password_confirm">
                                </div>
                                
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i>
                                        При смене пароля пользователь получит уведомление на email
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Кнопки действий -->
                        <div class="d-flex justify-content-between">
                            <a href="/admin/users" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>
                                Назад к списку
                            </a>
                            
                            <div>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <button type="button" class="btn btn-outline-danger me-2" 
                                            onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>')">
                                        <i class="bi bi-trash me-2"></i>
                                        Удалить пользователя
                                    </button>
                                <?php endif; ?>
                                
                                <button type="submit" class="btn btn-save">
                                    <i class="bi bi-check-lg me-2"></i>
                                    Сохранить изменения
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Проверка паролей
        document.getElementById('new_password_confirm').addEventListener('input', function() {
            const password = document.getElementById('new_password').value;
            const confirm = this.value;
            
            if (confirm && password !== confirm) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
        
        // Удаление пользователя
        function deleteUser(userId, userName) {
            if (!confirm(`Вы уверены, что хотите удалить пользователя "${userName}"?\n\nЭто действие нельзя отменить!`)) {
                return;
            }
            
            fetch(`/admin/users/delete/${userId}`, {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '/admin/users';
                } else {
                    alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
                }
            });
        }
    </script>
</body>
</html>