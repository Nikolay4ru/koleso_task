<?php
namespace App\Models;

use PDO;

class File {
    private $db;
    private $uploadDir;
    private $maxFileSize = 200485760; // 10MB по умолчанию
    private $allowedTypes = [
        // Изображения
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        // Документы
        'application/pdf', 'application/msword', 
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel', 
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.ms-excel.sheet.binary.macroEnabled.12', // XLSB формат
    
        // Текстовые файлы
        'text/plain', 'text/csv', 'text/html', 'text/css', 'text/javascript',
        // Архивы
        'application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed',
        // Видео
        'video/mp4', 'video/mpeg', 'video/quicktime', 'video/x-msvideo',
        // Аудио
        'audio/mpeg', 'audio/wav', 'audio/ogg'
    ];
    
public function __construct(PDO $db) {
    $this->db = $db;
    $this->uploadDir = __DIR__ . '/../../uploads/';
    
    // Создаем директорию для загрузок если её нет
    if (!is_dir($this->uploadDir)) {
        mkdir($this->uploadDir, 0755, true);
    }
    
    // Создаем поддиректории
    $subdirs = ['tasks', 'comments', 'thumbnails', 'share']; // Добавили 'share'
    foreach ($subdirs as $dir) {
        if (!is_dir($this->uploadDir . $dir)) {
            mkdir($this->uploadDir . $dir, 0755, true);
        }
    }
}
    
    /**
     * Загрузить файл
     */
    public function upload($file, $uploadedBy, $type = 'task') {
        // Валидация
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception('Ошибка при загрузке файла');
        }
        
        if ($file['size'] > $this->maxFileSize) {
            throw new \Exception('Файл слишком большой. Максимальный размер: ' . ($this->maxFileSize / 1024 / 1024) . 'MB');
        }
        
        $mimeType = mime_content_type($file['tmp_name']);
        if (!in_array($mimeType, $this->allowedTypes)) {
            throw new \Exception('Недопустимый тип файла');
        }
        
        // Генерируем уникальное имя файла
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $relativePath = $type . '/' . date('Y/m/') . $filename;
        $fullPath = $this->uploadDir . $relativePath;
        
        // Создаем директорию если её нет
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Перемещаем файл
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            throw new \Exception('Не удалось сохранить файл');
        }
        
        // Определяем тип файла
        $isImage = strpos($mimeType, 'image/') === 0;
        $isDocument = in_array($mimeType, [
            'application/pdf', 'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel.sheet.binary.macroEnabled.12', // Добавили XLSB
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation'
        ]);
        
        // Создаем миниатюру для изображений
        $thumbnailPath = null;
        if ($isImage) {
            $thumbnailPath = $this->createThumbnail($fullPath, $relativePath);
        }
        
        // Сохраняем в БД
        $sql = "INSERT INTO files (filename, original_name, mime_type, size, path, thumbnail_path, is_image, is_document, uploaded_by) 
                VALUES (:filename, :original_name, :mime_type, :size, :path, :thumbnail_path, :is_image, :is_document, :uploaded_by)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':filename' => $filename,
            ':original_name' => $file['name'],
            ':mime_type' => $mimeType,
            ':size' => $file['size'],
            ':path' => $relativePath,
            ':thumbnail_path' => $thumbnailPath,
            ':is_image' => $isImage ? 1 : 0,
            ':is_document' => $isDocument ? 1 : 0,
            ':uploaded_by' => $uploadedBy
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Создать миниатюру для изображения
     */
    private function createThumbnail($sourcePath, $relativePath) {
        $maxWidth = 300;
        $maxHeight = 300;
        
        // Получаем информацию об изображении
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return null;
        }
        
        list($width, $height, $type) = $imageInfo;
        
        // Вычисляем новые размеры
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = round($width * $ratio);
        $newHeight = round($height * $ratio);
        
        // Создаем изображение из файла
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($sourcePath);
                break;
            case IMAGETYPE_WEBP:
                $source = imagecreatefromwebp($sourcePath);
                break;
            default:
                return null;
        }
        
        if (!$source) {
            return null;
        }
        
        // Создаем пустое изображение для миниатюры
        $thumb = imagecreatetruecolor($newWidth, $newHeight);
        
        // Сохраняем прозрачность для PNG и GIF
        if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
            imagecolortransparent($thumb, imagecolorallocate($thumb, 0, 0, 0));
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
        }
        
        // Изменяем размер
        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Сохраняем миниатюру
        $thumbPath = 'thumbnails/' . $relativePath;
        $fullThumbPath = $this->uploadDir . $thumbPath;
        
        $dir = dirname($fullThumbPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($thumb, $fullThumbPath, 85);
                break;
            case IMAGETYPE_PNG:
                imagepng($thumb, $fullThumbPath, 8);
                break;
            case IMAGETYPE_GIF:
                imagegif($thumb, $fullThumbPath);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($thumb, $fullThumbPath, 85);
                break;
        }
        
        imagedestroy($source);
        imagedestroy($thumb);
        
        return $thumbPath;
    }
    
    /**
     * Прикрепить файл к задаче
     */
    public function attachToTask($fileId, $taskId) {
        $sql = "INSERT INTO task_files (task_id, file_id) VALUES (:task_id, :file_id)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':task_id' => $taskId,
            ':file_id' => $fileId
        ]);
    }
    
    /**
     * Прикрепить файл к комментарию
     */
    public function attachToComment($fileId, $commentId) {
        $sql = "INSERT INTO comment_files (comment_id, file_id) VALUES (:comment_id, :file_id)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':comment_id' => $commentId,
            ':file_id' => $fileId
        ]);
    }
    
    /**
     * Получить файлы задачи
     */
    public function getTaskFiles($taskId) {
        $sql = "SELECT f.*, u.name as uploaded_by_name 
                FROM files f
                JOIN task_files tf ON f.id = tf.file_id
                JOIN users u ON f.uploaded_by = u.id
                WHERE tf.task_id = :task_id
                ORDER BY tf.attached_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':task_id' => $taskId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Получить файлы комментария
     */
    public function getCommentFiles($commentId) {
        $sql = "SELECT f.*, u.name as uploaded_by_name 
                FROM files f
                JOIN comment_files cf ON f.id = cf.file_id
                JOIN users u ON f.uploaded_by = u.id
                WHERE cf.comment_id = :comment_id
                ORDER BY cf.attached_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':comment_id' => $commentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Получить файл по ID
     */
    public function findById($id) {
        $sql = "SELECT f.*, u.name as uploaded_by_name 
                FROM files f
                JOIN users u ON f.uploaded_by = u.id
                WHERE f.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Удалить файл
     */
    public function delete($id) {
        // Получаем информацию о файле
        $file = $this->findById($id);
        if (!$file) {
            return false;
        }
        
        // Удаляем физические файлы
        $filePath = $this->uploadDir . $file['path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        if ($file['thumbnail_path']) {
            $thumbPath = $this->uploadDir . $file['thumbnail_path'];
            if (file_exists($thumbPath)) {
                unlink($thumbPath);
            }
        }
        
        // Удаляем из БД
        $sql = "DELETE FROM files WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Получить путь к файлу
     */
    public function getFilePath($file) {
        return $this->uploadDir . $file['path'];
    }
    
    /**
     * Получить URL файла
     */
    public function getFileUrl($file) {
        return '/uploads/' . $file['path'];
    }
    
    /**
     * Получить URL миниатюры
     */
    public function getThumbnailUrl($file) {
        if ($file['thumbnail_path']) {
            return '/uploads/' . $file['thumbnail_path'];
        }
        return null;
    }
    
    /**
     * Форматировать размер файла
     */
    public function formatFileSize($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
    
    /**
     * Получить иконку для типа файла
     */
    public function getFileIcon1($mimeType) {
        if (strpos($mimeType, 'image/') === 0) {
            return 'bi-file-earmark-image';
        } elseif ($mimeType === 'application/pdf') {
            return 'bi-file-earmark-pdf';
        } elseif (strpos($mimeType, 'word') !== false) {
            return 'bi-file-earmark-word';
        } elseif (strpos($mimeType, 'excel') !== false || strpos($mimeType, 'spreadsheet') !== false) {
            return 'bi-file-earmark-excel';
        } elseif (strpos($mimeType, 'powerpoint') !== false || strpos($mimeType, 'presentation') !== false) {
            return 'bi-file-earmark-ppt';
        } elseif (strpos($mimeType, 'text/') === 0) {
            return 'bi-file-earmark-text';
        } elseif (strpos($mimeType, 'video/') === 0) {
            return 'bi-file-earmark-play';
        } elseif (strpos($mimeType, 'audio/') === 0) {
            return 'bi-file-earmark-music';
        } elseif (strpos($mimeType, 'zip') !== false || strpos($mimeType, 'rar') !== false || strpos($mimeType, '7z') !== false) {
            return 'bi-file-earmark-zip';
        } else {
            return 'bi-file-earmark';
        }
    }
    
    /**
     * Получить статистику по файлам пользователя
     */
    public function getUserFilesStats($userId) {
        $sql = "SELECT 
                COUNT(*) as total_files,
                SUM(size) as total_size,
                COUNT(CASE WHEN is_image = 1 THEN 1 END) as image_count,
                COUNT(CASE WHEN is_document = 1 THEN 1 END) as document_count
                FROM files
                WHERE uploaded_by = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

/**
 * Генерировать предпросмотр документа
 */
public function generateDocumentPreview($fileId) {
    $file = $this->findById($fileId);
    if (!$file || !$file['is_document']) {
        return false;
    }
    
    $filePath = $this->getFilePath($file);
    $preview = '';
    
    try {
        switch ($file['mime_type']) {
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                $preview = $this->extractWordContent($filePath);
                break;
                
            case 'application/msword':
                $preview = $this->extractDocContent($filePath);
                break;
                
            case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
                $preview = $this->extractExcelContent($filePath);
                break;
                
            case 'application/vnd.ms-excel':
                $preview = $this->extractXlsContent($filePath);
                break;

            // Новая поддержка XLSB
            case 'application/vnd.ms-excel.sheet.binary.macroEnabled.12':
                $preview = $this->extractXlsbContent($filePath);
                break;
                
            case 'application/pdf':
                $preview = $this->extractPdfContent($filePath);
                break;
                
            case 'text/plain':
            case 'text/csv':
                $preview = file_get_contents($filePath);
                break;
        }
        
        // Сохраняем предпросмотр в БД
        if ($preview) {
            $this->saveDocumentPreview($fileId, $preview);
        }
        
        return $preview;
        
    } catch (\Exception $e) {
        error_log("Preview generation error for file {$fileId}: " . $e->getMessage());
        return false;
    }
}

/**
 * Извлечь содержимое из Word документа (.docx)
 */
private function extractWordContent($filePath) {
    if (!class_exists('ZipArchive')) {
        throw new \Exception('ZipArchive extension required');
    }
    
    $zip = new \ZipArchive();
    if ($zip->open($filePath) !== TRUE) {
        throw new \Exception('Cannot open Word document');
    }
    
    $content = '';
    
    // Читаем основной документ
    $xml = $zip->getFromName('word/document.xml');
    if ($xml) {
        // Простая обработка XML для извлечения текста
        $xml = simplexml_load_string($xml);
        if ($xml) {
            $namespaces = $xml->getNamespaces(true);
            $xml->registerXPathNamespace('w', $namespaces['w'] ?? 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            
            $textNodes = $xml->xpath('//w:t');
            foreach ($textNodes as $textNode) {
                $content .= (string)$textNode . ' ';
            }
        }
    }
    
    $zip->close();
    return trim($content);
}



/**
 * Извлечь содержимое из XLSB файла с использованием PhpSpreadsheet
 */
private function extractXlsbContent($filePath) {
    // Проверяем, установлен ли PhpSpreadsheet
    if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
        error_log("PhpSpreadsheet not found for XLSB processing");
        return "Для обработки XLSB файлов требуется установка PhpSpreadsheet.\nВыполните: composer require phpoffice/phpspreadsheet";
    }
    
    try {
        // Увеличиваем лимит памяти для обработки больших файлов
        $originalMemoryLimit = ini_get('memory_limit');
        ini_set('memory_limit', '512M');
        
        // Создаем reader для XLSB
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsb');
        
        // Настройки для экономии памяти
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        
        // Загружаем только первый лист
        $worksheetNames = $reader->listWorksheetNames($filePath);
        if (!empty($worksheetNames)) {
            $reader->setLoadSheetsOnly([$worksheetNames[0]]);
        }
        
        // Загружаем файл
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        
        $content = '';
        $maxRows = min(50, $worksheet->getHighestRow()); // Ограничиваем количество строк
        $highestColumn = $worksheet->getHighestColumn();
        $maxCols = min(15, \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn));
        
        // Добавляем информацию о файле
        $content .= "=== XLSB File Preview ===\n";
        $content .= "Worksheet: " . $worksheet->getTitle() . "\n";
        $content .= "Total Rows: " . $worksheet->getHighestRow() . "\n";
        $content .= "Total Columns: " . $highestColumn . "\n";
        $content .= "Preview: First {$maxRows} rows x {$maxCols} columns\n";
        $content .= "=" . str_repeat("=", 50) . "\n\n";
        
        // Извлекаем данные
        for ($row = 1; $row <= $maxRows; $row++) {
            $rowData = [];
            $hasData = false;
            
            for ($col = 1; $col <= $maxCols; $col++) {
                try {
                    $cell = $worksheet->getCellByColumnAndRow($col, $row);
                    $value = '';
                    
                    if ($cell !== null) {
                        // Пытаемся получить вычисленное значение
                        try {
                            $value = $cell->getCalculatedValue();
                        } catch (\Exception $e) {
                            // Если не получается вычислить, берем отформатированное значение
                            $value = $cell->getFormattedValue();
                        }
                        
                        // Обрабатываем различные типы данных
                        if ($value instanceof \DateTime) {
                            $value = $value->format('Y-m-d H:i:s');
                        } elseif (is_object($value)) {
                            $value = (string) $value;
                        }
                        
                        $value = trim((string) $value);
                    }
                    
                    if ($value !== '') {
                        $hasData = true;
                    }
                    
                    $rowData[] = $value;
                } catch (\Exception $e) {
                    $rowData[] = '';
                    error_log("XLSB cell error at row {$row}, col {$col}: " . $e->getMessage());
                }
            }
            
            // Добавляем строку только если в ней есть данные
            if ($hasData) {
                $content .= implode("\t", $rowData) . "\n";
            }
            
            // Проверяем лимит размера предпросмотра
            if (strlen($content) > 4000) {
                $content .= "\n[... Показана часть данных. Скачайте файл для полного просмотра ...]\n";
                break;
            }
        }
        
        // Очищаем память
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        
        // Восстанавливаем лимит памяти
        ini_set('memory_limit', $originalMemoryLimit);
        
        if (empty(trim($content))) {
            return "XLSB файл загружен, но не содержит видимых данных или данные находятся за пределами первых {$maxRows} строк.";
        }
        
        return $content;
        
    } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
        error_log("PhpSpreadsheet XLSB reader error: " . $e->getMessage());
        return "Ошибка чтения XLSB файла: " . $e->getMessage() . "\nВозможно, файл поврежден или имеет неподдерживаемый формат.";
        
    } catch (\Exception $e) {
        error_log("General XLSB processing error: " . $e->getMessage());
        
        // Пытаемся альтернативный способ
        return $this->extractXlsbAlternative($filePath);
    } finally {
        // Всегда восстанавливаем лимит памяти
        if (isset($originalMemoryLimit)) {
            ini_set('memory_limit', $originalMemoryLimit);
        }
    }
}


/**
 * Извлечь содержимое из старого формата Word (.doc)
 */
private function extractDocContent($filePath) {
    // Для .doc файлов можно использовать antiword или другие инструменты
    // Или библиотеку PhpWord если доступна
    
    if (function_exists('exec') && $this->checkCommand('antiword')) {
        $output = [];
        $command = "antiword " . escapeshellarg($filePath);
        exec($command, $output);
        return implode("\n", $output);
    }
    
    return "Предпросмотр недоступен для .doc файлов";
}

/**
 * Извлечь содержимое из Excel документа (.xlsx)
 */
private function extractExcelContent($filePath) {
    if (!class_exists('ZipArchive')) {
        throw new \Exception('ZipArchive extension required');
    }
    
    $zip = new \ZipArchive();
    if ($zip->open($filePath) !== TRUE) {
        throw new \Exception('Cannot open Excel document');
    }
    
    $content = '';
    
    // Читаем shared strings
    $sharedStrings = [];
    $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedStringsXml) {
        $xml = simplexml_load_string($sharedStringsXml);
        if ($xml) {
            foreach ($xml->si as $si) {
                $sharedStrings[] = (string)$si->t;
            }
        }
    }
    
    // Читаем первый лист
    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    if ($sheetXml) {
        $xml = simplexml_load_string($sheetXml);
        if ($xml) {
            foreach ($xml->sheetData->row as $row) {
                $rowContent = [];
                foreach ($row->c as $cell) {
                    $value = '';
                    if (isset($cell->v)) {
                        $cellValue = (string)$cell->v;
                        
                        // Если это ссылка на shared string
                        if (isset($cell['t']) && $cell['t'] == 's') {
                            $value = $sharedStrings[$cellValue] ?? $cellValue;
                        } else {
                            $value = $cellValue;
                        }
                    }
                    if ($value) {
                        $rowContent[] = $value;
                    }
                }
                if (!empty($rowContent)) {
                    $content .= implode("\t", $rowContent) . "\n";
                }
            }
        }
    }
    
    $zip->close();
    return $content;
}

/**
 * Извлечь содержимое из старого Excel (.xls)
 */
private function extractXlsContent($filePath) {
    return "Предпросмотр недоступен для .xls файлов";
}

/**
 * Извлечь содержимое из PDF
 */
private function extractPdfContent($filePath) {
    if (function_exists('exec') && $this->checkCommand('pdftotext')) {
        $output = [];
        $command = "pdftotext " . escapeshellarg($filePath) . " -";
        exec($command, $output);
        return implode("\n", $output);
    }
    
    return "Предпросмотр PDF требует установки pdftotext";
}

/**
 * Проверить доступность команды в системе
 */
private function checkCommand($command) {
    $output = [];
    $return_var = 0;
    exec("which $command", $output, $return_var);
    return $return_var === 0;
}

/**
 * Сохранить предпросмотр документа
 */
private function saveDocumentPreview($fileId, $preview) {
    // Ограничиваем размер предпросмотра
    $maxPreviewLength = 5000;
    if (strlen($preview) > $maxPreviewLength) {
        $preview = substr($preview, 0, $maxPreviewLength) . '...';
    }
    
    $sql = "UPDATE files SET document_preview = :preview, preview_generated = 1 WHERE id = :id";
    $stmt = $this->db->prepare($sql);
    return $stmt->execute([
        ':preview' => $preview,
        ':id' => $fileId
    ]);
}

/**
 * Получить предпросмотр документа
 */
public function getDocumentPreview($fileId) {
    $sql = "SELECT document_preview, preview_generated FROM files WHERE id = :id";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([':id' => $fileId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        return null;
    }
    
    // Если предпросмотр еще не сгенерирован, генерируем его
    if (!$result['preview_generated']) {
        $preview = $this->generateDocumentPreview($fileId);
        return $preview;
    }
    
    return $result['document_preview'];
}

/**
 * Получить иконку файла по MIME типу
 */
public function getFileIcon($mimeType) {
    $icons = [
        // Документы Word
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'bi-file-earmark-word',
        'application/msword' => 'bi-file-earmark-word',
        
        // Документы Excel
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'bi-file-earmark-excel',
        'application/vnd.ms-excel' => 'bi-file-earmark-excel',
        
        // PowerPoint
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'bi-file-earmark-ppt',
        'application/vnd.ms-powerpoint' => 'bi-file-earmark-ppt',
        
        // PDF
        'application/pdf' => 'bi-file-earmark-pdf',
        
        // Изображения
        'image/jpeg' => 'bi-file-earmark-image',
        'image/jpg' => 'bi-file-earmark-image',
        'image/png' => 'bi-file-earmark-image',
        'image/gif' => 'bi-file-earmark-image',
        'image/webp' => 'bi-file-earmark-image',
        
        // Текстовые файлы
        'text/plain' => 'bi-file-earmark-text',
        'text/csv' => 'bi-file-earmark-text',
        
        // Архивы
        'application/zip' => 'bi-file-earmark-zip',
        'application/x-rar-compressed' => 'bi-file-earmark-zip',
        
        // Видео
        'video/mp4' => 'bi-file-earmark-play',
        'video/mpeg' => 'bi-file-earmark-play',
        
        // Аудио
        'audio/mpeg' => 'bi-file-earmark-music',
        'audio/wav' => 'bi-file-earmark-music',
    ];
    
    return $icons[$mimeType] ?? 'bi-file-earmark';
}
}