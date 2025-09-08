<?php
// views/shares/view.php - исправленная версия
?>

<?php
if (class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
    echo "PhpSpreadsheet установлен!";
} else {
    echo "PhpSpreadsheet не найден";
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($share['title'] ?: $share['original_name']) ?> - Общий доступ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .file-preview-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin: 2rem 0;
            padding: 2rem;
        }
        .file-header {
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
        .file-icon {
            font-size: 3rem;
            color: #6c757d;
        }
        .document-preview {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1.5rem;
            max-height: 400px;
            overflow-y: auto;
            white-space: pre-wrap;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            line-height: 1.5;
        }
        .image-preview {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .download-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            color: white;
            padding: 2rem;
            margin: 2rem 0;
            text-align: center;
        }
        .btn-download {
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.3);
            color: white;
            font-weight: 600;
            padding: 12px 30px;
            border-radius: 50px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        .btn-download:hover {
            background: rgba(255,255,255,0.3);
            border-color: rgba(255,255,255,0.5);
            color: white;
            transform: translateY(-2px);
        }
        .share-info {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f1f3f4;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .preview-loading {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        .xlsb-preview-header {
            background: linear-gradient(135deg, #e8f5e8 0%, #d4edda 100%);
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #28a745;
        }
        .table-responsive {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 6px;
        }
        .table th, .table td {
            border-color: #e9ecef;
            font-size: 0.85rem;
            white-space: nowrap;
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .table thead th {
            position: sticky;
            top: 0;
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
        .xlsb-info-badge {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                
                <!-- Заголовок -->
                <div class="text-center mb-4">
                    <h1 class="h2 mb-2"><?= htmlspecialchars($share['title'] ?: $share['original_name']) ?></h1>
                    <?php if ($share['description']): ?>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($share['description'])) ?></p>
                    <?php endif; ?>
                </div>

                <!-- Информация о файле -->
                <div class="share-info">
                    <div class="info-item">
                        <span><i class="bi bi-file-earmark me-2"></i>Имя файла</span>
                        <strong><?= htmlspecialchars($share['original_name']) ?></strong>
                    </div>
                    <div class="info-item">
                        <span><i class="bi bi-hdd me-2"></i>Размер</span>
                        <strong><?= $formatFileSize($share['size']) ?></strong>
                    </div>
                    <div class="info-item">
                        <span><i class="bi bi-file-earmark me-2"></i>Тип файла</span>
                        <strong>
                            <?php 
                            $extension = strtoupper(pathinfo($share['original_name'], PATHINFO_EXTENSION));
                            $fileTypeNames = [
                                'XLSB' => 'Excel Binary Workbook (.xlsb)',
                                'XLSX' => 'Excel Workbook (.xlsx)', 
                                'XLS' => 'Excel 97-2003 (.xls)',
                                'DOCX' => 'Word Document (.docx)',
                                'DOC' => 'Word 97-2003 (.doc)',
                                'PDF' => 'PDF Document',
                                'PPTX' => 'PowerPoint Presentation (.pptx)',
                                'PPT' => 'PowerPoint 97-2003 (.ppt)'
                            ];
                            
                            echo $fileTypeNames[$extension] ?? $extension . ' файл';
                            ?>
                        </strong>
                    </div>
                    <div class="info-item">
                        <span><i class="bi bi-person me-2"></i>Поделился</span>
                        <strong><?= htmlspecialchars($share['creator_name']) ?></strong>
                    </div>
                    <?php if ($share['expires_at']): ?>
                    <div class="info-item">
                        <span><i class="bi bi-clock me-2"></i>Действует до</span>
                        <strong><?= date('d.m.Y H:i', strtotime($share['expires_at'])) ?></strong>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Специальное уведомление для XLSB файлов -->
                    <?php if (strtolower(pathinfo($share['original_name'], PATHINFO_EXTENSION)) === 'xlsb'): ?>
                    <div class="info-item" style="background: linear-gradient(135deg, #1e7e34 0%, #28a745 100%); color: white; padding: 1rem; border-radius: 8px; margin-top: 1rem;">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-file-earmark-excel fs-4 me-3"></i>
                            <div>
                                <strong>Excel Binary Format</strong><br>
                                <small class="opacity-75">
                                    Оптимизированный формат для больших таблиц с быстрой обработкой данных
                                </small>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Предпросмотр -->
                <div class="file-preview-container">
                    <div class="file-header">
                        <div class="d-flex align-items-center">
                            <i class="<?= $getFileIcon($share['mime_type']) ?> file-icon me-3"></i>
                            <div>
                                <h5 class="mb-1"><?= htmlspecialchars($share['original_name']) ?></h5>
                                <small class="text-muted"><?= strtoupper(pathinfo($share['original_name'], PATHINFO_EXTENSION)) ?> файл</small>
                            </div>
                        </div>
                    </div>

                    <?php if ($share['allow_preview']): ?>
                        <!-- Предпросмотр изображения -->
                        <?php if ($share['is_image']): ?>
                            <div class="text-center">
                                <img src="/s/<?= $share['short_code'] ?>/preview" 
                                     alt="<?= htmlspecialchars($share['original_name']) ?>" 
                                     class="image-preview">
                            </div>
                        
                        <!-- Предпросмотр документа -->
                        <?php elseif ($share['is_document'] && $preview): ?>
                            <div class="document-preview" id="documentPreview">
                                <?php 
                                $extension = strtolower(pathinfo($share['original_name'], PATHINFO_EXTENSION));
                                
                                if ($extension === 'xlsb'): ?>
                                    <!-- Специальный предпросмотр для XLSB -->
                                    <div class="xlsb-preview-header mb-3">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-file-earmark-excel text-success fs-4 me-2"></i>
                                            <div>
                                                <strong>Excel Binary Workbook Preview</strong>
                                                <small class="text-muted d-block">Первые строки из таблицы</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Преобразуем табличные данные в HTML таблицу для XLSB -->
                                    <?php 
                                    $lines = explode("\n", trim($preview));
                                    if (count($lines) > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped">
                                                <?php foreach (array_slice($lines, 0, 20) as $index => $line): ?>
                                                    <?php if (trim($line)): ?>
                                                        <tr>
                                                            <?php 
                                                            $cells = explode("\t", $line);
                                                            foreach (array_slice($cells, 0, 10) as $cell): // Максимум 10 колонок ?>
                                                                <td><?= htmlspecialchars(trim($cell)) ?></td>
                                                            <?php endforeach; ?>
                                                        </tr>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            <i class="bi bi-info-circle me-2"></i>
                                            XLSB файл загружен, но содержимое пока обрабатывается...
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <!-- Обычный предпросмотр для других документов -->
                                    <?= htmlspecialchars($preview) ?>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (strlen($preview) >= 4990): ?>
                                <small class="text-muted mt-2 d-block">
                                    <i class="bi bi-info-circle me-1"></i>
                                    <?php if ($extension === 'xlsb'): ?>
                                        Показаны первые строки таблицы. Скачайте файл для работы с полными данными.
                                    <?php else: ?>
                                        Показана часть содержимого. Скачайте файл для полного просмотра.
                                    <?php endif; ?>
                                </small>
                            <?php endif; ?>
                        
                        <!-- Если предпросмотр недоступен -->
                        <?php else: ?>
                            <div class="preview-loading">
                                <i class="bi bi-file-earmark display-4 text-muted mb-3"></i>
                                <p>Предпросмотр недоступен для этого типа файла</p>
                                <small class="text-muted">Скачайте файл для просмотра</small>
                            </div>
                        <?php endif; ?>
                    
                    <?php else: ?>
                        <div class="preview-loading">
                            <i class="bi bi-eye-slash display-4 text-muted mb-3"></i>
                            <p>Предпросмотр отключен</p>
                            <small class="text-muted">Скачайте файл для просмотра</small>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Скачивание -->
                <div class="download-section">
                    <?php $extension = strtolower(pathinfo($share['original_name'], PATHINFO_EXTENSION)); ?>
                    
                    <h4 class="mb-3">
                        <i class="bi bi-download me-2"></i>
                        Скачать файл
                        <?php if ($extension === 'xlsb'): ?>
                            <span class="badge bg-light text-dark ms-2">XLSB</span>
                        <?php endif; ?>
                    </h4>
                    
                    <p class="mb-4 opacity-75">
                        <?= $formatFileSize($share['size']) ?> • 
                        <?= strtoupper(pathinfo($share['original_name'], PATHINFO_EXTENSION)) ?> файл
                        
                        <?php if ($extension === 'xlsb'): ?>
                            <br><small>Оптимизированный формат Excel для быстрой обработки данных</small>
                        <?php endif; ?>
                    </p>
                    
                    <a href="/s/<?= $share['short_code'] ?>/download" class="btn-download">
                        <i class="bi bi-download"></i>
                        Скачать <?= htmlspecialchars($share['original_name']) ?>
                    </a>
                    
                    <?php if ($extension === 'xlsb'): ?>
                        <div class="mt-3">
                            <small class="opacity-75">
                                <i class="bi bi-info-circle me-1"></i>
                                Требует Excel 2007 или новее для открытия
                            </small>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($share['max_downloads']): ?>
                        <div class="mt-3">
                            <small class="opacity-75">
                                Скачиваний: <?= $share['download_count'] ?> из <?= $share['max_downloads'] ?>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Подвал -->
                <div class="text-center text-muted small mt-4">
                    <p class="mb-0">
                        <i class="bi bi-shield-check me-1"></i>
                        Безопасное скачивание файлов
                    </p>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Если это документ и предпросмотр включен, но еще не сгенерирован -->
    <?php if ($share['allow_preview'] && $share['is_document'] && !$preview): ?>
    <script>
    // Пытаемся получить предпросмотр через AJAX
    const extension = '<?= strtolower(pathinfo($share['original_name'], PATHINFO_EXTENSION)) ?>';
    
    // Показываем загрузку для XLSB файлов
    if (extension === 'xlsb') {
        const previewContainer = document.querySelector('.file-preview-container');
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'text-center py-4';
        loadingDiv.innerHTML = `
            <div class="spinner-border text-success" role="status">
                <span class="visually-hidden">Загрузка...</span>
            </div>
            <p class="mt-3 text-muted">
                <i class="bi bi-file-earmark-excel me-2"></i>
                Обработка XLSB файла...
            </p>
            <small class="text-muted">
                Извлечение данных из Excel Binary формата может занять некоторое время
            </small>
        `;
        
        const existingPreview = document.querySelector('.preview-loading');
        if (existingPreview) {
            existingPreview.replaceWith(loadingDiv);
        }
    }
    
    fetch('/s/<?= $share['short_code'] ?>/preview')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.preview) {
                const previewElement = document.getElementById('documentPreview');
                if (previewElement) {
                    if (extension === 'xlsb') {
                        // Специальная обработка для XLSB
                        const lines = data.preview.split('\n').filter(line => line.trim());
                        
                        if (lines.length > 0) {
                            let tableHtml = '<div class="xlsb-preview-header mb-3">';
                            tableHtml += '<div class="d-flex align-items-center">';
                            tableHtml += '<i class="bi bi-file-earmark-excel text-success fs-4 me-2"></i>';
                            tableHtml += '<div><strong>Excel Binary Workbook Preview</strong>';
                            tableHtml += '<small class="text-muted d-block">Данные успешно извлечены</small></div>';
                            tableHtml += '</div></div>';
                            
                            tableHtml += '<div class="table-responsive">';
                            tableHtml += '<table class="table table-sm table-striped">';
                            
                            lines.slice(0, 20).forEach(line => {
                                const cells = line.split('\t').slice(0, 10); // Максимум 10 колонок
                                if (cells.some(cell => cell.trim())) {
                                    tableHtml += '<tr>';
                                    cells.forEach(cell => {
                                        tableHtml += `<td>${cell.trim()}</td>`;
                                    });
                                    tableHtml += '</tr>';
                                }
                            });
                            
                            tableHtml += '</table></div>';
                            
                            if (data.preview.length >= 4990) {
                                tableHtml += '<small class="text-muted mt-2 d-block">';
                                tableHtml += '<i class="bi bi-info-circle me-1"></i>';
                                tableHtml += 'Показаны первые строки таблицы. Скачайте файл для работы с полными данными.';
                                tableHtml += '</small>';
                            }
                            
                            previewElement.innerHTML = tableHtml;
                        }
                    } else {
                        // Обычная обработка для других документов
                        previewElement.textContent = data.preview;
                    }
                }
            } else {
                // Если предпросмотр не удался
                const errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-warning';
                errorDiv.innerHTML = `
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    ${extension === 'xlsb' ? 
                        'Не удалось обработать XLSB файл. Возможно, требуется установка дополнительных библиотек.' : 
                        'Предпросмотр недоступен для этого файла.'
                    }
                `;
                
                const existingPreview = document.querySelector('.preview-loading') || 
                                     document.querySelector('.text-center.py-4');
                if (existingPreview) {
                    existingPreview.replaceWith(errorDiv);
                }
            }
        })
        .catch(error => {
            console.log('Preview not available:', error);
            
            if (extension === 'xlsb') {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-info';
                errorDiv.innerHTML = `
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>XLSB Preview недоступен</strong><br>
                    <small>Для предпросмотра XLSB файлов требуется PhpSpreadsheet библиотека.</small>
                `;
                
                const existingPreview = document.querySelector('.text-center.py-4');
                if (existingPreview) {
                    existingPreview.replaceWith(errorDiv);
                }
            }
        });
    </script>
    <?php endif; ?>

</body>
</html>