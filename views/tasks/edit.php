<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать задачу - <?= htmlspecialchars($task['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .edit-task-container { max-width: 800px; margin: 2rem auto; }
        .card { border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.08); border-radius: 10px; }
        .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 10px 10px 0 0 !important; padding: 1.5rem; }
        .form-label { font-weight: 600; color: #495057; margin-bottom: 0.5rem; }
        .form-control, .form-select { border: 2px solid #e9ecef; border-radius: 8px; padding: 0.75rem 1rem; transition: all 0.3s; }
        .form-control:focus, .form-select:focus { border-color: #667eea; box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25); }
        .required-field::after { content: " *"; color: #dc3545; }
        .priority-select { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .priority-option { position: relative; flex: 1; min-width: 120px; }
        .priority-option input[type="radio"] { position: absolute; opacity: 0; }
        .priority-label { display: block; padding: 0.75rem 1rem; border: 2px solid #e9ecef; border-radius: 8px; text-align: center; cursor: pointer; transition: all 0.3s; font-weight: 600; }
        .priority-option input[type="radio"]:checked + .priority-label { color: white; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        .priority-low .priority-label { color: #28a745; }
        .priority-low input[type="radio"]:checked + .priority-label { background: #28a745; border-color: #28a745; }
        .priority-medium .priority-label { color: #17a2b8; }
        .priority-medium input[type="radio"]:checked + .priority-label { background: #17a2b8; border-color: #17a2b8; }
        .priority-high .priority-label { color: #ffc107; }
        .priority-high input[type="radio"]:checked + .priority-label { background: #ffc107; border-color: #ffc107; }
        .priority-urgent .priority-label { color: #dc3545; }
        .priority-urgent input[type="radio"]:checked + .priority-label { background: #dc3545; border-color: #dc3545; }
        .user-select-item { display: flex; align-items: center; padding: 0.5rem; }
        .user-avatar { width: 30px; height: 30px; border-radius: 50%; background: #667eea; color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; margin-right: 0.75rem; font-size: 0.875rem; }
        .btn-save { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; color: white; padding: 0.75rem 2rem; font-weight: 600; border-radius: 8px; transition: all 0.3s; }
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4); color: white; }
        .btn-cancel { background: #6c757d; border: none; color: white; padding: 0.75rem 2rem; font-weight: 600; border-radius: 8px; transition: all 0.3s; }
        .btn-cancel:hover { background: #5a6268; color: white; }
        .form-hint { font-size: 0.875rem; color: #6c757d; margin-top: 0.25rem; }
        .change-info { background: #f8f9fa; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem; }
        .change-info h6 { color: #495057; margin-bottom: 0.5rem; }
        .change-item { display: flex; align-items: center; margin-bottom: 0.5rem; font-size: 0.875rem; }
        .change-item i { margin-right: 0.5rem; color: #6c757d; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layouts/main.php'; ?>

    <div class="container edit-task-container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/dashboard">Главная</a></li>
                <li class="breadcrumb-item"><a href="/tasks/kanban">Задачи</a></li>
                <li class="breadcrumb-item"><a href="/tasks/view/<?= $task['id'] ?>"><?= htmlspecialchars($task['title']) ?></a></li>
                <li class="breadcrumb-item active">Редактировать</li>
            </ol>
        </nav>

        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="bi bi-pencil-square me-2"></i>
                    Редактирование задачи
                </h4>
            </div>

            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Информация об изменениях -->
                <div class="change-info">
                    <h6>Информация о задаче</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="change-item">
                                <i class="bi bi-person-circle"></i>
                                Создал: <strong class="ms-1"><?= htmlspecialchars($task['creator_name']) ?></strong>
                            </div>
                            <div class="change-item">
                                <i class="bi bi-calendar-plus"></i>
                                <?= date('d.m.Y H:i', strtotime($task['created_at'])) ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <?php if ($task['updated_at'] != $task['created_at']): ?>
                            <div class="change-item">
                                <i class="bi bi-pencil"></i>
                                Последнее изменение: <?= date('d.m.Y H:i', strtotime($task['updated_at'])) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <form method="POST" action="/tasks/edit/<?= $task['id'] ?>" id="editTaskForm">
                    <!-- Основная информация -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="mb-3">Основная информация</h5>
                        </div>

                        <div class="col-md-8 mb-3">
                            <label for="title" class="form-label required-field">Название задачи</label>
                            <input type="text"
                                   class="form-control"
                                   id="title"
                                   name="title"
                                   value="<?= htmlspecialchars($task['title']) ?>"
                                   placeholder="Введите название задачи"
                                   required>
                            <div class="form-hint">Краткое и понятное название задачи</div>
                        </div>

                        <select class="form-select" id="status" name="status">
    <option value="backlog" <?= $task['status'] == 'backlog' ? 'selected' : '' ?>>
        📋 Очередь задач
    </option>
    <option value="todo" <?= $task['status'] == 'todo' ? 'selected' : '' ?>>
        📝 К выполнению
    </option>
    <option value="in_progress" <?= $task['status'] == 'in_progress' ? 'selected' : '' ?>>
        🔄 В работе
    </option>
    <option value="waiting_approval" <?= $task['status'] == 'waiting_approval' ? 'selected' : '' ?>>
        ⏳ Ожидает проверки
    </option>
    <option value="done" <?= $task['status'] == 'done' ? 'selected' : '' ?>>
        ✅ Выполнено
    </option>
</select>

                        <div class="col-12 mb-3">
                            <label for="description" class="form-label">Описание</label>
                            <textarea class="form-control"
                                      id="description"
                                      name="description"
                                      rows="4"
                                      placeholder="Подробное описание задачи..."><?= htmlspecialchars($task['description']) ?></textarea>
                            <div class="form-hint">Опишите, что нужно сделать, какие есть требования и ограничения</div>
                        </div>
                    </div>

                    <!-- Приоритет и дедлайн -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="mb-3">Приоритет и сроки</h5>
                        </div>

                        <div class="col-md-8 mb-3">
                            <label class="form-label required-field">Приоритет</label>
                            <div class="priority-select">
                                <div class="priority-option priority-low">
                                    <input type="radio"
                                           id="priority-low"
                                           name="priority"
                                           value="low"
                                           <?= $task['priority'] == 'low' ? 'checked' : '' ?>>
                                    <label class="priority-label" for="priority-low">
                                        <i class="bi bi-arrow-down-circle me-1"></i>
                                        Низкий
                                    </label>
                                </div>

                                <div class="priority-option priority-medium">
                                    <input type="radio"
                                           id="priority-medium"
                                           name="priority"
                                           value="medium"
                                           <?= $task['priority'] == 'medium' ? 'checked' : '' ?>>
                                    <label class="priority-label" for="priority-medium">
                                        <i class="bi bi-dash-circle me-1"></i>
                                        Средний
                                    </label>
                                </div>

                                <div class="priority-option priority-high">
                                    <input type="radio"
                                           id="priority-high"
                                           name="priority"
                                           value="high"
                                           <?= $task['priority'] == 'high' ? 'checked' : '' ?>>
                                    <label class="priority-label" for="priority-high">
                                        <i class="bi bi-arrow-up-circle me-1"></i>
                                        Высокий
                                    </label>
                                </div>

                                <div class="priority-option priority-urgent">
                                    <input type="radio"
                                           id="priority-urgent"
                                           name="priority"
                                           value="urgent"
                                           <?= $task['priority'] == 'urgent' ? 'checked' : '' ?>>
                                    <label class="priority-label" for="priority-urgent">
                                        <i class="bi bi-exclamation-circle me-1"></i>
                                        Срочный
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="deadline" class="form-label">Дедлайн</label>
                            <input type="text"
                                   class="form-control"
                                   id="deadline"
                                   name="deadline"
                                   value="<?= $task['deadline'] ?>"
                                   placeholder="Выберите дату">
                            <div class="form-hint">Когда задача должна быть выполнена</div>
                        </div>
                    </div>

                    <!-- Назначение -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="mb-3">Назначение</h5>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="assignees" class="form-label">
                                <i class="bi bi-people-fill me-1"></i>
                                Исполнители
                            </label>
                            <select class="form-select"
                                    id="assignees"
                                    name="assignees[]"
                                    multiple>
                                <?php 
                                $assigneeIds = array_column($task['assignees'], 'id');
                                foreach ($users as $user): 
                                ?>
                                    <option value="<?= $user['id'] ?>"
                                            <?= in_array($user['id'], $assigneeIds) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($user['name']) ?>
                                        <?php if ($user['department_name']): ?>
                                            (<?= htmlspecialchars($user['department_name']) ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-hint">Выберите одного или нескольких исполнителей</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="watchers" class="form-label">
                                <i class="bi bi-eye-fill me-1"></i>
                                Наблюдатели
                            </label>
                            <select class="form-select"
                                    id="watchers"
                                    name="watchers[]"
                                    multiple>
                                <?php 
                                $watcherIds = array_column($task['watchers'], 'id');
                                foreach ($users as $user): 
                                ?>
                                    <option value="<?= $user['id'] ?>"
                                            <?= in_array($user['id'], $watcherIds) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($user['name']) ?>
                                        <?php if ($user['department_name']): ?>
                                            (<?= htmlspecialchars($user['department_name']) ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-hint">Они будут получать уведомления об изменениях</div>
                        </div>
                    </div>

                    <!-- Кнопки -->
                    <div class="d-flex justify-content-between mt-4">
                        <a href="/tasks/view/<?= $task['id'] ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>
                            Назад к задаче
                        </a>
                        <div>
                            <a href="/tasks/view/<?= $task['id'] ?>" class="btn btn-cancel me-2">
                                <i class="bi bi-x-circle me-2"></i>
                                Отмена
                            </a>
                            <button type="submit" class="btn btn-save">
                                <i class="bi bi-check-circle me-2"></i>
                                Сохранить изменения
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/ru.js"></script>
    <script>
        // Инициализация Select2 для выбора пользователей
        $(document).ready(function() {
            $('#assignees, #watchers').select2({
                theme: 'bootstrap-5',
                placeholder: 'Выберите пользователей',
                allowClear: true,
                language: {
                    noResults: function() { return 'Пользователи не найдены'; },
                    searching: function() { return 'Поиск...'; },
                    removeAllItems: function() { return 'Удалить все'; }
                },
                templateResult: formatUser,
                templateSelection: formatUserSelection
            });
        });

        function formatUser(user) {
            if (!user.id) return user.text;
            const name = user.text.split('(')[0].trim();
            const initials = name.split(' ').map(n => n[0]).join('').toUpperCase();
            return $(`
                <div class="user-select-item">
                    <div class="user-avatar">${initials}</div>
                    <div>
                        <div>${user.text}</div>
                    </div>
                </div>
            `);
        }
        function formatUserSelection(user) {
            return user.text.split('(')[0].trim();
        }

        // Инициализация Flatpickr для выбора даты
        flatpickr("#deadline", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            minDate: "today",
            locale: "ru",
            time_24hr: true,
            defaultDate: "<?= $task['deadline'] ?>"
        });

        // Предупреждение при уходе со страницы с несохраненными изменениями
        const form = document.getElementById('editTaskForm');
        let hasChanges = false;
        form.addEventListener('input', function() { hasChanges = true; });
        form.addEventListener('change', function() { hasChanges = true; });
        window.addEventListener('beforeunload', function(e) {
            if (hasChanges) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
        form.addEventListener('submit', function() { hasChanges = false; });

        // Валидация формы
        form.addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            if (!title) {
                e.preventDefault();
                alert('Пожалуйста, введите название задачи');
                document.getElementById('title').focus();
                return false;
            }
        });
    </script>
</body>
</html>