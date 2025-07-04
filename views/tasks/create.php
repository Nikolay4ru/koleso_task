<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создание задачи - Система управления задачами</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f8f9fa;
        }
        .form-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            padding: 2.5rem;
        }
        .form-header {
            margin-bottom: 2rem;
        }
        .form-title {
            font-size: 1.75rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        .form-subtitle {
            color: #6c757d;
        }
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            transition: all 0.3s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .form-hint {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
        .btn-primary {
            background: #667eea;
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            background: #5a67d8;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        .btn-secondary {
            background: #e9ecef;
            border: none;
            color: #495057;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
        }
        .form-section {
            margin-bottom: 2rem;
        }
        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e9ecef;
        }
        .priority-option {
            padding: 0.5rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }
        .priority-option:hover {
            border-color: #667eea;
        }
        .priority-option.selected {
            border-color: #667eea;
            background: #f0f4ff;
        }
        .status-option {
            padding: 0.5rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }
        .status-option:hover {
            border-color: #667eea;
        }
        .status-option.selected {
            border-color: #667eea;
            background: #f0f4ff;
        }
        
        /* Стили для загрузки файлов */
        .file-uploader {
            border: 2px dashed #dee2e6;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s;
            cursor: pointer;
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
        
        .file-input {
            display: none;
        }
        
        .file-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .file-item {
            position: relative;
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 0.75rem;
            width: 150px;
            transition: all 0.2s;
        }
        
        .file-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .file-item.uploading {
            opacity: 0.6;
        }
        
        .file-thumbnail {
            width: 100%;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 0.5rem;
        }
        
        .file-icon {
            width: 100%;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border-radius: 4px;
            margin-bottom: 0.5rem;
        }
        
        .file-icon i {
            font-size: 3rem;
            color: #6c757d;
        }
        
        .file-name {
            font-size: 0.875rem;
            font-weight: 500;
            color: #495057;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .file-size {
            font-size: 0.75rem;
            color: #6c757d;
        }
        
        .file-remove {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: rgba(255,255,255,0.9);
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            opacity: 0;
        }
        
        .file-item:hover .file-remove {
            opacity: 1;
        }
        
        .file-remove:hover {
            background: #dc3545;
            color: white;
        }
        
        .file-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: #e9ecef;
            border-radius: 0 0 8px 8px;
            overflow: hidden;
        }
        
        .file-progress-bar {
            height: 100%;
            background: #667eea;
            transition: width 0.3s;
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
                <li class="breadcrumb-item active">Создание задачи</li>
            </ol>
        </nav>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <form method="POST" action="/tasks/create" class="form-card">
                    <div class="form-header">
                        <h1 class="form-title">Создание новой задачи</h1>
                        <p class="form-subtitle">Заполните информацию о задаче</p>
                    </div>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= $error ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Основная информация -->
                    <div class="form-section">
                        <h5 class="section-title">Основная информация</h5>
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Название задачи <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="title" 
                                   name="title" 
                                   value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>"
                                   placeholder="Введите название задачи"
                                   required>
                            <div class="form-hint">Краткое и понятное название, которое отражает суть задачи</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Описание</label>
                            <textarea class="form-control" 
                                      id="description" 
                                      name="description" 
                                      rows="4"
                                      placeholder="Подробное описание задачи..."><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                            <div class="form-hint">Опишите детали задачи, требования и ожидаемый результат</div>
                        </div>
                    </div>
                    
                    <!-- Статус и приоритет -->
                    <div class="form-section">
                        <h5 class="section-title">Статус и приоритет</h5>
                        
                        <div class="row">
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
        <option value="waiting_approval" <?= (isset($_POST['status']) && $_POST['status'] == 'waiting_approval') ? 'selected' : '' ?>>
            ⏳ Ожидает проверки
        </option>
        <option value="done" <?= (isset($_POST['status']) && $_POST['status'] == 'done') ? 'selected' : '' ?>>
            ✅ Выполнено
        </option>
    </select>
</div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Приоритет</label>
                                <div class="d-flex gap-2">
                                    <div class="priority-option flex-fill" onclick="selectPriority('low')">
                                        <input type="radio" name="priority" value="low" id="priority-low" class="d-none" <?= (!isset($_POST['priority']) || $_POST['priority'] == 'low') ? 'checked' : '' ?>>
                                        <label for="priority-low" class="d-block m-0">
                                            <i class="bi bi-arrow-down-circle"></i> Низкий
                                        </label>
                                    </div>
                                    <div class="priority-option flex-fill" onclick="selectPriority('medium')">
                                        <input type="radio" name="priority" value="medium" id="priority-medium" class="d-none" <?= (isset($_POST['priority']) && $_POST['priority'] == 'medium') ? 'checked' : '' ?>>
                                        <label for="priority-medium" class="d-block m-0">
                                            <i class="bi bi-dash-circle"></i> Средний
                                        </label>
                                    </div>
                                    <div class="priority-option flex-fill" onclick="selectPriority('high')">
                                        <input type="radio" name="priority" value="high" id="priority-high" class="d-none" <?= (isset($_POST['priority']) && $_POST['priority'] == 'high') ? 'checked' : '' ?>>
                                        <label for="priority-high" class="d-block m-0">
                                            <i class="bi bi-arrow-up-circle"></i> Высокий
                                        </label>
                                    </div>
                                    <div class="priority-option flex-fill" onclick="selectPriority('urgent')">
                                        <input type="radio" name="priority" value="urgent" id="priority-urgent" class="d-none" <?= (isset($_POST['priority']) && $_POST['priority'] == 'urgent') ? 'checked' : '' ?>>
                                        <label for="priority-urgent" class="d-block m-0">
                                            <i class="bi bi-exclamation-circle"></i> Срочный
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="deadline" class="form-label">Дедлайн</label>
                            <input type="datetime-local" 
                                   class="form-control" 
                                   id="deadline" 
                                   name="deadline"
                                   value="<?= isset($_POST['deadline']) ? htmlspecialchars($_POST['deadline']) : '' ?>">
                            <div class="form-hint">Установите дату и время, когда задача должна быть выполнена</div>
                        </div>
                    </div>
                    
                    <!-- Назначение -->
                    <div class="form-section">
                        <h5 class="section-title">Назначение</h5>
                        
                        <div class="row">
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
                                <div class="form-hint">Выберите сотрудников, ответственных за выполнение</div>
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
                                <div class="form-hint">Они будут получать уведомления об изменениях задачи</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Прикрепленные файлы -->
                    <div class="form-section">
                        <h5 class="section-title">Прикрепленные файлы</h5>
                        
                        <!-- Область загрузки файлов -->
                        <div class="file-uploader" id="fileUploader">
                            <input type="file" class="file-input" id="fileInput" multiple accept="*/*">
                            <i class="bi bi-cloud-upload" style="font-size: 3rem; color: #6c757d;"></i>
                            <p class="mb-0 mt-2">Перетащите файлы сюда или нажмите для выбора</p>
                            <small class="text-muted">Максимальный размер файла: 10 MB</small>
                        </div>
                        
                        <!-- Предпросмотр загруженных файлов -->
                        <div class="file-preview" id="filePreview"></div>
                        
                        <!-- Скрытое поле для хранения ID загруженных файлов -->
                        <input type="hidden" name="uploaded_files" id="uploadedFiles" value="[]">
                    </div>
                    
                    <!-- Кнопки действий -->
                    <div class="d-flex justify-content-between">
                        <a href="/tasks/kanban" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Отмена
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Создать задачу
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
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
                    }
                }
            });
        });
        
        // Выбор приоритета
        function selectPriority(priority) {
            document.querySelectorAll('.priority-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            document.getElementById('priority-' + priority).checked = true;
            document.getElementById('priority-' + priority).closest('.priority-option').classList.add('selected');
        }
        
        // Инициализация выбранного приоритета
        document.addEventListener('DOMContentLoaded', function() {
            const selectedPriority = document.querySelector('input[name="priority"]:checked');
            if (selectedPriority) {
                selectedPriority.closest('.priority-option').classList.add('selected');
            }
        });
        
        // Загрузка файлов
        let uploadedFileIds = [];
        const fileUploader = document.getElementById('fileUploader');
        const fileInput = document.getElementById('fileInput');
        const filePreview = document.getElementById('filePreview');
        const uploadedFilesInput = document.getElementById('uploadedFiles');
        
        fileUploader.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', handleFileSelect);
        
        // Drag and Drop
        fileUploader.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileUploader.classList.add('drag-over');
        });
        
        fileUploader.addEventListener('dragleave', () => {
            fileUploader.classList.remove('drag-over');
        });
        
        fileUploader.addEventListener('drop', (e) => {
            e.preventDefault();
            fileUploader.classList.remove('drag-over');
            handleFiles(e.dataTransfer.files);
        });
        
        function handleFileSelect(e) {
            handleFiles(e.target.files);
        }
        
        function handleFiles(files) {
            Array.from(files).forEach(file => {
                if (file.size > 10 * 1024 * 1024) {
                    alert(`Файл ${file.name} слишком большой. Максимальный размер: 10 MB`);
                    return;
                }
                uploadFile(file);
            });
        }
        
        function uploadFile(file) {
            const tempId = 'temp_' + Date.now() + '_' + Math.random();
            const fileItem = createFileItem(file, tempId);
            filePreview.appendChild(fileItem);
            
            const formData = new FormData();
            formData.append('files[]', file);
            formData.append('type', 'task');
            
            fetch('/file/upload', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.files.length > 0) {
                    const uploadedFile = data.files[0];
                    uploadedFileIds.push(uploadedFile.id);
                    uploadedFilesInput.value = JSON.stringify(uploadedFileIds);
                    
                    // Обновляем элемент файла
                    updateFileItem(tempId, uploadedFile);
                } else {
                    removeFileItem(tempId);
                    alert(data.errors ? data.errors.join('\n') : 'Ошибка при загрузке файла');
                }
            })
            .catch(error => {
                removeFileItem(tempId);
                alert('Ошибка при загрузке файла');
                console.error('Upload error:', error);
            });
        }
        
        function createFileItem(file, tempId) {
            const div = document.createElement('div');
            div.className = 'file-item uploading';
            div.id = `file-${tempId}`;
            
            if (file.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.className = 'file-thumbnail';
                
                const reader = new FileReader();
                reader.onload = (e) => {
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
                
                div.appendChild(img);
            } else {
                const iconDiv = document.createElement('div');
                iconDiv.className = 'file-icon';
                iconDiv.innerHTML = `<i class="${getFileIcon(file.type)}"></i>`;
                div.appendChild(iconDiv);
            }
            
            const name = document.createElement('div');
            name.className = 'file-name';
            name.textContent = file.name;
            name.title = file.name;
            div.appendChild(name);
            
            const size = document.createElement('div');
            size.className = 'file-size';
            size.textContent = formatFileSize(file.size);
            div.appendChild(size);
            
            const progress = document.createElement('div');
            progress.className = 'file-progress';
            progress.innerHTML = '<div class="file-progress-bar" style="width: 0%"></div>';
            div.appendChild(progress);
            
            return div;
        }
        
        function updateFileItem(tempId, uploadedFile) {
            const fileItem = document.getElementById(`file-${tempId}`);
            if (!fileItem) return;
            
            fileItem.classList.remove('uploading');
            fileItem.id = `file-${uploadedFile.id}`;
            
            const removeBtn = document.createElement('button');
            removeBtn.className = 'file-remove';
            removeBtn.innerHTML = '<i class="bi bi-x"></i>';
            removeBtn.onclick = () => removeFile(uploadedFile.id);
            fileItem.appendChild(removeBtn);
            
            const progress = fileItem.querySelector('.file-progress');
            if (progress) progress.remove();
        }
        
        function removeFile(fileId) {
            uploadedFileIds = uploadedFileIds.filter(id => id !== fileId);
            uploadedFilesInput.value = JSON.stringify(uploadedFileIds);
            removeFileItem(fileId);
            
            fetch(`/file/delete/${fileId}`, {
                method: 'POST'
            }).catch(error => console.error('Delete error:', error));
        }
        
        function removeFileItem(id) {
            const fileItem = document.getElementById(`file-${id}`);
            if (fileItem) {
                fileItem.style.opacity = '0';
                setTimeout(() => fileItem.remove(), 300);
            }
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
        
        function formatFileSize(bytes) {
            if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2) + ' GB';
            if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
            if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
            return bytes + ' bytes';
        }
    </script>
</body>
</html>