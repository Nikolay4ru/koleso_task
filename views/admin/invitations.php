<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Приглашения - Администрирование</title>
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
        .content-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .invitation-form {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 2rem;
        }
        .invitation-item {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }
        .invitation-item:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-used {
            background: #d1e7dd;
            color: #0f5132;
        }
        .status-cancelled {
            background: #f8d7da;
            color: #842029;
        }
        .status-expired {
            background: #e9ecef;
            color: #6c757d;
        }
        .invitation-actions {
            display: flex;
            gap: 0.5rem;
        }
        .copy-link {
            cursor: pointer;
            color: #667eea;
            transition: color 0.2s;
        }
        .copy-link:hover {
            color: #5a67d8;
        }
        .invitation-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.5rem;
        }
        .invitation-details {
            flex-grow: 1;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layouts/main.php'; ?>
    
    <!-- Заголовок -->
    <div class="admin-header">
        <div class="container">
            <h2>Администрирование</h2>
            <p class="mb-0">Управление приглашениями</p>
        </div>
    </div>
    
    <div class="container">
        <!-- Навигация администратора -->
        <div class="admin-nav">
            <ul class="nav nav-pills">
                <li class="nav-item">
                    <a class="nav-link" href="/admin/dashboard">
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
                    <a class="nav-link active" href="/admin/invitations">
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
        
        <!-- Форма отправки приглашения -->
        <div class="content-card">
            <h4 class="mb-4">Отправить приглашение</h4>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    <?= $_SESSION['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <form method="POST" action="/admin/invitations/send" class="invitation-form">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <div class="form-text">Email адрес нового пользователя</div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Имя <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="form-text">Полное имя пользователя</div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="department_id" class="form-label">Отдел</label>
                        <select class="form-select" id="department_id" name="department_id">
                            <option value="">Не указан</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Права доступа</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_admin" name="is_admin">
                            <label class="form-check-label" for="is_admin">
                                <i class="bi bi-shield-check me-1"></i>
                                Права администратора
                            </label>
                        </div>
                        <div class="form-text">Администраторы могут управлять пользователями и системой</div>
                    </div>
                    
                    <div class="col-12 mb-3">
                        <label for="message" class="form-label">Персональное сообщение</label>
                        <textarea class="form-control" id="message" name="message" rows="3" 
                                  placeholder="Добавьте персональное сообщение к приглашению (опционально)"></textarea>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-send me-2"></i>
                    Отправить приглашение
                </button>
            </form>
        </div>
        
        <!-- Список приглашений -->
        <div class="content-card">
            <h4 class="mb-4">История приглашений</h4>
            
            <?php if (empty($invitations)): ?>
                <p class="text-muted text-center py-4">Приглашения еще не отправлялись</p>
            <?php else: ?>
                <?php foreach ($invitations as $invitation): ?>
                    <?php
                    $isExpired = strtotime($invitation['expires_at']) < time() && $invitation['status'] === 'pending';
                    $status = $isExpired ? 'expired' : $invitation['status'];
                    ?>
                    <div class="invitation-item">
                        <div class="invitation-info">
                            <div class="invitation-details">
                                <div class="d-flex align-items-center gap-3 mb-2">
                                    <h6 class="mb-0"><?= htmlspecialchars($invitation['name']) ?></h6>
                                    <span class="status-badge status-<?= $status ?>">
                                        <?php
                                        $statusLabels = [
                                            'pending' => 'Ожидает',
                                            'used' => 'Использовано',
                                            'cancelled' => 'Отменено',
                                            'expired' => 'Истекло'
                                        ];
                                        echo $statusLabels[$status];
                                        ?>
                                    </span>
                                    <?php if ($invitation['is_admin']): ?>
                                        <span class="badge bg-primary">
                                            <i class="bi bi-shield-check me-1"></i>
                                            Администратор
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="text-muted small">
                                    <i class="bi bi-envelope me-1"></i>
                                    <?= htmlspecialchars($invitation['email']) ?>
                                    
                                    <?php if ($invitation['department_name']): ?>
                                        <span class="ms-3">
                                            <i class="bi bi-building me-1"></i>
                                            <?= htmlspecialchars($invitation['department_name']) ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <span class="ms-3">
                                        <i class="bi bi-person me-1"></i>
                                        Приглашен: <?= htmlspecialchars($invitation['invited_by_name']) ?>
                                    </span>
                                    
                                    <span class="ms-3">
                                        <i class="bi bi-calendar me-1"></i>
                                        <?= date('d.m.Y H:i', strtotime($invitation['created_at'])) ?>
                                    </span>
                                </div>
                                
                                <?php if ($invitation['status'] === 'pending' && !$isExpired): ?>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="bi bi-clock me-1"></i>
                                            Действительно до: <?= date('d.m.Y H:i', strtotime($invitation['expires_at'])) ?>
                                        </small>
                                        <span class="copy-link ms-3" onclick="copyInviteLink('<?= $invitation['token'] ?>')">
                                            <i class="bi bi-link-45deg me-1"></i>
                                            Скопировать ссылку
                                        </span>
                                    </div>
                                <?php elseif ($invitation['status'] === 'used'): ?>
                                    <div class="mt-2">
                                        <small class="text-success">
                                            <i class="bi bi-check-circle me-1"></i>
                                            Использовано: <?= date('d.m.Y H:i', strtotime($invitation['used_at'])) ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($invitation['status'] === 'pending' && !$isExpired): ?>
                                <div class="invitation-actions">
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="resendInvitation(<?= $invitation['id'] ?>)"
                                            title="Отправить повторно">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" 
                                            onclick="cancelInvitation(<?= $invitation['id'] ?>)"
                                            title="Отменить">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyInviteLink(token) {
            const url = window.location.origin + '/register?token=' + token;
            navigator.clipboard.writeText(url).then(() => {
                alert('Ссылка скопирована в буфер обмена');
            });
        }
        
        function resendInvitation(id) {
            if (!confirm('Отправить приглашение повторно?')) return;
            
            fetch(`/admin/invitations/resend/${id}`, {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Приглашение отправлено повторно');
                    location.reload();
                } else {
                    alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
                }
            });
        }
        
        function cancelInvitation(id) {
            if (!confirm('Отменить это приглашение?')) return;
            
            fetch(`/admin/invitations/cancel/${id}`, {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
                }
            });
        }
    </script>
</body>
</html>