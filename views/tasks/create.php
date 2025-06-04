<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создать задачу - Система управления задачами</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .create-task-container {
            max-width: 800px;
            margin: 2rem auto;
        }
        .card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border-radius: 10px;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 1.5rem;
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
        .required-field::after {
            content: " *";
            color: #dc3545;
        }
        .priority-select {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .priority-option {
            position: relative;
            flex: 1;
            min-width: 120px;
        }
        .priority-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        .priority-label {
            display: block;
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
        }
        .priority-option input[type="radio"]:checked + .priority-label {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        .priority-low .priority-label { color: #28a745; }
        .priority-low input[type="radio"]:checked + .priority-label { 
            background: #28a745; 
            border-color: #28a745;
        }
        .priority-medium .priority-label { color: #17a2b8; }
        .priority-medium input[type="radio"]:checked + .priority-label { 
            background: #17a2b8; 
            border-color: #17a2b8;
        }
        .priority-high .priority-label { color: #ffc107; }
        .priority-high input[type="radio"]:checked + .priority-label { 
            background: #ffc107; 
            border-color: #ffc107;
        }
        .priority-urgent .priority-label { color: #dc3545; }
        .priority-urgent input[type="radio"]:checked + .priority-label { 
            background: #dc3545; 
            border-color: #dc3545;
        }
        .user-select-item {
            display: flex;
            align-items: center;
            padding: 0.5rem;
        }
        .user-avatar {
            width: 30px;
            height: 30px;
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
        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .btn-cancel {
            background: #6c757d;
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .btn-cancel:hover {
            background: #5a6268;
            color: white;
        }
        .form-hint {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
        .select2-container--bootstrap-5 .select2-selection {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 0.375rem 0.75rem;
            min-height: calc(1.5em + 1.5rem + 2px);
        }
        .select2-container--bootstrap-5.select2-container--focus .select2-selection {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .preview-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layouts/main.php'; ?>
    
    <div class="container create-task-container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/dashboard">Главная</a></li>
                <li class="breadcrumb-item"><a href="/tasks/kanban">Задачи</a></li>
                <li class="breadcrumb-item active">Создать задачу</li>
            </ol>
        </nav>
        
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="bi bi-plus-circle me-2"></i>
                    Создание новой задачи
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
                
                <form method="POST" action="/tasks/create" id="createTaskForm">
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
                                   value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>"
                                   placeholder="Введите название задачи"
                                   required>
                            <div class="form-hint">Краткое и понятное название задачи</div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="status" class="form-label">Статус</label>
                            <select class="form-select" id="status" name="status">
                                <option value="backlog" <?= (isset($_POST['status']) && $_POST['status'] == 'backlog') ? 'selected' : '' ?>>
                                    📋 Очередь задач
                                </option>
                                <option value="todo" <?= (isset($_POST['status']) && $_POST['status'] == 'todo') ? 'selected' : '' ?>>
                                    📝 К выполнению
                                </option>
                                <option value="in_progress" <?= (isset($_POST['status']) && $_POST['status'] == 'in_progress') ? 'selected' : '' ?>>
                                    🔄 В работе
                                </option>
                            </select>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label for="description" class="form-label">Описание</label>
                            <textarea class="form-control" 
                                      id="description" 
                                      name="description" 
                                      rows="4"
                                      placeholder="Подробное описание задачи..."><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
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
                                           <?= (!isset($_POST['priority']) || $_POST['priority'] == 'low') ? 'checked' : '' ?>>
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
                                           <?= (isset($_POST['priority']) && $_POST['priority'] == 'medium') ? 'checked' : '' ?>>
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
                                           <?= (isset($_POST['priority']) && $_POST['priority'] == 'high') ? 'checked' : '' ?>>
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
                                           <?= (isset($_POST['priority']) && $_POST['priority'] == 'urgent') ? 'checked' : '' ?>>
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
                                   value="<?= isset($_POST['deadline']) ? htmlspecialchars($_POST['deadline']) : '' ?>"
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
                                <?php if (isset($users)): ?>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?= $user['id'] ?>"
                                                <?= (isset($_POST['assignees']) && in_array($user['id'], $_POST['assignees'])) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($user['name']) ?>
                                            <?php if ($user['department_name']): ?>
                                                (<?= htmlspecialchars($user['department_name']) ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
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
                                <?php if (isset($users)): ?>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?= $user['id'] ?>"
                                                <?= (isset($_POST['watchers']) && in_array($user['id'], $_POST['watchers'])) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($user['name']) ?>
                                            <?php if ($user['department_name']): ?>
                                                (<?= htmlspecialchars($user['department_name']) ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <div class="form-hint">Они будут получать уведомления об изменениях</div>
                        </div>
                    </div>
                    
                    <!-- Предпросмотр -->
                    <div class="preview-section" id="taskPreview" style="display: none;">
                        <h5 class="mb-3">Предпросмотр задачи</h5>
                        <div class="row">
                            <div class="col-md-8">
                                <h6 id="previewTitle">Название задачи</h6>
                                <p id="previewDescription" class="text-muted">Описание задачи</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <span id="previewPriority" class="badge">Приоритет</span>
                                <p id="previewDeadline" class="text-muted small mt-2">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    <span></span>
                                </p>
                            </div>
                        </div>
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="bi bi-people me-1"></i>
                                Исполнители: <span id="previewAssignees">не назначены</span>
                            </small>
                            <br>
                            <small class="text-muted">
                                <i class="bi bi-eye me-1"></i>
                                Наблюдатели: <span id="previewWatchers">не назначены</span>
                            </small>
                        </div>
                    </div>
                    
                    <!-- Кнопки -->
                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" 
                                class="btn btn-outline-secondary" 
                                id="previewButton">
                            <i class="bi bi-eye me-2"></i>
                            Предпросмотр
                        </button>
                        
                        <div>
                            <a href="/tasks/kanban" class="btn btn-cancel me-2">
                                <i class="bi bi-x-circle me-2"></i>
                                Отмена
                            </a>
                            <button type="submit" class="btn btn-submit">
                                <i class="bi bi-check-circle me-2"></i>
                                Создать задачу
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
                    noResults: function() {
                        return 'Пользователи не найдены';
                    },
                    searching: function() {
                        return 'Поиск...';
                    },
                    removeAllItems: function() {
                        return 'Удалить все';
                    }
                },
                templateResult: formatUser,
                templateSelection: formatUserSelection
            });
        });
        
        // Форматирование пользователя в выпадающем списке
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
            time_24hr: true
        });
        
        // Предпросмотр задачи
        document.getElementById('previewButton').addEventListener('click', function() {
            const preview = document.getElementById('taskPreview');
            const title = document.getElementById('title').value;
            const description = document.getElementById('description').value;
            const priority = document.querySelector('input[name="priority"]:checked').value;
            const deadline = document.getElementById('deadline').value;
            
            // Обновляем предпросмотр
            document.getElementById('previewTitle').textContent = title || 'Без названия';
            document.getElementById('previewDescription').textContent = description || 'Без описания';
            
            // Приоритет
            const priorityBadge = document.getElementById('previewPriority');
            const priorityMap = {
                'low': { text: 'Низкий', class: 'bg-success' },
                'medium': { text: 'Средний', class: 'bg-info' },
                'high': { text: 'Высокий', class: 'bg-warning' },
                'urgent': { text: 'Срочный', class: 'bg-danger' }
            };
            
            priorityBadge.textContent = priorityMap[priority].text;
            priorityBadge.className = 'badge ' + priorityMap[priority].class;
            
            // Дедлайн
            const deadlineElement = document.querySelector('#previewDeadline span');
            deadlineElement.textContent = deadline || 'Не установлен';
            
            // Исполнители и наблюдатели
            const assignees = $('#assignees').select2('data');
            const watchers = $('#watchers').select2('data');
            
            document.getElementById('previewAssignees').textContent = 
                assignees.length > 0 
                    ? assignees.map(u => u.text.split('(')[0].trim()).join(', ')
                    : 'не назначены';
                    
            document.getElementById('previewWatchers').textContent = 
                watchers.length > 0 
                    ? watchers.map(u => u.text.split('(')[0].trim()).join(', ')
                    : 'не назначены';
            
            // Показываем/скрываем предпросмотр
            preview.style.display = preview.style.display === 'none' ? 'block' : 'none';
            
            // Меняем текст кнопки
            this.innerHTML = preview.style.display === 'none' 
                ? '<i class="bi bi-eye me-2"></i>Предпросмотр'
                : '<i class="bi bi-eye-slash me-2"></i>Скрыть предпросмотр';
        });
        
        // Валидация формы
        document.getElementById('createTaskForm').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            
            if (!title) {
                e.preventDefault();
                alert('Пожалуйста, введите название задачи');
                document.getElementById('title').focus();
                return false;
            }
            
            // Проверяем, выбран ли хотя бы один исполнитель
            const assignees = $('#assignees').select2('data');
            if (assignees.length === 0 && confirm('Вы не назначили исполнителей. Продолжить?') === false) {
                e.preventDefault();
                return false;
            }
        });
        
        // Автосохранение в localStorage
        const formInputs = ['title', 'description', 'status', 'deadline'];
        const formKey = 'taskFormDraft';
        
        // Восстановление данных
        const savedData = localStorage.getItem(formKey);
        if (savedData) {
            const data = JSON.parse(savedData);
            formInputs.forEach(field => {
                if (data[field] && document.getElementById(field)) {
                    document.getElementById(field).value = data[field];
                }
            });
        }
        
        // Сохранение данных при изменении
        formInputs.forEach(field => {
            const element = document.getElementById(field);
            if (element) {
                element.addEventListener('input', function() {
                    const data = {};
                    formInputs.forEach(f => {
                        const el = document.getElementById(f);
                        if (el) data[f] = el.value;
                    });
                    localStorage.setItem(formKey, JSON.stringify(data));
                });
            }
        });
        
        // Очистка localStorage после успешной отправки
        document.getElementById('createTaskForm').addEventListener('submit', function() {
            localStorage.removeItem(formKey);
        });
    </script>
</body>
</html>