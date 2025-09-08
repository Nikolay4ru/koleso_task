<?php
// views/shares/upload.php
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Загрузить файл и создать ссылку</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        
        .upload-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin: 2rem 0;
        }
        
        .upload-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 20px 20px 0 0;
            text-align: center;
        }
        
        .upload-body {
            padding: 2rem;
        }
        
        /* Drag & Drop Zone */
        .file-drop-zone {
            border: 2px dashed #dee2e6;
            border-radius: 15px;
            padding: 3rem 2rem;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .file-drop-zone:hover,
        .file-drop-zone.drag-over {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
            transform: scale(1.02);
        }
        
        .file-drop-zone.drag-over {
            border-style: solid;
            background: rgba(102, 126, 234, 0.1);
        }
        
        .drop-zone-icon {
            font-size: 4rem;
            color: #6c757d;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .file-drop-zone:hover .drop-zone-icon {
            color: #667eea;
            transform: scale(1.1);
        }
        
        .file-input {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        
        /* Progress Bar */
        .upload-progress {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 1rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: none;
        }
        
        .progress-bar-custom {
            height: 8px;
            border-radius: 4px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s ease;
        }
        
        /* File Preview */
        .file-preview {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 1rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: none;
        }
        
        .file-info-card {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
        
        .file-icon-large {
            font-size: 3rem;
            color: #667eea;
        }
        
        /* Share Settings */
        .share-settings {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .setting-group {
            margin-bottom: 1rem;
        }
        
        .setting-group:last-child {
            margin-bottom: 0;
        }
        
        /* Success Result */
        .share-result {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-top: 1rem;
            text-align: center;
            display: none;
        }
        
        .result-url {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            padding: 1rem;
            font-family: monospace;
            font-size: 1.1rem;
            color: white;
            margin: 1rem 0;
            word-break: break-all;
        }
        
        .btn-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            color: white;
        }
        
        .btn-success-custom {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        
        .btn-success-custom:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-in {
            animation: fadeInUp 0.5s ease-out;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .upload-container {
                margin: 1rem;
                border-radius: 15px;
            }
            
            .upload-header {
                padding: 1.5rem;
                border-radius: 15px 15px 0 0;
            }
            
            .upload-body {
                padding: 1.5rem;
            }
            
            .file-drop-zone {
                padding: 2rem 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                
                <!-- Навигация -->
                <div class="d-flex justify-content-between align-items-center mt-3 mb-4">
                    <a href="/shares/my" class="btn btn-light">
                        <i class="bi bi-arrow-left me-2"></i>
                        Мои ссылки
                    </a>
                    <a href="/tasks" class="btn btn-light">
                        <i class="bi bi-kanban me-2"></i>
                        К задачам
                    </a>
                </div>

                <!-- Основной контейнер -->
                <div class="upload-container">
                    
                    <!-- Заголовок -->
                    <div class="upload-header">
                        <h1 class="h3 mb-3">
                            <i class="bi bi-cloud-upload me-3"></i>
                            Загрузить файл и создать ссылку
                        </h1>
                        <p class="mb-0 opacity-75">
                            Загрузите документ и мгновенно получите ссылку для совместного доступа
                        </p>
                    </div>
                    
                    <!-- Тело страницы -->
                    <div class="upload-body">
                        
                        <!-- Зона загрузки -->
                        <div class="file-drop-zone" id="dropZone">
                            <input type="file" id="fileInput" class="file-input" 
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.xlsb,.ppt,.pptx,.txt,.csv,.zip,.rar,.jpg,.jpeg,.png,.gif,.webp">
                            
                            <i class="bi bi-cloud-upload drop-zone-icon"></i>
                            <h4 class="mb-3">Перетащите файл сюда</h4>
                            <p class="text-muted mb-3">или нажмите для выбора файла</p>
                            
                            <div class="supported-formats">
                                <small class="text-muted">
                                    Поддерживаются: Word, Excel (включая XLSB), PDF, PowerPoint, изображения, архивы<br>
                                    Максимальный размер: 10 MB
                                </small>
                            </div>
                        </div>
                        
                        <!-- Прогресс загрузки -->
                        <div class="upload-progress" id="uploadProgress">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold">Загрузка файла...</span>
                                <span id="progressPercent">0%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar-custom" id="progressBar" style="width: 0%"></div>
                            </div>
                            <small class="text-muted mt-2" id="progressStatus">Подготовка к загрузке...</small>
                        </div>
                        
                        <!-- Превью файла и настройки -->
                        <div class="file-preview" id="filePreview">
                            <div class="file-info-card" id="fileInfoCard">
                                <!-- Заполняется динамически -->
                            </div>
                            
                            <!-- Настройки ссылки -->
                            <div class="share-settings">
                                <h5 class="mb-3">
                                    <i class="bi bi-gear me-2"></i>
                                    Настройки ссылки
                                </h5>
                                
                                <form id="shareSettingsForm">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="setting-group">
                                                <label for="shareTitle" class="form-label">Название</label>
                                                <input type="text" class="form-control" id="shareTitle" 
                                                       name="title" placeholder="Красивое название">
                                            </div>
                                            
                                            <div class="setting-group">
                                                <label for="shareDescription" class="form-label">Описание</label>
                                                <textarea class="form-control" id="shareDescription" 
                                                          name="description" rows="2" 
                                                          placeholder="Краткое описание файла"></textarea>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="setting-group">
                                                <label for="sharePassword" class="form-label">Пароль</label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control" 
                                                           id="sharePassword" name="password" 
                                                           placeholder="Оставьте пустым для открытого доступа">
                                                    <button type="button" class="btn btn-outline-secondary" 
                                                            onclick="togglePasswordVisibility('sharePassword')">
                                                        <i class="bi bi-eye" id="sharePasswordIcon"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <div class="setting-group">
                                                <label for="shareExpires" class="form-label">Срок действия</label>
                                                <select class="form-select" id="shareExpires" name="expires_preset">
                                                    <option value="">Без ограничения</option>
                                                    <option value="1hour">1 час</option>
                                                    <option value="1day" selected>1 день</option>
                                                    <option value="1week">1 неделя</option>
                                                    <option value="1month">1 месяц</option>
                                                    <option value="custom">Указать дату</option>
                                                </select>
                                                <input type="datetime-local" class="form-control mt-2 d-none" 
                                                       id="shareExpiresCustom" name="expires_at">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="setting-group">
                                                <label for="maxDownloads" class="form-label">Лимит скачиваний</label>
                                                <select class="form-select" id="maxDownloads" name="max_downloads">
                                                    <option value="">Без ограничения</option>
                                                    <option value="1">1 раз</option>
                                                    <option value="5">5 раз</option>
                                                    <option value="10" selected>10 раз</option>
                                                    <option value="50">50 раз</option>
                                                    <option value="100">100 раз</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="setting-group">
                                                <div class="form-check form-switch mt-4">
                                                    <input class="form-check-input" type="checkbox" 
                                                           id="allowPreview" name="allow_preview" checked>
                                                    <label class="form-check-label" for="allowPreview">
                                                        <strong>Разрешить предпросмотр</strong><br>
                                                        <small class="text-muted">Показывать содержимое документов</small>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="text-center mt-4">
                                        <button type="button" class="btn btn-custom btn-lg" 
                                                id="createShareBtn" onclick="createShareLink()">
                                            <i class="bi bi-link-45deg me-2"></i>
                                            Создать ссылку
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Результат создания ссылки -->
                        <div class="share-result" id="shareResult">
                            <div class="animate-in">
                                <h4 class="mb-3">
                                    <i class="bi bi-check-circle-fill me-2"></i>
                                    Ссылка создана успешно!
                                </h4>
                                
                                <div class="result-url" id="resultUrl">
                                    <!-- URL заполняется динамически -->
                                </div>
                                
                                <div class="d-flex flex-wrap gap-2 justify-content-center">
                                    <button type="button" class="btn btn-success-custom" onclick="copyResultUrl()">
                                        <i class="bi bi-copy me-2"></i>Копировать ссылку
                                    </button>
                                    
                                    <a href="#" id="previewResultLink" target="_blank" 
                                       class="btn btn-success-custom text-decoration-none">
                                        <i class="bi bi-eye me-2"></i>Предпросмотр
                                    </a>
                                    
                                    <button type="button" class="btn btn-success-custom" onclick="showResultQR()">
                                        <i class="bi bi-qr-code me-2"></i>QR код
                                    </button>
                                    
                                    <a href="/shares/my" class="btn btn-success-custom text-decoration-none">
                                        <i class="bi bi-list-ul me-2"></i>Все ссылки
                                    </a>
                                </div>
                                
                                <hr class="my-4 opacity-25">
                                
                                <button type="button" class="btn btn-success-custom" 
                                        onclick="uploadAnother()">
                                    <i class="bi bi-plus-circle me-2"></i>
                                    Загрузить еще файл
                                </button>
                            </div>
                        </div>
                        
                    </div>
                </div>
                
            </div>
        </div>
    </div>

    <!-- QR Code Modal -->
    <div class="modal fade" id="qrModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-qr-code me-2"></i>
                        QR код для быстрого доступа
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="qrContainer"></div>
                    <p class="mt-3 text-muted">Отсканируйте код камерой телефона</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let uploadedFileId = null;
        let resultShareUrl = '';
        
        // Элементы DOM
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const uploadProgress = document.getElementById('uploadProgress');
        const filePreview = document.getElementById('filePreview');
        const shareResult = document.getElementById('shareResult');
        
        // Drag & Drop обработчики
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight(e) {
            dropZone.classList.add('drag-over');
        }
        
        function unhighlight(e) {
            dropZone.classList.remove('drag-over');
        }
        
        // Обработка drop события
        dropZone.addEventListener('drop', handleDrop, false);
        fileInput.addEventListener('change', handleFileSelect);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length > 0) {
                handleFile(files[0]);
            }
        }
        
        function handleFileSelect(e) {
            const files = e.target.files;
            if (files.length > 0) {
                handleFile(files[0]);
            }
        }
        
        // Обработка выбранного файла
        function handleFile(file) {
            // Валидация размера
            if (file.size > 200 * 1024 * 1024) { // 200MB
                alert('Файл слишком большой. Максимальный размер: 10 MB');
                return;
            }
            
            // Показываем прогресс
            showUploadProgress();
            
            // Загружаем файл
            uploadFile(file);
        }
        
        // Показать прогресс загрузки
        function showUploadProgress() {
            uploadProgress.style.display = 'block';
            filePreview.style.display = 'none';
            shareResult.style.display = 'none';
            
            // Анимация прогресса
            let progress = 0;
            const progressBar = document.getElementById('progressBar');
            const progressPercent = document.getElementById('progressPercent');
            const progressStatus = document.getElementById('progressStatus');
            
            progressStatus.textContent = 'Загрузка файла...';
            
            const interval = setInterval(() => {
                progress += Math.random() * 15;
                if (progress > 90) progress = 90;
                
                progressBar.style.width = progress + '%';
                progressPercent.textContent = Math.round(progress) + '%';
            }, 100);
            
            // Останавливаем анимацию через 2 секунды
            setTimeout(() => {
                clearInterval(interval);
            }, 2000);
        }
        
        // Загрузка файла
        async function uploadFile(file) {
            const formData = new FormData();
            formData.append('files[]', file);
            formData.append('type', 'share'); // Специальный тип для загруженных файлов
            
            try {
                const response = await fetch('/files/upload', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success && result.files && result.files.length > 0) {
                    const uploadedFile = result.files[0];
                    uploadedFileId = uploadedFile.id;
                    
                    // Завершаем прогресс
                    finishProgress();
                    
                    // Показываем превью и настройки
                    setTimeout(() => {
                        showFilePreview(uploadedFile);
                    }, 500);
                    
                } else {
                    throw new Error(result.error || 'Ошибка загрузки файла');
                }
                
            } catch (error) {
                console.error('Upload error:', error);
                alert('Ошибка загрузки файла: ' + error.message);
                hideProgress();
            }
        }
        
        // Завершить прогресс
        function finishProgress() {
            const progressBar = document.getElementById('progressBar');
            const progressPercent = document.getElementById('progressPercent');
            const progressStatus = document.getElementById('progressStatus');
            
            progressBar.style.width = '100%';
            progressPercent.textContent = '100%';
            progressStatus.textContent = 'Файл загружен успешно!';
            
            setTimeout(hideProgress, 1000);
        }
        
        // Скрыть прогресс
        function hideProgress() {
            uploadProgress.style.display = 'none';
        }
        
        // Показать превью файла
        function showFilePreview(fileData) {
            const fileInfoCard = document.getElementById('fileInfoCard');
            const shareTitle = document.getElementById('shareTitle');
            
            // Заполняем информацию о файле
            fileInfoCard.innerHTML = `
                <i class="${getFileIcon(fileData.name)} file-icon-large"></i>
                <div class="flex-grow-1">
                    <h6 class="mb-1">${fileData.name}</h6>
                    <div class="text-muted">
                        <small>${fileData.size} • ${getFileExtension(fileData.name).toUpperCase()} файл</small>
                    </div>
                </div>
                <div class="badge bg-success">Загружен</div>
            `;
            
            // Автозаполняем название
            shareTitle.value = fileData.name.replace(/\.[^/.]+$/, ''); // Убираем расширение
            
            filePreview.style.display = 'block';
            filePreview.classList.add('animate-in');
        }
        
        // Создать ссылку
        async function createShareLink() {
            if (!uploadedFileId) {
                alert('Сначала загрузите файл');
                return;
            }
            
            const form = document.getElementById('shareSettingsForm');
            const formData = new FormData(form);
            formData.append('file_id', uploadedFileId);
            
            // Обработка срока действия
            const expiresPreset = formData.get('expires_preset');
            if (expiresPreset && expiresPreset !== 'custom') {
                const expiresAt = calculateExpirationDate(expiresPreset);
                formData.set('expires_at', expiresAt);
            }
            formData.delete('expires_preset');
            
            const createBtn = document.getElementById('createShareBtn');
            createBtn.disabled = true;
            createBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Создание ссылки...';
            
            try {
                const response = await fetch('/shares/create', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    resultShareUrl = result.share_url;
                    showResult(result.share_url);
                } else {
                    throw new Error(result.error || 'Ошибка создания ссылки');
                }
                
            } catch (error) {
                console.error('Share creation error:', error);
                alert('Ошибка создания ссылки: ' + error.message);
            } finally {
                createBtn.disabled = false;
                createBtn.innerHTML = '<i class="bi bi-link-45deg me-2"></i>Создать ссылку';
            }
        }
        
        // Показать результат
        function showResult(shareUrl) {
            const resultUrl = document.getElementById('resultUrl');
            const previewLink = document.getElementById('previewResultLink');
            
            resultUrl.textContent = shareUrl;
            previewLink.href = shareUrl;
            
            // Скрываем превью и показываем результат
            filePreview.style.display = 'none';
            shareResult.style.display = 'block';
            
            // Плавно скроллим к результату
            shareResult.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
        }
        
        // Копировать результат
        async function copyResultUrl() {
            try {
                await navigator.clipboard.writeText(resultShareUrl);
                
                const button = event.target.closest('button');
                const originalContent = button.innerHTML;
                
                button.innerHTML = '<i class="bi bi-check me-2"></i>Скопировано!';
                button.classList.add('btn-light');
                
                setTimeout(() => {
                    button.innerHTML = originalContent;
                    button.classList.remove('btn-light');
                }, 2000);
                
            } catch (error) {
                // Fallback
                const textArea = document.createElement('textarea');
                textArea.value = resultShareUrl;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
            }
        }
        
        // Показать QR код результата
        function showResultQR() {
            const qrContainer = document.getElementById('qrContainer');
            qrContainer.innerHTML = `
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(resultShareUrl)}" 
                     alt="QR код" class="img-fluid border rounded p-2 bg-white">
                <div class="mt-3">
                    <small class="text-muted">${resultShareUrl}</small>
                </div>
            `;
            
            new bootstrap.Modal(document.getElementById('qrModal')).show();
        }
        
        // Загрузить еще файл
        function uploadAnother() {
            // Сбрасываем состояние
            uploadedFileId = null;
            resultShareUrl = '';
            
            // Скрываем результат
            shareResult.style.display = 'none';
            
            // Очищаем форму
            document.getElementById('shareSettingsForm').reset();
            document.getElementById('allowPreview').checked = true;
            document.getElementById('shareExpires').value = '1day';
            document.getElementById('maxDownloads').value = '10';
            
            // Возвращаемся к зоне загрузки
            dropZone.style.display = 'block';
            fileInput.value = '';
            
            // Плавно скроллим вверх
            dropZone.scrollIntoView({ behavior: 'smooth' });
        }
        
        // Переключение видимости пароля
        function togglePasswordVisibility(inputId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(inputId + 'Icon');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'bi bi-eye';
            }
        }
        
        // Обработка выбора срока действия
        document.getElementById('shareExpires').addEventListener('change', function() {
            const customField = document.getElementById('shareExpiresCustom');
            
            if (this.value === 'custom') {
                customField.classList.remove('d-none');
                customField.required = true;
            } else {
                customField.classList.add('d-none');
                customField.required = false;
                customField.value = '';
            }
        });
        
        // Вычисление даты истечения
        function calculateExpirationDate(preset) {
            const now = new Date();
            
            switch (preset) {
                case '1hour':
                    now.setHours(now.getHours() + 1);
                    break;
                case '1day':
                    now.setDate(now.getDate() + 1);
                    break;
                case '1week':
                    now.setDate(now.getDate() + 7);
                    break;
                case '1month':
                    now.setMonth(now.getMonth() + 1);
                    break;
            }
            
            return now.toISOString().slice(0, 16);
        }
        
        // Получить иконку файла
        function getFileIcon(filename) {
            const ext = getFileExtension(filename).toLowerCase();
            
            const icons = {
                // Word
                'doc': 'bi-file-earmark-word',
                'docx': 'bi-file-earmark-word',
                
                // Excel
                'xls': 'bi-file-earmark-excel',
                'xlsx': 'bi-file-earmark-excel',
                
                // PowerPoint
                'ppt': 'bi-file-earmark-ppt',
                'pptx': 'bi-file-earmark-ppt',
                
                // PDF
                'pdf': 'bi-file-earmark-pdf',
                
                // Images
                'jpg': 'bi-file-earmark-image',
                'jpeg': 'bi-file-earmark-image',
                'png': 'bi-file-earmark-image',
                'gif': 'bi-file-earmark-image',
                'webp': 'bi-file-earmark-image',
                
                // Text
                'txt': 'bi-file-earmark-text',
                'csv': 'bi-file-earmark-text',
                
                // Archive
                'zip': 'bi-file-earmark-zip',
                'rar': 'bi-file-earmark-zip',
                '7z': 'bi-file-earmark-zip',
                
                // Video
                'mp4': 'bi-file-earmark-play',
                'avi': 'bi-file-earmark-play',
                'mov': 'bi-file-earmark-play',
                
                // Audio
                'mp3': 'bi-file-earmark-music',
                'wav': 'bi-file-earmark-music',
                'ogg': 'bi-file-earmark-music'
            };
            
            return icons[ext] || 'bi-file-earmark';
        }
        
        // Получить расширение файла
        function getFileExtension(filename) {
            return filename.split('.').pop() || '';
        }
        
        // Анимация при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            // Добавляем анимацию появления
            document.querySelector('.upload-container').classList.add('animate-in');
            
            // Фокус на зону загрузки
            dropZone.focus();
        });
        
        // Обработка Enter на зоне загрузки
        dropZone.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                fileInput.click();
            }
        });
        
        // Предотвращение случайного ухода со страницы при загрузке
        window.addEventListener('beforeunload', function(e) {
            if (uploadProgress.style.display === 'block') {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    </script>
</body>
</html>