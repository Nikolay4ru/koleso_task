<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями - Администрирование</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
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
        .admin-header h2 {
            margin-bottom: 0.5rem;
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
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1rem;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .status-active {
            background: #d1e7dd;
            color: #0f5132;
        }
        .status-inactive {
            background: #f8d7da;
            color: #842029;
        }
        .role-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .role-admin {
            background: #cfe2ff;
            color: #084298;
        }
        .role-user {
            background: #e9ecef;
            color: #495057;
        }
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        .stats-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s;
        }
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
        }
        .stats-label {
            color: #6c757d;
            font-size: 0.875rem;
        }
        .filter-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layouts/main.php'; ?>
    
    <!-- Заголовок -->
    <div class="admin-header">
        <div class="container">
            <h2>Администрирование</h2>
            <p class="mb-0">Управление системой и пользователями</p>
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
                    <a class="nav-link active" href="/admin/users">
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
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?= count($users) ?></div>
                    <div class="stats-label">Всего пользователей</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?= count(array_filter($users, function($u) { return $u['is_active']; })) ?></div>
                    <div class="stats-label">Активных</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?= count(array_filter($users, function($u) { return $u['is_admin']; })) ?></div>
                    <div class="stats-label">Администраторов</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?= count($departments) ?></div>
                    <div class="stats-label">Отделов</div>
                </div>
            </div>
        </div>
        
        <!-- Основной контент -->
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>Список пользователей</h4>
                <div>
                    <a href="/admin/invitations" class="btn btn-primary">
                        <i class="bi bi-person-plus me-2"></i>
                        Пригласить пользователя
                    </a>
                </div>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    <?= $_SESSION['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <!-- Фильтры -->
            <div class="filter-section">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Статус</label>
                        <select class="form-select form-select-sm" id="filterStatus">
                            <option value="">Все</option>
                            <option value="active">Активные</option>
                            <option value="inactive">Неактивные</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Роль</label>
                        <select class="form-select form-select-sm" id="filterRole">
                            <option value="">Все</option>
                            <option value="admin">Администраторы</option>
                            <option value="user">Пользователи</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Отдел</label>
                        <select class="form-select form-select-sm" id="filterDepartment">
                            <option value="">Все</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-sm btn-outline-secondary" id="resetFilters">
                            <i class="bi bi-x-circle me-1"></i>
                            Сбросить
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Таблица пользователей -->
            <div class="table-responsive">
                <table id="usersTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Пользователь</th>
                            <th>Email</th>
                            <th>Отдел</th>
                            <th>Роль</th>
                            <th>Статус</th>
                            <th>Задачи</th>
                            <th>Последняя активность</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr data-status="<?= $user['is_active'] ? 'active' : 'inactive' ?>"
                                data-role="<?= $user['is_admin'] ? 'admin' : 'user' ?>"
                                data-department="<?= $user['department_id'] ?>">
                                <td>
                                    <div class="user-info">
                                        <?php 
                                        $initials = implode('', array_map(function($word) { 
                                            return mb_substr($word, 0, 1); 
                                        }, explode(' ', $user['name'])));
                                        ?>
                                        <div class="user-avatar"><?= mb_strtoupper($initials) ?></div>
                                        <div>
                                            <div class="fw-semibold"><?= htmlspecialchars($user['name']) ?></div>
                                            <small class="text-muted">ID: #<?= $user['id'] ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td>
                                    <?= $user['department_name'] ? htmlspecialchars($user['department_name']) : '<span class="text-muted">—</span>' ?>
                                </td>
                                <td>
                                    <?php if ($user['is_admin']): ?>
                                        <span class="role-badge role-admin">
                                            <i class="bi bi-shield-check me-1"></i>
                                            Администратор
                                        </span>
                                    <?php else: ?>
                                        <span class="role-badge role-user">
                                            <i class="bi bi-person me-1"></i>
                                            Пользователь
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['is_active']): ?>
                                        <span class="status-badge status-active">
                                            <i class="bi bi-check-circle me-1"></i>
                                            Активен
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge status-inactive">
                                            <i class="bi bi-x-circle me-1"></i>
                                            Неактивен
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small>
                                        Создано: <?= $user['created_tasks'] ?? 0 ?><br>
                                        Назначено: <?= $user['assigned_tasks'] ?? 0 ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if ($user['last_login']): ?>
                                        <small>
                                            <?= date('d.m.Y', strtotime($user['last_login'])) ?><br>
                                            <?= date('H:i', strtotime($user['last_login'])) ?>
                                        </small>
                                    <?php else: ?>
                                        <small class="text-muted">Не входил</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="/admin/users/edit/<?= $user['id'] ?>" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="Редактировать">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>')"
                                                    title="Удалить">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        // Инициализация DataTable
        const table = $('#usersTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/ru.json'
            },
            pageLength: 25,
            order: [[6, 'desc']], // Сортировка по последней активности
            columnDefs: [
                { orderable: false, targets: [7] } // Отключаем сортировку для действий
            ]
        });
        
        // Фильтрация
        function applyFilters() {
            const status = $('#filterStatus').val();
            const role = $('#filterRole').val();
            const department = $('#filterDepartment').val();
            
            table.rows().every(function() {
                const row = $(this.node());
                let show = true;
                
                if (status && row.data('status') !== status) show = false;
                if (role && row.data('role') !== role) show = false;
                if (department && row.data('department') != department) show = false;
                
                if (show) {
                    row.show();
                } else {
                    row.hide();
                }
            });
            
            table.draw();
        }
        
        $('#filterStatus, #filterRole, #filterDepartment').change(applyFilters);
        
        $('#resetFilters').click(function() {
            $('#filterStatus, #filterRole, #filterDepartment').val('');
            table.rows().every(function() {
                $(this.node()).show();
            });
            table.draw();
        });
        
        // Удаление пользователя
        function deleteUser(userId, userName) {
            if (!confirm(`Вы уверены, что хотите удалить пользователя "${userName}"?\n\nВсе его задачи будут сохранены, но переназначены.`)) {
                return;
            }
            
            fetch(`/admin/users/delete/${userId}`, {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
                }
            })
            .catch(error => {
                alert('Произошла ошибка при удалении пользователя');
                console.error('Error:', error);
            });
        }
    </script>
</body>
</html>