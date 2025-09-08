<?php
namespace App\Controllers;

use App\Models\File;
use App\Models\FileShare;

class FileShareController {
    private $db;
    private $file;
    private $fileShare;
    
    public function __construct($db) {
        $this->db = $db;
        $this->file = new File($db);
        $this->fileShare = new FileShare($db);
    }
    
    /**
     * Создать короткую ссылку
     */
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            return;
        }
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
        
        header('Content-Type: application/json');
        
        $fileId = $_POST['file_id'] ?? null;
        if (!$fileId) {
            echo json_encode(['success' => false, 'error' => 'File ID required']);
            return;
        }
        
        // Проверяем существование файла и права доступа
        $file = $this->file->findById($fileId);
        if (!$file) {
            echo json_encode(['success' => false, 'error' => 'File not found']);
            return;
        }
        
        // Проверяем права доступа к файлу
        if (!$this->checkFileAccess($file)) {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            return;
        }
        
        try {
            $options = [
                'title' => $_POST['title'] ?? null,
                'description' => $_POST['description'] ?? null,
                'expires_at' => $_POST['expires_at'] ?? null,
                'password' => $_POST['password'] ?? null,
                'max_downloads' => $_POST['max_downloads'] ?? null,
                'allow_preview' => isset($_POST['allow_preview']) ? 1 : 0
            ];
            
            // Логирование для отладки
            error_log("Creating share for file ID: " . $fileId);
            error_log("User ID: " . $_SESSION['user_id']);
            error_log("Options: " . json_encode($options));
            
            $shortCode = $this->fileShare->createShare($fileId, $_SESSION['user_id'], $options);
            $shareUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/s/' . $shortCode;
            
            echo json_encode([
                'success' => true,
                'short_code' => $shortCode,
                'share_url' => $shareUrl
            ]);
            
        } catch (\Exception $e) {
            error_log("Share creation error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            echo json_encode([
                'success' => false, 
                'error' => 'Failed to create share: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Просмотр файла по короткой ссылке
     */
    public function view($shortCode) {
        $password = $_POST['password'] ?? $_GET['password'] ?? null;
        
        // Проверяем доступ
        $access = $this->fileShare->checkAccess($shortCode, $password);
        
        if (!$access['success']) {
            if (isset($access['requires_password'])) {
                // Показываем форму ввода пароля
                require_once __DIR__ . '/../../views/shares/password.php';
                return;
            } else {
                http_response_code(404);
                require_once __DIR__ . '/../../views/errors/404.php';
                return;
            }
        }
        
        $share = $access['share'];
        
        // Логируем просмотр (с проверкой ошибок)
        $logResult = $this->fileShare->logAccess(
            $share['id'], 
            'view',
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        );
        
        if (!$logResult) {
            error_log("Failed to log access for share ID: " . $share['id']);
        }
        
        // Генерируем предпросмотр если нужно
        $preview = null;
        if ($share['allow_preview'] && $share['is_document']) {
            $preview = $this->file->getDocumentPreview($share['file_id']);
        }
        
        // Создаем вспомогательные функции для шаблона
        $formatFileSize = function($bytes) {
            if ($bytes >= 1073741824) {
                return number_format($bytes / 1073741824, 2) . ' GB';
            } elseif ($bytes >= 1048576) {
                return number_format($bytes / 1048576, 2) . ' MB';
            } elseif ($bytes >= 1024) {
                return number_format($bytes / 1024, 2) . ' KB';
            } else {
                return $bytes . ' B';
            }
        };
        
        $getFileIcon = function($mimeType) {
            $icons = [
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'bi-file-earmark-word',
                'application/msword' => 'bi-file-earmark-word',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'bi-file-earmark-excel',
                'application/vnd.ms-excel' => 'bi-file-earmark-excel',
                'application/vnd.ms-excel.sheet.binary.macroEnabled.12' => 'bi-file-earmark-excel',
                'application/pdf' => 'bi-file-earmark-pdf',
                'image/jpeg' => 'bi-file-earmark-image',
                'image/png' => 'bi-file-earmark-image',
                'text/plain' => 'bi-file-earmark-text',
                'application/zip' => 'bi-file-earmark-zip',
            ];
            return $icons[$mimeType] ?? 'bi-file-earmark';
        };
        
        require_once __DIR__ . '/../../views/shares/view.php';
    }
    
    /**
     * Предпросмотр файла по короткой ссылке
     */
    public function preview($shortCode) {
        $password = $_POST['password'] ?? $_GET['password'] ?? null;
        
        // Проверяем доступ
        $access = $this->fileShare->checkAccess($shortCode, $password);
        
        if (!$access['success']) {
            http_response_code(403);
            echo json_encode(['error' => $access['error']]);
            return;
        }
        
        $share = $access['share'];
        
        if (!$share['allow_preview']) {
            http_response_code(403);
            echo json_encode(['error' => 'Preview not allowed']);
            return;
        }
        
        // Логируем предпросмотр (с проверкой ошибок)
        $this->fileShare->logAccess(
            $share['id'], 
            'preview',
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        );
        
        // Для изображений отдаем файл напрямую
        if ($share['is_image']) {
            $filePath = $this->file->getFilePath($share);
            
            if ($share['thumbnail_path']) {
                $filePath = __DIR__ . '/../../uploads/' . $share['thumbnail_path'];
            }
            
            if (file_exists($filePath)) {
                header('Content-Type: ' . $share['mime_type']);
                header('Cache-Control: public, max-age=3600');
                readfile($filePath);
                exit;
            }
        }
        
        // Для документов возвращаем JSON с предпросмотром
        if ($share['is_document']) {
            $preview = $this->file->getDocumentPreview($share['file_id']);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'preview' => $preview,
                'filename' => $share['original_name'],
                'mime_type' => $share['mime_type']
            ]);
            exit;
        }
        
        http_response_code(404);
        echo json_encode(['error' => 'Preview not available']);
    }
    
    /**
     * Скачивание файла по короткой ссылке
     */
    public function download($shortCode) {
        $password = $_POST['password'] ?? $_GET['password'] ?? null;
        
        // Проверяем доступ
        $access = $this->fileShare->checkAccess($shortCode, $password);
        
        if (!$access['success']) {
            if (isset($access['requires_password'])) {
                header('Location: /s/' . $shortCode);
                return;
            } else {
                http_response_code(404);
                require_once __DIR__ . '/../../views/errors/404.php';
                return;
            }
        }
        
        $share = $access['share'];
        $filePath = $this->file->getFilePath($share);
        
        if (!file_exists($filePath)) {
            http_response_code(404);
            echo "Файл не найден";
            return;
        }
        
        // Логируем скачивание (с проверкой ошибок)
        $this->fileShare->logAccess(
            $share['id'], 
            'download',
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        );
        
        // Отправляем файл
        header('Content-Type: ' . $share['mime_type']);
        header('Content-Disposition: attachment; filename="' . $share['original_name'] . '"');
        header('Content-Length: ' . $share['size']);
        header('Cache-Control: no-cache, must-revalidate');
        
        readfile($filePath);
        exit;
    }
    
    /**
     * Список ссылок пользователя
     */
    public function myShares() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            return;
        }
        
        $shares = $this->fileShare->getUserShares($_SESSION['user_id']);
        
        // Создаем вспомогательные функции
        $formatFileSize = function($bytes) {
            if ($bytes >= 1073741824) {
                return number_format($bytes / 1073741824, 2) . ' GB';
            } elseif ($bytes >= 1048576) {
                return number_format($bytes / 1048576, 2) . ' MB';
            } elseif ($bytes >= 1024) {
                return number_format($bytes / 1024, 2) . ' KB';
            } else {
                return $bytes . ' B';
            }
        };
        
        $getFileIcon = function($mimeType) {
            $icons = [
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'bi-file-earmark-word',
                'application/msword' => 'bi-file-earmark-word',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'bi-file-earmark-excel',
                'application/vnd.ms-excel' => 'bi-file-earmark-excel',
                'application/vnd.ms-excel.sheet.binary.macroEnabled.12' => 'bi-file-earmark-excel',
                'application/pdf' => 'bi-file-earmark-pdf',
                'image/jpeg' => 'bi-file-earmark-image',
                'image/png' => 'bi-file-earmark-image',
                'text/plain' => 'bi-file-earmark-text',
                'application/zip' => 'bi-file-earmark-zip',
            ];
            return $icons[$mimeType] ?? 'bi-file-earmark';
        };
        
        require_once __DIR__ . '/../../views/shares/list.php';
    }
    
    /**
     * Статистика ссылки
     */
    public function stats($shortCode) {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        header('Content-Type: application/json');
        
        $stats = $this->fileShare->getShareStats($shortCode, $_SESSION['user_id']);
        if (!$stats) {
            http_response_code(404);
            echo json_encode(['error' => 'Share not found']);
            return;
        }
        
        echo json_encode(['success' => true, 'stats' => $stats]);
    }
    
    /**
     * Деактивировать ссылку
     */
    public function deactivate($shortCode) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        header('Content-Type: application/json');
        
        $success = $this->fileShare->deactivateShare($shortCode, $_SESSION['user_id']);
        echo json_encode(['success' => $success]);
    }
    
    /**
     * Удалить ссылку
     */
    public function delete($shortCode) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        header('Content-Type: application/json');
        
        $success = $this->fileShare->deleteShare($shortCode, $_SESSION['user_id']);
        echo json_encode(['success' => $success]);
    }
    
    /**
     * Генерировать предпросмотр для файла
     */
    public function generatePreview($fileId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        header('Content-Type: application/json');
        
        $file = $this->file->findById($fileId);
        if (!$file) {
            echo json_encode(['success' => false, 'error' => 'File not found']);
            return;
        }
        
        if (!$this->checkFileAccess($file)) {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            return;
        }
        
        try {
            $preview = $this->file->generateDocumentPreview($fileId);
            echo json_encode([
                'success' => true,
                'preview' => $preview
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to generate preview'
            ]);
        }
    }
    
    /**
     * Показать страницу загрузки файла и создания ссылки
     */
    public function uploadPage() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            return;
        }
        
        require_once __DIR__ . '/../../views/shares/upload.php';
    }
    
    /**
     * Форматировать размер файла
     */
    public static function formatFileSize($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }
    
    /**
     * Получить иконку файла по MIME типу
     */
    public static function getFileIcon($mimeType) {
        $icons = [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'bi-file-earmark-word',
            'application/msword' => 'bi-file-earmark-word',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'bi-file-earmark-excel',
            'application/vnd.ms-excel' => 'bi-file-earmark-excel',
            'application/vnd.ms-excel.sheet.binary.macroEnabled.12' => 'bi-file-earmark-excel', // XLSB
            'application/pdf' => 'bi-file-earmark-pdf',
            'image/jpeg' => 'bi-file-earmark-image',
            'image/png' => 'bi-file-earmark-image',
            'text/plain' => 'bi-file-earmark-text',
            'application/zip' => 'bi-file-earmark-zip',
        ];
        return $icons[$mimeType] ?? 'bi-file-earmark';
    }
    
    /**
     * Проверить доступ к файлу
     */
    private function checkFileAccess($file) {
        // Если файл загружен текущим пользователем, разрешаем доступ
        if ($file['uploaded_by'] == $_SESSION['user_id']) {
            return true;
        }
        
        // Проверяем, связан ли файл с задачами, к которым у пользователя есть доступ
        $sql = "SELECT COUNT(*) as count FROM task_files tf
                JOIN tasks t ON tf.task_id = t.id
                LEFT JOIN task_assignees ta ON t.id = ta.task_id
                LEFT JOIN task_watchers tw ON t.id = tw.task_id
                WHERE tf.file_id = :file_id AND (
                    t.creator_id = :user_id1 OR 
                    ta.user_id = :user_id2 OR 
                    tw.user_id = :user_id3
                )";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':file_id' => $file['id'],
            ':user_id1' => $_SESSION['user_id'],
            ':user_id2' => $_SESSION['user_id'],
            ':user_id3' => $_SESSION['user_id']
        ]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }
}