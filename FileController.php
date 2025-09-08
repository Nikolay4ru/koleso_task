<?php
namespace App\Controllers;

use App\Models\File;
use App\Models\Task;

class FileController {
    private $db;
    private $file;
    private $task;
    
    public function __construct($db) {
        $this->db = $db;
        $this->file = new File($db);
        $this->task = new Task($db);
    }
    
    /**
     * Скачать файл
     */
    public function download($fileId) {
        $file = $this->file->findById($fileId);
        
        if (!$file) {
            http_response_code(404);
            require_once __DIR__ . '/../../views/errors/404.php';
            return;
        }
        
        // Проверяем права доступа
        if (!$this->checkFileAccess($file)) {
            http_response_code(403);
            echo "Доступ запрещен";
            return;
        }
        
        $filePath = $this->file->getFilePath($file);
        
        if (!file_exists($filePath)) {
            http_response_code(404);
            echo "Файл не найден";
            return;
        }
        
        // Отправляем файл
        header('Content-Type: ' . $file['mime_type']);
        header('Content-Disposition: attachment; filename="' . $file['original_name'] . '"');
        header('Content-Length: ' . $file['size']);
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        
        readfile($filePath);
        exit;
    }
    
    /**
     * Предпросмотр файла
     */
    public function preview($fileId) {
        $file = $this->file->findById($fileId);
        
        if (!$file) {
            http_response_code(404);
            return;
        }
        
        // Проверяем права доступа
        if (!$this->checkFileAccess($file)) {
            http_response_code(403);
            return;
        }
        
        // Для изображений используем миниатюру если есть
        if ($file['is_image'] && $file['thumbnail_path']) {
            $filePath = __DIR__ . '/../../uploads/' . $file['thumbnail_path'];
        } else {
            $filePath = $this->file->getFilePath($file);
        }
        
        if (!file_exists($filePath)) {
            http_response_code(404);
            return;
        }
        
        // Определяем заголовки в зависимости от типа файла
        $headers = [
            'Content-Type' => $file['mime_type'],
            'Cache-Control' => 'public, max-age=86400',
            'Pragma' => 'public'
        ];
        
        // Для некоторых типов файлов указываем inline
        $inlineTypes = ['image/', 'application/pdf', 'text/'];
        $isInline = false;
        
        foreach ($inlineTypes as $type) {
            if (strpos($file['mime_type'], $type) === 0) {
                $isInline = true;
                break;
            }
        }
        
        if ($isInline) {
            $headers['Content-Disposition'] = 'inline; filename="' . $file['original_name'] . '"';
        } else {
            $headers['Content-Disposition'] = 'attachment; filename="' . $file['original_name'] . '"';
        }
        
        // Отправляем заголовки
        foreach ($headers as $key => $value) {
            header("$key: $value");
        }
        
        readfile($filePath);
        exit;
    }
    
    /**
     * Удалить файл
     */
    public function delete($fileId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            return;
        }
        
        $file = $this->file->findById($fileId);
        
        if (!$file) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Файл не найден']);
            return;
        }
        
        // Проверяем права на удаление (только загрузивший или админ)
        if ($file['uploaded_by'] != $_SESSION['user_id'] && !$_SESSION['is_admin']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Недостаточно прав']);
            return;
        }
        
        try {
            $this->file->delete($fileId);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Ошибка при удалении файла']);
        }
    }
    
    /**
     * Загрузить файлы (AJAX)
     */
    public function upload() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            return;
        }
        
        header('Content-Type: application/json');
        
        if (empty($_FILES['files'])) {
            echo json_encode(['success' => false, 'error' => 'Файлы не выбраны']);
            return;
        }
        
        $type = $_POST['type'] ?? 'task';
        $uploadedFiles = [];
        $errors = [];
        
        // Обрабатываем каждый файл
        $files = $this->reArrayFiles($_FILES['files']);
        
        foreach ($files as $file) {
            try {
                $fileId = $this->file->upload($file, $_SESSION['user_id'], $type);
                $uploadedFile = $this->file->findById($fileId);
                
                $uploadedFiles[] = [
                    'id' => $fileId,
                    'name' => $uploadedFile['original_name'],
                    'size' => $this->file->formatFileSize($uploadedFile['size']),
                    'icon' => $this->file->getFileIcon($uploadedFile['mime_type']),
                    'is_image' => $uploadedFile['is_image'],
                    'thumbnail_url' => $uploadedFile['thumbnail_path'] ? $this->file->getThumbnailUrl($uploadedFile) : null,
                    'preview_url' => '/file/preview/' . $fileId,
                    'download_url' => '/file/download/' . $fileId
                ];
            } catch (\Exception $e) {
                $errors[] = $file['name'] . ': ' . $e->getMessage();
            }
        }
        
        echo json_encode([
            'success' => count($uploadedFiles) > 0,
            'files' => $uploadedFiles,
            'errors' => $errors
        ]);
    }
    
    /**
     * Проверить доступ к файлу
     */
    private function checkFileAccess($file) {
        // Админы имеют доступ ко всем файлам
        if ($_SESSION['is_admin']) {
            return true;
        }
        
        // Загрузивший имеет доступ
        if ($file['uploaded_by'] == $_SESSION['user_id']) {
            return true;
        }
        
        // Проверяем доступ через задачи
        $sql = "SELECT COUNT(*) as count
                FROM task_files tf
                JOIN tasks t ON tf.task_id = t.id
                LEFT JOIN task_assignees ta ON t.id = ta.task_id
                LEFT JOIN task_watchers tw ON t.id = tw.task_id
                WHERE tf.file_id = :file_id
                AND (t.creator_id = :user_id 
                     OR ta.user_id = :user_id2 
                     OR tw.user_id = :user_id3)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':file_id' => $file['id'],
            ':user_id' => $_SESSION['user_id'],
            ':user_id2' => $_SESSION['user_id'],
            ':user_id3' => $_SESSION['user_id']
        ]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($result['count'] > 0) {
            return true;
        }
        
        // Проверяем доступ через комментарии
        $sql = "SELECT COUNT(*) as count
                FROM comment_files cf
                JOIN task_comments tc ON cf.comment_id = tc.id
                JOIN tasks t ON tc.task_id = t.id
                LEFT JOIN task_assignees ta ON t.id = ta.task_id
                LEFT JOIN task_watchers tw ON t.id = tw.task_id
                WHERE cf.file_id = :file_id
                AND (t.creator_id = :user_id 
                     OR ta.user_id = :user_id2 
                     OR tw.user_id = :user_id3
                     OR tc.user_id = :user_id4)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':file_id' => $file['id'],
            ':user_id' => $_SESSION['user_id'],
            ':user_id2' => $_SESSION['user_id'],
            ':user_id3' => $_SESSION['user_id'],
            ':user_id4' => $_SESSION['user_id']
        ]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['count'] > 0;
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
     * Преобразовать массив $_FILES для множественной загрузки
     */
    private function reArrayFiles(&$filePost) {
        $fileArray = [];
        $fileCount = count($filePost['name']);
        $fileKeys = array_keys($filePost);
        
        for ($i = 0; $i < $fileCount; $i++) {
            foreach ($fileKeys as $key) {
                $fileArray[$i][$key] = $filePost[$key][$i];
            }
        }
        
        return $fileArray;
    }
}