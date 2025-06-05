<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление отделами - Администрирование</title>
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
        .department-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }
        .department-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }
        .department-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        .department-stats {
            display: flex;
            gap: 2rem;
            margin-top: 1rem;
        }
        .stat-item {
            text-align: center;
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2c3e50;
        }
        .stat-label {
            font-size: 0.875rem;
            color: #6c757d;
        }
        .department-actions {
            display: flex;
            gap: 0.5rem;
        }
        .create-form {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 2rem;
        }
        .user-badge {
            display: inline-flex;
            align-items: center;
            background: #e9ecef;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.875rem;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .user-avatar-small {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.625rem;
            margin-right: 0.5rem;
        }
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        .empty-state-icon {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }
        .performance-indicator {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 0.5rem;
        }
        .performance-bar {
            height: 100%;
            background: linear-gradient(90deg, #28a745 0%, #ffc107 50%, #dc3545 100%);
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layouts/main.php'; ?>
    
    <!-- Заголовок -->
    <div class="admin-header">
        <div class="container">
            <h2>Администрирование</h2>
            <p class="mb-0">Управление отделами организации</p>
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
                    <a class="nav-link" href="/admin/invitations">
                        <i class="bi bi-envelope me-2"></i>
                        Приглашения
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="/admin/departments">
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
        
        <!-- Создание нового отдела -->
        <div class="content-card">
            <h4 class="mb-4">Создать новый отдел</h4>
            
            <form method="POST" action="/admin/departments/create" class="create-form" id="createDepartmentForm">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Название отдела <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="form-text">Например: Отдел разработки, Маркетинг, HR</div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="head_user_id" class="form-label">Руководитель отдела</label>
                        <select class="form-select" id="head_user_id" name="head_user_id">
                            <option value="">Не назначен</option>
                            <?php 
                            $allUsers = [];
                            foreach ($departments as $dept) {
                                if (isset($dept['users'])) {
                                    $allUsers = array_merge($allUsers, $dept['users']);
                                }
                            }
                            $uniqueUsers = array_unique(array_column($allUsers, 'id'));
                            foreach ($allUsers as $user): 
                                if (in_array($user['id'], $uniqueUsers)):
                            ?>
                                <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                            <?php 
                                    unset($uniqueUsers[array_search($user['id'], $uniqueUsers)]);
                                endif;
                            endforeach; 
                            ?>
                        </select>
                    </div>
                    
                    <div class="col-12 mb-3">
                        <label for="description" class="form-label">Описание</label>
                        <textarea class="form-control" id="description" name="description" rows="2" 
                                  placeholder="Краткое описание функций и задач отдела"></textarea>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>
                    Создать отдел
                </button>
            </form>
        </div>
        
        <!-- Список отделов -->
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>Существующие отделы</h4>
                <div class="text-muted">
                    Всего отделов: <?= count($departments) ?>
                </div>
            </div>
            
            <?php if (empty($departments)): ?>
                <div class="empty-state">
                    <i class="bi bi-building empty-state-icon"></i>
                    <h5>Отделы еще не созданы</h5>
                    <p>Создайте первый отдел для организации сотрудников</p>
                </div>
            <?php else: ?>
                <?php foreach ($departments as $department): ?>
                    <div class="department-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="d-flex gap-3 flex-grow-1">
                                <div class="department-icon">
                                    <i class="bi bi-diagram-3"></i>
                                </div>
                                
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h5 class="mb-1"><?= htmlspecialchars($department['name']) ?></h5>
                                            <?php if ($department['description']): ?>
                                                <p class="text-muted mb-2"><?= htmlspecialchars($department['description']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="department-actions">
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="editDepartment(<?= $department['id'] ?>, '<?= htmlspecialchars($department['name']) ?>', '<?= htmlspecialchars($department['description'] ?? '') ?>')"
                                                    title="Редактировать">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <?php if ($department['user_count'] == 0): ?>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="deleteDepartment(<?= $department['id'] ?>, '<?= htmlspecialchars($department['name']) ?>')"
                                                        title="Удалить">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Статистика отдела -->
                                    <div class="department-stats">
                                        <div class="stat-item">
                                            <div class="stat-value"><?= $department['user_count'] ?></div>
                                            <div class="stat-label">Сотрудников</div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-value"><?= $department['task_count'] ?></div>
                                            <div class="stat-label">Задач</div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-value"><?= $department['completed_tasks'] ?></div>
                                            <div class="stat-label">Выполнено</div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-value">
                                                <?= $department['task_count'] > 0 
                                                    ? round(($department['completed_tasks'] / $department['task_count']) * 100) 
                                                    : 0 ?>%
                                            </div>
                                            <div class="stat-label">Эффективность</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Индикатор производительности -->
                                    <?php if ($department['task_count'] > 0): ?>
                                        <div class="performance-indicator">
                                            <div class="performance-bar" 
                                                 style="width: <?= round(($department['completed_tasks'] / $department['task_count']) * 100) ?>%">
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Сотрудники отдела -->
                                    <?php if ($department['user_count'] > 0): ?>
                                        <div class="mt-3">
                                            <h6 class="text-muted mb-2">Сотрудники:</h6>
                                            <div>
                                                <?php 
                                                // Получаем пользователей отдела из контроллера
                                                $deptUsers = array_filter($allUsers ?? [], function($u) use ($department) {
                                                    return $u['department_id'] == $department['id'];
                                                });
                                                
                                                foreach ($deptUsers as $user): 
                                                    $initials = implode('', array_map(function($word) { 
                                                        return mb_substr($word, 0, 1); 
                                                    }, explode(' ', $user['name'])));
                                                ?>
                                                    <span class="user-badge">
                                                        <span class="user-avatar-small"><?= mb_strtoupper($initials) ?></span>
                                                        <?= htmlspecialchars($user['name']) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted mt-3 mb-0">
                                            <i class="bi bi-info-circle me-1"></i>
                                            В отделе пока нет сотрудников
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Модальное окно редактирования -->
    <div class="modal fade" id="editDepartmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Редактировать отдел</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editDepartmentForm">
                    <div class="modal-body">
                        <input type="hidden" id="edit_id" name="id">
                        
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Название отдела</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Описание</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i>
                            Сохранить изменения
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Редактирование отдела
        function editDepartment(id, name, description) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_description').value = description;
            
            const modal = new bootstrap.Modal(document.getElementById('editDepartmentModal'));
            modal.show();
        }
        
        // Обработка формы редактирования
        document.getElementById('editDepartmentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const id = formData.get('id');
            
            fetch(`/admin/departments/edit/${id}`, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
                }
            });
        });
        
        // Удаление отдела
        function deleteDepartment(id, name) {
            if (!confirm(`Удалить отдел "${name}"?`)) return;
            
            fetch(`/admin/departments/delete/${id}`, {
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