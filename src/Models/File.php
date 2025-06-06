<?php
namespace App\Models;

use PDO;

class File {
    private $db;
    private $uploadDir;
    private $maxFileSize = 10485760; // 10MB по умолчанию
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
        $subdirs = ['tasks', 'comments', 'thumbnails'];
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
    public function getFileIcon($mimeType) {
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
}